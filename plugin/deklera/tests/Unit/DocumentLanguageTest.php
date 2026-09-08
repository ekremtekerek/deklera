<?php
/**
 * Belge dili kuralının testleri.
 *
 * @package Deklera
 */

declare( strict_types = 1 );

namespace Deklera\Tests\Unit;

use Deklera\Invoice\Party;
use Deklera\Invoice\SemanticInvoice;
use Deklera\Preflight\Rules\DocumentLanguage;
use Deklera\Preflight\Severity;
use PHPUnit\Framework\TestCase;

/**
 * Bu kural bir ölçümden doğdu: paketlenmiş sürüm sınanırken Alman faturası
 * İngilizce çıktı. Sebep eksik çeviri değildi — çeviriler paketin içindeydi.
 * WordPress kurulu olmayan bir dile GEÇEMİYOR ve dil paketi indirilemediğinde
 * eklenti sessizce mağazanın diline düşüyordu.
 *
 * Sessiz düşüş bu üründe kabul edilemez; kural onu faturadan önce söyler.
 */
final class DocumentLanguageTest extends TestCase {

	/**
	 * Her testte ortamı sıfırlar.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$GLOBALS['deklera_test_options']   = array();
		$GLOBALS['deklera_test_languages'] = array();
	}

	/**
	 * Sipariş taklidi üretir.
	 *
	 * @param string $country Fatura ülkesi.
	 * @return \WC_Order
	 */
	private function order( string $country ): \WC_Order {
		$order = new \WC_Order();
		$order->set_billing_country( $country );

		return $order;
	}

	/**
	 * Dil paketi yoksa uyarı verilir.
	 *
	 * @return void
	 */
	public function test_a_missing_language_pack_is_reported(): void {
		$findings = ( new DocumentLanguage() )->check( $this->invoice(), $this->order( 'DE' ) );

		$this->assertCount( 1, $findings );
		$this->assertSame( Severity::WARNING, $findings[0]->severity );
		$this->assertStringContainsString( 'de_DE', $findings[0]->what );
	}

	/**
	 * Dil paketi kuruluysa bulgu kalmaz.
	 *
	 * @return void
	 */
	public function test_an_installed_language_pack_passes(): void {
		$GLOBALS['deklera_test_languages'] = array( 'de_DE' );

		$this->assertSame( array(), ( new DocumentLanguage() )->check( $this->invoice(), $this->order( 'DE' ) ) );
	}

	/**
	 * İngilizcenin hiçbir çeşidi uyarı üretmez.
	 *
	 * Bir dil paketi en_GB için gerçekten gerekir, ama oradan mağaza diline
	 * düşmek yazım farkından ibarettir. Uyarmak, gerçek bulguların arasına
	 * gürültü katmak olurdu.
	 *
	 * @return void
	 */
	public function test_no_variety_of_english_needs_a_pack(): void {
		$rule = new DocumentLanguage();

		$this->assertSame( array(), $rule->check( $this->invoice(), $this->order( 'GB' ) ) );
		$this->assertSame( array(), $rule->check( $this->invoice(), $this->order( 'US' ) ) );
	}

	/**
	 * Her dil ayrı satırda görünür.
	 *
	 * REGRESYON KORUMASI: gruplama anahtarı kural + koddur. Kod tüm dillerde
	 * aynı olsaydı Almanca ve Lehçe tek satırda birleşir, mesaj yalnızca
	 * birinin dilini söylerdi — diğeri sessizce kaybolurdu.
	 *
	 * @return void
	 */
	public function test_each_language_groups_separately(): void {
		$rule = new DocumentLanguage();

		$de = $rule->check( $this->invoice(), $this->order( 'DE' ) );
		$pl = $rule->check( $this->invoice(), $this->order( 'PL' ) );

		$this->assertNotSame( $de[0]->group_key(), $pl[0]->group_key() );
	}

	/**
	 * Kuralın okumadığı ama imzasının istediği fatura nesnesi.
	 *
	 * Kural yalnızca siparişe bakar; fatura burada en sade hâliyle durur.
	 *
	 * @return SemanticInvoice
	 */
	private function invoice(): SemanticInvoice {
		$party = new Party( 'Acme', 'DE', 'DE123456789', 'Street 1', 'Berlin', '10115', 'a@example.test', true );

		return new SemanticInvoice(
			'INV-1',
			new \DateTimeImmutable( '2026-09-01' ),
			'380',
			'EUR',
			$party,
			$party,
			array(),
			array(),
			0.0
		);
	}
}
