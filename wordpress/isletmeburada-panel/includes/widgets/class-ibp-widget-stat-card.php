<?php
/**
 * Üst sıradaki sayı kartları: etiket, büyük sayı ve alt not.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;

class IBP_Widget_Stat_Card extends IBP_Widget_Base {

	public function get_name() {
		return 'ibp-stat-card';
	}

	public function get_title() {
		return 'İstatistik Kartı';
	}

	public function get_icon() {
		return 'eicon-counter';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'İçerik' ) );
		$this->add_control(
			'label',
			array(
				'label'   => 'Etiket',
				'type'    => Controls_Manager::TEXT,
				'default' => 'Ortalama puan',
			)
		);
		$this->add_control(
			'source',
			array(
				'label'   => 'Değer',
				'type'    => Controls_Manager::SELECT,
				'default' => 'rating',
				'options' => array(
					'rating'  => 'Ortalama puan (Voxel yorumları)',
					'reviews' => 'Yorum sayısı (Voxel yorumları)',
					'photos'  => 'Galerideki fotoğraf sayısı',
					'manual'  => 'Elle yazılan değer',
				),
			)
		);
		$this->add_control(
			'scale',
			array(
				'label'     => 'Puan ölçeği',
				'type'      => Controls_Manager::SELECT,
				'default'   => '5',
				'options'   => array(
					'5'  => '5 üzerinden',
					'10' => '10 üzerinden',
				),
				'condition' => array( 'source' => 'rating' ),
			)
		);
		$this->add_control(
			'manual_value',
			array(
				'label'     => 'Değer',
				'type'      => Controls_Manager::TEXT,
				'default'   => '0',
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'source' => 'manual' ),
			)
		);
		$this->add_control(
			'change',
			array(
				'label'       => 'Değişim',
				'description' => 'Ör. +%11. Boş bırakılabilir.',
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'dynamic'     => array( 'active' => true ),
			)
		);
		$this->add_control(
			'change_tone',
			array(
				'label'   => 'Değişimin rengi',
				'type'    => Controls_Manager::SELECT,
				'default' => 'up',
				'options' => array(
					'up'      => 'Yeşil (artış)',
					'down'    => 'Kırmızı (düşüş)',
					'neutral' => 'Gri',
				),
			)
		);
		$this->add_control(
			'note',
			array(
				'label'   => 'Alt not',
				'type'    => Controls_Manager::TEXT,
				'default' => '',
				'dynamic' => array( 'active' => true ),
			)
		);
		$this->end_controls_section();

		$this->add_accent_control();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$business = IBP_Business::current();

		if ( ! $business && 'manual' !== $settings['source'] ) {
			$this->render_empty();
			return;
		}

		$value = $this->value( $settings, $business );
		?>
		<div class="ibp ibp-card ibp-stat">
			<div class="ibp-stat__label"><?php echo esc_html( $settings['label'] ); ?></div>
			<div class="ibp-stat__value"><?php echo esc_html( $value ); ?></div>
			<?php if ( '' !== $settings['change'] || '' !== $settings['note'] ) : ?>
				<div class="ibp-stat__note">
					<?php if ( '' !== $settings['change'] ) : ?>
						<span class="ibp-tone--<?php echo esc_attr( $settings['change_tone'] ); ?>"><?php echo esc_html( $settings['change'] ); ?></span>
					<?php endif; ?>
					<?php echo esc_html( $settings['note'] ); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	private function value( array $settings, $business ) {
		switch ( $settings['source'] ) {
			case 'rating':
				$average = $business->review_stats()['average'];
				if ( null === $average ) {
					return '–';
				}
				return number_format_i18n( '10' === $settings['scale'] ? $average * 2 : $average, 1 );
			case 'reviews':
				return number_format_i18n( $business->review_stats()['count'] );
			case 'photos':
				return number_format_i18n( count( IBP_Business::attachment_ids( $business->field( 'gallery' ) ) ) );
			default:
				return (string) $settings['manual_value'];
		}
	}
}
