<?php
/**
 * Bireysel panel verisi: kullanıcının Voxel yorumları, favorileri (koleksiyonlar),
 * takip ettikleri, siparişleri ve yerel rehber puanı.
 *
 * Voxel tabloları sürüme göre değişebildiği için her sorgu, tabloda gerçekten
 * bulunan sütunlara göre kurulur; tablo yoksa boş sonuç döner.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IBP_User {

	/** @var WP_User */
	public $user;

	private function __construct( WP_User $user ) {
		$this->user = $user;
	}

	/**
	 * @return IBP_User|null Giriş yapmış kullanıcı.
	 */
	public static function current() {
		$user = wp_get_current_user();
		return $user && $user->exists() ? new self( $user ) : null;
	}

	public function id() {
		return (int) $this->user->ID;
	}

	public function name() {
		return $this->user->display_name;
	}

	public function first_name() {
		return $this->user->first_name ?: (string) strtok( $this->user->display_name, ' ' );
	}

	public function initials() {
		$words = preg_split( '/\s+/u', trim( $this->name() ), -1, PREG_SPLIT_NO_EMPTY );
		$out   = '';
		foreach ( array_slice( $words, 0, 2 ) as $word ) {
			$out .= mb_substr( $word, 0, 1 );
		}
		return IBP_Business::upper_tr( $out );
	}

	/**
	 * Voxel avatarı (kullanıcı meta "voxel:avatar"), yoksa boş. Gravatar kullanılmaz;
	 * tasarımdaki gibi baş harfler gösterilir.
	 */
	public function avatar_url() {
		$id = (int) get_user_meta( $this->id(), 'voxel:avatar', true );
		return $id ? (string) wp_get_attachment_image_url( $id, 'thumbnail' ) : '';
	}

	/* ---------------------------------------------------------------------
	 * Yorumlar
	 * ------------------------------------------------------------------ */

	public function review_count() {
		global $wpdb;
		$columns = IBP_Reviews::columns();
		if ( ! in_array( 'user_id', $columns, true ) || ! in_array( 'post_id', $columns, true ) ) {
			return 0;
		}
		$feed = in_array( 'feed', $columns, true ) ? $wpdb->prepare( ' AND feed = %s', 'post_reviews' ) : '';
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM `' . IBP_Reviews::table() . '` WHERE user_id = %d', $this->id() ) . $feed ); // phpcs:ignore WordPress.DB
	}

	/**
	 * @return array[] business, url, stars, text, date
	 */
	public function reviews( $limit = 3 ) {
		global $wpdb;
		$columns = IBP_Reviews::columns();
		if ( ! in_array( 'user_id', $columns, true ) || ! in_array( 'post_id', $columns, true ) ) {
			return array();
		}
		$select = array( 'id', 'post_id' );
		foreach ( array( 'content', 'details', 'review_score', 'created_at' ) as $column ) {
			if ( in_array( $column, $columns, true ) ) {
				$select[] = $column;
			}
		}
		$feed  = in_array( 'feed', $columns, true ) ? $wpdb->prepare( ' AND feed = %s', 'post_reviews' ) : '';
		$order = in_array( 'created_at', $columns, true ) ? 'created_at' : 'id';
		$rows  = $wpdb->get_results( // phpcs:ignore WordPress.DB
			$wpdb->prepare( 'SELECT ' . implode( ', ', $select ) . ' FROM `' . IBP_Reviews::table() . '` WHERE user_id = %d', $this->id() ) . $feed . sprintf( ' ORDER BY %s DESC LIMIT %d', $order, max( 1, (int) $limit ) ),
			ARRAY_A
		);

		$out = array();
		foreach ( (array) $rows as $row ) {
			$time  = ! empty( $row['created_at'] ) ? strtotime( $row['created_at'] . ' UTC' ) : 0;
			$out[] = array(
				'business' => get_the_title( (int) $row['post_id'] ),
				'url'      => get_permalink( (int) $row['post_id'] ),
				'stars'    => IBP_Reviews::stars( $row ),
				'text'     => wp_trim_words( wp_strip_all_tags( (string) ( $row['content'] ?? '' ) ), 24, '…' ),
				'date'     => $time ? sprintf( '%s önce', human_time_diff( $time ) ) : '',
			);
		}
		return $out;
	}

	/* ---------------------------------------------------------------------
	 * Favoriler (Voxel koleksiyonları)
	 * ------------------------------------------------------------------ */

	public static function collection_post_type() {
		return apply_filters( 'ibp_collection_post_type', 'collection' );
	}

	/**
	 * Kullanıcının koleksiyonları ve içindeki öğeler.
	 *
	 * @return array{collections:array[], items:int[]} items: kaydedilen yazılar, yeniden eskiye.
	 */
	public function favorites() {
		static $cache = array();
		if ( isset( $cache[ $this->id() ] ) ) {
			return $cache[ $this->id() ];
		}

		$collections = get_posts(
			array(
				'post_type'      => self::collection_post_type(),
				'author'         => $this->id(),
				'post_status'    => array( 'publish', 'private', 'unlisted', 'draft' ),
				'posts_per_page' => 50,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		$items   = array();
		$list    = array();
		$columns = self::columns( self::relations_table() );
		foreach ( $collections as $collection ) {
			$ids = array();
			if ( in_array( 'parent_id', $columns, true ) && in_array( 'child_id', $columns, true ) ) {
				global $wpdb;
				$order = in_array( 'id', $columns, true ) ? 'id' : 'child_id';
				$ids   = array_map(
					'intval',
					(array) $wpdb->get_col( $wpdb->prepare( 'SELECT child_id FROM `' . self::relations_table() . "` WHERE parent_id = %d ORDER BY {$order} DESC", $collection ) ) // phpcs:ignore WordPress.DB
				);
			}
			$list[] = array(
				'id'    => (int) $collection,
				'title' => get_the_title( $collection ),
				'url'   => get_permalink( $collection ),
				'count' => count( $ids ),
			);
			$items  = array_merge( $items, $ids );
		}

		$cache[ $this->id() ] = array(
			'collections' => $list,
			'items'       => array_values( array_unique( $items ) ),
		);
		return $cache[ $this->id() ];
	}

	public static function relations_table() {
		global $wpdb;
		return apply_filters( 'ibp_relations_table', $wpdb->prefix . 'voxel_relations' );
	}

	/* ---------------------------------------------------------------------
	 * Takip
	 * ------------------------------------------------------------------ */

	public static function followers_table() {
		global $wpdb;
		return apply_filters( 'ibp_followers_table', $wpdb->prefix . 'voxel_followers' );
	}

	/**
	 * Takip edilen işletmeler (yazılar), yeniden eskiye.
	 *
	 * @return int[]
	 */
	public function following() {
		global $wpdb;
		$columns = self::columns( self::followers_table() );
		if ( ! in_array( 'object_id', $columns, true ) || ! in_array( 'follower_id', $columns, true ) ) {
			return array();
		}
		$where = array( $wpdb->prepare( 'follower_id = %d', $this->id() ) );
		if ( in_array( 'follower_type', $columns, true ) ) {
			$where[] = $wpdb->prepare( 'follower_type = %s', 'user' );
		}
		if ( in_array( 'object_type', $columns, true ) ) {
			$where[] = $wpdb->prepare( 'object_type = %s', 'post' );
		}
		if ( in_array( 'status', $columns, true ) ) {
			$where[] = 'status = 1'; // Voxel: 1 takip, -1 engelli.
		}
		$order = in_array( 'id', $columns, true ) ? 'id' : 'object_id';
		return array_map(
			'intval',
			(array) $wpdb->get_col( 'SELECT object_id FROM `' . self::followers_table() . '` WHERE ' . implode( ' AND ', $where ) . " ORDER BY {$order} DESC LIMIT 200" ) // phpcs:ignore WordPress.DB
		);
	}

	/* ---------------------------------------------------------------------
	 * Siparişler ve rezervasyonlar (Voxel Ecommerce)
	 * ------------------------------------------------------------------ */

	public static function orders_table() {
		global $wpdb;
		foreach ( apply_filters( 'ibp_orders_tables', array( $wpdb->prefix . 'vx_orders', $wpdb->prefix . 'voxel_orders' ) ) as $table ) {
			if ( self::columns( $table ) ) {
				return $table;
			}
		}
		return '';
	}

	public static function status_label( $status ) {
		$labels = array(
			'pending_payment'  => 'Ödeme bekliyor',
			'pending_approval' => 'Onay bekliyor',
			'completed'        => 'Onaylandı',
			'canceled'         => 'İptal edildi',
			'cancelled'        => 'İptal edildi',
			'refunded'         => 'İade edildi',
			'declined'         => 'Reddedildi',
			'sub_active'       => 'Aktif abonelik',
		);
		return $labels[ $status ] ?? ucfirst( str_replace( '_', ' ', (string) $status ) );
	}

	/**
	 * @return array[] id, vendor, status, label, date
	 */
	public function orders( $limit = 5 ) {
		global $wpdb;
		$table   = self::orders_table();
		$columns = $table ? self::columns( $table ) : array();
		if ( ! in_array( 'customer_id', $columns, true ) ) {
			return array();
		}
		$select = array_intersect( array( 'id', 'vendor_id', 'status', 'created_at' ), $columns );
		$order  = in_array( 'created_at', $columns, true ) ? 'created_at' : 'id';
		$rows   = $wpdb->get_results( // phpcs:ignore WordPress.DB
			$wpdb->prepare( 'SELECT ' . implode( ', ', $select ) . " FROM `{$table}` WHERE customer_id = %d", $this->id() ) . sprintf( ' ORDER BY %s DESC LIMIT %d', $order, max( 1, (int) $limit ) ),
			ARRAY_A
		);

		$out = array();
		foreach ( (array) $rows as $row ) {
			$vendor = ! empty( $row['vendor_id'] ) ? get_userdata( (int) $row['vendor_id'] ) : null;
			$time   = ! empty( $row['created_at'] ) ? strtotime( $row['created_at'] . ' UTC' ) : 0;
			$out[]  = array(
				'id'     => (int) ( $row['id'] ?? 0 ),
				'vendor' => $vendor ? $vendor->display_name : '',
				'status' => (string) ( $row['status'] ?? '' ),
				'label'  => self::status_label( $row['status'] ?? '' ),
				'date'   => $time ? wp_date( 'j F Y', $time ) : '',
			);
		}
		return $out;
	}

	public function order_count( $statuses = array() ) {
		global $wpdb;
		$table   = self::orders_table();
		$columns = $table ? self::columns( $table ) : array();
		if ( ! in_array( 'customer_id', $columns, true ) ) {
			return 0;
		}
		$sql = $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE customer_id = %d", $this->id() ); // phpcs:ignore WordPress.DB
		if ( $statuses && in_array( 'status', $columns, true ) ) {
			$sql .= ' AND status IN (' . implode( ', ', array_map( function ( $s ) use ( $wpdb ) { return $wpdb->prepare( '%s', $s ); }, $statuses ) ) . ')';
		}
		return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB
	}

	/* ---------------------------------------------------------------------
	 * Yerel rehber puanı
	 * ------------------------------------------------------------------ */

	/**
	 * @return array{points:int, level:int, progress:int, to_next:int}
	 */
	public function guide() {
		$per_review   = (int) IBP_Settings::get( 'points_review' );
		$per_favorite = (int) IBP_Settings::get( 'points_favorite' );
		$size         = max( 1, (int) IBP_Settings::get( 'level_size' ) );

		$points = $this->review_count() * $per_review + count( $this->favorites()['items'] ) * $per_favorite;
		$points = (int) apply_filters( 'ibp_guide_points', $points, $this );
		$level  = (int) floor( $points / $size );

		return array(
			'points'   => $points,
			'level'    => $level,
			'progress' => (int) round( 100 * ( $points - $level * $size ) / $size ),
			'to_next'  => ( $level + 1 ) * $size - $points,
		);
	}

	/* ---------------------------------------------------------------------
	 * Yardımcılar
	 * ------------------------------------------------------------------ */

	/**
	 * Bir tablonun sütunları; tablo yoksa boş dizi.
	 *
	 * @return string[]
	 */
	public static function columns( $table ) {
		static $cache = array();
		if ( isset( $cache[ $table ] ) ) {
			return $cache[ $table ];
		}
		global $wpdb;
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) !== $table ) {
			$cache[ $table ] = array();
			return $cache[ $table ];
		}
		$cache[ $table ] = (array) $wpdb->get_col( "SHOW COLUMNS FROM `{$table}`" ); // phpcs:ignore WordPress.DB
		return $cache[ $table ];
	}

	/**
	 * Tanılama için kullanıcı raporu.
	 */
	public function report() {
		$orders = self::orders_table();
		return array(
			'kullanici'      => array( 'id' => $this->id(), 'ad' => $this->name(), 'avatar' => $this->avatar_url() ),
			'yorum_sayisi'   => $this->review_count(),
			'son_yorumlar'   => $this->reviews( 2 ),
			'koleksiyon_tipi' => self::collection_post_type(),
			'iliski_tablosu' => array( 'tablo' => self::relations_table(), 'sutunlar' => self::columns( self::relations_table() ) ),
			'favoriler'      => $this->favorites(),
			'takip_tablosu'  => array( 'tablo' => self::followers_table(), 'sutunlar' => self::columns( self::followers_table() ) ),
			'takip_edilen'   => $this->following(),
			'siparis_tablosu' => array( 'tablo' => $orders, 'sutunlar' => $orders ? self::columns( $orders ) : array() ),
			'siparisler'     => $this->orders( 3 ),
			'rehber'         => $this->guide(),
		);
	}
}
