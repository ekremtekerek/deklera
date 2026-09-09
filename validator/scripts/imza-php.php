<?php
/**
 * Freemius imzasını SDK'nın kendi algoritmasıyla üretir.
 *
 * Node uygulamasıyla karşılaştırmak için; bkz. imza-capraz.mjs.
 * Tarih sabit verilir ki iki taraf aynı girdiyi imzalasın.
 *
 * Çalıştır: php scripts/imza-php.php "Tue, 09 Sep 2026 08:00:00 +0000"
 */

$date   = $argv[1] ?? 'Tue, 09 Sep 2026 08:00:00 +0000';
$secret = 'sk_sinav_gizli_anahtar';
$public = 'pk_sinav_acik_anahtar';
$id     = '38206';
$path   = '/v1/plugins/38206/installs/123456/license.json';

$string_to_sign = implode(
	"\n",
	array( 'GET', '', '', $date, $path )
);

// SDK ile birebir: hash_hmac onaltilik dizge dondurur, base64 ona uygulanir.
$signature = str_replace(
	'=',
	'',
	strtr( base64_encode( hash_hmac( 'sha256', $string_to_sign, $secret ) ), '+/', '-_' )
);

echo 'FS ' . $id . ':' . $public . ':' . $signature;
