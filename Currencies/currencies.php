<?php
/*
Plugin Name: Currencies API
Description: A simple custom API to manage currencies with a database in WordPress.
Version: 1.5
Author: Your Name
*/

// Hook to create the custom database tables
add_action('init', 'currencies_api_activate');

function currencies_api_activate() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Table names
    $currencies_table = $wpdb->base_prefix . 'currencies';

    // Insert default rows for each subsidiary (multisite)
    if (is_multisite()) {
        $sites = get_sites();
        foreach ($sites as $site) {
            $subsidiary = get_blog_details($site->blog_id)->blogname;
            $api_key = wp_generate_password(32, false);

            if (!get_site_option($subsidiary)) {
                // Add a new option
                add_site_option($subsidiary, $api_key);
            }
        }
    }

    // Check if the currencies table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$currencies_table'") != $currencies_table) {
        // Create currencies table
        $sql = "CREATE TABLE $currencies_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            currency tinytext NOT NULL,
            option_id bigint(20) unsigned NOT NULL,
            PRIMARY KEY  (id),
            FOREIGN KEY (option_id) REFERENCES {$wpdb->base_prefix}options(option_id) ON DELETE CASCADE
        ) $charset_collate;";
    
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Register the custom REST API endpoint for POST request only
add_action('rest_api_init', function () {
    // Register REST route for each subsidiary
    if (is_multisite()) {
        $sites = get_sites();
        foreach ($sites as $site) {
            $subsidiary = get_blog_details($site->blog_id)->blogname;
            register_rest_route("currencies-api/v1/$subsidiary", '/currencies', array(
                'methods' => 'POST',
                'callback' => function ($request) use ($subsidiary) {
                    return create_update_currencies_data($request, $subsidiary);
                },
                'permission_callback' => function ($request) use ($subsidiary) {
                    return check_api_key_header($request, $subsidiary);
                }
            ));
        }
    }
});


function create_update_currencies_data($request, $subsidiary) {
    global $wpdb;
    $currencies_table = $wpdb->base_prefix . 'currencies';

    $currency_data = sanitize_text_field($request->get_param('currency'));
    $api_key = $request->get_header('api_key');

    // Verify the API key
    $option_value = get_site_option($subsidiary);
    if ($api_key !== $option_value) {
        return new WP_Error('invalid_api_key', 'Invalid API key', array('status' => 403));
    }

    // Find option_id for the subsidiary
    $option_id = $wpdb->get_var($wpdb->prepare("SELECT option_id FROM {$wpdb->base_prefix}options WHERE option_name = %s", $subsidiary));
    if (!$option_id) {
        return new WP_Error('invalid_subsidiary', 'Invalid subsidiary', array('status' => 403));
    }
    // Ensure the currency data is unique for the subsidiary
    $existing_entry = $wpdb->get_row($wpdb->prepare("SELECT * FROM $currencies_table WHERE option_id = %d AND currency = %s", $option_id, $currency_data));
    
    if ($existing_entry) {
        // Update existing entry
        $wpdb->update($currencies_table, 
            array(
                'currency' => $currency_data
            ), 
            array('id' => $existing_entry->id)
        );
        $message = 'Data updated successfully';
    } else {
        // Insert new entry
        $wpdb->insert($currencies_table, array(
            'currency' => $currency_data,
            'option_id' => $option_id,
        ));
        $message = 'Data inserted successfully';
    }

    return rest_ensure_response(array(
        'status' => 'success',
        'message' => $message,
    ));
}

function check_api_key_header($request, $subsidiary) {
    $api_key = $request->get_header('api_key'); // Retrieve API key from request headers

    // Query the options table to check if the API key exists as an option value for the current subsidiary

    // If the option name is found, it means the API key is valid for the current subsidiary
    if ($api_key === get_site_option($subsidiary)) {
        return true;
    } else {
        return new WP_Error('invalid_api_key', 'Invalid API key ', array('status' => 403));
    }
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
    }
}

function subsidiary_currencies_page() {
    global $wpdb;
    $currencies_table = $wpdb->base_prefix . 'currencies';

    // Get the current subsidiary name
    $subsidiary_name = get_bloginfo('name');
    $option_id = $wpdb->get_var($wpdb->prepare("SELECT option_id FROM {$wpdb->base_prefix}options WHERE option_name = %s", $subsidiary_name));

    // Fetch currencies data specific to the current subsidiary
    $currencies = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $currencies_table WHERE option_id = %d",
        $option_id
    ));

    echo '<div class="wrap">';

    // Display API request details
    echo '<h2>API Request Details</h2>';
    echo '<p><strong>URL:</strong> ' . "/wp-json/currencies-api/v1/$subsidiary_name/currencies" . '</p>';
    echo '<p><strong>Headers:</strong> api_key: ' . get_site_option($subsidiary_name) . '</p>';

    echo '<h1>Currencies Data for ' . esc_html($subsidiary_name) . '</h1>';

    // Display currencies data
    echo '<h2>Currencies</h2>';
    echo '<table class="widefat fixed" cellspacing="0">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Currency</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    if ($currencies) {
        foreach ($currencies as $currency) {
            echo '<tr>';
            echo '<td>' . esc_html($currency->id) . '</td>';
            echo '<td>' . esc_html($currency->currency) . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="2">No currencies found</td></tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

// -------------------- [ Register the shortcode ] -------------------- //
/* 
[currencies_data subsidiary="Your Subsidiary Name"]
*/

add_shortcode('currencies_data', 'display_currencies_data_shortcode');

// Define the shortcode function
function display_currencies_data_shortcode($atts) {
    global $wpdb;
    $subsidiaries_table = $wpdb->base_prefix . 'subsidiaries';
    $currencies_table = $wpdb->base_prefix . 'currencies';

    // Extract shortcode attributes
    $atts = shortcode_atts(
        array(
            'subsidiary' => '', // Default value if no subsidiary is specified
            'type' => '',
        ),
        $atts,
        'currencies_data'
    );

    $subsidiary_name = sanitize_text_field($atts['subsidiary']);
    $type = sanitize_text_field($atts['type']);

    // Fetch subsidiary ID from name
    $subsidiary = $wpdb->get_row($wpdb->prepare("SELECT id FROM $subsidiaries_table WHERE subsidiary = %s", $subsidiary_name));

    if (!$subsidiary) {
        return '<p>No data found for the specified subsidiary.</p>';
    }

    // Fetch currencies data for the specific subsidiary
    $currencies = $wpdb->get_results($wpdb->prepare("SELECT * FROM $currencies_table WHERE subsidiary_id = %d", $subsidiary->id));

    if (!$currencies) {
        return '<p>No currencies data found for the specified subsidiary.</p>';
    }

    // Start output buffering
    ob_start();

    ?>
    
    <div class="finca-table-rate">
        <table width="100%">
            <thead>
                <tr>
                    <th align="left">Currency</th>
                    <th align="left">Buy</th>
                    <th align="left">Sell</th>
                </tr>
            </thead>
            <tbody>
                <?php if($type === 'cash'):?>
                    <?php foreach ($currencies as $currency): ?>
                        <tr>
                            <td align="left">
                                <span class="finca-badge"><?= esc_html($currency->currency) ?></span>
                            </td>
                            <td align="left"><?= esc_html($currency->cashBuy) ?></td>
                            <td align="left"><?= esc_html($currency->cashSell) ?></td>
                        </tr>
                    <?php endforeach ?>
                <?php elseif($type === 'noncash'):?>
                    <?php foreach ($currencies as $currency): ?>
                        <tr>
                            <td align="left">
                                <span class="finca-badge"><?= esc_html($currency->currency) ?></span>
                            </td>
                            <td align="left"><?= esc_html($currency->nonCashBuy) ?></td>
                            <td align="left"><?= esc_html($currency->nonCashSell) ?></td>
                        </tr>
                    <?php endforeach ?>
                <?php endif ?>
            </tbody>
        </table>
    </div>

    <?php

    // Return the buffered content
    return ob_get_clean();
}
