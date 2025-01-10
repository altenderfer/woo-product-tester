<?php
defined('ABSPATH') || exit;

class WCPT_Admin {

    public function init() {
        add_action('admin_menu', [$this, 'add_admin_menu'], 20);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // AJAX for product search
        add_action('wp_ajax_wcpt_search_products', [$this, 'ajax_search_products']);

        // Single product tester
        add_action('wp_ajax_wcpt_test_single_product', [$this, 'ajax_test_single_product']);
    }

    public function add_admin_menu() {
        // Only single product tester now
        add_submenu_page(
            'woocommerce',
            __('Product Tester', 'woo-product-tester'),
            __('Product Tester', 'woo-product-tester'),
            'manage_options',
            'wcpt-product-tester',
            [$this, 'render_single_product_page']
        );
    }

    public function enqueue_admin_assets($hook_suffix) {
        if ($hook_suffix !== 'woocommerce_page_wcpt-product-tester') {
            return;
        }

        // Material Icons
        wp_enqueue_style(
            'wcpt-material-icons',
            'https://fonts.googleapis.com/icon?family=Material+Icons',
            [],
            null
        );

        // Awesomplete CSS
        wp_enqueue_style(
            'wcpt-awesomplete-css',
            WCPT_PLUGIN_URL . 'assets/css/awesomplete.css',
            [],
            WCPT_VERSION
        );

        // Admin CSS
        wp_enqueue_style(
            'wcpt-admin-css',
            WCPT_PLUGIN_URL . 'assets/css/wcpt-admin.css',
            [],
            WCPT_VERSION
        );

        // Awesomplete JS
        wp_enqueue_script(
            'wcpt-awesomplete-js',
            WCPT_PLUGIN_URL . 'assets/js/awesomplete.min.js',
            [],
            WCPT_VERSION,
            true
        );

        // Admin JS
        wp_enqueue_script(
            'wcpt-admin-js',
            WCPT_PLUGIN_URL . 'assets/js/wcpt-admin.js',
            ['jquery','wcpt-awesomplete-js'],
            WCPT_VERSION,
            true
        );

        wp_localize_script('wcpt-admin-js', 'wcpt_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('wcpt_nonce'),
            'log_url'  => WCPT_PLUGIN_URL . 'wcpt_product_tester.log',
            'csv_url'  => WCPT_PLUGIN_URL . 'wcpt_product_tester.csv',
        ]);
    }

    public function render_single_product_page() {
        // We'll just directly include the partial
        include WCPT_PLUGIN_DIR . 'admin/partials/shared-howto.php';
        include WCPT_PLUGIN_DIR . 'admin/partials/single-product-page.php';
    }

    /**
     * AJAX: product search
     */
    public function ajax_search_products() {
        check_ajax_referer('wcpt_nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';
        if (!$term) {
            wp_send_json([]);
        }

        $args = [
            'limit' => -1,
            'status'=> ['publish','draft','private','pending','trash','future'],
            'type'  => ['simple','variable','subscription','variable-subscription','external'],
            'search'=> '*'.esc_attr($term).'*',
            'orderby'=> 'title',
            'order' => 'ASC',
        ];
        $products = wc_get_products($args);

        $results = [];
        foreach($products as $p) {
            $status_label = '';
            if ($p->get_status() !== 'publish') {
                $status_label = ' ('.ucfirst($p->get_status()).')';
            }
            $type_label = str_replace('-', ' ', $p->get_type());
            $results[] = [
                'label' => $p->get_name() . ' (ID: '.$p->get_id().') - '.$type_label.$status_label,
                'value' => $p->get_id()
            ];
        }
        wp_send_json($results);
    }

    /**
     * AJAX: single product test
     */
    public function ajax_test_single_product() {
        check_ajax_referer('wcpt_nonce','security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $pid = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        if (!$pid) {
            wp_send_json_error('Missing product ID');
        }

        $res = WCPT_Product_Tester::test_product($pid);

        // Write to log
        $log_file = WCPT_PLUGIN_DIR . 'wcpt_product_tester.log';
        file_put_contents($log_file, $res['log_data']);

        // Build columns from $res['rows']
        $col_map = [];
        foreach($res['rows'] as $r) {
            foreach(array_keys($r) as $c) {
                $col_map[$c] = true;
            }
        }
        $columns = array_keys($col_map);

        // Write CSV
        $csv_file = WCPT_PLUGIN_DIR . 'wcpt_product_tester.csv';
        $csv = new WCPT_CSV_Exporter($csv_file);
        $csv->open($columns);

        foreach($res['rows'] as $row) {
            $row_data = [];
            foreach($columns as $col) {
                $row_data[] = isset($row[$col]) ? $row[$col] : '';
            }
            $csv->write_row($row_data);
        }
        $csv->close();

        wp_send_json_success($res['output']);
    }
}
