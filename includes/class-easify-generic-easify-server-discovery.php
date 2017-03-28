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
 * Easify_Generic_Easify_Server_Discovery class.
 * 
 * This class handles obtaining the IP address and Port of the Easify Server
 * that is associated with the specified username and password. Where username
 * and password are the credentials for a valid Easify ECommerce Subscription.
 *
 * @class       Easify_Generic_Easify_Server_Discovery
 * @version     4.0
 * @package     easify-woocommerce-connector
 * @author      Easify
 */
class Easify_Generic_Easify_Server_Discovery {

    private $easify_discovery_server_url;
    private $username;
    private $password;

    public function __construct($easify_discovery_server_url, $username, $password) {
        $this->easify_discovery_server_url = $easify_discovery_server_url;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Queries the Easify API for the endpoint details of the Easify Server associated
     * with the specified username and password.
     */
    public function get_easify_server_endpoint() {
        // initialise PHP CURL for HTTP GET action
        $ch = curl_init();

        // server URL 
        curl_setopt($ch, CURLOPT_URL, $this->easify_discovery_server_url);

        // setting up coms to an Easify Server 
        // NB. required to allow self signed certificates
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        // HTTPS and BASIC Authentication
        // do not verify https certificates
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        // if https is set, user basic authentication
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "$this->username:$this->password");

        // return result or GET action
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // set timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, EASIFY_TIMEOUT_SHORT);

        // send GET request to server, capture result
        $result = curl_exec($ch);

        // record any errors
        if ($result === false) {
            $result = 'Curl error: ' . curl_error($ch);
            Easify_Logging::Log($result);

            // close connection
            curl_close($ch);
        } else {
            $result = str_replace('"', '', $result);
            Easify_Logging::Log("easify_web_service_location: " . $result);

            // close connection
            curl_close($ch);

            return $result;
        }
    }
}

?>