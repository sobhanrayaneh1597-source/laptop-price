<?php
if (!defined('ABSPATH')) { exit; }

class ULP_Admin {
    public function register() {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'handle_post']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
    }

    public function enqueue($hook) {
        if (strpos($hook, 'ulp') !== false) {
            wp_enqueue_style('ulp-admin', ULP_PLUGIN_URL . 'assets/css/admin.css', [], '1.0.0');
            wp_enqueue_script('ulp-admin', ULP_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], '1.0.0', true);
        }
    }

    public function admin_menu() {
        add_menu_page(__('قیمت‌گذار لپ‌تاپ', ULP_TEXT_DOMAIN), __('قیمت‌گذار لپ‌تاپ', ULP_TEXT_DOMAIN), 'manage_options', 'ulp_dashboard', [$this, 'render_dashboard'], 'dashicons-laptop', 58);
        add_submenu_page('ulp_dashboard', __('واردسازی/خروجی Excel', ULP_TEXT_DOMAIN), __('ورود/خروج Excel', ULP_TEXT_DOMAIN), 'manage_options', 'ulp_import_export', [$this, 'render_import_export']);
        add_submenu_page('ulp_dashboard', __('مدیریت مدل‌ها', ULP_TEXT_DOMAIN), __('مدل‌ها', ULP_TEXT_DOMAIN), 'manage_options', 'ulp_models', [$this, 'render_models']);
        add_submenu_page('ulp_dashboard', __('قیمت قطعات', ULP_TEXT_DOMAIN), __('قیمت قطعات', ULP_TEXT_DOMAIN), 'manage_options', 'ulp_components', [$this, 'render_components']);
        add_submenu_page('ulp_dashboard', __('وضعیت ظاهری', ULP_TEXT_DOMAIN), __('وضعیت ظاهری', ULP_TEXT_DOMAIN), 'manage_options', 'ulp_conditions', [$this, 'render_conditions']);
        add_submenu_page('ulp_dashboard', __('تنظیمات', ULP_TEXT_DOMAIN), __('تنظیمات', ULP_TEXT_DOMAIN), 'manage_options', 'ulp_settings', [$this, 'render_settings']);
        add_submenu_page('ulp_dashboard', __('راهنما', ULP_TEXT_DOMAIN), __('راهنما', ULP_TEXT_DOMAIN), 'manage_options', 'ulp_help', [$this, 'render_help']);
    }

    public function handle_post() {
        if (!current_user_can('manage_options')) { return; }
        if (!isset($_POST['ulp_action'])) { return; }
        check_admin_referer('ulp_admin_action', 'ulp_nonce');

        $action = sanitize_text_field($_POST['ulp_action']);
        switch ($action) {
            case 'import_excel':
                if (!empty($_FILES['ulp_excel']['tmp_name'])) {
                    $file = $_FILES['ulp_excel'];
                    $path = $file['tmp_name'];
                    try {
                        $count = ULP_Excel::import_models($path);
                        add_settings_error('ulp_messages', 'ulp_import_success', sprintf(__('تعداد %d رکورد با موفقیت پردازش شد.', ULP_TEXT_DOMAIN), $count), 'updated');
                    } catch (Exception $e) {
                        add_settings_error('ulp_messages', 'ulp_import_error', $e->getMessage(), 'error');
                    }
                }
                break;
            case 'export_excel':
                try {
                    $url = ULP_Excel::export_models();
                    add_settings_error('ulp_messages', 'ulp_export_success', sprintf(__('فایل خروجی آماده است: %s', ULP_TEXT_DOMAIN), '<a href="' . esc_url($url) . '" target="_blank">' . __('دانلود', ULP_TEXT_DOMAIN) . '</a>'), 'updated');
                } catch (Exception $e) {
                    add_settings_error('ulp_messages', 'ulp_export_error', $e->getMessage(), 'error');
                }
                break;
            case 'save_settings':
                $settings = [
                    'depr_rate_year1' => isset($_POST['depr_rate_year1']) ? (float) $_POST['depr_rate_year1'] : 0.30,
                    'depr_rate_year2' => isset($_POST['depr_rate_year2']) ? (float) $_POST['depr_rate_year2'] : 0.15,
                    'depr_rate_yearN' => isset($_POST['depr_rate_yearN']) ? (float) $_POST['depr_rate_yearN'] : 0.10,
                    'round_step' => isset($_POST['round_step']) ? intval($_POST['round_step']) : 100000,
                    'thousands_sep' => isset($_POST['thousands_sep']) ? sanitize_text_field($_POST['thousands_sep']) : ',',
                    'currency_label' => isset($_POST['currency_label']) ? sanitize_text_field($_POST['currency_label']) : __('تومان', ULP_TEXT_DOMAIN),
                ];
                update_option('ulp_settings', $settings);
                add_settings_error('ulp_messages', 'ulp_settings_saved', __('تنظیمات ذخیره شد.', ULP_TEXT_DOMAIN), 'updated');
                break;
            case 'add_component':
                $type = sanitize_text_field($_POST['component_type']);
                $label = sanitize_text_field($_POST['label']);
                $value = sanitize_text_field($_POST['value']);
                $delta = intval($_POST['price_delta']);
                global $wpdb;
                $wpdb->insert($wpdb->prefix . 'ulp_component_prices', [
                    'component_type' => $type,
                    'label' => $label,
                    'value' => $value,
                    'price_delta' => $delta,
                    'sort_order' => 0,
                ]);
                add_settings_error('ulp_messages', 'ulp_component_added', __('قیمت قطعه اضافه شد.', ULP_TEXT_DOMAIN), 'updated');
                break;
            case 'add_condition':
                $slug = sanitize_title($_POST['slug']);
                $label = sanitize_text_field($_POST['label']);
                $multiplier = (float) $_POST['multiplier'];
                global $wpdb;
                $wpdb->insert($wpdb->prefix . 'ulp_conditions', [
                    'slug' => $slug,
                    'label' => $label,
                    'multiplier' => $multiplier,
                    'sort_order' => 0,
                ]);
                add_settings_error('ulp_messages', 'ulp_condition_added', __('وضعیت ذخیره شد.', ULP_TEXT_DOMAIN), 'updated');
                break;
        }
    }

    public function render_dashboard() {
        echo '<div class="wrap"><h1>' . esc_html(__('قیمت‌گذار لپ‌تاپ', ULP_TEXT_DOMAIN)) . '</h1>';
        settings_errors('ulp_messages');
        echo '<p>' . esc_html(__('از منوی سمت چپ می‌توانید داده‌ها را وارد/خروج کرده، قطعات و وضعیت‌ها را مدیریت و تنظیمات را تغییر دهید.', ULP_TEXT_DOMAIN)) . '</p>';
        echo '</div>';
    }

    public function render_import_export() {
        echo '<div class="wrap"><h1>' . esc_html(__('ورود/خروج Excel', ULP_TEXT_DOMAIN)) . '</h1>';
        settings_errors('ulp_messages');
        echo '<h2>' . esc_html(__('ورود از Excel', ULP_TEXT_DOMAIN)) . '</h2>';
        echo '<form method="post" enctype="multipart/form-data">';
        wp_nonce_field('ulp_admin_action', 'ulp_nonce');
        echo '<input type="hidden" name="ulp_action" value="import_excel">';
        echo '<input type="file" name="ulp_excel" accept=".xlsx,.xls" required> ';
        submit_button(__('واردسازی', ULP_TEXT_DOMAIN));
        echo '</form>';

        echo '<hr><h2>' . esc_html(__('خروجی Excel', ULP_TEXT_DOMAIN)) . '</h2>';
        echo '<form method="post">';
        wp_nonce_field('ulp_admin_action', 'ulp_nonce');
        echo '<input type="hidden" name="ulp_action" value="export_excel">';
        submit_button(__('دریافت خروجی', ULP_TEXT_DOMAIN));
        echo '</form>';
        echo '</div>';
    }

    public function render_models() {
        global $wpdb;
        $table = $wpdb->prefix . 'ulp_models';
        $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY brand, model");
        echo '<div class="wrap"><h1>' . esc_html(__('مدل‌های موجود', ULP_TEXT_DOMAIN)) . '</h1>';
        echo '<table class="widefat"><thead><tr><th>' . __('برند', ULP_TEXT_DOMAIN) . '</th><th>' . __('مدل', ULP_TEXT_DOMAIN) . '</th><th>' . __('سال', ULP_TEXT_DOMAIN) . '</th><th>' . __('قیمت اولیه', ULP_TEXT_DOMAIN) . '</th><th>' . __('CPU', ULP_TEXT_DOMAIN) . '</th><th>' . __('RAM', ULP_TEXT_DOMAIN) . '</th><th>' . __('GPU', ULP_TEXT_DOMAIN) . '</th><th>' . __('Storage', ULP_TEXT_DOMAIN) . '</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            echo '<tr>';
            echo '<td>' . esc_html($r->brand) . '</td>';
            echo '<td>' . esc_html($r->model) . '</td>';
            echo '<td>' . esc_html($r->release_year) . '</td>';
            echo '<td>' . esc_html(ulp_format_currency($r->base_price)) . '</td>';
            echo '<td>' . esc_html($r->base_cpu) . '</td>';
            echo '<td>' . esc_html($r->base_ram_gb) . 'GB</td>';
            echo '<td>' . esc_html($r->base_gpu) . '</td>';
            echo '<td>' . esc_html($r->base_storage) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    }

    public function render_components() {
        global $wpdb;
        $table = $wpdb->prefix . 'ulp_component_prices';
        $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY component_type, sort_order, id DESC");
        echo '<div class="wrap"><h1>' . esc_html(__('قیمت قطعات', ULP_TEXT_DOMAIN)) . '</h1>';
        settings_errors('ulp_messages');
        echo '<form method="post">';
        wp_nonce_field('ulp_admin_action', 'ulp_nonce');
        echo '<input type="hidden" name="ulp_action" value="add_component">';
        echo '<table class="form-table"><tr><th>' . __('نوع قطعه', ULP_TEXT_DOMAIN) . '</th><td><select name="component_type" required>' .
            '<option value="cpu">CPU</option><option value="ram">RAM</option><option value="gpu">GPU</option><option value="storage">Storage</option></select></td></tr>';
        echo '<tr><th>' . __('برچسب نمایش', ULP_TEXT_DOMAIN) . '</th><td><input type="text" name="label" class="regular-text" required></td></tr>';
        echo '<tr><th>' . __('مقدار (برای RAM عدد GB، برای Storage مانند SSD 512GB)', ULP_TEXT_DOMAIN) . '</th><td><input type="text" name="value" class="regular-text" required></td></tr>';
        echo '<tr><th>' . __('تغییر قیمت (تومان، می‌تواند منفی باشد)', ULP_TEXT_DOMAIN) . '</th><td><input type="number" name="price_delta" required></td></tr></table>';
        submit_button(__('افزودن', ULP_TEXT_DOMAIN));
        echo '</form>';

        echo '<h2>' . esc_html(__('لیست قیمت‌ها', ULP_TEXT_DOMAIN)) . '</h2>';
        echo '<table class="widefat"><thead><tr><th>' . __('نوع', ULP_TEXT_DOMAIN) . '</th><th>' . __('برچسب', ULP_TEXT_DOMAIN) . '</th><th>' . __('مقدار', ULP_TEXT_DOMAIN) . '</th><th>' . __('تغییر قیمت', ULP_TEXT_DOMAIN) . '</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            echo '<tr>';
            echo '<td>' . esc_html($r->component_type) . '</td>';
            echo '<td>' . esc_html($r->label) . '</td>';
            echo '<td>' . esc_html($r->value) . '</td>';
            echo '<td>' . esc_html(ulp_format_currency($r->price_delta)) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    }

    public function render_conditions() {
        global $wpdb;
        $table = $wpdb->prefix . 'ulp_conditions';
        $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY sort_order, id");
        echo '<div class="wrap"><h1>' . esc_html(__('وضعیت ظاهری', ULP_TEXT_DOMAIN)) . '</h1>';
        settings_errors('ulp_messages');
        echo '<form method="post">';
        wp_nonce_field('ulp_admin_action', 'ulp_nonce');
        echo '<input type="hidden" name="ulp_action" value="add_condition">';
        echo '<table class="form-table"><tr><th>' . __('شناسه (لاتین)', ULP_TEXT_DOMAIN) . '</th><td><input type="text" name="slug" class="regular-text" required></td></tr>';
        echo '<tr><th>' . __('عنوان', ULP_TEXT_DOMAIN) . '</th><td><input type="text" name="label" class="regular-text" required></td></tr>';
        echo '<tr><th>' . __('ضریب', ULP_TEXT_DOMAIN) . '</th><td><input type="number" step="0.01" name="multiplier" required></td></tr></table>';
        submit_button(__('افزودن', ULP_TEXT_DOMAIN));
        echo '</form>';

        echo '<h2>' . esc_html(__('لیست وضعیت‌ها', ULP_TEXT_DOMAIN)) . '</h2>';
        echo '<table class="widefat"><thead><tr><th>' . __('شناسه', ULP_TEXT_DOMAIN) . '</th><th>' . __('عنوان', ULP_TEXT_DOMAIN) . '</th><th>' . __('ضریب', ULP_TEXT_DOMAIN) . '</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            echo '<tr>';
            echo '<td>' . esc_html($r->slug) . '</td>';
            echo '<td>' . esc_html($r->label) . '</td>';
            echo '<td>' . esc_html($r->multiplier) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    }

    public function render_settings() {
        $settings = get_option('ulp_settings', []);
        echo '<div class="wrap"><h1>' . esc_html(__('تنظیمات', ULP_TEXT_DOMAIN)) . '</h1>';
        settings_errors('ulp_messages');
        echo '<form method="post">';
        wp_nonce_field('ulp_admin_action', 'ulp_nonce');
        echo '<input type="hidden" name="ulp_action" value="save_settings">';
        echo '<table class="form-table">';
        echo '<tr><th>' . __('نرخ استهلاک سال اول', ULP_TEXT_DOMAIN) . '</th><td><input type="number" name="depr_rate_year1" step="0.01" value="' . esc_attr(isset($settings['depr_rate_year1']) ? $settings['depr_rate_year1'] : 0.30) . '"></td></tr>';
        echo '<tr><th>' . __('نرخ استهلاک سال دوم', ULP_TEXT_DOMAIN) . '</th><td><input type="number" name="depr_rate_year2" step="0.01" value="' . esc_attr(isset($settings['depr_rate_year2']) ? $settings['depr_rate_year2'] : 0.15) . '"></td></tr>';
        echo '<tr><th>' . __('نرخ استهلاک سال سوم به بعد', ULP_TEXT_DOMAIN) . '</th><td><input type="number" name="depr_rate_yearN" step="0.01" value="' . esc_attr(isset($settings['depr_rate_yearN']) ? $settings['depr_rate_yearN'] : 0.10) . '"></td></tr>';
        echo '<tr><th>' . __('مقدار رُند کردن (تومان)', ULP_TEXT_DOMAIN) . '</th><td><input type="number" name="round_step" value="' . esc_attr(isset($settings['round_step']) ? $settings['round_step'] : 100000) . '"></td></tr>';
        echo '<tr><th>' . __('جداکننده هزارگان', ULP_TEXT_DOMAIN) . '</th><td><input type="text" name="thousands_sep" value="' . esc_attr(isset($settings['thousands_sep']) ? $settings['thousands_sep'] : ',') . '"></td></tr>';
        echo '<tr><th>' . __('برچسب واحد پول', ULP_TEXT_DOMAIN) . '</th><td><input type="text" name="currency_label" value="' . esc_attr(isset($settings['currency_label']) ? $settings['currency_label'] : __('تومان', ULP_TEXT_DOMAIN)) . '"></td></tr>';
        echo '</table>';
        submit_button(__('ذخیره تنظیمات', ULP_TEXT_DOMAIN));
        echo '</form></div>';
    }

    public function render_help() {
        echo '<div class="wrap"><h1>' . esc_html(__('راهنما و آموزش', ULP_TEXT_DOMAIN)) . '</h1>';
        echo '<p>' . esc_html(__('برای استفاده از افزونه، ابتدا فایل Excel شامل ستون‌های زیر را آماده کنید:', ULP_TEXT_DOMAIN)) . '</p>';
        echo '<ul><li>برند | مدل | سال عرضه | قیمت اولیه (به تومان) | CPU پایه | RAM پایه | GPU پایه | Storage پایه</li></ul>';
        echo '<p>' . esc_html(__('سپس از منوی ورود/خروج Excel، فایل را بارگذاری کنید.', ULP_TEXT_DOMAIN)) . '</p>';
        echo '<p>' . esc_html(__('برای نمایش فرم قیمت‌گذاری در برگه‌ها/نوشته‌ها از شورت‌کد زیر استفاده کنید:', ULP_TEXT_DOMAIN)) . '</p>';
        echo '<code>[used_laptop_pricer]</code>';
        echo '<h2>' . esc_html(__('سناریوی نمونه', ULP_TEXT_DOMAIN)) . '</h2>';
        echo '<p>' . esc_html(__('مثال: مدل Dell Inspiron 15 3000، سال 2020، قیمت اولیه 25,000,000 تومان، CPU Core i5، RAM 8GB، GPU MX130، HDD 1TB. کاربر RAM را 16GB و Storage را SSD انتخاب می‌کند و افزونه قیمت نهایی و بازه را محاسبه می‌کند.', ULP_TEXT_DOMAIN)) . '</p>';
        echo '</div>';
    }
}