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
	/**
	 * @param int|int[] $post_id Bir ya da birden çok işletme (marka görünümü).
	 */
	public static function recent( $post_id, $limit = 2 ) {
		global $wpdb;
		$columns = self::columns();
		if ( ! in_array( 'post_id', $columns, true ) ) {
			return array();
		}

		$table  = self::table();
		$ids    = array_map( 'intval', (array) $post_id ) ?: array( 0 );
		$where  = array( 'post_id IN (' . implode( ', ', $ids ) . ')' );
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

	/**
	 * Ana sayfa referansları: en yeni 4–5 yıldızlı, en az 60 karakterlik yorumlar.
	 *
	 * @return array[] name, business, text, stars
	 */
	public static function best_recent( $limit = 3 ) {
		global $wpdb;
		$columns = self::columns();
		if ( ! in_array( 'content', $columns, true ) || ! in_array( 'post_id', $columns, true ) ) {
			return array();
		}
		$where = array( 'CHAR_LENGTH(content) >= 60' );
		if ( in_array( 'feed', $columns, true ) ) {
			$where[] = $wpdb->prepare( 'feed = %s', 'post_reviews' );
		}
		if ( in_array( 'review_score', $columns, true ) ) {
			$where[] = 'review_score >= 1'; // Voxel -2…+2: 1 ve üstü = 4–5 yıldız.
		}
		$select = array_intersect( array( 'id', 'post_id', 'user_id', 'content', 'details', 'review_score', 'created_at' ), $columns );
		$order  = in_array( 'created_at', $columns, true ) ? 'created_at' : 'id';
		$rows   = $wpdb->get_results( // phpcs:ignore WordPress.DB
			sprintf( 'SELECT %s FROM `%s` WHERE %s ORDER BY %s DESC LIMIT %d', implode( ', ', $select ), self::table(), implode( ' AND ', $where ), $order, max( 1, (int) $limit ) * 3 ),
			ARRAY_A
		);

		$out = array();
		foreach ( (array) $rows as $row ) {
			$post = get_post( (int) $row['post_id'] );
			if ( ! $post || 'publish' !== $post->post_status ) {
				continue;
			}
			$stars = self::stars( $row );
			$user  = ! empty( $row['user_id'] ) ? get_userdata( (int) $row['user_id'] ) : null;
			$out[] = array(
				'name'     => $user ? $user->display_name : 'Ziyaretçi',
				'business' => get_the_title( $post ),
				'text'     => wp_trim_words( wp_strip_all_tags( (string) $row['content'] ), 45, '…' ),
				'stars'    => $stars ? $stars : 5,
			);
			if ( count( $out ) >= $limit ) {
				break;
			}
		}
		return $out;
	}

	/**
	 * İşletme sahibinin henüz yanıtlamadığı yorum sayısı. Yanıt tablosu yoksa null.
	 */
	public static function unanswered( IBP_Business $business ) {
		global $wpdb;
		$columns = self::columns();
		$replies = self::reply_columns();
		if ( ! in_array( 'post_id', $columns, true ) || ! in_array( 'status_id', $replies, true ) || ! in_array( 'user_id', $replies, true ) ) {
			return null;
		}

		$owner  = (int) $business->post->post_author;
		$by_me  = $wpdb->prepare( 'r.user_id = %d', $owner );
		if ( in_array( 'published_as', $replies, true ) ) {
			// Voxel yanıtı "işletme adına" da yayınlayabilir.
			$by_me = '(' . $by_me . $wpdb->prepare( ' OR r.published_as = %d', $business->id ) . ')';
		}
		$feed = in_array( 'feed', $columns, true ) ? $wpdb->prepare( ' AND t.feed = %s', 'post_reviews' ) : '';

		return (int) $wpdb->get_var( // phpcs:ignore WordPress.DB
			sprintf(
				'SELECT COUNT(*) FROM `%1$s` t WHERE %2$s%3$s AND NOT EXISTS (SELECT 1 FROM `%4$s` r WHERE r.status_id = t.id AND %5$s)',
				self::table(),
				$wpdb->prepare( 't.post_id = %d', $business->id ),
				$feed,
				self::reply_table(),
				$by_me
			)
		);
	}

	public static function reply_table() {
		global $wpdb;
		return apply_filters( 'ibp_review_replies_table', $wpdb->prefix . 'voxel_timeline_replies' );
	}

	public static function reply_columns() {
		static $columns = null;
		if ( null !== $columns ) {
			return $columns;
		}
		global $wpdb;
		$table = self::reply_table();
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) !== $table ) {
			$columns = array();
			return $columns;
		}
		$columns = (array) $wpdb->get_col( "SHOW COLUMNS FROM `{$table}`" ); // phpcs:ignore WordPress.DB
		return $columns;
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
			'business' => get_the_title( (int) $row['post_id'] ),
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
