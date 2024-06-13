<?php

if (!defined('ABSPATH') ) {
    exit; // Exit if accessed directly.
}

// -------------------- [ Register the shortcode ] -------------------- //
/* 
[currencies_data]
*/

add_shortcode('currencies_data', 'display_currencies_data_shortcode');

// Define the shortcode function
function display_currencies_data_shortcode($atts)
{
    global $wpdb;
    $site_id = get_current_blog_id();
    $subsidiary_slug = get_sub_slugname($site_id);
    $currencies_table = $wpdb->base_prefix . 'currencies_' . $subsidiary_slug;

    // Check subsidiary table
    if($wpdb->get_var("SHOW TABLES LIKE '$currencies_table'") != $currencies_table) {
        return '<p>The currencies API plugin is not activated.</p>';
    }

    // Fetch the currencies column (containing JSON data) for the specific subsidiary
    $result = $wpdb->get_row($wpdb->prepare("SELECT currencies FROM $currencies_table WHERE site_id = %d", $site_id));

    if (!$result) {
        return '<p>No currencies data found for the specified subsidiary.</p>';
    }

    // Decode the JSON data
    $currencies = json_decode($result->currencies);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return '<p>Failed to decode JSON data.</p>';
    }

    // Start output buffering
    ob_start();

    ?>

    [fusion_tabs design="classic" layout="horizontal" justified="yes" alignment="start" sticky_tabs="no" sticky_tabs_offset="" icon="" icon_position="" icon_size="" hide_on_mobile="small-visibility,medium-visibility,large-visibility" class="" id="" margin_top_medium="" margin_right_medium="" margin_bottom_medium="" margin_left_medium="" margin_top_small="" margin_right_small="" margin_bottom_small="" margin_left_small="" margin_top="" margin_right="" margin_bottom="" margin_left="" title_tag="h4" fusion_font_family_title_font="" fusion_font_variant_title_font="" title_font_size="" title_line_height="" title_letter_spacing="" title_text_transform="" title_padding_top_medium="" title_padding_right_medium="" title_padding_bottom_medium="" title_padding_left_medium="" title_padding_top_small="" title_padding_right_small="" title_padding_bottom_small="" title_padding_left_small="" title_padding_top="" title_padding_right="" title_padding_bottom="" title_padding_left="" content_padding_top_medium="" content_padding_right_medium="" content_padding_bottom_medium="" content_padding_left_medium="" content_padding_top_small="" content_padding_right_small="" content_padding_bottom_small="" content_padding_left_small="" content_padding_top="" content_padding_right="" content_padding_bottom="" content_padding_left="" title_border_radius_top_left="" title_border_radius_top_right="" title_border_radius_bottom_right="" title_border_radius_bottom_left="" active_border_color="" hue="" saturation="" lightness="" alpha="" bordercolor="" backgroundcolor="" inactivecolor="" title_active_text_color="" title_text_color="" icon_active_color="" icon_color="" mobile_mode="" mobile_sticky_tabs="" parent_dynamic_content=""]
        [fusion_tab title="Cash" icon="" icon_active_color="" hue="" saturation="" lightness="" alpha="" icon_color=""]

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
                    <?php foreach ($currencies as $currency): ?>
                        <tr>
                            <td align="left">
                                <span class="finca-badge"><?= esc_html($currency->currency) ?></span>
                            </td>
                            <td align="left"><?= esc_html($currency->cashBuy) ?></td>
                            <td align="left"><?= esc_html($currency->cashSell) ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>

        [/fusion_tab]
        [fusion_tab title="nonCash" icon="" icon_active_color="" hue="" saturation="" lightness="" alpha="" icon_color=""]

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
                    <?php foreach ($currencies as $currency): ?>
                        <tr>
                            <td align="left">
                                <span class="finca-badge"><?= esc_html($currency->currency) ?></span>
                            </td>
                            <td align="left"><?= esc_html($currency->nonCashBuy) ?></td>
                            <td align="left"><?= esc_html($currency->nonCashSell) ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>

        [/fusion_tab]
    [/fusion_tabs]

    <?php

    // Return the buffered content
    return do_shortcode(ob_get_clean());
}
