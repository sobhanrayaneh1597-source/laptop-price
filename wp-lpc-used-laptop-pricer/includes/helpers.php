<?php
if (!defined('ABSPATH')) { exit; }

class LPC_FA_Helpers {
    public static function sanitize_text($value) {
        return sanitize_text_field(wp_unslash($value ?? ''));
    }

    public static function sanitize_float($value) {
        $value = is_string($value) ? str_replace([',', '٬', ' '], '', $value) : $value;
        return floatval($value);
    }

    public static function format_price_toman($amount) {
        $amount = round($amount);
        return number_format($amount, 0, '.', ',') . ' ' . __('تومان', 'lpc-fa');
    }

    public static function round_to_100k($amount) {
        return round($amount / 100000) * 100000;
    }

    public static function current_year() {
        return intval(current_time('Y'));
    }

    public static function verify_nonce_or_die($nonce_action, $nonce_key = '_lpc_nonce') {
        if (!isset($_POST[$nonce_key]) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[$nonce_key])), $nonce_action)) {
            wp_die(__('درخواست نامعتبر است.', 'lpc-fa'));
        }
    }
}