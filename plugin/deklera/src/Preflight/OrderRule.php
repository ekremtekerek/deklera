<?php
/**
 * Sipariş kapsamlı kural.
 *
 * @package Deklera
 */

declare( strict_types = 1 );

namespace Deklera\Preflight;

use Deklera\Invoice\SemanticInvoice;

defined( 'ABSPATH' ) || exit;

/**
 * Her sipariş için ayrı ayrı çalışan kural.
 */
interface OrderRule extends Rule {

	/**
	 * Siparişi denetler.
	 *
	 * @param SemanticInvoice $invoice Eşlenmiş fatura.
	 * @param \WC_Order       $order   Kaynak sipariş.
	 * @return Finding[]
	 */
	public function check( SemanticInvoice $invoice, \WC_Order $order ): array;
}
