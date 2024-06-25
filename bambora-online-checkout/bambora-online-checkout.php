<?php
/**
 * Plugin Name: Worldline Checkout
 * Plugin URI: https://worldline.com/sv-se/
 * Description: Worldline Checkout Payment Gateway for WooCommerce (prev. Bambora Online Checkout)
 * Version: 7.1.1
 * Author: Bambora
 * Author URI: https://worldline.com/sv-se/
 * Text Domain: bambora-online-checkout
 *
 * @author  Bambora
 * @package bambora_online_checkout
 */
use Automattic\WooCommerce\Utilities\FeaturesUtil;

add_action( 'plugins_loaded', 'init_bambora_online_checkout', 0 );

/**
 * Add Worldline Checkout
 *
 * @return void
 * @throws Exception
 */
function init_bambora_online_checkout() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	define( 'BOC_LIB', dirname( __FILE__ ) . '/lib/' );
	define( 'BOC_VERSION', '7.1.1' );

	// Including Bambora files!
	include( BOC_LIB . 'bambora-online-checkout-api.php' );
	include( BOC_LIB . 'bambora-online-checkout-helper.php' );
	include( BOC_LIB . 'bambora-online-checkout-currency.php' );
	include( BOC_LIB . 'bambora-online-checkout-log.php' );

	/**
	 * Worldline Checkout
	 **/
	class Bambora_Online_Checkout extends WC_Payment_Gateway {
		/**
		 * Singleton instance
		 *
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
			if ( ! isset( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		/**
		 * Constructor
		 */
		public function __construct() {
			$this->id                 = 'bambora';
			$this->method_title       = 'Worldline Checkout';
			$this->method_description = 'Worldline Checkout enables easy and secure payments on your shop';
			$this->icon               = WP_PLUGIN_URL . '/' . plugin_basename( dirname( __FILE__ ) ) . '/bambora-logo.svg';
			$this->has_fields         = false;

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

			// Init the Worldline Checkout logger
			$this->_boc_log = new Bambora_Online_Checkout_Log();

			// Load the form fields.!
			$this->init_form_fields();

			// Load the settings.!
			$this->init_settings();

			// Initialize Worldline Checkout Settings
			$this->init_bambora_online_checkout_settings();
		}

		/**
		 * Initialize Worldline Checkout Settings
		 */
		public function init_bambora_online_checkout_settings() {
			// Define user set variables!
			$this->enabled                   = array_key_exists( 'enabled', $this->settings ) ? $this->settings['enabled'] : 'yes';
			$this->title                     = array_key_exists( 'title', $this->settings ) ? $this->settings['title'] : 'Worldline Checkout';
			$this->description               = array_key_exists( 'description', $this->settings ) ? $this->settings['description'] : 'Pay using Worldline Checkout';
			$this->merchant                  = array_key_exists( 'merchant', $this->settings ) ? $this->settings['merchant'] : '';
			$this->accesstoken               = array_key_exists( 'accesstoken', $this->settings ) ? $this->settings['accesstoken'] : '';
			$this->secrettoken               = array_key_exists( 'secrettoken', $this->settings ) ? $this->settings['secrettoken'] : '';
			$this->paymentwindowid           = array_key_exists( 'paymentwindowid', $this->settings ) ? $this->settings['paymentwindowid'] : 1;
			$this->instantcapture            = array_key_exists( 'instantcapture', $this->settings ) ? $this->settings['instantcapture'] : 'no';
			$this->instantcaptureonrenewal   = array_key_exists( 'instantcaptureonrenewal', $this->settings ) ? $this->settings['instantcaptureonrenewal'] : 'no';
			$this->immediateredirecttoaccept = array_key_exists( 'immediateredirecttoaccept', $this->settings ) ? $this->settings['immediateredirecttoaccept'] : 'no';
			$this->addsurchargetoshipment    = array_key_exists( 'addsurchargetoshipment', $this->settings ) ? $this->settings['addsurchargetoshipment'] : 'no';
			$this->md5key                    = array_key_exists( 'md5key', $this->settings ) ? $this->settings['md5key'] : '';
			$this->roundingmode              = array_key_exists( 'roundingmode', $this->settings ) ? $this->settings['roundingmode'] : Bambora_Online_Checkout_Currency::ROUND_DEFAULT;
			$this->captureonstatuscomplete   = array_key_exists( 'captureonstatuscomplete', $this->settings ) ? $this->settings['captureonstatuscomplete'] : 'no';
			$this->rolecapturerefunddelete   = array_key_exists( 'rolecapturerefunddelete', $this->settings ) ? $this->settings['rolecapturerefunddelete'] : 'shop_manager';
			$this->allowlowvalueexemption    = array_key_exists( 'allowlowvalueexemption', $this->settings ) ? $this->settings['allowlowvalueexemption'] : 'no';
			$this->limitforlowvalueexemption = array_key_exists( 'limitforlowvalueexemption', $this->settings ) ? $this->settings['limitforlowvalueexemption'] : '';
			$this->termsandconditions        = array_key_exists( 'termsandconditions', $this->settings ) ? $this->settings['termsandconditions'] : '';
		}

		/**
		 * Initialize module hooks
		 */
		public function init_hooks() {
			// Actions!
			add_action( 'woocommerce_api_' . strtolower( get_class() ), array(
				$this,
				'bambora_online_checkout_callback'
			) );

			if ( is_admin() ) {
				add_action( 'add_meta_boxes', array(
					$this,
					'bambora_online_checkout_meta_boxes'
				) );
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
					$this,
					'process_admin_options'
				) );
				add_action( 'wp_before_admin_bar_render', array(
					$this,
					'bambora_online_checkout_actions'
				) );
				add_action( 'wp_before_admin_bar_render', array(
					$this,
					'bambora_online_checkout_paymentrequest_actions'
				) );
				add_action( 'admin_notices', array(
					$this,
					'bambora_online_checkout_admin_notices'
				) );
			}
			if ( $this->captureonstatuscomplete === 'yes' ) {
				add_action( 'woocommerce_order_status_completed', array(
					$this,
					'bambora_online_checkout_order_status_completed'
				) );
			}
			// Subscriptions
			add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, array(
				$this,
				'scheduled_subscription_payment'
			), 10, 2 );
			add_action( 'woocommerce_subscription_cancelled_' . $this->id, array(
				$this,
				'subscription_cancellation'
			) );

			// Register styles!
			add_action( 'admin_enqueue_scripts', array(
				$this,
				'enqueue_wc_bambora_online_checkout_admin_styles_and_scripts'
			) );
			add_action( 'wp_enqueue_scripts', array(
				$this,
				'enqueue_wc_bambora_online_checkout_front_styles'
			) );
			add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'add_custom_order_column' ), 20 );
			add_action( 'manage_woocommerce_page_wc-orders_custom_column', array(
				$this,
				'populate_custom_order_column_hpos'
			), 20, 2 );

		}

		/**
		 * Show messages in the Administration
		 */
		public function bambora_online_checkout_admin_notices() {
			Bambora_Online_Checkout_Helper::echo_admin_notices();
		}

		/**
		 * Enqueue Admin Styles and Scripts
		 */
		public function enqueue_wc_bambora_online_checkout_admin_styles_and_scripts() {
			wp_register_style( 'bambora_online_checkout_admin_style', plugins_url( 'bambora-online-checkout/style/bambora-online-checkout-admin.css' ), null, BOC_VERSION );
			wp_enqueue_style( 'bambora_online_checkout_admin_style' );

			// Fix for load of Jquery time!
			wp_enqueue_script( 'jquery' );

			wp_enqueue_script( 'bambora_online_checkout_admin', WP_PLUGIN_URL . '/' . plugin_basename( dirname( __FILE__ ) ) . '/js/bambora-online-checkout-admin.js', null, BOC_VERSION );
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
			if ( ! function_exists( 'get_editable_roles' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/user.php' );
			}

			$roles = wp_roles()->roles;

			foreach ( $roles as $role => $details ) {
				$roles_options[ $role ] = translate_user_role( $details['name'] );
			}
			$this->form_fields = array(
				'enabled'                   => array(
					'title'   => 'Activate module',
					'type'    => 'checkbox',
					'label'   => 'Enable Worldline Checkout as a payment option.',
					'default' => 'yes'
				),
				'title'                     => array(
					'title'       => 'Title',
					'type'        => 'text',
					'description' => 'The title of the payment method displayed to the customers.',
					'default'     => 'Worldline Checkout'
				),
				'description'               => array(
					'title'       => 'Description',
					'type'        => 'textarea',
					'description' => 'The description of the payment method displayed to the customers.',
					'default'     => 'Pay using Worldline Checkout'
				),
				'merchant'                  => array(
					'title'       => 'Merchant number',
					'type'        => 'text',
					'description' => 'The number identifying your Worldline merchant account.',
					'default'     => ''
				),
				'accesstoken'               => array(
					'title'       => 'Access token',
					'type'        => 'text',
					'description' => 'The Access token for the API user received from the Worldline administration.',
					'default'     => ''
				),
				'secrettoken'               => array(
					'title'       => 'Secret token',
					'type'        => 'password',
					'description' => 'The Secret token for the API user received from the Worldline administration.',
					'default'     => ''
				),
				'md5key'                    => array(
					'title'       => 'MD5 Key',
					'type'        => 'text',
					'description' => 'The MD5 key is used to stamp data sent between WooCommerce and Worldline to prevent it from being tampered with. The MD5 key is optional but if used here, must be the same as in the Bambora administration.',
					'default'     => ''
				),
				'paymentwindowid'           => array(
					'title'       => 'Payment Window ID',
					'type'        => 'text',
					'description' => 'The ID of the payment window to use.',
					'default'     => '1'
				),
				'instantcapture'            => array(
					'title'       => 'Instant capture',
					'type'        => 'checkbox',
					'description' => 'Capture the payments at the same time they are authorized. In some countries, this is only permitted if the consumer receives the products right away Ex. digital products.',
					'label'       => 'Enable Instant Capture',
					'default'     => 'no'
				),
				'instantcaptureonrenewal'   => array(
					'title'       => 'Instant capture for renewal of Subscriptions',
					'type'        => 'checkbox',
					'description' => 'Capture the payments at the same time they are authorized for recurring payments. In some countries, this is only permitted if the consumer receives the products right away Ex. digital products.',
					'label'       => 'Enable Instant Capture for Renewals',
					'default'     => 'no'
				),
				'immediateredirecttoaccept' => array(
					'title'       => 'Immediate Redirect',
					'type'        => 'checkbox',
					'description' => 'Immediately redirect your customer back to you shop after the payment completed.',
					'label'       => 'Enable Immediate redirect',
					'default'     => 'no'
				),
				'addsurchargetoshipment'    => array(
					'title'       => 'Add Surcharge',
					'type'        => 'checkbox',
					'description' => 'Display surcharge amount on the order as an item',
					'label'       => 'Enable Surcharge',
					'default'     => 'no'
				),
				'captureonstatuscomplete'   => array(
					'title'       => 'Capture on status Completed',
					'type'        => 'checkbox',
					'description' => 'When this is enabled the full payment will be captured when the order status changes to Completed',
					'default'     => 'no'
				),
				'roundingmode'              => array(
					'title'       => 'Rounding mode',
					'type'        => 'select',
					'description' => 'Please select how you want the rounding of the amount sent to the payment system',
					'options'     => array(
						Bambora_Online_Checkout_Currency::ROUND_DEFAULT => 'Default',
						Bambora_Online_Checkout_Currency::ROUND_UP      => 'Always up',
						Bambora_Online_Checkout_Currency::ROUND_DOWN    => 'Always down'
					),
					'label'       => 'Rounding mode',
					'default'     => 'normal',
				),
				'rolecapturerefunddelete'   => array(
					'title'       => 'User role for access to capture/refund/delete',
					'type'        => 'select',
					'description' => 'Please select user role for access to capture/refund/delete (role administrator will always have access). The role also of course need to have access to view orders. ',
					'options'     => $roles_options,
					'label'       => 'User role',
					'default'     => 'shop_manager'
				),
				'allowlowvalueexemption'    => array(
					'title'       => 'Enable Low Value Exemption',
					'type'        => 'checkbox',
					'description' => 'Allow you as a merchant to let the customer attempt to skip Strong Customer Authentication(SCA) when the value of the order is below your defined limit. Note: the liability will be on you as a merchant.',
					'default'     => 'no'
				),
				'limitforlowvalueexemption' => array(
					'title'       => 'Max Amount for Low Value Exemption',
					'type'        => 'text',
					'description' => 'Any amount below this max amount might skip SCA if the issuer would allow it. Recommended amount is about €30 in your local currency. <a href="https://developer.bambora.com/europe/checkout/psd2/lowvalueexemption"  target="_blank">See more information here.</a>',
					'default'     => ''
				),
				'termsandconditions'        => array(
					'title'       => 'URL to Terms & Conditions',
					'type'        => 'text',
					'description' => 'If you are using Payment Requests this is where you can set the URL for your Terms & Conditions.',
					'default'     => ''
				),
			);
		}

		/**
		 * Admin Panel Options
		 */
		public function admin_options() {
			$version = BOC_VERSION;

			$html = "<h3>Worldline Checkout v{$version}</h3>";

			$html .= Bambora_Online_Checkout_Helper::create_admin_debug_section();
			$html .= '<h3 class="wc-settings-sub-title">Module Configuration</h3>';

			if ( class_exists( 'sitepress' ) ) {
				$html .= '<div class="form-table">
					<h2>You have WPML activated.</h2>
					If you need to configure another merchant number for another language translate them under
					<a href="admin.php?page=wpml-string-translation/menu/string-translation.php&context=admin_texts_woocommerce_bambora_settings" class="current" aria-currents="page">String Translation</a>
					</br>
					Subscriptions are currently only supported for the default merchant number.
					</br>	
</div>';
			}

			$api_key           = $this->get_api_key();
			$api               = new Bambora_Online_Checkout_Api( $api_key );
			$valid_credentials = $api->test_if_valid_credentials();

			if ( $valid_credentials ) {
				$html .= "<b><i>The credentials for your Worldline account are valid.</i></b>";
			} else {
				$html .= "<b><i>The credentials you have provided for your Worldline account are not valid. Please check them before you enable Worldline as a payment option.</i></b>";
			}

			$html .= '<table class="form-table">';

			// Generate the HTML For the settings form.!
			$html .= $this->generate_settings_html( array(), false );
			$html .= '</table>';

			echo ent2ncr( $html );
		}

		/**
		 * Capture the payment on order status completed
		 *
		 * @param mixed $order_id
		 */
		public function bambora_online_checkout_order_status_completed( $order_id ) {
			if ( ! $this->module_check( $order_id ) ) {
				return;
			}

			$order          = wc_get_order( $order_id );
			$order_total    = $order->get_total();
			$capture_result = $this->bambora_online_checkout_capture_payment( $order_id, $order_total, '' );

			if ( is_wp_error( $capture_result ) ) {
				$message = $capture_result->get_error_message( 'bambora_online_checkout_error' );
				$this->_boc_log->add( $message );
				Bambora_Online_Checkout_Helper::add_admin_notices( Bambora_Online_Checkout_Helper::ERROR, $message );
			} else {
				$message = sprintf( __( 'The Capture action was a success for order %s', 'bambora-online-checkout' ), $order_id );
				Bambora_Online_Checkout_Helper::add_admin_notices( Bambora_Online_Checkout_Helper::SUCCESS, $message );
			}
		}

		/**
		 * There are no payment fields for bambora, but we want to show the description if set.
		 **/
		public function payment_fields() {
			if ( $this->description ) {
				// Set description for checkout page!
				$this->set_bambora_description_for_checkout();
				$text_replace             = wptexturize( $this->description );
				$text_remove_double_lines = wpautop( $text_replace );

				echo $text_remove_double_lines;
			}
		}

		/**
		 * Set the WC Payment Gateway description for the checkout page
		 */
		public function set_bambora_description_for_checkout($blocks = false) {
			global $woocommerce;
			$description = '';
			$cart        = WC()->cart;
			if ( isset( $cart ) ) {
				$error_message = __( 'Could not load the payment types', 'bambora-online-checkout' );
				try {
					$currency = get_woocommerce_currency();
					if ( $currency ) {
						$minorunits = Bambora_Online_Checkout_Currency::get_currency_minorunits( $currency );
						$amount     = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $cart->total, $minorunits, $this->roundingmode );
						$this->_boc_log->add( " Currency: {$currency} - Amount: {$amount} -  Minor Units: {$minorunits}" );
						$api_key                    = $this->get_api_key();
						$api                        = new Bambora_Online_Checkout_Api( $api_key );
						$get_payment_types_response = $api->get_payment_types( $currency, $amount );
						if ( isset( $get_payment_types_response ) && $get_payment_types_response->meta->result ) {
							$payment_types = array();
							foreach ( $get_payment_types_response->paymentcollections as $payment ) {
								foreach ( $payment->paymentgroups as $card ) {
									$payment_types[] = $card;
								}
							}
							ksort( $payment_types );

							$payment_types_html = '<div class="bambora_payment_types">';
							foreach ( $payment_types as $card ) {
								$payment_types_html .= '<img class="bambora_payment_type" title="' . $card->displayname . '" alt="' . $card->displayname . '" src="https://d3r1pwhfz7unl9.cloudfront.net/paymentlogos/' . $card->id . '.svg" />';
							}
							$payment_types_html .= '</div>';
							$description        = $payment_types_html;
						} else {
							$get_payment_types_response_error_enduser  = isset( $get_payment_types_response ) ? $get_payment_types_response->meta->message->enduser : __( 'No connection to Bambora', 'bambora-online-checkout' );
							$get_payment_types_response_error_merchant = isset( $get_payment_types_response ) ? $get_payment_types_response->meta->message->merchant : __( 'No connection to Bambora', 'bambora-online-checkout' );

							$this->_boc_log->add( "{$error_message} - {$get_payment_types_response_error_merchant} - Currency: {$currency} - Amount: {$amount} -  Minor Units: {$minorunits} - Api-key {$api_key}" );
							$description = " - {$error_message} - {$get_payment_types_response_error_enduser}";
						}
					} else {
						$message = " - {$error_message} - " . __( 'Could not load the currency', 'bambora-online-checkout' );
						$this->_boc_log->add( $message );
						$description = $message;
					}
				} catch ( Exception $ex ) {
					$description       = " - {$error_message}";
					$exception_message = $ex->getMessage();
					$this->_boc_log->add( "Could not load the payment types - Reason: {$exception_message}" );
				}
			}
			if ( $blocks ) {
				$this->description = "<p>" . $this->description . "</p>" . $description;
			} else {
				$this->description .= $description;
			}

		}

		/**
		 * Get the Worldline Checkout logger
		 *
		 * @return Bambora_Online_Checkout_Log
		 */
		public function get_boc_logger() {
			return $this->_boc_log;
		}

		/**
		 * Handle scheduled subscription payments
		 *
		 * @param mixed $amount_to_charge
		 * @param WC_Order $renewal_order
		 */
		public function scheduled_subscription_payment( $amount_to_charge, $renewal_order ) {
			$subscription     = Bambora_Online_Checkout_Helper::get_subscriptions_for_renewal_order( $renewal_order );
			$result           = $this->process_subscription_payment( $amount_to_charge, $renewal_order, $subscription );
			$renewal_order_id = $renewal_order->get_id();

			// Remove the Worldline Checkout subscription id copied from the subscription
			delete_post_meta( $renewal_order_id, Bambora_Online_Checkout_Helper::BAMBORA_ONLINE_CHECKOUT_SUBSCRIPTION_ID );

			if ( is_wp_error( $result ) ) {
				$message = sprintf( __( 'Worldline Checkout Subscription could not be authorized for renewal order # %s - %s', 'bambora-online-checkout' ), $renewal_order_id, $result->get_error_message( 'bambora_online_checkout_error' ) );
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
		public function process_subscription_payment( $amount, $renewal_order, $subscription ) {
			try {
				$bambora_subscription_id = Bambora_Online_Checkout_Helper::get_bambora_online_checkout_subscription_id( $subscription );
				if ( strlen( $bambora_subscription_id ) === 0 ) {
					return new WP_Error( 'bambora_online_checkout_error', __( 'Worldline Checkout Subscription id was not found', 'bambora-online-checkout' ) );
				}

				$order_currency = $renewal_order->get_currency();
				$minorunits     = Bambora_Online_Checkout_Currency::get_currency_minorunits( $order_currency );
				$amount         = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $amount, $minorunits, $this->roundingmode );

				$renewal_order_id = $renewal_order->get_id();

				$instant_capture_amount = $this->instantcaptureonrenewal === 'yes' ? $amount : 0;
				$api_key                = $this->get_api_key();
				$api                    = new Bambora_Online_Checkout_Api( $api_key );
				$authorize_response     = $api->authorize_subscription( $bambora_subscription_id, $amount, $order_currency, $renewal_order_id, $instant_capture_amount );

				if ( $authorize_response->meta->result == false ) {
					return new WP_Error( 'bambora_online_checkout_error', $authorize_response->meta->message->merchant );
				}
				$renewal_order->payment_complete( $authorize_response->transactionid );

				// Add order note
				$message = sprintf( __( 'Worldline Checkout Subscription was authorized for renewal order %s with transaction id %s', 'bambora-online-checkout' ), $renewal_order_id, $authorize_response->transactionid );
				$renewal_order->add_order_note( $message );
				$subscription->add_order_note( $message );

				return true;
			} catch ( Exception $ex ) {
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

				if ( is_wp_error( $result ) ) {
					$message = sprintf( __( 'Worldline Checkout Subscription could not be canceled - %s', 'bambora-online-checkout' ), $result->get_error_message( 'bambora_online_checkout_error' ) );
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
				if ( Bambora_Online_Checkout_Helper::order_is_subscription( $subscription ) ) {
					$bambora_subscription_id = Bambora_Online_Checkout_Helper::get_bambora_online_checkout_subscription_id( $subscription );
					if ( strlen( $bambora_subscription_id ) === 0 ) {
						$orderNote = __( 'Worldline Checkout Subscription ID was not found', 'bambora-online-checkout' );

						return new WP_Error( 'bambora_online_checkout_error', $orderNote );
					}

					$api_key         = $this->get_api_key();
					$api             = new Bambora_Online_Checkout_Api( $api_key );
					$delete_response = $api->delete_subscription( $bambora_subscription_id );

					if ( $delete_response->meta->result ) {
						$subscription->add_order_note( sprintf( __( 'Subscription successfully Cancelled. - Worldline Checkout Subscription Id: %s', 'bambora-online-checkout' ), $bambora_subscription_id ) );
					} else {
						$orderNote = sprintf( __( 'Worldline Checkout Subscription Id: %s', 'bambora-online-checkout' ), $bambora_subscription_id );
						$orderNote = " - {$delete_response->meta->message->merchant}";

						return new WP_Error( 'bambora_online_checkout_error', $orderNote );
					}
				}

				return true;
			} catch ( Exception $ex ) {
				return new WP_Error( 'bambora_online_checkout_error', $ex->getMessage() );
			}
		}

		/**
		 * Process the payment and return the result
		 *
		 * @param int $order_id
		 *
		 * @return string[]
		 */
		public function process_payment( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( Bambora_Online_Checkout_Helper::order_contains_switch( $order ) && ! $order->needs_payment() ) {
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order )
				);
			}
			$api_key                   = $this->get_api_key( $order_id );
			$api                       = new Bambora_Online_Checkout_Api( $api_key );
			$bambora_checkout_request  = $this->create_bambora_checkout_request( $order );
			$bambora_checkout_response = $api->set_checkout_session( $bambora_checkout_request );
			if ( ! isset( $bambora_checkout_response ) || ! $bambora_checkout_response->meta->result ) {
				$error_message = isset( $bambora_checkout_response ) ? $bambora_checkout_response->meta->message->enduser : __( 'No connection to Bambora', 'bambora-online-checkout' );
				$message       = __( 'Could not retrive the payment window. Reason:', 'bambora-online-checkout' ) . ' ' . $error_message;
				$this->_boc_log->add( $message );
				wc_add_notice( $message, 'error' );

				return array(
					'result'   => 'fail',
					'redirect' => ''
				);
			}

			return array(
				'result'   => 'success',
				'redirect' => $bambora_checkout_response->url
			);
		}

		/**
		 * Create Checkout Request
		 *
		 * @param WC_Order $order
		 *
		 * @return Bambora_Online_Checkout_Request
		 */
		protected function create_bambora_checkout_request( $order ) {
			$is_request_to_change_payment_method = Bambora_Online_Checkout_Helper::order_is_subscription( $order );

			$language = str_replace( '_', '-', get_locale() );

			if ( $language == "fi" ) {
				$language = "fi-FI";
			}

			$request                       = new Bambora_Online_Checkout_Request();
			$request->customer             = $this->create_bambora_customer( $order );
			$request->order                = $this->create_bambora_order( $order, $is_request_to_change_payment_method );
			$request->instantcaptureamount = $this->instantcapture === 'yes' ? $request->order->total : 0;
			$request->language             = $language;
			$paymentWindow                 = new Bambora_Online_Checkout_Request_Payment_Window();
			$paymentWindow->id             = $this->paymentwindowid;
			$paymentWindow->language       = $language;

			$request->paymentwindow = $paymentWindow;
			$request->url           = $this->create_bambora_url( $order );

			if ( Bambora_Online_Checkout_Helper::woocommerce_subscription_plugin_is_active() && ( Bambora_Online_Checkout_Helper::order_contains_subscription( $order ) || $is_request_to_change_payment_method ) ) {
				$bambora_subscription  = $this->create_bambora_subscription();
				$request->subscription = $bambora_subscription;
			} elseif ( $this->allowlowvalueexemption ) {
				if ( $request->order->total < Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $this->limitforlowvalueexemption, Bambora_Online_Checkout_Currency::get_currency_minorunits( $request->order->currency ), $this->roundingmode ) ) {
					$request->securityexemption = "lowvaluepayment";
					$request->securitylevel     = "none";
				}
			}

			return $request;
		}

		/**
		 * Create Bambora customer
		 *
		 * @param WC_Order $order
		 *
		 * @return Bambora_Online_Checkout_Customer
		 * */
		protected function create_bambora_customer( $order ) {
			$bambora_customer                         = new Bambora_Online_Checkout_Customer();
			$bambora_customer->email                  = $order->get_billing_email();
			$bambora_customer->phonenumber            = $order->get_billing_phone();
			$bambora_customer->phonenumbercountrycode = $order->get_billing_country();

			return $bambora_customer;
		}

		/**
		 * Create Bambora order
		 *
		 * @param WC_Order $order
		 * @param int $minorunits
		 *
		 * @return Bambora_Online_Checkout_Order
		 * */
		protected function create_bambora_order( $order, $is_request_to_change_payment_method ) {
			$currency   = $order->get_currency();
			$minorunits = Bambora_Online_Checkout_Currency::get_currency_minorunits( $currency );

			$bambora_order                  = new Bambora_Online_Checkout_Order();
			$bambora_order->billingaddress  = $this->create_bambora_address( $order );
			$bambora_order->currency        = $currency;
			$order_number                   = $this->clean_order_number( $order->get_order_number() );
			$bambora_order->id              = ( $order_number );
			$bambora_order->shippingaddress = $this->create_bambora_address( $order );
			$order_total                    = $order->get_total();
			$bambora_order->total           = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $order_total, $minorunits, $this->roundingmode );

			if ( $is_request_to_change_payment_method ) {
				$bambora_order->vatamount = 0;
			} else {
				$bambora_order->lines     = $this->create_bambora_orderlines( $order, $minorunits );
				$order_total_tax          = $order->get_total_tax();
				$bambora_order->vatamount = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $order_total_tax, $minorunits, $this->roundingmode );
			}

			return $bambora_order;
		}

		/**
		 * Removes any special characters from the order number
		 *
		 * @param string $order_number
		 *
		 * @return string
		 */
		protected function clean_order_number( $order_number ) {
			return preg_replace( '/[^a-z\d ]/i', "", $order_number );
		}

		/**
		 * Create Bambora address
		 *
		 * @param WC_Order $order
		 *
		 * @return Bambora_Online_Checkout_Address
		 */
		protected function create_bambora_address( $order ) {
			$bambora_address      = new Bambora_Online_Checkout_Address();
			$bambora_address->att = '';

			$bambora_address->city      = $order->get_shipping_city();
			$bambora_address->country   = $order->get_shipping_country();
			$bambora_address->firstname = $order->get_shipping_first_name();
			$bambora_address->lastname  = $order->get_shipping_last_name();
			$bambora_address->street    = $order->get_shipping_address_1();
			$bambora_address->zip       = $order->get_shipping_postcode();

			return $bambora_address;
		}

		/**
		 * Create Bambora Url
		 *
		 * @param WC_Order $order
		 *
		 * @return Bambora_Online_Checkout_Url
		 */
		protected function create_bambora_url( $order, $is_payment_request = false ) {
			$bambora_url = new Bambora_Online_Checkout_Url();
			$order_id    = $order->get_id();

			if ( ! $is_payment_request ) {
				$bambora_url->accept  = Bambora_Online_Checkout_Helper::get_accept_url( $order );
				$bambora_url->decline = Bambora_Online_Checkout_Helper::get_decline_url( $order );
			}

			$bambora_url->callbacks = array();
			$callback               = new Bambora_Online_Checkout_Callback();
			$callback->url          = apply_filters( 'bambora_online_checkout_callback_url', Bambora_Online_Checkout_Helper::get_bambora_online_checkout_callback_url( $order_id ) );

			$bambora_url->callbacks[]               = $callback;
			$bambora_url->immediateredirecttoaccept = $this->immediateredirecttoaccept === 'yes' ? 1 : 0;

			return $bambora_url;
		}

		/**
		 * Create Worldline Checkout Payment Request
		 *
		 * @param WC_Order $order
		 * @param          $amount
		 * @param          $description
		 *
		 * @return
		 */

		protected function bambora_create_paymentrequest( $order_id, $amount, $description ) {

			$order                               = wc_get_order( $order_id );
			$bambora_paymentrequest              = new Bambora_Online_Checkout_Payment_Request();
			$bambora_paymentrequest->description = $description;
			$bambora_paymentrequest->reference   = "WooCommercePaymentRequest" . $order_id;
			$bambora_paymentrequest_parameters   = new Bambora_Online_Checkout_Payment_Request_Parameters();

			$terms = $this->termsandconditions;

			if ( isset( $terms ) && $terms != "" ) {
				$bambora_paymentrequest->termsurl = $terms;
			}
			$bambora_paymentrequest_parameters->instantcaptureamount = $this->instantcapture === 'yes' ? $bambora_paymentrequest->order->total : 0;

			if ( $order->get_customer_id() ) {
				$bambora_paymentrequest_parameters->customer = $this->create_bambora_customer( $order );
			}

			$bambora_paymentrequest_parameters->order = $this->create_bambora_order( $order, false );
			$bambora_paymentrequest_parameters->url   = $this->create_bambora_url( $order, true );
			$bambora_paymentrequest_payment_window    = new Bambora_Online_Checkout_Request_Payment_Window();

			$bambora_paymentrequest_payment_window->language  = str_replace( '_', '-', get_locale() );
			$bambora_paymentrequest_payment_window->id        = $this->paymentwindowid;
			$bambora_paymentrequest_parameters->paymentwindow = $bambora_paymentrequest_payment_window;
			$bambora_paymentrequest->parameters               = $bambora_paymentrequest_parameters;

			$apiKey               = $this->get_api_key();
			$api                  = new Bambora_Online_Checkout_Api( $apiKey );
			$jsonData             = json_encode( $bambora_paymentrequest );
			$createPaymentRequest = $api->createPaymentRequest( $jsonData );

			if ( $createPaymentRequest->meta->result ) {

				$order->update_meta_data( 'bambora_paymentrequest_id', $createPaymentRequest->id );
				$order->update_meta_data( 'bambora_paymentrequest_url', $createPaymentRequest->url );
				$order->set_payment_method( $this->id );
				$order->set_payment_method_title( $this->method_title );
				$note = sprintf( __( 'Worldline Checkout Payment Request with id %s created for order %s', 'bambora-online-checkout' ), $createPaymentRequest->id, $order->get_order_number() );
				$order->add_order_note( $note );
				$order->save();
				do_action( 'bambora_online_checkout_after_create_paymentrequest', $order_id );

				// The text for the note

				return true;

			} else {
				$message = sprintf( __( 'Create payment request failed for order %s', 'bambora-online-checkout' ), $order_id );
				$this->_boc_log->add( $message );

				return new WP_Error( 'bambora_online_checkout_error', $message );
			}

		}

		/**
		 * Delete Worldline Checkout Payment Request
		 *
		 * @param WC_Order $order
		 * @param          $amount
		 * @param          $currency
		 * @param          $description
		 *
		 * @return
		 */

		protected function bambora_delete_paymentrequest( $order_id, $payment_request_id ) {

			$order                = wc_get_order( $order_id );
			$apiKey               = $this->get_api_key();
			$api                  = new Bambora_Online_Checkout_Api( $apiKey );
			$deletePaymentRequest = $api->deletePaymentRequest( $payment_request_id );

			if ( $deletePaymentRequest->meta->result ) {

				$order->delete_meta_data( 'bambora_paymentrequest_id' );
				$order->delete_meta_data( 'bambora_paymentrequest_url' );
				$note = sprintf( __( 'Worldline Checkout Payment Request %s deleted for order %s', 'bambora-online-checkout' ), $payment_request_id, $order->get_order_number() );
				$order->add_order_note( $note );
				$order->save();
				$order->save();
				do_action( 'bambora_online_checkout_after_delete_paymentrequest', $order_id );

				return true;

			} else {
				$message = sprintf( __( 'Delete payment request failed for order %s', 'bambora-online-checkout' ), $order_id );
				$this->_boc_log->add( $message );

				return new WP_Error( 'bambora_online_checkout_error', $message );
			}

		}

		/**
		 * Send Worldline Checkout Payment Request
		 *
		 *
		 * @return
		 */

		protected function bambora_send_paymentrequest( $order_id, $recipient_name, $recipient_email, $replyto_name, $replyto_email, $email_message ) {

			$order              = wc_get_order( $order_id );
			$payment_request_id = $order->get_meta( "bambora_paymentrequest_id" );

			$recipient                 = new Bambora_Online_Checkout_Payment_Request_Email_Recipient();
			$recipient->replyto        = new Bambora_Online_Checkout_Payment_Request_Email_Recipient_Address();
			$recipient->replyto->name  = $replyto_name;
			$recipient->replyto->email = $replyto_email;
			$recipient->message        = $email_message;
			$recipient->to             = new Bambora_Online_Checkout_Payment_Request_Email_Recipient_Address();
			$recipient->to->email      = $recipient_email;
			$recipient->to->name       = $recipient_name;
			$jsonData                  = json_encode( $recipient );

			$apiKey             = $this->get_api_key();
			$api                = new Bambora_Online_Checkout_Api( $apiKey );
			$sendPaymentRequest = $api->sendPaymentRequestEmail( $payment_request_id, $jsonData );

			if ( $sendPaymentRequest->meta->result ) {
				$note = sprintf( __( 'Worldline Checkout Payment Request with id %s sent with email to %s', 'bambora-online-checkout' ), $payment_request_id, $recipient_email );
				$order->add_order_note( $note );
				$order->save();
				do_action( 'bambora_online_checkout_after_send_paymentrequest', $order_id );

				return true;

			} else {
				$message = sprintf( __( 'Send payment request email failed for order %s', 'bambora-online-checkout' ), $order_id );
				$this->_boc_log->add( $message );

				return new WP_Error( 'bambora_online_checkout_error', $message );
			}

		}


		/**
		 * Create Bambora subscription
		 *
		 * @return Bambora_Online_Checkout_Subscription
		 */
		protected function create_bambora_subscription() {
			$bambora_subscription             = new Bambora_Online_Checkout_Subscription();
			$bambora_subscription->action     = 'create';
			$bambora_subscription->decription = "WooCommerce Subscription v." . WC_Subscriptions::$version;
			$bambora_subscription->reference  = $this->merchant;

			return $bambora_subscription;
		}

		/**
		 * Creates orderlines for an order
		 *
		 * @param WC_Order $order
		 * @param int $minorunits
		 *
		 * @return Bambora_Online_Checkout_Orderline[]
		 */
		protected function create_bambora_orderlines( $order, $minorunits ) {
			$bambora_orderlines = array();
			$items              = $order->get_items();
			$line_number        = 0;
			foreach ( $items as $item ) {
				$item_total                = $order->get_line_total( $item, false, true );
				$item_total_incl_vat       = $order->get_line_total( $item, true, true );
				$item_vat_amount           = $order->get_line_tax( $item );
				$item_quantity             = $item['qty'];
				$line                      = new Bambora_Online_Checkout_Orderline();
				$line->description         = $item['name'];
				$line->id                  = $item['product_id'];
				$line->linenumber          = ++ $line_number;
				$line->quantity            = $item['qty'];
				$line->text                = $item['name'];
				$line->totalprice          = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $item_total, $minorunits, $this->roundingmode );
				$line->totalpriceinclvat   = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $item_total_incl_vat, $minorunits, $this->roundingmode );
				$line->totalpricevatamount = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $item_vat_amount, $minorunits, $this->roundingmode );
				$line->unitpriceinclvat    = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $item_total_incl_vat / $item_quantity, $minorunits, $this->roundingmode );
				$line->unitpricevatamount  = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $item_vat_amount / $item_quantity, $minorunits, $this->roundingmode );
				$line->unitprice           = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( ( $item_total_incl_vat - $item_vat_amount ) / $item_quantity, $minorunits, $this->roundingmode );
				$line->unit                = __( 'pcs.', 'bambora-online-checkout' );
				$line->vat                 = round( ( $item_vat_amount > 0 && $item_total > 0 ? ( $item_vat_amount / $item_total ) * 100 : 0 ), 2, Bambora_Online_Checkout_Currency::roundingmode( $this->roundingmode ) );

				$bambora_orderlines[] = $line;
			}

			$shipping_methods = $order->get_shipping_methods();
			if ( $shipping_methods && count( $shipping_methods ) !== 0 ) {
				$shipping_total  = $order->get_shipping_total();
				$shipping_tax    = (float) $order->get_shipping_tax();
				$shipping_method = reset( $shipping_methods );

				$shipping_orderline                      = new Bambora_Online_Checkout_Orderline();
				$shipping_orderline->id                  = $shipping_method->get_method_id();
				$shipping_orderline->description         = $shipping_method->get_method_title();
				$shipping_orderline->quantity            = 1;
				$shipping_orderline->text                = $shipping_method->get_method_title();
				$shipping_orderline->unit                = __( 'pcs.', 'bambora-online-checkout' );
				$shipping_orderline->linenumber          = ++ $line_number;
				$shipping_orderline->totalprice          = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $shipping_total, $minorunits, $this->roundingmode );
				$shipping_orderline->totalpriceinclvat   = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( ( $shipping_total + $shipping_tax ), $minorunits, $this->roundingmode );
				$shipping_orderline->totalpricevatamount = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $shipping_tax, $minorunits, $this->roundingmode );
				$shipping_orderline->unitprice           = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $shipping_total, $minorunits, $this->roundingmode );
				$shipping_orderline->unitpriceinclvat    = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( ( $shipping_total + $shipping_tax ), $minorunits, $this->roundingmode );
				$shipping_orderline->unitpricevatamount  = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $shipping_tax, $minorunits, $this->roundingmode );
				$shipping_orderline->vat                 = round( (float) ( $shipping_tax > 0 && $shipping_total > 0 ? ( $shipping_tax / $shipping_total ) * 100 : 0 ), 2, Bambora_Online_Checkout_Currency::roundingmode( $this->roundingmode ) );
				$bambora_orderlines[]                    = $shipping_orderline;
			}
			$order_fees = $order->get_fees();
			if ( $order_fees && count( $order_fees ) !== 0 ) {
				foreach ( $order_fees as $fee_item ) {
					$fee_total                          = $fee_item->get_total();
					$fee_tax                            = (float) $fee_item->get_total_tax();
					$fee_orderline                      = new Bambora_Online_Checkout_Orderline();
					$fee_orderline->id                  = __( 'fee', 'bambora-online-checkout' );
					$fee_orderline->linenumber          = ++ $line_number;
					$fee_orderline->description         = __( 'Fee', 'bambora-online-checkout' );
					$fee_orderline->text                = __( 'Fee', 'bambora-online-checkout' );
					$fee_orderline->quantity            = 1;
					$fee_orderline->unit                = __( 'pcs.', 'bambora-online-checkout' );
					$fee_orderline->totalprice          = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $fee_total, $minorunits, $this->roundingmode );
					$fee_orderline->totalpriceinclvat   = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( ( $fee_total + $fee_tax ), $minorunits, $this->roundingmode );
					$fee_orderline->totalpricevatamount = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $fee_tax, $minorunits, $this->roundingmode );
					$fee_orderline->vat                 = round( (float) ( $fee_tax > 0 && $fee_total > 0 ? ( $fee_tax / $fee_total ) * 100 : 0 ), 2, Bambora_Online_Checkout_Currency::roundingmode( $this->roundingmode ) );
					$fee_orderline->unitprice           = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $fee_total, $minorunits, $this->roundingmode );
					$fee_orderline->unitpriceinclvat    = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( ( $fee_total + $fee_tax ), $minorunits, $this->roundingmode );
					$fee_orderline->unitpricevatamount  = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $fee_tax, $minorunits, $this->roundingmode );
					$bambora_orderlines[]               = $fee_orderline;
				}
			}
			$rounding_orderline = $this->create_bambora_orderlines_rounding_fee( $order, $minorunits, $bambora_orderlines, $line_number );
			if ( $rounding_orderline ) {
				$bambora_orderlines[] = $rounding_orderline;
			}

			return $bambora_orderlines;
		}


		protected function create_bambora_orderlines_rounding_fee( $order, $minorunits, $bambora_orderlines, $line_number ) {

			$wc_total      = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $order->get_total(), $minorunits, $this->roundingmode );
			$bambora_total = 0;
			foreach ( $bambora_orderlines as $order_line ) {
				$bambora_total += $order_line->quantity * $order_line->unitpriceinclvat;
			}

			if ( $wc_total != $bambora_total ) {
				$rounding_orderline                      = new Bambora_Online_Checkout_Orderline();
				$rounding_orderline->id                  = __( 'adjustment', 'bambora-online-checkout' );
				$rounding_orderline->totalprice          = $wc_total - $bambora_total;
				$rounding_orderline->totalpriceinclvat   = $wc_total - $bambora_total;
				$rounding_orderline->totalpricevatamount = 0;
				$rounding_orderline->text                = __( 'Rounding adjustment', 'bambora-online-checkout' );
				$rounding_orderline->unitprice           = $wc_total - $bambora_total;
				$rounding_orderline->unitpriceinclvat    = $wc_total - $bambora_total;
				$rounding_orderline->unitpricevatamount  = 0;
				$rounding_orderline->quantity            = 1;
				$rounding_orderline->description         = __( 'Rounding adjustment', 'bambora-online-checkout' );
				$rounding_orderline->linenumber          = ++ $line_number;
				$rounding_orderline->unit                = __( 'pcs.', 'bambora-online-checkout' );
				$rounding_orderline->vat                 = 0.0;

				return $rounding_orderline;
			}

			return false;
		}


		/**
		 * Handle for Bambora IPN Response
		 **/
		public function bambora_online_checkout_callback() {
			$params        = stripslashes_deep( $_GET );
			$message       = '';
			$order         = null;
			$response_code = 400;
			try {
				$isValidCall = Bambora_Online_Checkout_Helper::validate_bambora_online_checkout_callback_params( $params, $this->md5key, $order, $message );
				if ( $isValidCall ) {
					$api_key              = $this->get_api_key( $order->get_id() );
					$api                  = new Bambora_Online_Checkout_Api( $api_key );
					$transaction_response = $api->get_transaction( $params['txnid'] );
					if ( isset( $transaction_response ) && $transaction_response->meta->result ) {
						$transaction   = $transaction_response->transaction;
						$message       = $this->process_bambora_online_checkout_callback( $order, $transaction, $params );
						$response_code = 200;
					} else {
						$message = 'Get Transaction failed on Callback Reason: ';
						$message .= isset( $transaction_response ) ? $transaction_response->meta->message->merchant : 'No connection to bambora';
						$this->_boc_log->add( $message );
						$order->update_status( 'failed', $message );
					}
				} else {
					if ( ! empty( $order ) ) {
						$order->update_status( 'failed', $message );
					}
					$this->_boc_log->separator();
					$this->_boc_log->add( "Callback failed - {$message} - GET params:" );
					$this->_boc_log->add( $params );
					$this->_boc_log->separator();
				}
			} catch ( Exception $ex ) {
				$message       = 'Callback failed Reason: ' . $ex->getMessage();
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
		protected function process_bambora_online_checkout_callback( $order, $bambora_transaction, $params ) {
			try {
				$type                    = '';
				$bambora_subscription_id = array_key_exists( 'subscriptionid', $params ) ? $params['subscriptionid'] : null;
				if ( ( Bambora_Online_Checkout_Helper::order_contains_subscription( $order ) || Bambora_Online_Checkout_Helper::order_is_subscription( $order ) ) && isset( $bambora_subscription_id ) ) {
					$action = $this->process_subscription( $order, $bambora_transaction, $bambora_subscription_id );
					$type   = "Subscription {$action}";
				} else {
					$action = $this->process_standard_payments( $order, $bambora_transaction );
					$type   = "Standard Payment {$action}";
				}
			} catch ( Exception $e ) {
				throw $e;
			}

			return "Worldline Checkout Callback completed - {$type}";
		}

		protected function process_standard_payments( $order, $bambora_transaction ) {
			$action             = '';
			$old_transaction_id = Bambora_Online_Checkout_Helper::get_bambora_online_checkout_transaction_id( $order );
			if ( empty( $old_transaction_id ) ) {
				$this->add_surcharge_fee_to_order( $order, $bambora_transaction );
				$order->add_order_note( sprintf( __( 'Worldline Checkout Payment completed with transaction id %s', 'bambora-online-checkout' ), $bambora_transaction->id ) );
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
			if ( Bambora_Online_Checkout_Helper::order_is_subscription( $order ) ) {
				// Do not cancel subscription if the callback is called more than once !
				$old_bambora_subscription_id = Bambora_Online_Checkout_Helper::get_bambora_online_checkout_subscription_id( $order );
				if ( $bambora_subscription_id != $old_bambora_subscription_id ) {
					$this->subscription_cancellation( $order, true );
					$action = 'changed';
					$order->add_order_note( sprintf( __( 'Worldline Checkout Subscription changed from: %s to: %s', 'bambora-online-checkout' ), $old_bambora_subscription_id, $bambora_subscription_id ) );
					$order->payment_complete();
					$this->save_subscription_meta( $order, $bambora_subscription_id, true );
				} else {
					$action = 'changed (Called multiple times)';
				}
			} else {
				// Do not add surcharge if the callback is called more than once!
				$old_transaction_id = Bambora_Online_Checkout_Helper::get_bambora_online_checkout_transaction_id( $order );
				if ( $bambora_transaction->id != $old_transaction_id ) {
					$this->add_surcharge_fee_to_order( $order, $bambora_transaction );
					$action = 'activated';
					$order->add_order_note( sprintf( __( 'Worldline Checkout Subscription activated with subscription id: %s', 'bambora-online-checkout' ), $bambora_subscription_id ) );
					$order->payment_complete( $bambora_transaction->id );
					$this->save_subscription_meta( $order, $bambora_subscription_id, false );
				} else {
					$action = 'activated (Called multiple times)';
				}
			}

			return $action;
		}

		protected function add_surcharge_fee_to_order( $order, $bambora_transaction ) {
			$minorunits               = $bambora_transaction->currency->minorunits;
			$fee_amount_in_minorunits = $bambora_transaction->total->feeamount;
			if ( $fee_amount_in_minorunits > 0 && $this->addsurchargetoshipment === 'yes' ) {
				$fee_amount = Bambora_Online_Checkout_Currency::convert_price_from_minorunits( $fee_amount_in_minorunits, $minorunits );
				$fee        = (object) array(
					'name'      => __( 'Surcharge Fee', 'bambora-online-checkout' ),
					'amount'    => $fee_amount,
					'taxable'   => false,
					'tax_class' => null,
					'tax_data'  => array(),
					'tax'       => 0
				);

				$fee_item = new WC_Order_Item_Fee();
				$fee_item->set_props( array(
					'name'      => $fee->name,
					'tax_class' => $fee->tax_class,
					'total'     => $fee->amount,
					'total_tax' => $fee->tax,
					'order_id'  => $order->get_id()
				) );
				$fee_item->save();
				$order->add_item( $fee_item );

				$total_incl_fee = $order->get_total() + $fee_amount;
				$order->set_total( $total_incl_fee );
			}
		}

		/**
		 * Store the Worldline Checkout subscription id on subscriptions in the order.
		 *
		 * @param WC_Order $order_id
		 * @param string $bambora_subscription_id
		 */
		protected function save_subscription_meta( $order, $bambora_subscription_id, $is_subscription ) {
			$bambora_subscription_id = wc_clean( $bambora_subscription_id );
			$order_id                = $order->get_id();
			if ( $is_subscription ) {
				$subscription = wcs_get_subscription( $order_id );
				$subscription->update_meta_data( Bambora_Online_Checkout_Helper::BAMBORA_ONLINE_CHECKOUT_SUBSCRIPTION_ID, $bambora_subscription_id );
				$subscription->save();
			} else {
				// Also store it on the subscriptions being purchased in the order
				$subscriptions = Bambora_Online_Checkout_Helper::get_subscriptions_for_order( $order_id );
				foreach ( $subscriptions as $subscription ) {
					$subscription->update_meta_data( Bambora_Online_Checkout_Helper::BAMBORA_ONLINE_CHECKOUT_SUBSCRIPTION_ID, $bambora_subscription_id );
					$subscription->add_order_note( sprintf( __( 'Worldline Checkout Subscription activated with subscription id: %s by order %s', 'bambora-online-checkout' ), $bambora_subscription_id, $order_id ) );
					$subscription->save();
				}
			}
		}

		/**
		 * Process Refund
		 *
		 * @param int $order_id
		 * @param float|null $amount
		 * @param string $reason
		 *
		 * @return bool|WP_Error
		 */
		public function process_refund( $order_id, $amount = null, $reason = '' ) {
			$user = wp_get_current_user();
			if ( in_array( $this->rolecapturerefunddelete, (array) $user->roles ) || in_array( 'administrator', (array) $user->roles ) ) {
				// The user has the role required for  "Capture, Refund, Delete"  and can perform those actions.
			} else {
				// The user can only view the data.
				return new WP_Error( 'notpermitted', __( "Your user role is not allowed to refund via Worldline Checkout    ", "bambora-online-checkout" ) );
			}

			if ( ! isset( $amount ) ) {
				return true;
			}

			if ( $amount < 1 ) {
				return new WP_Error( 'toolow', __( "You have to refund a higher amount than 0.", "bambora-online-checkout" ) );
			}

			$refund_result = $this->bambora_online_checkout_refund_payment( $order_id, $amount, '' );
			if ( is_wp_error( $refund_result ) ) {
				return $refund_result;
			} else {
				$message = sprintf( __( 'The Refund action was a success for order %s', 'bambora-online-checkout' ), $order_id );
				Bambora_Online_Checkout_Helper::add_admin_notices( Bambora_Online_Checkout_Helper::SUCCESS, $message );
			}

			return true;
		}

		/**
		 * Try and create refund lines. If there is a negative amount on one of the refund items, it fails.
		 *
		 * @param WC_Order_Refund $refund
		 * @param Bambora_Online_Checkout_Orderline[] $bambora_refund_lines
		 * @param int $minorunits
		 * @param WC_Order $order
		 * @param boolean $isCollector
		 *
		 * @return boolean
		 */
		protected function create_bambora_refund_lines( $refund, &$bambora_refund_lines, $minorunits, $order, $isCollector = false ) {
			$line_number = 0;
			$total       = $refund->get_total();
			$items_total = 0;

			$refund_items = $refund->get_items();
			foreach ( $refund_items as $item ) {
				$line_total_with_vat = $refund->get_line_total( $item, true, true );
				$line_total          = $refund->get_line_total( $item, false, true );
				$line_vat            = $refund->get_line_tax( $item );

				if ( 0 < $line_total ) {
					throw new exception( __( 'Invalid refund amount for item', 'bambora-online-checkout' ) . ':' . $item['name'] );
				}
				$line                      = new Bambora_Online_Checkout_Orderline();
				$line->description         = $item['name'];
				$line->id                  = $item['product_id'];
				$line->linenumber          = ++ $line_number;
				$line->quantity            = abs( $item['qty'] );
				$line->text                = $item['name'];
				$line->totalpriceinclvat   = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( abs( $line_total_with_vat ), $minorunits, $this->roundingmode );
				$line->totalprice          = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( abs( $line_total ), $minorunits, $this->roundingmode );
				$line->totalpricevatamount = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( abs( $line_vat ), $minorunits, $this->roundingmode );
				$items_total               += $line_total_with_vat;

				if ( ! isset( $line->quantity ) || is_null( $line->quantity ) || $line->quantity == 0 ) {
					$quantity = 1;
				} else {
					$quantity = abs( $item['qty'] );
				}
				$line->unitpriceinclvat   = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( abs( $line_total_with_vat ) / $quantity, $minorunits, $this->roundingmode );
				$line->unitprice          = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( abs( $line_total ) / $quantity, $minorunits, $this->roundingmode );
				$line->unitpricevatamount = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( abs( $line_vat ) / $quantity, $minorunits, $this->roundingmode );
				$line->unit               = __( 'pcs.', 'bambora-online-checkout' );
				$line->vat                = round( (float) ( $line_vat > 0 ? ( $line_vat / $line_total ) * 100 : 0 ), 2, Bambora_Online_Checkout_Currency::roundingmode( $this->roundingmode ) );

				$bambora_refund_lines[] = $line;
			}

			$shipping_methods       = $refund->get_shipping_methods();
			$order_shipping_methods = $order->get_shipping_methods();
			if ( $shipping_methods && count( $shipping_methods ) !== 0 ) {
				$shipping_total = $refund->get_shipping_total();
				$shipping_tax   = $refund->get_shipping_tax();

				if ( 0 < $shipping_total || 0 < $shipping_tax ) {
					throw new Exception( __( 'Invalid refund amount for shipping', 'bambora-online-checkout' ) );
				}
				if ( $isCollector && $order_shipping_methods && count( $order_shipping_methods ) !== 0 ) {
					$order_shipping_total = $order->get_shipping_total();
					$order_shipping_tax   = $order->get_shipping_tax();
					if ( abs( (float) $order_shipping_total ) != abs( (float) $shipping_total ) || abs( (float) $order_shipping_tax ) != abs( (float) $shipping_tax ) ) {
						throw new Exception( __( 'You can only refund complete order lines for payments made with Walley.', 'bambora-online-checkout' ) );
					}
				}
				$shipping_method                         = reset( $shipping_methods );
				$shipping_orderline                      = new Bambora_Online_Checkout_Orderline();
				$shipping_orderline->id                  = $shipping_method->get_method_id();
				$shipping_orderline->linenumber          = ++ $line_number;
				$shipping_orderline->description         = $shipping_method->get_method_title();
				$shipping_orderline->text                = $shipping_method->get_method_title();
				$shipping_orderline->quantity            = 1;
				$shipping_orderline->unit                = __( 'pcs.', 'bambora-online-checkout' );
				$shipping_orderline->totalpriceinclvat   = abs( Bambora_Online_Checkout_Currency::convert_price_to_minorunits( ( $shipping_total + $shipping_tax ), $minorunits, $this->roundingmode ) );
				$shipping_orderline->totalprice          = abs( Bambora_Online_Checkout_Currency::convert_price_to_minorunits( ( $shipping_total ), $minorunits, $this->roundingmode ) );
				$shipping_orderline->totalpricevatamount = abs( Bambora_Online_Checkout_Currency::convert_price_to_minorunits( ( $shipping_tax ), $minorunits, $this->roundingmode ) );
				$shipping_orderline->vat                 = round( (float) ( abs( $shipping_tax ) > 0 ? ( ( abs( $shipping_tax ) / abs( $shipping_total ) ) ) * 100 : 0 ), 2, Bambora_Online_Checkout_Currency::roundingmode( $this->roundingmode ) );
				$shipping_orderline->unitpriceinclvat    = abs( Bambora_Online_Checkout_Currency::convert_price_to_minorunits( ( $shipping_total + $shipping_tax ), $minorunits, $this->roundingmode ) );
				$shipping_orderline->unitprice           = abs( Bambora_Online_Checkout_Currency::convert_price_to_minorunits( ( $shipping_total ), $minorunits, $this->roundingmode ) );
				$shipping_orderline->unitpricevatamount  = abs( Bambora_Online_Checkout_Currency::convert_price_to_minorunits( ( $shipping_tax ), $minorunits, $this->roundingmode ) );
				$bambora_refund_lines[]                  = $shipping_orderline;
				$items_total                             += $shipping_total + $shipping_tax;
			}
			$fees       = $refund->get_fees();
			$order_fees = $order->get_fees();
			if ( $fees && count( $fees ) !== 0 ) {
				foreach ( $fees as $fee_item ) {
					$fee_total = $fee_item->get_total();

					$fee_tax = $fee_item->get_total_tax();
					if ( 0 < $fee_total ) {
						throw new Exception( __( 'Invalid refund amount for fees', 'bambora-online-checkout' ) );
					}
					if ( $isCollector && $order_fees && count( $order_fees ) !== 0 ) {
						if ( $order_fees && count( $order_fees ) !== 0 ) {
							foreach ( $order_fees as $order_fee_item ) {
								$order_fee_total += (float) $order_fee_item->get_total();
								$order_fee_tax   += (float) $order_fee_item->get_total_tax();
							}
						}

						if ( abs( $order_fee_total ) != abs( $fee_total ) || abs( $order_fee_tax ) != abs( $fee_tax ) ) {
							throw new Exception( __( 'You can only refund complete order lines for payments made with Walley.', 'bambora-online-checkout' ) );
						}
					}

					$fee_orderline                      = new Bambora_Online_Checkout_Orderline();
					$fee_orderline->id                  = __( 'fee', 'bambora-online-checkout' );
					$fee_orderline->linenumber          = ++ $line_number;
					$fee_orderline->description         = __( 'Fee', 'bambora-online-checkout' );
					$fee_orderline->text                = __( 'Fee', 'bambora-online-checkout' );
					$fee_orderline->quantity            = 1;
					$fee_orderline->unit                = __( 'pcs.', 'bambora-online-checkout' );
					$fee_orderline->totalpriceinclvat   = abs( Bambora_Online_Checkout_Currency::convert_price_to_minorunits( ( $fee_total + $fee_tax ), $minorunits, $this->roundingmode ) );
					$fee_orderline->totalprice          = abs( Bambora_Online_Checkout_Currency::convert_price_to_minorunits( ( $fee_total ), $minorunits, $this->roundingmode ) );
					$fee_orderline->totalpricevatamount = abs( Bambora_Online_Checkout_Currency::convert_price_to_minorunits( ( $fee_tax ), $minorunits, $this->roundingmode ) );
					$fee_orderline->vat                 = round( (float) ( abs( $fee_tax ) > 0 ? ( ( abs( $fee_tax ) / abs( $fee_total ) ) ) * 100 : 0 ), 2, Bambora_Online_Checkout_Currency::roundingmode( $this->roundingmode ) );
					$fee_orderline->unitpriceinclvat    = abs( Bambora_Online_Checkout_Currency::convert_price_to_minorunits( ( $fee_total + $fee_tax ), $minorunits, $this->roundingmode ) );
					$fee_orderline->unitprice           = abs( Bambora_Online_Checkout_Currency::convert_price_to_minorunits( ( $fee_total ), $minorunits, $this->roundingmode ) );
					$fee_orderline->unitpricevatamount  = abs( Bambora_Online_Checkout_Currency::convert_price_to_minorunits( ( $fee_tax ), $minorunits, $this->roundingmode ) );
					$bambora_refund_lines[]             = $fee_orderline;
					$items_total                        += $fee_total + $fee_tax;
				}
			}
			if ( $items_total < $total ) {
				return false;
			} elseif ( $items_total > $total ) {
				$reason                                         = $refund->get_reason();
				$additional_refund_orderline                    = new Bambora_Online_Checkout_Orderline();
				$additional_refund_orderline->id                = __( 'Refund', 'bambora-online-checkout' );
				$additional_refund_orderline->linenumber        = ++ $line_number;
				$additional_refund_orderline->description       = __( 'Refund', 'bambora-online-checkout' ) . ( $reason !== '' ? ': ' . $reason : '' );
				$additional_refund_orderline->text              = __( 'Refund', 'bambora-online-checkout' );
				$additional_refund_orderline->quantity          = 1;
				$additional_refund_orderline->unit              = __( 'pcs.', 'bambora-online-checkout' );
				$additional_refund_orderline->totalpriceinclvat = abs( Bambora_Online_Checkout_Currency::convert_price_to_minorunits( ( $total - $items_total ), $minorunits, $this->roundingmode ) );
				$additional_refund_orderline->unitpriceinclvat  = $additional_refund_orderline->totalpriceinclvat;
				$additional_refund_orderline->vat               = 0;
				$bambora_refund_lines[]                         = $additional_refund_orderline;
			}

			return true;
		}

		/**
		 * Bambora Meta Boxes
		 */
		public function bambora_online_checkout_meta_boxes() {
			global $post;

			if ( ! isset( $post ) ) { //HPOS might be used
				$order = wc_get_order();
				if ( ! $order ) { //in case of not on order page.
					return;
				}
				$order_id = $order->get_id();
				$status   = $order->get_status();
			} else {
				$order_id = $post->ID;
				$status   = $post->post_status;
				$order    = wc_get_order( $order_id );
			}
			if ( ! $order ) {
				return;
			}
			$order_total    = $order->get_total();
			$transaction_id = Bambora_Online_Checkout_Helper::get_bambora_online_checkout_transaction_id( $order );

			//Only show create payment request if the order is created, has a total over 0 and does not have another payment method selected and no other transaction
			if ( $status != "" && $status != 'auto-draft' && $order_total > 0 && ! $this->order_has_other_payment_method( $order_id ) && strlen( $transaction_id ) <= 0 ) {
				$api_key = $this->get_api_key( $order_id );
				$api     = new Bambora_Online_Checkout_Api( $api_key );
				if ( $api->check_if_merchant_has_payment_request_permissions() ) {
					add_meta_box( 'bambora-paymentrequest-actions', __( 'Worldline Checkout Payment Request', 'bambora-online-checkout' ), array(
						&$this,
						'bambora_online_checkout_meta_box_payment_request'
					), 'shop_order', 'side', 'default' );
					add_meta_box( 'bambora-paymentrequest-actions', __( 'Worldline Checkout Payment Request', 'bambora-online-checkout' ), array(
						&$this,
						'bambora_online_checkout_meta_box_payment_request'
					), 'woocommerce_page_wc-orders', 'side', 'default' );

				}
			}
			if ( ! $this->module_check( $order_id ) ) {
				return;
			}
			add_meta_box( 'bambora-payment-actions', __( 'Worldline Checkout', 'bambora-online-checkout' ), array(
				&$this,
				'bambora_online_checkout_meta_box_payment'
			), 'shop_order', 'side', 'high' );
			if ( true ) {
				add_meta_box( 'bambora-payment-actions', __( 'Worldline Checkout', 'bambora-online-checkout' ), array(
					&$this,
					'bambora_online_checkout_meta_box_payment'
				), 'woocommerce_page_wc-orders', 'side', 'high' );
			}

		}


		public function bambora_online_checkout_meta_box_payment_request() {
			global $post;

			if ( ! isset( $post ) ) {
				$order = wc_get_order();
				if ( ! $order ) {
					return;
				}
				$order_id = $order->get_id();
			} else {
				$order_id = $post->ID;
				$order    = wc_get_order( $order_id );
			}

			$payment_request_id  = $order->get_meta( 'bambora_paymentrequest_id' );
			$payment_request_url = $order->get_meta( 'bambora_paymentrequest_url' );

			if ( isset( $payment_request_id ) && $payment_request_id != "" ) { //A payment request is already created for this order.
				$api_key = $this->get_api_key( $order_id );
				$api     = new Bambora_Online_Checkout_Api( $api_key );
				$pr      = $api->getPaymentRequest( $payment_request_id );
				$html    = '<div class="bambora_paymentrequest_action_container">';
				$html    .= '<div class="bambora_paymentrequest_details">';
				$html    .= '<h3>' . __( 'Payment Request Details', 'bambora-online-checkout' ) . '</h3>';
				$html    .= '<div class="bambora_pr_label">' . __( 'Payment Request ID', 'bambora-online-checkout' ) . ':</div>';
				$html    .= '<div class="bambora_pr_info"> ' . $payment_request_id . '</a></div><br/>';

				if ( isset( $pr->createddate ) ) {
					$datetime = new DateTime( $pr->createddate );
					$timezone = get_option( 'timezone_string' );
					$datetime->setTimezone( new DateTimeZone( $timezone ) );
					$date_format = get_option( 'date_format' );
					$time_format = get_option( 'time_format' );
					// Format the DateTime object to the desired date and time formats
					$formatted_date = $datetime->format( $date_format );
					$formatted_time = $datetime->format( $time_format );
					$created_date   = $formatted_date . ' ' . $formatted_time;
				} else {
					$created_date = "N/A";
				}
				$html .= '<div class="bambora_pr_label">' . __( 'Created', 'bambora-online-checkout' ) . ':</div><div class="bambora_pr_info"> ' . $created_date . '</a></div><br/>';
				$html .= '<div class="bambora_pr_label">' . __( 'Description', 'bambora-online-checkout' ) . ':</div><div class="bambora_pr_info"> ' . $pr->description . '</a></div><br/>';
				$html .= '<div class="bambora_pr_label">' . __( 'Status', 'bambora-online-checkout' ) . ':</div><div class="bambora_pr_info ">' . ucfirst( $pr->status ) . '</div><br/>';
				$html .= '<div class="bambora_pr_label">' . __( 'Reference', 'bambora-online-checkout' ) . ':</div><div class="bambora_pr_info"> ' . $pr->reference . '</a></div><br/>';

				$html .= '<div class="bambora_pr_label">' . __( 'Payment Request URL', 'bambora-online-checkout' ) . ':</div><div class="bambora_pr_info"><a href="' . $payment_request_url . '" target="_blank">' . $payment_request_url . '</a></div>';
				$html .= '<div class="bambora_pr_label"></div><div class="bambora_pr_info"></div>';
				$html .= '</div>';
				$html .= '</div>';

				if ( $pr->status != "closed" ) {
					$html .= '<div class="bambora_paymentrequest_action_container">';

					$html .= '<div class="bambora_paymentrequest_action bambora_paymentrequest_details">';
					$html .= '<input type="hidden" id="bambora_pr_id" name="bambora_pr_id" value="' . $payment_request_id . '" />';
					$html .= '<input type="hidden" id="bambora_delete_pr_message" name="bambora_delete_pr_message" value="' . __( 'Are you sure you want to delete this payment request?', 'bambora-online-checkout' ) . '" />';
					$html .= '<h3>' . __( 'Delete Payment Request', 'bambora-online-checkout' ) . '</h3>';
					$html .= '<input id="bambora_delete_pr_submit" class="button delete" name="bambora_delete_pr_submit" type="submit" value="' . __( 'Delete Payment Request', 'bambora-online-checkout' ) . '" />';
					wp_nonce_field( 'bambora_process_paymentrequest_action', 'bambora_nonce' );

					$html .= '</div><br/>';

					$html         .= '<div class="bambora_paymentrequest_action bambora_paymentrequest_details">';
					$html         .= '<h3 class="bambora_payment_request">' . __( 'Send Payment Request', 'bambora-online-checkout' ) . '</h3>';
					$current_user = wp_get_current_user();

					$customer_id    = $order->get_customer_id();
					$customer       = new WC_Customer( $customer_id );
					$customer_email = $customer->get_email();
					$customer_name  = $customer->get_first_name() . " " . $customer->get_last_name();

					$html .= '<input type="hidden" id="bambora_send_pr_message" name="bambora_send_pr_message" value="' . sprintf( __( 'Are you sure that you want to send the Payment Request to %s, requesting an amount of %s ?', 'bambora-online-checkout' ), $customer_email, $order->get_currency() . " " . $order->get_total() ) . '" />';
					$html .= '<input type="hidden" id="bambora_pr_id" name="bambora_pr_id" value="' . $payment_request_id . '" />';
					$html .= '<label class="bambora_pr_label" for="bambora_pr_recipient_email">' . __( 'Recipient Email', 'bambora-online-checkout' ) . ':</label>';
					$html .= '<input type="text" id="bambora_pr_recipient_email" ' . 'value="' . $customer_email . '"' . ' class="bambora_email" name="bambora_pr_recipient_email" />' . '<br/>';
					$html .= '<label class="bambora_pr_label" for="bambora_pr_recipient_name">' . __( 'Recipient Name', 'bambora-online-checkout' ) . ':</label>';
					$html .= '<input type="text" id="bambora_pr_recipient_name" ' . 'value="' . $customer_name . '"' . ' class="bambora" name="bambora_pr_recipient_name" />' . '<br/>';
					$html .= '<label  class="bambora_pr_label" for="bambora_pr_replyto_email">' . __( 'Reply-To Email', 'bambora-online-checkout' ) . ':</label>';
					$html .= '<input type="text" id="bambora_pr_replyto_email" ' . 'value="' . $current_user->user_email . '"' . ' class="bambora" name="bambora_pr_replyto_email" />' . '<br/>';
					$html .= '<label class="bambora_pr_label" for="bambora_pr_replyto_name">' . __( 'Reply-To Name', 'bambora-online-checkout' ) . ':</label>';
					$html .= '<input type="text" id="bambora_pr_replyto_name" ' . 'value="' . $current_user->first_name . ' ' . $current_user->last_name . '"' . ' class="bambora" name="bambora_pr_replyto_name" />' . '<br/>';
					$html .= '<label  class="bambora_pr_label" for="bambora_pr_email_message">' . __( 'Message', 'bambora-online-checkout' ) . ':</label>';
					$html .= '<input type="text" id="bambora_pr_email_message" ' . 'value="' . '"' . ' class="bambora" name="bambora_pr_email_message" />' . '<br/>';
					$html .= '<input id="bambora_send_pr_submit" class="button delete" name="bambora_send_pr_submit" type="submit" value="' . __( 'Send Payment Request by Email', 'bambora-online-checkout' ) . '" />';

					$html .= '</div>';
				}

				$html .= '</div>';
				$html .= '<br />';
				echo ent2ncr( $html );
			} else {
				$amount = $order->get_total();

				$html = '<div class="bambora_info">';
				$html .= '<div class="bambora_paymentrequest_action_container">';
				$html .= '<input type="hidden" id="bambora_create_pr_message" name="bambora_create_pr_message" value="' . __( 'Are you sure you want to create a payment request?', 'bambora-online-checkout' ) . '" />';
				$html .= '<div class="bambora_paymentrequest_action">';
				$html .= '<h3>' . __( 'Create Payment Request for Order', 'bambora-online-checkout' ) . " " . $order->get_order_number() . '</h3>';
				$html .= '<div>' . __( 'Once you have created the Payment Request, you will be able to send it directly to the customer.', 'bambora-online-checkout' ) . '</div>';
				$html .= '<label  class="bambora_pr_label" for="bambora_pr_amount">' . __( 'Order Amount in Request', 'bambora-online-checkout' ) . '</label>';
				$html .= $order->get_currency() . " " . $amount . '<br/>';
				$html .= '<label  class="bambora_pr_label" for="bambora_pr_description">' . __( 'Description', 'bambora-online-checkout' ) . ':</label>';
				$html .= '<input type="text" id="bambora_pr_description" ' . 'value=""' . ' class="bambora" name="bambora_pr_description" />' . '<br/>';
				$html .= '<input id="bambora_create_pr_submit" class="button create" name="bambora_create_pr_submit" type="submit" value="' . __( 'Create Payment Request', 'bambora-online-checkout' ) . '" />';
				wp_nonce_field( 'bambora_process_paymentrequest_action', 'bambora_nonce' );
				$html .= '</div>';
				$html .= '</div>';
				$html .= '<br />';
				$html = $html . '</div>';
				echo ent2ncr( $html );
			}

		}


		/**
		 * Generate the Bambora payment meta box and echos the HTML
		 */
		public function bambora_online_checkout_meta_box_payment() {
			global $post;

			if ( ! isset( $post ) ) { //HPOS might be used
				$order = wc_get_order();
				if ( ! $order ) {
					return;
				}
				$order_id = $order->get_id();
			} else {
				$order_id = $post->ID;
				$order = wc_get_order($order_id);
			}

			if ( ! empty( $order ) ) {
				$transaction_id = Bambora_Online_Checkout_Helper::get_bambora_online_checkout_transaction_id( $order );
				if ( strlen( $transaction_id ) > 0 ) {
					$html = '';
					try {
						$api_key = $this->get_api_key( $order_id );
						$api     = new Bambora_Online_Checkout_Api( $api_key );

						$transaction_response = $api->get_transaction( $transaction_id );

						if ( ! isset( $transaction_response ) || ! $transaction_response->meta->result ) {
							$get_transaction_error = isset( $transaction_response ) ? $transaction_response->meta->message->merchant : __( 'No connection to Bambora', 'bambora-online-checkout' );
							$message               = sprintf( __( 'Get transaction failed for order %s - %s', 'bambora-online-checkout' ), $order_id, $get_transaction_error );
							echo Bambora_Online_Checkout_Helper::message_to_html( Bambora_Online_Checkout_Helper::ERROR, $message );
							$this->_boc_log->add( $message );

							return null;
						}

						$transaction           = $transaction_response->transaction;
						$minorunits            = $transaction->currency->minorunits;
						$total_authorized      = Bambora_Online_Checkout_Currency::convert_price_from_minorunits( $transaction->total->authorized, $minorunits );
						$total_captured        = Bambora_Online_Checkout_Currency::convert_price_from_minorunits( $transaction->total->captured, $minorunits );
						$available_for_capture = Bambora_Online_Checkout_Currency::convert_price_from_minorunits( $transaction->available->capture, $minorunits );

						$total_credited      = Bambora_Online_Checkout_Currency::convert_price_from_minorunits( $transaction->total->credited, $minorunits );
						$can_delete          = $transaction->candelete;
						$currency_code       = $transaction->currency->code;
						$card_group_id       = $transaction->information->paymenttypes[0]->groupid;
						$card_name           = $transaction->information->paymenttypes[0]->displayname;
						$paymentTypesGroupId = $transaction->information->paymenttypes[0]->groupid;
						$paymentTypesId      = $transaction->information->paymenttypes[0]->id;
						if ( $paymentTypesGroupId == 19 && $paymentTypesId == 40 ) { // Collector Bank (from 1st September 2021 called Walley)
							$isCollector    = true;
							$collectorClass = "isCollectorTrue";
						} else {
							$isCollector    = false;
							$collectorClass = "isCollectorFalse";
						}
						$user = wp_get_current_user();
						if ( in_array( $this->rolecapturerefunddelete, (array) $user->roles ) || in_array( 'administrator', (array) $user->roles ) ) {
							// The user has the role required for  "Capture, Refund, Delete"  and can perform those actions.
							$canCaptureRefundDelete = true;
						} else {
							// The user can only view the data.
							$canCaptureRefundDelete = false;
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

						$html = '<div id="' . $collectorClass . '"></div>';

						$html .= '<div class="bambora_info">';
						$html .= '<img class="bambora_paymenttype_img" src="https://d3r1pwhfz7unl9.cloudfront.net/paymentlogos/' . $card_group_id . '.svg" alt="' . $card_name . '" title="' . $card_name . '" />';
						if ( isset( $transaction_operations[0]->transactionoperations[0]->acquirerdata[0]->key ) ) {
							if ( $transaction_operations[0]->transactionoperations[0]->acquirerdata[0]->key == "nordeaepaymentfi.customerbank" ) {
								$bank_name = $transaction_operations[0]->transactionoperations[0]->acquirerdata[0]->value;
							}
							if ( isset( $bank_name ) && $bank_name != "" ) {
								$html .= '<br/><img style="max-width: 65px;max-height:25px;float: right;clear: both;" src="https://d3r1pwhfz7unl9.cloudfront.net/paymentlogos/bank-' . $bank_name . '.svg" alt="' . $bank_name . '" title="' . $bank_name . '" />';
							}
						}
						if ( isset( $transactionInfo["information"]["wallets"][0]["name"] ) ) {
							$wallet_name = $transactionInfo["information"]["wallets"][0]["name"];
							if ( $wallet_name == "MobilePay" ) {
								$wallet_img = "13.svg";
							}
							if ( $wallet_name == "Vipps" ) {
								$wallet_img = "14.svg";
							}
							if ( $wallet_name == "GooglePay" ) {
								$wallet_img  = "22.svg";
								$wallet_name = "Google Pay";
							}
							if ( $wallet_name == "ApplePay" ) {
								$wallet_img  = "21.svg";
								$wallet_name = "Apple Pay";
							}
							if ( isset( $wallet_img ) ) {
								$html .= '&nbsp;<img style="max-height:51px;clear: both;" src="https://d3r1pwhfz7unl9.cloudfront.net/paymentlogos/' . $wallet_img . '" alt="' . $wallet_name . '" title="' . $wallet_name . '" />';
							}
						}

						$html .= '<div class="bambora_transactionid">';
						$html .= '<p>' . __( 'Transaction ID', 'bambora-online-checkout' ) . '</p>';
						$html .= '<p>' . $transaction->id . '</p>';
						$html .= '</div>';
						$html .= '<div class="bambora_paymenttype">';
						$html .= '<p>' . __( 'Payment Type', 'bambora-online-checkout' ) . '</p>';
						$html .= '<p>' . $card_name . '</p>';
						$html .= '</div>';

						if ( isset( $transaction->information->ecis ) ) {
							$lowestECI = $this->getLowestECI( $transaction->information->ecis );
							if ( isset( $lowestECI ) && $lowestECI != "" ) {
								$html .= '<div class="bambora_paymenttype">';
								$html .= '<p>' . __( 'ECI', 'bambora-online-checkout' ) . '</p>';
								$html .= '<p> <span title="' . Bambora_Online_Checkout_Helper::get3DSecureText( $lowestECI ) . '">' . $lowestECI . '</span></p>';
								$html .= '</div>';
							}
						}
						if ( isset( $transaction->information->acquirerreferences ) && isset( $transaction->information->acquirerreferences[0]->reference ) ) {
							$acquirer_reference = $transaction->information->acquirerreferences[0]->reference;
							if ( isset( $acquirer_reference ) && $acquirer_reference != "" ) {
								$html .= '<div class="bambora_paymenttype">';
								$html .= '<p>' . __( 'Acquirer Reference', 'bambora-online-checkout' ) . '</p>';
								$html .= '<p>' . $acquirer_reference . '</p>';
								$html .= '</div>';
							}
						}
						if ( isset( $transaction->information->exemptions ) ) {
							$exemptions = $this->getDistinctExemptions( $transaction->information->exemptions );
							if ( isset( $exemptions ) && $exemptions != "" ) {
								$html .= '<div class="bambora_paymenttype">';
								$html .= '<p>' . __( 'Exemptions', 'bambora-online-checkout' ) . '</p>';
								$html .= '<p>' . $exemptions . '</p>';
								$html .= '</div>';
							}
						}
						$html .= '<div class="bambora_info_overview">';
						$html .= '<p>' . __( 'Authorized:', 'bambora-online-checkout' ) . '</p>';
						$html .= '<p>' . wc_format_localized_price( $total_authorized ) . ' ' . $currency_code . '</p>';
						$html .= '</div>';

						$html .= '<div class="bambora_info_overview">';
						$html .= '<p>' . __( 'Captured:', 'bambora-online-checkout' ) . '</p>';
						$html .= '<p>' . wc_format_localized_price( $total_captured ) . ' ' . $currency_code . '</p>';
						$html .= '</div>';

						$html .= '<div class="bambora_info_overview">';
						$html .= '<p>' . __( 'Refunded:', 'bambora-online-checkout' ) . '</p>';
						$html .= '<p>' . wc_format_localized_price( $total_credited ) . ' ' . $currency_code . '</p>';
						$html .= '</div>';

						$html .= '</div>';

						if ( $available_for_capture > 0 || $can_delete === true ) {
							$html .= '<div class="bambora_action_container">';

							if ( 0 < $available_for_capture ) {
								if ( $isCollector ) {
									$tooltip  = __( 'With Payment Provider Walley only full capture is possible here. For partial capture, please use Bambora Merchant Portal.', 'bambora-online-checkout' );
									$readOnly = 'readonly data-toggle="tooltip" title="' . $tooltip . '"';
								} else {
									$readOnly = "";
								}
								$html .= '<input type="hidden" id="bambora_currency" name="bambora_currency" value="' . $currency_code . '">';
								$html .= '<input type="hidden" id="bambora_capture_message" name="bambora_capture_message" value="' . __( 'Are you sure you want to capture the payment?', 'bambora-online-checkout' ) . '" />';
								$html .= '<div class="bambora_action">';
								if ( $canCaptureRefundDelete ) {
									$html .= '<p>' . $currency_code . '</p>';
									$html .= '<input type="text" value="' . $available_for_capture . '"id="bambora_capture_amount" ' . $readOnly . ' class="bambora_amount" name="bambora_amount" />';
									$html .= '<input id="bambora_capture_submit" class="button capture" name="bambora_capture" type="submit" value="' . __( 'Capture', 'bambora-online-checkout' ) . '" />';
								} else {
									$html .= __( 'Your role cannot capture or delete the payment', 'bambora-online-checkout' );
								}
								$html .= '</div>';
								$html .= '<br />';
							}
							if ( $can_delete === true ) {
								$html .= '<input type="hidden" id="bambora_delete_message" name="bambora_delete_message" value="' . __( 'Are you sure you want to delete the payment?', 'bambora-online-checkout' ) . '" />';
								$html .= '<div class="bambora_action">';
								if ( $canCaptureRefundDelete ) {
									$html .= '<input id="bambora_delete_submit" class="button delete" name="bambora_delete" type="submit" value="' . __( 'Delete', 'bambora-online-checkout' ) . '" />';
								}
								$html .= '</div>';
							}
							wp_nonce_field( 'bambora_process_payment_action', 'bambora_nonce' );
							$html            .= '</div>';
							$warning_message = __( 'The amount you entered was in the wrong format.', 'bambora-online-checkout' );

							$html .= '<div id="bambora-format-error" class="bambora bambora_error"><strong>' . __( 'Warning', 'bambora-online-checkout' ) . ' </strong>' . $warning_message . '<br /><strong>' . __( 'Correct format is: 1234.56', 'bambora-online-checkout' ) . '</strong></div>';
						}

						$html .= $this->build_transaction_log_table( $transaction_operations, $minorunits );
						$html .= '<br />';

						echo ent2ncr( $html );
					} catch ( Exception $e ) {
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
				$message = sprintf( __( 'Could not load the order with order id %s', 'bambora-online-checkout' ), $order_id );
				echo $message;
				$this->_boc_log->add( $message );
			}
		}

		/**
		 * Build transaction log table HTML
		 *
		 * @param array $operations
		 * @param int $minorunits
		 *
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
		 * @param int $minorunits
		 *
		 * @return string
		 */
		protected function build_transaction_log_rows( $operations, $minorunits ) {
			$html = '';
			foreach ( $operations as $operation ) {
				$eventInfo      = Bambora_Online_Checkout_Helper::getEventText( $operation );
				$eventInfoExtra = '';

				if ( $operation->status != 'approved' ) {
					$eventInfoExtra = $this->get_event_extra( $operation );
					$eventInfoExtra = '<div style="color:#ec6459;">' . $eventInfoExtra . '</div>';
				}
				if ( $eventInfo['description'] != null ) {
					$html .= '<tr class="bambora_transaction_row_header">';
					$html .= '<td>' . Bambora_Online_Checkout_Helper::format_date_time( $operation->createddate ) . '</td>';
					$html .= '</tr>';

					$html .= '<tr class="bambora_transaction_header">';
					$html .= '<td>' . $eventInfo['title'] . '</td>';

					$amount = Bambora_Online_Checkout_Currency::convert_price_from_minorunits( $operation->amount, $minorunits );
					if ( $amount > 0 ) {
						$html .= '<td>' . wc_format_localized_price( $amount ) . ' ' . $operation->currency->code . '</td>';
					} else {
						$html .= '<td>-</td>';
					}

					$html .= '</tr>';
					$html .= '<tr class="bambora_transaction_description">';
					$html .= '<td colspan="2">' . $eventInfo['description'] . $eventInfoExtra . '</td>';

					$html .= '</tr>';

					if ( isset( $operation->transactionoperations ) && count( $operation->transactionoperations ) > 0 ) {
						$html .= $this->build_transaction_log_rows( $operation->transactionoperations, $minorunits );
					}
				} elseif ( isset( $operation->transactionoperations ) && count( $operation->transactionoperations ) > 0 ) {
					$html .= $this->build_transaction_log_rows( $operation->transactionoperations, $minorunits );
				}
			}
			$html = str_replace( "CollectorBank", "Walley", $html );

			return $html;
		}

		protected function get_event_extra( $operation ) {
			$source     = $operation->actionsource;
			$actionCode = $operation->actioncode;
			global $post;
			if ( ! isset( $post ) ) {
				$order = wc_get_order();
				$id    = $order->get_order_id();
			} else {
				$id = $post->ID;
			}

			$webservice    = new Bambora_Online_Checkout_Api( $this->get_api_key( $id ) );
			$responseCode  = $webservice->get_response_code_data( $source, $actionCode );
			$merchantLabel = "";
			if ( isset( $responseCode->responsecode ) ) {
				$merchantLabel = $responseCode->responsecode->merchantlabel . " - " . $source . " " . $actionCode;
			}

			return $merchantLabel;
		}

		private function getDistinctExemptions( $exemptions ) {
			$exemptionValues = null;
			foreach ( $exemptions as $exemption ) {
				$exemptionValues[] = $exemption->value;
			}

			return implode( ",", array_unique( $exemptionValues ) );
		}

		private function getLowestECI( $ecis ) {
			foreach ( $ecis as $eci ) {
				$eciValues[] = $eci->value;
			}

			return min( $eciValues );
		}


		/**
		 * Worldline Checkout Actions
		 */
		public function bambora_online_checkout_actions() {
			if ( isset( $_GET['bambora_action'] ) && isset( $_GET['bambora_nonce'] ) && wp_verify_nonce( $_GET['bambora_nonce'], 'bambora_process_payment_action' ) ) {
				$params = $_GET;

				$order_id = $params['post'] ?? $params['id'];
				$currency = $params['currency'];
				$amount   = $params['amount'];
				$action   = $params['bambora_action'];

				$action_result = null;
				try {
					switch ( $action ) {
						case 'capture':
							$action_result = $this->bambora_online_checkout_capture_payment( $order_id, $amount, $currency );
							break;
						case 'refund':
							$action_result = $this->bambora_online_checkout_refund_payment( $order_id, $amount, $currency );
							break;
						case 'delete':
							$action_result = $this->bambora_online_checkout_delete_payment( $order_id );
							break;
					}
				} catch ( Exception $ex ) {
					$action_result = new WP_Error( 'bambora_online_checkout_error', $ex->getMessage() );
				}

				if ( is_wp_error( $action_result ) ) {
					$message = $action_result->get_error_message( 'bambora_online_checkout ' );
					$this->_boc_log->add( $message );
					Bambora_Online_Checkout_Helper::add_admin_notices( Bambora_Online_Checkout_Helper::ERROR, $message );
				} else {
					global $post;

					$message = sprintf( __( 'The %s action was a success for order %s', 'bambora-online-checkout' ), $action, $order_id );
					Bambora_Online_Checkout_Helper::add_admin_notices( Bambora_Online_Checkout_Helper::SUCCESS, $message, true );
					if ( ! isset( $post ) ) {
						$url = admin_url( 'admin.php?page=wc-orders&action=edit&id=' . $order_id );
					} else {
						$url = admin_url( 'post.php?post=' . $post->ID . '&action=edit' );
					}

					wp_safe_redirect( $url );
				}
			}
		}

		/**
		 * Worldline Checkout Payment Request Actions
		 */
		public function bambora_online_checkout_paymentrequest_actions() {
			if ( isset( $_GET['bambora_paymentrequest_action'] ) && isset( $_GET['bambora_nonce'] ) && wp_verify_nonce( $_GET['bambora_nonce'], 'bambora_process_paymentrequest_action' ) ) {
				$params = $_GET;

				$order_id = $params['post'] ?? $params['id'];

				$amount             = $params['amount'] ?? 0;
				$description        = sanitize_text_field( $params['description'] ) ?? "";
				$recipient_name     = sanitize_text_field( $params['recipient_name'] ) ?? "";
				$recipient_email    = sanitize_text_field( $params['recipient_email'] ) ?? "";
				$action             = sanitize_text_field( $params['bambora_paymentrequest_action'] );
				$payment_request_id = $params['payment_request_id'] ?? "";
				$replyto_name       = sanitize_text_field( $params['replyto_name'] ) ?? "";
				$replyto_email      = sanitize_text_field( $params['replyto_email'] ) ?? "";
				$email_message      = sanitize_text_field( $params['email_message'] ) ?? "";

				$action_result = null;
				try {
					switch ( $action ) {
						case 'create_pr':
							$action_result = $this->bambora_create_paymentrequest( $order_id, $amount, $description );
							break;
						case 'delete_pr':
							$action_result = $this->bambora_delete_paymentrequest( $order_id, $payment_request_id );
							break;
						case 'send_pr':
							$action_result = $this->bambora_send_paymentrequest( $order_id, $recipient_name, $recipient_email, $replyto_name, $replyto_email, $email_message );
							break;
					}
				} catch ( Exception $ex ) {
					$action_result = new WP_Error( 'bambora_online_checkout_error', $ex->getMessage() );
				}

				if ( is_wp_error( $action_result ) ) {
					$message = $action_result->get_error_message( 'bambora_online_checkout ' );
					$this->_boc_log->add( $message );
					Bambora_Online_Checkout_Helper::add_admin_notices( Bambora_Online_Checkout_Helper::ERROR, $message );
				} else {
					global $post;
					$message = sprintf( __( 'The %s action was a success for order %s', 'bambora-online-checkout' ), $action, $order_id );
					Bambora_Online_Checkout_Helper::add_admin_notices( Bambora_Online_Checkout_Helper::SUCCESS, $message, true );
					if ( ! isset( $post ) ) {
						$url = admin_url( 'admin.php?page=wc-orders&action=edit&id=' . $order_id );
					} else {
						$url = admin_url( 'post.php?post=' . $post->ID . '&action=edit' );
					}
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
		 *
		 * @return bool|WP_Error
		 */
		public function bambora_online_checkout_capture_payment( $order_id, $amount, $currency ) {
			$order = wc_get_order( $order_id );
			if ( empty( $currency ) ) {
				$currency = $order->get_currency();
			}
			$minorunits           = Bambora_Online_Checkout_Currency::get_currency_minorunits( $currency );
			$amount               = str_replace( ',', '.', $amount );
			$amount_in_minorunits = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $amount, $minorunits, $this->roundingmode );
			$transaction_id       = Bambora_Online_Checkout_Helper::get_bambora_online_checkout_transaction_id( $order );
			$webservice           = new Bambora_Online_Checkout_Api( $this->get_api_key( $order_id ) );
			$capture_response     = $webservice->capture( $transaction_id, $amount_in_minorunits, $currency, null );

			if ( isset( $capture_response ) && $capture_response->meta->result ) {
				do_action( 'bambora_online_checkout_after_capture', $order_id );

				return true;
			} else {
				$messageReason = isset( $capture_response ) ? $capture_response->meta->message->merchant : __( 'No connection to Bambora', 'bambora-online-checkout' );
				$message       = sprintf( __( 'Capture action failed for order %s - %s', 'bambora-online-checkout' ), $order_id, $messageReason );
				$this->_boc_log->add( $message );

				return new WP_Error( 'bambora_online_checkout_error', $message );
			}
		}

		/**
		 * Refund a payment
		 *
		 * @param mixed $order_id
		 * @param mixed $amount
		 * @param mixed $currency
		 *
		 * @return bool|WP_Error
		 */
		public function bambora_online_checkout_refund_payment( $order_id, $amount, $currency ) {
			$order = wc_get_order( $order_id );
			if ( empty( $currency ) ) {
				$currency = $order->get_currency();
			}
			$minorunits           = Bambora_Online_Checkout_Currency::get_currency_minorunits( $currency );
			$amount               = str_replace( ',', '.', $amount );
			$amount_in_minorunits = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $amount, $minorunits, $this->roundingmode );
			$transaction_id       = Bambora_Online_Checkout_Helper::get_bambora_online_checkout_transaction_id( $order );
			$api_key              = $this->get_api_key( $order_id );
			$api                  = new Bambora_Online_Checkout_Api( $api_key );

			$transaction_response = $api->get_transaction( $transaction_id );

			if ( ! isset( $transaction_response ) || ! $transaction_response->meta->result ) {
				$get_transaction_error = isset( $transaction_response ) ? $transaction_response->meta->message->merchant : __( 'No connection to Bambora', 'bambora-online-checkout' );
				$message               = sprintf( __( 'Get transaction failed for order %s - %s', 'bambora-online-checkout' ), $order_id, $get_transaction_error );
				$this->_boc_log->add( $message );

				return new WP_Error( 'bambora_online_checkout_error', $message );
			}

			$transaction = $transaction_response->transaction;

			$paymentTypesGroupId = $transaction->information->paymenttypes[0]->groupid;
			$paymentTypesId      = $transaction->information->paymenttypes[0]->id;
			if ( $paymentTypesGroupId == 19 && $paymentTypesId == 40 ) { // Collector Bank (from 1st September 2021 called Walley)
				$isCollector = true;
			} else {
				$isCollector = false;
			}

			$refunds     = $order->get_refunds();
			$order_total = $order->get_total();

			$credit_response = null;
			$webservice      = new Bambora_Online_Checkout_Api( $this->get_api_key( $order_id ) );
			if ( $amount === $order_total ) { // Do not send credit lines when crediting full amount
				$credit_response = $webservice->credit( $transaction_id, $amount_in_minorunits, $currency, null );
			} else {
				/** @var Bambora_Online_Checkout_Orderline[] */
				$bambora_refund_lines = array();
				if ( ! $this->create_bambora_refund_lines( $refunds[0], $bambora_refund_lines, $minorunits, $order, $isCollector ) ) {
					$bambora_refund_lines = null;
				}
				$credit_response = $webservice->credit( $transaction_id, $amount_in_minorunits, $currency, $bambora_refund_lines );
			}

			if ( isset( $credit_response ) && $credit_response->meta->result ) {
				do_action( 'bambora_online_checkout_after_refund', $order_id );

				return true;
			} else {
				$messageReason = isset( $credit_response ) ? $credit_response->meta->message->merchant : __( 'No connection to Bambora', 'bambora-online-checkout' );
				$message       = sprintf( __( 'Refund action failed for order %s - %s', 'bambora-online-checkout' ), $order_id, $messageReason );
				$this->_boc_log->add( $message );

				return new WP_Error( 'bambora_online_checkout_error', $message );
			}
		}

		/**
		 * Delete a payment
		 *
		 * @param mixed $order_id
		 *
		 * @return bool|WP_Error
		 */
		public function bambora_online_checkout_delete_payment( $order_id ) {
			$order          = wc_get_order( $order_id );
			$transaction_id = Bambora_Online_Checkout_Helper::get_bambora_online_checkout_transaction_id( $order );
			$webservice     = new Bambora_Online_Checkout_Api( $this->get_api_key( $order_id ) );

			$delete_response = $webservice->delete( $transaction_id );
			if ( isset( $delete_response ) && $delete_response->meta->result ) {
				do_action( 'bambora_online_checkout_after_delete', $order_id );

				return true;
			} else {
				$messageReason = isset( $delete_response ) ? $delete_response->meta->message->merchant : __( 'No connection to Bambora', 'bambora-online-checkout' );
				$message       = sprintf( __( 'Delete action failed - %s', 'bambora-online-checkout' ), $messageReason );
				$this->_boc_log->add( $message );

				return new WP_Error( 'bambora_online_checkout_error', $message );
			}
		}

		public function get_icon() {
			$icon_html = '<img src="' . $this->icon . '" alt="' . $this->method_title . '" width="50"  />';

			return apply_filters( 'woocommerce_gateway_icon', $icon_html, $this->id );
		}

		/**
		 * Get the Bambora Api Key
		 */
		public function get_api_key( $order_id = null ) {
			if ( isset( $order_id ) ) {
				if ( class_exists( 'sitepress' ) ) {
					$order_language = Bambora_Online_Checkout_Helper::getWPMLOrderLanguage( $order_id );
					$merchant       = Bambora_Online_Checkout_Helper::getWPMLOptionValue( 'merchant', $order_language, $this->merchant );
					$accesstoken    = Bambora_Online_Checkout_Helper::getWPMLOptionValue( 'accesstoken', $order_language, $this->accesstoken );
					$secrettoken    = Bambora_Online_Checkout_Helper::getWPMLOptionValue( 'secrettoken', $order_language, $this->secrettoken );

					return Bambora_Online_Checkout_Helper::generate_api_key( $merchant, $accesstoken, $secrettoken );
				}
			}

			return Bambora_Online_Checkout_Helper::generate_api_key( $this->merchant, $this->accesstoken, $this->secrettoken );
		}

		public function module_check( $order_id ) {

			$order          = wc_get_order( $order_id );
			$payment_method = $order->get_meta( '_payment_method', true );

			return $this->id === $payment_method;
		}

		public function order_has_other_payment_method( $order_id ) {

			$order          = wc_get_order( $order_id );
			$payment_method = $order->get_meta( '_payment_method', true );
			if ( $this->id === $payment_method || $payment_method === "" ) {
				return false;
			} else {
				return true;
			}
		}

		public function add_custom_order_column( $columns ) {

			$columns['payment_request_field'] = __( 'Worldline Checkout Payment Request', 'bambora-online-checkout' );

			return $columns;
		}

		public function populate_custom_order_column_hpos( $column, $order ) {

			if ( $column === 'payment_request_field' ) {
				if ( ! isset( $order ) ) {
					return;
				}
				$orderId            = $order->get_id();
				$payment_request_id = $order->get_meta( 'bambora_paymentrequest_id' );
				if ( isset( $payment_request_id ) && $payment_request_id != "" ) {
					$api_key         = Bambora_Online_Checkout::get_instance()->get_api_key( $orderId );
					$api             = new Bambora_Online_Checkout_Api( $api_key );
					$payment_request = $api->getPaymentRequest( $payment_request_id );

					if ( isset( $payment_request->url ) ) {
						echo '<div class="bambora_pr_posts_pr"><span><a href="' . $payment_request->url . '" target="_blank">' . $payment_request_id . '</a></span></div>';
					}
					if ( isset( $payment_request->status ) ) {
						$statusclass = 'bambora_pr_status_' . $payment_request->status;
						echo '<br/><div class="order_status column-order_status ' . $statusclass . '"><span class="pr_status">Status: ' . ucfirst( $payment_request->status ) . '</span></div>';
					}
					if ( isset( $payment_request->description ) ) {
						echo '<br/><span class="bambora_pr_posts_description"> ' . $payment_request->description . '</span>';
					}
				}

			}
		}
	}

	add_filter( 'manage_edit-shop_order_columns', 'add_custom_order_column_posts' );
	add_action( 'manage_shop_order_posts_custom_column', 'populate_custom_order_column_posts' );
	// Load the module into WordPress / WooCommerce
	function add_custom_order_column_posts( $columns ) {

		$columns['payment_request_field'] = __( 'Worldline Checkout Payment Request', 'bambora-online-checkout' );

		return $columns;
	}

	function populate_custom_order_column_posts( $column ) {
		global $post;

		if ( $column === 'payment_request_field' ) {
			if ( isset( $post ) ) {
				$order = wc_get_order( $post->ID );
				if ( ! $order ) {
					return;
				}
			} else {
				return;
			}
			$orderId            = $order->get_id();
			$payment_request_id = $order->get_meta( 'bambora_paymentrequest_id' );
			if ( isset( $payment_request_id ) && $payment_request_id != "" ) {
				$api_key         = Bambora_Online_Checkout::get_instance()->get_api_key( $orderId );
				$api             = new Bambora_Online_Checkout_Api( $api_key );
				$payment_request = $api->getPaymentRequest( $payment_request_id );

				if ( isset( $payment_request->url ) ) {
					echo '<div class="bambora_pr_posts_pr"><span><a href="' . $payment_request->url . '" target="_blank">' . $payment_request_id . '</a></span></div>';
				}
				if ( isset( $payment_request->status ) ) {
					$statusclass = 'bambora_pr_status_' . $payment_request->status;
					echo '<br/><div class="order_status column-order_status ' . $statusclass . '"><span class="pr_status">Status: ' . ucfirst( $payment_request->status ) . '</span></div>';
				}
				if ( isset( $payment_request->description ) ) {
					echo '<br/><span class="bambora_pr_posts_description"> ' . $payment_request->description . '</span>';
				}
			}

		}
	}

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

    $plugin_dir = basename(dirname(__FILE__));
    load_plugin_textdomain(
        'bambora-online-checkout',
        false,
        $plugin_dir . '/languages/'
    );

    add_action('before_woocommerce_init', function () {
        if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'custom_order_tables',
                __FILE__,
                true
            );
        }
    });

    function bambora_online_declare_cart_checkout_blocks_compatibility()
    {
        // Check if the required class exists
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            // Declare compatibility for 'cart_checkout_blocks'
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'cart_checkout_blocks',
                __FILE__,
                true
            );
        }
    }

    // Hook the custom function to the 'before_woocommerce_init' action
    add_action(
        'before_woocommerce_init',
        'bambora_online_declare_cart_checkout_blocks_compatibility'
    );


    // Hook the custom function to the 'woocommerce_blocks_loaded' action
    add_action(
        'woocommerce_blocks_loaded',
        'bambora_online_register_order_approval_payment_method_type'
    );

    /**
     * Custom function to register a payment method type
     */
    function bambora_online_register_order_approval_payment_method_type()
    {
        // Check if the required class exists
        if ( ! class_exists(
            'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType'
        )) {
            return;
        }

        // Include the custom Blocks Checkout class
        require_once plugin_dir_path(
                         __FILE__
                     ) . 'bambora-online-checkout-blocks.php';

        // Hook the registration function to the 'woocommerce_blocks_payment_method_type_registration' action
        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function (
                Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry
            ) {
                // Register an instance
                $payment_method_registry->register(
                    new Bambora_Online_Checkout_Blocks
                );
            }
        );
    }
}
