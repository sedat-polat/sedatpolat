<?php
/**
 * Ana sayfa: referanslar — sitedeki gerçek, yüksek puanlı yorumlar ya da elle yazılanlar.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Repeater;

class IBP_Home_Testimonials extends IBP_Home_Widget_Base {

	public function get_name() {
		return 'ibp-home-testimonials';
	}

	public function get_title() {
		return 'Ana Sayfa: Referanslar';
	}

	public function get_icon() {
		return 'eicon-testimonial';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'İçerik' ) );
		$this->add_control( 'eyebrow', array( 'label' => 'Üst yazı', 'type' => Controls_Manager::TEXT, 'default' => 'Referanslar' ) );
		$this->add_control( 'title', array( 'label' => 'Başlık', 'type' => Controls_Manager::TEXT, 'default' => 'Hem arayanlar hem işletmeler memnun.' ) );
		$this->add_control( 'sub', array( 'label' => 'Alt yazı', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$this->add_control(
			'source',
			array(
				'label'       => 'Kaynak',
				'type'        => Controls_Manager::SELECT,
				'default'     => 'mixed',
				'options'     => array(
					'mixed'   => 'Gerçek yorumlar, yetmezse elle yazılanlar',
					'reviews' => 'Yalnız sitedeki gerçek yorumlar',
					'manual'  => 'Yalnız elle yazılanlar',
				),
				'description' => 'Gerçek yorumlar: 4–5 yıldızlı ve en az 60 karakterlik en yeni yorumlar.',
			)
		);
		$this->add_control( 'count', array( 'label' => 'Kart sayısı', 'type' => Controls_Manager::NUMBER, 'min' => 1, 'max' => 9, 'default' => 3 ) );

		$repeater = new Repeater();
		$repeater->add_control( 'text', array( 'label' => 'Metin', 'type' => Controls_Manager::TEXTAREA, 'default' => '' ) );
		$repeater->add_control( 'name', array( 'label' => 'Ad', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$repeater->add_control( 'meta', array( 'label' => 'Alt bilgi', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$this->add_control(
			'items',
			array(
				'label'       => 'Elle yazılanlar',
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ name }}}',
				'default'     => array(
					array( 'text' => 'Yeni taşındığım semtte güvenilir bir diş kliniği arıyordum. Yorumları okuyup karar verdim, hiç pişman olmadım. Artık her aramamda önce buraya bakıyorum.', 'name' => 'Ayşe Yılmaz', 'meta' => 'İstanbul' ),
					array( 'text' => 'Kafe açtığımda ilk hafta hiç müşteri gelmiyordu. Buraya kaydolduktan sonra yerel aramalarda çıkmaya başladık. Üç ayda ciro yüzde kırk arttı.', 'name' => 'Mehmet Kaya', 'meta' => 'İşletme sahibi · İzmir' ),
					array( 'text' => 'Aradığım yerde telefon açılmıyordu, profildeki bilgilerle dakikalar içinde ulaştım ve randevumu aynı gün aldım.', 'name' => 'Selin Demir', 'meta' => 'Ankara' ),
				),
			)
		);
		$this->end_controls_section();

		$this->add_section_style_controls();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$count    = max( 1, (int) $settings['count'] );
		$cards    = array();

		if ( 'manual' !== $settings['source'] ) {
			foreach ( IBP_Reviews::best_recent( $count ) as $review ) {
				$cards[] = array( 'text' => $review['text'], 'name' => $review['name'], 'meta' => $review['business'], 'stars' => $review['stars'] );
			}
		}
		if ( 'reviews' !== $settings['source'] ) {
			foreach ( (array) $settings['items'] as $item ) {
				if ( count( $cards ) >= $count ) {
					break;
				}
				if ( '' !== trim( (string) $item['text'] ) ) {
					$cards[] = array( 'text' => $item['text'], 'name' => $item['name'], 'meta' => $item['meta'], 'stars' => 5 );
				}
			}
		}
		if ( ! $cards ) {
			return;
		}
		?>
		<section class="ibp ibp-h ibp-h-testi ibp-reveal">
			<div class="ibp-h-container">
				<?php IBP_Home::head( $settings['eyebrow'], $settings['title'], $settings['sub'], '', true ); ?>
				<div class="ibp-h-testi__grid">
					<?php foreach ( array_slice( $cards, 0, $count ) as $card ) : ?>
						<figure class="ibp-h-testi__card">
							<span class="ibp-h-testi__mark" aria-hidden="true">"</span>
							<span class="ibp-h-testi__stars" role="img" aria-label="<?php echo esc_attr( $card['stars'] . ' / 5 yıldız' ); ?>"><?php echo esc_html( str_repeat( '★', (int) $card['stars'] ) ); ?></span>
							<blockquote><?php echo esc_html( $card['text'] ); ?></blockquote>
							<figcaption>
								<span class="ibp-h-av ibp-h-av--sm" style="background: <?php echo esc_attr( IBP_Home::gradient( $card['name'] ) ); ?>"><?php echo esc_html( IBP_Home::initials( $card['name'] ) ); ?></span>
								<span><b><?php echo esc_html( $card['name'] ); ?></b><small><?php echo esc_html( $card['meta'] ); ?></small></span>
							</figcaption>
						</figure>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
	}
}
