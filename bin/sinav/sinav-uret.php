<?php
/**
 * Pro dikişi sınavı — belge üretir ve sonucu döker.
 *
 * Asıl soru şu: eklenti gerçekten canlı servise gidiyor mu, ve gelen cevap
 * ULUSAL kural setinden mi geliyor. İkincisini görmek için ikinci bir tur
 * var: satıcı telefonu silinince resmi denetleyici BR-DE-6'dan düşmeli ve
 * üretim engellenmelidir. Düşmüyorsa doğrulama gerçekte çalışmıyor demektir.
 *
 * Çalıştır:
 *   ... wpcli wp eval-file /build/sinav-uret.php <siparis_id> --allow-root --path=/var/www/html
 *
 * @package Deklera
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

use Deklera\Invoice\Generator;
use Deklera\Invoice\Profile;
use Deklera\Storage\AuditLog;
use Deklera\Validation\HostedValidator;

$order_id = isset( $args[0] ) ? (int) $args[0] : 0;
$order    = $order_id > 0 ? wc_get_order( $order_id ) : null;

if ( ! $order instanceof WC_Order ) {
	WP_CLI::error( 'Siparis bulunamadi: ' . $order_id );
}

/**
 * Siparisin son olaylarini basar.
 *
 * @param int $order_id Siparis kimligi.
 * @param int $limit    Kac kayit.
 * @return void
 */
function deklera_sinav_gunluk( int $order_id, int $limit = 4 ): void {
	foreach ( array_reverse( AuditLog::for_order( $order_id, $limit ) ) as $row ) {
		WP_CLI::log( '    ' . $row['event'] . '  ' . (string) $row['detail'] );
	}
}

WP_CLI::log( '--- 1. tur: kurallara uyan belge' );

$before = ( new HostedValidator() )->is_configured();
WP_CLI::log( '  is_configured : ' . ( $before ? 'EVET' : 'HAYIR' ) );

if ( ! $before ) {
	WP_CLI::error( 'Dogrulama yapilandirilmamis. Anahtar girilmeden sinav anlamsiz.' );
}

$started  = microtime( true );
$document = Generator::generate( $order );
$elapsed  = (int) round( ( microtime( true ) - $started ) * 1000 );

WP_CLI::log( '  sure          : ' . $elapsed . ' ms' );

if ( $document instanceof Deklera\Storage\Document ) {
	WP_CLI::log( '  SONUC         : belge uretildi' );
	WP_CLI::log( '  profil        : ' . $document->profile . ' (' . $document->format . ')' );
	WP_CLI::log( '  numara        : ' . $document->invoice_number . ' surum ' . $document->version );
	WP_CLI::log( '  dosya         : ' . ( $document->exists() ? 'diskte' : 'YOK' ) );
} else {
	WP_CLI::log( '  SONUC         : belge URETILMEDI' );
}

deklera_sinav_gunluk( $order_id );

WP_CLI::log( '' );
WP_CLI::log( '--- 2. tur: satici telefonu silinmis (BR-DE-6 dusmeli)' );

$phone = (string) get_option( 'deklera_seller_phone', '' );
update_option( 'deklera_seller_phone', '' );

$blocked = Generator::generate( $order );

update_option( 'deklera_seller_phone', $phone );

if ( null === $blocked ) {
	WP_CLI::log( '  SONUC         : uretim ENGELLENDI (beklenen)' );
} else {
	WP_CLI::log( '  SONUC         : belge yine uretildi — dogrulama calismiyor olabilir' );
}

deklera_sinav_gunluk( $order_id, 2 );

WP_CLI::log( '' );
WP_CLI::log( 'profil beklentisi: ' . Profile::for_country( 'DE' )->value );
