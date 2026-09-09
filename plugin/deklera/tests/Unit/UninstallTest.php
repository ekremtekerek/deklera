<?php
/**
 * Kaldırma temizliğinin kapsamı.
 *
 * @package Deklera
 */

declare( strict_types = 1 );

namespace Deklera\Tests\Unit;

use Deklera\Uninstall;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Eklentinin yazdığı her seçenek kaldırma listesinde ya da bilerek dışında mı.
 *
 * NEDEN BU TEST VAR
 *
 * `deklera_seller_contact` ve `deklera_seller_phone` ADR 0010 ile eklendi ve
 * kaldırma listesine konmayı unuttu. Kimse fark etmedi çünkü hiçbir şey
 * patlamıyor: "ayarlarımı sil" diyen kullanıcının sitesinde bir telefon
 * numarası kalıyor, o kadar. 0.3.8'in temiz kurulum sınavında yakalandı.
 *
 * Sınav elle koşan bir adım; bu test her koşuda koşar. Yeni bir ayar eklenip
 * listeye konmazsa burada kırmızıya döner.
 */
final class UninstallTest extends TestCase {

	/**
	 * Bilerek SİLİNMEYEN seçenekler ve sebepleri.
	 *
	 * @var array<string,string>
	 */
	private const KORUNANLAR = array(
		// Arşiv dosyaları silinmiyor; bütünlüklerini doğrulayan anahtar da
		// kalmalı, yoksa geride doğrulanamayan belgeler kalır.
		'deklera_archive_key' => 'arsiv dosyalari duruyor',
	);

	/**
	 * Kaynakta yazılan bütün seçenek adlarını toplar.
	 *
	 * @return string[]
	 */
	private function yazilan_secenekler(): array {
		$kok   = dirname( __DIR__, 2 ) . '/src';
		$adlar = array();

		$dosyalar = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $kok ) );

		foreach ( $dosyalar as $dosya ) {
			if ( ! $dosya->isFile() || 'php' !== $dosya->getExtension() ) {
				continue;
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Yerel kaynak dosyasi okunuyor; wp_remote_get uzak adresler icin.
			$govde = (string) file_get_contents( $dosya->getPathname() );

			/*
			 * Iki bicim de yakalanir: dogrudan yazilan ad ve sinif sabiti
			 * olarak tanimlanip update_option'a verilen ad. Ikincisi
			 * atlanirsa deklera_ksef_token gibi sabitler gozden kacar.
			 *
			 * Sabit adinda OPTION aranir. Eklentideki her "deklera_" dizgesi
			 * secenek degil: kanca ve gecici veri adlari da ayni bicimde
			 * yaziliyor (deklera_generate_document, deklera_preflight_report).
			 * Onlari da toplamak testi surekli yanlis alarm veren bir seye
			 * cevirirdi.
			 */
			preg_match_all( "/(?:update_option|add_option)\(\s*'(deklera_[a-z_]+)'/", $govde, $dogrudan );
			preg_match_all( "/const\s+[A-Z_]*OPTION[A-Z_]*\s*=\s*'(deklera_[a-z_]+)'/", $govde, $sabit );

			$adlar = array_merge( $adlar, $dogrudan[1], $sabit[1] );
		}

		return array_values( array_unique( $adlar ) );
	}

	/**
	 * Kaldırma listesini okur.
	 *
	 * @return string[]
	 */
	private function kaldirma_listesi(): array {
		$sinif = new ReflectionClass( Uninstall::class );

		return (array) $sinif->getConstant( 'OPTIONS' );
	}

	/**
	 * Yazılan her seçenek ya siliniyor ya da bilerek korunuyor.
	 *
	 * @return void
	 */
	public function test_every_option_is_either_removed_or_deliberately_kept(): void {
		$silinen  = $this->kaldirma_listesi();
		$unutulan = array();

		foreach ( $this->yazilan_secenekler() as $ad ) {
			if ( in_array( $ad, $silinen, true ) || isset( self::KORUNANLAR[ $ad ] ) ) {
				continue;
			}

			$unutulan[] = $ad;
		}

		$this->assertSame(
			array(),
			$unutulan,
			'Kaldirma listesinde olmayan secenek(ler): ' . implode( ', ', $unutulan )
				. ' — ya Uninstall::OPTIONS icine ya da bu testteki KORUNANLAR icine gerekcesiyle eklenmeli.'
		);
	}

	/**
	 * Korunan seçenekler gerçekten listede değildir.
	 *
	 * Gerekçesi yazılmış bir seçenek sonradan sessizce silinmeye başlarsa,
	 * gerekçe kalır ama davranış değişir; bu da fark edilmez.
	 *
	 * @return void
	 */
	public function test_deliberately_kept_options_are_not_removed(): void {
		$silinen = $this->kaldirma_listesi();

		foreach ( array_keys( self::KORUNANLAR ) as $ad ) {
			$this->assertNotContains( $ad, $silinen, $ad . ' korunmali ama silme listesinde.' );
		}
	}
}
