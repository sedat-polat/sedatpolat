<?php
/**
 * Bireysel panel: sayı kartı (yorum, rehber puanı, favori, takip, rezervasyon/sipariş).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;

class IBP_Widget_User_Stat extends IBP_Widget_Base {

	public function get_name() {
		return 'ibp-user-stat';
	}

	public function get_title() {
		return 'Kişisel İstatistik';
	}

	public function get_icon() {
		return 'eicon-counter';
	}

	public static function sources() {
		return array(
			'points'    => 'Yerel rehber puanı',
			'reviews'   => 'Yazdığım yorumlar',
			'favorites' => 'Favorilerdeki işletmeler',
			'following' => 'Takip ettiğim işletmeler',
			'orders'    => 'Rezervasyon ve siparişler (tümü)',
			'pending'   => 'Onay/ödeme bekleyen rezervasyonlar',
		);
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'İçerik' ) );
		$this->add_control(
			'source',
			array(
				'label'   => 'Değer',
				'type'    => Controls_Manager::SELECT,
				'default' => 'reviews',
				'options' => self::sources(),
			)
		);
		$this->add_control(
			'label',
			array(
				'label'       => 'Etiket',
				'description' => 'Boş bırakılırsa değerin adı yazılır.',
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
			)
		);
		$this->add_control(
			'note',
			array(
				'label'   => 'Alt not',
				'type'    => Controls_Manager::TEXT,
				'default' => '',
			)
		);
		$this->add_control(
			'url',
			array(
				'label'       => 'Bağlantı',
				'description' => 'Doluysa kart tıklanabilir olur.',
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
			)
		);
		$this->end_controls_section();

		$this->add_accent_control();
	}

	protected function render() {
		$me = IBP_User::current();
		if ( ! $me ) {
			return;
		}
		$settings = $this->get_settings_for_display();
		$source   = $settings['source'];
		$label    = '' !== $settings['label'] ? $settings['label'] : ( self::sources()[ $source ] ?? '' );
		$note     = (string) $settings['note'];
		$guide    = null;

		switch ( $source ) {
			case 'points':
				$guide = $me->guide();
				$value = $guide['points'];
				$note  = '' !== $note ? $note : sprintf( 'Yerel rehber %d, sonraki seviyeye %s puan', $guide['level'], number_format_i18n( $guide['to_next'] ) );
				break;
			case 'reviews':
				$value = $me->review_count();
				$note  = '' !== $note ? $note : sprintf( 'Her yorum +%d rehber puanı', (int) IBP_Settings::get( 'points_review' ) );
				break;
			case 'favorites':
				$value = count( $me->favorites()['items'] );
				break;
			case 'following':
				$value = count( $me->following() );
				break;
			case 'pending':
				$value = $me->order_count( array( 'pending_payment', 'pending_approval' ) );
				break;
			default:
				$value = $me->order_count();
		}

		$tag  = '' !== $settings['url'] ? 'a' : 'div';
		$href = 'a' === $tag ? ' href="' . esc_url( $settings['url'] ) . '"' : '';
		?>
		<<?php echo esc_html( $tag ); ?> class="ibp ibp-card ibp-stat<?php echo 'a' === $tag ? ' ibp-stat--link' : ''; ?>"<?php echo $href; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
			<div class="ibp-stat__label"><?php echo esc_html( $label ); ?></div>
			<div class="ibp-stat__value"><?php echo esc_html( number_format_i18n( $value ) ); ?></div>
			<?php if ( $guide ) : ?>
				<div class="ibp-bar ibp-stat__bar"><i style="width: <?php echo esc_attr( $guide['progress'] ); ?>%"></i></div>
			<?php endif; ?>
			<?php if ( '' !== $note ) : ?>
				<div class="ibp-stat__note"><?php echo esc_html( $note ); ?></div>
			<?php endif; ?>
		</<?php echo esc_html( $tag ); ?>>
		<?php
	}
}
