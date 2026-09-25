<?php
/**
 * "Şehrin Sahipleri": aynı şehir ve kategorideki işletmelerin puan sıralaması.
 *
 * Puan, Voxel yorum ortalamasının ağırlıklı (Bayes) hâlidir: az yorumlu işletmeler
 * kategori ortalamasına çekilir, böylece tek bir 5 yıldızla zirveye çıkılamaz.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IBP_Ranking {

	const CACHE_GROUP = 'ibp_ranking';
	const WEIGHT      = 10; // Ortalamaya çekme gücü (yorum sayısı cinsinden).

	/**
	 * @return array{city:string, category:string, board:array, me:array|null, rank:int|null, gap:float|null, leader:bool, scale:int}
	 */
	public static function for_business( IBP_Business $business ) {
		$scale    = (int) IBP_Settings::get( 'ranking_scale' );
		$city     = $business->city();
		$taxonomy = IBP_Business::taxonomy_by_label( 'Kategori' );
		$terms    = $taxonomy ? get_the_terms( $business->post, $taxonomy ) : false;
		$term     = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0] : null;

		$result = array(
			'city'     => $city,
			'category' => $term ? $term->name : '',
			'board'    => array(),
			'me'       => null,
			'rank'     => null,
			'gap'      => null,
			'leader'   => false,
			'scale'    => $scale,
			'needs_reviews' => 0,
		);
		if ( ! $term || '' === $city ) {
			return $result;
		}

		$board = self::board( $taxonomy, $term->term_id, $city );

		$min = (int) IBP_Settings::get( 'ranking_min_reviews' );
		foreach ( $board as $index => $row ) {
			$board[ $index ]['score'] = $row['score'] * $scale / 5;
			$board[ $index ]['me']    = $row['id'] === $business->id;
		}
		$result['board'] = $board;

		foreach ( $board as $index => $row ) {
			if ( $row['me'] ) {
				$result['me']     = $row;
				$result['rank']   = $index + 1;
				$result['leader'] = 0 === $index;
				$result['gap']    = $index > 0 ? $board[ $index - 1 ]['score'] - $row['score'] : null;
			}
		}
		$result['needs_reviews'] = null === $result['me'] ? max( 0, $min - $business->review_stats()['count'] ) : 0;

		return $result;
	}

	/**
	 * Bir şehir ve kategorinin sıralaması (saatlik önbellekli). Puanlar 1–5 ölçeğinde.
	 *
	 * @return array[] id, name, district, average, count, score
	 */
	public static function board( $taxonomy, $term_id, $city ) {
		$key   = 'ibp_rank_' . md5( $term_id . '|' . IBP_Business::lower_tr( $city ) . '|' . IBP_Settings::get( 'ranking_min_reviews' ) );
		$board = get_transient( $key );
		if ( ! is_array( $board ) ) {
			$board = self::build( $taxonomy, $term_id, $city );
			set_transient( $key, $board, HOUR_IN_SECONDS );
			self::remember_leader( $term_id, $city, $board ? (int) $board[0]['id'] : 0 );
		}
		return $board;
	}

	/**
	 * Tahttaki işletmeyi ve ne zamandan beri orada olduğunu saklar.
	 */
	private static function remember_leader( $term_id, $city, $leader ) {
		$key     = $term_id . '|' . IBP_Business::lower_tr( $city );
		$history = get_option( 'ibp_crowns', array() );
		$history = is_array( $history ) ? $history : array();
		if ( ! $leader ) {
			unset( $history[ $key ] );
		} elseif ( ( $history[ $key ]['id'] ?? 0 ) !== $leader ) {
			$history[ $key ] = array( 'id' => $leader, 'since' => time() );
		}
		update_option( 'ibp_crowns', $history, false );
	}

	/**
	 * İşletme kaç aydır tahtta? 0 = bu ay çıktı; null = tahtta değil.
	 */
	public static function reign_months( $term_id, $city, $leader ) {
		$history = get_option( 'ibp_crowns', array() );
		$entry   = $history[ $term_id . '|' . IBP_Business::lower_tr( $city ) ] ?? null;
		if ( ! $entry || (int) $entry['id'] !== (int) $leader ) {
			return null;
		}
		return (int) floor( ( time() - (int) $entry['since'] ) / ( 30 * DAY_IN_SECONDS ) );
	}

	/**
	 * Ölçeğe çevrilmiş puan (ayarlardaki 5 ya da 10).
	 */
	public static function scaled( $score ) {
		return $score * (int) IBP_Settings::get( 'ranking_scale' ) / 5;
	}

	/**
	 * @return array[] id, name, district, score (1–5 ölçeğinde), count — puana göre sıralı.
	 */
	private static function build( $taxonomy, $term_id, $city ) {
		$ids = get_posts(
			array(
				'post_type'      => IBP_Business::post_type(),
				'post_status'    => 'publish',
				'posts_per_page' => 500,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					array(
						'taxonomy' => $taxonomy,
						'terms'    => array( $term_id ),
					),
				),
			)
		);

		$wanted = IBP_Business::lower_tr( $city );
		$min    = (int) IBP_Settings::get( 'ranking_min_reviews' );
		$rows   = array();

		foreach ( $ids as $id ) {
			$business = IBP_Business::from_id( $id );
			if ( ! $business || IBP_Business::lower_tr( $business->city() ) !== $wanted ) {
				continue;
			}
			$stats = self::stats_from_meta( $id );
			if ( $stats['count'] < $min || null === $stats['average'] ) {
				continue;
			}
			$rows[] = array(
				'id'       => (int) $id,
				'name'     => get_the_title( $id ),
				'district' => self::district( $business ),
				'average'  => $stats['average'],
				'count'    => $stats['count'],
			);
		}

		if ( ! $rows ) {
			return array();
		}

		// Kategori ortalaması (C) ve ağırlıklı puan: (v·R + m·C) / (v + m).
		$mean = array_sum( array_column( $rows, 'average' ) ) / count( $rows );
		foreach ( $rows as $index => $row ) {
			$rows[ $index ]['score'] = ( $row['count'] * $row['average'] + self::WEIGHT * $mean ) / ( $row['count'] + self::WEIGHT );
		}
		usort(
			$rows,
			function ( $a, $b ) {
				return $b['score'] <=> $a['score'] ?: $b['count'] <=> $a['count'];
			}
		);
		return $rows;
	}

	/**
	 * Voxel'in önbelleğe aldığı yorum istatistiği (1–5 ölçeğine çevrilmiş).
	 */
	public static function stats_from_meta( $post_id ) {
		$stats = json_decode( (string) get_post_meta( $post_id, 'voxel:review_stats', true ), true );
		$count = is_array( $stats ) ? (int) ( $stats['total'] ?? 0 ) : 0;
		return array(
			'count'   => $count,
			'average' => ( $count && isset( $stats['average'] ) && is_numeric( $stats['average'] ) ) ? (float) $stats['average'] + 3 : null,
		);
	}

	/**
	 * Satırda şehrin yanında gösterilecek semt: adresin şehirden önceki parçası.
	 */
	public static function district( IBP_Business $business ) {
		$location = $business->field( 'location' );
		if ( is_string( $location ) ) {
			$location = json_decode( $location, true );
		}
		$address = is_array( $location ) ? (string) ( $location['address'] ?? '' ) : '';
		if ( preg_match( '#([^,/\d]+)\s*/\s*[^,]+#u', $address, $match ) ) {
			return trim( $match[1] ); // "34710 Kadıköy/İstanbul" → "Kadıköy"
		}
		return '';
	}

	/**
	 * İşletme kaydedildiğinde ya da yorum geldiğinde sıralama önbelleğini boşaltır.
	 */
	public static function flush() {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_ibp\\_rank\\_%' OR option_name LIKE '\\_transient\\_timeout\\_ibp\\_rank\\_%'" ); // phpcs:ignore WordPress.DB
	}
}
