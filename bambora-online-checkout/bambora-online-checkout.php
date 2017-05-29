<?php
/**
 * Plugin Name: Bambora Online Checkout
 * Plugin URI: http://www.bambora.com
 * Description: A payment gateway for WooCommerce
 * Version: 3.0.0
 * Author: Bambora
 * Author URI: http://www.bambora.com
 * Text Domain: Bambora
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

	define( 'BAMBORA_LIB', dirname( __FILE__ ) . '/lib/' );

	// Including Bambora files!
	include( BAMBORA_LIB . 'bambora-api.php' );
	include( BAMBORA_LIB . 'bambora-helper.php' );
	include( BAMBORA_LIB . 'bambora-currency.php' );

	/**
     * Bambora Online Checkout
     **/
	class Bambora_Online_Checkout extends WC_Payment_Gateway {

		const MODULE_VERSION = '3.0.0';
		const PSP_REFERENCE = 'Transaction ID';

        /**
         * Singleton instance
         * @var Bambora_Online_Checkout
         */
        private static $_instance;

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
			$this->icon = WP_PLUGIN_URL . '/' . plugin_basename( dirname( __FILE__ ) ) . '/bambora-logo.png';
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
                'multiple_subscriptions');

			// Load the form fields.!
			$this->init_form_fields();

			// Load the settings.!
			$this->init_settings();

			// Define user set variables!
			$this->enabled = array_key_exists( 'enabled', $this->settings ) ? $this->settings['enabled'] : 'yes';
			$this->title = array_key_exists( 'title', $this->settings ) ? $this->settings['title'] : 'Bambora Checkout';
			$this->description = array_key_exists( 'description', $this->settings ) ? $this->settings['description'] : 'Pay using Bambora Checkout';
			$this->merchant = array_key_exists( 'merchant', $this->settings ) ? $this->settings['merchant'] : '';
			$this->accesstoken = array_key_exists( 'accesstoken', $this->settings ) ? $this->settings['accesstoken'] : '';
			$this->secrettoken = array_key_exists( 'secrettoken', $this->settings ) ? $this->settings['secrettoken'] : '';
			$this->paymentwindowid = array_key_exists( 'paymentwindowid', $this->settings ) ? $this->settings['paymentwindowid'] : 1;
			$this->windowstate = array_key_exists( 'windowstate', $this->settings ) ? $this->settings['windowstate'] : 2;
			$this->instantcapture = array_key_exists( 'instantcapture', $this->settings ) ? $this->settings['instantcapture'] :  'no';
			$this->immediateredirecttoaccept = array_key_exists( 'immediateredirecttoaccept', $this->settings ) ? $this->settings['immediateredirecttoaccept'] :  'no';
			$this->addsurchargetoshipment = array_key_exists( 'addsurchargetoshipment', $this->settings ) ? $this->settings['addsurchargetoshipment'] :  'no';
			$this->md5key = array_key_exists( 'md5key', $this->settings ) ? $this->settings['md5key'] : '';

			// Set description for checkout page!
			$this->set_bambora_description_for_checkout();
		}

        /**
         * Initilize module hooks
         */
        public function init_hooks()
        {
            // Actions!
            //add_action( 'init', array( &$this, 'check_callback' ) );
			add_action( 'valid_bambora_callback', array( $this, 'successful_request' ) );
			add_action( 'woocommerce_api_' . strtolower( get_class() ), array( $this, 'check_callback' ) );
            add_action( 'woocommerce_receipt_bambora', array( $this, 'receipt_page' ) );

            if(is_admin()) {
                add_action( 'add_meta_boxes', array( $this, 'bambora_meta_boxes' ) );
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
                add_action( 'wp_before_admin_bar_render', array( $this, 'bambora_action' ) );
            }

            //Subscriptions
            add_action('woocommerce_scheduled_subscription_payment_' . $this->id, array($this, 'scheduled_subscription_payment'), 10, 2);
            add_action('woocommerce_subscription_cancelled_' . $this->id, array($this, 'subscription_cancellation'));

			// Register styles!
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_wc_bambora_admin_styles_and_scripts' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_wc_bambora_front_styles' ) );
        }


		/**
         * Enqueue Admin Styles and Scripts
         */
		public function enqueue_wc_bambora_admin_styles_and_scripts() {
			wp_register_style( 'bambora_admin_style', plugins_url( 'bambora-online-checkout/style/bambora-admin.css' ) );
			wp_enqueue_style( 'bambora_admin_style' );

			// Fix for load of Jquery time!
			wp_enqueue_script( 'jquery' );

			wp_enqueue_script( 'bambora_script', WP_PLUGIN_URL . '/' . plugin_basename( dirname( __FILE__ ) ) . '/js/bambora.js' );
		}

		/**
         * Enqueue Frontend Styles and Scripts
         */
		public function enqueue_wc_bambora_front_styles() {
			wp_enqueue_style( 'bambora_front_style', plugins_url( 'bambora-online-checkout/style/bambora-front.css' ) );
			wp_enqueue_style( 'bambora_front_style' );
		}

		/**
         * Initialise Gateway Settings Form Fields
         */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled' => array(
								'title' => 'Activate module',
								'type' => 'checkbox',
								'label' => 'Set to Yes to allow your customers to use Bambora Checkout as a payment option.',
								'default' => 'yes',
							),
				'title' => array(
								'title' => 'Title',
								'type' => 'text',
								'description' => 'The title of the payment method displayed to the customers.',
								'default' => 'Bambora Checkout',
							),
				'description' => array(
								'title' => 'Description',
								'type' => 'textarea',
								'description' => 'The description of the payment method displayed to the customers.',
								'default' => 'Pay using Bambora Checkout',
							),
				'merchant' => array(
								'title' => 'Merchant number',
								'type' => 'text',
								'description' => 'The number identifying your Bambora merchant account.',
								'default' => '',
							),
				'accesstoken' => array(
								'title' => 'Access token',
								'type' => 'text',
								'description' => 'The Access token for the API user received from the Bambora administration.',
								'default' => '',
							),
				'secrettoken' => array(
								'title' => 'Secret token',
								'type' => 'password',
								'description' => 'The Secret token for the API user received from the Bambora administration.',
								'default' => '',
							),
				'md5key' => array(
								'title' => 'MD5 Key',
								'type' => 'text',
								'description' => 'The MD5 key is used to stamp data sent between Magento and Bambora to prevent it from being tampered with. The MD5 key is optional but if used here, must be the same as in the Bambora administration.',
								'default' => '',
							),
				'paymentwindowid' => array(
								'title' => 'Payment Window ID',
								'type' => 'text',
								'description' => 'The ID of the payment window to use.',
								'default' => '1',
							),
				'windowstate' => array(
								'title' => 'Window state',
								'type' => 'select',
								'description' => 'Please select if you want the Payment window shown as an overlay or as full screen.',
								'options' => array( 2 => 'Overlay',1 => 'Full screen' ),
								'label' => 'Window state',
								'default' => 2,
							),
				'instantcapture' => array(
								'title' => 'Instant capture',
								'type' => 'checkbox',
								'description' => 'Capture the payments at the same time they are authorized. In some countries, this is only permitted if the consumer receives the products right away Ex. digital products.',
								'label' => 'Enable Instant Capture',
								'default' => 'no',
							),
				'immediateredirecttoaccept' => array(
								'title' => 'Immediate Redirect',
								'type' => 'checkbox',
								'description' => 'Immediately redirect your customer back to you shop after the payment completed.',
								'label' => 'Enable Immediate redirect',
								'default' => 'no',
							),
				'addsurchargetoshipment' => array(
								'title' => 'Add Surcharge',
								'type' => 'checkbox',
								'description' => 'Display surcharge amount on the order as an item',
								'label' => 'Enable Surcharge',
								'default' => 'no',
							),
				);
		}

		/**
         * Admin Panel Options
         * - Options for bits like 'title' and availability on a country-by-country basis
         */
		public function admin_options() {
			$plugin_data = get_plugin_data( __FILE__, false, false );
			$version = $plugin_data['Version'];

			$html = "<h3>Bambora Online Checkout v{$version}</h3>";
			$html .= '<a href="http://dev.bambora.com/shopping-carts/guides/shopping-carts/woocommerce" target="_blank">' . __( 'Documentation can be found here', 'bambora-online-checkout' ) . '</a>';
			$html .= '<table class="form-table">';

			// Generate the HTML For the settings form.!
			$html .= $this->generate_settings_html( array(), false );
			$html .= '</table>';

			echo ent2ncr( $html );
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

        private function getSubscription($order)
        {
            if(!function_exists('wcs_get_subscriptions_for_renewal_order'))
            {
                return null;
            }
            $subscriptions = wcs_get_subscriptions_for_renewal_order($order);

            return end($subscriptions);
        }

        function scheduled_subscription_payment($amount_to_charge, $renewal_order)
        {
            try {
                $subscription = $this->getSubscription($renewal_order);
                if(isset($subscription)) {
                    $parent_order = $subscription->order;
                    $parent_order_id = $this->is_woocommerce_3() ? $parent_order->get_id() : $parent_order->id;
                    $bambora_subscription_id = get_post_meta($parent_order_id, 'Subscription ID', true);
                    $order_currency = $this->is_woocommerce_3() ? $renewal_order->get_currency() : $renewal_order->get_order_currency();

                    $minor_units = Bambora_Currency::get_currency_minorunits( $order_currency );
                    $amount = Bambora_Currency::convert_price_to_minorunits( $amount_to_charge, $minor_units );

                    $renewal_order_id = $this->is_woocommerce_3() ? $renewal_order->get_id() : $renewal_order->id;
                    $api_key = $this->get_api_key();
                    $api = new Bambora_Api( $api_key );
                    $authorize_response = $api->authorize_subscription( $bambora_subscription_id, $amount, $order_currency, $renewal_order_id );

                    if($authorize_response['meta']['result']) {
                        update_post_meta($renewal_order_id,'Transaction ID', $authorize_response['transactionid']);
                        $renewal_order->payment_complete();
                    }else {
                        $orderNote = __('Subscription could not be authorized', 'woocommerce-gateway-epay-dk');
                        $orderNote .= " - {$authorize_response['meta']['message']['merchant']}";
                        $renewal_order->update_status('failed', $orderNote);
                        $subscription->add_order_note($orderNote . ' ID: ' . $renewal_order_id);
                    }
                }else {
                    $renewal_order->update_status('failed', __('No subscription found', 'woocommerce-gateway-epay-dk'));
                }

            }
            catch(Exception $ex)
            {
                $renewal_order->update_status('failed', $ex->getMessage());
                error_log($ex->getMessage());
            }
        }

        public function subscription_cancellation($subscription)
        {
            try {
                if(function_exists('wcs_is_subscription') && wcs_is_subscription($subscription)) {
                    $parent_order = $subscription->order;
                    $parent_order_id = $this->is_woocommerce_3() ? $parent_order->get_id() : $parent_order->id;
                    $bamboraSubscriptionId = get_post_meta($parent_order_id, 'Subscription ID', true);
                    if(empty($bamboraSubscriptionId)) {
                        $orderNote = __('Bambora Subscription ID was not found', 'woocommerce-gateway-epay-dk');
                        $subscription->add_order_note($orderNote);
                        throw new Exception($orderNote);
                    }

                    $api_key = $this->get_api_key();
                    $api = new Bambora_Api( $api_key );

                    $delete_response = $api->delete_subscription( $bamboraSubscriptionId );

                    if($delete_response['meta']['result']) {
                        $subscription->add_order_note(__('Subscription successfully Canceled.', 'woocommerce-gateway-epay-dk'));
                    }else {
                        $orderNote = __('Subscription could not be canceled', 'woocommerce-gateway-epay-dk');
                        $orderNote = " - {$delete_response['meta']['message']['merchant']}";

                        $subscription->add_order_note($orderNote);
                        throw new Exception($orderNote);
                    }
                }
            }
            catch(Exception $ex)
            {
                error_log($ex->getMessage());
            }
        }

        /**
         * Checks if Woocommerce Subscriptions is enabled or not
         */
        private function woocommerce_subscription_plugin_is_active()
        {
            return class_exists('WC_Subscriptions') && WC_Subscriptions::$name = 'subscription';
        }

		/**
         * Get Bambora payment window
         *
         * @param int $order_id
         * @return string
         * */
		public function get_checkout_payment_window( $order_id ) {
			$api_key = $this->get_api_key();
			$api = new Bambora_Api( $api_key );

			$checkout_request = $this->create_checkout_request( $order_id );
			$checkout_response = $api->get_checkout_response( $checkout_request );

			if ( ! isset( $checkout_response ) || ! $checkout_response['meta']['result'] ) {
				$error_message = isset( $checkout_response ) ? $checkout_response['meta']['message']['enduser'] : __( 'No connection to Bambora' );
				$message = __( 'Could not retrive the payment window. Reason:', 'bambora-online-checkout' ) . ' ' . $error_message;
				return $this->message_to_html( 'error', $message );
			}

			$checkout_payment_window_js = $api->get_checkout_payment_window_js();
			$bambora_checkout_url = $checkout_response['url'];
			$bambora_checkout_payment_html = Bambora_Helper::create_bambora_checkout_payment_html( $checkout_payment_window_js, $this->windowstate, $bambora_checkout_url, $checkout_request->url->decline );

			return $bambora_checkout_payment_html;
		}

		/**
         * Create Checkout Request
         *
         * @param int $order_id
         * @return Bambora_Checkout_Request
         */
		private function create_checkout_request( $order_id ) {
			$order = wc_get_order( $order_id);
			$minorunits = Bambora_Currency::get_currency_minorunits( get_woocommerce_currency() );

			$bambora_custommer = $this->create_bambora_custommer( $order );
			$bambora_order = $this->create_bambora_order( $order, $minorunits );
			$bambora_url = $this->create_bambora_url( $order );

			$request = new Bambora_Checkout_Request();
			$request->customer = $bambora_custommer;
			$request->instantcaptureamount = 'yes' === $this->instantcapture ? $bambora_order->total : 0;
			$request->language = str_replace( '_', '-', get_locale() );
			$request->order = $bambora_order;
            $request->paymentwindowid = $this->paymentwindowid;
			$request->url = $bambora_url;

            if($this->woocommerce_subscription_plugin_is_active() && wcs_order_contains_subscription($order))
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
         * @return Bambora_Customer
         * */
		private function create_bambora_custommer( $order ) {
			$bambora_custommer = new Bambora_Customer();
            if($this->is_woocommerce_3())
            {
                $bambora_custommer->email = $order->get_billing_email();
                $bambora_custommer->phonenumber = $order->get_billing_phone();
                $bambora_custommer->phonenumbercountrycode = $order->get_billing_country();
            } else {
                $bambora_custommer->email = $order->billing_email;
                $bambora_custommer->phonenumber = $order->billing_phone;
                $bambora_custommer->phonenumbercountrycode = $order->billing_country;
            }

			return $bambora_custommer;
		}

		/**
         * Create Bambora order
         *
         * @param WC_Order $order
         * @param int      $minorunits
         * @return Bambora_Order
         * */
		private function create_bambora_order( $order, $minorunits ) {
			$bambora_order = new Bambora_Order();
			$bambora_order->billingaddress = $this->create_bambora_address( $order );
			$bambora_order->currency = get_woocommerce_currency();
			$bambora_order->lines = $this->create_bambora_orderlines( $order, $minorunits );

			$order_number = str_replace( _x( '#', 'hash before order number', 'woocommerce' ), '', $order->get_order_number() );
			$bambora_order->ordernumber = ( (int) $order_number);
			$bambora_order->shippingaddress = $this->create_bambora_address( $order );
            $order_total = $this->is_woocommerce_3() ? $order->get_total() : (float) $order->order_total;
			$bambora_order->total = Bambora_Currency::convert_price_to_minorunits( $order_total, $minorunits );
			$bambora_order->vatamount = Bambora_Currency::convert_price_to_minorunits( round($order->get_total_tax(), 2), $minorunits );

			return $bambora_order;
		}

		/**
         * Create Bambora address
         *
         * @param WC_Order $order
         * @return Bambora_Address
         * */
		private function create_bambora_address( $order ) {
			$bambora_address = new Bambora_Address();
			$bambora_address->att = '';
            if($this->is_woocommerce_3())
            {
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
         * @return Bambora_Url
         */
		private function create_bambora_url( $order ) {
			$bambora_url = new Bambora_Url();
			$bambora_url->accept = $this->fix_url( $this->get_return_url( $order ) );
			$bambora_url->decline = $this->fix_url( $order->get_cancel_order_url() );

            $order_id = $this->is_woocommerce_3() ? $order->get_id(): $order->id;
			$bambora_url->callbacks = array();
			$callback = new Bambora_Callback();
			$callback->url = $this->fix_url( add_query_arg( 'wooorderid', $order_id, add_query_arg( 'wc-api', 'Bambora_Online_Checkout', $this->get_return_url( $order ) ) ) );

			$bambora_url->callbacks[] = $callback;
			$bambora_url->immediateredirecttoaccept = 'yes' === $this->immediateredirecttoaccept ? 1 : 0;

            return $bambora_url;
		}

        /**
         * Create Bambora subscription
         *
         * @return Bambora_Subscription
         */
        private function create_bambora_subscription()
        {
            $bambora_subscription = new Bambora_Subscription();
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
         * @return Bambora_Orderline[]
         */
		private function create_bambora_orderlines( $order, $minorunits ) {
			$bambora_orderlines = array();

			$wc_tax = new WC_Tax();

			$items = $order->get_items();
			$line_number = 0;
			foreach ( $items as $item ) {
				$line = new Bambora_Orderline();
				$line->description = $item['name'];
				$line->id = $item['product_id'];
				$line->linenumber = ++$line_number;
				$line->quantity = $item['qty'];
				$line->text = $item['name'];
				$line->totalprice = Bambora_Currency::convert_price_to_minorunits( $order->get_line_total( $item, false, true ), $minorunits, false );
				$line->totalpriceinclvat = Bambora_Currency::convert_price_to_minorunits( $order->get_line_total( $item, true, true ), $minorunits, false );
				$line->totalpricevatamount = Bambora_Currency::convert_price_to_minorunits( $order->get_line_tax( $item ), $minorunits, false );
				$line->unit = __( 'pcs.', 'bambora-online-checkout' );

				$product = $order->get_product_from_item( $item );
				$item_tax_class = $product->get_tax_class();
				$item_tax_rate_array = $wc_tax->get_rates( $item_tax_class );
				$item_tax_rate = array_shift( $item_tax_rate_array );
				if ( isset( $item_tax_rate['rate'] ) ) {
					$line->vat = $item_tax_rate['rate'];
				} else {
					$line->vat = 0;
				}

				$bambora_orderlines[] = $line;
			}

			$shipping_methods = $order->get_shipping_methods();
			if ( $shipping_methods && count( $shipping_methods ) !== 0 ) {
				$shipping_total = $this->is_woocommerce_3() ? $order->get_shipping_total() : $order->get_total_shipping();
				$shipping_tax = $order->get_shipping_tax();
				$shipping_orderline = new Bambora_Orderline();
				$shipping_orderline->id = __( 'Shipping', 'bambora-online-checkout' );
				$shipping_orderline->description = __( 'Shipping', 'bambora-online-checkout' );
				$shipping_orderline->quantity = 1;
				$shipping_orderline->text = __( 'Shipping', 'bambora-online-checkout' );
				$shipping_orderline->unit = __( 'pcs.', 'bambora-online-checkout' );
				$shipping_orderline->linenumber = ++$line_number;
				$shipping_orderline->totalprice = Bambora_Currency::convert_price_to_minorunits( $shipping_total, $minorunits );
				$shipping_orderline->totalpriceinclvat = Bambora_Currency::convert_price_to_minorunits( $shipping_total + $shipping_tax, $minorunits );
				$shipping_orderline->totalpricevatamount = Bambora_Currency::convert_price_to_minorunits( $shipping_tax, $minorunits );

				$shipping_orderline->vat = $shipping_total > 0 ? round( $shipping_tax / $shipping_total * 100 ) : 0;
				$bambora_orderlines[] = $shipping_orderline;
			}

			return $bambora_orderlines;
		}

		/**
         * Fix Url
         *
         * @param string $url
         * @return string
         * */
		public function fix_url( $url ) {
			$url = str_replace( '&#038;', '&amp;', $url );
			$url = str_replace( '&amp;', '&', $url );

			return $url;
		}

		/**
         * Set the WC Payment Gateway description for the checkout page
         */
		public function set_bambora_description_for_checkout() {
			global $woocommerce;

			$cart = $this->is_woocommerce_3() ? WC()->cart : $woocommerce->cart;
			if ( ! $cart ) {
				return;
			}

			$cart_total = $cart->total;
			if ( $cart_total && $cart_total > 0 ) {
				$currency = get_woocommerce_currency();
				if ( ! $currency ) {
					return;
				}
				$minorunits = Bambora_Currency::get_currency_minorunits( $currency );
				$amount = Bambora_Currency::convert_price_to_minorunits( $cart_total, $minorunits );
				$api = new Bambora_Api( Bambora_Helper::generate_api_key( $this->merchant, $this->accesstoken, $this->secrettoken ) );
				$payment_type_ids = $api->get_avaliable_payment_type_ids( $currency, $amount );
				foreach ( $payment_type_ids as $id ) {
					$this->description .= '<img src="https://d3r1pwhfz7unl9.cloudfront.net/paymentlogos/' . $id . '.png" width="45" />';
				}
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

			return array(
				'result'     => 'success',
				'redirect'    => $order->get_checkout_payment_url( true ),
			);
		}

		/**
         * Receipt page
         *
         * @param WC_Order $order
         **/
		public function receipt_page( $order ) {
			$payment_window_html = $this->get_checkout_payment_window( $order );
			echo $payment_window_html;
		}

		/**
         * Check for Bambora IPN Response
         **/
		public function check_callback() {
			$params = stripslashes_deep( $_GET );
			do_action( 'valid_bambora_callback', $params );
		}

		/**
         * Successful Payment!
         *
         * @param array $params
         **/
		public function successful_request( $params ) {
			$message = '';
			$response_code = 400;
			$order = null;
			$transaction = null;
			if ( $this->validate_callback( $params, $message, $order, $transaction ) ) {
				$message = $this->process_callback( $params, $order, $transaction, $response_code );
			} else {
				$message = empty( $message ) ? __( 'Unknown error', 'bambora-online-checkout' ) : $message;
				$woo_order_id = array_key_exists( 'wooorderid', $params ) ? $params['wooorderid'] : 'Unknown';
				$message = "WooCommerce-OrderId: {$woo_order_id} " . __( 'Callback failed! Reason:', 'bambora-online-checkout' ) . ' ' . $message;
				if ( isset( $order ) ) {
					$order->add_order_note( $message );
				}

				error_log( $message );
			}

			$header = 'X-EPay-System: ' . Bambora_Helper::get_module_header_info();
			header( $header, true, $response_code );
			die( $message );
		}

		/**
         * Validate Callback
         *
         * @param mixed    $params
         * @param string   $message
         * @param WC_Order $order
         * @param mixed    $transaction
         * @return bool
         */
		private function validate_callback( $params, &$message, &$order, &$transaction ) {
            // Validate woocommerce order!
            if(empty( $params['wooorderid'] ))
            {
                $message = "No wooorderid was supplied to the system!";
				return false;
            }
			$order = wc_get_order( $params['wooorderid'] );
			if ( ! isset( $order ) ) {
				$message = "Could not find order with wooorderid {$params["wooorderid"]}";
				return false;
			}

            // Check exists transactionid!
			if ( ! isset( $params ) || empty( $params['txnid'] ) ) {
				$message = isset( $params ) ? 'No GET(txnid) was supplied to the system!' : 'Response is null';
				return false;
			}

			// Check exists orderid!
			if ( empty( $params['orderid'] ) ) {
				$message = 'No GET(orderid) was supplied to the system!';
				return false;
			}

			// Validate MD5!
			$var = '';
			if ( strlen( $this->md5key ) > 0 ) {
				foreach ( $params as $key => $value ) {
					if ( 'hash' !== $key ) {
						$var .= $value;
					}
				}

				$genstamp = md5( $var . $this->md5key );
				if ( ! hash_equals( $genstamp, $params['hash'] ) ) {
					$message = 'Hash validation failed - Please check your MD5 key';
					return false;
				}
			}

			// Validate bambora transaction!
			$api_key = $this->get_api_key();
			$api = new Bambora_Api( $api_key );
			$get_transaction = $api->get_transaction( $params['txnid'] );
			if ( ! isset( $get_transaction ) || ! $get_transaction['meta']['result'] ) {
				$message = "Get Transaction - ";
                $message .= isset( $get_transaction ) ? $get_transaction['meta']['message']['merchant'] : 'No connection to Bambora';
				return false;
			}
			$transaction = $get_transaction['transaction'];

			return true;
		}

		/**
         * Process Callback
         *
         * @param int      $woo_order_id
         * @param WC_Order $order
         * @param mixed    $transaction
         * @param int      $response_code
         * @return string
         */
		private function process_callback( $params, $order, $transaction, &$response_code ) {
			$message = '';
			try {
                $woo_order_id = $params['wooorderid'];
				$psp_reference = get_post_meta( $woo_order_id, $this::PSP_REFERENCE );
				if ( empty( $psp_reference ) ) {
					// Payment completed!
					$minorunits = $transaction['currency']['minorunits'];
					$fee_amount_in_minorunits = $transaction['total']['feeamount'];

					if ( 0 < $fee_amount_in_minorunits && 'yes' === $this->settings['addsurchargetoshipment'] ) {
						$fee_amount = Bambora_Currency::convert_price_from_minorunits( $fee_amount_in_minorunits, $minorunits );
                        if($this->is_woocommerce_3())
                        {
                            $order_fee = new WC_Order_Item_Fee();
                            $order_fee->set_total( $fee_amount );
                            $order_fee->set_tax_status('none');
                            $order_fee->set_total_tax(0);
                            $order_fee->save();

                            $order->add_item($order_fee);
                            $order->calculate_totals();

                        } else {
						    $order_fee              = new stdClass();
						    $order_fee->id          = 'bambora_surcharge_fee';
						    $order_fee->name        = __( 'Surcharge Fee', 'bambora-online-checkout' );
						    $order_fee->amount      = $fee_amount;
						    $order_fee->taxable     = false;
						    $order_fee->tax         = 0;
						    $order_fee->tax_data    = array();

						    $order->add_fee( $order_fee );
						    $order_total = ($this->is_woocommerce_3() ? $order->get_total() : $order->order_total) + $fee_amount;
						    $order->set_total( $order_total );
                        }
					}

					$order->payment_complete();

					$transaction_id = $transaction['id'];
					$card_number = $transaction['information']['primaryaccountnumbers'][0]['number'];
					$card_type = $transaction['information']['paymenttypes'][0]['displayname'];

					update_post_meta( $woo_order_id, $this::PSP_REFERENCE, $transaction_id );
					update_post_meta( $woo_order_id, 'Card no', $card_number );
					update_post_meta( $woo_order_id, 'Card type', $card_type );

                    //Subscription
                    $subscriptionId = array_key_exists('subscriptionid', $params) ? $params['subscriptionid'] : null;
                    if($this->woocommerce_subscription_plugin_is_active() && isset( $subscriptionId ))
                    {
                        WC_Subscriptions_Manager::activate_subscriptions_for_order($order);
                        update_post_meta($woo_order_id, 'Subscription ID', $subscriptionId);
                        $order->add_order_note( __( 'Subscription activated', 'bambora-online-checkout' ) );
                    }

                    $order->add_order_note( __( 'Callback completed', 'bambora-online-checkout' ) );
					$message = 'Callback completed - Order created';
					$response_code = 200;
				} else {
					$message = 'Callback completed - Order was already Created';
					$response_code = 200;
				}
			}
            catch (Exception $e) {
				$response_code = 500;
				$message = 'Action Failed: ' . $e->getMessage();
			}

			return $message;
		}

		/**
         * Process Refund
         *
         * @param int        $order_id
         * @param float|null $amount
         * @param string     $reason
         * @return bool
         */
		public function process_refund( $order_id, $amount = null, $reason = '' ) {
			$order = wc_get_order( $order_id );
			$refunds = $order->get_refunds();
			$currency = $this->is_woocommerce_3() ? $order->get_currency() : $order->order_currency;
			$minorunits = Bambora_Currency::get_currency_minorunits( $currency );
			$amount = Bambora_Currency::convert_price_to_minorunits( $amount, $minorunits );

			/** @var Bambora_Orderline[] */
			$bambora_refund_lines = array();
			if ( ! $this->create_bambora_refund_lines( $refunds[0], $bambora_refund_lines, $minorunits ) ) {
				echo $this->message_to_html( 'error', __( 'Could not create refund invoice lines', 'bambora-online-checkout' ) );
				return false;
			}

			$api_key = $this->get_api_key();
			$api = new Bambora_Api( $api_key );
			$transaction_id = get_post_meta( $order_id, $this::PSP_REFERENCE, true );
			$credit = $api->credit( $transaction_id, $amount, $currency, $bambora_refund_lines );

			if ( ! isset( $credit ) || ! $credit['meta']['result'] ) {
				$message = isset( $credit ) ? $credit['meta']['message']['merchant'] : __( 'No connection to Bambora', 'bambora-online-checkout' );
				echo $this->message_to_html( 'error', $message );
				return false;
			}

			return true;
		}

		/**
         * Try and create refund lines. If there is a negativ amount on one of the refund items, it fails.
         *
         * @param WC_Order_Refund     $refund
         * @param Bambora_Orderline[] $bambora_refund_lines
         * @param int                 $minorunits
         * @param string              $reason
         * @return boolean
         */
		private function create_bambora_refund_lines( $refund, &$bambora_refund_lines, $minorunits, $reason = '' ) {
			$wc_tax = new WC_Tax();
			$line_number = 0;
			$total = $refund->get_total();
			$items_total = 0;

			$refund_items = $refund->get_items();
			foreach ( $refund_items as $item ) {
				$line_total = $refund->get_line_total( $item, true, true );
				if ( 0 < $line_total ) {
					throw new exception( __( 'Invalid refund amount for item', 'bambora-online-checkout' ) . ':' . $item['name'] );
				}
				$line = new Bambora_Orderline();
				$line->description = $item['name'];
				$line->id = $item['product_id'];
				$line->linenumber = ++$line_number;
				$line->quantity = abs( $item['qty'] );
				$line->text = $item['name'];
				$line->totalpriceinclvat = Bambora_Currency::convert_price_to_minorunits( abs( $line_total ), $minorunits, false );
				$items_total += $line_total;
				$line->unit = __( 'pcs.', 'bambora-online-checkout' );
				$product = $refund->get_product_from_item( $item );
				$item_tax_class = $product->get_tax_class();
				$item_tax_rate_array = $wc_tax->get_rates( $item_tax_class );
				$item_tax_rate = array_shift( $item_tax_rate_array );
				if ( isset( $item_tax_rate['rate'] ) ) {
					$line->vat = $item_tax_rate['rate'];
				} else {
					$line->vat = 0;
				}
				$bambora_refund_lines[] = $line;
			}

			$shipping_methods = $refund->get_shipping_methods();

			if ( $shipping_methods && 0 !== count( $shipping_methods ) ) {
				$shipping_total = $this->is_woocommerce_3() ? $refund->get_shipping_total() : $refund->get_total_shipping();
				$shipping_tax = $refund->get_shipping_tax();

				if ( 0 < $shipping_total || 0 < $shipping_tax ) {
					throw new Exception( __( 'Invalid refund amount for shipping', 'bambora-online-checkout' ) );
				}

				$shipping_orderline = new Bambora_Orderline();
				$shipping_orderline->id = __( 'shipping', 'bambora-online-checkout' );
				$shipping_orderline->linenumber = ++$line_number;
				$shipping_orderline->description = __( 'Shipping', 'bambora-online-checkout' );
				$shipping_orderline->text = __( 'Shipping', 'bambora-online-checkout' );
				$shipping_orderline->quantity = 1;
				$shipping_orderline->unit = __( 'pcs.', 'bambora-online-checkout' );
				$shipping_orderline->totalpriceinclvat = abs( Bambora_Currency::convert_price_to_minorunits( $shipping_total + $shipping_tax, $minorunits ) );
				$shipping_orderline->vat = 0;
				$bambora_refund_lines[] = $shipping_orderline;
				$items_total += $shipping_total + $shipping_tax;
			}

			if ( $items_total < $total ) {
				return false;
			} elseif ( $items_total > $total ) {
				$additional_refund_orderline = new Bambora_Orderline();
				$additional_refund_orderline->id = __( 'Refund', 'bambora-online-checkout' );
				$additional_refund_orderline->linenumber = ++$line_number;
				$additional_refund_orderline->description = __( 'Refund', 'bambora-online-checkout' ) . ($reason !== '' ? ': ' . $reason : '');
				$additional_refund_orderline->text = __( 'Refund', 'bambora-online-checkout' );
				$additional_refund_orderline->quantity = 1;
				$additional_refund_orderline->unit = __( 'pcs.', 'bambora-online-checkout' );
				$additional_refund_orderline->totalpriceinclvat = abs( Bambora_Currency::convert_price_to_minorunits( $total - $items_total, $minorunits ) );
				$additional_refund_orderline->vat = 0;
				$bambora_refund_lines[] = $additional_refund_orderline;
			}

			return true;
		}

		/**
         * Bambora Meta Boxes
         */
		public function bambora_meta_boxes() {
			global $post;
			$order_id = $post->ID;
			$payment_method = get_post_meta( $order_id, '_payment_method', true );
			if ( $this->id === $payment_method ) {
				add_meta_box(
					'bambora-payment-actions',
					__( 'Bambora Online Checkout', 'bambora-online-checkout' ),
					array( &$this, 'bambora_meta_box_payment' ),
					'shop_order',
					'side',
					'high'
				);
			}
		}

		/**
         * Generate the Bambora payment meta box and echos the HTML
         */
		public function bambora_meta_box_payment() {
			global $post;
			$order_id = $post->ID;

			$transaction_id = get_post_meta( $order_id, $this::PSP_REFERENCE, true );
			if ( strlen( $transaction_id ) > 0 ) {
				$html = '';
				try {
					$api_key = $this->get_api_key();
					$api = new Bambora_Api( $api_key );

					$get_transaction = $api->get_transaction( $transaction_id );

					if ( ! isset( $get_transaction ) || ! $get_transaction['meta']['result'] ) {
						$error_message = isset( $get_transaction ) ? $get_transaction['meta']['message']['merchant'] : __( 'No connection to Bambora' );
						echo $html;
                        echo $this->message_to_html( 'error', $error_message );
						return null;
					}

					$transaction_info = $get_transaction['transaction'];
					$minorunits = $transaction_info['currency']['minorunits'];
					$total_authorized = Bambora_Currency::convert_price_from_minorunits( $transaction_info['total']['authorized'], $minorunits );
					$total_captured = Bambora_Currency::convert_price_from_minorunits( $transaction_info['total']['captured'], $minorunits );
					$available_for_capture = Bambora_Currency::convert_price_from_minorunits( $transaction_info['available']['capture'], $minorunits );

					$total_credited = Bambora_Currency::convert_price_from_minorunits( $transaction_info['total']['credited'], $minorunits );
					$can_delete = $transaction_info['candelete'];
					$curency_code = $transaction_info['currency']['code'];

					$html = '<div class="bambora_info">';
					$html .= '<div class="bambora_transactionid">';
					$html .= '<p>' . __( 'Transaction ID', 'bambora-online-checkout' ) . '</p>';
					$html .= '<p>' . $transaction_info['id'] . '</p>';
					$html .= '</div>';
					$html .= '<br />';

					$html .= '<div class="bambora_info_overview">';
					$html .= '<p>' . __( 'Authorized:', 'bambora-online-checkout' ) . '</p>';
					$html .= '<p>' . $this->format_number( $total_authorized, $minorunits ) . ' ' . $curency_code . '</p>';
					$html .= '</div>';

					$html .= '<div class="bambora_info_overview">';
					$html .= '<p>' . __( 'Captured:', 'bambora-online-checkout' ) . '</p>';
					$html .= '<p>' . $this->format_number( $total_captured, $minorunits ) . ' ' . $curency_code . '</p>';
					$html .= '</div>';

					$html .= '<div class="bambora_info_overview">';
					$html .= '<p>' . __( 'Refunded:', 'bambora-online-checkout' ) . '</p>';
					$html .= '<p>' . $this->format_number( $total_credited, $minorunits ) . ' ' . $curency_code . '</p>';
					$html .= '</div>';

					$html .= '</div>';
					$html .= '<br />';

					if ( 0 < $available_for_capture || true === $can_delete ) {
						$html .= '<div class="bambora_action_container">';

						if ( 0 < $available_for_capture ) {
							$html .= '<input type="hidden" id="bambora_currency" name="bambora_currency" value="' . $curency_code . '">';
							$html .= '<input type="hidden" id="bambora_capture_message" name="bambora_capture_message" value="' . __( 'Are you sure you want to capture the payment?', 'bambora-online-checkout' ) . '" />';
							$html .= '<div class="bambora_action">';
							$html .= '<p>' . $curency_code . '</p>';
							$html .= '<input type="text" value="' . $this->format_number( $available_for_capture, $minorunits, false ) . '"id="bambora_capture_amount" class="bambora_amount" name="bambora_amount" />';
							$html .= '<input id="bambora_capture_submit" class="button capture" name="bambora_capture" type="submit" value="' . __( 'Capture' ) . '" />';
							$html .= '</div>';
							$html .= '<br />';
						}
						if ( true === $can_delete ) {
							$html .= '<input type="hidden" id="bambora_delete_message" name="bambora_delete_message" value="' . __( 'Are you sure you want to delete the payment?', 'bambora-online-checkout' ) . '" />';
							$html .= '<div class="bambora_action">';
							$html .= '<input id="bambora_delete_submit" class="button delete" name="bambora_delete" type="submit" value="' . __( 'Delete' ) . '" />';
							$html .= '</div>';
						}
						$html .= '</div>';
						$warning_message = __( 'The amount you entered was in the wrong format.', 'bambora-online-checkout' );

						$html .= '<div id="bambora-format-error" class="bambora bambora_error"><strong>' . __( 'Warning' ) . ' </strong>' . $warning_message . '<br /><strong>' . __( 'Correct format is: 1234.56', 'bambora-online-checkout' ) . '</strong></div>';
						$html .= '<br />';
					}

					$get_transaction_operation = $api->get_transaction_operations( $transaction_id );

					if ( ! isset( $get_transaction_operation ) || ! $get_transaction_operation['meta']['result'] ) {
						$error_message = __( 'Get transactions' ). ' - ' . $get_transaction_operation['meta']['message']['merchant'];
						echo $html;
                        echo $this->message_to_html( 'error', $error_message );
                        return null;
					}

					$transaction_operations = $get_transaction_operation['transactionoperations'];

					$html .= $this->build_transaction_log_table( $transaction_operations, $minorunits );
					$html .= '<br />';

					echo ent2ncr( $html );
				}
                catch (Exception $e) {
					echo $this->message_to_html( 'error', $e->getMessage() );
					return null;
				}
			} else {
				esc_attr_e( 'No transaction was found', 'bambora-online-checkout' );
			}
		}

		/**
         * Build transaction log table HTML
         *
         * @param array $operations
         * @param int   $minorunits
         * @return string
         * */
		private function build_transaction_log_table( $operations, $minorunits ) {
			$html = '<h4>' . __( 'TRANSACTION HISTORY', 'bambora-online-checkout' ) . '</h4>';
			$html .= '<table class="bambora-table">';
			$html .= $this->build_transaction_log_rows( $operations, $minorunits );
			$html .= '</table>';

			return $html;
		}

		/**
         * Format date
         *
         * @param string $raw_date
         * @return string
         */
		private function format_date( $raw_date ) {
			$date = str_replace( 'T', ' ', substr( $raw_date, 0, 19 ) );
			$date_stamp = strtotime( $date );
			$date_format = wc_date_format();
			$formated_date = date( $date_format, $date_stamp );

			return $formated_date;
		}

		/**
         * Build transaction log row HTML
         *
         * @param array $operations
         * @param int   $minorunits
         * @return string
         */
		private function build_transaction_log_rows( $operations, $minorunits ) {
			$html = '';
			foreach ( $operations as $operation ) {
				$html .= '<tr class="bambora_transaction_row_header">';
				$html .= '<td>' . $this->format_date( $operation['createddate'] ) . '</td>';

				if ( array_key_exists( 'ecis', $operation ) && is_array( $operation['ecis'] ) && count( $operation['ecis'] ) > 0 ) {
					$html .= '<td>ECI: ' . $operation['ecis'][0]['value'] . '</td>';
				} else {
					$html .= '<td>ECI: -</td>';
				}

				$html .= '</tr>';

				$html .= '<tr class="bambora_transaction">';
				$html .= '<td>' . $this->convert_action( $operation['action'] ) . '</td>';

				$amount = Bambora_Currency::convert_price_from_minorunits( $operation['amount'], $minorunits );
				if ( $amount > 0 ) {
					$html .= '<td>' . $this->format_number( $amount, $minorunits ) . ' ' . $operation['currency']['code'] . '</td>';
				} else {
					$html .= '<td>-</td>';
				}

				$html .= '</tr>';

				if ( array_key_exists( 'transactionoperations', $operation ) && count( $operation['transactionoperations'] ) > 0 ) {
					$html .= $this->build_transaction_log_rows( $operation['transactionoperations'], $minorunits );
				}
			}

			return $html;
		}

		/**
         * Bambora Action
         */
		public function bambora_action() {
			if ( isset( $_GET['bambora_action'] ) ) {
				$order = wc_get_order( $_GET['post'] );
                $order_id = $this->is_woocommerce_3() ? $order->get_id() : $order->id;
				$transaction_id = get_post_meta( $order_id, $this::PSP_REFERENCE, true );
				$currency = $_GET['currency'];
				$minorunits = Bambora_Currency::get_currency_minorunits( $currency );
				$api_key = $this->get_api_key();
				$api = new Bambora_Api( $api_key );
                $success = true;
				try {
					switch ( $_GET['bambora_action'] ) {
						case 'capture':
							$amount = str_replace( ',', '.', $_GET['amount'] );
							$amount = Bambora_Currency::convert_price_to_minorunits( $amount, $minorunits );
							$capture = $api->capture( $transaction_id, $amount, $currency );

							if ( ! isset( $capture ) || ! $capture['meta']['result'] ) {
								$message = isset( $capture ) ? $capture['meta']['message']['merchant'] : __( 'No connection to Bambora', 'bambora-online-checkout' );
								echo $this->message_to_html( 'error', $message );
                                $success = false;
							}
							break;
						case 'delete':
							$delete = $api->delete( $transaction_id );
							if ( ! isset( $delete ) || ! $delete['meta']['result'] ) {
								$message = isset( $delete ) ? $delete['meta']['message']['merchant'] : __( 'No connection to Bambora', 'bambora-online-checkout' );
								echo $this->message_to_html( 'error', $message );
                                $success = false;
							}
							break;
					}
				}
                catch (Exception $e) {
					echo $this->message_to_html( 'error', $e->getMessage() );
                    $success = false;
				}

                if($success) {
                    global $post;
                    $url = admin_url( 'post.php?post=' . $post->ID . '&action=edit' );
                    wp_safe_redirect( $url );
                }
			}
		}

		/**
         * Format a number
         *
         * @param mixed $number
         * @param int   $decimals
         * @return string
         */
		private function format_number( $number, $decimals, $display_thousand_separator = true ) {
			if($display_thousand_separator) {
                return number_format( $number, $decimals, wc_get_price_decimal_separator(), wc_get_price_thousand_separator() );
            }

            return number_format( $number, $decimals, wc_get_price_decimal_separator(), '' );
		}

		/**
         * Convert action
         *
         * @param string $action
         * @return string
         * */
		private function convert_action( $action ) {
			if ( 'Authorize' === $action ) {
				return __( 'Authorized', 'bambora-online-checkout' );
			} elseif ( 'Capture' === $action ) {
				return __( 'Captured', 'bambora-online-checkout' );
			} elseif ( 'Credit' === $action ) {
				return __( 'Refunded', 'bambora-online-checkout' );
			} elseif ( 'Delete' === $action ) {
				return __( 'Deleted', 'bambora-online-checkout' );
			} else {
				return $action;
			}
		}

		/**
         * Convert message to HTML
         *
         * @param string $type
         * @param string $message
         * @return string
         * */
		private function message_to_html( $type, $message ) {
			$html = '<div id="message" class="bambora_message bambora_' . $type . '">
						<strong>' . $this->message_type_to_upper( $type ) . '! </strong>'
						. $message . '</div>';

			return ent2ncr( $html );
		}

		/**
         * Convert Message type first letter to upper
         *
         * @param string $type
         * @return string
         */
		private function message_type_to_upper( $type ) {
			if ( ! isset( $type ) ) {
				return '';
			}
			$first_letter = substr( $type, 0, 1 );
			$first_letter_to_upper = strtoupper( $first_letter );
			$result = str_replace( $first_letter, $first_letter_to_upper, $type );

			return $result;
		}

		/**
         * Get the Bambora Api Key
         */
		private function get_api_key() {
            return Bambora_Helper::generate_api_key( $this->merchant, $this->accesstoken, $this->secrettoken );
		}

        /**
         * Determines if the current WooCommerce version is 3.x.x
         *
         * @return boolean
         */
        private function is_woocommerce_3() {
            return version_compare(WC()->version, '3.0', 'ge');
        }
	}

    // Load the module into WordPress / WooCommerce

    add_filter( 'woocommerce_payment_gateways', 'add_bambora_online_checkout_woocommerce' );
    Bambora_Online_Checkout::get_instance()->init_hooks();
	/**
     * Add the Bambora gateway to WooCommerce
     *
     * @param array $methods
     **/
	function add_bambora_online_checkout_woocommerce( $methods ) {
		$methods[] = 'bambora_online_checkout';
		return $methods;
	}

    $plugin_dir = basename( dirname( __FILE__ ) );
    load_plugin_textdomain( 'bambora_online_checkout', false, $plugin_dir . '/languages/' );
}
