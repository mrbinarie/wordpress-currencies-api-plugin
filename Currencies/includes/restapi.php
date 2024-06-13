<?php

if (!defined('ABSPATH') ) {
    exit; // Exit if accessed directly.
}

// Register the custom REST API endpoint for POST request only
add_action('rest_api_init', function () {
    // Register REST route for each subsidiary
    if (is_multisite()) {
        $sites = get_sites();
        foreach ($sites as $site) {
            $subsidiary_slug = get_sub_slugname($site->blog_id);
            register_rest_route("currencies-api/v1/$subsidiary_slug", '/currencies', array(
                'methods' => 'POST',
                'callback' => function ($request) use ($subsidiary_slug) {
                    return create_update_currencies_data($request, $subsidiary_slug);
                },
                'permission_callback' => function ($request) use ($subsidiary_slug) {
                    return check_api_key_header($request, $subsidiary_slug);
                }
            ));
        }
    }
});


function create_update_currencies_data($request, $subsidiary_slug)
{
    $api_key = $request->get_header('X-API-KEY');

    // Verify the API key
    $option_value = get_sub_option($subsidiary_slug);
    if ($api_key !== $option_value) {
        return new WP_Error('invalid_api_key', 'Invalid API key', array('status' => 403));
    }

    // Find site_id for the subsidiary
    $site_id = get_id_from_blogname($subsidiary_slug);
    if (!$site_id) {
        return new WP_Error('invalid_subsidiary', 'Invalid subsidiary', array('status' => 403));
    }

    $currencies_json = $request->get_body();

    $currencies_array = json_decode($currencies_json, true);

    // currencies data checker
    if ($currencies_array === null || json_last_error() !== JSON_ERROR_NONE) {
        return new WP_Error('invalid_json', 'Invalid JSON data', array('status' => 403));
    }

    // Check if columns exist in each row
    foreach ($currencies_array as $currency) {
        if (!isset($currency['currency']) || !isset($currency['cashBuy']) || !isset($currency['cashSell']) || !isset($currency['nonCashBuy']) || !isset($currency['nonCashSell'])) {
            // Columns are missing in the current row
            return new WP_Error('missing_columns', 'Missing columns in JSON data', array('status' => 403));
        }
    }

    global $wpdb;
    $currencies_table = $wpdb->base_prefix . 'currencies_' . $subsidiary_slug;

    // Ensure the currency data is unique for the subsidiary
    $existing_entry = $wpdb->get_row($wpdb->prepare("SELECT * FROM $currencies_table WHERE site_id = %d", $site_id));
    
    if ($existing_entry) {
        // Update existing entry
        $wpdb->update($currencies_table, 
            array(
                'currencies' => $currencies_json
            ), 
            array('id' => $existing_entry->id)
        );
        $message = 'Data updated successfully';
    } else {
        // Insert new entry
        $wpdb->insert($currencies_table, array(
            'currencies' => $currencies_json,
            'site_id' => $site_id,
        ));
        $message = 'Data inserted successfully';
    }

    return rest_ensure_response(array(
        'status' => 'success',
        'message' => $message,
    ));
}

function check_api_key_header($request, $subsidiary_slug) {
    $api_key = $request->get_header('X-API-KEY'); // Retrieve API key from request headers

    // If the option name is found, it means the API key is valid for the current subsidiary
    if ($api_key === get_sub_option($subsidiary_slug)) {
        return true;
    } else {
        return new WP_Error('invalid_api_key', 'Invalid API key', array('status' => 403));
    }
}