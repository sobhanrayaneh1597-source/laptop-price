<?php
/**
 * Plugin Name: قیمت‌گذار لپ‌تاپ دست دوم
 * Plugin URI: https://example.com
 * Description: افزونه حرفه‌ای برای محاسبه قیمت لپ‌تاپ‌های دست دوم بر اساس روش Market-Based Pricing با واردسازی Excel، مدیریت قطعات و نرخ‌ها، و فرم AJAX. 
 * Version: 1.0.0
 * Author: hoseinmos
 * Author URI: https://example.com
 * Text Domain: used-laptop-pricer
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

// Constants
define('ULP_PLUGIN_FILE', __FILE__);
define('ULP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ULP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ULP_TEXT_DOMAIN', 'used-laptop-pricer');

// Load Composer autoload if present (PhpSpreadsheet)
if (file_exists(ULP_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once ULP_PLUGIN_DIR . 'vendor/autoload.php';
}

// Includes
require_once ULP_PLUGIN_DIR . 'includes/helpers.php';
require_once ULP_PLUGIN_DIR . 'includes/class-ulp-activator.php';
require_once ULP_PLUGIN_DIR . 'includes/class-ulp-calculator.php';
require_once ULP_PLUGIN_DIR . 'includes/class-ulp-ajax.php';
require_once ULP_PLUGIN_DIR . 'includes/class-ulp-excel.php';
require_once ULP_PLUGIN_DIR . 'includes/class-ulp-admin.php';
require_once ULP_PLUGIN_DIR . 'includes/class-ulp-plugin.php';

// Activation
register_activation_hook(__FILE__, ['ULP_Activator', 'activate']);

function ulp_run_plugin() {
    $plugin = new ULP_Plugin();
    $plugin->run();
}
add_action('plugins_loaded', 'ulp_run_plugin');