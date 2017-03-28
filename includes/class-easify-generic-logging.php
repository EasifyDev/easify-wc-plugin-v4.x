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

/**
 * Contains logging functionality for use with Easify Plugin Code
 * 
 * This is a static class and can be used anywhere that it is in scope without
 * needing to be instantiated.
 * 
 * @class       Easify_Logging
 * @version     4.0
 * @package     easify-woocommerce-connector
 * @author      Easify 
 */
class Easify_Logging {

    /**
     * A static logging method that you can use anywhere that includes this file
     * without having to instantiate the class.
     * 
     * Usage: Easify_Logging::Log("Hello, world!");
     * 
     * @param type $text - the text to be logged. If $text is an array it is 
     * rendered with print_r()
     * @return type void
     */
    public static function Log($text) {
        
        // Can't guarantee EASIFY_LOGGING_BY_DB_FLAG has been set handle case 
        // where not defined.
        $database_debug_flag = false;        
        if (defined('EASIFY_LOGGING_BY_DB_FLAG')) {
            $database_debug_flag = EASIFY_LOGGING_BY_DB_FLAG;
        }
                
        if (!$database_debug_flag && !EASIFY_LOGGING) {
            return;
        }

        // write to log file in the following format: 17-12-2012 10:15:10:000000 - $text \n
        $LogFile = fopen(dirname(dirname(__FILE__)) . '/logs/easify_log.txt', 'a');

        if (is_array($text)) {
            $text = print_r($text, true);
        }

        fwrite($LogFile, date('d-m-y H:i:s') . substr((string) microtime(), 1, 6) . ' - ' . $text . "\n");
        fclose($LogFile);
    }
}

?>
