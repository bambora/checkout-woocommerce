<?php

    use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

    final class Bambora_Online_Checkout_Blocks extends AbstractPaymentMethodType
    {

        private $gateway;
        protected $name = 'Bambora_Online_Checkout';

        public function initialize()
        {
            $this->settings = get_option(
                'woocommerce_bambora_payment_gateway_settings',
                []
            );
            $this->gateway  = new Bambora_Online_Checkout();
        }

        public function is_active()
        {
            return $this->gateway->is_available();
        }

        public function get_payment_method_script_handles()
        {
            wp_register_script(
                'bambora-online-checkout-blocks-integration',
                plugin_dir_url(__FILE__) . 'js/bamboracheckout.js',
                [
                    'wc-blocks-registry',
                    'wc-settings',
                    'wp-element',
                    'wp-html-entities',
                    'wp-i18n',
                ],
                null,
                true
            );
            if (function_exists('wp_set_script_translations')) {
                wp_set_script_translations(
                    'bambora-online-checkout-blocks-integration'
                );
            }

            return ['bambora-online-checkout-blocks-integration'];
        }

        public function get_payment_method_data()
        {
            $this->gateway->set_bambora_description_for_checkout(true);

            return [
                'title' => $this->gateway->title,
                'description' => $this->gateway->description,
                'icon' => WP_PLUGIN_URL . '/' . plugin_basename(
                        dirname(__FILE__)
                    ) . '/bambora-logo.svg'
            ];
        }

    }

?>
