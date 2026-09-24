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

		return array(
			$item( 'Genel bakış', 'grid', $urls['overview'] ?? '#', '', 'todos' ),
			$item( 'İstatistikler', 'chart', $urls['stats'] ?? '#' ),
			$head( 'Operasyon' ),
			$item( 'Rezervasyonlar', 'calcheck', '#', 'yes' ),
			$item( 'Masa planı', 'tables', '#', 'yes' ),
			$head( 'Profil' ),
			$item( 'İşletme bilgileri', 'store', '{edit}', '', 'missing' ),
			$item( 'Menü ve fiyatlar', 'list', '#', 'yes' ),
			$head( 'Müşteriler' ),
			$item( 'Yorumlar', 'star', '{view}', '', 'unanswered' ),
			$item( 'Şikâyetler', 'alert', '#', 'yes' ),
			$head( 'Yayınlar' ),
			$item( 'Fırsatlar', 'tag', '#', 'yes' ),
			$item( 'Duyurular', 'megaphone', '#', 'yes' ),
			$item( 'Kuponlar', 'ticket', '#', 'yes' ),
			$item( 'Etkinlikler', 'calendar', '#', 'yes' ),
			$item( 'İş ilanları', 'briefcase', '#', 'yes' ),
			$head( 'Hesap' ),
			$item( 'Paket ve fatura', 'card', '#', 'yes' ),
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
				),
			)
		);
		$repeater->add_control(
			'label',
			array(
				'label'   => 'Yazı',
				'type'    => Controls_Manager::TEXT,
				'default' => 'Yeni bağlantı',
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
				'description' => 'Kısayollar: {edit} işletmeyi düzenle, {view} işletme sayfası, {id} işletme ID\'si.',
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
		$current  = self::path( add_query_arg( array() ) ); // İstek adresinin yolu.
		$badges   = array();
		?>
		<nav class="ibp ibp-nav" aria-label="Panel menüsü">
			<?php foreach ( (array) $settings['items'] as $item ) : ?>
				<?php if ( 'heading' === $item['kind'] ) : ?>
					<div class="ibp-nav__group"><?php echo esc_html( $item['label'] ); ?></div>
					<?php continue; ?>
				<?php endif; ?>
				<?php
				$soon  = 'yes' === $item['soon'];
				$url   = $business ? $business->resolve_url( $item['url'] ) : $item['url'];
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
					$cache[ $type ] = count( $business->todos( $settings['reviews_url'] ) );
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
