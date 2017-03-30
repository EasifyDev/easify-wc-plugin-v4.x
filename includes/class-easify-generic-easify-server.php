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
 * @version     4.0
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

    private function GetFromWebService($Url) {
        // initialise PHP CURL for HTTP GET action
        $ch = curl_init();

        // setting up coms to an Easify Server 
        // HTTPS and BASIC Authentication
        // NB. required to allow self signed certificates
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if (version_compare(phpversion(), "7.0.7", ">=")) {
            // CURLOPT_SSL_VERIFYSTATUS is PHP 7.0.7 feature
            curl_setopt($ch, CURLOPT_SSL_VERIFYSTATUS, false);
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

        // record any errors
        if (curl_error($ch)) {
            $result = 'error:' . curl_error($ch);
            Easify_Logging::Log($result);
            throw new Exception($result);
        }

        curl_close($ch);

        return $result;
    }

    public function HaveComsWithEasify() {
        try {
            $xpath = $this->GetFromEasify("Products", "-100");

            if (empty($xpath))
                return false;

            $Sku = $xpath->evaluate('string(/a:entry/a:content/m:properties/d:SKU)');

            return $Sku == "-100";
        } catch (Exception $e) {
            Easify_Logging::Log($e);
            return false;
        }
    }

    public function GetProductFromEasify($EasifySku) {

        $xpath = $this->GetFromEasify("Products", $EasifySku);

        if ($xpath == null)
            return;

        $Sku = $xpath->evaluate('string(/a:entry/a:content/m:properties/d:SKU)');
        $Description = $xpath->evaluate('string(/a:entry/a:content/m:properties/d:Description)');
        $CategoryId = $xpath->evaluate('string(/a:entry/a:content/m:properties/d:CategoryId)');
        $SubcategoryId = $xpath->evaluate('string(/a:entry/a:content/m:properties/d:SubcategoryId)');
        $OurStockCode = $xpath->evaluate('string(/a:entry/a:content/m:properties/d:OurStockCode)');
        $EANCode = $xpath->evaluate('string(/a:entry/a:content/m:properties/d:EANCode)');
        $ManufacturerStockCode = $xpath->evaluate('string(/a:entry/a:content/m:properties/d:ManufacturerStockCode)');
        $SupplierStockCode = $xpath->evaluate('string(/a:entry/a:content/m:properties/d:SupplierStockCode)');
        $ManufacturerId = $xpath->evaluate('string(/a:entry/a:content/m:properties/d:ManufacturerId)');
        $CostPrice = $xpath->evaluate('string(/a:entry/a:content/m:properties/d:CostPrice)');
        $Markup = $xpath->evaluate('string(/a:entry/a:content/m:properties/d:Markup)');
        $Comments = $xpath->evaluate('string(/a:entry/a:content/m:properties/d:Comments)');
        $StockLevel = $xpath->evaluate('string(/a:entry/a:content/m:properties/d:StockLevel)');
        $Discontinued = $xpath->evaluate('string(/a:entry/a:content/m:properties/d:Discontinued)');
        $PriceChangeDate = $xpath->evaluate('string(/a:entry/a:content/m:properties/d:PriceChangeDate)');
        $MinStockLevel = $xpath->evaluate('string(/a:entry/a:content/m:properties/d:MinStockLevel)');
        $ReorderQty = $xpath->evaluate('string(/a:entry/a:content/m:properties/d:ReorderQty)');
        $ReorderWhenLow = $xpath->evaluate('string(/a:entry/a:content/m:properties/d:ReorderWhenLow)');
        $SupplierId = $xpath->evaluate('string(/a:entry/a:content/m:properties/d:SupplierId)');
        $RetailMargin = $xpath->evaluate('string(/a:entry/a:content/m:properties/d:RetailMargin)');
        $TradeMargin = $xpath->evaluate('string(/a:entry/a:content/m:properties/d:TradeMargin)');
        $TaxId = $xpath->evaluate('string(/a:entry/a:content/m:properties/d:TaxId)');
        $LastStockCheckDate = $xpath->evaluate('string(/a:entry/a:content/m:properties/d:LastStockCheckDate)');
        $Published = $xpath->evaluate('string(/a:entry/a:content/m:properties/d:Published)');
        $Allocatable = $xpath->evaluate('string(/a:entry/a:content/m:properties/d:Allocatable)');
        $LoyaltyPoints = $xpath->evaluate('string(/a:entry/a:content/m:properties/d:LoyaltyPoints)');
        $Weight = $xpath->evaluate('string(/a:entry/a:content/m:properties/d:Weight)');
        $ItemTypeId = $xpath->evaluate('string(/a:entry/a:content/m:properties/d:ItemTypeId)');
        $LocationId = $xpath->evaluate('string(/a:entry/a:content/m:properties/d:LocationId)');
        $DiscontinueWhenDepleted = $xpath->evaluate('string(/a:entry/a:content/m:properties/d:DiscontinueWhenDepleted)');
        $DateAddedToEasify = $xpath->evaluate('string(/a:entry/a:content/m:properties/d:DateAddedToEasify)');
        $WebInfoPresent = $xpath->evaluate('string(/a:entry/a:content/m:properties/d:WebInfoPresent)');

        $product = new ProductDetails();
        $product->SKU = $Sku;
        $product->Description = $Description;
        $product->CategoryId = $CategoryId;
        $product->SubcategoryId = $SubcategoryId;
        $product->OurStockCode = $OurStockCode;
        $product->EANCode = $EANCode;
        $product->ManufacturerStockCode = $ManufacturerStockCode;
        $product->SupplierStockCode = $SupplierStockCode;
        $product->ManufacturerId = $ManufacturerId;
        $product->CostPrice = $CostPrice;
        $product->Markup = $Markup;
        $product->Comments = $Comments;
        $product->StockLevel = $StockLevel;
        $product->Discontinued = $Discontinued;
        $product->DatePriceLastChanged = $PriceChangeDate;
        $product->MinimumStockLevel = $MinStockLevel;
        $product->ReorderAmount = $ReorderQty;
        $product->ReorderWhenLow = $ReorderWhenLow;
        $product->SupplierId = $SupplierId;
        $product->RetailMargin = $RetailMargin;
        $product->TradeMargin = $TradeMargin;
        $product->TaxId = $TaxId;
        $product->DateStockLevelLastUpdated = $LastStockCheckDate;
        $product->Published = $Published;
        $product->Allocatable = $Allocatable;
        $product->LoyaltyPoints = $LoyaltyPoints;
        $product->Weight = $Weight;
        $product->ItemTypeId = $ItemTypeId;
        $product->LocationId = $LocationId;
        $product->DiscontinueWhenDepleted = $DiscontinueWhenDepleted;
        $product->DateAdded = $DateAddedToEasify;
        $product->WebInfoPresent = $WebInfoPresent;

        return $product;
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

    public $SKU; // Integer
    public $OurStockCode; // String
    public $ManufacturerStockCode; // String
    public $SupplierStockCode; // String
    public $EANCode; // String
    public $Description; // String
    public $CategoryId; // Int
    public $SubcategoryId; // Int
    public $ManufacturerId; // Int
    public $CostPrice; // Decimal
    public $Notes; // String
    public $StockLevel; // Int
    public $Discontinued; // Boolean
    public $MinimumStockLevel; // Int
    public $ReorderAmount; // Int
    public $ReorderWhenLow; // Boolean
    public $DateStockLevelLastUpdated; // DateTime
    public $DatePriceLastChanged; // DateTime
    public $SupplierId; // Int
    public $RetailMargin; // Double
    public $TradeMargin; // Double
    public $TaxId; // Int
    public $Published; // Boolean
    public $Allocatable; // Boolean
    public $Picture; // String
    public $HTMLDescription; // String
    Public $Weight; // Double
    Public $DateAdded; // DateTime

}

?>