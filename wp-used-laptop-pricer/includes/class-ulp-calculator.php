<?php
if (!defined('ABSPATH')) { exit; }

class ULP_Calculator {
    public static function calculate(array $params) {
        global $wpdb;
        $models_table = $wpdb->prefix . 'ulp_models';
        $conditions_table = $wpdb->prefix . 'ulp_conditions';
        $components_table = $wpdb->prefix . 'ulp_component_prices';

        $model_id = isset($params['model_id']) ? intval($params['model_id']) : 0;
        $condition_slug = isset($params['condition']) ? ulp_sanitize_text($params['condition']) : '';
        $cpu = isset($params['cpu']) ? ulp_sanitize_text($params['cpu']) : '';
        $ram_gb = isset($params['ram_gb']) ? intval($params['ram_gb']) : 0;
        $gpu = isset($params['gpu']) ? ulp_sanitize_text($params['gpu']) : '';
        $storage_type = isset($params['storage_type']) ? ulp_sanitize_text($params['storage_type']) : '';
        $storage_size = isset($params['storage_size']) ? ulp_sanitize_text($params['storage_size']) : '';

        $model = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$models_table} WHERE id=%d", $model_id));
        if (!$model) {
            return new WP_Error('model_not_found', __('مدل یافت نشد.', ULP_TEXT_DOMAIN));
        }

        $base_price = (int) $model->base_price;
        $years_since_release = max(0, ulp_current_year() - (int) $model->release_year);

        $price_after_depr = self::apply_depreciation($base_price, $years_since_release);

        // Condition multiplier
        $condition = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$conditions_table} WHERE slug=%s", $condition_slug));
        $condition_multiplier = $condition ? (float) $condition->multiplier : 1.0;
        $price_after_condition = $price_after_depr * $condition_multiplier;

        // Components delta
        $components_delta = 0;
        $components_delta += self::get_component_delta($components_table, 'cpu', $cpu, $model->base_cpu);
        $components_delta += self::get_component_delta($components_table, 'ram', (string) $ram_gb, (string) $model->base_ram_gb);
        $components_delta += self::get_component_delta($components_table, 'gpu', $gpu, $model->base_gpu);
        $components_delta += self::get_component_delta($components_table, 'storage', $storage_type . ' ' . $storage_size, $model->base_storage);

        $final_price = max(0, $price_after_condition + $components_delta);

        // Rounding and bounds
        $final_price_rounded = ulp_round_to_step($final_price);
        $lower_bound = ulp_round_to_step($final_price_rounded * 0.9);
        $upper_bound = $final_price_rounded;

        return [
            'base_price' => $base_price,
            'years_since_release' => $years_since_release,
            'price_after_depreciation' => (int) round($price_after_depr),
            'depreciation_amount' => (int) max(0, $base_price - $price_after_depr),
            'condition_multiplier' => $condition_multiplier,
            'price_after_condition' => (int) round($price_after_condition),
            'components_delta' => (int) round($components_delta),
            'final_price' => (int) $final_price_rounded,
            'lower_bound' => (int) $lower_bound,
            'upper_bound' => (int) $upper_bound,
        ];
    }

    public static function apply_depreciation($base_price, $years) {
        $rate1 = (float) ulp_get_option('depr_rate_year1', 0.30);
        $rate2 = (float) ulp_get_option('depr_rate_year2', 0.15);
        $rateN = (float) ulp_get_option('depr_rate_yearN', 0.10);

        $price = (float) $base_price;
        for ($i = 1; $i <= $years; $i++) {
            if ($i === 1) {
                $price *= (1 - $rate1);
            } elseif ($i === 2) {
                $price *= (1 - $rate2);
            } else {
                $price *= (1 - $rateN);
            }
        }
        return $price;
    }

    private static function get_component_delta($table, $type, $selected_value, $base_value) {
        global $wpdb;
        if ($selected_value === '') { return 0; }
        // If selected equals base, delta could be zero, else look up delta by label/value
        if (strtolower(trim($selected_value)) === strtolower(trim((string) $base_value))) {
            return 0;
        }
        // Prefer exact match on value, else try label
        $row = $wpdb->get_row($wpdb->prepare("SELECT price_delta FROM {$table} WHERE component_type=%s AND (value=%s OR label=%s) ORDER BY (value=%s) DESC LIMIT 1", $type, $selected_value, $selected_value, $selected_value));
        if ($row) {
            return (int) $row->price_delta;
        }
        return 0;
    }
}