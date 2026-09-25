<?php
/**
 * Ana sayfa: öne çıkan şehir kartları ve "Tüm 81 ili göster".
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;

class IBP_Home_Cities extends IBP_Home_Widget_Base {

	/** Prototipteki şehir renkleri; diğer şehirler sırayla bunları kullanır. */
	const COLORS = array(
		'istanbul'  => array( '#8A4A2C', '#5C2E1A' ),
		'ankara'    => array( '#2C4A6E', '#1A2B45' ),
		'izmir'     => array( '#1E5F6E', '#0E3B45' ),
		'bursa'     => array( '#3E5535', '#223018' ),
		'antalya'   => array( '#A87842', '#6E4C24' ),
		'adana'     => array( '#4A3D6E', '#2A2145' ),
		'konya'     => array( '#2F5E52', '#1A3D34' ),
		'gaziantep' => array( '#6E2F3E', '#451A25' ),
	);

	public function get_name() {
		return 'ibp-home-cities';
	}

	public function get_title() {
		return 'Ana Sayfa: Şehirler';
	}

	public function get_icon() {
		return 'eicon-map-pin';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'İçerik' ) );
		$this->add_control( 'eyebrow', array( 'label' => 'Üst yazı', 'type' => Controls_Manager::TEXT, 'default' => 'Şehirler' ) );
		$this->add_control( 'title', array( 'label' => 'Başlık', 'type' => Controls_Manager::TEXT, 'default' => 'Şehrinize göre keşfedin.' ) );
		$this->add_control( 'sub', array( 'label' => 'Alt yazı', 'type' => Controls_Manager::TEXT, 'default' => 'Bir şehre tıkla; o şehrin sıralamaları ve işletmeleri tek sayfada.' ) );
		$this->add_control( 'featured', array( 'label' => 'Öne çıkan şehirler', 'description' => 'Virgülle ayır.', 'type' => Controls_Manager::TEXT, 'default' => 'İstanbul, Ankara, İzmir, Bursa, Antalya, Adana, Konya, Gaziantep' ) );
		$this->add_control(
			'link',
			array(
				'label'   => 'Şehre tıklayınca',
				'type'    => Controls_Manager::SELECT,
				'default' => 'home',
				'options' => array(
					'home'   => 'Ana sayfa o şehre göre açılsın',
					'search' => 'Arama sayfası o şehirle açılsın',
				),
			)
		);
		$this->add_control( 'show_all', array( 'label' => '"Tüm illeri göster" listesi', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->end_controls_section();

		$this->add_section_style_controls( '#F7F8FA' );
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$all      = IBP_City::all();
		$counts   = IBP_City::counts();
		$featured = array();
		foreach ( array_map( 'trim', explode( ',', (string) $settings['featured'] ) ) as $name ) {
			$slug = IBP_City::slug( $name );
			if ( isset( $all[ $slug ] ) ) {
				$featured[ $slug ] = $all[ $slug ];
			}
		}
		$palette = array_values( self::COLORS );
		$url     = function ( $slug ) use ( $settings ) {
			return 'search' === $settings['link'] ? IBP_Home::search_url( '', $slug ) : IBP_City::url( $slug );
		};
		?>
		<section class="ibp ibp-h ibp-h-cities ibp-reveal">
			<div class="ibp-h-container">
				<?php IBP_Home::head( $settings['eyebrow'], $settings['title'], $settings['sub'], '', true ); ?>
				<div class="ibp-h-city-grid">
					<?php $i = 0; ?>
					<?php foreach ( $featured as $slug => $name ) : ?>
						<?php $color = self::COLORS[ $slug ] ?? $palette[ $i % count( $palette ) ]; ?>
						<a class="ibp-h-city-card" href="<?php echo esc_url( $url( $slug ) ); ?>" style="background: linear-gradient(160deg, <?php echo esc_attr( $color[0] ); ?>, <?php echo esc_attr( $color[1] ); ?>)">
							<span class="ibp-h-city-card__name"><?php echo esc_html( $name ); ?></span>
							<span class="ibp-h-city-card__count"><?php echo esc_html( ! empty( $counts[ $slug ] ) ? number_format_i18n( $counts[ $slug ] ) . ' işletme' : 'Keşfet' ); ?></span>
						</a>
						<?php $i++; ?>
					<?php endforeach; ?>
				</div>
				<?php if ( 'yes' === $settings['show_all'] ) : ?>
					<details class="ibp-h-allcities">
						<summary><?php echo esc_html( sprintf( 'Tüm %d ili göster', count( $all ) ) ); ?><?php echo IBP_Icons::svg( 'chevron' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></summary>
						<div class="ibp-h-pills">
							<?php foreach ( $all as $slug => $name ) : ?>
								<a class="ibp-h-pill<?php echo ! empty( $counts[ $slug ] ) ? ' is-active' : ''; ?>" href="<?php echo esc_url( $url( $slug ) ); ?>"><?php echo esc_html( $name ); ?></a>
							<?php endforeach; ?>
						</div>
					</details>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}
}
