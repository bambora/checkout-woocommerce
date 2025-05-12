<?php

/**
 * Bambora Online Checkout Url
 */
class Bambora_Online_Checkout_Url {
	/**
	 * Url Accept
	 *
	 * @var string
	 */
	public $accept;
	/**
	 * Url Callback
	 *
	 * @var Bambora_Online_Checkout_Callback[]
	 */
	public $callbacks;
	/**
	 * Url Decline
	 *
	 * @var string
	 */
	public $decline;
	/**
	 * Url Immediate Redirect to Accept
	 *
	 * @var bool
	 */
	public $immediateredirecttoaccept;
}
