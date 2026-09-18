<?php
/**
 * İlk izlenim sınavı — ön uçuş ekranı ne söylüyor.
 *
 * NEDEN BU SINAV VAR
 *
 * Elimizdeki bütün kapılar MAKİNEYE bakan çıktıyı sınıyordu: birim testleri
 * modeli, temiz kurulum sınavı paketi ve dosya bütünlüğünü, üç ülke sınavı
 * XML'i. Oysa ürünün bütün değeri bir İNSANIN okuduğu ekranda ve o ekranı
 * hiçbir şey denetlemiyordu.
 *
 * Bedeli şu oldu: taze bir kurulumda, mağazanın KDV numarası girilmemişken —
 * yani her fatura reddedilecekken — ekran yeşil renkle "All 1 recent order
 * would be accepted" diyor, üç satır aşağıda aynı ekran iki kez "Would be
 * rejected" diyordu. 119 birim testi yeşildi; `ReportTest` hatayı doğuran
 * durumu zaten iddia ediyordu ve DOĞRU olduğu için geçiyordu. Model
 * doğruydu, ekran modele danışmıyordu.
 *
 * SÖZE DEĞİL YAPIYA BAKAR
 *
 * Metin yerine sınıf adına bakılıyor. Başlığın kelimeleri değişebilir ve
 * değişmesi de gerekir; değişmemesi gereken şey şu: mağaza geneli bir engel
 * varken başlık YEŞİL olamaz ve "faturalanmaya hazır" sayacı sıfırdan büyük
 * olamaz. Sözcük sınayan bir kapı ilk çeviri düzeltmesinde yalancı kırmızı
 * verir ve kapatılır.
 *
 * @package Deklera
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

use Deklera\Admin\PreflightPage;

/*
 * SAYAÇ NEDEN $GLOBALS İÇİNDE
 *
 * `wp eval-file` bu dosyayı bir fonksiyonun içinde `include` ediyor. Yani
 * buradaki `$hata` yerel bir değişken; `olc()` içinde `global $hata` demek
 * BAŞKA bir değişkene işaret eder ve sayaç hiç artmaz. Kapı da her koşuda
 * yeşil yanar — yani hiçbir şey ölçmeyen bir kapı olur, ki yakalamaya
 * çalıştığımız hatanın tam olarak aynısıdır.
 */
$GLOBALS['deklera_ekran_hata'] = 0;

/**
 * Bir beklentiyi ölçer.
 *
 * @param string $ad       Kontrolün adı.
 * @param bool   $gecti    Sonuç.
 * @param string $ayrinti  Başarısızlıkta yazılacak açıklama.
 * @return void
 */
function olc( string $ad, bool $gecti, string $ayrinti = '' ): void {
	if ( $gecti ) {
		WP_CLI::log( '  ok    ' . $ad );
		return;
	}

	WP_CLI::log( '  HATA  ' . $ad . ( '' !== $ayrinti ? ' — ' . $ayrinti : '' ) );
	++$GLOBALS['deklera_ekran_hata'];
}

/**
 * Ekranı çizer ve HTML'ini döndürür.
 *
 * @return string
 */
function ekran(): string {
	delete_transient( 'deklera_preflight_report' );

	wp_set_current_user( 1 );

	ob_start();
	PreflightPage::render();

	return (string) ob_get_clean();
}

/**
 * Başlığın sınıf listesini döndürür.
 *
 * NEDEN İLK EŞLEŞME
 *
 * `deklera-lede` sayfada yedi yerde geçiyor: başlık ve altındaki mağaza
 * bulguları. İlk yazımda sınıf adını sayfanın tamamında aradım ve kapı
 * başlığa değil bulgulara bakıyordu — hatayı yine de yakaladı ama yanlış
 * elemandan, yani ölçtüğünü sandığım şeyi ölçmüyordu.
 *
 * Çizim sırası sabit: `render_summary()` diğerlerinden önce çağrılıyor, o
 * yüzden belgedeki İLK `deklera-lede` başlığın kendisidir.
 *
 * @param string $html Ekran çıktısı.
 * @return string
 */
function baslik_siniflari( string $html ): string {
	if ( preg_match( '/<p class="([^"]*deklera-lede[^"]*)"/', $html, $m ) ) {
		return $m[1];
	}

	return '';
}

/**
 * "Faturalanmaya hazır" sayacının değerini okur.
 *
 * Kutunun sırası değişirse yakalansın diye etiketiyle birlikte aranıyor.
 *
 * @param string $html Ekran çıktısı.
 * @return int|null
 */
function hazir_sayaci( string $html ): ?int {
	if ( preg_match( '#deklera-stat deklera-ok"><span class="deklera-stat-value">([0-9.,]+)</span>#', $html, $m ) ) {
		return (int) preg_replace( '/[^0-9]/', '', $m[1] );
	}

	return null;
}

/* ---------------------------------------------------------------- */

WP_CLI::log( 'A. Magaza ayari eksikken' );

delete_option( 'deklera_seller_vat_number' );
delete_option( 'deklera_seller_phone' );
update_option( 'woocommerce_store_phone', '' );

$html     = ekran();
$siniflar = baslik_siniflari( $html );

olc(
	'basligin rengi kirmizi',
	str_contains( $siniflar, 'deklera-bad' ),
	'baslik sinifi: "' . $siniflar . '"'
);

olc(
	'baslik yesil DEGIL',
	! str_contains( $siniflar, 'deklera-ok' ),
	'her fatura reddedilecekken baslik yesil — sinif: "' . $siniflar . '"'
);

olc(
	'magaza bulgulari gosteriliyor',
	str_contains( $html, 'deklera-store' ),
	'engel var ama listelenmemis'
);

$hazir = hazir_sayaci( $html );

olc(
	'faturalanmaya hazir sayaci sifir',
	0 === $hazir,
	'gelen: ' . var_export( $hazir, true )
);

/* ---------------------------------------------------------------- */

WP_CLI::log( '' );
WP_CLI::log( 'B. Ayarlar girildikten sonra' );

update_option( 'deklera_seller_vat_number', 'DE123456789' );
update_option( 'deklera_seller_contact', 'Sinav' );
update_option( 'deklera_seller_phone', '+49 30 123456' );

/* A adiminda bosaltilmisti; magaza gercekten eksiksiz olsun diye geri konuyor. */
update_option( 'woocommerce_store_phone', '+49 30 123456' );

$html     = ekran();
$siniflar = baslik_siniflari( $html );

olc(
	'baslik yesile doner',
	str_contains( $siniflar, 'deklera-ok' ),
	'ayarlar tamken baslik hala yesil degil — sinif: "' . $siniflar . '"'
);

olc(
	'baslik kirmizi DEGIL',
	! str_contains( $siniflar, 'deklera-bad' ),
	'engel kalmadi ama baslik kirmizi — sinif: "' . $siniflar . '"'
);

olc(
	'faturalanmaya hazir sayaci sifirdan buyuk',
	( hazir_sayaci( $html ) ?? 0 ) > 0,
	'gelen: ' . var_export( hazir_sayaci( $html ), true )
);

/* ---------------------------------------------------------------- */

WP_CLI::log( '' );
WP_CLI::log( 'C. Urune giden yol' );

/*
 * Eklentiler listesindeki bağlantı kullanıcının ürünü bulduğu tek yer:
 * etkinleştirme bildirimi yok, üst menü yok, tek ekran WooCommerce
 * menüsünün en altında duruyor. Kanca `is_admin()` arkasında olduğu için
 * WP-CLI'da kayıtlı değil; elle kaydediliyor.
 */
PreflightPage::register();

$links = apply_filters( 'plugin_action_links_deklera/deklera.php', array() );
$birlesik = implode( ' ', array_map( 'strval', (array) $links ) );

olc(
	'eklentiler listesinde rapora baglanti var',
	str_contains( $birlesik, 'page=deklera' ),
	'gelen: ' . $birlesik
);

/* ---------------------------------------------------------------- */

WP_CLI::log( '' );

if ( $GLOBALS['deklera_ekran_hata'] > 0 ) {
	WP_CLI::error( $GLOBALS['deklera_ekran_hata'] . ' ekran kontrolu basarisiz.' );
}

WP_CLI::log( 'EKRAN: gecti' );
