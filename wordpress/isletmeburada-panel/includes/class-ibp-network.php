<?php
/**
 * Marka ağı: bir işletme bir markaya şube, bayi ya da franchise olarak bağlanır.
 *
 * Bağlantı alt işletmenin meta'sında tutulur (tek seviye: marka başka bir markaya
 * bağlanamaz, alt işletmenin alt işletmesi olamaz):
 *   _ibp_parent       Markanın (üst işletmenin) ID'si
 *   _ibp_link_type    sube | bayi | franchise
 *   _ibp_link_status  pending | approved
 *
 * Alt işletme bağlanma ister, markanın sahibi onaylar. Şubeleri marka yönetir
 * (düzenleyebilir); bayi ve franchise'ları yalnız izler.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IBP_Network {

	const PARENT = '_ibp_parent';
	const TYPE   = '_ibp_link_type';
	const STATUS = '_ibp_link_status';
	const SINCE  = '_ibp_link_since';
	const NONCE  = 'ibp_network';

	public static function types() {
		return array(
			'sube'      => 'Şube',
			'bayi'      => 'Bayi',
			'franchise' => 'Franchise',
		);
	}

	/**
	 * "Arçelik şubesi / bayisi / franchise'ı" için ek.
	 */
	public static function possessive( $type ) {
		$map = array(
			'sube'      => 'şubesi',
			'bayi'      => 'bayisi',
			'franchise' => "franchise'ı",
		);
		return $map[ $type ] ?? '';
	}

	public static function init() {
		foreach ( array( 'request', 'cancel', 'approve', 'reject', 'unlink' ) as $action ) {
			add_action( 'admin_post_ibp_net_' . $action, array( __CLASS__, 'handle' ) );
		}
		add_filter( 'map_meta_cap', array( __CLASS__, 'map_meta_cap' ), 10, 4 );
	}

	/* ---------------------------------------------------------------------
	 * Okuma
	 * ------------------------------------------------------------------ */

	/**
	 * @return array{parent:int, type:string, status:string}|null
	 */
	public static function link_of( $id ) {
		$parent = (int) get_post_meta( $id, self::PARENT, true );
		if ( ! $parent ) {
			return null;
		}
		$type = (string) get_post_meta( $id, self::TYPE, true );
		return array(
			'parent' => $parent,
			'type'   => isset( self::types()[ $type ] ) ? $type : 'sube',
			'status' => 'approved' === get_post_meta( $id, self::STATUS, true ) ? 'approved' : 'pending',
		);
	}

	public static function approved_parent( $id ) {
		$link = self::link_of( $id );
		return ( $link && 'approved' === $link['status'] ) ? $link['parent'] : 0;
	}

	/**
	 * Markaya bağlı işletmeler.
	 *
	 * @param string $status approved | pending
	 * @return int[]
	 */
	public static function children( $parent_id, $status = 'approved' ) {
		static $cache = array();
		$key = $parent_id . ':' . $status;
		if ( isset( $cache[ $key ] ) ) {
			return $cache[ $key ];
		}
		$cache[ $key ] = array_map(
			'intval',
			get_posts(
				array(
					'post_type'      => IBP_Business::post_type(),
					'post_status'    => array( 'publish', 'pending', 'draft', 'private' ),
					'posts_per_page' => 500,
					'orderby'        => 'title',
					'order'          => 'ASC',
					'fields'         => 'ids',
					'no_found_rows'  => true,
					'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
						array( 'key' => self::PARENT, 'value' => (int) $parent_id, 'type' => 'NUMERIC' ),
						array( 'key' => self::STATUS, 'value' => $status ),
					),
				)
			)
		);
		return $cache[ $key ];
	}

	public static function is_brand( $id ) {
		return (bool) self::children( $id );
	}

	/**
	 * Kullanıcının panelde görebileceği işletmeler: sahip oldukları + markalarına
	 * onaylı bağlı işletmeler.
	 *
	 * @return int[]
	 */
	public static function accessible_ids( $user_id = 0 ) {
		$ids = IBP_Business::owned_ids( $user_id );
		foreach ( $ids as $id ) {
			$ids = array_merge( $ids, self::children( $id ) );
		}
		return array_values( array_unique( $ids ) );
	}

	/**
	 * Kullanıcı bu işletmeyi yönetebilir mi? Sahibi ya da şubenin markasının sahibi.
	 */
	public static function can_manage( $id, $user_id = 0 ) {
		$user_id = $user_id ?: get_current_user_id();
		$post    = get_post( $id );
		if ( ! $user_id || ! $post ) {
			return false;
		}
		if ( (int) $post->post_author === $user_id ) {
			return true;
		}
		$link = self::link_of( $id );
		if ( ! $link || 'approved' !== $link['status'] || 'sube' !== $link['type'] ) {
			return false;
		}
		$parent = get_post( $link['parent'] );
		return $parent && (int) $parent->post_author === $user_id;
	}

	/**
	 * Markanın sahibi şubelerini WordPress'te düzenleyebilsin.
	 */
	public static function map_meta_cap( $caps, $cap, $user_id, $args ) {
		if ( 'edit_post' !== $cap || empty( $args[0] ) ) {
			return $caps;
		}
		$post = get_post( $args[0] );
		if ( ! $post || IBP_Business::post_type() !== $post->post_type || (int) $post->post_author === (int) $user_id ) {
			return $caps;
		}
		return self::can_manage( $post->ID, $user_id ) ? array( 'read' ) : $caps;
	}

	/**
	 * "Bağlı işletmeler" panel sayfasının adresi (sayfa oluşturulduysa).
	 */
	public static function page_url() {
		$pages = IBP_Page_Builder::existing();
		return ! empty( $pages['network'] ) ? (string) get_permalink( $pages['network'] ) : '';
	}

	/* ---------------------------------------------------------------------
	 * Bağlanma kuralları
	 * ------------------------------------------------------------------ */

	/**
	 * Bağlanma isteği geçerli mi? Geçerliyse '' döner, değilse nedenini.
	 */
	public static function validate_request( $child_id, $parent_id, $type, $user_id ) {
		$child  = get_post( $child_id );
		$parent = get_post( $parent_id );

		if ( ! $child || ! $parent || IBP_Business::post_type() !== $parent->post_type ) {
			return 'İşletme bulunamadı.';
		}
		if ( (int) $child->post_author !== (int) $user_id ) {
			return 'Yalnız kendi işletmeni bir markaya bağlayabilirsin.';
		}
		if ( $child_id === $parent_id ) {
			return 'Bir işletme kendine bağlanamaz.';
		}
		if ( ! isset( self::types()[ $type ] ) ) {
			return 'Bağlantı türünü seç.';
		}
		if ( 'publish' !== $parent->post_status ) {
			return 'Bu marka henüz yayında değil.';
		}
		if ( self::link_of( $child_id ) ) {
			return 'İşletmenin zaten bir markaya bağlantısı ya da bekleyen isteği var.';
		}
		if ( self::children( $child_id ) || self::children( $child_id, 'pending' ) ) {
			return 'Bağlı işletmesi olan bir marka başka bir markaya bağlanamaz.';
		}
		if ( self::link_of( $parent_id ) ) {
			return 'Seçtiğin işletme başka bir markaya bağlı; doğrudan markanın kendisine bağlan.';
		}
		return '';
	}

	/**
	 * Bağlanılabilecek markaları ada göre arar.
	 *
	 * @return int[]
	 */
	public static function search_brands( $query, $exclude = 0 ) {
		$query = trim( (string) $query );
		if ( mb_strlen( $query ) < 2 ) {
			return array();
		}
		$ids = get_posts(
			array(
				'post_type'      => IBP_Business::post_type(),
				'post_status'    => 'publish',
				's'              => $query,
				'posts_per_page' => 20,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		$ids = array_filter(
			array_map( 'intval', $ids ),
			function ( $id ) use ( $exclude ) {
				return $id !== (int) $exclude && ! self::link_of( $id );
			}
		);
		return array_slice( array_values( $ids ), 0, 8 );
	}

	/* ---------------------------------------------------------------------
	 * İşlemler (admin-post.php üzerinden, ön yüzden gönderilir)
	 * ------------------------------------------------------------------ */

	public static function handle() {
		$action = isset( $_POST['action'] ) ? substr( sanitize_key( wp_unslash( $_POST['action'] ) ), strlen( 'ibp_net_' ) ) : '';
		if ( ! is_user_logged_in() || ! check_admin_referer( self::NONCE ) ) {
			wp_die( 'Bu işlem için oturum açmalısın.' );
		}

		$user   = get_current_user_id();
		$admin  = current_user_can( 'manage_options' ); // Site yöneticisi her bağlantıyı yönetebilir.
		$child  = isset( $_POST['child'] ) ? absint( $_POST['child'] ) : 0;
		$result = '';

		switch ( $action ) {
			case 'request':
				$parent = isset( $_POST['parent'] ) ? absint( $_POST['parent'] ) : 0;
				$type   = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
				$error  = self::validate_request( $child, $parent, $type, $user );
				if ( $error ) {
					$result = 'error:' . $error;
					break;
				}
				update_post_meta( $child, self::PARENT, $parent );
				update_post_meta( $child, self::TYPE, $type );
				update_post_meta( $child, self::STATUS, 'pending' );
				update_post_meta( $child, self::SINCE, time() );
				$result = 'requested';
				break;

			case 'cancel':
			case 'unlink':
				// Alt işletmenin sahibi ya da markanın sahibi bağlantıyı kaldırabilir.
				$link = self::link_of( $child );
				$post = get_post( $child );
				if ( ! $link || ! $post ) {
					break;
				}
				$parent_post = get_post( $link['parent'] );
				$allowed     = $admin || (int) $post->post_author === $user || ( $parent_post && (int) $parent_post->post_author === $user );
				if ( $allowed ) {
					self::clear( $child );
					$result = 'unlinked';
				}
				break;

			case 'approve':
			case 'reject':
				$link        = self::link_of( $child );
				$parent_post = $link ? get_post( $link['parent'] ) : null;
				if ( ! $link || 'pending' !== $link['status'] || ! $parent_post || ( ! $admin && (int) $parent_post->post_author !== $user ) ) {
					$result = 'error:Bu isteği yalnız markanın sahibi yanıtlayabilir.';
					break;
				}
				if ( 'approve' === $action ) {
					update_post_meta( $child, self::STATUS, 'approved' );
					update_post_meta( $child, self::SINCE, time() );
					$result = 'approved';
				} else {
					self::clear( $child );
					$result = 'rejected';
				}
				break;
		}

		IBP_Ranking::flush();
		$back = wp_get_referer() ?: home_url( '/' );
		$back = remove_query_arg( array( 'ibp_net', 'ibp_q' ), $back );
		wp_safe_redirect( add_query_arg( 'ibp_net', rawurlencode( $result ), $back ) );
		exit;
	}

	private static function clear( $id ) {
		delete_post_meta( $id, self::PARENT );
		delete_post_meta( $id, self::TYPE );
		delete_post_meta( $id, self::STATUS );
		delete_post_meta( $id, self::SINCE );
	}
}
