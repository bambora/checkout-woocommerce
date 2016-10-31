<?php

class BamboraCustomer
{
    public $email;
    public $phonenumber;
    public $phonenumbercountrycode;
}

class BamboraOrder
{
    public $billingaddress; //Bambora Address
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
    public $id; // Stock-keeping unit (SKU)
    public $linenumber;
    public $quantity;
    public $text;
    public $totalprice;
    public $totalpriceinclvat;
    public $totalpricevatamount;
    public $unit;
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
    public $customer; //Bambora Custommer
    public $instantcaptureamount;
    public $language;
    public $order; //Bambora Order
    public $url; //Bambora Url
    public $paymentwindowid;
}
