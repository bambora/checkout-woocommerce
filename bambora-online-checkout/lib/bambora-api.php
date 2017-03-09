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

include( BAMBORA_LIB . 'bambora-models.php' );
include( BAMBORA_LIB . 'bambora-endpoints.php' );

/**
 * Bambora API
 */
class Bambora_Api {

	const GET = 'GET';
	const POST = 'POST';

	private $api_key;

	/**
	 * Constructor
	 *
	 * @param mixed $api_key
	 */
	public function __construct( $api_key = '' ) {
		$this->api_key = $api_key;
	}

	/**
	 * Get Bambora Checkout response.
	 *
	 * @param Bambora_Checkout_Request $bambora_checkout_request
	 * @return mixed
	 */
	public function get_checkout_response( $bambora_checkout_request ) {
		$service_url = Bambora_Endpoints::get_checkout_endpoint() . '/checkout' ;
		if ( $bambora_checkout_request == null ) {
			return null;
		}

		$json_data = wp_json_encode( $bambora_checkout_request );
		$checkout_response = $this->call_rest_service( $service_url, $json_data, self::POST );

		return json_decode( $checkout_response, true );
	}

	/**
	 * Get Bambora Checkout payment window JavaScript url
	 *
	 * @return string
	 */
	public function get_checkout_payment_window_js() {
		$url = Bambora_Endpoints::get_checkout_assets() . '/paymentwindow-v1.min.js';

		return $url;
	}

	/**
	 * Make a capture request to Bambora
	 *
	 * @param string $transaction_id
	 * @param int    $amount
	 * @param string $currency
	 * @return mixed
	 */
	public function capture( $transaction_id, $amount, $currency ) {
		$service_url = Bambora_Endpoints::get_transaction_endpoint() . '/transactions/' . sprintf( '%.0F', $transaction_id ) . '/capture';

		$data = array();
		$data['amount'] = $amount;
		$data['currency'] = $currency;

		$json_data = wp_json_encode( $data );

		$result = $this->call_rest_service( $service_url, $json_data, self::POST );
		return json_decode( $result, true );
	}

	/**
	 * Make a credit request to Bambora
	 *
	 * @param string              $transaction_id
	 * @param int                 $amount
	 * @param string              $currency
	 * @param Bambora_Orderline[] $credit_lines
	 * @return mixed
	 */
	public function credit( $transaction_id, $amount, $currency, $credit_lines ) {
		$service_url = Bambora_Endpoints::get_transaction_endpoint() . '/transactions/' . sprintf( '%.0F', $transaction_id ) . '/credit';

		$data = array();
		$data['amount'] = $amount;
		$data['currency'] = $currency;
		$data['invoicelines'] = $credit_lines;

		$json_data = wp_json_encode( $data );

		$result = $this->call_rest_service( $service_url, $json_data, self::POST );
		return json_decode( $result, true );
	}

	/**
	 * Make a delete request to Bambora
	 *
	 * @param string $transaction_id
	 * @return mixed
	 */
	public function delete( $transaction_id ) {
		$service_url = Bambora_Endpoints::get_transaction_endpoint() . '/transactions/' . sprintf( '%.0F', $transaction_id ) . '/delete';

		$data = array();
		$json_data = wp_json_encode( $data );

		$result = $this->call_rest_service( $service_url, $json_data, self::POST );
		return json_decode( $result, true );
	}

	/**
	 * Get specific transaction from Bambora
	 *
	 * @param string $transaction_id
	 * @return mixed
	 */
	public function get_transaction( $transaction_id ) {
		$service_url = Bambora_Endpoints::get_merchant_endpoint() . '/transactions/' . sprintf( '%.0F', $transaction_id );

		$data = array();
		$json_data = wp_json_encode( $data );

		$result = $this->call_rest_service( $service_url, $json_data, self::GET );
		return json_decode( $result, true );
	}

	/**
	 * Get transaction operations for a specific transaction from Bambora
	 *
	 * @param string $transaction_id
	 * @return mixed
	 */
	public function get_transaction_operations( $transaction_id ) {
		$service_url = Bambora_Endpoints::get_merchant_endpoint() . '/transactions/' . sprintf( '%.0F', $transaction_id ) . '/transactionoperations';

		$data = array();
		$json_data = wp_json_encode( $data );

		$result = $this->call_rest_service( $service_url, $json_data, self::GET );
		return json_decode( $result, true );
	}

	/**
	 * Get available payment types for the amount and currency from Bambora
	 *
	 * @param string $currency
	 * @param int    $amount
	 * @return mixed
	 */
	public function get_payment_types( $currency, $amount ) {
		$service_url = Bambora_Endpoints::get_merchant_endpoint() . '/paymenttypes?currency=' . $currency . '&amount=' . $amount;
		$data = array();

		$json_data = wp_json_encode( $data );

		$result = $this->call_rest_service( $service_url, $json_data, self::GET );
		return json_decode( $result, true );
	}

	/**
	 * Get the ids of the available payment groups for the amount and currency from Bambora
	 *
	 * @param string $currency
	 * @param int    $amount
	 * @return array
	 */
	public function get_avaliable_payment_type_ids( $currency, $amount ) {
		$res = array();
		$service_res = $this->get_payment_types( $currency, $amount );

		if ( true === $service_res['meta']['result'] ) {
			foreach ( $service_res['paymentcollections'] as $payment ) {
				foreach ( $payment['paymentgroups'] as $card ) {
					$res[] = $card['id'];
				}
			}

			ksort( $res );
		}
		return $res;
	}

	/**
	 * Call the rest service at the specified Url
	 *
	 * @param string $service_url
	 * @param mixed  $json_data
	 * @param string $method
	 * @return mixed
	 */
	private function call_rest_service( $service_url, $json_data, $method ) {

		$content_length = isset( $json_data ) ? strlen( $json_data ) : 0;
		$headers = array(
			'Content-Type: application/json',
			'Content-Length: ' . $content_length,
			'Accept: application/json',
			'Authorization: ' . $this->api_key,
			'X-EPay-System: ' . Bambora_Helper::get_module_header_info(),
		);

		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, $method );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, $json_data );
		curl_setopt( $curl, CURLOPT_URL, $service_url );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $curl, CURLOPT_FAILONERROR, false );
		curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );

		$result = curl_exec( $curl );
		return $result;
	}
}
