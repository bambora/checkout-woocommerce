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
 * @copyright Bambora Online (https://bambora.com)
 * @license   Bambora Online
 *
 */

/**
 * Bambora Online Checkout Currency
 */
class Bambora_Online_Checkout_Currency
{
    const ROUND_UP = "round_up";
    const ROUND_DOWN = "round_down";
    const ROUND_DEFAULT = "round_default";

    /**
     * Converts an amount to the specified minor units format
     *
     * @param mixed $amount
     * @param int $minorunits
     * @param string $rounding
     *
     * @return int
     */
    public static function convert_price_to_minorunits($amount, $minorunits, $rounding)
    {
        if (! isset($amount) || $amount == "") {
            return 0;
        }

        switch ($rounding) {
            case Bambora_Online_Checkout_Currency::ROUND_UP:
                $amount = ceil($amount * pow(10, $minorunits));
                break;
            case Bambora_Online_Checkout_Currency::ROUND_DOWN:
                $amount = floor($amount * pow(10, $minorunits));
                break;
            default:
                $amount = round($amount * pow(10, $minorunits));
                break;
        }

        return $amount;
    }

    /**
     * Convert an amount from minorunits
     *
     * @param float $amount_in_minorunits
     * @param int $minorunits
     *
     * @return float
     */
    public static function convert_price_from_minorunits($amount_in_minorunits, $minorunits)
    {
        if (empty($amount_in_minorunits) || $amount_in_minorunits === 0) {
            return 0;
        }

        return (float) ($amount_in_minorunits / pow(10, $minorunits));
    }


    /**
     * Get minor unit format for the specified currency
     *
     * @param string $currency_code
     *
     * @return int
     */
    public static function get_currency_minorunits($currency_code)
    {
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

        if (array_key_exists($currency_code, $currency_minorunits)) {
            return $currency_minorunits[ $currency_code ];
        } else {
            return 2;
        }
    }
}
