<?php
/**
 * Sipariş ekranındaki red gösterimini sınamak için reddedilmiş bir sipariş
 * üretir: yeni sipariş, ödeme aracı 58, IBAN yok. Belge oluşmaz, denetim
 * izine "invalid" düşer — ekranın göstermesi gereken durum tam olarak budur.
 *
 * @package Deklera
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

use Deklera\Invoice\Generator;
use Deklera\Storage\AuditLog;

$found = get_posts(
	array(
		'post_type'      => 'product',
		'title'          => 'Sinav Urunu',
		'posts_per_page' => 1,
		'fields'         => 'ids',
	)
);

if ( ! $found ) {
	WP_CLI::error( 'Urun yok; once sinav-kur.php calistirin.' );
}

$order = wc_create_order();
$order->add_product( wc_get_product( (int) $found[0] ), 1 );
$order->set_billing_first_name( 'Lena' );
$order->set_billing_last_name( 'Fischer' );
$order->set_billing_country( 'DE' );
$order->set_billing_address_1( 'Bahnhofstrasse 2' );
$order->set_billing_city( 'Muenchen' );
$order->set_billing_postcode( '80331' );
$order->set_billing_email( 'lena@example.test' );
$order->calculate_totals( false );
$order->set_status( 'completed' );
$order_id = (int) $order->save();

add_filter(
	'deklera/payment_means',
	static function () {
		return '58';
	}
);

$document = Generator::generate( $order );

WP_CLI::log( 'SIPARIS : ' . $order_id );
WP_CLI::log( 'belge   : ' . ( null === $document ? 'uretilmedi (beklenen)' : 'URETILDI' ) );

foreach ( array_reverse( AuditLog::for_order( $order_id, 3 ) ) as $row ) {
	WP_CLI::log( '  ' . $row['event'] . '  ' . substr( (string) $row['detail'], 0, 90 ) );
}

WP_CLI::log( '' );
WP_CLI::log( 'Ekran: /wp-admin/post.php?post=' . $order_id . '&action=edit' );
