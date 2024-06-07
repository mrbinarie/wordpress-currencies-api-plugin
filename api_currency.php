<?php
/*
Plugin Name: Custom Currencies API
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
    $subsidiaries_table = $wpdb->base_prefix . 'subsidiaries';
    $currencies_table = $wpdb->base_prefix . 'currencies';

    // Check if the subsidiaries table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$subsidiaries_table'") != $subsidiaries_table) {
        // Create subsidiaries table
        $sql = "CREATE TABLE $subsidiaries_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            subsidiary tinytext NOT NULL,
            api_key varchar(64) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Insert default rows for each subsidiary (multisite)
        if (is_multisite()) {
            $sites = get_sites();
            foreach ($sites as $site) {
                $subsidiary = get_blog_details($site->blog_id)->blogname;
                // Default data to be inserted
                $default_data = array(
                    'subsidiary' => $subsidiary,
                    'api_key' => wp_generate_password(32, false)
                );
                $wpdb->insert($subsidiaries_table, $default_data);
            }
        }
    }

    // Check if the currencies table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$currencies_table'") != $currencies_table) {
        // Create currencies table
        $sql = "CREATE TABLE $currencies_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            currency tinytext NOT NULL,
            cashBuy float NOT NULL,
            cashSell float NOT NULL,
            nonCashBuy float NOT NULL,
            nonCashSell float NOT NULL,
            subsidiary_id mediumint(9) NOT NULL,
            PRIMARY KEY  (id),
            FOREIGN KEY (subsidiary_id) REFERENCES $subsidiaries_table(id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Register the custom REST API endpoint for POST request only
add_action('rest_api_init', function () {
    register_rest_route('currencies-api/v1', '/currencies', array(
        'methods' => 'POST',
        'callback' => 'create_update_currencies_data',
        'permission_callback' => 'check_api_key'
    ));
});

function create_update_currencies_data($request) {
    global $wpdb;
    $subsidiaries_table = $wpdb->base_prefix . 'subsidiaries';
    $currencies_table = $wpdb->base_prefix . 'currencies';

    $currency = sanitize_text_field($request->get_param('currency'));
    $cashBuy = floatval($request->get_param('cashBuy'));
    $cashSell = floatval($request->get_param('cashSell'));
    $nonCashBuy = floatval($request->get_param('nonCashBuy'));
    $nonCashSell = floatval($request->get_param('nonCashSell'));
    $api_key = sanitize_text_field($request->get_param('api_key'));

    // Verify the API key and get the subsidiary ID
    $subsidiary_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $subsidiaries_table WHERE api_key = %s", $api_key));
    if (!$subsidiary_id) {
        return new WP_Error('invalid_api_key', 'Invalid API key ', array('status' => 403));
    }

    // Ensure the currency data is unique for the subsidiary
    $existing_entry = $wpdb->get_row($wpdb->prepare("SELECT * FROM $currencies_table WHERE subsidiary_id = %d AND currency = %s", $subsidiary_id, $currency));
    
    if ($existing_entry) {
        // Update existing entry
        $wpdb->update($currencies_table, 
            array(
                'cashBuy' => $cashBuy,
                'cashSell' => $cashSell,
                'nonCashBuy' => $nonCashBuy,
                'nonCashSell' => $nonCashSell
            ), 
            array('id' => $existing_entry->id)
        );
        $message = 'Data updated successfully';
    } else {
        // Insert new entry
        $wpdb->insert($currencies_table, array(
            'currency' => $currency,
            'cashBuy' => $cashBuy,
            'cashSell' => $cashSell,
            'nonCashBuy' => $nonCashBuy,
            'nonCashSell' => $nonCashSell,
            'subsidiary_id' => $subsidiary_id,
        ));
        $message = 'Data inserted successfully';
    }

    return rest_ensure_response(array(
        'status' => 'success',
        'message' => $message,
    ));
}

function check_api_key($request) {
    global $wpdb;
    $subsidiaries_table = $wpdb->base_prefix . 'subsidiaries';
    
    $api_key = sanitize_text_field($request->get_param('api_key'));

    if (empty($api_key)) {
        return new WP_Error('missing_api_key', 'API key or subsidiary not provided', array('status' => 403));
    }

    $stored_key = $wpdb->get_var($wpdb->prepare("SELECT api_key FROM $subsidiaries_table WHERE api_key = %s", $api_key));

    if (!$stored_key) {
        return new WP_Error('invalid_api_key', 'Invalid API key', array('status' => 403));
    }

    return true;
}

// Add an admin menu item to view the data
add_action('network_admin_menu', 'register_currencies_admin_page');

function register_currencies_admin_page() {
    add_menu_page(
        'Currencies Data',     // Page title
        'Currencies',          // Menu title
        'manage_options',      // Capability
        'currencies',          // Menu slug
        'currencies_admin_page', // Function to display the page
        'dashicons-chart-line' // Icon
    );
}

function currencies_admin_page() {
    global $wpdb;
    $subsidiaries_table = $wpdb->base_prefix . 'subsidiaries';
    $currencies_table = $wpdb->base_prefix . 'currencies';
    
    // Fetch all data
    $subsidiaries = $wpdb->get_results("SELECT * FROM $subsidiaries_table");
    $currencies = $wpdb->get_results("SELECT * FROM $currencies_table");
    
    echo '<div class="wrap">';
    echo '<h1>Currencies Data</h1>';

    // Display subsidiaries data
    echo '<h2>Subsidiaries</h2>';
    echo '<table class="widefat fixed" cellspacing="0">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Subsidiary</th>';
    echo '<th>API Key</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    if ($subsidiaries) {
        foreach ($subsidiaries as $subsidiary) {
            echo '<tr>';
            echo '<td>' . esc_html($subsidiary->id) . '</td>';
            echo '<td>' . esc_html($subsidiary->subsidiary) . '</td>';
            echo '<td>' . esc_html($subsidiary->api_key) . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="3">No subsidiaries found</td></tr>';
    }
    echo '</tbody>';
    echo '</table>';

    // Display currencies data
    echo '<h2>Currencies</h2>';
    echo '<table class="widefat fixed" cellspacing="0">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Currency</th>';
    echo '<th>Cash Buy</th>';
    echo '<th>Cash Sell</th>';
    echo '<th>Non-Cash Buy</th>';
    echo '<th>Non-Cash Sell</th>';
    echo '<th>Subsidiary ID</th>'; // Adding Subsidiary ID header
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    if ($currencies) {
        foreach ($currencies as $currency) {
            echo '<tr>';
            echo '<td>' . esc_html($currency->id) . '</td>';
            echo '<td>' . esc_html($currency->currency) . '</td>';
            echo '<td>' . esc_html($currency->cashBuy) . '</td>';
            echo '<td>' . esc_html($currency->cashSell) . '</td>';
            echo '<td>' . esc_html($currency->nonCashBuy) . '</td>';
            echo '<td>' . esc_html($currency->nonCashSell) . '</td>';
            echo '<td>' . esc_html($currency->subsidiary_id) . '</td>'; // Displaying Subsidiary ID
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="7">No currencies found</td></tr>';
    }
    echo '</tbody>';
    echo '</table>';
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
