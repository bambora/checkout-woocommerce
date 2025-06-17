<?php

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
		return class_exists( 'WC_Subscriptions' ); // && WC_Subscriptions::$name = 'subscription';
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
	 * Format date time
	 *
	 * @param string $raw_date_time - Raw Date Time.
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
	 * @param mixed $number - Number.
	 * @param int   $decimals - Decimals.
	 * @param bool  $display_thousand_separator - Display Thousand Separator (Default true).
	 * @return string
	 */
	public static function format_number( $number, $decimals, $display_thousand_separator = true ) {
		if ( $display_thousand_separator ) {
			return number_format( $number, $decimals, wc_get_price_decimal_separator(), wc_get_price_thousand_separator() );
		}
		return number_format( $number, $decimals, wc_get_price_decimal_separator(), '' );
	}

	/**
	 * Translate action
	 *
	 * @param string $action - Bambora Action.
	 * @return string
	 */
	public static function translate_action( $action ) {
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
	 * Validate Callback
	 *
	 * @param mixed    $params - GET Parameters.
	 * @param string   $md5_key - Bambora MD5 Key.
	 * @param WC_Order $order - WC Order.
	 * @param string   $message - Output message.
	 * @return bool
	 */
	public static function validate_bambora_online_checkout_callback_params( $params, $md5_key, &$order, &$message ) {
		if ( ! isset( $params ) || empty( $params ) ) {
			$message = 'No GET parameters supplied to the system';
			return false;
		}

		// Validate woocommerce order!
		if ( ! array_key_exists( 'wcorderid', $params ) || empty( $params['wcorderid'] ) ) {
			$message = 'No WooCommerce Order Id was supplied to the system!';
			return false;
		}

		$order = wc_get_order( $params['wcorderid'] );
		if ( ! isset( $order ) || false === $order ) {
			$message = "Could not find order with WooCommerce Order id {$params['wcorderid']}";
			return false;
		}

		// Check exists transactionid!
		if ( ! array_key_exists( 'txnid', $params ) || empty( $params['txnid'] ) ) {
			$message = 'No txnid was supplied to the system!';
			return false;
		}

		if ( class_exists( 'sitepress' ) ) {
			$order_language = self::get_wpml_order_language( $order->get_id() );
			$md5_key        = self::get_wpml_option_value( 'md5key', $order_language, $md5_key );
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
			$genstamp = md5( "{$var}{$md5_key}" );
			if ( ! hash_equals( $genstamp, $params['hash'] ) ) {
				$message = 'Validation failed - Please contact Worldline support';
				return false;
			}
		}
		return true;
	}

	/**
	 * Get the language the order was made in
	 *
	 * @param int $order_id - WC Order Id.
	 * @return string
	 */
	public static function get_wpml_order_language( $order_id ) {
		$order = wc_get_order( $order_id );
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
			$language = apply_filters( 'wpml_current_language', null );
		}
		$option_value = null;
		$options      = get_option( 'woocommerce_bambora_settings' );
		if ( isset( $options[ $key ] ) ) {
			$key_value = $options[ $key ];
			if ( isset( $language ) && ! empty( $language ) ) {
				$option_value = apply_filters( 'wpml_translate_single_string', $key_value, 'admin_texts_woocommerce_bambora_settings', '[woocommerce_bambora_settings]' . $key, $language );
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
			$parent_order_id         = $subscription->get_parent_id();
			$bambora_subscription_id = get_post_meta( $parent_order_id, self::BAMBORA_ONLINE_CHECKOUT_SUBSCRIPTION_ID_LEGACY, true );
			if ( ! empty( $bambora_subscription_id ) ) {
				// Transform Legacy to new standards.
				$subscription->update_meta_data( self::BAMBORA_ONLINE_CHECKOUT_SUBSCRIPTION_ID, $bambora_subscription_id );
				$subscription->save();
				$parent = wc_get_order( $parent_order_id );
				$parent->delete_meta_data( self::BAMBORA_ONLINE_CHECKOUT_SUBSCRIPTION_ID_LEGACY );
				$parent->save();
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
			$order_id       = $order->get_id();
			$transaction_id = get_post_meta( $order_id, self::BAMBORA_ONLINE_CHECKOUT_TRANSACTION_ID_LEGACY, true );
			if ( ! empty( $transaction_id ) ) {
				// Transform Legacy to new standards.
				delete_post_meta( $order_id, self::BAMBORA_ONLINE_CHECKOUT_TRANSACTION_ID_LEGACY );
				$order->set_transaction_id( $transaction_id );
				$order->save();
			}
		}
		return $transaction_id;
	}

	/**
	 * Build the list of notices to display on the administration
	 *
	 * @param string $type - Type.
	 * @param string $message - Message.
	 * @param bool   $keep_post - Keep Post (Default false).
	 * @return void
	 */
	public static function add_admin_notices( $type, $message, $keep_post = false ) {
		$message  = array(
			'type'    => $type,
			'message' => $message,
		);
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
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo self::message_to_html( $message['type'], $message['message'] );
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
			$third_party_name = strtolower( $third_party_name ) !== ( 'lindorff' || 'collectorbank' ) ? $third_party_name : 'Walley';

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

	/**
	 * Get the order ID depending on what was passed.
	 *
	 * @param  mixed $order Order data to convert to an ID.
	 * @return int|bool false on failure
	 */
	public static function get_order_id( $order ) {
		if ( is_numeric( $order ) ) {
			return $order;
		} elseif ( $order instanceof WC_Abstract_Order ) {
			return $order->get_id();
		} elseif ( ! empty( $order->ID ) ) {
			return $order->ID;
		} else {
			return false;
		}
	}
}
