<?php
/**
 * Copyright (c) 2017. All rights reserved Bambora Online.
 *
 * This program is free software. You are allowed to use the software but NOT
 * allowed to modify the software. It is also not legal to do any changes to the
 * software and distribute it in your own name / brand.
 *
 * All use of the payment modules happens at your own risk. We offer a free test
 * account that you can use to test the module.
 *
 * @author    Bambora Online
 * @copyright Bambora Online (https://bambora.com)
 * @license   Bambora Online
 */

/**
 * Bambora Online Checkout Helper
 */
class Bambora_Online_Checkout_Helper {
	const BAMBORA_ONLINE_CHECKOUT_TRANSACTION_ID_LEGACY = 'Transaction ID';
	const BAMBORA_ONLINE_CHECKOUT_SUBSCRIPTION_ID = 'bambora_online_checkout_subscription_id';
	const BAMBORA_ONLINE_CHECKOUT_SUBSCRIPTION_ID_LEGACY = 'Subscription ID';
	const BAMBORA_ONLINE_CHECKOUT_STATUS_MESSAGES = 'bambora_online_checkout_status_messages';
	const BAMBORA_ONLINE_CHECKOUT_STATUS_MESSAGES_KEEP_FOR_POST = 'bambora_online_checkout_status_messages_keep_for_post';
	const ERROR = 'error';
	const SUCCESS = 'success';

	/**
	 * Generate Bambora API key
	 *
	 * @param string $merchant
	 * @param string $accesstoken
	 * @param string $secrettoken
	 *
	 * @return string
	 */
	public static function generate_api_key( $merchant, $accesstoken, $secrettoken ) {
		$combined    = $accesstoken . '@' . $merchant . ':' . $secrettoken;
		$encoded_key = base64_encode( $combined );
		$api_key     = 'Basic ' . $encoded_key;

		return $api_key;
	}

	/**
	 * Returns the module header
	 *
	 * @return string
	 */
	public static function get_module_header_info() {
		global $woocommerce;

		$bambora_version     = BOC_VERSION;
		$woocommerce_version = $woocommerce->version;
		$php_version         = phpversion();
		$result              = "WooCommerce/{$woocommerce_version} Module/{$bambora_version} PHP/{$php_version}";

		return $result;
	}

	/**
	 * Create the admin debug section
	 *
	 * @return string
	 */
	public static function create_admin_debug_section() {
		$documentation_link = 'https://developer.bambora.com/europe/shopping-carts/shopping-carts/woocommerce';
		$html               = '<h3 class="wc-settings-sub-title">Debug</h3>';
		$html               .= sprintf( '<a id="boc-admin-documentation" class="button button-primary" href="%s" target="_blank">Module documentation</a>', $documentation_link );
		$html               .= sprintf( '<a id="boc-admin-log" class="button" href="%s" target="_blank">View debug logs</a>', Bambora_Online_Checkout::get_instance()->get_boc_logger()->get_admin_link() );

		return $html;
	}


	/**
	 * Checks if Woocommerce Subscriptions is enabled or not
	 */
	public static function woocommerce_subscription_plugin_is_active() {
		return class_exists( 'WC_Subscriptions' ) && WC_Subscriptions::$name = 'subscription';
	}

	/**
	 * Get the subscription for a renewal order
	 *
	 * @param WC_Order $renewal_order
	 *
	 * @return WC_Subscription|null
	 */
	public static function get_subscriptions_for_renewal_order( $renewal_order ) {
		if ( function_exists( 'wcs_get_subscriptions_for_renewal_order' ) ) {
			$subscriptions = wcs_get_subscriptions_for_renewal_order( $renewal_order );

			return end( $subscriptions );
		}

		return null;
	}

	/**
	 * Check if order contains switching products
	 *
	 * @param WC_Order|int $order The WC_Order object or ID of a WC_Order order.
	 *
	 * @return bool
	 */
	public static function order_contains_switch( $order ) {
		if ( function_exists( 'wcs_order_contains_switch' ) ) {
			return wcs_order_contains_switch( $order );
		}

		return false;
	}

	/**
	 * Check if order contains subscriptions.
	 *
	 * @param WC_Order|int $order_id
	 *
	 * @return bool
	 */
	public static function order_contains_subscription( $order_id ) {
		if ( function_exists( 'wcs_order_contains_subscription' ) ) {
			return wcs_order_contains_subscription( $order_id ) || wcs_order_contains_renewal( $order_id );
		}

		return false;
	}

	/**
	 * Get subscriptions for order
	 *
	 * @param mixed $order_id
	 *
	 * @return array
	 */
	public static function get_subscriptions_for_order( $order_id ) {
		if ( function_exists( 'wcs_get_subscriptions_for_order' ) ) {
			return wcs_get_subscriptions_for_order( $order_id, array( 'order_type' => 'any' ) );
		}

		return array();
	}

	/**
	 * Check if an order is of type subscription
	 *
	 * @param object $order
	 *
	 * @return boolean
	 */
	public static function order_is_subscription( $order ) {
		if ( function_exists( 'wcs_is_subscription' ) ) {
			return wcs_is_subscription( $order );
		}

		return false;
	}

	/**
	 * Format date time
	 *
	 * @param string $raw_date_time
	 *
	 * @return string
	 */
	public static function format_date_time( $raw_date_time ) {
		$date_format      = wc_date_format();
		$time_format      = wc_time_format();
		$date_time_format = "{$date_format} - {$time_format}";

		$date_time     = wc_string_to_datetime( $raw_date_time );
		$formated_date = wc_format_datetime( $date_time, $date_time_format );

		return $formated_date;
	}


	/**
	 * Format a number
	 *
	 * @param mixed $number
	 * @param int   $decimals
	 *
	 * @return string
	 */
	public static function format_number( $number, $decimals, $display_thousand_separator = true ) {
		if ( $display_thousand_separator ) {
			return number_format( $number, $decimals, wc_get_price_decimal_separator(), wc_get_price_thousand_separator() );
		}

		return number_format( $number, $decimals, wc_get_price_decimal_separator(), '' );
	}

	/**
	 * Convert action
	 *
	 * @param string $action
	 *
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
	 * Returns the Callback url
	 *
	 * @param WC_Order $order
	 */
	public static function get_bambora_online_checkout_callback_url( $order_id ) {
		$args = array(
			'wc-api'    => 'Bambora_Online_Checkout',
			'wcorderid' => $order_id
		);

		return add_query_arg( $args, site_url( '/' ) );
	}

	/**
	 * Returns the Accept url
	 *
	 * @param WC_Order $order
	 */
	public static function get_accept_url( $order ) {
		if ( method_exists( $order, 'get_checkout_order_received_url' ) ) {
			$acceptUrlRaw  = $order->get_checkout_order_received_url();
			$acceptUrlTemp = str_replace( '&amp;', '&', $acceptUrlRaw );
			$acceptUrl     = str_replace( '&#038', '&', $acceptUrlTemp );

			return $acceptUrl;
		}

		return add_query_arg( 'key', $order->order_key, add_query_arg( 'order', $order->get_id(), get_permalink( get_option( 'woocommerce_thanks_page_id' ) ) ) );
	}

	/**
	 * Returns the Decline url
	 *
	 * @param WC_Order $order
	 */
	public static function get_decline_url( $order ) {
		if ( method_exists( $order, 'get_cancel_order_url' ) ) {
			$declineUrlRaw  = $order->get_cancel_order_url();
			$declineUrlTemp = str_replace( '&amp;', '&', $declineUrlRaw );
			$declineUrl     = str_replace( '&#038', '&', $declineUrlTemp );

			return $declineUrl;
		}

		return add_query_arg( 'key', $order->get_order_key(), add_query_arg( array(
			'order'                => $order->get_id(),
			'payment_cancellation' => 'yes',
		), get_permalink( get_option( 'woocommerce_cart_page_id' ) ) ) );
	}

	/**
	 * Validate Callback
	 *
	 * @param mixed    $params
	 * @param string   $md5_key
	 * @param WC_Order $order
	 * @param string   $message
	 *
	 * @return bool
	 */
	public static function validate_bambora_online_checkout_callback_params( $params, $md5_key, &$order, &$message ) {
		if ( ! isset( $params ) || empty( $params ) ) {
			$message = "No GET parameters supplied to the system";

			return false;
		}

		// Validate woocommerce order!
		if ( empty( $params['wcorderid'] ) ) {
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

		if ( class_exists( 'sitepress' ) ) {
			$order_language = Bambora_Online_Checkout_Helper::getWPMLOrderLanguage( $order->get_id() );
			$md5_key        = Bambora_Online_Checkout_Helper::getWPMLOptionValue( 'md5key', $order_language, $md5_key );
		}
		// Validate MD5!
		$var = '';
		if ( strlen( $md5_key ) > 0 ) {
			foreach ( $params as $key => $value ) {
				if ( 'hash' === $key ) {
					break;
				}
				$var .= $value;
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
	 * Get the language the order was made in
	 *
	 * @param int $orderId
	 *
	 * @return string
	 */
	public static function getWPMLOrderLanguage( $orderId ) {
		$order_language = get_post_meta( $orderId, 'wpml_language', true );

		return $order_language;
	}

	/**
	 * Get the option value by language
	 *
	 * @param string $key
	 * @param string $language
	 * @param string $default_value
	 *
	 * @return string
	 */

	public static function getWPMLOptionValue( $key, $language = null, $default_value = null ) {
		if ( is_null( $language ) ) {
			$language = apply_filters( 'wpml_current_language', null );
		}
		$option_value = null;
		$options      = get_option( 'woocommerce_bambora_settings' );
		if ( isset( $options[ $key ] ) ) {
			$key_value = $options[ $key ];
			if ( isset( $language ) && $language != "" ) {
				$option_value = apply_filters( 'wpml_translate_single_string', $key_value, "admin_texts_woocommerce_bambora_settings", "[woocommerce_bambora_settings]" . $key, $language );
			}
		}
		// Always return default value in case of not set.
		if ( is_null( $option_value ) ) {
			$option_value = $default_value;
		}

		return $option_value;
	}

	/**
	 * Get the Bambora Online Checkout Subscription id from the order
	 *
	 * @param WC_Subscription $subscription
	 */
	public static function get_bambora_online_checkout_subscription_id( $subscription ) {
		$subscription_id         = $subscription->get_id();
		$bambora_subscription_id = get_post_meta( $subscription_id, Bambora_Online_Checkout_Helper::BAMBORA_ONLINE_CHECKOUT_SUBSCRIPTION_ID, true );

		// For Legacy
		if ( empty( $bambora_subscription_id ) ) {
			$parent_order_id         = $subscription->get_parent_id();
			$bambora_subscription_id = get_post_meta( $parent_order_id, Bambora_Online_Checkout_Helper::BAMBORA_ONLINE_CHECKOUT_SUBSCRIPTION_ID_LEGACY, true );
			if ( ! empty( $bambora_subscription_id ) ) {
				// Transform Legacy to new standards
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
	public static function get_bambora_online_checkout_transaction_id( $order ) {
		$transaction_id = $order->get_transaction_id();
		// For Legacy
		if ( empty( $transaction_id ) ) {
			$order_id       = $order->get_id();
			$transaction_id = get_post_meta( $order_id, Bambora_Online_Checkout_Helper::BAMBORA_ONLINE_CHECKOUT_TRANSACTION_ID_LEGACY, true );
			if ( ! empty( $transaction_id ) ) {
				// Transform Legacy to new standards
				delete_post_meta( $order_id, Bambora_Online_Checkout_Helper::BAMBORA_ONLINE_CHECKOUT_TRANSACTION_ID_LEGACY );
				$order->set_transaction_id( $transaction_id );
				$order->save();
			}
		}

		return $transaction_id;
	}

	/**
	 * Build the list of notices to display on the administration
	 *
	 * @param string $type
	 * @param string $message
	 * @param bool   $keep_post
	 */
	public static function add_admin_notices( $type, $message, $keep_post = false ) {
		$message  = array( "type" => $type, "message" => $message );
		$messages = get_option( self::BAMBORA_ONLINE_CHECKOUT_STATUS_MESSAGES, false );
		if ( ! $messages ) {
			update_option( self::BAMBORA_ONLINE_CHECKOUT_STATUS_MESSAGES, array( $message ) );
		} else {
			array_push( $messages, $message );
			update_option( self::BAMBORA_ONLINE_CHECKOUT_STATUS_MESSAGES, $messages );
		}
		update_option( self::BAMBORA_ONLINE_CHECKOUT_STATUS_MESSAGES_KEEP_FOR_POST, $keep_post );
	}

	/**
	 * Echo the notices to the Administration
	 *
	 * @return void
	 */
	public static function echo_admin_notices() {
		$messages = get_option( self::BAMBORA_ONLINE_CHECKOUT_STATUS_MESSAGES, false );
		if ( ! $messages ) {
			return;
		}
		foreach ( $messages as $message ) {
			echo Bambora_Online_Checkout_Helper::message_to_html( $message['type'], $message['message'] );
		}
		if ( ! get_option( self::BAMBORA_ONLINE_CHECKOUT_STATUS_MESSAGES_KEEP_FOR_POST, false ) ) {
			delete_option( self::BAMBORA_ONLINE_CHECKOUT_STATUS_MESSAGES );
		} else {
			delete_option( self::BAMBORA_ONLINE_CHECKOUT_STATUS_MESSAGES_KEEP_FOR_POST );
		}
	}

	/**
	 * Convert message to HTML
	 *
	 * @param string $type
	 * @param string $message
	 *
	 * @return string
	 * */
	public static function message_to_html( $type, $message ) {
		$class = '';
		if ( $type === self::SUCCESS ) {
			$class = "notice-success";
		} else {
			$class = "notice-error";
		}

		$html = '<div id="message" class="' . $class . ' notice"><p><strong>' . ucfirst( $type ) . '! </strong>' . $message . '</p></div>';

		return ent2ncr( $html );
	}

	/**
	 *  Get the 3D Secure info.
	 *
	 * @param integer $eciLevel
	 *
	 * @return string
	 */
	public static function get3DSecureText( $eciLevel ) {
		switch ( $eciLevel ) {
			case "7":
			case "00":
			case "0":
			case "07":
				return "Authentication is unsuccessful or not attempted. The credit card is either a non-3D card or card issuing bank does not handle it as a 3D transaction.";
			case "06":
			case "6":
			case "01":
			case "1":
				return "Either cardholder or card issuing bank is not 3D enrolled. 3D card authentication is unsuccessful, in sample situations as: 1. 3D Cardholder not enrolled, 2. Card issuing bank is not 3D Secure ready.";
			case "05":
			case "5":
			case "02":
			case "2":
				return "Both cardholder and card issuing bank are 3D enabled. 3D card authentication is successful.";
			default:
				return "";
		}
	}

	/**
	 *  Get event Log text.
	 *
	 * @param object $operation
	 *
	 * @return array
	 */
	public static function getEventText( $operation ) {
		$action                = strtolower( $operation->action );
		$subAction             = strtolower( $operation->subaction );
		$approved              = $operation->status == 'approved';
		$threeDSecureBrandName = "";
		$eventInfo             = array();

		if ( $action === "authorize" ) {
			if ( isset( $operation->paymenttype->id ) ) {
				$threeDSecureBrandName = Bambora_Online_Checkout_Helper::getCardAuthenticationBrandName( $operation->paymenttype->id );
			}
			// Temporary renaming for Lindorff to Walley & Collector Bank to Walley require until implemented in Acquire. (from 1st September 2021 called Walley)
			$thirdPartyName = $operation->acquirername;
			$thirdPartyName = strtolower( $thirdPartyName ) !== ( "lindorff" || "collectorbank" ) ? $thirdPartyName : "Walley";


			switch ( $subAction ) {
				case "threed":
				{
					$title       = $approved ? 'Payment completed (' . $threeDSecureBrandName . ')' : 'Payment failed (' . $threeDSecureBrandName . ')';
					$eci         = $operation->eci->value;
					$statusText  = $approved ? "completed successfully" : "failed";
					$description = "";
					if ( $eci === "7" ) {
						$description = 'Authentication was either not attempted or unsuccessful. Either the card does not support' . $threeDSecureBrandName . ' or the issuing bank does not handle it as a ' . $threeDSecureBrandName . ' payment. Payment ' . $statusText . ' at ECI level ' . $eci;
					}
					if ( $eci === "6" ) {
						$description = 'Authentication was attempted but failed. Either cardholder or card issuing bank is not enrolled for ' . $threeDSecureBrandName . '. Payment ' . $statusText . ' at ECI level ' . $eci;
					}
					if ( $eci === "5" ) {
						$description = $approved ? 'Payment was authenticated at ECI level ' . $eci . ' via ' . $threeDSecureBrandName . ' and ' . $statusText : 'Payment was did not authenticate via ' . $threeDSecureBrandName . ' and ' . $statusText;
					}
					$eventInfo['title']       = $title;
					$eventInfo['description'] = $description;

					return $eventInfo;
				}
				case "ssl":
				{
					$title = $approved ? 'Payment completed' : 'Payment failed';

					$description = $approved ? 'Payment was completed and authorized via SSL.' : 'Authorization was attempted via SSL, but failed.';

					$eventInfo['title']       = $title;
					$eventInfo['description'] = $description;

					return $eventInfo;
				}

				case "recurring":
				{
					$title = $approved ? 'Subscription payment completed' : 'Subscription payment failed';

					$description = $approved ? 'Payment was completed and authorized on a subscription.' : 'Authorization was attempted on a subscription, but failed.';

					$eventInfo['title']       = $title;
					$eventInfo['description'] = $description;

					return $eventInfo;
				}
				case "update":
				{
					$title = $approved ? 'Payment updated' : 'Payment update failed';

					$description = $approved ? 'The payment was successfully updated.' : 'The payment update failed.';

					$eventInfo['title']       = $title;
					$eventInfo['description'] = $description;

					return $eventInfo;
				}
				case "return":
				{
					$title      = $approved ? 'Payment completed' : 'Payment failed';
					$statusText = $approved ? 'successful' : 'failed';

					$description              = 'Returned from ' . $thirdPartyName . ' authentication with a ' . $statusText . ' authorization.';
					$eventInfo['title']       = $title;
					$eventInfo['description'] = $description;

					return $eventInfo;
				}
				case "redirect":
				{
					$statusText               = $approved ? "Successfully" : "Unsuccessfully";
					$eventInfo['title']       = 'Redirect to ' . $thirdPartyName;
					$eventInfo['description'] = $statusText . ' redirected to ' . $thirdPartyName . ' for authentication.';

					return $eventInfo;
				}
			}
		}
		if ( $action === "capture" ) {
			$captureMultiText = ( ( $subAction === "multi" || $subAction === "multiinstant" ) && $operation->currentbalance > 0 ) ? 'Further captures are possible.' : 'Further captures are no longer possible.';

			switch ( $subAction ) {
				case "full":
				{
					$title = $approved ? 'Captured full amount' : 'Capture failed';

					$description = $approved ? 'The full amount was successfully captured.' : 'The capture attempt failed.';

					$eventInfo['title']       = $title;
					$eventInfo['description'] = $description;

					return $eventInfo;
				}
				case "fullinstant":
				{
					$title = $approved ? 'Instantly captured full amount' : 'Instant capture failed';

					$description = $approved ? 'The full amount was successfully captured.' : 'The instant capture attempt failed.';

					$eventInfo['title']       = $title;
					$eventInfo['description'] = $description;

					return $eventInfo;
				}
				case "partly":
				case "multi":
				{
					$title = $approved ? 'Captured partial amount' : 'Capture failed';

					$description = $approved ? 'The partial amount was successfully captured. ' . $captureMultiText : 'The partial capture attempt failed.';

					$eventInfo['title']       = $title;
					$eventInfo['description'] = $description;

					return $eventInfo;
				}
				case "partlyinstant":
				case "multiinstant":
				{
					$title       = $approved ? 'Instantly captured partial amount' : 'Instant capture failed';
					$description = $approved ? 'The partial amount was successfully captured. ' . $captureMultiText : 'The instant partial capture attempt failed.';

					$eventInfo['title']       = $title;
					$eventInfo['description'] = $description;

					return $eventInfo;
				}
			}
		}
		if ( $action === "credit" ) {
			switch ( $subAction ) {
				case "full":
				{
					$title       = $approved ? 'Refunded full amount' : 'Refund failed';
					$description = $approved ? 'The full amount was successfully refunded.' : 'The refund attempt failed.';

					$eventInfo['title']       = $title;
					$eventInfo['description'] = $description;

					return $eventInfo;
				}
				case "partly":
				case "multi":
				{
					$title = $approved ? 'Refunded partial amount' : 'Refund failed';

					$refundMultiText = $subAction === "multi" ? "Further refunds are possible." : "Further refunds are no longer possible.";

					$description = $approved ? 'The amount was successfully refunded. ' . $refundMultiText : 'The partial refund attempt failed.';

					$eventInfo['title']       = $title;
					$eventInfo['description'] = $description;

					return $eventInfo;
				}
			}
		}
		if ( $action === "delete" ) {
			switch ( $subAction ) {
				case "instant":
				{
					$title = $approved ? 'Canceled' : 'Cancellation failed';

					$description = $approved ? 'The payment was canceled.' : 'The cancellation failed.';

					$eventInfo['title']       = $title;
					$eventInfo['description'] = $description;

					return $eventInfo;
				}
				case "delay":
				{
					$title = $approved ? 'Cancellation scheduled' : 'Cancellation scheduling failed';

					$description = $approved ? 'The payment was canceled.' : 'The cancellation failed.';

					$eventInfo['title']       = $title;
					$eventInfo['description'] = $description;

					return $eventInfo;
				}
			}
		}
		$eventInfo['title']       = $action . ":" . $subAction;
		$eventInfo['description'] = null;

		return $eventInfo;
	}

	/**
	 *  Get the Card Authentication Brand Name
	 *
	 * @param integer $paymentGroupId
	 *
	 * @return string
	 */
	public static function getCardAuthenticationBrandName( $paymentGroupId ) {
		switch ( $paymentGroupId ) {
			case 1:
				return "Dankort Secured by Nets";
			case 2:
				return "Verified by Visa";
			case 3:
			case 4:
				return "MasterCard SecureCode";
			case 5:
				return "J/Secure";
			case 6:
				return "American Express SafeKey";
			default:
				return "3D Secure";
		}
	}
}
