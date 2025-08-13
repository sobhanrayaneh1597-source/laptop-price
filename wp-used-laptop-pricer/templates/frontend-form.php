<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="ulp-container" dir="rtl">
  <form id="ulp-form" class="ulp-form">
    <div class="ulp-row">
      <label><?php _e('برند', ULP_TEXT_DOMAIN); ?></label>
      <select id="ulp-brand" name="brand" required></select>
    </div>
    <div class="ulp-row">
      <label><?php _e('مدل', ULP_TEXT_DOMAIN); ?></label>
      <select id="ulp-model" name="model_id" required></select>
    </div>
    <div class="ulp-row">
      <label><?php _e('وضعیت ظاهری', ULP_TEXT_DOMAIN); ?></label>
      <select id="ulp-condition" name="condition" required>
        <?php
        global $wpdb;
        $rows = $wpdb->get_results("SELECT slug, label FROM {$wpdb->prefix}ulp_conditions ORDER BY sort_order, id");
        foreach ($rows as $r) {
            echo '<option value="' . esc_attr($r->slug) . '">' . esc_html($r->label) . '</option>';
        }
        ?>
      </select>
    </div>
    <div class="ulp-row">
      <label><?php _e('CPU', ULP_TEXT_DOMAIN); ?></label>
      <input type="text" id="ulp-cpu" name="cpu" placeholder="مثلاً Core i5" />
    </div>
    <div class="ulp-row">
      <label><?php _e('RAM (GB)', ULP_TEXT_DOMAIN); ?></label>
      <input type="number" id="ulp-ram" name="ram_gb" min="2" step="2" placeholder="8" />
    </div>
    <div class="ulp-row">
      <label><?php _e('GPU', ULP_TEXT_DOMAIN); ?></label>
      <input type="text" id="ulp-gpu" name="gpu" placeholder="مثلاً MX130" />
    </div>
    <div class="ulp-row">
      <label><?php _e('نوع و ظرفیت حافظه', ULP_TEXT_DOMAIN); ?></label>
      <div class="ulp-inline">
        <select id="ulp-storage-type" name="storage_type">
          <option value="HDD">HDD</option>
          <option value="SSD">SSD</option>
        </select>
        <select id="ulp-storage-size" name="storage_size">
          <option value="256GB">256GB</option>
          <option value="512GB">512GB</option>
          <option value="1TB">1TB</option>
        </select>
      </div>
    </div>

    <div class="ulp-row">
      <button type="submit" class="ulp-btn"><?php _e('محاسبه قیمت', ULP_TEXT_DOMAIN); ?></button>
    </div>
  </form>

  <div id="ulp-result" class="ulp-result" style="display:none;">
    <h3><?php _e('نتیجه قیمت‌گذاری', ULP_TEXT_DOMAIN); ?></h3>
    <div class="ulp-grid">
      <div><strong><?php _e('قیمت اولیه', ULP_TEXT_DOMAIN); ?>:</strong> <span id="ulp-base"></span></div>
      <div><strong><?php _e('مبلغ استهلاک', ULP_TEXT_DOMAIN); ?>:</strong> <span id="ulp-depr"></span></div>
      <div><strong><?php _e('قیمت پس از استهلاک', ULP_TEXT_DOMAIN); ?>:</strong> <span id="ulp-after-depr"></span></div>
      <div><strong><?php _e('ضریب وضعیت', ULP_TEXT_DOMAIN); ?>:</strong> <span id="ulp-condition-mul"></span></div>
      <div><strong><?php _e('تغییر قیمت قطعات', ULP_TEXT_DOMAIN); ?>:</strong> <span id="ulp-components"></span></div>
      <div><strong><?php _e('قیمت نهایی', ULP_TEXT_DOMAIN); ?>:</strong> <span id="ulp-final"></span></div>
      <div class="ulp-range"><strong><?php _e('بازه پیشنهادی', ULP_TEXT_DOMAIN); ?>:</strong> <span id="ulp-range"></span></div>
    </div>
  </div>
</div>