<?php

if (!defined('ABSPATH') ) {
    exit; // Exit if accessed directly.
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
