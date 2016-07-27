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

    //Helpers
    public function getPropertyOfServiceResult($result, $objectName ,$propertyName)
    {      
        $json = json_decode($result, true);
        $val =  $json[$objectName][$propertyName];
        return $val; 
    }

    //API Methodes

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

    public function getcheckoutpaymentwindowjs()
    {
        $url = BamboraendpointConfig::getCheckoutAssets().'/paymentwindow-v1.min.js';

        return $url;   
    }


    public function convertJSonResultToArray($result, $elementName)
    {
        $json = json_decode($result, true);
        $transaction = $json[$elementName];
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

    public function credit($transactionid, $amount, $currency)
    {       
        $serviceUrl = BamboraendpointConfig::getTransactionEndpoint().'/transactions/'.  sprintf('%.0F',$transactionid) . '/credit';             

        $data = array();
        $data["amount"] = $amount;
        $data["currency"] = $currency; 

        $jsonData = json_encode($data);
        
        $result = $this->_callRestService($serviceUrl, $jsonData, "POST");
        return $result;        
    }

    public function delete($transactionid)
    {                  
        $serviceUrl = BamboraendpointConfig::getTransactionEndpoint().'/transactions/'.  sprintf('%.0F',$transactionid) . '/delete';             
        
        $data = array();
        $jsonData = json_encode($data);
        
        $result = $this->_callRestService($serviceUrl, $jsonData, "POST");
        return $result;    
    }

    public function gettransactionInformation($transactionid)
    {            
        $serviceUrl = BamboraendpointConfig::getMerchantEndpoint().'/transactions/'. sprintf('%.0F',$transactionid);                         

        $data = array();    
        $jsonData = json_encode($data);
        
        $result = $this->_callRestService($serviceUrl, $jsonData, "GET");
        return $result;    
    }
    
    public function getTransactionOperations($transactionid)
    {            
        $serviceUrl = BamboraendpointConfig::getMerchantEndpoint().'/transactions/'. sprintf('%.0F',$transactionid) .'/transactionoperations';             

        $data = array();    
        $jsonData = json_encode($data);
        
        $result = $this->_callRestService($serviceUrl, $jsonData, "GET");
        return $result;    
    }

    public function getPaymentTypes($currency, $amount)
    {   
        $serviceUrl = BamboraendpointConfig::getMerchantEndpoint().'/paymenttypes?currency='. $currency .'&amount='.$amount;
        $data = array();
        
        $jsonData = json_encode($data);
        
        $result = $this->_callRestService($serviceUrl, $jsonData, "GET");
        return $result;        
    }

    public function getAvaliablePaymentcardidsForMerchant($currency, $amount)
    {
        $res = array();
        $serviceRes = $this -> getPaymentTypes($currency, $amount);

        $availablePaymentTypesResjson = json_decode($serviceRes, true);
        if ($availablePaymentTypesResjson['meta']['result'] == true)
        {
            foreach($availablePaymentTypesResjson['paymentcollections'] as $payment )
            {
                if ($payment['name'] == 'paymentcard')
                {                    
                    foreach($payment['paymentgroups'] as $card)
                    {                         
                        //enshure unique id:
                        $cardname = $card['id'];
                        $res[$cardname] = $card['id'];              
                    }
                }
            }

            ksort($res);
        }
        return $res;               
    }

    private function _callRestService($serviceUrl,  $jsonData, $postOrGet)
    {    
        $headers = array(
            'Content-Type: application/json',
            'Content-Length: '.strlen(@$jsonData),
            'Accept: application/json',
            'Authorization: '.$this->apiKey
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
}