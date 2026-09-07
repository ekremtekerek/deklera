<?php
/**
 * tFPDF'in font olcum onbellegini URETIM ZAMANINDA hazirlar ve kullanilmayan
 * yazi tiplerini paketten atar.
 *
 * NEDEN BU BETIK VAR
 *
 * tFPDF, bir TrueType fontu ilk kullandiginda 750 KB'lik dosyayi ayristirip
 * yaninda .mtx.php / .cw.dat onbellegi birakir. Iki sorun var:
 *
 * 1. Onbellegi yazamazsa (dizin salt okunur) HER FATURADA yeniden ayristirir.
 *    Olculdu: fatura basina ~1,9 saniye. Toplu uretimde kabul edilemez.
 *
 * 2. Onbellegin icine TTF'in MUTLAK YOLUNU yazar. Derleme makinesinde uretilen
 *    bir onbellegi oldugu gibi paketlersek, musterinin sunucusunda o yol yoktur
 *    ve font gomulemez. PDF/A sessizce bozulur -- Factur-X'i gecersiz kilan
 *    tam olarak bu, bkz. docs/adr/0011.
 *
 * Cozum ikisini birden kapatiyor: onbellegi burada uretip $ttffile satirini
 * __DIR__ goreli hale getiriyoruz. Calisma aninda ne ayristirma ne yazma var.
 *
 * Ayrica tFPDF 9,5 MB font tasir; sablon DejaVuSans'in iki kesitini kullanir.
 * Gerisi pakete girmemeli.
 *
 * Kullanim: php font-cache.php <eklenti-dizini>
 */

$plugin = $argv[1] ?? '';
$dir    = rtrim( $plugin, '/' ) . '/vendor-prefixed/setasign/tfpdf/font/unifont/';

if ( ! is_dir( $dir ) ) {
	fwrite( STDERR, 'HATA: unifont dizini yok: ' . $dir . PHP_EOL );
	exit( 1 );
}

// Sablonun kullandigi kesitler. BuiltinPdfSource::render() ile ayni liste.
$tutulacak = array( 'DejaVuSans.ttf', 'DejaVuSans-Bold.ttf' );

require_once $dir . 'ttfonts.php';
require_once dirname( $dir, 2 ) . '/tfpdf.php';

$pdf = new Deklera_tFPDF( 'P', 'mm', 'A4' );
$pdf->AddPage();

foreach ( $tutulacak as $sira => $dosya ) {
	$stil = 0 === $sira ? '' : 'B';

	$pdf->AddFont( 'DejaVu', $stil, $dosya, true );
	$pdf->SetFont( 'DejaVu', $stil, 10 );

	// 128 ustu karakter yazilmali; .cw127.php ancak o zaman uretilir.
	$pdf->Cell( 0, 5, 'Lodz Athina Sofia zolc', 0, 1 );
	$pdf->Cell( 0, 5, 'Łódź Αθήνα София żółć', 0, 1 );
}

$pdf->Output( 'S' );

/*
 * Mutlak yolu __DIR__ gorelisiyle degistirir.
 *
 * Satir tFPDF tarafindan tek bir kalipla yazilir (bkz. tfpdf.php, AddFont):
 *
 *   $ttffile='/mutlak/yol/DejaVuSans.ttf';
 *
 * Onek ve son ek sabit; arada kalan kisim yoldur.
 */
$onek  = '$ttffile=' . "'";
$sonek = "';";
$sayac = 0;

foreach ( glob( $dir . '*.mtx.php' ) as $mtx ) {
	$satirlar = file( $mtx );
	$degisti  = false;

	foreach ( $satirlar as $i => $satir ) {
		$duz = rtrim( $satir, "\r\n" );

		if ( 0 !== strpos( $duz, $onek ) || $sonek !== substr( $duz, -2 ) ) {
			continue;
		}

		$yol            = substr( $duz, strlen( $onek ), -2 );
		$satirlar[ $i ] = '$ttffile=__DIR__ . ' . "'/" . basename( $yol ) . "';" . PHP_EOL;
		$degisti        = true;
		break;
	}

	if ( ! $degisti ) {
		fwrite( STDERR, 'HATA: ttffile satiri bulunamadi: ' . $mtx . PHP_EOL );
		exit( 1 );
	}

	file_put_contents( $mtx, implode( '', $satirlar ) );
	++$sayac;
}

if ( count( $tutulacak ) !== $sayac ) {
	fwrite( STDERR, 'HATA: ' . $sayac . ' onbellek uretildi, ' . count( $tutulacak ) . ' bekleniyordu' . PHP_EOL );
	exit( 1 );
}

// Kullanilmayan yazi tiplerini at.
$silinen = 0;

foreach ( glob( $dir . '*.ttf' ) as $ttf ) {
	if ( in_array( basename( $ttf ), $tutulacak, true ) ) {
		continue;
	}

	unlink( $ttf );
	++$silinen;
}

printf(
	'Font onbellegi: %d hazir, %d yazi tipi atildi, kalan %.1f MB' . PHP_EOL,
	$sayac,
	$silinen,
	array_sum( array_map( 'filesize', glob( $dir . '*' ) ) ) / 1048576
);
