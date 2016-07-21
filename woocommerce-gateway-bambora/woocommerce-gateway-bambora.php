<?php
/*
Plugin Name: WooCommerce Bambora Checkout Payment Gateway
Plugin URI: http://www.bambora.com
Description: A payment gateway for Bambora Checkout (http://www.bambora.com/sv/se/betalningslosningar/e-handel/produkter/bambora-checkout/).
Version: 1.2.7
Author: Bambora
Author URI: http://www.bambora.com
Text Domain: Bambora
 */

add_action('plugins_loaded', 'add_wc_bambora_gateway', 0);


function add_wc_bambora_gateway() 
{
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) { return; }
	
	define('bambora_LIB', dirname(__FILE__) . '/lib/');


    /**
     * Add Bambora Stylesheet and javascript to plugin
     */
    wp_enqueue_style('bambora_style',  WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__ )) . '/style/bambora.css');
	//Fix for load of Jquery time
	wp_enqueue_script('jquery');
	
    wp_enqueue_script('bambora_script',  WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__ )) . '/js/bambora.js');

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
			global $woocommerce;
			
			$this->id = 'bambora';
			$this->method_title = 'Bambora';
			$this->icon = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__ )) . '/Bambora_1_MINI_RGB-slim.png';
			$this->has_fields = false;

			$this->supports = array('products');
			
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
			
			// Actions
			add_action('init', array(& $this, 'check_callback'));
			add_action('valid-bambora-callback', array(&$this, 'successful_request'));
            add_action('add_meta_boxes', array( &$this, 'bambora_meta_boxes' ), 10, 0);
            add_action('woocommerce_api_' . strtolower(get_class()), array($this, 'check_callback'));
            add_action('wp_before_admin_bar_render', array($this, 'bambora_action', ));
            add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options', ));			
			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));			
			add_action('woocommerce_receipt_bambora', array($this, 'receipt_page'));
            //add_action('woocommerce_thankyou', array($this, 'bambora_accepted_payment'));
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
								'default' => ''
							),
                'accesstoken' => array(
								'title' => __( 'Access token', 'woocommerce-gateway-bambora'), 
								'type' => 'text',  
								'default' => ''
							),
                'secrettoken' => array(
								'title' => __( 'Secret token', 'woocommerce-gateway-bambora'), 
								'type' => 'password',  
								'default' => ''
							),
                'md5key' => array(
					            'title' => __( 'MD5 Key', 'woocommerce-gateway-bambora'), 
					            'type' => 'text',
                                'description' => __( 'This needs to be generated in the merchant administration <a href="https://merchant.bambora.com/" target="_blank">https://merchant.bambora.com/</a> . Settings->Merchant numbers -> Edit.', 'woocommerce'),
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
								'options' => array(2 => 'Overlay',1 => 'Full screen'),
								'label' => __( 'How to open the Bambora Checkout', 'woocommerce-gateway-bambora'), 
								'default' => 2
							),

				'instantcapture' => array(
								'title' => __( 'Instant capture', 'woocommerce-gateway-bambora'), 
								'type' => 'checkbox', 
								'label' => __( 'Enable instant capture', 'woocommerce-gateway-bambora'), 
								'default' => 'no'
							), 
                'immediateredirecttoaccept' => array(
								'title' => __( 'Immediate redirect to order confirmation page', 'woocommerce-gateway-bambora'), 
								'type' => 'checkbox', 
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
	    
		function fix_url($url)
		{
			$url = str_replace('&#038;', '&amp;', $url);
			$url = str_replace('&amp;', '&', $url);
			
			return $url;
		}
        
        public function generate_bambora_paymentwindow($order_id)
        {
            global $woocommerce;
            
            $order = new WC_Order($order_id);
            $minorUnits = bamboraCurrency::getCurrencyMinorunits(get_woocommerce_currency());

            $bamboraCustommer = $this -> create_bambora_custommer($order);
            $bamboraOrder = $this ->create_bambora_order($order,$minorUnits);
            $bamboraUrl = $this ->create_bambora_url($order,$minorUnits);
            
            $request = new BamboraCheckoutRequest();
            $request -> capturemulti = true; //TODO make config
            $request -> customer = $bamboraCustommer;
            $request -> instantcaptureamount = $this->instantcapture == 'yes' ? $bamboraOrder -> total : 0;
            $request -> language = str_replace("_","-",get_locale());
            $request -> order = $bamboraOrder;
            $request -> url = $bamboraUrl;
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

        private function create_bambora_custommer($order)
        {
            $bamboraCustommer = new BamboraCustomer();
            $bamboraCustommer ->email = $order->billing_email;
            $bamboraCustommer ->phonenumber = $order ->billing_phone;
            $bamboraCustommer ->phonenumbercountrycode = "";
            return $bamboraCustommer;
        }
        

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

        private function create_bambora_url($order,$minorUnits)
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
        
        private function create_bambora_orderlines($order,$minorUnits)
        {
            $bamboraOrderlines = array();
            
            $wc_tax = new WC_Tax();
            
            $items = $order->get_items();
            $lineNumber = 1;
            foreach($items as $item)
            {      
                $line = new BamboraOrderLine();
                $line->description = "";
                $line->id = $item["product_id"];
                $line->linenumber = $lineNumber;
                $line->quantity = $item["qty"];
                $line->text = $item["name"];
                $line->totalprice = BamboraCurrency::convertPriceToMinorUnits($item["line_total"],$minorUnits);
                $line->totalpriceinclvat = BamboraCurrency::convertPriceToMinorUnits($item["line_total"] + $item["line_tax"],$minorUnits);
                $line->totalpricevatamount =  BamboraCurrency::convertPriceToMinorUnits($item["line_tax"],$minorUnits);
                $line->unit = "";
                $line->unitprice = BamboraCurrency::convertPriceToMinorUnits($item["line_total"] / $item["qty"],$minorUnits);
                $line->unitpriceinclvat = BamboraCurrency::convertPriceToMinorUnits(($item["line_total"] + $item["line_tax"]) / $item["qty"],$minorUnits);
                $line->unitpricevatamount = BamboraCurrency::convertPriceToMinorUnits($item["line_tax"] / $item["qty"],$minorUnits);
                //$line->vat = round((BamboraCurrency::convertPriceToMinorUnits($item["line_subtotal_tax"], $minorUnits) / BamboraCurrency::convertPriceToMinorUnits($item["line_subtotal"], $minorUnits)) * 100, 2);    
                $product = $order->get_product_from_item($item);
                $item_tax_class = $product->get_tax_class();
                $item_tax_rate = array_shift($wc_tax->get_rates($item_tax_class));
                if(isset($item_tax_rate["rate"]))
                {
                    $line->vat = $item_tax_rate["rate"];
                }
                else
                {
                    $line->vat = 0;
                }
                
                $bamboraOrderlines[] = $line;   
                $lineNumber++;
            }
            
            return $bamboraOrderlines;
            
        }

		/**
         * Process the payment and return the result
         **/
		function process_payment($order_id)
		{
			$order = new WC_Order($order_id);
			
			return array(
				'result' 	=> 'success',
				'redirect'	=> $order->get_checkout_payment_url( true )
			);
		}

        function process_refund($order_id, $amount = null, $reason = '')
        {
            $order = new WC_Order($order_id);
            $transactionId = get_post_meta($order->id, 'Transaction ID', true);
            $currency = $order->order_currency;

            $api = new BamboraApi(BamboraHelper::generateApiKey($this->merchant, $this->accesstoken, $this->secrettoken));
            $credit = $api->credit($transactionId, $amount, $currency );
            if(!is_wp_error($credit))
            {
                if($credit)
                    return true;
            }
            else
            {
                foreach($credit->get_error_messages() as $error)
                    $reason .= $error->get_error_message();
            }
            
            return false;
        }

		/**
         * receipt_page
         **/
		function receipt_page( $order )
		{			
		    echo $this->generate_bambora_paymentwindow($order);
        }


        //function bambora_accepted_payment()
        //{
        //    $posted = stripslashes_deep($_GET);
        //    $this->successful_request($posted);           
        //}
		
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
                
                $transinfo = $rest_result["transaction"];
                $order = new WC_Order($transinfo["orderid"]);
                             
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
                        
                        if($genstamp != $posted["hash"])
                        {
                            echo "MD5 error";
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
                    
                    update_post_meta((int)$posted["orderid"], 'Transaction ID', $posted["txnid"]);
                    update_post_meta((int)$posted["orderid"], 'Card no', $posted["cardno"]);
                }
                status_header(200);
                
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
			global $woocommerce;
			
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
                        
						case 'credit':
                            $amount = str_replace(wc_get_price_decimal_separator(),".",$_GET["amount"]);
                            $amount = BamboraCurrency::convertPriceToMinorUnits($amount,$minorUnits);
							$credit = $api->credit($transactionId, $amount,$currency);
							$creditJson = $api->convertJSonResultToArray($credit, "meta");
                            if(!is_wp_error($credit))
							{
								if($creditJson["result"])
                                {
									echo $this->message('updated', __("Payment successfully","woocommerce-gateway-bambora").' <strong>'.__("Refunded","woocommerce-gateway-bambora").'</strong>.');
                                }else{
                                    echo $this->message('updated', $creditJson["message"]["merchant"]);
                                }
							}
							else
							{
								foreach($credit->get_error_messages() as $error)
									throw new Exception ($error->get_error_message());
							}
							
							break;
                        
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
			global $post, $woocommerce;
			
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
                        $availableForCredit =  BamboraCurrency::convertPriceFromMinorUnits($transInfo["available"]["credit"],$minorUnits);
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
                        
                        
                        if($availableForCapture > 0 || $availableForCredit > 0)
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
                            if($availableForCredit > 0)
                            {
                                echo '<div class="bambora_action">';
                                echo '<p>'.$order->get_order_currency().'</p>';
                                echo '<input type="text" value="' . $this->formatNumber($availableForCredit,$minorUnits). '"id="bambora_credit_amount" class="bambora_amount" name="bambora_credit_amount" />';
                                echo '<a class="button credit" onclick="javascript: (confirm(\'' . __('Are you sure you want to credit?', 'woocommerce-gateway-bambora') . '\') ? (location.href=\'' . admin_url('post.php?post=' . $post->ID . '&action=edit&bambora_action=credit') . '&amount=\' + document.getElementById(\'bambora_credit_amount\').value.replace(\''.wc_get_price_thousand_separator().'\',\'\')) : (false));">';
                                _e('Refund ', 'woocommerce-gateway-bambora');
                                echo '</a>';
                                echo '</div>';
                                echo '<br/>';

                            }

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
