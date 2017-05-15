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

require_once( ABSPATH . '/wp-config.php' );
require_once( ABSPATH . '/wp-admin/includes/image.php' );
require_once( plugin_dir_path(__FILE__) . 'class-easify-wc-web-service.php' );
require_once( plugin_dir_path(__FILE__) . 'class-easify-wc-shop.php' );
require_once( plugin_dir_path(__FILE__) . 'class-easify-wc-send-order-to-easify.php' );
require_once( plugin_dir_path(__FILE__) . 'class-easify-generic-crypto.php' );

/**
 * WooCommerce Easify Plugin Class
 * 
 * Hooks into WooCommerce and waits for either of two scenarios to occur.
 * 	Scenario 1: WooCommerce Order status changed to Processing, sends order 
 *                  data back to Easify Server via Easify Cloud API.
 * 	Scenario 2: Easify sends a notification of a product update, get product 
 *                  data from Easify Server and either insert / update or delete 
 *                  the product in WooCommerce.
 * 
 * @class       Easify_WC_Plugin
 * @version     4.2
 * @package     easify-woocommerce-connector
 * @author      Easify 
 */
class Easify_WC_Plugin {
    private $easify_crypto;
           
    /**
     * __construct function.
     */
    public function __construct() {
        // Check if WooCommerce is activated - if not return without creating the hooks...
        if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            return;
        }

        $this->easify_crypto = new Easify_Generic_Crypto();
        
        // Initialise hooks
        $this->initialise_hooks();
    }

    /**
     * hooks function.
     *
     * initialise hooks
     */
    private function initialise_hooks() {
        // Easify_Logging::Log('Easify_WC_Plugin.initialise_hooks()');
        
        // Intercepts incoming web service calls from the Easify Server
        add_action('parse_request', array($this, 'receive_from_easify'));

        // Register to be notified by WooCommerce when an order is ready to be sent to Easify
        add_action('woocommerce_order_status_processing', array($this, 'send_to_easify'));
    }

    /**
     * send_to_easify function.
     *
     * send order to the Easify web service
     */
    public function send_to_easify($order_id) {
        // Easify_WC_Send_Order_To_Easify gathers all order data from WooCommerce, then sends it to Easify
        $sender = new Easify_WC_Send_Order_To_Easify($order_id, get_option('easify_username'), $this->easify_crypto->decrypt(get_option('easify_password')));
        $sender->process();
    }

    /**
     * receive_from_easify function.
     *
     * handles incoming Easify requests 
     */
    public function receive_from_easify() {
         Easify_Logging::Log('Easify_WC_Plugin.receive_from_easify() ' . $_SERVER["REQUEST_URI"]);
                
        /* Any requests to /easify or /easify/ will be notifications coming from the Easify Server 
         * i.e. product update notifications. */
        if (!$_SERVER["REQUEST_URI"]) {
            return;
        }
                        
        // Make sure URI ends with /easify or /easify/
        $request_uri = strtolower($_SERVER["REQUEST_URI"]);       
        if ( (!(substr($request_uri, -strlen( '/easify' ) ) == '/easify') == true) && (!(substr($request_uri, -strlen( '/easify/' ) ) == '/easify/') == true) ) {
            // Request is not to /easify so return
            return;
        }

        // Notification from Easify Server - process it...
        Easify_Logging::Log('Easify_WC_Plugin.receive_from_easify() - Request to /Easify/ received, creating Easify web service.');
        
        // Create Easify Web Service...
        $ews = new Easify_WC_Web_Service(get_option('easify_username'), $this->easify_crypto->decrypt(get_option('easify_password')), null, get_option('easify_web_service_location'));

        // Process the request
        Easify_Logging::Log('Easify_WC_Plugin.receive_from_easify() - Processing incoming Easify Web Service request.');        
        $ews->process();

        exit; // do not return control to WordPress
    }

}

?>
