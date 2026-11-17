<?php

defined( 'ABSPATH' ) || exit;

/**
 * Bambora Online Checkout Log
 */
class Bambora_Online_Checkout_Log {
	/* The source used to name the log */
	const DOMAIN = 'bambora-online-checkout';


	/**
	 * The WooCommerce logger
	 *
	 * @var WC_Logger_Interface
	 */
	private $logger;


	/**
	 * __construct.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		$this->logger = wc_get_logger();
	}


	/**
	 * Uses the build in logging method in WooCommerce.
	 * Logs are available inside the System status tab.
	 *
	 * @param mixed $param - Item to put into the Log.
	 *
	 * @return void
	 */
	public function add( $param ) {
		if ( is_array( $param ) ) {
			$param = wp_json_encode( $param );
		}

		$this->logger->notice( (string) $param, array( 'source' => self::DOMAIN ) );
	}

	/**
	 * Inserts a separation line for better overview in the logs.
	 *
	 * @return void
	 */
	public function separator() {
		$this->add( '--------------------' );
	}

	/**
	 * Returns a link to the log files of the module in the WP backend.
	 *
	 * @return string
	 */
	public function get_admin_link() {
		return add_query_arg(
			array(
				'page'   => 'wc-status',
				'tab'    => 'logs',
				'source' => self::DOMAIN,
			),
			admin_url( 'admin.php' )
		);
	}
}
