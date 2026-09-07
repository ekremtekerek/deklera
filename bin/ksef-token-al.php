<?php
/**
 * KSeF api-test ortamindan KALICI bir KSeF jetonu uretir.
 *
 * NEDEN BU BETIK VAR
 *
 * bin/ksef-live-test.php XAdES ile bir ERISIM jetonu (kisa omurlu JWT) alir.
 * Ama gercek kullanici XAdES kullanmaz: KSeF portalindan bir JETON alip
 * eklentinin ayar kutusuna yapistirir, eklenti de onu her oturumda erisim
 * jetonuna cevirir (Client::authenticate).
 *
 * Yani iki ayri yol var ve eklentinin uretimde kullandigi yol, dev
 * betiklerinde hic kosulmuyordu. 0.2.0'da bu tam olarak basimiza geldi:
 * 85 birim test geciyor, canli gonderim calisiyor, WordPress'te hicbir fatura
 * gitmiyordu. Bkz. docs/RELEASE.md, "Polonya: gercek gonderim sinavi".
 *
 * Bu betik o bosluğu kapatir: elde bulunan erisim jetonuyla API'den kalici bir
 * KSeF jetonu ister, boylece WordPress yolu gercek veriyle kosulabilir.
 *
 * SADECE GELISTIRME ARACIDIR; eklenti paketine girmez.
 *
 * Jeton bir kimlik bilgisidir. Ekrana YAZILMAZ; yalnizca dosyaya gider.
 *
 * Calistir:
 *   docker compose run --rm -T composer php /repo/bin/ksef-token-al.php
 *
 * @package Deklera
 */

declare( strict_types = 1 );

require dirname( __DIR__ ) . '/plugin/deklera/vendor/autoload.php';

const BASE_URL = 'https://api-test.ksef.mf.gov.pl/v2';

$erisim_dosyasi = dirname( __DIR__ ) . '/build/ksef-access-token.txt';

if ( ! is_file( $erisim_dosyasi ) ) {
	echo "Erisim jetonu yok. Once: php bin/ksef-live-test.php\n";
	exit( 1 );
}

$erisim = trim( (string) file_get_contents( $erisim_dosyasi ) );

/**
 * Basit istek yardimcisi.
 *
 * @param string $method Yontem.
 * @param string $path   Yol.
 * @param string $token  Erisim jetonu.
 * @param array  $body   Govde.
 * @return array{0:int,1:array}
 */
function istek( string $method, string $path, string $token, array $body = array() ): array {
	$curl = curl_init( BASE_URL . $path );

	$basliklar = array(
		'Authorization: Bearer ' . $token,
		'Accept: application/json',
	);

	$secenekler = array(
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_CUSTOMREQUEST  => $method,
		CURLOPT_TIMEOUT        => 30,
	);

	if ( array() !== $body ) {
		$basliklar[]                    = 'Content-Type: application/json';
		$secenekler[ CURLOPT_POSTFIELDS ] = (string) json_encode( $body );
	}

	$secenekler[ CURLOPT_HTTPHEADER ] = $basliklar;

	curl_setopt_array( $curl, $secenekler );

	$ham    = (string) curl_exec( $curl );
	$durum  = (int) curl_getinfo( $curl, CURLINFO_HTTP_CODE );
	$cozulm = json_decode( $ham, true );

	return array( $durum, is_array( $cozulm ) ? $cozulm : array( 'ham' => $ham ) );
}

echo "=== KSeF jetonu isteniyor ===\n";

list( $durum, $cevap ) = istek(
	'POST',
	'/tokens',
	$erisim,
	array(
		'permissions' => array( 'InvoiceWrite', 'InvoiceRead' ),
		'description' => 'Deklera WordPress yolu sinavi',
	)
);

printf( "  HTTP %d\n", $durum );

if ( 200 !== $durum && 201 !== $durum && 202 !== $durum ) {
	echo '  cevap: ' . substr( (string) json_encode( $cevap ), 0, 400 ) . "\n";
	exit( 1 );
}

$jeton = (string) ( $cevap['token'] ?? '' );
$referans = (string) ( $cevap['referenceNumber'] ?? '' );

if ( '' === $jeton ) {
	echo "  Cevapta jeton yok. Alanlar: " . implode( ', ', array_keys( $cevap ) ) . "\n";
	exit( 1 );
}

$hedef = dirname( __DIR__ ) . '/build/ksef-token.txt';
file_put_contents( $hedef, $jeton );

printf( "  referans               %s\n", $referans );
printf( "  jeton                  %d karakter (ekrana yazilmadi)\n", strlen( $jeton ) );
printf( "\nJeton %s dosyasina yazildi.\n", $hedef );
