<?php
/**
 * Pro dikişi sınavı — ULUSAL kural setinin gerçekten geldiğini kanıtlar.
 *
 * İlk denemede telefonu silmek işe yaramadı: ön uçuş kuralı (NationalProfile)
 * üretimi zaten kendisi engelledi, yani cevap servisten gelmiş olamazdı.
 * Ölçüm ancak ön uçuşun BAKMADIĞI bir kuralı bozarsa anlamlı olur.
 *
 * Ödeme aracı kodu 58 (SEPA kredi transferi) bunu sağlar: Almanya
 * BR-DE-19/20/21 ile IBAN ister, ön uçuşta böyle bir kural yoktur. Belge
 * engellenirse engelleyen şey yalnızca resmi XRechnung kural seti olabilir.
 *
 * @package Deklera
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

use Deklera\Invoice\Generator;
use Deklera\Storage\AuditLog;
use Deklera\Storage\Document;

$order_id = isset( $args[0] ) ? (int) $args[0] : 0;
$order    = $order_id > 0 ? wc_get_order( $order_id ) : null;

if ( ! $order instanceof WC_Order ) {
	WP_CLI::error( 'Siparis bulunamadi: ' . $order_id );
}

add_filter(
	'deklera/payment_means',
	static function () {
		return '58';
	}
);

$document = Generator::generate( $order );

if ( $document instanceof Document ) {
	WP_CLI::log( 'SONUC : belge uretildi — ulusal kurallar ISLEMEDI' );
	WP_CLI::log( 'surum : ' . $document->version );
} else {
	WP_CLI::log( 'SONUC : uretim engellendi' );
}

foreach ( array_reverse( AuditLog::for_order( $order_id, 2 ) ) as $row ) {
	WP_CLI::log( '  ' . $row['event'] . '  ' . (string) $row['detail'] );
}
