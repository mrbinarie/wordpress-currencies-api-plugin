<?php

if (!defined('ABSPATH') ) {
    exit; // Exit if accessed directly.
}

if ( is_multisite() && isset( $_GET['networkwide'] ) && $_GET['networkwide'] == 1 ) {
    
    // Get all the sites in the network
    $sites = get_sites();

    // Loop through each site and run activation tasks
    foreach ( $sites as $site ) {
        $subsidiary_slug = get_sub_slugname($site->blog_id);
        if (get_sub_option($subsidiary_slug))
        {
            delete_sub_option($subsidiary_slug);
        }
    }
} else {
    $subsidiary_slug = get_sub_slugname(get_current_blog_id());
    if (get_sub_option($subsidiary_slug))
    {
        delete_sub_option($subsidiary_slug);
    }
}