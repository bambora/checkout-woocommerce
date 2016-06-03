<?php

define('bambora_endpoint_transaction', 'https://transaction-v1.api.epay.eu');
define('bambora_endpoint_merchant', 'https://merchant-v1.api.epay.eu');
define('bambora_endpoint_data','https://data-v1.api.epay.eu/');
define('bambora_endpoint_checkout', 'https://api.v1.checkout.bambora.com');
define('bambora_checkout_assets', 'https://v1.checkout.bambora.com/Assets');

class BamboraendpointConfig{
    
    static function getTransactionEndpoint(){
        return constant('bambora_endpoint_transaction');   
    }

    static function getMerchantEndpoint(){
        return constant('bambora_endpoint_merchant');   
    }
    
    static function getDataEndpoint(){
        return constant('bambora_endpoint_data');
    }
    
    static function getCheckoutEndpoint(){
        return constant('bambora_endpoint_checkout');   
    }

    static function getCheckoutAssets(){
        return constant('bambora_checkout_assets');   
    }

}

?>