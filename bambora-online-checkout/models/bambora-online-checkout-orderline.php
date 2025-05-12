<?php

/**
 * Bambora Online Checkout Orderline
 */
class Bambora_Online_Checkout_Orderline {

	/**
	 * OrderLine Description
	 *
	 * @var string
	 */
	public $description;
	/**
	 * OrderLine Id
	 *
	 * @var string
	 */
	public $id;
	/**
	 * OrderLine Line Number
	 *
	 * @var string
	 */
	public $linenumber;
	/**
	 * OrderLine Quantity
	 *
	 * @var float
	 */
	public $quantity;
	/**
	 * OrderLine Text
	 *
	 * @var string
	 */
	public $text;
	/**
	 * OrderLine Total Price in MinorUnits
	 *
	 * @var int
	 */
	public $totalprice;
	/**
	 * OrderLine  Total Prica Including Vat in MinorUnits
	 *
	 * @var int
	 */
	public $totalpriceinclvat;
	/**
	 * OrderLine Total Price Vat Amount in MinorUnits
	 *
	 * @var int
	 */
	public $totalpricevatamount;
	/**
	 * OrderLine Unit
	 *
	 * @var string
	 */
	public $unit;
	/**
	 * OrderLine Vat in percent
	 *
	 * @var int
	 */
	public $vat;
	/**
	 * OrderLine Unit Price including Vat in MinorUnits
	 *
	 * @var int
	 */
	public $unitpriceinclvat;
	/**
	 * OrderLine Unit Price in MinorUnits
	 *
	 * @var int
	 */
	public $unitprice;
	/**
	 * OrderLine Unit Price Vat Amount in MinorUnits
	 *
	 * @var int
	 */
	public $unitpricevatamount;
}
