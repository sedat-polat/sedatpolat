<?php
/**
 * Sol menünün altındaki kutu: yayın durumu, doluluk çubuğu, "Profili önizle".
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;

class IBP_Widget_Profile_Status extends IBP_Widget_Base {

	public function get_name() {
		return 'ibp-profile-status';
	}

	public function get_title() {
		return 'Profil Durumu';
	}

	public function get_icon() {
		return 'eicon-info-circle-o';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'İçerik' ) );
		$this->add_control(
			'link_text',
			array(
				'label'   => 'Bağlantı yazısı',
				'type'    => Controls_Manager::TEXT,
				'default' => 'Profili önizle',
			)
		);
		$this->end_controls_section();

		$this->add_accent_control();
	}

	protected function render() {
		$business = IBP_Business::current();
		if ( ! $business ) {
			return;
		}
		$settings          = $this->get_settings_for_display();
		list( $label, $dot ) = $business->status_label();
		$percent           = $business->completeness()['percent'];
		?>
		<div class="ibp ibp-card ibp-pstatus">
			<div class="ibp-pstatus__row">
				<span class="ibp-pstatus__dot" style="background: <?php echo esc_attr( $dot ); ?>"></span>
				<span class="ibp-pstatus__label"><?php echo esc_html( $label ); ?></span>
				<span class="ibp-pstatus__pct">%<?php echo esc_html( $percent ); ?> dolu</span>
			</div>
			<div class="ibp-bar ibp-bar--thin"><i style="width: <?php echo esc_attr( $percent ); ?>%"></i></div>
			<a class="ibp-pstatus__link" href="<?php echo esc_url( $business->permalink() ); ?>">
				<?php echo IBP_Icons::svg( 'eye', 'ibp-i ibp-i--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php echo esc_html( $settings['link_text'] ); ?>
			</a>
		</div>
		<?php
	}
}
