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
 * @copyright Bambora Online (https://bambora.com)
 * @license   Bambora Online
 *
 */

/**
 * Bambora Online Checkout Customer
 */
class Bambora_Online_Checkout_Customer {
    /** @var string */
    public $email;
    /** @var string */
    public $phonenumber;
    /** @var string */
    public $phonenumbercountrycode;
}

/**
 * Bambora Online Checkout Order
 */
class Bambora_Online_Checkout_Order {

    /** @var Bambora_Online_Checkout_Address */
    public $billingaddress;
    /** @var string */
    public $currency;
    /** @var Bambora_Online_Checkout_Orderline[] */
    public $lines;
    /** @var string */
    public $ordernumber;
    /** @var Bambora_Online_Checkout_Address */
    public $shippingaddress;
    /** @var long */
    public $total;
    /** @var long */
    public $vatamount;
}

/**
 * Bambora Online Checkout Address
 */
class Bambora_Online_Checkout_Address {
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
 * Bambora Online Checkout Orderline
 */
class Bambora_Online_Checkout_Orderline {
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
	/** @var int|long */
    public $unitpriceinclvat;
	/** @var int|long */
    public $unitprice;
	/** @var int|long */
    public $unitpricevatamount;

}

/**
 * Bambora Online Checkout Url
 */
class Bambora_Online_Checkout_Url {
    /** @var string */
    public $accept;
    /** @var  Bambora_Online_Checkout_Callback[]  */
    public $callbacks;
    /** @var string */
    public $decline;
}

/**
 * Bambora Online Checkout Callback
 */
class Bambora_Online_Checkout_Callback {
    /** @var string */
    public $url;
}



/**
 * Bambora Online Checkout Request
 */
class Bambora_Online_Checkout_Subscription {
    /** @var string */
    public $action;
    /** @var string */
    public $decription;
    /** @var string */
    public $reference;
}

/**
 * Bambora Online Checkout Request
 */
class Bambora_Online_Checkout_Request {

    /** @var Bambora_Online_Checkout_Customer */
    public $customer;
    /** @var long */
    public $instantcaptureamount;
    /** @var string */
    public $language;
    /** @var Bambora_Online_Checkout_Order */
    public $order;
    /** @var Bambora_Online_Checkout_Subscription */
    public $subscription;
    /** @var Bambora_Online_Checkout_Url */
    public $url;
    /** @var int */
    public $paymentwindowid;
    /** @var string */
	public $securityexemption;
	/** @var string */
	public $securitylevel;
}
