<?php

/**
 * Plugin Name: Easify WooCommerce Connector
 * Plugin URI: http://www.easify.co.uk/wordpress/
 * Description: Connects Easify Business Management, EPOS (Electronic Point of Sale) and invoicing software to your WooCommerce enabled WordPress website. Allowing you to keep your online and offline shop's orders and stock control synchronised.
 * Version: 4.0
 * Author: Easify
 * Author URI: http://www.easify.co.uk/
 * Requires at least: 4.0
 * Tested up to: 4.7.2
 */

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

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Logging can be enabled either in the Easify Plugin Options (via the database)
// or in the easify-generic-constants.php file.
if (!defined('EASIFY_LOGGING_BY_DB_FLAG')) {
   // $test = get_option('easify_options_logging')['easify_logging_enabled'];
    define('EASIFY_LOGGING_BY_DB_FLAG', get_option('easify_options_logging')['easify_logging_enabled']);
}

if (!defined('PLUGIN_ROOT_PATH')) {
    define('PLUGIN_ROOT_PATH', __FILE__);
}

// Includes
require_once( 'includes/class-easify-generic-logging.php' );
require_once( 'includes/class-easify-wc-activation.php' );
require_once( 'includes/class-easify-wc-plugin.php' );
require_once( 'includes/class-easify-wc-plugin-settings-page.php' );
require_once( 'includes/easify-generic-constants.php' );

// Wire up plugin activation class
$ewci = new Easify_WC_Activation();

// Initialise the plugin for action
$ewcc = new Easify_WC_Plugin();

// Initialise settings if logged in user is admin
if (is_admin()) {
    $ewcsp = new Easify_WC__Plugin_Settings_Page();
}
?>