<?php

/**
 * Bambora Online Checkout Request
 */
class Bambora_Online_Checkout_Request {
	/**
	 * Request Customer
	 *
	 * @var Bambora_Online_Checkout_Customer
	 */
	public $customer;
	/**
	 * Request Instant Capture Amount in MinorUnits
	 *
	 * @var int
	 */
	public $instantcaptureamount;
	/**
	 * Request Language
	 *
	 * @var string
	 */
	public $language;
	/**
	 * Request Order
	 *
	 * @var Bambora_Online_Checkout_Order
	 */
	public $order;
	/**
	 * Request Subscription
	 *
	 * @var Bambora_Online_Checkout_Subscription
	 */
	public $subscription;
	/**
	 * Request Url
	 *
	 * @var Bambora_Online_Checkout_Url
	 */
	public $url;
	/**
	 * Request Payment Window
	 *
	 * @var Bambora_Online_Checkout_Request_Payment_Window
	 */
	public $paymentwindow;
	/**
	 * Request Security Exemption
	 *
	 * @var string
	 */
	public $securityexemption;
	/**
	 * Request Security Level
	 *
	 * @var string
	 */
	public $securitylevel;
}
