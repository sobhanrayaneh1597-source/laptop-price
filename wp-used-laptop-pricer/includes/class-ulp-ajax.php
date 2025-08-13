<?php
if (!defined('ABSPATH')) { exit; }

class ULP_Ajax {
    public function register() {
        add_action('wp_ajax_ulp_get_brands', [$this, 'get_brands']);
        add_action('wp_ajax_nopriv_ulp_get_brands', [$this, 'get_brands']);

        add_action('wp_ajax_ulp_get_models', [$this, 'get_models']);
        add_action('wp_ajax_nopriv_ulp_get_models', [$this, 'get_models']);

        add_action('wp_ajax_ulp_get_model_base', [$this, 'get_model_base']);
        add_action('wp_ajax_nopriv_ulp_get_model_base', [$this, 'get_model_base']);

        add_action('wp_ajax_ulp_calculate_price', [$this, 'calculate_price']);
        add_action('wp_ajax_nopriv_ulp_calculate_price', [$this, 'calculate_price']);
    }

    private function verify_nonce() {
        $nonce = isset($_REQUEST['nonce']) ? sanitize_text_field(wp_unslash($_REQUEST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'ulp_nonce')) {
            wp_send_json_error(['message' => __('خطا در اعتبارسنجی امنیتی.', ULP_TEXT_DOMAIN)], 403);
        }
    }

    public function get_brands() {
        $this->verify_nonce();
        global $wpdb;
        $table = $wpdb->prefix . 'ulp_models';
        $brands = $wpdb->get_col("SELECT DISTINCT brand FROM {$table} ORDER BY brand ASC");
        wp_send_json_success(['brands' => $brands]);
    }

    public function get_models() {
        $this->verify_nonce();
        global $wpdb;
        $table = $wpdb->prefix . 'ulp_models';
        $brand = isset($_REQUEST['brand']) ? ulp_sanitize_text($_REQUEST['brand']) : '';
        $results = $wpdb->get_results($wpdb->prepare("SELECT id, model FROM {$table} WHERE brand=%s ORDER BY model ASC", $brand));
        wp_send_json_success(['models' => $results]);
    }

    public function get_model_base() {
        $this->verify_nonce();
        global $wpdb;
        $table = $wpdb->prefix . 'ulp_models';
        $id = isset($_REQUEST['model_id']) ? intval($_REQUEST['model_id']) : 0;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id=%d", $id));
        if (!$row) {
            wp_send_json_error(['message' => __('مدل یافت نشد.', ULP_TEXT_DOMAIN)], 404);
        }
        wp_send_json_success(['model' => $row]);
    }

    public function calculate_price() {
        $this->verify_nonce();
        $params = [
            'model_id' => isset($_REQUEST['model_id']) ? intval($_REQUEST['model_id']) : 0,
            'condition' => isset($_REQUEST['condition']) ? ulp_sanitize_text($_REQUEST['condition']) : '',
            'cpu' => isset($_REQUEST['cpu']) ? ulp_sanitize_text($_REQUEST['cpu']) : '',
            'ram_gb' => isset($_REQUEST['ram_gb']) ? intval($_REQUEST['ram_gb']) : 0,
            'gpu' => isset($_REQUEST['gpu']) ? ulp_sanitize_text($_REQUEST['gpu']) : '',
            'storage_type' => isset($_REQUEST['storage_type']) ? ulp_sanitize_text($_REQUEST['storage_type']) : '',
            'storage_size' => isset($_REQUEST['storage_size']) ? ulp_sanitize_text($_REQUEST['storage_size']) : '',
        ];

        $result = ULP_Calculator::calculate($params);
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], 400);
        }

        $response = $result;
        $response['formatted'] = [
            'base_price' => ulp_format_currency($result['base_price']),
            'price_after_depreciation' => ulp_format_currency($result['price_after_depreciation']),
            'depreciation_amount' => ulp_format_currency($result['depreciation_amount']),
            'price_after_condition' => ulp_format_currency($result['price_after_condition']),
            'components_delta' => ulp_format_currency($result['components_delta']),
            'final_price' => ulp_format_currency($result['final_price']),
            'lower_bound' => ulp_format_currency($result['lower_bound']),
            'upper_bound' => ulp_format_currency($result['upper_bound']),
        ];

        wp_send_json_success($response);
    }
}