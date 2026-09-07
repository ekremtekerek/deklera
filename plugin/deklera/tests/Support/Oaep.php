<?php
/**
 * Testler için EME-OAEP çözücüsü.
 *
 * @package Deklera
 */

declare( strict_types = 1 );

namespace Deklera\Tests\Support;

/**
 * RSA-OAEP (SHA-256) ile sarmalanmış bir bloğu, PHP sürümünden bağımsız çözer.
 *
 * NEDEN VAR
 *
 * `openssl_private_decrypt()` OAEP özetini seçmeyi ancak **PHP 8.5.0**'da
 * öğrendi. Daha eski sürümlerde OpenSSL'e "SHA-256 ile çöz" denemiyor; SHA-1
 * varsayıyor ve bloğu çözemiyor.
 *
 * Bu yüzden testler uzun süre şöyle yazılmıştı:
 *
 *     if ( PHP_VERSION_ID < 80500 ) { $this->markTestSkipped( ... ); }
 *
 * Sonuç ters çıktı. Eklenti PHP 8.2 istiyor ve gerçek kullanıcıların çoğu
 * 8.2–8.4'te; yani `Encryption`'ın KENDİ yazdığımız OAEP dalında. O dal tam da
 * müşterilerin bulunduğu sürümlerde atlanıyordu — sınanan tek şey, kimsenin
 * kullanmadığı yerel yoldu. `test_both_wrapping_paths_produce_a_valid_key`
 * gövdesinde bunu açıkça anlatıyor ve ardından o sürümlerde atlanıyordu.
 *
 * Çözüm atlamak değil, çözmek: RFC 8017 §7.1.2 kod çözme adımları burada.
 * Kodlayıcının aynası olduğu için bağımsız bir kontrol sağlar — ikisi birden
 * aynı yanlışı yapmadıkça hata yakalanır.
 */
final class Oaep {

	/**
	 * Özet algoritması. KSeF SHA-256 bekliyor.
	 */
	private const DIGEST = 'sha256';

	/**
	 * Özet uzunluğu (bayt).
	 */
	private const HASH_BYTES = 32;

	/**
	 * Sarmalanmış bloğu çözer.
	 *
	 * @param string $ciphertext  RSA çıktısı; uzunluğu modülüs kadar.
	 * @param string $private_key PEM biçiminde özel anahtar.
	 * @return string Sarmalanan veri.
	 * @throws \RuntimeException Anahtar okunamazsa ya da blok bozuksa.
	 */
	public static function decrypt( string $ciphertext, string $private_key ): string {
		$key = \openssl_pkey_get_private( $private_key );

		if ( false === $key ) {
			throw new \RuntimeException( 'Özel anahtar okunamadı.' );
		}

		$details = \openssl_pkey_get_details( $key );

		if ( false === $details ) {
			throw new \RuntimeException( 'Anahtar ayrıntıları alınamadı.' );
		}

		$modulus_bytes = (int) ( $details['bits'] / 8 );

		$raw = '';

		if ( ! \openssl_private_decrypt( $ciphertext, $raw, $key, OPENSSL_NO_PADDING ) ) {
			throw new \RuntimeException( 'Ham RSA çözümü başarısız oldu.' );
		}

		/*
		 * OPENSSL_NO_PADDING bazi yapilarda bastaki sifir bayti kirpiyor.
		 * Blok modulus uzunlugunda olmali; eksikse basa sifir eklenir.
		 */
		return self::decode( str_pad( $raw, $modulus_bytes, "\x00", STR_PAD_LEFT ) );
	}

	/**
	 * EME-OAEP kodunu çözer. RFC 8017, §7.1.2.
	 *
	 * @param string $block Kodlanmış blok; uzunluğu modülüs kadar.
	 * @return string
	 * @throws \RuntimeException Blok beklenen yapıda değilse.
	 */
	private static function decode( string $block ): string {
		$masked_seed       = substr( $block, 1, self::HASH_BYTES );
		$masked_data_block = substr( $block, 1 + self::HASH_BYTES );

		$seed       = $masked_seed ^ self::mgf1( $masked_data_block, self::HASH_BYTES );
		$data_block = $masked_data_block ^ self::mgf1( $seed, strlen( $masked_data_block ) );

		// Etiket bostur; lHash bos dizgenin ozeti olmali.
		if ( ! hash_equals( hash( self::DIGEST, '', true ), substr( $data_block, 0, self::HASH_BYTES ) ) ) {
			throw new \RuntimeException( 'lHash uyuşmuyor; blok OAEP-SHA256 değil ya da bozuk.' );
		}

		$separator = strpos( $data_block, "\x01", self::HASH_BYTES );

		if ( false === $separator ) {
			throw new \RuntimeException( 'Dolgu ile veriyi ayıran 0x01 baytı bulunamadı.' );
		}

		return substr( $data_block, $separator + 1 );
	}

	/**
	 * MGF1 maske üretme işlevi. RFC 8017, ek B.2.1.
	 *
	 * @param string $seed   Tohum.
	 * @param int    $length İstenen maske uzunluğu (bayt).
	 * @return string
	 */
	private static function mgf1( string $seed, int $length ): string {
		$mask = '';

		$blocks = (int) ceil( $length / self::HASH_BYTES );

		for ( $counter = 0; $counter < $blocks; $counter++ ) {
			$mask .= hash( self::DIGEST, $seed . pack( 'N', $counter ), true );
		}

		return substr( $mask, 0, $length );
	}
}
