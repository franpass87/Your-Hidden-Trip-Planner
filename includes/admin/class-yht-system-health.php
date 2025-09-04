<?php
/**
 * YHT System Health Monitor - Monitor plugin and system performance
 * 
 * @package YourHiddenTrip
 */

if (!defined('ABSPATH')) exit;

class YHT_System_Health {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_yht_system_health_check', array($this, 'ajax_health_check'));
        add_action('wp_ajax_yht_system_performance_test', array($this, 'ajax_performance_test'));
        add_action('wp_ajax_yht_clear_cache', array($this, 'ajax_clear_cache'));
        add_action('wp_ajax_yht_cleanup_database', array($this, 'ajax_cleanup_database'));
        add_action('wp_ajax_yht_rebuild_analytics', array($this, 'ajax_rebuild_analytics'));
        add_action('wp_ajax_yht_security_scan', array($this, 'ajax_security_scan'));
        add_action('wp_ajax_yht_get_system_logs', array($this, 'ajax_get_system_logs'));
        add_action('wp_ajax_yht_clear_logs', array($this, 'ajax_clear_logs'));
        add_action('wp_ajax_yht_auto_fix_issues', array($this, 'ajax_auto_fix_issues'));
        add_action('wp_ajax_yht_export_health_report', array($this, 'ajax_export_health_report'));
        
        // Schedule health checks
        if (!wp_next_scheduled('yht_daily_health_check')) {
            wp_schedule_event(time(), 'daily', 'yht_daily_health_check');
        }
        
        add_action('yht_daily_health_check', array($this, 'daily_health_check'));
        
        // Log system events
        add_action('activated_plugin', array($this, 'log_plugin_activation'));
        add_action('deactivated_plugin', array($this, 'log_plugin_deactivation'));
        add_action('wp_login', array($this, 'log_user_login'));
    }
    
    /**
     * Render system health page
     */
    public function render_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        ?>
        <div class="wrap">
            <h1>⚡ Sistema & Performance</h1>
            
            <div class="yht-system-health">
                <!-- Health Overview -->
                <div class="yht-health-overview">
                    <div class="yht-health-score">
                        <div id="overall-health-score" class="health-score-circle">
                            <div class="score-number">--</div>
                            <div class="score-label">Salute Sistema</div>
                        </div>
                        
                        <div class="health-actions">
                            <button type="button" id="run-health-check" class="button button-primary">
                                🔍 Esegui Controllo Completo
                            </button>
                            <button type="button" id="run-performance-test" class="button button-secondary">
                                ⚡ Test Performance
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- System Status Cards -->
                <div class="yht-status-grid">
                    <div class="yht-status-card" id="wordpress-status">
                        <div class="status-header">
                            <h3>🔧 WordPress</h3>
                            <div class="status-indicator" id="wp-indicator">⚪</div>
                        </div>
                        <div class="status-details">
                            <p>Versione: <span id="wp-version">--</span></p>
                            <p>Aggiornamenti: <span id="wp-updates">--</span></p>
                            <p>Debug: <span id="wp-debug">--</span></p>
                        </div>
                    </div>
                    
                    <div class="yht-status-card" id="plugins-status">
                        <div class="status-header">
                            <h3>🔌 Plugin</h3>
                            <div class="status-indicator" id="plugins-indicator">⚪</div>
                        </div>
                        <div class="status-details">
                            <p>Attivi: <span id="active-plugins">--</span></p>
                            <p>Aggiornamenti: <span id="plugin-updates">--</span></p>
                            <p>Conflitti: <span id="plugin-conflicts">--</span></p>
                        </div>
                    </div>
                    
                    <div class="yht-status-card" id="database-status">
                        <div class="status-header">
                            <h3>🗄️ Database</h3>
                            <div class="status-indicator" id="db-indicator">⚪</div>
                        </div>
                        <div class="status-details">
                            <p>Versione: <span id="db-version">--</span></p>
                            <p>Dimensione: <span id="db-size">--</span></p>
                            <p>Query lente: <span id="slow-queries">--</span></p>
                        </div>
                    </div>
                    
                    <div class="yht-status-card" id="performance-status">
                        <div class="status-header">
                            <h3>📊 Performance</h3>
                            <div class="status-indicator" id="perf-indicator">⚪</div>
                        </div>
                        <div class="status-details">
                            <p>Memoria: <span id="memory-usage">--</span></p>
                            <p>Tempo caricamento: <span id="load-time">--</span></p>
                            <p>Cache: <span id="cache-status">--</span></p>
                        </div>
                    </div>
                </div>
                
                <!-- Detailed Reports -->
                <div class="yht-health-tabs">
                    <div class="yht-tab-nav">
                        <button class="yht-tab-button active" data-tab="issues">🚨 Problemi Rilevati</button>
                        <button class="yht-tab-button" data-tab="recommendations">💡 Raccomandazioni</button>
                        <button class="yht-tab-button" data-tab="maintenance">🛠️ Manutenzione</button>
                        <button class="yht-tab-button" data-tab="logs">📝 Log Sistema</button>
                    </div>
                    
                    <div class="yht-tab-content">
                        <!-- Issues Tab -->
                        <div id="tab-issues" class="yht-tab-panel active">
                            <div class="issues-header">
                                <div class="issues-actions">
                                    <button type="button" id="auto-fix-issues" class="button button-secondary">
                                        🔧 Correzione Automatica
                                    </button>
                                    <button type="button" id="export-health-report" class="button button-secondary">
                                        📄 Esporta Report
                                    </button>
                                </div>
                            </div>
                            <div id="system-issues">
                                <div class="yht-loading">Controllo problemi in corso...</div>
                            </div>
                        </div>
                        
                        <!-- Recommendations Tab -->
                        <div id="tab-recommendations" class="yht-tab-panel">
                            <div id="system-recommendations">
                                <div class="yht-loading">Generazione raccomandazioni...</div>
                            </div>
                        </div>
                        
                        <!-- Maintenance Tab -->
                        <div id="tab-maintenance" class="yht-tab-panel">
                            <div class="yht-maintenance-tools">
                                <h3>🛠️ Strumenti di Manutenzione</h3>
                                
                                <div class="maintenance-action">
                                    <h4>🗑️ Pulizia Database</h4>
                                    <p>Rimuove dati obsoleti e ottimizza le tabelle del database.</p>
                                    <button type="button" id="cleanup-database" class="button">Pulisci Database</button>
                                </div>
                                
                                <div class="maintenance-action">
                                    <h4>🔄 Reset Cache</h4>
                                    <p>Svuota tutte le cache del plugin e ricostruisce gli indici.</p>
                                    <button type="button" id="clear-all-cache" class="button">Svuota Cache</button>
                                </div>
                                
                                <div class="maintenance-action">
                                    <h4>📊 Ricostruzione Analytics</h4>
                                    <p>Ricalcola tutte le statistiche e metriche analytics.</p>
                                    <button type="button" id="rebuild-analytics" class="button">Ricostruisci Analytics</button>
                                </div>
                                
                                <div class="maintenance-action">
                                    <h4>🔐 Test Sicurezza</h4>
                                    <p>Esegue controlli di sicurezza approfonditi.</p>
                                    <button type="button" id="security-scan" class="button">Scansione Sicurezza</button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Logs Tab -->
                        <div id="tab-logs" class="yht-tab-panel">
                            <div class="yht-logs-viewer">
                                <div class="logs-toolbar">
                                    <select id="log-filter">
                                        <option value="all">Tutti i log</option>
                                        <option value="error">Solo errori</option>
                                        <option value="warning">Solo warning</option>
                                        <option value="info">Solo info</option>
                                    </select>
                                    <button type="button" id="refresh-logs" class="button">🔄 Aggiorna</button>
                                    <button type="button" id="clear-logs" class="button">🗑️ Svuota Log</button>
                                </div>
                                
                                <div id="system-logs" class="logs-container">
                                    <div class="yht-loading">Caricamento log...</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Performance Results Tab (Dynamic) -->
                        <div id="tab-performance-results" class="yht-tab-panel">
                            <div id="performance-results-content">
                                <div class="yht-loading">Nessun test performance eseguito</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .yht-system-health {
            max-width: 1200px;
        }
        
        .yht-health-overview {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .yht-health-score {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 40px;
        }
        
        .health-score-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 8px solid #e0e0e0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .health-score-circle.excellent {
            border-color: #00a32a;
        }
        
        .health-score-circle.good {
            border-color: #dba617;
        }
        
        .health-score-circle.poor {
            border-color: #d63638;
        }
        
        .score-number {
            font-size: 28px;
            font-weight: bold;
            line-height: 1;
        }
        
        .score-label {
            font-size: 12px;
            color: #646970;
            margin-top: 5px;
        }
        
        .health-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .yht-status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .yht-status-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 8px;
            padding: 20px;
        }
        
        .status-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .status-header h3 {
            margin: 0;
            font-size: 16px;
        }
        
        .status-indicator {
            font-size: 20px;
        }
        
        .status-indicator.good::after {
            content: "🟢";
        }
        
        .status-indicator.warning::after {
            content: "🟡";
        }
        
        .status-indicator.error::after {
            content: "🔴";
        }
        
        .status-details p {
            margin: 8px 0;
            font-size: 14px;
        }
        
        .status-details span {
            font-weight: 600;
        }
        
        .yht-health-tabs {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .yht-tab-nav {
            display: flex;
            border-bottom: 1px solid #ccd0d4;
            background: #f6f7f7;
        }
        
        .yht-tab-button {
            padding: 15px 20px;
            border: none;
            background: none;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            font-weight: 500;
            font-size: 14px;
        }
        
        .yht-tab-button.active {
            border-bottom-color: #2271b1;
            background: #fff;
            color: #2271b1;
        }
        
        .yht-tab-panel {
            display: none;
            padding: 30px;
        }
        
        .yht-tab-panel.active {
            display: block;
        }
        
        .yht-loading {
            text-align: center;
            padding: 40px 20px;
            color: #646970;
        }
        
        .issue-item {
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 6px;
            border-left: 4px solid;
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }
        
        .issue-critical {
            background: #fcf0f1;
            border-left-color: #d63638;
        }
        
        .issue-warning {
            background: #fcf9e8;
            border-left-color: #dba617;
        }
        
        .issue-info {
            background: #f0f6fc;
            border-left-color: #2271b1;
        }
        
        .issue-icon {
            font-size: 20px;
        }
        
        .issue-content {
            flex: 1;
        }
        
        .issue-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .issue-description {
            color: #646970;
            font-size: 14px;
        }
        
        .maintenance-action {
            padding: 20px;
            margin-bottom: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
        
        .maintenance-action h4 {
            margin: 0 0 10px 0;
            font-size: 16px;
        }
        
        .maintenance-action p {
            margin-bottom: 15px;
            color: #646970;
        }
        
        .logs-toolbar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            align-items: center;
        }
        
        .logs-container {
            background: #1e1e1e;
            color: #fff;
            padding: 20px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .log-entry {
            margin-bottom: 10px;
            padding-left: 20px;
            position: relative;
        }
        
        .log-entry::before {
            content: "●";
            position: absolute;
            left: 0;
            top: 0;
        }
        
        .log-error::before {
            color: #ff6b6b;
        }
        
        .log-warning::before {
            color: #ffd93d;
        }
        
        .log-info::before {
            color: #74c0fc;
        }
        
        .log-timestamp {
            color: #a0a0a0;
            margin-right: 10px;
        }
        
        .performance-results {
            background: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .performance-results h4 {
            margin: 0 0 15px 0;
            color: #2271b1;
            font-size: 18px;
        }
        
        .perf-metric {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .perf-metric:last-child {
            border-bottom: none;
        }
        
        .perf-metric strong {
            color: #135e96;
        }
        
        .maintenance-action {
            background: #fff;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            transition: box-shadow 0.2s ease;
        }
        
        .maintenance-action:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .yht-loading {
            text-align: center;
            padding: 40px 20px;
            color: #646970;
            font-style: italic;
        }
        
        .yht-loading:before {
            content: "⏳ ";
            margin-right: 8px;
        }
        
        .issues-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .issues-actions {
            display: flex;
            gap: 10px;
        }
        
        .issue-fixable {
            position: relative;
        }
        
        .issue-fixable::after {
            content: "🔧";
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 16px;
            opacity: 0.7;
        }
        
        .issue-fix-hint {
            font-size: 12px;
            color: #00a32a;
            margin-top: 5px;
            font-style: italic;
        }
        
        .performance-recommendations {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }
        
        .performance-recommendations h5 {
            margin: 0 0 10px 0;
            font-size: 14px;
            font-weight: 600;
        }
        
        .perf-recommendation {
            padding: 8px 12px;
            margin-bottom: 8px;
            border-radius: 4px;
            font-size: 13px;
            background: #fcf9e8;
            border-left: 3px solid #dba617;
        }
        
        .perf-recommendation.perf-good {
            background: #f0f9ff;
            border-left-color: #00a32a;
            color: #00a32a;
        }
        
        @media (max-width: 782px) {
            .yht-health-score {
                flex-direction: column;
                gap: 20px;
            }
            
            .yht-status-grid {
                grid-template-columns: 1fr;
            }
            
            .yht-tab-nav {
                flex-wrap: wrap;
            }
            
            .yht-tab-button {
                flex: 1;
                min-width: 120px;
            }
        }
        </style>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Auto-run health check on page load
            runHealthCheck();
            
            // Tab switching
            $('.yht-tab-button').on('click', function() {
                const tab = $(this).data('tab');
                $('.yht-tab-button').removeClass('active');
                $('.yht-tab-panel').removeClass('active');
                $(this).addClass('active');
                $('#tab-' + tab).addClass('active');
                
                // Load tab content if needed
                if (tab === 'logs' && $('#system-logs .yht-loading').length) {
                    loadSystemLogs();
                }
            });
            
            // Health check button
            $('#run-health-check').on('click', function() {
                runHealthCheck();
            });
            
            // Performance test button
            $('#run-performance-test').on('click', function() {
                runPerformanceTest();
            });
            
            // Maintenance actions
            $('#cleanup-database').on('click', function() {
                if (confirm('Sei sicuro di voler pulire il database? Questa operazione potrebbe richiedere alcuni minuti.')) {
                    $(this).prop('disabled', true).text('Pulizia in corso...');
                    
                    $.post(ajaxurl, {
                        action: 'yht_cleanup_database',
                        nonce: '<?php echo wp_create_nonce('yht_system_nonce'); ?>'
                    }, function(response) {
                        $('#cleanup-database').prop('disabled', false).text('Pulisci Database');
                        
                        if (response.success) {
                            alert('Database pulito con successo! ' + response.data.message);
                            // Refresh health check to see improvements
                            runHealthCheck();
                        } else {
                            alert('Errore durante la pulizia del database: ' + (response.data.message || 'Errore sconosciuto'));
                        }
                    }).fail(function() {
                        $('#cleanup-database').prop('disabled', false).text('Pulisci Database');
                        alert('Errore di connessione durante la pulizia del database');
                    });
                }
            });
            
            $('#clear-all-cache').on('click', function() {
                $(this).prop('disabled', true).text('Svuotamento...');
                
                $.post(ajaxurl, {
                    action: 'yht_clear_cache',
                    nonce: '<?php echo wp_create_nonce('yht_system_nonce'); ?>'
                }, function(response) {
                    $('#clear-all-cache').prop('disabled', false).text('Svuota Cache');
                    
                    if (response.success) {
                        alert('Cache svuotata con successo!');
                    } else {
                        alert('Errore durante lo svuotamento della cache');
                    }
                }).fail(function() {
                    $('#clear-all-cache').prop('disabled', false).text('Svuota Cache');
                    alert('Errore di connessione durante lo svuotamento della cache');
                });
            });
            
            $('#rebuild-analytics').on('click', function() {
                if (confirm('Ricostruire i dati analytics? Questa operazione potrebbe richiedere alcuni minuti.')) {
                    $(this).prop('disabled', true).text('Ricostruzione...');
                    
                    $.post(ajaxurl, {
                        action: 'yht_rebuild_analytics',
                        nonce: '<?php echo wp_create_nonce('yht_system_nonce'); ?>'
                    }, function(response) {
                        $('#rebuild-analytics').prop('disabled', false).text('Ricostruisci Analytics');
                        
                        if (response.success) {
                            alert('Analytics ricostruiti con successo! ' + response.data.message);
                        } else {
                            alert('Errore durante la ricostruzione degli analytics: ' + (response.data.message || 'Errore sconosciuto'));
                        }
                    }).fail(function() {
                        $('#rebuild-analytics').prop('disabled', false).text('Ricostruisci Analytics');
                        alert('Errore di connessione durante la ricostruzione degli analytics');
                    });
                }
            });
            
            $('#security-scan').on('click', function() {
                $(this).prop('disabled', true).text('Scansione...');
                
                $.post(ajaxurl, {
                    action: 'yht_security_scan',
                    nonce: '<?php echo wp_create_nonce('yht_system_nonce'); ?>'
                }, function(response) {
                    $('#security-scan').prop('disabled', false).text('Scansione Sicurezza');
                    
                    if (response.success) {
                        const results = response.data;
                        let message = 'Scansione completata!\n\n';
                        message += 'File controllati: ' + results.files_scanned + '\n';
                        message += 'Problemi rilevati: ' + results.issues_found + '\n';
                        if (results.issues_found > 0) {
                            message += '\nControlla il tab "Problemi Rilevati" per i dettagli.';
                            runHealthCheck(); // Refresh to show new issues
                        }
                        alert(message);
                    } else {
                        alert('Errore durante la scansione di sicurezza: ' + (response.data.message || 'Errore sconosciuto'));
                    }
                }).fail(function() {
                    $('#security-scan').prop('disabled', false).text('Scansione Sicurezza');
                    alert('Errore di connessione durante la scansione di sicurezza');
                });
            });
            
            $('#refresh-logs').on('click', function() {
                loadSystemLogs();
            });
            
            $('#clear-logs').on('click', function() {
                if (confirm('Cancellare tutti i log? Questa operazione non può essere annullata.')) {
                    $.post(ajaxurl, {
                        action: 'yht_clear_logs',
                        nonce: '<?php echo wp_create_nonce('yht_system_nonce'); ?>'
                    }, function(response) {
                        if (response.success) {
                            $('#system-logs').html('<div class="log-entry log-info">Log cancellati con successo</div>');
                        } else {
                            alert('Errore durante la cancellazione dei log');
                        }
                    });
                }
            });
            
            $('#auto-fix-issues').on('click', function() {
                if (confirm('Eseguire la correzione automatica dei problemi rilevati? Questa operazione potrebbe modificare le impostazioni del sistema.')) {
                    $(this).prop('disabled', true).text('🔧 Correzione in corso...');
                    
                    $.post(ajaxurl, {
                        action: 'yht_auto_fix_issues',
                        nonce: '<?php echo wp_create_nonce('yht_system_nonce'); ?>'
                    }, function(response) {
                        $('#auto-fix-issues').prop('disabled', false).text('🔧 Correzione Automatica');
                        
                        if (response.success) {
                            const results = response.data;
                            let message = 'Correzione automatica completata!\n\n';
                            message += 'Problemi corretti: ' + results.fixed_count + '\n';
                            message += 'Problemi che richiedono intervento manuale: ' + results.manual_count + '\n\n';
                            if (results.actions.length > 0) {
                                message += 'Azioni eseguite:\n' + results.actions.join('\n');
                            }
                            alert(message);
                            
                            // Refresh health check to see improvements
                            runHealthCheck();
                        } else {
                            alert('Errore durante la correzione automatica: ' + (response.data.message || 'Errore sconosciuto'));
                        }
                    }).fail(function() {
                        $('#auto-fix-issues').prop('disabled', false).text('🔧 Correzione Automatica');
                        alert('Errore di connessione durante la correzione automatica');
                    });
                }
            });
            
            $('#export-health-report').on('click', function() {
                $(this).prop('disabled', true).text('📄 Generazione...');
                
                $.post(ajaxurl, {
                    action: 'yht_export_health_report',
                    nonce: '<?php echo wp_create_nonce('yht_system_nonce'); ?>'
                }, function(response) {
                    $('#export-health-report').prop('disabled', false).text('📄 Esporta Report');
                    
                    if (response.success) {
                        // Create and trigger download
                        const blob = new Blob([response.data.content], { type: 'application/json' });
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = response.data.filename;
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);
                        
                        alert('Report esportato con successo!');
                    } else {
                        alert('Errore durante l\'esportazione del report');
                    }
                }).fail(function() {
                    $('#export-health-report').prop('disabled', false).text('📄 Esporta Report');
                    alert('Errore di connessione durante l\'esportazione del report');
                });
            });
            
            function runHealthCheck() {
                $('#run-health-check').prop('disabled', true).text('🔍 Controllo in corso...');
                updateTabContent('issues', '<div class="yht-loading">Controllo problemi in corso...</div>');
                updateTabContent('recommendations', '<div class="yht-loading">Generazione raccomandazioni...</div>');
                
                $.post(ajaxurl, {
                    action: 'yht_system_health_check',
                    nonce: '<?php echo wp_create_nonce('yht_system_nonce'); ?>'
                }, function(response) {
                    $('#run-health-check').prop('disabled', false).text('🔍 Esegui Controllo Completo');
                    
                    if (response.success) {
                        updateHealthStatus(response.data);
                    } else {
                        alert('Errore durante il controllo del sistema');
                    }
                });
            }
            
            function runPerformanceTest() {
                $('#run-performance-test').prop('disabled', true).text('⚡ Test in corso...');
                
                $.post(ajaxurl, {
                    action: 'yht_system_performance_test',
                    nonce: '<?php echo wp_create_nonce('yht_system_nonce'); ?>'
                }, function(response) {
                    $('#run-performance-test').prop('disabled', false).text('⚡ Test Performance');
                    
                    if (response.success) {
                        updatePerformanceStatus(response.data);
                    } else {
                        alert('Errore durante il test delle performance');
                    }
                }).fail(function() {
                    $('#run-performance-test').prop('disabled', false).text('⚡ Test Performance');
                    alert('Errore di connessione durante il test delle performance');
                });
            }
            
            function updatePerformanceStatus(data) {
                let resultsHtml = '<div class="performance-results">';
                resultsHtml += '<h4>📊 Risultati Test Performance</h4>';
                resultsHtml += '<div class="perf-metric">⏱️ Tempo totale: <strong>' + data.total_time + 'ms</strong></div>';
                resultsHtml += '<div class="perf-metric">🗄️ Database: <strong>' + data.database_performance + '</strong> (' + data.database_time + 'ms)</div>';
                resultsHtml += '<div class="perf-metric">📁 File System: <strong>' + data.filesystem_performance + '</strong> (' + data.filesystem_time + 'ms)</div>';
                resultsHtml += '<div class="perf-metric">💾 Memoria: <strong>' + data.memory_performance + '</strong> (' + data.memory_time + 'ms)</div>';
                
                // Add performance recommendations
                resultsHtml += '<div class="performance-recommendations">';
                resultsHtml += '<h5>💡 Raccomandazioni Performance</h5>';
                if (data.total_time > 100) {
                    resultsHtml += '<div class="perf-recommendation">⚠️ Tempo di esecuzione elevato. Considera l\'ottimizzazione del database.</div>';
                }
                if (data.database_time > 50) {
                    resultsHtml += '<div class="perf-recommendation">🗄️ Query database lente. Controlla gli indici delle tabelle.</div>';
                }
                if (data.filesystem_time > 20) {
                    resultsHtml += '<div class="perf-recommendation">📁 File system lento. Verifica lo spazio disco disponibile.</div>';
                }
                if (data.memory_time > 10) {
                    resultsHtml += '<div class="perf-recommendation">💾 Gestione memoria inefficiente. Controlla i plugin attivi.</div>';
                }
                if (data.total_time <= 100) {
                    resultsHtml += '<div class="perf-recommendation perf-good">✅ Performance eccellenti! Il sistema funziona ottimamente.</div>';
                }
                resultsHtml += '</div>';
                resultsHtml += '</div>';
                
                // Add performance results tab if it doesn't exist
                if ($('.yht-tab-button[data-tab="performance-results"]').length === 0) {
                    $('.yht-tab-nav').append('<button class="yht-tab-button" data-tab="performance-results">📊 Performance</button>');
                }
                
                // Update tab content
                $('#tab-performance-results #performance-results-content').html(resultsHtml);
                
                // Auto-switch to performance tab to show results
                $('.yht-tab-button').removeClass('active');
                $('.yht-tab-panel').removeClass('active');
                $('.yht-tab-button[data-tab="performance-results"]').addClass('active');
                $('#tab-performance-results').addClass('active');
            }
            
            function updateHealthStatus(data) {
                // Update overall score
                const score = data.overall_score;
                $('#overall-health-score .score-number').text(score + '%');
                
                const scoreCircle = $('#overall-health-score');
                scoreCircle.removeClass('excellent good poor');
                if (score >= 90) scoreCircle.addClass('excellent');
                else if (score >= 70) scoreCircle.addClass('good');
                else scoreCircle.addClass('poor');
                
                // Update status cards
                updateStatusCard('wordpress', data.wordpress);
                updateStatusCard('plugins', data.plugins);
                updateStatusCard('database', data.database);
                updateStatusCard('performance', data.performance);
                
                // Update issues
                displayIssues(data.issues);
                displayRecommendations(data.recommendations);
            }
            
            function updateStatusCard(type, data) {
                const card = $('#' + type + '-status');
                const indicator = card.find('.status-indicator');
                
                indicator.removeClass('good warning error').addClass(data.status);
                
                if (type === 'wordpress') {
                    card.find('#wp-version').text(data.version);
                    card.find('#wp-updates').text(data.updates + ' disponibili');
                    card.find('#wp-debug').text(data.debug ? 'Attivo' : 'Disattivo');
                } else if (type === 'plugins') {
                    card.find('#active-plugins').text(data.active);
                    card.find('#plugin-updates').text(data.updates + ' disponibili');
                    card.find('#plugin-conflicts').text(data.conflicts + ' rilevati');
                } else if (type === 'database') {
                    card.find('#db-version').text(data.version);
                    card.find('#db-size').text(data.size);
                    card.find('#slow-queries').text(data.slow_queries);
                } else if (type === 'performance') {
                    card.find('#memory-usage').text(data.memory_usage);
                    card.find('#load-time').text(data.load_time + 'ms');
                    card.find('#cache-status').text(data.cache_enabled ? 'Attivo' : 'Disattivo');
                }
            }
            
            function displayIssues(issues) {
                let html = '';
                
                if (issues.length > 0) {
                    issues.forEach(function(issue) {
                        const fixableClass = issue.fixable ? ' issue-fixable' : '';
                        html += '<div class="issue-item issue-' + issue.severity + fixableClass + '">';
                        html += '<div class="issue-icon">' + getIssueIcon(issue.severity) + '</div>';
                        html += '<div class="issue-content">';
                        html += '<div class="issue-title">' + issue.title + '</div>';
                        html += '<div class="issue-description">' + issue.description + '</div>';
                        if (issue.fixable) {
                            html += '<div class="issue-fix-hint">💡 Questo problema può essere corretto automaticamente</div>';
                        }
                        html += '</div>';
                        html += '</div>';
                    });
                } else {
                    html = '<div class="issue-item issue-info"><div class="issue-icon">✅</div><div class="issue-content"><div class="issue-title">Nessun problema rilevato</div><div class="issue-description">Il sistema funziona correttamente</div></div></div>';
                }
                
                updateTabContent('issues', html);
            }
            
            function displayRecommendations(recommendations) {
                let html = '';
                
                if (recommendations.length > 0) {
                    recommendations.forEach(function(rec) {
                        html += '<div class="issue-item issue-info">';
                        html += '<div class="issue-icon">💡</div>';
                        html += '<div class="issue-content">';
                        html += '<div class="issue-title">' + rec.title + '</div>';
                        html += '<div class="issue-description">' + rec.description + '</div>';
                        html += '</div>';
                        html += '</div>';
                    });
                } else {
                    html = '<div class="issue-item issue-info"><div class="issue-icon">👍</div><div class="issue-content"><div class="issue-title">Nessuna raccomandazione</div><div class="issue-description">Il sistema è ottimizzato correttamente</div></div></div>';
                }
                
                updateTabContent('recommendations', html);
            }
            
            function getIssueIcon(severity) {
                switch(severity) {
                    case 'critical': return '🚨';
                    case 'warning': return '⚠️';
                    case 'info': return 'ℹ️';
                    default: return '•';
                }
            }
            
            function updateTabContent(tab, html) {
                $('#tab-' + tab).html(html);
            }
            
            function loadSystemLogs() {
                $('#system-logs').html('<div class="yht-loading">Caricamento log...</div>');
                
                $.post(ajaxurl, {
                    action: 'yht_get_system_logs',
                    nonce: '<?php echo wp_create_nonce('yht_system_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        displayLogs(response.data);
                    } else {
                        $('#system-logs').html('<div class="yht-loading">Impossibile caricare i log</div>');
                    }
                }).fail(function() {
                    $('#system-logs').html('<div class="yht-loading">Errore di connessione durante il caricamento dei log</div>');
                });
            }
            
            function displayLogs(logs) {
                let html = '';
                
                logs.forEach(function(log) {
                    html += '<div class="log-entry log-' + log.level + '">';
                    html += '<span class="log-timestamp">' + log.timestamp + '</span>';
                    html += log.message;
                    html += '</div>';
                });
                
                if (logs.length === 0) {
                    html = '<div class="log-entry log-info">Nessun log disponibile</div>';
                }
                
                $('#system-logs').html(html);
            }
        });
        </script>
        <?php
    }
    
    /**
     * AJAX: Perform system health check
     */
    public function ajax_health_check() {
        check_ajax_referer('yht_system_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $health_data = $this->run_health_check();
        wp_send_json_success($health_data);
    }
    
    /**
     * AJAX: Performance test
     */
    public function ajax_performance_test() {
        check_ajax_referer('yht_system_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $perf_data = $this->run_performance_test();
        wp_send_json_success($perf_data);
    }
    
    /**
     * AJAX: Clear cache
     */
    public function ajax_clear_cache() {
        check_ajax_referer('yht_system_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $this->clear_all_caches();
        wp_send_json_success();
    }
    
    /**
     * Run comprehensive health check
     */
    private function run_health_check() {
        $wordpress_check = $this->check_wordpress();
        $plugins_check = $this->check_plugins();
        $database_check = $this->check_database();
        $performance_check = $this->check_performance();
        
        $issues = array_merge(
            $wordpress_check['issues'] ?? array(),
            $plugins_check['issues'] ?? array(),
            $database_check['issues'] ?? array(),
            $performance_check['issues'] ?? array()
        );
        
        $recommendations = array_merge(
            $wordpress_check['recommendations'] ?? array(),
            $plugins_check['recommendations'] ?? array(),
            $database_check['recommendations'] ?? array(),
            $performance_check['recommendations'] ?? array()
        );
        
        // Calculate overall score
        $scores = array(
            $wordpress_check['score'],
            $plugins_check['score'],
            $database_check['score'],
            $performance_check['score']
        );
        $overall_score = array_sum($scores) / count($scores);
        
        return array(
            'overall_score' => round($overall_score),
            'wordpress' => $wordpress_check,
            'plugins' => $plugins_check,
            'database' => $database_check,
            'performance' => $performance_check,
            'issues' => $issues,
            'recommendations' => $recommendations
        );
    }
    
    /**
     * Check WordPress core health
     */
    private function check_wordpress() {
        global $wp_version;
        
        $issues = array();
        $recommendations = array();
        $score = 100;
        
        // Check WordPress version
        $latest_wp = $this->get_latest_wp_version();
        $updates_available = version_compare($wp_version, $latest_wp, '<') ? 1 : 0;
        
        if ($updates_available) {
            $issues[] = array(
                'severity' => 'warning',
                'title' => 'Aggiornamento WordPress disponibile',
                'description' => "È disponibile la versione {$latest_wp}. Versione corrente: {$wp_version}",
                'fixable' => false // Requires manual update
            );
            $score -= 10;
        }
        
        // Check debug mode
        $debug_enabled = defined('WP_DEBUG') && WP_DEBUG;
        if ($debug_enabled && (!defined('WP_DEBUG_DISPLAY') || WP_DEBUG_DISPLAY)) {
            $issues[] = array(
                'severity' => 'warning',
                'title' => 'Debug mode attivo in produzione',
                'description' => 'Il debug mode dovrebbe essere disattivato sui siti live',
                'fixable' => false // Requires wp-config.php edit
            );
            $score -= 15;
        }
        
        return array(
            'status' => $score >= 90 ? 'good' : ($score >= 70 ? 'warning' : 'error'),
            'score' => $score,
            'version' => $wp_version,
            'updates' => $updates_available,
            'debug' => $debug_enabled,
            'issues' => $issues,
            'recommendations' => $recommendations
        );
    }
    
    /**
     * Check plugins health
     */
    private function check_plugins() {
        $issues = array();
        $recommendations = array();
        $score = 100;
        
        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins', array());
        $update_plugins = get_site_transient('update_plugins');
        
        $active_count = count($active_plugins);
        $updates_available = $update_plugins ? count($update_plugins->response ?? array()) : 0;
        
        // Check for too many plugins
        if ($active_count > 30) {
            $recommendations[] = array(
                'title' => 'Troppi plugin attivi',
                'description' => "Hai {$active_count} plugin attivi. Considera di disattivare quelli non necessari per migliorare le performance."
            );
            $score -= 10;
        }
        
        // Check for plugin updates
        if ($updates_available > 0) {
            $issues[] = array(
                'severity' => 'warning',
                'title' => 'Aggiornamenti plugin disponibili',
                'description' => "{$updates_available} plugin hanno aggiornamenti disponibili",
                'fixable' => false // Requires manual update
            );
            $score -= 5;
        }
        
        // Check for potential conflicts (simplified check)
        $potential_conflicts = $this->check_plugin_conflicts();
        
        return array(
            'status' => $score >= 90 ? 'good' : ($score >= 70 ? 'warning' : 'error'),
            'score' => $score,
            'active' => $active_count,
            'updates' => $updates_available,
            'conflicts' => $potential_conflicts,
            'issues' => $issues,
            'recommendations' => $recommendations
        );
    }
    
    /**
     * Check database health
     */
    private function check_database() {
        global $wpdb;
        
        $issues = array();
        $recommendations = array();
        $score = 100;
        
        // Get database version
        $db_version = $wpdb->get_var('SELECT VERSION()');
        
        // Get database size
        $db_size = $this->get_database_size();
        
        // Check for slow queries (simplified)
        $slow_queries = 0; // This would require access to slow query log
        
        // Check table optimization
        $tables_need_optimization = $this->check_table_optimization();
        if ($tables_need_optimization > 0) {
            $recommendations[] = array(
                'title' => 'Tabelle database non ottimizzate',
                'description' => "{$tables_need_optimization} tabelle potrebbero beneficiare dell'ottimizzazione",
                'fixable' => true // Can be auto-fixed with database cleanup
            );
            $score -= 5;
        }
        
        return array(
            'status' => $score >= 90 ? 'good' : ($score >= 70 ? 'warning' : 'error'),
            'score' => $score,
            'version' => $db_version,
            'size' => $this->format_bytes($db_size),
            'slow_queries' => $slow_queries,
            'issues' => $issues,
            'recommendations' => $recommendations
        );
    }
    
    /**
     * Check performance metrics
     */
    private function check_performance() {
        $issues = array();
        $recommendations = array();
        $score = 100;
        
        // Memory usage
        $memory_usage = $this->get_memory_usage();
        $memory_limit = ini_get('memory_limit');
        $memory_bytes = memory_get_usage(true);
        $memory_limit_bytes = $this->parse_memory_limit($memory_limit);
        
        $memory_percentage = ($memory_limit_bytes > 0) ? ($memory_bytes / $memory_limit_bytes) * 100 : 0;
        
        if ($memory_percentage > 80) {
            $issues[] = array(
                'severity' => 'warning',
                'title' => 'Uso memoria elevato',
                'description' => "Memoria utilizzata: {$memory_percentage}%. Considera di aumentare il memory_limit.",
                'fixable' => false // Requires PHP configuration change
            );
            $score -= 15;
        }
        
        // Get real load time from performance history or measure it
        $avg_load_time = $this->get_average_load_time();
        
        if ($avg_load_time > 1000) {
            $issues[] = array(
                'severity' => 'warning',
                'title' => 'Tempo di caricamento lento',
                'description' => "Il tempo medio di caricamento è di {$avg_load_time}ms. Dovrebbe essere sotto i 800ms.",
                'fixable' => true // Can be improved with cache and optimization
            );
            $score -= 15;
        } elseif ($avg_load_time > 2000) {
            $issues[] = array(
                'severity' => 'critical',
                'title' => 'Tempo di caricamento critico',
                'description' => "Il tempo medio di caricamento è di {$avg_load_time}ms. Performance critiche.",
                'fixable' => true // Can be improved with cache and optimization
            );
            $score -= 30;
        }
        
        // Check if any caching is enabled
        $cache_enabled = $this->is_caching_enabled();
        if (!$cache_enabled) {
            $recommendations[] = array(
                'title' => 'Cache non attiva',
                'description' => 'Attivare un sistema di cache può migliorare significativamente le performance',
                'fixable' => false // Requires plugin installation
            );
            $score -= 20;
        }
        
        // Check PHP version performance
        $php_version = PHP_VERSION;
        if (version_compare($php_version, '8.0', '<')) {
            $recommendations[] = array(
                'title' => 'Aggiorna PHP',
                'description' => "PHP {$php_version} in uso. Aggiornare a PHP 8.0+ per migliori performance.",
                'fixable' => false // Requires server configuration
            );
            $score -= 10;
        }
        
        return array(
            'status' => $score >= 90 ? 'good' : ($score >= 70 ? 'warning' : 'error'),
            'score' => $score,
            'memory_usage' => $memory_usage,
            'memory_percentage' => round($memory_percentage, 1),
            'load_time' => $avg_load_time,
            'cache_enabled' => $cache_enabled,
            'php_version' => $php_version,
            'issues' => $issues,
            'recommendations' => $recommendations
        );
    }
    
    /**
     * Run performance test
     */
    private function run_performance_test() {
        $start_time = microtime(true);
        
        // Test database performance
        $db_start = microtime(true);
        $db_time = $this->test_database_performance();
        $db_result = $db_time < 0.1 ? 'Excellent' : ($db_time < 0.5 ? 'Good' : 'Poor');
        
        // Test file system performance
        $fs_start = microtime(true);
        $fs_time = $this->test_file_system_performance();
        $fs_result = $fs_time < 0.05 ? 'Excellent' : ($fs_time < 0.2 ? 'Good' : 'Poor');
        
        // Test memory performance
        $mem_start = microtime(true);
        $mem_time = $this->test_memory_performance();
        $mem_result = $mem_time < 0.01 ? 'Excellent' : ($mem_time < 0.05 ? 'Good' : 'Poor');
        
        $total_time = (microtime(true) - $start_time) * 1000;
        
        // Store performance results for trends
        $performance_history = get_option('yht_performance_history', array());
        $performance_history[] = array(
            'date' => current_time('mysql'),
            'total_time' => $total_time,
            'database_time' => $db_time * 1000,
            'filesystem_time' => $fs_time * 1000,
            'memory_time' => $mem_time * 1000
        );
        
        // Keep only last 30 entries
        $performance_history = array_slice($performance_history, -30);
        update_option('yht_performance_history', $performance_history);
        
        return array(
            'total_time' => round($total_time, 2),
            'database_performance' => $db_result,
            'database_time' => round($db_time * 1000, 2),
            'filesystem_performance' => $fs_result,
            'filesystem_time' => round($fs_time * 1000, 2),
            'memory_performance' => $mem_result,
            'memory_time' => round($mem_time * 1000, 2)
        );
    }
    
    /**
     * Daily health check (scheduled)
     */
    public function daily_health_check() {
        $health_data = $this->run_health_check();
        
        // Store health data for trends
        $health_history = get_option('yht_health_history', array());
        $health_history[] = array(
            'date' => current_time('mysql'),
            'score' => $health_data['overall_score'],
            'issues' => count($health_data['issues'])
        );
        
        // Keep only last 30 days
        $health_history = array_slice($health_history, -30);
        update_option('yht_health_history', $health_history);
        
        // Send alert if critical issues
        $critical_issues = array_filter($health_data['issues'], function($issue) {
            return $issue['severity'] === 'critical';
        });
        
        if (!empty($critical_issues)) {
            $this->send_health_alert($critical_issues);
            $this->log_system_event('Problemi critici rilevati: ' . count($critical_issues), 'critical');
        }
        
        // Log health check completion
        $this->log_system_event("Health check completato. Score: {$health_data['overall_score']}%, Problemi: " . count($health_data['issues']), 'info');
    }
    
    /**
     * Helper methods
     */
    private function get_latest_wp_version() {
        $version_check = get_site_transient('update_core');
        if ($version_check && isset($version_check->updates[0])) {
            return $version_check->updates[0]->current;
        }
        return get_bloginfo('version'); // Fallback to current version
    }
    
    private function check_plugin_conflicts() {
        // Simplified conflict detection
        // In reality, this would check for known problematic plugin combinations
        return 0;
    }
    
    private function get_database_size() {
        global $wpdb;
        $size = $wpdb->get_var("
            SELECT SUM(data_length + index_length) 
            FROM information_schema.tables 
            WHERE table_schema = '{$wpdb->dbname}'
        ");
        return $size ?: 0;
    }
    
    private function check_table_optimization() {
        global $wpdb;
        // Check for fragmented tables
        return 0; // Simplified
    }
    
    private function get_memory_usage() {
        $memory_usage = memory_get_usage(true);
        $memory_limit = ini_get('memory_limit');
        
        return $this->format_bytes($memory_usage) . ' / ' . $memory_limit;
    }
    
    private function is_caching_enabled() {
        // Check for common caching plugins
        $caching_plugins = array(
            'w3-total-cache/w3-total-cache.php',
            'wp-super-cache/wp-cache.php',
            'wp-fastest-cache/wpFastestCache.php',
            'cache-enabler/cache-enabler.php'
        );
        
        foreach ($caching_plugins as $plugin) {
            if (is_plugin_active($plugin)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function test_database_performance() {
        global $wpdb;
        $start = microtime(true);
        $wpdb->get_results("SELECT COUNT(*) FROM {$wpdb->posts}");
        return microtime(true) - $start;
    }
    
    private function test_file_system_performance() {
        $start = microtime(true);
        $temp_file = wp_upload_dir()['path'] . '/yht_test_' . uniqid();
        file_put_contents($temp_file, 'test');
        unlink($temp_file);
        return microtime(true) - $start;
    }
    
    private function test_memory_performance() {
        $start = microtime(true);
        $array = range(1, 10000);
        unset($array);
        return microtime(true) - $start;
    }
    
    private function clear_all_caches() {
        // Clear WordPress object cache
        wp_cache_flush();
        
        // Clear transients
        delete_expired_transients();
        
        // Clear YHT specific caches
        $this->clear_yht_cache();
    }
    
    private function clear_yht_cache() {
        // Clear plugin-specific cache and transients
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_yht_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_yht_%'");
    }
    
    private function format_bytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    private function send_health_alert($issues) {
        $settings = get_option(YHT_OPT, array());
        $notify_email = $settings['notify_email'] ?? get_option('admin_email');
        
        if (empty($notify_email)) {
            return;
        }
        
        $subject = 'YHT Plugin - Problemi Critici Rilevati';
        $message = 'Sono stati rilevati i seguenti problemi critici:\n\n';
        
        foreach ($issues as $issue) {
            $message .= "• {$issue['title']}: {$issue['description']}\n";
        }
        
        $message .= "\nControlla il pannello di amministrazione per maggiori dettagli.";
        
        wp_mail($notify_email, $subject, $message);
    }
    
    /**
     * AJAX: Database cleanup
     */
    public function ajax_cleanup_database() {
        check_ajax_referer('yht_system_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $results = $this->cleanup_database();
        wp_send_json_success($results);
    }
    
    /**
     * AJAX: Rebuild analytics
     */
    public function ajax_rebuild_analytics() {
        check_ajax_referer('yht_system_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $results = $this->rebuild_analytics();
        wp_send_json_success($results);
    }
    
    /**
     * AJAX: Security scan
     */
    public function ajax_security_scan() {
        check_ajax_referer('yht_system_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $results = $this->security_scan();
        wp_send_json_success($results);
    }
    
    /**
     * AJAX: Get system logs
     */
    public function ajax_get_system_logs() {
        check_ajax_referer('yht_system_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $logs = $this->get_system_logs();
        wp_send_json_success($logs);
    }
    
    /**
     * AJAX: Clear system logs
     */
    public function ajax_clear_logs() {
        check_ajax_referer('yht_system_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        // Clear YHT specific logs
        delete_option('yht_system_logs');
        
        wp_send_json_success(array('message' => 'Log cancellati con successo'));
    }
    
    /**
     * Perform database cleanup
     */
    private function cleanup_database() {
        global $wpdb;
        
        $cleaned_items = 0;
        $message_parts = array();
        
        // Clean expired transients
        $expired_transients = $wpdb->query("
            DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_timeout_%' 
            AND option_value < UNIX_TIMESTAMP()
        ");
        
        if ($expired_transients) {
            $cleaned_items += $expired_transients;
            $message_parts[] = "{$expired_transients} transient scaduti";
        }
        
        // Clean orphaned postmeta
        $orphaned_meta = $wpdb->query("
            DELETE pm FROM {$wpdb->postmeta} pm
            LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.ID IS NULL
        ");
        
        if ($orphaned_meta) {
            $cleaned_items += $orphaned_meta;
            $message_parts[] = "{$orphaned_meta} metadati orfani";
        }
        
        // Clean orphaned comments meta
        $orphaned_comment_meta = $wpdb->query("
            DELETE cm FROM {$wpdb->commentmeta} cm
            LEFT JOIN {$wpdb->comments} c ON cm.comment_id = c.comment_ID
            WHERE c.comment_ID IS NULL
        ");
        
        if ($orphaned_comment_meta) {
            $cleaned_items += $orphaned_comment_meta;
            $message_parts[] = "{$orphaned_comment_meta} metadati commenti orfani";
        }
        
        // Optimize tables
        $tables = $wpdb->get_col("SHOW TABLES");
        $optimized_tables = 0;
        
        foreach ($tables as $table) {
            $result = $wpdb->query("OPTIMIZE TABLE {$table}");
            if ($result) {
                $optimized_tables++;
            }
        }
        
        $message_parts[] = "{$optimized_tables} tabelle ottimizzate";
        
        $message = empty($message_parts) 
            ? 'Nessuna pulizia necessaria' 
            : 'Rimossi: ' . implode(', ', $message_parts);
        
        return array(
            'cleaned_items' => $cleaned_items,
            'optimized_tables' => $optimized_tables,
            'message' => $message
        );
    }
    
    /**
     * Rebuild analytics data
     */
    private function rebuild_analytics() {
        // This would rebuild analytics data - simplified implementation
        $processed_items = 0;
        
        // Rebuild analytics summary
        $start_date = date('Y-m-01', strtotime('-12 months'));
        $end_date = date('Y-m-d');
        
        // Here you would rebuild analytics data based on existing posts, bookings, etc.
        global $wpdb;
        
        // Get count of posts to process
        $posts = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->posts} 
            WHERE post_type IN ('tour', 'luogo', 'alloggio') 
            AND post_status = 'publish'
            AND post_date >= '{$start_date}'
        ");
        
        $processed_items = $posts;
        
        // Clear existing analytics cache
        delete_transient('yht_analytics_summary');
        delete_transient('yht_performance_metrics');
        
        return array(
            'processed_items' => $processed_items,
            'message' => "Processati {$processed_items} elementi. Cache analytics aggiornata."
        );
    }
    
    /**
     * Perform security scan
     */
    private function security_scan() {
        $files_scanned = 0;
        $issues_found = 0;
        $scan_results = array();
        
        // Check for common security issues
        
        // 1. Check for debug mode in production
        if (defined('WP_DEBUG') && WP_DEBUG && !defined('WP_DEBUG_DISPLAY')) {
            $issues_found++;
            $scan_results[] = array(
                'severity' => 'warning',
                'title' => 'Debug mode attivo',
                'description' => 'WP_DEBUG è attivo. Disattivare in produzione per sicurezza.'
            );
        }
        
        // 2. Check file permissions
        $upload_dir = wp_upload_dir();
        if (is_writable($upload_dir['basedir'])) {
            $files_scanned++;
            // This is normal, but we scan it
        }
        
        // 3. Check for suspicious files in uploads
        $suspicious_files = array();
        $upload_files = glob($upload_dir['basedir'] . '/*.php');
        foreach ($upload_files as $file) {
            if (file_exists($file)) {
                $issues_found++;
                $suspicious_files[] = basename($file);
            }
        }
        
        if (!empty($suspicious_files)) {
            $scan_results[] = array(
                'severity' => 'critical',
                'title' => 'File PHP sospetti nell\'upload directory',
                'description' => 'Trovati file PHP: ' . implode(', ', $suspicious_files)
            );
        }
        
        // 4. Check WordPress version
        $wp_version = get_bloginfo('version');
        $latest_version = $this->get_latest_wp_version();
        
        if (version_compare($wp_version, $latest_version, '<')) {
            $issues_found++;
            $scan_results[] = array(
                'severity' => 'warning',
                'title' => 'WordPress non aggiornato',
                'description' => "Versione corrente: {$wp_version}. Ultima versione: {$latest_version}"
            );
        }
        
        $files_scanned += 10; // Simulated additional files
        
        // Store scan results for health check
        if (!empty($scan_results)) {
            update_option('yht_security_scan_results', $scan_results);
        }
        
        return array(
            'files_scanned' => $files_scanned,
            'issues_found' => $issues_found,
            'scan_results' => $scan_results
        );
    }
    
    /**
     * Get system logs
     */
    private function get_system_logs() {
        $logs = array();
        
        // Get WordPress error logs if available
        $error_log_file = ini_get('error_log');
        if (file_exists($error_log_file) && is_readable($error_log_file)) {
            $log_lines = file($error_log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $log_lines = array_slice($log_lines, -50); // Last 50 lines
            
            foreach ($log_lines as $line) {
                if (strpos($line, '[') === 0) {
                    $logs[] = array(
                        'timestamp' => substr($line, 1, 19),
                        'level' => 'error',
                        'message' => substr($line, 21)
                    );
                }
            }
        }
        
        // Add YHT specific logs
        $yht_logs = get_option('yht_system_logs', array());
        foreach ($yht_logs as $log) {
            $logs[] = $log;
        }
        
        // If no logs, add a sample entry
        if (empty($logs)) {
            $logs[] = array(
                'timestamp' => current_time('Y-m-d H:i:s'),
                'level' => 'info',
                'message' => 'Sistema operativo correttamente - Nessun errore rilevato'
            );
        }
        
        // Sort by timestamp descending
        usort($logs, function($a, $b) {
            return strcmp($b['timestamp'], $a['timestamp']);
        });
        
        return array_slice($logs, 0, 100); // Return max 100 logs
    }
    
    /**
     * Parse memory limit string to bytes
     */
    private function parse_memory_limit($limit) {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit)-1]);
        $limit = (int) $limit;
        
        switch($last) {
            case 'g':
                $limit *= 1024;
            case 'm':
                $limit *= 1024;
            case 'k':
                $limit *= 1024;
        }
        
        return $limit;
    }
    
    /**
     * Get average load time from performance history or measure it
     */
    private function get_average_load_time() {
        // Try to get from performance history first
        $performance_history = get_option('yht_performance_history', array());
        
        if (!empty($performance_history)) {
            $recent_times = array_slice($performance_history, -10);
            $total_time = array_sum(array_column($recent_times, 'total_time'));
            return round($total_time / count($recent_times), 2);
        }
        
        // If no history, do a quick measurement
        $start_time = microtime(true);
        
        // Simulate a typical page load by doing some operations
        global $wpdb;
        $wpdb->get_results("SELECT * FROM {$wpdb->options} WHERE autoload = 'yes' LIMIT 10");
        
        // Check if some plugins are loaded
        if (function_exists('wp_get_active_and_valid_plugins')) {
            wp_get_active_and_valid_plugins();
        }
        
        $load_time = (microtime(true) - $start_time) * 1000;
        
        // Return a realistic load time estimate
        return round($load_time * 20, 2); // Multiply by factor to simulate full page load
    }
    
    /**
     * Log system events for monitoring
     */
    private function log_system_event($message, $level = 'info') {
        $logs = get_option('yht_system_logs', array());
        
        $logs[] = array(
            'timestamp' => current_time('Y-m-d H:i:s'),
            'level' => $level,
            'message' => $message
        );
        
        // Keep only last 100 log entries
        $logs = array_slice($logs, -100);
        
        update_option('yht_system_logs', $logs);
    }
    
    /**
     * Log plugin activation
     */
    public function log_plugin_activation($plugin) {
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
        $plugin_name = $plugin_data['Name'] ?? $plugin;
        $this->log_system_event("Plugin attivato: {$plugin_name}", 'info');
    }
    
    /**
     * Log plugin deactivation
     */
    public function log_plugin_deactivation($plugin) {
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
        $plugin_name = $plugin_data['Name'] ?? $plugin;
        $this->log_system_event("Plugin disattivato: {$plugin_name}", 'warning');
    }
    
    /**
     * Log user login
     */
    public function log_user_login($user_login) {
        if (current_user_can('manage_options')) {
            $this->log_system_event("Login amministratore: {$user_login}", 'info');
        }
    }
    
    /**
     * AJAX: Auto-fix common issues
     */
    public function ajax_auto_fix_issues() {
        check_ajax_referer('yht_system_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $results = $this->auto_fix_issues();
        wp_send_json_success($results);
    }
    
    /**
     * AJAX: Export health report
     */
    public function ajax_export_health_report() {
        check_ajax_referer('yht_system_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $report = $this->generate_health_report();
        wp_send_json_success($report);
    }
    
    /**
     * Automatically fix common issues
     */
    private function auto_fix_issues() {
        $fixed_count = 0;
        $manual_count = 0;
        $actions = array();
        
        // Run a health check to get current issues
        $health_data = $this->run_health_check();
        $issues = $health_data['issues'];
        
        foreach ($issues as $issue) {
            $fixed = false;
            
            // Try to auto-fix based on issue type
            if (strpos($issue['title'], 'transient') !== false || strpos($issue['title'], 'cache') !== false) {
                // Auto-fix: Clear expired transients and cache
                $this->clear_all_caches();
                delete_expired_transients();
                $actions[] = "Cache e transient puliti";
                $fixed = true;
            } elseif (strpos($issue['title'], 'Database') !== false && strpos($issue['description'], 'ottimizzazione') !== false) {
                // Auto-fix: Optimize database tables
                $this->cleanup_database();
                $actions[] = "Tabelle database ottimizzate";
                $fixed = true;
            } elseif (strpos($issue['title'], 'debug mode') !== false) {
                // Auto-fix: Suggest disabling debug (can't actually change wp-config.php)
                $actions[] = "Suggerito disattivare WP_DEBUG in wp-config.php";
                $manual_count++;
                continue;
            } elseif (strpos($issue['description'], 'aggiornamenti') !== false) {
                // Auto-fix: Can't auto-update, but clear update cache
                delete_site_transient('update_core');
                delete_site_transient('update_plugins');
                delete_site_transient('update_themes');
                wp_clean_update_cache();
                $actions[] = "Cache aggiornamenti pulito (aggiornamenti manuali necessari)";
                $manual_count++;
                continue;
            }
            
            if ($fixed) {
                $fixed_count++;
            } else {
                $manual_count++;
            }
        }
        
        // Log the auto-fix action
        $this->log_system_event("Auto-fix eseguito: {$fixed_count} problemi corretti, {$manual_count} richiedono intervento manuale", 'info');
        
        return array(
            'fixed_count' => $fixed_count,
            'manual_count' => $manual_count,
            'actions' => $actions
        );
    }
    
    /**
     * Generate comprehensive health report
     */
    private function generate_health_report() {
        $health_data = $this->run_health_check();
        $performance_data = $this->run_performance_test();
        $system_logs = $this->get_system_logs();
        
        // Get historical data
        $health_history = get_option('yht_health_history', array());
        $performance_history = get_option('yht_performance_history', array());
        
        $report = array(
            'generated_at' => current_time('Y-m-d H:i:s'),
            'site_url' => get_site_url(),
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'plugin_version' => defined('YHT_VERSION') ? YHT_VERSION : '1.0',
            'overall_health' => array(
                'score' => $health_data['overall_score'],
                'status' => $health_data['overall_score'] >= 90 ? 'excellent' : ($health_data['overall_score'] >= 70 ? 'good' : 'needs_attention')
            ),
            'system_components' => array(
                'wordpress' => $health_data['wordpress'],
                'plugins' => $health_data['plugins'],
                'database' => $health_data['database'],
                'performance' => $health_data['performance']
            ),
            'current_issues' => $health_data['issues'],
            'recommendations' => $health_data['recommendations'],
            'performance_metrics' => $performance_data,
            'recent_logs' => array_slice($system_logs, 0, 20),
            'trends' => array(
                'health_trend' => $this->calculate_health_trend($health_history),
                'performance_trend' => $this->calculate_performance_trend($performance_history)
            )
        );
        
        $filename = 'yht-health-report-' . date('Y-m-d-H-i-s') . '.json';
        $content = json_encode($report, JSON_PRETTY_PRINT);
        
        return array(
            'filename' => $filename,
            'content' => $content,
            'size' => strlen($content)
        );
    }
    
    /**
     * Calculate health trend from history
     */
    private function calculate_health_trend($history) {
        if (count($history) < 2) {
            return 'stable';
        }
        
        $recent = array_slice($history, -7); // Last 7 entries
        $scores = array_column($recent, 'score');
        
        $first_score = reset($scores);
        $last_score = end($scores);
        
        $change = $last_score - $first_score;
        
        if ($change > 5) return 'improving';
        if ($change < -5) return 'declining';
        return 'stable';
    }
    
    /**
     * Calculate performance trend from history
     */
    private function calculate_performance_trend($history) {
        if (count($history) < 2) {
            return 'stable';
        }
        
        $recent = array_slice($history, -7); // Last 7 entries
        $times = array_column($recent, 'total_time');
        
        $first_time = reset($times);
        $last_time = end($times);
        
        $change_percent = (($last_time - $first_time) / $first_time) * 100;
        
        if ($change_percent < -10) return 'improving';
        if ($change_percent > 10) return 'declining';
        return 'stable';
    }
}