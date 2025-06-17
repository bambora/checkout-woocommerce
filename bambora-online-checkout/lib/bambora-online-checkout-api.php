<?php

require BOC_LIB . 'bambora-online-checkout-endpoints.php';

/**
 * Bambora Online Checkout API
 */
class Bambora_Online_Checkout_Api {
	const GET                        = 'GET';
	const POST                       = 'POST';
	const DELETE                     = 'DELETE';
	const PERMISSION_PAYMENT_REQUEST = 'function#expresscheckoutservice#v1#createpaymentrequest';

	/**
	 * Bambora Api Key
	 *
	 * @var string
	 */
	private $api_key;

	/**
	 * Constructor
	 *
	 * @param string $api_key - Bambora Api Key.
	 */
	public function __construct( $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * Call the rest service at the specified Url
	 *
	 * @param string $service_url - Service Url.
	 * @param string $json_data - Json data.
	 * @param string $method - REST Method.
	 * @return mixed
	 * @throws Exception - Throws an Exeception if REST call fails.
	 */
	private function call_rest_service( $service_url, $json_data, $method ) {
		$headers  = array(
			'Content-Type'  => 'application/json',
			'Accept'        => 'application/json',
			'Authorization' => $this->api_key,
			'X-EPay-System' => Bambora_Online_Checkout_Helper::get_module_header_info(),
		);
		$timeout  = 30; // Defined in secounds.
		$response = null;
		switch ( $method ) {
			case self::GET:
				$response = wp_remote_get(
					$service_url,
					array(
						'headers' => $headers,
						'timeout' => $timeout,
					)
				);
				break;
			case self::POST:
				$response = wp_remote_post(
					$service_url,
					array(
						'headers' => $headers,
						'body'    => $json_data,
						'timeout' => $timeout,
					)
				);
				break;
			case self::DELETE:
				$response = wp_remote_request(
					$service_url,
					array(
						'headers' => $headers,
						'method'  => self::DELETE,
						'timeout' => $timeout,
					)
				);
				break;
		}

		if ( ! isset( $response ) ) {
			throw new Exception( 'Bambora REST Response is null' );
		} elseif ( is_wp_error( $response ) ) {
			throw new Exception( esc_attr( $response->get_error_message( 'http_request_failed' ) ) );
		}
		return json_decode( wp_remote_retrieve_body( $response ) );
	}

	/**
	 * Set Bambora Checkout session
	 *
	 * @param Bambora_Online_Checkout_Request $bambora_checkout_request - Bambora session request.
	 * @throws Exception - Throws an Exeception if REST call fails.
	 * @return mixed
	 */
	public function set_checkout_session( $bambora_checkout_request ) {
		$service_url = Bambora_Online_Checkout_Endpoints::get_checkout_api_endpoint() . '/checkout';
		$json_data   = wp_json_encode( $bambora_checkout_request );
		return $this->call_rest_service( $service_url, $json_data, self::POST );
	}

	/**
	 * Get Bambora Online Checkout payment window url
	 *
	 * @return string
	 */
	public function get_checkout_payment_window_url() {
		return Bambora_Online_Checkout_Endpoints::get_checkout_endpoint();
	}

	/**
	 * Get Bambora Online Checkout payment window JavaScript url
	 *
	 * @return string
	 */
	public function get_checkout_payment_window_js_url() {
		return Bambora_Online_Checkout_Endpoints::get_checkout_assets() . '/paymentwindow-v1.min.js';
	}

	/**
	 * Get Response Code data
	 *
	 * @param string $source - Bambora Action Source.
	 * @param string $action_code - Bambora Action Code.
	 * @throws Exception - Throws an Exeception if REST call fails.
	 * @return mixed
	 */
	public function get_response_code_data( $source, $action_code ) {
		$service_url = Bambora_Online_Checkout_Endpoints::get_data_endpoint() . "/responsecodes/{$source}/{$action_code}";
		return $this->call_rest_service( $service_url, null, self::GET );
	}

	/**
	 * Make a capture request to Bambora
	 *
	 * @param string                                        $transaction_id - Bambora Transaction Id.
	 * @param int                                           $amount - Amount.
	 * @param string                                        $currency - Currency.
	 * @param array<Bambora_Online_Checkout_Orderline>|null $capture_lines - Order Lines.
	 * @throws Exception - Throws an Exeception if REST call fails.
	 * @return mixed
	 */
	public function capture( $transaction_id, $amount, $currency, $capture_lines = null ) {
		$service_url      = Bambora_Online_Checkout_Endpoints::get_transaction_endpoint() . "/transactions/{$transaction_id}/capture";
		$data             = array();
		$data['amount']   = intval( $amount );
		$data['currency'] = $currency;
		if ( isset( $capture_lines ) ) {
			$data['invoicelines'] = $capture_lines;
		}
		$json_data = wp_json_encode( $data );
		return $this->call_rest_service( $service_url, $json_data, self::POST );
	}

	/**
	 * Make a credit request to Bambora
	 *
	 * @param string                                   $transaction_id - Bambora Transaction Id.
	 * @param int                                      $amount - Amount.
	 * @param string                                   $currency - Currency.
	 * @param Bambora_Online_Checkout_Orderline[]|null $credit_lines - Order Lines.
	 * @return mixed
	 * @throws Exception - Throws an Exeception if REST call fails.
	 */
	public function credit( $transaction_id, $amount, $currency, $credit_lines ) {
		$service_url = Bambora_Online_Checkout_Endpoints::get_transaction_endpoint() . "/transactions/{$transaction_id}/credit";

		$data             = array();
		$data['amount']   = intval( $amount );
		$data['currency'] = $currency;
		if ( isset( $credit_lines ) ) {
			$data['invoicelines'] = $credit_lines;
		}
		$json_data = wp_json_encode( $data );
		return $this->call_rest_service( $service_url, $json_data, self::POST );
	}

	/**
	 * Make a delete request to Bambora
	 *
	 * @param string $transaction_id - Bambora Transaction Id.
	 * @return mixed
	 * @throws Exception - Throws an Exeception if REST call fails.
	 */
	public function delete( $transaction_id ) {
		$service_url = Bambora_Online_Checkout_Endpoints::get_transaction_endpoint() . "/transactions/{$transaction_id}/delete";
		return $this->call_rest_service( $service_url, null, self::POST );
	}

	/**
	 * Get specific transaction from Bambora
	 *
	 * @param string $transaction_id - Bambora Transaction Id.
	 * @return mixed
	 * @throws Exception - Throws an Exeception if REST call fails.
	 */
	public function get_transaction( $transaction_id ) {
		$service_url = Bambora_Online_Checkout_Endpoints::get_merchant_endpoint() . "/transactions/{$transaction_id}";
		return $this->call_rest_service( $service_url, null, self::GET );
	}

	/**
	 * Get transaction operations for a specific transaction from Bambora
	 *
	 * @param string $transaction_id - Bambora Transaction Id.
	 * @return mixed
	 * @throws Exception - Throws an Exeception if REST call fails.
	 */
	public function get_transaction_operations( $transaction_id ) {
		$service_url = Bambora_Online_Checkout_Endpoints::get_merchant_endpoint() . "/transactions/{$transaction_id}/transactionoperations";
		return $this->call_rest_service( $service_url, null, self::GET );
	}

	/**
	 * Get available payment types for the amount and currency from Bambora
	 *
	 * @param string $currency - Currency.
	 * @param int    $amount - Amount.
	 * @return mixed
	 * @throws Exception - Throws an Exeception if REST call fails.
	 */
	public function get_payment_types( $currency, $amount ) {
		$service_url = Bambora_Online_Checkout_Endpoints::get_merchant_endpoint() . "/paymenttypes?currency={$currency}&amount={$amount}";
		return $this->call_rest_service( $service_url, null, self::GET );
	}

	/**
	 * Check if apikey is allowed to handle Payment Requests
	 *
	 * @return mixed
	 * @throws Exception - Throws an Exeception if REST call fails.
	 */
	public function get_merchant_api_permissions() {
		$service_url = Bambora_Online_Checkout_Endpoints::get_login_endpoint() . '/merchant/functionpermissionsandfeatures';
		return $this->call_rest_service( $service_url, null, self::GET );
	}

	/**
	 * Create a PaymentRequest
	 *
	 * @param Bambora_Online_Checkout_Payment_Request $bambora_paymentrequest - Bambora PaymentRequest request.
	 * @return mixed
	 * @throws Exception - Throws an Exeception if REST call fails.
	 */
	public function create_payment_request( $bambora_paymentrequest ) {
		$service_url = Bambora_Online_Checkout_Endpoints::get_checkout_api_endpoint() . '/paymentrequests';
		$json_data   = wp_json_encode( $bambora_paymentrequest );
		return $this->call_rest_service( $service_url, $json_data, self::POST );
	}

	/**
	 * Get a PaymentRequest
	 *
	 * @param string $payment_request_id - Bambora PaymentRequest Id.
	 * @return mixed
	 * @throws Exception - Throws an Exeception if REST call fails.
	 */
	public function get_payment_request( $payment_request_id ) {
		$service_url = Bambora_Online_Checkout_Endpoints::get_checkout_api_endpoint() . "/paymentrequests/{$payment_request_id}";
		return $this->call_rest_service( $service_url, null, self::GET );
	}

	/**
	 * Send PaymentRequest email
	 *
	 * @param string                                                  $payment_request_id - Bambora PaymentRequest Id.
	 * @param Bambora_Online_Checkout_Payment_Request_Email_Recipient $recipient - Reciepient.
	 * @return mixed
	 * @throws Exception - Throws an Exeception if REST call fails.
	 */
	public function send_payment_request_email( $payment_request_id, $recipient ) {
		$service_url = Bambora_Online_Checkout_Endpoints::get_checkout_api_endpoint() . "/paymentrequests/{$payment_request_id}/email-notifications";
		$json_data   = wp_json_encode( $recipient );
		return $this->call_rest_service( $service_url, $json_data, self::POST );
	}

	/**
	 * Delete a PaymentRequest
	 *
	 * @param string $payment_request_id - Bambora PaymentRequest Id.
	 * @return mixed
	 * @throws Exception - Throws an Exeception if REST call fails.
	 */
	public function delete_payment_request( $payment_request_id ) {
		$service_url = Bambora_Online_Checkout_Endpoints::get_checkout_api_endpoint() . "/paymentrequests/{$payment_request_id}";
		return $this->call_rest_service( $service_url, null, self::DELETE );
	}

	/**
	 * Authorize subscription by subscription Id
	 *
	 * @param string $subscription_id - Bambora Subscription Id.
	 * @param int    $amount - Amount.
	 * @param string $currency - Currency.
	 * @param string $order_id - WP Order Id.
	 * @param int    $instant_capture_amount - Instant Capture Amount.
	 * @return mixed
	 * @throws Exception - Throws an Exeception if REST call fails.
	 */
	public function authorize_subscription( $subscription_id, $amount, $currency, $order_id, $instant_capture_amount ) {
		$service_url                   = Bambora_Online_Checkout_Endpoints::get_subscription_endpoint() . "/subscriptions/{$subscription_id}/authorize";
		$data                          = array();
		$data['authorize']['currency'] = $currency;
		$data['authorize']['amount']   = intval( $amount );
		$data['authorize']['orderid']  = $order_id;
		if ( $instant_capture_amount > 0 ) {
			$data['authorize']['instantcaptureamount'] = intval( $instant_capture_amount );
		}
		$json_data = wp_json_encode( $data );
		return $this->call_rest_service( $service_url, $json_data, self::POST );
	}

	/**
	 * Delete subscription by subscription Id
	 *
	 * @param string $subscription_id - Bambora Subscription Id.
	 * @return mixed
	 * @throws Exception - Throws an Exeception if REST call fails.
	 */
	public function delete_subscription( $subscription_id ) {
		$service_url = Bambora_Online_Checkout_Endpoints::get_subscription_endpoint() . "/subscriptions/{$subscription_id}";
		return $this->call_rest_service( $service_url, null, self::DELETE );
	}
}
