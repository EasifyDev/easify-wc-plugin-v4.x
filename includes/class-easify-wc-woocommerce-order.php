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
 * This class provides access to the WooCommerce system allowing for read
 * access to WooCommerce orders...
 * 
 * @class       Easify_WC_WooCommerce_Order
 * @version     4.0
 * @package     easify-woocommerce-connector
 * @author      Easify 
 */
class Easify_WC_WooCommerce_Order {

    public $order_no;
    public $order;
    public $order_post_meta;
    public $customer_id;
    public $payment_method;
    public $order_details;
    public $shipping_methods;
    public $coupons;

    public function __construct($order_no) {
        $this->order_no = $order_no;

        // Get WooCommerce order
        $this->order = new WC_Order($this->order_no);

        $this->order_details = $this->order->get_items();

        // get order meta data.. containing customer data and payment data
        $this->order_post_meta = get_post_meta($this->order_no);

        // Get WooCommerce customer id 
        $this->customer_id = (int) $this->order->user_id;

        $this->payment_method = $this->order_post_meta['_payment_method'][0];

        $this->shipping_methods = $this->order->get_shipping_methods();

        $this->get_coupons($this->order);
        
        EASIFY_LOGGING::Log($this->coupons);  
    }

    private function get_coupons($woocommerce_order) {
        $this->coupons = array();
        foreach ($woocommerce_order->get_items('coupon') as $coupon_item_id => $coupon_item) {   
            array_push($this->coupons, new Easify_WC_WooCommerce_Coupon($coupon_item['name'], $coupon_item['discount_amount']));
        }
    }

}

class Easify_WC_WooCommerce_Coupon {
    public $coupon_code;
    public $coupon_value;

    public function __construct($code, $value) {
        $this->coupon_code = $code;
        $this->coupon_value = $value;
    }
}

?>