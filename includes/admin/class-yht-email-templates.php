<?php
/**
 * Email Templates Management System
 * 
 * @package YourHiddenTrip
 * @version 6.3
 */

if (!defined('ABSPATH')) exit;

/**
 * Email Templates Manager Class
 */
class YHT_Email_Templates {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_page'), 15);
        add_action('wp_ajax_yht_save_email_template', array($this, 'ajax_save_email_template'));
        add_action('wp_ajax_yht_preview_email_template', array($this, 'ajax_preview_email_template'));
        add_action('wp_ajax_yht_reset_email_template', array($this, 'ajax_reset_email_template'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Add menu page
     */
    public function add_menu_page() {
        add_submenu_page(
            'yht-dashboard',
            __('📧 Template Email', 'your-hidden-trip'),
            __('📧 Template Email', 'your-hidden-trip'),
            'manage_options',
            'yht-email-templates',
            array($this, 'render_page')
        );
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'your-hidden-trip_page_yht-email-templates') return;

        wp_enqueue_script('wp-color-picker');
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_editor();
        
        wp_localize_script('jquery', 'yhtEmailTemplates', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('yht_email_templates_nonce'),
            'strings' => array(
                'saved' => __('Template salvato con successo!', 'your-hidden-trip'),
                'error' => __('Errore nel salvare il template.', 'your-hidden-trip'),
                'preview' => __('Anteprima generata', 'your-hidden-trip'),
                'reset_confirm' => __('Sei sicuro di voler ripristinare il template predefinito?', 'your-hidden-trip'),
                'reset_success' => __('Template ripristinato con successo!', 'your-hidden-trip')
            )
        ));
    }

    /**
     * Get default email templates
     */
    public function get_default_templates() {
        return array(
            'booking_confirmation' => array(
                'name' => __('Conferma Prenotazione', 'your-hidden-trip'),
                'subject' => __('Conferma della tua prenotazione - {booking_id}', 'your-hidden-trip'),
                'content' => $this->get_default_booking_confirmation_template()
            ),
            'booking_reminder' => array(
                'name' => __('Promemoria Prenotazione', 'your-hidden-trip'),
                'subject' => __('Promemoria: La tua avventura inizia presto! - {booking_id}', 'your-hidden-trip'),
                'content' => $this->get_default_booking_reminder_template()
            ),
            'booking_cancelled' => array(
                'name' => __('Prenotazione Cancellata', 'your-hidden-trip'),
                'subject' => __('Cancellazione prenotazione - {booking_id}', 'your-hidden-trip'),
                'content' => $this->get_default_booking_cancelled_template()
            ),
            'welcome_new_customer' => array(
                'name' => __('Benvenuto Nuovo Cliente', 'your-hidden-trip'),
                'subject' => __('Benvenuto in Your Hidden Trip!', 'your-hidden-trip'),
                'content' => $this->get_default_welcome_template()
            ),
            'payment_reminder' => array(
                'name' => __('Promemoria Pagamento', 'your-hidden-trip'),
                'subject' => __('Promemoria pagamento - {booking_id}', 'your-hidden-trip'),
                'content' => $this->get_default_payment_reminder_template()
            )
        );
    }

    /**
     * Get saved templates
     */
    public function get_templates() {
        $saved_templates = get_option('yht_email_templates', array());
        $default_templates = $this->get_default_templates();
        
        return wp_parse_args($saved_templates, $default_templates);
    }

    /**
     * Get available placeholders
     */
    public function get_placeholders() {
        return array(
            'general' => array(
                '{site_name}' => __('Nome del sito', 'your-hidden-trip'),
                '{site_url}' => __('URL del sito', 'your-hidden-trip'),
                '{admin_email}' => __('Email amministratore', 'your-hidden-trip'),
                '{current_date}' => __('Data corrente', 'your-hidden-trip'),
                '{current_year}' => __('Anno corrente', 'your-hidden-trip')
            ),
            'customer' => array(
                '{customer_name}' => __('Nome cliente', 'your-hidden-trip'),
                '{customer_email}' => __('Email cliente', 'your-hidden-trip'),
                '{customer_phone}' => __('Telefono cliente', 'your-hidden-trip')
            ),
            'booking' => array(
                '{booking_id}' => __('ID Prenotazione', 'your-hidden-trip'),
                '{booking_date}' => __('Data prenotazione', 'your-hidden-trip'),
                '{booking_status}' => __('Stato prenotazione', 'your-hidden-trip'),
                '{booking_total}' => __('Totale prenotazione', 'your-hidden-trip'),
                '{booking_deposit}' => __('Acconto prenotazione', 'your-hidden-trip'),
                '{booking_balance}' => __('Saldo rimanente', 'your-hidden-trip'),
                '{trip_date}' => __('Data viaggio', 'your-hidden-trip'),
                '{trip_location}' => __('Destinazione', 'your-hidden-trip'),
                '{participants}' => __('Numero partecipanti', 'your-hidden-trip')
            )
        );
    }

    /**
     * Render the email templates page
     */
    public function render_page() {
        $templates = $this->get_templates();
        $placeholders = $this->get_placeholders();
        $first_key = array_key_first($templates);
        ?>
        <div class="wrap yht-email-templates-page">
            <div class="yht-header">
                <h1>📧 <?php _e('Gestione Template Email', 'your-hidden-trip'); ?></h1>
                <p class="description">
                    <?php _e('Personalizza i template email utilizzati dal sistema per comunicare con i clienti.', 'your-hidden-trip'); ?>
                </p>
            </div>

            <div class="yht-templates-container">
                <div class="yht-template-selector">
                    <h2><?php _e('Seleziona Template', 'your-hidden-trip'); ?></h2>
                    <div class="template-tabs">
                        <?php $first = true; foreach ($templates as $key => $template): ?>
                            <button class="template-tab <?php echo $first ? 'active' : ''; ?>" 
                                    data-template="<?php echo esc_attr($key); ?>">
                                <?php echo esc_html($template['name']); ?>
                            </button>
                            <?php $first = false; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="yht-template-editor">
                    <?php foreach ($templates as $key => $template): ?>
                        <div class="template-content <?php echo $key === $first_key ? 'active' : ''; ?>" 
                             data-template="<?php echo esc_attr($key); ?>">
                            
                            <div class="template-header">
                                <h3><?php echo esc_html($template['name']); ?></h3>
                                <div class="template-actions">
                                    <button class="button preview-template" data-template="<?php echo esc_attr($key); ?>">
                                        <?php _e('🔍 Anteprima', 'your-hidden-trip'); ?>
                                    </button>
                                    <button class="button button-primary save-template" data-template="<?php echo esc_attr($key); ?>">
                                        <?php _e('💾 Salva', 'your-hidden-trip'); ?>
                                    </button>
                                    <button class="button button-secondary reset-template" data-template="<?php echo esc_attr($key); ?>">
                                        <?php _e('🔄 Ripristina', 'your-hidden-trip'); ?>
                                    </button>
                                </div>
                            </div>

                            <div class="template-form">
                                <div class="form-group">
                                    <label for="template_subject_<?php echo $key; ?>">
                                        <?php _e('Oggetto Email', 'your-hidden-trip'); ?>
                                    </label>
                                    <input type="text" 
                                           id="template_subject_<?php echo $key; ?>" 
                                           class="template-subject regular-text" 
                                           value="<?php echo esc_attr($template['subject']); ?>"
                                           placeholder="<?php _e('Inserisci oggetto email...', 'your-hidden-trip'); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="template_content_<?php echo $key; ?>">
                                        <?php _e('Contenuto Email', 'your-hidden-trip'); ?>
                                    </label>
                                    <textarea id="template_content_<?php echo $key; ?>" 
                                              class="template-content-editor" 
                                              rows="15" 
                                              style="width: 100%;"><?php echo esc_textarea($template['content']); ?></textarea>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="yht-placeholders-sidebar">
                    <h3><?php _e('Placeholder Disponibili', 'your-hidden-trip'); ?></h3>
                    <p class="description">
                        <?php _e('Clicca su un placeholder per copiarlo negli appunti.', 'your-hidden-trip'); ?>
                    </p>
                    
                    <?php foreach ($placeholders as $category => $items): ?>
                        <div class="placeholder-category">
                            <h4><?php echo ucfirst($category); ?></h4>
                            <div class="placeholder-list">
                                <?php foreach ($items as $placeholder => $description): ?>
                                    <div class="placeholder-item" data-placeholder="<?php echo esc_attr($placeholder); ?>">
                                        <code><?php echo esc_html($placeholder); ?></code>
                                        <span class="placeholder-description"><?php echo esc_html($description); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div id="yht-template-preview-modal" class="yht-modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2><?php _e('Anteprima Template Email', 'your-hidden-trip'); ?></h2>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="preview-controls">
                            <label>
                                <?php _e('Oggetto:', 'your-hidden-trip'); ?>
                                <div id="preview-subject"></div>
                            </label>
                        </div>
                        <div class="preview-content">
                            <iframe id="preview-iframe" style="width: 100%; height: 400px; border: 1px solid #ddd;"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .yht-email-templates-page {
                max-width: 1400px;
            }

            .yht-header {
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 1px solid #e1e1e1;
            }

            .yht-templates-container {
                display: grid;
                grid-template-columns: 200px 1fr 300px;
                gap: 30px;
                margin-top: 20px;
            }

            .yht-template-selector h2 {
                margin-bottom: 15px;
                font-size: 18px;
            }

            .template-tabs {
                display: flex;
                flex-direction: column;
                gap: 5px;
            }

            .template-tab {
                background: #f9f9f9;
                border: 1px solid #ddd;
                padding: 12px 15px;
                text-align: left;
                cursor: pointer;
                border-radius: 4px;
                font-size: 14px;
            }

            .template-tab:hover {
                background: #e9e9e9;
            }

            .template-tab.active {
                background: #0073aa;
                color: white;
                border-color: #0073aa;
            }

            .yht-template-editor {
                background: white;
                border: 1px solid #e1e1e1;
                border-radius: 8px;
                padding: 25px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }

            .template-content {
                display: none;
            }

            .template-content.active {
                display: block;
            }

            .template-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 25px;
                padding-bottom: 15px;
                border-bottom: 1px solid #e1e1e1;
            }

            .template-header h3 {
                margin: 0;
                font-size: 20px;
                color: #333;
            }

            .template-actions {
                display: flex;
                gap: 10px;
            }

            .template-form .form-group {
                margin-bottom: 25px;
            }

            .template-form label {
                display: block;
                font-weight: 600;
                margin-bottom: 8px;
                color: #333;
            }

            .template-subject {
                width: 100%;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 14px;
            }

            .template-content-editor {
                width: 100%;
                font-family: monospace;
                font-size: 13px;
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 10px;
            }

            .yht-placeholders-sidebar {
                background: white;
                border: 1px solid #e1e1e1;
                border-radius: 8px;
                padding: 20px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }

            .yht-placeholders-sidebar h3 {
                margin: 0 0 15px 0;
                font-size: 18px;
                color: #333;
            }

            .placeholder-category {
                margin-bottom: 20px;
            }

            .placeholder-category h4 {
                margin: 0 0 10px 0;
                font-size: 14px;
                color: #666;
                text-transform: uppercase;
            }

            .placeholder-item {
                padding: 8px;
                border: 1px solid #e1e1e1;
                border-radius: 4px;
                margin-bottom: 5px;
                cursor: pointer;
                transition: all 0.2s;
            }

            .placeholder-item:hover {
                background: #f9f9f9;
                border-color: #0073aa;
            }

            .placeholder-item code {
                display: block;
                font-weight: bold;
                color: #0073aa;
                margin-bottom: 3px;
            }

            .placeholder-description {
                font-size: 12px;
                color: #666;
            }

            .yht-modal {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.7);
                z-index: 100000;
            }

            .modal-content {
                background: white;
                width: 80%;
                max-width: 900px;
                margin: 50px auto;
                border-radius: 8px;
                overflow: hidden;
            }

            .modal-header {
                padding: 20px;
                background: #f9f9f9;
                border-bottom: 1px solid #e1e1e1;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .modal-close {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: #666;
            }

            .modal-body {
                padding: 20px;
            }

            .preview-controls {
                margin-bottom: 15px;
                padding: 15px;
                background: #f9f9f9;
                border-radius: 4px;
            }

            #preview-subject {
                font-weight: bold;
                font-size: 16px;
                color: #333;
                margin-top: 5px;
            }

            @media (max-width: 1200px) {
                .yht-templates-container {
                    grid-template-columns: 1fr;
                    gap: 20px;
                }
            }
        </style>

        <script>
            jQuery(document).ready(function($) {
                // Template tab switching
                $('.template-tab').click(function() {
                    var templateKey = $(this).data('template');
                    
                    $('.template-tab').removeClass('active');
                    $(this).addClass('active');
                    
                    $('.template-content').removeClass('active');
                    $('.template-content[data-template="' + templateKey + '"]').addClass('active');
                });

                // Placeholder click to copy
                $('.placeholder-item').click(function() {
                    var placeholder = $(this).data('placeholder');
                    navigator.clipboard.writeText(placeholder).then(function() {
                        var $item = $('.placeholder-item[data-placeholder="' + placeholder + '"]');
                        var originalBg = $item.css('background-color');
                        $item.css('background-color', '#d4edda');
                        setTimeout(function() {
                            $item.css('background-color', originalBg);
                        }, 500);
                    });
                });

                // Save template
                $('.save-template').click(function() {
                    var templateKey = $(this).data('template');
                    var $content = $('.template-content[data-template="' + templateKey + '"]');
                    var subject = $content.find('.template-subject').val();
                    var content = $content.find('.template-content-editor').val();
                    
                    $.post(yhtEmailTemplates.ajaxurl, {
                        action: 'yht_save_email_template',
                        nonce: yhtEmailTemplates.nonce,
                        template_key: templateKey,
                        subject: subject,
                        content: content
                    }, function(response) {
                        if (response.success) {
                            alert(yhtEmailTemplates.strings.saved);
                        } else {
                            alert(yhtEmailTemplates.strings.error);
                        }
                    });
                });

                // Preview template
                $('.preview-template').click(function() {
                    var templateKey = $(this).data('template');
                    var $content = $('.template-content[data-template="' + templateKey + '"]');
                    var subject = $content.find('.template-subject').val();
                    var content = $content.find('.template-content-editor').val();
                    
                    $.post(yhtEmailTemplates.ajaxurl, {
                        action: 'yht_preview_email_template',
                        nonce: yhtEmailTemplates.nonce,
                        subject: subject,
                        content: content
                    }, function(response) {
                        if (response.success) {
                            $('#preview-subject').text(response.data.subject);
                            $('#preview-iframe').contents().find('html').html(response.data.content);
                            $('#yht-template-preview-modal').show();
                        }
                    });
                });

                // Reset template
                $('.reset-template').click(function() {
                    if (!confirm(yhtEmailTemplates.strings.reset_confirm)) return;
                    
                    var templateKey = $(this).data('template');
                    
                    $.post(yhtEmailTemplates.ajaxurl, {
                        action: 'yht_reset_email_template',
                        nonce: yhtEmailTemplates.nonce,
                        template_key: templateKey
                    }, function(response) {
                        if (response.success) {
                            var $content = $('.template-content[data-template="' + templateKey + '"]');
                            $content.find('.template-subject').val(response.data.subject);
                            $content.find('.template-content-editor').val(response.data.content);
                            alert(yhtEmailTemplates.strings.reset_success);
                        }
                    });
                });

                // Close modal
                $('.modal-close').click(function() {
                    $('#yht-template-preview-modal').hide();
                });

                // Close modal on background click
                $('#yht-template-preview-modal').click(function(e) {
                    if (e.target === this) {
                        $(this).hide();
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Default booking confirmation template
     */
    private function get_default_booking_confirmation_template() {
        return '<div style="max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; color: white;">
        <h1 style="margin: 0; font-size: 28px;">🎉 Prenotazione Confermata!</h1>
        <p style="margin: 10px 0 0 0; font-size: 18px;">La tua avventura ti aspetta</p>
    </div>
    
    <div style="padding: 30px; background: white;">
        <p style="font-size: 18px; margin-bottom: 25px;">
            Ciao <strong>{customer_name}</strong>,
        </p>
        
        <p>Siamo entusiasti di confermare la tua prenotazione con <strong>{site_name}</strong>!</p>
        
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0;">
            <h3 style="color: #495057; margin: 0 0 15px 0;">📋 Dettagli Prenotazione</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; font-weight: bold;">ID Prenotazione:</td>
                    <td style="padding: 8px 0;">{booking_id}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: bold;">Data Viaggio:</td>
                    <td style="padding: 8px 0;">{trip_date}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: bold;">Destinazione:</td>
                    <td style="padding: 8px 0;">{trip_location}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: bold;">Partecipanti:</td>
                    <td style="padding: 8px 0;">{participants}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: bold;">Totale:</td>
                    <td style="padding: 8px 0; font-size: 18px; color: #28a745;"><strong>€{booking_total}</strong></td>
                </tr>
            </table>
        </div>
        
        <p>Ti contatteremo presto con ulteriori dettagli per il tuo viaggio.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{site_url}" style="background: #28a745; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; display: inline-block;">
                Visita il nostro sito
            </a>
        </div>
    </div>
    
    <div style="background: #f8f9fa; padding: 20px; text-align: center; font-size: 14px; color: #6c757d;">
        <p>Grazie per aver scelto {site_name}!</p>
        <p>Per qualsiasi domanda, contattaci: <a href="mailto:{admin_email}">{admin_email}</a></p>
    </div>
</div>';
    }

    /**
     * Default booking reminder template
     */
    private function get_default_booking_reminder_template() {
        return '<div style="max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%); padding: 30px; text-align: center; color: white;">
        <h1 style="margin: 0; font-size: 28px;">⏰ Il tuo viaggio si avvicina!</h1>
        <p style="margin: 10px 0 0 0; font-size: 18px;">Preparati per l\'avventura</p>
    </div>
    
    <div style="padding: 30px; background: white;">
        <p style="font-size: 18px; margin-bottom: 25px;">
            Ciao <strong>{customer_name}</strong>,
        </p>
        
        <p>Il tuo viaggio con <strong>{site_name}</strong> inizia presto! Ecco un promemoria dei dettagli:</p>
        
        <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; border-radius: 8px; margin: 25px 0;">
            <h3 style="color: #856404; margin: 0 0 15px 0;">📅 Dettagli del Viaggio</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; font-weight: bold;">Data:</td>
                    <td style="padding: 8px 0; font-size: 16px; color: #d63031;"><strong>{trip_date}</strong></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: bold;">Destinazione:</td>
                    <td style="padding: 8px 0;">{trip_location}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: bold;">Prenotazione:</td>
                    <td style="padding: 8px 0;">{booking_id}</td>
                </tr>
            </table>
        </div>
        
        <h3>📝 Cosa portare:</h3>
        <ul>
            <li>Documento d\'identità valido</li>
            <li>Abbigliamento comodo</li>
            <li>Scarpe da trekking (se necessario)</li>
            <li>Macchina fotografica per immortalare i momenti</li>
        </ul>
        
        <p>Non vediamo l\'ora di vederti!</p>
    </div>
    
    <div style="background: #f8f9fa; padding: 20px; text-align: center; font-size: 14px; color: #6c757d;">
        <p>Per qualsiasi domanda, contattaci: <a href="mailto:{admin_email}">{admin_email}</a></p>
    </div>
</div>';
    }

    /**
     * Default booking cancelled template
     */
    private function get_default_booking_cancelled_template() {
        return '<div style="max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="background: #dc3545; padding: 30px; text-align: center; color: white;">
        <h1 style="margin: 0; font-size: 28px;">❌ Prenotazione Cancellata</h1>
        <p style="margin: 10px 0 0 0; font-size: 18px;">Ci dispiace per l\'inconveniente</p>
    </div>
    
    <div style="padding: 30px; background: white;">
        <p style="font-size: 18px; margin-bottom: 25px;">
            Ciao <strong>{customer_name}</strong>,
        </p>
        
        <p>Ci dispiace informarti che la tua prenotazione <strong>{booking_id}</strong> è stata cancellata.</p>
        
        <div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 20px; border-radius: 8px; margin: 25px 0;">
            <h3 style="color: #721c24; margin: 0 0 15px 0;">📋 Dettagli Cancellazione</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; font-weight: bold;">Prenotazione:</td>
                    <td style="padding: 8px 0;">{booking_id}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: bold;">Data Cancellazione:</td>
                    <td style="padding: 8px 0;">{current_date}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: bold;">Stato:</td>
                    <td style="padding: 8px 0; color: #dc3545;"><strong>Cancellato</strong></td>
                </tr>
            </table>
        </div>
        
        <p>Se hai pagato un acconto, verrà rimborsato secondo i nostri termini e condizioni. Ti contatteremo presto con i dettagli.</p>
        
        <p>Speriamo di poterti servire in futuro per una nuova avventura!</p>
    </div>
    
    <div style="background: #f8f9fa; padding: 20px; text-align: center; font-size: 14px; color: #6c757d;">
        <p>Per qualsiasi domanda, contattaci: <a href="mailto:{admin_email}">{admin_email}</a></p>
    </div>
</div>';
    }

    /**
     * Default welcome template
     */
    private function get_default_welcome_template() {
        return '<div style="max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%); padding: 30px; text-align: center; color: white;">
        <h1 style="margin: 0; font-size: 28px;">🎉 Benvenuto!</h1>
        <p style="margin: 10px 0 0 0; font-size: 18px;">La tua avventura inizia qui</p>
    </div>
    
    <div style="padding: 30px; background: white;">
        <p style="font-size: 18px; margin-bottom: 25px;">
            Ciao <strong>{customer_name}</strong>,
        </p>
        
        <p>Benvenuto nella famiglia di <strong>{site_name}</strong>! Siamo entusiasti di averti con noi.</p>
        
        <div style="background: #e7f3ff; border: 1px solid #b3d9ff; padding: 20px; border-radius: 8px; margin: 25px 0;">
            <h3 style="color: #0066cc; margin: 0 0 15px 0;">🌟 Cosa ti aspetta</h3>
            <ul style="margin: 0; padding-left: 20px;">
                <li>Esperienze uniche e indimenticabili</li>
                <li>Luoghi nascosti e segreti</li>
                <li>Guide esperte e appassionate</li>
                <li>Momenti di pura meraviglia</li>
            </ul>
        </div>
        
        <p>Esplora il nostro sito per scoprire tutte le avventure che abbiamo preparato per te!</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{site_url}" style="background: #0984e3; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; display: inline-block;">
                Scopri le nostre esperienze
            </a>
        </div>
    </div>
    
    <div style="background: #f8f9fa; padding: 20px; text-align: center; font-size: 14px; color: #6c757d;">
        <p>Seguici sui social per non perdere le ultime novità!</p>
        <p>Contattaci: <a href="mailto:{admin_email}">{admin_email}</a></p>
    </div>
</div>';
    }

    /**
     * Default payment reminder template
     */
    private function get_default_payment_reminder_template() {
        return '<div style="max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="background: linear-gradient(135deg, #fdcb6e 0%, #e17055 100%); padding: 30px; text-align: center; color: white;">
        <h1 style="margin: 0; font-size: 28px;">💳 Promemoria Pagamento</h1>
        <p style="margin: 10px 0 0 0; font-size: 18px;">È tempo di completare il pagamento</p>
    </div>
    
    <div style="padding: 30px; background: white;">
        <p style="font-size: 18px; margin-bottom: 25px;">
            Ciao <strong>{customer_name}</strong>,
        </p>
        
        <p>Ti ricordiamo che è necessario completare il pagamento per la tua prenotazione <strong>{booking_id}</strong>.</p>
        
        <div style="background: #fff8e1; border: 1px solid #ffecb3; padding: 20px; border-radius: 8px; margin: 25px 0;">
            <h3 style="color: #ff8f00; margin: 0 0 15px 0;">💰 Dettagli Pagamento</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; font-weight: bold;">Prenotazione:</td>
                    <td style="padding: 8px 0;">{booking_id}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: bold;">Totale:</td>
                    <td style="padding: 8px 0;">€{booking_total}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: bold;">Acconto Pagato:</td>
                    <td style="padding: 8px 0; color: #28a745;">€{booking_deposit}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: bold;">Saldo Rimanente:</td>
                    <td style="padding: 8px 0; font-size: 18px; color: #dc3545;"><strong>€{booking_balance}</strong></td>
                </tr>
            </table>
        </div>
        
        <p>Ti preghiamo di completare il pagamento entro la data del viaggio per confermare la tua partecipazione.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{site_url}" style="background: #28a745; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; display: inline-block;">
                Completa il Pagamento
            </a>
        </div>
    </div>
    
    <div style="background: #f8f9fa; padding: 20px; text-align: center; font-size: 14px; color: #6c757d;">
        <p>Per assistenza con il pagamento, contattaci: <a href="mailto:{admin_email}">{admin_email}</a></p>
    </div>
</div>';
    }

    /**
     * AJAX: Save email template
     */
    public function ajax_save_email_template() {
        check_ajax_referer('yht_email_templates_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permessi insufficienti.', 'your-hidden-trip'));
        }

        $template_key = sanitize_key($_POST['template_key']);
        $subject = sanitize_text_field($_POST['subject']);
        $content = wp_kses_post($_POST['content']);

        $templates = $this->get_templates();
        if (!isset($templates[$template_key])) {
            wp_die(__('Template non valido.', 'your-hidden-trip'));
        }

        $templates[$template_key]['subject'] = $subject;
        $templates[$template_key]['content'] = $content;

        update_option('yht_email_templates', $templates);

        wp_send_json_success(array(
            'message' => __('Template salvato con successo!', 'your-hidden-trip')
        ));
    }

    /**
     * AJAX: Preview email template
     */
    public function ajax_preview_email_template() {
        check_ajax_referer('yht_email_templates_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permessi insufficienti.', 'your-hidden-trip'));
        }

        $subject = sanitize_text_field($_POST['subject']);
        $content = wp_kses_post($_POST['content']);

        // Replace placeholders with sample data
        $sample_data = $this->get_sample_data();
        
        $preview_subject = $this->replace_placeholders($subject, $sample_data);
        $preview_content = $this->replace_placeholders($content, $sample_data);

        wp_send_json_success(array(
            'subject' => $preview_subject,
            'content' => $preview_content
        ));
    }

    /**
     * AJAX: Reset email template to default
     */
    public function ajax_reset_email_template() {
        check_ajax_referer('yht_email_templates_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permessi insufficienti.', 'your-hidden-trip'));
        }

        $template_key = sanitize_key($_POST['template_key']);
        $default_templates = $this->get_default_templates();
        
        if (!isset($default_templates[$template_key])) {
            wp_die(__('Template non valido.', 'your-hidden-trip'));
        }

        $templates = $this->get_templates();
        $templates[$template_key] = $default_templates[$template_key];
        
        update_option('yht_email_templates', $templates);

        wp_send_json_success(array(
            'subject' => $default_templates[$template_key]['subject'],
            'content' => $default_templates[$template_key]['content'],
            'message' => __('Template ripristinato con successo!', 'your-hidden-trip')
        ));
    }

    /**
     * Get sample data for preview
     */
    private function get_sample_data() {
        return array(
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url(),
            'admin_email' => get_option('admin_email'),
            'current_date' => date('d/m/Y'),
            'current_year' => date('Y'),
            'customer_name' => 'Mario Rossi',
            'customer_email' => 'mario.rossi@example.com',
            'customer_phone' => '+39 123 456 7890',
            'booking_id' => 'YHT-2025-001',
            'booking_date' => date('d/m/Y'),
            'booking_status' => 'Confermata',
            'booking_total' => '150.00',
            'booking_deposit' => '30.00',
            'booking_balance' => '120.00',
            'trip_date' => date('d/m/Y', strtotime('+7 days')),
            'trip_location' => 'Lago di Braies, Alto Adige',
            'participants' => '2'
        );
    }

    /**
     * Replace placeholders in template
     */
    public function replace_placeholders($content, $data) {
        foreach ($data as $key => $value) {
            $content = str_replace('{' . $key . '}', $value, $content);
        }
        return $content;
    }

    /**
     * Send email using template
     */
    public function send_email($template_key, $to_email, $data = array()) {
        $templates = $this->get_templates();
        
        if (!isset($templates[$template_key])) {
            return false;
        }

        $template = $templates[$template_key];
        
        // Merge with default sample data
        $sample_data = $this->get_sample_data();
        $data = wp_parse_args($data, $sample_data);
        
        $subject = $this->replace_placeholders($template['subject'], $data);
        $content = $this->replace_placeholders($template['content'], $data);

        // Set content type to HTML
        add_filter('wp_mail_content_type', function() {
            return 'text/html';
        });

        $result = wp_mail($to_email, $subject, $content);

        // Remove filter
        remove_all_filters('wp_mail_content_type');

        return $result;
    }
}
