<?php
/**
 * "Hızlı git": ikonlu kısayol kutuları.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Repeater;

class IBP_Widget_Shortcuts extends IBP_Widget_Base {

	public function get_name() {
		return 'ibp-shortcuts';
	}

	public function get_title() {
		return 'Hızlı Git';
	}

	public function get_icon() {
		return 'eicon-apps';
	}

	public static function default_items() {
		$item = function ( $label, $icon, $url, $badge = 'none' ) {
			return compact( 'label', 'icon', 'url', 'badge' );
		};
		return array(
			$item( 'Yorumlarım', 'star', '#ibp-reviews', 'reviews' ),
			$item( 'Favorilerim', 'heart', '#ibp-favorites', 'favorites' ),
			$item( 'Takip ettiklerim', 'bell', '#ibp-following', 'following' ),
			$item( 'Rezervasyon', 'calcheck', '#ibp-orders', 'pending' ),
		);
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'İçerik' ) );
		$this->add_control(
			'title',
			array(
				'label'   => 'Başlık',
				'type'    => Controls_Manager::TEXT,
				'default' => 'Hızlı git',
			)
		);
		$this->add_control(
			'subtitle',
			array(
				'label'   => 'Alt başlık',
				'type'    => Controls_Manager::TEXT,
				'default' => 'Sık kullandığın yerler',
			)
		);

		$repeater = new Repeater();
		$repeater->add_control( 'label', array( 'label' => 'Yazı', 'type' => Controls_Manager::TEXT, 'default' => 'Kısayol' ) );
		$repeater->add_control( 'icon', array( 'label' => 'İkon', 'type' => Controls_Manager::SELECT, 'default' => 'grid', 'options' => IBP_Icons::options() ) );
		$repeater->add_control( 'url', array( 'label' => 'Adres', 'type' => Controls_Manager::TEXT, 'default' => '#' ) );
		$repeater->add_control(
			'badge',
			array(
				'label'   => 'Rozet',
				'type'    => Controls_Manager::SELECT,
				'default' => 'none',
				'options' => array(
					'none'      => 'Yok',
					'reviews'   => 'Yorum sayısı',
					'favorites' => 'Favori sayısı',
					'following' => 'Takip sayısı',
					'pending'   => 'Bekleyen rezervasyon',
				),
			)
		);
		$this->add_control(
			'items',
			array(
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => self::default_items(),
				'title_field' => '{{{ label }}}',
			)
		);
		$this->end_controls_section();

		$this->add_accent_control();
	}

	protected function render() {
		$me       = IBP_User::current();
		$settings = $this->get_settings_for_display();
		$counts   = array();
		?>
		<section class="ibp ibp-card">
			<div class="ibp-card__head">
				<div>
					<h2 class="ibp-card__title"><?php echo esc_html( $settings['title'] ); ?></h2>
					<?php if ( '' !== $settings['subtitle'] ) : ?>
						<div class="ibp-card__sub"><?php echo esc_html( $settings['subtitle'] ); ?></div>
					<?php endif; ?>
				</div>
			</div>
			<div class="ibp-card__body ibp-sc">
				<?php foreach ( (array) $settings['items'] as $item ) : ?>
					<?php $n = $me ? $this->count( $me, $item['badge'] ?? 'none', $counts ) : 0; ?>
					<a class="ibp-sc__t" href="<?php echo esc_url( $item['url'] ); ?>">
						<span class="ibp-sc__ic">
							<?php echo IBP_Icons::svg( $item['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<?php if ( $n ) : ?>
								<span class="ibp-sc__n"><?php echo esc_html( $n > 99 ? '99+' : $n ); ?></span>
							<?php endif; ?>
						</span>
						<span class="ibp-sc__l"><?php echo esc_html( $item['label'] ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}

	private function count( IBP_User $me, $badge, array &$cache ) {
		if ( 'none' === $badge ) {
			return 0;
		}
		if ( ! isset( $cache[ $badge ] ) ) {
			switch ( $badge ) {
				case 'reviews':
					$cache[ $badge ] = $me->review_count();
					break;
				case 'favorites':
					$cache[ $badge ] = count( $me->favorites()['items'] );
					break;
				case 'following':
					$cache[ $badge ] = count( $me->following() );
					break;
				case 'pending':
					$cache[ $badge ] = $me->order_count( array( 'pending_payment', 'pending_approval' ) );
					break;
				default:
					$cache[ $badge ] = 0;
			}
		}
		return $cache[ $badge ];
	}
}
