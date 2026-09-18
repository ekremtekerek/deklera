<?php
/**
 * Pro dikişi sınavı — anahtardan ÖNCE ağı ölç.
 *
 * WordPress'in HTTP API'si kendi sertifika paketini kullanır; bu makinede
 * antivirüs TLS'i kestiği için o paket yamalanmazsa istek "cURL error 60" ile
 * düşer. Anahtar girildikten sonra bu hatayı görmek, sorunun anahtarda
 * sanılmasına yol açar — bir kez böyle arandı (bkz. docs/I18N.md).
 *
 * Bu yüzden önce KASITLI OLARAK GEÇERSİZ bir anahtarla gidilir. Beklenen
 * cevap 401/403'tür: kapı çalışıyor, yalnızca anahtar yanlış. Gerçek bir sır
 * gönderilmez, hiçbir sır yazdırılmaz.
 *
 * @package Deklera
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

use Deklera\Validation\HostedValidator;

$endpoint = (string) get_option( HostedValidator::OPTION_ENDPOINT, HostedValidator::DEFAULT_ENDPOINT );

$response = wp_remote_post(
	trailingslashit( $endpoint ) . 'v1/validate',
	array(
		'timeout' => 90,
		'headers' => array(
			'authorization' => 'Bearer sinav-gecersiz-anahtar',
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

WP_CLI::log( 'adres  : ' . $endpoint );

if ( is_wp_error( $response ) ) {
	WP_CLI::log( 'SONUC  : AG HATASI' );
	WP_CLI::log( 'sebep  : ' . $response->get_error_message() );
	WP_CLI::log( '' );
	WP_CLI::log( 'TLS kesiliyorsa cozum: yerel kok sertifikayi' );
	WP_CLI::log( 'wp-includes/certificates/ca-bundle.crt sonuna eklemek.' );

	return;
}

$status = (int) wp_remote_retrieve_response_code( $response );

WP_CLI::log( 'HTTP   : ' . $status );

if ( 401 === $status || 403 === $status ) {
	WP_CLI::log( 'SONUC  : TLS ve servis calisiyor; kapi anahtari dogru reddetti.' );
} elseif ( 200 === $status ) {
	WP_CLI::log( 'SONUC  : BEKLENMEDIK — gecersiz anahtar kabul edildi.' );
} else {
	WP_CLI::log( 'SONUC  : beklenmedik durum. Govde: ' . substr( (string) wp_remote_retrieve_body( $response ), 0, 200 ) );
}
