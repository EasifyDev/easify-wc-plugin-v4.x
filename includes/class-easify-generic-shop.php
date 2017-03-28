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

require_once( 'class-easify-generic-easify-server.php' );

/**
 * Provides a template for the basic functionality required by the 
 * Easify_Generic_Web_Service class.
 * 
 * You need to inherit (extend) from this class and implement the abstract 
 * methods below to communicate with the ecommerce shop system that you are 
 * working with.
 * 
 * @class       Easify_Generic_Shop
 * @version     4.0
 * @package     easify-woocommerce-connector
 * @author      Easify  
 */
abstract class Easify_Generic_Shop {
    // $easify_server provides access to the Easify Server so that the derived 
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