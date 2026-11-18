<?php

defined( 'ABSPATH' ) || exit;

/**
 * Bambora Online Checkout Helper
 */
class Bambora_Online_Checkout_Helper {
	const BAMBORA_ONLINE_CHECKOUT_TRANSACTION_ID_LEGACY         = 'Transaction ID';
	const BAMBORA_ONLINE_CHECKOUT_SUBSCRIPTION_ID               = 'bambora_online_checkout_subscription_id';
	const BAMBORA_ONLINE_CHECKOUT_SUBSCRIPTION_ID_LEGACY        = 'Subscription ID';
	const BAMBORA_ONLINE_CHECKOUT_STATUS_MESSAGES               = 'bambora_online_checkout_status_messages';
	const BAMBORA_ONLINE_CHECKOUT_STATUS_MESSAGES_KEEP_FOR_POST = 'bambora_online_checkout_status_messages_keep_for_post';
	const ERROR   = 'error';
	const SUCCESS = 'success';

	/**
	 * Generate Bambora API key
	 *
	 * @param string $merchant - Merchant Number.
	 * @param string $accesstoken - Access Token.
	 * @param string $secrettoken - Secret Token.
	 *
	 * @return string
	 */
	public static function generate_api_key( $merchant, $accesstoken, $secrettoken ) {
		$combined = $accesstoken . '@' . $merchant . ':' . $secrettoken;
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$encoded_key = base64_encode( $combined );
		return "Basic {$encoded_key}";
	}

	/**
	 * Get the version for an asset (script or style).
	 * The file modification time is added, so browsers and proxies load the new file whenever it changes.
	 *
	 * @param string $relative_path - Path relative to the plugin folder, ex. assets/js/bambora-online-checkout-admin.js.
	 * @return string
	 */
	public static function get_asset_version( $relative_path ) {
		$file = dirname( __DIR__ ) . '/' . ltrim( $relative_path, '/' );
		return file_exists( $file ) ? BOC_VERSION . '-' . filemtime( $file ) : BOC_VERSION;
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

		return "WooCommerce/{$woocommerce_version} Module/{$bambora_version} PHP/{$php_version}";
	}

	/**
	 * Create the admin debug section
	 *
	 * @return string
	 */
	public static function create_admin_debug_section() {
		$documentation_link = 'https://developer.bambora.com/europe/shopping-carts/shopping-carts/woocommerce';
		$html               = '<h3 class="wc-settings-sub-title">Debug</h3>';
		$html              .= sprintf( '<a id="boc-admin-documentation" class="button button-primary" href="%s" target="_blank">Module documentation</a>', $documentation_link );
		$html              .= sprintf( '<a id="boc-admin-log" class="button" href="%s" target="_blank">View debug logs</a>', Bambora_Online_Checkout::get_instance()->get_boc_logger()->get_admin_link() );
		return $html;
	}


	/**
	 * Checks if Woocommerce Subscriptions is enabled or not
	 */
	public static function woocommerce_subscription_plugin_is_active() {
		return class_exists( 'WC_Subscriptions' );
	}

	/**
	 * Get the subscription for a renewal order
	 *
	 * @param WC_Order $renewal_order - WC Order.
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
	 * @param WC_Order|int $order - The WC_Order object or ID of a WC_Order order.
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
	 * @param WC_Order|int $order_id - The WC_Order object or ID of a WC_Order order.
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
	 * @param WC_Order|int $order_id - The WC_Order object or ID of a WC_Order order.
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
	 * @param object $order - A WC_Subscription object or an ID.
	 * @return boolean
	 */
	public static function order_is_subscription( $order ) {
		if ( function_exists( 'wcs_is_subscription' ) ) {
			return wcs_is_subscription( $order );
		}

		return false;
	}

	/**
	 * Returns the Callback url
	 *
	 * @param string $order_id - WC Order Id.
	 * @return string
	 */
	public static function get_bambora_online_checkout_callback_url( $order_id ) {
		$args = array(
			'wc-api'    => 'Bambora_Online_Checkout',
			'wcorderid' => $order_id,
		);
		return add_query_arg( $args, site_url( '/' ) );
	}

	/**
	 * Returns the Accept url
	 *
	 * @param WC_Order $order - WC Order.
	 * @return string
	 */
	public static function get_accept_url( $order ) {
		if ( method_exists( $order, 'get_checkout_order_received_url' ) ) {
			$accept_url_raw  = $order->get_checkout_order_received_url();
			$accept_url_temp = str_replace( '&amp;', '&', $accept_url_raw );
			return str_replace( '&#038', '&', $accept_url_temp );
		}

		return add_query_arg(
			'key',
			$order->get_order_key(),
			add_query_arg(
				array(
					'order' => $order->get_id(),
				),
				get_permalink( get_option( 'woocommerce_thanks_page_id' ) )
			)
		);
	}

	/**
	 * Returns the Decline url
	 *
	 * @param WC_Order $order - WC Order.
	 * @return string
	 */
	public static function get_decline_url( $order ) {
		if ( method_exists( $order, 'get_cancel_order_url' ) ) {
			$decline_url_raw  = $order->get_cancel_order_url();
			$decline_url_temp = str_replace( '&amp;', '&', $decline_url_raw );
			return str_replace( '&#038', '&', $decline_url_temp );
		}

		return add_query_arg(
			'key',
			$order->get_order_key(),
			add_query_arg(
				array(
					'order'                => $order->get_id(),
					'payment_cancellation' => 'yes',
				),
				get_permalink( get_option( 'woocommerce_cart_page_id' ) )
			)
		);
	}

	/**
	 * Get the signed callback parameters
	 *
	 * Worldline always adds the hash last, so the parameters after the hash are not part of the hash.
	 * They are added by someone else (ex. a rewrite rule, a CDN or a redirect at the hosting) and could
	 * be added by anyone, so they are removed and never used.
	 *
	 * @param mixed $params - GET Parameters.
	 * @param array $unsigned_keys - Output keys of the removed parameters.
	 * @return mixed
	 */
	public static function get_signed_callback_params( $params, &$unsigned_keys = array() ) {
		$unsigned_keys = array();
		if ( ! is_array( $params ) || ! array_key_exists( 'hash', $params ) ) {
			return $params;
		}

		$signed_params = array();
		$after_hash    = false;
		foreach ( $params as $key => $value ) {
			if ( $after_hash ) {
				$unsigned_keys[] = (string) $key;
				continue;
			}
			$signed_params[ $key ] = $value;
			$after_hash            = 'hash' === $key;
		}

		return $signed_params;
	}

	/**
	 * Validate Callback
	 *
	 * Validates the callback parameters and the MD5 hash first. Only then is the transaction
	 * fetched from the Merchant API and validated against the callback.
	 *
	 * @param mixed    $params - GET Parameters.
	 * @param string   $md5_key - Bambora MD5 Key.
	 * @param string   $api_key - Bambora API Key for the order.
	 * @param WC_Order $order - Output WC Order.
	 * @param object   $transaction - Output transaction from the Merchant API.
	 * @param string   $message - Output message.
	 * @return bool
	 * @throws Exception - Throws an Exception if the call to the Merchant API fails.
	 */
	public static function validate_bambora_online_checkout_callback( $params, $md5_key, $api_key, &$order, &$transaction, &$message ) {
		if ( ! self::validate_bambora_online_checkout_callback_params( $params, $md5_key, $order, $message ) ) {
			return false;
		}

		return self::validate_bambora_online_checkout_callback_transaction( $params, $api_key, $transaction, $message );
	}

	/**
	 * Validate the callback parameters, the WooCommerce order and the MD5 hash
	 *
	 * @param mixed    $params - GET Parameters.
	 * @param string   $md5_key - Bambora MD5 Key.
	 * @param WC_Order $order - Output WC Order.
	 * @param string   $message - Output message.
	 * @return bool
	 */
	private static function validate_bambora_online_checkout_callback_params( $params, $md5_key, &$order, &$message ) {
		if ( empty( $params ) || ! is_array( $params ) ) {
			$message = 'No GET parameters supplied to the system';
			return false;
		}

		// All values are concatenated for the MD5 hash, so they must be strings!
		foreach ( $params as $key => $value ) {
			if ( ! is_string( $value ) ) {
				$message = "The GET parameter {$key} is not valid";
				return false;
			}
		}

		// Check required parameters exists!
		foreach ( array( 'wcorderid', 'txnid', 'orderid', 'currency', 'hash' ) as $key ) {
			if ( empty( $params[ $key ] ) ) {
				$message = "No {$key} was supplied to the system!";
				return false;
			}
		}

		// Worldline always adds the hash last. Parameters after the hash are not part of the hash, so they are not accepted!
		// The callback removes them with get_signed_callback_params() first, so this only protects other callers.
		if ( 'hash' !== array_key_last( $params ) ) {
			$message = 'The hash is not the last parameter';
			return false;
		}

		// The amount can be 0 (ex. subscriptions with a free trial) so empty() can not be used!
		if ( ! isset( $params['amount'] ) || ! ctype_digit( $params['amount'] ) ) {
			$message = 'No valid amount was supplied to the system!';
			return false;
		}

		// Validate woocommerce order!
		$order = wc_get_order( $params['wcorderid'] );
		if ( empty( $order ) ) {
			$message = "Could not find order with WooCommerce Order id {$params['wcorderid']}";
			return false;
		}

		if ( class_exists( 'sitepress' ) ) {
			$order_language = self::get_wpml_order_language( $order->get_id() );
			$md5_key        = self::get_wpml_option_value( 'md5key', $order_language, $md5_key );
		}

		if ( empty( $md5_key ) ) {
			$message = 'The MD5 key is missing. Please configure it in the plugin settings.';
			return false;
		}

		// Validate MD5!
		$var = '';
		foreach ( $params as $key => $value ) {
			if ( 'hash' === $key ) {
				break;
			}
			$var .= $value;
		}
		$genstamp = md5( "{$var}{$md5_key}" );
		if ( ! hash_equals( $genstamp, $params['hash'] ) ) {
			$message = 'Validation failed - Please contact Worldline support';
			return false;
		}

		return true;
	}

	/**
	 * Get the transaction from the Merchant API and validate that it matches the callback
	 *
	 * The callback amount is checked both with and without the surcharge fee,
	 * since the authorized amount on the transaction includes the fee.
	 *
	 * @param mixed  $params - GET Parameters.
	 * @param string $api_key - Bambora API Key for the order.
	 * @param object $transaction - Output transaction from the Merchant API.
	 * @param string $message - Output message.
	 * @return bool
	 * @throws Exception - Throws an Exception if the call to the Merchant API fails.
	 */
	private static function validate_bambora_online_checkout_callback_transaction( $params, $api_key, &$transaction, &$message ) {
		// Get the transaction!
		$api                      = new Bambora_Online_Checkout_Api( $api_key );
		$get_transaction_response = $api->get_transaction( $params['txnid'] );
		if ( ! $get_transaction_response->meta->result ) {
			$message = "Transaction is not valid: {$get_transaction_response->meta->message->merchant}";
			return false;
		}
		$transaction = $get_transaction_response->transaction;

		// Validate transaction!
		if ( empty( $transaction->orderid ) || empty( $transaction->currency ) || ! isset( $transaction->total->authorized ) ) {
			$message = 'The transaction is missing order id, currency or total';
			return false;
		}

		// Validate order id!
		if ( $params['orderid'] !== $transaction->orderid ) {
			$message = 'The order id does not match the transaction';
			return false;
		}

		// Validate currency - accept both ISO 4217 alpha-3 (DKK) and numeric-3 (208)!
		$transaction_currencies = array();
		if ( ! empty( $transaction->currency->code ) ) {
			$transaction_currencies[] = strtoupper( $transaction->currency->code );
		}
		if ( ! empty( $transaction->currency->number ) ) {
			$transaction_currencies[] = $transaction->currency->number;
		}
		if ( ! in_array( strtoupper( $params['currency'] ), $transaction_currencies, true ) ) {
			$message = 'The currency does not match the transaction';
			return false;
		}

		// Validate amount in minorunits!
		$amount     = intval( $params['amount'] );
		$authorized = $transaction->total->authorized;
		$fee_amount = isset( $transaction->total->feeamount ) ? $transaction->total->feeamount : 0;
		if ( ! in_array( $amount, array( $authorized, $authorized - $fee_amount ), true ) ) {
			$message = 'The amount does not match the transaction';
			return false;
		}

		return true;
	}

	/**
	 * Get the language the order was made in
	 *
	 * @param int $order_id - WC Order Id.
	 * @return string|null
	 */
	public static function get_wpml_order_language( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( empty( $order ) ) {
			return null;
		}
		return $order->get_meta( 'wpml_language', true );
	}

	/**
	 * Get the option value by language
	 *
	 * @param string $key - Key.
	 * @param string $language - Language.
	 * @param string $default_value - Default Value.
	 *
	 * @return string
	 */
	public static function get_wpml_option_value( $key, $language = null, $default_value = null ) {
		if ( is_null( $language ) ) {
			$language = apply_filters( 'wpml_current_language', null ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML hook.
		}
		$option_value = null;
		$options      = get_option( 'woocommerce_bambora_settings' );
		if ( isset( $options[ $key ] ) ) {
			$key_value = $options[ $key ];
			if ( isset( $language ) && ! empty( $language ) ) {
				$option_value = apply_filters( 'wpml_translate_single_string', $key_value, 'admin_texts_woocommerce_bambora_settings', '[woocommerce_bambora_settings]' . $key, $language ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML hook.
			}
		}
		// Always return default value in case of not set.
		if ( is_null( $option_value ) ) {
			$option_value = $default_value;
		}
		return $option_value;
	}

	/**
	 * Get the Worldline Online Checkout Subscription id from the order
	 *
	 * @param WC_Subscription $subscription - WC Subscription.
	 * @return string
	 */
	public static function get_bambora_online_checkout_subscription_id( $subscription ) {
		$subscription_id         = $subscription->get_id();
		$subscription            = wcs_get_subscription( $subscription_id );
		$bambora_subscription_id = $subscription->get_meta( self::BAMBORA_ONLINE_CHECKOUT_SUBSCRIPTION_ID, true );

		// For Legacy.
		if ( empty( $bambora_subscription_id ) ) {
			$parent = wc_get_order( $subscription->get_parent_id() );
			if ( ! empty( $parent ) ) {
				$bambora_subscription_id = $parent->get_meta( self::BAMBORA_ONLINE_CHECKOUT_SUBSCRIPTION_ID_LEGACY, true );
				if ( ! empty( $bambora_subscription_id ) ) {
					// Transform Legacy to new standards.
					$subscription->update_meta_data( self::BAMBORA_ONLINE_CHECKOUT_SUBSCRIPTION_ID, $bambora_subscription_id );
					$subscription->save();
					$parent->delete_meta_data( self::BAMBORA_ONLINE_CHECKOUT_SUBSCRIPTION_ID_LEGACY );
					$parent->save();
				}
			}
		}
		return $bambora_subscription_id;
	}

	/**
	 * Get the Worldline Online Checkout Transaction id from the order
	 *
	 * @param WC_Order $order - WC Order.
	 */
	public static function get_bambora_online_checkout_transaction_id( $order ) {
		$transaction_id = $order->get_transaction_id();
		// For Legacy.
		if ( empty( $transaction_id ) ) {
			$transaction_id = $order->get_meta( self::BAMBORA_ONLINE_CHECKOUT_TRANSACTION_ID_LEGACY, true );
			if ( ! empty( $transaction_id ) ) {
				// Transform Legacy to new standards.
				$order->delete_meta_data( self::BAMBORA_ONLINE_CHECKOUT_TRANSACTION_ID_LEGACY );
				$order->set_transaction_id( $transaction_id );
				$order->save();
			}
		}
		return $transaction_id;
	}

	/**
	 * Add a notice to display on the administration for the current user.
	 * The notices are stored per user, so they are not shown to other users.
	 * Without a logged in user (ex. cron or a callback) there is no one to show the notice to, so callers must log instead.
	 *
	 * @param string $type - Type.
	 * @param string $message - Message.
	 * @param bool   $keep_post - Keep Post (Default false).
	 * @return void
	 */
	public static function add_admin_notices( $type, $message, $keep_post = false ) {
		$user_id = get_current_user_id();
		if ( 0 === $user_id ) {
			return;
		}
		$transient  = self::get_admin_notices_transient_name( $user_id );
		$notices    = get_transient( $transient );
		$messages   = is_array( $notices ) && isset( $notices['messages'] ) && is_array( $notices['messages'] ) ? $notices['messages'] : array();
		$messages[] = array(
			'type'    => $type,
			'message' => $message,
		);
		set_transient(
			$transient,
			array(
				'messages'  => $messages,
				'keep_post' => (bool) $keep_post,
			),
			HOUR_IN_SECONDS
		);
	}

	/**
	 * Echo the notices for the current user to the Administration
	 *
	 * @return void
	 */
	public static function echo_admin_notices() {
		// Before version 9.0.0 the notices were stored in options shared by all users. Remove them.
		if ( false !== get_option( self::BAMBORA_ONLINE_CHECKOUT_STATUS_MESSAGES, false ) ) {
			delete_option( self::BAMBORA_ONLINE_CHECKOUT_STATUS_MESSAGES );
			delete_option( self::BAMBORA_ONLINE_CHECKOUT_STATUS_MESSAGES_KEEP_FOR_POST );
		}

		$user_id = get_current_user_id();
		if ( 0 === $user_id ) {
			return;
		}
		$transient = self::get_admin_notices_transient_name( $user_id );
		$notices   = get_transient( $transient );
		if ( ! is_array( $notices ) || empty( $notices['messages'] ) || ! is_array( $notices['messages'] ) ) {
			return;
		}
		foreach ( $notices['messages'] as $message ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in message_to_html.
			echo self::message_to_html( $message['type'], $message['message'] );
		}
		if ( ! empty( $notices['keep_post'] ) ) {
			// Show the notices one more time.
			$notices['keep_post'] = false;
			set_transient( $transient, $notices, HOUR_IN_SECONDS );
		} else {
			delete_transient( $transient );
		}
	}

	/**
	 * Get the name of the transient holding the admin notices for a user
	 *
	 * @param int $user_id - WP User Id.
	 * @return string
	 */
	private static function get_admin_notices_transient_name( $user_id ) {
		return self::BAMBORA_ONLINE_CHECKOUT_STATUS_MESSAGES . '_' . (int) $user_id;
	}

	/**
	 * Convert message to HTML
	 *
	 * @param string $type - Type.
	 * @param string $message - Message.
	 *
	 * @return string
	 * */
	public static function message_to_html( $type, $message ) {
		$class = '';
		if ( self::SUCCESS === $type ) {
			$class = 'notice-success';
		} else {
			$class = 'notice-error';
		}
		return '<div id="message" class="' . esc_attr( $class ) . ' notice"><p><strong>' . esc_attr( ucfirst( $type ) ) . '! </strong>' . esc_attr( $message ) . '</p></div>';
	}

	/**
	 *  Get the 3D Secure info.
	 *
	 * @param int $eci_level - ECI Level.
	 * @return string
	 */
	public static function get_3d_secure_text( $eci_level ) {
		switch ( $eci_level ) {
			case '7':
			case '00':
			case '0':
			case '07':
				return 'Authentication is unsuccessful or not attempted. The credit card is either a non-3D card or card issuing bank does not handle it as a 3D transaction.';
			case '06':
			case '6':
			case '01':
			case '1':
				return 'Either cardholder or card issuing bank is not 3D enrolled. 3D card authentication is unsuccessful, in sample situations as: 1. 3D Cardholder not enrolled, 2. Card issuing bank is not 3D Secure ready.';
			case '05':
			case '5':
			case '02':
			case '2':
				return 'Both cardholder and card issuing bank are 3D enabled. 3D card authentication is successful.';
			default:
				return '';
		}
	}

	/**
	 * Get Transaction Operation Event Text
	 *
	 * @param mixed $transaction_operation - Bambora Transaction Operation.
	 * @return array<string|null>
	 */
	public static function get_event_text( $transaction_operation ) {
		$action                    = strtolower( $transaction_operation->action );
		$sub_action                = strtolower( $transaction_operation->subaction );
		$approved                  = 'approved' === $transaction_operation->status;
		$three_d_secure_brand_name = '';
		$event_info                = array();

		if ( 'authorize' === $action ) {
			if ( isset( $transaction_operation->paymenttype->id ) ) {
				$three_d_secure_brand_name = self::get_card_authentication_brand_name( $transaction_operation->paymenttype->id );
			}
			// Temporary renaming for Lindorff to Walley & Collector Bank to Walley require until implemented in Acquire. (from 1st September 2021 called Walley).
			$third_party_name = $transaction_operation->acquirername;
			$third_party_name = in_array( strtolower( $third_party_name ), array( 'lindorff', 'collectorbank' ), true ) ? 'Walley' : $third_party_name;

			switch ( $sub_action ) {
				case 'threed':
					$title       = $approved ? 'Payment completed (' . $three_d_secure_brand_name . ')' : 'Payment failed (' . $three_d_secure_brand_name . ')';
					$eci         = $transaction_operation->eci->value;
					$status_text = $approved ? 'completed successfully' : 'failed';
					$description = '';
					if ( '7' === $eci ) {
						$description = 'Authentication was either not attempted or unsuccessful. Either the card does not support' . $three_d_secure_brand_name . ' or the issuing bank does not handle it as a ' . $three_d_secure_brand_name . ' payment. Payment ' . $status_text . ' at ECI level ' . $eci;
					} elseif ( '6' === $eci ) {
						$description = 'Authentication was attempted but failed. Either cardholder or card issuing bank is not enrolled for ' . $three_d_secure_brand_name . '. Payment ' . $status_text . ' at ECI level ' . $eci;
					} elseif ( '5' === $eci ) {
						$description = $approved ? 'Payment was authenticated at ECI level ' . $eci . ' via ' . $three_d_secure_brand_name . ' and ' . $status_text : 'Payment was did not authenticate via ' . $three_d_secure_brand_name . ' and ' . $status_text;
					}
					$event_info['title']       = $title;
					$event_info['description'] = $description;
					return $event_info;
				case 'ssl':
					$title                     = $approved ? 'Payment completed' : 'Payment failed';
					$description               = $approved ? 'Payment was completed and authorized via SSL.' : 'Authorization was attempted via SSL, but failed.';
					$event_info['title']       = $title;
					$event_info['description'] = $description;
					return $event_info;
				case 'recurring':
					$title                     = $approved ? 'Subscription payment completed' : 'Subscription payment failed';
					$description               = $approved ? 'Payment was completed and authorized on a subscription.' : 'Authorization was attempted on a subscription, but failed.';
					$event_info['title']       = $title;
					$event_info['description'] = $description;
					return $event_info;
				case 'update':
					$title                     = $approved ? 'Payment updated' : 'Payment update failed';
					$description               = $approved ? 'The payment was successfully updated.' : 'The payment update failed.';
					$event_info['title']       = $title;
					$event_info['description'] = $description;
					return $event_info;
				case 'return':
					$title                     = $approved ? 'Payment completed' : 'Payment failed';
					$status_text               = $approved ? 'successful' : 'failed';
					$description               = 'Returned from ' . $third_party_name . ' authentication with a ' . $status_text . ' authorization.';
					$event_info['title']       = $title;
					$event_info['description'] = $description;
					return $event_info;
				case 'redirect':
					$status_text               = $approved ? 'Successfully' : 'Unsuccessfully';
					$event_info['title']       = 'Redirect to ' . $third_party_name;
					$event_info['description'] = $status_text . ' redirected to ' . $third_party_name . ' for authentication.';
					return $event_info;
			}
		}
		if ( 'capture' === $action ) {
			$capture_multi_text = ( ( 'multi' === $sub_action || 'multiinstant' === $sub_action ) && 0 < $transaction_operation->currentbalance ) ? 'Further captures are possible.' : 'Further captures are no longer possible.';

			switch ( $sub_action ) {
				case 'full':
					$title                     = $approved ? 'Captured full amount' : 'Capture failed';
					$description               = $approved ? 'The full amount was successfully captured.' : 'The capture attempt failed.';
					$event_info['title']       = $title;
					$event_info['description'] = $description;
					return $event_info;
				case 'fullinstant':
					$title                     = $approved ? 'Instantly captured full amount' : 'Instant capture failed';
					$description               = $approved ? 'The full amount was successfully captured.' : 'The instant capture attempt failed.';
					$event_info['title']       = $title;
					$event_info['description'] = $description;
					return $event_info;
				case 'partly':
				case 'multi':
					$title                     = $approved ? 'Captured partial amount' : 'Capture failed';
					$description               = $approved ? 'The partial amount was successfully captured. ' . $capture_multi_text : 'The partial capture attempt failed.';
					$event_info['title']       = $title;
					$event_info['description'] = $description;
					return $event_info;
				case 'partlyinstant':
				case 'multiinstant':
					$title                     = $approved ? 'Instantly captured partial amount' : 'Instant capture failed';
					$description               = $approved ? 'The partial amount was successfully captured. ' . $capture_multi_text : 'The instant partial capture attempt failed.';
					$event_info['title']       = $title;
					$event_info['description'] = $description;
					return $event_info;
			}
		}
		if ( 'credit' === $action ) {
			switch ( $sub_action ) {
				case 'full':
					$title                     = $approved ? 'Refunded full amount' : 'Refund failed';
					$description               = $approved ? 'The full amount was successfully refunded.' : 'The refund attempt failed.';
					$event_info['title']       = $title;
					$event_info['description'] = $description;
					return $event_info;
				case 'partly':
				case 'multi':
					$title                     = $approved ? 'Refunded partial amount' : 'Refund failed';
					$refund_multi_text         = 'multi' === $sub_action ? 'Further refunds are possible.' : 'Further refunds are no longer possible.';
					$description               = $approved ? 'The amount was successfully refunded. ' . $refund_multi_text : 'The partial refund attempt failed.';
					$event_info['title']       = $title;
					$event_info['description'] = $description;
					return $event_info;
			}
		}
		if ( 'delete' === $action ) {
			switch ( $sub_action ) {
				case 'instant':
					$title                     = $approved ? 'Canceled' : 'Cancellation failed';
					$description               = $approved ? 'The payment was canceled.' : 'The cancellation failed.';
					$event_info['title']       = $title;
					$event_info['description'] = $description;
					return $event_info;
				case 'delay':
					$title                     = $approved ? 'Cancellation scheduled' : 'Cancellation scheduling failed';
					$description               = $approved ? 'The payment was canceled.' : 'The cancellation failed.';
					$event_info['title']       = $title;
					$event_info['description'] = $description;
					return $event_info;
			}
		}
		$event_info['title']       = $action . ':' . $sub_action;
		$event_info['description'] = null;

		return $event_info;
	}

	/**
	 *  Get the Card Authentication Brand Name
	 *
	 * @param int $payment_group_id - Payment Group Id.
	 * @return string
	 */
	public static function get_card_authentication_brand_name( $payment_group_id ) {
		switch ( $payment_group_id ) {
			case 1:
				return 'Dankort Secured by Nets';
			case 2:
				return 'Verified by Visa';
			case 3:
			case 4:
				return 'MasterCard SecureCode';
			case 5:
				return 'J/Secure';
			case 6:
				return 'American Express SafeKey';
			default:
				return '3D Secure';
		}
	}

	/**
	 * Summary of sanitize_array_item_by_key
	 *
	 * @param array  $items - Array.
	 * @param string $key - Key.
	 * @return string
	 */
	public static function sanitize_array_item_by_key( $items, $key ) {
		if ( empty( $items ) || ! array_key_exists( $key, $items ) ) {
			return '';
		}
		return sanitize_text_field( $items[ $key ] );
	}
}
