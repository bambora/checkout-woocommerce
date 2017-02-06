<?php
/**
 * Bambora Online Checkout for WooCommerce
 *
 * @author Bambora
 * @package bambora_online_checkout
 */

/**
 * Bambora Customer
 */
class Bambora_Customer {
	/** @var string */
	public $email;
	/** @var string */
	public $phonenumber;
	/** @var string */
	public $phonenumbercountrycode;
}

/**
 * Bambora Order
 */
class Bambora_Order {

	/** @var Bambora_Address */
	public $billingaddress;
	/** @var string */
	public $currency;
	/** @var Bambora_Orderline[] */
	public $lines;
	/** @var string */
	public $ordernumber;
	/** @var Bambora_Address */
	public $shippingaddress;
	/** @var long */
	public $total;
	/** @var long */
	public $vatamount;
}

/**
 * Bambora Address
 */
class Bambora_Address {
	/** @var string */
	public $att;
	/** @var string */
	public $city;
	/** @var string */
	public $country;
	/** @var string */
	public $firstname;
	/** @var string */
	public $lastname;
	/** @var string */
	public $street;
	/** @var string */
	public $zip;
}

/**
 * Bambora Orderline
 */
class Bambora_Orderline {
	/** @var string */
	public $description;
	/** @var string */
	public $id;
	/** @var string */
	public $linenumber;
	/** @var float */
	public $quantity;
	/** @var string */
	public $text;
	/** @var int|long */
	public $totalprice;
	/** @var int|long */
	public $totalpriceinclvat;
	/** @var int|long */
	public $totalpricevatamount;
	/** @var string */
	public $unit;
	/** @var int|long */
	public $vat;
}

/**
 * Bambora Url
 */
class Bambora_Url {
	/** @var string */
	public $accept;
	/** @var  Bambora_Callback[]  */
	public $callbacks;
	/** @var string */
	public $decline;
}

/**
 * Bambora Callback
 */
class Bambora_Callback {
	/** @var string */
	public $url;
}

/**
 * Bambora Ui Message
 */
class Bambora_Ui_Message {
	/** @var string */
	public $type;
	/** @var string */
	public $title;
	/** @var string */
	public $message;
}

/**
 * Bambora Checkout Request
 */
class Bambora_Checkout_Request {

	/** @var Bambora_Customer */
	public $customer;
	/** @var long */
	public $instantcaptureamount;
	/** @var string */
	public $language;
	/** @var Bambora_Order */
	public $order;
	/** @var Bambora_Url */
	public $url;
	/** @var int */
	public $paymentwindowid;
}
