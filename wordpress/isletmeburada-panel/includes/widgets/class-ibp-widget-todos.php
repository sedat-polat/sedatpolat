<?php
/**
 * Bekleyen işler kartı.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;

class IBP_Widget_Todos extends IBP_Widget_Base {

	public function get_name() {
		return 'ibp-todos';
	}

	public function get_title() {
		return 'Bekleyen İşler';
	}

	public function get_icon() {
		return 'eicon-checkbox';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'İçerik' ) );
		$this->add_control(
			'title',
			array(
				'label'   => 'Başlık',
				'type'    => Controls_Manager::TEXT,
				'default' => 'Bekleyen işler',
			)
		);
		$this->add_control(
			'limit',
			array(
				'label'   => 'En fazla gösterilecek iş',
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 10,
				'default' => 4,
			)
		);
		$this->add_control(
			'reviews_url',
			array(
				'label'       => 'Yorumlar sayfası',
				'description' => 'Kısayollar: {edit}, {view}, {id}.',
				'type'        => Controls_Manager::TEXT,
				'default'     => '{view}',
			)
		);
		$this->add_control(
			'empty_text',
			array(
				'label'   => 'İş yokken gösterilecek yazı',
				'type'    => Controls_Manager::TEXT,
				'default' => 'Bekleyen işin yok. Profilin eksiksiz görünüyor.',
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
		$todos    = array_slice( $business->todos( $settings['reviews_url'] ), 0, max( 1, (int) $settings['limit'] ) );
		?>
		<div class="ibp ibp-card">
			<div class="ibp-card__head">
				<h2 class="ibp-card__title"><?php echo esc_html( $settings['title'] ); ?></h2>
			</div>
			<div class="ibp-todos">
				<?php if ( ! $todos ) : ?>
					<div class="ibp-muted ibp-todos__empty"><?php echo esc_html( $settings['empty_text'] ); ?></div>
				<?php endif; ?>
				<?php foreach ( $todos as $todo ) : ?>
					<a class="ibp-todo" href="<?php echo esc_url( $todo['url'] ); ?>">
						<span class="ibp-todo__n"><?php echo esc_html( $todo['n'] ); ?></span>
						<span class="ibp-todo__text">
							<span class="ibp-todo__title"><?php echo esc_html( $todo['title'] ); ?></span>
							<span class="ibp-todo__sub"><?php echo esc_html( $todo['sub'] ); ?></span>
						</span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}
}
