<?php
defined('ABSPATH') || exit;

class WCPT_Product_Tester {

    /**
     * Utility to extract numeric from a cart price (like "$341.00") for CSV.
     */
    protected static function extract_numeric($val) {
        // decode & remove anything not 0-9 or dot
        $numeric = (float) preg_replace('/[^0-9\.]/', '', html_entity_decode($val));
        // Return as string with 2 decimals
        return number_format($numeric, 2, '.', '');
    }

    /**
     * Test one product (and variations if needed).
     */
    public static function test_product($product_id) {
        $product = wc_get_product($product_id);
        if (!$product) {
            return [
                'log_data' => "Invalid product ID: $product_id",
                'output'   => '<p>Invalid product ID.</p>',
                'rows'     => [],
            ];
        }

        // We'll build an HTML card for the parent, plus additional cards for variations
        ob_start(); // Capture the on-page HTML
        ?>
        <div class="wcpt-test-wrapper">
            <?php
                $parent_data = self::build_product_card($product);
                $rows = [$parent_data['csv_row']];
                $log_data = $parent_data['log'];

                echo $parent_data['html'];

                // If variable or variable-subscription, handle children
                if ($product->is_type('variable') || $product->is_type('variable-subscription')) {
                    $child_ids = $product->get_children();
                    foreach($child_ids as $cid) {
                        $variation = wc_get_product($cid);
                        if (!$variation) continue;

                        $var_data = self::build_variation_card($product, $variation);
                        $log_data .= $var_data['log'];
                        $rows[] = $var_data['csv_row'];
                        echo $var_data['html'];
                    }
                }
            ?>
        </div>
        <?php

        $html_output = ob_get_clean();

        return [
            'log_data' => $log_data,
            'output'   => $html_output,
            'rows'     => $rows,
        ];
    }

    /**
     * Build the "card" display + CSV row + log snippet for a parent product
     */
    protected static function build_product_card($product) {
        $p_id   = $product->get_id();
        $p_type = $product->get_type();
        $log    = "=== Product Tester Log ===\nProduct ID: $p_id\nProduct Type: " . ucfirst($p_type) . "\n";

        // Basic fields
        $sku  = $product->get_sku();
        $gtin = get_post_meta($p_id, '_gtin', true);

        // Price
        $price = $product->get_price();
        $reg_price = $product->get_regular_price();
        $sale_price = $product->get_sale_price();

        // Cart test
        $cart_price = '';
        $cart_sub   = '';
        $cart_total = '';
        if (is_user_logged_in() && function_exists('WC')) {
            WC()->cart->empty_cart();
            WC()->cart->add_to_cart($p_id, 1);
            $items = WC()->cart->get_cart();
            foreach($items as $c) {
                if ($c['product_id'] == $p_id) {
                    // On-page display can keep symbol
                    $cart_price = WC()->cart->get_product_price($c['data']);
                    $cart_sub   = WC()->cart->get_product_subtotal($c['data'], $c['quantity']);
                    $cart_tot   = WC()->cart->get_total(); // includes currency
                    $cart_total = $cart_tot;
                }
            }
        }
        // For CSV, we only store numeric
        $csv_cart_price  = self::extract_numeric($cart_price);
        $csv_cart_sub    = self::extract_numeric($cart_sub);
        $csv_cart_total  = self::extract_numeric($cart_total);

        // Sale dates
        $sale_start = $product->get_date_on_sale_from() ? $product->get_date_on_sale_from()->date('Y-m-d') : '';
        $sale_end   = $product->get_date_on_sale_to() ? $product->get_date_on_sale_to()->date('Y-m-d') : '';

        // Dimensions (avoid deprecation)
        $raw_dims = $product->get_dimensions(false);
        $dims     = wc_format_dimensions($raw_dims);
        if (!$dims) $dims = 'N/A';

        $weight   = $product->get_weight();
        $weight   = $weight ? $weight : '';

        $shipping = $product->get_shipping_class() ?: 'Default';
        $tax_class= $product->get_tax_class() ?: 'Standard';
        $desc     = $product->get_description();      // keep HTML
        $shortd   = $product->get_short_description(); // keep HTML
        $img_url  = wp_get_attachment_url($product->get_image_id()) ?: '';
        $slug     = $product->get_slug();
        $cats     = wc_get_product_category_list($p_id);
        $visibility = $product->get_catalog_visibility();
        $created  = $product->get_date_created() ? $product->get_date_created()->date('Y-m-d H:i:s') : '';
        $status   = $product->get_status();
        $stock    = $product->get_stock_status();
        $attrs    = json_encode($product->get_attributes());

        // Build log snippet
        $log .= "Name: " . $product->get_name() . "\nSKU: $sku\nGTIN: $gtin\nPrice: $price\n";
        $log .= "Regular Price: $reg_price\nSale Price: $sale_price\n";
        $log .= "Cart Item Price: $cart_price\nCart Item Subtotal: $cart_sub\nCart Item Total: $cart_total\n";
        $log .= "Sale Start: $sale_start\nSale End: $sale_end\nWeight: $weight\nDimensions: $dims\n";
        $log .= "Shipping Class: $shipping\nTax Class: $tax_class\nStatus: $status\nStock: $stock\n";
        $log .= "Description: $desc\nShort Description: $shortd\nFeatured Image: $img_url\n";
        $log .= "Slug: $slug\nCategories: $cats\nCatalog Visibility: $visibility\nCreated On: $created\n";

        // External product fields
        $p_url = '';
        $btn_text = '';
        if ($product->is_type('external')) {
            $p_url    = $product->get_product_url();
            $btn_text = $product->get_button_text();
            $log .= "Product URL: $p_url\nButton Text: $btn_text\n";
        }

        // Enabled, Downloadable, Virtual
        $enabled = '';
        $downloadable = '';
        $virtual = '';
        if (!$product->is_type('external')) {
            $enabled      = ($status === 'publish') ? 'Yes' : 'No';
            $downloadable = $product->is_downloadable() ? 'Yes' : 'No';
            $virtual      = $product->is_virtual() ? 'Yes' : 'No';

            $log .= "Enabled: $enabled\nDownloadable: $downloadable\nVirtual: $virtual\n";
        }

        // Subscription fields
        $sign_up_fee = '';
        $trial_str   = '';
        $sub_length  = '';
        $sub_period  = '';
        $sync_str    = '';

        if (in_array($p_type, ['subscription','variable-subscription'], true)) {
            $sign_up_fee  = get_post_meta($p_id, '_subscription_sign_up_fee', true) ?: '';
            $trial_length = get_post_meta($p_id, '_subscription_trial_length', true) ?: '';
            $trial_period = get_post_meta($p_id, '_subscription_trial_period', true) ?: '';
            $sub_length   = get_post_meta($p_id, '_subscription_length', true) ?: '';
            $sub_period   = get_post_meta($p_id, '_subscription_period', true) ?: '';
            $sync_data    = get_post_meta($p_id, '_subscription_payment_sync_date', true);
            if (!empty($sync_data) && is_array($sync_data)) {
                $sync_str = "Day: {$sync_data['day']}, Month: {$sync_data['month']}";
            }
            $trial_str = "$trial_length $trial_period";

            $log .= "Sign-up Fee: $sign_up_fee\nFree Trial: $trial_str\nSubscription Length: $sub_length\n";
            $log .= "Subscription Period: $sub_period\nSynchronization: $sync_str\n";
        }

        // Build CSV row
        $csv_row = [
            'Product ID'        => $p_id,
            'Type'              => $p_type,
            'Name'              => $product->get_name(),
            'SKU'               => $sku,
            'GTIN'              => $gtin,
            'Price'             => $price,
            'Regular Price'     => $reg_price,
            'Cart Item Price'   => $csv_cart_price,
            'Cart Item Subtotal'=> $csv_cart_sub,
            'Cart Item Total'   => $csv_cart_total,
            'Sale Price'        => $sale_price,
            'Sale Start Date'   => $sale_start,
            'Sale End Date'     => $sale_end,
            'Weight'            => $weight,
            'Dimensions'        => $dims,
            'Shipping Class'    => $shipping,
            'Tax Class'         => $tax_class,
            'Description'       => $desc,      // keep HTML
            'Short Description' => $shortd,    // keep HTML
            'Featured Image URL'=> $img_url,
            'Slug'              => $slug,
            'Categories'        => $cats,
            'Catalog Visibility'=> $visibility,
            'Created On'        => $created,
            'Status'            => $status,
            'Stock Status'      => $stock,
            'Attributes'        => $attrs,
            'Enabled'           => $enabled,
            'Downloadable'      => $downloadable,
            'Virtual'           => $virtual,
            'Permalink'         => $product->get_permalink(),
            'External URL'      => $p_url,
            'Button Text'       => $btn_text,
        ];

        if (in_array($p_type, ['subscription','variable-subscription'], true)) {
            $csv_row['Sign-up Fee']          = $sign_up_fee;
            $csv_row['Free Trial']           = $trial_str;
            $csv_row['Subscription Length']  = $sub_length;
            $csv_row['Subscription Period']  = $sub_period;
            $csv_row['Synchronization']      = $sync_str;
        }

        // HTML "card" layout
        ob_start();
        ?>
        <div class="wcpt-detail-card">
            <h2>Product #<?php echo esc_html($p_id); ?> (<?php echo ucfirst($p_type); ?>)</h2>
            <div class="wcpt-detail-cols">
                <div class="wcpt-detail-col">
                    <ul>
                        <li><strong>Name:</strong> <?php echo esc_html($product->get_name()); ?></li>
                        <li><strong>SKU:</strong> <?php echo esc_html($sku); ?></li>
                        <li><strong>GTIN:</strong> <?php echo esc_html($gtin); ?></li>
                        <li><strong>Price:</strong> <?php echo esc_html($price); ?></li>
                        <li><strong>Regular Price:</strong> <?php echo esc_html($reg_price); ?></li>
                        <li><strong>Sale Price:</strong> <?php echo esc_html($sale_price); ?></li>
                        <li><strong>Cart Price:</strong> <?php echo wp_kses_post($cart_price); ?></li>
                        <li><strong>Cart Subtotal:</strong> <?php echo wp_kses_post($cart_sub); ?></li>
                        <li><strong>Cart Total:</strong> <?php echo wp_kses_post($cart_total); ?></li>
                    </ul>
                </div>
                <div class="wcpt-detail-col">
                    <ul>
                        <li><strong>Sale Start:</strong> <?php echo esc_html($sale_start); ?></li>
                        <li><strong>Sale End:</strong> <?php echo esc_html($sale_end); ?></li>
                        <li><strong>Weight:</strong> <?php echo esc_html($weight); ?></li>
                        <li><strong>Dimensions:</strong> <?php echo esc_html($dims); ?></li>
                        <li><strong>Shipping Class:</strong> <?php echo esc_html($shipping); ?></li>
                        <li><strong>Tax Class:</strong> <?php echo esc_html($tax_class); ?></li>
                        <li><strong>Status:</strong> <?php echo esc_html($status); ?></li>
                        <li><strong>Stock Status:</strong> <?php echo esc_html($stock); ?></li>
                    </ul>
                </div>
            </div>
            <div class="wcpt-detail-cols">
                <div class="wcpt-detail-col">
                    <ul>
                        <li><strong>Slug:</strong> <?php echo esc_html($slug); ?></li>
                        <li><strong>Categories:</strong> <?php echo wp_kses_post($cats); ?></li>
                        <li><strong>Visibility:</strong> <?php echo esc_html($visibility); ?></li>
                        <li><strong>Created On:</strong> <?php echo esc_html($created); ?></li>
                        <li><strong>Enabled:</strong> <?php echo esc_html($enabled); ?></li>
                        <li><strong>Downloadable:</strong> <?php echo esc_html($downloadable); ?></li>
                        <li><strong>Virtual:</strong> <?php echo esc_html($virtual); ?></li>
                        <?php if ($product->is_type('external')): ?>
                        <li><strong>External URL:</strong> <?php echo esc_html($p_url); ?></li>
                        <li><strong>Button Text:</strong> <?php echo esc_html($btn_text); ?></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="wcpt-detail-col">
                    <?php if (in_array($p_type, ['subscription','variable-subscription'], true)): ?>
                    <ul>
                        <li><strong>Sign-up Fee:</strong> <?php echo esc_html($sign_up_fee); ?></li>
                        <li><strong>Free Trial:</strong> <?php echo esc_html($trial_str); ?></li>
                        <li><strong>Subscription Length:</strong> <?php echo esc_html($sub_length); ?></li>
                        <li><strong>Subscription Period:</strong> <?php echo esc_html($sub_period); ?></li>
                        <li><strong>Synchronization:</strong> <?php echo esc_html($sync_str); ?></li>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
            <div class="wcpt-desc-section">
                <h3>Description</h3>
                <?php echo $desc; // keep HTML ?>
                <h4>Short Description</h4>
                <?php echo $shortd; // keep HTML ?>
            </div>
        </div>
        <?php
        $html = ob_get_clean();

        return [
            'csv_row' => $csv_row,
            'log'     => $log,
            'html'    => $html,
        ];
    }

    /**
     * Build Variation "card"
     */
    protected static function build_variation_card($parent, $variation) {
        $vid = $variation->get_id();
        $log = "Variation ID: $vid\n";

        // Variation data
        $v_sku  = $variation->get_sku();
        $v_gtin = get_post_meta($vid, '_gtin', true);
        $v_price= $variation->get_price();
        $v_reg  = $variation->get_regular_price();
        $v_sale = $variation->get_sale_price();

        // Cart test
        $cart_price = '';
        $cart_sub   = '';
        $cart_total = '';
        if (is_user_logged_in() && function_exists('WC')) {
            WC()->cart->empty_cart();
            WC()->cart->add_to_cart($parent->get_id(), 1, $vid);
            $items = WC()->cart->get_cart();
            foreach($items as $it) {
                if (!empty($it['variation_id']) && $it['variation_id'] == $vid) {
                    $cart_price = WC()->cart->get_product_price($it['data']);
                    $cart_sub   = WC()->cart->get_product_subtotal($it['data'], $it['quantity']);
                    $cart_total = WC()->cart->get_total(); 
                }
            }
        }
        $csv_cart_price = self::extract_numeric($cart_price);
        $csv_cart_sub   = self::extract_numeric($cart_sub);
        $csv_cart_total = self::extract_numeric($cart_total);

        $sale_start = $variation->get_date_on_sale_from() ? $variation->get_date_on_sale_from()->date('Y-m-d') : '';
        $sale_end   = $variation->get_date_on_sale_to() ? $variation->get_date_on_sale_to()->date('Y-m-d') : '';

        // Dimensions
        $raw_dims = $variation->get_dimensions(false);
        $v_dims   = wc_format_dimensions($raw_dims);
        if (!$v_dims) $v_dims = 'N/A';
        $v_weight = $variation->get_weight() ?: '';

        // Shipping class
        $var_ship_id = $variation->get_shipping_class_id();
        if ($var_ship_id) {
            $term = get_term($var_ship_id);
            $var_ship = $term ? $term->name : '';
        } else {
            $p_ship_id = $parent->get_shipping_class_id();
            $var_ship  = $p_ship_id ? get_term($p_ship_id)->name : 'Default';
        }

        // Tax class
        $var_tax = $variation->get_tax_class();
        if (!$var_tax) {
            $p_tax = $parent->get_tax_class() ?: 'Standard';
            $var_tax = "Default ($p_tax)";
        }

        $stock_status = $variation->get_stock_status();
        $v_desc = $variation->get_description(); // keep HTML

        // Variation attributes
        $atts  = $variation->get_attributes();
        $att_str = '';
        foreach($atts as $k => $v) {
            $label = wc_attribute_label($k);
            $att_str .= "$label: $v; ";
        }
        $att_str = rtrim($att_str, '; ');

        // Enabled, Downloadable, Virtual
        $v_enabled = ($variation->get_status() === 'publish') ? 'Yes' : 'No';
        $v_download= $variation->is_downloadable() ? 'Yes' : 'No';
        $v_virtual = $variation->is_virtual() ? 'Yes' : 'No';

        // Subscription?
        $row_extras = [];
        $log_sub    = '';
        if ($parent->is_type('variable-subscription')) {
            $suf = get_post_meta($vid, '_subscription_sign_up_fee', true) ?: '';
            $trl_len = get_post_meta($vid, '_subscription_trial_length', true) ?: '';
            $trl_per = get_post_meta($vid, '_subscription_trial_period', true) ?: '';
            $sub_len = get_post_meta($vid, '_subscription_length', true) ?: '';
            $sub_per = get_post_meta($vid, '_subscription_period', true) ?: '';
            $syncd   = get_post_meta($vid, '_subscription_payment_sync_date', true);
            $sync_str= '';
            if (is_array($syncd)) {
                $sync_str = "Day: {$syncd['day']}, Month: {$syncd['month']}";
            }

            $log_sub .= "Sign-up Fee: $suf\nFree Trial: $trl_len $trl_per\nSubscription Length: $sub_len\n";
            $log_sub .= "Subscription Period: $sub_per\nSynchronization: $sync_str\n";

            $row_extras = [
                'Sign-up Fee'          => $suf,
                'Free Trial'           => "$trl_len $trl_per",
                'Subscription Length'  => $sub_len,
                'Subscription Period'  => $sub_per,
                'Synchronization'      => $sync_str,
            ];
        }

        // Log
        $log .= "SKU: $v_sku\nGTIN: $v_gtin\nPrice: $v_price\nRegular Price: $v_reg\nSale Price: $v_sale\n";
        $log .= "Cart Item Price: $cart_price\nCart Item Subtotal: $cart_sub\nCart Item Total: $cart_total\n";
        $log .= "Sale Start Date: $sale_start\nSale End Date: $sale_end\nWeight: $v_weight\nDimensions: $v_dims\n";
        $log .= "Shipping Class: $var_ship\nTax Class: $var_tax\nStock: $stock_status\nAttributes: $att_str\n";
        $log .= "Description: $v_desc\nEnabled: $v_enabled\nDownloadable: $v_download\nVirtual: $v_virtual\n";
        if ($log_sub) $log .= $log_sub;
        $log .= "\n";

        // CSV row
        $csv_row = [
            'Product ID'        => $vid,
            'Type'              => 'variation',
            'Name'              => $variation->get_name(),
            'SKU'               => $v_sku,
            'GTIN'              => $v_gtin,
            'Price'             => $v_price,
            'Regular Price'     => $v_reg,
            'Cart Item Price'   => $csv_cart_price,
            'Cart Item Subtotal'=> $csv_cart_sub,
            'Cart Item Total'   => $csv_cart_total,
            'Sale Price'        => $v_sale,
            'Sale Start Date'   => $sale_start,
            'Sale End Date'     => $sale_end,
            'Weight'            => $v_weight,
            'Dimensions'        => $v_dims,
            'Shipping Class'    => $var_ship,
            'Tax Class'         => $var_tax,
            'Stock Status'      => $stock_status,
            'Attributes'        => $att_str,
            'Description'       => $v_desc,  // keep HTML
            'Short Description' => '',
            'Featured Image URL'=> '',
            'Slug'              => '',
            'Categories'        => '',
            'Catalog Visibility'=> '',
            'Created On'        => '',
            'Status'            => $variation->get_status(),
            'Enabled'           => $v_enabled,
            'Downloadable'      => $v_download,
            'Virtual'           => $v_virtual,
            'Permalink'         => '',
            'External URL'      => '',
            'Button Text'       => '',
        ];

        // Subscription extras
        foreach($row_extras as $k => $v) {
            $csv_row[$k] = $v;
        }

        // Variation card
        ob_start();
        ?>
        <div class="wcpt-detail-card wcpt-variation-card">
            <h3>Variation #<?php echo esc_html($vid); ?></h3>
            <div class="wcpt-detail-cols">
                <div class="wcpt-detail-col">
                    <ul>
                        <li><strong>SKU:</strong> <?php echo esc_html($v_sku); ?></li>
                        <li><strong>GTIN:</strong> <?php echo esc_html($v_gtin); ?></li>
                        <li><strong>Price:</strong> <?php echo esc_html($v_price); ?></li>
                        <li><strong>Regular:</strong> <?php echo esc_html($v_reg); ?></li>
                        <li><strong>Sale:</strong> <?php echo esc_html($v_sale); ?></li>
                        <li><strong>Cart Price:</strong> <?php echo wp_kses_post($cart_price); ?></li>
                        <li><strong>Cart Subtotal:</strong> <?php echo wp_kses_post($cart_sub); ?></li>
                        <li><strong>Cart Total:</strong> <?php echo wp_kses_post($cart_total); ?></li>
                    </ul>
                </div>
                <div class="wcpt-detail-col">
                    <ul>
                        <li><strong>Sale Start:</strong> <?php echo esc_html($sale_start); ?></li>
                        <li><strong>Sale End:</strong> <?php echo esc_html($sale_end); ?></li>
                        <li><strong>Weight:</strong> <?php echo esc_html($v_weight); ?></li>
                        <li><strong>Dimensions:</strong> <?php echo esc_html($v_dims); ?></li>
                        <li><strong>Shipping Class:</strong> <?php echo esc_html($var_ship); ?></li>
                        <li><strong>Tax Class:</strong> <?php echo esc_html($var_tax); ?></li>
                        <li><strong>Stock Status:</strong> <?php echo esc_html($stock_status); ?></li>
                    </ul>
                </div>
            </div>
            <div class="wcpt-detail-cols">
                <div class="wcpt-detail-col">
                    <ul>
                        <li><strong>Enabled:</strong> <?php echo esc_html($v_enabled); ?></li>
                        <li><strong>Downloadable:</strong> <?php echo esc_html($v_download); ?></li>
                        <li><strong>Virtual:</strong> <?php echo esc_html($v_virtual); ?></li>
                        <li><strong>Attributes:</strong> <?php echo esc_html($att_str); ?></li>
                    </ul>
                </div>
                <?php if ($parent->is_type('variable-subscription')): ?>
                    <div class="wcpt-detail-col">
                        <ul>
                            <li><strong>Sign-up Fee:</strong> <?php echo esc_html($row_extras['Sign-up Fee'] ?? ''); ?></li>
                            <li><strong>Free Trial:</strong> <?php echo esc_html($row_extras['Free Trial'] ?? ''); ?></li>
                            <li><strong>Subscription Length:</strong> <?php echo esc_html($row_extras['Subscription Length'] ?? ''); ?></li>
                            <li><strong>Subscription Period:</strong> <?php echo esc_html($row_extras['Subscription Period'] ?? ''); ?></li>
                            <li><strong>Synchronization:</strong> <?php echo esc_html($row_extras['Synchronization'] ?? ''); ?></li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
            <div class="wcpt-desc-section">
                <h4>Variation Description</h4>
                <?php echo $v_desc; // keep HTML ?>
            </div>
        </div>
        <?php
        $html = ob_get_clean();

        return [
            'log'     => $log,
            'html'    => $html,
            'csv_row' => $csv_row,
        ];
    }
}
