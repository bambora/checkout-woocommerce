<?php

const BAMBORA_ENDPOINT_TRANSACTION  = 'https://transaction-v1.api-eu.bambora.com';
const BAMBORA_ENDPOINT_MERCHANT     = 'https://merchant-v1.api-eu.bambora.com';
const BAMBORA_ENDPOINT_DATA         = 'https://data-v1.api-eu.bambora.com';
const BAMBORA_ENDPOINT_SUBSCRIPTION = 'https://subscription-v1.api-eu.bambora.com';
const BAMBORA_ENDPOINT_CHECKOUT     = 'https://v1.checkout.bambora.com';
const BAMBORA_ENDPOINT_CHECKOUT_API = 'https://api.v1.checkout.bambora.com';
const BAMBORA_CHECKOUT_ASSETS       = 'https://v1.checkout.bambora.com/Assets';
const BAMBORA_ENDPOINT_LOGIN        = 'https://login-v1.api-eu.bambora.com';

/**
 * Bambora Online Checkout Endpoints
 */
class Bambora_Online_Checkout_Endpoints {
	/**
	 * Get Transaction Endpoint
	 *
	 * @return string
	 */
	public static function get_transaction_endpoint() {
		return BAMBORA_ENDPOINT_TRANSACTION;
	}

	/**
	 * Get Merchant Endpoint
	 *
	 * @return string
	 */
	public static function get_merchant_endpoint() {
		return BAMBORA_ENDPOINT_MERCHANT;
	}

	/**
	 * Get Data Endpoint
	 *
	 * @return string
	 */
	public static function get_data_endpoint() {
		return BAMBORA_ENDPOINT_DATA;
	}

	/**
	 * Get Subscription Endpoint
	 *
	 * @return string
	 */
	public static function get_subscription_endpoint() {
		return BAMBORA_ENDPOINT_SUBSCRIPTION;
	}

	/**
	 * Get Checkout Endpoint
	 *
	 * @return string
	 */
	public static function get_checkout_endpoint() {
		return BAMBORA_ENDPOINT_CHECKOUT;
	}

	/**
	 * Get Checkout Endpoint
	 *
	 * @return string
	 */
	public static function get_checkout_api_endpoint() {
		return BAMBORA_ENDPOINT_CHECKOUT_API;
	}


	/**
	 * Get Login Endpoint
	 *
	 * @return string
	 */
	public static function get_login_endpoint() {
		return BAMBORA_ENDPOINT_LOGIN;
	}

	/**
	 * Get Assets Endpoint
	 *
	 * @return string
	 */
	public static function get_checkout_assets() {
		return BAMBORA_CHECKOUT_ASSETS;
	}
}
