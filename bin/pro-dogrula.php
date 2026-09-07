<?php
/**
 * Pro'nun sattigi seyi uctan uca olcer: barindirilan resmi EN 16931 dogrulamasi.
 *
 * NEDEN AYRI BIR BETIK
 *
 * Birim testleri bu halkayi goremez -- dogrulama baska bir makinede, Render
 * uzerinde calisiyor. Servis uyuyabilir, kural seti eskiyebilir, anahtar
 * gecersiz olabilir; hicbiri testlerde gorunmez. Musteri 149 EUR'yu tam da
 * bunun icin odedigine gore, surumden once bir kez gercekten calistirilmali.
 *
 * IKI YONLU OLCER. Yalnizca gecerli bir belge gondermek hicbir sey
 * kanitlamaz: servis her seye "gecerli" diyor olabilir. Bu yuzden ayni
 * fatura bir de bozulup gonderilir ve reddedilmesi beklenir.
 *
 * Plan, eklentinin kendi `deklera/plan` filtresiyle Pro'ya zorlanir. Bu
 * lisansin YERINE gecmez; olculen sey, lisans acildiginda musterinin aldigi
 * ozelligin calisip calismadigidir.
 *
 * Kullanim (temiz kurulum ortaminda, premium paket kuruluyken):
 *
 *   docker compose -f docker-compose.clean.yml -p deklera-clean \
 *     run --rm -T --user root wpcli \
 *     wp eval-file /repo/bin/pro-dogrula.php --allow-root --path=/var/www/html
 *
 * Once dogrulama servisinin adresi ve anahtari ayarlanmis olmali;
 * yordam docs/RELEASE.md "Pro: gercek dogrulama sinavi" bolumunde.
 *
  * SADECE GELISTIRME ARACIDIR; eklenti paketine girmez.
 *
 * Not: `declare( strict_types = 1 )` YOK. Bu dosya `wp eval-file` ile
 * calisiyor; WP-CLI icerigi eval ettigi icin declare artik betigin ilk
 * ifadesi olmuyor ve PHP olumcul hata veriyor.
 *
 * @package Deklera
 */

use Deklera\Invoice\OrderMapper;
use Deklera\Invoice\Profile;
use Deklera\Invoice\ZugferdBuilder;
use Deklera\License\Licensing;
use Deklera\License\Plan;
use Deklera\Validation\HostedValidator;

add_filter( 'deklera/plan', static fn() => Plan::PRO );

printf( "plan (filtreyle)         : %s\n", Licensing::plan()->value );
printf( "has_hosted_validation()  : %s\n", Licensing::has_hosted_validation() ? 'ACIK' : 'kapali' );

$validator = new HostedValidator();

printf( "validator yapilandirildi : %s\n", $validator->is_configured() ? 'evet' : 'hayir' );

if ( ! $validator->is_configured() ) {
	echo "\nUc nokta ya da anahtar eksik; bkz. docs/RELEASE.md\n";
	return;
}

// Gercek bir siparis: AB ici teslim, alicinin KDV numarasi var.
update_option( 'woocommerce_store_address', '12 Rue de Rivoli' );
update_option( 'woocommerce_store_city', 'Paris' );
update_option( 'woocommerce_store_postcode', '75001' );
update_option( 'woocommerce_default_country', 'FR:IDF' );
update_option( 'woocommerce_currency', 'EUR' );
update_option( 'deklera_seller_vat_number', 'FR40303265045' );

$product = new WC_Product_Simple();
$product->set_name( 'Ergonomic Desk Lamp' );
$product->set_regular_price( '100' );
$product->save();

$order = wc_create_order();
$order->add_product( wc_get_product( $product->get_id() ), 1 );
$order->set_billing_company( 'Bakker Retail BV' );
$order->set_billing_first_name( 'Sanne' );
$order->set_billing_last_name( 'Bakker' );
$order->set_billing_address_1( 'Keizersgracht 12' );
$order->set_billing_city( 'Amsterdam' );
$order->set_billing_postcode( '1015 CJ' );
$order->set_billing_country( 'NL' );
$order->set_billing_email( 'sanne@example.test' );
$order->update_meta_data( '_billing_vat_number', 'NL123456789B01' );
$order->calculate_totals();
$order->set_status( 'completed' );
$order->save();

$xml = ( new ZugferdBuilder() )->build_xml( OrderMapper::map( $order ), Profile::FACTUR_X );

printf( "\nsiparis #%d, uretilen XML : %d bayt\n", $order->get_id(), strlen( $xml ) );

// --- 1. yon: gecerli belge kabul edilmeli --------------------------------

$sonuc = $validator->validate( $xml );

echo "\n=== GECERLI BELGE ===\n";
printf( "servise ulasildi         : %s\n", $sonuc->available ? 'EVET' : 'HAYIR' );
printf( "belge gecerli            : %s\n", $sonuc->valid ? 'EVET' : 'HAYIR (BEKLENMEDIK)' );
printf( "kural seti surumu        : %s\n", '' !== $sonuc->rules_version ? $sonuc->rules_version : '-' );
printf( "servis suresi            : %d ms\n", $sonuc->duration_ms );
printf( "olumcul bulgu            : %d\n", count( $sonuc->errors ) );

if ( '' !== $sonuc->message ) {
	printf( "mesaj                    : %s\n", $sonuc->message );
}

foreach ( array_slice( $sonuc->errors, 0, 3 ) as $bulgu ) {
	printf( "  - %s\n", implode( ' | ', array_map( 'strval', $bulgu ) ) );
}

// --- 2. yon: bozuk belge reddedilmeli ------------------------------------

$bozuk = preg_replace(
	'#(<ram:GrandTotalAmount>)[^<]+(</ram:GrandTotalAmount>)#',
	'${1}999.00${2}',
	$xml,
	1,
	$degisen
);

echo "\n=== BOZUK BELGE (toplam degistirildi) ===\n";

if ( 0 === (int) $degisen ) {
	echo "GrandTotalAmount bulunamadi; XML yapisi degismis olabilir\n";
	return;
}

$bozuk_sonuc = $validator->validate( (string) $bozuk );

printf( "belge gecerli            : %s\n", $bozuk_sonuc->valid ? 'EVET (BEKLENMEDIK!)' : 'hayir (beklenen)' );
printf( "olumcul bulgu            : %d\n", count( $bozuk_sonuc->errors ) );

foreach ( array_slice( $bozuk_sonuc->errors, 0, 4 ) as $bulgu ) {
	$parcalar = array();

	foreach ( $bulgu as $anahtar => $deger ) {
		$parcalar[] = $anahtar . '=' . mb_substr( (string) $deger, 0, 80 );
	}

	printf( "  - %s\n", implode( ' | ', $parcalar ) );
}

echo "\n";
echo $sonuc->valid && ! $bozuk_sonuc->valid
	? "SONUC: Pro'nun sattigi ozellik calisiyor.\n"
	: "SONUC: BEKLENEN DAVRANIS ALINAMADI -- surum cikarilmaz.\n";
