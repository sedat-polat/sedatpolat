<?php
/**
 * Voxel yorumlarını timeline tablosundan okur.
 *
 * Voxel yorumları {prefix}voxel_timeline tablosunda feed = 'post_reviews' olarak tutar;
 * puan review_score sütununda -2…+2 arasındadır. Sütunlar sürüme göre değişebildiği için
 * sorgu, tabloda gerçekten bulunan sütunlara göre kurulur.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IBP_Reviews {

	public static function table() {
		global $wpdb;
		return apply_filters( 'ibp_reviews_table', $wpdb->prefix . 'voxel_timeline' );
	}

	/**
	 * Tablodaki sütun adları. Tablo yoksa boş dizi.
	 *
	 * @return string[]
	 */
	public static function columns() {
		static $columns = null;
		if ( null !== $columns ) {
			return $columns;
		}
		global $wpdb;
		$table = self::table();
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) !== $table ) {
			$columns = array();
			return $columns;
		}
		$columns = (array) $wpdb->get_col( "SHOW COLUMNS FROM `{$table}`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $columns;
	}

	/**
	 * @return array[] Her biri: name, initials, stars (1–5 ya da null), text, date.
	 */
	public static function recent( $post_id, $limit = 2 ) {
		global $wpdb;
		$columns = self::columns();
		if ( ! in_array( 'post_id', $columns, true ) ) {
			return array();
		}

		$table  = self::table();
		$where  = array( $wpdb->prepare( 'post_id = %d', $post_id ) );
		$select = array( 'id', 'post_id' );

		if ( in_array( 'feed', $columns, true ) ) {
			$where[] = $wpdb->prepare( 'feed = %s', 'post_reviews' );
		}
		foreach ( array( 'user_id', 'content', 'details', 'review_score', 'created_at' ) as $column ) {
			if ( in_array( $column, $columns, true ) ) {
				$select[] = $column;
			}
		}
		$order = in_array( 'created_at', $columns, true ) ? 'created_at' : 'id';

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			sprintf(
				'SELECT %s FROM `%s` WHERE %s ORDER BY %s DESC LIMIT %d',
				implode( ', ', $select ),
				$table,
				implode( ' AND ', $where ),
				$order,
				max( 1, (int) $limit )
			),
			ARRAY_A
		);

		return array_map( array( __CLASS__, 'map_row' ), (array) $rows );
	}

	private static function map_row( array $row ) {
		$user = ! empty( $row['user_id'] ) ? get_userdata( (int) $row['user_id'] ) : null;
		$name = $user ? $user->display_name : 'Ziyaretçi';

		$words    = preg_split( '/\s+/u', trim( $name ), -1, PREG_SPLIT_NO_EMPTY );
		$initials = '';
		foreach ( array_slice( $words, 0, 2 ) as $word ) {
			$initials .= mb_substr( $word, 0, 1 );
		}

		$timestamp = ! empty( $row['created_at'] ) ? strtotime( $row['created_at'] . ' UTC' ) : 0;

		return array(
			'name'     => $name,
			'initials' => IBP_Business::upper_tr( $initials ),
			'stars'    => self::stars( $row ),
			'text'     => wp_trim_words( wp_strip_all_tags( (string) ( $row['content'] ?? '' ) ), 30, '…' ),
			'date'     => $timestamp ? sprintf( '%s önce', human_time_diff( $timestamp ) ) : '',
		);
	}

	/**
	 * Yıldız sayısı (1–5) ya da puan yoksa null.
	 */
	public static function stars( array $row ) {
		$score = null;
		if ( isset( $row['review_score'] ) && is_numeric( $row['review_score'] ) ) {
			$score = (float) $row['review_score'];
		} elseif ( ! empty( $row['details'] ) ) {
			$details = json_decode( $row['details'], true );
			if ( isset( $details['rating']['score'] ) && is_numeric( $details['rating']['score'] ) ) {
				$score = (float) $details['rating']['score'];
			}
		}
		if ( null === $score ) {
			return null;
		}
		// Voxel puanı -2…+2 arasında tutar; 1–5 yıldıza çeviriyoruz.
		return (int) max( 1, min( 5, round( $score + 3 ) ) );
	}
}
