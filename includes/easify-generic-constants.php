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

// Constants
if (!defined('EASIFY_TIMEOUT')) {
    define('EASIFY_TIMEOUT', 600);
}
if (!defined('EASIFY_TIMEOUT_SHORT')) {
    define('EASIFY_TIMEOUT_SHORT', 25);
}

// Logging can be enabled by setting it here or via the Easify WC Plugin 
// settings.
if (!defined('EASIFY_LOGGING')) {
    define('EASIFY_LOGGING', false);
}

if (!defined('EASIFY_DISCOVERY_SERVER_ENDPOINT_URI')) {
    define('EASIFY_DISCOVERY_SERVER_ENDPOINT_URI', "https://www.easify.co.uk/api/Security/GetEasifyServerEndpoint");
}

if (!defined('EASIFY_CLOUD_API_URI')) {
   define('EASIFY_CLOUD_API_URI', "https://cloudapi.easify.co.uk/api/EasifyCloudApi");
  //  define('EASIFY_CLOUD_API_URI', "http://localhost:8081/api/EasifyCloudApi");
}

if (!defined('EASIFY_HELP_BASE_URL')) {
   define('EASIFY_HELP_BASE_URL', "https://www.easify.co.uk");
  //  define('EASIFY_HELP_BASE_URL', "http://localhost");
}


const DEFAULT_EASIFY_ORDER_TYPE_ID = 5; // Internet Order
const DEFAULT_EASIFY_PAYMENT_TERMS_ID = 1;  // Pro forma  
const DEFAULT_EASIFY_ORDER_STATUS_ID = 11;  // New Order     
const DEFAULT_EASIFY_ORDER_COMMENT = "Internet Order";
const DEFAULT_EASIFY_CUSTOMER_TYPE_ID = 1; // Not Known
const DEFAULT_EASIFY_CUSTOMER_RELATIONSHIP_ID = 3; // Active
const DEFAULT_EASIFY_TAX_ID = 2; // Standard rate
const DEFAULT_EASIFY_TAX_RATE = 20; // Standard rate
const ERROR_EASIFY_SHIPPING_SKU_NOT_FOUND = -1;


?>