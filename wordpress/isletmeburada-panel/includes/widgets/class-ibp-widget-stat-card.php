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
				'default' => 'Profil görüntüleme',
			)
		);
		$this->add_control(
			'source',
			array(
				'label'   => 'Değer',
				'type'    => Controls_Manager::SELECT,
				'default' => 'view',
				'options' => array(
					'view'       => 'Profil görüntüleme (sayaç)',
					'phone'      => 'Telefon araması (sayaç)',
					'directions' => 'Yol tarifi (sayaç)',
					'website'    => 'Web sitesi tıklaması (sayaç)',
					'email'      => 'E-posta tıklaması (sayaç)',
					'contacts'   => 'Tüm iletişim tıklamaları (sayaç)',
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
				'description' => 'Sayaç verilerinde önceki döneme göre değişim kendiliğinden hesaplanır; buraya yazarsan onun yerine bu gösterilir.',
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
				'default' => 'önceki döneme göre',
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

		$value  = $this->value( $settings, $business );
		$change = (string) $settings['change'];
		$tone   = $settings['change_tone'];
		$note   = (string) $settings['note'];

		$tracked = in_array( $settings['source'], array_keys( IBP_Tracker::metrics() ), true );
		if ( $tracked && '' === $change ) {
			$summary = IBP_Tracker::summary( $business->id, IBP_Tracker::metric_types( $settings['source'] ), IBP_Tracker::period() );
			$change  = IBP_Tracker::format_change( $summary['change'] );
			$tone    = null === $summary['change'] ? 'neutral' : ( $summary['change'] < 0 ? 'down' : 'up' );
			if ( null === $summary['change'] ) {
				$note = 'Önceki dönemde veri yok';
			}
		} elseif ( ! $tracked && '' === $change && 'önceki döneme göre' === $note ) {
			$note = ''; // Karşılaştırması olmayan değerlerde varsayılan notu gösterme.
		}
		?>
		<div class="ibp ibp-card ibp-stat">
			<div class="ibp-stat__label"><?php echo esc_html( $settings['label'] ); ?></div>
			<div class="ibp-stat__value"><?php echo esc_html( $value ); ?></div>
			<?php if ( '' !== $change || '' !== $note ) : ?>
				<div class="ibp-stat__note">
					<?php if ( '' !== $change ) : ?>
						<span class="ibp-tone--<?php echo esc_attr( $tone ); ?>"><?php echo esc_html( $change ); ?></span>
					<?php endif; ?>
					<?php echo esc_html( $note ); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	private function value( array $settings, $business ) {
		if ( isset( IBP_Tracker::metrics()[ $settings['source'] ] ) ) {
			$types = IBP_Tracker::metric_types( $settings['source'] );
			return number_format_i18n( IBP_Tracker::summary( $business->id, $types, IBP_Tracker::period() )['total'] );
		}
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
