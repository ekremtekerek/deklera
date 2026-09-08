<?php
/**
 * Belgenin müşteri e-postasına eklenmesi.
 *
 * @package Deklera
 */

declare( strict_types = 1 );

namespace Deklera\Delivery;

use Deklera\Invoice\SemanticInvoice;
use Deklera\Storage\Archive;
use Deklera\Storage\AuditLog;
use Deklera\Storage\Document;

defined( 'ABSPATH' ) || exit;

/**
 * Üretilen e-faturayı WooCommerce'in müşteriye gönderdiği e-postaya ekler.
 *
 * Ayrı bir e-posta göndermek yerine var olana eklenir: müşteri zaten bir
 * sipariş e-postası alıyor, ikinci bir mesaj gereksiz gürültüdür ve teslim
 * edilebilirliği düşürür.
 *
 * Yalnızca BÜTÜNLÜĞÜ DOĞRULANMIŞ belge eklenir. Diskte değişmiş veya eksik
 * bir dosyayı müşteriye göndermektense hiç göndermemek doğrudur.
 */
final class EmailDelivery {

	/**
	 * Belgenin ekleneceği e-posta kimlikleri.
	 *
	 * @var string[]
	 */
	private const EMAIL_IDS = array( 'customer_completed_order', 'customer_invoice' );

	/**
	 * Ticari fatura belge türü (UNTDID 1001).
	 *
	 * @var string
	 */
	private const INVOICE_TYPE = '380';

	/**
	 * Kancaları kaydeder.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'woocommerce_email_attachments', array( self::class, 'attach' ), 20, 4 );
		add_action( 'deklera/document_generated', array( self::class, 'on_generated' ), 10, 3 );
	}

	/**
	 * E-postaya belgeyi ekler.
	 *
	 * @param string[] $attachments Mevcut ekler.
	 * @param string   $email_id    E-posta kimliği.
	 * @param mixed    $subject      E-postanın konusu; sipariş olması beklenir.
	 * @param mixed    $email       E-posta nesnesi.
	 * @return string[]
	 */
	public static function attach( $attachments, $email_id, $subject, $email = null ): array {
		unset( $email );

		$attachments = is_array( $attachments ) ? $attachments : array();

		/**
		 * Belgenin ekleneceği e-posta kimliklerini değiştirir.
		 *
		 * @param string[] $ids E-posta kimlikleri.
		 */
		$ids = (array) \apply_filters( 'deklera/email_attachment_ids', self::EMAIL_IDS );

		if ( ! in_array( (string) $email_id, $ids, true ) ) {
			return $attachments;
		}

		if ( ! $subject instanceof \WC_Order ) {
			return $attachments;
		}

		$document = Archive::latest_for_order( $subject->get_id() );

		if ( null === $document ) {
			return $attachments;
		}

		/*
		 * Butunlugu dogrulanmamis dosya gonderilmez. Diskte degismis bir mali
		 * belgeyi musteriye iletmek, hic iletmemekten kotudur.
		 */
		if ( ! $document->is_intact() ) {
			AuditLog::record(
				AuditLog::EVENT_FAILED,
				$subject->get_id(),
				$document->id,
				'Not attached to email: the archived file is missing or was modified.'
			);

			return $attachments;
		}

		$attachments[] = $document->absolute_path();

		AuditLog::record(
			AuditLog::EVENT_EMAILED,
			$subject->get_id(),
			$document->id,
			(string) $email_id
		);

		return $attachments;
	}

	/**
	 * Belge arşive yazıldıktan sonra fatura e-postasını gönderir.
	 *
	 * NEDEN AYRI BİR E-POSTA GEREKTİ
	 *
	 * Tasarım gereği ek, WooCommerce'in zaten gönderdiği e-postaya takılır —
	 * ikinci bir mesaj gürültüdür. Ama ölçüldü: "sipariş tamamlandı" e-postası
	 * belge daha ÜRETİLMEDEN çıkıyor. Üretim asenkron kuyrukta koşar, e-posta
	 * ise aynı istekte gider. Yani özellik bağlıydı ve asıl hedefinde HİÇ
	 * çalışmıyordu; müşteri faturasız bir onay alıyordu ve kimse fark etmezdi.
	 *
	 * Üretimi senkron yapmak bunu çözerdi, ama Pro'da uzak doğrulama çağrısını
	 * durum geçişinin içine sokardı — uyuyan serviste yirmi saniye. Bir
	 * yöneticiyi o kadar bekletmek kabul edilemez.
	 *
	 * Bu yüzden belge hazır olunca WooCommerce'in kendi fatura e-postası
	 * gönderilir; ek yukarıdaki süzgeçle ona zaten takılır.
	 *
	 * @param Document        $document Arşivlenmiş belge.
	 * @param \WC_Order       $order    Sipariş.
	 * @param SemanticInvoice $invoice  Anlamsal fatura.
	 * @return void
	 */
	public static function on_generated( Document $document, \WC_Order $order, SemanticInvoice $invoice ): void {
		/**
		 * Belge üretildikten sonra fatura e-postası gönderilsin mi.
		 *
		 * @param bool      $send     Gönderilsin mi.
		 * @param Document  $document Belge.
		 * @param \WC_Order $order    Sipariş.
		 */
		if ( ! \apply_filters( 'deklera/email_after_generation', true, $document, $order ) ) {
			return;
		}

		/*
		 * Yalnızca ilk sürüm. Yeniden üretim yöneticinin bilinçli bir eylemidir
		 * ve müşteriye habersiz ikinci bir fatura göndermemeli; o durumda
		 * sipariş ekranındaki gönderme düğmesi kullanılır.
		 */
		if ( 1 !== $document->version ) {
			return;
		}

		/*
		 * İade faturası bu yoldan gitmez: WooCommerce'in şablonu "Fatura"
		 * başlığını taşır ve bir iade belgesini o başlıkla yollamak müşteriye
		 * yanlış bilgi vermek olur.
		 */
		if ( self::INVOICE_TYPE !== $invoice->type_code ) {
			return;
		}

		if ( '' === trim( (string) $order->get_billing_email() ) ) {
			return;
		}

		if ( ! \function_exists( 'WC' ) ) {
			return;
		}

		$mailer = \WC()->mailer();

		if ( ! is_object( $mailer ) || ! method_exists( $mailer, 'customer_invoice' ) ) {
			return;
		}

		$mailer->customer_invoice( $order );
	}
}
