<?php
/**
 * Ana sayfa: bir kategorinin şehir sıralaması (en yüksek puanlı / en popüler / yükselenler).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;

class IBP_Home_Ranking extends IBP_Home_Widget_Base {

	public function get_name() {
		return 'ibp-home-ranking';
	}

	public function get_title() {
		return 'Ana Sayfa: Kategori Sıralaması';
	}

	public function get_icon() {
		return 'eicon-number-field';
	}

	protected function register_controls() {
		$options = array( '' => 'En çok işletmesi olan kategori' );
		foreach ( IBP_Home::categories( 40 ) as $category ) {
			$options[ $category['slug'] ] = $category['name'];
		}

		$this->start_controls_section( 'content', array( 'label' => 'İçerik' ) );
		$this->add_control( 'category', array( 'label' => 'Kategori', 'type' => Controls_Manager::SELECT, 'default' => '', 'options' => $options ) );
		$this->add_control( 'eyebrow', array( 'label' => 'Üst yazı', 'type' => Controls_Manager::TEXT, 'default' => '{category} sıralamaları' ) );
		$this->add_control(
			'title',
			array(
				'label'       => 'Başlık',
				'description' => '{city_gen}, {city_loc}, {city}, {category} kullanılabilir.',
				'type'        => Controls_Manager::TEXT,
				'default'     => '{city_gen} en iyi {category} işletmeleri.',
			)
		);
		$this->add_control( 'sub', array( 'label' => 'Alt yazı', 'type' => Controls_Manager::TEXT, 'default' => 'Gerçek kullanıcı yorumları ve ilgisine göre güncellenen sıralama.' ) );
		$this->add_control( 'all_text', array( 'label' => '"Tümü" bağlantı yazısı', 'type' => Controls_Manager::TEXT, 'default' => 'Tümünü gör' ) );
		$this->add_control( 'note', array( 'label' => 'Alt not', 'type' => Controls_Manager::TEXTAREA, 'default' => 'Sıralama, yorum sayısını da hesaba katan ağırlıklı puanla belirlenir. Sponsorlu işletmeler bu sıralamaya dahil edilmez.' ) );
		$this->end_controls_section();

		$this->add_section_style_controls( '#F7F8FA' );
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$city     = IBP_City::current();
		$taxonomy = IBP_Business::taxonomy_by_label( 'Kategori' );
		$category = null;
		foreach ( IBP_Home::categories( 40 ) as $item ) {
			if ( '' === $settings['category'] || $item['slug'] === $settings['category'] ) {
				$category = $item;
				break;
			}
		}
		if ( ! $taxonomy || ! $category ) {
			return;
		}

		$days  = 30;
		$tabs  = array(
			'top'     => array( 'star', 'En yüksek puanlı', $this->top( $taxonomy, $category, $city ) ),
			'popular' => array( 'flame', 'En popüler', $this->popular( $taxonomy, $category, $city, $days ) ),
			'rising'  => array( 'trend', 'Yükselenler', $this->rising( $taxonomy, $category, $city, $days ) ),
		);
		$fill  = function ( $text ) use ( $city, $category ) {
			return str_replace( '{category}', IBP_Business::lower_tr( $category['name'] ), IBP_Home::fill_city( $text, $city['name'] ) );
		};
		$side  = IBP_Home::city_switch( $city );
		if ( '' !== $settings['all_text'] ) {
			$side .= '<a class="ibp-h-action" href="' . esc_url( IBP_Home::search_url( $category['slug'], $city['slug'] ) ) . '">' . esc_html( $settings['all_text'] ) . IBP_Icons::svg( 'arrow' ) . '</a>';
		}
		$id = 'ibp-rank-' . $this->get_id();
		?>
		<section class="ibp ibp-h ibp-h-ranking ibp-reveal" data-ibp-tabs>
			<div class="ibp-h-container">
				<?php IBP_Home::head( IBP_Business::upper_tr( $fill( $settings['eyebrow'] ) ), $fill( $settings['title'] ), $settings['sub'], $side ); ?>

				<div class="ibp-h-tabs" role="tablist" aria-label="Sıralama türü">
					<?php $first = true; ?>
					<?php foreach ( $tabs as $key => $tab ) : ?>
						<button type="button" class="ibp-h-tab<?php echo $first ? ' is-on' : ''; ?>" role="tab" aria-selected="<?php echo $first ? 'true' : 'false'; ?>" data-tab="<?php echo esc_attr( $id . '-' . $key ); ?>"><?php echo IBP_Icons::svg( $tab[0] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $tab[1] ); ?></button>
						<?php $first = false; ?>
					<?php endforeach; ?>
				</div>

				<?php $first = true; ?>
				<?php foreach ( $tabs as $key => $tab ) : ?>
					<div id="<?php echo esc_attr( $id . '-' . $key ); ?>" role="tabpanel"<?php echo $first ? '' : ' hidden'; ?>>
						<?php $this->render_list( $tab[2], $category, $city, $key ); ?>
					</div>
					<?php $first = false; ?>
				<?php endforeach; ?>

				<?php if ( '' !== $settings['note'] ) : ?>
					<p class="ibp-h-note"><?php echo IBP_Icons::svg( 'info' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $settings['note'] ); ?></p>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}

	/* Listeler: her satır id, value (sağdaki büyük değer), small (altındaki). */

	private function top( $taxonomy, array $category, array $city ) {
		$rows = array();
		foreach ( array_slice( IBP_Ranking::board( $taxonomy, $category['term']->term_id, $city['name'] ), 0, 5 ) as $item ) {
			$rows[] = array( 'id' => $item['id'], 'value' => '★ ' . number_format_i18n( $item['average'], 1 ), 'small' => number_format_i18n( $item['count'] ) . ' yorum', 'tone' => '' );
		}
		return $rows;
	}

	private function popular( $taxonomy, array $category, array $city, $days ) {
		$views = array();
		foreach ( array_slice( IBP_Home::businesses_in( $taxonomy, $category['term']->term_id, $city['name'] ), 0, 80 ) as $id ) {
			$total = IBP_Tracker::summary( $id, 'view', $days )['total'];
			if ( $total > 0 ) {
				$views[ $id ] = $total;
			}
		}
		arsort( $views );
		$rows = array();
		foreach ( array_slice( $views, 0, 5, true ) as $id => $total ) {
			$rows[] = array( 'id' => $id, 'value' => number_format_i18n( $total ), 'small' => sprintf( 'son %d gün görüntülenme', $days ), 'tone' => '' );
		}
		return $rows;
	}

	private function rising( $taxonomy, array $category, array $city, $days ) {
		$growth = array();
		foreach ( array_slice( IBP_Home::businesses_in( $taxonomy, $category['term']->term_id, $city['name'] ), 0, 80 ) as $id ) {
			$summary = IBP_Tracker::summary( $id, 'view', $days );
			if ( null !== $summary['change'] && $summary['change'] > 0 && $summary['total'] >= 5 ) {
				$growth[ $id ] = $summary['change'];
			}
		}
		arsort( $growth );
		$rows = array();
		foreach ( array_slice( $growth, 0, 5, true ) as $id => $change ) {
			$rows[] = array( 'id' => $id, 'value' => '+%' . $change, 'small' => 'önceki döneme göre', 'tone' => 'up' );
		}
		return $rows;
	}

	private function render_list( array $rows, array $category, array $city, $kind ) {
		if ( ! $rows ) {
			$empty = array(
				'top'     => 'Bu şehirde bu kategoride henüz yorum almış işletme yok.',
				'popular' => 'Görüntülenme verisi toplanıyor; ziyaretler geldikçe burada en popüler işletmeler görünecek.',
				'rising'  => 'Karşılaştırma için en az iki dönemlik görüntülenme verisi gerekiyor.',
			);
			echo '<div class="ibp-h-empty">' . esc_html( $empty[ $kind ] ) . '</div>';
			return;
		}
		$first    = array_shift( $rows );
		$business = IBP_Business::from_id( $first['id'] );
		$stats    = $business ? $business->review_stats() : array( 'count' => 0, 'average' => null );
		$views    = IBP_Tracker::summary( $first['id'], 'view', 30 )['total'];
		$open     = $business ? $business->today_hours() : null;
		?>
		<div class="ibp-h-rank">
			<div class="ibp-h-rank__top">
				<span class="ibp-h-rank__badge">★ Şehrin 1 numarası</span>
				<span class="ibp-h-rank__num">01</span>
				<h3><?php echo esc_html( get_the_title( $first['id'] ) ); ?></h3>
				<p class="ibp-h-rank__meta"><?php echo esc_html( implode( ' · ', array_filter( array( $category['name'], $business ? implode( ', ', array_filter( array( IBP_Ranking::district( $business ), $city['name'] ) ) ) : '' ) ) ) ); ?></p>
				<div class="ibp-h-rank__chips">
					<?php if ( $stats['count'] ) : ?>
						<span>★ <?php echo esc_html( number_format_i18n( $stats['average'], 1 ) . ' · ' . number_format_i18n( $stats['count'] ) ); ?> yorum</span>
					<?php endif; ?>
					<?php if ( $views ) : ?>
						<span><?php echo esc_html( number_format_i18n( $views ) ); ?> görüntülenme</span>
					<?php endif; ?>
					<?php if ( $open && 'unknown' !== $open['status'] ) : ?>
						<span><?php echo $open['open_now'] ? 'Şu an açık' : 'Şu an kapalı'; ?></span>
					<?php endif; ?>
				</div>
				<a class="ibp-h-btn ibp-h-btn--white" href="<?php echo esc_url( get_permalink( $first['id'] ) ); ?>">Profili incele<?php echo IBP_Icons::svg( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
			</div>
			<div class="ibp-h-rank__list">
				<?php foreach ( $rows as $index => $row ) : ?>
					<?php
					$item  = IBP_Business::from_id( $row['id'] );
					$hours = $item ? $item->today_hours() : null;
					?>
					<a class="ibp-h-rank__row" href="<?php echo esc_url( get_permalink( $row['id'] ) ); ?>">
						<span class="ibp-h-rank__pos"><?php echo esc_html( sprintf( '%02d', $index + 2 ) ); ?></span>
						<span>
							<span class="ibp-h-rank__name"><?php echo esc_html( get_the_title( $row['id'] ) ); ?></span>
							<span class="ibp-h-rank__sub">
								<?php echo esc_html( implode( ' · ', array_filter( array( $item ? $item->term_by_label( 'Kategori' ) : '', $item ? IBP_Ranking::district( $item ) : '' ) ) ) ); ?>
								<?php if ( $hours && 'unknown' !== $hours['status'] ) : ?>
									· <span class="<?php echo $hours['open_now'] ? 'is-open' : 'is-closed'; ?>"><?php echo $hours['open_now'] ? 'Şu an açık' : 'Kapalı'; ?></span>
								<?php endif; ?>
							</span>
						</span>
						<span class="ibp-h-rank__val<?php echo 'up' === $row['tone'] ? ' is-up' : ''; ?>"><b><?php echo esc_html( $row['value'] ); ?></b><small><?php echo esc_html( $row['small'] ); ?></small></span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}
}
