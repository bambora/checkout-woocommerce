<?php
class BamboraHelper
{
    public static function create_bambora_paymentscript($paymentWindowUrl, $windowState, $bamboraCheckoutUrl, $runOnLoad)
    {
        return "<script type='text/javascript'>
                     (function (n, t, i, r, u, f, e) { n[u] = n[u] || function() {
                        (n[u].q = n[u].q || []).push(arguments)}; f = t.createElement(i);
                        e = t.getElementsByTagName(i)[0]; f.async = 1; f.src = r; e.parentNode.insertBefore(f, e)
                        })(window, document, 'script','".$paymentWindowUrl."', 'bam');
                        var windowstate = ".$windowState.";

                       var options = {
                            'windowstate': windowstate,
                       }

                       function openPaymentWindow()
                       {
                            bam('open', '".$bamboraCheckoutUrl."', options);
                       }
                   
                       if(".$runOnLoad.")
                       {
                            bam('open', '".$bamboraCheckoutUrl."', options);
                       }
                </script>";
    }

    public static function generateApiKey($merchant, $accesstoken, $secrettoken)
    {
        //Basic (accestoken@merchantnumer:secrettoken) -> base64
        $combined = $accesstoken . '@' . $merchant .':'. $secrettoken;
        $encodedKey = base64_encode($combined);
        $apiKey = 'Basic '.$encodedKey;

        return $apiKey;      
    }
}