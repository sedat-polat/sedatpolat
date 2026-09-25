<?php
/**
 * Etkileşim dağılımı: görüntülemeden telefon, yol tarifi, web sitesi ve e-postaya.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;

class IBP_Widget_Funnel extends IBP_Widget_Base {

	public function get_name() {
		return 'ibp-funnel';
	}

	public function get_title() {
		return 'Etkileşim Dağılımı';
	}

	public function get_icon() {
		return 'eicon-price-list';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'İçerik' ) );
		$this->add_control(
			'title',
			array(
				'label'   => 'Başlık',
				'type'    => Controls_Manager::TEXT,
				'default' => 'Profilden iletişime',
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
		$days     = IBP_Tracker::period();
		$rows     = array();
		foreach ( IBP_Tracker::TYPES as $type => $label ) {
			$rows[ $type ] = array(
				'label' => $label,
				'total' => IBP_Tracker::summary( IBP_Business::scope()['ids'], $type, $days )['total'],
			);
		}
		$views    = max( 1, $rows['view']['total'] );
		$contacts = $rows['phone']['total'] + $rows['directions']['total'] + $rows['website']['total'] + $rows['email']['total'];
		?>
		<div class="ibp ibp-card">
			<div class="ibp-card__head">
				<div>
					<h2 class="ibp-card__title"><?php echo esc_html( $settings['title'] ); ?></h2>
					<div class="ibp-card__sub">
						<?php
						// Bir ziyaretçi hem arayıp hem yol tarifi alabildiği için oran yerine sayıları yazıyoruz.
						echo esc_html( sprintf( 'Son %d günde %s görüntüleme, %s iletişim tıklaması.', $days, number_format_i18n( $rows['view']['total'] ), number_format_i18n( $contacts ) ) );
						?>
					</div>
				</div>
			</div>
			<div class="ibp-card__body ibp-stack">
				<?php foreach ( $rows as $type => $row ) : ?>
					<?php $percent = 'view' === $type ? 100 : min( 100, round( 100 * $row['total'] / $views, 1 ) ); ?>
					<div class="ibp-funnel__row">
						<div class="ibp-funnel__head">
							<span><?php echo esc_html( $row['label'] ); ?></span>
							<strong><?php echo esc_html( number_format_i18n( $row['total'] ) ); ?></strong>
						</div>
						<div class="ibp-bar"><i style="width: <?php echo esc_attr( $row['total'] ? max( 1, $percent ) : 0 ); ?>%"></i></div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}
}
