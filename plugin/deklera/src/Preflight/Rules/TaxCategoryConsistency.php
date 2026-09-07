<?php
/**
 * Vergi kategorisi ile oranın tutarlılığı.
 *
 * @package Deklera
 */

declare( strict_types = 1 );

namespace Deklera\Preflight\Rules;

use Deklera\Invoice\SemanticInvoice;
use Deklera\Preflight\Finding;
use Deklera\Preflight\OrderRule;
use Deklera\Preflight\Severity;

defined( 'ABSPATH' ) || exit;

/**
 * BR-S-05, BR-Z-05, BR-AE-05, BR-K-05, BR-G-05, BR-E-05: kategori ile oran
 * birbiriyle tutarlı olmak zorundadır.
 *
 * Standart kategoride oran sıfırdan büyük, vergisiz kategorilerde tam olarak
 * sıfır olmalıdır. WooCommerce yalnızca oranı bildiği için bu tutarsızlıklar
 * ancak eşleme sırasında görünür hâle gelir.
 */
final class TaxCategoryConsistency implements OrderRule {

	/**
	 * Oranı sıfır olmak zorunda olan kategoriler.
	 *
	 * @var string[]
	 */
	private const ZERO_RATE_CATEGORIES = array( 'Z', 'E', 'AE', 'K', 'G', 'O' );

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function id(): string {
		return 'tax_category_consistency';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function title(): string {
		return __( 'VAT category and rate', 'deklera' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param SemanticInvoice $invoice Eşlenmiş fatura.
	 * @param \WC_Order       $order   Kaynak sipariş.
	 * @return Finding[]
	 */
	public function check( SemanticInvoice $invoice, \WC_Order $order ): array {
		$findings = array();
		$id       = $order->get_id();

		foreach ( $invoice->tax_subtotals as $subtotal ) {
			if ( in_array( $subtotal->category, self::ZERO_RATE_CATEGORIES, true ) && $subtotal->rate > 0.0 ) {
				$findings[] = new Finding(
					$this->id(),
					'zero_category_with_rate',
					Severity::BLOCKER,
					$id,
					sprintf(
						/* translators: 1: VAT category code, 2: VAT rate as a percentage. */
						__( 'VAT category %1$s is used together with a rate of %2$s%%.', 'deklera' ),
						$subtotal->category,
						\number_format_i18n( $subtotal->rate, 2 )
					),
					__( 'Categories that carry no VAT must have a rate of exactly zero. A non-zero rate makes the invoice internally inconsistent.', 'deklera' ),
					__( 'Review the tax rates under WooCommerce > Settings > Tax for this customer country.', 'deklera' ),
					'BT-119 / BR-AE-05'
				);
			}

			if ( 'S' === $subtotal->category && 0.0 === $subtotal->rate ) {
				$findings[] = new Finding(
					$this->id(),
					'standard_category_zero_rate',
					Severity::BLOCKER,
					$id,
					__( 'A standard-rate VAT line carries a rate of zero.', 'deklera' ),
					__( 'The standard category requires a rate above zero. A zero rate here usually means no tax rule matched the customer address.', 'deklera' ),
					__( 'Check that a tax rate exists for this country under WooCommerce > Settings > Tax.', 'deklera' ),
					'BT-119 / BR-S-05'
				);
			}
		}

		return array_merge(
			$findings,
			$this->check_consumer_zero_rate( $invoice, $id ),
			$this->check_assumptions( $invoice, $id )
		);
	}

	/**
	 * AB içi tüketici satışında hiç KDV alınmamışsa uyarır.
	 *
	 * OSS kuralları gereği bireysel AB müşterisine yapılan satışta alıcının
	 * ülkesinin oranı uygulanmalıdır. Sıfır oran, vergi ayarlarının o ülke için
	 * hiç tanımlanmamış olduğunu gösterir — mağaza farkında olmadan eksik KDV
	 * beyan ediyordur.
	 *
	 * @param SemanticInvoice $invoice  Eşlenmiş fatura.
	 * @param int             $order_id Sipariş kimliği.
	 * @return Finding[]
	 */
	private function check_consumer_zero_rate( SemanticInvoice $invoice, int $order_id ): array {
		$seller = $invoice->seller;
		$buyer  = $invoice->buyer;

		if ( ! $seller->is_in_eu() || ! $buyer->is_in_eu() ) {
			return array();
		}

		if ( strtoupper( $seller->country ) === strtoupper( $buyer->country ) ) {
			return array();
		}

		if ( $buyer->is_company || 0.0 !== $invoice->tax_total() ) {
			return array();
		}

		return array(
			new Finding(
				$this->id(),
				'consumer_cross_border_zero_rate',
				Severity::BLOCKER,
				$order_id,
				sprintf(
					/* translators: %s: buyer country code. */
					__( 'No VAT was charged on a sale to a private customer in %s.', 'deklera' ),
					strtoupper( $buyer->country )
				),
				__( 'Under the One Stop Shop rules a sale to an EU consumer is taxed at the rate of their country. Charging no VAT means either the tax rate is missing or the exemption cannot be justified.', 'deklera' ),
				__( 'Add a tax rate for this country under WooCommerce > Settings > Tax, or confirm you are below the OSS threshold.', 'deklera' ),
				'BT-119'
			),
		);
	}

	/**
	 * Kesin olmayan kategori kararlarını uyarı olarak bildirir.
	 *
	 * Sessizce tahmin etmektense tahmini görünür kılmak tercih edilir.
	 *
	 * @param SemanticInvoice $invoice  Eşlenmiş fatura.
	 * @param int             $order_id Sipariş kimliği.
	 * @return Finding[]
	 */
	private function check_assumptions( SemanticInvoice $invoice, int $order_id ): array {
		$categories = $invoice->tax_categories();
		$is_service = in_array( 'AE', $categories, true );

		if ( ! $is_service && ! in_array( 'K', $categories, true ) ) {
			return array();
		}

		return array(
			new Finding(
				$this->id(),
				$is_service ? 'assumed_service' : 'assumed_goods',
				Severity::WARNING,
				$order_id,
				$is_service
					? __( 'This order was treated as an intra-community service.', 'deklera' )
					: __( 'This order was treated as an intra-community supply of goods.', 'deklera' ),
				__( 'Goods use category K and services use category AE. Deklera decides this from whether the items are shippable or virtual, which is a reasonable guess but not always right.', 'deklera' ),
				__( 'If the classification is wrong, override it with the deklera/tax_category filter.', 'deklera' ),
				'BT-151'
			),
		);
	}
}
