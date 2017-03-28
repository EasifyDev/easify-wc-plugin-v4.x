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

require_once ( 'class-easify-generic-easify-cloud-api.php' );
require_once ( 'class-easify-generic-easify-server.php' );
require_once ( 'class-easify-generic-easify-order-model.php' );
require_once ( 'class-easify-wc-easify-options.php' );
require_once ( 'class-easify-wc-woocommerce-order.php' );

/**
 * Sends a WooCommerce order to an Easify Server
 * 
 * This class gets a WooCommerce order, packages it into an Easify Order Model
 * object, and sends it to the Easify Cloud API Server so that the order can 
 * be queued for delivery to the relevant Easify Server.
 * 
 * @class       Easify_WC_Send_Order_To_Easify
 * @version     4.0
 * @package     easify-woocommerce-connector
 * @author      Easify 
 */
class Easify_WC_Send_Order_To_Easify {

    private $easify_username;
    private $easify_password;
    private $easify_order_model;
    private $woocommerce_order;
    private $easify_options;

    /**
     * Constructor
     * 
     * Initialises various classes that are used to get an order from WooCommerce,
     * and populate it into an Easify Order Model so that it can be sent to the 
     * Easify Cloud API to be queued for sending to the destination Easify
     * Server.
     * 
     * The Easify Order Model is just a class that represents an Easify order. 
     * It has no functionality, it is just a way of representing and storing the 
     * order data in a format that can be send to an Easify Server via the Easify 
     * Cloud API Server.
     * 
     * @param integer $order_no - The WooCommerce order number of the order.
     * @param string $username - The username of the Easify WooCommerce plugin subscription
     * @param string $password - The password of the Easify WooCommerce plugin subscription
     */
    function __construct($order_no, $username, $password) {
        $this->easify_username = $username;
        $this->easify_password = $password;

        // Instantiate a repository so we can easily get at WooCommerce order parameters
        $this->woocommerce_order = new Easify_WC_WooCommerce_Order($order_no);

        // Instantiate an Easify Order Model, we will populate this with the order
        // information and send it to the Easify Cloud API which will queue the order
        // to be sent to the relevant Easify Server.
        $this->easify_order_model = new Easify_Generic_Easify_Order_Model();

        // Create an Easify options class for easy access to Easify Options
        $this->easify_options = new Easify_WC_Easify_Options();
    }

    public function process() {
        // compile all required information and send it to the Easify Cloud API
        // to be forwarded to the relevant Easify Server.
        try {
            Easify_Logging::Log("Easify_WC_Send_Order_To_Easify Order no:" . $this->woocommerce_order->order_no);

            // Copy WooCommerce order information to Easify Order Model                        
            $this->do_order();

            // Copy WooCommerce customer details to Easify Order Model
            $this->do_customer();

            // Copy WooCommerce order details to Easify Order Model 
            $this->do_order_details();           
            
            // Add shipping to Easify Order Model
            $this->do_shipping();

            // Copy WooCommerce coupons of present to Easify Order Model 
            $this->do_order_coupons();
            
            // Add payment record to Easify Order Model
            $this->do_payment();

            // Send Easify Order Model to the Easify Server 
            if (!$this->send_order_to_easify_server()) {
                SendEmail("Order (" . $this->woocommerce_order->order_no . ") failed to submit to Easify.");
            }
        } catch (Exception $ex) {
            Easify_Logging::Log("Easify_WC_Send_Order_To_Easify Exception: " . $ex->getMessage());
            throw $ex;
        }
    }

    /**
     * Gets the WooCommerce Order and puts it into the Easify Order Model ready 
     * for it to be sent to the Easify Cloud API for delivery to the Easify Server
     */
    private function do_order() {
        // Populate Easify Order model with order information
        $this->easify_order_model->ExtOrderNo = $this->woocommerce_order->order_no;
        $this->easify_order_model->ExtCustomerId = $this->woocommerce_order->customer_id;
        $this->easify_order_model->DatePlaced = $this->get_formatted_date();
        $this->easify_order_model->StatusId = $this->easify_options->get_easify_order_status_id();

        // Determine whether paid. If payment method was not enabled in WordPress 
        // options, we don't want to set paid = true here. For example, if customer
        // is paying by COD they won't have yet paid for their order and we want
        // that reflected in the Easify order.        
        if ($this->easify_options->is_payment_method_enabled($this->woocommerce_order->payment_method)) {
            $this->easify_order_model->Paid = true;
            $this->easify_order_model->DatePaid = $this->get_formatted_date();
        } else {
            // If customer is paying later (COD, BACS, Cheque) no payment record will be
            // raised in Easify but we still need to record how they intend to pay.
            // Record payment method in internal notes to let Easify know how the 
            // customer intends to pay for the order...
            $this->easify_order_model->Paid = false;
            $this->easify_order_model->DatePaid = NULL;
            $this->easify_order_model->Notes = "Payment to follow - Payment method: " . $this->woocommerce_order->payment_method . ". ";
        }

        $this->easify_order_model->CustomerRef = "";
        $this->easify_order_model->Invoiced = true;
        $this->easify_order_model->DateInvoiced = $this->get_formatted_date();
        $this->easify_order_model->Comments = $this->easify_options->get_easify_order_comment() . " " . $this->woocommerce_order->order_no;

        if (!empty($this->woocommerce_order->order->customer_note)) {
            $this->easify_order_model->Notes .= "\r\n\r\nCustomer Notes: " . $this->woocommerce_order->order->customer_note;
        }

        $this->easify_order_model->DateOrdered = $this->get_formatted_date();
        $this->easify_order_model->DueDate = $this->get_formatted_date();
        $this->easify_order_model->DueTime = $this->get_formatted_date();
        $this->easify_order_model->Scheduled = false;
        $this->easify_order_model->Duration = 0;
        $this->easify_order_model->Priority = 0;
        $this->easify_order_model->Recurring = false;
        $this->easify_order_model->RecurTimePeriod = 0;
        $this->easify_order_model->DueDate2 = $this->get_formatted_date();
        $this->easify_order_model->DueTime2 = $this->get_formatted_date();
        $this->easify_order_model->DueDuration2 = 0;
        $this->easify_order_model->OrderType = $this->easify_options->get_easify_order_type_id();
        $this->easify_order_model->PaymentTermsId = $this->easify_options->get_easify_payment_terms_id();

        // Append footer to internal notes if present...
        if (!empty($this->easify_order_model->Notes)) {
            $this->easify_order_model->Notes .= "\r\n\r\n";
            $this->easify_order_model->Notes .= " - Comment auto generated by Easify WooCommerce plugin - " . date('d M Y \a\t H:i', time());
            $this->easify_order_model->Notes .= "\r\n______________________________";
        }
    }

    /**
     * Gets the WooCommerce Customer for the order and puts it into the Easify 
     * Order Model ready for it to be sent to the Easify Cloud API for delivery 
     * to the Easify Server
     */
    private function do_customer() {
        // Populate customer in Easify Model with customer details from WooCommerce order
        $this->easify_order_model->Customer->ExtCustomerId = $this->woocommerce_order->customer_id;
        $this->easify_order_model->Customer->CompanyName = $this->woocommerce_order->order_post_meta['_billing_company'][0];
        $this->easify_order_model->Customer->Title = "";
        $this->easify_order_model->Customer->FirstName = $this->woocommerce_order->order_post_meta['_billing_first_name'][0];
        $this->easify_order_model->Customer->Surname = $this->woocommerce_order->order_post_meta['_billing_last_name'][0];
        $this->easify_order_model->Customer->JobTitle = "";
        $this->easify_order_model->Customer->Address1 = $this->woocommerce_order->order_post_meta['_billing_address_1'][0];
        $this->easify_order_model->Customer->Address2 = $this->woocommerce_order->order_post_meta['_billing_address_2'][0];
        $this->easify_order_model->Customer->Address3 = "";
        $this->easify_order_model->Customer->Town = $this->woocommerce_order->order_post_meta['_billing_city'][0];
        $this->easify_order_model->Customer->County = $this->woocommerce_order->order_post_meta['_billing_postcode'][0];
        $this->easify_order_model->Customer->Country = $this->woocommerce_order->order_post_meta['_billing_country'][0];
        $this->easify_order_model->Customer->HomeTel = $this->woocommerce_order->order_post_meta['_billing_phone'][0];
        $this->easify_order_model->Customer->Email = $this->woocommerce_order->order_post_meta['_billing_email'][0];
        $this->easify_order_model->Customer->DeliveryFirstName = $this->woocommerce_order->order_post_meta['_shipping_first_name'][0];
        $this->easify_order_model->Customer->DeliverySurname = $this->woocommerce_order->order_post_meta['_shipping_last_name'][0];
        $this->easify_order_model->Customer->DeliveryCompanyName = $this->woocommerce_order->order_post_meta['_shipping_company'][0];
        $this->easify_order_model->Customer->DeliveryAddress1 = $this->woocommerce_order->order_post_meta['_shipping_address_1'][0];
        $this->easify_order_model->Customer->DeliveryAddress2 = $this->woocommerce_order->order_post_meta['_shipping_address_2'][0];
        $this->easify_order_model->Customer->DeliveryAddress3 = "";
        $this->easify_order_model->Customer->DeliveryTown = $this->woocommerce_order->order_post_meta['_shipping_city'][0];
        $this->easify_order_model->Customer->DeliveryCounty = $this->woocommerce_order->order_post_meta['_shipping_state'][0];
        $this->easify_order_model->Customer->DeliveryPostcode = $this->woocommerce_order->order_post_meta['_shipping_postcode'][0];
        $this->easify_order_model->Customer->DeliveryCountry = $this->woocommerce_order->order_post_meta['_shipping_country'][0];
        $this->easify_order_model->Customer->DeliveryTel = "";
        $this->easify_order_model->Customer->DeliveryEmail = "";
        $this->easify_order_model->Customer->SubscribeToNewsletter = "";

        $this->easify_order_model->Customer->CustomerTypeId = $this->easify_options->get_easify_customer_type_id();
        $this->easify_order_model->Customer->RelationshipId = $this->easify_options->get_easify_customer_relationship_id();        
        
        // Typically we don't want to overwrite the following values as they will have been set in Easify.
        // pass nulls so the values are ignored by the Easify Cloud API
        $this->easify_order_model->Customer->TradeAccount = null; // Set to null - don't want to overwrite an existing trade status
        $this->easify_order_model->Customer->CreditLimit = null; // Set to null - don't want to overwrite an existing credit limit       
        $this->easify_order_model->Customer->PaymentTermsId = null;  // Set to null - don't want to overwrite an existing payment terms setting
        

    }

    /**
     * Iterates each product in the WooCommerce order and adds it to the Easify 
     * Order Model prior to it being queued for delivery to the destination 
     * Easify Server via the Easify Cloud API.
     */
    private function do_order_details() {
        // Iterate each product in the order...
        foreach ($this->woocommerce_order->order_details as $woocommerce_product) {
            // Create a new Easify Order Details object 
            $easify_order_detail = new Easify_Order_Order_Details();

            // Copy WooCommerce order detail (product) to Easify Order Model order details    
            $easify_order_detail->Sku = $this->get_easify_sku_by_woocommerce_product_id($woocommerce_product['product_id']);
            $easify_order_detail->Qty = $woocommerce_product['qty'];
            $easify_order_detail->Price = round($woocommerce_product['line_subtotal'] / ($woocommerce_product['qty'] == 0 ? 1 : $woocommerce_product['qty']), 4);
            $easify_order_detail->Comments = '';
            $easify_order_detail->TaxId = $this->easify_options->get_easify_tax_id_by_code($woocommerce_product['tax_class']);
            $easify_order_detail->TaxRate = $this->easify_options->get_easify_tax_rate_by_code($woocommerce_product['tax_class']);
            $easify_order_detail->Spare = '';
            $easify_order_detail->ExtParentId = 0;
            $easify_order_detail->ExtOrderDetailsId = $woocommerce_product['product_id'];
            $easify_order_detail->ExtOrderNo = $this->woocommerce_order->order_no;
            $easify_order_detail->AutoAllocateStock = true;

            // Add the order detail to the Easify order model
            array_push($this->easify_order_model->OrderDetails, $easify_order_detail);
        }
    }

    
    
     /**
     * Iterates each coupon in the WooCommerce order and adds it to the Easify 
     * Order Model prior to it being queued for delivery to the destination 
     * Easify Server via the Easify Cloud API.
     */
    private function do_order_coupons() {
        // Iterate each product in the order...
        foreach ($this->woocommerce_order->coupons as $woocommerce_coupon) {
            // Create a new Easify Order Details object 
            $easify_order_detail = new Easify_Order_Order_Details();

            // Copy WooCommerce order detail (product) to Easify Order Model order details    
            $easify_order_detail->Sku =  $this->easify_options->get_easify_discount_sku(); //$this->get_easify_sku_by_woocommerce_product_id($woocommerce_product['product_id']);
            $easify_order_detail->Qty = 1;
            $easify_order_detail->Price = $woocommerce_coupon->coupon_value * -1; // Negative value for discount...
            $easify_order_detail->Comments = 'Coupon code: ' . $woocommerce_coupon->coupon_code;
            $easify_order_detail->TaxId = $this->easify_options->get_easify_default_tax_id();
            $easify_order_detail->TaxRate = $this->easify_options->get_easify_default_tax_rate();
            $easify_order_detail->Spare = '';
            $easify_order_detail->ExtParentId = 0;
            $easify_order_detail->ExtOrderNo = $this->woocommerce_order->order_no;
            $easify_order_detail->AutoAllocateStock = false;

            // Add the order detail to the Easify order model
            array_push($this->easify_order_model->OrderDetails, $easify_order_detail);
        }
    }  
    
    
    /**
     * Pass in a WooCommerce product id and this function will return the 
     * corresponding Easify product SKU.
     * 
     * @global database $wpdb
     * @param integer $woocommerce_product_id
     * @return integer
     */
    private function get_easify_sku_by_woocommerce_product_id($woocommerce_product_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
                                "SELECT meta_value FROM " . $wpdb->postmeta . " WHERE meta_key = '_sku' AND post_id = '%s' LIMIT 1", $woocommerce_product_id
        ));
    }

    
    /**
     * Gets the shipping methods from the WooCommerce order and creates an 
     * Easify order detail for each shipping method and adds it to the Easify
     * Order Model prior to it being sent to the Easify Cloud API to be queued 
     * for delivery to the destination Easify Server.   
     */
    private function do_shipping() {
        // Iterate each shipping method in the WooCommerce order...
        foreach ($this->woocommerce_order->shipping_methods as $woocommerce_shipping) {
            // If shipping has been expanded to include different instances, extract shipping method
            $wocommerce_shipping_method = $woocommerce_shipping['method_id'];
            if (strpos($wocommerce_shipping_method, ":") !== false) {
                $wocommerce_shipping_method = explode(":", $wocommerce_shipping_method)[0];
            }

            // Get the Easify SKU that is mapped to the WooCommerce shipping method
            $easify_sku = $this->easify_options->get_easify_shipping_method_sku_by_name($wocommerce_shipping_method);

            // if $easify_sku == -1 means that no shipping method has been mapped in Easify plugin settings
            if ($easify_sku > -1) {

                // Create a new Easify order detail to represent the shipping   
                $easify_order_detail = new Easify_Order_Order_Details();

                $easify_order_detail->Sku = $easify_sku;
                $easify_order_detail->Qty = 1;
                $easify_order_detail->Price = $woocommerce_shipping['cost'];
                $easify_order_detail->Comments = $woocommerce_shipping['name'];
                $easify_order_detail->TaxRate = $this->easify_options->get_easify_default_tax_rate();
                $easify_order_detail->TaxId = $this->easify_options->get_easify_default_tax_id();
                $easify_order_detail->Spare = '';
                $easify_order_detail->ExtParentId = 0;
                $easify_order_detail->ExtOrderDetailsId = 0;
                $easify_order_detail->ExtOrderNo = $this->woocommerce_order->order_no;
                $easify_order_detail->AutoAllocateStock = true;

                // Add the order detail to the Easify order model
                array_push($this->easify_order_model->OrderDetails, $easify_order_detail);
            }
        }
    }

    private function do_payment() {
        // If this payment method has not been enabled in Easify Options, do nothing    
        if (!$this->easify_options->is_payment_method_enabled($this->woocommerce_order->payment_method)) {
            return;
        }

        // Get the payment mapping details from the Easify options...
        $payment_mapping = $this->easify_options->get_payment_mapping_by_payment_method_name($this->woocommerce_order->payment_method);

        if ($payment_mapping == NULL) {
            // Use default payment mapping if no matching mapping found - i.e.
            // if WooCommerce has a payment method that we don't support.
            $payment_mapping = $this->easify_options->get_payment_mapping_by_payment_method_name('Default');
        }

        $easify_order_payment = new Easify_Order_Payments();

        $easify_order_payment->Amount = $this->woocommerce_order->order_post_meta['_order_total'][0];
        $easify_order_payment->Comments = $this->easify_options->get_payment_comment_by_payment_method_name($this->woocommerce_order->payment_method);
        $easify_order_payment->ExtOrderNo = $this->woocommerce_order->order_no;
        $easify_order_payment->PaymentAccountId = $payment_mapping['account_id'];
        $easify_order_payment->PaymentDate = $this->get_formatted_date();
        $easify_order_payment->PaymentMethodId = $payment_mapping['method_id'];
        $easify_order_payment->PaymentTypeId = 1; // should always be a sale type, default(1)
        $easify_order_payment->TransactionRef = !empty($this->woocommerce_order->order_post_meta['_transaction_id'][0]) ? $this->woocommerce_order->order_post_meta['_transaction_id'][0] : "";

        // Add the payment record to the Easify order model. Note we could add multiple 
        // payment records to the Easify Model if we wanted to i.e. The PayPal
        // amount and the PayPal trasnaction fee. WooCommerce doesn't give us the 
        // PayPal transaction fee though.
        array_push($this->easify_order_model->Payments, $easify_order_payment);
    }

    /**
     * When the WooCommerce order has been populated into the Easify Order Model,
     * this function passes the assembled Easify Order Model to the
     * Easify_Generic_Easify_Cloud_Api for it to be queued on the Easify Cloud API
     * to be sent to the destination Easify Server.
     * 
     * @return boolean Returns true on success else false
     */
    private function send_order_to_easify_server() {
        try {
            Easify_Logging::Log("Easify_WC_Send_Order_To_Easify::send_order_to_easify_server() easify_order_model:" . print_r($this->easify_order_model, true));

            // Here we send the model containing the order to the Easify Cloud API
            // for delivery to the Easify Server...
            $easify_cloud_api = new Easify_Generic_Easify_Cloud_Api(EASIFY_CLOUD_API_URI, $this->easify_username, $this->easify_password);
            $easify_cloud_api->send_order_to_easify_server($this->easify_order_model);

            Easify_Logging::Log("Easify_WC_Send_Order_To_Easify::send_order_to_easify_server() - End");
            return true;
        } catch (Exception $ex) {
            Easify_Logging::Log("Easify_WC_Send_Order_To_Easify::send_order_to_easify_server() Exception: " . $ex->getMessage());
            return false;
        }
    }

    /**
     * Helper function to format the current date to be compatible with the 
     * Easify Order Model.
     * 
     * @return date
     */
    function get_formatted_date() {
        return date('Y-m-d\Th:i:s', time());
    }

}

?>