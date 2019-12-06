<?php
/**
 * Plugin Name: Bambora Online Checkout
 * Plugin URI: https://www.bambora.com
 * Description: Bambora Online Checkout payment gateway for WooCommerce
 * Version: 4.3.0
 * Author: Bambora
 * Author URI: https://www.bambora.com
 * Text Domain: bambora-online-checkout
 *
 * @author Bambora
 * @package bambora_online_checkout
 */

add_action( 'plugins_loaded', 'init_bambora_online_checkout', 0 );

/**
 * Add Bambora Online Checkout
 *
 * @return void
 * @throws Exception
 */
function init_bambora_online_checkout() {
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
        return;
    }

    define( 'BOC_LIB', dirname( __FILE__ ) . '/lib/' );
    define( 'BOC_VERSION', '4.3.0' );

    // Including Bambora files!
    include( BOC_LIB . 'bambora-online-checkout-api.php' );
    include( BOC_LIB . 'bambora-online-checkout-helper.php' );
    include( BOC_LIB . 'bambora-online-checkout-currency.php' );
    include( BOC_LIB . 'bambora-online-checkout-log.php' );

    /**
     * Bambora Online Checkout
     **/
    class Bambora_Online_Checkout extends WC_Payment_Gateway {

        /**
         * Singleton instance
         * @var Bambora_Online_Checkout
         */
        private static $_instance;

        /**
         * @param Bambora_Online_Checkout_Log
         */
        private $_boc_log;

        /**
         * get_instance
         *
         * Returns a new instance of self, if it does not already exist.
         *
         * @access public
         * @static
         * @return Bambora_Online_Checkout
         */
        public static function get_instance() {
            if (!isset( self::$_instance ) ) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        /**
         * Constructor
         */
        public function __construct() {
            $this->id = 'bambora';
            $this->method_title = 'Bambora Online Checkout';
            $this->method_description = 'Bambora Online Checkout enables easy and secure payments on your shop';
            $this->icon = WP_PLUGIN_URL . '/' . plugin_basename( dirname( __FILE__ ) ) . '/bambora-logo.svg';
            $this->has_fields = false;

            $this->supports = array(
                'products',
                'refunds',
                'subscriptions',
                'subscription_cancellation',
                'subscription_suspension',
                'subscription_reactivation',
                'subscription_amount_changes',
                'subscription_date_changes',
                'subscription_payment_method_change_customer',
				'multiple_subscriptions'
                );

            // Init the Bambora Online Checkout logger
            $this->_boc_log = new Bambora_Online_Checkout_Log();

            // Load the form fields.!
            $this->init_form_fields();

            // Load the settings.!
            $this->init_settings();

            // Initilize Bambora Online Checkout Settings
            $this->init_bambora_online_checkout_settings();

            // Set description for checkout page!
            $this->set_bambora_description_for_checkout();
        }

        /**
         * Initilize Bambora Online Checkout Settings
         */
        public function init_bambora_online_checkout_settings() {
            // Define user set variables!
            $this->enabled = array_key_exists( 'enabled', $this->settings ) ? $this->settings['enabled'] : 'yes';
            $this->title = array_key_exists( 'title', $this->settings ) ? $this->settings['title'] : 'Bambora Online Checkout';
            $this->description = array_key_exists( 'description', $this->settings ) ? $this->settings['description'] : 'Pay using Bambora Online Checkout';
            $this->merchant = array_key_exists( 'merchant', $this->settings ) ? $this->settings['merchant'] : '';
            $this->accesstoken = array_key_exists( 'accesstoken', $this->settings ) ? $this->settings['accesstoken'] : '';
            $this->secrettoken = array_key_exists( 'secrettoken', $this->settings ) ? $this->settings['secrettoken'] : '';
            $this->paymentwindowid = array_key_exists( 'paymentwindowid', $this->settings ) ? $this->settings['paymentwindowid'] : 1;
            $this->windowstate = array_key_exists( 'windowstate', $this->settings ) ? $this->settings['windowstate'] : 2;
            $this->instantcapture = array_key_exists( 'instantcapture', $this->settings ) ? $this->settings['instantcapture'] :  'no';
            $this->immediateredirecttoaccept = array_key_exists( 'immediateredirecttoaccept', $this->settings ) ? $this->settings['immediateredirecttoaccept'] :  'no';
            $this->addsurchargetoshipment = array_key_exists( 'addsurchargetoshipment', $this->settings ) ? $this->settings['addsurchargetoshipment'] :  'no';
            $this->md5key = array_key_exists( 'md5key', $this->settings ) ? $this->settings['md5key'] : '';
            $this->roundingmode = array_key_exists( 'roundingmode', $this->settings ) ? $this->settings['roundingmode'] : Bambora_Online_Checkout_Currency::ROUND_DEFAULT;
            $this->captureonstatuscomplete = array_key_exists( 'captureonstatuscomplete', $this->settings ) ? $this->settings['captureonstatuscomplete'] : 'no';
        }

        /**
         * Initilize module hooks
         */
        public function init_hooks() {
            // Actions!
            add_action( 'woocommerce_api_' . strtolower( get_class() ), array( $this, 'bambora_online_checkout_callback' ) );
            add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );

            if( is_admin() ) {
                add_action( 'add_meta_boxes', array( $this, 'bambora_online_checkout_meta_boxes' ) );
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
                add_action( 'wp_before_admin_bar_render', array( $this, 'bambora_online_checkout_actions' ) );
                add_action( 'admin_notices', array( $this, 'bambora_online_checkout_admin_notices' ) );
                if($this->captureonstatuscomplete === 'yes') {
                    add_action( 'woocommerce_order_status_completed', array( $this, 'bambora_online_checkout_order_status_completed' ) );
                }
            }

            //Subscriptions
            add_action('woocommerce_scheduled_subscription_payment_' . $this->id, array($this, 'scheduled_subscription_payment'), 10, 2);
            add_action('woocommerce_subscription_cancelled_' . $this->id, array($this, 'subscription_cancellation'));

            // Register styles!
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_wc_bambora_online_checkout_admin_styles_and_scripts' ) );
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_wc_bambora_online_checkout_front_styles' ) );
        }

        /**
         * Show messages in the Administration
         */
        public function bambora_online_checkout_admin_notices(){
            Bambora_Online_Checkout_Helper::echo_admin_notices();
        }

        /**
         * Enqueue Admin Styles and Scripts
         */
        public function enqueue_wc_bambora_online_checkout_admin_styles_and_scripts() {
            wp_register_style( 'bambora_online_checkout_admin_style', plugins_url( 'bambora-online-checkout/style/bambora-online-checkout-admin.css' ) );
            wp_enqueue_style( 'bambora_online_checkout_admin_style' );

            // Fix for load of Jquery time!
            wp_enqueue_script( 'jquery' );

            wp_enqueue_script( 'bambora_online_checkout_admin', WP_PLUGIN_URL . '/' . plugin_basename( dirname( __FILE__ ) ) . '/js/bambora-online-checkout-admin.js' );
        }

        /**
         * Enqueue Frontend Styles and Scripts
         */
        public function enqueue_wc_bambora_online_checkout_front_styles() {
            wp_enqueue_style( 'bambora_online_checkout_front_style', plugins_url( 'bambora-online-checkout/style/bambora-online-checkout-front.css' ) );
            wp_enqueue_style( 'bambora_online_checkout_front_style' );
        }

        /**
         * Initialise Gateway Settings Form Fields
         */
        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => 'Activate module',
                    'type' => 'checkbox',
                    'label' => 'Enable Bambora Online Checkout as a payment option.',
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => 'Title',
                    'type' => 'text',
                    'description' => 'The title of the payment method displayed to the customers.',
                    'default' => 'Bambora Online Checkout'
                ),
                'description' => array(
                    'title' => 'Description',
                    'type' => 'textarea',
                    'description' => 'The description of the payment method displayed to the customers.',
                    'default' => 'Pay using Bambora Online Checkout'
                ),
                'merchant' => array(
                    'title' => 'Merchant number',
                    'type' => 'text',
                    'description' => 'The number identifying your Bambora merchant account.',
                    'default' => ''
                ),
                'accesstoken' => array(
                    'title' => 'Access token',
                    'type' => 'text',
                    'description' => 'The Access token for the API user received from the Bambora administration.',
                    'default' => ''
                ),
                'secrettoken' => array(
                    'title' => 'Secret token',
                    'type' => 'password',
                    'description' => 'The Secret token for the API user received from the Bambora administration.',
                    'default' => ''
                ),
                'md5key' => array(
                    'title' => 'MD5 Key',
                    'type' => 'text',
                    'description' => 'The MD5 key is used to stamp data sent between WooCommerce and Bambora to prevent it from being tampered with. The MD5 key is optional but if used here, must be the same as in the Bambora administration.',
                    'default' => ''
                ),
                'paymentwindowid' => array(
                    'title' => 'Payment Window ID',
                    'type' => 'text',
                    'description' => 'The ID of the payment window to use.',
                    'default' => '1'
                ),
                'windowstate' => array(
                    'title' => 'Window state',
                    'type' => 'select',
                    'description' => 'Please select if you want the Payment window shown as an overlay or as full screen.',
                    'options' => array( 2 => 'Overlay',1 => 'Full screen' ),
                    'label' => 'Window state',
                    'default' => 2
                ),
                'instantcapture' => array(
                    'title' => 'Instant capture',
                    'type' => 'checkbox',
                    'description' => 'Capture the payments at the same time they are authorized. In some countries, this is only permitted if the consumer receives the products right away Ex. digital products.',
                    'label' => 'Enable Instant Capture',
                    'default' => 'no'
                ),
                'immediateredirecttoaccept' => array(
                    'title' => 'Immediate Redirect',
                    'type' => 'checkbox',
                    'description' => 'Immediately redirect your customer back to you shop after the payment completed.',
                    'label' => 'Enable Immediate redirect',
                    'default' => 'no'
                ),
                'addsurchargetoshipment' => array(
                    'title' => 'Add Surcharge',
                    'type' => 'checkbox',
                    'description' => 'Display surcharge amount on the order as an item',
                    'label' => 'Enable Surcharge',
                    'default' => 'no'
                ),
                'captureonstatuscomplete' => array(
                    'title' => 'Capture on status Completed',
                    'type' => 'checkbox',
                    'description' => 'When this is enabled the full payment will be captured when the order status changes to Completed',
                    'default' => 'no'
                ),
                'roundingmode' => array(
                    'title' => 'Rounding mode',
                    'type' => 'select',
                    'description' => 'Please select how you want the rounding of the amount sendt to the payment system',
                    'options' => array( Bambora_Online_Checkout_Currency::ROUND_DEFAULT => 'Default', Bambora_Online_Checkout_Currency::ROUND_UP => 'Always up', Bambora_Online_Checkout_Currency::ROUND_DOWN => 'Always down' ),
                    'label' => 'Rounding mode',
                    'default' => 'normal',
                )
            );
        }

        /**
         * Admin Panel Options
         */
        public function admin_options() {
            $version = BOC_VERSION;

            $html = "<h3>Bambora Online Checkout v{$version}</h3>";

            $html .= Bambora_Online_Checkout_Helper::create_admin_debug_section();
            $html .= '<h3 class="wc-settings-sub-title">Module configuration</h3>';
            $html .= '<table class="form-table">';

            // Generate the HTML For the settings form.!
            $html .= $this->generate_settings_html( array(), false );
            $html .= '</table>';

            echo ent2ncr( $html );
        }

        /**
         * Capture the payment on order status completed
         * @param mixed $order_id
         */
        public function bambora_online_checkout_order_status_completed($order_id){
            if( !$this->module_check( $order_id ) ) {
                return;
            }

            $order = wc_get_order( $order_id );
            $order_total = Bambora_Online_Checkout_Helper::is_woocommerce_3() ? $order->get_total() : $order->order_total;
            $capture_result = $this->bambora_online_checkout_capture_payment($order_id, $order_total, '');

            if ( is_wp_error( $capture_result ) ) {
                $message = $capture_result->get_error_message( 'bambora_online_checkout_error' );
                $this->_boc_log->add( $message );
                Bambora_Online_Checkout_Helper::add_admin_notices(Bambora_Online_Checkout_Helper::ERROR, $message);
            } else {
                $message = __( "The Capture action was a success for order {$order_id} ", 'bambora-online-checkout' );
                Bambora_Online_Checkout_Helper::add_admin_notices(Bambora_Online_Checkout_Helper::SUCCESS, $message);
            }
        }

        /**
         * There are no payment fields for bambora, but we want to show the description if set.
         **/
        public function payment_fields() {
            if ( $this->description ) {
                $text_replace = wptexturize( $this->description );
                $text_remove_double_lines = wpautop( $text_replace );

                echo $text_remove_double_lines;
            }
        }

        /**
         * Set the WC Payment Gateway description for the checkout page
         */
        public function set_bambora_description_for_checkout() {
            global $woocommerce;
            $description = '';
            $cart = Bambora_Online_Checkout_Helper::is_woocommerce_3() ? WC()->cart : $woocommerce->cart;
            if ( isset( $cart ) ) {
                $error_message = __( 'Could not load the payment types', 'bambora-online-checkout' );
                try{
                    $currency = get_woocommerce_currency();
                    if ( $currency ) {
                        $minorunits = Bambora_Online_Checkout_Currency::get_currency_minorunits( $currency );
                        $amount = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $cart->total, $minorunits, $this->roundingmode );
                        $api_key = $this->get_api_key();
                        $api = new Bambora_Online_Checkout_Api( $api_key );
                        $get_payment_types_response = $api->get_payment_types( $currency, $amount );
                        if( isset( $get_payment_types_response ) && $get_payment_types_response->meta->result ) {
                            $payment_types = array();
                            foreach ( $get_payment_types_response->paymentcollections as $payment ) {
                                foreach ( $payment->paymentgroups as $card ) {
                                    $payment_types[] = $card->id;
                                }
                            }
                            ksort( $payment_types );

                            $payment_types_html = '<div class="bambora_payment_types">';
                            foreach ( $payment_types as $id ) {
                                $payment_types_html .= '<img class="bambora_payment_type" src="https://d3r1pwhfz7unl9.cloudfront.net/paymentlogos/' . $id . '.svg" />';
                            }
                            $payment_types_html .= '</div>';
                            $description = $payment_types_html;
                        } else {
                            $get_payment_types_response_error_enduser = isset( $get_payment_types_response ) ? $get_payment_types_response->meta->message->enduser : __( 'No connection to Bambora', 'bambora-online-checkout' );
                            $get_payment_types_response_error_merchant = isset( $get_payment_types_response ) ? $get_payment_types_response->meta->message->merchant : __( 'No connection to Bambora', 'bambora-online-checkout' );

                            $this->_boc_log->add( "{$error_message} - {$get_payment_types_response_error_merchant}" );
                            $description =  " - {$error_message} - {$get_payment_types_response_error_enduser}";
                        }
                    } else {
                        $message = " - {$error_message} - " . __( 'Could not load the currency', 'bambora-online-checkout' );
                        $this->_boc_log->add( $message );
                        $description = $message;
                    }
                }
                catch( Exception $ex) {
                    $description = " - {$error_message}";
                    $exception_message = $ex->getMessage();
                    $this->_boc_log->add( "Could not load the payment types - Reason: {$exception_message}"  );
                }
            }
            $this->description .= $description;
        }

        /**
         * Get the bambora online checkout logger
         *
         * @return Bambora_Online_Checkout_Log
         */
        public function get_boc_logger() {
            return $this->_boc_log;
        }

        /**
         * Handle scheduled subscription payments
         * @param mixed $amount_to_charge
         * @param WC_Order $renewal_order
         */
        public function scheduled_subscription_payment( $amount_to_charge, $renewal_order ) {
            $subscription = Bambora_Online_Checkout_Helper::get_subscriptions_for_renewal_order( $renewal_order );
            $result = $this->process_subscription_payment( $amount_to_charge, $renewal_order, $subscription );
            $renewal_order_id = Bambora_Online_Checkout_Helper::is_woocommerce_3() ? $renewal_order->get_id() : $renewal_order->id;

            // Remove the Bambora Online Checkout subscription id copied from the subscription
            delete_post_meta($renewal_order_id, Bambora_Online_Checkout_Helper::BAMBORA_ONLINE_CHECKOUT_SUBSCRIPTION_ID );

            if ( is_wp_error( $result ) ) {
                $message = sprintf( __( 'Bambora Online Checkout Subscription could not be authorized for renewal order # %s - %s', 'bambora-online-checkout' ), $renewal_order_id, $result->get_error_message( 'bambora_online_checkout_error' ) );
                $renewal_order->update_status( 'failed', $message );
                $this->_boc_log->add( $message );
            }
        }

        /**
         * Process a subscription renewal
         *
         * @param mixed $amount
         * @param WC_Order $renewal_order
         * @param WC_Subscription $subscription
         */
        public function process_subscription_payment($amount, $renewal_order, $subscription) {
            try {
                $bambora_subscription_id = Bambora_Online_Checkout_Helper::get_bambora_online_checkout_subscription_id( $subscription );
                if( strlen( $bambora_subscription_id ) === 0) {
                    return new WP_Error( 'bambora_online_checkout_error', __( 'Bambora Online Checkout Subscription id was not found', 'bambora-online-checkout' ) );
                }

                $order_currency = Bambora_Online_Checkout_Helper::is_woocommerce_3() ? $renewal_order->get_currency() : $renewal_order->get_order_currency();
                $minorunits = Bambora_Online_Checkout_Currency::get_currency_minorunits( $order_currency );
                $amount = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $amount, $minorunits, $this->roundingmode );

                $renewal_order_id = Bambora_Online_Checkout_Helper::is_woocommerce_3() ? $renewal_order->get_id() : $renewal_order->id;
                $api_key = $this->get_api_key();
                $api = new Bambora_Online_Checkout_Api( $api_key );
                $authorize_response = $api->authorize_subscription( $bambora_subscription_id, $amount, $order_currency, $renewal_order_id );

                if($authorize_response->meta->result == false) {
                    return new WP_Error( 'bambora_online_checkout_error', $authorize_response->meta->message->merchant );
                }
                $renewal_order->payment_complete( $authorize_response->transactionid );

                // Add order note
                $message = sprintf( __( 'Bambora Online Checkout Subscription was authorized for renewal order %s with transaction id %s','bambora-online-checkout' ), $renewal_order_id, $authorize_response->transactionid );
			    $renewal_order->add_order_note( $message );
                $subscription->add_order_note( $message );

                return true;
            }
            catch( Exception $ex ) {
                return new WP_Error( 'bambora_online_checkout_error', $ex->getMessage() );
            }
        }

        /**
         * Cancel a subscription
         *
         * @param WC_Subscription $subscription
         * @param bool $force_delete
         */
        public function subscription_cancellation( $subscription, $force_delete = false ) {
            if ( $subscription->get_status() === 'cancelled' || $force_delete ) {
                $result = $this->process_subscription_cancellation( $subscription );

                if( is_wp_error( $result ) ) {
                    $message = sprintf( __( 'Bambora Online Checkout Subscription could not be canceled - %s', 'bambora-online-checkout' ), $result->get_error_message( 'bambora_online_checkout_error' ) );
                    $subscription->add_order_note( $message );
                    $this->_boc_log->add( $message );
                }
            }
        }

        /**
         * Process canceling of a subscription
         *
         * @param WC_Subscription $subscription
         */
        protected function process_subscription_cancellation( $subscription ) {
            try {
                if( Bambora_Online_Checkout_Helper::order_is_subscription( $subscription ) ) {
                    $bambora_subscription_id = Bambora_Online_Checkout_Helper::get_bambora_online_checkout_subscription_id( $subscription );
                    if( strlen( $bambora_subscription_id ) === 0) {
                        $orderNote = __( 'Bambora Online Checkout Subscription ID was not found', 'bambora-online-checkout' );
                        return new WP_Error( 'bambora_online_checkout_error', $orderNote );
                    }

                    $api_key = $this->get_api_key();
                    $api = new Bambora_Online_Checkout_Api( $api_key );
                    $delete_response = $api->delete_subscription( $bambora_subscription_id );

                    if( $delete_response->meta->result ) {
                        $subscription->add_order_note( sprintf( __( 'Subscription successfully Canceled. - Bambora Online Checkout Subscription Id: %s', 'bambora-online-checkout' ), $bambora_subscription_id ) );
                    } else {
                        $orderNote = sprintf( __( 'Bambora Online Checkout Subscription Id: %s', 'bambora-online-checkout' ), $bambora_subscription_id );
                        $orderNote = " - {$delete_response->meta->message->merchant}";
                        return new WP_Error( 'bambora_online_checkout_error', $orderNote );
                    }
                }
                return true;
            }
            catch(Exception $ex) {
                return new WP_Error( 'bambora_online_checkout_error', $ex->getMessage() );
            }
        }

        /**
         * Process the payment and return the result
         *
         * @param int $order_id
         * @return string[]
         */
        public function process_payment( $order_id ) {
            $order = wc_get_order( $order_id );
            if (Bambora_Online_Checkout_Helper::order_contains_switch( $order ) && ! $order->needs_payment()) {
                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url( $order )
                );
            }
            $api_key = $this->get_api_key();
            $api = new Bambora_Online_Checkout_Api( $api_key );
            $bambora_checkout_request = $this->create_bambora_checkout_request( $order );
            $bambora_checkout_response = $api->set_checkout_session( $bambora_checkout_request );
            if ( ! isset( $bambora_checkout_response ) || ! $bambora_checkout_response->meta->result ) {
                $error_message = isset( $bambora_checkout_response ) ? $bambora_checkout_response->meta->message->enduser : __( 'No connection to Bambora', 'bambora-online-checkout' );
                $message = __( 'Could not retrive the payment window. Reason:', 'bambora-online-checkout' ) . ' ' . $error_message;
                $this->_boc_log->add( $message );
                wc_add_notice( $message, 'error' );
                return array(
                    'result' => 'fail',
                    'redirect' => ''
                );
            }

            return array(
                'result' => 'success',
                'redirect' => add_query_arg('checkout_token', $bambora_checkout_response->token, $order->get_checkout_payment_url( true )
            ));
        }

        /**
         * Receipt page
         *
         * @param int $order
         **/
        public function receipt_page( $order_id ) {
            $params = stripslashes_deep( $_GET );
            if( array_key_exists('checkout_token', $params) && strlen($params['checkout_token']) > 0 ) {
                $checkout_token = $params['checkout_token'];
                $html = Bambora_Online_Checkout_Helper::create_bambora_online_checkout_payment_html( $checkout_token, $this->windowstate );
                echo $html;
            } else {
                $message = sprintf( __( 'Could not open payment window for order id %s - No checkout token provided', 'bambora-online-checkout' ), $order_id );
                $this->_boc_log->add( $message );
                wc_add_notice( $message , 'error' );
            }
        }

        /**
         * Create Checkout Request
         *
         * @param WC_Order $order
         * @return Bambora_Online_Checkout_Request
         */
        protected function create_bambora_checkout_request( $order ) {
            $is_request_to_change_payment_method = Bambora_Online_Checkout_Helper::order_is_subscription( $order );

            $request = new Bambora_Online_Checkout_Request();
            $request->customer = $this->create_bambora_customer( $order );
            $request->order = $this->create_bambora_order( $order, $is_request_to_change_payment_method );
            $request->instantcaptureamount = $this->instantcapture === 'yes' ? $request->order->total : 0;
            $request->language = str_replace( '_', '-', get_locale() );
            $request->paymentwindowid = $this->paymentwindowid;
            $request->url = $this->create_bambora_url( $order );

            if(Bambora_Online_Checkout_Helper::woocommerce_subscription_plugin_is_active() && ( Bambora_Online_Checkout_Helper::order_contains_subscription( $order ) || $is_request_to_change_payment_method ))
            {
                $bambora_subscription = $this->create_bambora_subscription();
                $request->subscription = $bambora_subscription;
            }

            return $request;
        }

        /**
         * Create Bambora customer
         *
         * @param WC_Order $order
         * @return Bambora_Online_Checkout_Customer
         * */
        protected function create_bambora_customer( $order ) {
            $bambora_customer = new Bambora_Online_Checkout_Customer();
            if(Bambora_Online_Checkout_Helper::is_woocommerce_3()) {
                $bambora_customer->email = $order->get_billing_email();
                $bambora_customer->phonenumber = $order->get_billing_phone();
                $bambora_customer->phonenumbercountrycode = $order->get_billing_country();
            } else {
                $bambora_customer->email = $order->billing_email;
                $bambora_customer->phonenumber = $order->billing_phone;
                $bambora_customer->phonenumbercountrycode = $order->billing_country;
            }

            return $bambora_customer;
        }

        /**
         * Create Bambora order
         *
         * @param WC_Order $order
         * @param int      $minorunits
         * @return Bambora_Online_Checkout_Order
         * */
        protected function create_bambora_order( $order, $is_request_to_change_payment_method ) {
            $currency = Bambora_Online_Checkout_Helper::is_woocommerce_3() ? $order->get_currency() : $order->get_order_currency();
            $minorunits = Bambora_Online_Checkout_Currency::get_currency_minorunits( $currency );

            $bambora_order = new Bambora_Online_Checkout_Order();
            $bambora_order->billingaddress = $this->create_bambora_address( $order );
            $bambora_order->currency = $currency;
            $order_number = $this->clean_order_number($order->get_order_number());
            $bambora_order->ordernumber = ( $order_number);
            $bambora_order->shippingaddress = $this->create_bambora_address( $order );
            $order_total = Bambora_Online_Checkout_Helper::is_woocommerce_3() ? $order->get_total() : $order->order_total;
            $bambora_order->total = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $order_total, $minorunits, $this->roundingmode );

            if( $is_request_to_change_payment_method ) {
                $bambora_order->vatamount = 0;
            } else {
                $bambora_order->lines = $this->create_bambora_orderlines( $order, $minorunits );
                $order_total_tax = Bambora_Online_Checkout_Helper::is_woocommerce_3() ? $order->get_total_tax() : $order->get_total_tax;
                $bambora_order->vatamount = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $order_total_tax, $minorunits, $this->roundingmode );
            }

            return $bambora_order;
        }

        /**
         * Removes any special charactors from the order number
         *
         * @param string $order_number
         * @return string
         */
        protected function clean_order_number($order_number) {
            return preg_replace( '/[^a-z\d ]/i', "", $order_number );
        }

        /**
         * Create Bambora address
         *
         * @param WC_Order $order
         * @return Bambora_Online_Checkout_Address
         */
        protected function create_bambora_address( $order ) {
            $bambora_address = new Bambora_Online_Checkout_Address();
            $bambora_address->att = '';
            if( Bambora_Online_Checkout_Helper::is_woocommerce_3() ) {
                $bambora_address->city = $order->get_shipping_city();
                $bambora_address->country = $order->get_shipping_country();
                $bambora_address->firstname = $order->get_shipping_first_name();
                $bambora_address->lastname = $order->get_shipping_last_name();
                $bambora_address->street = $order->get_shipping_address_1();
                $bambora_address->zip = $order->get_shipping_postcode();
            } else {
                $bambora_address->city = $order->shipping_city;
                $bambora_address->country = $order->shipping_country;
                $bambora_address->firstname = $order->shipping_first_name;
                $bambora_address->lastname = $order->shipping_last_name;
                $bambora_address->street = $order->shipping_address_1;
                $bambora_address->zip = $order->shipping_postcode;
            }

            return $bambora_address;
        }

        /**
         * Create Bambora Url
         *
         * @param WC_Order $order
         * @return Bambora_Online_Checkout_Url
         */
        protected function create_bambora_url( $order ) {
            $bambora_url = new Bambora_Online_Checkout_Url();
            $order_id = Bambora_Online_Checkout_Helper::is_woocommerce_3() ? $order->get_id() : $order->id;
            $bambora_url->accept = Bambora_Online_Checkout_Helper::get_accept_url( $order );
            $bambora_url->decline = Bambora_Online_Checkout_Helper::get_decline_url( $order );
            $bambora_url->callbacks = array();
            $callback = new Bambora_Online_Checkout_Callback();
            $callback->url = apply_filters( 'bambora_online_checkout_callback_url', Bambora_Online_Checkout_Helper::get_bambora_online_checkout_callback_url( $order_id ) );

            $bambora_url->callbacks[] = $callback;
            $bambora_url->immediateredirecttoaccept = $this->immediateredirecttoaccept === 'yes' ? 1 : 0;

            return $bambora_url;
        }

        /**
         * Create Bambora subscription
         *
         * @return Bambora_Online_Checkout_Subscription
         */
        protected function create_bambora_subscription()
        {
            $bambora_subscription = new Bambora_Online_Checkout_Subscription();
            $bambora_subscription->action = 'create';
            $bambora_subscription->decription = "WooCommerce Subscription v.". WC_Subscriptions::$version;
            $bambora_subscription->reference = $this->merchant;

            return $bambora_subscription;
        }

        /**
         * Creates orderlines for an order
         *
         * @param WC_Order $order
         * @param int      $minorunits
         * @return Bambora_Online_Checkout_Orderline[]
         */
        protected function create_bambora_orderlines( $order, $minorunits ) {
            $bambora_orderlines = array();
            $items = $order->get_items();
            $line_number = 0;
            foreach ( $items as $item ) {
                $item_total = $order->get_line_total( $item, false, true );
                $item_total_incl_vat = $order->get_line_total( $item, true, true );
                $item_vat_amount = $order->get_line_tax( $item );

                $line = new Bambora_Online_Checkout_Orderline();
                $line->description = $item['name'];
                $line->id = $item['product_id'];
                $line->linenumber = ++$line_number;
                $line->quantity = $item['qty'];
                $line->text = $item['name'];
                $line->totalprice = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $item_total, $minorunits, $this->roundingmode );
                $line->totalpriceinclvat = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $item_total_incl_vat, $minorunits, $this->roundingmode );
                $line->totalpricevatamount = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $item_vat_amount, $minorunits, $this->roundingmode );
                $line->unit = __( 'pcs.', 'bambora-online-checkout' );
                $line->vat = $item_vat_amount > 0 ? ( $item_vat_amount / $item_total ) * 100 : 0;

                $bambora_orderlines[] = $line;
            }

            $shipping_methods = $order->get_shipping_methods();
            if ( $shipping_methods && count( $shipping_methods ) !== 0 ) {
                $shipping_total = Bambora_Online_Checkout_Helper::is_woocommerce_3() ? $order->get_shipping_total() : $order->get_total_shipping();
                $shipping_tax = (float)$order->get_shipping_tax();
                $shipping_method = reset( $shipping_methods );

                $shipping_orderline = new Bambora_Online_Checkout_Orderline();
                $shipping_orderline->id = $shipping_method->get_method_id();
                $shipping_orderline->description = $shipping_method->get_method_title();
                $shipping_orderline->quantity = 1;
                $shipping_orderline->text = $shipping_method->get_method_title();
                $shipping_orderline->unit = __( 'pcs.', 'bambora-online-checkout' );
                $shipping_orderline->linenumber = ++$line_number;
                $shipping_orderline->totalprice = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $shipping_total, $minorunits, $this->roundingmode );
                $shipping_orderline->totalpriceinclvat = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( ($shipping_total + $shipping_tax), $minorunits, $this->roundingmode );
                $shipping_orderline->totalpricevatamount = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $shipping_tax, $minorunits, $this->roundingmode );
                $shipping_orderline->vat = $shipping_tax > 0 ? ( $shipping_tax / $shipping_total ) * 100 : 0;

                $bambora_orderlines[] = $shipping_orderline;
            }

            return $bambora_orderlines;
        }

        /**
         * Handle for Bambora IPN Response
         **/
        public function bambora_online_checkout_callback() {
            $params = stripslashes_deep( $_GET );
            $message = '';
            $order = null;
            $response_code = 400;
            try {
                $isValidCall = Bambora_Online_Checkout_Helper::validate_bambora_online_checkout_callback_params( $params, $this->md5key, $order, $message );
                if( $isValidCall ) {
                    $api_key = $this->get_api_key();
                    $api = new Bambora_Online_Checkout_Api( $api_key );
                    $transaction_response = $api->get_transaction( $params['txnid'] );
                    if ( isset( $transaction_response ) && $transaction_response->meta->result ) {
                        $transaction = $transaction_response->transaction;
                        $message = $this->process_bambora_online_checkout_callback( $order, $transaction, $params );
                        $response_code = 200;
                    } else {
                        $message = 'Get Transaction failed on Callback Reason: ';
                        $message .= isset( $transaction_response ) ? $transaction_response->meta->message->merchant : 'No connection to bambora';
                        $this->_boc_log->add( $message );
                        $order->update_status( 'failed', $message );
                    }
                } else {
                    if( !empty( $order ) ) {
                        $order->update_status('failed', $message );
                    }
                    $this->_boc_log->separator();
                    $this->_boc_log->add( "Callback failed - {$message} - GET params:" );
                    $this->_boc_log->add( $params );
                    $this->_boc_log->separator();
                }
            }
            catch(Exception $ex) {
                $message = 'Callback failed Reason: ' . $ex->getMessage();
                $response_code = 500;
                $this->_boc_log->separator();
                $this->_boc_log->add( "Callback failed - {$message} - GET params:" );
                $this->_boc_log->add( $params );
                $this->_boc_log->separator();
            }

            $header = 'X-EPay-System: ' . Bambora_Online_Checkout_Helper::get_module_header_info();
            header( $header, true, $response_code );
            die( $message );
        }

        /**
         * Process the Bambora Callback
         *
         * @param WC_Order $order
         * @param mixed $bambora_transaction
         */
        protected function process_bambora_online_checkout_callback($order, $bambora_transaction, $params) {
            try {
                $type = '';
                $bambora_subscription_id = array_key_exists('subscriptionid', $params) ? $params['subscriptionid'] : null;
                if( ( Bambora_Online_Checkout_Helper::order_contains_subscription( $order ) || Bambora_Online_Checkout_Helper::order_is_subscription( $order ) ) && isset( $bambora_subscription_id ) ) {
                    $action = $this->process_subscription( $order, $bambora_transaction, $bambora_subscription_id );
                    $type = "Subscription {$action}";
                } else {
                    $action = $this->process_standard_payments( $order, $bambora_transaction );
                    $type = "Standard Payment {$action}";
                }
            }
            catch (Exception $e) {
                throw $e;
            }

            return  "Bambora Online Checkout Callback completed - {$type}";
        }

        protected function process_standard_payments( $order, $bambora_transaction ) {
            $action = '';
            $old_transaction_id = Bambora_Online_Checkout_Helper::get_bambora_online_checkout_transaction_id( $order );
            if( empty( $old_transaction_id ) ) {
                $this->add_surcharge_fee_to_order( $order, $bambora_transaction);
                $order->add_order_note( sprintf( __( 'Bambora Online Checkout Payment completed with transaction id %s', 'bambora-online-checkout' ), $bambora_transaction->id ) );
                $action = 'created';
            } else {
                $action = 'created (Called multiple times)';
            }
            $order->payment_complete( $bambora_transaction->id );
            return $action;
        }

        /**
         * Process the subscription
         *
         * @param WC_Order|WC_Subscription $order
         * @param mixed $bambora_transaction
         * @param string $bambora_subscription_id
         */
        protected function process_subscription( $order, $bambora_transaction, $bambora_subscription_id ) {
            $action = '';
            if( Bambora_Online_Checkout_Helper::order_is_subscription( $order ) ) {
                // Do not cancel subscription if the callback is called more than once !
                $old_bambora_subscription_id = Bambora_Online_Checkout_Helper::get_bambora_online_checkout_subscription_id( $order );
                if( $bambora_subscription_id != $old_bambora_subscription_id ) {
                    $this->subscription_cancellation( $order, true );
                    $action = 'changed';
                    $order->add_order_note( sprintf( __( 'Bambora Online Checkout Subscription changed from: %s to: %s', 'bambora-online-checkout' ), $old_bambora_subscription_id, $bambora_subscription_id ) );
                    $order->payment_complete();
                    $this->save_subscription_meta( $order, $bambora_subscription_id, true );
                } else {
                    $action = 'changed (Called multiple times)';
                }
            } else {
                // Do not add surcharge if the callback is called more than once!
                $old_transaction_id = Bambora_Online_Checkout_Helper::get_bambora_online_checkout_transaction_id( $order );
                if( $bambora_transaction->id != $old_transaction_id ) {
                    $this->add_surcharge_fee_to_order( $order, $bambora_transaction);
                    $action = 'activated';
                    $order->add_order_note( sprintf( __( 'Bambora Online Checkout Subscription activated with subscription id: %s', 'bambora-online-checkout' ), $bambora_subscription_id ) );
                    $order->payment_complete( $bambora_transaction->id );
                    $this->save_subscription_meta( $order, $bambora_subscription_id, false );
                } else {
                    $action = 'activated (Called multiple times)';
                }
            }

            return $action;
        }

        protected function add_surcharge_fee_to_order( $order, $bambora_transaction ) {
            $minorunits = $bambora_transaction->currency->minorunits;
            $fee_amount_in_minorunits = $bambora_transaction->total->feeamount;
            if ( $fee_amount_in_minorunits > 0 && $this->addsurchargetoshipment === 'yes' ) {
                $fee_amount = Bambora_Online_Checkout_Currency::convert_price_from_minorunits( $fee_amount_in_minorunits, $minorunits );
                $fee = (object) array(
                    'name'          => __( 'Surcharge Fee', 'bambora-online-checkout' ),
                    'amount'        => $fee_amount,
                    'taxable'       => false,
                    'tax_class'     => null,
                    'tax_data'      => array(),
                    'tax'           => 0
                    );
                if( !Bambora_Online_Checkout_Helper::is_woocommerce_3() ) {
                    $order->add_fee($fee);
                } else {
                    $fee_item = new WC_Order_Item_Fee();
                    $fee_item->set_props( array(
                        'name' => $fee->name,
                        'tax_class' => $fee->tax_class,
                        'total' => $fee->amount,
                        'total_tax' => $fee->tax,
                        'order_id' => $order->get_id()
                        )
                    );
                    $fee_item->save();
                    $order->add_item($fee_item);
                }

                $total_incl_fee = ( Bambora_Online_Checkout_Helper::is_woocommerce_3() ? $order->get_total() : $order->order_total ) + $fee_amount;
                $order->set_total( $total_incl_fee );
            }
        }

        /**
         * Store the Bambora Online Checkout subscription id on subscriptions in the order.
         *
         * @param WC_Order $order_id
         * @param string $bambora_subscription_id
         */
        protected function save_subscription_meta( $order, $bambora_subscription_id, $is_subscription ) {
            $bambora_subscription_id = wc_clean( $bambora_subscription_id );
            $order_id = Bambora_Online_Checkout_Helper::is_woocommerce_3() ? $order->get_id() : $order->id;
            if( $is_subscription ) {
                update_post_meta( $order_id, Bambora_Online_Checkout_Helper::BAMBORA_ONLINE_CHECKOUT_SUBSCRIPTION_ID, $bambora_subscription_id );
            } else {
                // Also store it on the subscriptions being purchased in the order
                $subscriptions = Bambora_Online_Checkout_Helper::get_subscriptions_for_order( $order_id );
                foreach ( $subscriptions as $subscription ) {
                    $wc_subscription_id = Bambora_Online_Checkout_Helper::is_woocommerce_3() ? $subscription->get_id() : $subscription->id;
                    update_post_meta( $wc_subscription_id, Bambora_Online_Checkout_Helper::BAMBORA_ONLINE_CHECKOUT_SUBSCRIPTION_ID, $bambora_subscription_id );
                    $subscription->add_order_note( sprintf( __( 'Bambora Online Checkout Subscription activated with subscription id: %s by order %s', 'bambora-online-checkout' ), $bambora_subscription_id, $order_id ) );
                }
            }
        }

        /**
         * Process Refund
         *
         * @param int $order_id
         * @param float|null $amount
         * @param string $reason
         * @return bool|WP_Error
         */
        public function process_refund( $order_id, $amount = null, $reason = '' ) {
            if ( ! isset( $amount ) ) {
                return true;
            }

            $refund_result = $this->bambora_online_checkout_refund_payment($order_id, $amount, '');
            if ( is_wp_error( $refund_result ) ) {
                return $refund_result;
            } else {
                $message = __( "The Refund action was a success for order {$order_id}", 'bambora-online-checkout' );
                Bambora_Online_Checkout_Helper::add_admin_notices(Bambora_Online_Checkout_Helper::SUCCESS, $message);
            }

            return true;
        }

        /**
         * Try and create refund lines. If there is a negativ amount on one of the refund items, it fails.
         *
         * @param WC_Order_Refund     $refund
         * @param Bambora_Online_Checkout_Orderline[] $bambora_refund_lines
         * @param int                 $minorunits
         * @param string              $reason
         * @return boolean
         */
        protected function create_bambora_refund_lines( $refund, &$bambora_refund_lines, $minorunits, $reason = '' ) {
            $line_number = 0;
            $total = $refund->get_total();
            $items_total = 0;

            $refund_items = $refund->get_items();
            foreach ( $refund_items as $item ) {
                $line_total_with_vat = $refund->get_line_total( $item, true, true );
                $line_total = $refund->get_line_total( $item, false, true);
                $line_vat = $refund->get_line_tax( $item );

                if ( 0 < $line_total ) {
                    throw new exception( __( 'Invalid refund amount for item', 'bambora-online-checkout' ) . ':' . $item['name'] );
                }
                $line = new Bambora_Online_Checkout_Orderline();
                $line->description = $item['name'];
                $line->id = $item['product_id'];
                $line->linenumber = ++$line_number;
                $line->quantity = abs( $item['qty'] );
                $line->text = $item['name'];
                $line->totalpriceinclvat = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( abs( $line_total_with_vat ), $minorunits, $this->roundingmode );
                $items_total += $line_total_with_vat;
                $line->unit = __( 'pcs.', 'bambora-online-checkout' );
                $line->vat = (float)($line_vat > 0 ? ($line_vat / $line_total) * 100 : 0);

                $bambora_refund_lines[] = $line;
            }

            $shipping_methods = $refund->get_shipping_methods();

            if ( $shipping_methods && count( $shipping_methods ) !== 0 ) {
                $shipping_total = Bambora_Online_Checkout_Helper::is_woocommerce_3() ? $refund->get_shipping_total() : $refund->get_total_shipping();
                $shipping_tax = $refund->get_shipping_tax();

                if ( 0 < $shipping_total || 0 < $shipping_tax ) {
                    throw new Exception( __( 'Invalid refund amount for shipping', 'bambora-online-checkout' ) );
                }

                $shipping_orderline = new Bambora_Online_Checkout_Orderline();
                $shipping_orderline->id = __( 'shipping', 'bambora-online-checkout' );
                $shipping_orderline->linenumber = ++$line_number;
                $shipping_orderline->description = __( 'Shipping', 'bambora-online-checkout' );
                $shipping_orderline->text = __( 'Shipping', 'bambora-online-checkout' );
                $shipping_orderline->quantity = 1;
                $shipping_orderline->unit = __( 'pcs.', 'bambora-online-checkout' );
                $shipping_orderline->totalpriceinclvat = abs( Bambora_Online_Checkout_Currency::convert_price_to_minorunits( ( $shipping_total + $shipping_tax ), $minorunits, $this->roundingmode ) );
                $shipping_orderline->vat = (float)($shipping_tax > 0 ? ($shipping_tax / $shipping_total) * 100 : 0);
                $bambora_refund_lines[] = $shipping_orderline;
                $items_total += $shipping_total + $shipping_tax;
            }

            if ( $items_total < $total ) {
                return false;
            } elseif ( $items_total > $total ) {
                $additional_refund_orderline = new Bambora_Online_Checkout_Orderline();
                $additional_refund_orderline->id = __( 'Refund', 'bambora-online-checkout' );
                $additional_refund_orderline->linenumber = ++$line_number;
                $additional_refund_orderline->description = __( 'Refund', 'bambora-online-checkout' ) . ($reason !== '' ? ': ' . $reason : '');
                $additional_refund_orderline->text = __( 'Refund', 'bambora-online-checkout' );
                $additional_refund_orderline->quantity = 1;
                $additional_refund_orderline->unit = __( 'pcs.', 'bambora-online-checkout' );
                $additional_refund_orderline->totalpriceinclvat = abs( Bambora_Online_Checkout_Currency::convert_price_to_minorunits( ( $total - $items_total ), $minorunits, $this->roundingmode ) );
                $additional_refund_orderline->vat = 0;
                $bambora_refund_lines[] = $additional_refund_orderline;
            }

            return true;
        }

        /**
         * Bambora Meta Boxes
         */
        public function bambora_online_checkout_meta_boxes() {
            global $post;
            $order_id = $post->ID;
            if( !$this->module_check( $order_id ) ) {
                return;
            }

            add_meta_box(
                'bambora-payment-actions',
                __( 'Bambora Online Checkout', 'bambora-online-checkout' ),
                array( &$this, 'bambora_online_checkout_meta_box_payment' ),
                'shop_order',
                'side',
                'high'
            );
        }

        /**
         * Generate the Bambora payment meta box and echos the HTML
         */
        public function bambora_online_checkout_meta_box_payment() {
            global $post;
            $order_id = $post->ID;
            $order = wc_get_order( $order_id );
            if ( !empty( $order ) ) {
                $transaction_id = Bambora_Online_Checkout_Helper::get_bambora_online_checkout_transaction_id( $order );

                if ( strlen( $transaction_id ) > 0 ) {
                    $html = '';
                    try {
                        $api_key = $this->get_api_key();
                        $api = new Bambora_Online_Checkout_Api( $api_key );

                        $transaction_response = $api->get_transaction( $transaction_id );

                        if ( ! isset( $transaction_response ) || ! $transaction_response->meta->result ) {
                            $get_transaction_error = isset( $transaction_response ) ? $transaction_response->meta->message->merchant : __( 'No connection to Bambora', 'bambora-online-checkout' );
                            $message = __( "Get transaction failed for order {$order_id} - {$get_transaction_error}", 'bambora-online-checkout' );
                            echo Bambora_Online_Checkout_Helper::message_to_html( Bambora_Online_Checkout_Helper::ERROR, $message );
                            $this->_boc_log->add( $message );
                            return null;
                        }

                        $transaction = $transaction_response->transaction;
                        $minorunits = $transaction->currency->minorunits;
                        $total_authorized = Bambora_Online_Checkout_Currency::convert_price_from_minorunits( $transaction->total->authorized, $minorunits );
                        $total_captured = Bambora_Online_Checkout_Currency::convert_price_from_minorunits( $transaction->total->captured, $minorunits );
                        $available_for_capture = Bambora_Online_Checkout_Currency::convert_price_from_minorunits( $transaction->available->capture, $minorunits );

                        $total_credited = Bambora_Online_Checkout_Currency::convert_price_from_minorunits( $transaction->total->credited, $minorunits );
                        $can_delete = $transaction->candelete;
                        $curency_code = $transaction->currency->code;
                        $card_group_id = $transaction->information->paymenttypes[0]->groupid;
                        $card_name = $transaction->information->paymenttypes[0]->displayname;

                        $html = '<div class="bambora_info">';
                        $html .= '<img class="bambora_paymenttype_img" src="https://d3r1pwhfz7unl9.cloudfront.net/paymentlogos/' . $card_group_id . '.svg" alt="' . $card_name . '" title="' . $card_name . '" />';
                        $html .= '<div class="bambora_transactionid">';
                        $html .= '<p>' . __( 'Transaction ID', 'bambora-online-checkout' ) . '</p>';
                        $html .= '<p>' . $transaction->id . '</p>';
                        $html .= '</div>';
                        $html .= '<div class="bambora_paymenttype">';
                        $html .= '<p>' . __( 'Payment Type', 'bambora-online-checkout' ) . '</p>';
                        $html .= '<p>' . $card_name . '</p>';
                        $html .= '</div>';

                        $html .= '<div class="bambora_info_overview">';
                        $html .= '<p>' . __( 'Authorized:', 'bambora-online-checkout' ) . '</p>';
                        $html .= '<p>' . wc_format_localized_price( $total_authorized ) . ' ' . $curency_code . '</p>';
                        $html .= '</div>';

                        $html .= '<div class="bambora_info_overview">';
                        $html .= '<p>' . __( 'Captured:', 'bambora-online-checkout' ) . '</p>';
                        $html .= '<p>' . wc_format_localized_price( $total_captured ) . ' ' . $curency_code . '</p>';
                        $html .= '</div>';

                        $html .= '<div class="bambora_info_overview">';
                        $html .= '<p>' . __( 'Refunded:', 'bambora-online-checkout' ) . '</p>';
                        $html .= '<p>' . wc_format_localized_price( $total_credited ) . ' ' . $curency_code . '</p>';
                        $html .= '</div>';

                        $html .= '</div>';

                        if ( $available_for_capture > 0 || $can_delete === true ) {
                            $html .= '<div class="bambora_action_container">';

                            if ( 0 < $available_for_capture ) {
                                $html .= '<input type="hidden" id="bambora_currency" name="bambora_currency" value="' . $curency_code . '">';
                                $html .= '<input type="hidden" id="bambora_capture_message" name="bambora_capture_message" value="' . __( 'Are you sure you want to capture the payment?', 'bambora-online-checkout' ) . '" />';
                                $html .= '<div class="bambora_action">';
                                $html .= '<p>' . $curency_code . '</p>';
                                $html .= '<input type="text" value="' .  $available_for_capture . '"id="bambora_capture_amount" class="bambora_amount" name="bambora_amount" />';
                                $html .= '<input id="bambora_capture_submit" class="button capture" name="bambora_capture" type="submit" value="' . __( 'Capture', 'bambora-online-checkout' ) . '" />';
                                $html .= '</div>';
                                $html .= '<br />';
                            }
                            if ( $can_delete === true ) {
                                $html .= '<input type="hidden" id="bambora_delete_message" name="bambora_delete_message" value="' . __( 'Are you sure you want to delete the payment?', 'bambora-online-checkout' ) . '" />';
                                $html .= '<div class="bambora_action">';
                                $html .= '<input id="bambora_delete_submit" class="button delete" name="bambora_delete" type="submit" value="' . __( 'Delete', 'bambora-online-checkout' ) . '" />';
                                $html .= '</div>';
                            }
                            $html .= '</div>';
                            $warning_message = __( 'The amount you entered was in the wrong format.', 'bambora-online-checkout' );

                            $html .= '<div id="bambora-format-error" class="bambora bambora_error"><strong>' . __( 'Warning', 'bambora-online-checkout' ) . ' </strong>' . $warning_message . '<br /><strong>' . __( 'Correct format is: 1234.56', 'bambora-online-checkout' ) . '</strong></div>';

                        }

                        $transaction_operations_response = $api->get_transaction_operations( $transaction_id );

                        if ( ! isset( $transaction_operations_response ) || ! $transaction_operations_response->meta->result ) {
                            $message = sprintf( __( 'Get transaction operations failed - %s', 'bambora-online-checkout' ), $transaction_operations_response->meta->message->merchant );
                            echo $html;
                            echo Bambora_Online_Checkout_Helper::message_to_html( Bambora_Online_Checkout_Helper::ERROR, $message );
                            $this->_boc_log->add( $message );
                            return null;
                        }

                        $transaction_operations = $transaction_operations_response->transactionoperations;

                        $html .= $this->build_transaction_log_table( $transaction_operations, $minorunits );
                        $html .= '<br />';

                        echo ent2ncr( $html );
                    }
                    catch (Exception $e) {
                        $message = $e->getMessage();
                        echo Bambora_Online_Checkout_Helper::message_to_html( Bambora_Online_Checkout_Helper::ERROR, $message );
                        $this->_boc_log->add( $message );
                        return null;
                    }
                } else {
                    $message = sprintf( __( 'No transaction was found for order %s', 'bambora-online-checkout' ), $order_id );
                    echo $message;
                    $this->_boc_log->add( $message );
                }
            } else {
                $message = sprintf( __( 'Could not load the order with order id %s', 'bambora-online-checkout'), $order_id );
                echo $message;
                $this->_boc_log->add( $message );
            }
        }

        /**
         * Build transaction log table HTML
         *
         * @param array $operations
         * @param int   $minorunits
         * @return string
         * */
        protected function build_transaction_log_table( $operations, $minorunits ) {
            $html = '<h4>' . __( 'TRANSACTION HISTORY', 'bambora-online-checkout' ) . '</h4>';
            $html .= '<table class="bambora-table">';
            $html .= $this->build_transaction_log_rows( $operations, $minorunits );
            $html .= '</table>';

            return $html;
        }

        /**
         * Build transaction log row HTML
         *
         * @param array $operations
         * @param int   $minorunits
         * @return string
         */
        protected function build_transaction_log_rows( $operations, $minorunits ) {
            $html = '';
            foreach ( $operations as $operation ) {
                $html .= '<tr class="bambora_transaction_row_header">';
                $html .= '<td>' . Bambora_Online_Checkout_Helper::format_date_time( $operation->createddate ) . '</td>';

                if ( array_key_exists( 'ecis', $operation ) && is_array( $operation->ecis ) && count( $operation->ecis ) > 0 ) {
                    $html .= '<td>ECI: ' . $operation->ecis[0]->value . '</td>';
                } else {
                    $html .= '<td>ECI: -</td>';
                }

                $html .= '</tr>';

                $html .= '<tr class="bambora_transaction">';
                $html .= '<td>' . Bambora_Online_Checkout_Helper::convert_action( $operation->action ) . '</td>';

                $amount = Bambora_Online_Checkout_Currency::convert_price_from_minorunits( $operation->amount, $minorunits );
                if ( $amount > 0 ) {
                    $html .= '<td>' . wc_format_localized_price( $amount ) . ' ' . $operation->currency->code . '</td>';
                } else {
                    $html .= '<td>-</td>';
                }

                $html .= '</tr>';

                if ( array_key_exists( 'transactionoperations', $operation ) && count( $operation->transactionoperations ) > 0 ) {
                    $html .= $this->build_transaction_log_rows( $operation->transactionoperations, $minorunits );
                }
            }

            return $html;
        }

        /**
         * Bambora Online Checkout Actions
         */
        public function bambora_online_checkout_actions() {
            if ( isset( $_GET['bambora_action'] ) ) {
                $params = $_GET;
                $order_id = $params['post'];
                $currency = $params['currency'];
                $amount = $params['amount'];
                $action = $params['bambora_action'];

                $action_result = null;
                try {
                    switch ( $action ) {
                        case 'capture':
                            $action_result = $this->bambora_online_checkout_capture_payment($order_id, $amount, $currency);
                            break;
                        case 'refund':
                            $action_result = $this->bambora_online_checkout_refund_payment($order_id, $amount, $currency);
                            break;
                        case 'delete':
                            $action_result = $this->bambora_online_checkout_delete_payment($order_id);
                            break;
                    }
                }
                catch ( Exception $ex ) {
                    $action_result = new WP_Error( 'bambora_online_checkout_error', $ex->getMessage() );
                }

                if( is_wp_error( $action_result ) ) {
                    $message = $action_result->get_error_message( 'bambora_online_checkout ' );
                    $this->_boc_log->add($message);
                    Bambora_Online_Checkout_Helper::add_admin_notices(Bambora_Online_Checkout_Helper::ERROR, $message);
                } else {
                    global $post;
                    $message = __( "The {$action} action was a success for order {$order_id }", 'bambora-online-checkout' );
                    Bambora_Online_Checkout_Helper::add_admin_notices(Bambora_Online_Checkout_Helper::SUCCESS, $message, true);
                    $url = admin_url( 'post.php?post=' . $post->ID . '&action=edit' );
                    wp_safe_redirect( $url );
                }
            }
        }

        /**
         * Capture a payment
         *
         * @param mixed $order_id
         * @param mixed $amount
         * @param mixed $currency
         * @return bool|WP_Error
         */
        public function bambora_online_checkout_capture_payment( $order_id, $amount, $currency ) {
            $order = wc_get_order( $order_id );
            if( empty( $currency ) ) {
                $currency = Bambora_Online_Checkout_Helper::is_woocommerce_3() ? $order->get_currency() : $order->get_order_currency;
            }
            $minorunits = Bambora_Online_Checkout_Currency::get_currency_minorunits( $currency );
            $amount = str_replace( ',', '.', $amount);
            $amount_in_minorunits = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $amount, $minorunits, $this->roundingmode );
            $transaction_id = Bambora_Online_Checkout_Helper::get_bambora_online_checkout_transaction_id( $order );

            $webservice = new Bambora_Online_Checkout_Api( $this->get_api_key() );
            $capture_response = $webservice->capture( $transaction_id, $amount_in_minorunits, $currency );

            if ( isset( $capture_response ) && $capture_response->meta->result ) {
                do_action( 'bambora_online_checkout_after_capture', $order_id );
                return true;
            } else {
                $messageReason = isset( $capture_response ) ? $capture_response->meta->message->merchant : __( 'No connection to Bambora', 'bambora-online-checkout' );
                $message = __( "Capture action failed for order {$order_id} - {$messageReason}", 'bambora-online-checkout' );
                $this->_boc_log->add( $message );
                return new WP_Error( 'bambora_online_checkout_error', $message);
            }
        }

        /**
         * Refund a payment
         *
         * @param mixed $order_id
         * @param mixed $amount
         * @param mixed $currency
         * @return bool|WP_Error
         */
        public function bambora_online_checkout_refund_payment( $order_id, $amount, $currency ) {
            $order = wc_get_order( $order_id );
            if( empty( $currency ) ) {
                $currency = Bambora_Online_Checkout_Helper::is_woocommerce_3() ? $order->get_currency() : $order->get_order_currency;
            }
            $minorunits = Bambora_Online_Checkout_Currency::get_currency_minorunits( $currency );
            $amount = str_replace( ',', '.', $amount);
            $amount_in_minorunits = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $amount, $minorunits, $this->roundingmode );
            $transaction_id = Bambora_Online_Checkout_Helper::get_bambora_online_checkout_transaction_id( $order );

            $refunds = $order->get_refunds();
            /** @var Bambora_Online_Checkout_Orderline[] */
            $bambora_refund_lines = array();
            if ( ! $this->create_bambora_refund_lines( $refunds[0], $bambora_refund_lines, $minorunits ) ) {
                $messageReason = __( 'Could not create refund invoice lines', 'bambora-online-checkout' );
                $message = __( "Refund action failed for order {$order_id} - {$messageReason}", 'bambora-online-checkout' );
                return new WP_Error( 'bambora_online_checkout_error', $message);
            }

            $webservice = new Bambora_Online_Checkout_Api( $this->get_api_key() );
            $credit_response = $webservice->credit( $transaction_id, $amount_in_minorunits, $currency, $bambora_refund_lines );

            if ( isset( $credit_response ) && $credit_response->meta->result ) {
                do_action( 'bambora_online_checkout_after_capture', $order_id );
                return true;
            } else {
                $messageReason = isset( $credit_response ) ? $credit_response->meta->message->merchant : __( 'No connection to Bambora', 'bambora-online-checkout' );
                $message = __( "Refund action failed for order {$order_id} - {$messageReason}", 'bambora-online-checkout' );
                $this->_boc_log->add( $message );
                return new WP_Error( 'bambora_online_checkout_error', $message);
            }
        }

        /**
         * Delete a payment
         *
         * @param mixed $order_id
         * @return bool|WP_Error
         */
        public function bambora_online_checkout_delete_payment( $order_id ) {
            $order = wc_get_order( $order_id );
            $transaction_id = Bambora_Online_Checkout_Helper::get_bambora_online_checkout_transaction_id( $order );
            $webservice = new Bambora_Online_Checkout_Api( $this->get_api_key() );

            $delete_response = $webservice->delete( $transaction_id );
            if ( isset( $delete_response ) && $delete_response->meta->result ) {
                do_action( 'bambora_online_checkout_after_delete', $order_id );
                return true;
            }
            else {
                $messageReason = isset( $delete_response ) ? $delete_response->meta->message->merchant : __( 'No connection to Bambora', 'bambora-online-checkout' );
                $message = __( "Delete action failed - {$messageReason}", 'bambora-online-checkout' );
                $this->_boc_log->add( $message );
                return new WP_Error( 'bambora_online_checkout_error', $message);
            }
        }

        /**
         * Get the Bambora Api Key
         */
        protected function get_api_key() {
            return Bambora_Online_Checkout_Helper::generate_api_key( $this->merchant, $this->accesstoken, $this->secrettoken );
        }

        public function module_check($order_id) {
            $payment_method = get_post_meta( $order_id, '_payment_method', true );
            return $this->id === $payment_method;
        }
    }

    // Load the module into WordPress / WooCommerce

    add_filter( 'woocommerce_payment_gateways', 'add_bambora_online_checkout' );
    Bambora_Online_Checkout::get_instance()->init_hooks();
    /**
     * Add the Bambora gateway to WooCommerce
     *
     * @param array $methods
     **/
    function add_bambora_online_checkout( $methods ) {
        $methods[] = 'bambora_online_checkout';
        return $methods;
    }

    $plugin_dir = basename( dirname( __FILE__ ) );
    load_plugin_textdomain( 'bambora-online-checkout', false, $plugin_dir . '/languages/' );
}
