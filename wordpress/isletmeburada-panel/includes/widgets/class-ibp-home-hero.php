<?php
/**
 * Ana sayfa: başlık ve 3 adımlı arama (kategori → şehir → sonuçlar).
 * JavaScript yoksa kategori kartları doğrudan arama sayfasına gider.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;

class IBP_Home_Hero extends IBP_Home_Widget_Base {

	public function get_name() {
		return 'ibp-home-hero';
	}

	public function get_title() {
		return 'Ana Sayfa: Arama';
	}

	public function get_icon() {
		return 'eicon-search';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'İçerik' ) );
		$this->add_control(
			'title',
			array(
				'label'       => 'Başlık',
				'description' => 'Yıldız içindeki kısım vurgulanır: *nokta atışı*',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Ne arıyorsan, *nokta atışı* bulalım.',
			)
		);
		$this->add_control(
			'lead',
			array(
				'label'   => 'Alt yazı',
				'type'    => Controls_Manager::TEXTAREA,
				'default' => 'Kategoriyi seç, şehrini söyle; doğrulanmış işletmeler, gerçek yorumlarla önünde.',
			)
		);
		$this->add_control(
			'placeholder',
			array(
				'label'   => 'Arama kutusu yazısı',
				'type'    => Controls_Manager::TEXT,
				'default' => 'Yaz ya da aşağıdan seç: berber, kebap, dişçi, lastikçi…',
			)
		);
		$this->add_control(
			'limit',
			array(
				'label'   => 'Gösterilecek kategori',
				'type'    => Controls_Manager::NUMBER,
				'min'     => 4,
				'max'     => 24,
				'default' => 12,
			)
		);
		$this->add_control(
			'popular_cities',
			array(
				'label'       => 'Öne çıkan şehirler',
				'description' => 'Virgülle ayır.',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'İstanbul, Ankara, İzmir, Bursa, Antalya, Adana, Konya, Gaziantep',
			)
		);
		$this->add_control(
			'show_stats',
			array(
				'label'     => 'Alttaki sayılar',
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'separator' => 'before',
			)
		);
		$this->add_control( 'stat_cities', array( 'label' => 'İl sayısı', 'type' => Controls_Manager::TEXT, 'default' => '81', 'condition' => array( 'show_stats' => 'yes' ) ) );
		$this->add_control( 'stat_last', array( 'label' => 'Son madde', 'type' => Controls_Manager::TEXT, 'default' => 'Ücretsiz|işletme kaydı', 'description' => 'Kalın kısım|devamı', 'condition' => array( 'show_stats' => 'yes' ) ) );
		$this->end_controls_section();

		$this->add_section_style_controls();
	}

	protected function render() {
		$settings   = $this->get_settings_for_display();
		$categories = IBP_Home::categories( (int) $settings['limit'] );
		$cities     = IBP_City::all();
		$current    = IBP_City::current();
		$popular    = array();
		foreach ( array_map( 'trim', explode( ',', (string) $settings['popular_cities'] ) ) as $name ) {
			$slug = IBP_City::slug( $name );
			if ( isset( $cities[ $slug ] ) ) {
				$popular[ $slug ] = $cities[ $slug ];
			}
		}
		$id = 'ibp-finder-' . $this->get_id();
		?>
		<section class="ibp ibp-h ibp-h-hero">
			<div class="ibp-h-container ibp-h-hero__inner">
				<h1 class="ibp-h-hero__title"><?php echo IBP_Home::title_html( $settings['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h1>
				<?php if ( '' !== $settings['lead'] ) : ?>
					<p class="ibp-h-hero__lead"><?php echo esc_html( $settings['lead'] ); ?></p>
				<?php endif; ?>

				<div class="ibp-h-finder" id="<?php echo esc_attr( $id ); ?>" data-ibp-finder
					data-search="<?php echo esc_attr( IBP_Home::search_url() ); ?>"
					data-cat-param="<?php echo esc_attr( IBP_Settings::get( 'cat_param' ) ); ?>"
					data-city-param="<?php echo esc_attr( IBP_Settings::get( 'city_param' ) ); ?>">
					<div class="ibp-h-steps" role="tablist" aria-label="Arama adımları">
						<button type="button" class="ibp-h-step is-on" data-step="1" role="tab" aria-selected="true"><span class="ibp-h-step__n">1</span><span><b>Ne arıyorsun?</b><small data-step-note>Kategori seç</small></span></button>
						<button type="button" class="ibp-h-step" data-step="2" role="tab" aria-selected="false" disabled><span class="ibp-h-step__n">2</span><span><b>Hangi şehirdesin?</b><small data-step-note>Şehir seç</small></span></button>
						<button type="button" class="ibp-h-step" data-step="3" role="tab" aria-selected="false" disabled><span class="ibp-h-step__n">3</span><span><b>Sonuçlar</b><small>Nokta atışı</small></span></button>
					</div>

					<div class="ibp-h-panel is-on" data-panel="1" role="tabpanel">
						<label class="ibp-h-search">
							<?php echo IBP_Icons::svg( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<span class="ibp-sr">Kategori ara</span>
							<input type="search" data-finder-filter placeholder="<?php echo esc_attr( $settings['placeholder'] ); ?>" autocomplete="off">
						</label>
						<div class="ibp-h-tiles">
							<?php foreach ( $categories as $category ) : ?>
								<a class="ibp-h-tile" href="<?php echo esc_url( IBP_Home::search_url( $category['slug'], $current['slug'] ) ); ?>" data-cat="<?php echo esc_attr( $category['slug'] ); ?>" data-name="<?php echo esc_attr( $category['name'] ); ?>" data-keywords="<?php echo esc_attr( IBP_Business::lower_tr( $category['name'] . ' ' . $category['sub'] ) ); ?>">
									<span class="ibp-h-tile__ic"><?php echo IBP_Icons::svg( $category['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
									<span class="ibp-h-tile__t"><b><?php echo esc_html( $category['name'] ); ?></b><?php if ( '' !== $category['sub'] ) : ?><small><?php echo esc_html( $category['sub'] ); ?></small><?php endif; ?></span>
								</a>
							<?php endforeach; ?>
						</div>
						<p class="ibp-h-finder__empty" data-finder-empty hidden>Eşleşen kategori yok. Farklı bir kelime dene.</p>
					</div>

					<div class="ibp-h-panel" data-panel="2" role="tabpanel" hidden>
						<div class="ibp-h-chips">
							<?php foreach ( $popular as $slug => $name ) : ?>
								<button type="button" class="ibp-h-chip<?php echo $slug === $current['slug'] ? ' is-on' : ''; ?>" data-city="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $name ); ?></button>
							<?php endforeach; ?>
						</div>
						<div class="ibp-h-cityrow">
							<label class="ibp-sr" for="<?php echo esc_attr( $id ); ?>-city">Tüm şehirler</label>
							<select id="<?php echo esc_attr( $id ); ?>-city" class="ibp-h-select" data-finder-city>
								<option value="">Başka bir şehir seç…</option>
								<?php foreach ( $cities as $slug => $name ) : ?>
									<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $name ); ?></option>
								<?php endforeach; ?>
							</select>
							<button type="button" class="ibp-h-btn ibp-h-btn--outline" data-city="">Tüm Türkiye</button>
						</div>
					</div>
				</div>

				<?php if ( 'yes' === $settings['show_stats'] ) : ?>
					<?php
					$stats = IBP_Home::stats();
					$last  = array_pad( explode( '|', (string) $settings['stat_last'], 2 ), 2, '' );
					?>
					<div class="ibp-h-hero__foot">
						<span><b><?php echo esc_html( IBP_Home::short_number( $stats['businesses'] ) ); ?></b> işletme</span>
						<?php if ( '' !== $settings['stat_cities'] ) : ?>
							<span><b><?php echo esc_html( $settings['stat_cities'] ); ?></b> il</span>
						<?php endif; ?>
						<?php if ( $stats['reviews'] ) : ?>
							<span><b><?php echo esc_html( IBP_Home::short_number( $stats['reviews'] ) ); ?></b> gerçek yorum</span>
						<?php endif; ?>
						<?php if ( '' !== $last[0] ) : ?>
							<span><b><?php echo esc_html( $last[0] ); ?></b> <?php echo esc_html( $last[1] ); ?></span>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}
}
