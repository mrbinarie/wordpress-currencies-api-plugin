<?php

if (!defined('ABSPATH') ) {
    exit; // Exit if accessed directly.
}

// Hook to add the admin menu item for each subsidiary
add_action('admin_menu', 'register_subsidiary_admin_menu');

function register_subsidiary_admin_menu() {
    if (is_multisite()) {
        // Add a menu page for the current subsidiary
        add_menu_page(
            'Subsidiary Currencies',  // Page title
            'Currencies',             // Menu title
            'manage_options',         // Capability
            'subsidiary_currencies',  // Menu slug
            'subsidiary_currencies_page', // Function to display the page
            'dashicons-chart-line'    // Icon
        );

        add_submenu_page(
            'subsidiary_currencies',           // Parent slug
            'API Documentation',              // Page title
            'API Documentation',              // Menu title
            'manage_options',                 // Capability
            'subsidiary_currencies_api_doc',  // Menu slug
            'subsidiary_currencies_api_page'  // Function to display the page
        );
    }
}