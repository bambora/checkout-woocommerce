<?php
/*
Plugin Name: WooCommerce Bambora Checkout Payment Gateway
Plugin URI: http://www.bambora.com
Description: A payment gateway for <a href="http://www.bambora.com/sv/se/betalningslosningar/e-handel/produkter/bambora-checkout">Bambora Checkout</a>.
Version: 1.4.6
Author: Bambora
Author URI: http://www.bambora.com
Text Domain: Bambora
 */

add_action('plugins_loaded', 'add_wc_bambora_gateway', 0);

function add_wc_bambora_gateway()
{
    if (!class_exists( 'WC_Payment_Gateway' ) ) { return; }

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
        const MODULE_VERSION = '1.4.6';
        const PSP_REFERENCE = 'Transaction ID';

        private $apiKey;

        #region Construce, setup and config

        public function __construct()
        {
            $this->id = 'bambora';
            $this->method_title = 'Bambora';
            $this->icon = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__ )) . '/bambora-logo.png';
            $this->has_fields = false;

            $this->supports = array('products', 'refunds');

            // Load the form fields.
            $this->initFormFields();

            // Load the settings.
            $this->init_settings();

            // Define user set variables
            $this->enabled = array_key_exists("enabled", $this->settings) ? $this->settings["enabled"] : 'yes';
            $this->title = array_key_exists("title", $this->settings) ? $this->settings["title"] : 'Bambora Checkout';
            $this->description = array_key_exists("description", $this->settings) ? $this->settings["description"] : 'Pay using Bambora Checkout';
            $this->merchant = array_key_exists("merchant", $this->settings) ? $this->settings["merchant"] : '';
            $this->accesstoken = array_key_exists("accesstoken", $this->settings) ? $this->settings["accesstoken"] : '';
            $this->secrettoken = array_key_exists("secrettoken", $this->settings) ? $this->settings["secrettoken"] : '';
			$this->paymentwindowid = array_key_exists("paymentwindowid", $this->settings) ? $this->settings["paymentwindowid"] : 1;
			$this->windowstate = array_key_exists("windowstate", $this->settings) ? $this->settings["windowstate"] : 2;
			$this->instantcapture = array_key_exists("instantcapture", $this->settings) ? $this->settings["instantcapture"] :  'no';
            $this->immediateredirecttoaccept = array_key_exists("immediateredirecttoaccept", $this->settings) ? $this->settings["immediateredirecttoaccept"] :  'no';
            $this->addsurchargetoshipment = array_key_exists("addsurchargetoshipment", $this->settings) ? $this->settings["addsurchargetoshipment"] :  'no';
            $this->md5key = array_key_exists("md5key", $this->settings) ? $this->settings["md5key"] : '';


            // Set description for checkout page
            $this->setBamboraDescriptionForCheckout();

            // Actions
            add_action('init', array(&$this, 'checkCallback'));
            add_action('valid-bambora-callback', array(&$this, 'successfulRequest'));
            add_action('add_meta_boxes', array(&$this, 'bambora_meta_boxes' ), 10, 0);
            add_action('woocommerce_api_' . strtolower(get_class()), array($this, 'checkCallback'));
            add_action('wp_before_admin_bar_render', array($this, 'bamboraAction'));
            add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('woocommerce_receipt_bambora', array($this, 'receiptPage'));


            //Register styles
            add_action('admin_enqueue_scripts', array($this, 'enqueueWcBamboraAdminStylesAndScripts'));
            add_action('wp_enqueue_scripts', array($this, 'enqueueWcBamboraFrontStyles'));
        }



        public function enqueueWcBamboraAdminStylesAndScripts()
        {
            wp_register_style('bambora_admin_style', plugins_url('woocommerce-gateway-bambora/style/bamboraAdmin.css'));
            wp_enqueue_style('bambora_admin_style');

            //Fix for load of Jquery time
            wp_enqueue_script('jquery');

            wp_enqueue_script('bambora_script',  WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__ )) . '/js/bambora.js');
        }

        public function enqueueWcBamboraFrontStyles()
        {
            wp_enqueue_style('bambora_front_style',  plugins_url('woocommerce-gateway-bambora/style/bamboraFront.css'));
            wp_enqueue_style('bambora_front_style');
        }

        /**
         * Initialise Gateway Settings Form Fields
         */
        public function initFormFields()
        {
            $this->form_fields = array(
                'enabled' => array(
                                'title' => 'Enable/Disable',
                                'type' => 'checkbox',
                                'label' => 'Enable Bambora Checkout',
                                'default' => 'yes'
                            ),
                'title' => array(
                                'title' => 'Title',
                                'type' => 'text',
                                'description' => 'This controls the title which the user sees during checkout',
                                'default' => 'Bambora Checkout'
                            ),
                'description' => array(
                                'title' => 'Description',
                                'type' => 'textarea',
                                'description' => 'This controls the description which the user sees during checkout.',
                                'default' => "Pay using Bambora Checkout"
                            ),
                'merchant' => array(
                                'title' => 'Merchant number',
                                'type' => 'text',
                                'description' => 'Get your Merchant number from the <a href="https://merchant.bambora.com/" target="_blank">Bambora Administration</a> via Settings -> Merchant numbers. If you haven\'t got a Merchant number, please contact <a href="http://www.bambora.com/da/dk/bamboraone/" target="_blank">Bambora</a> to get one. <br/><b>Note:</b> This field is mandatory to enable payments.',
                                'default' => ''
                            ),
                'accesstoken' => array(
                                'title' => 'Access token',
                                'type' => 'text',
                                'description' => 'Get your Access token from the <a href="https://merchant.bambora.com/" target="_blank">Bambora Administration</a> via Settings -> API users. Copy the Access token from the API user into this field.<br/><b>Note:</b> This field is mandatory in order to enable payments.',
                                'default' => ''
                            ),
                'secrettoken' => array(
                                'title' => 'Secret token',
                                'type' => 'password',
                                'description' => 'Get your Secret token from the <a href="https://merchant.bambora.com/" target="_blank">Bambora Administration</a> via Settings -> API users.<br/>The secret token is only displayed once when an API user is created! Please save this token in a safe place as Bambora will not be able to recover it.<br/><b>Note: </b> This field is mandatory in order to enable payments.',
                                'default' => ''
                            ),
                'md5key' => array(
                                'title' => 'MD5 Key',
                                'type' => 'text',
                                'description' => 'We recommend using MD5 to secure the data sent between your system and Bambora.<br/>If you have generated a MD5 key in the <a href="https://merchant.bambora.com/" target="_blank">Bambora Administration</a> via Settings -> Edit merchant, you have to enter the MD5 key here as well. <br/><b>Note:</b> The keys must be identical in the two systems.',
                                'default' => ''
                            ),
                'paymentwindowid' => array(
                                'title' => 'Payment Window ID',
                                'type' => 'text',
                                'description' => 'Choose which payment window to use. You can create multiple payment windows in the <a href="https://merchant.bambora.com/" target="_blank">Bambora Administration</a> via Settings -> Payment windows.<br/>This is useful if you want to show different layouts, payment types or transaction fees for various customers.',
                                'default' => '1'
                            ),
                'windowstate' => array(
                                'title' => 'Display window as',
                                'type' => 'select',
                                'description' => 'Please select if you want the Payment window shown as an overlay or as full screen.',
                                'options' => array(2 => 'Overlay',1 => 'Full screen'),
                                'label' => 'How to open the Bambora Checkout',
                                'default' => 2
                            ),

                'instantcapture' => array(
                                'title' => 'Instant capture',
                                'type' => 'checkbox',
                                'description' => 'Enable this to capture the payment immediately.<br/>You should only use this setting, if your customer receives the goods immediately e.g. via downloads or services.',
                                'label' => 'Enable instant capture',
                                'default' => 'no'
                            ),
                'immediateredirecttoaccept' => array(
                                'title' => 'Immediate redirect to order confirmation page',
                                'type' => 'checkbox',
                                'description' => 'Please select if you to go directly to the order confirmation page when payment is completed.',
                                'label' => 'Enable Immediate redirect',
                                'default' =>  'no'
                            ),
                'addsurchargetoshipment' => array(
                                'title' => 'Add surcharge fee to shipping amount',
                                'type' => 'checkbox',
                                'description' => 'Please select if you to add the surcharge fee to the shipment amount',
                                'label' => 'Enable Surcharge fee',
                                'default' =>  'no'
                            ),
                );
        }

        /**
         * Admin Panel Options
         * - Options for bits like 'title' and availability on a country-by-country basis
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
            {
                echo wpautop(wptexturize($this->description));
            }
        }

        #endregion

        #region Frontend

        /**
         * Get Bambora payment window
         *
         * @param int $order_id
         * @return string
         * */
        public function getCheckoutPaymentWindow($orderId)
        {
            $apiKey = $this->getApiKey();
            $api = new BamboraApi($apiKey);

            $checkoutRequest = $this->createCheckoutRequest($orderId);
            $checkoutResponse = $api->getCheckoutResponse($checkoutRequest);

            if (!isset($checkoutResponse) || !$checkoutResponse['meta']['result'])
            {
                $errorMessage = isset($checkoutResponse) ? $checkoutResponse['meta']['message']["enduser"] : "No connection to Bambora";
                $message = __('Could not retrive the payment window. Reason:', 'woocommerce-gateway-bambora') .' '.$errorMessage;
                return $this->messageToHtml("error", $message);
            }

            $checkoutPaymentWindowJs = $api->getcheckoutpaymentwindowjs();
            $bamboraCheckouturl = $checkoutResponse['url'];
            $bamboraCheckoutPaymentHtml = BamboraHelper::createBamboraCheckoutPaymentHtml($checkoutPaymentWindowJs, $this->windowstate, $bamboraCheckouturl, $checkoutRequest->url->decline);

            return $bamboraCheckoutPaymentHtml;
        }

        private function createCheckoutRequest($orderId)
        {
            $order = new WC_Order($orderId);
            $minorUnits = bamboraCurrency::getCurrencyMinorunits(get_woocommerce_currency());

            $bamboraCustommer = $this->createBamboraCustommer($order);
            $bamboraOrder = $this->createBamboraOrder($order, $minorUnits);
            $bamboraUrl = $this->createBamboraUrl($order);

            $request = new BamboraCheckoutRequest();
            $request->customer = $bamboraCustommer;
            $request->instantcaptureamount = $this->instantcapture == 'yes' ? $bamboraOrder -> total : 0;
            $request->language = str_replace("_","-", get_locale());
            $request->order = $bamboraOrder;
            $request->url = $bamboraUrl;
            $request->paymentwindowid = $this->paymentwindowid;

            return $request;
        }

        /**
         * Create Bambora customer
         *
         * @param WC_Order $order
         * @return BamboraCustomer
         * */
        private function createBamboraCustommer($order)
        {
            $bamboraCustommer = new BamboraCustomer();
            $bamboraCustommer->email = $order->billing_email;
            $bamboraCustommer->phonenumber = $order->billing_phone;
            $bamboraCustommer->phonenumbercountrycode = $order->billing_country;

            return $bamboraCustommer;
        }

        /**
         * Create Bambora order
         *
         * @param WC_Order $order
         * @param $minorUnits
         * @return BamboraOrder
         * */
        private function createBamboraOrder($order, $minorUnits)
        {
            $bamboraOrder = new BamboraOrder();
            $bamboraOrder->billingaddress = $this->createBamboraAddress($order);
            $bamboraOrder->currency = get_woocommerce_currency();
            $bamboraOrder->lines = $this->createBamboraOrderlines($order,$minorUnits);

            $ordernumber = str_replace(_x( '#', 'hash before order number', 'woocommerce'), "", $order->get_order_number());
            $bamboraOrder->ordernumber = ((int)$ordernumber);
            $bamboraOrder->shippingaddress = $this->createBamboraAddress($order);
            $bamboraOrder->total = BamboraCurrency::convertPriceToMinorUnits($order->order_total, $minorUnits);
            $bamboraOrder->vatamount = BamboraCurrency::convertPriceToMinorUnits($order->get_total_tax(), $minorUnits);

            return $bamboraOrder;
        }

        /**
         * Create Bambora address
         *
         * @param WC_Order $order
         * @return BamboraAddress
         * */
        private function createBamboraAddress($order)
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

        private function createBamboraUrl($order)
        {
            $bamboraUrl = new BamboraUrl();
            $bamboraUrl->accept = $this->fixUrl($this->get_return_url($order));
            $bamboraUrl->decline = $this->fixUrl($order->get_cancel_order_url());

            $bamboraUrl->callbacks = array();
            $callback = new BamboraCallback();
            $callback->url = $this->fixUrl(add_query_arg('wooorderid', $order->id, add_query_arg('wc-api', 'WC_Gateway_Bambora', $this->get_return_url($order))));

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
        private function createBamboraOrderlines($order,$minorUnits)
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

                $shippingOrderline->vat = $shipping_total > 0 ? round( $shipping_tax/ $shipping_total * 100) : 0;
                $bamboraOrderlines[] = $shippingOrderline;
            }

            return $bamboraOrderlines;
        }

        /**
         * Fix Url
         *
         * @param string $url
         * @return string
         * */
        function fixUrl($url)
        {
            $url = str_replace('&#038;', '&amp;', $url);
            $url = str_replace('&amp;', '&', $url);

            return $url;
        }

        /**
         * Set the WC Payment Gateway description for the checkout page
         */
        function setBamboraDescriptionForCheckout()
        {
            global $woocommerce;

            $cart = $woocommerce->cart;
            if(!$cart)
            {
                return;
            }

            $cartTotal = $cart->total;
            if($cartTotal && $cartTotal > 0)
            {
                $currency = get_woocommerce_currency();
                if(!$currency)
                {
                    return;
                }
                $minorUnits = BamboraCurrency::getCurrencyMinorunits($currency);
                $amount = BamboraCurrency::convertPriceToMinorUnits($cartTotal,$minorUnits);
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

        /**
         * Receipt page
         * @param WC_Order $order
         **/
        public function receiptPage( $order )
        {
            echo $this->getCheckoutPaymentWindow($order);
        }

        /**
         * Check for Bambora IPN Response
         **/
        public function checkCallback()
        {
            $_GET = stripslashes_deep($_GET);
            do_action("valid-bambora-callback", $_GET);
        }

        /**
         * Successful Payment!
         * @param array $params
         **/
        public function successfulRequest($params)
        {
            $message = "";
            $responseCode = 400;
            $order = null;
            $transaction = null;
            if($this->validateCallback($params, $message, $order, $transaction))
            {
                $message = $this->processCallback($params["wooorderid"], $order, $transaction, $responseCode);
            }
            else
            {
                $message = empty($message) ? __('Unknown error', 'woocommerce-gateway-bambora') : $message;
                $wooOrderId = array_key_exists("wooorderid", $params) ? $params["wooorderid"] : "Unknown";
                $errorMessage = "WooCommerce-OrderId: {$wooOrderId} ".__('Callback failed! Reason:', 'woocommerce-gateway-bambora') . ' ' . $message;
                if(isset($order))
                {
                    $order->add_order_note($errorMessage);
                }

                error_log($errorMessage);
            }

            $header = "X-EPay-System: ". BamboraHelper::getModuleHeaderInfo();
            header($header, true, $responseCode);
            die($message);
        }

        private function validateCallback($params, &$message, &$order, &$transaction)
        {
            // Check exists transactionid
            if(!isset($params) || !isset($params["txnid"]))
            {
                $message = isset($posted) ? "No GET(txnid) was supplied to the system!" : "Response is null";
                return false;
            }

            // Check exists orderid
            if (!isset($params["orderid"]))
            {
                $message = "No GET(orderid) was supplied to the system!";
                return false;
            }

            //Validate MD5
            $var = "";
            if(strlen($this->md5key) > 0)
            {
                foreach($params as $key => $value)
                {
                    if($key != "hash")
                    {
                        $var .= $value;
                    }
                }

                $genstamp = md5($var . $this->md5key);
                if(!hash_equals($genstamp, $params["hash"]))
                {
                    $message = "Hash validation failed - Please check your MD5 key";
                    return false;
                }
            }

            //Validate bambora transaction
            $apiKey = $this->getApiKey();
            $api = new BamboraApi($apiKey);
            $getTransaction = $api->getTransaction($params["txnid"]);
            if (!isset($getTransaction) || !$getTransaction["meta"]["result"])
            {
                $message = isset($rest_result) ? $rest_result["meta"]["message"]["enduser"] : "No connection to Bambora";
                return false;
            }
            $transaction = $getTransaction["transaction"];

            //Validate woocommerce order
            $order = wc_get_order($params["wooorderid"]);
            if(!isset($order))
            {
                $message = "Could not find order with wooorderid {$params["wooorderid"]}";
                return false;
            }

            return true;
        }

        private function processCallback($wooOrderId, $order, $transaction, &$responseCode)
        {
            $message = "";
            try
            {
                $pspReference = get_post_meta($wooOrderId, $this::PSP_REFERENCE);
                if(empty($pspReference))
                {
                    // Payment completed

                    $minorUnits = $transaction["currency"]["minorunits"];
                    $feeAmountInMinorUnits = $transaction["total"]["feeamount"];

                    if($feeAmountInMinorUnits > 0 && $this->settings["addsurchargetoshipment"] == "yes")
                    {
                        $feeAmount = BamboraCurrency::convertPriceFromMinorUnits($feeAmountInMinorUnits, $minorUnits);

                        $order_fee              = new stdClass();
                        $order_fee->id          = 'bambora_surcharge_fee';
                        $order_fee->name        = __('Surcharge Fee', 'woocommerce-gateway-bambora');
                        $order_fee->amount      = $feeAmount;
                        $order_fee->taxable     = false;
                        $order_fee->tax         = 0;
                        $order_fee->tax_data    = array();

                        $order->add_fee($order_fee);
                        $orderTotal = $order->order_total + $feeAmount;
                        $order->set_total($orderTotal);
                    }

                    $order->payment_complete();

                    $transactionId = $transaction["id"];
                    $cardNumber = $transaction['information']['primaryaccountnumbers'][0]['number'];
                    $cardType = $transaction['information']['paymenttypes'][0]['displayname'];

                    update_post_meta($wooOrderId, $this::PSP_REFERENCE, $transactionId);
                    update_post_meta($wooOrderId, 'Card no', $cardNumber);
                    update_post_meta($wooOrderId, 'Card type', $cardType);

                    $order->add_order_note(__('Callback completed', 'woocommerce-gateway-bambora'));
                    $message = "Order created";
                    $responseCode = 200;
                }
                else
                {
                    $message = "Order was already Created";
                    $responseCode = 200;
                }
            }
            catch (Exception $e)
            {
                $responseCode = 500;
                $message = "Action Failed: " .$e->getMessage();
            }

            return $message;
        }


        #endregion

        #region back office

        public function process_refund($order_id, $amount = null, $reason = '')
        {
            $order = new WC_Order($order_id);
            $refunds = $order->get_refunds();
            $currency = $order->order_currency;
            $minorUnits = BamboraCurrency::getCurrencyMinorunits($currency);
            $amount = BamboraCurrency::convertPriceToMinorUnits($amount,$minorUnits);

            /** @var BamboraOrderLine[] */
            $bamboraRefundLines = array();
            if(!$this->createBamboraRefundLines($refunds[0], $bamboraRefundLines, $minorUnits))
            {
                echo $this->messageToHtml('error', __('Could not create refund invoice lines', 'woocommerce-gateway-bambora'));
                return false;
            }

            $apiKey = $this->getApiKey();
            $api = new BamboraApi($apiKey);
            $transactionId = get_post_meta($order->id, $this::PSP_REFERENCE, true);
            $credit = $api->credit($transactionId, $amount, $currency, $bamboraRefundLines );

            if(!isset($credit) || !$credit["meta"]["result"])
            {
                $message = isset($credit) ? $credit["meta"]["message"]["merchant"] : __('No connection to Bambora', 'woocommerce-gateway-bambora');
                echo $this->messageToHtml('error', $message);
                return false;
            }


            return true;
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
        private function createBamboraRefundLines($refund,&$bamboraRefundLines,$minorUnits,$reason='')
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
                $line->quantity = abs($item["qty"]);
                $line->text = $item["name"];
                $line->totalpriceinclvat = BamboraCurrency::convertPriceToMinorUnits(abs($line_total), $minorUnits, false);
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
                $shippingOrderLine->totalpriceinclvat = abs(BamboraCurrency::convertPriceToMinorUnits($shipping_total + $shipping_tax, $minorUnits));
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
                $additionalRefundOrderLine->totalpriceinclvat = abs(BamboraCurrency::convertPriceToMinorUnits($total-$items_total, $minorUnits));
                $additionalRefundOrderLine->vat = 0;
                $bamboraRefundLines[] = $additionalRefundOrderLine;
            }

            return true;
        }

        public function bambora_meta_boxes()
        {
            global $post;
            $orderId = $post->ID;
            $paymentMethod = get_post_meta( $orderId, '_payment_method', true );
            if($this->id === $paymentMethod)
            {
                add_meta_box(
                    'bambora-payment-actions',
                    __('Bambora Payment Solutions', 'woocommerce-gateway-bambora'),
                    array(&$this, 'bamboraMetaBoxPayment'),
                    'shop_order',
                    'side',
                    'high'
                );
            }
        }

        /**
         * Generate the Bambora payment meta box and echos the HTML
         */
        public function bamboraMetaBoxPayment()
        {
            global $post;
            $orderId = $post->ID;
            $order = new WC_Order($orderId);

            $transactionId = get_post_meta($order->id, $this::PSP_REFERENCE, true);
            if(strlen($transactionId) > 0)
            {
                $html = "";
                try
                {
                    $apiKey = $this->getApiKey();
                    $api = new BamboraApi($apiKey);

                    $getTransaction = $api->getTransaction($transactionId);

                    if(!isset($getTransaction) || !$getTransaction["meta"]["result"])
                    {
                        $errorMessage = isset($getTransaction) ? $getTransaction["meta"]["message"]["merchant"] : "No connection to Bambora";
                        echo $this->messageToHtml("error", $errorMessage);
                        return null;
                    }

                    $transactionInfo = $getTransaction["transaction"];
                    $minorUnits = $transactionInfo["currency"]["minorunits"];
                    $totalAuthorized = BamboraCurrency::convertPriceFromMinorUnits($transactionInfo["total"]["authorized"], $minorUnits);
                    $totalCaptured =  BamboraCurrency::convertPriceFromMinorUnits($transactionInfo["total"]["captured"], $minorUnits);
                    $availableForCapture = BamboraCurrency::convertPriceFromMinorUnits($transactionInfo["available"]["capture"], $minorUnits);

                    $totalCredited =  BamboraCurrency::convertPriceFromMinorUnits($transactionInfo["total"]["credited"], $minorUnits);
                    $canDelete = $transactionInfo["candelete"];
                    $curencyCode = $transactionInfo["currency"]["code"];

                    $html = '<div class="bambora_info">';
                    $html .= '<div class="bambora_transactionid">';
                    $html .= '<p>'.__('Transaction ID', 'woocommerce-gateway-bambora').'</p>';
                    $html .= '<p>'.$transactionInfo["id"].'</p>';
                    $html .= '</div>';
                    $html .= '<br/>';

                    $html .= '<div class="bambora_info_overview">';
                    $html .= '<p>'.__('Authorized:', 'woocommerce-gateway-bambora').'</p>';
                    $html .= '<p>'.$this->formatNumber($totalAuthorized, $minorUnits). ' ' . $curencyCode.'</p>';
                    $html .= '</div>';

                    $html .= '<div class="bambora_info_overview">';
                    $html .= '<p>'.__('Captured:', 'woocommerce-gateway-bambora').'</p>';
                    $html .= '<p>'.$this->formatNumber($totalCaptured, $minorUnits). ' ' . $curencyCode.'</p>';
                    $html .= '</div>';

                    $html .= '<div class="bambora_info_overview">';
                    $html .= '<p>'.__('Refunded:', 'woocommerce-gateway-bambora').'</p>';
                    $html .= '<p>'.$this->formatNumber($totalCredited, $minorUnits). ' ' . $curencyCode.'</p>';
                    $html .= '</div>';

                    $html .= '</div>';
                    $html .= '<br/>';

                    if($availableForCapture > 0 || $canDelete == true)
                    {
                        $html .= '<div class="bambora_action_container">';

                        if($availableForCapture > 0)
                        {
                            $html .= '<input type="hidden" id="bambora_currency" name="bambora_currency" value="'.$curencyCode.'">';
                            $html .= '<input type="hidden" id="bambora_capture_message" name="bambora_capture_message" value="' . __('Are you sure you want to capture the payment?', 'woocommerce-gateway-bambora') . '" />';
                            $html .= '<div class="bambora_action">';
                            $html .= '<p>'.$curencyCode.'</p>';
                            $html .= '<input type="text" value="' . $this->formatNumber($availableForCapture, $minorUnits). '"id="bambora_capture_amount" class="bambora_amount" name="bambora_amount" />';
                            $html .= '<input id="bambora_capture_submit" class="button capture" name="bambora_capture" type="submit" value="' .__('Capture').'" />';
                            $html .= '</div>';
                            $html .= '<br/>';
                        }
                        if ($canDelete == true)
                        {
                            $html .= '<input type="hidden" id="bambora_delete_message" name="bambora_delete_message" value="' . __('Are you sure you want to delete the payment?', 'woocommerce-gateway-bambora') . '" />';
                            $html .= '<div class="bambora_action">';
                            $html .= '<input id="bambora_delete_submit" class="button delete" name="bambora_delete" type="submit" value="' .__('Delete').'" />';
                            $html .= '</div>';
                        }
                        $html .= '</div>';
                        $warningMessage = __('The amount you entered was in the wrong format.', 'woocommerce-gateway-bambora');

                        $html .= '<div id="bambora-format-error" class="bambora bambora_error"><strong>'.__('Warning').' </strong>'.$warningMessage.'<br/><strong>'.__('Correct format is: 1234.56', 'woocommerce-gateway-bambora').'</strong></div>';
                        $html .= '<br />';
                    }



                    $getTransactionOperation = $api->getTransactionOperations($transactionId);

                    if(!isset($getTransactionOperation) && !$getTransactionOperation["meta"]["result"])
                    {
                        $errorMessage = isset($getTransactionOperation) ? $getTransactionOperation["meta"]["message"]["merchant"] : "No connection to Bambora";
                        return $this->messageToHtml("error", $errorMessage);
                    }

                    $transactionOperations = $getTransactionOperation["transactionoperations"];

                    $html .= $this->buildTransactionLogtable($transactionOperations, $minorUnits);
                    $html .= '<br/>';


                    echo $html;
                }
                catch(Exception $e)
                {
                    echo $this->messageToHtml("error", $e->getMessage());
                    return null;
                }
            }
            else
            {
                _e("No transaction was found","woocommerce-gateway-bambora");
            }
        }

        /**
         * Build transaction log table HTML
         *
         * @param array $operations
         * @param int $minorUnits
         * @return string
         * */
        private function buildTransactionLogtable($operations, $minorUnits)
        {
            $html = '<h4>'.__('TRANSACTION HISTORY', 'woocommerce-gateway-bambora').'</h4>';
            $html .= '<table class="bambora-table">';
            $html .= $this->buildTransactionLogRows($operations, $minorUnits);
            $html .= '</table>';

            return $html;
        }

        private function formatDate($rawDate)
        {
            $date = str_replace("T", " ",substr($rawDate, 0, 19));
            $dateStamp = strtotime($date);
            $dateFormat = wc_date_format();
            $formatedDate = date($dateFormat, $dateStamp);

            return $formatedDate;
        }

        /**
         * Build transaction log row HTML
         * @param array $operation
         * @param int $minorUnits
         * @return string
         * */
        private function buildTransactionLogRows($operations, $minorUnits)
        {
            $html = "";
            foreach($operations as $operation)
            {
                $html .= '<tr class="bambora_transaction_row_header">';
                $html .= '<td>' .$this->formatDate($operation["createddate"]).'</td>';

                if(key_exists("ecis",$operation) && is_array($operation["ecis"]) && count($operation["ecis"])> 0)
                {
                    $html .= '<td>ECI: ' . $operation["ecis"][0]["value"] .'</td>';
                }
                else
                {
                    $html .= '<td>ECI: -</td>';
                }

                $html .= '</tr>';

                $html .= '<tr class="bambora_transaction">';
                $html .= '<td>'.$this->convertAction($operation["action"]).'</td>';

                $amount = BamboraCurrency::convertPriceFromMinorUnits($operation["amount"], $minorUnits);
                if($amount > 0)
                {
                    $html .= '<td>'. $this->formatNumber($amount, $minorUnits) . ' '.$operation["currency"]["code"] ."</td>";
                }
                else
                {
                    $html .= '<td>-</td>';
                }

                $html .= '</tr>';

                if(key_exists("transactionoperations", $operation) && count($operation["transactionoperations"]) > 0)
                {
                    $html .= $this->buildTransactionLogRows($operation["transactionoperations"], $minorUnits);
                }
            }

            return $html;
        }

        public function bamboraAction()
        {
            if(isset($_GET["bambora_action"]))
            {
                $order = new WC_Order($_GET['post']);
                $transactionId = get_post_meta($order->id, $this::PSP_REFERENCE, true);
                $currency = $_GET["currency"];
                $minorUnits = BamboraCurrency::getCurrencyMinorunits($currency);
                $api = new BamboraApi(BamboraHelper::generateApiKey($this->merchant, $this->accesstoken, $this->secrettoken));
                try
                {
                    switch($_GET["bambora_action"])
                    {
                        case 'capture':
                            $amount = str_replace(",", ".", $_GET["amount"]);
                            $amount = BamboraCurrency::convertPriceToMinorUnits($amount,$minorUnits);
                            $capture = $api->capture($transactionId, $amount,$currency);

                            if(!isset($capture) || $capture["meta"]["result"] == false)
                            {
                                $message = isset($capture) ? $capture["meta"]["message"]["merchant"] : __('No connection to Bambora', 'woocommerce-gateway-bambora');
                                echo $this->messageToHtml('error', $message);

                            }
                            else
                            {
                                echo $this->messageToHtml('success', __('Payment successfully','woocommerce-gateway-bambora').' '.__("Captured","woocommerce-gateway-bambora"));
                            }

                            break;

                        case 'delete':
                            $delete = $api->delete($transactionId);
                            if(!isset($deleted) || $delete["meta"]["result"] == false)
                            {
                                $message = isset($capture) ? $capture["meta"]["message"]["merchant"] : __('No connection to Bambora', 'woocommerce-gateway-bambora');
                                echo $this->messageToHtml('error', $message);
                            }
                            else
                            {
                                echo $this->messageToHtml('success', __("Payment successfully", "woocommerce-gateway-bambora").' '.__("Deleted","woocommerce-gateway-bambora"));
                            }

                            break;
                    }
                }
                catch(Exception $e)
                {
                    echo $this->messageToHtml("error", $e->getMessage());
                }

                global $post;
                $url = admin_url('post.php?post=' . $post->ID . '&action=edit');
                wp_redirect($url);
            }
        }

        #endregion

        #region General

        private function formatNumber($number,$decimals)
        {
            return number_format($number,$decimals,wc_get_price_decimal_separator(), wc_get_price_thousand_separator());
        }

        /**
         * Convert action
         * @param string $action
         * @return string
         * */
        private function convertAction($action)
        {
            if($action == "Authorize")
            {
                return __('Authorized', 'woocommerce-gateway-bambora');
            }
            else if($action == "Capture")
            {
                return __('Captured', 'woocommerce-gateway-bambora');
            }
            else if($action == "Credit")
            {
                return __('Refunded', 'woocommerce-gateway-bambora');
            }
            else if($action == "Delete")
            {
                return __('Deleted', 'woocommerce-gateway-bambora');
            }
            else
            {
                return $action;
            }
        }

        /**
         * Get error message from parsed json response
         * @param string[] $transMeta
         * @param string[] $operationsMeta
         * @return string
         * */
        private function getErrormessageUsingJSon($transMeta, $operationsMeta){
            $errMessage = 'Could not lookup the transaction. Reason: ';

            if($transMeta["result"] == false)
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
        private function messageToHtml($type, $message)
        {
            $html = '<div class="bambora_message bambora_'.$type.'">
                        <strong>'.$this->messageTypeToUpper($type).'! </strong>'
                        .$message.'</div>';

            return $html;
        }

        private function messageTypeToUpper($type)
        {
            if(!isset($type))
            {
                return "";
            }
            $firstLetter = substr($type,0,1);
            $firstLetterToUpper = strtoupper($firstLetter);
            $result = str_replace($firstLetter, $firstLetterToUpper, $type);

            return $result;

        }

        private function getApiKey()
        {
            if(empty($this->apiKey))
            {
                $this->apiKey = BamboraHelper::generateApiKey($this->merchant, $this->accesstoken, $this->secrettoken);
            }

            return $this->apiKey;
        }

        #endregion
    }

    /**
     * Add the Bambora gateway to WooCommerce
     * @param array $methods
     **/
    function add_bambora_gateway($methods)
    {
        $methods[] = 'WC_Gateway_Bambora';
        return $methods;
    }

    /**
     * Initialise the Bambora gateway
     */
    function init_bambora_gateway()
    {
        $plugin_dir = basename(dirname(__FILE__ ));
        load_plugin_textdomain('woocommerce-gateway-bambora', false, $plugin_dir . '/languages/');
    }

    add_filter('woocommerce_payment_gateways', 'add_bambora_gateway');
    add_action('plugins_loaded', 'init_bambora_gateway');

    /**
     * Get a new WC_Bambora_Gateway instance
     * @return WC_Gateway_Bambora
     */
    function WC_Gateway_Bambora()
    {
        return new WC_Gateway_Bambora();
    }

    if (is_admin())
    {
        add_action('load-post.php', 'WC_Gateway_Bambora');
    }
}