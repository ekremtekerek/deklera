<?php
/**
 * Birim testleri için önyükleme.
 *
 * Testler WordPress kurulumu GEREKTİRMEZ. Denenen şeyler alan mantığı —
 * vergi kategorisi çözümlemesi, toplam aritmetiği, karar kuralları — ve bunlar
 * WordPress'e bağlı değildir. Tam bir WP test paketi kurmak, en kırılgan
 * mantığı test etmenin önüne engel koyardı.
 *
 * Sınıflar `defined( 'ABSPATH' ) || exit;` ile korunduğu için ABSPATH burada
 * tanımlanır; ihtiyaç duyulan avuç dolusu WordPress fonksiyonu da sahtelenir.
 *
 * @package Deklera
 */

declare( strict_types = 1 );

define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );
define( 'MINUTE_IN_SECONDS', 60 );

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

/**
 * Kayıtlı filtreler.
 *
 * Başlangıçta apply_filters() değeri olduğu gibi döndürüyordu. Bu, filtresi
 * olan davranışların sınanmasını imkânsız kılıyordu: HostedValidator'ın
 * çalışabilmesi için planın Pro olması gerekiyor ve plan yalnızca
 * `deklera/plan` filtresiyle zorlanabiliyor.
 *
 * @var array<string, callable[]>
 */
$GLOBALS['deklera_test_filters'] = array();

if ( ! function_exists( 'add_filter' ) ) {
	/**
	 * Filtre kaydeder.
	 *
	 * @param string   $tag      Kanca adı.
	 * @param callable $callback Geri çağrı.
	 * @return void
	 */
	function add_filter( string $tag, callable $callback ): void {
		$GLOBALS['deklera_test_filters'][ $tag ][] = $callback;
	}
}

if ( ! function_exists( 'deklera_test_reset_filters' ) ) {
	/**
	 * Kayıtlı filtreleri temizler. Testler birbirine sızmamalı.
	 *
	 * @return void
	 */
	function deklera_test_reset_filters(): void {
		$GLOBALS['deklera_test_filters'] = array();
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * Kayıtlı filtreleri sırayla uygular.
	 *
	 * @param string $tag   Kanca adı.
	 * @param mixed  $value Değer.
	 * @param mixed  ...$args Ek argümanlar.
	 * @return mixed
	 */
	function apply_filters( string $tag, $value, ...$args ) {
		foreach ( $GLOBALS['deklera_test_filters'][ $tag ] ?? array() as $callback ) {
			$value = $callback( $value, ...$args );
		}

		return $value;
	}
}

if ( ! function_exists( 'do_action' ) ) {
	/**
	 * Eylem tetiklemez.
	 *
	 * @param string $tag     Kanca adı.
	 * @param mixed  ...$args Argümanlar.
	 * @return void
	 */
	function do_action( string $tag, ...$args ): void {
		unset( $tag, $args );
	}
}

if ( ! function_exists( '__' ) ) {
	/**
	 * Çeviri yapmaz; metni olduğu gibi döndürür.
	 *
	 * @param string $text   Metin.
	 * @param string $domain Metin alanı.
	 * @return string
	 */
	function __( string $text, string $domain = 'default' ): string {
		unset( $domain );

		return $text;
	}
}

if ( ! function_exists( '_n' ) ) {
	/**
	 * Çoğul biçimi seçer.
	 *
	 * @param string $single Tekil.
	 * @param string $plural Çoğul.
	 * @param int    $number Sayı.
	 * @param string $domain Metin alanı.
	 * @return string
	 */
	function _n( string $single, string $plural, int $number, string $domain = 'default' ): string {
		unset( $domain );

		return 1 === $number ? $single : $plural;
	}
}

if ( ! function_exists( 'number_format_i18n' ) ) {
	/**
	 * Sayıyı biçimlendirir.
	 *
	 * @param float $number   Sayı.
	 * @param int   $decimals Ondalık basamak.
	 * @return string
	 */
	function number_format_i18n( float $number, int $decimals = 0 ): string {
		return number_format( $number, $decimals );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * JSON'a çevirir.
	 *
	 * @param mixed $data    Veri.
	 * @param int   $options Seçenekler.
	 * @param int   $depth   Derinlik.
	 * @return string|false
	 */
	function wp_json_encode( $data, int $options = 0, int $depth = 512 ) {
		return json_encode( $data, $options, $depth );
	}
}

/*
 * Seçenek deposu. Gerçek WordPress yerine bellekte tutulur; ayar okuyan
 * sınıflar böylece veritabanı olmadan sınanabiliyor.
 */
$GLOBALS['deklera_test_options'] = array();

if ( ! function_exists( 'get_option' ) ) {
	/**
	 * Seçenek okur.
	 *
	 * @param string $name    Ad.
	 * @param mixed  $default Varsayılan.
	 * @return mixed
	 */
	function get_option( string $name, $default = false ) {
		return $GLOBALS['deklera_test_options'][ $name ] ?? $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	/**
	 * Seçenek yazar.
	 *
	 * @param string $name     Ad.
	 * @param mixed  $value    Değer.
	 * @param mixed  $autoload Otomatik yükleme.
	 * @return bool
	 */
	function update_option( string $name, $value, $autoload = null ): bool {
		unset( $autoload );

		$GLOBALS['deklera_test_options'][ $name ] = $value;

		return true;
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	/**
	 * Seçenek siler.
	 *
	 * @param string $name Ad.
	 * @return bool
	 */
	function delete_option( string $name ): bool {
		unset( $GLOBALS['deklera_test_options'][ $name ] );

		return true;
	}
}

/**
 * Sıradaki HTTP yanıtları. Testler doldurur, wp_remote_post() tüketir.
 *
 * @var array<int, mixed>
 */
$GLOBALS['deklera_test_http'] = array();

/**
 * Gönderilen istekler; sıra ve gövde denetlenebilsin diye.
 *
 * @var array<int, array<string,mixed>>
 */
$GLOBALS['deklera_test_http_requests'] = array();

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * WP_Error'un sınamaya yetecek kadarı.
	 */
	class WP_Error {

		/**
		 * Kurucu.
		 *
		 * @param string $code    Hata kodu.
		 * @param string $message Mesaj.
		 */
		public function __construct( private string $code = '', private string $message = '' ) {}

		/**
		 * Mesajı döndürür.
		 *
		 * @return string
		 */
		public function get_error_message(): string {
			return $this->message;
		}

		/**
		 * Kodu döndürür.
		 *
		 * @return string
		 */
		public function get_error_code(): string {
			return $this->code;
		}
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	/**
	 * Değer WP_Error mi.
	 *
	 * @param mixed $thing Değer.
	 * @return bool
	 */
	function is_wp_error( $thing ): bool {
		return $thing instanceof WP_Error;
	}
}

if ( ! function_exists( 'wp_remote_post' ) ) {
	/**
	 * Ağa çıkmaz; kuyruktaki yanıtı döndürür ve isteği kaydeder.
	 *
	 * @param string               $url  Adres.
	 * @param array<string,mixed>  $args Argümanlar.
	 * @return array<string,mixed>|WP_Error
	 */
	function wp_remote_post( string $url, array $args = array() ) {
		$GLOBALS['deklera_test_http_requests'][] = array(
			'url'  => $url,
			'args' => $args,
		);

		if ( array() === $GLOBALS['deklera_test_http'] ) {
			return new WP_Error( 'http_request_failed', 'Kuyrukta yanıt kalmadı.' );
		}

		return array_shift( $GLOBALS['deklera_test_http'] );
	}
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
	/**
	 * Yanıtın HTTP durumunu döndürür.
	 *
	 * @param array<string,mixed>|WP_Error $response Yanıt.
	 * @return int
	 */
	function wp_remote_retrieve_response_code( $response ): int {
		return is_array( $response ) ? (int) ( $response['response']['code'] ?? 0 ) : 0;
	}
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	/**
	 * Yanıtın gövdesini döndürür.
	 *
	 * @param array<string,mixed>|WP_Error $response Yanıt.
	 * @return string
	 */
	function wp_remote_retrieve_body( $response ): string {
		return is_array( $response ) ? (string) ( $response['body'] ?? '' ) : '';
	}
}

if ( ! function_exists( 'trailingslashit' ) ) {
	/**
	 * Sonuna tek bir eğik çizgi koyar.
	 *
	 * @param string $value Değer.
	 * @return string
	 */
	function trailingslashit( string $value ): string {
		return rtrim( $value, "/\\" ) . '/';
	}
}

if ( ! function_exists( 'wc_get_base_location' ) ) {
	/**
	 * WooCommerce'in mağaza konumu.
	 *
	 * Çekirdek, ülke ve bölgeyi tek seçenekte "DE:BE" biçiminde saklar ve bu
	 * fonksiyonda ayırır. Kural kodu ayrımı kendisi yapmasın diye taklit de
	 * aynısını yapar.
	 *
	 * @return array{country:string,state:string}
	 */
	function wc_get_base_location(): array {
		$raw   = (string) get_option( 'woocommerce_default_country', '' );
		$parts = explode( ':', $raw );

		return array(
			'country' => $parts[0],
			'state'   => $parts[1] ?? '',
		);
	}
}

if ( ! function_exists( 'get_available_languages' ) ) {
	/**
	 * Sitede kurulu dillerin listesi.
	 *
	 * WordPress bunu WP_LANG_DIR'i tarayarak uretir. Testlerde diskte dosya
	 * aramak yerine dogrudan verilir; kurali ilgilendiren tek sey listenin
	 * icerigidir.
	 *
	 * @return string[]
	 */
	function get_available_languages(): array {
		return (array) ( $GLOBALS['deklera_test_languages'] ?? array() );
	}
}

if ( ! class_exists( 'WC_Order' ) ) {
	/**
	 * WooCommerce siparisinin testler icin yeterli taklidi.
	 *
	 * Yalnizca kurallarin okudugu alanlar var; tam bir taklit yazmak
	 * WooCommerce'in davranisini ikinci kez uygulamak olurdu ve o kopya
	 * gercekten sapardi.
	 */
	class WC_Order {

		/**
		 * Siparis kimligi.
		 *
		 * @var int
		 */
		private int $id;

		/**
		 * Fatura ulkesi.
		 *
		 * @var string
		 */
		private string $country = '';

		/**
		 * Meta degerleri.
		 *
		 * @var array<string,string>
		 */
		private array $meta = array();

		/**
		 * Kurucu.
		 *
		 * @param int $id Siparis kimligi.
		 */
		public function __construct( int $id = 1 ) {
			$this->id = $id;
		}

		/**
		 * Kimligi dondurur.
		 *
		 * @return int
		 */
		public function get_id(): int {
			return $this->id;
		}

		/**
		 * Fatura ulkesini belirler.
		 *
		 * @param string $country Ulke kodu.
		 * @return void
		 */
		public function set_billing_country( string $country ): void {
			$this->country = $country;
		}

		/**
		 * Fatura ulkesini dondurur.
		 *
		 * @return string
		 */
		public function get_billing_country(): string {
			return $this->country;
		}

		/**
		 * Meta degeri belirler.
		 *
		 * @param string $key   Anahtar.
		 * @param string $value Deger.
		 * @return void
		 */
		public function set_meta( string $key, string $value ): void {
			$this->meta[ $key ] = $value;
		}

		/**
		 * Meta degeri dondurur.
		 *
		 * @param string $key Anahtar.
		 * @return string
		 */
		public function get_meta( string $key = '' ) {
			return $this->meta[ $key ] ?? '';
		}
	}
}

if ( ! function_exists( 'get_locale' ) ) {
	/**
	 * Sitenin dili.
	 *
	 * @return string
	 */
	function get_locale(): string {
		return (string) ( $GLOBALS['deklera_test_locale'] ?? 'en_US' );
	}
}
