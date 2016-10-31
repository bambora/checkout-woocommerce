<?php
include(bambora_LIB .'bamboraModels.php');
include(bambora_LIB .'bamboraEndpoints.php');

class BamboraApi
{
    private $apiKey = "";

    function __construct($apiKey = "")
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Get the specified property of a service result
     * @param string $result
     * @param string $objectName
     * @param string $propertyName
     * @return mixed
     */
    public function getPropertyOfServiceResult($result, $objectName ,$propertyName)
    {
        $json = json_decode($result, true);
        $val =  $json[$objectName][$propertyName];
        return $val;
    }

    //API Methodes
    /**
     * Get Bambora Checkout response.
     * @param BamboraCheckoutRequest $bamboracheckoutrequest
     * @return mixed
     */
    public function getcheckoutresponse($bamboracheckoutrequest)
    {
        $serviceUrl = BamboraendpointConfig::getCheckoutEndpoint().'/checkout' ;
        if($bamboracheckoutrequest == null)
        {
            return null;
        }

        $jsonData = json_encode($bamboracheckoutrequest);
        $expresscheckoutresponse = $this->_callRestService($serviceUrl, $jsonData, "POST");

        return $expresscheckoutresponse;
    }

    /**
     * Get Bambora Checkout payment window JavaScript url
     * @return string
     */
    public function getcheckoutpaymentwindowjs()
    {
        $url = BamboraendpointConfig::getCheckoutAssets().'/paymentwindow-v1.min.js';

        return $url;
    }

    /**
     * Convert JSon string to array
     * @param string $result
     * @param string $elementName
     * @return array|null
     */
    public function convertJSonResultToArray($result, $elementName)
    {
        $json = json_decode($result, true);
        $transaction = array_key_exists($elementName, $json) ? $json[$elementName] : null;
        $res = array();

        if ($transaction == null)
            return null;

        $properties = array_keys($transaction);

        foreach($properties as $attr )
        {
            $res[$attr] =  $transaction[$attr];
        }
        return $res;
    }

    /**
     * Make a capture request to Bambora
     * @param string $transactionid
     * @param int $amount
     * @param string $currency
     * @return mixed
     */
    public function capture($transactionid, $amount, $currency)
    {
        $serviceUrl = BamboraendpointConfig::getTransactionEndpoint().'/transactions/'.  sprintf('%.0F',$transactionid) . '/capture';

        $data = array();
        $data["amount"] = $amount;
        $data["currency"] = $currency;

        $jsonData = json_encode($data);

        $result = $this->_callRestService($serviceUrl, $jsonData, "POST");
        return $result;
    }

    /**
     * Make a credit request to Bambora
     * @param string $transactionid
     * @param int $amount
     * @param string $currency
     * @param BamboraOrderLine $creditLines
     * @return mixed
     */
    public function credit($transactionid, $amount, $currency, $creditLines)
    {
        $serviceUrl = BamboraendpointConfig::getTransactionEndpoint().'/transactions/'.  sprintf('%.0F',$transactionid) . '/credit';

        $data = array();
        $data["amount"] = $amount;
        $data["currency"] = $currency;
        $data["invoicelines"] = $creditLines;

        $jsonData = json_encode($data);

        $result = $this->_callRestService($serviceUrl, $jsonData, "POST");
        return $result;
    }

    /**
     * Make a delete request to Bambora
     * @param string $transactionid
     * @return mixed
     */
    public function delete($transactionid)
    {
        $serviceUrl = BamboraendpointConfig::getTransactionEndpoint().'/transactions/'.  sprintf('%.0F',$transactionid) . '/delete';

        $data = array();
        $jsonData = json_encode($data);

        $result = $this->_callRestService($serviceUrl, $jsonData, "POST");
        return $result;
    }

    /**
     * Get specific transaction from Bambora
     * @param string $transactionid
     * @return mixed
     */
    public function gettransactionInformation($transactionid)
    {
        $serviceUrl = BamboraendpointConfig::getMerchantEndpoint().'/transactions/'. sprintf('%.0F',$transactionid);

        $data = array();
        $jsonData = json_encode($data);

        $result = $this->_callRestService($serviceUrl, $jsonData, "GET");
        return $result;
    }

    /**
     * Get transaction operations for a specific transaction from Bambora
     * @param string $transactionid
     * @return mixed
     */
    public function getTransactionOperations($transactionid)
    {
        $serviceUrl = BamboraendpointConfig::getMerchantEndpoint().'/transactions/'. sprintf('%.0F',$transactionid) .'/transactionoperations';

        $data = array();
        $jsonData = json_encode($data);

        $result = $this->_callRestService($serviceUrl, $jsonData, "GET");
        return $result;
    }

    /**
     * Get available payment types for the amount and currency from Bambora
     * @param string $currency
     * @param int $amount
     * @return mixed
     */
    public function getPaymentTypes($currency, $amount)
    {
        $serviceUrl = BamboraendpointConfig::getMerchantEndpoint().'/paymenttypes?currency='. $currency .'&amount='.$amount;
        $data = array();

        $jsonData = json_encode($data);

        $result = $this->_callRestService($serviceUrl, $jsonData, "GET");
        return $result;
    }

    /**
     * Get the ids of the available payment groups for the amount and currency from Bambora
     * @param string $currency
     * @param int $amount
     * @return array
     */
    public function getAvaliablePaymentGroupIdsForMerchant($currency, $amount)
    {
        $res = array();
        $serviceRes = $this -> getPaymentTypes($currency, $amount);

        $availablePaymentTypesResjson = json_decode($serviceRes, true);
        if ($availablePaymentTypesResjson['meta']['result'] == true)
        {
            foreach($availablePaymentTypesResjson['paymentcollections'] as $payment )
            {
                foreach($payment['paymentgroups'] as $card)
                {
                    $res[] = $card['id'];
                }
            }

            ksort($res);
        }
        return $res;
    }

    /**
     * Call the rest service at the specified Url
     * @param string $serviceUrl
     * @param mixed $jsonData
     * @param string $postOrGet
     * @return mixed
     */
    private function _callRestService($serviceUrl,  $jsonData, $postOrGet)
    {
        $headers = array(
            'Content-Type: application/json',
            'Content-Length: '.strlen(@$jsonData),
            'Accept: application/json',
            'Authorization: '.$this->apiKey,
            //'X-EPay-System: '.$this->getModuleHeaderInfo()

        );

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST,$postOrGet);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($curl, CURLOPT_URL, $serviceUrl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);


        $result = curl_exec($curl);
        return $result;
    }

    /**
     * Returns the module header
     *
     * @return string
     */
    private function getModuleHeaderInfo()
    {
        global $woocommerce;
        $bamboraVersion = WC_Gateway_Bambora::MODULE_VERSION;
        $woocommerceVersion = $woocommerce->version;
        $result = 'WooCommerce/' . $woocommerceVersion . ' Module/' . $bamboraVersion;
        return $result;
    }
}