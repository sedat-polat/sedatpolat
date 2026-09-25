<?php
/**
 * Bağlı işletmeler: marka için istekler ve bağlı işletme listesi,
 * alt işletme için bağlantı durumu ya da "markaya bağlan" formu.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;

class IBP_Widget_Network extends IBP_Widget_Base {

	public function get_name() {
		return 'ibp-network';
	}

	public function get_title() {
		return 'Bağlı İşletmeler';
	}

	public function get_icon() {
		return 'eicon-sitemap';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'İçerik' ) );
		$this->add_control(
			'title',
			array(
				'label'   => 'Başlık',
				'type'    => Controls_Manager::TEXT,
				'default' => 'Bağlı işletmeler',
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
		$is_owner = (int) $business->post->post_author === get_current_user_id();
		$pending  = IBP_Network::children( $business->id, 'pending' );
		$children = IBP_Network::children( $business->id );
		$link     = IBP_Network::link_of( $business->id );
		?>
		<div class="ibp ibp-net">
			<?php $this->notice(); ?>

			<?php if ( $pending && $is_owner ) : ?>
				<div class="ibp-card">
					<div class="ibp-card__head">
						<div>
							<h2 class="ibp-card__title">Bağlanma istekleri</h2>
							<div class="ibp-card__sub"><?php echo esc_html( sprintf( '%s markasına bağlanmak isteyen işletmeler.', $business->name() ) ); ?></div>
						</div>
					</div>
					<div class="ibp-net__list">
						<?php foreach ( $pending as $id ) : ?>
							<?php $item = IBP_Business::from_id( $id ); ?>
							<?php $child_link = IBP_Network::link_of( $id ); ?>
							<div class="ibp-net__row">
								<div class="ibp-net__main">
									<div class="ibp-net__name"><?php echo esc_html( $item->name() ); ?></div>
									<div class="ibp-net__meta"><?php echo esc_html( implode( ' · ', array_filter( array( IBP_Network::types()[ $child_link['type'] ], $item->city(), $this->since( $id ) ) ) ) ); ?></div>
								</div>
								<div class="ibp-net__actions">
									<a class="ibp-btn ibp-btn--sm ibp-btn--ghost" href="<?php echo esc_url( $item->permalink() ); ?>" target="_blank" rel="noopener">Sayfası</a>
									<?php $this->action_button( 'reject', $id, 'Reddet', 'ibp-btn ibp-btn--sm' ); ?>
									<?php $this->action_button( 'approve', $id, 'Onayla', 'ibp-btn ibp-btn--sm ibp-btn--primary' ); ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $children ) : ?>
				<?php $this->render_children( $business, $children, $settings, $is_owner ); ?>
			<?php endif; ?>

			<?php if ( $link ) : ?>
				<?php $this->render_link( $business, $link, $is_owner ); ?>
			<?php elseif ( ! $children && ! $pending && $is_owner ) : ?>
				<?php $this->render_connect( $business ); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	private function render_children( IBP_Business $brand, array $children, array $settings, $is_owner ) {
		$days = IBP_Tracker::period();
		?>
		<div class="ibp-card">
			<div class="ibp-card__head">
				<div>
					<h2 class="ibp-card__title"><?php echo esc_html( $settings['title'] ); ?></h2>
					<div class="ibp-card__sub"><?php echo esc_html( sprintf( '%s markasına bağlı %d işletme. Görüntüleme son %d gün.', $brand->name(), count( $children ), $days ) ); ?></div>
				</div>
				<a class="ibp-btn ibp-btn--sm" href="<?php echo esc_url( IBP_Business::switch_url( 'brand:' . $brand->id ) ); ?>">Toplu görünüm</a>
			</div>
			<div class="ibp-net__table" role="table">
				<div class="ibp-net__tr ibp-net__th" role="row">
					<span role="columnheader">İşletme</span>
					<span role="columnheader">Tür</span>
					<span role="columnheader" class="ibp-num">Görüntüleme</span>
					<span role="columnheader" class="ibp-num">Puan</span>
					<span role="columnheader" class="ibp-num">Doluluk</span>
					<span role="columnheader"></span>
				</div>
				<?php foreach ( $children as $id ) : ?>
					<?php
					$item   = IBP_Business::from_id( $id );
					$link   = IBP_Network::link_of( $id );
					$rating = $item->review_stats()['average'];
					?>
					<div class="ibp-net__tr" role="row">
						<span role="cell" class="ibp-net__cell-main">
							<span class="ibp-net__name"><?php echo esc_html( $item->name() ); ?></span>
							<span class="ibp-net__meta"><?php echo esc_html( $item->city() ); ?></span>
						</span>
						<span role="cell"><span class="ibp-pill ibp-pill--<?php echo esc_attr( $link['type'] ); ?>"><?php echo esc_html( IBP_Network::types()[ $link['type'] ] ); ?></span></span>
						<span role="cell" class="ibp-num" data-label="Görüntüleme"><?php echo esc_html( number_format_i18n( IBP_Tracker::summary( $id, 'view', $days )['total'] ) ); ?></span>
						<span role="cell" class="ibp-num" data-label="Puan"><?php echo esc_html( null === $rating ? '–' : number_format_i18n( $rating, 1 ) ); ?></span>
						<span role="cell" class="ibp-num" data-label="Doluluk">%<?php echo esc_html( $item->completeness()['percent'] ); ?></span>
						<span role="cell" class="ibp-net__actions">
							<a class="ibp-btn ibp-btn--sm" href="<?php echo esc_url( IBP_Business::switch_url( $id ) ); ?>"><?php echo 'sube' === $link['type'] ? 'Yönet' : 'İncele'; ?></a>
							<?php if ( $is_owner ) : ?>
								<?php $this->action_button( 'unlink', $id, 'Kaldır', 'ibp-btn ibp-btn--sm ibp-btn--ghost', 'Bu işletmenin markayla bağlantısı kaldırılsın mı?' ); ?>
							<?php endif; ?>
						</span>
					</div>
				<?php endforeach; ?>
			</div>
			<div class="ibp-card__note ibp-net__foot">Şubeleri yönetebilirsin; bayi ve franchise'ların verilerini görebilirsin ama profillerini sahipleri düzenler.</div>
		</div>
		<?php
	}

	private function render_link( IBP_Business $business, array $link, $is_owner ) {
		$brand   = get_the_title( $link['parent'] );
		$type    = IBP_Network::types()[ $link['type'] ];
		$pending = 'pending' === $link['status'];
		?>
		<div class="ibp-card">
			<div class="ibp-card__head">
				<h2 class="ibp-card__title">Marka bağlantısı</h2>
				<span class="ibp-pill <?php echo $pending ? 'ibp-pill--pending' : 'ibp-pill--ok'; ?>"><?php echo $pending ? 'Onay bekliyor' : 'Bağlı'; ?></span>
			</div>
			<div class="ibp-card__body ibp-stack">
				<div class="ibp-net__status">
					<?php
					echo esc_html(
						$pending
							? sprintf( '%s markasına %s olarak bağlanma isteğin gönderildi. Marka onaylayınca panelinde görüneceksin.', $brand, IBP_Business::lower_tr( $type ) )
							: sprintf( '%s, %s %s.', $business->name(), $brand, IBP_Network::possessive( $link['type'] ) )
					);
					?>
				</div>
				<?php if ( ! $pending ) : ?>
					<div class="ibp-card__note">
						<?php
						echo esc_html(
							'sube' === $link['type']
								? 'Şube olduğun için marka bu işletmenin panelini görebilir ve profilini düzenleyebilir.'
								: 'Marka bu işletmenin istatistiklerini, puanını ve yorumlarını görebilir; profili yalnız sen düzenlersin.'
						);
						?>
					</div>
				<?php endif; ?>
				<?php if ( $is_owner ) : ?>
					<div>
						<?php $this->action_button( $pending ? 'cancel' : 'unlink', $business->id, $pending ? 'İsteği geri çek' : 'Markadan ayrıl', 'ibp-btn ibp-btn--sm', $pending ? '' : 'Markayla bağlantın kaldırılsın mı?' ); ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	private function render_connect( IBP_Business $business ) {
		$query   = isset( $_GET['ibp_q'] ) ? sanitize_text_field( wp_unslash( $_GET['ibp_q'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$results = IBP_Network::search_brands( $query, $business->id );
		?>
		<div class="ibp-card">
			<div class="ibp-card__head">
				<div>
					<h2 class="ibp-card__title">Bir markaya bağlan</h2>
					<div class="ibp-card__sub"><?php echo esc_html( sprintf( '%s bir markanın şubesi, bayisi ya da franchise\'ı ise markayı bul ve istek gönder. Marka onaylayınca bağlanırsın.', $business->name() ) ); ?></div>
				</div>
			</div>
			<div class="ibp-card__body ibp-stack">
				<form class="ibp-net__search" method="get">
					<?php foreach ( $_GET as $key => $value ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
						<?php if ( is_string( $value ) && ! in_array( $key, array( 'ibp_q', 'ibp_net' ), true ) ) : ?>
							<input type="hidden" name="<?php echo esc_attr( sanitize_key( $key ) ); ?>" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $value ) ) ); ?>">
						<?php endif; ?>
					<?php endforeach; ?>
					<label class="ibp-sr" for="ibp-net-q-<?php echo esc_attr( $this->get_id() ); ?>">Marka adı</label>
					<input id="ibp-net-q-<?php echo esc_attr( $this->get_id() ); ?>" class="ibp-input" type="search" name="ibp_q" value="<?php echo esc_attr( $query ); ?>" placeholder="Marka adı, ör. Arçelik" minlength="2" required>
					<button type="submit" class="ibp-btn">Ara</button>
				</form>

				<?php if ( '' !== $query && ! $results ) : ?>
					<div class="ibp-muted">Bu adla bağlanılabilecek bir marka bulunamadı.</div>
				<?php endif; ?>

				<?php foreach ( $results as $id ) : ?>
					<?php $item = IBP_Business::from_id( $id ); ?>
					<form class="ibp-net__row" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( IBP_Network::NONCE ); ?>
						<input type="hidden" name="action" value="ibp_net_request">
						<input type="hidden" name="child" value="<?php echo esc_attr( $business->id ); ?>">
						<input type="hidden" name="parent" value="<?php echo esc_attr( $id ); ?>">
						<div class="ibp-net__main">
							<div class="ibp-net__name"><?php echo esc_html( $item->name() ); ?></div>
							<div class="ibp-net__meta"><?php echo esc_html( $item->subtitle() ); ?></div>
						</div>
						<div class="ibp-net__actions">
							<label class="ibp-sr" for="ibp-net-type-<?php echo esc_attr( $id ); ?>">Bağlantı türü</label>
							<select id="ibp-net-type-<?php echo esc_attr( $id ); ?>" class="ibp-select ibp-select--inline" name="type" required>
								<option value="">Tür seç…</option>
								<?php foreach ( IBP_Network::types() as $type => $label ) : ?>
									<option value="<?php echo esc_attr( $type ); ?>"><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
							<button type="submit" class="ibp-btn ibp-btn--sm ibp-btn--primary">İstek gönder</button>
						</div>
					</form>
				<?php endforeach; ?>

				<div class="ibp-card__note">Markaysan bir şey yapmana gerek yok: bağlı işletmelerin sana istek gönderir, istekler burada ve Bekleyen işler'de görünür.</div>
			</div>
		</div>
		<?php
	}

	private function action_button( $action, $child_id, $label, $class, $confirm = '' ) {
		?>
		<form class="ibp-inline-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"<?php echo $confirm ? ' onsubmit="return confirm(' . esc_attr( wp_json_encode( $confirm ) ) . ')"' : ''; ?>>
			<?php wp_nonce_field( IBP_Network::NONCE ); ?>
			<input type="hidden" name="action" value="<?php echo esc_attr( 'ibp_net_' . $action ); ?>">
			<input type="hidden" name="child" value="<?php echo esc_attr( $child_id ); ?>">
			<button type="submit" class="<?php echo esc_attr( $class ); ?>"<?php echo IBP_Business::is_editor_preview() ? ' disabled' : ''; ?>><?php echo esc_html( $label ); ?></button>
		</form>
		<?php
	}

	private function notice() {
		$result = isset( $_GET['ibp_net'] ) ? sanitize_text_field( wp_unslash( $_GET['ibp_net'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '' === $result ) {
			return;
		}
		$messages = array(
			'requested' => 'İsteğin markaya gönderildi.',
			'approved'  => 'İşletme markana bağlandı.',
			'rejected'  => 'İstek reddedildi.',
			'unlinked'  => 'Bağlantı kaldırıldı.',
		);
		$error = 0 === strpos( $result, 'error:' );
		$text  = $error ? substr( $result, 6 ) : ( $messages[ $result ] ?? '' );
		if ( '' !== $text ) {
			printf( '<div class="ibp-alert%s" role="status">%s</div>', $error ? ' ibp-alert--error' : '', esc_html( $text ) );
		}
	}

	private function since( $id ) {
		$time = (int) get_post_meta( $id, IBP_Network::SINCE, true );
		return $time ? sprintf( '%s önce istedi', human_time_diff( $time ) ) : '';
	}
}
