<?php
/**
 * Pro dikişi sınavı — anahtar reddedildiğinde teşhis.
 *
 * Anahtarın kendisi ASLA yazdırılmaz. Yazdırılanlar yalnızca onu
 * tanımlamayan ölçülerdir: uzunluk, biçim, baştaki/sondaki boşluk ve
 * servisin verdiği HTTP durumu. Bunlar "yanlış değer mi, bozuk kopya mı,
 * hiç kaydedilmemiş mi" sorusunu sırrı ifşa etmeden ayırır.
 *
 * @package Deklera
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

use Deklera\Validation\HostedValidator;

$ham      = get_option( HostedValidator::OPTION_KEY, '' );
$key      = (string) $ham;
$endpoint = (string) get_option( HostedValidator::OPTION_ENDPOINT, HostedValidator::DEFAULT_ENDPOINT );

WP_CLI::log( 'adres           : ' . $endpoint );
WP_CLI::log( 'secenek tipi    : ' . gettype( $ham ) );
WP_CLI::log( 'anahtar kayitli : ' . ( '' === $key ? 'HAYIR' : 'EVET' ) );

if ( '' === $key ) {
	WP_CLI::log( '' );
	WP_CLI::log( 'Anahtar hic kaydedilmemis. Form kaydedilmemis ya da baska' );
	WP_CLI::log( 'bir alana yapistirilmis olabilir.' );

	return;
}

WP_CLI::log( 'uzunluk         : ' . strlen( $key ) );
WP_CLI::log( 'kirpilinca      : ' . strlen( trim( $key ) ) . ' (fark varsa bosluk kopyalanmis)' );
WP_CLI::log( 'bicim           : ' . ( 1 === preg_match( '/^[0-9a-f]{64}$/', $key ) ? '64 haneli onaltilik (beklenen)' : 'BEKLENEN BICIMDE DEGIL' ) );
WP_CLI::log( 'yalniz gorunur  : ' . ( 1 === preg_match( '/^[\x21-\x7e]+$/', $key ) ? 'EVET' : 'HAYIR — gorunmez karakter var' ) );
WP_CLI::log( 'parmak izi      : ' . substr( hash( 'sha256', $key ), 0, 12 ) . ' (ilk 12 hane)' );

$response = wp_remote_post(
	trailingslashit( $endpoint ) . 'v1/validate',
	array(
		'timeout' => 90,
		'headers' => array(
			'authorization' => 'Bearer ' . $key,
			'content-type'  => 'application/json',
		),
		'body'    => (string) wp_json_encode(
			array(
				'xml'     => '<rsm:CrossIndustryInvoice/>',
				'profile' => 'xrechnung',
			)
		),
	)
);

WP_CLI::log( '' );

if ( is_wp_error( $response ) ) {
	WP_CLI::log( 'SONUC : AG HATASI — ' . $response->get_error_message() );

	return;
}

$status = (int) wp_remote_retrieve_response_code( $response );
$body   = (string) wp_remote_retrieve_body( $response );

WP_CLI::log( 'HTTP  : ' . $status );
WP_CLI::log( 'govde : ' . substr( $body, 0, 200 ) );

if ( 401 === $status || 403 === $status ) {
	WP_CLI::log( '' );
	WP_CLI::log( 'Anahtar servise ULASIYOR ama kabul edilmiyor. Yani deger,' );
	WP_CLI::log( 'servisin LICENSE_SECRET degiskeninden farkli.' );
} elseif ( 400 === $status ) {
	WP_CLI::log( '' );
	WP_CLI::log( 'Anahtar KABUL EDILDI; 400 gonderilen ornek XML gecersiz oldugu icin.' );
}
