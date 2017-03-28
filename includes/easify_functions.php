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

require_once ( plugin_dir_path(__FILE__) . '../lib/defuse-crypto.phar' );
require_once(ABSPATH . 'wp-admin/includes/file.php');

function SendEmail($Text) {
    // utilise php email method with WOOCOMM settings
    try {
        mail(get_option('admin_email'), 'Receive Error From ' . get_option('blogname') . ' Website', $Text, 'From:' . get_option('woocommerce_email_from_address'));
    } catch (Exception $e) {
        Easify_Logging::Log('SendEmail Exception: ' . $e->getMessage() . '\n');
    }
}

function CreateSlug($Name) {
    // trim white spaces at beginning and end of alias and make lowercase
    $String = trim(strtolower($Name));

    // remove any duplicate whitespace, and ensure all characters are alphanumeric
    $String = preg_replace('/(\s|[^A-Za-z0-9\-])+/', '-', $String);

    // trim dashes at beginning and end of alias
    $String = trim($String, '-');

    // if we are left with an empty string, make a date with random number
    if (trim(str_replace('-', '', $String)) == '') {
        $String = date("Y-m-d-h-i-s") . mt_rand();
    }

    // use a unique slug name
    $i = 1;
    $ReturnString = $String;
    while (is_page($ReturnString)) {
        $ReturnString = $String . $i++;
    }
    return $ReturnString;
}

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;

function GetKey() {
    $key_file = get_home_path() . 'esfywc.cfg'; 

    if (file_exists($key_file)) {
        $keyString = file_get_contents($key_file);
        return Key::loadFromAsciiSafeString($keyString);
    } else {
        $key = Key::createNewRandomKey();
        file_put_contents($key_file, $key->saveToAsciiSafeString());
        return $key;
    }
}

function Encrypt($String) {
    $ciphertext = Crypto::encrypt($String, GetKey());
    return base64_encode($ciphertext);
}

function Decrypt($string) {
    $key = GetKey();

    return Crypto::decrypt(base64_decode($string), GetKey());
}
?>