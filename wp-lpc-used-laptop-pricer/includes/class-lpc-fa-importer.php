<?php
if (!defined('ABSPATH')) { exit; }

require_once LPC_FA_PLUGIN_DIR . 'lib/simplexlsx.php';

class LPC_FA_Importer {
    public static function handle_upload() {
        LPC_FA_Helpers::verify_nonce_or_die('lpc_fa_import');

        if (empty($_FILES['lpc_fa_excel']['tmp_name'])) {
            wp_die(__('فایل اکسل انتخاب نشده است.', 'lpc-fa'));
        }

        $file = $_FILES['lpc_fa_excel'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'xlsx') {
            wp_die(__('لطفاً فایل Excel با پسوند xlsx بارگذاری کنید.', 'lpc-fa'));
        }

        if (!current_user_can('manage_options')) {
            wp_die(__('دسترسی غیرمجاز', 'lpc-fa'));
        }

        $xlsx = SimpleXLSX::parse($file['tmp_name']);
        if (!$xlsx) {
            wp_die(__('خواندن فایل اکسل ناموفق بود.', 'lpc-fa'));
        }

        $rows = $xlsx->rows();
        if (count($rows) < 2) {
            wp_die(__('فایل شامل داده نیست.', 'lpc-fa'));
        }

        // Expect header row
        // برند | مدل | سال عرضه | قیمت اولیه (تومان) | CPU لیست | RAM لیست | GPU لیست | SSD لیست | HDD لیست
        $imported = 0;
        foreach ($rows as $index => $row) {
            if ($index === 0) { continue; }
            $brand = sanitize_text_field($row[0] ?? '');
            $model = sanitize_text_field($row[1] ?? '');
            $year = intval($row[2] ?? 0);
            $base_price = intval(preg_replace('/[^0-9]/', '', $row[3] ?? '0'));
            $cpu_list = array_filter(array_map('trim', explode(',', (string)($row[4] ?? ''))));
            $ram_list = array_filter(array_map('trim', explode(',', (string)($row[5] ?? ''))));
            $gpu_list = array_filter(array_map('trim', explode(',', (string)($row[6] ?? ''))));
            $ssd_list = array_filter(array_map('trim', explode(',', (string)($row[7] ?? ''))));
            $hdd_list = array_filter(array_map('trim', explode(',', (string)($row[8] ?? ''))));

            if ($brand === '' || $model === '' || $year <= 0 || $base_price <= 0) { continue; }

            $model_id = LPC_FA_DB::upsert_model([
                'brand' => $brand,
                'model' => $model,
                'release_year' => $year,
                'base_price' => $base_price,
                'default_cpu' => $cpu_list[0] ?? null,
                'default_ram' => $ram_list[0] ?? null,
                'default_gpu' => $gpu_list[0] ?? null,
                'default_ssd' => $ssd_list[0] ?? null,
                'default_hdd' => $hdd_list[0] ?? null,
            ]);

            LPC_FA_DB::replace_model_components($model_id, 'cpu', $cpu_list);
            LPC_FA_DB::replace_model_components($model_id, 'ram', $ram_list);
            LPC_FA_DB::replace_model_components($model_id, 'gpu', $gpu_list);
            LPC_FA_DB::replace_model_components($model_id, 'ssd', $ssd_list);
            LPC_FA_DB::replace_model_components($model_id, 'hdd', $hdd_list);

            $imported++;
        }

        wp_redirect(add_query_arg(['page' => 'lpc-fa-import', 'imported' => $imported], admin_url('admin.php')));
        exit;
    }
}