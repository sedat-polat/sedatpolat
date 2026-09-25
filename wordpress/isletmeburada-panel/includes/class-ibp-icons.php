<?php
/**
 * Prototipteki çizgi ikonlar.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IBP_Icons {

	/**
	 * Anahtar => [ Elementor'da görünen ad, SVG içeriği ].
	 */
	public static function all() {
		return array(
			'grid'      => array( 'Genel bakış', '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>' ),
			'chart'     => array( 'İstatistik', '<path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 5-6"/>' ),
			'calcheck'  => array( 'Rezervasyon', '<path d="M3 6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2zM16 2v4M8 2v4M3 10h18M9 16l2 2 4-4"/>' ),
			'tables'    => array( 'Masa planı', '<path d="M3 7a4 4 0 1 0 8 0a4 4 0 1 0-8 0M13 3h8v8h-8zM3 13h18v8H3z"/>' ),
			'store'     => array( 'İşletme', '<path d="M3 9l1.5-5h15L21 9"/><path d="M4 9v11h16V9"/><path d="M9 20v-6h6v6"/>' ),
			'list'      => array( 'Menü / liste', '<path d="M9 6h12M9 12h12M9 18h12"/><path d="M4 6h.01M4 12h.01M4 18h.01" style="stroke-width:3"/>' ),
			'star'      => array( 'Yorum', '<path d="M12 3l2.8 5.7 6.2.9-4.5 4.4 1 6.2L12 17.3 6.5 20.2l1-6.2L3 9.6l6.2-.9z"/>' ),
			'alert'     => array( 'Şikâyet', '<path d="M21 12a8 8 0 0 1-11.6 7.1L3 21l1.9-6.4A8 8 0 1 1 21 12z"/><path d="M12 8v4M12 16h.01"/>' ),
			'tag'       => array( 'Fırsat', '<path d="M20.6 13.4l-7.2 7.2a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><circle cx="7.5" cy="7.5" r="1.5"/>' ),
			'megaphone' => array( 'Duyuru', '<path d="M3 11v2a1 1 0 0 0 1 1h2l5 4V6L6 10H4a1 1 0 0 0-1 1zM15 9a3 3 0 0 1 0 6M18 6a7 7 0 0 1 0 12"/>' ),
			'ticket'    => array( 'Kupon', '<path d="M3 8a2 2 0 0 0 0 4v4a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-4a2 2 0 0 0 0-4V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2zM13 4v2M13 10v2M13 16v2"/>' ),
			'calendar'  => array( 'Etkinlik', '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>' ),
			'briefcase' => array( 'İş ilanı', '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>' ),
			'card'      => array( 'Paket ve fatura', '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>' ),
			'sliders'   => array( 'Ayarlar', '<path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"/>' ),
			'person'    => array( 'Kişi', '<path d="M12 3a4 4 0 1 0 0 8a4 4 0 1 0 0-8M5 21a7 7 0 0 1 14 0"/>' ),
			'heart'     => array( 'Favori', '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8z"/>' ),
			'eye'       => array( 'Önizle', '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>' ),
			'bell'      => array( 'Bildirim', '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.9 1.9 0 0 0 3.4 0"/>' ),
			'plus'      => array( 'Ekle', '<path d="M12 5v14M5 12h14"/>' ),
			'pin'       => array( 'Konum', '<path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>' ),
			'crown'     => array( 'Taç', '<path d="M3 19h18M4 8l4 4 4-7 4 7 4-4-2 11H6z"/>' ),
			'phone'     => array( 'Telefon', '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/>' ),
			'sidebar'   => array( 'Kenar çubuğu', '<rect x="3" y="4" width="18" height="16" rx="2.5"/><path d="M9 4v16"/><path d="M15 10l-2 2 2 2"/>' ),
			'menu'      => array( 'Menü', '<path d="M4 6h16M4 12h16M4 18h16"/>' ),
			'close'     => array( 'Kapat', '<path d="M6 6l12 12M18 6L6 18"/>' ),
			'logout'    => array( 'Çıkış', '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>' ),
			'chevron'   => array( 'Ok', '<path d="M6 9l6 6 6-6"/>' ),
		);
	}

	public static function options() {
		return array_map(
			function ( $icon ) {
				return $icon[0];
			},
			self::all()
		);
	}

	public static function svg( $key, $class = 'ibp-i' ) {
		$icons = self::all();
		if ( ! isset( $icons[ $key ] ) ) {
			return '';
		}
		return '<svg class="' . esc_attr( $class ) . '" viewBox="0 0 24 24" aria-hidden="true">' . $icons[ $key ][1] . '</svg>';
	}
}
