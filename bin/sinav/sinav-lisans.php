<?php
/**
 * Lisansla yetkilendirmeyi ölçer.
 *
 * Anahtarın kendisi ASLA yazdırılmaz; yalnızca var mı yok mu ve Freemius'un
 * verdiği cevap. Sorgu konteynerin içinden yapılır, sır dışarı çıkmaz.
 *
 * @package Deklera
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

use Deklera\License\Licensing;
use Deklera\Validation\HostedValidator;

if ( ! function_exists( 'deklera_fs' ) ) {
	WP_CLI::error( 'Freemius SDK yok.' );
}

$freemius = deklera_fs();
$site     = is_object( $freemius ) && method_exists( $freemius, 'get_site' ) ? $freemius->get_site() : null;
$license  = HostedValidator::license_key();
$install  = is_object( $site ) && isset( $site->id ) ? (string) $site->id : '';
$uid      = is_object( $freemius ) ? (string) $freemius->get_anonymous_id() : '';

WP_CLI::log( 'is_registered  : ' . ( $freemius->is_registered() ? 'EVET' : 'HAYIR' ) );
WP_CLI::log( 'plan           : ' . Licensing::plan()->value );
WP_CLI::log( 'lisans anahtari: ' . ( '' === $license ? 'YOK' : 'VAR (' . strlen( $license ) . ' hane)' ) );
WP_CLI::log( 'install id     : ' . ( '' === $install ? 'YOK' : $install ) );
WP_CLI::log( 'uid            : ' . ( '' === $uid ? 'YOK' : substr( $uid, 0, 6 ) . '… (' . strlen( $uid ) . ' hane)' ) );

if ( '' === $license || '' === $install || '' === $uid ) {
	WP_CLI::log( '' );
	WP_CLI::log( 'Uclu tamam degil; Freemius sorgusu yapilamaz.' );

	return;
}

$url = sprintf(
	'https://api.freemius.com/v1/products/38206/installs/%s/license.json?uid=%s&license_key=%s',
	rawurlencode( $install ),
	rawurlencode( $uid ),
	rawurlencode( $license )
);

$response = wp_remote_get( $url, array( 'timeout' => 20 ) );

WP_CLI::log( '' );

if ( is_wp_error( $response ) ) {
	WP_CLI::log( 'AG HATASI : ' . $response->get_error_message() );

	return;
}

$status = (int) wp_remote_retrieve_response_code( $response );
$body   = json_decode( (string) wp_remote_retrieve_body( $response ), true );

WP_CLI::log( 'HTTP      : ' . $status );

if ( 200 !== $status ) {
	WP_CLI::log( 'govde     : ' . substr( (string) wp_remote_retrieve_body( $response ), 0, 300 ) );

	return;
}

/*
 * Anahtarin kendisi cevapta da geciyor; alan alan basiyoruz ki sir
 * ekrana dusmesin.
 */
foreach ( array( 'id', 'plan_id', 'is_cancelled', 'expiration', 'quota', 'activated' ) as $field ) {
	if ( array_key_exists( $field, (array) $body ) ) {
		WP_CLI::log( sprintf( '%-10s: %s', $field, var_export( $body[ $field ], true ) ) );
	}
}
