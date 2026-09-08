<?php
/**
 * Belge üretildikten sonra gönderilen fatura e-postasının testleri.
 *
 * @package Deklera
 */

declare( strict_types = 1 );

namespace Deklera\Tests\Unit;

use Deklera\Delivery\EmailDelivery;
use Deklera\Invoice\Party;
use Deklera\Invoice\SemanticInvoice;
use Deklera\Storage\Document;
use PHPUnit\Framework\TestCase;

/**
 * Bu davranış bir ölçümden doğdu: "sipariş tamamlandı" e-postası belge daha
 * ÜRETİLMEDEN çıkıyordu, çünkü üretim asenkron kuyrukta koşuyor. Ek özelliği
 * bağlıydı ama asıl hedefinde hiç çalışmıyordu — müşteri faturasız bir onay
 * alıyor ve kimse fark etmiyordu.
 *
 * Testlerin çoğu göndermeme durumlarını koruyor, çünkü asıl risk orada:
 * müşteriye habersiz ikinci bir fatura yollamak, hiç yollamamaktan daha kötü
 * bir hatadır.
 */
final class EmailDeliveryTest extends TestCase {

	/**
	 * Her testte gönderim kaydını sıfırlar.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$GLOBALS['deklera_test_sent']    = array();
		$GLOBALS['deklera_test_filters'] = array();
	}

	/**
	 * Belge üretir.
	 *
	 * @param int $version Sürüm.
	 * @return Document
	 */
	private function document( int $version = 1 ): Document {
		return new Document(
			1,
			42,
			'INV-1',
			'xrechnung',
			'xml',
			'de_DE',
			'2026/09/1-v1-abc.xml',
			str_repeat( 'a', 64 ),
			1024,
			$version,
			'2026-09-08 00:00:00',
			0
		);
	}

	/**
	 * Anlamsal fatura üretir.
	 *
	 * @param string $type_code Belge türü.
	 * @return SemanticInvoice
	 */
	private function invoice( string $type_code = '380' ): SemanticInvoice {
		$party = new Party( 'Acme', 'DE', 'DE123456789', 'Street 1', 'Berlin', '10115', 'a@example.test', true );

		return new SemanticInvoice(
			'INV-1',
			new \DateTimeImmutable( '2026-09-01' ),
			$type_code,
			'EUR',
			$party,
			$party,
			array(),
			array()
		);
	}

	/**
	 * Sipariş taklidi.
	 *
	 * @param string $email Fatura e-postası.
	 * @return \WC_Order
	 */
	private function order( string $email = 'buyer@example.test' ): \WC_Order {
		$order = new \WC_Order( 42 );
		$order->set_billing_email( $email );

		return $order;
	}

	/**
	 * İlk sürüm fatura, müşteriye gönderilir.
	 *
	 * @return void
	 */
	public function test_the_first_invoice_is_emailed(): void {
		EmailDelivery::on_generated( $this->document(), $this->order(), $this->invoice() );

		$this->assertSame( array( 42 ), $GLOBALS['deklera_test_sent'] );
	}

	/**
	 * Yeniden üretim müşteriye ikinci bir fatura göndermez.
	 *
	 * REGRESYON KORUMASI: yeniden üretim yöneticinin bilinçli eylemidir.
	 * Her düzeltmede müşterinin kutusuna yeni bir "fatura" düşmesi, düzeltmeyi
	 * yapan kişinin beklemediği bir şeydir.
	 *
	 * @return void
	 */
	public function test_a_regenerated_version_is_not_emailed(): void {
		EmailDelivery::on_generated( $this->document( 2 ), $this->order(), $this->invoice() );

		$this->assertSame( array(), $GLOBALS['deklera_test_sent'] );
	}

	/**
	 * İade faturası bu yoldan gitmez.
	 *
	 * WooCommerce'in şablonu "Fatura" başlığını taşır; bir iade belgesini o
	 * başlıkla yollamak müşteriye yanlış bilgi vermektir.
	 *
	 * @return void
	 */
	public function test_a_credit_note_is_not_emailed_as_an_invoice(): void {
		EmailDelivery::on_generated( $this->document(), $this->order(), $this->invoice( '381' ) );

		$this->assertSame( array(), $GLOBALS['deklera_test_sent'] );
	}

	/**
	 * Fatura e-postası olmayan siparişe gönderilmez.
	 *
	 * @return void
	 */
	public function test_an_order_without_an_email_address_is_skipped(): void {
		EmailDelivery::on_generated( $this->document(), $this->order( '' ), $this->invoice() );

		$this->assertSame( array(), $GLOBALS['deklera_test_sent'] );
	}

	/**
	 * Süzgeç kapatabilir.
	 *
	 * Kendi teslim akışı olan mağazalar bunu kapatmak isteyebilir; kapatma
	 * yolu olmadan tek çare kancayı sökmek olurdu.
	 *
	 * @return void
	 */
	public function test_the_filter_can_switch_it_off(): void {
		add_filter( 'deklera/email_after_generation', static fn (): bool => false );

		EmailDelivery::on_generated( $this->document(), $this->order(), $this->invoice() );

		$this->assertSame( array(), $GLOBALS['deklera_test_sent'] );
	}
}
