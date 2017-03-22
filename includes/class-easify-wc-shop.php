<?php

include_once(dirname(__FILE__) . '/easify_functions.php');
include_once(dirname(__FILE__) . '/class-easify-generic-shop.php');

/**
 * Provides a means for the Easify Web Service to manipulate a WooCommerce
 * shopping system.
 * 
 * Implements abstract methods from the Easify_Generic_Shop superclass as 
 * required for use by the Easify_Generic_Web_Service class.
 */
class Easify_WC_Shop extends Easify_Generic_Shop {

    /**
     * Public implementation of abstract methods in superclass
     */
    
    public function IsExistingProduct($SKU) {
        try {
            // get number of WooCommerce products that match the Easify SKU
            global $wpdb;
            $ProductId = $wpdb->get_var($wpdb->prepare(
                            "SELECT post_id FROM " . $wpdb->postmeta . " WHERE meta_key = '_sku' AND meta_value = '%s' LIMIT 1", $SKU
            ));
            return is_numeric($ProductId) ? true : false;
        } catch (Exception $e) {
            Easify_Logging::Log("IsExistingProduct Exception: " . $e->getMessage() . "\n");
        }
    }

    public function InsertProduct($EasifySku) {
        try {

            // Get product from Easify Server
            $Product = $this->easify_server->GetProductFromEasify($EasifySku);

            if ($Product->Published == 'false') {
                Easify_Logging::Log('Not published, deleting trace of product and skipping insert');
                $this->DeleteProduct($EasifySku);
                return;
            }

            // calculate price from retail margin and cost price
            $Price = round(($Product->CostPrice / (100 - $Product->RetailMargin) * 100), 4);

            // catch reserved delivery SKUs and update delivery prices
            if ($this->UpdateDeliveryPrice($Product->SKU, $Price))
                return;

            // sanitise weight value
            $Product->Weight = (isset($Product->Weight) && is_numeric($Product->Weight) ? $Product->Weight : 0);

            // get Easify product categories
            $EasifyCategories = $this->easify_server->GetEasifyProductCategories();

            // get Easify category description by the Easify category id
            $CategoryDescription = $this->easify_server->GetEasifyCategoryDescriptionFromEasifyCategoryId($EasifyCategories, $Product->CategoryId);

            // get Easify product sub categories by Easify category id
            $EasifySubCategories = $this->easify_server->GetEasifyProductSubCategoriesByCategory($Product->CategoryId);

            // get Easify sub category description by Easify sub category id
            $SubCategoryDescription = $this->easify_server->GetEasifyCategoryDescriptionFromEasifyCategoryId($EasifySubCategories, $Product->SubcategoryId);

            //Easify_Logging::Log("..Subcategory: " . $SubCategoryDescription . "..");
            // insert new category if needed and return WooCommerce category id
            $CategoryId = $this->InsertCategoryIntoWooCommerce($CategoryDescription, $CategoryDescription);

            // insert new sub category if needed and return WooCommerce sub category id
            $SubCategoryId = $this->InsertSubCategoryIntoWooCommerce($SubCategoryDescription, $SubCategoryDescription, $CategoryId);

            // create a WooCommerce stub for the new product
            $ProductStub = array(
                'post_title' => $Product->Description,
                'post_content' => '',
                'post_status' => 'publish',
                'post_type' => 'product'
            );

            // insert product record and get WooCommerce product id
            $ProductId = wp_insert_post($ProductStub);

            // link subcategory to product
            wp_set_post_terms($ProductId, array($SubCategoryId), "product_cat");

            // get WooCommerce tax class from Easify tax id
            $TaxClass = $this->GetWooCommerceTaxIdByEasifyTaxId($Product->TaxId);

            /*
              flesh out product record meta data
             */

            // pricing
            update_post_meta($ProductId, '_sku', $Product->SKU);
            update_post_meta($ProductId, '_price', $Price);
            update_post_meta($ProductId, '_regular_price', $Price);
            update_post_meta($ProductId, '_sale_price', $Price);
            update_post_meta($ProductId, '_sale_price_dates_from	', '');
            update_post_meta($ProductId, '_sale_price_dates_to', '');
            update_post_meta($ProductId, '_tax_status', 'taxable');
            update_post_meta($ProductId, '_tax_class', strtolower($TaxClass));

            // handling stock
            update_post_meta($ProductId, '_stock_status', 'instock');
            update_post_meta($ProductId, '_manage_stock', 'yes');
            update_post_meta($ProductId, '_downloadable', 'no');
            update_post_meta($ProductId, '_virtual', 'no');
            update_post_meta($ProductId, '_visibility', 'visible');
            update_post_meta($ProductId, '_sold_individually', '');
            update_post_meta($ProductId, '_manage_stock', 'yes');
            update_post_meta($ProductId, '_backorders', 'no');
            update_post_meta($ProductId, '_stock', $Product->StockLevel);

            // physical properties
            update_post_meta($ProductId, '_weight', $Product->Weight);
            update_post_meta($ProductId, '_length', '');
            update_post_meta($ProductId, '_width', '');
            update_post_meta($ProductId, '_height', '');

            // misc
            update_post_meta($ProductId, '_purchase_note', '');
            update_post_meta($ProductId, '_featured', 'no');
            update_post_meta($ProductId, '_product_attributes', 'a:0:{}'); // no attributes
            // get web info if available
            if ($Product->WebInfoPresent == 'true') {
                // get the related product info (picture and html description)
                $ProductInfo = $this->easify_server->GetProductWebInfo($EasifySku);

                // update product's web info
                $this->UpdateProductInfoInDatabase($EasifySku, $ProductInfo['Image'], $ProductInfo['Description']);
            }

            Easify_Logging::Log("..end insert..");
        } catch (Exception $e) {
            Easify_Logging::Log("Easify_WC_Shop->InsertProductIntoDatabase Exception: " . $e->getMessage());
            throw $e;
        }
    }

    public function UpdateProduct($EasifySku) {
        try {

            // get product
            if (empty($this->easify_server)){
             Easify_Logging::Log("Easify Server is NULL");               
            }

            
            $Product = $this->easify_server->GetProductFromEasify($EasifySku);

            if ($Product->Published == 'false') {
                Easify_Logging::Log('Not published, deleting trace of product and skipping update');
                $this->DeleteProduct($EasifySku);
                return;
            }

            // calculate price from retail margin and cost price
            $Price = round(($Product->CostPrice / (100 - $Product->RetailMargin) * 100), 4);

            // catch reserved delivery SKUs and update delivery prices
            if ($this->UpdateDeliveryPrice($Product->SKU, $Price))
                return;

            // sanitise weight value
            $Product->Weight = (isset($Product->Weight) && is_numeric($Product->Weight) ? $Product->Weight : 0);

            // get Easify product categories
            $EasifyCategories = $this->easify_server->GetEasifyProductCategories();

            // get Easify category description by the Easify category id
            $CategoryDescription = $this->easify_server->GetEasifyCategoryDescriptionFromEasifyCategoryId($EasifyCategories, $Product->CategoryId);

            // get Easify product sub categories by Easify category id
            $EasifySubCategories = $this->easify_server->GetEasifyProductSubCategoriesByCategory($Product->CategoryId);

            // get Easify sub category description by Easify sub category id
            $SubCategoryDescription = $this->easify_server->GetEasifyCategoryDescriptionFromEasifyCategoryId($EasifySubCategories, $Product->SubcategoryId);

            // insert new category if needed and return WooCommerce category id
            $CategoryId = $this->InsertCategoryIntoWooCommerce($CategoryDescription, $CategoryDescription);

            // insert new sub category if needed and return WooCommerce sub category id
            $SubCategoryId = $this->InsertSubCategoryIntoWooCommerce($SubCategoryDescription, $SubCategoryDescription, $CategoryId);

            // get WooCommerce product id from Easify SKU
            $ProductId = $this->GetWooCommerceProductIdFromEasifySKU($Product->SKU);

            // create a WooCommerce stub for the new product
            $ProductStub = array(
                'ID' => $ProductId,
                'post_title' => $Product->Description,
                'post_content' => '',
                'post_status' => 'publish',
                'post_type' => 'product'
            );

            // insert product record and get WooCommerce product id
            $ProductId = wp_update_post($ProductStub);

            // link subcategory to product
            wp_set_post_terms($ProductId, array($SubCategoryId), "product_cat");

            // get WooCommerce tax class from Easify tax id
            $TaxClass = $this->GetWooCommerceTaxIdByEasifyTaxId($Product->TaxId);

            // Easify_Logging::Log("UpdateProduct.TaxClass: " . $TaxClass);

            /*
              flesh out product record meta data
             */

            // pricing
            update_post_meta($ProductId, '_sku', $Product->SKU);
            update_post_meta($ProductId, '_price', $Price);
            update_post_meta($ProductId, '_regular_price', $Price);
            update_post_meta($ProductId, '_sale_price', $Price);
            update_post_meta($ProductId, '_sale_price_dates_from	', '');
            update_post_meta($ProductId, '_sale_price_dates_to', '');
            update_post_meta($ProductId, '_tax_status', 'taxable');
            update_post_meta($ProductId, '_tax_class', strtolower($TaxClass));

            // handling stock
            update_post_meta($ProductId, '_stock_status', 'instock');
            update_post_meta($ProductId, '_manage_stock', 'yes');
            update_post_meta($ProductId, '_downloadable', 'no');
            update_post_meta($ProductId, '_virtual', 'no');
            update_post_meta($ProductId, '_visibility', 'visible');
            update_post_meta($ProductId, '_sold_individually', '');
            update_post_meta($ProductId, '_manage_stock', 'yes');
            update_post_meta($ProductId, '_backorders', 'no');
            update_post_meta($ProductId, '_stock', $Product->StockLevel);

            // physical properties
            update_post_meta($ProductId, '_weight', $Product->Weight);
            update_post_meta($ProductId, '_length', '');
            update_post_meta($ProductId, '_width', '');
            update_post_meta($ProductId, '_height', '');

            // misc
            update_post_meta($ProductId, '_purchase_note', '');
            update_post_meta($ProductId, '_featured', 'no');
            update_post_meta($ProductId, '_product_attributes', 'a:0:{}'); // no attributes
            // get web info if available
            if ($Product->WebInfoPresent == 'true') {
                // get the related product info (picture and html description)
                $ProductInfo = $this->easify_server->GetProductWebInfo($EasifySku);

                // update product's web info
                $this->UpdateProductInfoInDatabase($EasifySku, $ProductInfo['Image'], $ProductInfo['Description']);
            }

            Easify_Logging::Log("..end update..");
        } catch (Exception $e) {
            Easify_Logging::Log("Easify_WC_Shop->UpdateProductInDatabase Exception: " . $e->getMessage());
            throw $e;
        }
    }

    public function DeleteProduct($ProductSKU) {
        // get the WooCommerce product id by the Easify SKU
        $ProductId = $this->GetWooCommerceProductIdFromEasifySKU($ProductSKU);
        wp_delete_post($ProductId, true);
    }

    public function UpdateProductInfo($EasifySku) {
        try {

            // get product from Easify Server
            $Product = $this->easify_server->GetProductFromEasify($EasifySku);

            if ($Product->Published == 'false') {
                Easify_Logging::Log('Not published, deleting trace of product and skipping product info update');
                $this->DeleteProduct($EasifySku);
                return;
            }

            // get web info if available
            if ($Product->WebInfoPresent == 'true') {
                // get the related product info (picture and html description)
                $ProductInfo = $this->easify_server->GetProductWebInfo($EasifySku);

                // update product's web info
                $this->UpdateProductInfoInDatabase($EasifySku, $ProductInfo['Image'], $ProductInfo['Description']);
            }
        } catch (Exception $e) {
            Easify_Logging::Log("Easify_WC_Shop->UpdateProductInfo Exception: " . $e->getMessage());
            throw $e;
        }
    }

    public function UpdateTaxRate($EasifyTaxId) {
        Easify_Logging::Log("UpdateTaxRate() - Start");

        $EasifyTax = $this->easify_server->GetEasifyTaxRates();
        $TaxCode = null;
        $TaxRate = null;
        $TaxDescription = null;

        // get Easify tax info
        for ($i = 0; $i < sizeof($EasifyTax); $i++) {
            if ($EasifyTax[$i]['TaxId'] == $EasifyTaxId) {
                $TaxCode = trim($EasifyTax[$i]['Code']);
                $TaxRate = trim($EasifyTax[$i]['Rate']);
                $TaxDescription = trim($EasifyTax[$i]['TaxDescription']);
            }
        }

        Easify_Logging::Log("UpdateTaxRate() - TaxCode: " . $TaxCode . " - TaxRate: " . $TaxRate . " - TaxDescription: " . $TaxDescription);

        global $wpdb;
        $TaxResult = $wpdb->get_row($wpdb->prepare(
                        "SELECT tax_rate_id, IFNULL(tax_rate_class, '') AS tax_rate_class, tax_rate_name, tax_rate FROM " . $wpdb->prefix . "woocommerce_tax_rates" . " WHERE tax_rate_name = '%s' LIMIT 1", $TaxDescription
        ));

        Easify_Logging::Log("UpdateTaxRate() - TaxResult: " . print_r($TaxResult, true));

        if (!empty($TaxResult->tax_rate_id)) {

            // insert tax data into WooCommerce
            $wpdb->update(
                    $wpdb->prefix . 'woocommerce_tax_rates', array(
                'tax_rate_country' => '',
                'tax_rate' => $TaxRate,
                'tax_rate_name' => $TaxDescription,
                'tax_rate_shipping' => '1',
                'tax_rate_class' => $TaxCode
                    ), array('tax_rate_id' => $TaxResult->tax_rate_id)
            );

            Easify_Logging::Log("UpdateTaxRate() - Updated existing tax rate");
        } else {
            // create a tax record in WooCommerce			
            $this->AddTaxRate($EasifyTax, $TaxCode, $TaxRate, $TaxDescription);

            Easify_Logging::Log("UpdateTaxRate() - Added new tax rate");
        }

        Easify_Logging::Log("UpdateTaxRate() - End");
    }

    public function DeleteTaxRate($EasifyTaxId) {

        $EasifyTax = $this->easify_server->GetEasifyTaxRates();
        $TaxCode = null;
        $TaxRate = null;
        $TaxDescription = null;

        // get Easify tax info
        for ($i = 0; $i < sizeof($EasifyTax); $i++) {
            if ($EasifyTax[$i]['TaxId'] == $EasifyTaxId) {
                $TaxCode = trim($EasifyTax[$i]['Code']);
                $TaxRate = trim($EasifyTax[$i]['Rate']);
                $TaxDescription = trim($EasifyTax[$i]['TaxDescription']);
            }
        }

        global $wpdb;
        $TaxClassOption = $wpdb->get_row(
                "SELECT option_value FROM " . $wpdb->options . " WHERE option_name = 'woocommerce_tax_classes' LIMIT 1"
        );

        $TaxClassList = preg_split("/\\r\\n|\\r|\\n/", $TaxClassOption->option_value);

        if (!in_array($TaxCode, $TaxClassList)) {

            for ($i = 0; $i < sizeof($TaxClassList); $i++) {
                if ($TaxClassList[$i] == $TaxCode) {
                    unset($TaxClassList[$i]);
                    $TaxClassList = implode("\r\n", $TaxClassList);

                    $wpdb->update(
                            $wpdb->options, array(
                        'option_value' => $TaxClassList
                            ), array(
                        'option_name' => 'woocommerce_tax_classes'
                            )
                    );

                    $TaxResult = $wpdb->get_row($wpdb->prepare(
                                    "SELECT tax_rate_id AS tax_rate_class FROM " . $wpdb->prefix . "woocommerce_tax_rates" . " WHERE tax_rate_name = '%s' LIMIT 1", $TaxDescription
                    ));

                    if (!empty($TaxResult->tax_rate_id)) {
                        $wpdb->delete(
                                $wpdb->prefix . 'woocommerce_tax_rates', array('tax_rate_id' => $TaxResult->tax_rate_id)
                        );
                    }

                    break;
                }
            }
        } else {
            Easify_Logging::Log("DeleteTaxRate - Tax Rate doesn't exist");
        }
    }

    
    
    /**
     * Private methods specific to WooCommerce shop
     */    
      
    private function GetWooCommerceProductIdFromEasifySKU($SKU) {
        // get WooCommerce product id from Easify SKU
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
                                "SELECT post_id FROM " . $wpdb->postmeta . " WHERE meta_key = '_sku' AND meta_value = '%s' LIMIT 1", $SKU
        ));
    }

    private function GetWooCommerceTaxIdByEasifyTaxId($EasifyTaxId) {
        Easify_Logging::Log("GetWooCommerceTaxIdByEasifyTaxId() - Start");

        global $wpdb;

        // match Easify tax id with an equivalent WooCommerce tax id 
        // if not match, add Easify tax record to WooCommerce and return WooCommerce class name
        
        $EasifyTax = $this->easify_server->GetEasifyTaxRates();
        $TaxCode = null;
        $TaxRate = null;
        $TaxDescription = null;

        // get Easify tax info
        for ($i = 0; $i < sizeof($EasifyTax); $i++) {
            if ($EasifyTax[$i]['TaxId'] == $EasifyTaxId) {
                $TaxCode = trim($EasifyTax[$i]['Code']);
                $TaxRate = trim($EasifyTax[$i]['Rate']);
                $TaxDescription = trim($EasifyTax[$i]['TaxDescription']);
            }
        }

        // get WooCommerce tax id and class name by Easify tax code
        $TaxResult = $wpdb->get_row($wpdb->prepare(
                        "SELECT tax_rate_id, IFNULL(tax_rate_class, '') AS tax_rate_class FROM " . $wpdb->prefix . "woocommerce_tax_rates" . " WHERE tax_rate_name = '%s' LIMIT 1", $TaxDescription
        ));

        // return WooCommerce tax class name if it exists, else create new and return new WooCommerce class name
        if (!empty($TaxResult->tax_rate_id) && !empty($TaxResult->tax_rate_class)) {
            return $TaxResult->tax_rate_class;
        } else {
            // create a tax record in WooCommerce			
            return $this->AddTaxRate($EasifyTax, $TaxCode, $TaxRate, $TaxDescription);
        }
    }


    private function AddTaxRate($EasifyTax, $TaxCode, $TaxRate, $TaxDescription) {
        // Easify_Logging::Log("AddTaxRate() - Start");
        // default tax class is represented as a blank string in WooComm
        $TaxClass = '';
        $TaxCode = trim($TaxCode);
        $TaxRate = trim($TaxRate);
        $TaxDescription = trim($TaxDescription);

        // check if current tax code is the Easify default
        // get list of WooCommerce existing tax classes
        global $wpdb;
        $TaxClassOption = $wpdb->get_row(
                "SELECT option_value FROM " . $wpdb->options . " WHERE option_name = 'woocommerce_tax_classes' LIMIT 1"
        );

        $TaxClassList = preg_split("/\\r\\n|\\r|\\n/", $TaxClassOption->option_value);

        if (!in_array($TaxCode, $TaxClassList)) {

            if (!empty($TaxCode)) {

                $NewTaxClassOptions = $TaxClassOption->option_value . "\r\n" . $TaxCode;

                $wpdb->update(
                        $wpdb->options, array(
                    'option_value' => $NewTaxClassOptions
                        ), array(
                    'option_name' => 'woocommerce_tax_classes'
                        )
                );
            }
        }

        // insert tax data into WooCommerce
        $wpdb->insert(
                $wpdb->prefix . 'woocommerce_tax_rates', array(
            'tax_rate_country' => '',
            'tax_rate' => $TaxRate,
            'tax_rate_name' => $TaxDescription,
            'tax_rate_shipping' => '1',
            'tax_rate_class' => $TaxCode
                )
        );

        // return class name
        return $TaxCode;
    }

    private function InsertCategoryIntoWooCommerce($Name, $Description) {
        $Term = term_exists($Name, 'product_cat');

        // if category doesn't exist, create it
        if ($Term == 0 || $Term == null) {
            $Term = wp_insert_term($Name, 'product_cat', array('description' => $Description, 'slug' => CreateSlug($Name)));
            $CategoryId = $Term['term_id'];
        } else
            $CategoryId = $Term['term_id'];

        return $CategoryId;
    }

    private function InsertSubCategoryIntoWooCommerce($Name, $Description, $ParentId) {
        // NB. if two sub categories have the same slug, Term can return NULL even though the subcategory exists
        $Term = term_exists($Name, 'product_cat', $ParentId);

        // if subcategory doesn't exist, create it
        if ($Term == 0 || $Term == null) {
            $Term = wp_insert_term($Name, 'product_cat', array('description' => $Description, 'slug' => CreateSlug($Name), 'parent' => $ParentId));
            if (is_wp_error($Term))
                Easify_Logging::Log($Term);
            if (isset($Term['term_id']))
                $SubCategoryId = $Term['term_id'];
        } else
            $SubCategoryId = $Term['term_id'];

        return $SubCategoryId;
    }

    private function UpdateDeliveryPrice($SKU, $Price) {
        // get WooCommerce Easify options from WordPress database
        $EasifyOptionsShipping = get_option('easify_options_shipping');

        // check each supported type of shipping to see if the SKU matches any that have been mapped in the WordPress Easify options
        if ($EasifyOptionsShipping['easify_shipping_mapping']['free_shipping'] == $SKU) {
            // update the minimum amount to qualify for free shipping
            $WoocommSetting = get_option('woocommerce_free_shipping_settings');
            $WoocommSetting['min_amount'] = $Price;
            update_option('woocommerce_free_shipping_settings', $WoocommSetting);

            return true;
        }

        if ($EasifyOptionsShipping['easify_shipping_mapping']['local_delivery'] == $SKU) {
            // update the local delivery flat rate fee
            $WoocommSetting = get_option('woocommerce_local_delivery_settings');
            $WoocommSetting['fee'] = $Price;
            update_option('woocommerce_local_delivery_settings', $WoocommSetting);

            return true;
        }

        if ($EasifyOptionsShipping['easify_shipping_mapping']['flat_rate'] == $SKU) {
            // update the flat rate delivery fee
            $WoocommSetting = get_option('woocommerce_flat_rate_settings');
            $WoocommSetting['cost_per_order'] = $Price;
            update_option('woocommerce_flat_rate_settings', $WoocommSetting);

            return true;
        }

        if ($EasifyOptionsShipping['easify_shipping_mapping']['international_delivery'] == $SKU) {
            // update the cost of international delivery
            $WoocommSetting = get_option('woocommerce_international_delivery_settings');
            $WoocommSetting['cost'] = $Price;
            update_option('woocommerce_international_delivery_settings', $WoocommSetting);

            return true;
        }

        // we're not dealing with shipping here, so tell the calling method to continue...
        return false;
    }

    private function UpdateProductInfoInDatabase($ProductSKU, $Picture, $HTMLDescription) {
        try {

            // get WordPress current image upload path
            $arr = wp_upload_dir();
            $ImageDirectory = $arr['path'] . '/';

            // save directory on the web server if required
            if (!is_dir($ImageDirectory)) {
                mkdir($ImageDirectory, 0777, true);
            }

            // create image file name and save image byte array
            $FileName = $ImageDirectory . $ProductSKU . ".jpg";
            $ByteArray = $Picture;
            $fp = fopen($FileName, "w");
            fwrite($fp, $ByteArray);
            fclose($fp);

            // get WooCommerce product id from Easify SKU
            $ProductId = $this->GetWooCommProductIdFromEasifySKU($ProductSKU);

            // delete old picture, if one exists
            $this->DeleteProductAttachement($ProductId);

            // get the file type of the image we've just uploaded
            $FileType = wp_check_filetype(basename($FileName), null);

            // get WordPress current image upload URL
            $UploadFolder = $arr['url'] . '/';

            // create a WooCommerce stub for product image / post attachment
            $Attachment = array(
                'guid' => $UploadFolder . basename($FileName),
                'post_mime_type' => $FileType['type'],
                'post_title' => preg_replace('/\.[^.]+$/', '', basename($FileName)),
                'post_content' => '',
                'post_status' => 'inherit'
            );

            // insert product image and get WooCommerce attachment id
            $AttachmentId = wp_insert_attachment($Attachment, $FileName, $ProductId);

            // generate the meta data for the attachment, and update the database record.
            $AttachData = wp_generate_attachment_metadata($AttachmentId, $FileName);

            // insert product image meta data 
            wp_update_attachment_metadata($AttachmentId, $AttachData);

            // link product record and product image thumbnail
            update_post_meta($ProductId, '_thumbnail_id', $AttachmentId);

            // create a WooCommerce stub for the new product
            $ProductStub = array(
                'ID' => $ProductId,
                'post_content' => $HTMLDescription
            );

            // update product record with html description
            $ProductId = wp_update_post($ProductStub);
        } catch (Exception $e) {
            Easify_Logging::Log("Easify_WC_Shop->UpdateProductInfoInDatabase Exception: " . $e->getMessage());
            throw $e;
        }
    }

    private function DeleteProductAttachement($ProductId) {
        //TDOO: Dont know why this was commented out???
        // delete post attachment
        // wp_delete_attachment($ProductId, true);
        // delete_post_thumbnail($ProductId);
        // $this->DeleteProductChildren($ProductId);
    }

    private function DeleteProductChildren($ProductId) {
        global $wpdb;
        $ids = $wpdb->get_results("SELECT post_id FROM " . $wpdb->posts . " WHERE post_parent = '" . $ProductId . "'");

        if (isset($ids)) {
            foreach ($ids as $key) {
                wp_delete_post($key->post_id, true);

                delete_post_meta($key->post_id, '_wp_attached_file');
                delete_post_meta($key->post_id, '_wp_attachment_metadata');
            }
        }
    }

}

?>