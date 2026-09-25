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
		$scope     = IBP_Business::scope();
		$subtitle  = $scope['brand'] ? sprintf( 'Marka · %d bağlı işletme', count( $scope['children'] ) ) : $business->subtitle();
		$link      = $scope['brand'] ? null : IBP_Network::link_of( $business->id );
		if ( $link && 'approved' === $link['status'] ) {
			$subtitle = trim( $subtitle . ' · ' . get_the_title( $link['parent'] ) . ' ' . IBP_Network::possessive( $link['type'] ), ' ·' );
		}
		$options   = IBP_Business::switch_options();
		$selected  = $scope['brand'] ? 'brand:' . $business->id : (string) $business->id;
		$in_editor = IBP_Business::is_editor_preview();
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

			<?php if ( 'yes' === $settings['show_switcher'] && count( $options ) > 1 ) : ?>
				<form class="ibp-biz__switch" method="get"<?php echo $in_editor ? ' onsubmit="return false"' : ''; ?>>
					<?php foreach ( $this->kept_query_args() as $key => $value ) : ?>
						<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>">
					<?php endforeach; ?>
					<input type="hidden" name="_ibpnonce" value="<?php echo esc_attr( wp_create_nonce( IBP_Business::NONCE ) ); ?>">
					<label class="ibp-sr" for="ibp-switch-<?php echo esc_attr( $this->get_id() ); ?>">İşletme seç</label>
					<?php // Elementor önizlemesinde kutu sayfayı değiştirmesin; yoksa önizleme düzenleyiciden kopar. ?>
					<select id="ibp-switch-<?php echo esc_attr( $this->get_id() ); ?>" class="ibp-select" name="ibp_isletme" <?php echo $in_editor ? 'disabled title="Düzenleyicide işletme değiştirilemez"' : 'onchange="this.form.submit()"'; ?>>
						<?php $group = null; ?>
						<?php foreach ( $options as $option ) : ?>
							<?php if ( $option['group'] !== $group ) : ?>
								<?php echo null !== $group && '' !== $group ? '</optgroup>' : ''; ?>
								<?php $group = $option['group']; ?>
								<?php if ( '' !== $group ) : ?>
									<optgroup label="<?php echo esc_attr( $group ); ?>">
								<?php endif; ?>
							<?php endif; ?>
							<option value="<?php echo esc_attr( $option['value'] ); ?>" <?php selected( $option['value'], $selected ); ?>><?php echo esc_html( $option['label'] ); ?></option>
						<?php endforeach; ?>
						<?php echo null !== $group && '' !== $group ? '</optgroup>' : ''; ?>
					</select>
					<?php if ( ! $in_editor ) : ?>
						<noscript><button type="submit" class="ibp-btn ibp-btn--sm">Değiştir</button></noscript>
					<?php endif; ?>
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
