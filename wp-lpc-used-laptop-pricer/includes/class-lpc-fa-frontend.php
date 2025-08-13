<?php
if (!defined('ABSPATH')) { exit; }

class LPC_FA_Frontend {
    public static function init() {
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue']);
    }

    public static function enqueue() {
        wp_register_style('lpc-fa-frontend', LPC_FA_PLUGIN_URL . 'assets/css/frontend.css', [], LPC_FA_VERSION);
        wp_register_script('lpc-fa-frontend', LPC_FA_PLUGIN_URL . 'assets/js/frontend.js', ['jquery'], LPC_FA_VERSION, true);
        wp_localize_script('lpc-fa-frontend', 'LPCFA', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lpc_fa_nonce'),
            'i18n' => [
                'select_brand' => __('انتخاب برند', 'lpc-fa'),
                'select_model' => __('انتخاب مدل', 'lpc-fa'),
                'loading' => __('در حال بارگذاری...', 'lpc-fa'),
            ],
        ]);
    }

    public static function render_shortcode() {
        wp_enqueue_style('lpc-fa-frontend');
        wp_enqueue_script('lpc-fa-frontend');

        $brands = LPC_FA_DB::get_brands();

        ob_start();
        ?>
        <div class="lpcfa-wrapper" dir="rtl">
            <div class="lpcfa-row">
                <label><?php _e('برند', 'lpc-fa'); ?></label>
                <select id="lpcfa-brand">
                    <option value=""><?php _e('انتخاب برند', 'lpc-fa'); ?></option>
                    <?php foreach ($brands as $b): ?>
                        <option value="<?php echo esc_attr($b); ?>"><?php echo esc_html($b); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="lpcfa-row">
                <label><?php _e('مدل', 'lpc-fa'); ?></label>
                <select id="lpcfa-model" disabled>
                    <option value=""><?php _e('ابتدا برند را انتخاب کنید', 'lpc-fa'); ?></option>
                </select>
            </div>
            <div id="lpcfa-configs" class="lpcfa-configs" style="display:none">
                <div class="lpcfa-row"><label>CPU</label><select id="lpcfa-cpu"></select></div>
                <div class="lpcfa-row"><label>RAM</label><select id="lpcfa-ram"></select></div>
                <div class="lpcfa-row"><label>GPU</label><select id="lpcfa-gpu"></select></div>
                <div class="lpcfa-row"><label>SSD</label><select id="lpcfa-ssd"></select></div>
                <div class="lpcfa-row"><label>HDD</label><select id="lpcfa-hdd"></select></div>
                <div class="lpcfa-row"><label><?php _e('وضعیت ظاهری', 'lpc-fa'); ?></label><select id="lpcfa-condition"></select></div>
                <div class="lpcfa-row"><button id="lpcfa-calc" class="lpcfa-btn"><?php _e('محاسبه قیمت', 'lpc-fa'); ?></button></div>
            </div>
            <div id="lpcfa-result" class="lpcfa-result" style="display:none">
                <div class="lpcfa-price"><span class="label"><?php _e('قیمت پیشنهادی', 'lpc-fa'); ?>:</span> <span id="lpcfa-price"></span></div>
                <div class="lpcfa-range"><span class="label"><?php _e('بازه قیمتی', 'lpc-fa'); ?>:</span> <span id="lpcfa-lower"></span> - <span id="lpcfa-upper"></span></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}