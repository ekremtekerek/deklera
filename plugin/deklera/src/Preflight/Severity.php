<?php
/**
 * Bulgu ciddiyeti.
 *
 * @package Deklera
 */

declare( strict_types = 1 );

namespace Deklera\Preflight;

defined( 'ABSPATH' ) || exit;

/**
 * Ciddiyet, kullanıcının neyi önce düzeltmesi gerektiğini söyler.
 *
 * Ayrım keskin tutulur: BLOCKER gerçekten faturanın reddedileceği anlamına
 * gelir. Her şeyi blocker yapmak raporu işe yaramaz hale getirir.
 */
enum Severity: string {

	/**
	 * Fatura bu hâliyle reddedilir.
	 */
	case BLOCKER = 'blocker';

	/**
	 * Muhtemelen geçer, ama doğrulanmalı.
	 */
	case WARNING = 'warning';

	/**
	 * Bilgilendirme; işlem gerekmez.
	 */
	case INFO = 'info';

	/**
	 * Kullanıcıya gösterilecek etiket.
	 *
	 * @return string
	 */
	public function label(): string {
		switch ( $this ) {
			case self::BLOCKER:
				return __( 'Would be rejected', 'deklera' );

			case self::WARNING:
				return __( 'Needs review', 'deklera' );

			case self::INFO:
				return __( 'For information', 'deklera' );
		}
	}

	/**
	 * Sıralama ağırlığı; büyük olan önce gösterilir.
	 *
	 * @return int
	 */
	public function weight(): int {
		switch ( $this ) {
			case self::BLOCKER:
				return 3;

			case self::WARNING:
				return 2;

			case self::INFO:
				return 1;
		}
	}
}
