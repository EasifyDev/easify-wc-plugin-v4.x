<?php

require_once( 'class-easify-generic-logging.php' );

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

        // setting up coms to an Easify Server 
        // NB. required to allow self signed certificates
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        // HTTPS and BASIC Authentication
        // do not verify https certificates
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        // if https is set, user basic authentication
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "$this->username:$this->password");

        // server URL 
        curl_setopt($ch, CURLOPT_URL, $this->easify_cloud_api_url);
        // return result or GET action
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // add post payload
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

//        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
//        $header = substr($result, 0, $header_size);
    //    $body = substr($result, $header_size);

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
