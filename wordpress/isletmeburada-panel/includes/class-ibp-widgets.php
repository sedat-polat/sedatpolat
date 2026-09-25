<?php
/**
 * Widget kaydı: hangi widget'ların olduğu, hangi grupta durduğu ve açık/kapalı olduğu.
 * Elementor kaydı da yönetimdeki "Widget'lar" ekranı da bu listeyi kullanır.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IBP_Widgets {

	const OPTION = 'ibp_disabled_widgets';
	const NONCE  = 'ibp_save_widgets';

	public static function groups() {
		return array(
			'panel'    => 'İşletme paneli',
			'personal' => 'Bireysel panel',
			'home'     => 'Ana sayfa',
		);
	}

	/**
	 * Elementor adı => dosya, sınıf, grup, başlık, açıklama, ikon.
	 */
	public static function all() {
		$w = function ( $file, $class, $group, $title, $desc, $icon ) {
			return compact( 'file', 'class', 'group', 'title', 'desc', 'icon' );
		};
		return array(
			'ibp-brand'             => $w( 'class-ibp-widget-brand', 'IBP_Widget_Brand', 'panel', 'Panel Logosu', 'Sol menünün tepesindeki logo ve site adı.', 'pin' ),
			'ibp-business-card'     => $w( 'class-ibp-widget-business-card', 'IBP_Widget_Business_Card', 'panel', 'İşletme Kartı', 'Logo, ad, şehir ve kategori; birden çok işletmede ve markada seçme kutusu.', 'store' ),
			'ibp-nav'               => $w( 'class-ibp-widget-nav', 'IBP_Widget_Nav', 'panel', 'Panel Menüsü', 'Gruplu sol menü; kategoriye göre operasyon modülleri, rozetler, "Yakında" öğeleri.', 'list' ),
			'ibp-profile-status'    => $w( 'class-ibp-widget-profile-status', 'IBP_Widget_Profile_Status', 'panel', 'Profil Durumu', 'Yayın durumu, doluluk çubuğu ve "Profili önizle".', 'eye' ),
			'ibp-topbar'            => $w( 'class-ibp-widget-topbar', 'IBP_Widget_Topbar', 'panel', 'Üst Bar', 'Sabit başlık çubuğu, ana düğme, bildirim zili, kullanıcı menüsü, menü daraltma.', 'sidebar' ),
			'ibp-greeting'          => $w( 'class-ibp-widget-greeting', 'IBP_Widget_Greeting', 'panel', 'Karşılama', 'Selamlama, tarih, bugünün çalışma saatleri ve 7/30/90 gün seçici.', 'home' ),
			'ibp-stat-card'         => $w( 'class-ibp-widget-stat-card', 'IBP_Widget_Stat_Card', 'panel', 'İstatistik Kartı', 'Görüntüleme, arama, yol tarifi, puan ya da yorum sayısı; önceki döneme göre değişim.', 'chart' ),
			'ibp-chart'             => $w( 'class-ibp-widget-chart', 'IBP_Widget_Chart', 'panel', 'Grafik', 'Seçilen verinin günlük çubuk grafiği.', 'chart' ),
			'ibp-ranking'           => $w( 'class-ibp-widget-ranking', 'IBP_Widget_Ranking', 'panel', 'Şehrin Sahipleri', 'İşletmenin şehir ve kategorideki sırası; markada bağlı işletmelerin sıralaması.', 'crown' ),
			'ibp-todos'             => $w( 'class-ibp-widget-todos', 'IBP_Widget_Todos', 'panel', 'Bekleyen İşler', 'Onay durumu, bağlanma istekleri, yanıt bekleyen yorumlar, eksik alanlar.', 'check' ),
			'ibp-reviews'           => $w( 'class-ibp-widget-reviews', 'IBP_Widget_Reviews', 'panel', 'Son Yorumlar', 'İşletmeye (ya da markaya) gelen son Voxel yorumları.', 'star' ),
			'ibp-completeness'      => $w( 'class-ibp-widget-completeness', 'IBP_Widget_Completeness', 'panel', 'Profil Doluluğu', '13 alandan kaçının dolu olduğu ve eksikler.', 'doc' ),
			'ibp-funnel'            => $w( 'class-ibp-widget-funnel', 'IBP_Widget_Funnel', 'panel', 'Etkileşim Dağılımı', 'Görüntülemeden telefon, yol tarifi, web sitesi ve e-postaya.', 'trend' ),
			'ibp-network'           => $w( 'class-ibp-widget-network', 'IBP_Widget_Network', 'panel', 'Bağlı İşletmeler', 'Şube, bayi ve franchise istekleri ve listesi; "markaya bağlan".', 'network' ),
			'ibp-user-card'         => $w( 'class-ibp-widget-user-card', 'IBP_Widget_User_Card', 'personal', 'Kullanıcı Kartı', 'Avatar, ad ve yerel rehber seviyesi.', 'person' ),
			'ibp-user-stat'         => $w( 'class-ibp-widget-user-stat', 'IBP_Widget_User_Stat', 'personal', 'Kişisel İstatistik', 'Yorum, favori, takip, rezervasyon sayısı ya da rehber puanı.', 'chart' ),
			'ibp-user-list'         => $w( 'class-ibp-widget-user-list', 'IBP_Widget_User_List', 'personal', 'Kişisel Liste', 'Yorumlarım, Favorilerim, Takip ettiklerim ya da Rezervasyonlarım.', 'heart' ),
			'ibp-shortcuts'         => $w( 'class-ibp-widget-shortcuts', 'IBP_Widget_Shortcuts', 'personal', 'Hızlı Git', 'İkonlu kısayol kutuları, sayı rozetleriyle.', 'grid' ),
			'ibp-promo'             => $w( 'class-ibp-widget-promo', 'IBP_Widget_Promo', 'personal', 'Tanıtım Kutusu', 'Koyu tanıtım kutusu; paketi olan rollerde gizlenir.', 'megaphone' ),
			'ibp-home-hero'         => $w( 'class-ibp-home-hero', 'IBP_Home_Hero', 'home', 'Arama', 'Başlık ve 3 adımlı arama: kategori → şehir → sonuçlar.', 'search' ),
			'ibp-home-crowns'       => $w( 'class-ibp-home-crowns', 'IBP_Home_Crowns', 'home', 'Şehrin Sahipleri', 'Kategorilerin taht sahipleri, rakip farkı ve Yarış Arenası.', 'crown' ),
			'ibp-home-ranking'      => $w( 'class-ibp-home-ranking', 'IBP_Home_Ranking', 'home', 'Kategori Sıralaması', 'En yüksek puanlı, en popüler ve yükselen işletmeler.', 'trend' ),
			'ibp-home-cities'       => $w( 'class-ibp-home-cities', 'IBP_Home_Cities', 'home', 'Şehirler', 'Şehir kartları, işletme sayıları ve tüm iller.', 'pin' ),
			'ibp-home-pricing'      => $w( 'class-ibp-home-pricing', 'IBP_Home_Pricing', 'home', 'İşletme Paketleri', 'İşletme sahiplerine tanıtım ve düzenlenebilir paketler.', 'card' ),
			'ibp-home-testimonials' => $w( 'class-ibp-home-testimonials', 'IBP_Home_Testimonials', 'home', 'Referanslar', 'Gerçek 4–5 yıldızlı yorumlar ya da elle yazılanlar.', 'star' ),
			'ibp-home-blog'         => $w( 'class-ibp-home-blog', 'IBP_Home_Blog', 'home', 'Blog', 'Son blog yazıları.', 'book' ),
			'ibp-home-cta'          => $w( 'class-ibp-home-cta', 'IBP_Home_Cta', 'home', 'Çağrı Bandı', 'Koyu çağrı bandı ve düğmeler.', 'megaphone' ),
		);
	}

	/**
	 * @return string[] Kapatılmış widget adları.
	 */
	public static function disabled() {
		$disabled = get_option( self::OPTION, array() );
		return is_array( $disabled ) ? array_values( array_intersect( $disabled, array_keys( self::all() ) ) ) : array();
	}

	public static function is_enabled( $name ) {
		return ! in_array( $name, self::disabled(), true );
	}

	/**
	 * Açık widget'ları Elementor'a kaydeder.
	 */
	public static function register( $widgets_manager ) {
		require_once IBP_DIR . 'includes/widgets/class-ibp-widget-base.php';
		require_once IBP_DIR . 'includes/widgets/class-ibp-home-widget-base.php';
		foreach ( self::all() as $name => $widget ) {
			if ( ! self::is_enabled( $name ) ) {
				continue;
			}
			require_once IBP_DIR . 'includes/widgets/' . $widget['file'] . '.php';
			$widgets_manager->register( new $widget['class']() );
		}
	}

	/**
	 * Her widget'ın kaç Elementor sayfasında kullanıldığı.
	 *
	 * @return array<string,array{count:int, pages:string[]}>
	 */
	public static function usage() {
		global $wpdb;
		$rows  = $wpdb->get_results( // phpcs:ignore WordPress.DB
			$wpdb->prepare(
				"SELECT p.ID, p.post_title, m.meta_value FROM {$wpdb->postmeta} m JOIN {$wpdb->posts} p ON p.ID = m.post_id WHERE m.meta_key = '_elementor_data' AND m.meta_value LIKE %s AND p.post_status IN ('publish','draft','private','pending') AND p.post_type NOT IN ('revision')",
				'%' . $wpdb->esc_like( '"widgetType":"ibp-' ) . '%'
			)
		);
		$usage = array();
		foreach ( (array) $rows as $row ) {
			if ( ! preg_match_all( '/"widgetType":"(ibp-[a-z-]+)"/', (string) $row->meta_value, $matches ) ) {
				continue;
			}
			foreach ( array_unique( $matches[1] ) as $name ) {
				$usage[ $name ]['count']   = ( $usage[ $name ]['count'] ?? 0 ) + 1;
				$usage[ $name ]['pages'][] = $row->post_title ?: '#' . $row->ID;
			}
		}
		return $usage;
	}

	/**
	 * Widget'lar ekranındaki formu kaydeder: gönderilmeyen anahtarlar kapalıdır.
	 */
	public static function save() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( self::NONCE ) ) {
			wp_die( 'Bu işlem için yetkin yok.' );
		}
		$enabled  = isset( $_POST['enabled'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['enabled'] ) ) : array();
		$disabled = array_values( array_diff( array_keys( self::all() ), $enabled ) );
		update_option( self::OPTION, $disabled, false );
		wp_safe_redirect( add_query_arg( array( 'page' => IBP_Admin::SLUG . '-widgets', 'ibp_msg' => 'widgets' ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
