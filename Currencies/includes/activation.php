<?php

if (!defined('ABSPATH') ) {
    exit; // Exit if accessed directly.
}

if ( is_multisite() && isset( $_GET['networkwide'] ) && $_GET['networkwide'] == 1 ) {
    
    // Get all the sites in the network
    $sites = get_sites();

    // Loop through each site and run activation tasks
    foreach ( $sites as $site ) {
        set_single_site_options($site->blog_id);
    }
} else {
    set_single_site_options(get_current_blog_id());
}

function set_single_site_options($site_id) {
    
    $subsidiary_slug = get_sub_slugname($site_id);
    if (!get_sub_option($subsidiary_slug)) {
        set_sub_option_key($subsidiary_slug);
    }

    global $wpdb;
    // Get the base prefix (site-specific table prefix)
    $currencies_table = $wpdb->base_prefix . 'currencies_' . $subsidiary_slug;

    // Check if the table already exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$currencies_table'") != $currencies_table) {
        $charset_collate = $wpdb->get_charset_collate();

        // SQL to create the table
        $sql = "CREATE TABLE $currencies_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            currencies longtext NOT NULL,
            site_id bigint(20) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // Include the upgrade file
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Create the table
        dbDelta($sql);
    }
}