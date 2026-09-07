<?php
/**
 * Faturanın temel zorunlu alanları.
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
 * BR-02, BR-05, BR-16: fatura numarası, para birimi ve en az bir satır
 * zorunludur.
 */
final class InvoiceBasics implements OrderRule {

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function id(): string {
		return 'invoice_basics';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function title(): string {
		return __( 'Invoice essentials', 'deklera' );
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

		if ( '' === trim( $invoice->number ) ) {
			$findings[] = new Finding(
				$this->id(),
				'number_missing',
				Severity::BLOCKER,
				$id,
				__( 'The order has no invoice number.', 'deklera' ),
				__( 'Every e-invoice must carry a unique invoice number. Without it the document cannot be issued.', 'deklera' ),
				__( 'Check your order numbering plugin, or set a number with the deklera/invoice_number filter.', 'deklera' ),
				'BT-1 / BR-02'
			);
		}

		if ( 1 !== preg_match( '/^[A-Z]{3}$/', $invoice->currency ) ) {
			$findings[] = new Finding(
				$this->id(),
				'currency_invalid',
				Severity::BLOCKER,
				$id,
				sprintf(
					/* translators: %s: currency code stored on the order. */
					__( 'The order currency "%s" is not a valid ISO 4217 code.', 'deklera' ),
					$invoice->currency
				),
				__( 'The invoice currency must be a three-letter ISO 4217 code such as EUR or PLN.', 'deklera' ),
				__( 'Fix the store currency under WooCommerce > Settings > General.', 'deklera' ),
				'BT-5 / BR-05'
			);
		}

		if ( 0 === count( $invoice->lines ) ) {
			$findings[] = new Finding(
				$this->id(),
				'no_lines',
				Severity::BLOCKER,
				$id,
				__( 'The order has no billable lines.', 'deklera' ),
				__( 'An invoice must contain at least one line. Orders with only zero-value items cannot be invoiced.', 'deklera' ),
				__( 'Open the order and check that it still contains its products.', 'deklera' ),
				'BG-25 / BR-16'
			);
		}

		return $findings;
	}
}
