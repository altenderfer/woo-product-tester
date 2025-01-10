<?php
/**
 * Plugin Name: Woo Product Tester
 * Plugin URI:  https://altenderfer.io/
 * Description: Single-product WooCommerce tester. Logs and CSV-exports product data with a modern UI.
 * Version:     1.0
 * Author:      Kyle Altenderfer
 * Author URI:  https://altenderfer.io/
 * Text Domain: woo-product-tester
 * Domain Path: /languages
 */

defined('ABSPATH') || exit;

define('WCPT_VERSION', '1.0');
define('WCPT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WCPT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Includes
require_once WCPT_PLUGIN_DIR . 'includes/class-wcpt-plugin-activator.php';
require_once WCPT_PLUGIN_DIR . 'includes/class-wcpt-plugin-deactivator.php';
require_once WCPT_PLUGIN_DIR . 'includes/class-wcpt-logger.php';
require_once WCPT_PLUGIN_DIR . 'includes/class-wcpt-csv-exporter.php';
require_once WCPT_PLUGIN_DIR . 'includes/class-wcpt-product-tester.php';
require_once WCPT_PLUGIN_DIR . 'admin/class-wcpt-admin.php'; // Bulk references removed

register_activation_hook(__FILE__, ['WCPT_Plugin_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['WCPT_Plugin_Deactivator', 'deactivate']);

/**
 * Initialize the plugin
 */
function wcpt_init_plugin() {
    // Ensure WooCommerce is active
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p><strong>Woo Product Tester</strong> requires WooCommerce to be active.</p></div>';
        });
        return;
    }

    // Load text domain if needed
    load_plugin_textdomain('woo-product-tester', false, dirname(plugin_basename(__FILE__)) . '/languages');

    // Instantiate the Admin
    $admin = new WCPT_Admin();
    $admin->init();
}
add_action('plugins_loaded', 'wcpt_init_plugin');
