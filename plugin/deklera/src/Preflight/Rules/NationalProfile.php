<?php
/**
 * Ulusal profil (CIUS) ek zorunlulukları.
 *
 * @package Deklera
 */

declare( strict_types = 1 );

namespace Deklera\Preflight\Rules;

use Deklera\Invoice\OrderMapper;
use Deklera\Preflight\Finding;
use Deklera\Preflight\Severity;
use Deklera\Preflight\StoreRule;

defined( 'ABSPATH' ) || exit;

/**
 * EN 16931 bir taban; ülkeler üstüne kendi daraltmalarını (CIUS) koyar.
 *
 * Almanya'nın XRechnung 3.0.2 profili, EN 16931'de isteğe bağlı olan iki alanı
 * zorunlu kılar: satıcı iletişim kişisi (BR-DE-5) ve telefonu (BR-DE-6).
 * Bunlar boşken KoSIT'in resmi denetleyicisi faturayı reddeder — ölçüldü,
 * bkz. docs/adr/0010.
 *
 * Bu kuralın var olma sebebi zamanlama: eksiklik dosya üretilirken değil,
 * fatura kuruma sunulduğunda patlar. O noktada müşteri geç kalmış olur.
 * Burada ise mağaza hiç sipariş almadan önce görünür.
 */
final class NationalProfile implements StoreRule {

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function id(): string {
		return 'national_profile';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function title(): string {
		return __( 'National profile requirements', 'deklera' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return Finding[]
	 */
	public function check_store(): array {
		$seller = OrderMapper::seller();

		if ( 'DE' !== strtoupper( $seller->country ) ) {
			return array();
		}

		if ( '' !== trim( $seller->phone ) ) {
			return array();
		}

		return array(
			new Finding(
				$this->id(),
				'de_seller_phone_missing',
				Severity::BLOCKER,
				Finding::STORE_WIDE,
				__( 'Your store has no billing phone number.', 'deklera' ),
				__( 'Germany\'s XRechnung profile makes the seller contact phone mandatory (BR-DE-6). Without it the official validator rejects the invoice, even though the EU standard itself allows it to be empty.', 'deklera' ),
				__( 'Add it in the Settings box at the bottom of this page.', 'deklera' ),
				'BT-42 / BR-DE-6'
			),
		);
	}
}
