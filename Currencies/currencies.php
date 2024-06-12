<?php
/*
Plugin Name: Currencies API
Description: A simple custom API to manage currencies with a database in WordPress.
Version: 1.5
Author: Your Name
*/

if (!defined('ABSPATH') ) {
    exit; // Exit if accessed directly.
}

require_once plugin_dir_path( __FILE__ ) . 'includes/restapi.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/functions.php';

if ( is_admin() ) {
    require_once plugin_dir_path( __FILE__ ) . 'admin/menus/admin-menu.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/settings/subsidiary-currencies-page.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/settings/subsidiary-currencies-api.php';
}

// Activation and deactivation hooks.
register_activation_hook( __FILE__, 'plugin_name_activate' );
register_deactivation_hook( __FILE__, 'plugin_name_deactivate' );

function plugin_name_activate() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/activation.php';
}

function plugin_name_deactivate() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/deactivation.php';
}
