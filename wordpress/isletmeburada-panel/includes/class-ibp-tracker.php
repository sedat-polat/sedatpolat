<?php
/**
 * İşletme sayfası görüntüleme ve tıklama sayacı.
 *
 * Sayım tarayıcıdan admin-ajax'a gönderilir; böylece LiteSpeed gibi sayfa önbellekleri
 * sayımı atlatmaz. Veriler günlük toplam olarak {prefix}ibp_stats tablosunda tutulur.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IBP_Tracker {

	const DB_VERSION = '1';

	const TYPES = array(
		'view'       => 'Profil görüntüleme',
		'phone'      => 'Telefon araması',
		'directions' => 'Yol tarifi',
		'website'    => 'Web sitesi tıklaması',
		'email'      => 'E-posta tıklaması',
	);

	/**
	 * Grafik ve istatistik kartlarında seçilebilen veriler.
	 */
	public static function metrics() {
		return self::TYPES + array( 'contacts' => 'Tüm iletişim tıklamaları' );
	}

	/**
	 * Bir verinin hangi sayım türlerinden oluştuğu.
	 *
	 * @return string[]
	 */
	public static function metric_types( $metric ) {
		return 'contacts' === $metric ? array( 'phone', 'directions', 'website', 'email' ) : array( $metric );
	}

	public static function init() {
		add_action( 'wp_ajax_ibp_track', array( __CLASS__, 'handle' ) );
		add_action( 'wp_ajax_nopriv_ibp_track', array( __CLASS__, 'handle' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'ibp_stats';
	}

	/**
	 * Tabloyu oluşturur. Eklenti zip ile güncellendiğinde etkinleştirme kancası
	 * çalışmayabildiği için her açılışta sürüm kontrol edilir.
	 */
	public static function maybe_install() {
		if ( get_option( 'ibp_db_version' ) === self::DB_VERSION ) {
			return;
		}
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table   = self::table();
		$charset = $wpdb->get_charset_collate();
		dbDelta(
			"CREATE TABLE {$table} (
				post_id bigint(20) unsigned NOT NULL,
				type varchar(20) NOT NULL,
				day date NOT NULL,
				hits int(10) unsigned NOT NULL DEFAULT 0,
				PRIMARY KEY  (post_id,type,day),
				KEY day (day)
			) {$charset};"
		);
		update_option( 'ibp_db_version', self::DB_VERSION );
	}

	public static function table_exists() {
		global $wpdb;
		$table = self::table();
		return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) === $table;
	}

	/* ---------------------------------------------------------------------
	 * Sayım
	 * ------------------------------------------------------------------ */

	public static function enqueue() {
		if ( '1' !== IBP_Settings::get( 'track_enabled' ) || ! is_singular( IBP_Business::post_type() ) ) {
			return;
		}
		$business = IBP_Business::from_id( get_queried_object_id() );
		if ( ! $business ) {
			return;
		}
		$website = (string) $business->field( 'website' );
		wp_enqueue_script( 'ibp-tracker', IBP_URL . 'assets/tracker.js', array(), IBP_VERSION, true );
		wp_add_inline_script(
			'ibp-tracker',
			'window.ibpTrack = ' . wp_json_encode(
				array(
					'url'     => admin_url( 'admin-ajax.php' ),
					'post'    => $business->id,
					'website' => $website ? (string) wp_parse_url( $website, PHP_URL_HOST ) : '',
				)
			) . ';',
			'before'
		);
	}

	public static function handle() {
		// Sayfalar önbellekte beklediği için nonce kullanılamaz; kötüye kullanım
		// yalnızca sayıları şişirebilir, veri okuyamaz ya da değiştiremez.
		$post_id = isset( $_POST['post'] ) ? absint( $_POST['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$type    = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( ! $post_id || ! isset( self::TYPES[ $type ] ) || '1' !== IBP_Settings::get( 'track_enabled' ) ) {
			wp_die( '', '', array( 'response' => 204 ) );
		}
		$post = get_post( $post_id );
		if ( ! $post || IBP_Business::post_type() !== $post->post_type || 'publish' !== $post->post_status ) {
			wp_die( '', '', array( 'response' => 204 ) );
		}
		if ( self::is_bot() || self::is_excluded_user( $post ) ) {
			wp_die( '', '', array( 'response' => 204 ) );
		}

		self::record( $post_id, $type );
		wp_die( '', '', array( 'response' => 204 ) );
	}

	public static function record( $post_id, $type, $day = '' ) {
		global $wpdb;
		self::maybe_install();
		$day = $day ?: wp_date( 'Y-m-d' );
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'INSERT INTO ' . self::table() . ' (post_id, type, day, hits) VALUES (%d, %s, %s, 1) ON DUPLICATE KEY UPDATE hits = hits + 1', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$post_id,
				$type,
				$day
			)
		);
	}

	private static function is_excluded_user( WP_Post $post ) {
		if ( ! is_user_logged_in() || '1' !== IBP_Settings::get( 'track_skip_owners' ) ) {
			return false;
		}
		return get_current_user_id() === (int) $post->post_author || current_user_can( 'edit_others_posts' );
	}

	private static function is_bot() {
		$agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) ) : '';
		return '' === $agent || (bool) preg_match( '/bot|crawl|spider|slurp|preview|facebookexternalhit|headless|lighthouse/', $agent );
	}

	/* ---------------------------------------------------------------------
	 * Okuma
	 * ------------------------------------------------------------------ */

	/**
	 * Seçili dönem (gün sayısı): ?donem=7|30|90, yoksa ayarlardaki varsayılan.
	 */
	public static function period() {
		$period = isset( $_GET['donem'] ) ? absint( $_GET['donem'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return in_array( $period, array( 7, 30, 90 ), true ) ? $period : (int) IBP_Settings::get( 'default_period' );
	}

	/**
	 * Son $days günün günlük değerleri, eskiden yeniye.
	 *
	 * @param string|string[] $types
	 * @return array<string,int> 'Y-m-d' => sayı
	 */
	public static function daily( $post_id, $types, $days, $offset = 0 ) {
		$types = (array) $types;
		$today = new DateTimeImmutable( 'today', wp_timezone() );
		$from  = $today->modify( '-' . ( $days - 1 + $offset ) . ' days' );
		$to    = $today->modify( '-' . $offset . ' days' );

		$series = array();
		for ( $d = $from; $d <= $to; $d = $d->modify( '+1 day' ) ) {
			$series[ $d->format( 'Y-m-d' ) ] = 0;
		}
		if ( ! self::table_exists() ) {
			return $series;
		}

		global $wpdb;
		$in   = implode( ', ', array_fill( 0, count( $types ), '%s' ) );
		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT day, SUM(hits) AS hits FROM ' . self::table() . " WHERE post_id = %d AND type IN ($in) AND day BETWEEN %s AND %s GROUP BY day", // phpcs:ignore WordPress.DB.PreparedSQL
				array_merge( array( $post_id ), $types, array( $from->format( 'Y-m-d' ), $to->format( 'Y-m-d' ) ) )
			)
		);
		foreach ( (array) $rows as $row ) {
			if ( isset( $series[ $row->day ] ) ) {
				$series[ $row->day ] = (int) $row->hits;
			}
		}
		return $series;
	}

	/**
	 * Dönem toplamı ve önceki döneme göre değişim yüzdesi.
	 *
	 * @return array{total:int, previous:int, change:int|null}
	 */
	public static function summary( $post_id, $types, $days ) {
		$total    = array_sum( self::daily( $post_id, $types, $days ) );
		$previous = array_sum( self::daily( $post_id, $types, $days, $days ) );
		return array(
			'total'    => $total,
			'previous' => $previous,
			'change'   => $previous > 0 ? (int) round( 100 * ( $total - $previous ) / $previous ) : null,
		);
	}

	/**
	 * Değişimi prototipteki gibi "+%11" / "−%3" biçiminde yazar.
	 */
	public static function format_change( $change ) {
		if ( null === $change ) {
			return '';
		}
		return ( $change < 0 ? '−' : '+' ) . '%' . abs( $change );
	}

	public static function total_all( $days = 30 ) {
		if ( ! self::table_exists() ) {
			return 0;
		}
		global $wpdb;
		$from = ( new DateTimeImmutable( 'today', wp_timezone() ) )->modify( '-' . ( $days - 1 ) . ' days' )->format( 'Y-m-d' );
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT SUM(hits) FROM ' . self::table() . ' WHERE day >= %s', $from ) ); // phpcs:ignore WordPress.DB
	}
}
