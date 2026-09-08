<?php
/**
 * Belge dili gerçekten üretilebiliyor mu.
 *
 * @package Deklera
 */

declare( strict_types = 1 );

namespace Deklera\Preflight\Rules;

use Deklera\I18n\Locale;
use Deklera\Invoice\SemanticInvoice;
use Deklera\Preflight\Finding;
use Deklera\Preflight\OrderRule;
use Deklera\Preflight\Severity;

defined( 'ABSPATH' ) || exit;

/**
 * Fatura ALICININ dilinde üretilir. Eklenti Almanca, Fransızca ve Lehçe
 * çevirileri paketle birlikte taşır — ama bunların devreye girebilmesi için
 * WordPress'in o dile GEÇEBİLMESİ gerekir.
 *
 * WordPress buna izin vermez: `switch_to_locale()` yalnızca kurulu dillere
 * geçer (`WP_Locale_Switcher`, `available_languages` denetimi). Dil paketi
 * yoksa eklenti onu indirmeyi dener; indiremezse (dışarı kapalı sunucu,
 * güvenlik duvarı, kotası dolmuş barındırma) sessizce mağazanın diline düşer.
 *
 * Sonuç: Alman müşteri İngilizce fatura alır. Belge hukuken geçersiz olmaz,
 * ama satıcı bunu asla fark etmez — tam olarak bu üründe kaçındığımız sessiz
 * bozulma. Bu yüzden fatura kesilmeden önce söylenir.
 *
 * Engelleyici DEĞİL: dil, EN 16931'in zorunlu kıldığı bir şey değildir.
 */
final class DocumentLanguage implements OrderRule {

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function id(): string {
		return 'document_language';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function title(): string {
		return __( 'Invoice language', 'deklera' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param SemanticInvoice $invoice Eşlenmiş fatura.
	 * @param \WC_Order       $order   Sipariş.
	 * @return Finding[]
	 */
	public function check( SemanticInvoice $invoice, \WC_Order $order ): array {
		unset( $invoice );

		/*
		 * requested(), document()'in aksine hiçbir şey indirmez. Tarama
		 * yüzlerce siparişi gezer; burada ağa çıkmak kabul edilemez.
		 */
		$wanted = Locale::requested( $order );

		/*
		 * Ingilizcenin herhangi bir cesidi atlanir. en_GB icin dil paketi
		 * gercekten gerekir, ama oradan magaza diline dusmek yazim farkindan
		 * ibarettir; uyarmak gurultu olur ve gercek bulgulari gormeyi
		 * zorlastirir.
		 */
		if ( '' === $wanted || 'en' === strtolower( substr( $wanted, 0, 2 ) ) ) {
			return array();
		}

		if ( in_array( $wanted, (array) \get_available_languages(), true ) ) {
			return array();
		}

		/*
		 * Locale kodun İÇİNDE: gruplama anahtarı kural + koddur ve farklı
		 * diller ayrı satırlar hâlinde görünmeli. Aynı gruba düşselerdi
		 * mesaj yalnızca birinin dilini söylerdi, diğeri sessizce kaybolurdu.
		 */
		$code = 'pack_missing_' . strtolower( $wanted );

		return array(
			new Finding(
				$this->id(),
				$code,
				Severity::WARNING,
				$order->get_id(),
				sprintf(
					/* translators: %s: locale code, for example de_DE. */
					__( 'This invoice would be issued in your store language, not %s.', 'deklera' ),
					$wanted
				),
				__( 'Deklera writes the invoice in the buyer\'s language and ships German, French and Polish. WordPress can only switch to a language it has installed, so without the language pack the document falls back to your store language.', 'deklera' ),
				__( 'Install it under Settings > General > Site Language, or add it under Dashboard > Updates. The site needs to reach WordPress.org once to download it.', 'deklera' ),
				'BT-22'
			),
		);
	}
}
