<?php
/**
 * Plugin Name:       İşletmeBurada Panel
 * Description:       İşletme paneli tasarımını Voxel verisiyle çalışan Elementor widget'ları olarak ekler.
 * Version:           0.5.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            İşletmeBurada
 * Text Domain:       isletmeburada-panel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'IBP_VERSION', '0.5.0' );
define( 'IBP_FILE', __FILE__ );
define( 'IBP_DIR', plugin_dir_path( __FILE__ ) );
define( 'IBP_URL', plugin_dir_url( __FILE__ ) );

require_once IBP_DIR . 'includes/class-ibp-settings.php';
require_once IBP_DIR . 'includes/class-ibp-icons.php';
require_once IBP_DIR . 'includes/class-ibp-work-hours.php';
require_once IBP_DIR . 'includes/class-ibp-business.php';
require_once IBP_DIR . 'includes/class-ibp-sectors.php';
require_once IBP_DIR . 'includes/class-ibp-network.php';
require_once IBP_DIR . 'includes/class-ibp-user.php';
require_once IBP_DIR . 'includes/class-ibp-reviews.php';
require_once IBP_DIR . 'includes/class-ibp-tracker.php';
require_once IBP_DIR . 'includes/class-ibp-ranking.php';
require_once IBP_DIR . 'includes/class-ibp-page-builder.php';
require_once IBP_DIR . 'includes/class-ibp-debug.php';
require_once IBP_DIR . 'includes/class-ibp-admin.php';

register_activation_hook( __FILE__, array( 'IBP_Tracker', 'maybe_install' ) );
add_action( 'plugins_loaded', 'ibp_boot' );

function ibp_boot() {
	add_action( 'template_redirect', array( 'IBP_Business', 'handle_switch' ) );
	add_action( 'wp_enqueue_scripts', 'ibp_register_assets' );
	add_action( 'admin_init', array( 'IBP_Tracker', 'maybe_install' ) );
	add_action( 'save_post_' . IBP_Business::post_type(), array( 'IBP_Ranking', 'flush' ) );
	add_action( 'updated_post_meta', 'ibp_flush_ranking_on_reviews', 10, 3 );
	add_action( 'added_post_meta', 'ibp_flush_ranking_on_reviews', 10, 3 );

	IBP_Tracker::init();
	IBP_Network::init();
	IBP_Debug::init();
	if ( is_admin() ) {
		IBP_Admin::init();
	}

	if ( ! did_action( 'elementor/loaded' ) ) {
		add_action( 'admin_notices', 'ibp_notice_missing_elementor' );
		return;
	}

	add_action( 'elementor/elements/categories_registered', 'ibp_register_category' );
	add_action( 'elementor/widgets/register', 'ibp_register_widgets' );
	add_action( 'elementor/frontend/after_register_styles', 'ibp_register_assets' );
	add_action( 'elementor/frontend/after_register_scripts', 'ibp_register_assets' );
	add_action( 'elementor/preview/enqueue_styles', 'ibp_enqueue_assets' );
}

function ibp_register_assets() {
	wp_register_style( 'ibp-panel', IBP_URL . 'assets/panel.css', array(), IBP_VERSION );
	wp_register_script( 'ibp-panel', IBP_URL . 'assets/panel.js', array(), IBP_VERSION, true );
}

function ibp_enqueue_assets() {
	ibp_register_assets();
	wp_enqueue_style( 'ibp-panel' );
}

/**
 * Voxel bir işletmenin yorum istatistiğini güncelleyince sıralamayı yeniden hesaplat.
 */
function ibp_flush_ranking_on_reviews( $meta_id, $post_id, $meta_key ) {
	if ( 'voxel:review_stats' === $meta_key ) {
		IBP_Ranking::flush();
	}
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
		'brand'          => 'IBP_Widget_Brand',
		'business-card'  => 'IBP_Widget_Business_Card',
		'nav'            => 'IBP_Widget_Nav',
		'profile-status' => 'IBP_Widget_Profile_Status',
		'topbar'         => 'IBP_Widget_Topbar',
		'greeting'       => 'IBP_Widget_Greeting',
		'stat-card'      => 'IBP_Widget_Stat_Card',
		'chart'          => 'IBP_Widget_Chart',
		'ranking'        => 'IBP_Widget_Ranking',
		'todos'          => 'IBP_Widget_Todos',
		'reviews'        => 'IBP_Widget_Reviews',
		'completeness'   => 'IBP_Widget_Completeness',
		'funnel'         => 'IBP_Widget_Funnel',
		'network'        => 'IBP_Widget_Network',
		'user-card'      => 'IBP_Widget_User_Card',
		'user-stat'      => 'IBP_Widget_User_Stat',
		'user-list'      => 'IBP_Widget_User_List',
		'shortcuts'      => 'IBP_Widget_Shortcuts',
		'promo'          => 'IBP_Widget_Promo',
	);

	foreach ( $widgets as $file => $class ) {
		require_once IBP_DIR . 'includes/widgets/class-ibp-widget-' . $file . '.php';
		$widgets_manager->register( new $class() );
	}
}
