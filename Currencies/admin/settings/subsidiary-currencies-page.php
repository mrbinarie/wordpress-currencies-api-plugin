<?php

if (!defined('ABSPATH') ) {
    exit; // Exit if accessed directly.
}

function subsidiary_currencies_page() {

    // Get the current subsidiary name & slug
    $site_id = get_current_blog_id();
    $subsidiary_name = get_bloginfo('name');
    $subsidiary_slug = get_sub_slugname($site_id);

    global $wpdb;

    $currencies_table = $wpdb->base_prefix . 'currencies_' . $subsidiary_slug;

    // Fetch currencies data specific to the current subsidiary
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $currencies_table WHERE site_id = %d",
        $site_id
    ));

    echo '<div class="wrap">';
    echo '<h1>Currencies Data - ' . esc_html($subsidiary_name) . '</h1>';// Display the data in a table
    echo '<h2>Stored Currencies</h2>';
    if ($results) {
        foreach ($results as $row) {
            $currencies_array = json_decode($row->currencies, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                echo '<table class="widefat fixed" cellspacing="0">';
                echo '<thead>';
                echo '<tr>';
                echo '<th>Currency</th>';
                echo '<th>Cash Buy</th>';
                echo '<th>Cash Sell</th>';
                echo '<th>Non-Cash Buy</th>';
                echo '<th>Non-Cash Sell</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';
                foreach ($currencies_array as $currency) {
                    echo '<tr>';
                    echo '<td>' . esc_html($currency['currency']) . '</td>';
                    echo '<td>' . esc_html($currency['cashBuy']) . '</td>';
                    echo '<td>' . esc_html($currency['cashSell']) . '</td>';
                    echo '<td>' . esc_html($currency['nonCashBuy']) . '</td>';
                    echo '<td>' . esc_html($currency['nonCashSell']) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody>';
                echo '</table>';
            } else {
                echo '<div class="error"><p>Invalid JSON data stored.</p></div>';
            }
        }
    } else {
        echo '<p>No currencies found.</p>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

/*
if (!get_site_option($subsidiary)) {
    <div class="wrap">
        <div class="notice notice-primary">
            <p>Would you like to establish a database for currencies and generate an API key?</p>
            <form method="post" action="">
                <p><input type="submit" name="submit_create_currencies_button" class="button button-primary" value="Confirm"></p>
            </form>
        </div>
    </div>
}

// Hook to handle the form submission.
add_action( 'admin_init', 'submit_create_currencies_handle' );

function submit_create_currencies_handle()
{
    if ( isset( $_POST['submit_create_currencies_button'] ))
    {
        if (is_multisite())
        {
            $subsidiary = get_bloginfo('name');
            if (!get_site_option($subsidiary))
            {
                $api_key = wp_generate_password(32, false);
                add_site_option($subsidiary, $api_key);

                wp_redirect(admin_url('admin.php?page=subsidiary_currencies&status=success'));
            }
        }
    }
}
*/