<?php
/**
 * Bireysel panel: Yorumlarım, Favorilerim, Takip ettiklerim ya da
 * Rezervasyon ve siparişlerim listesi.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;

class IBP_Widget_User_List extends IBP_Widget_Base {

	public function get_name() {
		return 'ibp-user-list';
	}

	public function get_title() {
		return 'Kişisel Liste';
	}

	public function get_icon() {
		return 'eicon-bullet-list';
	}

	public static function kinds() {
		return array(
			'reviews'   => array( 'Yorumlarım', 'Henüz yorum yazmadın. Gittiğin bir işletmeyi puanla, her yorum rehber puanı kazandırır.' ),
			'favorites' => array( 'Favorilerim', 'Henüz favorin yok. Beğendiğin işletmeleri kalp simgesiyle kaydedebilirsin.' ),
			'following' => array( 'Takip ettiklerim', 'Henüz bir işletmeyi takip etmiyorsun. Takip ettiğin işletmelerin fırsat ve duyurularını kaçırmazsın.' ),
			'orders'    => array( 'Rezervasyon ve siparişlerim', 'Henüz rezervasyonun ya da siparişin yok.' ),
		);
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'İçerik' ) );
		$this->add_control(
			'kind',
			array(
				'label'   => 'Liste',
				'type'    => Controls_Manager::SELECT,
				'default' => 'reviews',
				'options' => wp_list_pluck( self::kinds(), 0 ),
			)
		);
		$this->add_control(
			'title',
			array(
				'label'       => 'Başlık',
				'description' => 'Boş bırakılırsa listenin adı yazılır.',
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
			)
		);
		$this->add_control(
			'count',
			array(
				'label'   => 'Gösterilecek öğe',
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 20,
				'default' => 4,
			)
		);
		$this->add_control(
			'all_url',
			array(
				'label'       => '"Tümü" bağlantısı',
				'description' => 'Boş bırakılırsa düğme gizlenir.',
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
			)
		);
		$this->add_control(
			'order_url',
			array(
				'label'       => 'Sipariş ayrıntı adresi',
				'description' => 'Voxel sipariş sayfası, ör. /siparislerim/?order_id={id}. Boşsa satırlar tıklanmaz.',
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'condition'   => array( 'kind' => 'orders' ),
			)
		);
		$this->add_control(
			'empty_url',
			array(
				'label'       => 'Liste boşken düğme adresi',
				'description' => 'Ör. işletme arama sayfası. Boş bırakılırsa düğme gizlenir.',
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
			)
		);
		$this->add_control(
			'empty_button',
			array(
				'label'   => 'Liste boşken düğme yazısı',
				'type'    => Controls_Manager::TEXT,
				'default' => 'İşletmeleri keşfet',
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
		$kinds    = self::kinds();
		$kind     = isset( $kinds[ $settings['kind'] ] ) ? $settings['kind'] : 'reviews';
		$limit    = max( 1, (int) $settings['count'] );
		$title    = '' !== $settings['title'] ? $settings['title'] : $kinds[ $kind ][0];
		$rows     = $this->rows( $me, $kind, $limit );
		?>
		<section class="ibp ibp-card ibp-ulist" id="<?php echo esc_attr( 'ibp-' . $kind ); ?>">
			<div class="ibp-card__head">
				<h2 class="ibp-card__title"><?php echo esc_html( $title ); ?></h2>
				<?php if ( '' !== $settings['all_url'] && $rows ) : ?>
					<a class="ibp-btn ibp-btn--sm ibp-btn--ghost" href="<?php echo esc_url( $settings['all_url'] ); ?>">Tümü</a>
				<?php endif; ?>
			</div>
			<div class="ibp-card__body ibp-ulist__body">
				<?php if ( ! $rows ) : ?>
					<div class="ibp-ulist__empty">
						<span class="ibp-muted"><?php echo esc_html( $kinds[ $kind ][1] ); ?></span>
						<?php if ( '' !== $settings['empty_url'] && '' !== $settings['empty_button'] ) : ?>
							<a class="ibp-btn ibp-btn--sm" href="<?php echo esc_url( $settings['empty_url'] ); ?>"><?php echo esc_html( $settings['empty_button'] ); ?></a>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<?php foreach ( $rows as $row ) : ?>
					<?php $linked = '' !== $row['url'] && '#' !== $row['url']; ?>
					<<?php echo $linked ? 'a' : 'div'; ?> class="ibp-ulist__row"<?php echo $linked ? ' href="' . esc_url( $row['url'] ) . '"' : ''; ?>>
						<span class="ibp-ulist__ic"><?php echo IBP_Icons::svg( $row['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<span class="ibp-ulist__main">
							<span class="ibp-ulist__title"><?php echo esc_html( $row['title'] ); ?></span>
							<?php if ( '' !== $row['sub'] ) : ?>
								<span class="ibp-ulist__sub"><?php echo esc_html( $row['sub'] ); ?></span>
							<?php endif; ?>
						</span>
						<span class="ibp-ulist__side">
							<?php if ( ! empty( $row['stars'] ) ) : ?>
								<span class="ibp-stars" role="img" aria-label="<?php echo esc_attr( $row['stars'] . ' / 5 yıldız' ); ?>">
									<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
										<svg viewBox="0 0 24 24" width="12" height="12" aria-hidden="true"><path class="<?php echo $i <= $row['stars'] ? 'on' : ''; ?>" d="M12 3l2.8 5.7 6.2.9-4.5 4.4 1 6.2L12 17.3 6.5 20.2l1-6.2L3 9.6l6.2-.9z"></path></svg>
									<?php endfor; ?>
								</span>
							<?php endif; ?>
							<?php if ( ! empty( $row['pill'] ) ) : ?>
								<span class="ibp-pill ibp-pill--<?php echo esc_attr( $row['pill_tone'] ); ?>"><?php echo esc_html( $row['pill'] ); ?></span>
							<?php endif; ?>
							<?php if ( '' !== $row['meta'] ) : ?>
								<span class="ibp-ulist__meta"><?php echo esc_html( $row['meta'] ); ?></span>
							<?php endif; ?>
						</span>
					</<?php echo $linked ? 'a' : 'div'; ?>>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}

	/**
	 * Listeyi tek biçime çevirir: title, sub, url, icon, meta, stars, pill, pill_tone.
	 */
	private function rows( IBP_User $me, $kind, $limit ) {
		$rows = array();
		switch ( $kind ) {
			case 'reviews':
				foreach ( $me->reviews( $limit ) as $review ) {
					$rows[] = array(
						'title' => $review['business'],
						'sub'   => $review['text'],
						'url'   => $review['url'],
						'icon'  => 'star',
						'meta'  => $review['date'],
						'stars' => $review['stars'],
					);
				}
				break;

			case 'favorites':
			case 'following':
				$ids = 'favorites' === $kind ? $me->favorites()['items'] : $me->following();
				foreach ( array_slice( $ids, 0, $limit ) as $id ) {
					$post = get_post( $id );
					if ( ! $post || 'publish' !== $post->post_status ) {
						continue;
					}
					$business = IBP_Business::from_id( $id );
					$rows[]   = array(
						'title' => get_the_title( $id ),
						'sub'   => $business ? $business->subtitle() : '',
						'url'   => get_permalink( $id ),
						'icon'  => 'favorites' === $kind ? 'heart' : 'bell',
						'meta'  => '',
					);
				}
				break;

			case 'orders':
				foreach ( $me->orders( $limit ) as $order ) {
					$tone   = in_array( $order['status'], array( 'completed', 'sub_active' ), true ) ? 'ok' : ( in_array( $order['status'], array( 'canceled', 'cancelled', 'refunded', 'declined' ), true ) ? 'muted' : 'pending' );
					$rows[] = array(
						'title'     => $order['vendor'] ? $order['vendor'] : sprintf( 'Sipariş #%d', $order['id'] ),
						'sub'       => sprintf( 'Sipariş #%d', $order['id'] ),
						'url'       => '' !== $this->get_settings_for_display()['order_url'] ? str_replace( '{id}', (string) $order['id'], $this->get_settings_for_display()['order_url'] ) : '',
						'icon'      => 'calcheck',
						'meta'      => $order['date'],
						'pill'      => $order['label'],
						'pill_tone' => $tone,
					);
				}
				break;
		}
		return $rows;
	}
}
