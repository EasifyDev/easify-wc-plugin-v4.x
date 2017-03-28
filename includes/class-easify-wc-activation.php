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
 * Handles activation of the Easify WooCommerce Plugin
 * 
 * When you create an instance of this class, it registers an activation hook
 * in WordPress which will call the activate() method. This ensures that the 
 * WordPress database is initialised with default values for the Easify 
 * WooCommerce Plugin when the plugin is activated.
 * 
 * @class       Easify_WC_Activation
 * @version     4.0
 * @package     easify-woocommerce-connector
 * @author      Easify 
 */
class Easify_WC_Activation{
     /**
     * __construct function.
     */
    public function __construct() {
        $this->register_easify_activation_hook();        
    }
        
    /**
     * Registers the activate() method to be called when the plugin is activated...
     */
    public function register_easify_activation_hook(){      
        register_activation_hook( PLUGIN_ROOT_PATH , array( $this, "activate"));             
    }
    
    /**
     * Carries out initialisation of the Easify WC plugin i.e. making sure default
     * database options are in the database...
     */
    public function activate(){
        /* If Orders Options not present - create them. Note: we don't want to 
         * overwrite them in case the plugin was temporarily deactivated then 
         * re-activated */
        
        // Default options for orders settings
        $orderOptions = get_option('easify_options_orders');        
        if (empty($orderOptions)){
            $option = array(
                "easify_order_status_id" => "11",
                "easify_order_type_id" => "5",
                "easify_order_comment" => "Internet Order",
            );
            update_option('easify_options_orders', $option);
        }
        
        // Default options for payments settings
        $paymentOptions = get_option('easify_options_payment');        
        if (empty($paymentOptions)){
            $option = array(
                "easify_payment_comment" => "Payment via website",
                "easify_payment_terms_id" => "1",
                "easify_payment_mapping" => array(
                        "paypal" => array(
                            "method_id" => "3",
                            "account_id" => "7",
                            "raise" => "true"
                        ),
                        "sagepayform" => array(
                            "method_id" => "7",
                            "account_id" => "1",
                            "raise" => "true"
                        ),
                        "worldpay" => array(
                            "method_id" => "7",
                            "account_id" => "1",
                            "raise" => "true"
                        ),
                        "cod" => array(
                            "method_id" => "1",
                            "account_id" => "2",
                            "raise" => "false"
                        ),
                        "cheque" => array(
                            "method_id" => "2",
                            "account_id" => "1",
                            "raise" => "false"
                        ),
                        "bacs" => array(
                            "method_id" => "3",
                            "account_id" => "1",
                            "raise" => "false"
                        ),
                        "default" => array(
                            "method_id" => "8",
                            "account_id" => "1",
                            "raise" => "false"
                        )                        
                    )
            );
            update_option('easify_options_payment', $option);
        }     
      
        return;
    }
    
}

?>
