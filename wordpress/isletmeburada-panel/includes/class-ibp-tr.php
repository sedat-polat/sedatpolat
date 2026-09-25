<?php
/**
 * Türkçe ek yardımcıları: "İstanbul'un", "İzmir'in", "Ankara'da", "Antep'te".
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IBP_Tr {

	const BACK    = array( 'a', 'ı', 'o', 'u' );
	const VOWELS  = array( 'a', 'e', 'ı', 'i', 'o', 'ö', 'u', 'ü' );
	const HARD    = array( 'ç', 'f', 'h', 'k', 'p', 's', 'ş', 't' ); // Sert ünsüzler: -da → -ta.

	/**
	 * İlgi hâli: İstanbul → İstanbul'un, Ankara → Ankara'nın, İzmir → İzmir'in.
	 */
	public static function genitive( $name ) {
		$name = trim( (string) $name );
		if ( '' === $name ) {
			return '';
		}
		$vowel = self::last_vowel( $name );
		$map   = array( 'a' => 'ın', 'ı' => 'ın', 'e' => 'in', 'i' => 'in', 'o' => 'un', 'u' => 'un', 'ö' => 'ün', 'ü' => 'ün' );
		$suf   = $map[ $vowel ] ?? 'in';
		return $name . "'" . ( self::ends_with_vowel( $name ) ? 'n' : '' ) . $suf;
	}

	/**
	 * Bulunma hâli: İstanbul → İstanbul'da, İzmir → İzmir'de, Antep → Antep'te.
	 */
	public static function locative( $name ) {
		$name = trim( (string) $name );
		if ( '' === $name ) {
			return '';
		}
		$vowel = in_array( self::last_vowel( $name ), self::BACK, true ) ? 'a' : 'e';
		$last  = mb_substr( IBP_Business::lower_tr( $name ), -1 );
		return $name . "'" . ( in_array( $last, self::HARD, true ) ? 't' : 'd' ) . $vowel;
	}

	private static function last_vowel( $name ) {
		$chars = preg_split( '//u', IBP_Business::lower_tr( $name ), -1, PREG_SPLIT_NO_EMPTY );
		for ( $i = count( $chars ) - 1; $i >= 0; $i-- ) {
			if ( in_array( $chars[ $i ], self::VOWELS, true ) ) {
				return $chars[ $i ];
			}
		}
		return 'e';
	}

	private static function ends_with_vowel( $name ) {
		return in_array( mb_substr( IBP_Business::lower_tr( $name ), -1 ), self::VOWELS, true );
	}
}
