<?php
/**
 * Eklenti ayarları (WordPress → İşletmeBurada → Ayarlar).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IBP_Settings {

	const OPTION = 'ibp_settings';

	public static function defaults() {
		return array(
			'post_type'           => 'isletme',
			'default_period'      => '30',
			'track_enabled'       => '1',
			'track_skip_owners'   => '1',
			'ranking_scale'       => '10',
			'ranking_min_reviews' => '1',
			'edit_url'            => '',
			'home_city'           => 'İstanbul',
			'search_url'          => '',
			'cat_param'           => 'kategori',
			'city_param'          => 'sehir',
			'points_review'       => '50',
			'points_favorite'     => '5',
			'level_size'          => '300',
			'sector_map'          => array(),
			'module_urls'         => array(),
		);
	}

	public static function all() {
		$saved = get_option( self::OPTION, array() );
		return array_merge( self::defaults(), is_array( $saved ) ? $saved : array() );
	}

	public static function get( $key ) {
		$all = self::all();
		return $all[ $key ] ?? null;
	}

	/**
	 * Her sekme yalnız kendi alanlarını gönderir; diğer sekmelerin ayarları korunur.
	 */
	public static function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();
		$clean = self::all();
		$tab   = $input['_tab'] ?? 'general';

		if ( 'sectors' === $tab ) {
			$profiles             = IBP_Sectors::profiles();
			$clean['sector_map']  = array();
			$clean['module_urls'] = array();
			foreach ( (array) ( $input['sector_map'] ?? array() ) as $term_id => $key ) {
				if ( '' !== $key && isset( $profiles[ $key ] ) ) {
					$clean['sector_map'][ absint( $term_id ) ] = $key;
				}
			}
			foreach ( (array) ( $input['module_urls'] ?? array() ) as $module => $url ) {
				$url = trim( sanitize_text_field( $url ) );
				if ( '' !== $url && isset( IBP_Sectors::modules()[ $module ] ) ) {
					$clean['module_urls'][ $module ] = $url;
				}
			}
			return $clean;
		}

		$clean['post_type']           = sanitize_key( $input['post_type'] ?? $clean['post_type'] ) ?: 'isletme';
		$clean['default_period']      = in_array( $input['default_period'] ?? '', array( '7', '30', '90' ), true ) ? $input['default_period'] : '30';
		$clean['track_enabled']       = empty( $input['track_enabled'] ) ? '0' : '1';
		$clean['track_skip_owners']   = empty( $input['track_skip_owners'] ) ? '0' : '1';
		$clean['ranking_scale']       = in_array( $input['ranking_scale'] ?? '', array( '5', '10' ), true ) ? $input['ranking_scale'] : '10';
		$clean['ranking_min_reviews'] = (string) max( 1, min( 50, absint( $input['ranking_min_reviews'] ?? 1 ) ) );
		$clean['edit_url']            = trim( sanitize_text_field( $input['edit_url'] ?? '' ) );
		$clean['home_city']           = sanitize_text_field( $input['home_city'] ?? 'İstanbul' ) ?: 'İstanbul';
		$clean['search_url']          = esc_url_raw( trim( (string) ( $input['search_url'] ?? '' ) ) );
		$clean['cat_param']           = sanitize_key( $input['cat_param'] ?? 'kategori' ) ?: 'kategori';
		$clean['city_param']          = sanitize_key( $input['city_param'] ?? 'sehir' ) ?: 'sehir';
		$clean['points_review']       = (string) min( 1000, absint( $input['points_review'] ?? 50 ) );
		$clean['points_favorite']     = (string) min( 1000, absint( $input['points_favorite'] ?? 5 ) );
		$clean['level_size']          = (string) max( 10, min( 100000, absint( $input['level_size'] ?? 300 ) ) );

		return $clean;
	}
}
