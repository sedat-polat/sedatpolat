<?php
/**
 * Üst bar: mobil menü düğmesi, sayfa başlığı, "Yeni oluştur", bildirim ve kullanıcı menüsü.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;

class IBP_Widget_Topbar extends IBP_Widget_Base {

	public function get_name() {
		return 'ibp-topbar';
	}

	public function get_title() {
		return 'Üst Bar';
	}

	public function get_icon() {
		return 'eicon-header';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'Başlık' ) );
		$this->add_control(
			'title',
			array(
				'label'   => 'Başlık',
				'type'    => Controls_Manager::TEXT,
				'default' => 'Genel bakış',
				'dynamic' => array( 'active' => true ),
			)
		);
		$this->add_control(
			'subtitle',
			array(
				'label'   => 'Alt başlık',
				'type'    => Controls_Manager::TEXT,
				'default' => 'İşletmenin bu dönemki durumu ve bekleyen işler.',
				'dynamic' => array( 'active' => true ),
			)
		);
		$this->add_control(
			'sticky',
			array(
				'label'        => 'Kaydırınca üstte sabit kalsın',
				'type'         => Controls_Manager::SELECT,
				'default'      => 'yes',
				'options'      => array(
					'yes' => 'Evet',
					'no'  => 'Hayır',
				),
				// Widget kutusuna ibp-sticky-yes / ibp-sticky-no sınıfı ekler.
				'prefix_class' => 'ibp-sticky-',
			)
		);
		$this->end_controls_section();

		$this->start_controls_section( 'actions', array( 'label' => 'Düğmeler' ) );
		$this->add_control(
			'button_text',
			array(
				'label'       => 'Ana düğme yazısı',
				'description' => 'Boş bırakılırsa düğme gizlenir.',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Profili düzenle',
			)
		);
		$this->add_control(
			'button_url',
			array(
				'label'       => 'Ana düğme adresi',
				'description' => 'Kısayollar: {edit}, {view}, {id}.',
				'type'        => Controls_Manager::TEXT,
				'default'     => '{edit}',
			)
		);
		$this->add_control(
			'bell_url',
			array(
				'label'       => 'Bildirimler sayfası',
				'description' => 'Boş bırakılırsa zil gizlenir. Voxel\'in bildirim sayfasının adresini yazabilirsin.',
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'separator'   => 'before',
			)
		);
		$this->add_control(
			'bell_badge',
			array(
				'label'   => 'Zil rozeti',
				'type'    => Controls_Manager::SELECT,
				'default' => 'todos',
				'options' => array(
					'none'  => 'Yok',
					'todos' => 'Bekleyen iş sayısı',
				),
			)
		);
		$this->add_control(
			'profile_url',
			array(
				'label'     => 'Kullanıcı menüsü: Hesabım adresi',
				'type'      => Controls_Manager::TEXT,
				'default'   => '',
				'separator' => 'before',
			)
		);
		$this->add_control(
			'show_logout',
			array(
				'label'   => 'Çıkış bağlantısı',
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);
		$this->end_controls_section();

		$this->add_accent_control();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$business = IBP_Business::current();
		$user     = wp_get_current_user();
		$button   = $business ? $business->resolve_url( $settings['button_url'] ) : $settings['button_url'];
		$count    = ( $business && 'todos' === $settings['bell_badge'] ) ? count( IBP_Business::scope_todos() ) : 0;
		$menu_id  = 'ibp-user-' . $this->get_id();

		$words    = preg_split( '/\s+/u', trim( $user->exists() ? $user->display_name : '' ), -1, PREG_SPLIT_NO_EMPTY );
		$initials = '';
		foreach ( array_slice( $words, 0, 2 ) as $word ) {
			$initials .= mb_substr( $word, 0, 1 );
		}
		?>
		<?php if ( ! IBP_Business::is_editor_preview() ) : ?>
			<script>try{if(localStorage.getItem('ibp:sb')==='1'&&innerWidth>1024)document.body.classList.add('ibp-sb-collapsed')}catch(e){}</script>
		<?php endif; ?>
		<header class="ibp ibp-topbar">
			<button type="button" class="ibp-icb ibp-topbar__menu" data-ibp-nav-toggle aria-label="Menüyü daralt" aria-expanded="true">
				<?php echo IBP_Icons::svg( 'sidebar' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</button>
			<div class="ibp-topbar__titles">
				<h1 class="ibp-topbar__title"><?php echo esc_html( $settings['title'] ); ?></h1>
				<?php if ( '' !== $settings['subtitle'] ) : ?>
					<div class="ibp-topbar__sub"><?php echo esc_html( $settings['subtitle'] ); ?></div>
				<?php endif; ?>
			</div>

			<?php if ( '' !== $settings['button_text'] && $business ) : ?>
				<a class="ibp-btn ibp-btn--primary ibp-topbar__cta" href="<?php echo esc_url( $button ); ?>">
					<?php echo IBP_Icons::svg( 'plus' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<span><?php echo esc_html( $settings['button_text'] ); ?></span>
				</a>
			<?php endif; ?>

			<?php if ( '' !== $settings['bell_url'] ) : ?>
				<a class="ibp-icb" href="<?php echo esc_url( $settings['bell_url'] ); ?>" aria-label="Bildirimler">
					<?php echo IBP_Icons::svg( 'bell' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php if ( $count ) : ?>
						<span class="ibp-icb__dot"><?php echo esc_html( $count ); ?></span>
					<?php endif; ?>
				</a>
			<?php endif; ?>

			<?php if ( $user->exists() ) : ?>
				<div class="ibp-topbar__user">
					<button type="button" class="ibp-topbar__avatar" data-ibp-dropdown="<?php echo esc_attr( $menu_id ); ?>" aria-expanded="false" aria-controls="<?php echo esc_attr( $menu_id ); ?>" aria-label="Kullanıcı menüsü">
						<?php echo esc_html( IBP_Business::upper_tr( $initials ) ); ?>
					</button>
					<div class="ibp-dd" id="<?php echo esc_attr( $menu_id ); ?>" hidden>
						<div class="ibp-dd__head">
							<strong><?php echo esc_html( $user->display_name ); ?></strong>
							<span><?php echo esc_html( $user->user_email ); ?></span>
						</div>
						<?php if ( $business ) : ?>
							<a class="ibp-dd__item" href="<?php echo esc_url( $business->permalink() ); ?>"><?php echo IBP_Icons::svg( 'eye' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>İşletme sayfam</a>
						<?php endif; ?>
						<?php foreach ( array( '{business_panel}' => array( 'store', 'İşletme panelim' ), '{personal_panel}' => array( 'person', 'Bireysel panelim' ) ) as $token => $link ) : ?>
							<?php $panel = IBP_Page_Builder::resolve_panel_url( $token ); ?>
							<?php if ( $panel && untrailingslashit( (string) wp_parse_url( $panel, PHP_URL_PATH ) ) !== untrailingslashit( (string) wp_parse_url( add_query_arg( array() ), PHP_URL_PATH ) ) ) : ?>
								<a class="ibp-dd__item" href="<?php echo esc_url( $panel ); ?>"><?php echo IBP_Icons::svg( $link[0] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $link[1] ); ?></a>
							<?php endif; ?>
						<?php endforeach; ?>
						<?php if ( '' !== $settings['profile_url'] ) : ?>
							<a class="ibp-dd__item" href="<?php echo esc_url( $settings['profile_url'] ); ?>"><?php echo IBP_Icons::svg( 'person' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>Hesabım</a>
						<?php endif; ?>
						<?php if ( 'yes' === $settings['show_logout'] ) : ?>
							<a class="ibp-dd__item" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php echo IBP_Icons::svg( 'logout' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>Çıkış yap</a>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>
		</header>
		<?php
	}
}
