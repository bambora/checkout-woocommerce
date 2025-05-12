<?php


/**
 * Bambora_Online_Checkout_Payment_Request.
 */
class Bambora_Online_Checkout_Payment_Request {
	/**
	 * Payment Request Reference
	 *
	 * @var string
	 */
	public $reference;
	/**
	 * Payment Request Parameters
	 *
	 * @var Bambora_Online_Checkout_Payment_Request_Parameters
	 */
	public $parameters;
	/**
	 * Payment Request Description
	 *
	 * @var string
	 */
	public $description;
	/**
	 * Payment Request Terms Url
	 *
	 * @var string
	 */
	public $termsurl;
	/**
	 * Payment Request Status
	 *
	 * @var string
	 */
	public $status;
}
