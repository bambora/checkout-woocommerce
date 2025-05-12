<?php

/**
 * Bambora Online Checkout Currency
 */
class Bambora_Online_Checkout_Currency {
	const ROUND_UP      = 'round_up';
	const ROUND_DOWN    = 'round_down';
	const ROUND_DEFAULT = 'round_default';

	/**
	 * Converts an amount to the specified minor units format
	 *
	 * @param mixed  $amount - Amount.
	 * @param int    $minorunits - MinorUnits.
	 * @param string $rounding - Rounding Mode.
	 * @return int
	 */
	public static function convert_price_to_minorunits( $amount, $minorunits, $rounding ) {
		if ( ! isset( $amount ) || empty( $amount ) ) {
			return 0;
		}

		switch ( $rounding ) {
			case self::ROUND_UP:
				return ceil( (float) $amount * pow( 10, $minorunits ) );
			case self::ROUND_DOWN:
				return floor( (float) $amount * pow( 10, $minorunits ) );
			default:
				return round( (float) $amount * pow( 10, $minorunits ) );
		}
	}

	/**
	 * Get roundingmode
	 *
	 * @param string $rounding - Rounding Mode.
	 * @return int
	 */
	public static function roundingmode( $rounding ) {
		switch ( $rounding ) {
			case self::ROUND_UP:
				return PHP_ROUND_HALF_UP;
			case self::ROUND_DOWN:
				return PHP_ROUND_HALF_DOWN;
			default:
				return PHP_ROUND_HALF_EVEN;
		}
	}


	/**
	 * Convert an amount from minorunits
	 *
	 * @param float $amount_in_minorunits - Amount in MinorUnits.
	 * @param int   $minorunits - MinorUnits.
	 * @return float
	 */
	public static function convert_price_from_minorunits( $amount_in_minorunits, $minorunits ) {
		if ( empty( $amount_in_minorunits ) || 0 === $amount_in_minorunits ) {
			return 0;
		}
		return (float) ( $amount_in_minorunits / pow( 10, $minorunits ) );
	}


	/**
	 * Get minor unit format for the specified currency
	 *
	 * @param string $currency_code - Currency Code.
	 * @return int
	 */
	public static function get_currency_minorunits( $currency_code ) {
		$currency_minorunits = array(
			'TTD' => 0,
			'KMF' => 0,
			'ADP' => 0,
			'TPE' => 0,
			'BIF' => 0,
			'DJF' => 0,
			'MGF' => 0,
			'XPF' => 0,
			'GNF' => 0,
			'BYR' => 0,
			'PYG' => 0,
			'JPY' => 0,
			'CLP' => 0,
			'XAF' => 0,
			'TRL' => 0,
			'VUV' => 0,
			'CLF' => 0,
			'KRW' => 0,
			'XOF' => 0,
			'RWF' => 0,
			'ISK' => 0,
			'IQD' => 3,
			'TND' => 3,
			'BHD' => 3,
			'JOD' => 3,
			'OMR' => 3,
			'KWD' => 3,
			'LYD' => 3,
		);

		if ( array_key_exists( $currency_code, $currency_minorunits ) ) {
			return $currency_minorunits[ $currency_code ];
		} else {
			return 2;
		}
	}
}
