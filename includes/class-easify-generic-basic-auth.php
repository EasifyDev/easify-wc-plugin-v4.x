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
/*
  This class derived from the WP BASIC Auth plugin by wokamoto, original file header
  is included below.
  Original Plugin Name: WP BASIC Auth
  Original Plugin URI: https://github.com/wokamoto/wp-basic-auth
  Original Description: Enabling this plugin allows you to set up Basic authentication on your site using your WordPress's user name and password.
  Original Author: wokamoto
  Original Version: 1.1.3
  Original Author URI: http://dogmap.jp/

  License:
  Released under the GPL license
  http://www.gnu.org/copyleft/gpl.html
  Copyright 2013-2015 wokamoto (email : wokamoto1973@gmail.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

require_once( 'class-easify-generic-logging.php' );

/**
 * This class ensures that HTTP Basic Authentication is enabled for use, and
 * provides easy access to the basic authentication username and password.
 * 
 * 
 * @class       Easify_Generic_Basic_Auth
 * @version     4.1
 * @package     easify-woocommerce-connector
 * @author      Easify 
 */
class Easify_Generic_Basic_Auth {

    public $username;
    public $password;
    
    const HTACCES_REWRITE_RULE = '
# BEGIN WP BASIC Auth
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{HTTP:Authorization} ^(.*)
RewriteRule ^(.*) - [E=HTTP_AUTHORIZATION:%1]
</IfModule>
# END WP BASIC Auth
';

    function __construct() {
        $this->get_credentials(); 
    }

    /**
     * Modifies the root .htaccess to ensure that HTTP Basic Auth 
     * parameters are enabled.
     * 
     * @return void
     */
    public function activate() {
        if (!file_exists(ABSPATH . '.htaccess')) {
            return;
        }

        $htaccess = file_get_contents(ABSPATH . '.htaccess');

        if (strpos($htaccess, self::HTACCES_REWRITE_RULE) !== false) {
            return;
        }

        file_put_contents(ABSPATH . '.htaccess', self::HTACCES_REWRITE_RULE . $htaccess);
    }

    /**
     * Removes the modifications from the .htaccess file.
     * 
     * @return void
     */
    public function deactivate() {
        if (!file_exists(ABSPATH . '.htaccess')) {
            return;
        }

        $htaccess = file_get_contents(ABSPATH . '.htaccess');

        if (strpos($htaccess, self::HTACCES_REWRITE_RULE) === false) {
            return;
        }

        file_put_contents(ABSPATH . '.htaccess', str_replace(self::HTACCES_REWRITE_RULE, '', $htaccess));
    }

    /**
     * Gets the HTTP Basic Auth credentials from the header and stores them in 
     * class variables.
     */
    public function get_credentials() {
        //nocache_headers();
                
        $usr = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : '';
        
        $pwd = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
        
        if (empty($usr) && empty($pwd)){
            $authorization = null;
            
            if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) && $_SERVER['REDIRECT_HTTP_AUTHORIZATION']) {
                Easify_Logging::Log("Easify_Generic_Basic_Auth->get_credentials() - PHP_AUTH_USER not set, getting credentials from REDIRECT_HTTP_AUTHORIZATION.");            
                $authorization = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            }
        
            if (isset($_SERVER['HTTP_AUTHORIZATION']) && $_SERVER['HTTP_AUTHORIZATION']) {
                Easify_Logging::Log("Easify_Generic_Basic_Auth->get_credentials() - PHP_AUTH_USER not set, getting credentials from HTTP_AUTHORIZATION.");            
                $authorization = $_SERVER['HTTP_AUTHORIZATION'];
            }
                    
            if (!isset($authorization))
            {
                Easify_Logging::Log("Easify_Generic_Basic_Auth->get_credentials() - no authorization ceredentials found..");             
                return;
            }
            
            list($type, $auth) = explode(' ', $authorization);
            
            Easify_Logging::Log("Easify_Generic_Basic_Auth->get_credentials() - Type: " . $type);           
            
            if (strtolower($type) === 'basic') {
                list($usr, $pwd) = explode(':', base64_decode($auth));
            }
        }

        $this->username = $usr;
        $this->password = $pwd;            
    }
}