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

	public static function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();
		$clean = self::defaults();

		$clean['post_type']           = sanitize_key( $input['post_type'] ?? $clean['post_type'] ) ?: 'isletme';
		$clean['default_period']      = in_array( $input['default_period'] ?? '', array( '7', '30', '90' ), true ) ? $input['default_period'] : '30';
		$clean['track_enabled']       = empty( $input['track_enabled'] ) ? '0' : '1';
		$clean['track_skip_owners']   = empty( $input['track_skip_owners'] ) ? '0' : '1';
		$clean['ranking_scale']       = in_array( $input['ranking_scale'] ?? '', array( '5', '10' ), true ) ? $input['ranking_scale'] : '10';
		$clean['ranking_min_reviews'] = (string) max( 1, min( 50, absint( $input['ranking_min_reviews'] ?? 1 ) ) );
		$clean['edit_url']            = trim( sanitize_text_field( $input['edit_url'] ?? '' ) );

		return $clean;
	}
}
