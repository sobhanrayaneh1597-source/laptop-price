<?php
if (!defined('ABSPATH')) { exit; }

function ulp_get_option($key, $default = null) {
    $options = get_option('ulp_settings', []);
    return isset($options[$key]) ? $options[$key] : $default;
}

function ulp_update_option($key, $value) {
    $options = get_option('ulp_settings', []);
    $options[$key] = $value;
    update_option('ulp_settings', $options);
}

function ulp_format_currency($amount) {
    $amount = (float) $amount;
    $thousands = ulp_get_option('thousands_sep', ',');
    $suffix = ulp_get_option('currency_label', __('تومان', ULP_TEXT_DOMAIN));
    $formatted = number_format($amount, 0, '.', $thousands);
    return sprintf('%s %s', $formatted, $suffix);
}

function ulp_round_to_step($amount) {
    $step = (int) ulp_get_option('round_step', 100000);
    if ($step <= 0) { $step = 100000; }
    return round($amount / $step) * $step;
}

function ulp_current_year() {
    return (int) current_time('Y');
}

function ulp_sanitize_text($value) {
    return sanitize_text_field(wp_unslash($value));
}