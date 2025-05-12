<?php

/**
 * Bambora_Online_Checkout_Payment_Request_Email_Recipient.
 */
class Bambora_Online_Checkout_Payment_Request_Email_Recipient {
	/**
	 * Payment Request Email Recipient Message
	 *
	 * @var string
	 */
	public $message;
	/**
	 * Payment Request Email Recipient To
	 *
	 * @var Bambora_Online_Checkout_Payment_Request_Email_Recipient_Address
	 */
	public $to;
	/**
	 * Payment Request Email Recipient Reply to
	 *
	 * @var Bambora_Online_Checkout_Payment_Request_Email_Recipient_Address
	 */
	public $replyto;
}
