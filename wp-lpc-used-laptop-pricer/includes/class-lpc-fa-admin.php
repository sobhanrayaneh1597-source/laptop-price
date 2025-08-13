<?php
if (!defined('ABSPATH')) { exit; }

class LPC_FA_Admin {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'menu']);
        add_action('admin_post_lpc_fa_import', ['LPC_FA_Importer', 'handle_upload']);
        add_action('admin_post_lpc_fa_save_component_prices', [__CLASS__, 'save_component_prices']);
        add_action('admin_post_lpc_fa_save_settings', [__CLASS__, 'save_settings']);
    }

    public static function menu() {
        add_menu_page(
            __('محاسبه‌گر لپ‌تاپ دست دوم', 'lpc-fa'),
            __('لپ‌تاپ دست دوم', 'lpc-fa'),
            'manage_options',
            'lpc-fa-dashboard',
            [__CLASS__, 'render_dashboard'],
            'dashicons-laptop',
            58
        );

        add_submenu_page('lpc-fa-dashboard', __('ورود از اکسل', 'lpc-fa'), __('ورود از اکسل', 'lpc-fa'), 'manage_options', 'lpc-fa-import', [__CLASS__, 'render_import']);
        add_submenu_page('lpc-fa-dashboard', __('قیمت قطعات', 'lpc-fa'), __('قیمت قطعات', 'lpc-fa'), 'manage_options', 'lpc-fa-components', [__CLASS__, 'render_components']);
        add_submenu_page('lpc-fa-dashboard', __('تنظیمات محاسبه', 'lpc-fa'), __('تنظیمات', 'lpc-fa'), 'manage_options', 'lpc-fa-settings', [__CLASS__, 'render_settings']);
        add_submenu_page('lpc-fa-dashboard', __('مدل‌ها', 'lpc-fa'), __('مدل‌ها', 'lpc-fa'), 'manage_options', 'lpc-fa-models', [__CLASS__, 'render_models']);
    }

    public static function render_dashboard() {
        echo '<div class="wrap"><h1>' . esc_html(__('محاسبه‌گر قیمت لپ‌تاپ دست دوم', 'lpc-fa')) . '</h1>';
        echo '<p>' . esc_html(__('از منوی کناری برای ورود داده‌ها و تنظیمات استفاده کنید.', 'lpc-fa')) . '</p>';
        echo '</div>';
    }

    public static function render_import() {
        $imported = isset($_GET['imported']) ? intval($_GET['imported']) : null;
        echo '<div class="wrap">';
        echo '<h1>' . esc_html(__('ورود مدل‌ها از Excel', 'lpc-fa')) . '</h1>';
        if ($imported !== null) {
            echo '<div class="updated notice"><p>' . sprintf(esc_html(__('تعداد %d مدل ثبت/بروزرسانی شد.', 'lpc-fa')), $imported) . '</p></div>';
        }
        echo '<p>' . esc_html(__('لطفاً فایل Excel با فرمت xlsx را طبق قالب نمونه بارگذاری کنید.', 'lpc-fa')) . '</p>';
        $sample_url = LPC_FA_PLUGIN_URL . 'sample/sample.csv';
        echo '<p><a class="button" href="' . esc_url($sample_url) . '">' . esc_html(__('دانلود فایل نمونه (CSV)', 'lpc-fa')) . '</a></p>';
        echo '<p class="description">' . esc_html(__('می‌توانید CSV را در Excel باز کرده و به xlsx ذخیره کنید یا مستقیم از xlsx خود استفاده کنید.', 'lpc-fa')) . '</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" enctype="multipart/form-data">';
        wp_nonce_field('lpc_fa_import', '_lpc_nonce');
        echo '<input type="hidden" name="action" value="lpc_fa_import" />';
        echo '<input type="file" name="lpc_fa_excel" accept=".xlsx" required /> ';
        submit_button(__('بارگذاری و ثبت', 'lpc-fa'));
        echo '</form>';
        echo '</div>';
    }

    public static function render_components() {
        $rows = LPC_FA_DB::get_all_component_prices();
        echo '<div class="wrap">';
        echo '<h1>' . esc_html(__('قیمت قطعات (نو)', 'lpc-fa')) . '</h1>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('lpc_fa_save_component_prices', '_lpc_nonce');
        echo '<input type="hidden" name="action" value="lpc_fa_save_component_prices" />';

        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>' . esc_html(__('نوع', 'lpc-fa')) . '</th>';
        echo '<th>' . esc_html(__('نام', 'lpc-fa')) . '</th>';
        echo '<th>' . esc_html(__('قیمت نو (تومان)', 'lpc-fa')) . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($rows as $row) {
            echo '<tr>';
            echo '<td><input type="text" name="type[]" value="' . esc_attr($row['type']) . '" /></td>';
            echo '<td><input type="text" name="name[]" value="' . esc_attr($row['name']) . '" /></td>';
            echo '<td><input type="number" name="new_price[]" value="' . esc_attr($row['new_price']) . '" step="10000" /></td>';
            echo '</tr>';
        }

        // Empty row for adding new
        for ($i=0; $i<5; $i++) {
            echo '<tr>';
            echo '<td><select name="type[]">'
                . '<option value="cpu">' . esc_html(__('CPU', 'lpc-fa')) . '</option>'
                . '<option value="ram">' . esc_html(__('RAM', 'lpc-fa')) . '</option>'
                . '<option value="gpu">' . esc_html(__('GPU', 'lpc-fa')) . '</option>'
                . '<option value="ssd">' . esc_html(__('SSD', 'lpc-fa')) . '</option>'
                . '<option value="hdd">' . esc_html(__('HDD', 'lpc-fa')) . '</option>'
                . '</select></td>';
            echo '<td><input type="text" name="name[]" value="" placeholder="" /></td>';
            echo '<td><input type="number" name="new_price[]" value="" step="10000" /></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        submit_button(__('ذخیره قیمت‌ها', 'lpc-fa'));
        echo '</form>';
        echo '</div>';
    }

    public static function save_component_prices() {
        LPC_FA_Helpers::verify_nonce_or_die('lpc_fa_save_component_prices');
        if (!current_user_can('manage_options')) { wp_die(__('دسترسی غیرمجاز', 'lpc-fa')); }
        $types = $_POST['type'] ?? [];
        $names = $_POST['name'] ?? [];
        $prices = $_POST['new_price'] ?? [];
        $count = min(count($types), count($names), count($prices));
        for ($i = 0; $i < $count; $i++) {
            $type = sanitize_text_field($types[$i]);
            $name = sanitize_text_field($names[$i]);
            $price = intval($prices[$i]);
            if ($type && $name && $price > 0) {
                LPC_FA_DB::upsert_component_price($type, $name, $price);
            }
        }
        wp_redirect(add_query_arg(['page' => 'lpc-fa-components', 'saved' => 1], admin_url('admin.php')));
        exit;
    }

    public static function render_settings() {
        $inflation = floatval(get_option('lpc_fa_inflation_rate', 0.30));
        $cond = (array) get_option('lpc_fa_condition_multipliers', []);
        $factor = floatval(get_option('lpc_fa_component_adjust_factor', 0.6));
        echo '<div class="wrap">';
        echo '<h1>' . esc_html(__('تنظیمات محاسبه', 'lpc-fa')) . '</h1>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('lpc_fa_save_settings', '_lpc_nonce');
        echo '<input type="hidden" name="action" value="lpc_fa_save_settings" />';

        echo '<h2>' . esc_html(__('تورم سالانه', 'lpc-fa')) . '</h2>';
        echo '<p><input type="number" name="inflation" step="0.01" min="0" value="' . esc_attr($inflation) . '" /> ' . esc_html(__('(مثلاً 0.30 برای 30٪)', 'lpc-fa')) . '</p>';

        echo '<h2>' . esc_html(__('ضرایب وضعیت ظاهری', 'lpc-fa')) . '</h2>';
        echo '<table class="widefat striped"><thead><tr><th>' . esc_html(__('وضعیت', 'lpc-fa')) . '</th><th>' . esc_html(__('ضریب', 'lpc-fa')) . '</th></tr></thead><tbody>';
        foreach ($cond as $name => $val) {
            echo '<tr><td><input type="text" name="cond_name[]" value="' . esc_attr($name) . '" /></td>';
            echo '<td><input type="number" name="cond_val[]" step="0.01" value="' . esc_attr($val) . '" /></td></tr>';
        }
        for ($i=0; $i<3; $i++) {
            echo '<tr><td><input type="text" name="cond_name[]" value="" /></td>';
            echo '<td><input type="number" name="cond_val[]" step="0.01" value="" /></td></tr>';
        }
        echo '</tbody></table>';

        echo '<h2>' . esc_html(__('ضریب تعدیل کانفیگ', 'lpc-fa')) . '</h2>';
        echo '<p><input type="number" name="adjust_factor" step="0.01" min="0" max="1" value="' . esc_attr($factor) . '" /> ' . esc_html(__('(بین 0 تا 1؛ مثلاً 0.6)', 'lpc-fa')) . '</p>';

        submit_button(__('ذخیره تنظیمات', 'lpc-fa'));
        echo '</form>';
        echo '</div>';
    }

    public static function save_settings() {
        LPC_FA_Helpers::verify_nonce_or_die('lpc_fa_save_settings');
        if (!current_user_can('manage_options')) { wp_die(__('دسترسی غیرمجاز', 'lpc-fa')); }
        $inflation = floatval($_POST['inflation'] ?? 0.30);
        update_option('lpc_fa_inflation_rate', $inflation);

        $names = $_POST['cond_name'] ?? [];
        $vals = $_POST['cond_val'] ?? [];
        $out = [];
        for ($i=0; $i < min(count($names), count($vals)); $i++) {
            $n = sanitize_text_field($names[$i]);
            $v = floatval($vals[$i]);
            if ($n !== '' && $v > 0) { $out[$n] = $v; }
        }
        if (empty($out)) {
            $out = [ 'نو' => 1.00, 'تمیز' => 0.95, 'کارکرده' => 0.90, 'خط و خش‌دار' => 0.85 ];
        }
        update_option('lpc_fa_condition_multipliers', $out);

        $factor = floatval($_POST['adjust_factor'] ?? 0.6);
        update_option('lpc_fa_component_adjust_factor', $factor);

        wp_redirect(add_query_arg(['page' => 'lpc-fa-settings', 'saved' => 1], admin_url('admin.php')));
        exit;
    }

    public static function render_models() {
        global $wpdb;
        $table = LPC_FA_DB::table_name(LPC_FA_DB::TABLE_MODELS);
        $rows = $wpdb->get_results("SELECT * FROM $table ORDER BY brand, model", ARRAY_A);
        echo '<div class="wrap">';
        echo '<h1>' . esc_html(__('مدل‌ها', 'lpc-fa')) . '</h1>';
        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>' . esc_html(__('برند', 'lpc-fa')) . '</th>';
        echo '<th>' . esc_html(__('مدل', 'lpc-fa')) . '</th>';
        echo '<th>' . esc_html(__('سال عرضه', 'lpc-fa')) . '</th>';
        echo '<th>' . esc_html(__('قیمت اولیه', 'lpc-fa')) . '</th>';
        echo '<th>' . esc_html(__('کانفیگ پایه', 'lpc-fa')) . '</th>';
        echo '</tr></thead><tbody>';
        foreach ($rows as $r) {
            echo '<tr>';
            echo '<td>' . esc_html($r['brand']) . '</td>';
            echo '<td>' . esc_html($r['model']) . '</td>';
            echo '<td>' . esc_html($r['release_year']) . '</td>';
            echo '<td>' . esc_html(number_format($r['base_price'])) . '</td>';
            $conf = [];
            foreach (['cpu','ram','gpu','ssd','hdd'] as $t) {
                if (!empty($r['default_'.$t])) { $conf[] = strtoupper($t) . ': ' . $r['default_'.$t]; }
            }
            echo '<td>' . esc_html(implode(' | ', $conf)) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
    }
}