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

class Easify_Generic_Crypto {

    private $method = 'aes-256-ctr';

    private $key;

    public function __construct() {
        // Retrieve or generate the key        
        $this->key = $this->get_key();
    }

    /**
     * Encrypts (but does not authenticate) a message
     * 
     * @param string $message - plaintext message
     * @return string
     */
    public function encrypt($message) {
        $nonceSize = openssl_cipher_iv_length($this->method);
        $nonce = openssl_random_pseudo_bytes($nonceSize);

        $ciphertext = openssl_encrypt(
                $message, $this->method, $this->key, OPENSSL_RAW_DATA, $nonce
        );

        // Now let's pack the IV and the ciphertext together
        // Naively, we can just concatenate
        return base64_encode($nonce . $ciphertext);
    }

    /**
     * Decrypts (but does not verify) a message
     * 
     * @param string $message - ciphertext message
     * @return string
     */
    public function decrypt($message) {
        $message = base64_decode($message, true);

        if ($message === false) {
            throw new Exception('Encryption failure');
        }

        $nonceSize = openssl_cipher_iv_length($this->method);
        $nonce = mb_substr($message, 0, $nonceSize, '8bit');
        $ciphertext = mb_substr($message, $nonceSize, null, '8bit');

        $plaintext = openssl_decrypt(
                $ciphertext, $this->method, $this->key, OPENSSL_RAW_DATA, $nonce
        );

        return $plaintext;
    }

    /**
     * Retrieves the key from a file, or generates and saves a new random
     * key if key file not found.
     * 
     * @return type string
     */
    private function get_key() {
        $key_file = get_home_path() . 'esfysrvwc.cfg';

        if (file_exists($key_file)) {
            return file_get_contents($key_file);
        } else {
            $key = openssl_random_pseudo_bytes(64);
            file_put_contents($key_file, $key);
            return $key;
        }
    }

}
