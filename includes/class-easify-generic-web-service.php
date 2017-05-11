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

require_once( 'class-easify-generic-logging.php' );
require_once( 'class-easify-generic-basic-auth.php' );

/**
 * A class that implements an Easify web service to receive notifications from 
 * an Easify Server.
 * 
 * The Easify Server can authenticate with this service either by using http 
 * basic authentication (the recommended method) or by passing a pre-shared 
 * private key.
 * 
 * The Easify server will send notifications when products have been updated, 
 * these changes are propagated to the related eCommerce shop by the $shop 
 * class, which must be instantiated before calling the process() method of 
 * this class.
 * 
 * You can implement your own shop class by extending the 
 * Easify_Generic_Shop abstract class and implementing your own methods to 
 * communicate with the particular shop you are using.
 * 
 * In this case we have created an implementation Easify_WC_Shop which is a 
 * class that extends Easify_Generic_Shop to allow it to communicate with a 
 * WooCommerce shop.
 * 
 * @class       Easify_Generic_Web_Service
 * @version     4.1
 * @package     easify-woocommerce-connector
 * @author      Easify 
 */
abstract class Easify_Generic_Web_Service {

    // Factory method to be implemented in subclass...
    public abstract function create_shop();

    protected $shop;
    protected $username;
    protected $password;
    private $easify_entity_name;
    private $easify_key_value;
    private $easify_action;
    private $easify_pk;
    private $pk;
    protected $easify_server_url;    
    private $basic_auth;

    /**
     * Class constructor
     * 
     * @param type $username - The Easify Subscription username
     * @param type $password - The Easify Subscription password
     * @param type $pk - The Easify ECommerce channel private key (not used by WooCommerce plugin)
     * @return type void
     */
    function __construct($username, $password, $pk, $easify_server_url) {
        $this->username = $username;
        $this->password = $password;
        $this->pk = $pk;
        $this->easify_server_url = $easify_server_url;
        $this->basic_auth = new Easify_Generic_Basic_Auth();
    }

    /**
     * Kicks off the processing of the incoming request
     * 
     * @return type void
     */
    public function process() {
        Easify_Logging::Log("Easify Server Notification received: ");
        Easify_Logging::Log(print_r($_POST, true));

        // Get the shop from our class factory...
        $this->get_shop();

        // Validate http POST exists
        if (!$this->verify_post()) {
            return;
        }

        // Authorise incoming http credentials
        if (!$this->authorise($this->username, $this->password, $this->pk)) {
            return;
        }

        // Extract POST variables
        $this->get_post_vars();

        // Process request       
        $this->process_request();
    }

    /**
     * We get an instance of an ecommerce shop class from the class factory method
     * in the subclass.
     * 
     * @throws Exception if the shop is not an instance of Easify_Generic_Shop
     */
    private function get_shop() {
        $this->shop = $this->create_shop();

        // Make sure we've been given a valid shop class to work with...
        if (!$this->shop instanceof Easify_Generic_Shop) {
            Easify_Logging::Log("Easify_Generic_Web_Service->shop must extend Easify_Generic_Shop.");
            throw new Exception("Easify_Generic_Web_Service->shop must be initialised as a new instance of a class derived from Easify_Generic_Shop.");
        }
    }

    /**
     * Determines whether the http POST contains data
     * 
     * @return type boolean
     */
    private function verify_post() {
        return (!empty($_POST));
    }

    /**
     * Handles authorisation of the incoming POST request from the Easify Server
     * 
     * Can either use http basic authentication or a private key value.
     * 
     * Recommended to use basic authentication.
     * 
     * You can use both http basic auth and a pk if you want.
     * 
     * WooCommerce plugin uses http basic auth, PK not used.
     * 
     * @param type $username - The Easify Subscription username
     * @param type $password - The Easify Subscription password
     * @param type $pk - The Easify ECommerce Channel Private Key
     * @return type boolean
     */
    private function authorise() {
        // Check basic authentication if credentials were supplied...
        if (!empty($this->username) && !empty($this->password)) {
            /* Check basic auth credentials... 
             * This is the Easify Subscription username and password. It is entered in the 
             * Easify ECommerce Channel manager and is passed using basic http authentication
             * with the incoming notification. */
     
            // Make sure we have credentials from header
            if (!isset($this->basic_auth->username) || !isset($this->basic_auth->password)) {
                // PHP Auth not found - return 403 error
                Easify_Logging::Log("Incoming notification from Easify Server - could not get HTTP Basic Authentication values from http header. " .
                        "Make sure that PHP Basic Authentication is enabled for your website, and that your website is not set to run in PHP Safe Mode." .                        
                        "Also ensure that the appropriate re-write rules are present in .htaccess.");
                header('HTTP/1.0 403 Forbidden');
                echo 'Could not get authentication values.';
                return false;
            }             
            
            // Authenticate user...                  
            if ($this->basic_auth->username != $this->username || $this->basic_auth->password != $this->password) {                              
                // Username or password doesn't match - return 403 error                    
                Easify_Logging::Log("Incoming notification from Easify Server - invalid username or password. " .
                        "Check that the username and password for the Easify Subscription has been correctly " .
                        "entered in both the Easify ECommerce Channel Manager in Easify pro, and also in the " .
                        "settings page of the Easify plugin.");
                header('HTTP/1.0 403 Forbidden');
                echo 'Invalid username or password.';
                return false;
            }                        
        }

        // Check the PK if it was provided (not used in Easify WooCommerce plugin)...
        if (!empty($this->pk)) {
            /* Check $pk matches what we expect... 
             * This is the Easify Private Key value. It is entered in the 
             * Easify ECommerce Channel manager and is passed with the incoming 
             * notification. */
            if ($this->pk != $this->easify_pk) {
                // Username or password doesn't match - return 403 error
                Easify_Logging::Log("Incoming notification from Easify Server - invalid private key value. " .
                        "Check that the private for has been correctly " .
                        "entered in the Easify ECommerce Channel Manager in Easify pro.");
                header('HTTP/1.0 403 Forbidden');
                echo 'Invalid private key.';
                return false;
            }
        }

        return true;
    }

    /**
     * Gets the variables that were passed in the http POST by the Easify Server.
     * 
     * These determine what type of notification we have received from the Easify Server
     */
    private function get_post_vars() {
        $this->easify_entity_name = $_POST['EntityName'];
        $this->easify_key_value = $_POST['KeyValue'];
        $this->easify_action = $_POST['Action'];
        $this->easify_pk = $_POST['PrivateKey'];
    }

    /**
     * Processes the request as per the variables passed in the http POST
     */
    private function process_request() {
        Easify_Logging::Log("Easify_Generic_Web_Service->process_request()");
      
        if ($this->easify_entity_name == 'Products') {
            // Product has been added, updated, or deleted in Easify Server           
            switch ($this->easify_action) {
                case "Delete":

                    if ($this->shop->IsExistingProduct($this->easify_key_value)) {
                        // update existing product
                        Easify_Logging::Log("Easify_Generic_Web_Service.DeleteProduct(" . $this->easify_key_value . ")");
                        $this->shop->DeleteProduct($this->easify_key_value);
                    } else {
                        // product doesn't exist, log error message
                        Easify_Logging::Log("Easify_Generic_Web_Service.DeleteProduct(" . $this->easify_key_value . ") - doesn't exist");
                    }

                    break;

                case "Modified":
                    Easify_Logging::Log("Easify_Generic_Web_Service->process_request() - Product modified.");

                    // determine if we insert or update the Easify product
                    if ($this->shop->IsExistingProduct($this->easify_key_value)) {
                        // update existing product 
                        Easify_Logging::Log("Easify_Generic_Web_Service.UpdateProduct(" . $this->easify_key_value . ")");
                        $this->shop->UpdateProduct($this->easify_key_value);
                    } else {
                        // product doesn't exist, log error message
                        Easify_Logging::Log("Easify_Generic_Web_Service.UpdateProduct(" . $this->easify_key_value . ") - doesn't exist - trying insert");
                        Easify_Logging::Log("Easify_Generic_Web_Service.InsertProduct(" . $this->easify_key_value . ")");
                        $this->shop->InsertProduct($this->easify_key_value);
                    }

                    break;

                case "Added":
                    // determine if we insert or update the Easify product
                    if (!$this->shop->IsExistingProduct($this->easify_key_value)) {
                        // insert new product 
                        Easify_Logging::Log("Easify_Generic_Web_Service.InsertProduct(" . $this->easify_key_value . ")");
                        $this->shop->InsertProduct($this->easify_key_value);
                    } else {
                        // product doesn't exist, log error message
                        Easify_Logging::Log("Easify_Generic_Web_Service.InsertProduct(" . $this->easify_key_value . ") - already exists");
                    }

                    break;
            }
        }

        // Product info (image or web info) updated in Easify Server
        if ($this->easify_entity_name == 'ProductInfo') {
            switch ($this->easify_action) {
                case "Delete":
                    break;

                case "Modified":
                case "Added":
                    Easify_Logging::Log("Easify_Generic_Web_Service.UpdateProductInfo(" . $this->easify_key_value . ")");
                    $this->shop->UpdateProductInfo($this->easify_key_value);
                    break;
            }
        }

        // Tax Rates modified in Easify Server
        if ($this->easify_entity_name == 'TaxRates') {
            switch ($this->easify_action) {
                case "Delete":
                    Easify_Logging::Log("Easify_Generic_Web_Service.DeleteTaxRate(" . $this->easify_key_value . ")");
                    $this->shop->DeleteTaxRate($this->easify_key_value);
                    break;

                case "Modified":
                case "Added":
                    Easify_Logging::Log("Easify_Generic_Web_Service.UpdateTaxRate(" . $this->easify_key_value . ")");
                    $this->shop->UpdateTaxRate($this->easify_key_value);
                    break;
            }
        }
    }

}

?>