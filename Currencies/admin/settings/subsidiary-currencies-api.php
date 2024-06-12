<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Function to display API Documentation page
function subsidiary_currencies_api_page() {
    $subsidiary_slug = get_sub_slugname(get_current_blog_id());
    $subsidiary_name = get_bloginfo('name');
    $api_url = network_site_url("/wp-json/currencies-api/v1/$subsidiary_slug/currencies");
    $api_key = get_sub_option($subsidiary_slug);
    ?>

    <div class="wrap">
        <h1 class="wp-heading-inline">Currencies API Documentation - <?= esc_html($subsidiary_name) ?></h1>
        <div class="card">
            <p><strong>URL:</strong> <a href="<?= esc_url($api_url) ?>" target="_blank"><?= esc_html($api_url) ?></a></p>
            <p><strong>Method:</strong> POST</p>
            <p><strong>Headers:</strong></p>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Header Name', 'text-domain'); ?></th>
                        <th><?php esc_html_e('Header Value', 'text-domain'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php esc_html_e('X-API-KEY', 'text-domain'); ?></td>
                        <td><?= esc_html($api_key) ?></td>
                    </tr>
                </tbody>
            </table>
            <p><strong>Request Body Example:</strong></p>
            <pre><code>[
                {
                    "currency": "GEL",
                    "cashBuy": 1.34,
                    "cashSell": 1.4,
                    "nonCashBuy": 1.5,
                    "nonCashSell": 1.15 
                },
                {
                    "currency": "USD",
                    "cashBuy": 1.55,
                    "cashSell": 1.3,
                    "nonCashBuy": 1.25,
                    "nonCashSell": 1.35
                }
            ]</code></pre>
        </div>
    </div>
    
    <?php
}