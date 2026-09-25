<?php
/**
 * Profil doluluğu kartı: yüzde, çubuk ve eksik alanlar.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;

class IBP_Widget_Completeness extends IBP_Widget_Base {

	public function get_name() {
		return 'ibp-completeness';
	}

	public function get_title() {
		return 'Profil Doluluğu';
	}

	public function get_icon() {
		return 'eicon-skill-bar';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'İçerik' ) );
		$this->add_control(
			'title',
			array(
				'label'   => 'Başlık',
				'type'    => Controls_Manager::TEXT,
				'default' => 'Profil doluluğu',
			)
		);
		$this->add_control(
			'max_missing',
			array(
				'label'   => 'Gösterilecek eksik alan sayısı',
				'type'    => Controls_Manager::NUMBER,
				'min'     => 0,
				'max'     => 13,
				'default' => 3,
			)
		);
		$this->add_control(
			'edit_url',
			array(
				'label'       => 'Düzenleme sayfası adresi',
				'description' => 'Boş bırakılırsa Voxel\'in düzenleme bağlantısı kullanılır. {id} yazdığın yere işletme ID\'si gelir.',
				'type'        => Controls_Manager::TEXT,
				'placeholder' => '/isletme-ekle/?post_id={id}',
				'default'     => '',
			)
		);
		$this->add_control(
			'full_text',
			array(
				'label'   => 'Profil tamken gösterilecek yazı',
				'type'    => Controls_Manager::TEXT,
				'default' => 'Profilin eksiksiz görünüyor.',
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
		if ( IBP_Business::scope()['brand'] ) {
			$this->render_brand( $settings );
			return;
		}
		$result   = $business->completeness();
		$missing  = array_slice( $result['missing'], 0, max( 0, (int) $settings['max_missing'] ) );
		$edit_url = $business->edit_url( trim( (string) $settings['edit_url'] ) );
		?>
		<div class="ibp ibp-card">
			<div class="ibp-card__head">
				<h2 class="ibp-card__title"><?php echo esc_html( $settings['title'] ); ?></h2>
				<span class="ibp-comp__pct">%<?php echo esc_html( $result['percent'] ); ?></span>
			</div>
			<div class="ibp-card__body ibp-stack">
				<div class="ibp-bar" role="progressbar" aria-valuenow="<?php echo esc_attr( $result['percent'] ); ?>" aria-valuemin="0" aria-valuemax="100" aria-label="<?php echo esc_attr( $settings['title'] ); ?>">
					<i style="width: <?php echo esc_attr( $result['percent'] ); ?>%"></i>
				</div>
				<?php foreach ( $missing as $label ) : ?>
					<a class="ibp-opt" href="<?php echo esc_url( $edit_url ); ?>">
						<svg class="ibp-i ibp-i--sm" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>
						<span><?php echo esc_html( $label ); ?> ekle</span>
					</a>
				<?php endforeach; ?>
				<?php if ( ! $result['missing'] ) : ?>
					<div class="ibp-comp__full"><?php echo esc_html( $settings['full_text'] ); ?></div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Marka görünümü: ortalama doluluk ve en eksik işletmeler.
	 */
	private function render_brand( array $settings ) {
		$rows = array();
		foreach ( IBP_Business::scope()['ids'] as $id ) {
			$item = IBP_Business::from_id( $id );
			if ( $item ) {
				$rows[] = array( 'id' => $id, 'name' => $item->name(), 'percent' => $item->completeness()['percent'] );
			}
		}
		usort(
			$rows,
			function ( $a, $b ) {
				return $a['percent'] <=> $b['percent'];
			}
		);
		$average = $rows ? (int) round( array_sum( array_column( $rows, 'percent' ) ) / count( $rows ) ) : 0;
		$lowest  = array_filter(
			array_slice( $rows, 0, max( 1, (int) $settings['max_missing'] ) ),
			function ( $row ) {
				return $row['percent'] < 100;
			}
		);
		?>
		<div class="ibp ibp-card">
			<div class="ibp-card__head">
				<h2 class="ibp-card__title"><?php echo esc_html( $settings['title'] ); ?></h2>
				<span class="ibp-comp__pct">ort. %<?php echo esc_html( $average ); ?></span>
			</div>
			<div class="ibp-card__body ibp-stack">
				<div class="ibp-bar" role="progressbar" aria-valuenow="<?php echo esc_attr( $average ); ?>" aria-valuemin="0" aria-valuemax="100" aria-label="<?php echo esc_attr( $settings['title'] ); ?>"><i style="width: <?php echo esc_attr( $average ); ?>%"></i></div>
				<?php foreach ( $lowest as $row ) : ?>
					<a class="ibp-opt" href="<?php echo esc_url( IBP_Business::switch_url( $row['id'] ) ); ?>">
						<span class="ibp-opt__main"><?php echo esc_html( $row['name'] ); ?></span>
						<span class="ibp-opt__meta">%<?php echo esc_html( $row['percent'] ); ?></span>
					</a>
				<?php endforeach; ?>
				<?php if ( ! $lowest ) : ?>
					<div class="ibp-comp__full">Tüm bağlı işletmelerin profili eksiksiz.</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
