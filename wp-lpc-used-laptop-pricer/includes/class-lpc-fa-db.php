<?php
if (!defined('ABSPATH')) { exit; }

class LPC_FA_DB {
    const TABLE_MODELS = 'lpc_models';
    const TABLE_COMPONENTS = 'lpc_model_components';
    const TABLE_COMPONENT_PRICES = 'lpc_component_prices';

    public static function table_name($suffix) {
        global $wpdb;
        return $wpdb->prefix . $suffix;
    }

    public static function activate() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();

        $sql_models = "CREATE TABLE " . self::table_name(self::TABLE_MODELS) . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            brand VARCHAR(191) NOT NULL,
            model VARCHAR(191) NOT NULL,
            release_year INT NOT NULL,
            base_price BIGINT UNSIGNED NOT NULL,
            default_cpu VARCHAR(191) NULL,
            default_ram VARCHAR(191) NULL,
            default_gpu VARCHAR(191) NULL,
            default_ssd VARCHAR(191) NULL,
            default_hdd VARCHAR(191) NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY brand_model (brand, model)
        ) $charset;";

        $sql_components = "CREATE TABLE " . self::table_name(self::TABLE_COMPONENTS) . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            model_id BIGINT UNSIGNED NOT NULL,
            type ENUM('cpu','ram','gpu','ssd','hdd') NOT NULL,
            value VARCHAR(191) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY model_type (model_id, type),
            CONSTRAINT fk_model FOREIGN KEY (model_id) REFERENCES " . self::table_name(self::TABLE_MODELS) . "(id) ON DELETE CASCADE
        ) $charset;";

        $sql_component_prices = "CREATE TABLE " . self::table_name(self::TABLE_COMPONENT_PRICES) . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            type ENUM('cpu','ram','gpu','ssd','hdd') NOT NULL,
            name VARCHAR(191) NOT NULL,
            new_price BIGINT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY type_name (type, name)
        ) $charset;";

        dbDelta($sql_models);
        dbDelta($sql_components);
        dbDelta($sql_component_prices);

        // Default options
        add_option('lpc_fa_inflation_rate', 0.30);
        add_option('lpc_fa_condition_multipliers', [
            'نو' => 1.00,
            'تمیز' => 0.95,
            'کارکرده' => 0.90,
            'خط و خش‌دار' => 0.85,
        ]);
        add_option('lpc_fa_component_adjust_factor', 0.6);
    }

    public static function uninstall() {
        global $wpdb;
        $wpdb->query('DROP TABLE IF EXISTS ' . self::table_name(self::TABLE_COMPONENTS));
        $wpdb->query('DROP TABLE IF EXISTS ' . self::table_name(self::TABLE_COMPONENT_PRICES));
        $wpdb->query('DROP TABLE IF EXISTS ' . self::table_name(self::TABLE_MODELS));
        delete_option('lpc_fa_inflation_rate');
        delete_option('lpc_fa_condition_multipliers');
        delete_option('lpc_fa_component_adjust_factor');
    }

    public static function init() {
        // Placeholder for future init if needed
    }

    public static function upsert_model($data) {
        global $wpdb;
        $table = self::table_name(self::TABLE_MODELS);
        $now = current_time('mysql');

        $existing_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE brand = %s AND model = %s",
            $data['brand'], $data['model']
        ));

        $row = [
            'brand' => $data['brand'],
            'model' => $data['model'],
            'release_year' => intval($data['release_year']),
            'base_price' => intval($data['base_price']),
            'default_cpu' => $data['default_cpu'] ?? null,
            'default_ram' => $data['default_ram'] ?? null,
            'default_gpu' => $data['default_gpu'] ?? null,
            'default_ssd' => $data['default_ssd'] ?? null,
            'default_hdd' => $data['default_hdd'] ?? null,
            'updated_at' => $now,
        ];

        if ($existing_id) {
            $wpdb->update($table, $row, ['id' => intval($existing_id)]);
            return intval($existing_id);
        } else {
            $row['created_at'] = $now;
            $wpdb->insert($table, $row);
            return intval($wpdb->insert_id);
        }
    }

    public static function replace_model_components($model_id, $type, $values) {
        global $wpdb;
        $table = self::table_name(self::TABLE_COMPONENTS);
        $wpdb->delete($table, ['model_id' => intval($model_id), 'type' => $type]);
        $now = current_time('mysql');
        foreach ($values as $val) {
            $val = sanitize_text_field($val);
            if ($val === '') { continue; }
            $wpdb->insert($table, [
                'model_id' => intval($model_id),
                'type' => $type,
                'value' => $val,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public static function get_brands() {
        global $wpdb;
        $table = self::table_name(self::TABLE_MODELS);
        return $wpdb->get_col("SELECT DISTINCT brand FROM $table ORDER BY brand ASC");
    }

    public static function get_models_by_brand($brand) {
        global $wpdb;
        $table = self::table_name(self::TABLE_MODELS);
        return $wpdb->get_results($wpdb->prepare("SELECT id, model FROM $table WHERE brand = %s ORDER BY model ASC", $brand));
    }

    public static function get_model($model_id) {
        global $wpdb;
        $table = self::table_name(self::TABLE_MODELS);
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $model_id), ARRAY_A);
    }

    public static function get_components($model_id) {
        global $wpdb;
        $table = self::table_name(self::TABLE_COMPONENTS);
        $rows = $wpdb->get_results($wpdb->prepare("SELECT type, value FROM $table WHERE model_id = %d", $model_id), ARRAY_A);
        $result = ['cpu'=>[], 'ram'=>[], 'gpu'=>[], 'ssd'=>[], 'hdd'=>[]];
        foreach ($rows as $row) {
            $result[$row['type']][] = $row['value'];
        }
        return $result;
    }

    public static function upsert_component_price($type, $name, $new_price) {
        global $wpdb;
        $table = self::table_name(self::TABLE_COMPONENT_PRICES);
        $now = current_time('mysql');
        $existing_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE type = %s AND name = %s", $type, $name));
        $data = [
            'type' => $type,
            'name' => $name,
            'new_price' => intval($new_price),
            'updated_at' => $now,
        ];
        if ($existing_id) {
            $wpdb->update($table, $data, ['id' => intval($existing_id)]);
        } else {
            $data['created_at'] = $now;
            $wpdb->insert($table, $data);
        }
    }

    public static function get_component_price($type, $name) {
        global $wpdb;
        $table = self::table_name(self::TABLE_COMPONENT_PRICES);
        $val = $wpdb->get_var($wpdb->prepare("SELECT new_price FROM $table WHERE type = %s AND name = %s", $type, $name));
        return $val ? intval($val) : 0;
    }

    public static function get_all_component_prices() {
        global $wpdb;
        $table = self::table_name(self::TABLE_COMPONENT_PRICES);
        $rows = $wpdb->get_results("SELECT * FROM $table ORDER BY type, name", ARRAY_A);
        return $rows ?: [];
    }
}