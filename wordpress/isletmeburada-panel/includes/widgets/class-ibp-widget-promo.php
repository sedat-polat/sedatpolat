<?php
/**
 * Koyu tanıtım kutusu (ör. "Bireysel Plus – 7 gün ücretsiz dene").
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;

class IBP_Widget_Promo extends IBP_Widget_Base {

	public function get_name() {
		return 'ibp-promo';
	}

	public function get_title() {
		return 'Tanıtım Kutusu';
	}

	public function get_icon() {
		return 'eicon-call-to-action';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'İçerik' ) );
		$this->add_control( 'title', array( 'label' => 'Başlık', 'type' => Controls_Manager::TEXT, 'default' => 'Bireysel Plus' ) );
		$this->add_control( 'text', array( 'label' => 'Metin', 'type' => Controls_Manager::TEXTAREA, 'default' => 'Öne çıkan yorumlar, özel kuponlar ve daha fazlası.' ) );
		$this->add_control( 'button', array( 'label' => 'Düğme yazısı', 'type' => Controls_Manager::TEXT, 'default' => '7 gün ücretsiz dene' ) );
		$this->add_control( 'url', array( 'label' => 'Düğme adresi', 'description' => 'Ör. Voxel üyelik paketleri sayfası.', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$this->add_control(
			'hide_roles',
			array(
				'label'       => 'Bu rollerde gizle',
				'description' => 'Virgülle ayır, ör. plus_uye. Paketi olan kullanıcılar kutuyu görmez.',
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
			)
		);
		$this->end_controls_section();

		$this->add_accent_control();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$user     = wp_get_current_user();
		$roles    = array_filter( array_map( 'trim', explode( ',', (string) $settings['hide_roles'] ) ) );
		if ( $roles && $user->exists() && array_intersect( $roles, (array) $user->roles ) ) {
			return;
		}
		?>
		<div class="ibp ibp-promo">
			<div class="ibp-promo__title"><?php echo esc_html( $settings['title'] ); ?></div>
			<?php if ( '' !== $settings['text'] ) : ?>
				<div class="ibp-promo__text"><?php echo esc_html( $settings['text'] ); ?></div>
			<?php endif; ?>
			<?php if ( '' !== $settings['button'] && '' !== $settings['url'] ) : ?>
				<a class="ibp-btn ibp-btn--sm ibp-btn--primary" href="<?php echo esc_url( $settings['url'] ); ?>"><?php echo esc_html( $settings['button'] ); ?></a>
			<?php endif; ?>
		</div>
		<?php
	}
}
