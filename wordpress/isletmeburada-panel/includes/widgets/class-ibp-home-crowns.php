<?php
/**
 * Ana sayfa: "Şehrin Sahipleri" — her kategoride şehrin tahtındaki işletme — ve
 * kategori seçilerek taht düellosunun izlendiği "Yarış Arenası".
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;

class IBP_Home_Crowns extends IBP_Home_Widget_Base {

	const CLOSE_RACE = 0.1; // 5'lik ölçekte bu farkın altı "canlı yarış".

	public function get_name() {
		return 'ibp-home-crowns';
	}

	public function get_title() {
		return 'Ana Sayfa: Şehrin Sahipleri';
	}

	public function get_icon() {
		return 'eicon-rating';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'İçerik' ) );
		$this->add_control( 'eyebrow', array( 'label' => 'Üst yazı', 'type' => Controls_Manager::TEXT, 'default' => '👑 Şehrin Sahipleri' ) );
		$this->add_control(
			'title',
			array(
				'label'       => 'Başlık',
				'description' => '{city_gen} → İstanbul\'un, {city_loc} → İstanbul\'da, {city} → İstanbul',
				'type'        => Controls_Manager::TEXT,
				'default'     => '{city_gen} sahipleri.',
			)
		);
		$this->add_control( 'sub', array( 'label' => 'Alt yazı', 'type' => Controls_Manager::TEXT, 'default' => 'Her kategoride şehrin tahtında oturan işletme. Taht her ay yeniden belirlenir.' ) );
		$this->add_control( 'limit', array( 'label' => 'Kategori sayısı', 'type' => Controls_Manager::NUMBER, 'min' => 2, 'max' => 20, 'default' => 8 ) );
		$this->add_control( 'show_empty', array( 'label' => 'Tahtı boş kategorileri göster', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->add_control( 'show_arena', array( 'label' => 'Yarış Arenası', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes', 'separator' => 'before' ) );
		$this->add_control( 'review_url', array( 'label' => '"Yorum yaz" adresi', 'type' => Controls_Manager::TEXT, 'default' => '', 'condition' => array( 'show_arena' => 'yes' ) ) );
		$this->add_control(
			'rules',
			array(
				'label'     => 'Kurallar notu',
				'type'      => Controls_Manager::TEXTAREA,
				'default'   => '*Taht satın alınamaz.* Sıra, yorum puanı ve yorum sayısından hesaplanan ağırlıklı puanla belirlenir; az yorumlu işletmeler kategori ortalamasına yaklaştırılır. Sponsorlu işletmelere ayrıcalık yoktur.',
				'separator' => 'before',
			)
		);
		$this->add_control( 'cta_text', array( 'label' => 'Düğme yazısı', 'type' => Controls_Manager::TEXT, 'default' => '👑 İşletmeni ücretsiz ekle, tahtı sen al' ) );
		$this->add_control( 'cta_url', array( 'label' => 'Düğme adresi', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$this->end_controls_section();

		$this->add_section_style_controls();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$city     = IBP_City::current();
		$taxonomy = IBP_Business::taxonomy_by_label( 'Kategori' );
		$rows     = array();

		foreach ( IBP_Home::categories( (int) $settings['limit'] ) as $category ) {
			$board = $taxonomy ? IBP_Ranking::board( $taxonomy, $category['term']->term_id, $city['name'] ) : array();
			if ( ! $board && 'yes' !== $settings['show_empty'] ) {
				continue;
			}
			$rows[] = $category + array( 'board' => $board );
		}

		$days_left = (int) wp_date( 't' ) - (int) wp_date( 'j' ) + 1;
		$timer     = sprintf( '<span class="ibp-h-timer">👑 <b>%s tahtı</b> · %d gün sonra yeniden belirlenir</span>', esc_html( wp_date( 'F' ) ), $days_left );
		?>
		<section class="ibp ibp-h ibp-h-crowns ibp-reveal">
			<div class="ibp-h-container">
				<?php IBP_Home::head( $settings['eyebrow'], IBP_Home::fill_city( $settings['title'], $city['name'] ), $settings['sub'], $timer . IBP_Home::city_switch( $city ) ); ?>

				<?php if ( ! $rows ) : ?>
					<div class="ibp-h-empty">Bu şehirde henüz sıralamaya giren işletme yok. İlk yorumlar geldikçe tahtlar dolacak.</div>
				<?php else : ?>
					<div class="ibp-h-crown-grid">
						<?php foreach ( $rows as $row ) : ?>
							<?php $this->render_card( $row, $city ); ?>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<?php
				if ( 'yes' === $settings['show_arena'] ) {
					$this->render_arena( $rows, $city, $settings );
				}
				?>

				<div class="ibp-h-crown-foot">
					<?php if ( '' !== $settings['rules'] ) : ?>
						<p class="ibp-h-rules"><?php echo IBP_Icons::svg( 'info' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo preg_replace( '/\*(.+?)\*/u', '<b>$1</b>', esc_html( $settings['rules'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span></p>
					<?php endif; ?>
					<?php if ( '' !== $settings['cta_text'] && '' !== $settings['cta_url'] ) : ?>
						<a class="ibp-h-btn ibp-h-btn--gold" href="<?php echo esc_url( $settings['cta_url'] ); ?>"><?php echo esc_html( $settings['cta_text'] ); ?></a>
					<?php endif; ?>
				</div>
			</div>
		</section>
		<?php
	}

	private function render_card( array $row, array $city ) {
		$board = $row['board'];
		$icon  = '<span class="ibp-h-crown__ci">' . IBP_Icons::svg( $row['icon'] ) . '</span>';
		if ( ! $board ) {
			?>
			<div class="ibp-h-crown ibp-h-crown--empty">
				<div class="ibp-h-crown__cat"><?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $row['name'] ); ?></div>
				<b>Taht boş</b>
				<span>Bu kategoride ilk yorumu alan işletme tahta oturur.</span>
			</div>
			<?php
			return;
		}

		$king   = $board[0];
		$rival  = $board[1] ?? null;
		$gap    = $rival ? $king['score'] - $rival['score'] : null;
		$shaky  = null !== $gap && $gap < self::CLOSE_RACE;
		$reign  = IBP_Ranking::reign_months( $row['term']->term_id, $city['name'], $king['id'] );
		?>
		<a class="ibp-h-crown<?php echo $shaky ? ' is-shaky' : ''; ?>" href="<?php echo esc_url( get_permalink( $king['id'] ) ); ?>">
			<?php if ( $shaky ) : ?>
				<span class="ibp-h-crown__live">Canlı yarış</span>
			<?php endif; ?>
			<div class="ibp-h-crown__cat"><?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $row['name'] ); ?></div>
			<div class="ibp-h-crown__king">
				<span class="ibp-h-crown__avwrap"><?php echo IBP_Home::avatar( $king['id'], 'ibp-h-av ibp-h-av--king' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo IBP_Icons::svg( 'crown', 'ibp-h-crown__cr' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<span>
					<span class="ibp-h-crown__name"><?php echo esc_html( $king['name'] ); ?></span>
					<span class="ibp-h-crown__where"><?php echo esc_html( implode( ', ', array_filter( array( $king['district'], $city['name'] ) ) ) ); ?></span>
				</span>
			</div>
			<div class="ibp-h-crown__stats">
				<span>★ <?php echo esc_html( number_format_i18n( $king['average'], 1 ) . ' · ' . number_format_i18n( $king['count'] ) ); ?> yorum</span>
				<?php if ( null !== $reign ) : ?>
					<span class="ibp-h-crown__streak"><?php echo esc_html( $reign > 0 ? sprintf( '%d aydır tahtta', $reign ) : 'Bu ay tahta çıktı' ); ?></span>
				<?php endif; ?>
			</div>
			<div class="ibp-h-crown__rival">
				<?php if ( $rival ) : ?>
					<div class="ibp-h-crown__rtop"><span>Rakip: <b><?php echo esc_html( $rival['name'] ); ?></b></span><span>★ <?php echo esc_html( number_format_i18n( $rival['average'], 1 ) ); ?></span></div>
					<div class="ibp-h-bar"><i style="width: <?php echo esc_attr( min( 100, round( 100 * $rival['score'] / max( 0.01, $king['score'] ) ) ) ); ?>%"></i></div>
					<div class="ibp-h-crown__note<?php echo $shaky ? ' is-hot' : ''; ?>">
						<?php echo esc_html( $shaky ? '🔥 Taht sallanıyor — fark çok az!' : sprintf( 'Tahta yaklaşma: %%%d', min( 99, round( 100 * $rival['score'] / max( 0.01, $king['score'] ) ) ) ) ); ?>
					</div>
				<?php else : ?>
					<div class="ibp-h-crown__note">Henüz rakibi yok. Rakip olmak için ilk yorumunu topla.</div>
				<?php endif; ?>
			</div>
		</a>
		<?php
	}

	private function render_arena( array $rows, array $city, array $settings ) {
		$rows = array_values(
			array_filter(
				$rows,
				function ( $row ) {
					return count( $row['board'] ) >= 1;
				}
			)
		);
		if ( ! $rows ) {
			return;
		}
		$ids    = array();
		foreach ( $rows as $row ) {
			$ids = array_merge( $ids, wp_list_pluck( array_slice( $row['board'], 0, 5 ), 'id' ) );
		}
		$latest = IBP_Reviews::recent( $ids, 1 );
		$id     = 'ibp-arena-' . $this->get_id();
		?>
		<div class="ibp-h-arena" data-ibp-tabs>
			<div class="ibp-h-arena__head">
				<div>
					<h3>🔥 Yarış Arenası</h3>
					<p>Bir kategori seç, tahtın etrafındaki kapışmayı yakından izle.</p>
				</div>
				<?php if ( $latest ) : ?>
					<div class="ibp-h-ticker"><span class="ibp-h-live"></span><span><b><?php echo esc_html( $latest[0]['business'] ); ?></b> yeni bir yorum aldı · <?php echo esc_html( $latest[0]['date'] ); ?></span></div>
				<?php endif; ?>
			</div>
			<div class="ibp-h-arena__cats" role="tablist" aria-label="Kategori">
				<?php foreach ( $rows as $i => $row ) : ?>
					<?php $hot = isset( $row['board'][1] ) && $row['board'][0]['score'] - $row['board'][1]['score'] < self::CLOSE_RACE; ?>
					<button type="button" class="ibp-h-acat<?php echo 0 === $i ? ' is-on' : ''; ?>" role="tab" aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr( $id . '-' . $i ); ?>" data-tab="<?php echo esc_attr( $id . '-' . $i ); ?>">
						<?php echo IBP_Icons::svg( $row['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $row['name'] ); ?><?php echo $hot ? ' 🔥' : ''; ?>
					</button>
				<?php endforeach; ?>
			</div>
			<?php foreach ( $rows as $i => $row ) : ?>
				<?php
				$king  = $row['board'][0];
				$rival = $row['board'][1] ?? null;
				$gap   = $rival ? $king['score'] - $rival['score'] : null;
				?>
				<div class="ibp-h-arena__body" id="<?php echo esc_attr( $id . '-' . $i ); ?>" role="tabpanel"<?php echo 0 === $i ? '' : ' hidden'; ?>>
					<div class="ibp-h-duel">
						<div class="ibp-h-duel__label"><span>👑 Tahtın düellosu · <?php echo esc_html( $row['name'] ); ?></span><?php if ( null !== $gap && $gap < self::CLOSE_RACE ) : ?><span class="is-hot">Fark çok az!</span><?php endif; ?></div>
						<?php if ( $rival ) : ?>
							<div class="ibp-h-duel__vs">
								<?php $this->duelist( $king, true ); ?>
								<span class="ibp-h-vs">VS</span>
								<?php $this->duelist( $rival, false ); ?>
							</div>
							<div class="ibp-h-duel__bar"><i style="width: <?php echo esc_attr( round( 100 * $king['score'] / ( $king['score'] + $rival['score'] ) ) ); ?>%"></i></div>
							<p class="ibp-h-duel__note"><?php echo esc_html( sprintf( '%s, tahta %s puan uzaklıkta. Yeni yorumlar sıralamayı her saat günceller.', $rival['name'], number_format_i18n( IBP_Ranking::scaled( $gap ), 2 ) ) ); ?></p>
						<?php else : ?>
							<div class="ibp-h-duel__vs ibp-h-duel__vs--solo"><?php $this->duelist( $king, true ); ?></div>
							<p class="ibp-h-duel__note">Tahtın tek sahibi. Rakip olmak için bu kategoride yorum toplayan ikinci işletme olmak yeterli.</p>
						<?php endif; ?>
					</div>
					<div class="ibp-h-board">
						<div class="ibp-h-board__head"><span>Sıralama</span><span>Puan</span></div>
						<?php foreach ( array_slice( $row['board'], 0, 5 ) as $pos => $item ) : ?>
							<a class="ibp-h-board__row<?php echo 0 === $pos ? ' is-king' : ''; ?>" href="<?php echo esc_url( get_permalink( $item['id'] ) ); ?>">
								<span class="ibp-h-board__pos"><?php echo 0 === $pos ? '👑' : esc_html( $pos + 1 ); ?></span>
								<span class="ibp-h-board__name"><b><?php echo esc_html( $item['name'] ); ?></b><small><?php echo esc_html( implode( ' · ', array_filter( array( $item['district'], number_format_i18n( $item['count'] ) . ' yorum' ) ) ) ); ?></small></span>
								<span class="ibp-h-board__score"><?php echo esc_html( number_format_i18n( IBP_Ranking::scaled( $item['score'] ), 2 ) ); ?></span>
							</a>
						<?php endforeach; ?>
						<a class="ibp-h-board__all" href="<?php echo esc_url( IBP_Home::search_url( $row['slug'], $city['slug'] ) ); ?>"><?php echo esc_html( sprintf( '%s tüm %s listesini gör →', IBP_Tr::locative( $city['name'] ), IBP_Business::lower_tr( $row['name'] ) ) ); ?></a>
					</div>
				</div>
			<?php endforeach; ?>
			<?php if ( '' !== $settings['review_url'] ) : ?>
				<div class="ibp-h-arena__cta">
					<span><b>Bu işletmelerden birine gittin mi?</b> Yorumun yarışın kaderini değiştirebilir.</span>
					<a class="ibp-h-btn ibp-h-btn--outline" href="<?php echo esc_url( $settings['review_url'] ); ?>">Yorum yaz</a>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	private function duelist( array $item, $king ) {
		?>
		<div class="ibp-h-duelist<?php echo $king ? ' is-king' : ''; ?>">
			<span class="ibp-h-crown__avwrap"><?php echo IBP_Home::avatar( $item['id'], 'ibp-h-av ibp-h-av--duel' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo $king ? IBP_Icons::svg( 'crown', 'ibp-h-crown__cr' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<b><?php echo esc_html( $item['name'] ); ?></b>
			<small><?php echo esc_html( implode( ' · ', array_filter( array( $item['district'], number_format_i18n( $item['count'] ) . ' yorum' ) ) ) ); ?></small>
			<span class="ibp-h-duelist__sc"><?php echo esc_html( number_format_i18n( IBP_Ranking::scaled( $item['score'] ), 2 ) ); ?><span>taht puanı</span></span>
		</div>
		<?php
	}
}
