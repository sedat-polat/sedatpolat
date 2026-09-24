<?php
/**
 * Plugin Name:       İşletmeBurada Panel
 * Description:       İşletme paneli tasarımını Voxel verisiyle çalışan Elementor widget'ları olarak ekler.
 * Version:           0.1.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            İşletmeBurada
 * Text Domain:       isletmeburada-panel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'IBP_VERSION', '0.1.0' );
define( 'IBP_FILE', __FILE__ );
define( 'IBP_DIR', plugin_dir_path( __FILE__ ) );
define( 'IBP_URL', plugin_dir_url( __FILE__ ) );

require_once IBP_DIR . 'includes/class-ibp-work-hours.php';
require_once IBP_DIR . 'includes/class-ibp-business.php';

add_action( 'plugins_loaded', 'ibp_boot' );

function ibp_boot() {
	add_action( 'template_redirect', array( 'IBP_Business', 'handle_switch' ) );
	add_action( 'wp_enqueue_scripts', 'ibp_register_assets' );

	if ( ! did_action( 'elementor/loaded' ) ) {
		add_action( 'admin_notices', 'ibp_notice_missing_elementor' );
		return;
	}

	add_action( 'elementor/elements/categories_registered', 'ibp_register_category' );
	add_action( 'elementor/widgets/register', 'ibp_register_widgets' );
	add_action( 'elementor/frontend/after_register_styles', 'ibp_register_assets' );
	add_action( 'elementor/preview/enqueue_styles', 'ibp_enqueue_assets' );
}

function ibp_register_assets() {
	wp_register_style( 'ibp-panel', IBP_URL . 'assets/panel.css', array(), IBP_VERSION );
}

function ibp_enqueue_assets() {
	ibp_register_assets();
	wp_enqueue_style( 'ibp-panel' );
}

function ibp_notice_missing_elementor() {
	echo '<div class="notice notice-warning"><p><strong>İşletmeBurada Panel</strong> çalışmak için Elementor eklentisine ihtiyaç duyar.</p></div>';
}

function ibp_register_category( $elements_manager ) {
	$elements_manager->add_category(
		'isletmeburada',
		array(
			'title' => 'İşletmeBurada',
			'icon'  => 'fa fa-store',
		)
	);
}

function ibp_register_widgets( $widgets_manager ) {
	require_once IBP_DIR . 'includes/widgets/class-ibp-widget-base.php';

	$widgets = array(
		'business-card' => 'IBP_Widget_Business_Card',
		'greeting'      => 'IBP_Widget_Greeting',
		'completeness'  => 'IBP_Widget_Completeness',
		'stat-card'     => 'IBP_Widget_Stat_Card',
	);

	foreach ( $widgets as $file => $class ) {
		require_once IBP_DIR . 'includes/widgets/class-ibp-widget-' . $file . '.php';
		$widgets_manager->register( new $class() );
	}
}
