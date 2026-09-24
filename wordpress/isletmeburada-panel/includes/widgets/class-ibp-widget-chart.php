<?php
/**
 * Günlük çubuk grafik (profil görüntüleme, arama, yol tarifi…).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;

class IBP_Widget_Chart extends IBP_Widget_Base {

	public function get_name() {
		return 'ibp-chart';
	}

	public function get_title() {
		return 'Grafik';
	}

	public function get_icon() {
		return 'eicon-skill-bar';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'İçerik' ) );
		$this->add_control(
			'metric',
			array(
				'label'   => 'Veri',
				'type'    => Controls_Manager::SELECT,
				'default' => 'view',
				'options' => IBP_Tracker::metrics(),
			)
		);
		$this->add_control(
			'title',
			array(
				'label'   => 'Başlık',
				'type'    => Controls_Manager::TEXT,
				'default' => 'Profil görüntülemeleri',
			)
		);
		$this->add_control(
			'button_text',
			array(
				'label'   => 'Düğme yazısı',
				'type'    => Controls_Manager::TEXT,
				'default' => 'Detaylı istatistik',
			)
		);
		$this->add_control(
			'button_url',
			array(
				'label'       => 'Düğme adresi',
				'description' => 'Boş bırakılırsa düğme gizlenir.',
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
			)
		);
		$this->add_responsive_control(
			'height',
			array(
				'label'      => 'Grafik yüksekliği',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 100, 'max' => 400 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 220 ),
				'mobile_default' => array( 'unit' => 'px', 'size' => 160 ),
				'selectors'  => array( '{{WRAPPER}} .ibp-bars' => 'height: {{SIZE}}{{UNIT}};' ),
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
		$series   = IBP_Tracker::daily( $business->id, IBP_Tracker::metric_types( $settings['metric'] ), $days );
		$values   = array_values( $series );
		$dates    = array_keys( $series );
		$max      = max( 1, max( $values ) );
		$total    = array_sum( $values );
		$last     = count( $values ) - 1;
		$label    = IBP_Tracker::metrics()[ $settings['metric'] ] ?? '';

		// Eksende 5 tarih: baş, son ve aradaki eşit aralıklar.
		$ticks = array();
		for ( $k = 0; $k < 5; $k++ ) {
			$ticks[] = wp_date( 'j M', strtotime( $dates[ (int) round( $last * $k / 4 ) ] ) );
		}
		?>
		<div class="ibp ibp-card">
			<div class="ibp-card__head">
				<div>
					<h2 class="ibp-card__title"><?php echo esc_html( $settings['title'] ); ?></h2>
					<div class="ibp-card__sub"><?php echo esc_html( sprintf( 'Son %d gün, toplam %s', $days, number_format_i18n( $total ) ) ); ?></div>
				</div>
				<?php if ( '' !== $settings['button_url'] && '' !== $settings['button_text'] ) : ?>
					<a class="ibp-btn ibp-btn--sm" href="<?php echo esc_url( $business->resolve_url( $settings['button_url'] ) ); ?>"><?php echo esc_html( $settings['button_text'] ); ?></a>
				<?php endif; ?>
			</div>
			<div class="ibp-card__body">
				<div class="ibp-bars<?php echo $days > 30 ? ' ibp-bars--dense' : ''; ?>" role="img" aria-label="<?php echo esc_attr( sprintf( '%s, son %d gün, toplam %s', $label, $days, number_format_i18n( $total ) ) ); ?>">
					<?php foreach ( $values as $i => $value ) : ?>
						<div class="ibp-bars__col" title="<?php echo esc_attr( wp_date( 'j F', strtotime( $dates[ $i ] ) ) . ': ' . number_format_i18n( $value ) ); ?>">
							<i class="<?php echo $i === $last ? 'is-today' : ''; ?>" style="height: <?php echo esc_attr( $value ? max( 2, round( 100 * $value / $max, 1 ) ) : 0 ); ?>%"></i>
						</div>
					<?php endforeach; ?>
				</div>
				<div class="ibp-bars__ticks">
					<?php foreach ( $ticks as $index => $tick ) : ?>
						<span class="<?php echo in_array( $index, array( 1, 3 ), true ) ? 'ibp-hide-mobile' : ''; ?>"><?php echo esc_html( $tick ); ?></span>
					<?php endforeach; ?>
				</div>
				<?php if ( 0 === $total ) : ?>
					<div class="ibp-card__note">Henüz veri yok. Sayaç çalışıyor; işletme sayfası ziyaret edildikçe grafik dolacak.</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
