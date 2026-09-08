<?php
/**
 * .pot sablonundan .po dosyalarini uretir.
 *
 * NEDEN BU BETIK VAR
 *
 * Ceviriler elle .po yazarak degil, kaynak dizge -> ceviri esleme
 * dosyalarindan uretiliyor (bin/ceviri/<locale>.php). Sebep bakim:
 * .pot her surumde yeniden uretilir ve satir numaralari, dosya
 * referanslari, yeni dizgeler degisir. Esleme dosyasi bunlardan
 * etkilenmez; yalnizca dizgenin KENDISI anahtar olur.
 *
 * Ayrica eksik cevirileri sayar. Sessizce bos birakilan bir dizge,
 * kullanicinin ekraninda Ingilizce olarak belirir ve kimse fark etmez.
 *
 * Kullanim: php po-uret.php <eklenti-dizini> [locale ...]
 */

$plugin = $argv[1] ?? '';
$dir    = rtrim( $plugin, '/' ) . '/languages/';
$pot    = $dir . 'deklera.pot';

if ( ! is_file( $pot ) ) {
	fwrite( STDERR, 'HATA: sablon yok: ' . $pot . PHP_EOL );
	exit( 1 );
}

$locales = array_slice( $argv, 2 );

if ( array() === $locales ) {
	$locales = array( 'de_DE', 'fr_FR', 'pl_PL' );
}

/*
 * Cogul kurallari. Yanlis nplurals ile uretilen .po, msgfmt tarafindan
 * reddedilir; Lehce ucuncu bir bicim ister.
 */
$plural_forms = array(
	'de_DE' => 'nplurals=2; plural=(n != 1);',
	'fr_FR' => 'nplurals=2; plural=(n > 1);',
	'pl_PL' => 'nplurals=3; plural=(n==1 ? 0 : n%10>=2 && n%10<=4 && (n%100<12 || n%100>14) ? 1 : 2);',
	'es_ES' => 'nplurals=2; plural=(n != 1);',
	'it_IT' => 'nplurals=2; plural=(n != 1);',
	'nl_NL' => 'nplurals=2; plural=(n != 1);',
);

$dil_adi = array(
	'de_DE' => 'German',
	'fr_FR' => 'French',
	'pl_PL' => 'Polish',
	'es_ES' => 'Spanish',
	'it_IT' => 'Italian',
	'nl_NL' => 'Dutch',
);

/**
 * .po kacislarini cozer.
 *
 * @param string $s Kacisli dizge.
 * @return string
 */
function coz( string $s ): string {
	return str_replace(
		array( '\\n', '\\t', '\\"', '\\\\' ),
		array( "\n", "\t", '"', '\\' ),
		$s
	);
}

/**
 * .po icin kacislar.
 *
 * @param string $s Ham dizge.
 * @return string
 */
function kacis( string $s ): string {
	return str_replace(
		array( '\\', '"', "\n", "\t" ),
		array( '\\\\', '\\"', '\\n', '\\t' ),
		$s
	);
}

$sablon = file( $pot, FILE_IGNORE_NEW_LINES );

foreach ( $locales as $locale ) {
	$harita_dosyasi = __DIR__ . '/ceviri/' . $locale . '.php';

	if ( ! is_file( $harita_dosyasi ) ) {
		fwrite( STDERR, 'ATLANDI: esleme yok: ' . $harita_dosyasi . PHP_EOL );
		continue;
	}

	$harita = require $harita_dosyasi;
	$cogul  = $harita['__cogul__'] ?? array();
	unset( $harita['__cogul__'] );

	$nplurals = (int) preg_replace( '/^nplurals=(\d+).*$/', '$1', $plural_forms[ $locale ] ?? 'nplurals=2;' );

	$cikti   = array();
	$eksik   = array();
	$toplam  = 0;
	$basligi = false;

	for ( $i = 0, $n = count( $sablon ); $i < $n; $i++ ) {
		$satir = $sablon[ $i ];

		// Baslik bloğu locale'e gore yeniden yazilir.
		if ( ! $basligi && 'msgid ""' === $satir ) {
			$cikti[] = 'msgid ""';
			$cikti[] = 'msgstr ""';
			$cikti[] = '"Project-Id-Version: Deklera\\n"';
			$cikti[] = '"Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/deklera\\n"';
			$cikti[] = '"MIME-Version: 1.0\\n"';
			$cikti[] = '"Content-Type: text/plain; charset=UTF-8\\n"';
			$cikti[] = '"Content-Transfer-Encoding: 8bit\\n"';
			$cikti[] = '"Language: ' . $locale . '\\n"';
			$cikti[] = '"Language-Team: ' . ( $dil_adi[ $locale ] ?? $locale ) . '\\n"';
			$cikti[] = '"PO-Revision-Date: ' . gmdate( 'Y-m-d H:i:sO' ) . '\\n"';
			$cikti[] = '"Last-Translator: Hasan Ekrem Tekerek\\n"';
			$cikti[] = '"Plural-Forms: ' . ( $plural_forms[ $locale ] ?? 'nplurals=2; plural=(n != 1);' ) . '\\n"';
			$cikti[] = '"X-Domain: deklera\\n"';

			// Sablonun kendi baslik govdesini atla.
			while ( $i + 1 < $n && '' !== $sablon[ $i + 1 ] ) {
				$i++;
			}

			$basligi = true;
			continue;
		}

		if ( 0 !== strpos( $satir, 'msgid "' ) ) {
			// msgstr satirlari asagida uretiliyor; sablondakiler atilir.
			if ( 0 === strpos( $satir, 'msgstr' ) ) {
				continue;
			}

			$cikti[] = $satir;
			continue;
		}

		$anahtar = coz( substr( $satir, 7, -1 ) );
		$cikti[] = $satir;
		++$toplam;

		// Cogul mu?
		if ( $i + 1 < $n && 0 === strpos( $sablon[ $i + 1 ], 'msgid_plural "' ) ) {
			$cikti[] = $sablon[ ++$i ];
			$bicimler = $cogul[ $anahtar ] ?? array();

			for ( $f = 0; $f < $nplurals; $f++ ) {
				$deger = $bicimler[ $f ] ?? '';
				$cikti[] = 'msgstr[' . $f . '] "' . kacis( $deger ) . '"';
			}

			if ( array() === $bicimler ) {
				$eksik[] = $anahtar;
			}

			continue;
		}

		$deger = $harita[ $anahtar ] ?? '';

		if ( '' === $deger ) {
			$eksik[] = $anahtar;
		}

		$cikti[] = 'msgstr "' . kacis( $deger ) . '"';
	}

	$hedef = $dir . 'deklera-' . $locale . '.po';
	file_put_contents( $hedef, implode( PHP_EOL, $cikti ) . PHP_EOL );

	printf( '%s : %d dizeden %d cevrildi', $locale, $toplam, $toplam - count( $eksik ) );

	if ( array() !== $eksik ) {
		printf( ', %d EKSIK', count( $eksik ) );

		foreach ( array_slice( $eksik, 0, 5 ) as $e ) {
			printf( PHP_EOL . '    eksik: %s', mb_substr( $e, 0, 70 ) );
		}
	}

	echo PHP_EOL;
}
