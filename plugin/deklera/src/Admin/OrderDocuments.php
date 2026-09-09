<?php
/**
 * Sipariş ekranındaki belge kutusu ve indirme ucu.
 *
 * @package Deklera
 */

declare( strict_types = 1 );

namespace Deklera\Admin;

use Deklera\Invoice\Generator;
use Deklera\Invoice\Profile;
use Deklera\Storage\Archive;
use Deklera\Storage\AuditLog;
use Deklera\Storage\Document;

defined( 'ABSPATH' ) || exit;

/**
 * Siparişin e-fatura belgelerini yönetici ekranında gösterir.
 *
 * İndirme doğrudan URL ile yapılmaz. Arşivdeki dosyalar mali belgelerdir;
 * yalnızca yetkili kullanıcı, geçerli bir nonce ile ve bütünlüğü doğrulanmış
 * hâlde indirebilir. Her indirme denetim izine yazılır.
 */
final class OrderDocuments {

	/**
	 * Kancaları kaydeder.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'admin_post_deklera_download', array( self::class, 'download' ) );
		add_action( 'admin_post_deklera_generate', array( self::class, 'generate' ) );
		add_action( 'add_meta_boxes', array( self::class, 'add_meta_box' ) );
	}

	/**
	 * Sipariş ekranına kutuyu ekler.
	 *
	 * HPOS açıkken ekran kimliği farklıdır; ikisi de kaydedilir.
	 *
	 * @return void
	 */
	public static function add_meta_box(): void {
		foreach ( array( 'shop_order', 'woocommerce_page_wc-orders' ) as $screen ) {
			add_meta_box(
				'deklera-documents',
				__( 'E-invoice', 'deklera' ),
				array( self::class, 'render_meta_box' ),
				$screen,
				'side',
				'default'
			);
		}
	}

	/**
	 * Kutunun içeriğini çizer.
	 *
	 * @param mixed $post_or_order Gönderi veya sipariş nesnesi.
	 * @return void
	 */
	public static function render_meta_box( $post_or_order ): void {
		$order = $post_or_order instanceof \WC_Order
			? $post_or_order
			: \wc_get_order( is_object( $post_or_order ) ? $post_or_order->ID : 0 );

		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$order_id  = $order->get_id();
		$documents = Archive::for_order( $order_id );

		if ( array() === $documents ) {
			self::render_blockers( $order );
		} else {
			self::render_documents( $documents );
		}

		self::render_generate_button( $order_id, array() !== $documents );
		self::render_audit( $order_id );
	}

	/**
	 * Üretimi engelleyen bulguları listeler.
	 *
	 * @param \WC_Order $order Sipariş.
	 * @return void
	 */
	private static function render_blockers( \WC_Order $order ): void {
		$blockers = Generator::blockers( $order );

		if ( array() === $blockers ) {
			self::render_last_rejection( $order->get_id() );

			return;
		}

		printf(
			'<p><strong>%s</strong></p><ul style="margin-inline-start:16px;list-style:disc">',
			esc_html__( 'This order cannot be invoiced yet:', 'deklera' )
		);

		foreach ( array_slice( $blockers, 0, 5 ) as $blocker ) {
			printf( '<li>%s</li>', esc_html( $blocker ) );
		}

		echo '</ul>';
	}

	/**
	 * Belge yokken sebebi resmi kural setinden geliyorsa onu gösterir.
	 *
	 * NEDEN VAR
	 *
	 * Ön uçuş temizken belge üretilmemişse sebep uzak doğrulamadır. Ekranda
	 * yalnızca "No document generated yet." yazıyordu; hangi kuralın durdurduğu
	 * denetim izinde duruyor ama hiçbir yerde gösterilmiyordu. Pro'nun sattığı
	 * şey tam olarak o cevaptır — görünmezse satılan şey de görünmez.
	 *
	 * @param int $order_id Sipariş kimliği.
	 * @return void
	 */
	private static function render_last_rejection( int $order_id ): void {
		foreach ( AuditLog::for_order( $order_id, 5 ) as $event ) {
			if ( AuditLog::EVENT_INVALID !== (string) $event['event'] ) {
				continue;
			}

			printf(
				'<p><strong>%s</strong></p>',
				esc_html__( 'The official rule set rejected this document:', 'deklera' )
			);

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Metot kendi ciktisini esc_html ile kaciyor.
			echo self::findings_html( (string) $event['detail'], '#a4261d' );

			return;
		}

		printf( '<p>%s</p>', esc_html__( 'No document generated yet.', 'deklera' ) );
	}

	/**
	 * Denetim ayrıntısını okunur satırlara böler.
	 *
	 * Doğrulama özeti bulguları " | " ile birleştiriyor; kutu dar olduğu için
	 * tek satırda okunmuyorlar. Kural konumları boşluksuz uzun dizgeler
	 * olabildiğinden sarmalama da açıkça verilir, yoksa kutu yana taşar.
	 *
	 * @param string $detail Denetim izindeki ayrıntı.
	 * @param string $color  CSS rengi; boşsa soluk gri.
	 * @return string Kaçırılmış HTML.
	 */
	private static function findings_html( string $detail, string $color = '' ): string {
		$lines = array_filter(
			array_map( 'trim', explode( ' | ', $detail ) ),
			static fn ( string $line ): bool => '' !== $line
		);

		if ( array() === $lines ) {
			return '';
		}

		$html = '';

		foreach ( $lines as $line ) {
			$html .= '<div style="margin-bottom:2px">' . esc_html( $line ) . '</div>';
		}

		return sprintf(
			'<div style="overflow-wrap:anywhere;color:%s">%s</div>',
			esc_attr( '' === $color ? '#646970' : $color ),
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Satirlar yukarida esc_html ile kacirildi.
			$html
		);
	}

	/**
	 * Arşivlenmiş belgeleri listeler.
	 *
	 * @param Document[] $documents Belgeler.
	 * @return void
	 */
	private static function render_documents( array $documents ): void {
		echo '<ul style="margin:0">';

		foreach ( $documents as $document ) {
			$intact = $document->is_intact();

			printf(
				'<li style="margin-bottom:8px"><a href="%1$s"><strong>%2$s</strong></a><br/><span class="description">%3$s</span>%4$s%5$s</li>',
				esc_url( $document->download_url() ),
				esc_html(
					sprintf(
						/* translators: %d: document version number. */
						__( 'Version %d', 'deklera' ),
						$document->version
					)
				),
				esc_html(
					sprintf(
						'%s · %s · %s',
						$document->profile,
						$document->locale,
						size_format( $document->byte_size )
					)
				),
				$intact
					? ''
					: '<br/><span style="color:#a4261d">' . esc_html__( 'File missing or modified', 'deklera' ) . '</span>',
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Metot kendi ciktisini esc_html ile kaciyor.
				self::registration_note( $document )
			);
		}

		echo '</ul>';
	}

	/**
	 * KSeF tescil durumunu çizer.
	 *
	 * Yalnizca Polonya belgeleri icin anlamli. FA(3) dosyasi KSeF numarasi
	 * alana kadar hukuken var olmaz, bu yuzden numarasi olmayan bir belge
	 * "uretildi" degil "henuz tescil edilmedi" diye gosteriliyor: kullanici
	 * elindekinin fatura olmadigini indirmeden once gormeli.
	 *
	 * @param Document $document Belge.
	 * @return string
	 */
	private static function registration_note( Document $document ): string {
		if ( Profile::KSEF->value !== $document->profile ) {
			return '';
		}

		if ( $document->is_registered() ) {
			return '<br/><span class="description">'
				. esc_html__( 'KSeF number:', 'deklera' ) . ' <code>'
				. esc_html( $document->ksef_number ) . '</code></span>';
		}

		return '<br/><span style="color:#996800">'
			. esc_html__( 'Not registered with KSeF yet — this file is not a legal invoice.', 'deklera' )
			. '</span>';
	}

	/**
	 * Üretim düğmesini çizer.
	 *
	 * @param int  $order_id Sipariş kimliği.
	 * @param bool $exists   Daha önce üretilmiş mi.
	 * @return void
	 */
	private static function render_generate_button( int $order_id, bool $exists ): void {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-top:10px">';
		wp_nonce_field( 'deklera_generate_' . $order_id );
		echo '<input type="hidden" name="action" value="deklera_generate"/>';
		printf( '<input type="hidden" name="order_id" value="%d"/>', (int) $order_id );

		printf(
			'<button type="submit" class="button">%s</button>',
			esc_html( $exists ? __( 'Generate new version', 'deklera' ) : __( 'Generate document', 'deklera' ) )
		);

		echo '</form>';
	}

	/**
	 * Denetim izini özetler.
	 *
	 * @param int $order_id Sipariş kimliği.
	 * @return void
	 */
	private static function render_audit( int $order_id ): void {
		$events = AuditLog::for_order( $order_id, 5 );

		if ( array() === $events ) {
			return;
		}

		printf( '<p style="margin-bottom:4px"><strong>%s</strong></p><ul style="margin:0;font-size:12px">', esc_html__( 'History', 'deklera' ) );

		foreach ( $events as $event ) {
			$type   = (string) $event['event'];
			$detail = trim( (string) $event['detail'] );

			/*
			 * Ayrinti eskiden hic basilmiyordu: satir "Invalid" diyor, hangi
			 * kuralin durdurdugunu soylemiyordu. Olculdu — 9 Eylul 2026'da
			 * BR-DE-23-a belgeyi dusurdu ve ekranda sebep gorunmedi.
			 */
			$note = '' === $detail
				? ''
				: self::findings_html(
					$detail,
					in_array( $type, array( AuditLog::EVENT_INVALID, AuditLog::EVENT_FAILED ), true )
						? '#a4261d'
						: ''
				);

			printf(
				'<li style="margin-bottom:6px">%1$s — %2$s%3$s</li>',
				esc_html( (string) $event['created_at'] ),
				esc_html( AuditLog::label( $type ) ),
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- findings_html kendi ciktisini esc_html ile kaciyor.
				$note
			);
		}

		echo '</ul>';
	}

	/**
	 * Belgeyi indirir.
	 *
	 * @return void
	 */
	public static function download(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You are not allowed to download this document.', 'deklera' ), '', array( 'response' => 403 ) );
		}

		$id = isset( $_GET['document'] ) ? absint( wp_unslash( $_GET['document'] ) ) : 0;

		check_admin_referer( 'deklera_download_' . $id );

		$document = Archive::find( $id );

		if ( null === $document ) {
			wp_die( esc_html__( 'Document not found.', 'deklera' ), '', array( 'response' => 404 ) );
		}

		if ( ! $document->is_intact() ) {
			wp_die(
				esc_html__( 'The archived file is missing or has been modified since it was created. It cannot be served.', 'deklera' ),
				'',
				array( 'response' => 409 )
			);
		}

		AuditLog::record( AuditLog::EVENT_DOWNLOADED, $document->order_id, $document->id );

		$filename = sprintf( 'invoice-%s-v%d.%s', $document->invoice_number, $document->version, $document->format );

		nocache_headers();
		header( 'Content-Type: ' . ( 'pdf' === $document->format ? 'application/pdf' : 'application/xml' ) );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );
		header( 'Content-Length: ' . $document->byte_size );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- Dogrulanmis arsiv dosyasinin akitilmasi; WP_Filesystem burada uygun degil.
		readfile( $document->absolute_path() );

		exit;
	}

	/**
	 * Belgeyi elle üretir.
	 *
	 * @return void
	 */
	public static function generate(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You are not allowed to generate documents.', 'deklera' ), '', array( 'response' => 403 ) );
		}

		$order_id = isset( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : 0;

		check_admin_referer( 'deklera_generate_' . $order_id );

		$order = \wc_get_order( $order_id );

		if ( $order instanceof \WC_Order ) {
			Generator::generate( $order );
		}

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
		exit;
	}
}
