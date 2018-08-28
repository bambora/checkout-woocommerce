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
 * Bambora Online Checkout Helper
 */
class Bambora_Online_Checkout_Helper {

    const BAMBORA_ONLINE_CHECKOUT_TRANSACTION_ID_LEGACY = 'Transaction ID';
    const BAMBORA_ONLINE_CHECKOUT_SUBSCRIPTION_ID = 'bambora_online_checkout_subscription_id';
    const BAMBORA_ONLINE_CHECKOUT_SUBSCRIPTION_ID_LEGACY = 'Subscription ID';

    /**
     * Create Bambora Online Checkout payment HTML
     *
     * @param string $bambora_checkout_js_url
     * @param int    $window_state
     * @param string $bambora_checkout_url
     * @param string $cancel_url
     * @return string
     */
    public static function create_bambora_online_checkout_payment_html( $checkout_token, $window_state ) {
        $html = '<section>';
        $html .= '<script src="https://static.bambora.com/checkout-sdk-web/latest/checkout-sdk-web.min.js"></script>';
        $html .= '<h3>' . __( 'Thank you for using Bambora Online Checkout.', 'bambora-online-checkout' ) . '</h3>';
        $html .= '<p>' . __( 'Please wait...', 'bambora-online-checkout' ) . '</p>';
        $html .= "<script type='text/javascript'>
                    var checkoutToken = '{$checkout_token}';
                    var windowState = {$window_state};
                    if(windowState === 1) {
                        new Bambora.RedirectCheckout(checkoutToken);
                    } else {
                        var checkout = new Bambora.ModalCheckout(null);
                        checkout.on(Bambora.Event.Cancel, function(payload) {
                            window.location.href = payload.declineUrl;
                        });
                        checkout.on(Bambora.Event.Close, function(payload) {
                            window.location.href = payload.acceptUrl;
                        })
                        checkout.initialize(checkoutToken).then(function() {
                            checkout.show();
                        });
                    }
                </script>";
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

        $bambora_version = BOC_VERSION;
        $woocommerce_version = $woocommerce->version;
        $php_version = phpversion();
        $result = "WooCommerce/{$woocommerce_version} Module/{$bambora_version} PHP/{$php_version}";
        return $result;
    }

    /**
     * Create the admin debug section
     *
     * @return string
     */
    public static function create_admin_debug_section() {
        $documentation_link = 'https://developer.bambora.com/europe/shopping-carts/shopping-carts/woocommerce';
        $html = '<h3 class="wc-settings-sub-title">Debug</h3>';
        $html .= sprintf( '<a id="boc-admin-documentation" class="button button-primary" href="%s" target="_blank">Module documentation</a>', $documentation_link );
        $html .= sprintf( '<a id="boc-admin-log" class="button" href="%s" target="_blank">View debug logs</a>', Bambora_Online_Checkout::get_instance()->get_boc_logger()->get_admin_link() );

        return $html;
    }


    /**
     * Checks if Woocommerce Subscriptions is enabled or not
     */
    public static function woocommerce_subscription_plugin_is_active() {
        return class_exists('WC_Subscriptions') && WC_Subscriptions::$name = 'subscription';
    }

    /**
     * Get the subscription for a renewal order
     *
     * @param WC_Order $renewal_order
     * @return WC_Subscription|null
     */
    public static function get_subscriptions_for_renewal_order( $renewal_order ) {
        if( function_exists( 'wcs_get_subscriptions_for_renewal_order' ) ) {
            $subscriptions = wcs_get_subscriptions_for_renewal_order( $renewal_order );
            return end( $subscriptions );
        }
        return null;
    }

    /**
     * Check if order contains switching products
     *
     * @param WC_Order|int $order The WC_Order object or ID of a WC_Order order.
     * @return bool
     */
    public static function order_contains_switch( $order ) {
        if (function_exists( 'wcs_order_contains_switch' )) {
            return wcs_order_contains_switch( $order );
        }
        return false;
    }

    /**
     * Check if order contains subscriptions.
     *
     * @param  WC_Order|int $order_id
     * @return bool
     */
    public static function order_contains_subscription( $order_id ) {
        if( function_exists( 'wcs_order_contains_subscription' ) ) {
            return wcs_order_contains_subscription( $order_id ) || wcs_order_contains_renewal( $order_id );
        }
        return false;
    }

    /**
     * Get subscriptions for order
     *
     * @param mixed $order_id
     * @return array
     */
    public static function get_subscriptions_for_order( $order_id ) {
        if( function_exists( 'wcs_get_subscriptions_for_order' ) ) {
            return wcs_get_subscriptions_for_order( $order_id, array( 'order_type' => 'any' ) );
        }
        return array();
    }

    /**
     * Check if an order is of type subscription
     *
     * @param object $order
     * @return boolean
     */
    public static function order_is_subscription( $order ) {
        if( function_exists( 'wcs_is_subscription' ) ) {
            return wcs_is_subscription( $order );
        }
        return false;
    }

    /**
     * Format date time
     *
     * @param string $raw_date_time
     * @return string
     */
    public static function format_date_time( $raw_date_time ) {
        $date_format = wc_date_format();
        $time_format = wc_time_format();
        $date_time_format = "{$date_format} - {$time_format}";
        $date_time = wc_string_to_datetime( $raw_date_time );
        $formated_date = wc_format_datetime( $date_time, $date_time_format );

        return $formated_date;
    }

    /**
     * Determines if the current WooCommerce version is 3.x.x
     *
     * @return boolean
     */
    public static function is_woocommerce_3() {
        return version_compare( WC()->version, '3.0', '>' );
    }

    /**
     * Format a number
     *
     * @param mixed $number
     * @param int   $decimals
     * @return string
     */
    public static function format_number( $number, $decimals, $display_thousand_separator = true ) {
        if($display_thousand_separator) {
            return number_format( $number, $decimals, wc_get_price_decimal_separator(), wc_get_price_thousand_separator() );
        }

        return number_format( $number, $decimals, wc_get_price_decimal_separator(), '' );
    }

    /**
     * Convert action
     *
     * @param string $action
     * @return string
     */
    public static function convert_action( $action ) {
        if ( 'Authorize' === $action ) {
            return __( 'Authorized', 'bambora-online-checkout' );
        } elseif ( 'Capture' === $action ) {
            return __( 'Captured', 'bambora-online-checkout' );
        } elseif ( 'Credit' === $action ) {
            return __( 'Refunded', 'bambora-online-checkout' );
        } elseif ( 'Delete' === $action ) {
            return __( 'Deleted', 'bambora-online-checkout' );
        } else {
            return $action;
        }
    }

    /**
     * Convert message to HTML
     *
     * @param string $type
     * @param string $message
     * @return string
     * */
    public static function message_to_html( $type, $message ) {
        if ( !empty( $type ) ) {
            $first_letter = substr( $type, 0, 1 );
            $first_letter_to_upper = strtoupper( $first_letter );
            $type = str_replace( $first_letter, $first_letter_to_upper, $type );
        }

        $html = '<div id="message" class=" '.$type. ' bambora_message bambora_' . $type . '">
                        <strong>' . $type . '! </strong>'
                    . $message . '</div>';

        return ent2ncr( $html );
    }

    /**
     * Returns the Callback url
     *
     * @param WC_Order $order
     */
    public static function get_bambora_online_checkout_callback_url( $order_id ) {
        $args = array( 'wc-api' => 'Bambora_Online_Checkout', 'wcorderid' => $order_id);
        return add_query_arg( $args , site_url( '/' ) );
    }

    /**
     * Returns the Accept url
     *
     * @param WC_Order $order
     */
    public static function get_accept_url( $order ) {
        if ( method_exists( $order, 'get_checkout_order_received_url' ) ) {
            return str_replace( '&amp;', '&', $order->get_checkout_order_received_url() );
        }

        return add_query_arg( 'key', $order->order_key, add_query_arg(
                'order', Bambora_Online_Checkout_Helper::is_woocommerce_3() ? $order->get_id() : $order->id,
                get_permalink( get_option( 'woocommerce_thanks_page_id' ) )
            )
        );
    }

    /**
     * Returns the Decline url
     *
     * @param WC_Order $order
     */
    public static function get_decline_url( $order ) {
        if ( method_exists( $order, 'get_cancel_order_url' ) ) {
            return str_replace( '&amp;', '&', $order->get_cancel_order_url() );
        }

        return add_query_arg( 'key', $order->get_order_key(), add_query_arg(
                array(
                    'order' => Bambora_Online_Checkout_Helper::is_woocommerce_3() ? $order->get_id() : $order->id,
                    'payment_cancellation' => 'yes',
                ),
                get_permalink( get_option( 'woocommerce_cart_page_id' ) ) )
        );
    }

    /**
     * Validate Callback
     *
     * @param mixed $params
     * @param string $md5_key
     * @param WC_Order $order
     * @param string $message
     * @return bool
     */
    public static function validate_bambora_online_checkout_callback_params( $params, $md5_key, &$order, &$message ) {

        if ( ! isset( $params ) || empty( $params ) ) {
            $message = "No GET parameteres supplied to the system";
            return false;
        }

        // Validate woocommerce order!
        if(empty( $params['wcorderid'] ))
        {
            $message = "No WooCommerce Order Id was supplied to the system!";
            return false;
        }

        $order = wc_get_order( $params['wcorderid'] );
        if ( empty( $order ) ) {
            $message = "Could not find order with WooCommerce Order id {$params["wcorderid"]}";
            return false;
        }

        // Check exists transactionid!
        if ( empty( $params['txnid'] ) ) {
            $message = isset( $params ) ? 'No GET(txnid) was supplied to the system!' : 'Response is null';
            return false;
        }

        // Validate MD5!
        $var = '';
        if ( strlen( $md5_key ) > 0 ) {
            foreach ( $params as $key => $value ) {
                if ( 'hash' !== $key ) {
                    $var .= $value;
                }
            }
            $genstamp = md5( $var . $md5_key );
            if ( ! hash_equals( $genstamp, $params['hash'] ) ) {
                $message = 'Hash validation failed - Please check your MD5 key';
                return false;
            }
        }

        return true;
    }

    /**
     * Get the Bambora Online Checkout Subscription id from the order
     *
     * @param WC_Subscription $subscription
     */
    public static function get_bambora_online_checkout_subscription_id( $subscription ) {

        $subscription_id = Bambora_Online_Checkout_Helper::is_woocommerce_3() ? $subscription->get_id() : $subscription->id;
        $bambora_subscription_id = get_post_meta( $subscription_id, Bambora_Online_Checkout_Helper::BAMBORA_ONLINE_CHECKOUT_SUBSCRIPTION_ID, true );

        //For Legacy
        if( empty( $bambora_subscription_id ) ) {
            $parent_order_id = Bambora_Online_Checkout_Helper::is_woocommerce_3() ? $subscription->get_parent_id() : $subscription->parent_id;
            $bambora_subscription_id = get_post_meta( $parent_order_id, Bambora_Online_Checkout_Helper::BAMBORA_ONLINE_CHECKOUT_SUBSCRIPTION_ID_LEGACY, true);
            if( !empty( $bambora_subscription_id ) ) {
                //Transform Legacy to new standards
                update_post_meta( $subscription_id, Bambora_Online_Checkout_Helper::BAMBORA_ONLINE_CHECKOUT_SUBSCRIPTION_ID, $bambora_subscription_id );
                delete_post_meta( $parent_order_id, Bambora_Online_Checkout_Helper::BAMBORA_ONLINE_CHECKOUT_SUBSCRIPTION_ID_LEGACY );
            }
        }

        return $bambora_subscription_id;
    }

    /**
     * get the Bambora Online Checkout Transaction id from the order
     *
     * @param WC_Order $order
     */
    public static function get_bambora_online_checkout_transaction_id($order) {
        $transaction_id = $order->get_transaction_id();
        //For Legacy
        if( empty( $transaction_id ) ) {
            $order_id = Bambora_Online_Checkout_Helper::is_woocommerce_3() ? $order->get_id() : $order->id;
            $transaction_id = get_post_meta( $order_id, Bambora_Online_Checkout_Helper::BAMBORA_ONLINE_CHECKOUT_TRANSACTION_ID_LEGACY, true );
            if( !empty( $transaction_id ) ) {
                //Transform Legacy to new standards
                delete_post_meta( $order_id, Bambora_Online_Checkout_Helper::BAMBORA_ONLINE_CHECKOUT_TRANSACTION_ID_LEGACY );
                $order->set_transaction_id( $transaction_id );
                $order->save();
            }
        }

        return $transaction_id;
    }
}
