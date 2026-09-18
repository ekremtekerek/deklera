<?php
/**
 * Pro dikişi sınavı — durum raporu.
 *
 * Anahtarın kendisini ASLA yazdırmaz; yalnızca var mı yok mu söyler.
 * Sırrı yönetici ekranında bile maskeliyoruz, konsola basmak çelişki olurdu.
 *
 * @package Deklera
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

use Deklera\License\Licensing;
use Deklera\Validation\HostedValidator;

$key      = (string) get_option( HostedValidator::OPTION_KEY, '' );
$endpoint = (string) get_option( HostedValidator::OPTION_ENDPOINT, HostedValidator::DEFAULT_ENDPOINT );

WP_CLI::log( 'plan                  : ' . Licensing::plan()->value );
WP_CLI::log( 'has_hosted_validation : ' . ( Licensing::has_hosted_validation() ? 'ACIK' : 'KAPALI' ) );
WP_CLI::log( 'servis adresi         : ' . $endpoint );
WP_CLI::log( 'anahtar               : ' . ( '' === $key ? 'YOK' : 'VAR' ) );
WP_CLI::log( 'is_configured()       : ' . ( ( new HostedValidator() )->is_configured() ? 'EVET' : 'HAYIR' ) );
