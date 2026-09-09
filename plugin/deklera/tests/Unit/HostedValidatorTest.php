<?php
/**
 * Barındırılan doğrulama istemcisinin testleri.
 *
 * @package Deklera
 */

declare( strict_types = 1 );

namespace Deklera\Tests\Unit;

use Deklera\License\Plan;
use Deklera\Invoice\Profile;
use Deklera\Validation\HostedValidator;
use PHPUnit\Framework\TestCase;

/**
 * Pro'nun sattığı özelliğin İSTEMCİ tarafı.
 *
 * Bu sınıfın hiç birim testi yoktu, çünkü `wp_remote_post()` sahtelenmemişti.
 * Sonucu şu oldu: soğuk başlangıçta doğrulamanın sessizce kaybedildiği hata,
 * aylarca kimsenin görmediği bir yerde durdu ve ancak elle yapılan bir uçtan
 * uca ölçümde tesadüfen yakalandı.
 *
 * Servisin kendisi burada sınanmaz — o başka bir makinede ve `bin/pro-dogrula.php`
 * ile ölçülür. Burada sınanan şey istemcinin davranışı: ne zaman yeniden dener,
 * ne zaman pes eder, hatayı nasıl anlatır.
 */
final class HostedValidatorTest extends TestCase {

	/**
	 * Her testten önce sahte ortamı sıfırlar ve planı Pro'ya çeker.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		deklera_test_reset_filters();

		$GLOBALS['deklera_test_http']          = array();
		$GLOBALS['deklera_test_http_requests'] = array();
		$GLOBALS['deklera_test_options']       = array();

		add_filter( 'deklera/plan', static fn() => Plan::PRO );

		update_option( HostedValidator::OPTION_ENDPOINT, 'https://validator.example.test' );
		update_option( HostedValidator::OPTION_KEY, 'anahtar' );
	}

	/**
	 * Başarılı bir yanıt üretir.
	 *
	 * @param int   $status Durum kodu.
	 * @param array $body   Gövde.
	 * @return array<string,mixed>
	 */
	private function response( int $status, array $body ): array {
		return array(
			'response' => array( 'code' => $status ),
			'body'     => (string) wp_json_encode( $body ),
		);
	}

	/**
	 * Geçerli bir belge geçerli olarak raporlanır.
	 *
	 * @return void
	 */
	public function test_a_valid_document_is_reported_valid(): void {
		$GLOBALS['deklera_test_http'][] = $this->response(
			200,
			array(
				'valid'         => true,
				'errors'        => array(),
				'warnings'      => array(),
				'rules_version' => '1.3.16',
				'duration_ms'   => 210,
			)
		);

		$sonuc = ( new HostedValidator() )->validate( '<xml/>' );

		$this->assertTrue( $sonuc->available );
		$this->assertTrue( $sonuc->valid );
		$this->assertSame( '1.3.16', $sonuc->rules_version );
		$this->assertSame( 210, $sonuc->duration_ms );
		$this->assertCount( 1, $GLOBALS['deklera_test_http_requests'] );
	}

	/**
	 * Uykudaki servis: ilk istek düşer, ikincisi başarılı olur.
	 *
	 * BU TESTIN VAR OLMA SEBEBI. Render'ın ücretsiz katmanı ~15 dakika boşta
	 * kalınca uyuyor. Uyandırma isteği etkileşimli bütçeyi (15 sn) aşıyor ve
	 * WP_Error olarak dönüyor; hemen ardından gelen ikinci istek ısınmış
	 * servisi buluyor. Yeniden deneme olmasaydı Pro müşterisi günün İLK
	 * doğrulamasını her seferinde kaybederdi.
	 *
	 * @return void
	 */
	public function test_a_sleeping_service_is_woken_by_the_first_attempt(): void {
		$GLOBALS['deklera_test_http'][] = new \WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' );
		$GLOBALS['deklera_test_http'][] = $this->response(
			200,
			array(
				'valid'         => true,
				'rules_version' => '1.3.16',
			)
		);

		$sonuc = ( new HostedValidator() )->validate( '<xml/>' );

		$this->assertTrue( $sonuc->available, 'Uyanan servis erişilemez sayıldı.' );
		$this->assertTrue( $sonuc->valid );
		$this->assertCount( 2, $GLOBALS['deklera_test_http_requests'], 'İkinci deneme yapılmadı.' );
	}

	/**
	 * İki deneme de düşerse servis erişilemez sayılır ve sebebi taşınır.
	 *
	 * @return void
	 */
	public function test_two_failed_attempts_report_the_reason(): void {
		$GLOBALS['deklera_test_http'][] = new \WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' );
		$GLOBALS['deklera_test_http'][] = new \WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' );

		$sonuc = ( new HostedValidator() )->validate( '<xml/>' );

		$this->assertFalse( $sonuc->available );
		$this->assertFalse( $sonuc->valid );
		$this->assertStringContainsString( 'timed out', $sonuc->message );
		$this->assertCount( 2, $GLOBALS['deklera_test_http_requests'] );
	}

	/**
	 * Geçici HTTP durumlarında bir kez daha denenir.
	 *
	 * @return void
	 */
	public function test_a_transient_http_status_is_retried(): void {
		$GLOBALS['deklera_test_http'][] = $this->response( 503, array() );
		$GLOBALS['deklera_test_http'][] = $this->response( 200, array( 'valid' => true ) );

		$sonuc = ( new HostedValidator() )->validate( '<xml/>' );

		$this->assertTrue( $sonuc->available );
		$this->assertTrue( $sonuc->valid );
		$this->assertCount( 2, $GLOBALS['deklera_test_http_requests'] );
	}

	/**
	 * Bulgular olduğu gibi taşınır.
	 *
	 * @return void
	 */
	public function test_findings_are_carried_through(): void {
		$GLOBALS['deklera_test_http'][] = $this->response(
			200,
			array(
				'valid'    => false,
				'errors'   => array(
					array(
						'rule'    => 'BR-CO-15',
						'message' => 'Toplamlar tutmuyor.',
					),
				),
				'warnings' => array(
					array(
						'rule'    => 'BR-CL-01',
						'message' => 'Kod listesi.',
					),
				),
			)
		);

		$sonuc = ( new HostedValidator() )->validate( '<xml/>' );

		$this->assertTrue( $sonuc->available );
		$this->assertFalse( $sonuc->valid );
		$this->assertCount( 1, $sonuc->errors );
		$this->assertCount( 1, $sonuc->warnings );
		$this->assertSame( 'BR-CO-15', $sonuc->errors[0]['rule'] );
	}

	/**
	 * Beklenmedik gövde, "geçerli" sayılmaz.
	 *
	 * Sessizce geçerli saymak en tehlikelisi olurdu: müşteri doğrulandığını
	 * sanır, oysa hiçbir şey doğrulanmamıştır.
	 *
	 * @return void
	 */
	public function test_an_unexpected_body_is_not_treated_as_valid(): void {
		$GLOBALS['deklera_test_http'][] = array(
			'response' => array( 'code' => 200 ),
			'body'     => 'merhaba',
		);

		$sonuc = ( new HostedValidator() )->validate( '<xml/>' );

		$this->assertFalse( $sonuc->available );
		$this->assertFalse( $sonuc->valid );
	}

	/**
	 * Ücretsiz planda istek hiç gönderilmez.
	 *
	 * @return void
	 */
	public function test_the_free_plan_never_calls_the_service(): void {
		deklera_test_reset_filters();
		add_filter( 'deklera/plan', static fn() => Plan::FREE );

		$sonuc = ( new HostedValidator() )->validate( '<xml/>' );

		$this->assertFalse( $sonuc->available );
		$this->assertSame( array(), $GLOBALS['deklera_test_http_requests'] );
	}

	/**
	 * Anahtar yoksa istek gönderilmez.
	 *
	 * @return void
	 */
	public function test_a_missing_key_stops_the_request(): void {
		delete_option( HostedValidator::OPTION_KEY );

		$sonuc = ( new HostedValidator() )->validate( '<xml/>' );

		$this->assertFalse( $sonuc->available );
		$this->assertSame( array(), $GLOBALS['deklera_test_http_requests'] );
	}

	/**
	 * İstek doğru uca, doğru başlıkla gider.
	 *
	 * @return void
	 */
	public function test_the_request_targets_the_documented_endpoint(): void {
		$GLOBALS['deklera_test_http'][] = $this->response( 200, array( 'valid' => true ) );

		( new HostedValidator() )->validate( '<xml/>' );

		$istek = $GLOBALS['deklera_test_http_requests'][0];

		$this->assertSame( 'https://validator.example.test/v1/validate', $istek['url'] );
		$this->assertSame( 'Bearer anahtar', $istek['args']['headers']['authorization'] );
		// Profil gövdede gider; servis onunla ulusal kural setini seçer.
		$this->assertSame( '{"xml":"<xml\/>","profile":"en16931"}', $istek['args']['body'] );
	}

	/**
	 * Alman belgesi ulusal kural setini ister.
	 *
	 * REGRESYON KORUMASI: eklentinin çıktısı EN 16931 taban setini geçerken
	 * Almanya'nın resmi denetleyicisinden on iki iddiadan düşüyordu
	 * (docs/adr/0010). Profil gitmezse servis tabanı çalıştırır, Pro müşterisi
	 * "geçti" cevabı alır ve faturayı kuruma reddettirir.
	 *
	 * @return void
	 */
	public function test_a_german_document_asks_for_the_national_ruleset(): void {
		$GLOBALS['deklera_test_http'][] = $this->response( 200, array( 'valid' => true ) );

		( new HostedValidator() )->validate( '<xml/>', Profile::XRECHNUNG );

		$govde = (string) $GLOBALS['deklera_test_http_requests'][0]['args']['body'];

		$this->assertStringContainsString( '"profile":"xrechnung"', $govde );
	}

	/**
	 * Fransız belgesi tabanda kalır.
	 *
	 * Factur-X bir CIUS değildir; ulusal seti ona uygulamak, standardın izin
	 * verdiği bir şeyi yasaklamak olurdu.
	 *
	 * @return void
	 */
	public function test_a_french_document_stays_on_the_base_ruleset(): void {
		$GLOBALS['deklera_test_http'][] = $this->response( 200, array( 'valid' => true ) );

		( new HostedValidator() )->validate( '<xml/>', Profile::FACTUR_X );

		$govde = (string) $GLOBALS['deklera_test_http_requests'][0]['args']['body'];

		$this->assertStringContainsString( '"profile":"en16931"', $govde );
	}

	/*
	 * --- Lisansla yetkilendirme ---
	 *
	 * Ayri bir dogrulama anahtari kaldirildi; eklenti Freemius lisans
	 * anahtarini gonderiyor ve kurulumu iki baslikla tanitiyor. Servis bu
	 * ucluyu Freemius'a soruyor -- biri eksikse lisans dogrulanamaz ve Pro
	 * musterisi dogrulamayi hic acamaz. O yuzden istegin nasil kuruldugu
	 * burada olculur.
	 */

	/**
	 * Ayarda anahtar yokken lisans anahtari gönderilir.
	 *
	 * @return void
	 */
	public function test_the_licence_key_is_sent_when_no_key_is_stored(): void {
		delete_option( HostedValidator::OPTION_KEY );
		deklera_test_fs_ile( 'sk_lisans_anahtari' );

		$GLOBALS['deklera_test_http'][] = $this->response( 200, array( 'valid' => true ) );

		( new HostedValidator() )->validate( '<xml/>' );

		$basliklar = $GLOBALS['deklera_test_http_requests'][0]['args']['headers'];

		$this->assertSame( 'Bearer sk_lisans_anahtari', $basliklar['authorization'] );
	}

	/**
	 * Kurulum ve site kimliği başlıklarda gider.
	 *
	 * Servis lisansı bu ikisi olmadan soramaz; eksiklerse Freemius
	 * "missing_install" der ve doğrulama hiç çalışmaz.
	 *
	 * @return void
	 */
	public function test_the_install_is_identified_in_headers(): void {
		delete_option( HostedValidator::OPTION_KEY );
		deklera_test_fs_ile( 'sk_lisans_anahtari', '4242', 'ffffeeee11112222ffffeeee11112222' );

		$GLOBALS['deklera_test_http'][] = $this->response( 200, array( 'valid' => true ) );

		( new HostedValidator() )->validate( '<xml/>' );

		$basliklar = $GLOBALS['deklera_test_http_requests'][0]['args']['headers'];

		$this->assertSame( '4242', $basliklar['x-deklera-install'] );
		$this->assertSame( 'ffffeeee11112222ffffeeee11112222', $basliklar['x-deklera-uid'] );
	}

	/**
	 * Elle girilmiş anahtar lisans anahtarını yener.
	 *
	 * Kendi kopyasını çalıştıran kurulumun Freemius'a bağlı olmaması gerekir;
	 * anahtar girmişse onu göndeririz.
	 *
	 * @return void
	 */
	public function test_a_stored_key_wins_over_the_licence(): void {
		update_option( HostedValidator::OPTION_KEY, 'elle_girilen' );
		deklera_test_fs_ile( 'sk_lisans_anahtari' );

		$GLOBALS['deklera_test_http'][] = $this->response( 200, array( 'valid' => true ) );

		( new HostedValidator() )->validate( '<xml/>' );

		$basliklar = $GLOBALS['deklera_test_http_requests'][0]['args']['headers'];

		$this->assertSame( 'Bearer elle_girilen', $basliklar['authorization'] );
	}

	/**
	 * Lisans da anahtar da yoksa istek gönderilmez.
	 *
	 * @return void
	 */
	public function test_without_a_licence_or_key_nothing_is_sent(): void {
		delete_option( HostedValidator::OPTION_KEY );
		deklera_test_fs_ile( '' );

		$sonuc = ( new HostedValidator() )->validate( '<xml/>' );

		$this->assertFalse( $sonuc->available );
		$this->assertSame( array(), $GLOBALS['deklera_test_http_requests'] );
	}

	/**
	 * Servisin bildirdiği sebep yöneticiye ulaşır.
	 *
	 * "HTTP 401" tek başına hiçbir şey anlatmıyordu; iptal edilmiş lisansla
	 * eksik kurulum kimliği arasındaki fark ne yapılacağını belirler.
	 *
	 * @return void
	 */
	public function test_the_reason_reaches_the_message(): void {
		$GLOBALS['deklera_test_http'][] = $this->response(
			401,
			array(
				'error'  => 'unauthorised',
				'reason' => 'licence_expired',
			)
		);
		$GLOBALS['deklera_test_http'][] = $this->response(
			401,
			array(
				'error'  => 'unauthorised',
				'reason' => 'licence_expired',
			)
		);

		$sonuc = ( new HostedValidator() )->validate( '<xml/>' );

		$this->assertFalse( $sonuc->available );
		$this->assertStringContainsString( 'licence_expired', $sonuc->summary() );
	}
}
