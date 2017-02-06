<?php
/**
 * Bambora Online Checkout for WooCommerce
 *
 * @author Bambora
 * @package bambora_online_checkout
 */

define( 'BAMBORA_ENDPOINT_TRANSACTION', 'https://transaction-v1.api.epay.eu' );
define( 'BAMBORA_ENDPOINT_MERCHANT', 'https://merchant-v1.api.epay.eu' );
define( 'BAMBORA_ENDPOINT_DATA', 'https://data-v1.api.epay.eu/' );
define( 'BAMBORA_ENDPOINT_CHECKOUT', 'https://api.v1.checkout.bambora.com' );
define( 'BAMBORA_CHECKOUT_ASSETS', 'https://v1.checkout.bambora.com/Assets' );

/**
 * Bambora Endpoints
 */
class Bambora_Endpoints {

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
	 * Get Checkout Endpoint
	 *
	 * @return mixed
	 */
	public static function get_checkout_endpoint() {
		return constant( 'BAMBORA_ENDPOINT_CHECKOUT' );
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
