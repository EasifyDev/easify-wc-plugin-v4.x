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

/**
 * Easify_Generic_Easify_Cloud_Api class.
 *
 * Provides generic access to the Easify Cloud API Server allowing you to 
 * easily pass a populated Easify Order model object to the Easify Cloud API
 * Server so that it can be queued for dispatch to the associated Easify Server. 
 * 
 * @class       Easify_Generic_Easify_Cloud_Api
 * @version     4.0
 * @package     easify-woocommerce-connector
 * @author      Easify
 */
class Easify_Generic_Easify_Cloud_Api {

    private $easify_cloud_api_url;
    private $username;
    private $password;

    public function __construct($easify_cloud_api_url, $username, $password) {
        $this->easify_cloud_api_url = $easify_cloud_api_url;
        $this->username = $username;
        $this->password = $password;
    }

    public function send_order_to_easify_server($model) {
        Easify_Logging::Log("send_order_to_easify_server - Start");

        // initialise PHP CURL for HTTP POST action
        $ch = curl_init();

        // Require verification of Easify CloudAPI SSL Cert...
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true); // Set false if debugging aganist self signed cert
        
        
         // HTTPS and BASIC Authentication       
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "$this->username:$this->password");

        // server URL 
        curl_setopt($ch, CURLOPT_URL, $this->easify_cloud_api_url);
        // return result or GET action
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // add POST Easify Order Model
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($model));

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // follow redirects
        // curl_setopt($ch, CURLOPT_ENCODING, "utf-8"); // handle all encodings
        curl_setopt($ch, CURLOPT_AUTOREFERER, true); // set referer on redirect

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, EASIFY_TIMEOUT); // timeout on connect (seconds)
        curl_setopt($ch, CURLOPT_TIMEOUT, EASIFY_TIMEOUT); // timeout on response (seconds)


        curl_setopt($ch, CURLINFO_HEADER_OUT, true); // grab http request header use in conjunction with curl_getinfo($ch, CURLINFO_HEADER_OUT)
        // send POST request to server, capture result
        $result = curl_exec($ch);

        // record any errors
        if ($result === false) {
            $result = 'Curl error: ' . curl_error($ch);
        }

        $header_info = curl_getinfo($ch, CURLINFO_HEADER_OUT);

        Easify_Logging::Log("send_order_to_easify_server(Request-Header):\r\n" . $header_info . "\r\n\r\n");
        Easify_Logging::Log("send_order_to_easify_server(Request-Body):\r\n" . http_build_query($model) . "\r\n\r\n");

        // close connection
        curl_close($ch);

        // log result
        Easify_Logging::Log($result);

        Easify_Logging::Log("send_order_to_easify_server - End");

        return $result;
    }

}

?>
