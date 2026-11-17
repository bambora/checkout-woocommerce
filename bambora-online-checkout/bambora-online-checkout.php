<?php

/**
 * Plugin Name: Worldline Online Checkout
 * Plugin URI: https://worldline.com/
 * Description: Worldline Online Checkout Payment Gateway for WooCommerce (prev. Bambora Online Checkout)
 * Version: 9.0.0
 * Author: Bambora
 * Author URI: https://worldline.com/
 * Text Domain: bambora-online-checkout
 * Requires PHP: 8.2
 * WC requires at least: 8.0
 * WC tested up to: 11.3.0
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @author  Bambora
 * @package bambora_online_checkout
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

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

	// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Existing constants that other code may use. Renaming them would break it.
	define( 'BOC_LIB', __DIR__ . '/lib/' );
	define( 'BOC_MODELS', __DIR__ . '/models/' );
	define( 'BOC_VERSION', '9.0.0' );
	// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound

	// Including Bambora files!
	include BOC_LIB . 'bambora-online-checkout-endpoints.php';
	include BOC_LIB . 'bambora-online-checkout-api.php';
	include BOC_LIB . 'bambora-online-checkout-helper.php';
	include BOC_LIB . 'bambora-online-checkout-currency.php';
	include BOC_LIB . 'bambora-online-checkout-log.php';
	include BOC_LIB . 'bambora-online-checkout-php-version.php';
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

	Bambora_Online_Checkout_Php_Version::init();

	/**
	 * Worldline Online Checkout
	 **/
	class Bambora_Online_Checkout extends WC_Payment_Gateway {
		/**
		 * The admin-post action (and nonce action prefix) for capture and delete.
		 */
		const PAYMENT_ACTION = 'bambora_online_checkout_payment_action';
		/**
		 * The admin-post action (and nonce action prefix) for payment requests.
		 */
		const PAYMENTREQUEST_ACTION = 'bambora_online_checkout_paymentrequest_action';
		/**
		 * Order meta holding the id of a transaction that did not match the order total or currency.
		 */
		const AMOUNT_MISMATCH_META = 'bambora_online_checkout_amount_mismatch';
		/**
		 * Prefix for the transients used to cache responses from the Worldline API.
		 */
		const CACHE_PREFIX = 'boc_cache_';

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
			// Define user set variables! Settings that are not saved yet use the default from the form fields.
			$this->enabled     = $this->get_option( 'enabled' );
			$this->title       = $this->get_option( 'title' );
			$this->description = $this->get_option( 'description' );
			foreach ( array( 'merchant', 'accesstoken', 'secrettoken', 'paymentwindowid', 'instantcapture', 'instantcaptureonrenewal', 'immediateredirecttoaccept', 'addsurchargetoshipment', 'md5key', 'roundingmode', 'captureonstatuscomplete', 'rolecapturerefunddelete', 'allowlowvalueexemption', 'limitforlowvalueexemption', 'termsandconditions' ) as $key ) {
				$this->$key = $this->get_option( $key );
			}
		}

		/**
		 * Initialize module hooks
		 */
		public function init_hooks() {
			// Filters!
			add_filter(
				'woocommerce_payment_gateways',
				function ( $methods ) {
					// The instance is added instead of the class name, so WooCommerce does not create a second gateway.
					$methods[] = $this;
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
					'bambora_subscription_cancellation',
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
							// Public by design: the checkout calls it for guest customers. The arguments are strictly validated.
							'permission_callback' => '__return_true',
							'args'                => array(
								'amount'   => array(
									'required' => true,
									'type'     => 'integer',
									'minimum'  => 0,
								),
								'currency' => array(
									'required'          => true,
									'type'              => 'string',
									'pattern'           => '^[A-Za-z]{3}$',
									// WordPress only adds the schema validation by default when there is no custom sanitize_callback.
									'validate_callback' => 'rest_validate_request_arg',
									// Sanitizing runs before validation, so non strings are left for the validation to reject.
									'sanitize_callback' => function ( $value ) {
										return is_string( $value ) ? strtoupper( $value ) : $value;
									},
								),
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
					),
					10,
					2
				);
				add_action(
					'woocommerce_update_options_payment_gateways_' . $this->id,
					array(
						$this,
						'process_admin_options',
					)
				);
				add_action(
					'admin_post_' . self::PAYMENT_ACTION,
					array(
						$this,
						'bambora_online_checkout_actions',
					)
				);
				add_action(
					'admin_post_' . self::PAYMENTREQUEST_ACTION,
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
					'admin_init',
					array(
						$this,
						'redirect_outdated_admin_script_request',
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

			if ( 'yes' === $this->enabled && ! $this->has_md5_key() && current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce capability.
				$settings_url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $this->id );
				$message      = __( 'Worldline Online Checkout is not offered to your customers, because the MD5 key is missing. Configure the same MD5 key in the module settings and in the Worldline administration.', 'bambora-online-checkout' );
				echo '<div class="notice notice-error"><p>' . esc_html( $message ) . ' <a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Open the module settings', 'bambora-online-checkout' ) . '</a></p></div>';
			}
		}

		/**
		 * The admin script before version 9.0.0 (ex. a copy cached by the browser) sends the actions as GET parameters.
		 * Those are no longer handled, so show a notice and redirect to the page without the parameters.
		 *
		 * @return void
		 */
		public function redirect_outdated_admin_script_request() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Only checks if the parameters exist. Nothing is processed.
			if ( wp_doing_ajax() || ( ! isset( $_GET['bambora_action'] ) && ! isset( $_GET['bambora_paymentrequest_action'] ) ) ) {
				return;
			}
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
			if ( ! current_user_can( 'edit_shop_orders' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce capability.
				return;
			}

			Bambora_Online_Checkout_Helper::add_admin_notices(
				Bambora_Online_Checkout_Helper::ERROR,
				__( 'The action was not performed, because your browser used an outdated version of the Worldline Online Checkout script. Please reload the page (Ctrl+F5) and try again.', 'bambora-online-checkout' )
			);
			$legacy_parameters = array( 'bambora_action', 'bambora_paymentrequest_action', 'bambora_nonce', 'amount', 'currency', 'description', 'recipient_name', 'recipient_email', 'replyto_name', 'replyto_email', 'email_message' );
			wp_safe_redirect( remove_query_arg( $legacy_parameters ) );
			exit;
		}

		/**
		 * Enqueue Admin Styles and Scripts
		 *
		 * @return void
		 */
		public function enqueue_wc_bambora_online_checkout_admin_styles_and_scripts() {
			$admin_css = 'assets/style/bambora-online-checkout-admin.css';
			$admin_js  = 'assets/js/bambora-online-checkout-admin.js';
			wp_register_style( 'bambora_online_checkout_admin_style', plugins_url( $admin_css, __FILE__ ), array(), Bambora_Online_Checkout_Helper::get_asset_version( $admin_css ) );
			wp_enqueue_style( 'bambora_online_checkout_admin_style' );
			wp_enqueue_script( 'bambora_online_checkout_admin', plugins_url( $admin_js, __FILE__ ), array( 'jquery' ), Bambora_Online_Checkout_Helper::get_asset_version( $admin_js ), true );
			wp_localize_script(
				'bambora_online_checkout_admin',
				'bambora_online_checkout_admin',
				array(
					'admin_post_url' => admin_url( 'admin-post.php' ),
				)
			);
		}

		/**
		 * Enqueue Frontend Styles and Scripts
		 *
		 * @return void
		 */
		public function enqueue_wc_bambora_online_checkout_front_styles() {
			$front_css = 'assets/style/bambora-online-checkout-front.css';
			wp_register_style( 'bambora_online_checkout_front_style', plugins_url( $front_css, __FILE__ ), array(), Bambora_Online_Checkout_Helper::get_asset_version( $front_css ) );
			wp_enqueue_style( 'bambora_online_checkout_front_style' );
		}

		/**
		 * Initialise Gateway Settings Form Fields
		 *
		 * @return void
		 */
		public function init_form_fields() {
			$roles         = wp_roles()->roles;
			$roles_options = array();
			foreach ( $roles as $role => $details ) {
				$roles_options[ $role ] = $details['name'];
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
					'title'             => 'Merchant number',
					'type'              => 'text',
					'description'       => '(Required) The number identifying your Worldline merchant account.',
					'default'           => '',
					'custom_attributes' => array( 'required' => 'required' ),
				),
				'accesstoken'               => array(
					'title'             => 'Access token',
					'type'              => 'text',
					'description'       => '(Required) The Access token for the API user received from the Worldline administration.',
					'default'           => '',
					'custom_attributes' => array( 'required' => 'required' ),
				),
				'secrettoken'               => array(
					'title'             => 'Secret token',
					'type'              => 'password',
					'description'       => '(Required) The Secret token for the API user received from the Worldline administration.',
					'default'           => '',
					'custom_attributes' => array( 'required' => 'required' ),
				),
				'md5key'                    => array(
					'title'             => 'MD5 Key',
					'type'              => 'password',
					'description'       => '(Required) The MD5 key is used to stamp data sent between WooCommerce and Worldline to prevent it from being tampered with. The MD5 key must be the same as in the Bambora administration.',
					'default'           => '',
					'custom_attributes' => array( 'required' => 'required' ),
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
					'default'     => Bambora_Online_Checkout_Currency::ROUND_DEFAULT,
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
		 * Get the sanitized value of a settings field. Called by WooCommerce on save.
		 * Fields marked as required (custom_attributes.required) that are posted empty
		 * add an error to the settings page and keep the previously saved value.
		 *
		 * @param string $key - Field key.
		 * @param array  $field - Field definition.
		 * @param array  $post_data - Posted data.
		 * @return string|array
		 */
		public function get_field_value( $key, $field, $post_data = array() ) {
			$value = parent::get_field_value( $key, $field, $post_data );

			if ( ! empty( $field['custom_attributes']['required'] ) && '' === $value ) {
				WC_Admin_Settings::add_error( "{$field['title']} is required." );
				return $this->get_option( $key );
			}

			return $value;
		}

		/**
		 * Save the settings. The settings are loaded again afterwards, since WooCommerce uses this same instance.
		 *
		 * @return bool
		 */
		public function process_admin_options() {
			$saved = parent::process_admin_options();
			$this->init_bambora_online_checkout_settings();
			return $saved;
		}

		/**
		 * Check if the MD5 key is configured.
		 *
		 * @return bool
		 */
		public function has_md5_key() {
			return '' !== trim( (string) $this->md5key );
		}

		/**
		 * The payment method is not offered without an MD5 key, since the payments could not be validated
		 * and the customer would get an error at the last step of the checkout.
		 *
		 * @return bool
		 */
		public function is_available() {
			if ( ! $this->has_md5_key() ) {
				return false;
			}
			return parent::is_available();
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
					Note: Users with access to String Translation can read the Secret token and the MD5 key.
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
					$html .= '<p class="bambora_credentials_success">The credentials for your Worldline account are valid.</p>';
				}
			} catch ( Exception $e ) {
				$html .= '<p class="bambora_credentials_error">The credentials you have provided for your Worldline account are not valid. Please check them before you enable Worldline as a payment option.</p>';
				$this->boc_log->add( "Credential validation failed: {$e->getMessage()}" );
			}

			if ( empty( $this->md5key ) ) {
				$html .= '<p class="bambora_credentials_error">The MD5 key is not set. Please configure it in the module settings.</p>';
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
			$transaction_id = Bambora_Online_Checkout_Helper::get_bambora_online_checkout_transaction_id( $order );
			if ( empty( $transaction_id ) ) {
				// The order was never paid with Worldline Online Checkout, so there is nothing to capture.
				return;
			}

			try {
				$api                      = new Bambora_Online_Checkout_Api( $this->get_api_key( $order_id ) );
				$get_transaction_response = $api->get_transaction( $transaction_id );
				if ( ! $get_transaction_response->meta->result ) {
					throw new Exception( $get_transaction_response->meta->message->merchant );
				}
				$transaction = $get_transaction_response->transaction;
				$currency    = $order->get_currency();
				$minorunits  = Bambora_Online_Checkout_Currency::get_currency_minorunits( $currency );
				$order_total = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $order->get_total(), $minorunits, $this->roundingmode );

				// Capture what is left of the order total, but never more than Worldline allows.
				$amount_to_capture = min( (int) $transaction->available->capture, $order_total - (int) $transaction->total->captured );
				if ( $amount_to_capture <= 0 ) {
					// Already captured, ex. with instant capture.
					return;
				}
				$capture_result = $this->bambora_online_checkout_capture_payment( $order_id, Bambora_Online_Checkout_Currency::convert_price_from_minorunits( $amount_to_capture, $minorunits ), $currency );
			} catch ( Exception $e ) {
				/* translators: %s: search term */
				$capture_result = new WP_Error( 'bambora_online_checkout_error', sprintf( __( 'Capture action failed for order %1$s - %2$s', 'bambora-online-checkout' ), $order_id, $e->getMessage() ) );
			}

			if ( is_wp_error( $capture_result ) ) {
				$message = $capture_result->get_error_message( 'bambora_online_checkout_error' );
				$this->boc_log->add( $message );
				// The status can be changed without a logged in user (ex. by an integration), so the error is also added to the order.
				$order->add_order_note( $message );
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
			$html = '';
			$cart = WC()->cart;
			if ( isset( $cart ) ) {
				$currency          = get_woocommerce_currency();
				$minorunits        = Bambora_Online_Checkout_Currency::get_currency_minorunits( $currency );
				$amount_minorunits = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $cart->total, $minorunits, $this->roundingmode );
				$payment_groups    = $this->get_payment_types(
					array(
						'amount'   => $amount_minorunits,
						'currency' => $currency,
					)
				);
				$logos_html        = '';
				foreach ( $payment_groups as $cards ) {
					foreach ( $cards as $card ) {
						foreach ( $card->assets as $asset ) {
							if ( 'logo' === $asset->type ) {
								$logos_html .= '<img title="' . esc_attr( $card->displayname ) . '" alt="' . esc_attr( $card->displayname ) . '" src="' . esc_url( $asset->data ) . '" />';
							}
						}
					}
				}
				if ( ! empty( $logos_html ) ) {
					$html .= '<div class="bambora_payment_types">' . $logos_html . '</div>';
				}
			}
			if ( ! empty( $this->description ) ) {
				$html .= '<div class="bambora_payment_text">' . wpautop( wptexturize( wp_kses_post( $this->description ) ) ) . '</div>';
			}
			if ( empty( $html ) ) {
				return;
			}
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped when the HTML is built.
			echo '<div class="bambora_payment_description">' . $html . '</div>';
		}

		/**
		 * Legacy - The small Worldline logo shown next to the title when not using Blocks.
		 *
		 * @return string
		 */
		public function get_icon() {
			$icon = '<img class="bambora_payment_icon" src="' . esc_url( $this->icon_checkout ) . '" alt="Worldline" />';
			return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce hook.
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
			if ( 'yes' !== $this->enabled ) {
				return $response;
			}
			try {
				$amout_minorunits = (int) $request['amount'];
				$currency         = strtoupper( (string) $request['currency'] );

				// The payment types are cached for a short time. The checkout asks for them on every update,
				// and the REST endpoint is public, so this also limits the calls to the Worldline API.
				$cache_key = self::CACHE_PREFIX . 'pt_' . md5( "{$this->merchant}|{$currency}|{$amout_minorunits}" );
				$cached    = get_transient( $cache_key );
				if ( is_array( $cached ) ) {
					return $cached;
				}

				$api_key                    = $this->get_api_key();
				$api                        = new Bambora_Online_Checkout_Api( $api_key );
				$get_payment_types_response = $api->get_payment_types( $currency, $amout_minorunits );
				if ( ! $get_payment_types_response->meta->result ) {
					throw new Exception( $get_payment_types_response->meta->message->merchant );
				} else {
					foreach ( $get_payment_types_response->paymentcollections as $paymentcollection ) {
						$response[] = $paymentcollection->paymentgroups;
					}
					set_transient( $cache_key, $response, 10 * MINUTE_IN_SECONDS );
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
		 * @param mixed    $amount_to_charge - Amount to Charge.
		 * @param WC_Order $renewal_order - Order to Renew.
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
						// WooCommerce Subscriptions can save an incomplete renewal order when the database rolls back part of it
						// (ex. MariaDB "Record has changed since last read" while another order is saved at the same time).
						// Do not charge such an order.
						if ( empty( $order_currency ) || (int) $renewal_order->get_customer_id() !== (int) $subscription->get_customer_id() ) {
							throw new Exception( __( 'The renewal order is incomplete (missing currency or customer), so it was not saved correctly and is not charged. Create a new renewal order from the subscription.', 'bambora-online-checkout' ) );
						}
						$authorize_subscription_response = $api->authorize_subscription( $bambora_subscription_id, $amount, $order_currency, $renewal_order_id, $instant_capture_amount );
						if ( $authorize_subscription_response->meta->result ) {
							/* translators: %s: search term */
							$order_note = sprintf( __( 'Worldline Online Checkout Subscription (%1$s) was authorized for renewal order %2$s with transaction id %3$s', 'bambora-online-checkout' ), $bambora_subscription_id, $renewal_order_id, $authorize_subscription_response->transactionid );
							$renewal_order->add_order_note( $order_note );
							$renewal_order->payment_complete( $authorize_subscription_response->transactionid );
							$subscription->payment_complete_for_order( $renewal_order );
							$result = true;
						} else {
							throw new Exception( $authorize_subscription_response->meta->message->merchant );
						}
					} catch ( Exception $e ) {
						/* translators: %s: search term */
						$order_note = sprintf( __( 'Worldline Online Checkout Subscription Id: %1$s could not be renewed - %2$s', 'bambora-online-checkout' ), $bambora_subscription_id, $e->getMessage() );
						$this->boc_log->add( $order_note );
						$renewal_order->add_order_note( $order_note );
						$subscription->payment_failed_for_related_order( 'on-hold', $renewal_order );
					}
				}
				// Remove the Worldline Online Checkout subscription id copied from the subscription.
				$renewal_order->delete_meta_data( Bambora_Online_Checkout_Helper::BAMBORA_ONLINE_CHECKOUT_SUBSCRIPTION_ID );
				$renewal_order->save();
				$subscription->add_order_note( $order_note );
				$subscription->save();
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
		public function bambora_subscription_cancellation( $subscription, $force_delete = false ) {
			$result = false;
			if ( Bambora_Online_Checkout_Helper::order_is_subscription( $subscription ) && ( 'cancelled' === $subscription->get_status() || $force_delete ) ) {
					$bambora_subscription_id = Bambora_Online_Checkout_Helper::get_bambora_online_checkout_subscription_id( $subscription );
					$order_note              = '';
				if ( empty( $bambora_subscription_id ) ) {
					$order_note = __( 'Worldline Online Checkout Subscription ID was not found', 'bambora-online-checkout' );
				} elseif ( $this->is_bambora_subscription_used_by_other_subscription( $bambora_subscription_id, $subscription->get_id() ) ) {
					// Subscriptions bought in the same order share the Worldline subscription.
					/* translators: %s: search term */
					$order_note = sprintf( __( 'Worldline Online Checkout Subscription Id: %s was not deleted at Worldline, because it is used by another subscription', 'bambora-online-checkout' ), $bambora_subscription_id );
					$result     = true;
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
					$subscription->save();
			}
			return $result;
		}

		/**
		 * Check if another subscription, that is not cancelled, uses the same Worldline Online Checkout subscription.
		 *
		 * @param string $bambora_subscription_id - Bambora Subscription Id.
		 * @param int    $subscription_id - The WC Subscription Id to leave out.
		 * @return bool
		 */
		protected function is_bambora_subscription_used_by_other_subscription( $bambora_subscription_id, $subscription_id ) {
			$subscription_ids = wc_get_orders(
				array(
					'type'       => 'shop_subscription',
					'status'     => array( 'wc-active', 'wc-on-hold', 'wc-pending', 'wc-pending-cancel' ),
					'limit'      => 2,
					'return'     => 'ids',
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Only runs when a subscription is cancelled or changed.
					'meta_query' => array(
						array(
							'key'   => Bambora_Online_Checkout_Helper::BAMBORA_ONLINE_CHECKOUT_SUBSCRIPTION_ID,
							'value' => $bambora_subscription_id,
						),
					),
				)
			);
			if ( ! is_array( $subscription_ids ) ) {
				return false;
			}
			$other_subscription_ids = array_diff( array_map( 'intval', $subscription_ids ), array( (int) $subscription_id ) );

			return ! empty( $other_subscription_ids );
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
			$message = '';
			try {
				if ( empty( $this->md5key ) ) {
					$message = __( 'The MD5 key is missing. Please configure it in the plugin settings.', 'bambora-online-checkout' );
					throw new Exception( 'The MD5 key is missing. Please configure it in the plugin settings.' );
				}
				$api_key                       = $this->get_api_key( $order_id );
				$api                           = new Bambora_Online_Checkout_Api( $api_key );
				$bambora_checkout_request      = $this->create_bambora_checkout_request( $order );
				$set_checkout_session_response = $api->set_checkout_session( $bambora_checkout_request );
				if ( ! $set_checkout_session_response->meta->result ) {
					$message = __( 'Could not retrive the payment window. Reason:', 'bambora-online-checkout' ) . ' ' . $set_checkout_session_response->meta->message->enduser;
					throw new Exception( "Could not retrive the payment window. Reason: {$set_checkout_session_response->meta->message->merchant}" );
				}
				return array(
					'result'   => 'success',
					'redirect' => $set_checkout_session_response->url,
				);
			} catch ( Throwable $e ) {
				$this->boc_log->add( $e->getMessage() );
				if ( empty( $message ) ) {
					$message = __( 'Could not retrive the payment window. Please try again or contact us for assistance.', 'bambora-online-checkout' );
				}
				// Classic checkout shows the notice.
				wc_add_notice( $message, 'error' );

				// Block checkout (Store API) clears all notices added in process_payment
				// and only shows the 'message' from the result, or a generic error.
				return array(
					'result'   => 'failure',
					'redirect' => '',
					'message'  => $message,
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
			} elseif ( 'yes' === $this->allowlowvalueexemption ) {
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
			$bambora_order->billingaddress  = $this->create_bambora_address( $order, 'billing' );
			$bambora_order->currency        = $currency;
			$bambora_order->id              = $this->clean_order_number( $order->get_order_number() );
			$bambora_order->shippingaddress = $this->create_bambora_address( $order, $order->has_shipping_address() ? 'shipping' : 'billing' );
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
		 * @param string   $type - Address type, 'billing' or 'shipping'.
		 * @return Bambora_Online_Checkout_Address
		 */
		protected function create_bambora_address( $order, $type ) {
			$address                    = $order->get_address( $type );
			$bambora_address            = new Bambora_Online_Checkout_Address();
			$bambora_address->att       = '';
			$bambora_address->city      = $address['city'];
			$bambora_address->country   = $address['country'];
			$bambora_address->firstname = $address['first_name'];
			$bambora_address->lastname  = $address['last_name'];
			$bambora_address->street    = $address['address_1'];
			$bambora_address->zip       = $address['postcode'];

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
			$api_key = $this->get_api_key( $order_id );
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
			$api_key = $this->get_api_key( $order_id );
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
			$api_key                   = $this->get_api_key( $order_id );
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
			$bambora_subscription              = new Bambora_Online_Checkout_Subscription();
			$bambora_subscription->action      = 'create';
			$bambora_subscription->description = 'WooCommerce Subscription v.' . WC_Subscriptions::$version;
			$bambora_subscription->reference   = $this->merchant;

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
				$item_total          = $order->get_line_total( $item, false, true );
				$item_total_incl_vat = $order->get_line_total( $item, true, true );
				$item_vat_amount     = $order->get_line_tax( $item );
				// The quantity can be 0 on orders made in the administration.
				$item_quantity             = 0 < $item['qty'] ? $item['qty'] : 1;
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
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Worldline calls the callback server to server, so there is no nonce. The parameters are signed with the MD5 key instead and validated before use.
			if ( ! isset( $_GET ) || empty( $_GET ) ) {
				die( 'Callback is missing GET parameters' );
			}
			$params = wp_unslash( $_GET );
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
			$message       = '';
			$error_message = '';
			$order         = null;
			$transaction   = null;
			$response_code = 400;
			try {
				// Validate Callback Parameters, MD5 and Transaction.
				$api_key = $this->get_api_key( Bambora_Online_Checkout_Helper::sanitize_array_item_by_key( $params, 'wcorderid' ) );
				if ( Bambora_Online_Checkout_Helper::validate_bambora_online_checkout_callback( $params, $this->md5key, $api_key, $order, $transaction, $message ) ) {
					// A paid payment request is closed at Worldline, so the order list must not keep showing the cached status.
					// Only done after the validation, so unsigned callbacks cannot clear the cache.
					$this->clear_payment_request_status_cache( $order );
					$subscription_id = Bambora_Online_Checkout_Helper::sanitize_array_item_by_key( $params, 'subscriptionid' );
					$message         = $this->process_bambora_online_checkout_callback( $order, $transaction, $subscription_id );
					$response_code   = 200;
				} else {
					$error_message = $message;
				}
			} catch ( Throwable $e ) {
				$message       = 'An internal error occured - see log for more information.';
				$error_message = $e->getMessage();
				$response_code = 500;
			}
			if ( 200 !== $response_code ) {
				$this->boc_log->add( "Callback failed - {$error_message} - GET params: ?" . http_build_query( $params ) );
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
			if ( $this->is_known_amount_mismatch( $order, $bambora_transaction ) ) {
				return 'on hold - amount mismatch (Called multiple times)';
			}
			$action                  = '';
			$existing_transaction_id = Bambora_Online_Checkout_Helper::get_bambora_online_checkout_transaction_id( $order );
			// Handling of Multiple callbacks. A new transaction for an order that is not paid yet is handled as the first.
			if ( empty( $existing_transaction_id ) || ( $bambora_transaction->id !== $existing_transaction_id && ! $order->is_paid() ) ) {
				if ( $this->hold_order_on_amount_mismatch( $order, $bambora_transaction ) ) {
					return 'on hold - amount mismatch';
				}
				$this->add_surcharge_fee_to_order( $order, $bambora_transaction );
				/* translators: %s: search term */
				$order->add_order_note( sprintf( __( 'Worldline Online Checkout Payment completed with transaction id %s', 'bambora-online-checkout' ), $bambora_transaction->id ) );
				$order->delete_meta_data( self::AMOUNT_MISMATCH_META );
				$action = 'created';
			} else {
				$action = 'created (Called multiple times)';
			}
			$order->payment_complete( $bambora_transaction->id );

			return $action;
		}

		/**
		 * Check if the transaction was already put on hold, because it did not match the order.
		 *
		 * @param WC_Order $order - WC Order.
		 * @param mixed    $bambora_transaction - Bambora Transaction.
		 * @return bool
		 */
		protected function is_known_amount_mismatch( $order, $bambora_transaction ) {
			$mismatch_transaction_id = $order->get_meta( self::AMOUNT_MISMATCH_META, true );
			return ! empty( $mismatch_transaction_id ) && (string) $bambora_transaction->id === (string) $mismatch_transaction_id;
		}

		/**
		 * Validate that the authorized amount and the currency of the transaction match the order.
		 *
		 * The callback is signed by Worldline, but the order can have changed after the payment window was opened
		 * (ex. the customer changed the cart in another tab, which updates the same order). In that case the order
		 * is not marked as paid. It is put on hold with a note, so the merchant can check the payment.
		 * The authorized amount includes the surcharge fee, so the amount is accepted both with and without the fee.
		 *
		 * @param WC_Order $order - WC Order.
		 * @param mixed    $bambora_transaction - Bambora Transaction.
		 * @return bool - True if the transaction does not match and the order was put on hold.
		 */
		protected function hold_order_on_amount_mismatch( $order, $bambora_transaction ) {
			/**
			 * Filter to turn off the validation of the authorized amount and currency against the order.
			 *
			 * @param bool     $validate - Validate the amount and currency. Default true.
			 * @param WC_Order $order - WC Order.
			 * @param object   $bambora_transaction - Bambora Transaction.
			 */
			if ( ! apply_filters( 'bambora_online_checkout_validate_callback_amount', true, $order, $bambora_transaction ) ) {
				return false;
			}

			$order_currency       = strtoupper( (string) $order->get_currency() );
			$minorunits           = Bambora_Online_Checkout_Currency::get_currency_minorunits( $order_currency );
			$order_total          = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $order->get_total(), $minorunits, $this->roundingmode );
			$authorized           = (int) $bambora_transaction->total->authorized;
			$fee_amount           = isset( $bambora_transaction->total->feeamount ) ? (int) $bambora_transaction->total->feeamount : 0;
			$transaction_currency = ! empty( $bambora_transaction->currency->code ) ? strtoupper( (string) $bambora_transaction->currency->code ) : $order_currency;

			if ( $order_currency === $transaction_currency && in_array( $order_total, array( $authorized, $authorized - $fee_amount ), true ) ) {
				return false;
			}

			$note = sprintf(
				/* translators: 1: transaction id, 2: authorized amount, 3: order total */
				__( 'Worldline Online Checkout payment with transaction id %1$s was not accepted automatically: the authorized amount %2$s does not match the order total %3$s. Check the payment before you process the order.', 'bambora-online-checkout' ),
				$bambora_transaction->id,
				$this->format_amount( Bambora_Online_Checkout_Currency::convert_price_from_minorunits( $authorized, $minorunits ), $minorunits, $transaction_currency ),
				$this->format_amount( Bambora_Online_Checkout_Currency::convert_price_from_minorunits( $order_total, $minorunits ), $minorunits, $order_currency )
			);
			$this->boc_log->add( "Callback for order {$order->get_id()} - {$note}" );

			$order->update_meta_data( self::AMOUNT_MISMATCH_META, (string) $bambora_transaction->id );
			if ( $order->is_paid() ) {
				// The order is already paid with another transaction. Only add the note.
				$order->add_order_note( $note );
				$order->save();
			} else {
				// The transaction is stored on the order, so it is shown in the order administration where it can be captured or deleted.
				$order->set_transaction_id( $bambora_transaction->id );
				$order->update_status( 'on-hold', $note );
			}

			return true;
		}

		/**
		 * Process the subscription
		 *
		 * @param WC_Order|WC_Subscription $order - WC_Order or WC_Subscription.
		 * @param mixed                    $bambora_transaction - Bambora Transaction.
		 * @param string                   $bambora_subscription_id - Bambora Subscription Id.
		 * @return string
		 */
		protected function process_subscription( $order, $bambora_transaction, $bambora_subscription_id ) {
			$action = '';
			if ( Bambora_Online_Checkout_Helper::order_is_subscription( $order ) ) {
				// The customer changed the payment method for the subscription. Nothing is paid, so there is no amount to validate.
				$subscription                     = $order;
				$existing_bambora_subscription_id = Bambora_Online_Checkout_Helper::get_bambora_online_checkout_subscription_id( $subscription );
				if ( $bambora_subscription_id === $existing_bambora_subscription_id ) {
					// Do not cancel subscription if the callback is called more than once !
					$action = 'changed (Called multiple times)';
				} else {
					// The subscription has no existing Worldline subscription when it was paid with another payment method before.
					if ( ! empty( $existing_bambora_subscription_id ) ) {
						$this->bambora_subscription_cancellation( $subscription, true );
						/* translators: %s: search term */
						$subscription->add_order_note( sprintf( __( 'Worldline Online Checkout Subscription changed from: %1$s to: %2$s', 'bambora-online-checkout' ), $existing_bambora_subscription_id, $bambora_subscription_id ) );
					}
					$action = 'changed';
					$this->save_subscription_meta( $subscription, $bambora_subscription_id );
				}
			} elseif ( $this->is_known_amount_mismatch( $order, $bambora_transaction ) ) {
				$action = 'on hold - amount mismatch (Called multiple times)';
			} else {
				// Do not add surcharge if the callback is called more than once!
				$existing_transaction_id = Bambora_Online_Checkout_Helper::get_bambora_online_checkout_transaction_id( $order );
				if ( $bambora_transaction->id !== $existing_transaction_id ) {
					if ( $this->hold_order_on_amount_mismatch( $order, $bambora_transaction ) ) {
						return 'on hold - amount mismatch';
					}
					$this->add_surcharge_fee_to_order( $order, $bambora_transaction );
					$action = 'activated';

					$subscriptions = Bambora_Online_Checkout_Helper::get_subscriptions_for_order( $order );
					foreach ( $subscriptions as $subscription ) {
						// Cancel an existing subscription if a new one is created.
						$existing_bambora_subscription_id = Bambora_Online_Checkout_Helper::get_bambora_online_checkout_subscription_id( $subscription );
						if ( ! empty( $existing_bambora_subscription_id ) && $bambora_subscription_id !== $existing_bambora_subscription_id ) {
							$action = 'changed';
							/* translators: %s: search term */
							$subscription->add_order_note( sprintf( __( 'Worldline Online Checkout Subscription changed from: %1$s to: %2$s', 'bambora-online-checkout' ), $existing_bambora_subscription_id, $bambora_subscription_id ) );
							$this->bambora_subscription_cancellation( $subscription, true );
						}
						$this->save_subscription_meta( $subscription, $bambora_subscription_id );
					}
					/* translators: %s: search term */
					$order->add_order_note( sprintf( __( 'Worldline Online Checkout Subscription activated with subscription id: %s', 'bambora-online-checkout' ), $bambora_subscription_id ) );
					$order->delete_meta_data( Bambora_Online_Checkout_Helper::BAMBORA_ONLINE_CHECKOUT_SUBSCRIPTION_ID );
					$order->delete_meta_data( self::AMOUNT_MISMATCH_META );
					$order->payment_complete( $bambora_transaction->id );
				} else {
					$action = 'activated or changed (Called multiple times)';
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
		 * @param WC_Subscription $subscription - Subscription.
		 * @param string          $bambora_subscription_id - Bambora Subscription Id.
		 * @return void
		 */
		protected function save_subscription_meta( $subscription, $bambora_subscription_id ) {
			$bambora_subscription_id = wc_clean( $bambora_subscription_id );
			$subscription_id         = $subscription->get_id();
			/* translators: %s: search term */
			$subscription->add_order_note( sprintf( __( 'Worldline Online Checkout Subscription activated with subscription id: %1$s by order %2$s', 'bambora-online-checkout' ), $bambora_subscription_id, $subscription_id ) );
			$subscription->update_meta_data( Bambora_Online_Checkout_Helper::BAMBORA_ONLINE_CHECKOUT_SUBSCRIPTION_ID, $bambora_subscription_id );
			$subscription->save();
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
			if ( ! $this->current_user_can_manage_payments() ) {
				// The user can only view the data.
				return new WP_Error( 'notpermitted', __( 'Your user role is not allowed to refund via Worldline Online Checkout', 'bambora-online-checkout' ) );
			}

			if ( ! isset( $amount ) || (float) $amount <= 0 ) {
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
				$line->vat                 = round( (float) ( 0 < abs( $line_vat ) && 0 < abs( $line_total ) ? ( abs( $line_vat ) / abs( $line_total ) ) * 100 : 0 ), 2, Bambora_Online_Checkout_Currency::roundingmode( $this->roundingmode ) );

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
				$additional_refund_orderline->description = __( 'Refund', 'bambora-online-checkout' ) . ( ! empty( $reason ) ? ": {$reason}" : '' );
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
		 * @param string           $post_type - From where is the function called.
		 * @param WP_Post|WC_Order $post_or_order - The post (legacy storage) or the order (HPOS).
		 * @return void
		 * @throws Exception - In case of error Throw an Exception.
		 */
		public function bambora_online_checkout_meta_boxes( $post_type, $post_or_order = null ) {
			if ( 'shop_order' !== $post_type && 'woocommerce_page_wc-orders' !== $post_type ) {
				return;
			}

			$order = $this->get_order_from_post_or_order( $post_or_order );
			if ( empty( $order ) || ! $this->module_check( $order->get_id() ) ) {
				return;
			}
			$order_total    = $order->get_total();
			$transaction_id = Bambora_Online_Checkout_Helper::get_bambora_online_checkout_transaction_id( $order );
			$status         = $order->get_status();
			// Only show create payment request if the order is created, has a total over 0 and does not have another payment method selected and no other transaction.
			if ( ! empty( $status ) && 'auto-draft' !== $status && 0 < $order_total && empty( $transaction_id ) ) {
				$payment_request_allowed = $this->is_payment_request_allowed( $this->get_api_key( $order->get_id() ) );
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
		 * Check if the API user is allowed to create payment requests.
		 * The answer is cached, so the order page does not have to wait for the Worldline API every time.
		 *
		 * @param string $api_key - Bambora Api Key.
		 * @return bool
		 */
		private function is_payment_request_allowed( $api_key ) {
			$cache_key = self::CACHE_PREFIX . 'pr_' . md5( wp_hash( $api_key ) );
			$cached    = get_transient( $cache_key );
			if ( 'yes' === $cached || 'no' === $cached ) {
				return 'yes' === $cached;
			}

			$payment_request_allowed = false;
			try {
				$api                                   = new Bambora_Online_Checkout_Api( $api_key );
				$get_merchant_api_permissions_response = $api->get_merchant_api_permissions();
				if ( ! $get_merchant_api_permissions_response->meta->result ) {
					throw new Exception( $get_merchant_api_permissions_response->meta->message->merchant );
				}
				foreach ( $get_merchant_api_permissions_response->functionpermissions as $function_permission ) {
					if ( Bambora_Online_Checkout_Api::PERMISSION_PAYMENT_REQUEST === $function_permission->name ) {
						$payment_request_allowed = true;
						break;
					}
				}
				// A missing permission is cached for a shorter time, so it is found soon after Worldline has added it.
				set_transient( $cache_key, $payment_request_allowed ? 'yes' : 'no', $payment_request_allowed ? HOUR_IN_SECONDS : 5 * MINUTE_IN_SECONDS );
			} catch ( Exception $e ) {
				$this->boc_log->add( "Payment Request Permissions could not be retrived: {$e->getMessage()}" );
			}

			return $payment_request_allowed;
		}

		/**
		 * Meta Box for Bambora Online Checkout Payment Request
		 *
		 * @param WP_Post|WC_Order $post_or_order - The post (legacy storage) or the order (HPOS).
		 * @return void
		 * @throws Exception - In case of error Throw an Exception.
		 */
		public function bambora_online_checkout_meta_box_payment_request( $post_or_order ) {
			$order    = $this->get_order_from_post_or_order( $post_or_order );
			$order_id = ! empty( $order ) ? $order->get_id() : '';

			if ( empty( $order_id ) || ! $this->module_check( $order_id ) ) {
				return;
			}

			$payment_request_id = $order->get_meta( 'bambora_paymentrequest_id' );
			if ( empty( $payment_request_id ) ) {
				if ( ! $this->current_user_can_manage_payments() ) {
					echo esc_html__( 'Your role cannot create payment requests', 'bambora-online-checkout' );
					return;
				}
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped when the HTML is built.
				echo $this->build_payment_request_create_html( $order );
				return;
			}

			// A payment request is already created for this order.
			try {
				$api                          = new Bambora_Online_Checkout_Api( $this->get_api_key( $order_id ) );
				$get_payment_request_response = $api->get_payment_request( $payment_request_id );
				if ( ! $get_payment_request_response->meta->result ) {
					throw new Exception( $get_payment_request_response->meta->message->merchant );
				}
				$status = empty( $get_payment_request_response->status ) ? '' : (string) $get_payment_request_response->status;

				$html  = '<div class="boc-box">';
				$html .= $this->build_payment_request_details_html( $order, $payment_request_id, $get_payment_request_response, $status );
				if ( 'closed' !== $status && $this->current_user_can_manage_payments() ) {
					$html .= $this->build_payment_request_send_html( $order );
				}
				$html .= '</div>';
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped when the HTML is built.
				echo $html;
			} catch ( Exception $e ) {
				$error_message = "Could not retrive Payment Request with Id: {$payment_request_id} - {$e->getMessage()}";
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in message_to_html.
				echo Bambora_Online_Checkout_Helper::message_to_html( Bambora_Online_Checkout_Helper::ERROR, $error_message );
				$this->boc_log->add( $error_message );
			}
		}

		/**
		 * Format the order total the same way as the amounts in the payment meta box
		 *
		 * @param WC_Order $order - WC Order.
		 * @return string
		 */
		private function format_order_total( $order ) {
			$currency_code = $order->get_currency();
			return $this->format_amount( $order->get_total(), Bambora_Online_Checkout_Currency::get_currency_minorunits( $currency_code ), $currency_code );
		}

		/**
		 * Build a label with a value below it
		 *
		 * @param string $label - Label.
		 * @param string $value - Value.
		 * @param bool   $mono - Show the value in a monospace font, used for ids.
		 * @return string
		 */
		private function build_payment_request_value_html( $label, $value, $mono = false ) {
			$html  = '<div class="boc-field">';
			$html .= '<span class="boc-label">' . esc_html( $label ) . '</span>';
			$html .= '<div class="boc-value' . ( $mono ? ' boc-mono' : '' ) . '">' . esc_html( $value ) . '</div>';
			$html .= '</div>';

			return $html;
		}

		/**
		 * Build a label with a text input below it
		 *
		 * @param string $id - Id of the input.
		 * @param string $label - Label.
		 * @param string $value - Value.
		 * @return string
		 */
		private function build_payment_request_input_html( $id, $label, $value ) {
			$html  = '<div class="boc-field">';
			$html .= '<label class="boc-label" for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label>';
			$html .= '<input type="text" id="' . esc_attr( $id ) . '" value="' . esc_attr( $value ) . '" />';
			$html .= '</div>';

			return $html;
		}

		/**
		 * Build the form for creating a payment request
		 *
		 * @param WC_Order $order - WC Order.
		 * @return string
		 */
		private function build_payment_request_create_html( $order ) {
			$html  = '<div class="boc-box">';
			$html .= '<input type="hidden" id="bambora_create_pr_message" value="' . esc_attr__( 'Are you sure you want to create a payment request?', 'bambora-online-checkout' ) . '" />';
			$html .= '<div class="boc-section">';
			$html .= '<div class="boc-row"><span>' . esc_html__( 'Order Amount in Request', 'bambora-online-checkout' ) . '</span><span class="boc-strong">' . esc_html( $this->format_order_total( $order ) ) . '</span></div>';
			$html .= '</div>';
			$html .= '<div class="boc-section">';
			$html .= $this->build_payment_request_input_html( 'bambora_pr_description', __( 'Description', 'bambora-online-checkout' ), '' );
			$html .= '<p class="boc-hint">' . esc_html__( 'Once you have created the Payment Request, you will be able to send it directly to the customer.', 'bambora-online-checkout' ) . '</p>';
			$html .= '</div>';
			$html .= '<div class="boc-footer">';
			$html .= '<span></span>';
			$html .= '<button type="button" id="bambora_create_pr_submit" class="button button-primary"' . $this->get_action_button_attributes( self::PAYMENTREQUEST_ACTION, $order->get_id() ) . '>' . esc_html__( 'Create Payment Request', 'bambora-online-checkout' ) . '</button>';
			$html .= '</div>';
			$html .= '</div>';

			return $html;
		}

		/**
		 * Build the details of a payment request: amount, status, description, ids and the payment link
		 *
		 * @param WC_Order $order - WC Order.
		 * @param string   $payment_request_id - Payment Request Id.
		 * @param object   $payment_request - Get payment request response from the API.
		 * @param string   $status - Status of the payment request.
		 * @return string
		 */
		private function build_payment_request_details_html( $order, $payment_request_id, $payment_request, $status ) {
			$html  = '<div class="boc-section">';
			$html .= '<div class="boc-row">';
			$html .= '<span class="boc-strong">' . esc_html( $this->format_order_total( $order ) ) . '</span>';
			if ( '' !== $status ) {
				$html .= '<span class="boc-badge boc-badge-' . esc_attr( sanitize_html_class( $status ) ) . '">' . esc_html( $this->get_payment_request_status_label( $status ) ) . '</span>';
			}
			$html   .= '</div>';
			$created = empty( $payment_request->createddate ) ? false : strtotime( (string) $payment_request->createddate );
			if ( false !== $created ) {
				$html .= '<div class="boc-muted">' . esc_html__( 'Created', 'bambora-online-checkout' ) . ' ' . esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $created ) ) . '</div>';
			}
			$html .= '</div>';

			$html .= '<div class="boc-section">';
			if ( ! empty( $payment_request->description ) ) {
				$html .= $this->build_payment_request_value_html( __( 'Description', 'bambora-online-checkout' ), (string) $payment_request->description );
			}
			if ( ! empty( $payment_request->reference ) ) {
				$html .= $this->build_payment_request_value_html( __( 'Reference', 'bambora-online-checkout' ), (string) $payment_request->reference, true );
			}
			$html .= $this->build_payment_request_value_html( __( 'Payment Request ID', 'bambora-online-checkout' ), (string) $payment_request_id, true );

			$payment_request_url = $order->get_meta( 'bambora_paymentrequest_url' );
			if ( ! empty( $payment_request_url ) ) {
				$html .= '<div class="boc-field">';
				$html .= '<label class="boc-label" for="bambora_pr_url">' . esc_html__( 'Payment Request URL', 'bambora-online-checkout' ) . '</label>';
				$html .= '<div class="boc-input-group">';
				$html .= '<input type="text" id="bambora_pr_url" value="' . esc_url( $payment_request_url ) . '" readonly />';
				$html .= '<button type="button" id="bambora_pr_url_copy" class="button" data-copied="' . esc_attr__( 'Copied', 'bambora-online-checkout' ) . '">' . esc_html__( 'Copy', 'bambora-online-checkout' ) . '</button>';
				$html .= '</div>';
				$html .= '</div>';
			}
			$html .= '</div>';

			return $html;
		}

		/**
		 * Build the form for sending the payment request by email, and the delete action
		 *
		 * @param WC_Order $order - WC Order.
		 * @return string
		 */
		private function build_payment_request_send_html( $order ) {
			$order_id = $order->get_id();

			// The order's billing details are used, since they are what the customer gave for this order. The customer account is the fallback.
			$recipient_email = $order->get_billing_email();
			$recipient_name  = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
			if ( ( '' === $recipient_email || '' === $recipient_name ) && $order->get_customer_id() ) {
				$customer = new WC_Customer( $order->get_customer_id() );
				if ( '' === $recipient_email ) {
					$recipient_email = $customer->get_email();
				}
				if ( '' === $recipient_name ) {
					$recipient_name = trim( $customer->get_first_name() . ' ' . $customer->get_last_name() );
				}
			}
			$current_user = wp_get_current_user();

			// The recipient can be changed before sending, so the email in the confirm message is filled in by the script.
			/* translators: %s: search term */
			$confirm_message = sprintf( __( 'Are you sure that you want to send the Payment Request to %1$s, requesting an amount of %2$s ?', 'bambora-online-checkout' ), '{email}', $this->format_order_total( $order ) );

			$html  = '<input type="hidden" id="bambora_send_pr_message" value="' . esc_attr( $confirm_message ) . '" />';
			$html .= '<input type="hidden" id="bambora_delete_pr_message" value="' . esc_attr__( 'Are you sure you want to delete this payment request?', 'bambora-online-checkout' ) . '" />';
			$html .= '<div class="boc-section">';
			$html .= '<h4 class="boc-heading">' . esc_html__( 'Send Payment Request', 'bambora-online-checkout' ) . '</h4>';
			$html .= $this->build_payment_request_input_html( 'bambora_pr_recipient_email', __( 'Recipient Email', 'bambora-online-checkout' ), $recipient_email );
			$html .= $this->build_payment_request_input_html( 'bambora_pr_recipient_name', __( 'Recipient Name', 'bambora-online-checkout' ), $recipient_name );
			$html .= $this->build_payment_request_input_html( 'bambora_pr_replyto_email', __( 'Reply-To Email', 'bambora-online-checkout' ), $current_user->user_email );
			$html .= $this->build_payment_request_input_html( 'bambora_pr_replyto_name', __( 'Reply-To Name', 'bambora-online-checkout' ), trim( $current_user->first_name . ' ' . $current_user->last_name ) );
			$html .= '<div class="boc-field">';
			$html .= '<label class="boc-label" for="bambora_pr_email_message">' . esc_html__( 'Message', 'bambora-online-checkout' ) . '</label>';
			$html .= '<textarea id="bambora_pr_email_message" rows="3"></textarea>';
			$html .= '</div>';
			$html .= '<div id="bambora-email-error" class="boc-inline-notice">' . esc_html__( 'Please enter a valid email address.', 'bambora-online-checkout' ) . '</div>';
			$html .= '</div>';
			$html .= '<div class="boc-footer">';
			$html .= '<button type="button" id="bambora_delete_pr_submit" class="button-link button-link-delete"' . $this->get_action_button_attributes( self::PAYMENTREQUEST_ACTION, $order_id ) . '>' . esc_html__( 'Delete request', 'bambora-online-checkout' ) . '</button>';
			$html .= '<button type="button" id="bambora_send_pr_submit" class="button button-primary"' . $this->get_action_button_attributes( self::PAYMENTREQUEST_ACTION, $order_id ) . '>' . esc_html__( 'Send email', 'bambora-online-checkout' ) . '</button>';
			$html .= '</div>';

			return $html;
		}

		/**
		 * Generate the Bambora payment meta box and echos the HTML
		 *
		 * @param WP_Post|WC_Order $post_or_order - The post (legacy storage) or the order (HPOS).
		 * @return null
		 * @throws Exception - In case of error Throw an Exception.
		 */
		public function bambora_online_checkout_meta_box_payment( $post_or_order ) {
			$order    = $this->get_order_from_post_or_order( $post_or_order );
			$order_id = ! empty( $order ) ? $order->get_id() : '';

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
							$transaction     = $get_transaction_response->transaction;
							$minorunits      = $transaction->currency->minorunits;
							$payment_type    = ! empty( $transaction->information->paymenttypes ) ? $transaction->information->paymenttypes[0] : null;
							$wallet_name     = ! empty( $transaction->information->wallets ) ? $transaction->information->wallets[0]->name : null;
							$is_collector    = false;
							$collector_class = 'isCollectorFalse';

							if ( isset( $payment_type ) && 19 === $payment_type->groupid && 40 === $payment_type->id ) { // Collector Bank (from 1st September 2021 called Walley).
								$is_collector    = true;
								$collector_class = 'isCollectorTrue';
							}

							$can_capture_refund_delete           = $this->current_user_can_manage_payments();
							$get_transaction_operations_response = $api->get_transaction_operations( $transaction_id );
							if ( ! $get_transaction_operations_response->meta->result ) {
								/* translators: %s: search term */
								$message = sprintf( __( 'Get transaction operations failed - %s', 'bambora-online-checkout' ), $get_transaction_operations_response->meta->message->merchant );
								echo Bambora_Online_Checkout_Helper::message_to_html( Bambora_Online_Checkout_Helper::ERROR, $message ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in message_to_html.
								$this->boc_log->add( $message );
							} else {
								$transaction_operations = $get_transaction_operations_response->transactionoperations;

								$html  = '<div id="' . esc_attr( $collector_class ) . '"></div>';
								$html .= '<div class="boc-box">';
								$html .= $this->build_payment_summary_html( $transaction, $payment_type, $wallet_name, $transaction_operations );
								$html .= $this->build_payment_totals_html( $transaction, $minorunits );
								$html .= $this->build_payment_actions_html( $transaction, $minorunits, $order_id, $is_collector, $can_capture_refund_delete );
								$html .= $this->build_transaction_history_html( $transaction_operations, $minorunits, $order_id );
								$html .= '</div>';
								// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped when the HTML is built.
								echo $html;
							}
						}
					} catch ( Exception $e ) {
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo Bambora_Online_Checkout_Helper::message_to_html( Bambora_Online_Checkout_Helper::ERROR, $e->getMessage() );
						$this->boc_log->add( $e->getMessage() );
					}
				}
			}
		}

		/**
		 * Format an amount with the decimals of the currency, ex. 10.00 DKK
		 *
		 * @param float  $amount - Amount.
		 * @param int    $minorunits - MinorUnits.
		 * @param string $currency_code - Currency code.
		 * @return string
		 */
		private function format_amount( $amount, $minorunits, $currency_code ) {
			return wc_format_localized_price( wc_format_decimal( $amount, (int) $minorunits ) ) . ' ' . $currency_code;
		}

		/**
		 * Build the payment summary: payment type, wallet, logos, transaction id and payment details
		 *
		 * @param object      $transaction - Bambora Transaction.
		 * @param object|null $payment_type - Bambora Payment Type.
		 * @param string|null $wallet_name - Wallet name from the API.
		 * @param array       $transaction_operations - Bambora Transaction Operations.
		 * @return string
		 */
		private function build_payment_summary_html( $transaction, $payment_type, $wallet_name, $transaction_operations ) {
			$logos = array(); // Each logo is array( file name, name ).

			// Wallet name from the API => array( logo file, display name ).
			$wallet_logos        = array(
				'MobilePay' => array( '13.svg', 'MobilePay' ),
				'Vipps'     => array( '14.svg', 'Vipps' ),
				'GooglePay' => array( '22.svg', 'Google Pay' ),
				'ApplePay'  => array( '21.svg', 'Apple Pay' ),
			);
			$wallet_display_name = '';
			if ( is_string( $wallet_name ) && isset( $wallet_logos[ $wallet_name ] ) ) {
				$wallet_display_name = $wallet_logos[ $wallet_name ][1];
				$logos[]             = $wallet_logos[ $wallet_name ];
			}
			if ( isset( $payment_type ) ) {
				$logos[] = array( "{$payment_type->groupid}.svg", $payment_type->displayname );
			}
			if ( isset( $transaction_operations[0]->transactionoperations[0]->acquirerdata[0]->key ) && 'nordeaepaymentfi.customerbank' === $transaction_operations[0]->transactionoperations[0]->acquirerdata[0]->key ) {
				$bank_name = $transaction_operations[0]->transactionoperations[0]->acquirerdata[0]->value;
				$logos[]   = array( "bank-{$bank_name}.svg", $bank_name );
			}

			$title = isset( $payment_type ) ? $payment_type->displayname : $wallet_display_name;

			$html  = '<div class="boc-section">';
			$html .= '<div class="boc-row">';
			$html .= '<div>';
			$html .= '<div class="boc-payment-type">' . esc_html( $title ) . '</div>';
			if ( ! empty( $wallet_display_name ) && isset( $payment_type ) ) {
				/* translators: %s: wallet name, ex. MobilePay */
				$html .= '<div class="boc-muted">' . esc_html( sprintf( __( 'via %s', 'bambora-online-checkout' ), $wallet_display_name ) ) . '</div>';
			}
			$html .= '</div>';
			$html .= '<div class="boc-logos">';
			foreach ( $logos as $logo ) {
				$html .= '<img src="' . esc_url( Bambora_Online_Checkout_Endpoints::get_payment_logos_endpoint() . '/' . $logo[0] ) . '" alt="' . esc_attr( $logo[1] ) . '" title="' . esc_attr( $logo[1] ) . '" />';
			}
			$html .= '</div>';
			$html .= '</div>';
			$html .= '<div class="boc-muted">' . esc_html__( 'Transaction', 'bambora-online-checkout' ) . ' <span class="boc-mono">' . esc_html( $transaction->id ) . '</span></div>';

			$details = array();
			if ( isset( $transaction->information->ecis ) ) {
				$lowest_eci = $this->get_lowest_eci( $transaction->information->ecis );
				if ( ! empty( $lowest_eci ) ) {
					/* translators: %s: ECI value */
					$details[] = '<span class="boc-help" title="' . esc_attr( Bambora_Online_Checkout_Helper::get_3d_secure_text( $lowest_eci ) ) . '">' . esc_html( sprintf( __( 'ECI %s', 'bambora-online-checkout' ), $lowest_eci ) ) . '</span>';
				}
			}
			if ( isset( $transaction->information->exemptions ) ) {
				$exemptions = $this->getDistinctExemptions( $transaction->information->exemptions );
				if ( ! empty( $exemptions ) ) {
					/* translators: %s: exemptions, ex. LowValuePayment */
					$details[] = esc_html( sprintf( __( 'Exemption: %s', 'bambora-online-checkout' ), $exemptions ) );
				}
			}
			if ( ! empty( $transaction->information->acquirerreferences[0]->reference ) ) {
				/* translators: %s: acquirer reference */
				$details[] = esc_html( sprintf( __( 'Acquirer reference: %s', 'bambora-online-checkout' ), $transaction->information->acquirerreferences[0]->reference ) );
			}
			if ( ! empty( $details ) ) {
				$html .= '<div class="boc-muted">' . implode( ' &middot; ', $details ) . '</div>';
			}
			$html .= '</div>';

			return $html;
		}

		/**
		 * Build the totals: authorized, captured, refunded and left to capture
		 *
		 * @param object $transaction - Bambora Transaction.
		 * @param int    $minorunits - MinorUnits.
		 * @return string
		 */
		private function build_payment_totals_html( $transaction, $minorunits ) {
			$currency_code = $transaction->currency->code;
			$rows          = array(
				__( 'Authorized', 'bambora-online-checkout' ) => $transaction->total->authorized,
				__( 'Captured', 'bambora-online-checkout' )   => $transaction->total->captured,
				__( 'Refunded', 'bambora-online-checkout' )   => $transaction->total->credited,
			);

			$html = '<div class="boc-section">';
			foreach ( $rows as $label => $amount_in_minorunits ) {
				$amount = Bambora_Online_Checkout_Currency::convert_price_from_minorunits( $amount_in_minorunits, $minorunits );
				$html  .= '<div class="boc-row"><span>' . esc_html( $label ) . '</span><span>' . esc_html( $this->format_amount( $amount, $minorunits, $currency_code ) ) . '</span></div>';
			}
			if ( 0 < $transaction->available->capture ) {
				$available = Bambora_Online_Checkout_Currency::convert_price_from_minorunits( $transaction->available->capture, $minorunits );
				$html     .= '<div class="boc-row boc-total"><span>' . esc_html__( 'Left to capture', 'bambora-online-checkout' ) . '</span><span>' . esc_html( $this->format_amount( $available, $minorunits, $currency_code ) ) . '</span></div>';
			}
			$html .= '</div>';

			return $html;
		}

		/**
		 * Build the capture and delete actions
		 *
		 * @param object $transaction - Bambora Transaction.
		 * @param int    $minorunits - MinorUnits.
		 * @param int    $order_id - WC Order Id.
		 * @param bool   $is_collector - Is the payment made with Walley.
		 * @param bool   $can_manage_payments - Can the current user capture and delete.
		 * @return string
		 */
		private function build_payment_actions_html( $transaction, $minorunits, $order_id, $is_collector, $can_manage_payments ) {
			$can_capture = 0 < $transaction->available->capture;
			$can_delete  = ! empty( $transaction->candelete );
			if ( ! $can_capture && ! $can_delete ) {
				return '';
			}
			if ( ! $can_manage_payments ) {
				return '<div class="boc-footer"><span class="boc-muted">' . esc_html__( 'Your role cannot capture or delete the payment.', 'bambora-online-checkout' ) . '</span></div>';
			}

			$currency_code = $transaction->currency->code;
			$html          = '';
			if ( $can_capture ) {
				$available = Bambora_Online_Checkout_Currency::convert_price_from_minorunits( $transaction->available->capture, $minorunits );

				$html .= '<input type="hidden" id="bambora_currency" value="' . esc_attr( $currency_code ) . '" />';
				$html .= '<input type="hidden" id="bambora_capture_message" value="' . esc_attr__( 'Are you sure you want to capture the payment?', 'bambora-online-checkout' ) . '" />';
				$html .= '<div class="boc-section">';
				$html .= '<label class="boc-label" for="bambora_capture_amount">' . esc_html__( 'Amount to capture', 'bambora-online-checkout' ) . '</label>';
				$html .= '<div class="boc-input-group">';
				$html .= '<input type="text" id="bambora_capture_amount" class="bambora_amount" value="' . esc_attr( number_format( (float) $available, (int) $minorunits, '.', '' ) ) . '"' . ( $is_collector ? ' readonly' : '' ) . ' />';
				$html .= '<span class="boc-input-suffix">' . esc_html( $currency_code ) . '</span>';
				$html .= '</div>';
				if ( $is_collector ) {
					$html .= '<p class="boc-hint">' . esc_html__( 'With Walley only full capture is possible here. For partial capture, use the Worldline Merchant Portal.', 'bambora-online-checkout' ) . '</p>';
				}
				$html .= '<div id="bambora-format-error" class="boc-inline-notice">' . esc_html__( 'The amount is in the wrong format. Use for example 1234.56', 'bambora-online-checkout' ) . '</div>';
				$html .= '</div>';
			}

			$html .= '<div class="boc-footer">';
			if ( $can_delete ) {
				$html .= '<input type="hidden" id="bambora_delete_message" value="' . esc_attr__( 'Are you sure you want to delete the payment?', 'bambora-online-checkout' ) . '" />';
				$html .= '<button type="button" id="bambora_delete_submit" class="button-link button-link-delete"' . $this->get_action_button_attributes( self::PAYMENT_ACTION, $order_id ) . '>' . esc_html__( 'Delete payment', 'bambora-online-checkout' ) . '</button>';
			} else {
				$html .= '<span></span>';
			}
			if ( $can_capture ) {
				$html .= '<button type="button" id="bambora_capture_submit" class="button button-primary"' . $this->get_action_button_attributes( self::PAYMENT_ACTION, $order_id ) . '>' . esc_html__( 'Capture', 'bambora-online-checkout' ) . '</button>';
			}
			$html .= '</div>';

			return $html;
		}

		/**
		 * Build the transaction history as a timeline, newest first
		 *
		 * @param array $transaction_operations - Bambora Transaction Operations.
		 * @param int   $minorunits - MinorUnits.
		 * @param int   $order_id - WC Order Id.
		 * @return string
		 */
		protected function build_transaction_history_html( $transaction_operations, $minorunits, $order_id ) {
			$entries = $this->get_transaction_history_entries( $transaction_operations, $order_id );
			if ( empty( $entries ) ) {
				return '';
			}
			// Newest first. Entries with the same time keep the latest operation first.
			$entries = array_reverse( $entries );
			usort(
				$entries,
				function ( $a, $b ) {
					return $b['timestamp'] <=> $a['timestamp'];
				}
			);

			$html  = '<div class="boc-section boc-history">';
			$html .= '<h4>' . esc_html__( 'Transaction history', 'bambora-online-checkout' ) . '</h4>';
			$html .= '<ul class="boc-timeline">';
			foreach ( $entries as $entry ) {
				$operation = $entry['operation'];
				$amount    = Bambora_Online_Checkout_Currency::convert_price_from_minorunits( $operation->amount, $minorunits );
				$html     .= '<li class="boc-event-' . esc_attr( $entry['type'] ) . '" title="' . esc_attr( $entry['description'] ) . '">';
				$html     .= '<div class="boc-row"><span class="boc-event-title">' . esc_html( $entry['title'] ) . '</span><span>' . ( $amount > 0 ? esc_html( $this->format_amount( $amount, $minorunits, $operation->currency->code ) ) : '&ndash;' ) . '</span></div>';
				if ( ! empty( $entry['extra'] ) ) {
					$html .= '<div class="boc-event-extra">' . esc_html( $entry['extra'] ) . '</div>';
				}
				$html .= '<div class="boc-muted">' . esc_html( $entry['date'] ) . '</div>';
				$html .= '</li>';
			}
			$html .= '</ul>';
			$html .= '</div>';

			return $html;
		}

		/**
		 * Get the transaction operations as a flat list of history entries, in the order they are returned by the API
		 *
		 * @param array $transaction_operations - Bambora Transaction Operations.
		 * @param int   $order_id - WC Order Id.
		 * @return array<array<string, mixed>>
		 */
		private function get_transaction_history_entries( $transaction_operations, $order_id ) {
			$entries = array();
			foreach ( $transaction_operations as $transaction_operation ) {
				$event_info = Bambora_Online_Checkout_Helper::get_event_text( $transaction_operation );
				if ( isset( $event_info['description'] ) ) {
					$approved  = 'approved' === $transaction_operation->status;
					$action    = strtolower( $transaction_operation->action );
					$type      = 'other';
					$date_time = wc_string_to_datetime( $transaction_operation->createddate );
					if ( ! $approved ) {
						$type = 'failed';
					} elseif ( 'capture' === $action ) {
						$type = 'capture';
					} elseif ( 'credit' === $action ) {
						$type = 'refund';
					}
					$entries[] = array(
						'operation'   => $transaction_operation,
						'type'        => $type,
						// Legacy Collector Bank.
						'title'       => str_replace( 'CollectorBank', 'Walley', $event_info['title'] ),
						'description' => str_replace( 'CollectorBank', 'Walley', $event_info['description'] ),
						'extra'       => $approved ? '' : $this->get_event_extra( $transaction_operation, $order_id ),
						'timestamp'   => $date_time->getTimestamp(),
						'date'        => wc_format_datetime( $date_time, wc_date_format() ) . ' · ' . wc_format_datetime( $date_time, wc_time_format() ),
					);
				}
				if ( ! empty( $transaction_operation->transactionoperations ) ) {
					$entries = array_merge( $entries, $this->get_transaction_history_entries( $transaction_operation->transactionoperations, $order_id ) );
				}
			}

			return $entries;
		}

		/**
		 * Get Transaction Operation Event Extra Text
		 *
		 * @param mixed $transaction_operation - Bambora Transaction Operation.
		 * @param int   $order_id - WC Order Id.
		 * @return string
		 * @throws Exception - In case of error Throw an Exception.
		 */
		protected function get_event_extra( $transaction_operation, $order_id ) {
			$source         = $transaction_operation->actionsource;
			$action_code    = $transaction_operation->actioncode;
			$merchant_label = '';
			// The texts for the response codes do not change, so they are cached.
			$cache_key = self::CACHE_PREFIX . 'rc_' . md5( "{$source}|{$action_code}" );
			$cached    = get_transient( $cache_key );
			if ( is_string( $cached ) && '' !== $cached ) {
				return $cached;
			}
			$webservice = new Bambora_Online_Checkout_Api( $this->get_api_key( $order_id ) );
			try {
				$get_response_code_data_response = $webservice->get_response_code_data( $source, $action_code );

				if ( ! $get_response_code_data_response->meta->result || ! isset( $get_response_code_data_response->responsecode ) ) {
					throw new Exception( $get_response_code_data_response->meta->message->merchant );
				}
				$merchant_label = $get_response_code_data_response->responsecode->merchantlabel . ' - ' . $source . ' ' . $action_code;
				set_transient( $cache_key, $merchant_label, WEEK_IN_SECONDS );
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
		 * Check if the current user may capture, refund and delete payments and handle payment requests.
		 *
		 * @return bool
		 */
		public function current_user_can_manage_payments() {
			if ( ! current_user_can( 'edit_shop_orders' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce capability.
				return false;
			}
			$roles = (array) wp_get_current_user()->roles;
			return in_array( $this->rolecapturerefunddelete, $roles, true ) || in_array( 'administrator', $roles, true );
		}

		/**
		 * Get the data attributes the admin JavaScript needs to post an action for an order.
		 *
		 * @param string $action - The admin-post action, also used as nonce action prefix.
		 * @param int    $order_id - WC Order Id.
		 * @return string
		 */
		private function get_action_button_attributes( $action, $order_id ) {
			return ' data-order-id="' . esc_attr( $order_id ) . '" data-nonce="' . esc_attr( wp_create_nonce( "{$action}_{$order_id}" ) ) . '"';
		}

		/**
		 * Validate an admin-post action request and return the order id.
		 * Stops the request if the nonce, the user's permissions or the order are not valid.
		 *
		 * @param string $action - The admin-post action, also used as nonce action prefix.
		 * @return int
		 */
		private function validate_admin_action_request( $action ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- The nonce is verified below and depends on the order id.
			$order_id = isset( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : 0;
			check_admin_referer( "{$action}_{$order_id}", 'bambora_nonce' );

			if ( ! $this->current_user_can_manage_payments() ) {
				wp_die( esc_html__( 'Your user role is not allowed to perform this action', 'bambora-online-checkout' ), 403 );
			}
			if ( ! $this->module_check( $order_id ) ) {
				wp_die( esc_html__( 'The order was not paid with Worldline Online Checkout', 'bambora-online-checkout' ), 400 );
			}

			return $order_id;
		}

		/**
		 * Get a posted value, sanitized as a text field.
		 * Only call after validate_admin_action_request has verified the nonce.
		 *
		 * @param string $key - Key.
		 * @return string
		 */
		private function get_posted_text( $key ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in validate_admin_action_request.
			return isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
		}

		/**
		 * Add the admin notice for the result of an action and redirect back to the order.
		 *
		 * @param int           $order_id - WC Order Id.
		 * @param string        $action_name - Translated action name.
		 * @param bool|WP_Error $action_result - Action result.
		 * @return never
		 */
		private function finish_admin_action( $order_id, $action_name, $action_result ) {
			if ( is_wp_error( $action_result ) ) {
				$message = $action_result->get_error_message( 'bambora_online_checkout_error' );
				$this->boc_log->add( $message );
				Bambora_Online_Checkout_Helper::add_admin_notices( Bambora_Online_Checkout_Helper::ERROR, $message );
			} else {
				/* translators: %s: search term */
				$message = sprintf( __( 'The %1$s action was a success for order %2$s', 'bambora-online-checkout' ), $action_name, $order_id );
				$this->boc_log->add( $message );
				Bambora_Online_Checkout_Helper::add_admin_notices( Bambora_Online_Checkout_Helper::SUCCESS, $message );
			}

			wp_safe_redirect( wc_get_order( $order_id )->get_edit_order_url() );
			exit;
		}

		/**
		 * Worldline Online Checkout Actions (admin-post handler for capture and delete)
		 *
		 * @return never
		 */
		public function bambora_online_checkout_actions() {
			$order_id      = $this->validate_admin_action_request( self::PAYMENT_ACTION );
			$action        = $this->get_posted_text( 'bambora_action' );
			$action_result = null;
			$action_name   = '';
			try {
				switch ( $action ) {
					case 'capture':
						$action_name   = __( 'Capture', 'bambora-online-checkout' );
						$action_result = $this->bambora_online_checkout_capture_payment( $order_id, $this->get_posted_text( 'amount' ), $this->get_posted_text( 'currency' ) );
						break;
					case 'delete':
						$action_name   = __( 'Delete', 'bambora-online-checkout' );
						$action_result = $this->bambora_online_checkout_delete_payment( $order_id );
						break;
					default:
						$action_result = new WP_Error( 'bambora_online_checkout_error', __( 'Transaction Action is incorrect', 'bambora-online-checkout' ) );
						break;
				}
			} catch ( Exception $ex ) {
				$action_result = new WP_Error( 'bambora_online_checkout_error', $ex->getMessage() );
			}

			$this->finish_admin_action( $order_id, $action_name, $action_result );
		}

		/**
		 * Worldline Online Checkout Payment Request Actions (admin-post handler)
		 *
		 * @return never
		 */
		public function bambora_online_checkout_paymentrequest_actions() {
			$order_id      = $this->validate_admin_action_request( self::PAYMENTREQUEST_ACTION );
			$action        = $this->get_posted_text( 'bambora_paymentrequest_action' );
			$action_result = null;
			$action_name   = '';
			try {
				switch ( $action ) {
					case 'create_pr':
						$action_name   = __( 'Create Payment Request', 'bambora-online-checkout' );
						$action_result = $this->bambora_create_paymentrequest( $order_id, $this->get_posted_text( 'description' ) );
						break;
					case 'delete_pr':
						$action_name   = __( 'Delete Payment Request', 'bambora-online-checkout' );
						$action_result = $this->bambora_delete_paymentrequest( $order_id, wc_get_order( $order_id )->get_meta( 'bambora_paymentrequest_id' ) );
						break;
					case 'send_pr':
						$action_name   = __( 'Send Payment Request', 'bambora-online-checkout' );
						$action_result = $this->bambora_send_paymentrequest(
							$order_id,
							$this->get_posted_text( 'recipient_name' ),
							sanitize_email( $this->get_posted_text( 'recipient_email' ) ),
							$this->get_posted_text( 'replyto_name' ),
							sanitize_email( $this->get_posted_text( 'replyto_email' ) ),
							// The message is a text area, so the line breaks are kept.
							// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in validate_admin_action_request.
							isset( $_POST['email_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['email_message'] ) ) : ''
						);
						break;
					default:
						$action_result = new WP_Error( 'bambora_online_checkout_error', __( 'PaymentRequest Action is incorrect', 'bambora-online-checkout' ) );
						break;
				}
			} catch ( Exception $ex ) {
				$action_result = new WP_Error( 'bambora_online_checkout_error', $ex->getMessage() );
			}

			$this->finish_admin_action( $order_id, $action_name, $action_result );
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

				$refunds                   = $order->get_refunds();
				$order_total_in_minorunits = Bambora_Online_Checkout_Currency::convert_price_to_minorunits( $order->get_total(), $minorunits, $this->roundingmode );
				$bambora_refund_lines      = null;
				// Do not send credit lines when crediting the full amount. The latest WC refund is the one being processed.
				if ( $amount_in_minorunits !== $order_total_in_minorunits && ! empty( $refunds ) ) {
					$bambora_refund_lines = array();
					if ( ! $this->create_bambora_refund_lines( $refunds[0], $bambora_refund_lines, $minorunits, $order, $is_walley ) ) {
						$bambora_refund_lines = null;
					}
				}
				$credit_response = $api->credit( $transaction_id, $amount_in_minorunits, $currency, $bambora_refund_lines );

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
			$order = wc_get_order( $order_id );
			return ! empty( $order ) && $this->id === $order->get_payment_method();
		}

		/**
		 * Get the order from the object WooCommerce passes to order screen hooks.
		 *
		 * @param WP_Post|WC_Order|null $post_or_order - The post (legacy storage) or the order (HPOS).
		 * @return WC_Order|false
		 */
		private function get_order_from_post_or_order( $post_or_order ) {
			if ( $post_or_order instanceof WC_Abstract_Order ) {
				return $post_or_order;
			}
			if ( $post_or_order instanceof WP_Post ) {
				return wc_get_order( $post_or_order->ID );
			}
			return false;
		}
		/**
		 * Add a custom column to an order
		 *
		 * @param array $columns - An array of Columns.
		 * @return array
		 */
		public function add_custom_order_column( $columns ) {

			$columns['bambora_payment_request_field'] = __( 'Payment Request', 'bambora-online-checkout' );

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
			if ( empty( $payment_request_id ) ) {
				// Same placeholder as the empty cells in the other WooCommerce columns.
				echo '<span class="na">&ndash;</span>';
				return;
			}
			// The order list shows many orders, so the status is cached for a short time instead of calling Worldline for every row on every page load.
			$cache_key       = $this->get_payment_request_status_cache_key( $payment_request_id );
			$payment_request = get_transient( $cache_key );
			if ( ! is_array( $payment_request ) ) {
				try {
					$api                          = new Bambora_Online_Checkout_Api( $this->get_api_key( $order->get_id() ) );
					$get_payment_request_response = $api->get_payment_request( $payment_request_id );
					if ( ! $get_payment_request_response->meta->result ) {
						throw new Exception( $get_payment_request_response->meta->message->merchant );
					}
					$payment_request = array(
						'status'      => empty( $get_payment_request_response->status ) ? '' : (string) $get_payment_request_response->status,
						'description' => empty( $get_payment_request_response->description ) ? '' : (string) $get_payment_request_response->description,
					);
					set_transient( $cache_key, $payment_request, 5 * MINUTE_IN_SECONDS );
				} catch ( Exception $e ) {
					$this->boc_log->add( "Payment Request with Id: {$payment_request_id} could not be retrieved for the order list - {$e->getMessage()}" );
					$payment_request = array(
						'status'      => '',
						'description' => '',
					);
				}
			}

			if ( empty( $payment_request['status'] ) ) {
				echo '<span class="boc-muted tips" data-tip="' . esc_attr__( 'The status could not be retrieved from Worldline. See the log for details.', 'bambora-online-checkout' ) . '">' . esc_html__( 'Unavailable', 'bambora-online-checkout' ) . '</span>';
				return;
			}

			$status = $payment_request['status'];
			$html   = '<mark class="boc-badge boc-badge-list boc-badge-' . esc_attr( sanitize_html_class( $status ) ) . ( empty( $payment_request['description'] ) ? '' : ' tips' ) . '"';
			if ( ! empty( $payment_request['description'] ) ) {
				$html .= ' data-tip="' . esc_attr( $payment_request['description'] ) . '"';
			}
			$html .= '>' . esc_html( $this->get_payment_request_status_label( $status ) ) . '</mark>';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped when the HTML is built.
			echo $html;
		}

		/**
		 * Get the key of the cached payment request status shown in the order list
		 *
		 * @param string $payment_request_id - Payment Request Id.
		 * @return string
		 */
		private function get_payment_request_status_cache_key( $payment_request_id ) {
			return self::CACHE_PREFIX . 'prs_' . md5( (string) $payment_request_id );
		}

		/**
		 * Remove the cached payment request status of an order, so the order list gets the current status from Worldline
		 *
		 * @param WC_Order $order - WC Order.
		 * @return void
		 */
		private function clear_payment_request_status_cache( $order ) {
			$payment_request_id = $order->get_meta( 'bambora_paymentrequest_id' );
			if ( ! empty( $payment_request_id ) ) {
				delete_transient( $this->get_payment_request_status_cache_key( $payment_request_id ) );
			}
		}

		/**
		 * Get the translated label for a payment request status from Worldline
		 *
		 * @param string $status - Status from the API, ex. open.
		 * @return string
		 */
		private function get_payment_request_status_label( $status ) {
			$labels = array(
				'open'    => __( 'Open', 'bambora-online-checkout' ),
				'closed'  => __( 'Closed', 'bambora-online-checkout' ),
				'expired' => __( 'Expired', 'bambora-online-checkout' ),
				'deleted' => __( 'Deleted', 'bambora-online-checkout' ),
			);

			return isset( $labels[ $status ] ) ? $labels[ $status ] : ucfirst( $status );
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
