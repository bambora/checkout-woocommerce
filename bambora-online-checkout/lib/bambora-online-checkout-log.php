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
 * Bambora Online Checkout Log
 */
class Bambora_Online_Checkout_Log
{
    /* The domain handler used to name the log */
    private $_domain = 'bambora-online-checkout';


    /* The WC_Logger instance */
    private $_logger;


    /**
     * __construct.
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        $this->_logger = new WC_Logger();
    }


    /**
     * Uses the build in logging method in WooCommerce.
     * Logs are available inside the System status tab
     *
     * @access public
     *
     * @param string|array|object
     *
     * @return void
     */
    public function add($param)
    {
        if (is_array($param)) {
            $param = print_r($param, true);
        }

        $this->_logger->add($this->_domain, $param);
    }

    /**
     * Inserts a separation line for better overview in the logs.
     *
     * @access public
     * @return void
     */
    public function separator()
    {
        $this->add('--------------------');
    }

    /**
     * Returns a link to the log files in the WP backend.
     */
    public function get_admin_link()
    {
        $log_path       = wc_get_log_file_path($this->_domain);
        $log_path_parts = explode('/', $log_path);

        return add_query_arg(array(
            'page'     => 'wc-status',
            'tab'      => 'logs',
            'log_file' => end($log_path_parts)
        ), admin_url('admin.php'));
    }
}
