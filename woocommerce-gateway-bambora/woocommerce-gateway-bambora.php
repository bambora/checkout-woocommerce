<?php
/*
Plugin Name: WooCommerce Bambora Checkout Payment Gateway
Plugin URI: http://www.bambora.com
Description: A payment gateway for <a href="http://www.bambora.com/sv/se/betalningslosningar/e-handel/produkter/bambora-checkout">Bambora Checkout</a>.
Version: 1.4.0
Author: Bambora
Author URI: http://www.bambora.com
Text Domain: Bambora
 */

 /*
Add Bambora Stylesheet and javascript to plugin
*/
add_action('admin_enqueue_scripts', 'enqueue_wc_bambora_styles_and_scripts');

function enqueue_wc_bambora_styles_and_scripts()
{
    wp_enqueue_style('bambora_style',  WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__ )) . '/style/bambora.css');

    //Fix for load of Jquery time
    wp_enqueue_script('jquery');

    wp_enqueue_script('bambora_script',  WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__ )) . '/js/bambora.js');
}

add_action('plugins_loaded', 'add_wc_bambora_gateway', 0);

function add_wc_bambora_gateway()
{
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) { return; }

    define('bambora_LIB', dirname(__FILE__) . '/lib/');

    //Including Bambora files
    include(bambora_LIB .'bamboraApi.php');
    include(bambora_LIB .'bamboraHelper.php');
    include(bambora_LIB .'bamboraCurrency.php');


    /**
     * Gateway class
     **/
    class WC_Gateway_Bambora extends WC_Payment_Gateway
    {
        public function __construct()
        {
            $this->id = 'bambora';
            $this->method_title = 'Bambora';
            $this->icon = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__ )) . '/bambora-logo.png';
            $this->has_fields = false;

            $this->supports = array('products', 'refunds');

            // Load the form fields.
            $this->init_form_fields();

            // Load the settings.
            $this->init_settings();

            // Define user set variables
            $this->enabled = $this->settings["enabled"];
            $this->title = $this->settings["title"];
            $this->description = $this->settings["description"];
            $this->merchant = $this->settings["merchant"];
            $this->accesstoken = $this->settings["accesstoken"];
            $this->secrettoken = $this->settings["secrettoken"];
			$this->paymentwindowid = $this->settings["paymentwindowid"];
			$this->windowstate = $this->settings["windowstate"];
			$this->instantcapture = $this->settings["instantcapture"];
            $this->immediateredirecttoaccept = $this->settings["immediateredirecttoaccept"];
            $this->md5key = $this->settings["md5key"];

            // Set description for checkout page
            $this->set_bambora_description_for_checkout();

            // Actions
            add_action('init', array(& $this, 'check_callback'));
            add_action('valid-bambora-callback', array(&$this, 'successful_request'));
            add_action('add_meta_boxes', array( &$this, 'bambora_meta_boxes' ), 10, 0);
            add_action('woocommerce_api_' . strtolower(get_class()), array($this, 'check_callback'));
            add_action('wp_before_admin_bar_render', array($this, 'bambora_action', ));
            add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options', ));
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('woocommerce_receipt_bambora', array($this, 'receipt_page'));
        }

        /**
         * Initialise Gateway Settings Form Fields
         */
        function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                                'title' => __( 'Enable/Disable', 'woocommerce'),
                                'type' => 'checkbox',
                                'label' => __( 'Enable Bambora Checkout', 'woocommerce'),
                                'default' => 'yes'
                            ),
                'title' => array(
                                'title' => __( 'Title', 'bambora' , 'woocommerce-gateway-bambora'),
                                'type' => 'text',
                                'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce'),
                                'default' => __( 'Bambora Checkout', 'bambora')
                            ),
                'description' => array(
                                'title' => __( 'Description', 'woocommerce' , 'woocommerce-gateway-bambora'),
                                'type' => 'textarea',
                                'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce'),
                                'default' => __("Pay using Bambora Checkout", 'woocommerce-gateway-bambora')
                            ),
                'merchant' => array(
                                'title' => __( 'Merchant number', 'woocommerce-gateway-bambora'),
                                'type' => 'text',
                                'description' => __('Get your Merchant number from the <a href="https://merchant.bambora.com/" target="_blank">Bambora Administration</a> via Settings -> Merchant numbers. If you haven\'t got a Merchant number, please contact <a href="http://www.bambora.com/da/dk/bamboraone/" target="_blank">Bambora</a> to get one. <br/><b>Note:</b> This field is mandatory to enable payments.', 'woocommerce'),
                                'default' => ''
                            ),
                'accesstoken' => array(
                                'title' => __( 'Access token', 'woocommerce-gateway-bambora'),
                                'type' => 'text',
                                'description' => __('Get your Access token from the <a href="https://merchant.bambora.com/" target="_blank">Bambora Administration</a> via Settings -> API users. Copy the Access token from the API user into this field.<br/><b>Note:</b> This field is mandatory in order to enable payments.', 'woocommerce'),
                                'default' => ''
                            ),
                'secrettoken' => array(
                                'title' => __( 'Secret token', 'woocommerce-gateway-bambora'),
                                'type' => 'password',
                                'description' => __('Get your Secret token from the <a href="https://merchant.bambora.com/" target="_blank">Bambora Administration</a> via Settings -> API users.<br/>The secret token is only displayed once when an API user is created! Please save this token in a safe place as Bambora will not be able to recover it.<br/><b>Note: </b> This field is mandatory in order to enable payments.', 'woocommerce'),
                                'default' => ''
                            ),
                'md5key' => array(
                                'title' => __( 'MD5 Key', 'woocommerce-gateway-bambora'),
                                'type' => 'text',
                                'description' => __( 'We recommend using MD5 to secure the data sent between your system and Bambora.<br/>If you have generated a MD5 key in the <a href="https://merchant.bambora.com/" target="_blank">Bambora Administration</a> via Settings -> Edit merchant, you have to enter the MD5 key here as well. <br/><b>Note:</b> The keys must be identical in the two systems.', 'woocommerce'),
                                'default' => ''
                            ),
                'paymentwindowid' => array(
                                'title' => __( 'Payment Window ID', 'woocommerce-gateway-bambora'),
                                'type' => 'text',
                                'description' => __( 'Choose which payment window to use. You can create multiple payment windows in the <a href="https://merchant.bambora.com/" target="_blank">Bambora Administration</a> via Settings -> Payment windows.<br/>This is useful if you want to show different layouts, payment types or transaction fees for various customers.', 'woocommerce'),
                                'default' => '1'
                            ),
                'windowstate' => array(
                                'title' => __( 'Display window as', 'woocommerce-gateway-bambora'),
                                'type' => 'select',
                                'description' => __('Please select if you want the Payment window shown as an overlay or as full screen.', 'woocommerce'),
                                'options' => array(2 => 'Overlay',1 => 'Full screen'),
                                'label' => __( 'How to open the Bambora Checkout', 'woocommerce-gateway-bambora'),
                                'default' => 2
                            ),

                'instantcapture' => array(
                                'title' => __( 'Instant capture', 'woocommerce-gateway-bambora'),
                                'type' => 'checkbox',
                                'description' => __('Enable this to capture the payment immediately.<br/>You should only use this setting, if your customer receives the goods immediately e.g. via downloads or services.', 'woocommerce'),
                                'label' => __( 'Enable instant capture', 'woocommerce-gateway-bambora'),
                                'default' => 'no'
                            ),
                'immediateredirecttoaccept' => array(
                                'title' => __( 'Immediate redirect to order confirmation page', 'woocommerce-gateway-bambora'),
                                'type' => 'checkbox',
                                'description' => __('Please select if you to go directly to the order confirmation page when payment is completed.', 'woocommerce'),
                                'label' => __( 'Enable Immediate redirect', 'woocommerce-gateway-bambora'),
                                'default' => 0
                            ),


                );

        } // End init_form_fields()

        /**
         * Admin Panel Options
         * - Options for bits like 'title' and availability on a country-by-country basis
         *
         * @since 1.0.0
         */
        public function admin_options()
        {
            $plugin_data = get_plugin_data(__FILE__, false, false);
            $version = $plugin_data["Version"];

            echo '<h3>' . 'Bambora Payment Solutions' . ' v' . $version . '</h3>';
            echo __('<a href="http://dev.bambora.com/carts.html#woo-commerce" target="_blank">Documentation can be found here</a>', 'woocommerce-gateway-bambora');
            echo '<table class="form-table">';
            // Generate the HTML For the settings form.
            $this->generate_settings_html();
            echo '</table>';
        }

        /**
         * There are no payment fields for bambora, but we want to show the description if set.
         **/
        function payment_fields()
        {
            if($this->description)
                echo wpautop(wptexturize($this->description));
        }

        /**
         * Fix Url
         *
         * @param string $url
         * @return string
         * */
        function fix_url($url)
        {
            $url = str_replace('&#038;', '&amp;', $url);
            $url = str_replace('&amp;', '&', $url);

            return $url;
        }

        /**
         * Generate Bambora payment window
         *
         * @param int $order_id
         * @return string
         * */
        public function generate_bambora_paymentwindow($order_id)
        {
            $order = new WC_Order($order_id);
            $minorUnits = bamboraCurrency::getCurrencyMinorunits(get_woocommerce_currency());

            $bamboraCustommer = $this->create_bambora_custommer($order);
            $bamboraOrder = $this->create_bambora_order($order,$minorUnits);
            $bamboraUrl = $this->create_bambora_url($order);

            $request = new BamboraCheckoutRequest();
            $request->customer = $bamboraCustommer;
            $request->instantcaptureamount = $this->instantcapture == 'yes' ? $bamboraOrder -> total : 0;
            $request->language = str_replace("_","-",get_locale());
            $request->order = $bamboraOrder;
            $request->url = $bamboraUrl;
            $request->paymentwindowid = $this->paymentwindowid;

            $api = new BamboraApi(BamboraHelper::generateApiKey($this->merchant, $this->accesstoken, $this->secrettoken));

            $expressRes = $api -> getcheckoutresponse($request);

            $json = json_decode($expressRes, true);

            if ($json['meta']['result'] == false)
            {
                return "<p>".$json['meta']['message']["enduser"]."<p>";
            }
            $bambora_paymentwindow = $api->getcheckoutpaymentwindowjs();

            $bambora_checkouturl = $json['url'];

            echo '<p>' . __('Thank you for paying with Bambora.', 'woocommerce-gateway-bambora') . '</p>';

            $bamboraScript = BamboraHelper::create_bambora_paymentscript($bambora_paymentwindow,$this->windowstate, $bambora_checkouturl, true);

            $bamboraScript .= "<a class='button' onclick=' javascript: openPaymentWindow();' id='submit_bambora_payment_form'/>" . __('Pay now', 'woocommerce-gateway-bambora') . "</a>&nbsp;";
            $bamboraScript .= "<a class='button cancel' href='".esc_url($order->get_cancel_order_url()) . "'>". __('Cancel order &amp; restore cart', 'woocommerce-gateway-bambora') . "</a>";

            return $bamboraScript;
        }

        /**
         * Create Bambora customer
         *
         * @param WC_Order $order
         * @return BamboraCustomer
         * */
        private function create_bambora_custommer($order)
        {
            $bamboraCustommer = new BamboraCustomer();
            $bamboraCustommer ->email = $order->billing_email;
            $bamboraCustommer ->phonenumber = $order ->billing_phone;
            $bamboraCustommer ->phonenumbercountrycode = $order->billing_country;
            return $bamboraCustommer;
        }

        /**
         * Create Bambora order
         *
         * @param WC_Order $order
         * @param $minorUnits
         * @return BamboraOrder
         * */
        private function create_bambora_order($order,$minorUnits)
        {
            $bamboraOrder = new BamboraOrder();
            $bamboraOrder->billingaddress = $this->create_bambora_billing_address($order);
            $bamboraOrder->currency = get_woocommerce_currency();
            $bamboraOrder->lines = $this->create_bambora_orderlines($order,$minorUnits);

            $ordernumber = str_replace(_x( '#', 'hash before order number', 'woocommerce'), "", $order->get_order_number());
            $bamboraOrder -> ordernumber = ((int)$ordernumber);

            $bamboraOrder->shippingaddress = $this->create_bambora_shipping_address($order);
            $bamboraOrder -> total =  BamboraCurrency::convertPriceToMinorUnits($order->order_total,$minorUnits);

            $bamboraOrder -> vatamount = BamboraCurrency::convertPriceToMinorUnits($order ->get_total_tax(),$minorUnits);

            return $bamboraOrder;
        }

        /**
         * Create Bambora billing address
         *
         * @param WC_Order $order
         * @return BamboraAddress
         * */
        private function create_bambora_billing_address($order)
        {
            $bamboraAddress = new BamboraAddress();
            $bamboraAddress->att = "";
            $bamboraAddress->city = $order->billing_city;
            $bamboraAddress->country = $order->billing_country;
            $bamboraAddress->firstname = $order->billing_first_name;
            $bamboraAddress->lastname = $order->billing_last_name;
            $bamboraAddress->street = $order->billing_address_1;
            $bamboraAddress->zip = $order->billing_postcode;

            return $bamboraAddress;
        }

        /**
         * Create Bambora shipping address
         *
         * @param WC_Order $order
         * @return BamboraAddress
         * */
        private function create_bambora_shipping_address($order)
        {
            $bamboraAddress = new BamboraAddress();
            $bamboraAddress->att = "";
            $bamboraAddress->city = $order->shipping_city;
            $bamboraAddress->country = $order->shipping_country;
            $bamboraAddress->firstname = $order->shipping_first_name;
            $bamboraAddress->lastname = $order->shipping_last_name;
            $bamboraAddress->street = $order->shipping_address_1;
            $bamboraAddress->zip = $order->shipping_postcode;

            return $bamboraAddress;
        }

        private function create_bambora_url($order)
        {
            $bamboraUrl = new BamboraUrl();
            $bamboraUrl->accept = $this->fix_url($this->get_return_url($order));
            $bamboraUrl->decline = $this->fix_url($order->get_cancel_order_url());


            $bamboraUrl->callbacks = array();
            $callback = new BamboraCallback();
            $callback->url = $this->fix_url(add_query_arg ('wooorderid',$order->id, add_query_arg ('wc-api', 'WC_Gateway_Bambora', $this->get_return_url( $order ))));

            $bamboraUrl->callbacks[] = $callback;
            $bamboraUrl->immediateredirecttoaccept = $this->immediateredirecttoaccept == "yes" ? 1 : 0;
            return $bamboraUrl;
        }

        /**
         * Creates orderlines for an order
         *
         * @param WC_Order $order
         * @param int $minorUnits
         * @return BamboraOrderLine[]
         */
        private function create_bambora_orderlines($order,$minorUnits)
        {
            $bamboraOrderlines = array();

            $wc_tax = new WC_Tax();

            $items = $order->get_items();
            $lineNumber = 0;
            foreach($items as $item)
            {
                $line = new BamboraOrderLine();
                $line->description = $item["name"];
                $line->id = $item["product_id"];
                $line->linenumber = ++$lineNumber;
                $line->quantity = $item["qty"];
                $line->text = $item["name"];
                $line->totalprice = BamboraCurrency::convertPriceToMinorUnits($order->get_line_total($item, false, true), $minorUnits, false);
                $line->totalpriceinclvat = BamboraCurrency::convertPriceToMinorUnits($order->get_line_total($item, true, true), $minorUnits, false);
                $line->totalpricevatamount =  BamboraCurrency::convertPriceToMinorUnits($order->get_line_tax( $item ), $minorUnits, false);
                $line->unit = __("pcs.", 'woocommerce-gateway-bambora');
                $product = $order->get_product_from_item($item);
                $item_tax_class = $product->get_tax_class();
                $item_tax_rate_array = $wc_tax->get_rates($item_tax_class);
                $item_tax_rate = array_shift($item_tax_rate_array);
                if(isset($item_tax_rate["rate"]))
                {
                    $line->vat = $item_tax_rate["rate"];
                }
                else
                {
                    $line->vat = 0;
                }

                $bamboraOrderlines[] = $line;
            }

            $shipping_methods = $order->get_shipping_methods();
            if($shipping_methods && count($shipping_methods) != 0)
            {
                $shipping_total = $order->get_total_shipping();
                $shipping_tax = $order->get_shipping_tax();
                $shippingOrderline = new BamboraOrderLine();
                $shippingOrderline->id = __("Shipping", 'woocommerce-gateway-bambora');
                $shippingOrderline->description = __("Shipping", 'woocommerce-gateway-bambora');
                $shippingOrderline->quantity = 1;
                $shippingOrderline->text = __("Shipping", 'woocommerce-gateway-bambora');
                $shippingOrderline->unit = __("pcs.", 'woocommerce-gateway-bambora');
                $shippingOrderline->linenumber = ++$lineNumber;
                $shippingOrderline->totalprice = BamboraCurrency::convertPriceToMinorUnits($shipping_total, $minorUnits);
                $shippingOrderline->totalpriceinclvat = BamboraCurrency::convertPriceToMinorUnits($shipping_total + $shipping_tax, $minorUnits);
                $shippingOrderline->totalpricevatamount = BamboraCurrency::convertPriceToMinorUnits($shipping_tax, $minorUnits);

                $shippingOrderline->vat = round( $shipping_tax/ $shipping_total * 100);
                $bamboraOrderlines[] = $shippingOrderline;
            }

            return $bamboraOrderlines;
        }

        /**
         * Set the WC Payment Gateway description for the checkout page  
         */
        function set_bambora_description_for_checkout()
        {
            global $woocommerce;
            $cart_total = $woocommerce->cart->total;

            if($cart_total && $cart_total > 0)
            {
                $currency = get_woocommerce_currency();
                if(!$currency)
                {
                    return;
                }
                $minorUnits = BamboraCurrency::getCurrencyMinorunits($currency);
                $amount = BamboraCurrency::convertPriceToMinorUnits($cart_total,$minorUnits);
                $api = new BamboraApi(BamboraHelper::generateApiKey($this->merchant, $this->accesstoken, $this->secrettoken));
                $paymentTypeIds = $api->getAvaliablePaymentGroupIdsForMerchant($currency, $amount);
                foreach($paymentTypeIds as $id)
                {
                    $this->description .='<img src="https://d3r1pwhfz7unl9.cloudfront.net/paymentlogos/'.$id.'.png" width="45"/>';
                }
            }
        }



        /**
         * Process the payment and return the result
         *
         * @param int $order_id
         * @return string[]
         * @throws Exception
         */
        function process_payment($order_id)
        {
            $order = new WC_Order($order_id);

            return array(
                'result'     => 'success',
                'redirect'    => $order->get_checkout_payment_url( true )
            );
        }

        function process_refund($order_id, $amount = null, $reason = '')
        {
            $order = new WC_Order($order_id);
            $refunds = $order->get_refunds();
            $currency = $order->order_currency;
            $minorUnits = BamboraCurrency::getCurrencyMinorunits($currency);
            $amount = BamboraCurrency::convertPriceToMinorUnits($amount,$minorUnits);

            $bamboraRefundLines = array();
            if(!$this->try_create_bambora_refund_lines($refunds[0], $bamboraRefundLines, $minorUnits))
            {
                return false;
            }

            $api = new BamboraApi(BamboraHelper::generateApiKey($this->merchant, $this->accesstoken, $this->secrettoken));
            $transactionId = get_post_meta($order->id, 'Transaction ID', true);
            $credit = $api->credit($transactionId, $amount, $currency, $bamboraRefundLines );
            if(!is_wp_error($credit) && $credit)
            {
                $rest_result = json_decode($credit, true);
                if(isset($rest_result) && $rest_result["meta"]["result"])
                {
                    return true;
                }
            }
            else
            {
                $error_string = '';
                foreach($credit->get_error_messages() as $error)
                {
                    $error_string .= $error->get_error_message();
                }
                throw new exception($error_string);
            }
            return false;
        }

        /**
         * Try and create refund lines. If there is a negativ amount on one of the refund items, it fails.
         *
         * @param WC_Order_Refund $refund
         * @param BamboraOrderLine[] $bamboraRefundLines
         * @param int $minorUnits
         * @param string $reason
         * @return boolean
         * @throws Exception
         */
        private function try_create_bambora_refund_lines($refund,&$bamboraRefundLines,$minorUnits,$reason='')
        {
            $wc_tax = new WC_Tax();
            $lineNumber = 0;
            $total = $refund->get_total();
            $items_total = 0;

            $refund_items = $refund->get_items();
            foreach($refund_items as $item)
            {
                $line_total = $refund->get_line_total($item, true, true);
                if($line_total > 0)
                {
                    throw new exception( __( 'Invalid refund amount for item', 'woocommerce-gateway-bambora' ).':'.$item["name"] );
                }
                $line = new BamboraOrderLine();
                $line->description = $item["name"];
                $line->id = $item["product_id"];
                $line->linenumber = ++$lineNumber;
                $line->quantity = $item["qty"];
                $line->text = $item["name"];
                $line->totalpriceinclvat = BamboraCurrency::convertPriceToMinorUnits($line_total, $minorUnits, false) * -1;
                $items_total += $line_total;
                $line->unit = __("pcs.", 'woocommerce-gateway-bambora');
                $product = $refund->get_product_from_item($item);
                $item_tax_class = $product->get_tax_class();
                $item_tax_rate_array = $wc_tax->get_rates($item_tax_class);
                $item_tax_rate = array_shift($item_tax_rate_array);
                if(isset($item_tax_rate["rate"]))
                {
                    $line->vat = $item_tax_rate["rate"];
                }
                else
                {
                    $line->vat = 0;
                }
                $bamboraRefundLines[] = $line;
            }

            $shipping_methods = $refund->get_shipping_methods();

            if($shipping_methods && count($shipping_methods) != 0)
            {
                $shipping_total = $refund->get_total_shipping();
                $shipping_tax = $refund->get_shipping_tax();

                if($shipping_total > 0 || $shipping_tax > 0)
                {
                    throw new exception( __( 'Invalid refund amount for shipping', 'woocommerce-gateway-bambora' ) );
                }

                $shippingOrderLine = new BamboraOrderLine();
                $shippingOrderLine->id = __("shipping", 'woocommerce-gateway-bambora');
                $shippingOrderLine->linenumber = ++$lineNumber;
                $shippingOrderLine->description = __("Shipping", 'woocommerce-gateway-bambora');
                $shippingOrderLine->text = __("Shipping", 'woocommerce-gateway-bambora');
                $shippingOrderLine->quantity = 1;
                $shippingOrderLine->unit = __("pcs.", 'woocommerce-gateway-bambora');
                $shippingOrderLine->totalpriceinclvat = BamboraCurrency::convertPriceToMinorUnits($shipping_total + $shipping_tax, $minorUnits) * -1;
                $shippingOrderLine->vat = 0;
                $bamboraRefundLines[] = $shippingOrderLine;
                $items_total += $shipping_total + $shipping_tax;
            }

            if($items_total < $total)
            {
                return false;
            }
            else if ($items_total > $total)
            {
                $additionalRefundOrderLine = new BamboraOrderLine();
                $additionalRefundOrderLine->id = __("Refund", 'woocommerce-gateway-bambora');
                $additionalRefundOrderLine->linenumber = ++$lineNumber;
                $additionalRefundOrderLine->description = __("Refund", 'woocommerce-gateway-bambora').($reason !== '' ? ': '.$reason : '');
                $additionalRefundOrderLine->text = __("Refund", 'woocommerce-gateway-bambora');
                $additionalRefundOrderLine->quantity = 1;
                $additionalRefundOrderLine->unit = __("pcs.", 'woocommerce-gateway-bambora');
                $additionalRefundOrderLine->totalpriceinclvat = BamboraCurrency::convertPriceToMinorUnits($total-$items_total, $minorUnits) * -1;
                $additionalRefundOrderLine->vat = 0;
                $bamboraRefundLines[] = $additionalRefundOrderLine;
            }

            return true;
        }

        /**
         * Receipt page
         *
         * @param WC_Order $order
         **/
        function receipt_page( $order )
        {
            echo $this->generate_bambora_paymentwindow($order);
        }

        /**
         * Check for Bambora IPN Response
         **/
        function check_callback()
        {
            $_GET = stripslashes_deep($_GET);
            do_action("valid-bambora-callback", $_GET);
        }

        /**
         * Successful Payment!
         **/
        function successful_request($posted)
        {
            $api = new BamboraApi(BamboraHelper::generateApiKey($this->merchant, $this->accesstoken, $this->secrettoken));
            try
            {
                $api_result = $api->gettransactionInformation($posted["txnid"]);
                $rest_result = json_decode($api_result, true);

                if(!isset($rest_result))
                {
                    status_header(400);
                    return;
                }

                if (!$rest_result["meta"]["result"])
                {
                    status_header(400);
                    echo $rest_result["meta"]["message"]["enduser"];
                    return;
                }

                $order = wc_get_order( $posted["wooorderid"] );

                if(isset($order) && $order->has_status('pending'))
                {
                    //Validate MD5
                    $var = "";
                    if(strlen($this->md5key) > 0)
                    {
                        foreach($posted as $key => $value)
                        {
                            if($key != "hash")
                                $var .= $value;
                        }

                        $genstamp = md5($var . $this->md5key);

                        if(!hash_equals($genstamp, $posted["hash"]))
                        {
                            echo "MD5 error";
                            $order->add_order_note('MD5 check failed');
                            error_log('MD5 check failed for Bambora callback with order_id:' . $posted["wooorderid"]);
                            status_header(500);
                            return;
                        }
                    }

                    // Payment completed
                    $order->add_order_note(__('Callback completed', 'woocommerce-gateway-bambora'));
                    $minorUnits = BamboraCurrency::getCurrencyMinorunits($order->order_currency);

                    if($this->settings["addfeetoorder"] == "yes")
                    {
                        $order_fee              = new stdClass();
                        $order_fee->id          = 'bambora_fee';
                        $order_fee->name        = __('Fee', 'woocommerce-gateway-bambora');
                        $order_fee->amount      = isset( $posted['txnfee'] ) ? BamboraCurrency::convertPriceFromMinorUnits($posted['txnfee'],$minorUnits) : 0;
                        $order_fee->taxable     = false;
                        $order_fee->tax         = 0;
                        $order_fee->tax_data    = array();

                        $order->add_fee($order_fee);
                        $order->set_total($order->order_total + BamboraCurrency::convertPriceFromMinorUnits($posted['txnfee'],$minorUnits));
                    }

                    $order->payment_complete();

                    update_post_meta((int)$posted["wooorderid"], 'Transaction ID', $posted["txnid"]);
                    update_post_meta((int)$posted["wooorderid"], 'Card no', $posted["cardno"]);
                }
                status_header(200);
                echo "Ok";

            }
            catch (Exception $e)
            {
                echo $this->message("error", $e->getMessage());
            }
        }

        public function bambora_meta_boxes()
        {
            add_meta_box(
                'bambora-payment-actions',
                __('Bambora Payment Solutions', 'woocommerce-gateway-bambora'),
                array(&$this, 'bambora_meta_box_payment'),
                'shop_order',
                'side',
                'high'
            );
        }

        public function bambora_action()
        {
            if(isset($_GET["bambora_action"]))
            {
                $order = new WC_Order($_GET['post']);
                $transactionId = get_post_meta($order->id, 'Transaction ID', true);
                $currency = $order->order_currency;
                $minorUnits = BamboraCurrency::getCurrencyMinorunits($currency);
                $api = new BamboraApi(BamboraHelper::generateApiKey($this->merchant, $this->accesstoken, $this->secrettoken));
                try
                {
                    switch($_GET["bambora_action"])
                    {
                        case 'capture':

                            $amount = str_replace(wc_get_price_decimal_separator(),".",$_GET["amount"]);
                            $amount = BamboraCurrency::convertPriceToMinorUnits($amount,$minorUnits);
                            $capture = $api->capture($transactionId, $amount,$currency);
                            $captureJson = $api->convertJSonResultToArray($capture,"meta");
                            if(!is_wp_error($capture))
                            {
                                if($captureJson["result"])
                                {
                                    echo $this->message('updated', __("Payment successfully","woocommerce-gateway-bambora").' <strong>'.__("Captured","woocommerce-gateway-bambora").'</strong>.');
                                }else
                                {
                                    echo $this->message('updated', $captureJson["message"]["merchant"]);
                                }

                            }
                            else
                            {
                                foreach ($capture->get_error_messages() as $error)
                                    throw new Exception ($error->get_error_message());
                            }

                            break;

                        //case 'credit':
                        //    $amount = str_replace(wc_get_price_decimal_separator(),".",$_GET["amount"]);
                        //    $amount = BamboraCurrency::convertPriceToMinorUnits($amount,$minorUnits);
                        //    $credit = $api->credit($transactionId, $amount,$currency);
                        //    $creditJson = $api->convertJSonResultToArray($credit, "meta");
                        //    if(!is_wp_error($credit))
                        //    {
                        //        if($creditJson["result"])
                        //        {
                        //            echo $this->message('updated', __("Payment successfully","woocommerce-gateway-bambora").' <strong>'.__("Refunded","woocommerce-gateway-bambora").'</strong>.');
                        //        }else{
                        //            echo $this->message('updated', $creditJson["message"]["merchant"]);
                        //        }
                        //    }
                        //    else
                        //    {
                        //        foreach($credit->get_error_messages() as $error)
                        //            throw new Exception ($error->get_error_message());
                        //    }

                        //    break;

                        case 'delete':
                            $delete = $api->delete($transactionId);
                            $deleteJson = $api->convertJSonResultToArray($delete, "meta");
                            if(!is_wp_error($delete))
                            {
                                if($deleteJson["result"])
                                {
                                    echo $this->message('updated', __("Payment successfully","woocommerce-gateway-bambora").' <strong>'.__("Deleted","woocommerce-gateway-bambora").'</strong>.');
                                }else
                                {
                                    echo $this->message('updated', $deleteJson["message"]["merchant"]);
                                }

                            }
                            else
                            {
                                foreach ($delete->get_error_messages() as $error)
                                    throw new Exception ($error->get_error_message());
                            }

                            break;
                    }
                }
                catch(Exception $e)
                {
                    echo $this->message("error", $e->getMessage());
                }
            }
        }

        public function bambora_meta_box_payment()
        {
            global $post;

            $order = new WC_Order($post->ID);

            $transactionId = get_post_meta($order->id, 'Transaction ID', true);

            if(strlen($transactionId) > 0)
            {
                try
                {
                    if(!is_wp_error($transactionId))
                    {
                        $api = new BamboraApi(BamboraHelper::generateApiKey($this->merchant, $this->accesstoken, $this->secrettoken));
                        $rest_result = $api->gettransactionInformation($transactionId);
                        $operationsRes = $api->getTransactionOperations($transactionId);

                        if($rest_result == null || $operationsRes == null)
                        {
                            echo "Could not connect to Bambora backend";
                            return;
                        }

                        $transMeta = $api-> convertJSonResultToArray($rest_result, "meta");
                        $transInfo = $api-> convertJSonResultToArray($rest_result, "transaction");
                        $operations = $api-> convertJSonResultToArray($operationsRes, "transactionoperations");
                        $operationsMeta = $api-> convertJSonResultToArray($operationsRes, "meta");

                        if ($transInfo == null || $operations == null || $transMeta["result"] == false || $operationsMeta["result"] == false)
                        {
                            echo $this->getErrormessageUsingJSon($transMeta,$operationsMeta );
                            return;
                        }

                        $minorUnits = $transInfo["currency"]["minorunits"];
                        if($minorUnits == "" || $minorUnits == null)
                            $minorUnits = BamboraCurrency::getCurrencyMinorunits($order->order_currency);

                        $totalAuthorized = BamboraCurrency::convertPriceFromMinorUnits($transInfo["total"]["authorized"],$minorUnits);
                        $totalCaptured =  BamboraCurrency::convertPriceFromMinorUnits($transInfo["total"]["captured"],$minorUnits);
                        $availableForCapture = BamboraCurrency::convertPriceFromMinorUnits($transInfo["available"]["capture"],$minorUnits);
                        $totalCredited =  BamboraCurrency::convertPriceFromMinorUnits($transInfo["total"]["credited"],$minorUnits);
                        //$availableForCredit =  BamboraCurrency::convertPriceFromMinorUnits($transInfo["available"]["credit"],$minorUnits);
                        $canDelete = $transInfo["candelete"];


                        echo '<div class="bambora_info">';
                        echo    '<div class="bambora_transactionid">';
                        echo        '<p>';
                        _e('Transaction ID', 'woocommerce-gateway-bambora');
                        echo        '</p>';
                        echo        '<p>'.$transInfo["id"].'</p>';
                        echo    '</div>';
                        echo '<br/>';

                        echo '<div class="bambora_info_overview">';
                        echo    '<p>';
                        _e('Authorized:', 'woocommerce-gateway-bambora');
                        echo    '</p>';
                        echo '<p>'.$this->formatNumber($totalAuthorized,$minorUnits). ' ' . $order->get_order_currency().'</p>';
                        echo '</div>';

                        echo '<div class="bambora_info_overview">';
                        echo    '<p>';
                        _e('Captured:', 'woocommerce-gateway-bambora');
                        echo    '</p>';
                        echo '<p>'.$this->formatNumber($totalCaptured,$minorUnits). ' ' . $order->get_order_currency().'</p>';
                        echo '</div>';

                        echo '<div class="bambora_info_overview">';
                        echo    '<p>';
                        _e('Refunded:', 'woocommerce-gateway-bambora');
                        echo    '</p>';
                        echo '<p>'.$this->formatNumber($totalCredited,$minorUnits). ' ' . $order->get_order_currency().'</p>';
                        echo '</div>';

                        echo '</div>';
                        echo '<br/>';


                        if($availableForCapture > 0 )//|| $availableForCredit > 0)
                        {
                            echo '<div class="bambora_action_container">';

                            if($availableForCapture > 0)
                            {
                                echo '<div class="bambora_action">';
                                echo '<p>'.$order->get_order_currency().'</p>';
                                echo '<input type="text" value="' . $this->formatNumber($availableForCapture,$minorUnits). '"id="bambora_capture_amount" class="bambora_amount" name="bambora_amount" />';
                                echo '<a class="button capture" onclick="javascript: (confirm(\'' . __('Are you sure you want to capture?', 'woocommerce-gateway-bambora') . '\') ? (location.href=\'' . admin_url('post.php?post=' . $post->ID . '&action=edit&bambora_action=capture') . '&amount=\' + document.getElementById(\'bambora_capture_amount\').value.replace(\''.wc_get_price_thousand_separator().'\',\'\')) : (false));">';
                                _e('Capture', 'woocommerce-gateway-bambora');
                                echo '</a>';
                                echo '</div>';
                                echo '<br/>';
                            }
                            //if($availableForCredit > 0)
                            //{
                            //    echo '<div class="bambora_action">';
                            //    echo '<p>'.$order->get_order_currency().'</p>';
                            //    echo '<input type="text" value="' . $this->formatNumber($availableForCredit,$minorUnits). '"id="bambora_credit_amount" class="bambora_amount" name="bambora_credit_amount" />';
                            //    echo '<a class="button credit" onclick="javascript: (confirm(\'' . __('Are you sure you want to credit?', 'woocommerce-gateway-bambora') . '\') ? (location.href=\'' . admin_url('post.php?post=' . $post->ID . '&action=edit&bambora_action=credit') . '&amount=\' + document.getElementById(\'bambora_credit_amount\').value.replace(\''.wc_get_price_thousand_separator().'\',\'\')) : (false));">';
                            //    _e('Refund ', 'woocommerce-gateway-bambora');
                            //    echo '</a>';
                            //    echo '</div>';
                            //    echo '<br/>';

                            //}

                            if ($totalCaptured == 0 && $canDelete == true)
                            {
                                echo '<div class="bambora_action">';
                                echo '<a class="button delete "  onclick="javascript: (confirm(\'' . __('Are you sure you want to Delete?', 'woocommerce-gateway-bambora') . '\') ? (location.href=\''. admin_url('post.php?post=' . $post->ID . '&action=edit&bambora_action=delete\'') .') : (false));">';
                                _e('Delete', 'woocommerce-gateway-bambora');
                                echo '</a>';
                                echo '</div>';
                            }

                            echo '</div>';
                            echo '<br />';
                        }


                        echo $this->buildTransactionLogtable($operations,$minorUnits);
                        echo '<br/>';
                    }
                    else
                    {
                        foreach ($transactionId->get_error_messages() as $error)
                        {
                            throw new Exception ($error->get_error_message());
                        }
                    }
                }
                catch(Exception $e)
                {
                    echo $this->message("error", $e->getMessage());
                }
            }
            else
            {
                _e("No transaction was found","woocommerce-gateway-bambora");
            }
        }

        private function formatNumber($number,$decimals)
        {
            return number_format($number,$decimals,wc_get_price_decimal_separator(), wc_get_price_thousand_separator());
        }

        /**
         * Build transaction log table HTML
         *
         * @param string[] $operations
         * @param int $minorUnits
         * @return string
         * */
        private function buildTransactionLogtable($operations, $minorUnits){

            $html = "";

            if (count($operations) > 0 )
            {
                echo '<h4>';
                _e('TRANSACTION HISTORY', 'woocommerce-gateway-bambora');
                echo '</h4>';

                $html .= '<table class="bambora-table">';

                foreach($operations as $arr)
                {
                    $html .= $this -> buildTransactionLogRow($arr,$minorUnits);
                    if(!$arr["transactionoperations"] == null)
                    {
                        foreach($arr["transactionoperations"] as $op)
                        {
                            $html .= $this -> buildTransactionLogRow($op,$minorUnits);
                        }
                    }
                }
                $html .= '</table>';
            }

            return $html;
        }

        /**
         * Build transaction log row HTML
         *
         * @param string[] $operation
         * @param int $minorUnits
         * @return string
         * */
        private function buildTransactionLogRow($operation,$minorUnits)
        {
            $zone =  date_default_timezone_get();
            $tz = new DateTimeZone($zone);
            $date =str_replace("T", " ",substr($operation["createddate"],0,19));

            $utcDate = new DateTime($date,new DateTimeZone('UTC'));
            $localDate = $utcDate->setTimezone($tz);

            $html = '<tr class="bambora_transaction_date">';
            $html .= '<td>';
            if ($this->context->language->language_code == 'en_US')
            {
                //show date as December 17, 2015, 01:49:45 PM
                $html .= $localDate->format('F d\, Y\, g:i:s a');
            }
            else
            {
                //show date as 17. December 2015, 13:49:45"
                $html .= $localDate->format('d\. F Y\, H:i:s');
            }
            $html .= '</td>';
            $html .= '</tr>';

            $html .= '<tr class="bambora_transaction">';
            $amount = $this->formatNumber(BamboraCurrency::convertPriceFromMinorUnits($operation["amount"],$minorUnits),$minorUnits);
            $html .= '<td>'.$this->convertAction($operation["action"]).'</td>';

            if($operation["action"] != "Delete")
                $html .= '<td>'. $amount . ' '.$operation["currency"]["code"] ."</td>";
            else
                $html .= '<td>-</td>';
            $html .= '</tr>';


            return $html;
        }

        /**
         * Convert action
         *
         * @param string $action
         * @return string
         * */
        private function convertAction($action)
        {
            if($action == "Authorize")
                return __('Authorized', 'woocommerce-gateway-bambora');
            else if($action == "Capture")
                return __('Captured', 'woocommerce-gateway-bambora');
            else if($action == "Credit")
                return __('Refunded', 'woocommerce-gateway-bambora');
            else if($action == "Delete")
                return __('Deleted', 'woocommerce-gateway-bambora');
            else
                return $action;
        }

        /**
         * Get error message from parsed json response
         *
         * @param string[] $transMeta
         * @param string[] $operationsMeta
         * @return string
         * */
        private function getErrormessageUsingJSon($transMeta, $operationsMeta){
            $errMessage = 'Could not lookup the transaction. Reason: ';

            if($transMeta["result"] == false && $operationsMeta["result"] == false)
            {
                $errMessage .= $transMeta["message"]["merchant"].' and '.$operationsMeta["message"]["merchant"];
            }
            else if($transMeta["result"] == false)
            {
                $errMessage .= $transMeta["message"]["merchant"];
            }
            else if($operationsMeta["result"] == false)
            {
                $errMessage .= $operationsMeta["message"]["merchant"];
            }

            return $errMessage;
        }

        /**
         * Convert message to HTML
         *
         * @param string $type
         * @param string $message
         * @return string
         * */
        private function message($type, $message) {
            return '<div id="message" class="'.$type.'">
                <p>'.$message.'</p>
            </div>';
        }
    }

    /**
     * Add the Gateway to WooCommerce
     **/
    function add_bambora_gateway($methods)
    {
        $methods[] = 'WC_Gateway_Bambora';
        return $methods;
    }

    function init_bambora_gateway()
    {
        $plugin_dir = basename(dirname(__FILE__ ));
        load_plugin_textdomain('woocommerce-gateway-bambora', false, $plugin_dir . '/languages/');
    }

    add_filter('woocommerce_payment_gateways', 'add_bambora_gateway');
    add_action('plugins_loaded', 'init_bambora_gateway');

    function WC_Gateway_Bambora()
    {
        return new WC_Gateway_Bambora();
    }

    if (is_admin())
        add_action('load-post.php', 'WC_Gateway_Bambora');
}
