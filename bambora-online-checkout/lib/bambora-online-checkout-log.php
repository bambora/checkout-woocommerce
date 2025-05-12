<?php

/**
 * Bambora Online Checkout Log
 */

use Automattic\WooCommerce\Utilities\LoggingUtil;
/**
 * Bambora Online Checkout Log
 */
class Bambora_Online_Checkout_Log {
	/* The domain handler used to name the log */
	const DOMAIN = 'bambora-online-checkout';


	/**
	 * The WC_Logger instance
	 *
	 * @var WC_Logger
	 */
	private $logger;


	/**
	 * __construct.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		$this->logger = new WC_Logger();
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

		$this->logger->add( self::DOMAIN, $param );
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
	 * Returns a link to the log files in the WP backend.
	 *
	 * @return string
	 */
	public function get_admin_link() {
		$log_path       = $this->get_log_file_path( self::DOMAIN );
		$log_path_parts = explode( '/', $log_path );

		return add_query_arg(
			array(
				'page'     => 'wc-status',
				'tab'      => 'logs',
				'log_file' => end( $log_path_parts ),
			),
			admin_url( 'admin.php' )
		);
	}
	/**
	 * Get Log File path
	 *
	 * @param mixed $handle - The source of the log file.
	 * @return string
	 */
	private function get_log_file_path( $handle ) {

		$directory = LoggingUtil::get_log_directory();
		$file_id   = LoggingUtil::generate_log_file_id( $handle, null, time() );
		$hash      = LoggingUtil::generate_log_file_hash( $file_id );

		return "{$directory}{$file_id}-{$hash}.log";
	}
}
