<?php
if (!defined('ABSPATH')) { exit; }

class LPC_FA_Calculator {
    public static function calculate(array $args) {
        // $args: model_id, cpu, ram, gpu, ssd, hdd, condition
        $model = LPC_FA_DB::get_model(intval($args['model_id']));
        if (!$model) {
            return new WP_Error('not_found', __('مدل یافت نشد.', 'lpc-fa'));
        }

        $inflation_rate = floatval(get_option('lpc_fa_inflation_rate', 0.30));
        $condition_multipliers = (array) get_option('lpc_fa_condition_multipliers', []);
        $component_adjust_factor = floatval(get_option('lpc_fa_component_adjust_factor', 0.6));

        $base_price = floatval($model['base_price']);
        $release_year = intval($model['release_year']);
        $years_passed = max(0, LPC_FA_Helpers::current_year() - $release_year);

        // Step 2: apply inflation to base price
        $inflated_price = $base_price * pow(1 + $inflation_rate, $years_passed);

        // Step 3: depreciation schedule
        $price_after_depr = $inflated_price;
        if ($years_passed >= 1) {
            $price_after_depr *= 0.70; // first year 30% off
        }
        if ($years_passed >= 2) {
            $price_after_depr *= 0.85; // second year 15% off remaining
        }
        if ($years_passed >= 3) {
            $extra_years = $years_passed - 2;
            $price_after_depr *= pow(0.90, $extra_years); // 10% each extra year
        }

        // Step 5: component adjustments based on new component prices
        $components = ['cpu','ram','gpu','ssd','hdd'];
        $adjust_total = 0.0;
        foreach ($components as $type) {
            $selected = sanitize_text_field($args[$type] ?? '');
            $default = sanitize_text_field($model['default_' . $type] ?? '');
            if ($selected === '' || $default === '' || $selected === $default) { continue; }

            $selected_new = LPC_FA_DB::get_component_price($type, $selected);
            $default_new = LPC_FA_DB::get_component_price($type, $default);
            if ($selected_new <= 0 || $default_new <= 0) { continue; }

            $diff = $selected_new - $default_new; // positive means upgrade
            $adjust_total += $component_adjust_factor * $diff;
        }

        $price_with_components = $price_after_depr + $adjust_total;

        // Step 4: apply condition multiplier
        $condition = sanitize_text_field($args['condition'] ?? '');
        $condition_multiplier = isset($condition_multipliers[$condition]) ? floatval($condition_multipliers[$condition]) : 1.0;
        $final = $price_with_components * $condition_multiplier;

        // Step 6: round to nearest 100,000
        $final = LPC_FA_Helpers::round_to_100k($final);

        // Step 7: range
        $lower = LPC_FA_Helpers::round_to_100k($final * 0.90);
        $upper = $final;

        return [
            'price' => $final,
            'lower' => $lower,
            'upper' => $upper,
        ];
    }
}