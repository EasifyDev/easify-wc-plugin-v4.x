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

require_once( 'class-easify-generic-web-service.php' );

/**
 * Implementation of the abstract Easify_Generic_Web_Service class to provide
 * the shop functionality for a WooCommerce system.
 * 
 * The shop class provides functionality to manipulate products in the online shop,
 * i.e. Add/Update/Delete products.
 * 
 * Because each online shop requires different code, you can subclass the 
 * Easify_Generic_Web_Service class as done here in order to provide a shop 
 * class that is compatible with your online shop. 
 * 
 * @class       Easify_WC_Web_Service
 * @version     4.0
 * @package     easify-woocommerce-connector
 * @author      Easify 
 */
class Easify_WC_Web_Service extends Easify_Generic_Web_Service {

    /**
     * Factory method to create a WooCommerce shop class...
     * 
     * Returns a WooCommerce shop class to the superclass.
     */
    public function create_shop() {
        // Create WooCommerce shop class...
        return new Easify_WC_Shop($this->easify_server_url, $this->username, $this->password);
    }
}
?>