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
 * Bambora Helper
 */
class Bambora_Helper {

    /**
     * Create Bambora Checkout payment HTML
     *
     * @param string $bambora_checkout_js_url
     * @param int    $window_state
     * @param string $bambora_checkout_url
     * @param string $cancel_url
     * @return string
     */
    public static function create_bambora_checkout_payment_html( $bambora_checkout_js_url, $window_state, $bambora_checkout_url, $cancel_url ) {
        $html = '<section>';
        $html .= '<h3>' . __( 'Thank you for using Bambora Checkout.', 'bambora-online-checkout' ) . '</h3>';
        $html .= '<p>' . __( 'Please wait...', 'bambora-online-checkout' ) . '</p>';
        $html .= "<script type='text/javascript'>
                     (function (n, t, i, r, u, f, e) { n[u] = n[u] || function() {
                        (n[u].q = n[u].q || []).push(arguments)}; f = t.createElement(i);
                        e = t.getElementsByTagName(i)[0]; f.async = 1; f.src = r; e.parentNode.insertBefore(f, e)
                        })(window, document, 'script','{$bambora_checkout_js_url}', 'bam');

                        var onClose = function(){
                            window.location.href = '{$cancel_url}';
                        };

                        var options = {
                            'windowstate': {$window_state},
                            'onClose': onClose
                        };

                        bam('open', '{$bambora_checkout_url}', options);
                </script>";
        $html .= '</section>';

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
    public static function generate_api_key( $merchant, $accesstoken, $secrettoken ) {
        $combined = $accesstoken . '@' . $merchant . ':' . $secrettoken;
        $encoded_key = base64_encode( $combined );
        $api_key = 'Basic ' . $encoded_key;

        return $api_key;
    }

    /**
     * Returns the module header
     *
     * @return string
     */
    public static function get_module_header_info() {
        global $woocommerce;

        $bambora_version = Bambora_Online_Checkout::MODULE_VERSION;
        $woocommerce_version = $woocommerce->version;
        $result = 'WooCommerce/' . $woocommerce_version . ' Module/' . $bambora_version;
        return $result;
    }
}
