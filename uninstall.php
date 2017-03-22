<?php
/**
 * Easify WooCommerce Connector Uninstall
 *
 * Uninstalling Easify WooCommerce Connector options
 *
 * @author 		Easify
 * @version     1.5
 */
if( !defined('ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') )
    exit();

global $wpdb;

// Delete options
$wpdb->query( "DELETE FROM " . $wpdb->options . " WHERE option_name LIKE 'easify_%'" );
