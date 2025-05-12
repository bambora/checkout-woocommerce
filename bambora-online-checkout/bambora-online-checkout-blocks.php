<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
/**
 * Bambora Online Checkout Blocks
 */
final class Bambora_Online_Checkout_Blocks extends AbstractPaymentMethodType {

	/**
	 * This property is a string used to reference your payment method. It is important to use the same name as in your
	 * client-side JavaScript payment method registration.
	 *
	 * @var string
	 */
	protected $name = 'bambora_online_checkout';
	/**
	 * Bambora Online Checkout Gateway
	 *
	 * @var Bambora_Online_Checkout
	 */
	private $gateway;

	/**
	 * Initialize Bambora_Online_Checkout_Blocks
	 *
	 * @param mixed $payment_gateway - Bambora Online Checkout Payment Gateway.
	 */
	public function __construct( $payment_gateway ) {
		$this->gateway = $payment_gateway;
	}

	/**
	 * Initializes the payment method.
	 *
	 * This function will get called during the server side initialization process and is a good place to put any settings
	 * population etc. Basically anything you need to do to initialize your gateway.
	 *
	 * Note, this will be called on every request so don't put anything expensive here.
	 */
	public function initialize() {
		$this->settings = get_option(
			"woocommerce_{$this->name}_settings",
			array()
		);
	}

	/**
	 * This should return whether the payment method is active or not.
	 *
	 * If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return $this->gateway->is_available();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * In this function you should register your payment method scripts (using `wp_register_script`) and then return the
	 * script handles you registered with. This will be used to add your payment method as a dependency of the checkout script
	 * and thus take sure of loading it correctly.
	 *
	 * Note that you should still make sure any other asset dependencies your script has are registered properly here, if
	 * you're using Webpack to build your assets, you may want to use the WooCommerce Webpack Dependency Extraction Plugin
	 * (https://www.npmjs.com/package/@woocommerce/dependency-extraction-webpack-plugin) to make this easier for you.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		wp_register_script(
			"{$this->name}-blocks",
			plugins_url( 'assets/js/bambora-online-checkout.js', __FILE__ ),
			array(
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-html-entities',
				'wp-i18n',
			),
			BOC_VERSION,
			true
		);
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations(
				"{$this->name}-blocks"
			);
		}
		return array( "{$this->name}-blocks" );
	}

	/**
	 * Returns an array of script handles to be enqueued for the admin.
	 *
	 * Include this if your payment method has a script you _only_ want to load in the editor context for the checkout block.
	 * Include here any script from `get_payment_method_script_handles` that is also needed in the admin.
	 */
	public function get_payment_method_script_handles_for_admin() {
		return array( "{$this->name}-blocks" );
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script client side.
	 *
	 * This data will be available client side via `wc.wcSettings.getSetting`. So for instance if you assigned `bambora_online_checkout` as the
	 * value of the `name` property for this class, client side you can access any data via:
	 * `wc.wcSettings.getSetting( 'bambora_online_checkout_data' )`. That would return an object matching the shape of the associative array
	 * you returned from this function.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return array(
			'id'          => $this->gateway->id,
			'label'       => $this->gateway->title,
			'ariaLabel'   => $this->gateway->method_title,
			'description' => $this->gateway->description,
			'supports'    => $this->gateway->supports,
			'icon'        => $this->gateway->icon_checkout,
		);
	}
}
