<?php
if (!defined('ABSPATH')) { exit; }

class ULP_Plugin {
    private $ajax;
    private $admin;

    public function __construct() {
        $this->ajax = new ULP_Ajax();
        $this->admin = new ULP_Admin();
    }

    public function run() {
        add_action('init', [$this, 'load_textdomain']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public']);
        add_shortcode('used_laptop_pricer', [$this, 'render_shortcode']);

        $this->ajax->register();
        $this->admin->register();
    }

    public function load_textdomain() {
        load_plugin_textdomain(ULP_TEXT_DOMAIN, false, dirname(plugin_basename(ULP_PLUGIN_FILE)) . '/languages');
    }

    public function enqueue_public() {
        wp_enqueue_style('ulp-frontend', ULP_PLUGIN_URL . 'assets/css/frontend.css', [], '1.0.0');
        wp_enqueue_script('ulp-frontend', ULP_PLUGIN_URL . 'assets/js/frontend.js', ['jquery'], '1.0.0', true);
        wp_localize_script('ulp-frontend', 'ULPAjax', [
            'url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ulp_nonce'),
        ]);
    }

    public function render_shortcode($atts) {
        ob_start();
        include ULP_PLUGIN_DIR . 'templates/frontend-form.php';
        return ob_get_clean();
    }
}