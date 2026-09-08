<?php
/**
 * WordPress.org eklenti banner'ini uretir.
 *
 * NEDEN BU BETIK VAR
 *
 * Dizin sayfasinda banner basligin uzerinde tam genislikte durur; olmayinca
 * yerine duz bir renk cikar ve eklenti yarim birakilmis gorunur. Ikonlar
 * vardi, banner yoktu.
 *
 * Tasarim ikonla ayni motifi tasir (bkz. bin/make-icon.mjs): saga hibrit
 * belge -- ust yarida insanin okudugu satirlar, alt yarida makinenin okudugu
 * serit. Ayni fikri iki yerde tekrarlamak, dizin sayfasinda ikon ile banner'in
 * ayni urune ait oldugunu bir bakista soyler.
 *
 * Yazi tipi eklentinin zaten pakete koydugu DejaVuSans'tir; ayri bir font
 * indirmemek icin. vendor-prefixed/ bir yapi artefaktidir, yani once
 * "sh bin/deps.sh plugin/deklera" calismis olmali.
 *
 * Calistir:
 *   docker compose run --rm -T --entrypoint php wpcli /repo/bin/make-banner.php
 *
 * @package Deklera
 */

$kok = dirname( __DIR__ );

$font_dizini = $kok . '/plugin/deklera/vendor-prefixed/setasign/tfpdf/font/unifont/';
$font_kalin  = $font_dizini . 'DejaVuSans-Bold.ttf';
$font_duz    = $font_dizini . 'DejaVuSans.ttf';

foreach ( array( $font_kalin, $font_duz ) as $f ) {
	if ( ! is_file( $f ) ) {
		fwrite( STDERR, 'HATA: yazi tipi yok: ' . $f . PHP_EOL );
		fwrite( STDERR, 'Once: docker compose run --rm --entrypoint sh composer -c "sh /repo/bin/deps.sh /repo/plugin/deklera"' . PHP_EOL );
		exit( 1 );
	}
}

/*
 * WordPress.org iki boyut ister: 772x250 ve retina icin 1544x500. Ikisi de
 * AYNI tasarim uzayindan uretilir; hazir bir PNG'yi kucultmek metni bulaniklastirir.
 */
const TASARIM_G = 1544;
const TASARIM_Y = 500;
const BOYUTLAR  = array(
	array( 772, 250 ),
	array( 1544, 500 ),
);

// Asiri ornekleme: kenarlar ve yazi tipi boylece yumusak cikar.
const SS = 2;

/**
 * Yuvarlatilmis dolu dikdortgen cizer.
 *
 * @param \GdImage $im Resim.
 * @param float    $x0 Sol.
 * @param float    $y0 Ust.
 * @param float    $x1 Sag.
 * @param float    $y1 Alt.
 * @param float    $r  Kose yaricapi.
 * @param int      $c  Renk.
 * @return void
 */
function yuvarlak_kutu( $im, float $x0, float $y0, float $x1, float $y1, float $r, int $c ): void {
	$x0 = (int) round( $x0 );
	$y0 = (int) round( $y0 );
	$x1 = (int) round( $x1 );
	$y1 = (int) round( $y1 );
	$r  = (int) round( $r );

	imagefilledrectangle( $im, $x0 + $r, $y0, $x1 - $r, $y1, $c );
	imagefilledrectangle( $im, $x0, $y0 + $r, $x1, $y1 - $r, $c );

	$d = $r * 2;
	imagefilledellipse( $im, $x0 + $r, $y0 + $r, $d, $d, $c );
	imagefilledellipse( $im, $x1 - $r, $y0 + $r, $d, $d, $c );
	imagefilledellipse( $im, $x0 + $r, $y1 - $r, $d, $d, $c );
	imagefilledellipse( $im, $x1 - $r, $y1 - $r, $d, $d, $c );
}

/**
 * Makine okunur seridin cubuklarini uretir.
 *
 * Desen sabittir; her uretimde ayni banner cikmali. Genislikler
 * bin/make-icon.mjs ile aynidir.
 *
 * @param float $x0 Baslangic.
 * @param float $x1 Bitis.
 * @return array<int,array{0:float,1:float}>
 */
function serit( float $x0, float $x1 ): array {
	$genislikler = array( 7, 3, 4, 9, 3, 6, 3, 8, 4, 3, 7, 5 );
	$bosluk      = 4;
	$toplam      = array_sum( $genislikler ) + $bosluk * ( count( $genislikler ) - 1 );
	$olcek       = ( $x1 - $x0 ) / $toplam;

	$cubuklar = array();
	$imlec    = $x0;

	foreach ( $genislikler as $g ) {
		$w          = $g * $olcek;
		$cubuklar[] = array( $imlec, $imlec + $w );
		$imlec     += $w + $bosluk * $olcek;
	}

	return $cubuklar;
}

/**
 * Banner'i verilen boyutta cizer.
 *
 * @param int    $genislik   Hedef genislik.
 * @param int    $yukseklik  Hedef yukseklik.
 * @param string $font_kalin Kalin yazi tipi.
 * @param string $font_duz   Duz yazi tipi.
 * @return \GdImage
 */
function ciz( int $genislik, int $yukseklik, string $font_kalin, string $font_duz ) {
	$g = $genislik * SS;
	$y = $yukseklik * SS;
	$k = ( $genislik / TASARIM_G ) * SS;

	$im = imagecreatetruecolor( $g, $y );
	imagealphablending( $im, true );

	// Fiskal defter yesili; ikonla ayni.
	$zemin = imagecolorallocate( $im, 15, 107, 85 );
	$kagit = imagecolorallocate( $im, 255, 255, 255 );
	$cizgi = imagecolorallocate( $im, 154, 190, 178 );
	$soluk = imagecolorallocate( $im, 190, 216, 207 );

	imagefilledrectangle( $im, 0, 0, $g, $y, $zemin );

	// --- sag taraf: hibrit belge motifi ---
	$bx0 = 1120 * $k;
	$by0 = 62 * $k;
	$bx1 = 1424 * $k;
	$by1 = 438 * $k;

	yuvarlak_kutu( $im, $bx0, $by0, $bx1, $by1, 22 * $k, $kagit );

	// Insanin okudugu satirlar.
	foreach ( array( array( 120, 96 ), array( 145, 78 ), array( 170, 88 ) ) as $satir ) {
		yuvarlak_kutu(
			$im,
			1162 * $k,
			( $satir[0] - 6 ) * $k,
			( 1162 + $satir[1] * 1.7 ) * $k,
			( $satir[0] + 6 ) * $k,
			6 * $k,
			$cizgi
		);
	}

	// Makinenin okudugu serit.
	foreach ( serit( 1162 * $k, 1382 * $k ) as $cubuk ) {
		imagefilledrectangle(
			$im,
			(int) round( $cubuk[0] ),
			(int) round( 240 * $k ),
			(int) round( $cubuk[1] ),
			(int) round( 396 * $k ),
			$zemin
		);
	}

	// --- sol taraf: ad ve tanim ---
	imagettftext( $im, 128 * $k, 0, (int) round( 104 * $k ), (int) round( 250 * $k ), $kagit, $font_kalin, 'Deklera' );
	imagettftext( $im, 36 * $k, 0, (int) round( 110 * $k ), (int) round( 318 * $k ), $soluk, $font_duz, 'EU e-invoicing for WooCommerce' );

	/*
	 * Ucuncu satir urunun farkini soyler. Banner dar ekranda saga dogru
	 * kirpilabildigi icin cumle kisa ve sol hizali tutuldu.
	 */
	imagettftext( $im, 30 * $k, 0, (int) round( 110 * $k ), (int) round( 392 * $k ), $cizgi, $font_duz, 'Factur-X  ·  XRechnung  ·  KSeF  ·  EN 16931' );

	if ( 1 === SS ) {
		return $im;
	}

	$kucuk = imagecreatetruecolor( $genislik, $yukseklik );
	imagecopyresampled( $kucuk, $im, 0, 0, 0, 0, $genislik, $yukseklik, $g, $y );
	imagedestroy( $im );

	return $kucuk;
}

foreach ( BOYUTLAR as $boyut ) {
	list( $genislik, $yukseklik ) = $boyut;

	$im  = ciz( $genislik, $yukseklik, $font_kalin, $font_duz );
	$ad  = sprintf( 'banner-%dx%d.png', $genislik, $yukseklik );
	$yol = $kok . '/assets/' . $ad;

	imagepng( $im, $yol, 9 );
	imagedestroy( $im );

	printf( "  %-22s %7d bayt%s", $ad, filesize( $yol ), PHP_EOL );
}

echo PHP_EOL . 'assets/ hazir.' . PHP_EOL;
