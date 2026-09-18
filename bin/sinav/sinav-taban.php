<?php
/**
 * Pro dikişi sınavı — anahtar girilmeden taban ölçüm.
 *
 * Doğrulama yapılandırılmamışken belge üretilmeli (ValidationResult::skipped).
 * Bu çizgi olmadan, anahtar girildikten sonra görülen her fark doğrulamaya
 * yazılamaz; ortamın kendisi mi çalışıyordu bilinmez.
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

$document = Generator::generate( $order );

if ( $document instanceof Document ) {
	WP_CLI::log( 'SONUC  : belge uretildi (dogrulama atlandi)' );
	WP_CLI::log( 'profil : ' . $document->profile . ' / ' . $document->format );
	WP_CLI::log( 'numara : ' . $document->invoice_number . ' surum ' . $document->version );
	WP_CLI::log( 'dosya  : ' . ( $document->exists() ? 'diskte' : 'YOK' ) );
	WP_CLI::log( 'butun  : ' . ( $document->is_intact() ? 'EVET' : 'HAYIR' ) );
} else {
	WP_CLI::log( 'SONUC  : belge URETILMEDI' );
}

foreach ( array_reverse( AuditLog::for_order( $order_id, 3 ) ) as $row ) {
	WP_CLI::log( '  ' . $row['event'] . '  ' . (string) $row['detail'] );
}
