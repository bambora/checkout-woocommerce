<?php
class BamboraHelper
{
    /**
     * Create Bambora Checkout payment HTML
     *
     * @param string $bamboraCheckoutJsUrl
     * @param int $windowState
     * @param string $bamboraCheckoutUrl
     * @param string $cancelUrl
     * @return string
     */
    public static function createBamboraCheckoutPaymentHtml($bamboraCheckoutJsUrl, $windowState, $bamboraCheckoutUrl, $cancelUrl)
    {
        $html = '<section>';
        $html .=  '<h3>'.__('Thank you for using Bambora Checkout.', 'woocommerce-gateway-bambora').'</h3>';
        $html .= '<p>'.__('Please wait...', 'woocommerce-gateway-bambora').'</p>';
        $html .= "<script type='text/javascript'>
                     (function (n, t, i, r, u, f, e) { n[u] = n[u] || function() {
                        (n[u].q = n[u].q || []).push(arguments)}; f = t.createElement(i);
                        e = t.getElementsByTagName(i)[0]; f.async = 1; f.src = r; e.parentNode.insertBefore(f, e)
                        })(window, document, 'script','{$bamboraCheckoutJsUrl}', 'bam');

                        var onClose = function(){
                            window.location.href = '{$cancelUrl}';
                        };

                        var options = {
                            'windowstate': {$windowState},
                            'onClose': onClose
                        };

                        bam('open', '{$bamboraCheckoutUrl}', options);
                </script>";
        $html .= "</section>";

        return $html;
    }

    /**
     * Generate Bambora API key
     * 
     * @param string $merchant
     * @param string $accesstoken
     * @param string $secrettoken
     * @return string
     */
    public static function generateApiKey($merchant, $accesstoken, $secrettoken)
    {
        $combined = $accesstoken . '@' . $merchant .':'. $secrettoken;
        $encodedKey = base64_encode($combined);
        $apiKey = 'Basic '.$encodedKey;

        return $apiKey;
    }

    /**
     * Returns the module header
     *
     * @return string
     */
    public static function getModuleHeaderInfo()
    {
        global $woocommerce;

        $bamboraVersion = WC_Gateway_Bambora::MODULE_VERSION;
        $woocommerceVersion = $woocommerce->version;
        $result = 'WooCommerce/' . $woocommerceVersion . ' Module/' . $bamboraVersion;
        return $result;
    }
}