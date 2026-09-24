<?php
/**
 * WordPress yönetim menüsü: İşletmeBurada → Durum / Ayarlar / Tanılama.
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
		add_filter( 'plugin_action_links_' . plugin_basename( IBP_FILE ), array( __CLASS__, 'action_links' ) );
	}

	public static function menu() {
		add_menu_page( 'İşletmeBurada Panel', 'İşletmeBurada', 'manage_options', self::SLUG, array( __CLASS__, 'render' ), 'dashicons-store', 58 );
	}

	public static function action_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( self::url() ) . '">Durum ve ayarlar</a>' );
		return $links;
	}

	public static function register_settings() {
		register_setting( 'ibp_settings_group', IBP_Settings::OPTION, array( 'sanitize_callback' => array( 'IBP_Settings', 'sanitize' ) ) );
	}

	private static function url( $tab = 'status', array $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => self::SLUG, 'tab' => $tab ), $args ), admin_url( 'admin.php' ) );
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
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'status'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tabs = array(
			'status'   => 'Durum',
			'settings' => 'Ayarlar',
			'debug'    => 'Tanılama',
		);
		?>
		<div class="wrap ibp-admin">
			<h1>İşletmeBurada Panel <span style="font-size:13px;color:#6B7280;font-weight:400">sürüm <?php echo esc_html( IBP_VERSION ); ?></span></h1>
			<?php self::notice(); ?>
			<nav class="nav-tab-wrapper">
				<?php foreach ( $tabs as $key => $label ) : ?>
					<a class="nav-tab<?php echo $key === $tab ? ' nav-tab-active' : ''; ?>" href="<?php echo esc_url( self::url( $key ) ); ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>
			<div style="margin-top:20px;max-width:1100px">
				<?php
				switch ( $tab ) {
					case 'settings':
						self::render_settings();
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
			'created'      => array( 'success', 'Panel sayfaları oluşturuldu. Aşağıdan açıp Elementor ile düzenleyebilirsin.' ),
			'flushed'      => array( 'success', 'Şehrin Sahipleri sıralaması yeniden hesaplanacak.' ),
			'no-elementor' => array( 'error', 'Sayfa oluşturmak için Elementor etkin olmalı.' ),
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
			<p class="description">Menüdeki "İşletme Paneli" ve "İstatistikler" bağlantıları bu sayfalara gider.</p>
		<?php else : ?>
			<p>Genel bakış ve İstatistikler sayfalarını, tüm widget'lar yerleşmiş ve mobil ayarları yapılmış hâlde oluşturur.</p>
		<?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:12px">
			<input type="hidden" name="action" value="ibp_create_pages">
			<?php wp_nonce_field( 'ibp_create_pages' ); ?>
			<?php submit_button( $pages ? 'Sayfaları yeniden oluştur (yeni kopya)' : 'Panel sayfalarını oluştur', $pages ? 'secondary' : 'primary', 'submit', false ); ?>
		</form>

		<h2 style="margin-top:28px">Widget'lar</h2>
		<p>Elementor'da <strong>İşletmeBurada</strong> başlığı altında bulunur.</p>
		<table class="widefat striped" style="max-width:800px">
			<thead><tr><th>Widget</th><th>Ne gösterir</th></tr></thead>
			<tbody>
				<?php foreach ( self::widget_list() as $name => $description ) : ?>
					<tr><td><strong><?php echo esc_html( $name ); ?></strong></td><td><?php echo esc_html( $description ); ?></td></tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:20px">
			<input type="hidden" name="action" value="ibp_flush_ranking">
			<?php wp_nonce_field( 'ibp_flush_ranking' ); ?>
			<?php submit_button( 'Şehrin Sahipleri sıralamasını yenile', 'secondary', 'submit', false ); ?>
			<span class="description" style="margin-left:8px">Sıralama saatte bir kendiliğinden yenilenir.</span>
		</form>
		<?php
	}

	public static function widget_list() {
		return array(
			'Panel Logosu'       => 'Sol menünün tepesindeki logo ve site adı.',
			'İşletme Kartı'      => 'Logo, işletme adı, şehir ve kategori; birden fazla işletmede seçme kutusu.',
			'Panel Menüsü'       => 'Gruplu sol menü, ikonlar, rozetler, aktif sayfa vurgusu, "Yakında" öğeleri.',
			'Profil Durumu'      => 'Yayın durumu, doluluk çubuğu ve "Profili önizle".',
			'Üst Bar'            => 'Sayfa başlığı, ana düğme, bildirim zili, kullanıcı menüsü; mobilde menü düğmesi.',
			'Karşılama'          => 'Selamlama, tarih, bugünün çalışma saatleri ve 7/30/90 gün seçici.',
			'İstatistik Kartı'   => 'Görüntüleme, arama, yol tarifi, web sitesi tıklaması, puan, yorum sayısı; önceki döneme göre değişim.',
			'Grafik'             => 'Seçilen verinin günlük çubuk grafiği.',
			'Şehrin Sahipleri'   => 'Aynı şehir ve kategorideki işletmeler arasında sıralama.',
			'Bekleyen İşler'     => 'Onay durumu, yanıt bekleyen yorumlar, eksik alanlar, fotoğraf önerisi.',
			'Son Yorumlar'       => 'Son Voxel yorumları: kişi, yıldız, tarih, metin.',
			'Profil Doluluğu'    => '13 alandan kaçının dolu olduğu ve eksikler.',
			'Etkileşim Dağılımı' => 'Görüntüleyenlerin ne kadarının aradığı, yol tarifi aldığı ya da siteye gittiği.',
		);
	}

	private static function render_settings() {
		$s = IBP_Settings::all();
		$n = IBP_Settings::OPTION;
		?>
		<form method="post" action="options.php">
			<?php settings_fields( 'ibp_settings_group' ); ?>
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
			</table>
			<?php submit_button( 'Kaydet' ); ?>
		</form>
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
			<input type="hidden" name="page" value="<?php echo esc_attr( self::SLUG ); ?>">
			<input type="hidden" name="tab" value="debug">
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
		if ( ! $business ) {
			echo '<p class="description">Bir işletme seç; Voxel verisinin nasıl okunduğu, sayaç, sıralama ve bekleyen işler burada görünür. Bir şey yanlış görünürse bu metni kopyalayıp gönder.</p>';
			return;
		}
		echo '<pre style="margin-top:16px;padding:16px;background:#1C1E21;color:#E6E8EB;font:12px/1.5 ui-monospace,Menlo,monospace;white-space:pre-wrap;border-radius:8px;max-height:70vh;overflow:auto">';
		echo esc_html( IBP_Debug::json( IBP_Debug::report( $business ) ) );
		echo '</pre>';
	}
}
