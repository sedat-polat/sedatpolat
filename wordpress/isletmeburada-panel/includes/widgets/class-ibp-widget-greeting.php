<?php
/**
 * "İyi günler, Mert." başlığı ve bugünün çalışma saatleri.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;

class IBP_Widget_Greeting extends IBP_Widget_Base {

	public function get_name() {
		return 'ibp-greeting';
	}

	public function get_title() {
		return 'Karşılama';
	}

	public function get_icon() {
		return 'eicon-heading';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'İçerik' ) );
		$this->add_control(
			'show_hours',
			array(
				'label'   => 'Bugünün çalışma saatlerini göster',
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);
		$this->add_control(
			'extra',
			array(
				'label'       => 'Ek cümle',
				'description' => 'Alt satırın sonuna eklenir. Boş bırakılabilir.',
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
			)
		);
		$this->end_controls_section();

		$this->add_accent_control();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$now      = new DateTimeImmutable( 'now', wp_timezone() );
		$hour     = (int) $now->format( 'G' );
		$greet    = $hour < 12 ? 'Günaydın' : ( $hour < 18 ? 'İyi günler' : 'İyi akşamlar' );

		$user       = wp_get_current_user();
		$first_name = $user->exists() ? ( $user->first_name ?: strtok( $user->display_name, ' ' ) ) : '';

		$line = wp_date( 'j F l', $now->getTimestamp() ) . '.';

		$business = IBP_Business::current();
		if ( $business && 'yes' === $settings['show_hours'] ) {
			$sentence = IBP_Work_Hours::sentence( $business->today_hours() );
			if ( $sentence ) {
				$line .= ' ' . $sentence;
			}
		}
		if ( ! empty( $settings['extra'] ) ) {
			$line .= ' ' . $settings['extra'];
		}
		?>
		<div class="ibp ibp-greet">
			<div class="ibp-greet__title"><?php echo esc_html( $greet . ( $first_name ? ', ' . $first_name : '' ) . '.' ); ?></div>
			<div class="ibp-greet__sub"><?php echo esc_html( $line ); ?></div>
		</div>
		<?php
	}
}
