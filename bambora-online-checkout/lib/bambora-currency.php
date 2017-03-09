<?php
/**
 * Copyright (c) 2017. All rights reserved Bambora Online.
 *
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software.
 * It is also not legal to do any changes to the software and distribute it in your own name / brand.
 *
 * All use of the payment modules happens at your own risk. We offer a free test account that you can use to test the module.
 *
 * @author    Bambora Online
 * @copyright Bambora Online (http://bambora.com)
 * @license   Bambora Online
 *
 */

/**
 * Bambora Currency
 */
class Bambora_Currency {

	/**
	 * Converts an amount to the specified minor units format
	 *
	 * @param float|int $amount
	 * @param int       $minorunits
	 * @return int
	 */
	public static function convert_price_to_minorunits( $amount, $minorunits ) {
		if ( ! isset( $amount ) ) {
			return 0;
		}

		return $amount * pow( 10, $minorunits );
	}

	/**
	 * Converts an amount from the specified minor units format
	 *
	 * @param float|int $amount
	 * @param int       $minorunits
	 * @param string    $decimal_seperator
	 * @return string
	 */
	public static function convert_price_from_minorunits( $amount, $minorunits, $decimal_seperator = '.' ) {
		if ( ! isset( $amount ) ) {
			return 0;
		}

		return number_format( $amount / pow( 10, $minorunits ), $minorunits, $decimal_seperator, '' );
	}

	/**
	 * Get minor unit format for the specified currency
	 *
	 * @param string $currency_code
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
