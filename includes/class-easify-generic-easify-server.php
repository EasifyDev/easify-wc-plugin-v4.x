<?php

/**
 * Copyright (C) 2017  Easify Ltd (email:support@easify.co.uk)
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

/**
 * Provides generic access to the specified Easify Server
 * 
 * Construct this class with the URL of your Easify Server, along with the 
 * username and password of your Easify ECommerce subscription.
 * 
 * You can then call the methods within the class to retrieve data from your 
 * Easify Server.
 * 
 * @class       Easify_Generic_Easify_Server
 * @version     4.6
 * @package     easify-woocommerce-connector
 * @author      Easify 
 */
class Easify_Generic_Easify_Server {

    private $server_url;
    private $username;
    private $password;

    public function __construct($server_url, $username, $password) {
        $this->server_url = $server_url;
        $this->username = $username;
        $this->password = $password;
    }

    public function UpdateServerUrl($server_url) {
        $this->server_url = $server_url;
    }

    private function GetFromEasify($Entity, $Key) {
        if (empty($this->server_url))
            return;

        if ($Key == null) {
            $url = $this->server_url . "/" . $Entity;
        } else {
            $url = $this->server_url . "/" . $Entity . '(' . $Key . ')';
        }

        $result = $this->GetFromWebService($url, true);

        // parse XML so it can be navigated
        $xpath = $this->ParseXML($result);

        return $xpath;
    }

    private function ParseXML($Xml) {
        if (empty($Xml))
            return;

        // load and parse returned xml result from get operation
        $document = new DOMDocument($Xml);
        $document->loadXml($Xml);
        $xpath = new DOMXpath($document);

        // register name spaces
        $namespaces = array(
            'a' => 'http://www.w3.org/2005/Atom',
            'd' => 'http://schemas.microsoft.com/ado/2007/08/dataservices',
            'm' => 'http://schemas.microsoft.com/ado/2007/08/dataservices/metadata'
        );

        foreach ($namespaces as $prefix => $namespace) {
            $xpath->registerNamespace($prefix, $namespace);
        }

        // return navigatable xml result
        return $xpath;
    }
/**
 * DEPRECATED
 * 
 * Being replaced with GetFromEasifyServer() which uses JSON instead of XML
 * 
 * @param type $Url
 * @return string
 * @throws Exception
 */
    private function GetFromWebService($Url) {
        // initialise PHP CURL for HTTP GET action
        $ch = curl_init();

        // setting up coms to an Easify Server 
        // HTTPS and BASIC Authentication
        // NB. required to allow self signed certificates
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if (version_compare(phpversion(), "7.0.7", ">=")) {
            // CURLOPT_SSL_VERIFYSTATUS is PHP 7.0.7 feature
            // TODO: Also need to ensure CURL is V7.41.0 or later!
            //curl_setopt($ch, CURLOPT_SSL_VERIFYSTATUS, false);
        }

        // do not verify https certificates
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        // if https is set, user basic authentication
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "$this->username:$this->password");

        // server URL 
        curl_setopt($ch, CURLOPT_URL, $Url);
        // return result or GET action
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // set timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, EASIFY_TIMEOUT);

        // send GET request to server, capture result
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
		
        if ($info['http_code'] != '200')
        {
            Easify_Logging::Log('Could not communicate with Easify Server -  http response code: ' . $info['http_code']);
            throw new Exception('Could not communicate with Easify Server -  http response code: ' . $info['http_code']);
        }
        
        // record any errors
        if (curl_error($ch)) {
            $result = 'error:' . curl_error($ch);
            Easify_Logging::Log($result);
            throw new Exception($result);
        }

        curl_close($ch);

        return $result;
    }
    
    /**
     * Gets a JSON response from the specified Easify Server...
     * 
     * If you want to send an order to an Easify Server, use the Easify Cloud
     * API Server (See Easify_WC_Send_Order_To_Easify()).
     * 
     * @param type $url
     * @return string
     * @throws Exception
     */
    private function GetFromEasifyServer($url) {
        Easify_Logging::Log("Easify_Generic_Easify_Server.GetFromEasifyServer()");
                            
        // initialise PHP CURL for HTTP GET action
        $ch = curl_init();

        // Specify JSON to an Easify Server and it will return JSON instead of 
        // XML.
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json'
        ));
        
        // setting up coms to an Easify Server 
        // HTTPS and BASIC Authentication
        // NB. required to allow self signed certificates
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if (version_compare(phpversion(), "7.0.7", ">=")) {
            // CURLOPT_SSL_VERIFYSTATUS is PHP 7.0.7 feature
            // TODO: Also need to ensure CURL is V7.41.0 or later!
            //curl_setopt($ch, CURLOPT_SSL_VERIFYSTATUS, false);
        }

        // do not verify https certificates
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        // if https is set, user basic authentication
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "$this->username:$this->password");

        // server URL 
        curl_setopt($ch, CURLOPT_URL, $url);
        // return result or GET action
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // set timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, EASIFY_TIMEOUT);

        // send GET request to server, capture result
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
		
        if ($info['http_code'] != '200')
        {
            Easify_Logging::Log('Could not communicate with Easify Server -  http response code: ' . $info['http_code']);
            throw new Exception('Could not communicate with Easify Server -  http response code: ' . $info['http_code']);
        }
                
        // record any errors
        if (curl_error($ch)) {
            $result = 'error:' . curl_error($ch);
            Easify_Logging::Log($result);
            throw new Exception($result);
        }

        curl_close($ch);

        return $result;
    }

    private function GetJsonFromEasifyServer($entity, $key) {
        Easify_Logging::Log("Easify_Generic_Easify_Server.GetJsonFromEasifyServer() - Entity: " . $entity . " Key: " . $key);
                    
        if (empty($this->server_url))
            return;

        if ($key == null) {
            $url = $this->server_url . "/" . $entity;
        } else {
            $url = $this->server_url . "/" . $entity . '(' . $key . ')';
        }

        $ret = $this->GetFromEasifyServer($url, true);
        return $ret;
    }
    
    /**
     * Try to pull a sentinel product from the Easify Server to see if we can #
     * communicate with it...
     * 
     * @return boolean
     */
    public function HaveComsWithEasify() {
        try {
            Easify_Logging::Log("Easify_Generic_Easify_Server.HaveComsWithEasify()"); 
            
            // Get sentinel product from Easify Server
            $product = new ProductDetails($this->GetJsonFromEasifyServer("Products", "-100"));                       
            
            // See if the product we got has the data we expect
            return $product->SKU == "-100";            
        } catch (Exception $e) {
            Easify_Logging::Log($e);
            return false;
        }
    }

    /**
     * To get NetBeans autocomplete to work add the following comment anywhere
     * before this method is called:
     *      @var $product ProductDetails   
     *    
     * @param type $EasifySku
     * @return ProductDetails
     */
    public function GetProductFromEasify($EasifySku) {
        return new ProductDetails($this->GetJsonFromEasifyServer("Products", $EasifySku));        
    }

    public function GetEasifyProductCategories() {
        $xpath = $this->GetFromEasify("ProductCategories", null);

        $CategoryIds = $xpath->query('/a:feed/a:entry/a:content/m:properties/d:CategoryId');
        $CategoryDescriptions = $xpath->query('/a:feed/a:entry/a:content/m:properties/d:Description');

        $categories = array();
        for ($i = 0; $i < $CategoryIds->length; $i++) {
            $categories[$i] = array(
                'CategoryId' => $CategoryIds->item($i)->nodeValue,
                'Description' => $CategoryDescriptions->item($i)->nodeValue
            );
        }

        return $categories;
    }

    public function GetEasifyCategoryDescriptionFromEasifyCategoryId($EasifyCategories, $CategoryId) {
        // match the category description by its id
        for ($i = 0; $i < sizeof($EasifyCategories); $i++)
            if ($EasifyCategories[$i]['CategoryId'] == $CategoryId)
                return $EasifyCategories[$i]['Description'];
        return null;
    }

    public function GetEasifyProductSubCategoriesByCategory($CategoryId) {
        $xpath = $this->GetFromEasify('ProductSubcategories?$filter=CategoryId%20eq%20' . $CategoryId, null);

        $SubcategoryIds = $xpath->query('/a:feed/a:entry/a:content/m:properties/d:SubCategoryId');
        $SubcategoryDescriptions = $xpath->query('/a:feed/a:entry/a:content/m:properties/d:Description');

        $subcategories = array();
        for ($i = 0; $i < $SubcategoryIds->length; $i++) {
            $subcategories[$i] = array(
                'CategoryId' => $SubcategoryIds->item($i)->nodeValue,
                'Description' => $SubcategoryDescriptions->item($i)->nodeValue
            );
        }

        return $subcategories;
    }

    public function GetProductWebInfo($EasifySku) {
        $xpath = $this->GetFromEasify('ProductInfo?$filter=SKU%20eq%20' . $EasifySku, null);

        $Images = $xpath->query('/a:feed/a:entry/a:content/m:properties/d:Image');
        $Descriptions = $xpath->query('/a:feed/a:entry/a:content/m:properties/d:Description');

        $product_info = array(
            'Image' => base64_decode($Images->item(0)->nodeValue),
            'Description' => $Descriptions->item(0)->nodeValue
        );

        return $product_info;
    }

    public function GetEasifyOrderStatuses() {
        $xpath = $this->GetFromEasify('OrderStatuses', null);

        $OrderStatusIds = $xpath->query('/a:feed/a:entry/a:content/m:properties/d:OrderStatusId');
        $Descriptions = $xpath->query('/a:feed/a:entry/a:content/m:properties/d:Description');
        $OrderStatusTypeIds = $xpath->query('/a:feed/a:entry/a:content/m:properties/d:OrderStatusTypeId');
        $Systems = $xpath->query('/a:feed/a:entry/a:content/m:properties/d:System');
        $DefaultTypes = $xpath->query('/a:feed/a:entry/a:content/m:properties/d:DefaultType');

        $order_statuses = array();
        for ($i = 0; $i < $OrderStatusIds->length; $i++) {
            $order_statuses[$i] = array(
                'OrderStatusId' => $OrderStatusIds->item($i)->nodeValue,
                'Description' => $Descriptions->item($i)->nodeValue,
                'OrderStatusTypeId' => $OrderStatusTypeIds->item($i)->nodeValue,
                'System' => $Systems->item($i)->nodeValue,
                'DefaultType' => $DefaultTypes->item($i)->nodeValue
            );
        }

        return $order_statuses;
    }

    public function GetEasifyOrderTypes() {
        $xpath = $this->GetFromEasify('OrderTypes', null);

        $OrderTypeIds = $xpath->query('/a:feed/a:entry/a:content/m:properties/d:OrderTypeId');
        $Descriptions = $xpath->query('/a:feed/a:entry/a:content/m:properties/d:Description');

        $order_types = array();
        for ($i = 0; $i < $OrderTypeIds->length; $i++) {
            $order_types[$i] = array(
                'OrderTypeId' => $OrderTypeIds->item($i)->nodeValue,
                'Description' => $Descriptions->item($i)->nodeValue
            );
        }

        return $order_types;
    }

    public function GetEasifyCustomerTypes() {
        $xpath = $this->GetFromEasify('CustomerTypes', null);

        $CustomerTypeIds = $xpath->query('/a:feed/a:entry/a:content/m:properties/d:CustomerTypeId');
        $Descriptions = $xpath->query('/a:feed/a:entry/a:content/m:properties/d:Description');

        $customer_types = array();
        for ($i = 0; $i < $CustomerTypeIds->length; $i++) {
            $customer_types[$i] = array(
                'CustomerTypeId' => $CustomerTypeIds->item($i)->nodeValue,
                'Description' => $Descriptions->item($i)->nodeValue
            );
        }

        return $customer_types;
    }

    public function GetEasifyCustomerRelationships() {
        $xpath = $this->GetFromEasify('CustomerRelationships', null);

        $CustomerRelationshipIds = $xpath->query('/a:feed/a:entry/a:content/m:properties/d:CustomerRelationshipId');
        $Descriptions = $xpath->query('/a:feed/a:entry/a:content/m:properties/d:Description');

        $customer_relationships = array();
        for ($i = 0; $i < $CustomerRelationshipIds->length; $i++) {
            $customer_relationships[$i] = array(
                'CustomerRelationshipId' => $CustomerRelationshipIds->item($i)->nodeValue,
                'Description' => $Descriptions->item($i)->nodeValue
            );
        }

        return $customer_relationships;
    }

    public function GetEasifyPaymentTerms() {
        $xpath = $this->GetFromEasify('PaymentTerms', null);

        $PaymentTermsIds = $xpath->query('/a:feed/a:entry/a:content/m:properties/d:PaymentTermsId');
        $Descriptions = $xpath->query('/a:feed/a:entry/a:content/m:properties/d:Description');
        $PaymentDays = $xpath->query('/a:feed/a:entry/a:content/m:properties/d:PaymentDays');

        $payment_terms = array();
        for ($i = 0; $i < $PaymentTermsIds->length; $i++) {
            $payment_terms[$i] = array(
                'PaymentTermsId' => $PaymentTermsIds->item($i)->nodeValue,
                'Description' => $Descriptions->item($i)->nodeValue,
                'PaymentDays' => $PaymentDays->item($i)->nodeValue
            );
        }

        return $payment_terms;
    }

    public function GetEasifyPaymentMethods() {
        $xpath = $this->GetFromEasify('PaymentMethods', null);

        $PaymentMethodsIds = $xpath->query('/a:feed/a:entry/a:content/m:properties/d:PaymentMethodsId');
        $Descriptions = $xpath->query('/a:feed/a:entry/a:content/m:properties/d:Description');
        $Actives = $xpath->query('/a:feed/a:entry/a:content/m:properties/d:Active');
        $PaymentMethodTypeIds = $xpath->query('/a:feed/a:entry/a:content/m:properties/d:PaymentMethodTypeId');
        $ShowInPOSs = $xpath->query('/a:feed/a:entry/a:content/m:properties/d:ShowInPOS');
        $RowOrders = $xpath->query('/a:feed/a:entry/a:content/m:properties/d:RowOrder');

        $payment_methods = array();
        for ($i = 0; $i < $PaymentMethodsIds->length; $i++) {
            $payment_methods[$i] = array(
                'PaymentMethodsId' => $PaymentMethodsIds->item($i)->nodeValue,
                'Description' => $Descriptions->item($i)->nodeValue,
                'Active' => $Actives->item($i)->nodeValue,
                'PaymentMethodTypeId' => $PaymentMethodTypeIds->item($i)->nodeValue,
                'ShowInPOS' => $ShowInPOSs->item($i)->nodeValue,
                'RowOrder' => $RowOrders->item($i)->nodeValue
            );
        }

        return $payment_methods;
    }

    public function GetEasifyPaymentAccounts() {
        $xpath = $this->GetFromEasify('PaymentAccounts', null);

        $PaymentAccountIds = $xpath->query('/a:feed/a:entry/a:content/m:properties/d:PaymentAccountId');
        $Descriptions = $xpath->query('/a:feed/a:entry/a:content/m:properties/d:Description');
        $Actives = $xpath->query('/a:feed/a:entry/a:content/m:properties/d:Active');
        $AccountTypes = $xpath->query('/a:feed/a:entry/a:content/m:properties/d:AccountType');
        $OpeningBalances = $xpath->query('/a:feed/a:entry/a:content/m:properties/d:OpeningBalance');

        $payment_accounts = array();
        for ($i = 0; $i < $PaymentAccountIds->length; $i++) {
            $payment_accounts[$i] = array(
                'PaymentAccountId' => $PaymentAccountIds->item($i)->nodeValue,
                'Description' => $Descriptions->item($i)->nodeValue,
                'Active' => $Actives->item($i)->nodeValue,
                'AccountType' => $AccountTypes->item($i)->nodeValue,
                'OpeningBalance' => $OpeningBalances->item($i)->nodeValue
            );
        }

        return $payment_accounts;
    }

    public function GetEasifyTaxRates() {
        $xpath = $this->GetFromEasify('TaxRates', null);

        $TaxIds = $xpath->query('/a:feed/a:entry/a:content/m:properties/d:TaxId');
        $TaxCodes = $xpath->query('/a:feed/a:entry/a:content/m:properties/d:Code');
        $IsDefaultTaxCodes = $xpath->query('/a:feed/a:entry/a:content/m:properties/d:IsDefaultTaxCode');
        $TaxRates = $xpath->query('/a:feed/a:entry/a:content/m:properties/d:Rate');
        $TaxDescriptions = $xpath->query('/a:feed/a:entry/a:content/m:properties/d:TaxDescription');

        $tax_rates = array();
        for ($i = 0; $i < $TaxIds->length; $i++) {
            $tax_rates[$i] = array(
                'TaxId' => $TaxIds->item($i)->nodeValue,
                'Code' => $TaxCodes->item($i)->nodeValue,
                'IsDefaultTaxCode' => $IsDefaultTaxCodes->item($i)->nodeValue,
                'Rate' => $TaxRates->item($i)->nodeValue,
                'TaxDescription' => $TaxDescriptions->item($i)->nodeValue
            );
        }

        return $tax_rates;
    }

    /**
     * Determines the amount of stock for the specified SKU that has been 
     * allocated to other orders.
     * 
     * @param string $sku
     * @return string
     */
    public function get_allocation_count_by_easify_sku($sku) {
        // Call a WebGet to get the allocated stock level...
        $url = $this->server_url . '/Products_Allocated?SKU=' . $sku;
        $xmlString = $this->GetFromWebService($url);               
        $xml = simplexml_load_string($xmlString);                   
        return (string)$xml[0];                            
    }

}

class ProductDetails {
/**
 * The constructor takes in a serialised JSON object, and de-serialises it into
 * the properties of this class.
 * 
 * Make sure that the property names in this class map exactly to the property
 * names in the JSON object, otherwise you'll get blank values where you 
 * expect values.
 * 
 * @param type $json
 */
    function __construct($json) {
        // Initialise class based on passed in json...
        $json = json_decode($json);

        // Here we iterate each property in the JSON and map it into the 
        // corresponding property of this class.
        foreach ($json as $key => $value) {
            if (!property_exists($this, $key)) continue;

            $this->{$key} = $value;
        }
    }
    
    public $SKU; // Integer
    public $Description; // String   
    public $CategoryId; // Int     
    public $SubcategoryId; // Int       
    public $OurStockCode; // String
    public $EANCode; // String            
    public $ManufacturerStockCode; // String
    public $SupplierStockCode; // String
    public $ManufacturerId; // Int
    public $CostPrice; // Decimal
    public $Markup; // Decimal    
    public $Comments; // String        
    public $StockLevel; // Int
    public $Discontinued; // Boolean
    public $PriceChangeDate; // DateTime            
    public $MinStockLevel; // Int            
    public $ReorderQty; // Int
    public $ReorderWhenLow; // Boolean
    public $SupplierId; // Int    
    public $RetailMargin; // Double
    public $TradeMargin; // Double
    public $TaxId; // Int       
    public $LastStockCheckDate; // DateTime
    public $Published; // Boolean
    public $Allocatable; // Boolean
    public $LoyaltyPoints; // Int   
    Public $Weight; // Double    
    public $ItemTypeId; // Int    
    public $LocationId; // Int    
    public $DiscontinueWhenDepleted; // Boolean      
    Public $DateAddedToEasify; // DateTime
    public $WebInfoPresent; // Boolean            
    Public $Tags; // String
    Public $ConditionId; // Int
    Public $ConditionDescription; // String
    public $UseSecondHandVat; // Boolean         
}

?>