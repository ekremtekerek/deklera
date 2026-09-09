<?php
/**
 * Barındırılan doğrulama servisi istemcisi.
 *
 * @package Deklera
 */

declare( strict_types = 1 );

namespace Deklera\Validation;

use Deklera\Invoice\Profile;

use Deklera\License\Licensing;
use Deklera\Queue\Scheduler;

defined( 'ABSPATH' ) || exit;

/**
 * Belgeyi resmi EN 16931 kural setine göre doğrulatır.
 *
 * Doğrulama neden burada değil de uzakta: EN 16931 kural setleri XSLT 2.0'a
 * derlenir, PHP'nin ext-xsl uzantısı ise XSLT 1.0'da kalır. Kütüphane
 * seviyesindeki kontroller yalnızca yapısaldır (şema ve tamlık), alıcının
 * dayattığı 200'den fazla iş kuralı değil.
 *
 * Bu kısıt ürünün lisans korumasıdır: null'lanmış bir kopya doğrulama
 * yapamaz, yani işe yaramaz. Bkz. docs/adr/0003-dogrulama-calisma-ortami.md
 */
final class HostedValidator {

	/**
	 * Servis adresinin saklandığı seçenek.
	 */
	public const OPTION_ENDPOINT = 'deklera_validator_endpoint';

	/**
	 * Lisans anahtarının saklandığı seçenek.
	 */
	public const OPTION_KEY = 'deklera_validator_key';

	/**
	 * Doğrulama servisinin varsayılan adresi.
	 *
	 * NEDEN GÖMÜLÜ
	 *
	 * Adres bir sır değil; kapıyı anahtar tutuyor. Boş bırakıldığında Pro'yu
	 * satın alan kişi ayarlarda iki boş alan ve `validator.example.com` gibi
	 * bir yer tutucu görüyordu — ne yazacağını söyleyen hiçbir şey yoktu.
	 * Parayı ödedikten sonra karşılaşılacak en kötü ekran budur.
	 *
	 * Kendi kopyasını çalıştırmak isteyen (kurumsal müşteri, veri ikametgâhı)
	 * ayardan ya da `deklera/validator_endpoint` süzgecinden değiştirebilir;
	 * servis açık kaynak, kurulumu validator/README.md'de.
	 */
	public const DEFAULT_ENDPOINT = 'https://konform-validator.onrender.com';

	/**
	 * Etkileşimli istekte zaman aşımı, saniye.
	 *
	 * Bir yönetici ekran başında bekliyor. Doğrulamanın kendisi ısınmış
	 * serviste 100–300 ms sürer; 15 saniye ağ gecikmesi için fazlasıyla
	 * yeterlidir ve ekranı kilitlemez.
	 */
	private const TIMEOUT_INTERACTIVE = 15;

	/**
	 * Arka plan işinde zaman aşımı, saniye.
	 *
	 * Uykuya dalan barındırmalarda (Render'ın ücretsiz katmanı gibi) ilk
	 * isteğin uyanması 50 saniyeyi bulabiliyor. Kuyrukta kimse ekran başında
	 * beklemediği için burada beklemek serbesttir; 15 saniyede kesmek,
	 * uyanmakta olan bir servisi erişilemez saymak olurdu.
	 */
	private const TIMEOUT_BACKGROUND = 90;

	/**
	 * Bir kez daha denenecek HTTP durumları.
	 *
	 * Bunlar servisin ayakta olmadığını değil, o an cevap veremediğini
	 * gösterir. 404 listede çünkü uykuya dalan barındırmalarda geçiş
	 * anında kenar sunucu bunu döndürüyor.
	 *
	 * @var int[]
	 */
	private const RETRYABLE = array( 404, 500, 502, 503, 504 );

	/**
	 * Yeniden denemeden önce beklenecek süre, saniye.
	 */
	private const RETRY_PAUSE = 3;

	/**
	 * Doğrulama yapılabilir durumda mı.
	 *
	 * @return bool
	 */
	public function is_configured(): bool {
		if ( ! Licensing::has_hosted_validation() ) {
			return false;
		}

		return '' !== $this->endpoint() && '' !== $this->key();
	}

	/**
	 * Belgeyi doğrular.
	 *
	 * @param string  $xml     Fatura XML'i.
	 * @param Profile $profile Belge profili; ulusal kural setini seçer.
	 * @return ValidationResult
	 */
	public function validate( string $xml, ?Profile $profile = null ): ValidationResult {
		if ( ! $this->is_configured() ) {
			return ValidationResult::skipped();
		}

		$response = $this->request( $xml, $this->ruleset( $profile ) );

		/*
		 * Zaman asimi ve baglanti hatasi WP_Error olarak gelir, HTTP durumu
		 * olarak degil. Burada bir kez daha denenir ve sebebi olculdu:
		 *
		 * Render'in ucretsiz katmani ~15 dakika bos kalinca uyuyor. Uyandirma
		 * istegi 12-20 saniye suruyor, yani etkilesimli butceyi (15 sn) asip
		 * "0 bytes received" ile dusuyor. Hemen ardindan gelen ikinci istek
		 * ISINMIS servisi buluyor ve 2,5 saniyede tam raporu donduruyor.
		 *
		 * Yani ilk istegin isi cevap almak degil, servisi uyandirmak. Denemeyi
		 * birakmak, Pro musterisinin gunun ILK dogrulamasini her seferinde
		 * kaybetmesi demekti.
		 *
		 * Arka plan yolunda butce zaten 90 saniye; orada ilk istek nadiren
		 * duser, dusuyorsa da ikinci deneme kimseyi bekletmez.
		 */
		if ( \is_wp_error( $response ) ) {
			sleep( self::RETRY_PAUSE );

			$response = $this->request( $xml, $this->ruleset( $profile ) );
		}

		if ( \is_wp_error( $response ) ) {
			return ValidationResult::unavailable( $response->get_error_message() );
		}

		$status = (int) \wp_remote_retrieve_response_code( $response );

		/*
		 * Uykuya dalan barindirmalar (Render'in ucretsiz katmani) makine
		 * durdurulurken yolu kisa bir sure kaydinden dusuruyor ve kenar
		 * sunucu istegi bekletmek yerine 404 donduruyor. Olculdu: birkac
		 * dakika 404, sonra kendiliginden 200.
		 *
		 * Bu yuzden kesin bir HTTP durumuyla donen gecici hatalarda da bir kez
		 * daha deneriz. (Zaman asimi yukarida ayrica ele alindi.)
		 */
		if ( in_array( $status, self::RETRYABLE, true ) ) {
			sleep( self::RETRY_PAUSE );

			$response = $this->request( $xml );

			if ( \is_wp_error( $response ) ) {
				return ValidationResult::unavailable( $response->get_error_message() );
			}

			$status = (int) \wp_remote_retrieve_response_code( $response );
		}

		if ( 200 !== $status ) {
			return ValidationResult::unavailable(
				404 === $status
					// 404 hem yanlis adres hem gecici kesinti demek olabilir.
					// Iki ihtimali de soyleyelim; teshis suresini kisaltir.
					? 'Validation service returned HTTP 404. Check the service address, or the service may be starting up.'
					: sprintf( 'Validation service returned HTTP %d.', $status )
			);
		}

		$body = json_decode( (string) \wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) || ! isset( $body['valid'] ) ) {
			return ValidationResult::unavailable( 'Validation service returned an unexpected response.' );
		}

		return new ValidationResult(
			(bool) $body['valid'],
			true,
			isset( $body['errors'] ) && is_array( $body['errors'] ) ? $body['errors'] : array(),
			isset( $body['warnings'] ) && is_array( $body['warnings'] ) ? $body['warnings'] : array(),
			isset( $body['rules_version'] ) ? (string) $body['rules_version'] : '',
			isset( $body['duration_ms'] ) ? (int) $body['duration_ms'] : 0
		);
	}

	/**
	 * Belge profilinden servisin kural seti adını türetir.
	 *
	 * EN 16931 bir tabandır; Almanya XRechnung ile üstüne daraltma koyar ve
	 * tabanda isteğe bağlı olan alanları zorunlu kılar. Ölçüldü: eklentinin
	 * çıktısı taban seti geçerken XRechnung'dan altı iddiadan düşüyordu,
	 * bkz. docs/adr/0010. Yani Alman bir müşteriye taban set tek başına
	 * "bu fatura kabul edilir" diyemez.
	 *
	 * Bilinmeyen profil tabana düşer; servis de aynısını yapar.
	 *
	 * @param Profile|null $profile Belge profili.
	 * @return string
	 */
	private function ruleset( ?Profile $profile ): string {
		return Profile::XRECHNUNG === $profile ? 'xrechnung' : 'en16931';
	}

	/**
	 * Doğrulama isteğini gönderir.
	 *
	 * @param string $xml     Fatura XML'i.
	 * @param string $ruleset Kural seti adı.
	 * @return array<string,mixed>|\WP_Error
	 */
	private function request( string $xml, string $ruleset = 'en16931' ) {
		return \wp_remote_post(
			\trailingslashit( $this->endpoint() ) . 'v1/validate',
			array(
				'timeout' => $this->timeout(),
				'headers' => array_merge(
					array(
						'authorization' => 'Bearer ' . $this->key(),
						'content-type'  => 'application/json',
					),
					self::identity_headers()
				),
				'body'    => (string) \wp_json_encode(
					array(
						'xml'     => $xml,
						'profile' => $ruleset,
					)
				),
			)
		);
	}

	/**
	 * İsteğe tanınacak süre.
	 *
	 * @return int Saniye.
	 */
	private function timeout(): int {
		$timeout = Scheduler::is_running_in_background()
			? self::TIMEOUT_BACKGROUND
			: self::TIMEOUT_INTERACTIVE;

		/**
		 * Doğrulama isteğine tanınan süreyi değiştirir.
		 *
		 * Uykuya dalan bir barındırma kullanıyorsanız ve arka plan süresi
		 * yetmiyorsa buradan uzatabilirsiniz.
		 *
		 * @param int  $timeout       Saniye.
		 * @param bool $in_background Arka plan işinde miyiz.
		 */
		return (int) \apply_filters(
			'deklera/validation_timeout',
			$timeout,
			Scheduler::is_running_in_background()
		);
	}

	/**
	 * Servis adresi.
	 *
	 * @return string
	 */
	private function endpoint(): string {
		/**
		 * Doğrulama servisinin adresini değiştirir.
		 *
		 * @param string $endpoint Adres.
		 */
		return (string) \apply_filters(
			'deklera/validator_endpoint',
			(string) \get_option( self::OPTION_ENDPOINT, self::DEFAULT_ENDPOINT )
		);
	}

	/**
	 * Servise gönderilecek anahtar.
	 *
	 * NEDEN ARTIK AYRI BİR ANAHTAR YOK
	 *
	 * Önceden her Pro müşterisi ikinci bir anahtar alıyordu ve onu elle
	 * yapıştırıyordu. Bunun üç maliyeti vardı: dizge herkeste aynıydı, yani
	 * tek bir müşterinin erişimi iptal edilemiyordu ve aboneliği biten
	 * kullanmaya devam ediyordu; kurulum bir adım uzuyordu; ve ilk ekran
	 * Freemius'un lisans anahtarını istediği için ikisi karışıyordu — ürünün
	 * sahibi bile yanlış kutuya yapıştırdı.
	 *
	 * Artık eklenti zaten taşıdığı Freemius lisans anahtarını gönderiyor ve
	 * servis onu Freemius'a soruyor. Girilecek bir şey yok, iptal ve abonelik
	 * bitişi kendiliğinden işliyor.
	 *
	 * Ayardaki alan kaldırılmadı: kendi kopyasını çalıştıran kurulumun
	 * Freemius'a bağlı olmaması gerekir, ve daha önce anahtar girmiş olanın
	 * kurulumu bozulmamalı. Girilmişse o kazanır.
	 *
	 * @return string
	 */
	private function key(): string {
		$manual = (string) \get_option( self::OPTION_KEY, '' );

		/**
		 * Doğrulama servisine gönderilen anahtarı değiştirir.
		 *
		 * @param string $key Anahtar.
		 */
		return (string) \apply_filters(
			'deklera/validator_key',
			'' !== $manual ? $manual : self::license_key()
		);
	}

	/**
	 * Freemius lisans anahtarı.
	 *
	 * SDK yoksa ya da lisans yoksa boş döner; çağıran taraf bunu
	 * "yapılandırılmamış" olarak okur.
	 *
	 * @return string
	 */
	public static function license_key(): string {
		if ( ! \function_exists( 'deklera_fs' ) ) {
			return '';
		}

		$freemius = \deklera_fs();

		if ( ! is_object( $freemius ) || ! method_exists( $freemius, '_get_license' ) ) {
			return '';
		}

		$license = $freemius->_get_license();

		return is_object( $license ) && isset( $license->secret_key )
			? (string) $license->secret_key
			: '';
	}

	/**
	 * Kurulumu tanıtan başlıklar.
	 *
	 * Servis lisansı Freemius'a sorarken anahtarın yanında kurulum kimliğini
	 * ve sitenin anonim kimliğini istiyor; üçü birlikte olmadan sorgu
	 * yapılamıyor. Kişisel veri taşımazlar: biri Freemius'un kendi ürettiği
	 * sayı, öteki siteye özgü rastgele bir dizge.
	 *
	 * @return array<string,string>
	 */
	private static function identity_headers(): array {
		if ( ! \function_exists( 'deklera_fs' ) ) {
			return array();
		}

		$freemius = \deklera_fs();

		if ( ! is_object( $freemius ) || ! method_exists( $freemius, 'get_site' ) ) {
			return array();
		}

		$site = $freemius->get_site();

		if ( ! is_object( $site ) || ! isset( $site->id ) ) {
			return array();
		}

		return array(
			'x-deklera-install' => (string) $site->id,
			'x-deklera-uid'     => (string) $freemius->get_anonymous_id(),
		);
	}
}
