<?php
/**
 * Plugin Name: محاسبه‌گر قیمت لپ‌تاپ دست دوم
 * Plugin URI: https://example.com/
 * Description: افزونه‌ای برای محاسبه قیمت پیشنهادی لپ‌تاپ‌های دست دوم بر اساس برند، مدل، سال عرضه، کانفیگ و وضعیت ظاهری با ورود اطلاعات از فایل Excel.
 * Version: 1.0.0
 * Author: hoseinmos
 * Author URI: https://example.com/
 * Text Domain: lpc-fa
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) { exit; }

// Define plugin constants
define('LPC_FA_VERSION', '1.0.0');
define('LPC_FA_PLUGIN_FILE', __FILE__);
define('LPC_FA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LPC_FA_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoload simple includes
require_once LPC_FA_PLUGIN_DIR . 'includes/helpers.php';
require_once LPC_FA_PLUGIN_DIR . 'includes/class-lpc-fa-db.php';
require_once LPC_FA_PLUGIN_DIR . 'includes/class-lpc-fa-admin.php';
require_once LPC_FA_PLUGIN_DIR . 'includes/class-lpc-fa-frontend.php';
require_once LPC_FA_PLUGIN_DIR . 'includes/class-lpc-fa-importer.php';
require_once LPC_FA_PLUGIN_DIR . 'includes/class-lpc-fa-calculator.php';
require_once LPC_FA_PLUGIN_DIR . 'includes/class-lpc-fa-ajax.php';

// Activation / Deactivation hooks
register_activation_hook(__FILE__, ['LPC_FA_DB', 'activate']);
register_uninstall_hook(__FILE__, ['LPC_FA_DB', 'uninstall']);

// Init plugin
add_action('plugins_loaded', function () {
    load_plugin_textdomain('lpc-fa', false, dirname(plugin_basename(__FILE__)) . '/languages');

    // Initialize components
    LPC_FA_DB::init();
    LPC_FA_Admin::init();
    LPC_FA_Frontend::init();
    LPC_FA_AJAX::init();
});

// Shortcode
add_shortcode('lpc_calculator', ['LPC_FA_Frontend', 'render_shortcode']);