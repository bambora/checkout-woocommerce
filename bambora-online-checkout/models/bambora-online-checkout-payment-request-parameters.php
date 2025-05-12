<?php

/**
 * Bambora_Online_Checkout_Payment_Request_Parameters.
 */
class Bambora_Online_Checkout_Payment_Request_Parameters {
	/**
	 * Payment Request Parameters Order
	 *
	 * @var Bambora_Online_Checkout_Order
	 */
	public $order;
	/**
	 * Payment Request Parameters Instant Capture Amount in MinorUnits
	 *
	 * @var int
	 */
	public $instantcaptureamount;
	/**
	 * Payment Request Parameters Payment Window
	 *
	 * @var Bambora_Online_Checkout_Request_Payment_Window
	 */
	public $paymentwindow;
	/**
	 * Payment Request Parameters Customer
	 *
	 * @var Bambora_Online_Checkout_Customer
	 */
	public $customer;
	/**
	 * Payment Request Parameters Url
	 *
	 * @var Bambora_Online_Checkout_Url
	 */
	public $url;
}
