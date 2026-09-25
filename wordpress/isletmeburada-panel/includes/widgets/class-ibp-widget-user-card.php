<?php
/**
 * Bireysel panel: sol menüdeki kullanıcı kartı (avatar, ad, yerel rehber seviyesi).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;

class IBP_Widget_User_Card extends IBP_Widget_Base {

	public function get_name() {
		return 'ibp-user-card';
	}

	public function get_title() {
		return 'Kullanıcı Kartı';
	}

	public function get_icon() {
		return 'eicon-person';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'İçerik' ) );
		$this->add_control(
			'show_level',
			array(
				'label'   => 'Yerel rehber seviyesini göster',
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);
		$this->end_controls_section();

		$this->add_accent_control();
	}

	protected function render() {
		$me = IBP_User::current();
		if ( ! $me ) {
			return;
		}
		$settings = $this->get_settings_for_display();
		$guide    = $me->guide();
		$avatar   = $me->avatar_url();
		?>
		<div class="ibp ibp-card ibp-biz ibp-ucard">
			<div class="ibp-biz__row" title="<?php echo esc_attr( $me->name() ); ?>">
				<?php if ( $avatar ) : ?>
					<img class="ibp-biz__logo ibp-ucard__av" src="<?php echo esc_url( $avatar ); ?>" alt="">
				<?php else : ?>
					<span class="ibp-biz__logo ibp-ucard__av ibp-ucard__av--ini" aria-hidden="true"><?php echo esc_html( $me->initials() ); ?></span>
				<?php endif; ?>
				<div class="ibp-biz__text">
					<div class="ibp-biz__name"><?php echo esc_html( $me->name() ); ?></div>
					<?php if ( 'yes' === $settings['show_level'] ) : ?>
						<div class="ibp-biz__sub"><?php echo esc_html( sprintf( 'Yerel rehber %d, %s puan', $guide['level'], number_format_i18n( $guide['points'] ) ) ); ?></div>
					<?php endif; ?>
				</div>
			</div>
			<?php if ( 'yes' === $settings['show_level'] ) : ?>
				<div class="ibp-ucard__level">
					<div class="ibp-bar ibp-bar--thin" role="progressbar" aria-valuenow="<?php echo esc_attr( $guide['progress'] ); ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Sonraki seviyeye ilerleme"><i style="width: <?php echo esc_attr( $guide['progress'] ); ?>%"></i></div>
					<div class="ibp-ucard__next"><?php echo esc_html( sprintf( 'Sonraki seviyeye %s puan', number_format_i18n( $guide['to_next'] ) ) ); ?></div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}
