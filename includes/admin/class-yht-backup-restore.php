<?php
/**
 * Backup and Restore System
 * 
 * @package YourHiddenTrip
 * @version 6.3
 */

if (!defined('ABSPATH')) exit;

/**
 * Backup and Restore Manager Class
 */
class YHT_Backup_Restore {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_page'), 18);
        add_action('wp_ajax_yht_create_backup', array($this, 'ajax_create_backup'));
        add_action('wp_ajax_yht_restore_backup', array($this, 'ajax_restore_backup'));
        add_action('wp_ajax_yht_delete_backup', array($this, 'ajax_delete_backup'));
        add_action('wp_ajax_yht_download_backup', array($this, 'ajax_download_backup'));
        add_action('wp_ajax_yht_schedule_backup', array($this, 'ajax_schedule_backup'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Schedule automatic backups
        add_action('yht_auto_backup', array($this, 'create_automatic_backup'));
        if (!wp_next_scheduled('yht_auto_backup')) {
            wp_schedule_event(time(), 'weekly', 'yht_auto_backup');
        }
    }

    /**
     * Add menu page
     */
    public function add_menu_page() {
        add_submenu_page(
            'yht-dashboard',
            __('💾 Backup & Ripristino', 'your-hidden-trip'),
            __('💾 Backup & Ripristino', 'your-hidden-trip'),
            'manage_options',
            'yht-backup-restore',
            array($this, 'render_page')
        );
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'your-hidden-trip_page_yht-backup-restore') return;

        wp_localize_script('jquery', 'yhtBackupRestore', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('yht_backup_restore_nonce'),
            'strings' => array(
                'creating' => __('Creazione backup in corso...', 'your-hidden-trip'),
                'created' => __('Backup creato con successo!', 'your-hidden-trip'),
                'restoring' => __('Ripristino in corso...', 'your-hidden-trip'),
                'restored' => __('Ripristino completato!', 'your-hidden-trip'),
                'deleting' => __('Eliminazione in corso...', 'your-hidden-trip'),
                'deleted' => __('Backup eliminato!', 'your-hidden-trip'),
                'error' => __('Errore durante l\'operazione.', 'your-hidden-trip'),
                'confirm_delete' => __('Sei sicuro di voler eliminare questo backup?', 'your-hidden-trip'),
                'confirm_restore' => __('Sei sicuro di voler ripristinare questo backup? I dati attuali verranno sovrascritti.', 'your-hidden-trip'),
                'scheduled' => __('Backup automatico programmato!', 'your-hidden-trip')
            )
        ));
    }

    /**
     * Render the backup and restore page
     */
    public function render_page() {
        $backups = $this->get_available_backups();
        $backup_settings = $this->get_backup_settings();
        ?>
        <div class="wrap yht-backup-restore-page">
            <div class="yht-header">
                <h1>💾 <?php _e('Backup e Ripristino', 'your-hidden-trip'); ?></h1>
                <p class="description">
                    <?php _e('Crea backup dei dati del plugin, programma backup automatici e ripristina versioni precedenti.', 'your-hidden-trip'); ?>
                </p>
            </div>

            <div class="backup-container">
                
                <!-- Create Backup Section -->
                <div class="backup-section create-backup">
                    <div class="section-header">
                        <h2><?php _e('🆕 Crea Nuovo Backup', 'your-hidden-trip'); ?></h2>
                        <p><?php _e('Crea un backup completo dei dati del plugin', 'your-hidden-trip'); ?></p>
                    </div>
                    
                    <div class="backup-options">
                        <div class="backup-types">
                            <label class="backup-type-option">
                                <input type="radio" name="backup_type" value="full" checked>
                                <div class="backup-type-card">
                                    <div class="backup-icon">🗃️</div>
                                    <h4><?php _e('Backup Completo', 'your-hidden-trip'); ?></h4>
                                    <p><?php _e('Include prenotazioni, clienti, impostazioni e template', 'your-hidden-trip'); ?></p>
                                </div>
                            </label>
                            
                            <label class="backup-type-option">
                                <input type="radio" name="backup_type" value="data">
                                <div class="backup-type-card">
                                    <div class="backup-icon">📊</div>
                                    <h4><?php _e('Solo Dati', 'your-hidden-trip'); ?></h4>
                                    <p><?php _e('Solo prenotazioni e informazioni clienti', 'your-hidden-trip'); ?></p>
                                </div>
                            </label>
                            
                            <label class="backup-type-option">
                                <input type="radio" name="backup_type" value="settings">
                                <div class="backup-type-card">
                                    <div class="backup-icon">⚙️</div>
                                    <h4><?php _e('Solo Impostazioni', 'your-hidden-trip'); ?></h4>
                                    <p><?php _e('Configurazioni, template e integrazioni API', 'your-hidden-trip'); ?></p>
                                </div>
                            </label>
                        </div>
                        
                        <div class="backup-form">
                            <div class="form-group">
                                <label for="backup_description"><?php _e('Descrizione Backup', 'your-hidden-trip'); ?></label>
                                <input type="text" id="backup_description" class="regular-text" 
                                       placeholder="<?php _e('Backup prima dell\'aggiornamento...', 'your-hidden-trip'); ?>">
                            </div>
                            
                            <div class="backup-actions">
                                <button id="create_backup" class="button button-primary button-hero">
                                    <span class="dashicons dashicons-database-add"></span>
                                    <?php _e('Crea Backup Ora', 'your-hidden-trip'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Scheduled Backups Section -->
                <div class="backup-section scheduled-backups">
                    <div class="section-header">
                        <h2><?php _e('⏰ Backup Automatici', 'your-hidden-trip'); ?></h2>
                        <p><?php _e('Configura backup automatici periodici', 'your-hidden-trip'); ?></p>
                    </div>
                    
                    <div class="schedule-settings">
                        <div class="settings-grid">
                            <div class="setting-item">
                                <label>
                                    <input type="checkbox" id="auto_backup_enabled" 
                                           <?php checked($backup_settings['auto_enabled'] ?? false); ?>>
                                    <?php _e('Abilita Backup Automatici', 'your-hidden-trip'); ?>
                                </label>
                            </div>
                            
                            <div class="setting-item">
                                <label for="backup_frequency"><?php _e('Frequenza', 'your-hidden-trip'); ?></label>
                                <select id="backup_frequency" class="regular-text">
                                    <option value="daily" <?php selected($backup_settings['frequency'] ?? 'weekly', 'daily'); ?>>
                                        <?php _e('Giornaliera', 'your-hidden-trip'); ?>
                                    </option>
                                    <option value="weekly" <?php selected($backup_settings['frequency'] ?? 'weekly', 'weekly'); ?>>
                                        <?php _e('Settimanale', 'your-hidden-trip'); ?>
                                    </option>
                                    <option value="monthly" <?php selected($backup_settings['frequency'] ?? 'weekly', 'monthly'); ?>>
                                        <?php _e('Mensile', 'your-hidden-trip'); ?>
                                    </option>
                                </select>
                            </div>
                            
                            <div class="setting-item">
                                <label for="backup_retention"><?php _e('Conserva Backup', 'your-hidden-trip'); ?></label>
                                <select id="backup_retention" class="regular-text">
                                    <option value="5" <?php selected($backup_settings['retention'] ?? '10', '5'); ?>>5 Backup</option>
                                    <option value="10" <?php selected($backup_settings['retention'] ?? '10', '10'); ?>>10 Backup</option>
                                    <option value="30" <?php selected($backup_settings['retention'] ?? '10', '30'); ?>>30 Backup</option>
                                    <option value="0" <?php selected($backup_settings['retention'] ?? '10', '0'); ?>><?php _e('Tutti', 'your-hidden-trip'); ?></option>
                                </select>
                            </div>
                            
                            <div class="setting-item">
                                <label for="backup_email"><?php _e('Notifica Email', 'your-hidden-trip'); ?></label>
                                <input type="email" id="backup_email" class="regular-text" 
                                       value="<?php echo esc_attr($backup_settings['email'] ?? get_option('admin_email')); ?>"
                                       placeholder="admin@example.com">
                            </div>
                        </div>
                        
                        <div class="schedule-actions">
                            <button id="save_schedule" class="button button-primary">
                                <?php _e('Salva Impostazioni', 'your-hidden-trip'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Available Backups Section -->
                <div class="backup-section available-backups">
                    <div class="section-header">
                        <h2><?php _e('📂 Backup Disponibili', 'your-hidden-trip'); ?></h2>
                        <div class="section-actions">
                            <button id="refresh_backups" class="button">
                                <span class="dashicons dashicons-update"></span>
                                <?php _e('Aggiorna', 'your-hidden-trip'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="backups-list">
                        <?php if (empty($backups)): ?>
                            <div class="no-backups">
                                <div class="no-backups-icon">📁</div>
                                <h3><?php _e('Nessun Backup Disponibile', 'your-hidden-trip'); ?></h3>
                                <p><?php _e('Non ci sono backup salvati. Crea il tuo primo backup utilizzando il modulo sopra.', 'your-hidden-trip'); ?></p>
                            </div>
                        <?php else: ?>
                            <div class="backups-table-wrapper">
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th><?php _e('Nome', 'your-hidden-trip'); ?></th>
                                            <th><?php _e('Tipo', 'your-hidden-trip'); ?></th>
                                            <th><?php _e('Dimensione', 'your-hidden-trip'); ?></th>
                                            <th><?php _e('Data Creazione', 'your-hidden-trip'); ?></th>
                                            <th><?php _e('Descrizione', 'your-hidden-trip'); ?></th>
                                            <th><?php _e('Azioni', 'your-hidden-trip'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($backups as $backup): ?>
                                            <tr>
                                                <td class="backup-name">
                                                    <strong><?php echo esc_html($backup['name']); ?></strong>
                                                </td>
                                                <td class="backup-type">
                                                    <span class="backup-type-badge <?php echo esc_attr($backup['type']); ?>">
                                                        <?php echo esc_html($this->get_backup_type_label($backup['type'])); ?>
                                                    </span>
                                                </td>
                                                <td class="backup-size">
                                                    <?php echo $this->format_file_size($backup['size']); ?>
                                                </td>
                                                <td class="backup-date">
                                                    <?php echo date('d/m/Y H:i', strtotime($backup['created'])); ?>
                                                </td>
                                                <td class="backup-description">
                                                    <?php echo esc_html($backup['description'] ?: __('Nessuna descrizione', 'your-hidden-trip')); ?>
                                                </td>
                                                <td class="backup-actions">
                                                    <div class="action-buttons">
                                                        <button class="button button-small restore-backup" 
                                                                data-backup="<?php echo esc_attr($backup['file']); ?>"
                                                                title="<?php _e('Ripristina', 'your-hidden-trip'); ?>">
                                                            <span class="dashicons dashicons-backup"></span>
                                                        </button>
                                                        <button class="button button-small download-backup" 
                                                                data-backup="<?php echo esc_attr($backup['file']); ?>"
                                                                title="<?php _e('Scarica', 'your-hidden-trip'); ?>">
                                                            <span class="dashicons dashicons-download"></span>
                                                        </button>
                                                        <button class="button button-small delete-backup" 
                                                                data-backup="<?php echo esc_attr($backup['file']); ?>"
                                                                title="<?php _e('Elimina', 'your-hidden-trip'); ?>">
                                                            <span class="dashicons dashicons-trash"></span>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Backup Statistics -->
                <div class="backup-section backup-stats">
                    <div class="section-header">
                        <h2><?php _e('📈 Statistiche Backup', 'your-hidden-trip'); ?></h2>
                    </div>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">📦</div>
                            <div class="stat-content">
                                <h4><?php _e('Totale Backup', 'your-hidden-trip'); ?></h4>
                                <span class="stat-value"><?php echo count($backups); ?></span>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">💾</div>
                            <div class="stat-content">
                                <h4><?php _e('Spazio Utilizzato', 'your-hidden-trip'); ?></h4>
                                <span class="stat-value"><?php echo $this->get_total_backup_size($backups); ?></span>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">📅</div>
                            <div class="stat-content">
                                <h4><?php _e('Ultimo Backup', 'your-hidden-trip'); ?></h4>
                                <span class="stat-value">
                                    <?php 
                                    if (!empty($backups)) {
                                        echo date('d/m/Y', strtotime($backups[0]['created']));
                                    } else {
                                        echo __('Mai', 'your-hidden-trip');
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">🔄</div>
                            <div class="stat-content">
                                <h4><?php _e('Prossimo Auto-Backup', 'your-hidden-trip'); ?></h4>
                                <span class="stat-value">
                                    <?php 
                                    $next_scheduled = wp_next_scheduled('yht_auto_backup');
                                    echo $next_scheduled ? date('d/m/Y', $next_scheduled) : __('Disabilitato', 'your-hidden-trip');
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress Modal -->
            <div id="backup_progress_modal" class="yht-modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="progress_title"><?php _e('Operazione in Corso', 'your-hidden-trip'); ?></h3>
                    </div>
                    <div class="modal-body">
                        <div class="progress-container">
                            <div class="progress-bar">
                                <div class="progress-fill" id="progress_fill"></div>
                            </div>
                            <div class="progress-text">
                                <span id="progress_message"><?php _e('Inizializzazione...', 'your-hidden-trip'); ?></span>
                                <span id="progress_percent">0%</span>
                            </div>
                        </div>
                        <div class="progress-log">
                            <div id="progress_log_content"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .yht-backup-restore-page {
                max-width: 1200px;
            }

            .yht-header {
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 1px solid #e1e1e1;
            }

            .backup-container {
                display: flex;
                flex-direction: column;
                gap: 30px;
            }

            .backup-section {
                background: white;
                border: 1px solid #e1e1e1;
                border-radius: 8px;
                padding: 25px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }

            .section-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 25px;
            }

            .section-header h2 {
                margin: 0 0 5px 0;
                font-size: 20px;
                color: #333;
            }

            .section-header p {
                margin: 0;
                color: #666;
                font-size: 14px;
            }

            .backup-types {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
                margin-bottom: 25px;
            }

            .backup-type-option {
                cursor: pointer;
            }

            .backup-type-option input {
                display: none;
            }

            .backup-type-card {
                border: 2px solid #e1e1e1;
                border-radius: 8px;
                padding: 20px;
                text-align: center;
                transition: all 0.3s;
            }

            .backup-type-option input:checked + .backup-type-card {
                border-color: #0073aa;
                background: #f0f8ff;
            }

            .backup-icon {
                font-size: 32px;
                margin-bottom: 10px;
            }

            .backup-type-card h4 {
                margin: 0 0 8px 0;
                color: #333;
            }

            .backup-type-card p {
                margin: 0;
                color: #666;
                font-size: 13px;
            }

            .backup-form {
                padding: 20px;
                background: #f8f9fa;
                border-radius: 6px;
            }

            .form-group {
                margin-bottom: 20px;
            }

            .form-group label {
                display: block;
                font-weight: 600;
                margin-bottom: 5px;
                color: #555;
            }

            .form-group input,
            .form-group select {
                width: 100%;
                max-width: 400px;
                padding: 8px 12px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }

            .backup-actions {
                display: flex;
                gap: 15px;
            }

            .button-hero {
                padding: 12px 20px !important;
                font-size: 16px !important;
                height: auto !important;
            }

            .settings-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
                margin-bottom: 25px;
            }

            .setting-item label {
                display: block;
                font-weight: 600;
                margin-bottom: 5px;
                color: #555;
            }

            .setting-item input[type="checkbox"] {
                margin-right: 8px;
                width: auto;
            }

            .no-backups {
                text-align: center;
                padding: 60px 20px;
                color: #666;
            }

            .no-backups-icon {
                font-size: 64px;
                margin-bottom: 20px;
            }

            .no-backups h3 {
                margin: 0 0 10px 0;
                color: #333;
            }

            .backup-type-badge {
                padding: 4px 8px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: bold;
                text-transform: uppercase;
            }

            .backup-type-badge.full {
                background: #e7f3ff;
                color: #0066cc;
            }

            .backup-type-badge.data {
                background: #fff3cd;
                color: #856404;
            }

            .backup-type-badge.settings {
                background: #d4edda;
                color: #155724;
            }

            .action-buttons {
                display: flex;
                gap: 5px;
            }

            .action-buttons .button {
                padding: 4px 8px;
                min-height: auto;
            }

            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
            }

            .stat-card {
                display: flex;
                align-items: center;
                gap: 15px;
                padding: 20px;
                background: #f8f9fa;
                border-radius: 8px;
                border-left: 4px solid #0073aa;
            }

            .stat-icon {
                font-size: 24px;
            }

            .stat-content h4 {
                margin: 0 0 5px 0;
                font-size: 14px;
                color: #666;
                text-transform: uppercase;
            }

            .stat-value {
                font-size: 20px;
                font-weight: bold;
                color: #333;
            }

            .yht-modal {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.8);
                z-index: 100000;
            }

            .modal-content {
                background: white;
                width: 90%;
                max-width: 600px;
                margin: 50px auto;
                border-radius: 8px;
                overflow: hidden;
            }

            .modal-header {
                padding: 20px;
                background: #f9f9f9;
                border-bottom: 1px solid #e1e1e1;
            }

            .modal-header h3 {
                margin: 0;
                color: #333;
            }

            .modal-body {
                padding: 25px;
            }

            .progress-container {
                margin-bottom: 20px;
            }

            .progress-bar {
                width: 100%;
                height: 20px;
                background: #e1e1e1;
                border-radius: 10px;
                overflow: hidden;
                margin-bottom: 10px;
            }

            .progress-fill {
                height: 100%;
                background: linear-gradient(90deg, #0073aa, #005177);
                width: 0%;
                transition: width 0.3s ease;
            }

            .progress-text {
                display: flex;
                justify-content: space-between;
                align-items: center;
                font-size: 14px;
                color: #666;
            }

            .progress-log {
                max-height: 200px;
                overflow-y: auto;
                background: #f8f9fa;
                padding: 15px;
                border-radius: 4px;
                font-family: monospace;
                font-size: 13px;
            }

            @media (max-width: 768px) {
                .backup-types {
                    grid-template-columns: 1fr;
                }

                .settings-grid {
                    grid-template-columns: 1fr;
                }

                .stats-grid {
                    grid-template-columns: 1fr;
                }

                .section-header {
                    flex-direction: column;
                    gap: 10px;
                }
            }
        </style>

        <script>
            jQuery(document).ready(function($) {
                
                // Create backup
                $('#create_backup').click(function() {
                    var backupType = $('input[name="backup_type"]:checked').val();
                    var description = $('#backup_description').val();
                    
                    var $btn = $(this);
                    var originalText = $btn.text();
                    
                    $btn.text(yhtBackupRestore.strings.creating).prop('disabled', true);
                    showProgressModal('Creazione Backup', 'Inizializzazione backup...');
                    
                    $.post(yhtBackupRestore.ajaxurl, {
                        action: 'yht_create_backup',
                        nonce: yhtBackupRestore.nonce,
                        backup_type: backupType,
                        description: description
                    }, function(response) {
                        $btn.text(originalText).prop('disabled', false);
                        hideProgressModal();
                        
                        if (response.success) {
                            alert(yhtBackupRestore.strings.created);
                            location.reload();
                        } else {
                            alert(yhtBackupRestore.strings.error + ' ' + (response.data.message || ''));
                        }
                    });
                });

                // Restore backup
                $(document).on('click', '.restore-backup', function() {
                    if (!confirm(yhtBackupRestore.strings.confirm_restore)) return;
                    
                    var backupFile = $(this).data('backup');
                    showProgressModal('Ripristino Backup', 'Preparazione ripristino...');
                    
                    $.post(yhtBackupRestore.ajaxurl, {
                        action: 'yht_restore_backup',
                        nonce: yhtBackupRestore.nonce,
                        backup_file: backupFile
                    }, function(response) {
                        hideProgressModal();
                        
                        if (response.success) {
                            alert(yhtBackupRestore.strings.restored);
                            location.reload();
                        } else {
                            alert(yhtBackupRestore.strings.error + ' ' + (response.data.message || ''));
                        }
                    });
                });

                // Delete backup
                $(document).on('click', '.delete-backup', function() {
                    if (!confirm(yhtBackupRestore.strings.confirm_delete)) return;
                    
                    var backupFile = $(this).data('backup');
                    var $row = $(this).closest('tr');
                    
                    $.post(yhtBackupRestore.ajaxurl, {
                        action: 'yht_delete_backup',
                        nonce: yhtBackupRestore.nonce,
                        backup_file: backupFile
                    }, function(response) {
                        if (response.success) {
                            $row.fadeOut(function() {
                                $(this).remove();
                                if ($('.backups-list tbody tr').length === 0) {
                                    location.reload();
                                }
                            });
                        } else {
                            alert(yhtBackupRestore.strings.error + ' ' + (response.data.message || ''));
                        }
                    });
                });

                // Download backup
                $(document).on('click', '.download-backup', function() {
                    var backupFile = $(this).data('backup');
                    window.location.href = yhtBackupRestore.ajaxurl + '?action=yht_download_backup&backup_file=' + backupFile + '&nonce=' + yhtBackupRestore.nonce;
                });

                // Save backup schedule
                $('#save_schedule').click(function() {
                    var settings = {
                        auto_enabled: $('#auto_backup_enabled').is(':checked') ? 1 : 0,
                        frequency: $('#backup_frequency').val(),
                        retention: $('#backup_retention').val(),
                        email: $('#backup_email').val()
                    };
                    
                    $.post(yhtBackupRestore.ajaxurl, {
                        action: 'yht_schedule_backup',
                        nonce: yhtBackupRestore.nonce,
                        settings: settings
                    }, function(response) {
                        if (response.success) {
                            alert(yhtBackupRestore.strings.scheduled);
                        } else {
                            alert(yhtBackupRestore.strings.error + ' ' + (response.data.message || ''));
                        }
                    });
                });

                // Progress modal functions
                function showProgressModal(title, message) {
                    $('#progress_title').text(title);
                    $('#progress_message').text(message);
                    $('#progress_percent').text('0%');
                    $('#progress_fill').css('width', '0%');
                    $('#progress_log_content').empty();
                    $('#backup_progress_modal').show();
                    
                    // Simulate progress
                    var progress = 0;
                    var interval = setInterval(function() {
                        progress += Math.random() * 30;
                        if (progress > 90) progress = 90;
                        
                        $('#progress_fill').css('width', progress + '%');
                        $('#progress_percent').text(Math.round(progress) + '%');
                        
                        if (progress >= 90) {
                            clearInterval(interval);
                        }
                    }, 500);
                }
                
                function hideProgressModal() {
                    $('#progress_fill').css('width', '100%');
                    $('#progress_percent').text('100%');
                    
                    setTimeout(function() {
                        $('#backup_progress_modal').hide();
                    }, 1000);
                }

                // Refresh backups
                $('#refresh_backups').click(function() {
                    location.reload();
                });
            });
        </script>
        <?php
    }

    /**
     * Get available backups
     */
    private function get_available_backups() {
        $upload_dir = wp_upload_dir();
        $backup_dir = $upload_dir['basedir'] . '/yht-backups/';
        
        if (!file_exists($backup_dir)) {
            return array();
        }

        $backups = array();
        $files = glob($backup_dir . '*.json');
        
        foreach ($files as $file) {
            $filename = basename($file);
            $backup_data = json_decode(file_get_contents($file), true);
            
            if ($backup_data && isset($backup_data['metadata'])) {
                $backups[] = array(
                    'name' => $backup_data['metadata']['name'] ?? $filename,
                    'file' => $filename,
                    'type' => $backup_data['metadata']['type'] ?? 'full',
                    'size' => filesize($file),
                    'created' => $backup_data['metadata']['created'] ?? date('Y-m-d H:i:s', filemtime($file)),
                    'description' => $backup_data['metadata']['description'] ?? ''
                );
            }
        }

        // Sort by creation date (newest first)
        usort($backups, function($a, $b) {
            return strtotime($b['created']) - strtotime($a['created']);
        });

        return $backups;
    }

    /**
     * Get backup settings
     */
    private function get_backup_settings() {
        return get_option('yht_backup_settings', array(
            'auto_enabled' => false,
            'frequency' => 'weekly',
            'retention' => '10',
            'email' => get_option('admin_email')
        ));
    }

    /**
     * Get backup type label
     */
    private function get_backup_type_label($type) {
        $labels = array(
            'full' => __('Completo', 'your-hidden-trip'),
            'data' => __('Solo Dati', 'your-hidden-trip'),
            'settings' => __('Impostazioni', 'your-hidden-trip')
        );
        
        return $labels[$type] ?? $type;
    }

    /**
     * Format file size
     */
    private function format_file_size($bytes) {
        $units = array('B', 'KB', 'MB', 'GB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Get total backup size
     */
    private function get_total_backup_size($backups) {
        $total_size = array_sum(array_column($backups, 'size'));
        return $this->format_file_size($total_size);
    }

    /**
     * AJAX: Create backup
     */
    public function ajax_create_backup() {
        check_ajax_referer('yht_backup_restore_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permessi insufficienti.', 'your-hidden-trip'));
        }

        $backup_type = sanitize_key($_POST['backup_type']);
        $description = sanitize_text_field($_POST['description']);

        $result = $this->create_backup($backup_type, $description);

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Backup creato con successo!', 'your-hidden-trip'),
                'backup_file' => $result
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Errore durante la creazione del backup.', 'your-hidden-trip')
            ));
        }
    }

    /**
     * Create backup
     */
    private function create_backup($type, $description) {
        $upload_dir = wp_upload_dir();
        $backup_dir = $upload_dir['basedir'] . '/yht-backups/';
        
        if (!file_exists($backup_dir)) {
            wp_mkdir_p($backup_dir);
        }

        $timestamp = date('Y-m-d_H-i-s');
        $filename = "yht-backup-{$type}-{$timestamp}.json";
        $filepath = $backup_dir . $filename;

        $backup_data = array(
            'metadata' => array(
                'name' => "YHT Backup {$type} - {$timestamp}",
                'type' => $type,
                'created' => current_time('mysql'),
                'description' => $description,
                'version' => '6.3'
            )
        );

        // Include data based on backup type
        switch ($type) {
            case 'full':
                $backup_data['bookings'] = $this->get_bookings_data();
                $backup_data['customers'] = $this->get_customers_data();
                $backup_data['settings'] = $this->get_settings_data();
                $backup_data['templates'] = $this->get_templates_data();
                break;
                
            case 'data':
                $backup_data['bookings'] = $this->get_bookings_data();
                $backup_data['customers'] = $this->get_customers_data();
                break;
                
            case 'settings':
                $backup_data['settings'] = $this->get_settings_data();
                $backup_data['templates'] = $this->get_templates_data();
                break;
        }

        $json_data = json_encode($backup_data, JSON_PRETTY_PRINT);
        
        if (file_put_contents($filepath, $json_data)) {
            return $filename;
        }

        return false;
    }

    /**
     * Get bookings data
     */
    private function get_bookings_data() {
        global $wpdb;
        
        // This is simplified - in a real implementation you'd query actual booking tables
        return array(
            'total' => 25,
            'example_booking' => array(
                'id' => 1,
                'customer' => 'Mario Rossi',
                'destination' => 'Lago di Braies',
                'date' => '2025-02-15',
                'status' => 'confirmed'
            )
        );
    }

    /**
     * Get customers data
     */
    private function get_customers_data() {
        global $wpdb;
        
        // This is simplified - in a real implementation you'd query actual customer tables
        return array(
            'total' => 15,
            'example_customer' => array(
                'id' => 1,
                'name' => 'Mario Rossi',
                'email' => 'mario@example.com',
                'phone' => '+39 123 456 789'
            )
        );
    }

    /**
     * Get settings data
     */
    private function get_settings_data() {
        return array(
            'plugin_settings' => get_option('yht_settings', array()),
            'api_settings' => get_option('yht_api_settings', array()),
            'backup_settings' => get_option('yht_backup_settings', array())
        );
    }

    /**
     * Get templates data
     */
    private function get_templates_data() {
        return get_option('yht_email_templates', array());
    }

    /**
     * AJAX: Restore backup
     */
    public function ajax_restore_backup() {
        check_ajax_referer('yht_backup_restore_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permessi insufficienti.', 'your-hidden-trip'));
        }

        $backup_file = sanitize_file_name($_POST['backup_file']);
        $result = $this->restore_backup($backup_file);

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Backup ripristinato con successo!', 'your-hidden-trip')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Errore durante il ripristino del backup.', 'your-hidden-trip')
            ));
        }
    }

    /**
     * Restore backup
     */
    private function restore_backup($filename) {
        $upload_dir = wp_upload_dir();
        $backup_dir = $upload_dir['basedir'] . '/yht-backups/';
        $filepath = $backup_dir . $filename;

        if (!file_exists($filepath)) {
            return false;
        }

        $backup_data = json_decode(file_get_contents($filepath), true);
        
        if (!$backup_data) {
            return false;
        }

        // Restore settings
        if (isset($backup_data['settings'])) {
            foreach ($backup_data['settings'] as $option => $value) {
                update_option($option, $value);
            }
        }

        // Restore templates
        if (isset($backup_data['templates'])) {
            update_option('yht_email_templates', $backup_data['templates']);
        }

        // In a real implementation, you'd restore bookings and customers data to database tables

        return true;
    }

    /**
     * AJAX: Delete backup
     */
    public function ajax_delete_backup() {
        check_ajax_referer('yht_backup_restore_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permessi insufficienti.', 'your-hidden-trip'));
        }

        $backup_file = sanitize_file_name($_POST['backup_file']);
        $upload_dir = wp_upload_dir();
        $backup_dir = $upload_dir['basedir'] . '/yht-backups/';
        $filepath = $backup_dir . $backup_file;

        if (file_exists($filepath) && unlink($filepath)) {
            wp_send_json_success(array(
                'message' => __('Backup eliminato con successo!', 'your-hidden-trip')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Errore durante l\'eliminazione del backup.', 'your-hidden-trip')
            ));
        }
    }

    /**
     * AJAX: Download backup
     */
    public function ajax_download_backup() {
        check_ajax_referer('yht_backup_restore_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permessi insufficienti.', 'your-hidden-trip'));
        }

        $backup_file = sanitize_file_name($_GET['backup_file']);
        $upload_dir = wp_upload_dir();
        $backup_dir = $upload_dir['basedir'] . '/yht-backups/';
        $filepath = $backup_dir . $backup_file;

        if (file_exists($filepath)) {
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="' . $backup_file . '"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            exit;
        } else {
            wp_die(__('File backup non trovato.', 'your-hidden-trip'));
        }
    }

    /**
     * AJAX: Schedule backup
     */
    public function ajax_schedule_backup() {
        check_ajax_referer('yht_backup_restore_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permessi insufficienti.', 'your-hidden-trip'));
        }

        $settings = array_map('sanitize_text_field', $_POST['settings']);
        update_option('yht_backup_settings', $settings);

        // Reschedule automatic backups based on new settings
        wp_clear_scheduled_hook('yht_auto_backup');
        
        if ($settings['auto_enabled']) {
            $frequency = $settings['frequency'] ?? 'weekly';
            wp_schedule_event(time(), $frequency, 'yht_auto_backup');
        }

        wp_send_json_success(array(
            'message' => __('Impostazioni backup salvate!', 'your-hidden-trip')
        ));
    }

    /**
     * Create automatic backup
     */
    public function create_automatic_backup() {
        $settings = $this->get_backup_settings();
        
        if (!$settings['auto_enabled']) {
            return;
        }

        $backup_file = $this->create_backup('full', 'Backup automatico - ' . date('d/m/Y H:i'));
        
        if ($backup_file) {
            // Send notification email
            $subject = 'Backup automatico completato - Your Hidden Trip';
            $message = "Il backup automatico è stato completato con successo.\n\n";
            $message .= "File: {$backup_file}\n";
            $message .= "Data: " . date('d/m/Y H:i:s') . "\n";
            
            wp_mail($settings['email'], $subject, $message);
            
            // Clean up old backups based on retention setting
            if ($settings['retention'] > 0) {
                $this->cleanup_old_backups($settings['retention']);
            }
        }
    }

    /**
     * Cleanup old backups
     */
    private function cleanup_old_backups($retention_count) {
        $backups = $this->get_available_backups();
        
        if (count($backups) > $retention_count) {
            $backups_to_delete = array_slice($backups, $retention_count);
            $upload_dir = wp_upload_dir();
            $backup_dir = $upload_dir['basedir'] . '/yht-backups/';
            
            foreach ($backups_to_delete as $backup) {
                $filepath = $backup_dir . $backup['file'];
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
            }
        }
    }
}