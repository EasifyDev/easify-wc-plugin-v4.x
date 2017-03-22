<?php

require_once( 'class-easify-generic-easify-server.php' );

/**
 * Provides a template for the basic functionality required by the Easify_Generic_Web_Service
 * class.
 * 
 * You need to inherit (extend) from this class and implement the abstract methods
 * below to communicate with the ecommerce shop system that you are working with.
 * 
 */
abstract class Easify_Generic_Shop {
    // $easify_server provides access to the Easify Server so that the deerived 
    // shop can retrieve product information from the Easify Server.
    protected $easify_server;
    
    public function __construct($easify_server_url, $username, $password) {
        // Create an Easify Server class so that the subclasses can communicate with the 
        // Easify Server to retrieve product details etc....
        $this->easify_server = new Easify_Generic_Easify_Server($easify_server_url, $username, $password);
    }
    
    public abstract function IsExistingProduct($SKU);

    public abstract function InsertProduct($EasifySku);

    public abstract function UpdateProduct($EasifySku);

    public abstract function DeleteProduct($ProductSKU);

    public abstract function UpdateProductInfo($EasifySku);

    public abstract function UpdateTaxRate($EasifyTaxId);

    public abstract function DeleteTaxRate($EasifyTaxId);
}

?>