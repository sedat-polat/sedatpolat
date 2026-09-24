<?php
/**
 * Son yorumlar kartı.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;

class IBP_Widget_Reviews extends IBP_Widget_Base {

	public function get_name() {
		return 'ibp-reviews';
	}

	public function get_title() {
		return 'Son Yorumlar';
	}

	public function get_icon() {
		return 'eicon-review';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'İçerik' ) );
		$this->add_control(
			'title',
			array(
				'label'   => 'Başlık',
				'type'    => Controls_Manager::TEXT,
				'default' => 'Son yorumlar',
			)
		);
		$this->add_control(
			'count',
			array(
				'label'   => 'Gösterilecek yorum sayısı',
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 10,
				'default' => 2,
			)
		);
		$this->add_control(
			'all_url',
			array(
				'label'       => '"Tümünü gör" bağlantısı',
				'description' => 'Boş bırakılırsa düğme gizlenir. {id} yazdığın yere işletme ID\'si gelir.',
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
			)
		);
		$this->add_control(
			'empty_text',
			array(
				'label'   => 'Yorum yokken gösterilecek yazı',
				'type'    => Controls_Manager::TEXT,
				'default' => 'Henüz yorum yok. İlk yorum geldiğinde burada görünecek.',
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
		$reviews  = IBP_Reviews::recent( $business->id, (int) $settings['count'] );
		$all_url  = trim( (string) $settings['all_url'] );
		?>
		<div class="ibp ibp-card">
			<div class="ibp-card__head">
				<h2 class="ibp-card__title"><?php echo esc_html( $settings['title'] ); ?></h2>
				<?php if ( $all_url ) : ?>
					<a class="ibp-btn ibp-btn--sm ibp-btn--ghost" href="<?php echo esc_url( str_replace( '{id}', (string) $business->id, $all_url ) ); ?>">Tümünü gör</a>
				<?php endif; ?>
			</div>
			<div class="ibp-card__body ibp-reviews">
				<?php if ( ! $reviews ) : ?>
					<div class="ibp-muted"><?php echo esc_html( $settings['empty_text'] ); ?></div>
				<?php endif; ?>
				<?php foreach ( $reviews as $review ) : ?>
					<div class="ibp-review">
						<span class="ibp-av" aria-hidden="true"><?php echo esc_html( $review['initials'] ); ?></span>
						<div class="ibp-review__main">
							<div class="ibp-review__head">
								<span class="ibp-review__name"><?php echo esc_html( $review['name'] ); ?></span>
								<?php if ( null !== $review['stars'] ) : ?>
									<span class="ibp-stars" role="img" aria-label="<?php echo esc_attr( $review['stars'] . ' / 5 yıldız' ); ?>">
										<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
											<svg viewBox="0 0 24 24" width="13" height="13" aria-hidden="true"><path class="<?php echo $i <= $review['stars'] ? 'on' : ''; ?>" d="M12 3l2.8 5.7 6.2.9-4.5 4.4 1 6.2L12 17.3 6.5 20.2l1-6.2L3 9.6l6.2-.9z"></path></svg>
										<?php endfor; ?>
									</span>
								<?php endif; ?>
								<span class="ibp-review__date"><?php echo esc_html( $review['date'] ); ?></span>
							</div>
							<?php if ( '' !== $review['text'] ) : ?>
								<div class="ibp-review__text"><?php echo esc_html( $review['text'] ); ?></div>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}
}
