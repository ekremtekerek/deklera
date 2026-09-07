<?php
/**
 * Yerleşik sade fatura şablonu.
 *
 * @package Deklera
 */

declare( strict_types = 1 );

namespace Deklera\Pdf;

use Deklera\Invoice\ExemptionReason;
use Deklera\Invoice\Party;
use Deklera\Invoice\SemanticInvoice;

defined( 'ABSPATH' ) || exit;

/**
 * Mağazada PDF fatura eklentisi yoksa devreye giren son çare şablon.
 *
 * İki şey bu şablonun tFPDF ile çizilmesini zorunlu kılıyor; ikisi de
 * ölçüldü, bkz. docs/adr/0011.
 *
 * BİRİNCİSİ: Factur-X bir PDF/A-3 belgesidir ve PDF/A, sayfada kullanılan
 * her yazı tipinin dosyanın İÇİNE gömülmesini şart koşar. FPDF'in çekirdek
 * fontları (Helvetica) gömülmez — tanım gereği. Bu yüzden ürettiğimiz
 * Factur-X, veraPDF'te tam olarak bu tek maddeden düşüyordu. Fransa'da bu,
 * belgenin Factur-X sayılmaması demek.
 *
 * İKİNCİSİ: FPDF yalnızca Latin-1 (CP1252) yazabilir. Lehçe, Çekçe, Macarca,
 * Romence, Yunanca ve Baltık dilleri bu kümeye sığmaz. Eskiden bu durumda
 * üretmeyi reddediyorduk — karakteri sessizce kırpmak alıcının adını
 * faturada yanlış yazmak olurdu. Ama KSeF'i desteklediğimiz bir üründe Lehçe
 * bir firma adının basılamaması, reddedilerek kabul edilebilir bir sınır
 * değildi.
 *
 * Gömülü TrueType (DejaVuSans) ikisini birden çözer: font dosyanın içindedir
 * ve UTF-8 doğrudan yazılır. tFPDF yalnızca kullanılan glifleri gömer, o
 * yüzden PDF birkaç KB büyür — tüm fontu taşımaz.
 *
 * Bkz. docs/adr/0002-pdf-uretimi.md
 */
final class BuiltinPdfSource implements PdfSource {

	/**
	 * Sayfa kenar boşluğu, mm.
	 */
	private const MARGIN = 15.0;

	/**
	 * Kullanılabilir içerik genişliği, mm (A4 = 210).
	 */
	private const WIDTH = 180.0;

	/**
	 * Gömülü yazı tipi ailesi.
	 *
	 * DejaVuSans tFPDF ile birlikte gelir ve Latin, Yunan ve Kiril
	 * alfabelerini kapsar — desteklediğimiz her AB dili için yeterli.
	 */
	private const FONT = 'DejaVu';

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function id(): string {
		return 'builtin';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function label(): string {
		return __( 'Deklera built-in template', 'deklera' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return class_exists( '\Deklera_tFPDF' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param \WC_Order       $order   Sipariş.
	 * @param SemanticInvoice $invoice Anlamsal fatura.
	 * @return string
	 */
	public function render( \WC_Order $order, SemanticInvoice $invoice ): string {
		unset( $order );

		$pdf = new \Deklera_tFPDF( 'P', 'mm', 'A4' );
		$pdf->AddFont( self::FONT, '', 'DejaVuSans.ttf', true );
		$pdf->AddFont( self::FONT, 'B', 'DejaVuSans-Bold.ttf', true );
		$pdf->SetAutoPageBreak( true, 20 );
		$pdf->SetMargins( self::MARGIN, self::MARGIN, self::MARGIN );
		$pdf->AddPage();

		$this->draw_header( $pdf, $invoice );
		$this->draw_parties( $pdf, $invoice );
		$this->draw_lines( $pdf, $invoice );
		$this->draw_totals( $pdf, $invoice );
		$this->draw_tax_notes( $pdf, $invoice );

		return (string) $pdf->Output( 'S' );
	}

	/**
	 * Başlık ve fatura künyesi.
	 *
	 * @param \Deklera_tFPDF  $pdf     PDF.
	 * @param SemanticInvoice $invoice Fatura.
	 * @return void
	 */
	private function draw_header( \Deklera_tFPDF $pdf, SemanticInvoice $invoice ): void {
		$pdf->SetFont( self::FONT, 'B', 16 );
		$pdf->Cell( 110, 8, $invoice->seller->name, 0, 0, 'L' );

		$pdf->SetFont( self::FONT, 'B', 16 );
		$pdf->Cell( 70, 8, __( 'Invoice', 'deklera' ), 0, 1, 'R' );

		$pdf->SetFont( self::FONT, '', 9 );
		$pdf->Cell( 110, 5, $invoice->seller->address, 0, 0, 'L' );
		$pdf->Cell(
			70,
			5,
			__( 'Number', 'deklera' ) . ': ' . $invoice->number,
			0,
			1,
			'R'
		);

		$pdf->Cell(
			110,
			5,
			trim( $invoice->seller->postcode . ' ' . $invoice->seller->city . ' ' . $invoice->seller->country ),
			0,
			0,
			'L'
		);
		$pdf->Cell(
			70,
			5,
			__( 'Date', 'deklera' ) . ': ' . \wp_date( 'Y-m-d', $invoice->issue_date->getTimestamp() ),
			0,
			1,
			'R'
		);

		if ( '' !== $invoice->seller->vat_number ) {
			$pdf->Cell(
				110,
				5,
				__( 'VAT number', 'deklera' ) . ': ' . $invoice->seller->vat_number,
				0,
				1,
				'L'
			);
		}

		$pdf->Ln( 6 );
	}

	/**
	 * Alıcı bloğu.
	 *
	 * @param \Deklera_tFPDF  $pdf     PDF.
	 * @param SemanticInvoice $invoice Fatura.
	 * @return void
	 */
	private function draw_parties( \Deklera_tFPDF $pdf, SemanticInvoice $invoice ): void {
		$buyer = $invoice->buyer;

		$pdf->SetFont( self::FONT, 'B', 9 );
		$pdf->Cell( self::WIDTH, 5, __( 'Bill to', 'deklera' ), 0, 1, 'L' );

		$pdf->SetFont( self::FONT, '', 10 );
		$pdf->Cell( self::WIDTH, 5, $buyer->name, 0, 1, 'L' );

		$pdf->SetFont( self::FONT, '', 9 );

		foreach ( $this->address_lines( $buyer ) as $line ) {
			$pdf->Cell( self::WIDTH, 5, $line, 0, 1, 'L' );
		}

		$pdf->Ln( 6 );
	}

	/**
	 * Alıcının adres satırlarını döndürür.
	 *
	 * @param Party $party Taraf.
	 * @return string[]
	 */
	private function address_lines( Party $party ): array {
		$lines = array();

		if ( '' !== $party->address ) {
			$lines[] = $party->address;
		}

		$city = trim( $party->postcode . ' ' . $party->city );

		if ( '' !== $city ) {
			$lines[] = $city;
		}

		if ( '' !== $party->country ) {
			$lines[] = $party->country;
		}

		if ( '' !== $party->vat_number ) {
			$lines[] = __( 'VAT number', 'deklera' ) . ': ' . $party->vat_number;
		}

		return $lines;
	}

	/**
	 * Satır tablosu.
	 *
	 * @param \Deklera_tFPDF  $pdf     PDF.
	 * @param SemanticInvoice $invoice Fatura.
	 * @return void
	 */
	private function draw_lines( \Deklera_tFPDF $pdf, SemanticInvoice $invoice ): void {
		$columns = array(
			array( __( 'Description', 'deklera' ), 88.0, 'L' ),
			array( __( 'Qty', 'deklera' ), 16.0, 'R' ),
			array( __( 'Unit price', 'deklera' ), 28.0, 'R' ),
			array( __( 'VAT', 'deklera' ), 20.0, 'R' ),
			array( __( 'Net', 'deklera' ), 28.0, 'R' ),
		);

		$pdf->SetFont( self::FONT, 'B', 9 );
		$pdf->SetFillColor( 235, 238, 240 );

		foreach ( $columns as $column ) {
			$pdf->Cell( $column[1], 7, (string) $column[0], 0, 0, (string) $column[2], true );
		}

		$pdf->Ln();
		$pdf->SetFont( self::FONT, '', 9 );

		foreach ( $invoice->lines as $line ) {
			$name = $line->name;

			if ( mb_strlen( $name ) > 52 ) {
				$name = mb_substr( $name, 0, 51 ) . '…';
			}

			$pdf->Cell( 88, 6, $name, 0, 0, 'L' );
			$pdf->Cell( 16, 6, \number_format_i18n( $line->quantity, 0 ), 0, 0, 'R' );
			$pdf->Cell( 28, 6, $this->money( $line->net_price, $invoice->currency ), 0, 0, 'R' );
			$pdf->Cell(
				20,
				6,
				$line->tax_category . ' ' . \number_format_i18n( $line->tax_rate, 0 ) . '%',
				0,
				0,
				'R'
			);
			$pdf->Cell( 28, 6, $this->money( $line->net_amount, $invoice->currency ), 0, 1, 'R' );
		}

		$pdf->Ln( 2 );
	}

	/**
	 * Toplamlar.
	 *
	 * @param \Deklera_tFPDF  $pdf     PDF.
	 * @param SemanticInvoice $invoice Fatura.
	 * @return void
	 */
	private function draw_totals( \Deklera_tFPDF $pdf, SemanticInvoice $invoice ): void {
		$rows = array(
			array( __( 'Net total', 'deklera' ), $invoice->tax_exclusive_total(), false ),
			array( __( 'VAT', 'deklera' ), $invoice->tax_total(), false ),
			array( __( 'Total', 'deklera' ), $invoice->tax_inclusive_total(), true ),
		);

		foreach ( $rows as $row ) {
			$pdf->SetFont( self::FONT, $row[2] ? 'B' : '', $row[2] ? 11 : 9 );
			$pdf->Cell( 132, 6, '', 0, 0 );
			$pdf->Cell( 20, 6, (string) $row[0], 0, 0, 'R' );
			$pdf->Cell( 28, 6, $this->money( (float) $row[1], $invoice->currency ), 0, 1, 'R' );
		}

		$pdf->Ln( 4 );
	}

	/**
	 * KDV kırılımı ve istisna gerekçeleri.
	 *
	 * @param \Deklera_tFPDF  $pdf     PDF.
	 * @param SemanticInvoice $invoice Fatura.
	 * @return void
	 */
	private function draw_tax_notes( \Deklera_tFPDF $pdf, SemanticInvoice $invoice ): void {
		$pdf->SetFont( self::FONT, '', 8 );

		foreach ( $invoice->tax_subtotals as $subtotal ) {
			if ( ! ExemptionReason::is_required( $subtotal->category ) ) {
				continue;
			}

			$reason = '' !== $subtotal->exemption_reason
				? $subtotal->exemption_reason
				: ExemptionReason::text( $subtotal->category );

			if ( '' === $reason ) {
				continue;
			}

			$pdf->MultiCell( self::WIDTH, 4, $reason, 0, 'L' );
			$pdf->Ln( 1 );
		}
	}

	/**
	 * Tutarı biçimlendirir.
	 *
	 * @param float  $amount   Tutar.
	 * @param string $currency Para birimi kodu.
	 * @return string
	 */
	private function money( float $amount, string $currency ): string {
		return \number_format_i18n( $amount, 2 ) . ' ' . $currency;
	}
}
