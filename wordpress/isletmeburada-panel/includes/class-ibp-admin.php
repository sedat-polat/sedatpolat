<?php
/**
 * WordPress yönetim menüsü: İşletmeBurada → Genel bakış, Widget'lar, Sektörler,
 * Marka ağı, Ayarlar, Tanılama (her biri yan menüde ayrı alt sayfa).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IBP_Admin {

	const SLUG = 'isletmeburada-panel';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_post_ibp_create_pages', array( __CLASS__, 'create_pages' ) );
		add_action( 'admin_post_ibp_flush_ranking', array( __CLASS__, 'flush_ranking' ) );
		add_action( 'admin_post_ibp_create_home', array( __CLASS__, 'create_home' ) );
		add_action( 'admin_post_ibp_save_widgets', array( 'IBP_Widgets', 'save' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( IBP_FILE ), array( __CLASS__, 'action_links' ) );
	}

	/**
	 * Alt sayfalar: sekme anahtarı => menüdeki ad.
	 */
	public static function pages() {
		return array(
			'status'   => 'Genel bakış',
			'widgets'  => 'Widget\'lar',
			'sectors'  => 'Sektörler',
			'network'  => 'Marka ağı',
			'settings' => 'Ayarlar',
			'debug'    => 'Tanılama',
		);
	}

	public static function menu() {
		add_menu_page( 'İşletmeBurada Panel', 'İşletmeBurada', 'manage_options', self::SLUG, array( __CLASS__, 'render' ), 'dashicons-store', 58 );
		foreach ( self::pages() as $tab => $label ) {
			add_submenu_page( self::SLUG, $label . ' — İşletmeBurada', $label, 'manage_options', self::slug_for( $tab ), array( __CLASS__, 'render' ) );
		}
	}

	private static function slug_for( $tab ) {
		return 'status' === $tab ? self::SLUG : self::SLUG . '-' . $tab;
	}

	private static function current_tab() {
		$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : self::SLUG; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		foreach ( array_keys( self::pages() ) as $tab ) {
			if ( self::slug_for( $tab ) === $page ) {
				return $tab;
			}
		}
		return 'status';
	}

	public static function assets( $hook ) {
		if ( false === strpos( (string) $hook, self::SLUG ) ) {
			return;
		}
		wp_enqueue_style( 'ibp-admin', IBP_URL . 'assets/admin.css', array(), IBP_VERSION );
		wp_enqueue_script( 'ibp-admin', IBP_URL . 'assets/admin.js', array(), IBP_VERSION, true );
	}

	public static function action_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( self::url() ) . '">Genel bakış</a>', '<a href="' . esc_url( self::url( 'widgets' ) ) . '">Widget\'lar</a>' );
		return $links;
	}

	public static function register_settings() {
		register_setting( 'ibp_settings_group', IBP_Settings::OPTION, array( 'sanitize_callback' => array( 'IBP_Settings', 'sanitize' ) ) );
	}

	public static function url( $tab = 'status', array $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => self::slug_for( $tab ) ), $args ), admin_url( 'admin.php' ) );
	}

	/* ---------------------------------------------------------------------
	 * İşlemler
	 * ------------------------------------------------------------------ */

	public static function create_pages() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ibp_create_pages' ) ) {
			wp_die( 'Bu işlem için yetkin yok.' );
		}
		if ( ! did_action( 'elementor/loaded' ) ) {
			wp_safe_redirect( self::url( 'status', array( 'ibp_msg' => 'no-elementor' ) ) );
			exit;
		}
		// Varsayılan menü öğeleri widget sınıfında tanımlı.
		require_once IBP_DIR . 'includes/widgets/class-ibp-widget-base.php';
		require_once IBP_DIR . 'includes/widgets/class-ibp-widget-nav.php';

		IBP_Page_Builder::create();
		wp_safe_redirect( self::url( 'status', array( 'ibp_msg' => 'created' ) ) );
		exit;
	}

	public static function create_home() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ibp_create_home' ) ) {
			wp_die( 'Bu işlem için yetkin yok.' );
		}
		if ( ! did_action( 'elementor/loaded' ) ) {
			wp_safe_redirect( self::url( 'status', array( 'ibp_msg' => 'no-elementor' ) ) );
			exit;
		}
		IBP_Page_Builder::create_home();
		delete_transient( 'ibp_city_counts' );
		delete_transient( 'ibp_home_stats' );
		wp_safe_redirect( self::url( 'status', array( 'ibp_msg' => 'home' ) ) );
		exit;
	}

	public static function flush_ranking() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ibp_flush_ranking' ) ) {
			wp_die( 'Bu işlem için yetkin yok.' );
		}
		IBP_Ranking::flush();
		wp_safe_redirect( self::url( 'status', array( 'ibp_msg' => 'flushed' ) ) );
		exit;
	}

	/* ---------------------------------------------------------------------
	 * Sayfa
	 * ------------------------------------------------------------------ */

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$tab   = self::current_tab();
		$pages = self::pages();
		?>
		<div class="wrap ibp-admin">
			<div class="ibp-admin__bar">
				<span class="ibp-admin__logo"><span class="dashicons dashicons-store"></span></span>
				<div>
					<h1><?php echo esc_html( $pages[ $tab ] ); ?></h1>
					<span class="ibp-admin__crumb">İşletmeBurada Panel · sürüm <?php echo esc_html( IBP_VERSION ); ?></span>
				</div>
			</div>
			<?php self::notice(); ?>
			<div class="ibp-admin__body">
				<?php
				switch ( $tab ) {
					case 'widgets':
						self::render_widgets();
						break;
					case 'settings':
						self::render_settings();
						break;
					case 'sectors':
						self::render_sectors();
						break;
					case 'network':
						self::render_network();
						break;
					case 'debug':
						self::render_debug();
						break;
					default:
						self::render_status();
				}
				?>
			</div>
		</div>
		<?php
	}

	private static function notice() {
		$messages = array(
			'created'      => array( 'success', 'Panel sayfaları oluşturuldu/güncellendi. Aşağıdan açıp Elementor ile düzenleyebilirsin.' ),
			'flushed'      => array( 'success', 'Şehrin Sahipleri sıralaması yeniden hesaplanacak.' ),
			'home'         => array( 'success', 'Ana sayfa taslağı hazır. Önizleyip beğenirsen Ayarlar → Okuma → "Ana sayfa" olarak seç.' ),
			'no-elementor' => array( 'error', 'Sayfa oluşturmak için Elementor etkin olmalı.' ),
			'widgets'      => array( 'success', 'Widget ayarları kaydedildi. Kapatılan widget\'lar Elementor\'da artık görünmez.' ),
		);
		$key = isset( $_GET['ibp_msg'] ) ? sanitize_key( $_GET['ibp_msg'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $messages[ $key ] ) ) {
			printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $messages[ $key ][0] ), esc_html( $messages[ $key ][1] ) );
		}
	}

	private static function render_status() {
		$post_type = IBP_Business::post_type();
		$counts    = wp_count_posts( $post_type );
		$checks    = array(
			array( 'Elementor', did_action( 'elementor/loaded' ), defined( 'ELEMENTOR_VERSION' ) ? 'Sürüm ' . ELEMENTOR_VERSION : 'Kurulu değil; widget\'lar görünmez.' ),
			array( 'Elementor Pro', defined( 'ELEMENTOR_PRO_VERSION' ), defined( 'ELEMENTOR_PRO_VERSION' ) ? 'Sürüm ' . ELEMENTOR_PRO_VERSION : 'Gerekli değil; Display Conditions için önerilir.' ),
			array( 'Voxel', class_exists( '\Voxel\Post' ), class_exists( '\Voxel\Post' ) ? 'Voxel API bulundu.' : 'Voxel bulunamadı; alanlar post meta\'dan okunur.' ),
			array( 'İşletme post type', post_type_exists( $post_type ), sprintf( '"%s": %d yayında, %d onay bekliyor', $post_type, (int) ( $counts->publish ?? 0 ), (int) ( $counts->pending ?? 0 ) ) ),
			array( 'Yorum tablosu', in_array( 'post_id', IBP_Reviews::columns(), true ), IBP_Reviews::table() ),
			array( 'Yorum yanıtları', in_array( 'status_id', IBP_Reviews::reply_columns(), true ), 'Yanıt bekleyen yorum sayısı için' ),
			array( 'Sayaç tablosu', IBP_Tracker::table_exists(), sprintf( 'Son 30 günde %s kayıt', number_format_i18n( IBP_Tracker::total_all( 30 ) ) ) ),
			array( 'Sayaç', '1' === IBP_Settings::get( 'track_enabled' ), '1' === IBP_Settings::get( 'track_enabled' ) ? 'Açık' : 'Ayarlardan kapatılmış' ),
		);
		$pages = IBP_Page_Builder::existing();
		?>
		<h2>Kontroller</h2>
		<table class="widefat striped" style="max-width:800px">
			<tbody>
				<?php foreach ( $checks as $check ) : ?>
					<tr>
						<td style="width:28px"><span class="dashicons <?php echo $check[1] ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>" style="color:<?php echo $check[1] ? '#15803D' : '#B45309'; ?>"></span></td>
						<td style="width:180px"><strong><?php echo esc_html( $check[0] ); ?></strong></td>
						<td><?php echo esc_html( $check[2] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<h2 style="margin-top:28px">Panel sayfaları</h2>
		<?php if ( $pages ) : ?>
			<table class="widefat striped" style="max-width:800px">
				<tbody>
					<?php foreach ( $pages as $id ) : ?>
						<tr>
							<td><strong><?php echo esc_html( get_the_title( $id ) ); ?></strong></td>
							<td><a href="<?php echo esc_url( get_permalink( $id ) ); ?>" target="_blank" rel="noopener">Görüntüle</a></td>
							<td><a href="<?php echo esc_url( admin_url( 'post.php?post=' . $id . '&action=elementor' ) ); ?>">Elementor ile düzenle</a></td>
							<td><a href="<?php echo esc_url( add_query_arg( 'ibp_debug', 1, get_permalink( $id ) ) ); ?>" target="_blank" rel="noopener">Tanılamayla aç</a></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p class="description">İşletme paneli (Genel bakış, İstatistikler, Bağlı işletmeler) ve bireysel panel (Hesabım) sayfaları.</p>
		<?php else : ?>
			<p>İşletme paneli (Genel bakış, İstatistikler, Bağlı İşletmeler) ve bireysel panel (Hesabım) sayfalarını, tüm widget'lar yerleşmiş ve mobil ayarları yapılmış hâlde oluşturur.</p>
		<?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:12px"<?php echo $pages ? ' onsubmit="return confirm(\'Bu sayfalarda Elementor ile yaptığın değişiklikler silinip varsayılan düzen yeniden kurulacak. Devam edilsin mi?\')"' : ''; ?>>
			<input type="hidden" name="action" value="ibp_create_pages">
			<?php wp_nonce_field( 'ibp_create_pages' ); ?>
			<?php submit_button( $pages ? 'Sayfaları varsayılan düzene sıfırla (eksik sayfayı ekler)' : 'Panel sayfalarını oluştur', $pages ? 'secondary' : 'primary', 'submit', false ); ?>
		</form>

		<h2 style="margin-top:28px">Ana sayfa</h2>
		<p>Prototipteki ana sayfayı (arama, Şehrin Sahipleri, kategori sıralaması, şehirler, paketler, referanslar, blog, çağrı bandı) <strong>taslak</strong> bir sayfa olarak kurar; mevcut ana sayfan değişmez. Sitenin üst menüsü ve alt bilgisi korunur.</p>
		<?php $home = IBP_Page_Builder::existing()['home'] ?? 0; ?>
		<?php if ( $home ) : ?>
			<p>
				<a class="button" href="<?php echo esc_url( get_preview_post_link( $home ) ?: get_permalink( $home ) ); ?>" target="_blank" rel="noopener">Önizle</a>
				<a class="button" href="<?php echo esc_url( admin_url( 'post.php?post=' . $home . '&action=elementor' ) ); ?>">Elementor ile düzenle</a>
				<a class="button" href="<?php echo esc_url( admin_url( 'options-reading.php' ) ); ?>">Ana sayfa olarak ayarla</a>
				<span class="description" style="margin-left:8px">Durum: <?php echo esc_html( 'publish' === get_post_status( $home ) ? 'Yayında' : 'Taslak' ); ?></span>
			</p>
		<?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"<?php echo $home ? ' onsubmit="return confirm(\'Ana sayfa taslağındaki Elementor değişikliklerin silinip varsayılan düzen yeniden kurulacak. Devam edilsin mi?\')"' : ''; ?>>
			<input type="hidden" name="action" value="ibp_create_home">
			<?php wp_nonce_field( 'ibp_create_home' ); ?>
			<?php submit_button( $home ? 'Ana sayfa taslağını varsayılana sıfırla' : 'Ana sayfa taslağı oluştur', $home ? 'secondary' : 'primary', 'submit', false ); ?>
		</form>
		<p class="description">Arama ve şehir kartlarının doğru sonuca gitmesi için <strong>Ayarlar → Arama sayfası</strong> alanını doldur.</p>

		<?php
		$all      = IBP_Widgets::all();
		$disabled = IBP_Widgets::disabled();
		?>
		<h2 style="margin-top:28px">Widget'lar</h2>
		<p><?php echo esc_html( sprintf( '%d widget\'tan %d tanesi açık.', count( $all ), count( $all ) - count( $disabled ) ) ); ?> <a href="<?php echo esc_url( self::url( 'widgets' ) ); ?>">Widget'ları yönet →</a></p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:20px">
			<input type="hidden" name="action" value="ibp_flush_ranking">
			<?php wp_nonce_field( 'ibp_flush_ranking' ); ?>
			<?php submit_button( 'Şehrin Sahipleri sıralamasını yenile', 'secondary', 'submit', false ); ?>
			<span class="description" style="margin-left:8px">Sıralama saatte bir kendiliğinden yenilenir.</span>
		</form>
		<?php
	}

	/**
	 * Widget'lar: kartlar, arama, grup filtresi ve aç/kapat anahtarları.
	 */
	private static function render_widgets() {
		$all      = IBP_Widgets::all();
		$groups   = IBP_Widgets::groups();
		$disabled = IBP_Widgets::disabled();
		$usage    = IBP_Widgets::usage();
		$counts   = array_count_values( wp_list_pluck( $all, 'group' ) );
		$in_use   = count( array_intersect( array_keys( $all ), array_keys( $usage ) ) );
		?>
		<div class="ibp-wx">
			<section class="ibp-wx-hero">
				<div class="ibp-wx-hero__main">
					<span class="ibp-wx-hero__kicker">ELEMENTOR WIDGET'LARI</span>
					<h2>Panel, bireysel hesap ve ana sayfa; tek eklentide.</h2>
					<p>Kullanmadığın widget'ları kapat; Elementor'un widget listesinde görünmez ve yüklenmez. Bir sayfada kullanılan widget'ı kapatmadan önce uyarı alırsın.</p>
					<ul class="ibp-wx-hero__checks">
						<li><span class="dashicons dashicons-yes"></span>Voxel verisiyle çalışır</li>
						<li><span class="dashicons dashicons-yes"></span>Kategoriye göre menü</li>
						<li><span class="dashicons dashicons-yes"></span>Marka, şube ve bayi</li>
						<li><span class="dashicons dashicons-yes"></span>Mobil uyumlu</li>
					</ul>
					<div class="ibp-wx-hero__actions">
						<a class="ibp-wx-btn ibp-wx-btn--primary" href="<?php echo esc_url( self::url() ); ?>">Panel sayfalarını kur</a>
						<a class="ibp-wx-btn ibp-wx-btn--ghost" href="<?php echo esc_url( self::url( 'settings' ) ); ?>">Ayarlar</a>
					</div>
				</div>
				<div class="ibp-wx-hero__stats">
					<div><b data-ibp-count-on><?php echo esc_html( count( $all ) - count( $disabled ) ); ?></b><span>açık</span></div>
					<div><b><?php echo esc_html( count( $all ) ); ?></b><span>toplam</span></div>
					<div><b><?php echo esc_html( $in_use ); ?></b><span>sayfalarda kullanılıyor</span></div>
				</div>
			</section>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ibp-wx-form" data-ibp-widgets>
				<input type="hidden" name="action" value="ibp_save_widgets">
				<?php wp_nonce_field( IBP_Widgets::NONCE ); ?>

				<div class="ibp-wx-toolbar">
					<label class="ibp-wx-search">
						<span class="dashicons dashicons-search"></span>
						<span class="screen-reader-text">Widget ara</span>
						<input type="search" placeholder="Widget ara…" data-ibp-search>
					</label>
					<div class="ibp-wx-chips" role="group" aria-label="Grup">
						<button type="button" class="ibp-wx-chip is-on" data-ibp-group="">Tümü <span><?php echo esc_html( count( $all ) ); ?></span></button>
						<?php foreach ( $groups as $key => $label ) : ?>
							<button type="button" class="ibp-wx-chip" data-ibp-group="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?> <span><?php echo esc_html( $counts[ $key ] ?? 0 ); ?></span></button>
						<?php endforeach; ?>
					</div>
					<div class="ibp-wx-bulk">
						<button type="button" class="ibp-wx-btn ibp-wx-btn--sm" data-ibp-all="1">Görünenleri aç</button>
						<button type="button" class="ibp-wx-btn ibp-wx-btn--sm" data-ibp-all="0">Görünenleri kapat</button>
					</div>
				</div>

				<div class="ibp-wx-grid">
					<?php foreach ( $all as $name => $widget ) : ?>
						<?php
						$on    = ! in_array( $name, $disabled, true );
						$used  = $usage[ $name ]['count'] ?? 0;
						$pages = $usage[ $name ]['pages'] ?? array();
						$id    = 'ibp-wx-' . $name;
						?>
						<div class="ibp-wx-card<?php echo $on ? '' : ' is-off'; ?>" data-group="<?php echo esc_attr( $widget['group'] ); ?>" data-search="<?php echo esc_attr( IBP_Business::lower_tr( $widget['title'] . ' ' . $widget['desc'] . ' ' . $groups[ $widget['group'] ] ) ); ?>">
							<div class="ibp-wx-card__top">
								<span class="ibp-wx-card__ic ibp-wx-card__ic--<?php echo esc_attr( $widget['group'] ); ?>"><?php echo IBP_Icons::svg( $widget['icon'], 'ibp-wx-i' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
								<div class="ibp-wx-card__title">
									<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $widget['title'] ); ?></label>
									<span class="ibp-wx-tag ibp-wx-tag--<?php echo esc_attr( $widget['group'] ); ?>"><?php echo esc_html( $groups[ $widget['group'] ] ); ?></span>
								</div>
							</div>
							<p class="ibp-wx-card__desc"><?php echo esc_html( $widget['desc'] ); ?></p>
							<div class="ibp-wx-card__foot">
								<span class="ibp-wx-card__usage<?php echo $used ? ' is-used' : ''; ?>"<?php echo $pages ? ' title="' . esc_attr( implode( ', ', array_slice( $pages, 0, 8 ) ) ) . '"' : ''; ?>>
									<?php echo esc_html( $used ? sprintf( '%d sayfada kullanılıyor', $used ) : 'Hiçbir sayfada kullanılmıyor' ); ?>
								</span>
								<span class="ibp-wx-switch">
									<input type="checkbox" role="switch" id="<?php echo esc_attr( $id ); ?>" name="enabled[]" value="<?php echo esc_attr( $name ); ?>" data-used="<?php echo esc_attr( $used ); ?>" data-title="<?php echo esc_attr( $widget['title'] ); ?>"<?php checked( $on ); ?>>
									<span aria-hidden="true"></span>
								</span>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<p class="ibp-wx-none" data-ibp-none hidden>Aramaya uyan widget yok.</p>

				<div class="ibp-wx-save" data-ibp-save>
					<span data-ibp-dirty-text>Değişiklik yok.</span>
					<button type="submit" class="ibp-wx-btn ibp-wx-btn--primary">Kaydet</button>
				</div>
			</form>
		</div>
		<?php
	}

	private static function render_settings() {
		$s = IBP_Settings::all();
		$n = IBP_Settings::OPTION;
		?>
		<form method="post" action="options.php">
			<?php settings_fields( 'ibp_settings_group' ); ?>
			<input type="hidden" name="<?php echo esc_attr( $n ); ?>[_tab]" value="general">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="ibp-pt">İşletme post type anahtarı</label></th>
					<td><input id="ibp-pt" class="regular-text" name="<?php echo esc_attr( $n ); ?>[post_type]" value="<?php echo esc_attr( $s['post_type'] ); ?>"><p class="description">Voxel → Structure'da İşletme'nin altında yazan anahtar.</p></td>
				</tr>
				<tr>
					<th scope="row"><label for="ibp-edit">Düzenleme sayfası adresi</label></th>
					<td><input id="ibp-edit" class="regular-text" name="<?php echo esc_attr( $n ); ?>[edit_url]" value="<?php echo esc_attr( $s['edit_url'] ); ?>" placeholder="Boş: Voxel'in düzenleme bağlantısı"><p class="description">Özel bir adres kullanacaksan {id} yazdığın yere işletme ID'si gelir.</p></td>
				</tr>
				<tr>
					<th scope="row"><label for="ibp-period">Varsayılan dönem</label></th>
					<td>
						<select id="ibp-period" name="<?php echo esc_attr( $n ); ?>[default_period]">
							<?php foreach ( array( '7', '30', '90' ) as $days ) : ?>
								<option value="<?php echo esc_attr( $days ); ?>" <?php selected( $s['default_period'], $days ); ?>><?php echo esc_html( $days . ' gün' ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">Sayaç</th>
					<td>
						<label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[track_enabled]" value="1" <?php checked( $s['track_enabled'], '1' ); ?>> İşletme sayfalarındaki görüntüleme ve tıklamaları say</label><br>
						<label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[track_skip_owners]" value="1" <?php checked( $s['track_skip_owners'], '1' ); ?>> İşletme sahiplerini ve yöneticileri sayma</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ibp-scale">Şehrin Sahipleri puan ölçeği</label></th>
					<td>
						<select id="ibp-scale" name="<?php echo esc_attr( $n ); ?>[ranking_scale]">
							<option value="10" <?php selected( $s['ranking_scale'], '10' ); ?>>10 üzerinden (prototipteki gibi)</option>
							<option value="5" <?php selected( $s['ranking_scale'], '5' ); ?>>5 üzerinden (yıldız)</option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ibp-min">Sıralamaya girmek için en az yorum</label></th>
					<td><input id="ibp-min" type="number" min="1" max="50" class="small-text" name="<?php echo esc_attr( $n ); ?>[ranking_min_reviews]" value="<?php echo esc_attr( $s['ranking_min_reviews'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="ibp-home-city">Ana sayfa varsayılan şehri</label></th>
					<td><input id="ibp-home-city" class="regular-text" name="<?php echo esc_attr( $n ); ?>[home_city]" value="<?php echo esc_attr( $s['home_city'] ); ?>"><p class="description">Ziyaretçi şehir seçmediyse ana sayfadaki sıralamalar bu şehir için gösterilir. Şehir ?sehir=izmir ile değişir.</p></td>
				</tr>
				<tr>
					<th scope="row"><label for="ibp-search-url">Arama sayfası</label></th>
					<td>
						<input id="ibp-search-url" class="regular-text" name="<?php echo esc_attr( $n ); ?>[search_url]" value="<?php echo esc_attr( $s['search_url'] ); ?>" placeholder="https://isletmeburada.com/isletmeler/">
						<p class="description">Ana sayfadaki arama, kategori ve şehir kartları bu sayfaya gider. Voxel arama formundaki filtre anahtarlarını yaz:</p>
						<label>Kategori parametresi <input class="small-text" style="width:120px" name="<?php echo esc_attr( $n ); ?>[cat_param]" value="<?php echo esc_attr( $s['cat_param'] ); ?>"></label>
						<label style="margin-left:12px">Şehir parametresi <input class="small-text" style="width:120px" name="<?php echo esc_attr( $n ); ?>[city_param]" value="<?php echo esc_attr( $s['city_param'] ); ?>"></label>
						<p class="description">Örnek sonuç: /isletmeler/?kategori=restaurant&amp;sehir=izmir. Voxel'de bir filtre uygulayıp adres çubuğundaki adları buraya kopyalayabilirsin.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Yerel rehber puanı (bireysel panel)</th>
					<td>
						<label>Her yorum <input type="number" min="0" max="1000" class="small-text" name="<?php echo esc_attr( $n ); ?>[points_review]" value="<?php echo esc_attr( $s['points_review'] ); ?>"> puan</label><br>
						<label>Favorilere eklenen her işletme <input type="number" min="0" max="1000" class="small-text" name="<?php echo esc_attr( $n ); ?>[points_favorite]" value="<?php echo esc_attr( $s['points_favorite'] ); ?>"> puan</label><br>
						<label>Her <input type="number" min="10" max="100000" class="small-text" name="<?php echo esc_attr( $n ); ?>[level_size]" value="<?php echo esc_attr( $s['level_size'] ); ?>"> puanda bir seviye atlanır</label>
					</td>
				</tr>
			</table>
			<?php submit_button( 'Kaydet' ); ?>
		</form>
		<?php
	}

	private static function render_sectors() {
		$settings = IBP_Settings::all();
		$n        = IBP_Settings::OPTION;
		$taxonomy = IBP_Business::taxonomy_by_label( 'Kategori' );
		$terms    = $taxonomy ? get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false, 'number' => 500, 'orderby' => 'name' ) ) : array();
		$profiles = IBP_Sectors::profiles();
		?>
		<p>Panel menüsündeki operasyon grubu (Rezervasyonlar, Randevular, Siparişler…) ve "Menü ve fiyatlar" gibi adlar işletmenin kategorisine göre değişir. Kategoriler çoğunlukla <strong>otomatik</strong> eşleşir; yanlış olanı buradan düzelt. Alt kategoriler, eşleştirilmemişse üst kategorinin sektörünü alır.</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'ibp_settings_group' ); ?>
			<input type="hidden" name="<?php echo esc_attr( $n ); ?>[_tab]" value="sectors">
			<?php if ( is_wp_error( $terms ) || ! $terms ) : ?>
				<p><em>"Kategori" taksonomisi ya da terimi bulunamadı.</em></p>
			<?php else : ?>
				<table class="widefat striped" style="max-width:900px">
					<thead><tr><th>Kategori</th><th>İşletme</th><th>Sektör menüsü</th><th>Menüde görünecekler</th></tr></thead>
					<tbody>
						<?php foreach ( $terms as $term ) : ?>
							<?php
							$chosen  = $settings['sector_map'][ $term->term_id ] ?? '';
							$auto    = IBP_Sectors::guess( $term );
							$current = $profiles[ $chosen ?: IBP_Sectors::key_for_term( $term ) ];
							?>
							<tr>
								<td><?php echo esc_html( ( $term->parent ? '— ' : '' ) . $term->name ); ?></td>
								<td><?php echo esc_html( $term->count ); ?></td>
								<td>
									<select name="<?php echo esc_attr( $n ); ?>[sector_map][<?php echo esc_attr( $term->term_id ); ?>]">
										<option value=""><?php echo esc_html( 'Otomatik: ' . ( IBP_Sectors::GENERAL === $auto && $term->parent ? 'üst kategoriden' : $profiles[ $auto ]['name'] ) ); ?></option>
										<?php foreach ( $profiles as $key => $profile ) : ?>
											<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $chosen, $key ); ?>><?php echo esc_html( $profile['name'] ); ?></option>
										<?php endforeach; ?>
									</select>
								</td>
								<td style="color:#6B7280"><?php echo esc_html( $current['ops'] . ': ' . ( implode( ', ', wp_list_pluck( IBP_Sectors::menu_items( $current ), 0 ) ) ?: '—' ) . ' · ' . $current['menu'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<h2 style="margin-top:28px">Modül sayfaları</h2>
			<p>Bir modülün sayfası hazır olduğunda adresini yaz; menüde "Yakında" yerine bağlantı olarak görünür. Boş bırakılanlar "Yakında" kalır. {edit}, {view}, {id} kısayolları kullanılabilir.</p>
			<table class="form-table" role="presentation">
				<?php foreach ( IBP_Sectors::modules() as $key => $module ) : ?>
					<tr>
						<th scope="row"><label for="ibp-mod-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $module[1] ); ?></label></th>
						<td><input id="ibp-mod-<?php echo esc_attr( $key ); ?>" class="regular-text" name="<?php echo esc_attr( $n ); ?>[module_urls][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $settings['module_urls'][ $key ] ?? '' ); ?>" placeholder="Yakında"></td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php submit_button( 'Kaydet' ); ?>
		</form>
		<?php
	}

	private static function render_network() {
		$ids = get_posts(
			array(
				'post_type'      => IBP_Business::post_type(),
				'post_status'    => array( 'publish', 'pending', 'draft', 'private' ),
				'posts_per_page' => 500,
				'fields'         => 'ids',
				'meta_key'       => IBP_Network::PARENT, // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);
		$result = isset( $_GET['ibp_net'] ) ? sanitize_text_field( wp_unslash( $_GET['ibp_net'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '' !== $result ) {
			$error = 0 === strpos( $result, 'error:' );
			printf( '<div class="notice notice-%s"><p>%s</p></div>', $error ? 'error' : 'success', esc_html( $error ? substr( $result, 6 ) : 'İşlem tamamlandı.' ) );
		}
		?>
		<p>İşletmeler panellerindeki <strong>Bağlı İşletmeler</strong> sayfasından bir markaya bağlanma ister; markanın sahibi onaylar. Burada tüm bağlantıları görebilir, gerekirse site yöneticisi olarak onaylayabilir ya da kaldırabilirsin.</p>
		<?php if ( ! $ids ) : ?>
			<p><em>Henüz bağlantı ya da istek yok.</em></p>
			<?php return; ?>
		<?php endif; ?>
		<table class="widefat striped" style="max-width:1000px">
			<thead><tr><th>İşletme</th><th>Marka</th><th>Tür</th><th>Durum</th><th>Tarih</th><th></th></tr></thead>
			<tbody>
				<?php foreach ( $ids as $id ) : ?>
					<?php
					$link = IBP_Network::link_of( $id );
					if ( ! $link ) {
						continue;
					}
					$since = (int) get_post_meta( $id, IBP_Network::SINCE, true );
					?>
					<tr>
						<td><a href="<?php echo esc_url( get_permalink( $id ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( get_the_title( $id ) ); ?></a></td>
						<td><a href="<?php echo esc_url( get_permalink( $link['parent'] ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( get_the_title( $link['parent'] ) ); ?></a></td>
						<td><?php echo esc_html( IBP_Network::types()[ $link['type'] ] ); ?></td>
						<td><?php echo 'approved' === $link['status'] ? '<span style="color:#15803D">Onaylı</span>' : '<span style="color:#B45309">Onay bekliyor</span>'; ?></td>
						<td><?php echo esc_html( $since ? wp_date( 'j F Y', $since ) : '' ); ?></td>
						<td style="white-space:nowrap">
							<?php foreach ( ( 'pending' === $link['status'] ? array( 'approve' => 'Onayla', 'reject' => 'Reddet' ) : array( 'unlink' => 'Kaldır' ) ) as $action => $label ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
									<?php wp_nonce_field( IBP_Network::NONCE ); ?>
									<input type="hidden" name="action" value="<?php echo esc_attr( 'ibp_net_' . $action ); ?>">
									<input type="hidden" name="child" value="<?php echo esc_attr( $id ); ?>">
									<button type="submit" class="button button-small"><?php echo esc_html( $label ); ?></button>
								</form>
							<?php endforeach; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	private static function render_debug() {
		$selected = isset( $_GET['business'] ) ? absint( $_GET['business'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$ids      = get_posts(
			array(
				'post_type'      => IBP_Business::post_type(),
				'post_status'    => array( 'publish', 'pending', 'draft', 'private' ),
				'posts_per_page' => 300,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'fields'         => 'ids',
			)
		);
		?>
		<form method="get">
			<input type="hidden" name="page" value="<?php echo esc_attr( self::slug_for( 'debug' ) ); ?>">
			<label for="ibp-biz"><strong>İşletme:</strong></label>
			<select id="ibp-biz" name="business">
				<option value="0">Seç…</option>
				<?php foreach ( $ids as $id ) : ?>
					<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $selected, $id ); ?>><?php echo esc_html( get_the_title( $id ) . ' (#' . $id . ')' ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php submit_button( 'Göster', 'secondary', '', false ); ?>
		</form>
		<?php
		$business = $selected ? IBP_Business::from_id( $selected ) : null;
		$me       = IBP_User::current();
		if ( $me ) {
			echo '<h3>Bireysel panel verisi (senin hesabın)</h3><pre style="padding:16px;background:#1C1E21;color:#E6E8EB;font:12px/1.5 ui-monospace,Menlo,monospace;white-space:pre-wrap;border-radius:8px;max-height:40vh;overflow:auto">' . esc_html( IBP_Debug::json( $me->report() ) ) . '</pre>';
		}
		if ( ! $business ) {
			echo '<p class="description">Bir işletme seç; Voxel verisinin nasıl okunduğu, sayaç, sıralama ve bekleyen işler burada görünür. Bir şey yanlış görünürse bu metni kopyalayıp gönder.</p>';
			return;
		}
		echo '<pre style="margin-top:16px;padding:16px;background:#1C1E21;color:#E6E8EB;font:12px/1.5 ui-monospace,Menlo,monospace;white-space:pre-wrap;border-radius:8px;max-height:70vh;overflow:auto">';
		echo esc_html( IBP_Debug::json( IBP_Debug::report( $business ) ) );
		echo '</pre>';
	}
}
