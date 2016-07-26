<?php

class BamboraCustomer
{
    public $email;
    public $phonenumber;
    public $phonenumbercountrycode;
}

class BamboraOrder
{
    public $billingaddress; //BanboraAddress
    public $currency;
    public $lines;
    public $ordernumber;
    public $shippingaddress;        
    public $total;
    public $vatamount;
}
class BamboraAddress
{
    public $att;
    public $city;
    public $country;
    public $firstname;
    public $lastname;
    public $street;
    public $zip;
}

class BamboraOrderLine
{
    public $description;
    public $id; //sku
    public $linenumber;
    public $quantity;
    public $text;
    public $totalprice;
    public $totalpriceinclvat;
    public $totalpricevatamount;
    public $unit;
    public $unitprice;
    public $unitpriceinclvat;
    public $unitpricevatamount;
    public $vat;
}

class BamboraUrl
{
    public $accept;
    public $callbacks;
    public $decline;
}

class BamboraCallback
{
    public $url;
}

class BamboraUiMessage
{
    public $type;
    public $title;
    public $message;
}

class BamboraCheckoutRequest
{
    public $capturemulti;
    public $customer; //BamboraCustommer
    public $instantcaptureamount;
    public $language;
    public $order; //bamboraOrder 
    public $url; //bamboraUrl
    public $paymentwindowid;
}
