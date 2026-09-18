<?php
/**
 * Pro dikişi sınavı — ortamı kurar.
 *
 * Alman mağaza, satıcı iletişim alanları ve tek bir yurt içi sipariş.
 * Amaç, ekranın önündeki tek eksik şeyin doğrulama anahtarı olması.
 *
 * Çalıştır:
 *   ... wpcli wp eval-file /build/sinav-kur.php --allow-root --path=/var/www/html
 *
 * @package Deklera
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

// Satıcı Almanya'da: Profile::for_country() bunu XRechnung'a çevirir.
update_option( 'woocommerce_default_country', 'DE:BE' );
update_option( 'woocommerce_store_address', 'Hauptstrasse 5' );
update_option( 'woocommerce_store_city', 'Berlin' );
update_option( 'woocommerce_store_postcode', '10115' );
update_option( 'woocommerce_currency', 'EUR' );

/*
 * BR-DE-5 ve BR-DE-6 satıcı iletişim kişisi ve telefonu ister; ikisi de
 * EN 16931 tabanında isteğe bağlıdır. Boş bırakılırsa resmi denetleyici
 * belgeyi düşürür — sınavın ikinci yarısında bunu bilerek yapacağız.
 */
update_option( 'deklera_seller_contact', 'Rechnungswesen' );
update_option( 'deklera_seller_phone', '+49 30 1234567' );
update_option( 'deklera_seller_vat_number', 'DE123456789' );

$name  = 'Sinav Urunu';
$found = get_posts(
	array(
		'post_type'      => 'product',
		'title'          => $name,
		'posts_per_page' => 1,
		'fields'         => 'ids',
	)
);

if ( $found ) {
	$product_id = (int) $found[0];
} else {
	$product = new WC_Product_Simple();
	$product->set_name( $name );
	$product->set_regular_price( '100' );
	$product->set_status( 'publish' );
	$product->set_catalog_visibility( 'visible' );
	$product_id = (int) $product->save();
}

$order = wc_create_order();
$order->add_product( wc_get_product( $product_id ), 1 );
$order->set_billing_first_name( 'Jonas' );
$order->set_billing_last_name( 'Weber' );
$order->set_billing_country( 'DE' );
$order->set_billing_address_1( 'Bahnhofstrasse 2' );
$order->set_billing_city( 'Muenchen' );
$order->set_billing_postcode( '80331' );
$order->set_billing_email( 'jonas@example.test' );
$order->calculate_totals( false );
$order->set_status( 'completed' );
$order_id = (int) $order->save();

WP_CLI::log( 'magaza      : DE / Berlin' );
WP_CLI::log( 'urun        : ' . $product_id );
WP_CLI::log( 'SIPARIS     : ' . $order_id );
WP_CLI::log( 'toplam      : ' . $order->get_total() . ' ' . $order->get_currency() );
