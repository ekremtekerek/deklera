<?php
/**
 * Ön uçuş kontrolü yönetici sayfası.
 *
 * @package Deklera
 */

declare( strict_types = 1 );

namespace Deklera\Admin;

use Deklera\Invoice\Profile;
use Deklera\Ksef\Settings;
use Deklera\License\Licensing;
use Deklera\Preflight\Finding;
use Deklera\Preflight\Report;
use Deklera\Preflight\Scanner;
use Deklera\Validation\HostedValidator;

defined( 'ABSPATH' ) || exit;

/**
 * Ürünün ilk izlenimini veren ekran.
 *
 * Kullanıcı hiçbir yapılandırma yapmadan buraya gelir ve 60 saniye içinde kaç
 * siparişinin reddedileceğini görür. Bu ekran satışın kendisidir.
 */
final class PreflightPage {

	/**
	 * Yönetici sayfası tanımlayıcısı.
	 */
	private const SLUG = 'deklera';

	/**
	 * Tarama sonucunun önbellek anahtarı.
	 */
	private const CACHE_KEY = 'deklera_preflight_report';

	/**
	 * Bir grupta gösterilecek en fazla sipariş bağlantısı.
	 */
	private const MAX_LINKS = 10;

	/**
	 * Kullanım kılavuzu.
	 *
	 * Ekranda anlatılamayacak kadar uzun olan her şey orada: ön uçuş
	 * bulgularının ne anlama geldiği, belgelerin nereye yazıldığı, iade
	 * faturaları, Polonya akışı, süzgeçler.
	 */
	private const GUIDE_URL = 'https://github.com/ekremtekerek/deklera/blob/main/docs/GUIDE.md';

	/**
	 * Kancaları kaydeder.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'add_menu' ), 60 );
		add_action( 'admin_post_deklera_save_settings', array( self::class, 'save_settings' ) );
		add_action( 'admin_post_deklera_rescan', array( self::class, 'rescan' ) );
	}

	/**
	 * Menü kaydını yapar.
	 *
	 * @return void
	 */
	public static function add_menu(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Deklera e-invoicing', 'deklera' ),
			__( 'Deklera', 'deklera' ),
			'manage_woocommerce',
			self::SLUG,
			array( self::class, 'render' )
		);
	}

	/**
	 * Ayarları kaydeder.
	 *
	 * @return void
	 */
	public static function save_settings(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You are not allowed to change these settings.', 'deklera' ) );
		}

		check_admin_referer( 'deklera_save_settings' );

		$vat_number = isset( $_POST['deklera_seller_vat_number'] )
			? sanitize_text_field( wp_unslash( $_POST['deklera_seller_vat_number'] ) )
			: '';

		$vat_number = strtoupper( (string) preg_replace( '/[^A-Za-z0-9]/', '', $vat_number ) );

		update_option( 'deklera_seller_vat_number', $vat_number );

		$contact = isset( $_POST['deklera_seller_contact'] )
			? sanitize_text_field( wp_unslash( $_POST['deklera_seller_contact'] ) )
			: '';

		update_option( 'deklera_seller_contact', $contact );

		$phone = isset( $_POST['deklera_seller_phone'] )
			? sanitize_text_field( wp_unslash( $_POST['deklera_seller_phone'] ) )
			: '';

		update_option( 'deklera_seller_phone', $phone );

		$endpoint = isset( $_POST['deklera_validator_endpoint'] )
			? esc_url_raw( wp_unslash( $_POST['deklera_validator_endpoint'] ) )
			: '';

		update_option( HostedValidator::OPTION_ENDPOINT, untrailingslashit( $endpoint ) );

		/*
		 * Bos gonderilen anahtar mevcut degeri SILMEZ. Alan ekranda maskeli
		 * gosterildigi icin, kullanicinin ona dokunmadan formu kaydetmesi
		 * anahtari kaybetmesine yol acmamali.
		 */
		$key = isset( $_POST['deklera_validator_key'] )
			? sanitize_text_field( wp_unslash( $_POST['deklera_validator_key'] ) )
			: '';

		if ( '' !== $key ) {
			update_option( HostedValidator::OPTION_KEY, $key );
		}

		/*
		 * KSeF jetonu da ayni kurala tabi: bos gonderim mevcut jetonu SILMEZ,
		 * cunku alan ekranda maskeli. Jetonu silmek isteyenin bunu bilerek
		 * yapmasi gerekir; kazara kaybetmesi degil.
		 */
		$ksef_token = isset( $_POST['deklera_ksef_token'] )
			? sanitize_text_field( wp_unslash( $_POST['deklera_ksef_token'] ) )
			: '';

		if ( '' !== $ksef_token ) {
			Settings::set_token( $ksef_token );
		}

		$environment = isset( $_POST['deklera_ksef_environment'] )
			? sanitize_text_field( wp_unslash( $_POST['deklera_ksef_environment'] ) )
			: Settings::ENVIRONMENT_TEST;

		update_option(
			Settings::OPTION_ENVIRONMENT,
			Settings::ENVIRONMENT_PRODUCTION === $environment
				? Settings::ENVIRONMENT_PRODUCTION
				: Settings::ENVIRONMENT_TEST
		);

		delete_transient( self::CACHE_KEY );

		wp_safe_redirect( add_query_arg( 'deklera-saved', '1', self::url() ) );
		exit;
	}

	/**
	 * Önbelleği temizleyip yeniden tarar.
	 *
	 * @return void
	 */
	public static function rescan(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You are not allowed to run this scan.', 'deklera' ) );
		}

		check_admin_referer( 'deklera_rescan' );

		delete_transient( self::CACHE_KEY );

		wp_safe_redirect( self::url() );
		exit;
	}

	/**
	 * Sayfayı çizer.
	 *
	 * @return void
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$report = self::report();

		echo '<div class="wrap deklera-wrap">';
		printf( '<h1>%s</h1>', esc_html__( 'Deklera — e-invoicing pre-flight check', 'deklera' ) );

		if ( isset( $_GET['deklera-saved'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html__( 'Settings saved. The check was run again.', 'deklera' )
			);
		}

		self::render_styles();
		self::render_summary( $report );
		self::render_store_findings( $report );
		self::render_order_findings( $report );
		self::render_settings();

		echo '</div>';
	}

	/**
	 * Özet bloğunu çizer.
	 *
	 * @param Report $report Tarama raporu.
	 * @return void
	 */
	private static function render_summary( Report $report ): void {
		$blocked = $report->blocked_orders();

		echo '<div class="deklera-hero">';

		if ( 0 === $report->scanned ) {
			printf(
				'<p class="deklera-lede">%s</p>',
				esc_html__( 'There are no completed orders to check yet. Come back once you have taken your first order.', 'deklera' )
			);
		} elseif ( 0 === $blocked ) {
			printf(
				'<p class="deklera-lede deklera-ok">%s</p>',
				esc_html(
					sprintf(
						/* translators: %s: number of orders that were checked. */
						_n(
							'All %s recent order would be accepted.',
							'All %s recent orders would be accepted.',
							$report->scanned,
							'deklera'
						),
						number_format_i18n( $report->scanned )
					)
				)
			);
		} else {
			printf(
				'<p class="deklera-lede deklera-bad">%s</p>',
				esc_html(
					sprintf(
						/* translators: 1: number of orders that would be rejected, 2: number of orders checked. */
						_n(
							'%1$s of your last %2$s orders would be rejected.',
							'%1$s of your last %2$s orders would be rejected.',
							$blocked,
							'deklera'
						),
						number_format_i18n( $blocked ),
						number_format_i18n( $report->scanned )
					)
				)
			);
		}

		$stats = array(
			array( __( 'Checked', 'deklera' ), $report->scanned, '' ),
			array( __( 'Would be rejected', 'deklera' ), $blocked, 'bad' ),
			array( __( 'Needs review', 'deklera' ), $report->flagged_orders(), 'warn' ),
			array( __( 'Ready to invoice', 'deklera' ), $report->clean_orders(), 'ok' ),
		);

		echo '<div class="deklera-stats">';

		foreach ( $stats as $stat ) {
			printf(
				'<div class="deklera-stat deklera-%1$s"><span class="deklera-stat-value">%2$s</span><span class="deklera-stat-label">%3$s</span></div>',
				esc_attr( (string) $stat[2] ),
				esc_html( number_format_i18n( (int) $stat[1] ) ),
				esc_html( (string) $stat[0] )
			);
		}

		echo '</div>';

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'deklera_rescan' );
		echo '<input type="hidden" name="action" value="deklera_rescan"/>';
		printf( '<button type="submit" class="button">%s</button>', esc_html__( 'Run the check again', 'deklera' ) );
		echo '</form>';

		echo '</div>';
	}

	/**
	 * Mağaza genelindeki bulguları çizer.
	 *
	 * Bunlar tek bir ayar düzeltmesiyle çözülür ve bu yüzden listenin başında,
	 * sipariş bulgularından ayrı gösterilirler.
	 *
	 * @param Report $report Tarama raporu.
	 * @return void
	 */
	private static function render_store_findings( Report $report ): void {
		$findings = $report->store_findings();

		if ( array() === $findings ) {
			return;
		}

		printf( '<h2>%s</h2>', esc_html__( 'Fix these once, for the whole store', 'deklera' ) );

		foreach ( $findings as $finding ) {
			echo '<div class="deklera-group deklera-store">';
			self::render_finding_body( $finding, '' );
			echo '</div>';
		}
	}

	/**
	 * Sipariş bulgularını çizer.
	 *
	 * @param Report $report Tarama raporu.
	 * @return void
	 */
	private static function render_order_findings( Report $report ): void {
		$groups = $report->grouped();

		if ( array() === $groups ) {
			return;
		}

		printf( '<h2>%s</h2>', esc_html__( 'Order data that needs attention', 'deklera' ) );

		foreach ( $groups as $findings ) {
			$orders = Report::distinct_orders( $findings );

			$count = sprintf(
				/* translators: %s: number of affected orders. */
				_n( '%s order', '%s orders', $orders, 'deklera' ),
				number_format_i18n( $orders )
			);

			echo '<div class="deklera-group">';
			self::render_finding_body( $findings[0], $count );
			self::render_affected( $findings );
			echo '</div>';
		}
	}

	/**
	 * Tek bir bulgunun gövdesini çizer.
	 *
	 * @param Finding $finding Bulgu.
	 * @param string  $count   Etkilenen sipariş sayısı metni; boş bırakılabilir.
	 * @return void
	 */
	private static function render_finding_body( Finding $finding, string $count ): void {
		printf(
			'<h3><span class="deklera-badge deklera-%1$s">%2$s</span> %3$s <span class="deklera-count">%4$s</span></h3>',
			esc_attr( $finding->severity->value ),
			esc_html( $finding->severity->label() ),
			esc_html( self::rule_title( $finding->rule_id ) ),
			esc_html( $count )
		);

		printf( '<p class="deklera-what">%s</p>', esc_html( $finding->what ) );
		printf( '<p class="deklera-why">%s</p>', esc_html( $finding->why ) );
		printf(
			'<p class="deklera-fix"><strong>%1$s</strong> %2$s</p>',
			esc_html__( 'How to fix:', 'deklera' ),
			esc_html( $finding->fix )
		);

		if ( '' !== $finding->standard ) {
			printf(
				'<p class="deklera-standard">%1$s %2$s</p>',
				esc_html__( 'Rule:', 'deklera' ),
				esc_html( $finding->standard )
			);
		}
	}

	/**
	 * Etkilenen siparişlerin bağlantılarını çizer.
	 *
	 * @param Finding[] $findings Bulgular.
	 * @return void
	 */
	private static function render_affected( array $findings ): void {
		$seen = array();

		foreach ( $findings as $finding ) {
			$seen[ $finding->order_id ] = $finding;
		}

		$shown     = array_slice( $seen, 0, self::MAX_LINKS, true );
		$remaining = count( $seen ) - count( $shown );

		echo '<p class="deklera-orders">';
		printf( '%s ', esc_html__( 'Affected orders:', 'deklera' ) );

		foreach ( $shown as $order_id => $finding ) {
			$url = $finding->order_url();

			if ( '' === $url ) {
				printf( '<span>#%s</span> ', esc_html( (string) $order_id ) );
				continue;
			}

			printf( '<a href="%1$s">#%2$s</a> ', esc_url( $url ), esc_html( (string) $order_id ) );
		}

		if ( $remaining > 0 ) {
			printf(
				'<span class="deklera-more">%s</span>',
				esc_html(
					sprintf(
						/* translators: %s: number of additional affected orders. */
						_n( 'and %s more', 'and %s more', $remaining, 'deklera' ),
						number_format_i18n( $remaining )
					)
				)
			);
		}

		echo '</p>';
	}

	/**
	 * Ayar formunu çizer.
	 *
	 * @return void
	 */
	private static function render_settings(): void {
		printf( '<h2>%s</h2>', esc_html__( 'Settings', 'deklera' ) );

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="deklera-settings">';
		wp_nonce_field( 'deklera_save_settings' );
		echo '<input type="hidden" name="action" value="deklera_save_settings"/>';

		printf(
			'<p><label for="deklera_seller_vat_number"><strong>%1$s</strong></label><br/><input type="text" id="deklera_seller_vat_number" name="deklera_seller_vat_number" value="%2$s" class="regular-text" placeholder="FR12345678901"/><br/><span class="description">%3$s</span></p>',
			esc_html__( 'Your VAT number', 'deklera' ),
			esc_attr( (string) get_option( 'deklera_seller_vat_number', '' ) ),
			esc_html__( 'WooCommerce has no field for this, so Deklera stores it. Include the country prefix.', 'deklera' )
		);

		/*
		 * Bu iki alan EN 16931'de istege bagli, XRechnung'da ZORUNLU
		 * (BR-DE-5, BR-DE-6). Almanya'nin resmi denetleyicisi bunlar olmadan
		 * faturayi reddediyor; olcum docs/adr/0010-ulusal-kurallar.md'de.
		 */
		printf(
			'<p><label for="deklera_seller_contact">%1$s</label><br/><input type="text" id="deklera_seller_contact" name="deklera_seller_contact" value="%2$s" class="regular-text"/><br/><span class="description">%3$s</span></p>',
			esc_html__( 'Contact name on the invoice', 'deklera' ),
			esc_attr( (string) get_option( 'deklera_seller_contact', '' ) ),
			esc_html__( 'Leave empty to use the store name. Germany requires this field to be present.', 'deklera' )
		);

		printf(
			'<p><label for="deklera_seller_phone">%1$s</label><br/><input type="text" id="deklera_seller_phone" name="deklera_seller_phone" value="%2$s" class="regular-text" placeholder="+49 30 123456"/><br/><span class="description">%3$s</span></p>',
			esc_html__( 'Contact telephone number', 'deklera' ),
			esc_attr( (string) get_option( 'deklera_seller_phone', '' ) ),
			esc_html__( 'Required for German invoices (XRechnung). There is no sensible default, so it cannot be filled in for you.', 'deklera' )
		);

		self::render_validator_settings();
		self::render_ksef_settings();

		printf( '<button type="submit" class="button button-primary">%s</button>', esc_html__( 'Save', 'deklera' ) );
		echo '</form>';
	}

	/**
	 * KSeF ayarlarını çizer.
	 *
	 * Yalnizca satici Polonya'da oldugunda gosterilir; baska bir ulkedeki
	 * magazaya KSeF jetonu sormak, ise yaramayacak bir alani doldurmaya davet
	 * etmek olurdu.
	 *
	 * Jeton, dogrulama anahtari gibi maskeli gosterilir ve bos gonderim mevcut
	 * degeri silmez.
	 *
	 * @return void
	 */
	private static function render_ksef_settings(): void {
		if ( Profile::KSEF !== Profile::for_country( self::seller_country() ) ) {
			return;
		}

		printf( '<h3>%s</h3>', esc_html__( 'KSeF (Poland)', 'deklera' ) );

		printf(
			'<p class="description" style="margin-bottom:8px">%s</p>',
			esc_html__(
				'An FA(3) invoice does not legally exist until KSeF has accepted it and assigned a number. Deklera sends each invoice automatically and records the number.',
				'deklera'
			)
		);

		printf(
			'<p><label for="deklera_ksef_token">%1$s</label><br/><input type="password" id="deklera_ksef_token" name="deklera_ksef_token" value="" class="regular-text" placeholder="%2$s" autocomplete="new-password"/><br/><span class="description">%3$s</span></p>',
			esc_html__( 'KSeF token', 'deklera' ),
			Settings::has_token()
				? esc_attr__( 'Saved — leave blank to keep it', 'deklera' )
				: esc_attr__( 'Not set', 'deklera' ),
			esc_html__( 'Generate the token in your KSeF account. It is stored on this site and never shown again.', 'deklera' )
		);

		$production = Settings::is_production();

		printf(
			'<p><label for="deklera_ksef_environment">%1$s</label><br/><select id="deklera_ksef_environment" name="deklera_ksef_environment"><option value="%2$s"%4$s>%6$s</option><option value="%3$s"%5$s>%7$s</option></select><br/><span class="description">%8$s</span></p>',
			esc_html__( 'Environment', 'deklera' ),
			esc_attr( Settings::ENVIRONMENT_TEST ),
			esc_attr( Settings::ENVIRONMENT_PRODUCTION ),
			$production ? '' : ' selected',
			$production ? ' selected' : '',
			esc_html__( 'Test — invoices have no legal effect', 'deklera' ),
			esc_html__( 'Production — invoices are real', 'deklera' ),
			esc_html__( 'Start with Test. Invoices sent to Production are legally issued and cannot be withdrawn.', 'deklera' )
		);
	}

	/**
	 * Satıcının ülkesi.
	 *
	 * @return string
	 */
	private static function seller_country(): string {
		$base = \function_exists( 'WC' ) && null !== \WC()->countries
			? (string) \WC()->countries->get_base_country()
			: '';

		return strtoupper( trim( (string) \apply_filters( 'deklera/seller_country', $base ) ) );
	}

	/**
	 * Doğrulama servisi ayarlarını çizer.
	 *
	 * Anahtar ekranda maskeli gösterilir; kaydedilmiş bir sırrı yönetici
	 * ekranında düz metin olarak basmak gereksiz bir sızıntı yüzeyidir.
	 *
	 * @return void
	 */
	private static function render_validator_settings(): void {
		$has_pro = Licensing::has_hosted_validation();
		$key     = (string) get_option( HostedValidator::OPTION_KEY, '' );

		printf( '<hr/><p><strong>%s</strong></p>', esc_html__( 'Official validation (Pro)', 'deklera' ) );

		if ( ! $has_pro ) {
			printf(
				'<p class="description">%s</p>',
				esc_html__( 'Validation against the official EN 16931 rule set requires the Pro plan. It cannot run inside WordPress because the rule set needs XSLT 2.0, which PHP does not support.', 'deklera' )
			);
		} elseif ( '' === $key ) {
			/*
			 * Pro'yu yeni almis birinin gordugu ilk ekran burasi. Onceden bu
			 * bolum iki bos alandan ve "validator.example.com" yer
			 * tutucusundan ibaretti; ne yazilacagini soyleyen hicbir sey
			 * yoktu. Para odendikten hemen sonra karsilasilacak en kotu ekran
			 * budur.
			 */
			printf(
				'<div class="notice notice-warning inline"><p>%1$s</p><p><a href="%2$s" target="_blank" rel="noopener">%3$s</a></p></div>',
				esc_html__( 'One step left: paste your validation key below. It is in the email you received when you bought Pro. The service address is already filled in.', 'deklera' ),
				esc_url( self::GUIDE_URL ),
				esc_html__( 'Read the setup guide', 'deklera' )
			);
		}

		printf(
			'<p><label for="deklera_validator_endpoint">%1$s</label><br/><input type="url" id="deklera_validator_endpoint" name="deklera_validator_endpoint" value="%2$s" class="regular-text"%3$s/><br/><span class="description">%4$s</span></p>',
			esc_html__( 'Validation service address', 'deklera' ),
			esc_attr( (string) get_option( HostedValidator::OPTION_ENDPOINT, HostedValidator::DEFAULT_ENDPOINT ) ),
			$has_pro ? '' : ' disabled',
			esc_html__( 'Already set to the service run by the plugin author. Change it only if you run your own copy of it.', 'deklera' )
		);

		printf(
			'<p><label for="deklera_validator_key">%1$s</label><br/><input type="password" id="deklera_validator_key" name="deklera_validator_key" value="" class="regular-text" placeholder="%2$s" autocomplete="new-password"%3$s/><br/><span class="description">%4$s</span></p>',
			esc_html__( 'Validation key', 'deklera' ),
			esc_attr( '' === $key ? __( 'Not set', 'deklera' ) : str_repeat( '•', 12 ) ),
			$has_pro ? '' : ' disabled',
			'' === $key
				? esc_html__( 'From your Pro purchase email. This is not the licence key that activated the plugin.', 'deklera' )
				: esc_html__( 'Saved. Leave empty to keep it.', 'deklera' )
		);
	}

	/**
	 * Raporu önbellekten alır veya üretir.
	 *
	 * @return Report
	 */
	private static function report(): Report {
		$cached = get_transient( self::CACHE_KEY );

		if ( $cached instanceof Report ) {
			return $cached;
		}

		$report = Scanner::scan();

		set_transient( self::CACHE_KEY, $report, 15 * MINUTE_IN_SECONDS );

		return $report;
	}

	/**
	 * Kural kimliğinden başlık üretir.
	 *
	 * @param string $rule_id Kural kimliği.
	 * @return string
	 */
	private static function rule_title( string $rule_id ): string {
		foreach ( Scanner::rules() as $rule ) {
			if ( $rule->id() === $rule_id ) {
				return $rule->title();
			}
		}

		return $rule_id;
	}

	/**
	 * Sayfa adresi.
	 *
	 * @return string
	 */
	private static function url(): string {
		return admin_url( 'admin.php?page=' . self::SLUG );
	}

	/**
	 * Sayfaya özel stiller.
	 *
	 * Ayrı bir dosya yüklemeye değmeyecek kadar küçük; tek sayfada kullanılıyor.
	 * Yön bağımsız olması için mantıksal CSS özellikleri kullanılır — bkz.
	 * docs/I18N.md bölüm 7.
	 *
	 * @return void
	 */
	private static function render_styles(): void {
		$css = '
		.deklera-wrap{max-width:900px}
		.deklera-hero{background:#fff;border:1px solid #dcdcde;border-radius:4px;padding:20px 24px;margin:16px 0 24px}
		.deklera-lede{font-size:20px;line-height:1.4;margin:0 0 16px;font-weight:600}
		.deklera-lede.deklera-bad{color:#a4261d}
		.deklera-lede.deklera-ok{color:#116149}
		.deklera-stats{display:flex;flex-wrap:wrap;gap:24px;margin-bottom:16px}
		.deklera-stat{display:flex;flex-direction:column;min-width:110px}
		.deklera-stat-value{font-size:26px;font-weight:600;line-height:1.2}
		.deklera-stat-label{font-size:12px;color:#646970;font-weight:600}
		.deklera-stat.deklera-bad .deklera-stat-value{color:#a4261d}
		.deklera-stat.deklera-warn .deklera-stat-value{color:#8a5700}
		.deklera-stat.deklera-ok .deklera-stat-value{color:#116149}
		.deklera-group{background:#fff;border:1px solid #dcdcde;border-radius:4px;padding:16px 20px;margin-bottom:14px}
		.deklera-group.deklera-store{border-inline-start:3px solid #2271b1}
		.deklera-group h3{margin:0 0 10px;display:flex;align-items:center;gap:10px;flex-wrap:wrap}
		.deklera-badge{font-size:11px;font-weight:600;letter-spacing:.02em;padding:3px 8px;border-radius:3px}
		.deklera-badge.deklera-blocker{background:#f6e4e2;color:#a4261d}
		.deklera-badge.deklera-warning{background:#f7eddc;color:#8a5700}
		.deklera-badge.deklera-info{background:#e6eef4;color:#2c5777}
		.deklera-count{font-weight:400;color:#646970;font-size:13px}
		.deklera-what{margin:0 0 6px;font-weight:600}
		.deklera-why{margin:0 0 6px;color:#50575e}
		.deklera-fix{margin:0 0 6px}
		.deklera-standard{margin:0;color:#787c82;font-size:12px;font-family:monospace}
		.deklera-orders{margin:10px 0 0;font-size:13px;color:#646970}
		.deklera-orders a{margin-inline-end:4px}
		.deklera-settings{background:#fff;border:1px solid #dcdcde;border-radius:4px;padding:16px 20px}
		';

		printf( '<style>%s</style>', esc_html( $css ) );
	}
}
