<?php
/**
 * "Şehrin Sahipleri" koyu kartı: sıra, puan, ilk 5 ve fark.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;

class IBP_Widget_Ranking extends IBP_Widget_Base {

	public function get_name() {
		return 'ibp-ranking';
	}

	public function get_title() {
		return 'Şehrin Sahipleri';
	}

	public function get_icon() {
		return 'eicon-number-field';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'İçerik' ) );
		$this->add_control(
			'title',
			array(
				'label'   => 'Başlık',
				'type'    => Controls_Manager::TEXT,
				'default' => 'Şehrin Sahipleri',
			)
		);
		$this->add_control(
			'limit',
			array(
				'label'   => 'Listede gösterilecek işletme',
				'type'    => Controls_Manager::NUMBER,
				'min'     => 3,
				'max'     => 10,
				'default' => 5,
			)
		);
		$this->add_control(
			'brand_title',
			array(
				'label'       => 'Marka görünümünde başlık',
				'description' => '"Tüm bağlı işletmeler" seçiliyken kart, bağlı işletmeleri sıralar.',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Bağlı işletmeler',
			)
		);
		$this->add_control(
			'note',
			array(
				'label'   => 'Alt açıklama',
				'type'    => Controls_Manager::TEXTAREA,
				'default' => 'Puan; yorum ortalaması ve yorum sayısından hesaplanır. Az yorumlu işletmeler kategori ortalamasına yaklaştırılır. Yorumlara hızlı yanıt vermek ve yeni yorum toplamak sıranı yükseltir.',
			)
		);
		$this->end_controls_section();

		$this->start_controls_section( 'ibp_style', array( 'label' => 'Renkler', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control(
			'bg',
			array(
				'label'     => 'Arka plan',
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => array( '{{WRAPPER}} .ibp-rank' => 'background: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'highlight',
			array(
				'label'     => 'Vurgu rengi',
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => array( '{{WRAPPER}} .ibp-rank' => '--ibp-rank-accent: {{VALUE}};' ),
			)
		);
		$this->end_controls_section();
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
		$data     = IBP_Ranking::for_business( $business );
		$limit    = max( 3, (int) $settings['limit'] );
		$board    = array_slice( $data['board'], 0, $limit, true );
		$decimals = 1;

		// Kendisi ilk N'de değilse listenin sonuna kendi satırını ekle.
		if ( $data['rank'] && $data['rank'] > $limit ) {
			$board[ $data['rank'] - 1 ] = $data['me'];
		}
		$scope = implode( ', ', array_filter( array( $data['city'], $data['category'] ) ) );
		?>
		<div class="ibp ibp-rank">
			<div class="ibp-rank__head">
				<div class="ibp-rank__title"><?php echo IBP_Icons::svg( 'crown' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $settings['title'] ); ?></div>
				<?php if ( $scope ) : ?>
					<span class="ibp-rank__scope"><?php echo esc_html( $scope ); ?></span>
				<?php endif; ?>
			</div>

			<?php if ( $data['rank'] ) : ?>
				<div class="ibp-rank__hero">
					<span class="ibp-rank__num"><?php echo esc_html( $data['rank'] ); ?>.</span>
					<div>
						<div class="ibp-rank__line"><?php echo esc_html( sprintf( 'sıradasın, puanın %s', number_format_i18n( $data['me']['score'], $decimals ) ) ); ?></div>
						<div class="ibp-rank__gap">
							<?php
							if ( $data['leader'] ) {
								echo esc_html( 'Tahttasın. Farkı korumak için yorumlara hızlı yanıt ver.' );
							} else {
								$ahead = $data['board'][ $data['rank'] - 2 ];
								echo esc_html( sprintf( '%s ile arandaki fark %s puan.', $ahead['name'], number_format_i18n( $data['gap'], $decimals ) ) );
							}
							?>
						</div>
					</div>
				</div>
			<?php else : ?>
				<div class="ibp-rank__empty">
					<?php
					if ( '' === $data['city'] || '' === $data['category'] ) {
						echo esc_html( 'Sıralamaya girmek için işletmenin şehri ve kategorisi dolu olmalı.' );
					} elseif ( $data['needs_reviews'] > 0 ) {
						echo esc_html( sprintf( 'Sıralamaya girmek için %d yorum daha gerekiyor.', $data['needs_reviews'] ) );
					} else {
						echo esc_html( 'Sıralama hazırlanıyor. Birkaç dakika içinde görünecek.' );
					}
					?>
				</div>
			<?php endif; ?>

			<?php if ( $board ) : ?>
				<div class="ibp-rank__board">
					<?php foreach ( $board as $index => $row ) : ?>
						<div class="ibp-rank__row<?php echo ! empty( $row['me'] ) ? ' is-me' : ''; ?>">
							<span class="ibp-rank__pos"><?php echo esc_html( $index + 1 ); ?></span>
							<span class="ibp-rank__name"><?php echo esc_html( $row['name'] ); ?></span>
							<?php if ( '' !== $row['district'] ) : ?>
								<span class="ibp-rank__district"><?php echo esc_html( $row['district'] ); ?></span>
							<?php endif; ?>
							<span class="ibp-rank__score"><?php echo esc_html( number_format_i18n( $row['score'], $decimals ) ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( '' !== $settings['note'] ) : ?>
				<div class="ibp-rank__note"><?php echo esc_html( $settings['note'] ); ?></div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Marka görünümü: bağlı işletmeler seçili dönemdeki görüntülenmeye göre sıralanır.
	 */
	private function render_brand( array $settings ) {
		$scope = IBP_Business::scope();
		$days  = IBP_Tracker::period();
		$rows  = array();
		foreach ( $scope['ids'] as $id ) {
			$item = IBP_Business::from_id( $id );
			if ( ! $item ) {
				continue;
			}
			$link   = IBP_Network::link_of( $id );
			$rows[] = array(
				'id'     => $id,
				'name'   => $item->name(),
				'type'   => $link ? IBP_Network::types()[ $link['type'] ] : 'Merkez',
				'views'  => IBP_Tracker::summary( $id, 'view', $days )['total'],
				'rating' => $item->review_stats()['average'],
			);
		}
		usort(
			$rows,
			function ( $a, $b ) {
				return $b['views'] <=> $a['views'] ?: strcmp( $a['name'], $b['name'] );
			}
		);
		$limit = max( 3, (int) $settings['limit'] );
		$top   = $rows[0] ?? null;
		?>
		<div class="ibp ibp-rank">
			<div class="ibp-rank__head">
				<div class="ibp-rank__title"><?php echo IBP_Icons::svg( 'network' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $settings['brand_title'] ); ?></div>
				<span class="ibp-rank__scope"><?php echo esc_html( sprintf( '%d işletme, son %d gün', count( $rows ), $days ) ); ?></span>
			</div>
			<?php if ( $top && $top['views'] > 0 ) : ?>
				<div class="ibp-rank__hero">
					<span class="ibp-rank__num">1.</span>
					<div>
						<div class="ibp-rank__line"><?php echo esc_html( $top['name'] ); ?></div>
						<div class="ibp-rank__gap"><?php echo esc_html( sprintf( '%s görüntülemeyle en çok ilgi gören işletmen.', number_format_i18n( $top['views'] ) ) ); ?></div>
					</div>
				</div>
			<?php else : ?>
				<div class="ibp-rank__empty">Sayaç veri topladıkça bağlı işletmelerin burada görüntülenmeye göre sıralanacak.</div>
			<?php endif; ?>
			<div class="ibp-rank__board">
				<?php foreach ( array_slice( $rows, 0, $limit ) as $index => $row ) : ?>
					<a class="ibp-rank__row ibp-rank__row--link" href="<?php echo esc_url( IBP_Business::switch_url( $row['id'] ) ); ?>" title="Bu işletmenin paneline geç">
						<span class="ibp-rank__pos"><?php echo esc_html( $index + 1 ); ?></span>
						<span class="ibp-rank__name"><?php echo esc_html( $row['name'] ); ?></span>
						<span class="ibp-rank__district"><?php echo esc_html( $row['type'] ); ?></span>
						<span class="ibp-rank__score ibp-rank__score--wide"><?php echo esc_html( number_format_i18n( $row['views'] ) ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
			<div class="ibp-rank__note">Sayı, seçili dönemdeki profil görüntülemesidir. Bir satıra tıklayınca o işletmenin paneline geçersin.</div>
		</div>
		<?php
	}
}
