<?php
/**
 * Sektör profilleri: işletmenin kategorisine göre menüde hangi operasyon
 * modüllerinin ve hangi adların görüneceği (prototipteki SECTORS tablosu).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IBP_Sectors {

	const GENERAL = 'genel';

	/**
	 * Modüller: ikon ve varsayılan ad. Ad, sektöre göre değişebilir.
	 */
	public static function modules() {
		return array(
			'reservations' => array( 'calcheck', 'Rezervasyonlar' ),
			'tables'       => array( 'tables', 'Masa planı' ),
			'appts'        => array( 'cal', 'Randevular' ),
			'doctors'      => array( 'person', 'Personel' ),
			'members'      => array( 'card', 'Üyeler' ),
			'classes'      => array( 'classes', 'Ders programı' ),
			'rooms'        => array( 'bed', 'Oda takvimi' ),
			'orders'       => array( 'box', 'Siparişler' ),
			'stock'        => array( 'stock', 'Ürünler ve stok' ),
			'leads'        => array( 'leads', 'Talepler' ),
		);
	}

	/**
	 * Anahtar => profil. "labels" modül adlarını sektöre göre değiştirir.
	 */
	public static function profiles() {
		$p = function ( $name, $ops, $mods, $menu, $events = 'Etkinlikler', $labels = array() ) {
			return compact( 'name', 'ops', 'mods', 'menu', 'events', 'labels' );
		};

		return array(
			'restaurant'                         => $p( 'Restoran', 'Operasyon', array( 'reservations', 'tables' ), 'Menü ve fiyatlar' ),
			'bar'                                => $p( 'Bar', 'Operasyon', array( 'reservations', 'tables' ), 'Menü ve fiyatlar' ),
			'gece-klubu'                         => $p( 'Gece Kulübü', 'Operasyon', array( 'reservations', 'tables' ), 'Menü ve fiyatlar', 'Geceler ve biletler' ),
			'saglik'                             => $p( 'Sağlık', 'Klinik', array( 'appts', 'doctors' ), 'Hizmetler ve fiyatlar', 'Etkinlikler', array( 'doctors' => 'Hekimler' ) ),
			'guzellik-kisisel-bakim'             => $p( 'Güzellik & Kişisel Bakım', 'Salon', array( 'appts', 'doctors', 'members' ), 'Hizmetler ve fiyatlar', 'Etkinlikler', array( 'doctors' => 'Uzmanlar', 'members' => 'Paket müşterileri' ) ),
			'evcil-hayvan'                       => $p( 'Evcil Hayvan', 'Klinik', array( 'appts', 'doctors' ), 'Hizmetler ve fiyatlar' ),
			'fitness'                            => $p( 'Fitness', 'Kulüp', array( 'members', 'classes', 'appts', 'doctors' ), 'Hizmetler ve fiyatlar', 'Etkinlikler', array( 'doctors' => 'Eğitmenler' ) ),
			'egitim-kurslar'                     => $p( 'Eğitim & Kurslar', 'Akademi', array( 'members', 'classes', 'appts', 'doctors' ), 'Kurslar ve ücretler', 'Etkinlikler', array( 'doctors' => 'Öğretmenler', 'members' => 'Öğrenciler' ) ),
			'konaklama'                          => $p( 'Konaklama', 'Otel', array( 'rooms' ), 'Oda tipleri ve fiyatlar' ),
			'alisveris-perakende'                => $p( 'Alışveriş & Perakende', 'Mağaza', array( 'orders', 'stock' ), 'Ürün vitrini' ),
			'online-magaza'                      => $p( 'Online Mağaza', 'Mağaza', array( 'orders', 'stock' ), 'Ürün vitrini' ),
			'otomotiv'                           => $p( 'Otomotiv', 'Servis', array( 'appts', 'doctors', 'leads' ), 'Hizmetler ve fiyatlar', 'Etkinlikler', array( 'doctors' => 'Ustalar ve liftler', 'leads' => 'Servis talepleri' ) ),
			'profesyonel-danismanlik-hizmetleri' => $p( 'Profesyonel & Danışmanlık', 'Ofis', array( 'appts', 'doctors', 'leads' ), 'Hizmetler ve ücretler', 'Etkinlikler', array( 'doctors' => 'Danışmanlar', 'leads' => 'Danışmanlık talepleri' ) ),
			'ev-yasam-hizmetleri'                => $p( 'Ev & Yaşam Hizmetleri', 'Saha', array( 'leads', 'appts', 'doctors' ), 'Hizmetler ve fiyatlar', 'Etkinlikler', array( 'doctors' => 'Ekipler', 'leads' => 'İş talepleri' ) ),
			'etkinlik-organizasyon'              => $p( 'Etkinlik & Organizasyon', 'Organizasyon', array( 'leads' ), 'Paketler ve fiyatlar', 'Etkinlikler', array( 'leads' => 'Organizasyon talepleri' ) ),
			'sinema'                             => $p( 'Sinema & Eğlence', 'Gişe', array(), 'Bilet fiyatları', 'Seanslar ve biletler' ),
			'turizm-gezi'                        => $p( 'Turizm & Gezi', 'Tur operasyonu', array( 'leads' ), 'Turlar ve fiyatlar', 'Turlar ve katılımcılar', array( 'leads' => 'Özel tur talepleri' ) ),
			self::GENERAL                        => $p( 'Genel', 'Operasyon', array(), 'Hizmetler ve fiyatlar' ),
		);
	}

	public static function options() {
		return wp_list_pluck( self::profiles(), 'name' );
	}

	/**
	 * İşletmenin sektör anahtarı: önce ayarlardaki eşleştirme, sonra kategori adı/slug'ı.
	 */
	public static function key_for( IBP_Business $business ) {
		$taxonomy = IBP_Business::taxonomy_by_label( 'Kategori' );
		$terms    = $taxonomy ? get_the_terms( $business->post, $taxonomy ) : false;
		if ( ! $terms || is_wp_error( $terms ) ) {
			return self::GENERAL;
		}
		foreach ( $terms as $term ) {
			$key = self::key_for_term( $term );
			if ( self::GENERAL !== $key ) {
				return $key;
			}
		}
		return self::GENERAL;
	}

	/**
	 * Bir kategori teriminin sektörü. Alt kategoriler üst kategorinin sektörünü alır.
	 */
	public static function key_for_term( $term, $depth = 0 ) {
		$map = (array) IBP_Settings::get( 'sector_map' );
		if ( ! empty( $map[ $term->term_id ] ) && isset( self::profiles()[ $map[ $term->term_id ] ] ) ) {
			return $map[ $term->term_id ];
		}
		$auto = self::guess( $term );
		if ( self::GENERAL !== $auto ) {
			return $auto;
		}
		if ( ! empty( $term->parent ) && $depth < 5 ) {
			$parent = get_term( $term->parent, $term->taxonomy );
			if ( $parent && ! is_wp_error( $parent ) ) {
				return self::key_for_term( $parent, $depth + 1 );
			}
		}
		return self::GENERAL;
	}

	/**
	 * Terim slug'ı ya da adı bir sektörle eşleşiyor mu? ("Restaurant" → restaurant)
	 */
	public static function guess( $term ) {
		$profiles = self::profiles();
		if ( isset( $profiles[ $term->slug ] ) ) {
			return $term->slug;
		}
		$name = IBP_Business::lower_tr( $term->name );
		foreach ( $profiles as $key => $profile ) {
			if ( IBP_Business::lower_tr( $profile['name'] ) === $name ) {
				return $key;
			}
		}
		// Kelime başında aranan kökler. Kökler Türkçe ünsüz yumuşamasına göre kısaltıldı
		// (klinik → kliniği, mutfak → mutfağı); "oto" gibi kısa kökler yalnız tam kelimede eşleşir.
		$aliases = array(
			'restoran'   => 'restaurant',
			'restaurant' => 'restaurant',
			'lokanta'    => 'restaurant',
			'kafe'       => 'restaurant',
			'cafe'       => 'restaurant',
			'pastane'    => 'restaurant',
			'otel'       => 'konaklama',
			'pansiyon'   => 'konaklama',
			'spor'       => 'fitness',
			'pilates'    => 'fitness',
			'yoga'       => 'fitness',
			'güzellik'   => 'guzellik-kisisel-bakim',
			'kuaför'     => 'guzellik-kisisel-bakim',
			'berber'     => 'guzellik-kisisel-bakim',
			'veteriner'  => 'evcil-hayvan',
			'pet'        => 'evcil-hayvan',
			'mağaza'     => 'alisveris-perakende',
			'market'     => 'alisveris-perakende',
			'eğitim'     => 'egitim-kurslar',
			'kurs'       => 'egitim-kurslar',
			'sağlık'     => 'saglik',
			'klini'      => 'saglik',
			'hastane'    => 'saglik',
			'diş'        => 'saglik',
			'eczane'     => 'saglik',
			'otomotiv'   => 'otomotiv',
			'oto'        => 'otomotiv',
			'sinema'     => 'sinema',
			'tur'        => 'turizm-gezi',
		);
		$short = array( 'oto', 'pet', 'tur', 'diş' );
		foreach ( $aliases as $stem => $key ) {
			$pattern = in_array( $stem, $short, true )
				? '/(^|[\s\-&\/(])' . preg_quote( $stem, '/' ) . '($|[\s\-&\/),])/u'
				: '/(^|[\s\-&\/(])' . preg_quote( $stem, '/' ) . '/u';
			if ( preg_match( $pattern, $name ) ) {
				return $key;
			}
		}
		return self::GENERAL;
	}

	public static function for_business( IBP_Business $business ) {
		$key = self::key_for( $business );
		return array( 'key' => $key ) + self::profiles()[ $key ];
	}

	/**
	 * Sektörün operasyon menüsü: [ [ad, ikon], … ].
	 */
	public static function menu_items( array $profile ) {
		$modules = self::modules();
		$items   = array();
		foreach ( $profile['mods'] as $mod ) {
			if ( isset( $modules[ $mod ] ) ) {
				$items[ $mod ] = array( $profile['labels'][ $mod ] ?? $modules[ $mod ][1], $modules[ $mod ][0] );
			}
		}
		return $items;
	}
}
