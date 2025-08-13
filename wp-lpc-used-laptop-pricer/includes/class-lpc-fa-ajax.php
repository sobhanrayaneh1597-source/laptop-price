<?php
if (!defined('ABSPATH')) { exit; }

class LPC_FA_AJAX {
    public static function init() {
        add_action('wp_ajax_lpc_get_models', [__CLASS__, 'get_models']);
        add_action('wp_ajax_nopriv_lpc_get_models', [__CLASS__, 'get_models']);

        add_action('wp_ajax_lpc_get_model_configs', [__CLASS__, 'get_model_configs']);
        add_action('wp_ajax_nopriv_lpc_get_model_configs', [__CLASS__, 'get_model_configs']);

        add_action('wp_ajax_lpc_calculate', [__CLASS__, 'calculate']);
        add_action('wp_ajax_nopriv_lpc_calculate', [__CLASS__, 'calculate']);
    }

    public static function get_models() {
        check_ajax_referer('lpc_fa_nonce', 'nonce');
        $brand = LPC_FA_Helpers::sanitize_text($_GET['brand'] ?? '');
        if ($brand === '') {
            wp_send_json_error(['message' => __('برند نامعتبر است.', 'lpc-fa')]);
        }
        $models = LPC_FA_DB::get_models_by_brand($brand);
        wp_send_json_success(['models' => $models]);
    }

    public static function get_model_configs() {
        check_ajax_referer('lpc_fa_nonce', 'nonce');
        $model_id = intval($_GET['model_id'] ?? 0);
        if ($model_id <= 0) {
            wp_send_json_error(['message' => __('مدل نامعتبر است.', 'lpc-fa')]);
        }
        $model = LPC_FA_DB::get_model($model_id);
        if (!$model) {
            wp_send_json_error(['message' => __('مدل یافت نشد.', 'lpc-fa')]);
        }
        $components = LPC_FA_DB::get_components($model_id);
        $conditions = get_option('lpc_fa_condition_multipliers', []);
        wp_send_json_success([
            'model' => $model,
            'components' => $components,
            'conditions' => $conditions,
        ]);
    }

    public static function calculate() {
        check_ajax_referer('lpc_fa_nonce', 'nonce');
        $args = [
            'model_id' => intval($_POST['model_id'] ?? 0),
            'cpu' => LPC_FA_Helpers::sanitize_text($_POST['cpu'] ?? ''),
            'ram' => LPC_FA_Helpers::sanitize_text($_POST['ram'] ?? ''),
            'gpu' => LPC_FA_Helpers::sanitize_text($_POST['gpu'] ?? ''),
            'ssd' => LPC_FA_Helpers::sanitize_text($_POST['ssd'] ?? ''),
            'hdd' => LPC_FA_Helpers::sanitize_text($_POST['hdd'] ?? ''),
            'condition' => LPC_FA_Helpers::sanitize_text($_POST['condition'] ?? ''),
        ];
        $result = LPC_FA_Calculator::calculate($args);
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        $result_formatted = [
            'price' => LPC_FA_Helpers::format_price_toman($result['price']),
            'lower' => LPC_FA_Helpers::format_price_toman($result['lower']),
            'upper' => LPC_FA_Helpers::format_price_toman($result['upper']),
            'raw' => $result,
        ];
        wp_send_json_success($result_formatted);
    }
}