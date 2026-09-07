<?php
/**
 * Ulusal profil kuralının testleri.
 *
 * @package Deklera
 */

declare( strict_types = 1 );

namespace Deklera\Tests\Unit;

use Deklera\Preflight\Rules\NationalProfile;
use Deklera\Preflight\Severity;
use PHPUnit\Framework\TestCase;

/**
 * Bu kural bir ölçümden doğdu: ürettiğimiz XRechnung, KoSIT'in resmi
 * denetleyicisinde BR-DE-5 ve BR-DE-6'dan düşüyordu. Alanlar eklendi, ama
 * telefon mağaza sahibinden geliyor — kod dolduramaz. Tek savunma, eksikliği
 * fatura kuruma gitmeden önce göstermek.
 */
final class NationalProfileTest extends TestCase {

	/**
	 * Her testte seçenekleri sıfırlar.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['deklera_test_options'] = array();
	}

	/**
	 * Alman satıcıda telefon yoksa kural bunu engelleyici olarak bildirir.
	 *
	 * @return void
	 */
	public function test_a_german_store_without_a_phone_is_blocked(): void {
		update_option( 'woocommerce_default_country', 'DE' );

		$findings = ( new NationalProfile() )->check_store();

		$this->assertCount( 1, $findings );
		$this->assertSame( 'de_seller_phone_missing', $findings[0]->code );
		$this->assertSame( Severity::BLOCKER, $findings[0]->severity );
	}

	/**
	 * Telefon dolduğunda bulgu kalmaz.
	 *
	 * @return void
	 */
	public function test_a_german_store_with_a_phone_passes(): void {
		update_option( 'woocommerce_default_country', 'DE' );
		update_option( 'deklera_seller_phone', '+49 30 1234567' );

		$this->assertSame( array(), ( new NationalProfile() )->check_store() );
	}

	/**
	 * Kural yalnızca Almanya'ya aittir; başka ülkeye taşınmaz.
	 *
	 * BR-DE-6 XRechnung'a özgüdür. Fransız bir mağazaya telefon zorunluluğu
	 * dayatmak, standardın izin verdiği bir şeyi yasaklamak olur.
	 *
	 * @return void
	 */
	public function test_the_rule_does_not_leak_into_other_countries(): void {
		update_option( 'woocommerce_default_country', 'FR' );

		$this->assertSame( array(), ( new NationalProfile() )->check_store() );
	}

	/**
	 * WooCommerce ülkeyi "DE:BE" gibi bölge ekiyle saklayabilir.
	 *
	 * @return void
	 */
	public function test_a_country_with_a_state_suffix_is_still_germany(): void {
		update_option( 'woocommerce_default_country', 'DE:BE' );

		$this->assertCount( 1, ( new NationalProfile() )->check_store() );
	}
}
