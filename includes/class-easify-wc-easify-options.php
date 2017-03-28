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

require_once( 'easify-generic-constants.php' );

/**
 * Provides easy access to the Easify options that are stored in WordPress
 * 
 * @class       Easify_WC_Easify_Options
 * @version     4.0
 * @package     easify-woocommerce-connector
 * @author      Easify 
 */
class Easify_WC_Easify_Options {

    private $order_options;
    private $payment_options;
    private $customer_options;
    private $shipping_options;
    private $coupon_options;

    /**
     * Constructor
     * 
     * Gets Easify options from WordPress
     */
    public function __construct() {
        // Get Easify options arrays from WordPress
        $this->order_options = get_option('easify_options_orders');
        $this->payment_options = get_option('easify_options_payment');
        $this->customer_options = get_option('easify_options_customers');
        $this->shipping_options = get_option('easify_options_shipping');
        $this->coupon_options = get_option('easify_options_coupons');        
    }

    /**
     * Gets the Easify Order Type Id from WordPress options
     * 
     * @return integer
     */
    public function get_easify_order_type_id() {
        if (isset($this->order_options['easify_order_type_id'])) {
            return $this->order_options['easify_order_type_id'];
        }

        return DEFAULT_EASIFY_ORDER_TYPE_ID;
    }

    /**
     * Gets the Easify Payment Terms Id from WordPress options
     * 
     * @return integer
     */
    public function get_easify_payment_terms_id() {
        if (isset($this->payment_options['easify_payment_terms_id'])) {
            return $this->payment_options['easify_payment_terms_id'];
        }

        return DEFAULT_EASIFY_PAYMENT_TERMS_ID;
    }

    /**
     * Gets the Easify Order Status Id from WordPress options
     * 
     * @return integer
     */
    public function get_easify_order_status_id() {
        if (isset($this->order_options['easify_order_status_id'])) {
            return $this->order_options['easify_order_status_id'];
        }

        return DEFAULT_EASIFY_ORDER_STATUS_ID;
    }

    /**
     * Gets the Easify Order Comments from WordPress options
     * 
     * @return string
     */
    public function get_easify_order_comment() {
        if (isset($this->order_options['easify_order_comment'])) {
            return $this->order_options['easify_order_comment'];
        }

        return DEFAULT_EASIFY_ORDER_COMMENT;
    }

    /**
     * Gets the Easify customer type id from WordPress options
     * 
     * @return integer
     */
    public function get_easify_customer_type_id() {
        if (isset($this->customer_options['easify_customer_type_id'])) {
            return $this->customer_options['easify_customer_type_id'];
        }

        return DEFAULT_EASIFY_CUSTOMER_TYPE_ID;
    }

    /**
     * Gets the Easify customer type id from WordPress options
     * 
     * @return integer
     */
    public function get_easify_customer_relationship_id() {
        if (isset($this->customer_options['easify_customer_relationship_id'])) {
            return $this->customer_options['easify_customer_relationship_id'];
        }

        return DEFAULT_EASIFY_CUSTOMER_RELATIONSHIP_ID;
    }

    /**
     * Gets the Easify tax rates from WordPress options
     * 
     * @return array
     */
    public function get_easify_tax_rates() {
        return get_option('easify_tax_rates');
    }

    /**
     * Gets the Easify default tax id from WordPress options
     * 
     * @return integer
     */
    public function get_easify_default_tax_id() {
        $easify_tax_rates = $this->get_easify_tax_rates();

        // Iterate easify tax rates and return the one that is the default
        for ($i = 0; $i < sizeof($easify_tax_rates) - 1; $i++) {
            if (strtolower($easify_tax_rates[$i]['IsDefaultTaxCode']) == 'true') {
                return $easify_tax_rates[$i]['TaxId'];
            }
        }

        // If no default found, presume 2, Easify's default tax id for standard rate
        return DEFAULT_EASIFY_TAX_ID;
    }

    /**
     * Gets the Easify default tax rate from WordPress options
     * 
     * @return float
     */
    public function get_easify_default_tax_rate() {
        $easify_tax_rates = $this->get_easify_tax_rates();

        // Iterate easify tax rates and return the one that is the default
        for ($i = 0; $i < sizeof($easify_tax_rates) - 1; $i++) {
            if (strtolower($easify_tax_rates[$i]['IsDefaultTaxCode']) == 'true') {
                return $easify_tax_rates[$i]['Rate'];
            }
        }

        // If no default found, presume 20% standard rate
        return DEFAULT_EASIFY_TAX_RATE;
    }

    /**
     * Gets the Easify tax id for the specified tax code from WordPress options
     * 
     * @param string $code The tax code to lookup the tax id for.
     * @return integer Returns the default tax id if the specified $code was not 
     * found.
     */
    public function get_easify_tax_id_by_code($code) {
        if (!empty($code)) {
            $easify_tax_rates = $this->get_easify_tax_rates();

            for ($i = 0; $i <= sizeof($easify_tax_rates) - 1; $i++) {
                if (strtolower(trim($easify_tax_rates[$i]['Code'])) == strtolower(trim($code))) {
                    return $easify_tax_rates[$i]['TaxId'];
                }
            }
        }

        // If code not found return default tax id
        return $this->get_easify_default_tax_id();
    }

    /**
     * Gets the Easify tax rate for the specified tax code from WordPress options
     * 
     * @param string $code The tax code to lookup the tax rate for.
     * @return float Returns the default tax rate if the specified $code was not 
     * found.
     */
    public function get_easify_tax_rate_by_code($code) {
        if (!empty($code)) {
            $easify_tax_rates = $this->get_easify_tax_rates();

            for ($i = 0; $i <= sizeof($easify_tax_rates) - 1; $i++) {
                if (strtolower(trim($easify_tax_rates[$i]['Code'])) == strtolower(trim($code))) {
                    return $easify_tax_rates[$i]['Rate'];
                }
            }
        }

        // If code not found return default tax rate
        return $this->get_easify_default_tax_rate();
    }

    /**
     * Pass in a shipping method name, and this function will attempt to find 
     * the Easify Sku associated with the mapping and return it.
     * 
     * @param string $shipping_method_name
     * @return integer Returns -1 if a Sku could not be found.
     */
    public function get_easify_shipping_method_sku_by_name($shipping_method_name) {
        // If options not set, return sentinel value
        if (!isset($this->shipping_options)) {
            return ERROR_EASIFY_SHIPPING_SKU_NOT_FOUND;
        }

        // Iterate each Easify shipping method mapping to see if we can find the method we are after
        foreach ($this->shipping_options['easify_shipping_mapping'] as $easify_shipping_method_name => $easify_sku) {
            if ($easify_shipping_method_name == $shipping_method_name) {
                if (empty($easify_sku)) {
                    // If no Easify SKU set, return sentinel (sku not found)
                    Easify_Logging::Log("Easify_WC_Easify_Options::get_easify_shipping_method_sku_by_name Delivery SKU not set for " . $easify_shipping_method_name . ", skipping");
                    return ERROR_EASIFY_SHIPPING_SKU_NOT_FOUND;
                }

                // Sku found in shippign mappings, return it.
                return $easify_sku;
            }
        }
    }

    /**
     * Determines whether the specified payment method has been enabled in
     * the Easify plugin settings.
     * 
     * @param string $payment_method
     * @return boolean Returns true if the payment method is enabled.
     */
    public function is_payment_method_enabled($payment_method) {
        if (!isset($this->payment_options['easify_payment_mapping'][$payment_method]['raise'])) {
            return false;
        }

        if ($this->payment_options['easify_payment_mapping'][$payment_method]['raise'] == 'true') {
            return true;
        }

        return false;
    }

    /**
     * Pass in a payment method name and this function will return an array
     * containing the payment mapping details for that payment method.
     * 
     * @param string $payment_method_name
     * @return array Returns the payment mapping array if found, else returns 
     * NULL if no payment mapping found.
     */
    public function get_payment_mapping_by_payment_method_name($payment_method_name) {
        if (isset($this->payment_options['easify_payment_mapping'][$payment_method_name])) {
            return $this->payment_options['easify_payment_mapping'][$payment_method_name];
        }

        return NULL;
    }

    /**
     * Returns the payment comment for the specified payment method.
     * 
     * @param string $payment_method_name
     * @return string
     */
    public function get_payment_comment_by_payment_method_name($payment_method_name) {
        if (!isset($this->payment_options['easify_payment_comment'])) {
            return '';
        }

        return $this->payment_options['easify_payment_comment'];
    }

    /**
     * Gets the Easify discount sku from WordPress options
     * 
     * @return integer
     */
    public function get_easify_discount_sku() {
        if (!isset($this->coupon_options['easify_discount_sku'])) {
            return '';
        }

        return $this->coupon_options['easify_discount_sku'];
    }
    
    
    /**
     * Determines whether at least one shipping SKU has been configured in 
     * Easify options.
     * 
     * @return boolean Returns true if at least one Easify SKU has been 
     * assigned to a shipping method in Easify Options.
     */
    public function are_shipping_skus_present() {
        // If options not set, return false
        if (!isset($this->shipping_options)) {
            return false;
        }

        if (!isset($this->shipping_options['easify_shipping_mapping'])) {
            return false;
        }
        
        // Iterate each Easify shipping method mapping to see if we can find 
        // at least one sku that has been configured...
        $sku_found = false;
        foreach ($this->shipping_options['easify_shipping_mapping'] as $easify_shipping_method_name => $easify_sku) {
            if (!empty($easify_sku)) {
                $sku_found = true;
            }
        }
        
        return $sku_found;        
    }

    /**
     * Determines whether a discount SKU has been configured in 
     * Easify options.
     * 
     * @return boolean
     */
    public function is_discount_sku_present() {
        // If options not set, return false
        if (!isset($this->coupon_options)) {
            return false;
        }

        if (empty($this->coupon_options['easify_discount_sku'])) {
            return false;
        }
               
        return true;        
    }    
}

?>