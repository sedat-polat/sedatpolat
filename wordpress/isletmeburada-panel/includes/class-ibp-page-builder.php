<?php
/**
 * Panel sayfalarını (Genel bakış, İstatistikler) widget'ları yerleşmiş hâlde oluşturur.
 *
 * Sayfalar Elementor Tuval (Canvas) şablonuyla açılır; sol menü "ibp-sidebar" sınıflı
 * container'dır ve tablet/mobilde çekmeceye dönüşür.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IBP_Page_Builder {

	const OPTION = 'ibp_pages';

	/**
	 * Panel sayfalarını oluşturur; daha önce oluşturulmuşsa aynı sayfaları günceller.
	 *
	 * @return array{overview:int, stats:int, network:int} Sayfa ID'leri.
	 */
	public static function create() {
		$pages    = self::existing();
		$overview = $pages['overview'] ?? self::insert_page( 'İşletme Paneli', 'isletme-paneli', 0 );
		$stats    = $pages['stats'] ?? self::insert_page( 'İstatistikler', 'istatistikler', $overview );
		$network  = $pages['network'] ?? self::insert_page( 'Bağlı İşletmeler', 'bagli-isletmeler', $overview );

		$urls = array(
			'overview' => get_permalink( $overview ),
			'stats'    => get_permalink( $stats ),
			'network'  => get_permalink( $network ),
		);

		self::save_elementor( $overview, self::shell( $urls, 'Genel bakış', 'İşletmenin bu dönemki durumu ve bekleyen işler.', self::overview_content( $urls ) ) );
		self::save_elementor( $stats, self::shell( $urls, 'İstatistikler', 'Profilini kaç kişinin görüp iletişime geçtiği.', self::stats_content() ) );
		self::save_elementor( $network, self::shell( $urls, 'Bağlı işletmeler', 'Şubeler, bayiler ve franchise\'lar.', array( self::widget( 'ibp-network' ) ) ) );

		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}

		$pages = array( 'overview' => $overview, 'stats' => $stats, 'network' => $network );
		update_option( self::OPTION, $pages );
		return $pages;
	}

	public static function existing() {
		$pages = get_option( self::OPTION, array() );
		foreach ( (array) $pages as $key => $id ) {
			if ( ! get_post( $id ) || 'trash' === get_post_status( $id ) ) {
				unset( $pages[ $key ] );
			}
		}
		return $pages;
	}

	private static function insert_page( $title, $slug, $parent ) {
		return (int) wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => $title,
				'post_name'   => $slug,
				'post_parent' => $parent,
			)
		);
	}

	private static function save_elementor( $post_id, array $elements ) {
		update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $post_id, '_elementor_template_type', 'wp-page' );
		update_post_meta( $post_id, '_wp_page_template', 'elementor_canvas' );
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			update_post_meta( $post_id, '_elementor_version', ELEMENTOR_VERSION );
		}
		update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $elements ) ) );
	}

	/* ---------------------------------------------------------------------
	 * Düzen
	 * ------------------------------------------------------------------ */

	private static function shell( array $urls, $title, $subtitle, array $content ) {
		$sidebar = self::container(
			array(
				'css_classes'           => 'ibp-sidebar',
				'flex_direction'        => 'column',
				'width'                 => array( 'unit' => 'px', 'size' => 256 ),
				'padding'               => self::box( 18, 12, 12, 12 ),
				'flex_gap'              => self::gap( 14 ),
				'background_background' => 'classic',
				'background_color'      => '#FBFBFC',
				'border_border'         => 'solid',
				'border_width'          => self::box( 0, 1, 0, 0 ),
				'border_color'          => '#EEF0F2',
				'_flex_size'            => 'none',
			),
			array(
				self::widget( 'ibp-brand' ),
				self::widget( 'ibp-business-card' ),
				self::widget( 'ibp-nav', array( 'items' => IBP_Widget_Nav::default_items( $urls ) ), array( '_flex_size' => 'grow' ) ),
				self::widget( 'ibp-profile-status' ),
			)
		);

		$main = self::container(
			array(
				'flex_direction' => 'column',
				'padding'        => self::box( 0, 0, 0, 0 ),
				'flex_gap'       => self::gap( 0 ),
				'_flex_size'     => 'grow',
				'min_width'      => array( 'unit' => 'px', 'size' => 0 ),
			),
			array(
				self::widget( 'ibp-topbar', array( 'title' => $title, 'subtitle' => $subtitle ) ),
				self::container(
					array(
						'flex_direction' => 'column',
						'padding'        => self::box( 28, 32, 40, 32 ),
						'padding_tablet' => self::box( 24, 20, 32, 20 ),
						'padding_mobile' => self::box( 16, 16, 28, 16 ),
						'flex_gap'       => self::gap( 20 ),
						'flex_gap_mobile' => self::gap( 16 ),
					),
					$content
				),
			)
		);

		return array(
			self::container(
				array(
					'content_width'         => 'full',
					'flex_direction'        => 'row',
					'flex_wrap'             => 'nowrap',
					'align_items'           => 'stretch',
					'padding'               => self::box( 0, 0, 0, 0 ),
					'flex_gap'              => self::gap( 0 ),
					'min_height'            => array( 'unit' => 'vh', 'size' => 100 ),
					'background_background' => 'classic',
					'background_color'      => '#F7F8FA',
				),
				array( $sidebar, $main ),
				false
			),
		);
	}

	private static function overview_content( array $urls ) {
		return array(
			self::widget( 'ibp-greeting' ),
			self::grid(
				4,
				2,
				1,
				array(
					self::widget( 'ibp-stat-card', array( 'label' => 'Profil görüntüleme', 'source' => 'view' ) ),
					self::widget( 'ibp-stat-card', array( 'label' => 'Telefon araması', 'source' => 'phone' ) ),
					self::widget( 'ibp-stat-card', array( 'label' => 'Yol tarifi', 'source' => 'directions' ) ),
					self::widget( 'ibp-stat-card', array( 'label' => 'Ortalama puan', 'source' => 'rating', 'scale' => '5', 'note' => '' ) ),
				)
			),
			self::row(
				array(
					array( 62, self::widget( 'ibp-chart', array( 'metric' => 'view', 'button_url' => $urls['stats'] ) ) ),
					array( 38, self::widget( 'ibp-ranking' ) ),
				)
			),
			self::grid(
				3,
				1,
				1,
				array(
					self::widget( 'ibp-todos' ),
					self::widget( 'ibp-reviews' ),
					self::widget( 'ibp-completeness' ),
				)
			),
		);
	}

	private static function stats_content() {
		return array(
			self::widget( 'ibp-greeting' ),
			self::grid(
				4,
				2,
				1,
				array(
					self::widget( 'ibp-stat-card', array( 'label' => 'Profil görüntüleme', 'source' => 'view' ) ),
					self::widget( 'ibp-stat-card', array( 'label' => 'Telefon araması', 'source' => 'phone' ) ),
					self::widget( 'ibp-stat-card', array( 'label' => 'Yol tarifi', 'source' => 'directions' ) ),
					self::widget( 'ibp-stat-card', array( 'label' => 'Web sitesi tıklaması', 'source' => 'website' ) ),
				)
			),
			self::widget( 'ibp-chart', array( 'metric' => 'view', 'title' => 'Profil görüntülemeleri', 'button_text' => '' ) ),
			self::row(
				array(
					array( 62, self::widget( 'ibp-chart', array( 'metric' => 'contacts', 'title' => 'İletişim tıklamaları', 'button_text' => '' ) ) ),
					array( 38, self::widget( 'ibp-funnel' ) ),
				)
			),
		);
	}

	/* ---------------------------------------------------------------------
	 * Elementor öğe yardımcıları
	 * ------------------------------------------------------------------ */

	private static function container( array $settings, array $children, $inner = true ) {
		return array(
			'id'       => self::id(),
			'elType'   => 'container',
			'isInner'  => $inner,
			'settings' => $settings + array( 'content_width' => 'full' ),
			'elements' => $children,
		);
	}

	private static function widget( $type, array $settings = array(), array $extra = array() ) {
		return array(
			'id'         => self::id(),
			'elType'     => 'widget',
			'widgetType' => $type,
			'settings'   => $settings + $extra,
			'elements'   => array(),
		);
	}

	/**
	 * Masaüstü/tablet/mobil sütun sayıları verilen ızgara.
	 */
	private static function grid( $desktop, $tablet, $mobile, array $children ) {
		return self::container(
			array(
				'container_type'          => 'grid',
				'grid_columns_grid'       => array( 'unit' => 'fr', 'size' => $desktop ),
				'grid_columns_grid_tablet' => array( 'unit' => 'fr', 'size' => $tablet ),
				'grid_columns_grid_mobile' => array( 'unit' => 'fr', 'size' => $mobile ),
				'grid_rows_grid'          => array( 'unit' => 'fr', 'size' => 1 ),
				'grid_gaps'               => self::gap( 16 ),
				'grid_auto_flow'          => 'row',
				'padding'                 => self::box( 0, 0, 0, 0 ),
			),
			$children
		);
	}

	/**
	 * Masaüstünde yan yana (yüzde genişlikli), tablet ve mobilde alt alta.
	 *
	 * @param array $columns [ [ yüzde, öğe ], … ]
	 */
	private static function row( array $columns ) {
		$children = array();
		foreach ( $columns as $column ) {
			$children[] = self::container(
				array(
					'flex_direction' => 'column',
					'width'          => array( 'unit' => '%', 'size' => $column[0] ),
					'width_tablet'   => array( 'unit' => '%', 'size' => 100 ),
					'padding'        => self::box( 0, 0, 0, 0 ),
					'_flex_size'     => 'grow',
				),
				array( $column[1] )
			);
		}
		return self::container(
			array(
				'flex_direction'        => 'row',
				'flex_direction_tablet' => 'column',
				'flex_wrap'             => 'nowrap',
				'align_items'           => 'stretch',
				'flex_gap'              => self::gap( 16 ),
				'padding'               => self::box( 0, 0, 0, 0 ),
			),
			$children
		);
	}

	private static function box( $top, $right, $bottom, $left ) {
		return array(
			'unit'     => 'px',
			'top'      => (string) $top,
			'right'    => (string) $right,
			'bottom'   => (string) $bottom,
			'left'     => (string) $left,
			'isLinked' => false,
		);
	}

	private static function gap( $size ) {
		return array(
			'unit'     => 'px',
			'size'     => $size,
			'column'   => (string) $size,
			'row'      => (string) $size,
			'isLinked' => true,
		);
	}

	private static function id() {
		return substr( md5( wp_generate_uuid4() ), 0, 7 );
	}
}
