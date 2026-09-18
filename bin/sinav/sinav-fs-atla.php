<?php
/**
 * Pro dikişi sınavı — Freemius kapısını yerel olarak atlar.
 *
 * NEDEN GEREKLİ
 *
 * Premium paket temiz kurulumda eklentinin BÜTÜN ekranını "Welcome to
 * Deklera! To get started, please enter your license key" kapısıyla
 * değiştiriyor. Doğrulama anahtarı alanı o kapının arkasında kalıyor.
 *
 * Sınavın ölçtüğü şey lisans zinciri değil (o 7 Eylül'de ayrıca kanıtlandı),
 * doğrulama servisi dikişi. Kapıyı `skip_connection()` ile geçiyoruz: bu
 * yerel bir işlem, Freemius'a hiçbir şey göndermez ve bir lisans koltuğu
 * harcamaz.
 *
 * @package Deklera
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

if ( ! function_exists( 'deklera_fs' ) ) {
	WP_CLI::error( 'Freemius SDK yuklu degil.' );
}

$freemius = deklera_fs();

if ( ! is_object( $freemius ) || ! method_exists( $freemius, 'skip_connection' ) ) {
	WP_CLI::error( 'skip_connection() bulunamadi.' );
}

$freemius->skip_connection();

WP_CLI::log( 'is_anonymous_page       : ' . ( method_exists( $freemius, 'is_anonymous' ) && $freemius->is_anonymous() ? 'EVET' : 'HAYIR' ) );
WP_CLI::log( 'is_registered           : ' . ( $freemius->is_registered() ? 'EVET' : 'HAYIR' ) );
WP_CLI::log( 'can_use_premium_code    : ' . ( $freemius->can_use_premium_code() ? 'EVET' : 'HAYIR' ) );
WP_CLI::log( 'Deklera plan            : ' . Deklera\License\Licensing::plan()->value );
WP_CLI::log( 'has_hosted_validation   : ' . ( Deklera\License\Licensing::has_hosted_validation() ? 'ACIK' : 'KAPALI' ) );
