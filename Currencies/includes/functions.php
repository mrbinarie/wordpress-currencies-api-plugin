<?php

if (!defined('ABSPATH') ) {
    exit; // Exit if accessed directly.
}

// function get_sub_slug($subsidiary_slug)
// {
//     $sites = get_sites();
//     foreach($sites as $site) {
//         $site_slug = str_replace("/", "", $site->path);
//         if($site_slug === $subsidiary_slug) {
//             $result = get_blog_details($site->blog_id)->blogname;
//         }
//     }
//     return $subsidiary_slug;
// }

function get_sub_option($subsidiary_slug) {
    $prefix = "currencies_";
    return get_site_option($prefix . $subsidiary_slug);
}

function set_sub_option_key($subsidiary_slug) {
    $prefix = "currencies_";
    $api_key = wp_generate_password(32, false);
    add_site_option($prefix . $subsidiary_slug, $api_key);
}

function delete_sub_option($subsidiary_slug) {
    $prefix = "currencies_";
    delete_site_option($prefix . $subsidiary_slug);
}


function get_sub_slugname($id) {
    $site_details = get_blog_details($id);
    $sub_slugname = '';

    if($site_details) {
        $sub_slugname = str_replace("/", "", $site_details->path);
        if(!$sub_slugname) $sub_slugname = 'main';
    }
    return $sub_slugname;
}