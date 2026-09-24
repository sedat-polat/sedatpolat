<?php
/**
 * Giriş yapan kullanıcının işletmelerini bulur ve Voxel alanlarını okur.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IBP_Business {

	const USER_META = 'ibp_current_isletme';
	const NONCE     = 'ibp_switch';

	/** @var int */
	public $id;

	/** @var WP_Post */
	public $post;

	private function __construct( WP_Post $post ) {
		$this->id   = (int) $post->ID;
		$this->post = $post;
	}

	public static function post_type() {
		return apply_filters( 'ibp_post_type', 'isletme' );
	}

	/* ---------------------------------------------------------------------
	 * Hangi işletme?
	 * ------------------------------------------------------------------ */

	/**
	 * Kullanıcının sahibi olduğu işletmelerin ID'leri (Voxel'de sahip = yazar).
	 *
	 * @return int[]
	 */
	public static function owned_ids( $user_id = 0 ) {
		$user_id = $user_id ? (int) $user_id : get_current_user_id();
		if ( ! $user_id ) {
			return array();
		}
		return array_map(
			'intval',
			get_posts(
				array(
					'post_type'      => self::post_type(),
					'author'         => $user_id,
					'post_status'    => array( 'publish', 'pending', 'draft', 'private' ),
					'posts_per_page' => 50,
					'orderby'        => 'title',
					'order'          => 'ASC',
					'fields'         => 'ids',
					'no_found_rows'  => true,
				)
			)
		);
	}

	/**
	 * Panelde gösterilecek işletme. Yoksa null.
	 *
	 * @return IBP_Business|null
	 */
	public static function current() {
		static $cache = false;
		if ( false !== $cache ) {
			return $cache;
		}

		$ids = self::owned_ids();
		$id  = 0;

		if ( $ids ) {
			$saved = (int) get_user_meta( get_current_user_id(), self::USER_META, true );
			$id    = in_array( $saved, $ids, true ) ? $saved : $ids[0];
		} elseif ( self::is_editor_preview() ) {
			// Elementor'da düzenlerken örnek olarak son işletmeyi göster.
			$latest = get_posts(
				array(
					'post_type'      => self::post_type(),
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'no_found_rows'  => true,
				)
			);
			$id     = $latest ? (int) $latest[0] : 0;
		}

		$post  = $id ? get_post( $id ) : null;
		$cache = $post ? new self( $post ) : null;
		return $cache;
	}

	public static function is_editor_preview() {
		if ( ! class_exists( '\Elementor\Plugin' ) || ! current_user_can( 'edit_posts' ) ) {
			return false;
		}
		$elementor = \Elementor\Plugin::$instance;
		return ( isset( $elementor->editor ) && $elementor->editor->is_edit_mode() )
			|| ( isset( $elementor->preview ) && $elementor->preview->is_preview_mode() );
	}

	/**
	 * İşletme seçme kutusundan gelen isteği işler: ?ibp_isletme=ID&_ibpnonce=...
	 */
	public static function handle_switch() {
		if ( ! isset( $_GET['ibp_isletme'], $_GET['_ibpnonce'] ) || ! is_user_logged_in() ) {
			return;
		}
		if ( isset( $_GET['elementor-preview'] ) || self::is_editor_preview() ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_ibpnonce'] ) ), self::NONCE ) ) {
			return;
		}
		$id = absint( $_GET['ibp_isletme'] );
		if ( in_array( $id, self::owned_ids(), true ) ) {
			update_user_meta( get_current_user_id(), self::USER_META, $id );
		}
		wp_safe_redirect( remove_query_arg( array( 'ibp_isletme', '_ibpnonce' ) ) );
		exit;
	}

	/* ---------------------------------------------------------------------
	 * Alanlar
	 * ------------------------------------------------------------------ */

	/**
	 * Voxel alan değerini okur. Voxel API'si yoksa post meta'ya düşer.
	 *
	 * @return mixed
	 */
	public function field( $key ) {
		$value = null;

		$voxel_post = $this->voxel_post();
		if ( $voxel_post && method_exists( $voxel_post, 'get_field' ) ) {
			try {
				$field = $voxel_post->get_field( $key );
				if ( $field && method_exists( $field, 'get_value' ) ) {
					$value = $field->get_value();
				}
			} catch ( \Throwable $e ) {
				$value = null;
			}
		}

		if ( null === $value ) {
			$value = get_post_meta( $this->id, $key, true );
		}

		return apply_filters( 'ibp_field_value', $value, $key, $this );
	}

	public function name() {
		return get_the_title( $this->post );
	}

	public function initials() {
		$words = preg_split( '/\s+/u', trim( wp_strip_all_tags( $this->name() ) ), -1, PREG_SPLIT_NO_EMPTY );
		$out   = '';
		foreach ( array_slice( $words, 0, 2 ) as $word ) {
			$out .= mb_substr( $word, 0, 1 );
		}
		return self::upper_tr( $out );
	}

	public function logo_url( $size = 'thumbnail' ) {
		$ids = self::attachment_ids( $this->field( 'logo' ) );
		return $ids ? wp_get_attachment_image_url( $ids[0], $size ) : '';
	}

	/**
	 * Taksonomiyi etiketine göre bulur (ör. "Kategori", "Şehir"), ilk terimin adını döner.
	 */
	public function term_by_label( $label ) {
		$taxonomy = self::taxonomy_by_label( $label );
		if ( ! $taxonomy ) {
			return '';
		}
		$terms = get_the_terms( $this->post, $taxonomy );
		return ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : '';
	}

	public function subtitle() {
		$parts = array_filter( array( $this->term_by_label( 'Şehir' ), $this->term_by_label( 'Kategori' ) ) );
		return implode( ', ', $parts );
	}

	/**
	 * @return array{status:string, ranges:array, open_now:bool}
	 */
	public function today_hours() {
		$groups = IBP_Work_Hours::normalize( $this->field( 'work-hours' ) );
		return IBP_Work_Hours::for_day( $groups, new DateTimeImmutable( 'now', wp_timezone() ) );
	}

	/**
	 * Voxel yorum istatistikleri.
	 *
	 * @return array{count:int, average:float|null} average 1–5 ölçeğinde.
	 */
	public function review_stats() {
		$stats      = null;
		$voxel_post = $this->voxel_post();

		if ( $voxel_post && isset( $voxel_post->repository ) && method_exists( $voxel_post->repository, 'get_review_stats' ) ) {
			try {
				$stats = $voxel_post->repository->get_review_stats();
			} catch ( \Throwable $e ) {
				$stats = null;
			}
		}
		if ( ! is_array( $stats ) ) {
			$stats = json_decode( (string) get_post_meta( $this->id, 'voxel:review_stats', true ), true );
		}

		$count   = is_array( $stats ) ? (int) ( $stats['total'] ?? 0 ) : 0;
		$average = ( $count && isset( $stats['average'] ) && is_numeric( $stats['average'] ) )
			? (float) $stats['average'] + 3 // Voxel puanı -2…+2 arasında tutar; 1–5'e çeviriyoruz.
			: null;

		return apply_filters( 'ibp_review_stats', array( 'count' => $count, 'average' => $average ), $this );
	}

	/**
	 * Ön yüzde işletmeyi düzenleme bağlantısı.
	 *
	 * @param string $template Widget'ta girilen adres; {id} işletme ID'si ile değişir.
	 */
	public function edit_url( $template = '' ) {
		if ( $template ) {
			return str_replace( '{id}', (string) $this->id, $template );
		}
		$voxel_post = $this->voxel_post();
		if ( $voxel_post && method_exists( $voxel_post, 'get_edit_link' ) ) {
			try {
				$link = $voxel_post->get_edit_link();
				if ( $link ) {
					return $link;
				}
			} catch ( \Throwable $e ) {
				// Aşağıdaki varsayılana düş.
			}
		}
		return get_permalink( $this->post );
	}

	/* ---------------------------------------------------------------------
	 * Profil doluluğu
	 * ------------------------------------------------------------------ */

	/**
	 * @return array Etiket => dolu mu.
	 */
	public function completeness_items() {
		$items = array(
			'Kısa açıklama'    => self::has_value( $this->field( 'text' ) ),
			'Kategori'         => '' !== $this->term_by_label( 'Kategori' ),
			'Fiyat aralığı'    => '' !== $this->term_by_label( 'Fiyat Aralığı' ),
			'İşletme hakkında' => self::has_value( wp_strip_all_tags( $this->post->post_content ) ),
			'Logo'             => (bool) self::attachment_ids( $this->field( 'logo' ) ),
			'Vitrin görseli'   => has_post_thumbnail( $this->post ),
			'Galeri'           => (bool) self::attachment_ids( $this->field( 'gallery' ) ),
			'E-posta'          => self::has_value( $this->field( 'email' ) ),
			'Telefon'          => self::has_value( $this->field( 'phone' ) ),
			'Web sitesi'       => self::has_value( $this->field( 'website' ) ),
			'Sosyal medya'     => self::has_value( $this->field( 'repeater' ) ),
			'Çalışma saatleri' => (bool) IBP_Work_Hours::normalize( $this->field( 'work-hours' ) ),
			'Konum'            => self::has_location( $this->field( 'location' ) ),
		);
		return apply_filters( 'ibp_completeness_items', $items, $this );
	}

	/**
	 * @return array{percent:int, missing:string[]}
	 */
	public function completeness() {
		$items = $this->completeness_items();
		$done  = count( array_filter( $items ) );
		return array(
			'percent' => $items ? (int) round( 100 * $done / count( $items ) ) : 0,
			'missing' => array_keys( array_filter( $items, function ( $ok ) { return ! $ok; } ) ),
		);
	}

	/* ---------------------------------------------------------------------
	 * Yardımcılar
	 * ------------------------------------------------------------------ */

	private function voxel_post() {
		if ( ! class_exists( '\Voxel\Post' ) ) {
			return null;
		}
		try {
			return \Voxel\Post::get( $this->post );
		} catch ( \Throwable $e ) {
			return null;
		}
	}

	public static function taxonomy_by_label( $label ) {
		$wanted = self::lower_tr( $label );
		foreach ( get_object_taxonomies( self::post_type(), 'objects' ) as $taxonomy ) {
			$names = array( $taxonomy->label, $taxonomy->labels->name ?? '', $taxonomy->labels->singular_name ?? '' );
			foreach ( $names as $name ) {
				if ( $name && self::lower_tr( $name ) === $wanted ) {
					return $taxonomy->name;
				}
			}
		}
		return '';
	}

	/**
	 * "12", "12,34", [12, 34] ya da [ ['id' => 12] ] biçimlerini ID listesine çevirir.
	 *
	 * @return int[]
	 */
	public static function attachment_ids( $value ) {
		if ( is_string( $value ) ) {
			$decoded = json_decode( $value, true );
			$value   = is_array( $decoded ) ? $decoded : explode( ',', $value );
		}
		$ids = array();
		foreach ( (array) $value as $item ) {
			$id = is_array( $item ) ? ( $item['id'] ?? 0 ) : $item;
			if ( is_numeric( $id ) && (int) $id > 0 ) {
				$ids[] = (int) $id;
			}
		}
		return $ids;
	}

	public static function has_value( $value ) {
		if ( is_string( $value ) ) {
			$decoded = json_decode( $value, true );
			if ( is_array( $decoded ) ) {
				$value = $decoded;
			}
		}
		if ( is_array( $value ) ) {
			foreach ( $value as $item ) {
				if ( self::has_value( $item ) ) {
					return true;
				}
			}
			return false;
		}
		return null !== $value && false !== $value && '' !== trim( (string) $value );
	}

	public static function has_location( $value ) {
		if ( is_string( $value ) ) {
			$value = json_decode( $value, true );
		}
		if ( ! is_array( $value ) ) {
			return false;
		}
		return ! empty( $value['address'] ) || ( ! empty( $value['latitude'] ) && ! empty( $value['longitude'] ) );
	}

	public static function upper_tr( $text ) {
		return mb_strtoupper( strtr( $text, array( 'i' => 'İ', 'ı' => 'I' ) ), 'UTF-8' );
	}

	public static function lower_tr( $text ) {
		return mb_strtolower( strtr( $text, array( 'İ' => 'i', 'I' => 'ı' ) ), 'UTF-8' );
	}
}
