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

define( 'BAMBORA_ENDPOINT_TRANSACTION', 'https://transaction-v1.api-eu.bambora.com' );
define( 'BAMBORA_ENDPOINT_MERCHANT', 'https://merchant-v1.api-eu.bambora.com' );
define( 'BAMBORA_ENDPOINT_DATA', 'https://data-v1.api-eu.bambora.com' );
define( 'BAMBORA_ENDPOINT_SUBSCRIPTION', 'https://subscription-v1.api-eu.bambora.com' );
define( 'BAMBORA_ENDPOINT_CHECKOUT', 'https://v1.checkout.bambora.com' );
define( 'BAMBORA_ENDPOINT_CHECKOUT_API', 'https://api.v1.checkout.bambora.com' );
define( 'BAMBORA_CHECKOUT_ASSETS', 'https://v1.checkout.bambora.com/Assets' );
define( 'BAMBORA_ENDPOINT_LOGIN', 'https://login-v1.api-eu.bambora.com' );

/**
 * Bambora Online Checkout Endpoints
 */
class Bambora_Online_Checkout_Endpoints {
	/**
	 * Get Transaction Endpoint
	 *
	 * @return mixed
	 */
	public static function get_transaction_endpoint() {
		return constant( 'BAMBORA_ENDPOINT_TRANSACTION' );
	}

	/**
	 * Get Merchant Endpoint
	 *
	 * @return mixed
	 */
	public static function get_merchant_endpoint() {
		return constant( 'BAMBORA_ENDPOINT_MERCHANT' );
	}

	/**
	 * Get Data Endpoint
	 *
	 * @return mixed
	 */
	public static function get_data_endpoint() {
		return constant( 'BAMBORA_ENDPOINT_DATA' );
	}

	/**
	 * Get Subscription Endpoint
	 *
	 * @return mixed
	 */
	public static function get_subscription_endpoint() {
		return constant( 'BAMBORA_ENDPOINT_SUBSCRIPTION' );
	}

	/**
	 * Get Checkout Endpoint
	 *
	 * @return mixed
	 */
	public static function get_checkout_endpoint() {
		return constant( 'BAMBORA_ENDPOINT_CHECKOUT' );
	}

	/**
	 * Get Checkout Endpoint
	 *
	 * @return mixed
	 */
	public static function get_checkout_api_endpoint() {
		return constant( 'BAMBORA_ENDPOINT_CHECKOUT_API' );
	}


	/**
	 * Get Login Endpoint
	 *
	 * @return mixed
	 */
	public static function get_login_endpoint() {
		return constant( 'BAMBORA_ENDPOINT_LOGIN' );
	}

	/**
	 * Get Assets Endpoint
	 *
	 * @return mixed
	 */
	public static function get_checkout_assets() {
		return constant( 'BAMBORA_CHECKOUT_ASSETS' );
	}
}
