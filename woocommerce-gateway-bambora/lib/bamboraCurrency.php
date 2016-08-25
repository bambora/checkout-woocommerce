<?php

/**
 * bamboraCurrency short summary.
 *
 * bamboraCurrency description.
 *
 * @version 1.0
 * @author Allan W. Lie
 */
class BamboraCurrency
{
    private static $currencyMinorUnits = null;

    public static function convertPriceToMinorUnits($amount, $minorUnits, $defaultMinorUnits = 2, $round = true)
    {
        if($minorUnits == "" || $minorUnits == null)
            $minorUnits = $defaultMinorUnits;

        if($amount == "" || $amount == null)
            return 0;

        if($round)
        {
            $amount = round($amount,$minorUnits);
        }

        return $amount * pow(10,$minorUnits);
    }

    public static function convertPriceFromMinorUnits($amount, $minorUnits, $defaultMinorUnits = 2)
    {
        if($minorUnits == "" || $minorUnits == null)
            $minorUnits = $defaultMinorUnits;

        if($amount == "" || $amount == null)
            return 0;

        return $amount / pow(10,$minorUnits);
    }

    public static function getCurrencyMinorunits($currencyCode)
    {
        if(self::$currencyMinorUnits == null)
        {
            self::$currencyMinorUnits = array (
                "TTD" => 0,
                "KMF" => 0,
                "ADP" => 0,
                "TPE" => 0,
                "BIF" => 0,
                "DJF" => 0,
                "MGF" => 0,
                "XPF" => 0,
                "GNF" => 0,
                "BYR" => 0,
                "PYG" => 0,
                "JPY" => 0,
                "CLP" => 0,
                "XAF" => 0,
                "TRL" => 0,
                "VUV" => 0,
                "CLF" => 0,
                "KRW" => 0,
                "XOF" => 0,
                "RWF" => 0,
                "IQD" => 3,
                "TND" => 3,
                "BHD" => 3,
                "JOD" => 3,
                "OMR" => 3,
                "KWD" => 3,
                "LYD" => 3
                );
        }

        if(isset(self::$currencyMinorUnits[$currencyCode]))
        {
            return self::$currencyMinorUnits[$currencyCode];
        }
        else
        {
            return 2;
        }
    }
}

