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
 * Easify WooCommerce Connector Uninstall
 *
 * Uninstalls Easify WooCommerce Connector options
 *
 * @author      Easify
 * @version     4.0
 */
if( !defined('ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') )
    exit();

global $wpdb;

// Delete options
$wpdb->query( "DELETE FROM " . $wpdb->options . " WHERE option_name LIKE 'easify_%'" );
