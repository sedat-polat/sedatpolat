<?php
/**
 * Ana sayfa widget'larının ortak temeli: ayrı Elementor kategorisi ve ana sayfa stilleri.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;

abstract class IBP_Home_Widget_Base extends IBP_Widget_Base {

	public function get_categories() {
		return array( 'isletmeburada-home' );
	}

	public function get_style_depends() {
		return array( 'ibp-home' );
	}

	public function get_script_depends() {
		return array( 'ibp-home' );
	}

	/**
	 * Bölümün arka planı ve üst/alt boşluğu.
	 */
	protected function add_section_style_controls( $default_bg = '' ) {
		$this->start_controls_section( 'ibp_section', array( 'label' => 'Bölüm', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control(
			'section_bg',
			array(
				'label'     => 'Arka plan',
				'type'      => Controls_Manager::COLOR,
				'default'   => $default_bg,
				'selectors' => array( '{{WRAPPER}} .ibp-h' => 'background: {{VALUE}};' ),
			)
		);
		$this->add_responsive_control(
			'section_pad',
			array(
				'label'      => 'Üst ve alt boşluk',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 200 ) ),
				'selectors'  => array( '{{WRAPPER}} .ibp-h' => 'padding-top: {{SIZE}}{{UNIT}}; padding-bottom: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->end_controls_section();
	}
}
