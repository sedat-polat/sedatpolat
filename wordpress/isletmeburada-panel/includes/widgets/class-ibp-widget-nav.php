<?php
/**
 * Sol menü: gruplar, ikonlar, rozetler ve aktif sayfa vurgusu.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Repeater;

class IBP_Widget_Nav extends IBP_Widget_Base {

	public function get_name() {
		return 'ibp-nav';
	}

	public function get_title() {
		return 'Panel Menüsü';
	}

	public function get_icon() {
		return 'eicon-nav-menu';
	}

	/**
	 * Prototipteki menü. "soon" işaretli olanların verisi henüz sitede yok.
	 *
	 * @param array $urls Anahtar => adres (panel sayfaları oluşturulunca doldurulur).
	 */
	public static function default_items( array $urls = array() ) {
		$item = function ( $label, $icon, $url = '#', $soon = '', $badge = 'none' ) {
			return array( 'kind' => 'link', 'label' => $label, 'icon' => $icon, 'url' => $url, 'soon' => $soon, 'badge' => $badge, 'badge_text' => '' );
		};
		$head = function ( $label ) {
			return array( 'kind' => 'heading', 'label' => $label, 'icon' => 'grid', 'url' => '', 'soon' => '', 'badge' => 'none', 'badge_text' => '' );
		};
		$sector = array( 'kind' => 'sector', 'label' => 'Sektör menüsü', 'icon' => 'grid', 'url' => '', 'soon' => '', 'badge' => 'none', 'badge_text' => '' );

		return array(
			$item( 'Genel bakış', 'grid', $urls['overview'] ?? '#', '', 'todos' ),
			$item( 'İstatistikler', 'chart', $urls['stats'] ?? '#' ),
			$sector,
			$head( 'Profil' ),
			$item( 'İşletme bilgileri', 'store', '{edit}', '', 'missing' ),
			$item( '{menu}', 'list', '#', 'yes' ),
			$head( 'Müşteriler' ),
			$item( 'Yorumlar', 'star', '{view}', '', 'unanswered' ),
			$item( 'Şikâyetler', 'alert', '#', 'yes' ),
			$head( 'Yayınlar' ),
			$item( 'Fırsatlar', 'tag', '#', 'yes' ),
			$item( 'Duyurular', 'megaphone', '#', 'yes' ),
			$item( 'Kuponlar', 'ticket', '#', 'yes' ),
			$item( '{events}', 'calendar', '#', 'yes' ),
			$item( 'İş ilanları', 'briefcase', '#', 'yes' ),
			$head( 'Marka' ),
			$item( 'Bağlı işletmeler', 'network', $urls['network'] ?? '#', '', 'requests' ),
			$head( 'Hesap' ),
			$item( 'Bireysel panelim', 'person', '{personal_panel}' ),
			$item( 'Paket ve fatura', 'card', '#', 'yes' ),
			$item( 'Ayarlar', 'sliders', '#', 'yes' ),
		);
	}

	/**
	 * Bireysel panelin menüsü (prototipteki "Bireysel panel").
	 *
	 * @param array $urls personal => Hesabım sayfası adresi.
	 */
	public static function personal_items( array $urls = array() ) {
		$base = $urls['personal'] ?? '';
		$item = function ( $label, $icon, $url = '#', $soon = '' ) {
			return array( 'kind' => 'link', 'label' => $label, 'icon' => $icon, 'url' => $url, 'soon' => $soon, 'badge' => 'none', 'badge_text' => '' );
		};
		$head = function ( $label ) {
			return array( 'kind' => 'heading', 'label' => $label, 'icon' => 'grid', 'url' => '', 'soon' => '', 'badge' => 'none', 'badge_text' => '' );
		};
		return array(
			$item( 'Ana sayfa', 'home', $base ?: '#' ),
			$head( 'Planlarım' ),
			$item( 'Rezervasyon ve randevular', 'calcheck', $base . '#ibp-orders' ),
			$item( 'Biletlerim', 'ticket', '#', 'yes' ),
			$item( 'Etkinlik takvimim', 'calendar', '#', 'yes' ),
			$item( 'Üyelik ve paketler', 'card', '#', 'yes' ),
			$head( 'Kariyer' ),
			$item( 'İş başvurularım', 'briefcase', '#', 'yes' ),
			$item( 'Özgeçmişim', 'doc', '#', 'yes' ),
			$item( 'Takip ve iş alarmları', 'bell', $base . '#ibp-following' ),
			$head( 'Fırsatlar' ),
			$item( 'Kuponlarım', 'tag', '#', 'yes' ),
			$item( 'Favorilerim', 'heart', $base . '#ibp-favorites' ),
			$head( 'İlanlarım' ),
			$item( 'Etkinliklerim', 'gift', '#', 'yes' ),
			$item( 'Teklif taleplerim', 'quote', '#', 'yes' ),
			$head( 'Katkılarım' ),
			$item( 'Yorumlarım', 'star', $base . '#ibp-reviews' ),
			$item( 'Şikâyetlerim', 'alert', '#', 'yes' ),
			$head( 'Hesap' ),
			$item( 'İşletme panelim', 'store', '{business_panel}' ),
			$item( 'Aboneliğim', 'crown', '#', 'yes' ),
			$item( 'Ayarlar', 'sliders', '#', 'yes' ),
		);
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'Menü' ) );

		$repeater = new Repeater();
		$repeater->add_control(
			'kind',
			array(
				'label'   => 'Tür',
				'type'    => Controls_Manager::SELECT,
				'default' => 'link',
				'options' => array(
					'link'    => 'Bağlantı',
					'heading' => 'Grup başlığı',
					'sector'  => 'Sektör menüsü (kategoriye göre)',
				),
			)
		);
		$repeater->add_control(
			'label',
			array(
				'label'       => 'Yazı',
				'description' => '{menu} sektöre göre "Menü ve fiyatlar / Hizmetler ve fiyatlar…", {events} "Etkinlikler / Seanslar ve biletler…" olur.',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Yeni bağlantı',
			)
		);
		$repeater->add_control(
			'icon',
			array(
				'label'     => 'İkon',
				'type'      => Controls_Manager::SELECT,
				'default'   => 'grid',
				'options'   => IBP_Icons::options(),
				'condition' => array( 'kind' => 'link' ),
			)
		);
		$repeater->add_control(
			'url',
			array(
				'label'       => 'Adres',
				'description' => 'Kısayollar: {edit} işletmeyi düzenle, {view} işletme sayfası, {id} işletme ID\'si, {business_panel} işletme paneli (işletmesi olmayana gizlenir), {personal_panel} bireysel panel.',
				'type'        => Controls_Manager::TEXT,
				'default'     => '#',
				'condition'   => array( 'kind' => 'link' ),
			)
		);
		$repeater->add_control(
			'soon',
			array(
				'label'       => 'Yakında',
				'description' => 'Açıkken bağlantı soluk görünür ve tıklanmaz.',
				'type'        => Controls_Manager::SWITCHER,
				'default'     => '',
				'condition'   => array( 'kind' => 'link' ),
			)
		);
		$repeater->add_control(
			'badge',
			array(
				'label'     => 'Rozet',
				'type'      => Controls_Manager::SELECT,
				'default'   => 'none',
				'options'   => array(
					'none'       => 'Yok',
					'unanswered' => 'Yanıt bekleyen yorum sayısı',
					'todos'      => 'Bekleyen iş sayısı',
					'requests'   => 'Bağlanma isteği sayısı',
					'missing'    => 'Eksik profil alanı sayısı',
					'manual'     => 'Elle yazılan',
				),
				'condition' => array( 'kind' => 'link' ),
			)
		);
		$repeater->add_control(
			'badge_text',
			array(
				'label'     => 'Rozet yazısı',
				'type'      => Controls_Manager::TEXT,
				'default'   => '',
				'condition' => array(
					'kind'  => 'link',
					'badge' => 'manual',
				),
			)
		);

		$this->add_control(
			'items',
			array(
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => self::default_items(),
				'title_field' => '{{{ kind === "heading" ? "— " + label : label }}}',
			)
		);
		$this->add_control(
			'reviews_url',
			array(
				'label'       => 'Yorumlar sayfası (bekleyen işler için)',
				'type'        => Controls_Manager::TEXT,
				'default'     => '{view}',
				'separator'   => 'before',
			)
		);
		$this->end_controls_section();

		$this->add_accent_control();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$business = IBP_Business::current();
		$profile  = $business ? IBP_Sectors::for_business( $business ) : IBP_Sectors::profiles()[ IBP_Sectors::GENERAL ];
		$items    = $this->expand( self::migrate( (array) $settings['items'] ), $profile );
		$current  = self::path( add_query_arg( array() ) ); // İstek adresinin yolu.
		$badges   = array();
		?>
		<nav class="ibp ibp-nav" aria-label="Panel menüsü">
			<?php foreach ( $items as $item ) : ?>
				<?php if ( 'heading' === $item['kind'] ) : ?>
					<div class="ibp-nav__group"><?php echo esc_html( $item['label'] ); ?></div>
					<?php continue; ?>
				<?php endif; ?>
				<?php
				$soon  = 'yes' === $item['soon'];
				$url   = IBP_Page_Builder::resolve_panel_url( (string) $item['url'] );
				if ( null === $url ) {
					continue; // {business_panel}: kullanıcının işletmesi yok.
				}
				$url   = $business ? $business->resolve_url( $url ) : $url;
				$on    = ! $soon && '#' !== $url && '' !== $url && self::path( $url ) === $current;
				$badge = $this->badge( $item, $business, $settings, $badges );
				$class = 'ibp-nav__item' . ( $on ? ' is-on' : '' ) . ( $soon ? ' is-soon' : '' );
				?>
				<?php if ( $soon ) : ?>
					<span class="<?php echo esc_attr( $class ); ?>" aria-disabled="true">
				<?php else : ?>
					<a class="<?php echo esc_attr( $class ); ?>" href="<?php echo esc_url( $url ); ?>"<?php echo $on ? ' aria-current="page"' : ''; ?>>
				<?php endif; ?>
					<?php echo IBP_Icons::svg( $item['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<span class="ibp-nav__label"><?php echo esc_html( $item['label'] ); ?></span>
					<?php if ( $soon ) : ?>
						<span class="ibp-nav__soon">Yakında</span>
					<?php elseif ( '' !== $badge ) : ?>
						<span class="ibp-nav__badge"><?php echo esc_html( $badge ); ?></span>
					<?php endif; ?>
				<?php echo $soon ? '</span>' : '</a>'; ?>
			<?php endforeach; ?>
		</nav>
		<?php
	}

	/**
	 * Eski sürümlerin sabit "Operasyon / Rezervasyonlar / Masa planı" öğelerini
	 * sektör menüsüne çevirir; böylece önceden oluşturulmuş sayfalar da sektöre uyar.
	 */
	private static function migrate( array $items ) {
		if ( in_array( 'sector', wp_list_pluck( $items, 'kind' ), true ) ) {
			return $items;
		}
		$out = array();
		for ( $i = 0, $n = count( $items ); $i < $n; $i++ ) {
			$item = $items[ $i ];
			if ( 'heading' === $item['kind'] && 'Operasyon' === $item['label']
				&& 'Rezervasyonlar' === ( $items[ $i + 1 ]['label'] ?? '' ) && 'Masa planı' === ( $items[ $i + 2 ]['label'] ?? '' ) ) {
				$out[] = array( 'kind' => 'sector', 'label' => '', 'icon' => 'grid', 'url' => '', 'soon' => '', 'badge' => 'none', 'badge_text' => '' );
				$i    += 2;
				continue;
			}
			if ( 'Menü ve fiyatlar' === $item['label'] ) {
				$item['label'] = '{menu}';
			} elseif ( 'Etkinlikler' === $item['label'] ) {
				$item['label'] = '{events}';
			}
			$out[] = $item;
		}
		return $out;
	}

	/**
	 * Sektör öğesini başlık + modüllere açar, {menu}/{events} yazılarını doldurur.
	 */
	private function expand( array $items, array $profile ) {
		$urls = (array) IBP_Settings::get( 'module_urls' );
		$out  = array();
		foreach ( $items as $item ) {
			$item += array( 'icon' => 'grid', 'url' => '', 'soon' => '', 'badge' => 'none', 'badge_text' => '' );
			if ( 'sector' === $item['kind'] ) {
				$modules = IBP_Sectors::menu_items( $profile );
				if ( $modules ) {
					$out[] = array( 'kind' => 'heading', 'label' => $profile['ops'] ) + $item;
				}
				foreach ( $modules as $key => $module ) {
					$url   = $urls[ $key ] ?? '';
					$out[] = array(
						'kind'  => 'link',
						'label' => $module[0],
						'icon'  => $module[1],
						'url'   => '' !== $url ? $url : '#',
						'soon'  => '' !== $url ? '' : 'yes',
					) + $item;
				}
				continue;
			}
			$item['label'] = strtr( (string) $item['label'], array( '{menu}' => $profile['menu'], '{events}' => $profile['events'] ) );
			$out[]         = $item;
		}
		return $out;
	}

	private function badge( array $item, $business, array $settings, array &$cache ) {
		$type = $item['badge'] ?? 'none';
		if ( 'manual' === $type ) {
			return (string) $item['badge_text'];
		}
		if ( 'none' === $type || ! $business ) {
			return '';
		}
		if ( ! isset( $cache[ $type ] ) ) {
			switch ( $type ) {
				case 'unanswered':
					$cache[ $type ] = (int) IBP_Reviews::unanswered( $business );
					break;
				case 'todos':
					$cache[ $type ] = count( IBP_Business::scope_todos( $settings['reviews_url'] ) );
					break;
				case 'requests':
					$cache[ $type ] = count( IBP_Network::children( $business->id, 'pending' ) );
					break;
				case 'missing':
					$cache[ $type ] = count( $business->completeness()['missing'] );
					break;
				default:
					$cache[ $type ] = 0;
			}
		}
		return $cache[ $type ] > 0 ? (string) $cache[ $type ] : '';
	}

	private static function path( $url ) {
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		return untrailingslashit( $path ) ?: '/';
	}
}
