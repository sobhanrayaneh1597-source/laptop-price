<?php
if (!defined('ABSPATH')) { exit; }

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ULP_Excel {
    public static function import_models($file_path) {
        global $wpdb;
        $table = $wpdb->prefix . 'ulp_models';
        $spreadsheet = IOFactory::load($file_path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        // Expected header: برند | مدل | سال عرضه | قیمت اولیه (به تومان) | CPU پایه | RAM پایه | GPU پایه | Storage پایه
        $count = 0;
        foreach ($rows as $index => $row) {
            if ($index === 0) { continue; } // skip header
            $brand = isset($row[0]) ? sanitize_text_field($row[0]) : '';
            $model = isset($row[1]) ? sanitize_text_field($row[1]) : '';
            $release_year = isset($row[2]) ? intval($row[2]) : 0;
            $base_price = isset($row[3]) ? intval(preg_replace('/[^0-9]/', '', (string) $row[3])) : 0;
            $base_cpu = isset($row[4]) ? sanitize_text_field($row[4]) : '';
            $base_ram_gb = isset($row[5]) ? intval($row[5]) : 0;
            $base_gpu = isset($row[6]) ? sanitize_text_field($row[6]) : '';
            $base_storage = isset($row[7]) ? sanitize_text_field($row[7]) : '';

            if ($brand === '' || $model === '' || $release_year === 0 || $base_price === 0) {
                continue;
            }

            $existing_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE brand=%s AND model=%s", $brand, $model));
            $data = [
                'brand' => $brand,
                'model' => $model,
                'release_year' => $release_year,
                'base_price' => $base_price,
                'base_cpu' => $base_cpu,
                'base_ram_gb' => $base_ram_gb,
                'base_gpu' => $base_gpu,
                'base_storage' => $base_storage,
                'updated_at' => current_time('mysql')
            ];
            if ($existing_id) {
                $wpdb->update($table, $data, ['id' => $existing_id]);
            } else {
                $data['created_at'] = current_time('mysql');
                $wpdb->insert($table, $data);
            }
            $count++;
        }
        return $count;
    }

    public static function export_models() {
        global $wpdb;
        $table = $wpdb->prefix . 'ulp_models';
        $rows = $wpdb->get_results("SELECT brand, model, release_year, base_price, base_cpu, base_ram_gb, base_gpu, base_storage FROM {$table} ORDER BY brand, model", ARRAY_A);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = ['برند', 'مدل', 'سال عرضه', 'قیمت اولیه (به تومان)', 'CPU پایه', 'RAM پایه', 'GPU پایه', 'Storage پایه'];
        $sheet->fromArray($headers, null, 'A1');
        $rowIndex = 2;
        foreach ($rows as $row) {
            $sheet->fromArray([
                $row['brand'],
                $row['model'],
                $row['release_year'],
                $row['base_price'],
                $row['base_cpu'],
                $row['base_ram_gb'],
                $row['base_gpu'],
                $row['base_storage'],
            ], null, 'A' . $rowIndex);
            $rowIndex++;
        }

        $writer = new Xlsx($spreadsheet);
        $upload_dir = wp_upload_dir();
        $file_path = trailingslashit($upload_dir['basedir']) . 'ulp-models-export.xlsx';
        $writer->save($file_path);

        return trailingslashit($upload_dir['baseurl']) . 'ulp-models-export.xlsx';
    }
}