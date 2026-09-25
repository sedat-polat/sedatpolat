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
		return apply_filters( 'ibp_post_type', IBP_Settings::get( 'post_type' ) ?: 'isletme' );
	}

	/**
	 * @return IBP_Business|null
	 */
	public static function from_id( $id ) {
		$post = get_post( (int) $id );
		return ( $post && self::post_type() === $post->post_type ) ? new self( $post ) : null;
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
	 * Panelde gösterilecek işletme. "Tüm bağlı işletmeler" seçiliyse markanın kendisi.
	 * Kapsamın tamamı için IBP_Business::scope() kullanılır.
	 *
	 * @return IBP_Business|null
	 */
	public static function current() {
		return self::scope()['business'];
	}

	/**
	 * Panelin kapsamı.
	 *
	 * @return array{business:IBP_Business|null, brand:bool, ids:int[], children:int[]}
	 *   brand: "Tüm bağlı işletmeler" görünümü mü; ids: verisi toplanacak işletmeler.
	 */
	public static function scope() {
		static $cache = null;
		if ( null !== $cache ) {
			return $cache;
		}

		$cache = array( 'business' => null, 'brand' => false, 'ids' => array(), 'children' => array() );
		$ids   = IBP_Network::accessible_ids();
		$saved = (string) get_user_meta( get_current_user_id(), self::USER_META, true );
		$id    = 0;

		if ( 0 === strpos( $saved, 'brand:' ) ) {
			$brand    = (int) substr( $saved, 6 );
			$children = in_array( $brand, self::owned_ids(), true ) ? IBP_Network::children( $brand ) : array();
			if ( $children ) {
				$cache['business'] = self::from_id( $brand );
				$cache['brand']    = true;
				$cache['children'] = $children;
				$cache['ids']      = array_merge( array( $brand ), $children );
				return $cache;
			}
		}

		if ( $ids ) {
			$id = in_array( (int) $saved, $ids, true ) ? (int) $saved : $ids[0];
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

		$business = $id ? self::from_id( $id ) : null;
		if ( $business ) {
			$cache['business'] = $business;
			$cache['ids']      = array( $business->id );
		}
		return $cache;
	}

	/**
	 * Paneli başka bir işletmeye (ya da "brand:ID" ile marka görünümüne) geçiren adres.
	 */
	public static function switch_url( $value ) {
		return add_query_arg(
			array(
				'ibp_isletme' => (string) $value,
				'_ibpnonce'   => wp_create_nonce( self::NONCE ),
			),
			remove_query_arg( array( 'ibp_isletme', '_ibpnonce', 'ibp_net' ) )
		);
	}

	/**
	 * Seçme kutusunun seçenekleri: kendi işletmeleri, markalarının toplu görünümü ve
	 * bağlı işletmeleri.
	 *
	 * @return array[] Her biri: value, label, group.
	 */
	public static function switch_options() {
		$options = array();
		foreach ( self::owned_ids() as $id ) {
			$children = IBP_Network::children( $id );
			if ( ! $children ) {
				continue;
			}
			$group     = get_the_title( $id ) . ' markası';
			$options[] = array( 'value' => 'brand:' . $id, 'label' => sprintf( 'Tüm bağlı işletmeler (%d)', count( $children ) + 1 ), 'group' => $group );
			$options[] = array( 'value' => (string) $id, 'label' => get_the_title( $id ) . ' (merkez)', 'group' => $group );
			foreach ( $children as $child ) {
				$link      = IBP_Network::link_of( $child );
				$options[] = array( 'value' => (string) $child, 'label' => get_the_title( $child ) . ' · ' . IBP_Network::types()[ $link['type'] ], 'group' => $group );
			}
		}
		$grouped = wp_list_pluck( $options, 'value' );
		foreach ( self::owned_ids() as $id ) {
			if ( ! in_array( (string) $id, $grouped, true ) ) {
				$options[] = array( 'value' => (string) $id, 'label' => get_the_title( $id ), 'group' => $options ? 'Diğer işletmelerim' : '' );
			}
		}
		return $options;
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
		$value = sanitize_text_field( wp_unslash( $_GET['ibp_isletme'] ) );
		if ( in_array( $value, wp_list_pluck( self::switch_options(), 'value' ), true ) ) {
			update_user_meta( get_current_user_id(), self::USER_META, $value );
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
		$parts = array_filter( array( $this->city(), $this->term_by_label( 'Kategori' ) ) );
		return implode( ', ', $parts );
	}

	/**
	 * Şehir terimi; atanmamışsa konum adresinden çıkarılan şehir.
	 */
	public function city() {
		$term = $this->term_by_label( 'Şehir' );
		if ( '' !== $term ) {
			return $term;
		}
		$location = $this->field( 'location' );
		if ( is_string( $location ) ) {
			$location = json_decode( $location, true );
		}
		return is_array( $location ) ? self::city_from_address( (string) ( $location['address'] ?? '' ) ) : '';
	}

	/**
	 * "Moda Cd. No:5, 34710 Kadıköy/İstanbul, Türkiye" → "İstanbul"
	 * "İzmir, Ege Bölgesi, Türkiye" → "İzmir"
	 */
	public static function city_from_address( $address ) {
		$parts = array_filter(
			array_map( 'trim', explode( ',', $address ) ),
			function ( $part ) {
				$lower = self::lower_tr( $part );
				return '' !== $part
					&& ! in_array( $lower, array( 'türkiye', 'turkey', 'turkiye' ), true )
					&& ! preg_match( '/bölgesi$|region$/u', $lower );
			}
		);
		if ( ! $parts ) {
			return '';
		}
		$last = explode( '/', end( $parts ) );
		return trim( preg_replace( '/^\d{5}\s*/', '', end( $last ) ) );
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
		if ( ! $template ) {
			$template = (string) IBP_Settings::get( 'edit_url' );
		}
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

	/**
	 * Widget'lara girilen adreslerdeki kısayolları çözer:
	 * {id} işletme ID'si, {edit} düzenleme adresi, {view} işletme sayfası.
	 */
	public function resolve_url( $url ) {
		$url = (string) $url;
		if ( false !== strpos( $url, '{edit}' ) ) {
			$url = str_replace( '{edit}', $this->edit_url(), $url );
		}
		return str_replace( array( '{view}', '{id}' ), array( get_permalink( $this->post ), (string) $this->id ), $url );
	}

	public function permalink() {
		return get_permalink( $this->post );
	}

	public function status_label() {
		switch ( $this->post->post_status ) {
			case 'publish':
				return array( 'Profil yayında', '#10B981' );
			case 'pending':
				return array( 'Onay bekliyor', '#F59E0B' );
			case 'draft':
				return array( 'Taslak', '#9CA3AF' );
			default:
				return array( 'Gizli', '#9CA3AF' );
		}
	}

	/**
	 * Birden çok işletmenin toplam yorum sayısı ve yorum sayısına göre ağırlıklı ortalaması.
	 *
	 * @param int[] $ids
	 * @return array{count:int, average:float|null}
	 */
	public static function combined_review_stats( array $ids ) {
		$count = 0;
		$sum   = 0.0;
		foreach ( $ids as $id ) {
			$business = self::from_id( $id );
			if ( ! $business ) {
				continue;
			}
			$stats = $business->review_stats();
			if ( $stats['count'] && null !== $stats['average'] ) {
				$count += $stats['count'];
				$sum   += $stats['count'] * $stats['average'];
			}
		}
		return array( 'count' => $count, 'average' => $count ? $sum / $count : null );
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
	 * Bekleyen işler
	 * ------------------------------------------------------------------ */

	/**
	 * İşletme sahibinin yapması gerekenler, önem sırasıyla.
	 *
	 * @param string $reviews_url Yorumlar sayfası adresi (kısayollar çözülür).
	 * @return array[] Her biri: n (rozet), title, sub, url.
	 */
	public function todos( $reviews_url = '' ) {
		$todos = array();
		$edit  = $this->edit_url();

		if ( 'pending' === $this->post->post_status ) {
			$todos[] = array( 'n' => '!', 'title' => 'Profilin onay bekliyor', 'sub' => 'Onaylanınca aramalarda ve şehir sayfalarında görünür.', 'url' => $this->permalink() );
		} elseif ( 'draft' === $this->post->post_status ) {
			$todos[] = array( 'n' => '!', 'title' => 'Profilin taslakta', 'sub' => 'Yayına göndermeden ziyaretçiler seni göremez.', 'url' => $edit );
		}

		$unanswered = IBP_Reviews::unanswered( $this );
		if ( $unanswered ) {
			$todos[] = array(
				'n'     => (string) $unanswered,
				'title' => 'yorum yanıt bekliyor',
				'sub'   => 'Hızlı yanıt, Şehrin Sahipleri sıralamasında öne çıkarır.',
				'url'   => $reviews_url ? $this->resolve_url( $reviews_url ) : $this->permalink(),
			);
		}

		$missing = $this->completeness()['missing'];
		if ( $missing ) {
			$todos[] = array(
				'n'     => (string) count( $missing ),
				'title' => 'profil alanı eksik',
				'sub'   => implode( ', ', array_slice( $missing, 0, 3 ) ) . ( count( $missing ) > 3 ? ' ve diğerleri' : '' ),
				'url'   => $edit,
			);
		}

		$photos = count( self::attachment_ids( $this->field( 'gallery' ) ) );
		if ( $photos < 5 ) {
			$todos[] = array(
				'n'     => (string) ( 5 - $photos ),
				'title' => 'fotoğraf daha ekle',
				'sub'   => sprintf( 'Galeride %d fotoğraf var; en az 5 fotoğraflı profiller daha çok tıklanıyor.', $photos ),
				'url'   => $edit,
			);
		}

		if ( 0 === $this->review_stats()['count'] ) {
			$todos[] = array(
				'n'     => '0',
				'title' => 'İlk yorumunu topla',
				'sub'   => 'Profil bağlantını müşterilerinle paylaş; sıralamaya girmek için yorum gerekiyor.',
				'url'   => $this->permalink(),
			);
		}

		return apply_filters( 'ibp_todos', $todos, $this );
	}

	/**
	 * Panel kapsamına göre bekleyen işler: tek işletmede kendi işleri, marka görünümünde
	 * bağlanma istekleri ve bağlı işletmelerin toplu durumu.
	 */
	public static function scope_todos( $reviews_url = '' ) {
		$scope    = self::scope();
		$business = $scope['business'];
		if ( ! $business ) {
			return array();
		}
		$network = IBP_Network::page_url();
		$todos   = array();

		// Markaya gelen bağlanma istekleri (tek işletme görünümünde de gösterilir).
		$requests = IBP_Network::children( $business->id, 'pending' );
		if ( $requests && (int) $business->post->post_author === get_current_user_id() ) {
			$todos[] = array(
				'n'     => (string) count( $requests ),
				'title' => 'bağlanma isteği onay bekliyor',
				'sub'   => implode( ', ', array_map( 'get_the_title', array_slice( $requests, 0, 3 ) ) ),
				'url'   => $network ?: $business->permalink(),
			);
		}

		// Alt işletmenin markaya bağlantısı henüz onaylanmadıysa.
		$link = IBP_Network::link_of( $business->id );
		if ( ! $scope['brand'] && $link && 'pending' === $link['status'] ) {
			$todos[] = array(
				'n'     => '…',
				'title' => 'Marka onayı bekleniyor',
				'sub'   => sprintf( '%s markasına %s olarak bağlanma isteğin gönderildi.', get_the_title( $link['parent'] ), IBP_Network::types()[ $link['type'] ] ),
				'url'   => $network ?: $business->permalink(),
			);
		}

		if ( ! $scope['brand'] ) {
			return apply_filters( 'ibp_scope_todos', array_merge( $todos, $business->todos( $reviews_url ) ), $scope );
		}

		$unanswered = 0;
		$incomplete = array();
		$pending    = array();
		foreach ( $scope['ids'] as $id ) {
			$item = self::from_id( $id );
			if ( ! $item ) {
				continue;
			}
			$unanswered += (int) IBP_Reviews::unanswered( $item );
			if ( $item->completeness()['missing'] ) {
				$incomplete[] = $item->name();
			}
			if ( 'publish' !== $item->post->post_status ) {
				$pending[] = $item->name();
			}
		}
		if ( $unanswered ) {
			$todos[] = array( 'n' => (string) $unanswered, 'title' => 'yorum yanıt bekliyor', 'sub' => 'Marka ve bağlı işletmelerin toplamı.', 'url' => $reviews_url ? $business->resolve_url( $reviews_url ) : $business->permalink() );
		}
		if ( $incomplete ) {
			$todos[] = array( 'n' => (string) count( $incomplete ), 'title' => 'işletmenin profili eksik', 'sub' => implode( ', ', array_slice( $incomplete, 0, 3 ) ) . ( count( $incomplete ) > 3 ? ' ve diğerleri' : '' ), 'url' => $network ?: $business->permalink() );
		}
		if ( $pending ) {
			$todos[] = array( 'n' => (string) count( $pending ), 'title' => 'işletme yayında değil', 'sub' => implode( ', ', array_slice( $pending, 0, 3 ) ), 'url' => $network ?: $business->permalink() );
		}
		return apply_filters( 'ibp_scope_todos', $todos, $scope );
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
