<?php
/**
 * Voxel "Work hours" alanının değerini okur.
 *
 * Voxel değeri şu biçimde saklar:
 * [ { "days": ["mon","tue"], "status": "hours", "hours": [ { "from": "09:00", "to": "17:00" } ] }, ... ]
 * status: hours | open | closed | appointments_only
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IBP_Work_Hours {

	const DAYS = array( 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' );

	/**
	 * @param mixed $raw Voxel alan değeri (dizi ya da JSON metni).
	 * @return array Gruplar; okunamazsa boş dizi.
	 */
	public static function normalize( $raw ) {
		if ( is_string( $raw ) ) {
			$raw = json_decode( $raw, true );
		}
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$groups = array();
		foreach ( $raw as $group ) {
			if ( ! is_array( $group ) || empty( $group['days'] ) || ! is_array( $group['days'] ) ) {
				continue;
			}
			$ranges = array();
			foreach ( (array) ( $group['hours'] ?? array() ) as $range ) {
				if ( isset( $range['from'], $range['to'] ) && self::is_time( $range['from'] ) && self::is_time( $range['to'] ) ) {
					$ranges[] = array( 'from' => $range['from'], 'to' => $range['to'] );
				}
			}
			$groups[] = array(
				'days'   => array_values( array_intersect( $group['days'], self::DAYS ) ),
				'status' => (string) ( $group['status'] ?? 'hours' ),
				'hours'  => $ranges,
			);
		}
		return $groups;
	}

	/**
	 * Verilen günün durumu.
	 *
	 * @return array{status:string, ranges:array, open_now:bool}
	 *         status: hours | open | closed | appointments | unknown
	 */
	public static function for_day( array $groups, DateTimeInterface $now ) {
		$index = ( (int) $now->format( 'N' ) ) - 1;
		$day   = self::DAYS[ $index ];
		$time  = $now->format( 'H:i' );

		// Dün gece yarısını geçen aralık hâlâ sürüyor olabilir (ör. 18:00 – 02:00).
		$after_midnight = self::spills_into( self::ranges_of( $groups, self::DAYS[ ( $index + 6 ) % 7 ] ), $time );

		foreach ( $groups as $group ) {
			if ( ! in_array( $day, $group['days'], true ) ) {
				continue;
			}
			switch ( $group['status'] ) {
				case 'open':
					return array( 'status' => 'open', 'ranges' => array(), 'open_now' => true );
				case 'closed':
					return array( 'status' => 'closed', 'ranges' => array(), 'open_now' => $after_midnight );
				case 'appointments_only':
					return array( 'status' => 'appointments', 'ranges' => array(), 'open_now' => $after_midnight );
				default:
					if ( empty( $group['hours'] ) ) {
						return array( 'status' => 'closed', 'ranges' => array(), 'open_now' => $after_midnight );
					}
					return array(
						'status'   => 'hours',
						'ranges'   => $group['hours'],
						'open_now' => $after_midnight || self::in_ranges( $group['hours'], $time ),
					);
			}
		}

		return array( 'status' => 'unknown', 'ranges' => array(), 'open_now' => $after_midnight );
	}

	/**
	 * "Bugün 11:30 – 23:00 arası açıksın." gibi bir cümle üretir.
	 */
	public static function sentence( array $today ) {
		switch ( $today['status'] ) {
			case 'open':
				return 'Bugün gün boyu açıksın.';
			case 'closed':
				return 'Bugün kapalısın.';
			case 'appointments':
				return 'Bugün sadece randevuyla hizmet veriyorsun.';
			case 'hours':
				$parts = array_map(
					function ( $r ) {
						return $r['from'] . ' – ' . $r['to'];
					},
					$today['ranges']
				);
				return 'Bugün ' . implode( ', ', $parts ) . ' arası açıksın.';
		}
		return '';
	}

	private static function in_ranges( array $ranges, $time ) {
		foreach ( $ranges as $r ) {
			if ( $r['from'] <= $r['to'] ) {
				if ( $time >= $r['from'] && $time < $r['to'] ) {
					return true;
				}
			} elseif ( $time >= $r['from'] ) {
				// Gece yarısını geçen aralığın bugüne düşen kısmı.
				return true;
			}
		}
		return false;
	}

	private static function spills_into( array $ranges, $time ) {
		foreach ( $ranges as $r ) {
			if ( $r['from'] > $r['to'] && $time < $r['to'] ) {
				return true;
			}
		}
		return false;
	}

	private static function ranges_of( array $groups, $day ) {
		foreach ( $groups as $group ) {
			if ( in_array( $day, $group['days'], true ) ) {
				return 'hours' === $group['status'] ? $group['hours'] : array();
			}
		}
		return array();
	}

	private static function is_time( $value ) {
		return is_string( $value ) && preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $value );
	}
}
