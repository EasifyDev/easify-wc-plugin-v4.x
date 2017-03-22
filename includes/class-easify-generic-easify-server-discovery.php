<?php

require_once( 'class-easify-generic-logging.php' );

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