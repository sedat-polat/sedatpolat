<?php
/**
 * Sol menüdeki işletme kartı: logo, isim, şehir/kategori ve işletme seçme kutusu.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;

class IBP_Widget_Business_Card extends IBP_Widget_Base {

	public function get_name() {
		return 'ibp-business-card';
	}

	public function get_title() {
		return 'İşletme Kartı';
	}

	public function get_icon() {
		return 'eicon-person';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'İçerik' ) );
		$this->add_control(
			'show_logo',
			array(
				'label'       => 'Logoyu göster',
				'description' => 'Kapalıysa ya da logo yoksa baş harfler gösterilir.',
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);
		$this->add_control(
			'show_switcher',
			array(
				'label'       => 'İşletme seçme kutusu',
				'description' => 'Kullanıcının birden fazla işletmesi varsa görünür.',
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);
		$this->end_controls_section();

		$this->add_accent_control();
	}

	protected function render() {
		$business = IBP_Business::current();
		if ( ! $business ) {
			$this->render_empty();
			return;
		}

		$settings = $this->get_settings_for_display();
		$logo     = 'yes' === $settings['show_logo'] ? $business->logo_url() : '';
		$subtitle = $business->subtitle();
		$owned    = IBP_Business::owned_ids();
		?>
		<div class="ibp ibp-card ibp-biz">
			<div class="ibp-biz__row" title="<?php echo esc_attr( $business->name() ); ?>">
				<?php if ( $logo ) : ?>
					<img class="ibp-biz__logo" src="<?php echo esc_url( $logo ); ?>" alt="">
				<?php else : ?>
					<span class="ibp-biz__logo ibp-biz__logo--ini" aria-hidden="true"><?php echo esc_html( $business->initials() ); ?></span>
				<?php endif; ?>
				<div class="ibp-biz__text">
					<div class="ibp-biz__name"><?php echo esc_html( $business->name() ); ?></div>
					<?php if ( $subtitle ) : ?>
						<div class="ibp-biz__sub"><?php echo esc_html( $subtitle ); ?></div>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( 'yes' === $settings['show_switcher'] && count( $owned ) > 1 ) : ?>
				<form class="ibp-biz__switch" method="get">
					<?php foreach ( $this->kept_query_args() as $key => $value ) : ?>
						<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>">
					<?php endforeach; ?>
					<input type="hidden" name="_ibpnonce" value="<?php echo esc_attr( wp_create_nonce( IBP_Business::NONCE ) ); ?>">
					<label class="ibp-sr" for="ibp-switch-<?php echo esc_attr( $this->get_id() ); ?>">İşletme seç</label>
					<select id="ibp-switch-<?php echo esc_attr( $this->get_id() ); ?>" class="ibp-select" name="ibp_isletme" onchange="this.form.submit()">
						<?php foreach ( $owned as $id ) : ?>
							<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $id, $business->id ); ?>><?php echo esc_html( get_the_title( $id ) ); ?></option>
						<?php endforeach; ?>
					</select>
					<noscript><button type="submit" class="ibp-btn ibp-btn--sm">Değiştir</button></noscript>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Sayfadaki diğer sorgu parametrelerini korur (ör. Voxel sekme parametreleri).
	 */
	private function kept_query_args() {
		$args = array();
		foreach ( $_GET as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( is_string( $value ) && ! in_array( $key, array( 'ibp_isletme', '_ibpnonce' ), true ) ) {
				$args[ sanitize_key( $key ) ] = sanitize_text_field( wp_unslash( $value ) );
			}
		}
		return $args;
	}
}
