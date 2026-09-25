<?php
/**
 * Ana sayfa şehri: ?sehir=izmir ile seçilir (önbellek her şehir için ayrı sayfa tutar),
 * yoksa ayarlardaki varsayılan. Şehir listesi "Şehir" taksonomisinden, o yoksa 81 ilden.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IBP_City {

	const PARAM = 'sehir';

	const PROVINCES = array(
		'Adana', 'Adıyaman', 'Afyonkarahisar', 'Ağrı', 'Aksaray', 'Amasya', 'Ankara', 'Antalya', 'Ardahan', 'Artvin', 'Aydın',
		'Balıkesir', 'Bartın', 'Batman', 'Bayburt', 'Bilecik', 'Bingöl', 'Bitlis', 'Bolu', 'Burdur', 'Bursa', 'Çanakkale',
		'Çankırı', 'Çorum', 'Denizli', 'Diyarbakır', 'Düzce', 'Edirne', 'Elazığ', 'Erzincan', 'Erzurum', 'Eskişehir',
		'Gaziantep', 'Giresun', 'Gümüşhane', 'Hakkari', 'Hatay', 'Iğdır', 'Isparta', 'İstanbul', 'İzmir', 'Kahramanmaraş',
		'Karabük', 'Karaman', 'Kars', 'Kastamonu', 'Kayseri', 'Kilis', 'Kırıkkale', 'Kırklareli', 'Kırşehir', 'Kocaeli',
		'Konya', 'Kütahya', 'Malatya', 'Manisa', 'Mardin', 'Mersin', 'Muğla', 'Muş', 'Nevşehir', 'Niğde', 'Ordu',
		'Osmaniye', 'Rize', 'Sakarya', 'Samsun', 'Şanlıurfa', 'Siirt', 'Sinop', 'Şırnak', 'Sivas', 'Tekirdağ', 'Tokat',
		'Trabzon', 'Tunceli', 'Uşak', 'Van', 'Yalova', 'Yozgat', 'Zonguldak',
	);

	/**
	 * Şehir listesi: slug => ad.
	 */
	public static function all() {
		static $cache = null;
		if ( null !== $cache ) {
			return $cache;
		}
		$cache    = array();
		$taxonomy = IBP_Business::taxonomy_by_label( 'Şehir' );
		$terms    = $taxonomy ? get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false, 'parent' => 0, 'number' => 200 ) ) : array();
		if ( ! is_wp_error( $terms ) && count( (array) $terms ) >= 5 ) {
			foreach ( $terms as $term ) {
				$cache[ $term->slug ] = $term->name;
			}
		} else {
			foreach ( self::PROVINCES as $name ) {
				$cache[ self::slug( $name ) ] = $name;
			}
		}
		return $cache;
	}

	public static function slug( $name ) {
		return sanitize_title( strtr( $name, array( 'İ' => 'I', 'ı' => 'i', 'Ş' => 'S', 'ş' => 's', 'Ğ' => 'G', 'ğ' => 'g', 'Ü' => 'U', 'ü' => 'u', 'Ö' => 'O', 'ö' => 'o', 'Ç' => 'C', 'ç' => 'c' ) ) );
	}

	/**
	 * @return array{slug:string, name:string}
	 */
	public static function current() {
		$all  = self::all();
		$slug = isset( $_GET[ self::PARAM ] ) ? sanitize_title( wp_unslash( $_GET[ self::PARAM ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '' === $slug || ! isset( $all[ $slug ] ) ) {
			$slug = self::slug( (string) IBP_Settings::get( 'home_city' ) );
		}
		if ( ! isset( $all[ $slug ] ) ) {
			$slug = (string) array_key_first( $all );
		}
		return array( 'slug' => $slug, 'name' => $all[ $slug ] ?? '' );
	}

	/**
	 * Aynı sayfanın başka bir şehirle açılan adresi.
	 */
	public static function url( $slug ) {
		return add_query_arg( self::PARAM, $slug, remove_query_arg( self::PARAM ) );
	}

	/**
	 * Şehir başına yayındaki işletme sayısı (günlük önbellekli).
	 *
	 * @return array<string,int> slug => sayı
	 */
	public static function counts() {
		$cached = get_transient( 'ibp_city_counts' );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		$counts  = array();
		$by_name = array();
		foreach ( self::all() as $slug => $name ) {
			$by_name[ IBP_Business::lower_tr( $name ) ] = $slug;
			$counts[ $slug ] = 0;
		}

		// Şehri taksonomi ya da adresten okunan her işletmeyi say (ilk 5.000 işletme).
		$ids = get_posts(
			array(
				'post_type'      => IBP_Business::post_type(),
				'post_status'    => 'publish',
				'posts_per_page' => 5000,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		foreach ( $ids as $id ) {
			$business = IBP_Business::from_id( $id );
			$key      = $business ? IBP_Business::lower_tr( $business->city() ) : '';
			if ( isset( $by_name[ $key ] ) ) {
				$counts[ $by_name[ $key ] ]++;
			}
		}
		set_transient( 'ibp_city_counts', $counts, DAY_IN_SECONDS );
		return $counts;
	}
}
