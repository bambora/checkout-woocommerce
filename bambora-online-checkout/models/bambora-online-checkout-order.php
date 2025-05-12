<?php

/**
 * Bambora Online Checkout Order
 */
class Bambora_Online_Checkout_Order {
	/**
	 * Order Id
	 *
	 * @var string
	 */
	public $id;
	/**
	 * Order Total Amount in MinorUnits
	 *
	 * @var int
	 */
	public $total;
	/**
	 * Order Vat Amount in MinorUnits
	 *
	 * @var int
	 */
	public $vatamount;
	/**
	 * Order Currency
	 *
	 * @var string
	 */
	public $currency;
	/**
	 * Order Billing Address
	 *
	 * @var Bambora_Online_Checkout_Address
	 */
	public $billingaddress;
	/**
	 * Order Shipping Address
	 *
	 * @var Bambora_Online_Checkout_Address
	 */
	public $shippingaddress;
	/**
	 * Order Invoice Lines
	 *
	 * @var Bambora_Online_Checkout_Orderline[]
	 */
	public $lines;
}
