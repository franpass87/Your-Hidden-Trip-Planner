<?php
/**
 * Handle Plugin Settings
 * 
 * @package YourHiddenTrip
 */

if (!defined('ABSPATH')) exit;

class YHT_Settings {
    
    /**
     * Render settings page
     */
    public function render_page() {
        if(!current_user_can('manage_options')) return;
        
        $settings = YHT_Plugin::get_instance()->get_settings();
        
        if(isset($_POST['yht_save'])){
            check_admin_referer('yht_settings');
            $new_settings = array(
                'notify_email'     => sanitize_email($_POST['notify_email'] ?? ''),
                'brevo_api_key'    => sanitize_text_field($_POST['brevo_api_key'] ?? ''),
                'ga4_id'           => sanitize_text_field($_POST['ga4_id'] ?? ''),
                'wc_deposit_pct'   => sanitize_text_field($_POST['wc_deposit_pct'] ?? '20'),
                'wc_price_per_pax' => sanitize_text_field($_POST['wc_price_per_pax'] ?? '80')
            );
            update_option(YHT_OPT, $new_settings);
            $settings = $new_settings;
            echo '<div class="updated"><p>Impostazioni salvate.</p></div>';
        }
        
        $this->render_form($settings);
    }
    
    /**
     * Render settings form
     */
    private function render_form($settings) {
        ?>
        <div class="wrap">
            <h1>Your Hidden Trip – Impostazioni</h1>
            <form method="post">
                <?php wp_nonce_field('yht_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Email notifiche</th>
                        <td><input type="email" name="notify_email" value="<?php echo esc_attr($settings['notify_email']); ?>" class="regular-text"/></td>
                    </tr>
                    <tr>
                        <th scope="row">Brevo API Key</th>
                        <td><input type="text" name="brevo_api_key" value="<?php echo esc_attr($settings['brevo_api_key']); ?>" class="regular-text"/></td>
                    </tr>
                    <tr>
                        <th scope="row">GA4 ID (opz.)</th>
                        <td><input type="text" name="ga4_id" value="<?php echo esc_attr($settings['ga4_id']); ?>" class="regular-text" placeholder="G-XXXXXX"/></td>
                    </tr>
                    <tr>
                        <th scope="row">Woo – Prezzo base per pax (€)</th>
                        <td><input type="number" step="1" name="wc_price_per_pax" value="<?php echo esc_attr($settings['wc_price_per_pax']); ?>"/></td>
                    </tr>
                    <tr>
                        <th scope="row">Woo – Acconto (%)</th>
                        <td><input type="number" step="1" name="wc_deposit_pct" value="<?php echo esc_attr($settings['wc_deposit_pct']); ?>"/></td>
                    </tr>
                </table>
                <p><button class="button button-primary" name="yht_save" value="1">Salva</button></p>
            </form>
        </div>
        <?php
    }
}