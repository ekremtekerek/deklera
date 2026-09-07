<?php
/**
 * Freemius genel erişim noktası.
 *
 * Freemius, SDK'ya `deklera_fs()` gibi GENEL ad alanındaki bir fonksiyonla
 * erişilmesini bekler. Eklentinin geri kalanı `Deklera` ad alanında olduğu
 * için bu köprü ayrı bir dosyada duruyor.
 *
 * Kimlik bilgileri girilene kadar fonksiyon null döner ve eklenti ücretsiz
 * planda çalışmaya devam eder.
 *
 * @package Deklera
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'deklera_fs' ) ) {
	/**
	 * Freemius SDK örneğini döndürür.
	 *
	 * @return object|null Yapılandırılmamışsa null.
	 */
	function deklera_fs(): ?object {
		return \Deklera\License\Freemius::instance();
	}
}
