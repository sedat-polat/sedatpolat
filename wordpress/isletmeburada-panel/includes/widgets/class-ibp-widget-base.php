<?php
/**
 * Tüm İşletmeBurada widget'larının ortak temeli.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

abstract class IBP_Widget_Base extends Widget_Base {

	public function get_categories() {
		return array( 'isletmeburada' );
	}

	public function get_style_depends() {
		return array( 'ibp-panel' );
	}

	public function get_keywords() {
		return array( 'isletme', 'panel', 'voxel', 'isletmeburada' );
	}

	/**
	 * Her widget'ın Stil sekmesine ana renk seçimi ekler.
	 */
	protected function add_accent_control() {
		$this->start_controls_section( 'ibp_style', array( 'label' => 'Renkler', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control(
			'accent',
			array(
				'label'     => 'Ana renk',
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => array( '{{WRAPPER}} .ibp' => '--ibp-accent: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'card_bg',
			array(
				'label'     => 'Kart arka planı',
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => array( '{{WRAPPER}} .ibp' => '--ibp-card: {{VALUE}};' ),
			)
		);
		$this->end_controls_section();
	}

	/**
	 * İşletme yoksa gösterilecek kutu. Ziyaretçiye hiçbir şey göstermez.
	 */
	protected function render_empty() {
		if ( ! is_user_logged_in() ) {
			return;
		}
		echo '<div class="ibp ibp-card ibp-empty">Panelde gösterilecek bir işletmen yok.</div>';
	}
}
