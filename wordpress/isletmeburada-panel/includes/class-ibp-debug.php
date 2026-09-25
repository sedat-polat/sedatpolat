<?php
/**
 * Tanılama: yönetici panel sayfasını ?ibp_debug=1 ile açınca
 * işletmenin Voxel verisinin nasıl saklandığını sayfanın altında gösterir.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IBP_Debug {

	public static function init() {
		add_action( 'wp_footer', array( __CLASS__, 'render' ), 999 );
	}

	public static function render() {
		if ( empty( $_GET['ibp_debug'] ) || ! current_user_can( 'manage_options' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		echo '<pre id="ibp-debug" style="margin:24px;padding:16px;background:#1C1E21;color:#E6E8EB;font:12px/1.5 ui-monospace,Menlo,monospace;white-space:pre-wrap;border-radius:12px;position:relative;z-index:99999">';
		echo esc_html( self::json( self::report( IBP_Business::current() ) ) );
		echo '</pre>';
	}

	public static function json( array $report ) {
		return wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * İşletmenin Voxel verisinin nasıl saklandığını gösteren rapor.
	 */
	public static function report( $business ) {
		$report   = array(
			'eklenti'     => IBP_VERSION,
			'saat'        => wp_date( 'Y-m-d H:i l' ) . ' (' . wp_timezone_string() . ')',
			'voxel_post'  => class_exists( '\Voxel\Post' ),
			'sahip_olunan' => IBP_Business::owned_ids(),
		);

		if ( $business ) {
			$report['isletme'] = array( 'id' => $business->id, 'ad' => $business->name(), 'durum' => $business->post->post_status );

			$report['taksonomiler'] = array();
			foreach ( get_object_taxonomies( IBP_Business::post_type(), 'objects' ) as $taxonomy ) {
				$terms = get_the_terms( $business->post, $taxonomy->name );
				$report['taksonomiler'][ $taxonomy->name ] = array(
					'etiket'  => $taxonomy->label,
					'terimler' => ( $terms && ! is_wp_error( $terms ) ) ? wp_list_pluck( $terms, 'name' ) : array(),
				);
			}

			$report['meta'] = array();
			foreach ( get_post_meta( $business->id ) as $key => $values ) {
				if ( 0 === strpos( $key, '_elementor' ) || in_array( $key, array( '_edit_lock', '_edit_last' ), true ) ) {
					continue;
				}
				$report['meta'][ $key ] = self::short( $values[0] ?? '' );
			}

			$report['alanlar'] = array();
			foreach ( array( 'text', 'logo', 'gallery', 'email', 'phone', 'website', 'repeater', 'work-hours', 'location' ) as $key ) {
				$report['alanlar'][ $key ] = self::short( $business->field( $key ) );
			}

			$report['bugun']        = $business->today_hours();
			$report['yorum']        = $business->review_stats();
			$report['doluluk']      = $business->completeness_items();
			$report['voxel_metodlar'] = self::voxel_methods( $business );
			$report['yorum_tablosu']  = array(
				'tablo'    => IBP_Reviews::table(),
				'sutunlar' => IBP_Reviews::columns(),
				'son'      => IBP_Reviews::recent( $business->id, 2 ),
				'yanit_tablosu_sutunlar' => IBP_Reviews::reply_columns(),
				'yanit_bekleyen' => IBP_Reviews::unanswered( $business ),
			);
			$report['sayac']   = array();
			foreach ( array_keys( IBP_Tracker::TYPES ) as $type ) {
				$report['sayac'][ $type ] = IBP_Tracker::summary( $business->id, $type, 30 );
			}
			$report['sektor']         = IBP_Sectors::for_business( $business );
			$report['marka_agi']      = array(
				'baglanti'        => IBP_Network::link_of( $business->id ),
				'bagli_isletmeler' => IBP_Network::children( $business->id ),
				'bekleyen_istek'  => IBP_Network::children( $business->id, 'pending' ),
			);
			$report['siralama']       = IBP_Ranking::for_business( $business );
			$report['bekleyen_isler'] = wp_list_pluck( $business->todos(), 'title' );
		}

		return $report;
	}

	private static function voxel_methods( IBP_Business $business ) {
		if ( ! class_exists( '\Voxel\Post' ) ) {
			return array();
		}
		try {
			$post = \Voxel\Post::get( $business->post );
		} catch ( \Throwable $e ) {
			return array( 'hata' => $e->getMessage() );
		}
		if ( ! $post ) {
			return array();
		}
		$out = array( 'post' => array_values( array_filter( get_class_methods( $post ), array( __CLASS__, 'interesting' ) ) ) );
		if ( isset( $post->repository ) && is_object( $post->repository ) ) {
			$out['repository'] = array_values( array_filter( get_class_methods( $post->repository ), array( __CLASS__, 'interesting' ) ) );
		}
		return $out;
	}

	private static function interesting( $method ) {
		return (bool) preg_match( '/review|rating|stat|visit|view|edit|field/i', $method );
	}

	private static function short( $value ) {
		if ( is_object( $value ) ) {
			return 'object(' . get_class( $value ) . ')';
		}
		if ( is_array( $value ) ) {
			$value = wp_json_encode( $value, JSON_UNESCAPED_UNICODE );
		}
		$value = (string) $value;
		return mb_strlen( $value ) > 400 ? mb_substr( $value, 0, 400 ) . '…' : $value;
	}
}
