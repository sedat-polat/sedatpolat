<?php
/**
 * Ana sayfa widget'larının ortak yardımcıları.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IBP_Home {

	/**
	 * Sektöre göre kategori ikonu.
	 */
	const SECTOR_ICONS = array(
		'restaurant'                         => 'utensils',
		'bar'                                => 'utensils',
		'gece-klubu'                         => 'music',
		'saglik'                             => 'heart',
		'guzellik-kisisel-bakim'             => 'scissors',
		'evcil-hayvan'                       => 'paw',
		'fitness'                            => 'dumbbell',
		'egitim-kurslar'                     => 'book',
		'konaklama'                          => 'bed',
		'alisveris-perakende'                => 'box',
		'online-magaza'                      => 'box',
		'otomotiv'                           => 'wrench',
		'profesyonel-danismanlik-hizmetleri' => 'briefcase',
		'ev-yasam-hizmetleri'                => 'home',
		'etkinlik-organizasyon'              => 'calendar',
		'sinema'                             => 'music',
		'turizm-gezi'                        => 'pin',
		'genel'                              => 'store',
	);

	/**
	 * Üst kategoriler, işletme sayısına göre.
	 *
	 * @return array[] term, name, slug, icon, sub (alt kategori adları)
	 */
	public static function categories( $limit = 12 ) {
		$taxonomy = IBP_Business::taxonomy_by_label( 'Kategori' );
		if ( ! $taxonomy ) {
			return array();
		}
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'parent'     => 0,
				'hide_empty' => false,
				'orderby'    => 'count',
				'order'      => 'DESC',
				'number'     => max( 1, (int) $limit ),
			)
		);
		if ( is_wp_error( $terms ) ) {
			return array();
		}
		$out = array();
		foreach ( $terms as $term ) {
			$children = get_terms( array( 'taxonomy' => $taxonomy, 'parent' => $term->term_id, 'hide_empty' => false, 'number' => 4, 'fields' => 'names' ) );
			$out[]    = array(
				'term' => $term,
				'name' => $term->name,
				'slug' => $term->slug,
				'icon' => self::icon_for_term( $term ),
				'sub'  => is_wp_error( $children ) ? '' : IBP_Business::lower_tr( implode( ', ', (array) $children ) ),
			);
		}
		return $out;
	}

	/**
	 * Bir kategoride ve şehirdeki yayındaki işletmeler (saatlik önbellekli).
	 *
	 * @return int[]
	 */
	public static function businesses_in( $taxonomy, $term_id, $city ) {
		$key = 'ibp_in_' . md5( $taxonomy . '|' . $term_id . '|' . IBP_Business::lower_tr( $city ) );
		$ids = get_transient( $key );
		if ( is_array( $ids ) ) {
			return $ids;
		}
		$wanted = IBP_Business::lower_tr( $city );
		$ids    = array();
		$all    = get_posts(
			array(
				'post_type'      => IBP_Business::post_type(),
				'post_status'    => 'publish',
				'posts_per_page' => 500,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'tax_query'      => array( array( 'taxonomy' => $taxonomy, 'terms' => array( (int) $term_id ) ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);
		foreach ( $all as $id ) {
			$business = IBP_Business::from_id( $id );
			if ( $business && IBP_Business::lower_tr( $business->city() ) === $wanted ) {
				$ids[] = (int) $id;
			}
		}
		set_transient( $key, $ids, HOUR_IN_SECONDS );
		return $ids;
	}

	public static function icon_for_term( $term ) {
		return self::SECTOR_ICONS[ IBP_Sectors::key_for_term( $term ) ] ?? 'store';
	}

	/**
	 * Voxel arama sayfası adresi; kategori ve şehir, ayarlardaki parametre adlarıyla eklenir.
	 */
	public static function search_url( $category = '', $city = '' ) {
		$url  = (string) IBP_Settings::get( 'search_url' ) ?: home_url( '/' );
		$args = array();
		if ( '' !== $category ) {
			$args[ (string) IBP_Settings::get( 'cat_param' ) ] = $category;
		}
		if ( '' !== $city ) {
			$args[ (string) IBP_Settings::get( 'city_param' ) ] = $city;
		}
		return $args ? add_query_arg( $args, $url ) : $url;
	}

	/**
	 * "Ne arıyorsan, *nokta atışı* bulalım." → yıldızlı kısım vurgulu.
	 */
	public static function title_html( $text ) {
		return preg_replace( '/\*(.+?)\*/u', '<em>$1</em>', esc_html( (string) $text ) );
	}

	/**
	 * {city}, {city_gen} (İstanbul'un), {city_loc} (İstanbul'da) kısayollarını doldurur.
	 */
	public static function fill_city( $text, $city ) {
		return strtr(
			(string) $text,
			array(
				'{city_gen}' => IBP_Tr::genitive( $city ),
				'{city_loc}' => IBP_Tr::locative( $city ),
				'{city}'     => $city,
			)
		);
	}

	public static function initials( $name ) {
		$words = preg_split( '/\s+/u', trim( wp_strip_all_tags( (string) $name ) ), -1, PREG_SPLIT_NO_EMPTY );
		$out   = '';
		foreach ( array_slice( $words, 0, 2 ) as $word ) {
			$out .= mb_substr( $word, 0, 1 );
		}
		return IBP_Business::upper_tr( $out );
	}

	/**
	 * İsme göre sabit bir renk geçişi (logo yoksa baş harflerin zemini).
	 */
	public static function gradient( $name ) {
		$pairs = array(
			array( '#FF8A8E', '#E5484D' ),
			array( '#3A3F45', '#1C1E21' ),
			array( '#2C4A6E', '#1A2B45' ),
			array( '#1E5F6E', '#0E3B45' ),
			array( '#A87842', '#6E4C24' ),
			array( '#4A3D6E', '#2A2145' ),
			array( '#2F5E52', '#1A3D34' ),
		);
		$pair = $pairs[ abs( crc32( (string) $name ) ) % count( $pairs ) ];
		return 'linear-gradient(145deg,' . $pair[0] . ',' . $pair[1] . ')';
	}

	/**
	 * İşletme avatarı: logo ya da baş harfler.
	 */
	public static function avatar( $business_id, $class = 'ibp-h-av' ) {
		$name     = get_the_title( $business_id );
		$business = IBP_Business::from_id( $business_id );
		$logo     = $business ? $business->logo_url() : '';
		if ( $logo ) {
			return '<span class="' . esc_attr( $class ) . '"><img src="' . esc_url( $logo ) . '" alt=""></span>';
		}
		return '<span class="' . esc_attr( $class ) . '" style="background:' . esc_attr( self::gradient( $name ) ) . '">' . esc_html( self::initials( $name ) ) . '</span>';
	}

	/**
	 * Sayfanın alt bilgisindeki sayılar.
	 *
	 * @return array{businesses:int, reviews:int}
	 */
	public static function stats() {
		$cached = get_transient( 'ibp_home_stats' );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		global $wpdb;
		$counts  = wp_count_posts( IBP_Business::post_type() );
		$reviews = 0;
		$columns = IBP_Reviews::columns();
		if ( in_array( 'post_id', $columns, true ) ) {
			$feed    = in_array( 'feed', $columns, true ) ? $wpdb->prepare( ' WHERE feed = %s', 'post_reviews' ) : '';
			$reviews = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM `' . IBP_Reviews::table() . '`' . $feed ); // phpcs:ignore WordPress.DB
		}
		$stats = array( 'businesses' => (int) ( $counts->publish ?? 0 ), 'reviews' => $reviews );
		set_transient( 'ibp_home_stats', $stats, HOUR_IN_SECONDS );
		return $stats;
	}

	/**
	 * Kısa sayı: 42500 → "42.500+", 180000 → "180B+".
	 */
	public static function short_number( $n ) {
		if ( $n >= 100000 ) {
			return number_format_i18n( floor( $n / 1000 ) ) . 'B+';
		}
		return number_format_i18n( $n ) . ( $n >= 100 ? '+' : '' );
	}

	/**
	 * Bölüm başlığı (prototipteki "section-head-editorial").
	 */
	public static function head( $eyebrow, $title, $sub = '', $side = '', $center = false ) {
		?>
		<div class="ibp-h-head<?php echo $center ? ' ibp-h-head--center' : ''; ?>">
			<div>
				<?php if ( '' !== $eyebrow ) : ?>
					<span class="ibp-h-eyebrow"><?php echo esc_html( $eyebrow ); ?></span>
				<?php endif; ?>
				<h2 class="ibp-h-title"><?php echo self::title_html( $title ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h2>
				<?php if ( '' !== $sub ) : ?>
					<p class="ibp-h-sub"><?php echo esc_html( $sub ); ?></p>
				<?php endif; ?>
			</div>
			<?php if ( '' !== $side ) : ?>
				<div class="ibp-h-head__side"><?php echo $side; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Şehir değiştirme düğmesi (açılır liste).
	 */
	public static function city_switch( array $city ) {
		ob_start();
		?>
		<details class="ibp-h-city">
			<summary><?php echo IBP_Icons::svg( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><b><?php echo esc_html( $city['name'] ); ?></b><span>Değiştir</span></summary>
			<div class="ibp-h-city__list">
				<?php foreach ( IBP_City::all() as $slug => $name ) : ?>
					<a href="<?php echo esc_url( IBP_City::url( $slug ) ); ?>"<?php echo $slug === $city['slug'] ? ' aria-current="true"' : ''; ?>><?php echo esc_html( $name ); ?></a>
				<?php endforeach; ?>
			</div>
		</details>
		<?php
		return ob_get_clean();
	}
}
