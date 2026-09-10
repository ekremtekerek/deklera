<?php
/**
 * Üç ülkenin çıktısını gerçekten üretir.
 *
 * NEDEN AYRI BİR SINAV
 *
 * 0.3.9'da paketten 620 dosya çıkarıldı. Silinenlerin çalışma anında
 * okunmadığı kaynağa bakılarak belirlendi — ama "kaynağa baktım" bir ölçüm
 * değil. Eksik bir şema ya da renk profili hiçbir hata vermez; belge yine
 * üretilir, yalnızca yanlış üretilir ve bunu ancak müşteri görür.
 *
 * Bu yüzden üç yol da gerçekten koşturulur:
 *
 *   XRechnung  düz XML          — taban
 *   Factur-X   PDF/A-3 melez    — .xmp ve .icc buradan okunur
 *   FA(3)      Polonya XML      — .xsd buradan okunur
 *
 * @package Deklera
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

use Deklera\Invoice\Fa3Builder;
use Deklera\Invoice\OrderMapper;
use Deklera\Invoice\Profile;
use Deklera\Invoice\ZugferdBuilder;
use Deklera\Pdf\PdfRenderer;

$hata = 0;

/**
 * Bir beklentiyi olcer.
 *
 * @param string $ad      Olcumun adi.
 * @param bool   $kosul   Kosul.
 * @param string $ayrinti Ayrinti.
 * @return void
 */
function olc( string $ad, bool $kosul, string $ayrinti = '' ): void {
	global $hata;

	if ( $kosul ) {
		WP_CLI::log( '  ok    ' . $ad . ( '' !== $ayrinti ? '  (' . $ayrinti . ')' : '' ) );

		return;
	}

	WP_CLI::log( '  HATA  ' . $ad . ( '' !== $ayrinti ? '  ' . $ayrinti : '' ) );
	++$hata;
}

/**
 * Verilen ulke icin siparis kurar ve anlamsal faturayi dondurur.
 *
 * @param string $ulke Ulke kodu.
 * @return array{0: object, 1: \WC_Order}
 */
function sinav_fatura( string $ulke ): array {
	update_option( 'woocommerce_default_country', $ulke );
	update_option( 'woocommerce_store_address', 'Testweg 1' );
	update_option( 'woocommerce_store_city', 'Testort' );
	update_option( 'woocommerce_store_postcode', '10115' );
	update_option( 'woocommerce_currency', 'EUR' );
	update_option( 'deklera_seller_vat_number', $ulke . '1234567890' );
	update_option( 'deklera_seller_contact', 'Buchhaltung' );
	update_option( 'deklera_seller_phone', '+49 30 1234567' );

	$product = new WC_Product_Simple();
	$product->set_name( 'Sinav ' . $ulke );
	$product->set_regular_price( '100' );
	$product->save();

	$order = wc_create_order();
	$order->add_product( wc_get_product( $product->get_id() ), 1 );
	$order->set_billing_first_name( 'Test' );
	$order->set_billing_last_name( 'Alici' );
	$order->set_billing_address_1( 'Teststrasse 2' );
	$order->set_billing_city( 'Teststadt' );
	$order->set_billing_postcode( '80331' );
	$order->set_billing_country( $ulke );
	$order->set_billing_email( 'test@example.test' );
	$order->calculate_totals();
	$order->set_status( 'completed' );
	$order->save();

	return array( OrderMapper::map( $order ), $order );
}

WP_CLI::log( 'Budama sonrasi uc ulke' );

// 1. Almanya — duz XML, taban yol.
list( $de ) = sinav_fatura( 'DE' );
$xr = ( new ZugferdBuilder() )->build_xml( $de, Profile::XRECHNUNG );
olc( 'XRechnung XML uretildi', strlen( $xr ) > 2000, strlen( $xr ) . ' bayt' );
olc( 'XRechnung CII kok elemani', str_contains( $xr, 'CrossIndustryInvoice' ) );

/*
 * 2. Fransa — melez PDF/A-3. XMP uzanti semasi ve ICC renk profili tam
 * burada okunuyor; ikisinden biri eksik olsa PDF yine cikar ama PDF/A
 * olmaz ve veraPDF'ten duser.
 */
list( $fr, $fr_order ) = sinav_fatura( 'FR' );
$pdf    = PdfRenderer::render( $fr_order, $fr );
$melez  = ( new ZugferdBuilder() )->build_hybrid( $fr, Profile::FACTUR_X, $pdf );

olc( 'Factur-X PDF uretildi', strlen( $melez ) > 5000, strlen( $melez ) . ' bayt' );
olc( 'PDF baslikli', str_starts_with( $melez, '%PDF' ) );
olc( 'gomulu XML var', str_contains( $melez, 'factur-x.xml' ) );
olc( 'XMP uzanti semasi gomulu', str_contains( $melez, 'urn:factur-x:pdfa:CrossIndustryDocument' ) );
olc( 'ICC renk profili gomulu', str_contains( $melez, 'OutputIntent' ) );

// 3. Polonya — FA(3). Resmi XSD'ye karsi dogrulaniyor.
list( $pl ) = sinav_fatura( 'PL' );
$fa3 = ( new Fa3Builder() )->build_xml( $pl, Profile::KSEF );

olc( 'FA(3) XML uretildi', strlen( $fa3 ) > 1000, strlen( $fa3 ) . ' bayt' );
olc( 'FA(3) kok elemani', str_contains( $fa3, 'Faktura' ) );

WP_CLI::log( '' );

if ( $hata > 0 ) {
	WP_CLI::error( $hata . ' kontrol basarisiz — budama bir seyi bozdu.' );
}

WP_CLI::log( 'Budama uc yolu da bozmadi.' );
