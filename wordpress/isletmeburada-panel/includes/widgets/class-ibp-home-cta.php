<?php
/**
 * Ana sayfa: koyu çağrı bandı.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;

class IBP_Home_Cta extends IBP_Home_Widget_Base {

	public function get_name() {
		return 'ibp-home-cta';
	}

	public function get_title() {
		return 'Ana Sayfa: Çağrı Bandı';
	}

	public function get_icon() {
		return 'eicon-call-to-action';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'İçerik' ) );
		$this->add_control( 'title', array( 'label' => 'Başlık', 'description' => 'Yıldız içi vurgulanır.', 'type' => Controls_Manager::TEXT, 'default' => 'İşletmenizi bugün listeleyin, *doğru müşteriye* ulaşın.' ) );
		$this->add_control( 'text', array( 'label' => 'Metin', 'type' => Controls_Manager::TEXTAREA, 'default' => 'Kayıt iki dakika sürer, kredi kartı gerekmez.' ) );
		$this->add_control( 'button', array( 'label' => 'Ana düğme', 'type' => Controls_Manager::TEXT, 'default' => 'Ücretsiz başlayın' ) );
		$this->add_control( 'url', array( 'label' => 'Ana düğme adresi', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$this->add_control( 'button2', array( 'label' => 'İkinci düğme', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$this->add_control( 'url2', array( 'label' => 'İkinci düğme adresi', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$this->end_controls_section();

		$this->add_section_style_controls( '#1C1E21' );
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		?>
		<section class="ibp ibp-h ibp-h-cta ibp-reveal">
			<div class="ibp-h-container ibp-h-cta__inner">
				<div>
					<h2 class="ibp-h-title"><?php echo IBP_Home::title_html( $settings['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h2>
					<?php if ( '' !== $settings['text'] ) : ?>
						<p><?php echo esc_html( $settings['text'] ); ?></p>
					<?php endif; ?>
				</div>
				<div class="ibp-h-cta__actions">
					<?php if ( '' !== $settings['button'] && '' !== $settings['url'] ) : ?>
						<a class="ibp-h-btn ibp-h-btn--primary" href="<?php echo esc_url( $settings['url'] ); ?>"><?php echo esc_html( $settings['button'] ); ?><?php echo IBP_Icons::svg( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
					<?php endif; ?>
					<?php if ( '' !== $settings['button2'] && '' !== $settings['url2'] ) : ?>
						<a class="ibp-h-btn ibp-h-btn--ghost" href="<?php echo esc_url( $settings['url2'] ); ?>"><?php echo esc_html( $settings['button2'] ); ?></a>
					<?php endif; ?>
				</div>
			</div>
		</section>
		<?php
	}
}
