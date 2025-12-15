<?php

/**
 * Plugin Name: Worldline Online Checkout
 * Plugin URI: https://worldline.com/
 * Description: Worldline Online Checkout Payment Gateway for WooCommerce (prev. Bambora Online Checkout)
 * Version: 8.0.4
 * Author: Bambora
 * Author URI: https://worldline.com/
 * Text Domain: bambora-online-checkout
 * WC requires at least: 8.0
 * WC tested up to: 10.4.2
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @author  Bambora
 * @package bambora_online_checkout
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;
add_action( 'plugins_loaded', 'init_bambora_online_checkout', 0 );

/**
 * Add Worldline Online Checkout
 *
 * @return void
 * @throws Exception - In case of error Throw an Exception.
 */
function init_bambora_online_checkout() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	define( 'BOC_LIB', __DIR__ . '/lib/' );
	define( 'BOC_MODELS', __DIR__ . '/models/' );
	define( 'BOC_VERSION', '8.0.4' );

	// Including Bambora files!
	include BOC_LIB . 'bambora-online-checkout-api.php';
	include BOC_LIB . 'bambora-online-checkout-helper.php';
	include BOC_LIB . 'bambora-online-checkout-currency.php';
	include BOC_LIB . 'bambora-online-checkout-log.php';
	include BOC_MODELS . 'bambora-online-checkout-address.php';
	include BOC_MODELS . 'bambora-online-checkout-callback.php';
	include BOC_MODELS . 'bambora-online-checkout-customer.php';
	include BOC_MODELS . 'bambora-online-checkout-order.php';
	include BOC_MODELS . 'bambora-online-checkout-orderline.php';
	include BOC_MODELS . 'bambora-online-checkout-payment-request-email-recipient-address.php';
	include BOC_MODELS . 'bambora-online-checkout-payment-request-email-recipient.php';
	include BOC_MODELS . 'bambora-online-checkout-payment-request-parameters.php';
	include BOC_MODELS . 'bambora-online-checkout-payment-request.php';
	include BOC_MODELS . 'bambora-online-checkout-request-payment-window.php';
	include BOC_MODELS . 'bambora-online-checkout-request.php';
	include BOC_MODELS . 'bambora-online-checkout-subscription.php';
	include BOC_MODELS . 'bambora-online-checkout-url.php';

	/**
	 * Worldline Online Checkout
	 **/
	class Bambora_Online_Checkout extends WC_Payment_Gateway {
		/**
		 * Singleton instance.
		 *
		 * @var Bambora_Online_Checkout
		 */
		private static $instance;
		/**
		 * Merchant number.
		 *
		 * @var string
		 */
		private $merchant;
		/**
		 * Access Token.
		 *
		 * @var string
		 */
		private $accesstoken;
		/**
		 * Secret Token.
		 *
		 * @var string
		 */
		private $secrettoken;
		/**
		 * MD5 Key.
		 *
		 * @var string
		 */
		private $md5key;
		/**
		 * Payment Window ID.
		 *
		 * @var int
		 */
		private $paymentwindowid;
		/**
		 * Enable Instant Capture - yes or no.
		 *
		 * @var string
		 */
		private $instantcapture;
		/**
		 * Instant Capture on Subscription Renewal - yes or no.
		 *
		 * @var string
		 */
		private $instantcaptureonrenewal;
		/**
		 * Redirect automatic from the payment window when a payment is completed - yes or no.
		 *
		 * @var string
		 */
		private $immediateredirecttoaccept;
		/**
		 * Add Surcharge fees to the shipping amount - yes or no.
		 *
		 * @var string
		 */
		private $addsurchargetoshipment;
		/**
		 * Rounding Mode - Up, Down or Default.
		 *
		 * @var string
		 */
		private $roundingmode;
		/**
		 * Capture a payment when order status is Completed - yes or no.
		 *
		 * @var string
		 */
		private $captureonstatuscomplete;
		/**
		 * Allowed Role to perform transaction operations.
		 *
		 * @var string
		 */
		private $rolecapturerefunddelete;
		/**
		 * Allow Low Value Exemptions - yes or no.
		 *
		 * @var string
		 */
		private $allowlowvalueexemption;
		/**
		 * Limit for Low Value Exemption.
		 *
		 * @var string
		 */
		private $limitforlowvalueexemption;
		/**
		 * Url for terms and conditions.
		 *
		 * @var string
		 */
		private $termsandconditions;
		/**
		 * Module logging.
		 *
		 * @var Bambora_Online_Checkout_Log
		 */
		private $boc_log;

		/**
		 * Icon for Checkout page.
		 *
		 * @var string
		 */
		public $icon_checkout;
		/**
		 *
		 * Returns a new instance of self, if it does not already exist.
		 *
		 * @access public
		 * @static
		 * @return Bambora_Online_Checkout
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor
		 */
		public function __construct() {
			$this->id                 = 'bambora';
			$this->method_title       = 'Worldline Online Checkout';
			$this->method_description = 'Worldline Online Checkout enables easy and secure payments on your shop';
			$this->icon               = WP_PLUGIN_URL . '/' . plugin_basename( __DIR__ ) . '/worldline-logo.svg';
			$this->icon_checkout      = WP_PLUGIN_URL . '/' . plugin_basename( __DIR__ ) . '/worldline-logo-checkout.svg';
			$this->has_fields         = true;
			$this->supports           = array(
				'products',
				'refunds',
				'subscriptions',
				'subscription_cancellation',
				'subscription_suspension',
				'subscription_reactivation',
				'subscription_amount_changes',
				'subscription_date_changes',
				'subscription_payment_method_change_customer',
				'multiple_subscriptions',
			);

			// Initialize Worldline Online Checkout Logging.
			$this->boc_log = new Bambora_Online_Checkout_Log();

			// Load Form fields.
			$this->init_form_fields();

			// Load settings.
			$this->init_settings();

			// Initialize Worldline Online Checkout Settings.
			$this->init_bambora_online_checkout_settings();
		}

		/**
		 * Initialize Worldline Online Checkout Settings
		 */
		public function init_bambora_online_checkout_settings() {
			// Define user set variables!
			$this->enabled                   = array_key_exists( 'enabled', $this->settings ) ? $this->settings['enabled'] : 'yes';
			$this->title                     = array_key_exists( 'title', $this->settings ) ? $this->settings['title'] : 'Worldline Online Checkout';
			$this->description               = array_key_exists( 'description', $this->settings ) ? $this->settings['description'] : 'Pay using Worldline Online Checkout';
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
			// Filters!
			add_filter(
				'woocommerce_payment_gateways',
				function ( $methods ) {
					$methods[] = 'bambora_online_checkout';
					return $methods;
				}
			);

			// Allowed Redirect Hosts.
			add_filter(
				'allowed_redirect_hosts',
				array(
					$this,
					'allowed_redirect_hosts',
				)
			);

			add_action(
				'woocommerce_api_' . strtolower( get_class( $this ) ),
				array(
					$this,
					'bambora_online_checkout_callback',
				)
			);

			// Woocommerce Subscriptions Actions.
			add_action(
				'woocommerce_scheduled_subscription_payment_' . $this->id,
				array(
					$this,
					'scheduled_subscription_payment',
				),
				10,
				2
			);
			add_action(
				'woocommerce_subscription_cancelled_' . $this->id,
				array(
					$this,
					'subscription_cancellation',
				)
			);

			// Register styles!
			add_action(
				'wp_enqueue_scripts',
				array(
					$this,
					'enqueue_wc_bambora_online_checkout_front_styles',
				)
			);

			add_action(
				'init',
				function () {
					load_plugin_textdomain(
						'bambora-online-checkout',
						false,
						dirname( plugin_basename( __FILE__ ) ) . '/languages/'
					);
				}
			);

			add_action(
				'before_woocommerce_init',
				function () {
					if ( class_exists( FeaturesUtil::class ) ) {
						FeaturesUtil::declare_compatibility(
							'custom_order_tables',
							__FILE__,
							true
						);
					}
				}
			);

			// Hook the custom function to the 'before_woocommerce_init' action.
			add_action(
				'before_woocommerce_init',
				array(
					$this,
					'bambora_online_declare_cart_checkout_blocks_compatibility',
				)
			);

			// Hook the custom function to the 'woocommerce_blocks_loaded' action.
			add_action(
				'woocommerce_blocks_loaded',
				array(
					$this,
					'bambora_online_register_order_approval_payment_method_type',
				)
			);

			// Hook for connecting the frontend with the backend.
			add_action(
				'rest_api_init',
				function () {
					register_rest_route(
						'bambora/v1',
						'paymenttypes',
						array(
							'methods'             => 'POST',
							'callback'            => array( $this, 'get_payment_types' ),
							'permission_callback' => '__return_true',
							'args'                => array(
								'amount'   => array( 'required' => true ),
								'currency' => array( 'required' => true ),
							),
						)
					);
				}
			);

			if ( 'yes' === $this->captureonstatuscomplete ) {
				add_action(
					'woocommerce_order_status_completed',
					array(
						$this,
						'bambora_online_checkout_order_status_completed',
					)
				);
			}

			if ( is_admin() ) {
				// Actions.

				add_action(
					'add_meta_boxes',
					array(
						$this,
						'bambora_online_checkout_meta_boxes',
					)
				);
				add_action(
					'woocommerce_update_options_payment_gateways_' . $this->id,
					array(
						$this,
						'process_admin_options',
					)
				);
				add_action(
					'wp_before_admin_bar_render',
					array(
						$this,
						'bambora_online_checkout_actions',
					)
				);
				add_action(
					'wp_before_admin_bar_render',
					array(
						$this,
						'bambora_online_checkout_paymentrequest_actions',
					)
				);
				add_action(
					'admin_notices',
					array(
						$this,
						'bambora_online_checkout_admin_notices',
					)
				);
				add_action(
					'admin_enqueue_scripts',
					array(
						$this,
						'enqueue_wc_bambora_online_checkout_admin_styles_and_scripts',
					)
				);
				add_action(
					'manage_shop_order_posts_custom_column',
					array(
						$this,
						'populate_payment_request_custom_order_column',
					),
					20,
					2
				);
				add_action(
					'manage_woocommerce_page_wc-orders_custom_column',
					array(
						$this,
						'populate_payment_request_custom_order_column',
					),
					20,
					2
				);
				// Filters.

				// Legacy WordPress posts storage.
				add_filter(
					'manage_edit-shop_order_columns',
					array(
						$this,
						'add_custom_order_column',
					),
					20
				);

				// High-performance order storage (HPOS).
				add_filter(
					'manage_woocommerce_page_wc-orders_columns',
					array(
						$this,
						'add_custom_order_column',
					),
					20
				);
			}
		}

		/**
		 * Show messages in the WordPress Administration
		 *
		 * @return void
		 */
		public function bambora_online_checkout_admin_notices() {
			Bambora_Online_Checkout_Helper::echo_admin_notices();
		}

		/**
		 * Enqueue Admin Styles and Scripts
		 *
		 * @return void
		 */
		public function enqueue_wc_bambora_online_checkout_admin_styles_and_scripts() {
			wp_register_style( 'bambora_online_checkout_admin_style', plugins_url( 'assets/style/bambora-online-checkout-admin.css', __FILE__ ), array(), BOC_VERSION );
			wp_enqueue_style( 'bambora_online_checkout_admin_style' );
			// Fix for load of Jquery time!
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'bambora_online_checkout_admin', plugins_url( 'assets/js/bambora-online-checkout-admin.js', __FILE__ ), array(), BOC_VERSION, true );
		}

		/**
		 * Enqueue Frontend Styles and Scripts
		 *
		 * @return void
		 */
		public function enqueue_wc_bambora_online_checkout_front_styles() {
			wp_register_style( 'bambora_online_checkout_front_style', plugins_url( 'assets/style/bambora-online-checkout-front.css', __FILE__ ), array(), BOC_VERSION );
			wp_enqueue_style( 'bambora_online_checkout_front_style' );
		}

		/**
		 * Initialise Gateway Settings Form Fields
		 *
		 * @return void
		 */
		public function init_form_fields() {
			if ( ! function_exists( 'get_editable_roles' ) ) {
				require_once ABSPATH . 'wp-admin/includes/user.php';
			}

			$roles = wp_roles()->roles;

			foreach ( $roles as $role => $details ) {
				$roles_options[ $role ] = translate_user_role( $details['name'] );
			}
			$this->form_fields = array(
				'enabled'                   => array(
					'title'   => 'Activate module',
					'type'    => 'checkbox',
					'label'   => 'Enable Worldline Online Checkout as a payment option.',
					'default' => 'yes',
				),
				'title'                     => array(
					'title'       => 'Title',
					'type'        => 'text',
					'description' => 'The title of the payment method displayed to the customers.',
					'default'     => 'Worldline Online Checkout',
				),
				'description'               => array(
					'title'       => 'Description',
					'type'        => 'textarea',
					'description' => 'The description of the payment method displayed to the customers.',
					'default'     => 'Pay using Worldline Online Checkout',
				),
				'merchant'                  => array(
					'title'       => 'Merchant number',
					'type'        => 'text',
					'description' => 'The number identifying your Worldline merchant account.',
					'default'     => '',
				),
				'accesstoken'               => array(
					'title'       => 'Access token',
					'type'        => 'text',
					'description' => 'The Access token for the API user received from the Worldline administration.',
					'default'     => '',
				),
				'secrettoken'               => array(
					'title'       => 'Secret token',
					'type'        => 'password',
					'description' => 'The Secret token for the API user received from the Worldline administration.',
					'default'     => '',
				),
				'md5key'                    => array(
					'title'       => 'MD5 Key',
					'type'        => 'text',
					'description' => 'The MD5 key is used to stamp data sent between WooCommerce and Worldline to prevent it from being tampered with. The MD5 key is optional but if used here, must be the same as in the Bambora administration.',
					'default'     => '',
				),
				'paymentwindowid'           => array(
					'title'       => 'Payment Window ID',
					'type'        => 'text',
					'description' => 'The ID of the payment window to use.',
					'default'     => '1',
				),
				'instantcapture'            => array(
					'title'       => 'Instant capture',
					'type'        => 'checkbox',
					'description' => 'Capture the payments at the same time they are authorized. In some countries, this is only permitted if the consumer receives the products right away Ex. digital products.',
					'label'       => 'Enable Instant Capture',
					'default'     => 'no',
				),
				'instantcaptureonrenewal'   => array(
					'title'       => 'Instant capture for renewal of Subscriptions',
					'type'        => 'checkbox',
					'description' => 'Capture the payments at the same time they are authorized for recurring payments. In some countries, this is only permitted if the consumer receives the products right away Ex. digital products.',
					'label'       => 'Enable Instant Capture for Renewals',
					'default'     => 'no',
				),
				'immediateredirecttoaccept' => array(
					'title'       => 'Immediate Redirect',
					'type'        => 'checkbox',
					'description' => 'Immediately redirect your customer back to you shop after the payment completed.',
					'label'       => 'Enable Immediate redirect',
					'default'     => 'no',
				),
				'addsurchargetoshipment'    => array(
					'title'       => 'Add Surcharge',
					'type'        => 'checkbox',
					'description' => 'Display surcharge amount on the order as an item',
					'label'       => 'Enable Surcharge',
					'default'     => 'no',
				),
				'captureonstatuscomplete'   => array(
					'title'       => 'Capture on status Completed',
					'type'        => 'checkbox',
					'description' => 'When this is enabled the full payment will be captured when the order status changes to Completed',
					'default'     => 'no',
				),
				'roundingmode'              => array(
					'title'       => 'Rounding mode',
					'type'        => 'select',
					'description' => 'Please select how you want the rounding of the amount sent to the payment system',
					'options'     => array(
						Bambora_Online_Checkout_Currency::ROUND_DEFAULT => 'Default',
						Bambora_Online_Checkout_Currency::ROUND_UP      => 'Always up',
						Bambora_Online_Checkout_Currency::ROUND_DOWN    => 'Always down',
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
					'default'     => 'shop_manager',
				),
				'allowlowvalueexemption'    => array(
					'title'       => 'Enable Low Value Exemption',
					'type'        => 'checkbox',
					'description' => 'Allow you as a merchant to let the customer attempt to skip Strong Customer Authentication(SCA) when the value of the order is below your defined limit. Note: the liability will be on you as a merchant.',
					'default'     => 'no',
				),
				'limitforlowvalueexemption' => array(
					'title'       => 'Max Amount for Low Value Exemption',
					'type'        => 'text',
					'description' => 'Any amount below this max amount might skip SCA if the issuer would allow it. Recommended amount is about €30 in your local currency. <a href="https://developer.bambora.com/europe/checkout/psd2/lowvalueexemption"  target="_blank">See more information here.</a>',
					'default'     => '',
				),
				'termsandconditions'        => array(
					'title'       => 'URL to Terms & Conditions',
					'type'        => 'text',
					'description' => 'If you are using Payment Requests this is where you can set the URL for your Terms & Conditions.',
					'default'     => '',
				),
			);
		}

		/**
		 * Add Allowed Redirect Hosts.
		 *
		 * @param array $hosts - An array of existing allowed hosts.
		 */
		public function allowed_redirect_hosts( $hosts ) {
			$allowed_redirect_hosts = array(
				'v1.checkout.bambora.com',
			);
			return array_merge( $hosts, $allowed_redirect_hosts );
		}

		/**
		 * Admin Panel Options
		 *
		 * @throws Exception - In case of error Throw an Exception.
		 */
		public function admin_options() {
			$version = BOC_VERSION;

			$html = "<h3>Worldline Online Checkout v{$version}</h3>";

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

			$api_key = $this->get_api_key();
			$api     = new Bambora_Online_Checkout_Api( $api_key );
			try {
				$get_merchant_api_permissions_response = $api->get_merchant_api_permissions();
				if ( ! $get_merchant_api_permissions_response->meta->result ) {
					throw new Exception( $get_merchant_api_permissions_response->meta->message->merchant );
				} else {
					$html .= '<b><i>The credentials for your Worldline account are valid.</i></b>';
				}
			} catch ( Exception $e ) {
				$html .= '<b><i>The credentials you have provided for your Worldline account are not valid. Please check them before you enable Worldline as a payment option.</i></b>';
				$this->boc_log->add( "Credential validation failed: {$e->getMessage()}" );
			}
			$html .= '<table class="form-table">';
			// Generate the HTML For the settings form.!
			$html .= $this->generate_settings_html( array(), false );
			$html .= '</table>';
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $html;
		}

		/**
		 * Capture the payment on order status completed
		 *
		 * @param mixed $order_id - Order Id.
		 * @return void
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
				$this->boc_log->add( $message );
				Bambora_Online_Checkout_Helper::add_admin_notices( Bambora_Online_Checkout_Helper::ERROR, $message );
			} else {
				/* translators: %s: search term */
				$message = sprintf( __( 'The Capture action was a success for order %s', 'bambora-online-checkout' ), $order_id );
				Bambora_Online_Checkout_Helper::add_admin_notices( Bambora_Online_Checkout_Helper::SUCCESS, $message );
			}
		}

		/**
		 * Legacy - Set the Payment method description when not using Blocks.
		 *
		 * @return void
		 */
		public function payment_fields() {
			if ( empty( $this->description ) ) {
				return;
			}
			$cart = WC()->cart;
			if ( ! isset( $cart ) ) {
				return;
			}
			$currency          = get_woocommerce_currency();
			$minorunits        = Bambora_Online_Checkout_Currency::get_currency_minorunits( $currency );
			$amount_minorunits = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $cart->total, $minorunits, $this->roundingmode );
			$payment_groups    = $this->get_payment_types(
				array(
					'amount'   => $amount_minorunits,
					'currency' => $currency,
				)
			);
			$html              = $this->description;
			if ( ! empty( $payment_groups ) ) {
				$payment_types_html = '<div class="bambora_payment_types">';
				foreach ( $payment_groups as $cards ) {
					foreach ( $cards as $card ) {
						foreach ( $card->assets as $asset ) {
							if ( 'logo' === $asset->type ) {
								$payment_types_html .= '<img title="' . $card->displayname . '" alt="' . $card->displayname . '" src="' . $asset->data . '" />';
							}
						}
					}
				}
				$payment_types_html .= '</div>';
				$html               .= $payment_types_html;
			}
			$text_replace             = wptexturize( $html );
			$text_remove_double_lines = wpautop( $text_replace );
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $text_remove_double_lines;
		}

		/**
		 * Returns the allowed payment logos based on amount and currency.
		 *
		 * @param array $request - The request containing amount and currency.
		 * @return array - Returns an array of allowed payment groups
		 * @throws Exception - In case of error Throw an Exception.
		 */
		public function get_payment_types( $request ) {
			$response = array();
			try {
				$amout_minorunits           = $request['amount'];
				$currency                   = $request['currency'];
				$api_key                    = $this->get_api_key();
				$api                        = new Bambora_Online_Checkout_Api( $api_key );
				$get_payment_types_response = $api->get_payment_types( $currency, $amout_minorunits );
				if ( ! $get_payment_types_response->meta->result ) {
					throw new Exception( $get_payment_types_response->meta->message->merchant );
				} else {
					foreach ( $get_payment_types_response->paymentcollections as $paymentcollection ) {
						$response[] = $paymentcollection->paymentgroups;
					}
				}
			} catch ( Exception $e ) {
				$this->boc_log->add( "Could not load the payment types: {$e->getMessage()}" );
			}
			return $response;
		}

		/**
		 * Get the Worldline Online Checkout logger
		 *
		 * @return Bambora_Online_Checkout_Log
		 */
		public function get_boc_logger() {
			return $this->boc_log;
		}

		/**
		 * Handle scheduled subscription payments
		 *
		 * @param mixed $amount_to_charge - Amount to Charge.
		 * @param mixed $renewal_order - Order to Renew.
		 * @return bool
		 * @throws Exception - In case of error Throw an Exception.
		 */
		public function scheduled_subscription_payment( $amount_to_charge, $renewal_order ) {
			$result       = false;
			$subscription = Bambora_Online_Checkout_Helper::get_subscriptions_for_renewal_order( $renewal_order );
			if ( Bambora_Online_Checkout_Helper::order_is_subscription( $subscription ) ) {
				$order_note              = '';
				$bambora_subscription_id = Bambora_Online_Checkout_Helper::get_bambora_online_checkout_subscription_id( $subscription );
				if ( empty( $bambora_subscription_id ) ) {
					$order_note = __( 'Worldline Online Checkout Subscription ID was not found', 'bambora-online-checkout' );
				} else {
					$order_currency         = $renewal_order->get_currency();
					$renewal_order_id       = $renewal_order->get_id();
					$minorunits             = Bambora_Online_Checkout_Currency::get_currency_minorunits( $order_currency );
					$amount                 = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $amount_to_charge, $minorunits, $this->roundingmode );
					$instant_capture_amount = 'yes' === $this->instantcaptureonrenewal ? $amount : 0;

					$api_key = $this->get_api_key();
					$api     = new Bambora_Online_Checkout_Api( $api_key );
					try {
						$authorize_subscription_response = $api->authorize_subscription( $bambora_subscription_id, $amount, $order_currency, $renewal_order_id, $instant_capture_amount );
						if ( $authorize_subscription_response->meta->result ) {
							/* translators: %s: search term */
							$order_note = sprintf( __( 'Worldline Online Checkout Subscription was authorized for renewal order %1$s with transaction id %2$s', 'bambora-online-checkout' ), $renewal_order_id, $authorize_subscription_response->transactionid );
							$renewal_order->add_order_note( $order_note );
							$renewal_order->payment_complete( $authorize_subscription_response->transactionid );
							$result = true;
						} else {
							throw new Exception( $authorize_subscription_response->meta->message->merchant );
						}
					} catch ( Exception $e ) {
						/* translators: %s: search term */
						$order_note = sprintf( __( 'Worldline Online Checkout Subscription Id: %1$s could not be renewed - %2$s', 'bambora-online-checkout' ), $bambora_subscription_id, $e->getMessage() );
						$this->boc_log->add( $order_note );
						$renewal_order->update_status( 'failed', $order_note );
					}
				}
				// Remove the Worldline Online Checkout subscription id copied from the subscription.
				delete_post_meta( $renewal_order_id, Bambora_Online_Checkout_Helper::BAMBORA_ONLINE_CHECKOUT_SUBSCRIPTION_ID );
				$subscription->add_order_note( $order_note );
			}
			return $result;
		}

		/**
		 * Cancel a subscription
		 *
		 * @param WC_Subscription $subscription - WC Subscription.
		 * @param bool            $force_delete - Force Delete.
		 * @return bool
		 * @throws Exception - In case of error Throw an Exception.
		 */
		public function subscription_cancellation( $subscription, $force_delete = false ) {
			$result = false;
			if ( Bambora_Online_Checkout_Helper::order_is_subscription( $subscription ) && ( 'cancelled' === $subscription->get_status() || $force_delete ) ) {
					$bambora_subscription_id = Bambora_Online_Checkout_Helper::get_bambora_online_checkout_subscription_id( $subscription );
					$order_note              = '';
				if ( empty( $bambora_subscription_id ) ) {
					$order_note = __( 'Worldline Online Checkout Subscription ID was not found', 'bambora-online-checkout' );
				} else {
					$api_key = $this->get_api_key();
					$api     = new Bambora_Online_Checkout_Api( $api_key );
					try {
						$delete_subscription_response = $api->delete_subscription( $bambora_subscription_id );
						if ( $delete_subscription_response->meta->result ) {
							/* translators: %s: search term */
							$order_note = sprintf( __( 'Subscription successfully Cancelled. - Worldline Online Checkout Subscription Id: %s', 'bambora-online-checkout' ), $bambora_subscription_id );
							$result     = true;
						} else {
							throw new Exception( $delete_subscription_response->meta->message->merchant );
						}
					} catch ( Exception $e ) {
						/* translators: %s: search term */
						$order_note = sprintf( __( 'Worldline Online Checkout Subscription Id: %1$s could not be canceled - %2$s', 'bambora-online-checkout' ), $bambora_subscription_id, $e->getMessage() );
						$this->boc_log->add( $order_note );
					}
				}
					$subscription->add_order_note( $order_note );
			}
			return $result;
		}

		/**
		 * Process the payment and return the result
		 *
		 * @param string $order_id - Order ID.
		 * @return array
		 * @throws Exception - In case of error Throw an Exception.
		 */
		public function process_payment( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( Bambora_Online_Checkout_Helper::order_contains_switch( $order ) && ! $order->needs_payment() ) {
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);
			}
			$message                  = '';
			$api_key                  = $this->get_api_key( $order_id );
			$api                      = new Bambora_Online_Checkout_Api( $api_key );
			$bambora_checkout_request = $this->create_bambora_checkout_request( $order );
			try {
				$set_checkout_session_response = $api->set_checkout_session( $bambora_checkout_request );
				if ( ! $set_checkout_session_response->meta->result ) {
					$message = __( 'Could not retrive the payment window. Reason:', 'bambora-online-checkout' ) . ' ' . $set_checkout_session_response->meta->message->enduser;
					throw new Exception( "Could not retrive the payment window. Reason: {$set_checkout_session_response->meta->message->merchant}" );
				}
				return array(
					'result'   => 'success',
					'redirect' => $set_checkout_session_response->url,
				);
			} catch ( Exception $e ) {
				$this->boc_log->add( $e->getMessage() );
				wc_add_notice( $message, 'error' );

				return array(
					'result'   => 'fail',
					'redirect' => '',
				);
			}
		}

		/**
		 * Create Checkout Request
		 *
		 * @param WC_Order $order - WC Order.
		 * @return Bambora_Online_Checkout_Request
		 */
		protected function create_bambora_checkout_request( $order ) {
			$is_request_to_change_payment_method = Bambora_Online_Checkout_Helper::order_is_subscription( $order );
			$language                            = str_replace( '_', '-', get_locale() );
			if ( 'fi' === $language ) {
				$language = 'fi-FI';
			}
			$payment_window           = new Bambora_Online_Checkout_Request_Payment_Window();
			$payment_window->id       = $this->paymentwindowid;
			$payment_window->language = $language;

			$request                       = new Bambora_Online_Checkout_Request();
			$request->customer             = $this->create_bambora_customer( $order );
			$request->order                = $this->create_bambora_order( $order, $is_request_to_change_payment_method );
			$request->instantcaptureamount = 'yes' === $this->instantcapture ? $request->order->total : 0;
			$request->language             = $language;
			$request->paymentwindow        = $payment_window;
			$request->url                  = $this->create_bambora_url( $order );

			if ( Bambora_Online_Checkout_Helper::woocommerce_subscription_plugin_is_active() && ( Bambora_Online_Checkout_Helper::order_contains_subscription( $order ) || $is_request_to_change_payment_method ) ) {
				$bambora_subscription  = $this->create_bambora_subscription();
				$request->subscription = $bambora_subscription;
			} elseif ( $this->allowlowvalueexemption ) {
				if ( $request->order->total < Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $this->limitforlowvalueexemption, Bambora_Online_Checkout_Currency::get_currency_minorunits( $request->order->currency ), $this->roundingmode ) ) {
					$request->securityexemption = 'lowvaluepayment';
					$request->securitylevel     = 'none';
				}
			}

			return $request;
		}

		/**
		 * Create Bambora customer
		 *
		 * @param WC_Order $order - WC Order.
		 * @return Bambora_Online_Checkout_Customer
		 */
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
		 * @param WC_Order $order - WC Order.
		 * @param bool     $is_request_to_change_payment_method - Request to change Payment Method.
		 * @return Bambora_Online_Checkout_Order
		 */
		protected function create_bambora_order( $order, $is_request_to_change_payment_method ) {
			$currency   = $order->get_currency();
			$minorunits = Bambora_Online_Checkout_Currency::get_currency_minorunits( $currency );

			$bambora_order                  = new Bambora_Online_Checkout_Order();
			$bambora_order->billingaddress  = $this->create_bambora_address( $order );
			$bambora_order->currency        = $currency;
			$bambora_order->id              = $this->clean_order_number( $order->get_order_number() );
			$bambora_order->shippingaddress = $this->create_bambora_address( $order );
			$bambora_order->total           = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $order->get_total(), $minorunits, $this->roundingmode );

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
		 * @param string $order_number - Order Number.
		 * @return string
		 */
		protected function clean_order_number( $order_number ) {
			return preg_replace( '/[^a-z\d ]/i', '', $order_number );
		}

		/**
		 * Create Bambora address
		 *
		 * @param WC_Order $order - WC Order.
		 * @return Bambora_Online_Checkout_Address
		 */
		protected function create_bambora_address( $order ) {
			$bambora_address            = new Bambora_Online_Checkout_Address();
			$bambora_address->att       = '';
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
		 * @param WC_Order $order - WC Order.
		 * @param bool     $is_payment_request - Is Payment Request.
		 * @return Bambora_Online_Checkout_Url
		 */
		protected function create_bambora_url( $order, $is_payment_request = false ) {
			$bambora_url = new Bambora_Online_Checkout_Url();
			$order_id    = $order->get_id();

			if ( ! $is_payment_request ) {
				$bambora_url->accept  = Bambora_Online_Checkout_Helper::get_accept_url( $order );
				$bambora_url->decline = Bambora_Online_Checkout_Helper::get_decline_url( $order );
			}

			$callback                               = new Bambora_Online_Checkout_Callback();
			$callback->url                          = apply_filters( 'bambora_online_checkout_callback_url', Bambora_Online_Checkout_Helper::get_bambora_online_checkout_callback_url( $order_id ) );
			$bambora_url->callbacks                 = array( $callback );
			$bambora_url->immediateredirecttoaccept = 'yes' === $this->immediateredirecttoaccept ? 1 : 0;

			return $bambora_url;
		}

		/**
		 * Create Worldline Online Checkout Payment Request
		 *
		 * @param string $order_id - WC Order Id.
		 * @param string $description - Payment Request Description.
		 * @return bool|WP_Error
		 * @throws Exception - In case of error Throw an Exception.
		 */
		protected function bambora_create_paymentrequest( $order_id, $description ) {
			$order                                    = wc_get_order( $order_id );
			$bambora_paymentrequest                   = new Bambora_Online_Checkout_Payment_Request();
			$bambora_paymentrequest->description      = $description;
			$bambora_paymentrequest->reference        = "WooCommercePaymentRequest{$order_id}";
			$bambora_paymentrequest_parameters        = new Bambora_Online_Checkout_Payment_Request_Parameters();
			$bambora_paymentrequest->termsurl         = '' !== $this->termsandconditions ? $this->termsandconditions : null;
			$bambora_paymentrequest_parameters->order = $this->create_bambora_order( $order, false );
			$bambora_paymentrequest_parameters->url   = $this->create_bambora_url( $order, true );
			$bambora_paymentrequest_payment_window    = new Bambora_Online_Checkout_Request_Payment_Window();
			$bambora_paymentrequest_parameters->instantcaptureamount = 'yes' === $this->instantcapture ? $bambora_paymentrequest_parameters->order->total : 0;
			$bambora_paymentrequest_payment_window->language         = str_replace( '_', '-', get_locale() );
			$bambora_paymentrequest_payment_window->id               = $this->paymentwindowid;
			$bambora_paymentrequest_parameters->paymentwindow        = $bambora_paymentrequest_payment_window;
			$bambora_paymentrequest->parameters                      = $bambora_paymentrequest_parameters;
			if ( $order->get_customer_id() ) {
				$bambora_paymentrequest_parameters->customer = $this->create_bambora_customer( $order );
			}
			$api_key = $this->get_api_key();
			$api     = new Bambora_Online_Checkout_Api( $api_key );
			try {
				$create_payment_request_response = $api->create_payment_request( $bambora_paymentrequest );
				if ( $create_payment_request_response->meta->result ) {
					$order->update_meta_data( 'bambora_paymentrequest_id', $create_payment_request_response->id );
					$order->update_meta_data( 'bambora_paymentrequest_url', $create_payment_request_response->url );
					$order->set_payment_method( $this->id );
					$order->set_payment_method_title( $this->method_title );
					/* translators: %s: search term */
					$note = sprintf( __( 'Worldline Online Checkout Payment Request with id %1$s created for order %2$s', 'bambora-online-checkout' ), $create_payment_request_response->id, $order->get_order_number() );
					$order->add_order_note( $note );
					$order->save();
					do_action( 'bambora_online_checkout_after_create_paymentrequest', $order_id );
					return true;
				} else {
					throw new Exception( $create_payment_request_response->meta->message->merchant );
				}
			} catch ( Exception $e ) {
				/* translators: %s: search term */
				$message = sprintf( __( 'Create payment request failed for order %s', 'bambora-online-checkout' ), $order_id ) . ' - Bambora: ' . $e->getMessage();
				return new WP_Error( 'bambora_online_checkout_error', $message );
			}
		}

		/**
		 * Delete Worldline Online Checkout Payment Request
		 *
		 * @param string $order_id - WC Order Id.
		 * @param string $payment_request_id - Payment Request Id.
		 * @return bool|WP_Error
		 * @throws Exception - In case of error Throw an Exception.
		 */
		protected function bambora_delete_paymentrequest( $order_id, $payment_request_id ) {
			$order   = wc_get_order( $order_id );
			$api_key = $this->get_api_key();
			$api     = new Bambora_Online_Checkout_Api( $api_key );
			try {
				$delete_payment_request_response = $api->delete_payment_request( $payment_request_id );
				if ( $delete_payment_request_response->meta->result ) {
					$order->delete_meta_data( 'bambora_paymentrequest_id' );
					$order->delete_meta_data( 'bambora_paymentrequest_url' );
					/* translators: %s: search term */
					$note = sprintf( __( 'Worldline Online Checkout Payment Request %1$s deleted for order %2$s', 'bambora-online-checkout' ), $payment_request_id, $order->get_order_number() );
					$order->add_order_note( $note );
					$order->save();
					do_action( 'bambora_online_checkout_after_delete_paymentrequest', $order_id );
					return true;
				} else {
					throw new Exception( $delete_payment_request_response->meta->message->merchant );
				}
			} catch ( Exception $e ) {
				/* translators: %s: search term */
				$message = sprintf( __( 'Delete payment request failed for order %s', 'bambora-online-checkout' ), $order_id ) . ' - Bambora: ' . $e->getMessage();
				return new WP_Error( 'bambora_online_checkout_error', $message );
			}
		}

		/**
		 * Send Worldline Online Checkout Payment Request
		 *
		 * @param string $order_id - WC Order Id.
		 * @param string $recipient_name - Recipient Name.
		 * @param string $recipient_email - Recipient Email.
		 * @param string $replyto_name - Reply to Name.
		 * @param string $replyto_email - Reply to Email.
		 * @param string $email_message - Email Message.
		 * @return bool|WP_Error
		 * @throws Exception - In case of error Throw an Exception.
		 */
		protected function bambora_send_paymentrequest( $order_id, $recipient_name, $recipient_email, $replyto_name, $replyto_email, $email_message ) {
			$order                     = wc_get_order( $order_id );
			$payment_request_id        = $order->get_meta( 'bambora_paymentrequest_id' );
			$recipient                 = new Bambora_Online_Checkout_Payment_Request_Email_Recipient();
			$recipient->replyto        = new Bambora_Online_Checkout_Payment_Request_Email_Recipient_Address();
			$recipient->replyto->name  = $replyto_name;
			$recipient->replyto->email = $replyto_email;
			$recipient->message        = $email_message;
			$recipient->to             = new Bambora_Online_Checkout_Payment_Request_Email_Recipient_Address();
			$recipient->to->email      = $recipient_email;
			$recipient->to->name       = $recipient_name;
			$api_key                   = $this->get_api_key();
			$api                       = new Bambora_Online_Checkout_Api( $api_key );
			try {
				$send_payment_request_response = $api->send_payment_request_email( $payment_request_id, $recipient );
				if ( $send_payment_request_response->meta->result ) {
					/* translators: %s: search term */
					$note = sprintf( __( 'Worldline Online Checkout Payment Request with id %1$s sent with email to %2$s', 'bambora-online-checkout' ), $payment_request_id, $recipient_email );
					$order->add_order_note( $note );
					$order->save();
					do_action( 'bambora_online_checkout_after_send_paymentrequest', $order_id );
					return true;
				} else {
					throw new Exception( $send_payment_request_response->meta->message->merchant );
				}
			} catch ( Exception $e ) {
				/* translators: %s: search term */
				$message = sprintf( __( 'Send payment request email failed for order %s', 'bambora-online-checkout' ), $order_id ) . ' - Bambora: ' . $e->getMessage();
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
			$bambora_subscription->decription = 'WooCommerce Subscription v.' . WC_Subscriptions::$version;
			$bambora_subscription->reference  = $this->merchant;

			return $bambora_subscription;
		}

		/**
		 * Create Bambora Orderlines
		 *
		 * @param WC_Order $order - WC Order.
		 * @param int      $minorunits - MinorUnits.
		 * @return array<Bambora_Online_Checkout_Orderline|bool>
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
				$line->linenumber          = ++$line_number;
				$line->quantity            = $item['qty'];
				$line->text                = $item['name'];
				$line->totalprice          = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $item_total, $minorunits, $this->roundingmode );
				$line->totalpriceinclvat   = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $item_total_incl_vat, $minorunits, $this->roundingmode );
				$line->totalpricevatamount = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $item_vat_amount, $minorunits, $this->roundingmode );
				$line->unitpriceinclvat    = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $item_total_incl_vat / $item_quantity, $minorunits, $this->roundingmode );
				$line->unitpricevatamount  = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $item_vat_amount / $item_quantity, $minorunits, $this->roundingmode );
				$line->unitprice           = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( ( $item_total_incl_vat - $item_vat_amount ) / $item_quantity, $minorunits, $this->roundingmode );
				$line->unit                = __( 'pcs.', 'bambora-online-checkout' );
				$line->vat                 = round( $item_vat_amount > 0 && $item_total > 0 ? ( $item_vat_amount / $item_total ) * 100 : 0, 2, Bambora_Online_Checkout_Currency::roundingmode( $this->roundingmode ) );

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
				$shipping_orderline->linenumber          = ++$line_number;
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
					$fee_text                           = __( 'fee', 'bambora-online-checkout' );
					$fee_total                          = $fee_item->get_total();
					$fee_tax                            = (float) $fee_item->get_total_tax();
					$fee_orderline                      = new Bambora_Online_Checkout_Orderline();
					$fee_orderline->id                  = $fee_text;
					$fee_orderline->linenumber          = ++$line_number;
					$fee_orderline->description         = $fee_text;
					$fee_orderline->text                = $fee_text;
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
			if ( isset( $rounding_orderline ) ) {
				$bambora_orderlines[] = $rounding_orderline;
			}

			return $bambora_orderlines;
		}

		/**
		 * Create Bambora Orderlines Rounding Fee
		 *
		 * @param WC_Order                            $order - WC Order.
		 * @param int                                 $minorunits - MinorUnits.
		 * @param Bambora_Online_Checkout_Orderline[] $bambora_orderlines - Bambora Orderlines.
		 * @param mixed                               $line_number - Line Number.
		 * @return Bambora_Online_Checkout_Orderline|null
		 */
		protected function create_bambora_orderlines_rounding_fee( $order, $minorunits, $bambora_orderlines, $line_number ) {
			$wc_total      = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $order->get_total(), $minorunits, $this->roundingmode );
			$bambora_total = 0;
			foreach ( $bambora_orderlines as $order_line ) {
				$bambora_total += $order_line->quantity * $order_line->unitpriceinclvat;
			}

			if ( $wc_total !== $bambora_total ) {
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
				$rounding_orderline->linenumber          = ++$line_number;
				$rounding_orderline->unit                = __( 'pcs.', 'bambora-online-checkout' );
				$rounding_orderline->vat                 = 0.0;

				return $rounding_orderline;
			}
			return null;
		}

		/**
		 * Handle for Bambora Callback
		 *
		 * @return never
		 */
		public function bambora_online_checkout_callback() {
			if ( ! isset( $_GET ) || empty( $_GET ) ) {
				die( 'Callback is missing GET parameters' );
			}
			$params        = wp_unslash( $_GET );
			$message       = '';
			$error_message = '';
			$order         = null;
			$response_code = 400;
			// Validate Callback Parameters.
			$is_valid_callback_params = Bambora_Online_Checkout_Helper::validate_bambora_online_checkout_callback_params( $params, $this->md5key, $order, $message );
			if ( $is_valid_callback_params ) {
				// Verify Callback Parameters.
				$api_key = $this->get_api_key( $order->get_id() );
				$api     = new Bambora_Online_Checkout_Api( $api_key );
				try {
					$get_transaction_response = $api->get_transaction( $params['txnid'] );
					if ( $get_transaction_response->meta->result ) {
						$transaction     = $get_transaction_response->transaction;
						$subscription_id = Bambora_Online_Checkout_Helper::sanitize_array_item_by_key( $params, 'subscriptionid' );
						$message         = $this->process_bambora_online_checkout_callback( $order, $transaction, $subscription_id );
						$response_code   = 200;
					} else {
						$message       = "Transaction is not valid: {$get_transaction_response->meta->message->enduser}";
						$error_message = "Failed to validate Transaction: {$get_transaction_response->meta->message->merchant}";
						$response_code = 400;
					}
				} catch ( Exception $e ) {
					$message       = 'An internal error occured - see log for more information.';
					$error_message = $e->getMessage();
					$response_code = 500;
				}
			}
			if ( 200 !== $response_code ) {
				$params_as_string = '';
				if ( ! empty( $params ) ) {
					foreach ( $params as $key => $value ) {
						if ( empty( $params_as_string ) ) {
							$params_as_string .= "?{$key}={$value}";
						} else {
							$params_as_string .= "&{$key}={$value}";
						}
					}
				}
				$this->boc_log->add( 'Callback failed - ' . esc_attr( $error_message ) . ' - GET params:\n ' . esc_attr( $params_as_string ) );
			}
			$header = 'X-EPay-System: ' . Bambora_Online_Checkout_Helper::get_module_header_info();
			header( $header, true, $response_code );
			die( esc_attr( $message ) );
		}

		/**
		 * Process the Bambora Callback
		 *
		 * @param WC_Order $order - WC Order.
		 * @param mixed    $bambora_transaction - Bambora Transaction.
		 * @param string   $bambora_subscription_id - Bambora Subscription Id.
		 * @throws Exception - Throws Exeception.
		 * @return string
		 */
		protected function process_bambora_online_checkout_callback( $order, $bambora_transaction, $bambora_subscription_id ) {
			try {
				$type = '';
				if ( ! empty( $bambora_subscription_id ) && ( Bambora_Online_Checkout_Helper::order_contains_subscription( $order ) || Bambora_Online_Checkout_Helper::order_is_subscription( $order ) ) ) {
					$action = $this->process_subscription( $order, $bambora_transaction, $bambora_subscription_id );
					$type   = "Subscription {$action}";
				} else {
					$action = $this->process_standard_payments( $order, $bambora_transaction );
					$type   = "Standard Payment {$action}";
				}
			} catch ( Exception $e ) {
				throw $e;
			}

			return "Worldline Online Checkout Callback completed - {$type}";
		}

		/**
		 * Process the Bambora Callback
		 *
		 * @param WC_Order $order - WC Order.
		 * @param mixed    $bambora_transaction - Bambora Transaction.
		 * @return string
		 */
		protected function process_standard_payments( $order, $bambora_transaction ) {
			$action                  = '';
			$existing_transaction_id = Bambora_Online_Checkout_Helper::get_bambora_online_checkout_transaction_id( $order );
			// Handling of Multiple callbacks.
			if ( empty( $existing_transaction_id ) ) {
				$this->add_surcharge_fee_to_order( $order, $bambora_transaction );
				/* translators: %s: search term */
				$order->add_order_note( sprintf( __( 'Worldline Online Checkout Payment completed with transaction id %s', 'bambora-online-checkout' ), $bambora_transaction->id ) );
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
		 * @param WC_Subscription $order - WC_Subscription.
		 * @param mixed           $bambora_transaction - Bambora Transaction.
		 * @param string          $bambora_subscription_id - Bambora Subscription Id.
		 * @return string
		 */
		protected function process_subscription( $order, $bambora_transaction, $bambora_subscription_id ) {
			$action = '';
			if ( Bambora_Online_Checkout_Helper::order_is_subscription( $order ) ) {
				// Do not cancel subscription if the callback is called more than once !
				$old_bambora_subscription_id = Bambora_Online_Checkout_Helper::get_bambora_online_checkout_subscription_id( $order );
				if ( $bambora_subscription_id !== $old_bambora_subscription_id ) {
					$this->subscription_cancellation( $order, true );
					$action = 'changed';
					/* translators: %s: search term */
					$order->add_order_note( sprintf( __( 'Worldline Online Checkout Subscription changed from: %1$s to: %2$s', 'bambora-online-checkout' ), $old_bambora_subscription_id, $bambora_subscription_id ) );
					$order->payment_complete();
					$this->save_subscription_meta( $order, $bambora_subscription_id, false );
				} else {
					$action = 'changed (Called multiple times)';
				}
			} else {
				// Do not add surcharge if the callback is called more than once!
				$old_transaction_id = Bambora_Online_Checkout_Helper::get_bambora_online_checkout_transaction_id( $order );
				if ( $bambora_transaction->id !== $old_transaction_id ) {
					$this->add_surcharge_fee_to_order( $order, $bambora_transaction );
					$action = 'activated';
					/* translators: %s: search term */
					$order->add_order_note( sprintf( __( 'Worldline Online Checkout Subscription activated with subscription id: %s', 'bambora-online-checkout' ), $bambora_subscription_id ) );
					$order->payment_complete( $bambora_transaction->id );
					$this->save_subscription_meta( $order, $bambora_subscription_id, true );
				} else {
					$action = 'activated (Called multiple times)';
				}
			}
			return $action;
		}
		/**
		 * Summary of add_surcharge_fee_to_order
		 *
		 * @param WC_Order|WC_Subscription $order - WC Order.
		 * @param mixed                    $bambora_transaction - Bambora Transaction.
		 * @return void
		 */
		protected function add_surcharge_fee_to_order( $order, $bambora_transaction ) {
			$minorunits               = $bambora_transaction->currency->minorunits;
			$fee_amount_in_minorunits = $bambora_transaction->total->feeamount;
			if ( $fee_amount_in_minorunits > 0 && 'yes' === $this->addsurchargetoshipment ) {
				$fee_amount = Bambora_Online_Checkout_Currency::convert_price_from_minorunits( $fee_amount_in_minorunits, $minorunits );
				$fee_item   = new WC_Order_Item_Fee();
				$fee_item->set_props(
					array(
						'name'      => __( 'Surcharge Fee', 'bambora-online-checkout' ),
						'tax_class' => null,
						'total'     => $fee_amount,
						'total_tax' => 0,
						'order_id'  => $order->get_id(),
					)
				);
				$fee_item->save();
				$order->add_item( $fee_item );
				$total_incl_fee = $order->get_total() + $fee_amount;
				$order->set_total( $total_incl_fee );
			}
		}

		/**
		 * Store the Worldline Online Checkout subscription id on subscriptions in the order.
		 *
		 * @param WC_Order|WC_Subscription $order - WC Order.
		 * @param string                   $bambora_subscription_id - Bambora Subscription Id.
		 * @param bool                     $is_new_subscription - Is New Subscription.
		 * @return void
		 */
		protected function save_subscription_meta( $order, $bambora_subscription_id, $is_new_subscription ) {
			$bambora_subscription_id = wc_clean( $bambora_subscription_id );
			$order_id                = $order->get_id();
			if ( $is_new_subscription ) {
				// Also store it on the subscriptions being purchased in the order.
				$subscriptions = Bambora_Online_Checkout_Helper::get_subscriptions_for_order( $order_id );
				foreach ( $subscriptions as $subscription ) {
					$subscription->update_meta_data( Bambora_Online_Checkout_Helper::BAMBORA_ONLINE_CHECKOUT_SUBSCRIPTION_ID, $bambora_subscription_id );
					/* translators: %s: search term */
					$subscription->add_order_note( sprintf( __( 'Worldline Online Checkout Subscription activated with subscription id: %1$s by order %2$s', 'bambora-online-checkout' ), $bambora_subscription_id, $order_id ) );
					$subscription->save();
				}
			} else {
				$subscription = wcs_get_subscription( $order_id );
				$subscription->update_meta_data( Bambora_Online_Checkout_Helper::BAMBORA_ONLINE_CHECKOUT_SUBSCRIPTION_ID, $bambora_subscription_id );
				$subscription->save();
			}
		}

		/**
		 * Process Refund Hook
		 *
		 * @param string     $order_id - WC Order Id.
		 * @param float|null $amount - Amount to Refund.
		 * @param string     $reason - Reason for Refund.
		 * @return bool|WP_Error
		 */
		public function process_refund( $order_id, $amount = null, $reason = '' ) {
			$user = wp_get_current_user();
			if ( ! in_array( $this->rolecapturerefunddelete, $user->roles, true ) && ! in_array( 'administrator', $user->roles, true ) ) {
				// The user can only view the data.
				return new WP_Error( 'notpermitted', __( 'Your user role is not allowed to refund via Worldline Online Checkout', 'bambora-online-checkout' ) );
			}

			if ( ! isset( $amount ) || $amount < 1 ) {
				return new WP_Error( 'toolow', __( 'You have to refund a higher amount than 0.', 'bambora-online-checkout' ) );
			}

			$refund_result = $this->bambora_online_checkout_refund_payment( $order_id, $amount, '' );
			if ( is_wp_error( $refund_result ) ) {
				return $refund_result;
			} else {
				/* translators: %s: search term */
				$message = sprintf( __( 'The Refund action was a success for order %s', 'bambora-online-checkout' ), $order_id );
				Bambora_Online_Checkout_Helper::add_admin_notices( Bambora_Online_Checkout_Helper::SUCCESS, $message );
			}

			return true;
		}

		/**
		 * Try and create refund lines. If there is a negative amount on one of the refund items, it fails.
		 *
		 * @param WC_Order_Refund                     $refund - WC Order Refund.
		 * @param Bambora_Online_Checkout_Orderline[] $bambora_refund_lines - Bambora Refund Order lines.
		 * @param int                                 $minorunits - MinorUnits.
		 * @param WC_Order                            $order - WC Order.
		 * @param boolean                             $is_walley - Is Collector Bank.
		 * @throws \Exception - Throws Exeception.
		 * @return bool
		 */
		protected function create_bambora_refund_lines( $refund, &$bambora_refund_lines, $minorunits, $order, $is_walley = false ) {
			$line_number = 0;
			$total       = $refund->get_total();
			$items_total = 0;

			$refund_items = $refund->get_items();
			foreach ( $refund_items as $item ) {
				$line_total_with_vat = $refund->get_line_total( $item, true, true );
				$line_total          = $refund->get_line_total( $item, false, true );
				$line_vat            = $refund->get_line_tax( $item );

				if ( 0 < $line_total ) {
					continue;
				}
				$line                      = new Bambora_Online_Checkout_Orderline();
				$line->description         = $item['name'];
				$line->id                  = $item['product_id'];
				$line->linenumber          = ++$line_number;
				$line->quantity            = abs( $item['qty'] );
				$line->text                = $item['name'];
				$line->totalpriceinclvat   = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( abs( $line_total_with_vat ), $minorunits, $this->roundingmode );
				$line->totalprice          = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( abs( $line_total ), $minorunits, $this->roundingmode );
				$line->totalpricevatamount = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( abs( $line_vat ), $minorunits, $this->roundingmode );
				$items_total              += $line_total_with_vat;
				$quantity                  = isset( $line->quantity ) && 0 < $line->quantity ? abs( $item['qty'] ) : 1;
				$line->unitpriceinclvat    = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( abs( $line_total_with_vat ) / $quantity, $minorunits, $this->roundingmode );
				$line->unitprice           = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( abs( $line_total ) / $quantity, $minorunits, $this->roundingmode );
				$line->unitpricevatamount  = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( abs( $line_vat ) / $quantity, $minorunits, $this->roundingmode );
				$line->unit                = __( 'pcs.', 'bambora-online-checkout' );
				$line->vat                 = round( (float) ( $line_vat > 0 ? ( $line_vat / $line_total ) * 100 : 0 ), 2, Bambora_Online_Checkout_Currency::roundingmode( $this->roundingmode ) );

				$bambora_refund_lines[] = $line;
			}

			$shipping_methods       = $refund->get_shipping_methods();
			$order_shipping_methods = $order->get_shipping_methods();
			if ( $shipping_methods && count( $shipping_methods ) !== 0 ) {
				$shipping_total = $refund->get_shipping_total();
				$shipping_tax   = $refund->get_shipping_tax();

				if ( 0 < $shipping_total || 0 < $shipping_tax ) {
					throw new Exception( esc_textarea( __( 'Invalid refund amount for shipping', 'bambora-online-checkout' ) ) );
				}
				if ( $is_walley && $order_shipping_methods && count( $order_shipping_methods ) !== 0 ) {
					$order_shipping_total = $order->get_shipping_total();
					$order_shipping_tax   = $order->get_shipping_tax();
					if ( abs( (float) $order_shipping_total ) !== abs( (float) $shipping_total ) || abs( (float) $order_shipping_tax ) !== abs( (float) $shipping_tax ) ) {
						throw new Exception( esc_textarea( __( 'You can only refund complete order lines for payments made with Walley.', 'bambora-online-checkout' ) ) );
					}
				}
				$shipping_method                         = reset( $shipping_methods );
				$shipping_orderline                      = new Bambora_Online_Checkout_Orderline();
				$shipping_orderline->id                  = $shipping_method->get_method_id();
				$shipping_orderline->linenumber          = ++$line_number;
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
				$items_total                            += $shipping_total + $shipping_tax;
			}
			$fees       = $refund->get_fees();
			$order_fees = $order->get_fees();
			if ( $fees && count( $fees ) !== 0 ) {
				foreach ( $fees as $fee_item ) {
					$fee_total = $fee_item->get_total();

					$fee_tax = $fee_item->get_total_tax();
					if ( 0 < $fee_total ) {
						throw new Exception( esc_textarea( __( 'Invalid refund amount for fees', 'bambora-online-checkout' ) ) );
					}
					if ( $is_walley && $order_fees && 0 !== count( $order_fees ) ) {
						$order_fee_total = 0;
						$order_fee_tax   = 0;
						if ( $order_fees && count( $order_fees ) !== 0 ) {
							foreach ( $order_fees as $order_fee_item ) {
								$order_fee_total += (float) $order_fee_item->get_total();
								$order_fee_tax   += (float) $order_fee_item->get_total_tax();
							}
						}
						if ( abs( $order_fee_total ) !== abs( $fee_total ) || abs( $order_fee_tax ) !== abs( $fee_tax ) ) {
							throw new Exception( esc_textarea( __( 'You can only refund complete order lines for payments made with Walley.', 'bambora-online-checkout' ) ) );
						}
					}

					$fee_orderline                      = new Bambora_Online_Checkout_Orderline();
					$fee_orderline->id                  = __( 'fee', 'bambora-online-checkout' );
					$fee_orderline->linenumber          = ++$line_number;
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
					$items_total                       += $fee_total + $fee_tax;
				}
			}
			if ( $items_total < $total ) {
				return false;
			} elseif ( $items_total > $total ) {
				$reason                                   = $refund->get_reason();
				$additional_refund_orderline              = new Bambora_Online_Checkout_Orderline();
				$additional_refund_orderline->id          = __( 'Refund', 'bambora-online-checkout' );
				$additional_refund_orderline->linenumber  = ++$line_number;
				$additional_refund_orderline->description = __( 'Refund', 'bambora-online-checkout' ) . ! empty( $reason ) ? ": {$reason}" : '';
				$additional_refund_orderline->text        = __( 'Refund', 'bambora-online-checkout' );
				$additional_refund_orderline->quantity    = 1;
				$additional_refund_orderline->unit        = __( 'pcs.', 'bambora-online-checkout' );
				$additional_refund_orderline->totalpriceinclvat = abs( Bambora_Online_Checkout_Currency::convert_price_to_minorunits( ( $total - $items_total ), $minorunits, $this->roundingmode ) );
				$additional_refund_orderline->unitpriceinclvat  = $additional_refund_orderline->totalpriceinclvat;
				$additional_refund_orderline->vat               = 0;
				$bambora_refund_lines[]                         = $additional_refund_orderline;
			}
			return true;
		}

		/**
		 * Bambora Meta Boxes
		 *
		 * @param string $post_type - From where is the function called.
		 * @return void
		 * @throws Exception - In case of error Throw an Exception.
		 */
		public function bambora_online_checkout_meta_boxes( $post_type ) {
			if ( 'shop_order' !== $post_type && 'woocommerce_page_wc-orders' !== $post_type ) {
				return;
			}

			global $post;
			$order = isset( $post ) ? wc_get_order( $post->ID ) : wc_get_order();
			if ( empty( $order ) || ! $this->module_check( $order->get_id() ) ) {
				return;
			}
			$order_total    = $order->get_total();
			$transaction_id = Bambora_Online_Checkout_Helper::get_bambora_online_checkout_transaction_id( $order );
			$status         = $order->get_status();
			// Only show create payment request if the order is created, has a total over 0 and does not have another payment method selected and no other transaction.
			if ( ! empty( $status ) && 'auto-draft' !== $status && 0 < $order_total && empty( $transaction_id ) ) {
				$api_key                 = $this->get_api_key( $order->get_id() );
				$api                     = new Bambora_Online_Checkout_Api( $api_key );
				$payment_request_allowed = false;
				try {
					$get_merchant_api_permissions_response = $api->get_merchant_api_permissions();
					if ( $get_merchant_api_permissions_response->meta->result ) {
						foreach ( $get_merchant_api_permissions_response->functionpermissions as $function_permission ) {
							if ( Bambora_Online_Checkout_Api::PERMISSION_PAYMENT_REQUEST === $function_permission->name ) {
								$payment_request_allowed = true;
								break;
							}
						}
					} else {
						throw new Exception( $get_merchant_api_permissions_response->meta->message->merchant );
					}
				} catch ( Exception $e ) {
					$error_message = "Payment Request Permissions could not be retrived: {$e->getMessage()}";
					$this->boc_log->add( $error_message );
				}
				if ( $payment_request_allowed ) {
					add_meta_box(
						'bambora-paymentrequest-actions',
						__( 'Worldline Online Checkout Payment Request', 'bambora-online-checkout' ),
						array(
							$this,
							'bambora_online_checkout_meta_box_payment_request',
						),
						'shop_order',
						'side',
						'high'
					);
					add_meta_box(
						'bambora-paymentrequest-actions',
						__( 'Worldline Online Checkout Payment Request', 'bambora-online-checkout' ),
						array(
							$this,
							'bambora_online_checkout_meta_box_payment_request',
						),
						'woocommerce_page_wc-orders',
						'side',
						'high'
					);
				}
			}
			add_meta_box(
				'bambora-payment-actions',
				__( 'Worldline Online Checkout', 'bambora-online-checkout' ),
				array(
					$this,
					'bambora_online_checkout_meta_box_payment',
				),
				'shop_order',
				'side',
				'high'
			);
			add_meta_box(
				'bambora-payment-actions',
				__( 'Worldline Online Checkout', 'bambora-online-checkout' ),
				array(
					$this,
					'bambora_online_checkout_meta_box_payment',
				),
				'woocommerce_page_wc-orders',
				'side',
				'high'
			);
		}
		/**
		 * Meta Box for Bambora Online Checkout Payment Request
		 *
		 * @return void
		 * @throws Exception - In case of error Throw an Exception.
		 */
		public function bambora_online_checkout_meta_box_payment_request() {
			global $post;

			// HPOS ($post) might be used.
			$order    = isset( $post ) ? wc_get_order( $post->ID ) : wc_get_order();
			$order_id = isset( $order ) ? $order->get_id() : '';

			if ( empty( $order_id ) || ! $this->module_check( $order_id ) ) {
				return;
			}

			$payment_request_id  = $order->get_meta( 'bambora_paymentrequest_id' );
			$payment_request_url = $order->get_meta( 'bambora_paymentrequest_url' );

			if ( isset( $payment_request_id ) && ! empty( $payment_request_id ) ) { // A payment request is already created for this order.
					$html  = '<div class="bambora_paymentrequest_action_container">';
					$html .= '<div class="bambora_paymentrequest_details">';
					$html .= '<h3>' . esc_attr( __( 'Payment Request Details', 'bambora-online-checkout' ) ) . '</h3>';
					$html .= '<div class="bambora_pr_label">' . esc_attr( __( 'Payment Request ID', 'bambora-online-checkout' ) ) . ':</div>';
					$html .= '<div class="bambora_pr_info"> ' . esc_attr( $payment_request_id ) . '</a></div>';

					$api_key = $this->get_api_key( $order_id );
					$api     = new Bambora_Online_Checkout_Api( $api_key );
				try {
					$get_payment_request_response = $api->get_payment_request( $payment_request_id );
					if ( $get_payment_request_response->meta->result ) {
						$created_date = 'N/A';
						if ( isset( $pr->createddate ) ) {
							$datetime = new DateTime( $get_payment_request_response->createddate );
							$timezone = wp_timezone_string();
							$datetime->setTimezone( new DateTimeZone( $timezone ) );
							$date_format = get_option( 'date_format' );
							$time_format = get_option( 'time_format' );
							// Format the DateTime object to the desired date and time formats.
							$formatted_date = $datetime->format( $date_format );
							$formatted_time = $datetime->format( $time_format );
							$created_date   = "{$formatted_date} {$formatted_time}";
						}
						$html .= '<div class="bambora_pr_label">' . esc_attr( __( 'Created', 'bambora-online-checkout' ) ) . ':</div><div class="bambora_pr_info"> ' . esc_attr( $created_date ) . '</a></div>';
						$html .= '<div class="bambora_pr_label">' . esc_attr( __( 'Description', 'bambora-online-checkout' ) ) . ':</div><div class="bambora_pr_info"> ' . esc_attr( $get_payment_request_response->description ) . '</a></div>';
						$html .= '<div class="bambora_pr_label">' . esc_attr( __( 'Status', 'bambora-online-checkout' ) ) . ':</div><div class="bambora_pr_info ">' . esc_attr( ucfirst( $get_payment_request_response->status ) ) . '</div>';
						$html .= '<div class="bambora_pr_label">' . esc_attr( __( 'Reference', 'bambora-online-checkout' ) ) . ':</div><div class="bambora_pr_info"> ' . esc_attr( $get_payment_request_response->reference ) . '</a></div>';
						$html .= '<div class="bambora_pr_label">' . esc_attr( __( 'Payment Request URL', 'bambora-online-checkout' ) ) . ':</div><div class="bambora_pr_info"><a href="' . esc_attr( $payment_request_url ) . '" target="_blank">' . esc_attr( $payment_request_url ) . '</a></div>';
						$html .= '<div class="bambora_pr_label"></div>';
						$html .= '<div class="bambora_pr_info"></div>';
						$html .= '</div></div>';

						if ( 'closed' !== $get_payment_request_response->status ) {

							$html        .= '<div class="bambora_paymentrequest_action bambora_paymentrequest_details">';
							$html        .= '<h3 class="bambora_payment_request">' . esc_attr( __( 'Send Payment Request', 'bambora-online-checkout' ) ) . '</h3>';
							$current_user = wp_get_current_user();

							$customer_id    = $order->get_customer_id();
							$customer       = new WC_Customer( $customer_id );
							$customer_email = $customer->get_email();
							$customer_name  = $customer->get_first_name() . ' ' . $customer->get_last_name();

							$html .= '<input type="hidden" id="bambora_send_pr_message" name="bambora_send_pr_message" value="' .
							/* translators: %s: search term */
							esc_attr( sprintf( __( 'Are you sure that you want to send the Payment Request to %1$s, requesting an amount of %2$s ?', 'bambora-online-checkout' ), $customer_email, $order->get_currency() . ' ' . $order->get_total() ) ) . '" />';
							$html .= '<input type="hidden" id="bambora_pr_id" name="bambora_pr_id" value="' . esc_attr( $payment_request_id ) . '" />';
							$html .= '<label class="bambora_pr_label" for="bambora_pr_recipient_email">' . esc_attr( __( 'Recipient Email', 'bambora-online-checkout' ) ) . ':</label>';
							$html .= '<input type="text" id="bambora_pr_recipient_email" value="' . esc_attr( $customer_email ) . '" class="bambora_email" name="bambora_pr_recipient_email" />';
							$html .= '<label class="bambora_pr_label" for="bambora_pr_recipient_name">' . esc_attr( __( 'Recipient Name', 'bambora-online-checkout' ) ) . ':</label>';
							$html .= '<input type="text" id="bambora_pr_recipient_name" value="' . esc_attr( $customer_name ) . '" class="bambora" name="bambora_pr_recipient_name"/>';
							$html .= '<label  class="bambora_pr_label" for="bambora_pr_replyto_email">' . esc_attr( __( 'Reply-To Email', 'bambora-online-checkout' ) ) . ':</label>';
							$html .= '<input type="text" id="bambora_pr_replyto_email" value="' . esc_attr( $current_user->user_email ) . '" class="bambora" name="bambora_pr_replyto_email"/>';
							$html .= '<label class="bambora_pr_label" for="bambora_pr_replyto_name">' . esc_attr( __( 'Reply-To Name', 'bambora-online-checkout' ) ) . ':</label>';
							$html .= '<input type="text" id="bambora_pr_replyto_name" value="' . esc_attr( $current_user->first_name ) . ' ' . esc_attr( $current_user->last_name ) . '" class="bambora" name="bambora_pr_replyto_name" />';
							$html .= '<label  class="bambora_pr_label" for="bambora_pr_email_message">' . __( 'Message', 'bambora-online-checkout' ) . ':</label>';
							$html .= '<input type="text" id="bambora_pr_email_message" value="" class="bambora" name="bambora_pr_email_message" />';
							$html .= '<input id="bambora_send_pr_submit" class="button delete" name="bambora_send_pr_submit" type="submit" value="' . __( 'Send Payment Request by Email', 'bambora-online-checkout' ) . '"/>';
							$html .= '</div>';

							$html .= '<div class="bambora_paymentrequest_action_container">';
							$html .= '<div class="bambora_paymentrequest_action bambora_paymentrequest_details">';
							$html .= '<input type="hidden" id="bambora_pr_id" name="bambora_pr_id" value="' . esc_attr( $payment_request_id ) . '" />';
							$html .= '<input type="hidden" id="bambora_delete_pr_message" name="bambora_delete_pr_message" value="' . esc_attr( __( 'Are you sure you want to delete this payment request?', 'bambora-online-checkout' ) ) . '" />';
							$html .= '<h3>' . esc_attr( __( 'Delete Payment Request', 'bambora-online-checkout' ) ) . '</h3>';
							$html .= '<input id="bambora_delete_pr_submit" class="button delete" name="bambora_delete_pr_submit" type="submit" value="' . esc_attr( __( 'Delete Payment Request', 'bambora-online-checkout' ) ) . '" />';
							wp_nonce_field( 'bambora_process_paymentrequest_action', 'bambora_nonce' );
							$html .= '</div></div>';
						}
					} else {
						throw new Exception( $get_payment_request_response->meta->message->merchant );
					}
				} catch ( Exception $e ) {
					$error_message = "Could not retrive Payment Request with Id: {$payment_request_id} - {$e->getMessage()}";
					$html         .= '<p>' . esc_attr( $error_message ) . '</p></div></div>';
					$this->boc_log->add( $error_message );
				}
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $html;
			} else {
				$amount = $order->get_total();

				$html  = '<div class="bambora_info">';
				$html .= '<div class="bambora_paymentrequest_action_container">';
				$html .= '<input type="hidden" id="bambora_create_pr_message" name="bambora_create_pr_message" value="' . esc_attr( __( 'Are you sure you want to create a payment request?', 'bambora-online-checkout' ) ) . '" />';
				$html .= '<div class="bambora_paymentrequest_action">';
				$html .= '<h3>' . esc_attr( __( 'Create Payment Request for Order', 'bambora-online-checkout' ) ) . ' ' . esc_attr( $order->get_order_number() ) . '</h3>';
				$html .= '<div class="pr_create_description">' . esc_attr( __( 'Once you have created the Payment Request, you will be able to send it directly to the customer.', 'bambora-online-checkout' ) ) . '</div>';
				$html .= '<div class="bambora_pr_label">' . esc_attr( __( 'Order Amount in Request', 'bambora-online-checkout' ) ) . ':</div>';
				$html .= '<div class="bambora_pr_info">' . esc_attr( $order->get_currency() ) . ' ' . esc_attr( $amount ) . '</div>';
				$html .= '<div class="bambora_pr_label">' . esc_attr( __( 'Description', 'bambora-online-checkout' ) ) . ':</div>';
				$html .= '<input type="text" id="bambora_pr_description" value="" class="bambora" name="bambora_pr_description" />';
				$html .= '<input id="bambora_create_pr_submit" class="button create" name="bambora_create_pr_submit" type="submit" value="' . esc_attr( __( 'Create Payment Request', 'bambora-online-checkout' ) ) . '" />';
				wp_nonce_field( 'bambora_process_paymentrequest_action', 'bambora_nonce' );
				$html .= '</div></div></div>';
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $html;
			}
		}

		/**
		 * Generate the Bambora payment meta box and echos the HTML
		 *
		 * @return null
		 * @throws Exception - In case of error Throw an Exception.
		 */
		public function bambora_online_checkout_meta_box_payment() {
			global $post;
			// HPOS ($post) might be used.
			$order    = isset( $post ) ? wc_get_order( $post->ID ) : wc_get_order();
			$order_id = ( isset( $order ) ) ? $order->get_id() : '';

			if ( empty( $order_id ) || ! $this->module_check( $order_id ) ) {
				return;
			} else {
				$transaction_id = Bambora_Online_Checkout_Helper::get_bambora_online_checkout_transaction_id( $order );
				if ( empty( $transaction_id ) ) {
					/* translators: %s: search term */
					echo esc_attr( sprintf( __( 'No transaction was found for order %s', 'bambora-online-checkout' ), $order_id ) );
					return;
				} else {
					$html    = '';
					$api_key = $this->get_api_key( $order_id );
					$api     = new Bambora_Online_Checkout_Api( $api_key );
					try {
						$get_transaction_response = $api->get_transaction( $transaction_id );
						if ( ! $get_transaction_response->meta->result ) {
							/* translators: %s: search term */
							$message = sprintf( __( 'Get transaction failed for order %1$s - %2$s', 'bambora-online-checkout' ), $order_id, $get_transaction_response->meta->message->merchant );
							throw new Exception( esc_attr( $message ) );
						} else {
							$transaction           = $get_transaction_response->transaction;
							$minorunits            = $transaction->currency->minorunits;
							$total_authorized      = Bambora_Online_Checkout_Currency::convert_price_from_minorunits( $transaction->total->authorized, $minorunits );
							$total_captured        = Bambora_Online_Checkout_Currency::convert_price_from_minorunits( $transaction->total->captured, $minorunits );
							$available_for_capture = Bambora_Online_Checkout_Currency::convert_price_from_minorunits( $transaction->available->capture, $minorunits );
							$total_credited        = Bambora_Online_Checkout_Currency::convert_price_from_minorunits( $transaction->total->credited, $minorunits );
							$currency_code         = $transaction->currency->code;
							$payment_type          = ! empty( $transaction->information->paymenttypes ) ? $transaction->information->paymenttypes[0] : null;
							$wallet_name           = ! empty( $transaction->information->wallets ) ? $transaction->information->wallets[0]->name : null;
							$is_collector          = false;
							$collector_class       = 'isCollectorFalse';

							if ( isset( $payment_type ) && 19 === $payment_type->groupid && 40 === $payment_type->id ) { // Collector Bank (from 1st September 2021 called Walley).
								$is_collector    = true;
								$collector_class = 'isCollectorTrue';
							}

							$user                                    = wp_get_current_user();
							$can_capture_refund_delete               = in_array( $this->rolecapturerefunddelete, (array) $user->roles, true ) || in_array( 'administrator', (array) $user->roles, true );
								$get_transaction_operations_response = $api->get_transaction_operations( $transaction_id );
							if ( ! $get_transaction_operations_response->meta->result ) {
								/* translators: %s: search term */
								$message = sprintf( __( 'Get transaction operations failed - %s', 'bambora-online-checkout' ), $get_transaction_operations_response->meta->message->merchant );
								echo esc_attr( Bambora_Online_Checkout_Helper::message_to_html( Bambora_Online_Checkout_Helper::ERROR, $message ) );
								$this->boc_log->add( $message );
							} else {
								$transaction_operations = $get_transaction_operations_response->transactionoperations;

								$html  = '<div id="' . esc_attr( $collector_class ) . '"></div>';
								$html .= '<div class="bambora_info">';
								if ( isset( $payment_type ) ) {
									$html .= '<img class="bambora_card_logo" src="https://static.bambora.com/assets/paymentlogos/' . esc_attr( $payment_type->groupid ) . '.svg" alt="' . esc_attr( $payment_type->displayname ) . '" title="' . esc_attr( $payment_type->displayname ) . '" />';
								}
								if ( isset( $transaction_operations[0]->transactionoperations[0]->acquirerdata[0]->key ) ) {
									if ( 'nordeaepaymentfi.customerbank' === $transaction_operations[0]->transactionoperations[0]->acquirerdata[0]->key ) {
										$bank_name = $transaction_operations[0]->transactionoperations[0]->acquirerdata[0]->value;
									}
									$bank_name = 'nordea';
									if ( ! empty( $bank_name ) ) {
										$html .= '<img class="bambora_bank_logo" src="https://static.bambora.com/assets/paymentlogos/bank-' . esc_attr( $bank_name ) . '.svg" alt="' . esc_attr( $bank_name ) . '" title="' . esc_attr( $bank_name ) . '" />';
									}
								}
								if ( isset( $wallet_name ) ) {
									if ( 'MobilePay' === $wallet_name ) {
										$wallet_img = '13.svg';
									}
									if ( 'Vipps' === $wallet_name ) {
										$wallet_img = '14.svg';
									}
									if ( 'GooglePay' === $wallet_name ) {
										$wallet_img  = '22.svg';
										$wallet_name = 'Google Pay';
									}
									if ( 'ApplePay' === $wallet_name ) {
										$wallet_img  = '21.svg';
										$wallet_name = 'Apple Pay';
									}
									if ( isset( $wallet_img ) ) {
										$html .= '<img class="bambora_wallet_logo" src="https://static.bambora.com/assets/paymentlogos/' . esc_attr( $wallet_img ) . '" alt="' . esc_attr( $wallet_name ) . '" title="' . esc_attr( $wallet_name ) . '" />';
									}
								}
								$html .= '<div class="bambora_transactionid">';
								$html .= '<p>' . __( 'Transaction ID', 'bambora-online-checkout' ) . '</p>';
								$html .= '<p>' . $transaction->id . '</p>';
								$html .= '</div>';
								$html .= '<div class="bambora_paymenttype">';
								$html .= '<p>' . __( 'Payment Type', 'bambora-online-checkout' ) . '</p>';
								$html .= '<p>' . esc_attr( $payment_type->displayname ) . '</p>';
								$html .= '</div>';
								if ( isset( $transaction->information->ecis ) ) {
									$lowest_eci = $this->get_lowest_eci( $transaction->information->ecis );
									if ( isset( $lowest_eci ) && ! empty( $lowest_eci ) ) {
										$html .= '<div class="bambora_paymenttype">';
										$html .= '<p>' . esc_attr( __( 'ECI', 'bambora-online-checkout' ) ) . '</p>';
										$html .= '<p> <span title="' . esc_attr( Bambora_Online_Checkout_Helper::get_3d_secure_text( $lowest_eci ) ) . '">' . esc_attr( $lowest_eci ) . '</span></p>';
										$html .= '</div>';
									}
								}
								if ( isset( $transaction->information->acquirerreferences ) && isset( $transaction->information->acquirerreferences[0]->reference ) ) {
									$acquirer_reference = $transaction->information->acquirerreferences[0]->reference;
									if ( isset( $acquirer_reference ) && ! empty( $acquirer_reference ) ) {
										$html .= '<div class="bambora_paymenttype">';
										$html .= '<p>' . esc_attr( __( 'Acquirer Reference', 'bambora-online-checkout' ) ) . '</p>';
										$html .= '<p>' . esc_attr( $acquirer_reference ) . '</p>';
										$html .= '</div>';
									}
								}
								if ( isset( $transaction->information->exemptions ) ) {
									$exemptions = $this->getDistinctExemptions( $transaction->information->exemptions );
									if ( isset( $exemptions ) && ! empty( $exemptions ) ) {
										$html .= '<div class="bambora_paymenttype">';
										$html .= '<p>' . esc_attr( __( 'Exemptions', 'bambora-online-checkout' ) ) . '</p>';
										$html .= '<p>' . esc_attr( $exemptions ) . '</p>';
										$html .= '</div>';
									}
								}
								$html .= '<div class="bambora_info_overview">';
								$html .= '<p>' . __( 'Authorized:', 'bambora-online-checkout' ) . '</p>';
								$html .= '<p>' . esc_attr( wc_format_localized_price( $total_authorized ) ) . ' ' . esc_attr( $currency_code ) . '</p>';
								$html .= '</div>';
								$html .= '<div class="bambora_info_overview">';
								$html .= '<p>' . esc_attr( __( 'Captured:', 'bambora-online-checkout' ) ) . '</p>';
								$html .= '<p>' . esc_attr( wc_format_localized_price( $total_captured ) ) . ' ' . esc_attr( $currency_code ) . '</p>';
								$html .= '</div>';
								$html .= '<div class="bambora_info_overview">';
								$html .= '<p>' . esc_attr( __( 'Refunded:', 'bambora-online-checkout' ) ) . '</p>';
								$html .= '<p>' . esc_attr( wc_format_localized_price( $total_credited ) ) . ' ' . esc_attr( $currency_code ) . '</p>';
								$html .= '</div>';
								$html .= '</div>';

								if ( 0 < $available_for_capture || $transaction->candelete ) {
									$html .= '<div class="bambora_action_container">';
									if ( 0 < $available_for_capture ) {
										if ( $is_collector ) {
											$tooltip   = esc_attr( __( 'With Payment Provider Walley only full capture is possible here. For partial capture, please use Bambora Merchant Portal.', 'bambora-online-checkout' ) );
											$read_only = 'readonly data-toggle="tooltip" title="' . esc_attr( $tooltip ) . '"';
										} else {
											$read_only = '';
										}
										$html .= '<input type="hidden" id="bambora_currency" name="bambora_currency" value="' . esc_attr( $currency_code ) . '">';
										$html .= '<input type="hidden" id="bambora_capture_message" name="bambora_capture_message" value="' . __( 'Are you sure you want to capture the payment?', 'bambora-online-checkout' ) . '" />';
										$html .= '<div class="bambora_action">';
										if ( $can_capture_refund_delete ) {
											$html .= '<p>' . esc_attr( $currency_code ) . '</p>';
											$html .= '<input type="text" value="' . esc_attr( $available_for_capture ) . '"id="bambora_capture_amount" ' . esc_attr( $read_only ) . ' class="bambora_amount" name="bambora_amount" />';
											$html .= '<input id="bambora_capture_submit" class="button capture" name="bambora_capture" type="submit" value="' . esc_attr( __( 'Capture', 'bambora-online-checkout' ) ) . '" />';
										} else {
											$html .= esc_attr( __( 'Your role cannot capture or delete the payment', 'bambora-online-checkout' ) );
										}
										$html .= '</div>';
										$html .= '<br />';
									}
									if ( $transaction->candelete ) {
										$html .= '<input type="hidden" id="bambora_delete_message" name="bambora_delete_message" value="' . esc_attr( __( 'Are you sure you want to delete the payment?', 'bambora-online-checkout' ) ) . '" />';
										$html .= '<div class="bambora_action">';
										if ( $can_capture_refund_delete ) {
											$html .= '<input id="bambora_delete_submit" class="button delete" name="bambora_delete" type="submit" value="' . esc_attr( __( 'Delete', 'bambora-online-checkout' ) ) . '" />';
										}
										$html .= '</div>';
									}
									wp_nonce_field( 'bambora_process_payment_action', 'bambora_nonce' );
									$html           .= '</div>';
									$warning_message = esc_attr( __( 'The amount you entered was in the wrong format.', 'bambora-online-checkout' ) );

									$html .= '<div id="bambora-format-error" class="bambora bambora_error"><strong>' . esc_attr( __( 'Warning', 'bambora-online-checkout' ) ) . ' </strong>' . $warning_message . '<br /><strong>' . esc_attr( __( 'Correct format is: 1234.56', 'bambora-online-checkout' ) ) . '</strong></div>';
								}

								$html .= $this->build_transaction_log_table( $transaction_operations, $minorunits );
								// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								echo $html;
							}
						}
					} catch ( Exception $e ) {
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo esc_attr( Bambora_Online_Checkout_Helper::message_to_html( Bambora_Online_Checkout_Helper::ERROR, esc_attr( $e->getMessage() ) ) );
						$this->boc_log->add( $e->getMessage() );
					}
				}
			}
		}

		/**
		 * Build transaction log table HTML
		 *
		 * @param array $transaction_operations - Bambora Transaction Operations.
		 * @param int   $minorunits - MinorUnits.
		 * @return string
		 */
		protected function build_transaction_log_table( $transaction_operations, $minorunits ) {
			$html  = '<h4>' . __( 'Transaction History', 'bambora-online-checkout' ) . '</h4>';
			$html .= '<table class="bambora_table">';
			$html .= $this->build_transaction_log_rows( $transaction_operations, $minorunits );
			$html .= '</table>';

			return $html;
		}

		/**
		 * Build transaction log row HTML
		 *
		 * @param array $transaction_operations - Bambora Transaction Operations.
		 * @param int   $minorunits - MinorUnits.
		 * @return string
		 */
		protected function build_transaction_log_rows( $transaction_operations, $minorunits ) {
			$html = '';
			foreach ( $transaction_operations as $transaction_operation ) {
				$event_info       = Bambora_Online_Checkout_Helper::get_event_text( $transaction_operation );
				$event_info_extra = '';

				if ( 'approved' !== $transaction_operation->status ) {
					$event_info_extra = $this->get_event_extra( $transaction_operation );
					$event_info_extra = '<div class="bambora_transaction_not_approved">' . $event_info_extra . '</div>';
				}
				if ( isset( $event_info['description'] ) ) {
					$html .= '<tr class="bambora_transaction_row_header">';
					$html .= '<td>' . Bambora_Online_Checkout_Helper::format_date_time( $transaction_operation->createddate ) . '</td>';
					$html .= '</tr>';

					$html .= '<tr class="bambora_transaction_header">';
					$html .= '<td>' . $event_info['title'] . '</td>';

					$amount = Bambora_Online_Checkout_Currency::convert_price_from_minorunits( $transaction_operation->amount, $minorunits );
					if ( $amount > 0 ) {
						$html .= '<td>' . wc_format_localized_price( $amount ) . ' ' . $transaction_operation->currency->code . '</td>';
					} else {
						$html .= '<td>-</td>';
					}
					$html .= '</tr>';
					$html .= '<tr class="bambora_transaction_description">';
					$html .= '<td colspan="2">' . $event_info['description'] . $event_info_extra . '</td>';
					$html .= '</tr>';

					if ( isset( $transaction_operation->transactionoperations ) && ! empty( $transaction_operation->transactionoperations ) ) {
						$html .= $this->build_transaction_log_rows( $transaction_operation->transactionoperations, $minorunits );
					}
				} elseif ( isset( $transaction_operation->transactionoperations ) && count( $transaction_operation->transactionoperations ) > 0 ) {
					$html .= $this->build_transaction_log_rows( $transaction_operation->transactionoperations, $minorunits );
				}
			}
			// Legacy Collector Bank.
			$html = str_replace( 'CollectorBank', 'Walley', $html );

			return $html;
		}

		/**
		 * Get Transaction Operation Event Extra Text
		 *
		 * @param mixed $transaction_operation - Bambora Transaction Operation.
		 * @return string
		 * @throws Exception - In case of error Throw an Exception.
		 */
		protected function get_event_extra( $transaction_operation ) {
			$source      = $transaction_operation->actionsource;
			$action_code = $transaction_operation->actioncode;
			global $post;
			if ( ! isset( $post ) ) {
				$order = wc_get_order();
				$id    = $order->get_id();
			} else {
				$id = $post->ID;
			}
			$merchant_label = '';
			$webservice     = new Bambora_Online_Checkout_Api( $this->get_api_key( $id ) );
			try {
				$get_response_code_data_response = $webservice->get_response_code_data( $source, $action_code );

				if ( ! $get_response_code_data_response->meta->result || isset( $response_code->responsecode ) ) {
					throw new Exception( $get_response_code_data_response->meta->message->merchant );
				}
				$merchant_label = $get_response_code_data_response->responsecode->merchantlabel . ' - ' . $source . ' ' . $action_code;
			} catch ( Exception $e ) {
				$this->boc_log->add( "Could not retrive response code data: {$e->getMessage()}" );
			}
			return $merchant_label;
		}

		/**
		 * Summary of Get Distinct Exemptions
		 *
		 * @param array $exemptions - List of Exemptions.
		 * @return string
		 */
		private function getDistinctExemptions( $exemptions ) {
			$exemption_values = array();
			foreach ( $exemptions as $exemption ) {
				$exemption_values[] = $exemption->value;
			}

			return implode( ',', array_unique( $exemption_values ) );
		}

		/**
		 * Get Lowest ECI value
		 *
		 * @param array $ecis - List of ECI objects.
		 * @return int
		 */
		private function get_lowest_eci( $ecis ) {
			$eci_values = array();
			foreach ( $ecis as $eci ) {
				$eci_values[] = $eci->value;
			}

			return min( $eci_values );
		}
		/**
		 * Verify Action Nounce
		 *
		 * @param string $action - Bambora Action.
		 * @return bool
		 */
		public function verify_action_nounce( $action ) {
			return array_key_exists( 'bambora_nonce', $_GET ) &&
					wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['bambora_nonce'] ) ), $action );
		}
		/**
		 * Worldline Online Checkout Actions
		 *
		 * @return void
		 */
		public function bambora_online_checkout_actions() {
			if ( array_key_exists( 'bambora_action', $_GET ) && $this->verify_action_nounce( 'bambora_process_payment_action' ) ) {

				$params        = $_GET;
				$post          = Bambora_Online_Checkout_Helper::sanitize_array_item_by_key( $params, 'post' );
				$id            = Bambora_Online_Checkout_Helper::sanitize_array_item_by_key( $params, 'id' );
				$action        = Bambora_Online_Checkout_Helper::sanitize_array_item_by_key( $params, 'bambora_action' );
				$currency      = Bambora_Online_Checkout_Helper::sanitize_array_item_by_key( $params, 'currency' );
				$amount        = Bambora_Online_Checkout_Helper::sanitize_array_item_by_key( $params, 'amount' );
				$order_id      = empty( $post ) ? $id : $post;
				$action_result = null;
				$action_name   = '';
				try {
					switch ( $action ) {
						case 'capture':
							$action_name   = __( 'Capture', 'bambora-online-checkout' );
							$action_result = $this->bambora_online_checkout_capture_payment( $order_id, $amount, $currency );
							break;
						case 'refund':
							$action_name   = __( 'Refund', 'bambora-online-checkout' );
							$action_result = $this->bambora_online_checkout_refund_payment( $order_id, $amount, $currency );
							break;
						case 'delete':
							$action_name   = __( 'Delete', 'bambora-online-checkout' );
							$action_result = $this->bambora_online_checkout_delete_payment( $order_id );
							break;
						default:
							$message       = __( 'Transaction Action is incorrect', 'bambora-online-checkout' );
							$action_result = new WP_Error( 'bambora_online_checkout_error', $message );
							break;
					}
				} catch ( Exception $ex ) {
					$action_result = new WP_Error( 'bambora_online_checkout_error', $ex->getMessage() );
				}

				if ( is_wp_error( $action_result ) ) {
					$message = $action_result->get_error_message( 'bambora_online_checkout ' );
					$this->boc_log->add( $message );
					Bambora_Online_Checkout_Helper::add_admin_notices( Bambora_Online_Checkout_Helper::ERROR, $message );
				} else {
					global $post;
					/* translators: %s: search term */
					$message = sprintf( __( 'The %1$s action was a success for order %2$s', 'bambora-online-checkout' ), $action_name, $order_id );
					$this->boc_log->add( $message );
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
		 * Worldline Online Checkout Payment Request Actions
		 *
		 * @return void
		 */
		public function bambora_online_checkout_paymentrequest_actions() {
			if ( array_key_exists( 'bambora_paymentrequest_action', $_GET ) && $this->verify_action_nounce( 'bambora_process_paymentrequest_action' ) ) {
				$params        = $_GET;
				$post          = Bambora_Online_Checkout_Helper::sanitize_array_item_by_key( $params, 'post' );
				$id            = Bambora_Online_Checkout_Helper::sanitize_array_item_by_key( $params, 'id' );
				$action        = Bambora_Online_Checkout_Helper::sanitize_array_item_by_key( $params, 'bambora_paymentrequest_action' );
				$order_id      = empty( $post ) ? $id : $post;
				$action_result = null;
				$action_name   = '';
				try {
					switch ( $action ) {
						case 'create_pr':
							$action_name   = __( 'Create Payment Request', 'bambora-online-checkout' );
							$description   = Bambora_Online_Checkout_Helper::sanitize_array_item_by_key( $params, 'description' );
							$action_result = $this->bambora_create_paymentrequest( $order_id, $description );
							break;
						case 'delete_pr':
							$action_name        = __( 'Delete Payment Request', 'bambora-online-checkout' );
							$payment_request_id = Bambora_Online_Checkout_Helper::sanitize_array_item_by_key( $params, 'payment_request_id' );
							$action_result      = $this->bambora_delete_paymentrequest( $order_id, $payment_request_id );
							break;
						case 'send_pr':
							$action_name     = __( 'Send Payment Request', 'bambora-online-checkout' );
							$recipient_name  = Bambora_Online_Checkout_Helper::sanitize_array_item_by_key( $params, 'recipient_name' );
							$recipient_email = Bambora_Online_Checkout_Helper::sanitize_array_item_by_key( $params, 'recipient_email' );
							$replyto_name    = Bambora_Online_Checkout_Helper::sanitize_array_item_by_key( $params, 'replyto_name' );
							$replyto_email   = Bambora_Online_Checkout_Helper::sanitize_array_item_by_key( $params, 'replyto_email' );
							$email_message   = Bambora_Online_Checkout_Helper::sanitize_array_item_by_key( $params, 'email_message' );
							$action_result   = $this->bambora_send_paymentrequest( $order_id, $recipient_name, $recipient_email, $replyto_name, $replyto_email, $email_message );
							break;
						default:
							$message       = __( 'PaymentRequest Action is incorrect', 'bambora-online-checkout' );
							$action_result = new WP_Error( 'bambora_online_checkout_error', $message );
							break;
					}
				} catch ( Exception $ex ) {
					$action_result = new WP_Error( 'bambora_online_checkout_error', $ex->getMessage() );
				}

				if ( is_wp_error( $action_result ) ) {
					$message = $action_result->get_error_message( 'bambora_online_checkout_error' );
					$this->boc_log->add( $message );
					Bambora_Online_Checkout_Helper::add_admin_notices( Bambora_Online_Checkout_Helper::ERROR, $message );
				} else {
					global $post;
					/* translators: %s: search term */
					$message = sprintf( __( 'The %1$s action was a success for order %2$s', 'bambora-online-checkout' ), $action_name, $order_id );
					$this->boc_log->add( $message );
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
		 * @param mixed $order_id - Order Id.
		 * @param mixed $amount - Amount.
		 * @param mixed $currency - Currency.
		 * @return bool|WP_Error
		 * @throws Exception - In case of error Throw an Exception.
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
			try {
				$capture_response = $webservice->capture( $transaction_id, $amount_in_minorunits, $currency );
				if ( ! $capture_response->meta->result ) {
					throw new Exception( $capture_response->meta->message->merchant );
				}
				do_action( 'bambora_online_checkout_after_capture', $order_id );
				return true;
			} catch ( Exception $e ) {
				/* translators: %s: search term */
				$message = sprintf( __( 'Capture action failed for order %1$s - %2$s', 'bambora-online-checkout' ), $order_id, esc_attr( $e->getMessage() ) );
				return new WP_Error( 'bambora_online_checkout_error', $message );
			}
		}

		/**
		 * Refund a payment
		 *
		 * @param mixed $order_id - Order Id.
		 * @param mixed $amount - Amount.
		 * @param mixed $currency - Currency.
		 * @return bool|WP_Error
		 * @throws Exception - In case of error Throw an Exception.
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
			try {
				$get_transaction_response = $api->get_transaction( $transaction_id );
				if ( ! $get_transaction_response->meta->result ) {
					throw new Exception( $get_transaction_response->meta->message->merchant );
				}

				$transaction            = $get_transaction_response->transaction;
				$payment_types_group_id = $transaction->information->paymenttypes[0]->groupid;
				$payment_types_id       = $transaction->information->paymenttypes[0]->id;
				$is_walley              = 19 === $payment_types_group_id && 40 === $payment_types_id;

				$refunds         = $order->get_refunds();
				$order_total     = $order->get_total();
				$webservice      = new Bambora_Online_Checkout_Api( $this->get_api_key( $order_id ) );
				$credit_response = null;
				if ( $amount === $order_total ) { // Do not send credit lines when crediting full amount.
					$credit_response = $webservice->credit( $transaction_id, $amount_in_minorunits, $currency, null );
				} else {
					$bambora_refund_lines = array();
					if ( ! $this->create_bambora_refund_lines( $refunds[0], $bambora_refund_lines, $minorunits, $order, $is_walley ) ) {
						$bambora_refund_lines = null;
					}
					$credit_response = $webservice->credit( $transaction_id, $amount_in_minorunits, $currency, $bambora_refund_lines );
				}

				if ( ! $credit_response->meta->result ) {
					throw new Exception( $credit_response->meta->message->merchant );
				}
				do_action( 'bambora_online_checkout_after_refund', $order_id );
				return true;
			} catch ( Exception $e ) {
				/* translators: %s: search term */
				$message = sprintf( __( 'Refund action failed for order %1$s - %2$s', 'bambora-online-checkout' ), $order_id, $e->getMessage() );
				return new WP_Error( 'bambora_online_checkout_error', $message );
			}
		}

		/**
		 * Delete a payment
		 *
		 * @param mixed $order_id - Order Id.
		 * @return bool|WP_Error
		 * @throws Exception - In case of error Throw an Exception.
		 */
		public function bambora_online_checkout_delete_payment( $order_id ) {
			$order          = wc_get_order( $order_id );
			$transaction_id = Bambora_Online_Checkout_Helper::get_bambora_online_checkout_transaction_id( $order );
			$webservice     = new Bambora_Online_Checkout_Api( $this->get_api_key( $order_id ) );
			try {
				$delete_response = $webservice->delete( $transaction_id );

				if ( ! $delete_response->meta->result ) {
					throw new Exception( $delete_response->meta->message->merchant );
				}
				do_action( 'bambora_online_checkout_after_delete', $order_id );
				return true;
			} catch ( Exception $e ) {
				/* translators: %s: search term */
				$message = sprintf( __( 'Delete action failed - %s', 'bambora-online-checkout' ), esc_attr( $e->getMessage() ) );
				return new WP_Error( 'bambora_online_checkout_error', $message );
			}
		}

		/**
		 * Get Payment Gateway Logo
		 *
		 * @return mixed
		 */
		public function get_icon() {
			$icon_html = '<img class="bambora_payment_icon" src="' . $this->icon_checkout . '" alt="' . $this->method_title . '"/>';

			return apply_filters( 'woocommerce_gateway_icon', $icon_html, $this->id );
		}

		/**
		 * Get the Bambora Api Key
		 *
		 * @param mixed $order_id - WC Order Id.
		 * @return string
		 */
		public function get_api_key( $order_id = null ) {
			if ( isset( $order_id ) ) {
				if ( class_exists( 'sitepress' ) ) {
					$order_language = Bambora_Online_Checkout_Helper::get_wpml_order_language( $order_id );
					$merchant       = Bambora_Online_Checkout_Helper::get_wpml_option_value( 'merchant', $order_language, $this->merchant );
					$accesstoken    = Bambora_Online_Checkout_Helper::get_wpml_option_value( 'accesstoken', $order_language, $this->accesstoken );
					$secrettoken    = Bambora_Online_Checkout_Helper::get_wpml_option_value( 'secrettoken', $order_language, $this->secrettoken );

					return Bambora_Online_Checkout_Helper::generate_api_key( $merchant, $accesstoken, $secrettoken );
				}
			}

			return Bambora_Online_Checkout_Helper::generate_api_key( $this->merchant, $this->accesstoken, $this->secrettoken );
		}

		/**
		 * Check if Order is using Gatewat
		 *
		 * @param mixed $order_id - WC Order Id.
		 * @return bool
		 */
		public function module_check( $order_id ) {
			$order          = wc_get_order( $order_id );
			$payment_method = $order->get_payment_method();
			return $this->id === $payment_method;
		}
		/**
		 * Add a custom column to an order
		 *
		 * @param array $columns - An array of Columns.
		 * @return array
		 */
		public function add_custom_order_column( $columns ) {

			$columns['bambora_payment_request_field'] = __( 'Worldline Online Checkout Payment Request', 'bambora-online-checkout' );

			return $columns;
		}

		/**
		 * Populate custom Payment Request order column
		 *
		 * @param mixed    $column - Column.
		 * @param WC_Order $order - WC Order.
		 * @return void
		 * @throws Exception - In case of error Throw an Exception.
		 */
		public function populate_payment_request_custom_order_column( $column, $order ) {
			if ( 'bambora_payment_request_field' !== $column || empty( $order ) ) {
				return;
			}

			// Legacy WordPress posts storage.
			if ( is_numeric( $order ) ) {
				$order = wc_get_order( $order );
				if ( ! isset( $order ) ) {
					return;
				}
			}

			$payment_request_id = $order->get_meta( 'bambora_paymentrequest_id' );
			if ( ! isset( $payment_request_id ) || empty( $payment_request_id ) ) {
				return;
			}
			$api_key = $this->get_api_key( $order->get_id() );
			$api     = new Bambora_Online_Checkout_Api( $api_key );
			try {
				$get_payment_request_response = $api->get_payment_request( $payment_request_id );
				if ( $get_payment_request_response->meta->result ) {
					if ( empty( $get_payment_request_response->status ) ) {
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo 'No Status available';
					} else {
						$status_class = 'bambora_pr_status_' . $get_payment_request_response->status;
						$tip_data     = empty( $get_payment_request_response->description ) ? 'No description provided' : $get_payment_request_response->description;
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo '<mark class="bambora_pr_status ' . esc_attr( $status_class ) . ' tips" data-tip="' . esc_attr( $tip_data ) . '"><span>Status: ' . esc_attr( ucfirst( $get_payment_request_response->status ) ) . '</span></mark>';
					}
				} else {
					throw new Exception( $get_payment_request_response->meta->message->merchant );
				}
			} catch ( Exception $e ) {
				$error_message = "Payment Request with Id: {$payment_request_id} is not found - see log.";
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<p>' . esc_attr( $error_message ) . '</p>';
				$this->boc_log->add( $error_message );
			}
		}

		/**
		 * Custom function to register a payment method type
		 *
		 * @return void
		 */
		public function bambora_online_declare_cart_checkout_blocks_compatibility() {
			// Check if the required class exists.
			if ( class_exists( FeaturesUtil::class ) ) {
				// Declare compatibility for 'cart_checkout_blocks'.
				FeaturesUtil::declare_compatibility(
					'cart_checkout_blocks',
					__FILE__,
					true
				);
			}
		}

			/**
			 * Custom function to register a payment method type
			 *
			 * @return void
			 */
		public function bambora_online_register_order_approval_payment_method_type() {
			// Check if the required class exists.
			if ( ! class_exists(
				'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType'
			) ) {
				return;
			}

			// Include the custom Blocks Checkout class.
			require_once plugin_dir_path(
				__FILE__
			) . 'bambora-online-checkout-blocks.php';

			// Hook the registration function to the 'woocommerce_blocks_payment_method_type_registration' action.
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function (
					Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry
				) {
					$payment_method_registry->register( new Bambora_Online_Checkout_Blocks( $this->get_instance() ) );
				}
			);
		}
	}
	Bambora_Online_Checkout::get_instance()->init_hooks();
}
