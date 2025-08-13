<?php
if (!defined('ABSPATH')) { exit; }

class ULP_Activator {
    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $models_table = $wpdb->prefix . 'ulp_models';
        $components_table = $wpdb->prefix . 'ulp_component_prices';
        $conditions_table = $wpdb->prefix . 'ulp_conditions';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql_models = "CREATE TABLE {$models_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            brand VARCHAR(100) NOT NULL,
            model VARCHAR(150) NOT NULL,
            release_year INT UNSIGNED NOT NULL,
            base_price BIGINT UNSIGNED NOT NULL,
            base_cpu VARCHAR(100) DEFAULT '' NOT NULL,
            base_ram_gb INT UNSIGNED DEFAULT 0 NOT NULL,
            base_gpu VARCHAR(100) DEFAULT '' NOT NULL,
            base_storage VARCHAR(100) DEFAULT '' NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY brand_model (brand, model),
            PRIMARY KEY (id)
        ) {$charset_collate};";

        $sql_components = "CREATE TABLE {$components_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            component_type VARCHAR(20) NOT NULL,
            label VARCHAR(150) NOT NULL,
            value VARCHAR(150) NOT NULL,
            price_delta BIGINT NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY component_type (component_type)
        ) {$charset_collate};";

        $sql_conditions = "CREATE TABLE {$conditions_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            slug VARCHAR(50) NOT NULL,
            label VARCHAR(100) NOT NULL,
            multiplier FLOAT NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) {$charset_collate};";

        dbDelta($sql_models);
        dbDelta($sql_components);
        dbDelta($sql_conditions);

        // Default settings
        if (!get_option('ulp_settings')) {
            update_option('ulp_settings', [
                'depr_rate_year1' => 0.30,
                'depr_rate_year2' => 0.15,
                'depr_rate_yearN' => 0.10,
                'round_step' => 100000,
                'thousands_sep' => ',',
                'currency_label' => __('تومان', ULP_TEXT_DOMAIN),
            ]);
        }

        // Seed default conditions if empty
        $existing = $wpdb->get_var("SELECT COUNT(*) FROM {$conditions_table}");
        if ((int) $existing === 0) {
            $defaults = [
                ['slug' => 'new', 'label' => __('نو', ULP_TEXT_DOMAIN), 'multiplier' => 1.00, 'sort_order' => 1],
                ['slug' => 'clean', 'label' => __('تمیز', ULP_TEXT_DOMAIN), 'multiplier' => 0.95, 'sort_order' => 2],
                ['slug' => 'used', 'label' => __('کارکرده', ULP_TEXT_DOMAIN), 'multiplier' => 0.90, 'sort_order' => 3],
            ];
            foreach ($defaults as $row) {
                $wpdb->insert($conditions_table, $row);
            }
        }
    }
}