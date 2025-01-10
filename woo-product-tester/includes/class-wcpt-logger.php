<?php
defined('ABSPATH') || exit;

class WCPT_Logger {

    public static function log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $log_file = WCPT_PLUGIN_DIR . 'wcpt_debug_log.txt';
            $timestamp = date("Y-m-d H:i:s");
            $output = "[{$timestamp}] " . print_r($message, true) . "\n";
            file_put_contents($log_file, $output, FILE_APPEND);
        }
    }
}
