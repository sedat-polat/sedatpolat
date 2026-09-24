<?php
/**
 * Sol menünün tepesindeki logo: kırmızı konum ikonu + "İşletmeBurada".
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;

class IBP_Widget_Brand extends IBP_Widget_Base {

	public function get_name() {
		return 'ibp-brand';
	}

	public function get_title() {
		return 'Panel Logosu';
	}

	public function get_icon() {
		return 'eicon-site-logo';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'İçerik' ) );
		$this->add_control(
			'text',
			array(
				'label'   => 'Yazı',
				'type'    => Controls_Manager::TEXT,
				'default' => 'İşletmeBurada',
			)
		);
		$this->add_control(
			'image',
			array(
				'label'       => 'Logo görseli',
				'description' => 'Seçilirse ikon yerine bu görsel gösterilir.',
				'type'        => Controls_Manager::MEDIA,
				'default'     => array( 'url' => '' ),
			)
		);
		$this->add_control(
			'url',
			array(
				'label'   => 'Bağlantı',
				'type'    => Controls_Manager::TEXT,
				'default' => '/',
			)
		);
		$this->end_controls_section();

		$this->add_accent_control();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$image    = $settings['image']['url'] ?? '';
		?>
		<a class="ibp ibp-brand" href="<?php echo esc_url( $settings['url'] ?: home_url( '/' ) ); ?>">
			<?php if ( $image ) : ?>
				<img class="ibp-brand__img" src="<?php echo esc_url( $image ); ?>" alt="">
			<?php else : ?>
				<span class="ibp-brand__mark"><?php echo IBP_Icons::svg( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<?php endif; ?>
			<span class="ibp-brand__text"><?php echo esc_html( $settings['text'] ); ?></span>
		</a>
		<?php
	}
}
