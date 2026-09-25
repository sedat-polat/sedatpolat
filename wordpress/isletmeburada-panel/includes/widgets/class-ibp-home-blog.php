<?php
/**
 * Ana sayfa: son blog yazıları.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;

class IBP_Home_Blog extends IBP_Home_Widget_Base {

	const GRADIENTS = array(
		array( '#FF7A7F', '#E5484D' ),
		array( '#2C4A6E', '#1A2B45' ),
		array( '#7A9A6E', '#3E5535' ),
	);

	public function get_name() {
		return 'ibp-home-blog';
	}

	public function get_title() {
		return 'Ana Sayfa: Blog';
	}

	public function get_icon() {
		return 'eicon-posts-grid';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'İçerik' ) );
		$this->add_control( 'eyebrow', array( 'label' => 'Üst yazı', 'type' => Controls_Manager::TEXT, 'default' => 'Blog' ) );
		$this->add_control( 'title', array( 'label' => 'Başlık', 'type' => Controls_Manager::TEXT, 'default' => 'Rehberler ve analizler.' ) );
		$this->add_control( 'sub', array( 'label' => 'Alt yazı', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$this->add_control( 'count', array( 'label' => 'Yazı sayısı', 'type' => Controls_Manager::NUMBER, 'min' => 1, 'max' => 9, 'default' => 3 ) );
		$this->add_control( 'all_text', array( 'label' => '"Tüm yazılar" bağlantısı', 'type' => Controls_Manager::TEXT, 'default' => 'Tüm yazılar' ) );
		$this->end_controls_section();

		$this->add_section_style_controls( '#F7F8FA' );
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$posts    = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => max( 1, (int) $settings['count'] ),
				'no_found_rows'  => true,
			)
		);
		if ( ! $posts ) {
			return;
		}
		$blog = get_option( 'page_for_posts' ) ? get_permalink( (int) get_option( 'page_for_posts' ) ) : '';
		$side = ( $blog && '' !== $settings['all_text'] ) ? '<a class="ibp-h-action" href="' . esc_url( $blog ) . '">' . esc_html( $settings['all_text'] ) . IBP_Icons::svg( 'arrow' ) . '</a>' : '';
		?>
		<section class="ibp ibp-h ibp-h-blog ibp-reveal">
			<div class="ibp-h-container">
				<?php IBP_Home::head( $settings['eyebrow'], $settings['title'], $settings['sub'], $side ); ?>
				<div class="ibp-h-blog__grid">
					<?php foreach ( $posts as $i => $post ) : ?>
						<?php
						$image    = get_the_post_thumbnail_url( $post, 'medium_large' );
						$gradient = self::GRADIENTS[ $i % count( self::GRADIENTS ) ];
						$cats     = get_the_category( $post->ID );
						$minutes  = max( 1, (int) ceil( str_word_count( wp_strip_all_tags( $post->post_content ) ) / 200 ) );
						?>
						<a class="ibp-h-blog__card" href="<?php echo esc_url( get_permalink( $post ) ); ?>">
							<span class="ibp-h-blog__img" style="<?php echo esc_attr( $image ? 'background-image:url(' . $image . ')' : 'background:linear-gradient(160deg,' . $gradient[0] . ',' . $gradient[1] . ')' ); ?>">
								<?php if ( $cats ) : ?>
									<span class="ibp-h-blog__cat"><?php echo esc_html( $cats[0]->name ); ?></span>
								<?php endif; ?>
							</span>
							<span class="ibp-h-blog__body">
								<span class="ibp-h-blog__meta"><?php echo esc_html( wp_date( 'j F Y', get_post_timestamp( $post ) ) . ' · ' . $minutes . ' dk okuma' ); ?></span>
								<span class="ibp-h-blog__title"><?php echo esc_html( get_the_title( $post ) ); ?></span>
								<span class="ibp-h-blog__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt( $post ), 22, '…' ) ); ?></span>
								<span class="ibp-h-blog__more">Devamını oku<?php echo IBP_Icons::svg( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
							</span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
	}
}
