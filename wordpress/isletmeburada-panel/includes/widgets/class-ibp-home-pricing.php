<?php
/**
 * Ana sayfa: işletme sahipleri için tanıtım ve paketler (koyu bölüm).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Repeater;

class IBP_Home_Pricing extends IBP_Home_Widget_Base {

	public function get_name() {
		return 'ibp-home-pricing';
	}

	public function get_title() {
		return 'Ana Sayfa: İşletme Paketleri';
	}

	public function get_icon() {
		return 'eicon-price-table';
	}

	protected function register_controls() {
		$this->start_controls_section( 'intro', array( 'label' => 'Tanıtım' ) );
		$this->add_control( 'eyebrow', array( 'label' => 'Üst yazı', 'type' => Controls_Manager::TEXT, 'default' => 'İşletme sahipleri için' ) );
		$this->add_control( 'title', array( 'label' => 'Başlık', 'type' => Controls_Manager::TEXT, 'default' => 'İşletmenizi doğru müşteriyle buluşturun.' ) );
		$this->add_control( 'text', array( 'label' => 'Metin', 'type' => Controls_Manager::TEXTAREA, 'default' => 'Türkiye\'nin dört bir yanından, sizi arayan gerçek müşterilere ulaşın. Profilinizi oluşturun, yönetin, büyütün.' ) );
		$this->add_control(
			'features',
			array(
				'label'       => 'Maddeler',
				'description' => 'Her satır bir madde.',
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => "Ücretsiz listeleme — kredi kartı, komisyon, taahhüt yok\nDoğrulanmış profil rozeti ile müşteri güveni kazanın\nYorumlara kurumsal kimlikle yanıt verin\nProfil görüntüleme, telefon tıklama ve yol tarifi istatistiklerini takip edin\nŞubelerinizi, bayilerinizi tek hesaptan yönetin",
			)
		);
		$this->add_control( 'button', array( 'label' => 'Düğme yazısı', 'type' => Controls_Manager::TEXT, 'default' => 'İşletmenizi ücretsiz ekleyin' ) );
		$this->add_control( 'url', array( 'label' => 'Düğme adresi', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$this->end_controls_section();

		$this->start_controls_section( 'plans_section', array( 'label' => 'Paketler' ) );
		$repeater = new Repeater();
		$repeater->add_control( 'name', array( 'label' => 'Paket adı', 'type' => Controls_Manager::TEXT, 'default' => 'Paket' ) );
		$repeater->add_control( 'desc', array( 'label' => 'Kısa açıklama', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$repeater->add_control( 'price', array( 'label' => 'Fiyat', 'type' => Controls_Manager::TEXT, 'default' => '₺0' ) );
		$repeater->add_control( 'period', array( 'label' => 'Dönem', 'type' => Controls_Manager::TEXT, 'default' => '/ aylık' ) );
		$repeater->add_control( 'features', array( 'label' => 'Özellikler (her satır bir)', 'type' => Controls_Manager::TEXTAREA, 'default' => '' ) );
		$repeater->add_control( 'button', array( 'label' => 'Düğme', 'type' => Controls_Manager::TEXT, 'default' => 'Paketi seç' ) );
		$repeater->add_control( 'url', array( 'label' => 'Düğme adresi', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$repeater->add_control( 'featured', array( 'label' => 'Önerilen', 'type' => Controls_Manager::SWITCHER, 'default' => '' ) );
		$this->add_control(
			'plans',
			array(
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ name }}}',
				'default'     => array(
					array( 'name' => 'Ücretsiz', 'desc' => 'Temel profille başlayın', 'price' => '₺0', 'period' => '/ süresiz', 'features' => "İşletme profili ve temel bilgiler\nAdres, harita ve iletişim bilgileri\nÇalışma saatleri ve açık/kapalı rozeti\nKullanıcı yorum ve puanları", 'button' => 'Başlayın', 'url' => '', 'featured' => '' ),
					array( 'name' => 'Standart', 'desc' => 'Görünürlüğünüzü artırın', 'price' => '₺199', 'period' => '/ aylık', 'features' => "Ücretsiz paketteki her şey\nEtkinlik ve duyuru yayınlama\nİş ilanı yayınlama hakkı\nKampanya ve kupon oluşturma\nTemel istatistik paneli", 'button' => 'Paketi seç', 'url' => '', 'featured' => 'yes' ),
					array( 'name' => 'Premium', 'desc' => 'Kurumsal çözümler', 'price' => '₺499', 'period' => '/ aylık', 'features' => "Standart paketteki her şey\nArama sonuçlarında en üst sıra\nAna sayfa vitrin ve slider\nReklamsız profil ve gelişmiş analitik\nÇoklu şube yönetimi", 'button' => 'Paketi seç', 'url' => '', 'featured' => '' ),
				),
			)
		);
		$this->add_control( 'featured_label', array( 'label' => 'Önerilen etiketi', 'type' => Controls_Manager::TEXT, 'default' => 'Önerilen' ) );
		$this->end_controls_section();

		$this->add_section_style_controls( '#1C1E21' );
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$check    = IBP_Icons::svg( 'check' );
		?>
		<section class="ibp ibp-h ibp-h-pricing ibp-reveal">
			<div class="ibp-h-container ibp-h-pricing__grid">
				<div class="ibp-h-pricing__intro">
					<span class="ibp-h-eyebrow"><?php echo esc_html( $settings['eyebrow'] ); ?></span>
					<h2 class="ibp-h-title"><?php echo IBP_Home::title_html( $settings['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h2>
					<p><?php echo esc_html( $settings['text'] ); ?></p>
					<ul class="ibp-h-checks">
						<?php foreach ( array_filter( array_map( 'trim', explode( "\n", (string) $settings['features'] ) ) ) as $line ) : ?>
							<li><?php echo $check; // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $line ); ?></li>
						<?php endforeach; ?>
					</ul>
					<?php if ( '' !== $settings['button'] && '' !== $settings['url'] ) : ?>
						<a class="ibp-h-btn ibp-h-btn--primary" href="<?php echo esc_url( $settings['url'] ); ?>"><?php echo esc_html( $settings['button'] ); ?><?php echo IBP_Icons::svg( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
					<?php endif; ?>
				</div>
				<div class="ibp-h-plans">
					<?php foreach ( (array) $settings['plans'] as $plan ) : ?>
						<?php $featured = 'yes' === $plan['featured']; ?>
						<div class="ibp-h-plan<?php echo $featured ? ' is-featured' : ''; ?>">
							<?php if ( $featured && '' !== $settings['featured_label'] ) : ?>
								<span class="ibp-h-plan__flag"><?php echo esc_html( $settings['featured_label'] ); ?></span>
							<?php endif; ?>
							<h3><?php echo esc_html( $plan['name'] ); ?></h3>
							<p class="ibp-h-plan__desc"><?php echo esc_html( $plan['desc'] ); ?></p>
							<div class="ibp-h-plan__price"><b><?php echo esc_html( $plan['price'] ); ?></b><span><?php echo esc_html( $plan['period'] ); ?></span></div>
							<ul class="ibp-h-checks ibp-h-checks--sm">
								<?php foreach ( array_filter( array_map( 'trim', explode( "\n", (string) $plan['features'] ) ) ) as $line ) : ?>
									<li><?php echo $check; // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $line ); ?></li>
								<?php endforeach; ?>
							</ul>
							<?php if ( '' !== $plan['button'] ) : ?>
								<a class="ibp-h-btn <?php echo $featured ? 'ibp-h-btn--primary' : 'ibp-h-btn--ghost'; ?>" href="<?php echo esc_url( $plan['url'] ?: ( $settings['url'] ?: '#' ) ); ?>"><?php echo esc_html( $plan['button'] ); ?></a>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
	}
}
