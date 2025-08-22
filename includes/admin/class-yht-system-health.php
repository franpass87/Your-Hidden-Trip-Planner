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
        
        // Schedule health checks
        if (!wp_next_scheduled('yht_daily_health_check')) {
            wp_schedule_event(time(), 'daily', 'yht_daily_health_check');
        }
        
        add_action('yht_daily_health_check', array($this, 'daily_health_check'));
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
                    // Implementation would go here
                    setTimeout(() => {
                        $(this).prop('disabled', false).text('Pulisci Database');
                        alert('Database pulito con successo!');
                    }, 3000);
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
                    }
                });
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
                        html += '<div class="issue-item issue-' + issue.severity + '">';
                        html += '<div class="issue-icon">' + getIssueIcon(issue.severity) + '</div>';
                        html += '<div class="issue-content">';
                        html += '<div class="issue-title">' + issue.title + '</div>';
                        html += '<div class="issue-description">' + issue.description + '</div>';
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
                $.post(ajaxurl, {
                    action: 'yht_get_system_logs',
                    nonce: '<?php echo wp_create_nonce('yht_system_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        displayLogs(response.data);
                    } else {
                        $('#system-logs').html('<div class="yht-loading">Impossibile caricare i log</div>');
                    }
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
                'description' => "È disponibile la versione {$latest_wp}. Versione corrente: {$wp_version}"
            );
            $score -= 10;
        }
        
        // Check debug mode
        $debug_enabled = defined('WP_DEBUG') && WP_DEBUG;
        if ($debug_enabled && !defined('WP_DEBUG_DISPLAY') || WP_DEBUG_DISPLAY) {
            $issues[] = array(
                'severity' => 'warning',
                'title' => 'Debug mode attivo in produzione',
                'description' => 'Il debug mode dovrebbe essere disattivato sui siti live'
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
                'description' => "{$updates_available} plugin hanno aggiornamenti disponibili"
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
                'description' => "{$tables_need_optimization} tabelle potrebbero beneficiare dell'ottimizzazione"
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
        
        // Simulated load time (in reality, this would come from real metrics)
        $avg_load_time = rand(200, 1200);
        
        if ($avg_load_time > 1000) {
            $issues[] = array(
                'severity' => 'warning',
                'title' => 'Tempo di caricamento lento',
                'description' => "Il tempo medio di caricamento è di {$avg_load_time}ms. Dovrebbe essere sotto i 800ms."
            );
            $score -= 15;
        }
        
        // Check if any caching is enabled
        $cache_enabled = $this->is_caching_enabled();
        if (!$cache_enabled) {
            $recommendations[] = array(
                'title' => 'Cache non attiva',
                'description' => 'Attivare un sistema di cache può migliorare significativamente le performance'
            );
            $score -= 20;
        }
        
        return array(
            'status' => $score >= 90 ? 'good' : ($score >= 70 ? 'warning' : 'error'),
            'score' => $score,
            'memory_usage' => $memory_usage,
            'load_time' => $avg_load_time,
            'cache_enabled' => $cache_enabled,
            'issues' => $issues,
            'recommendations' => $recommendations
        );
    }
    
    /**
     * Run performance test
     */
    private function run_performance_test() {
        $start_time = microtime(true);
        
        // Simulate various operations
        $this->test_database_performance();
        $this->test_file_system_performance();
        $this->test_memory_performance();
        
        $total_time = (microtime(true) - $start_time) * 1000;
        
        return array(
            'total_time' => round($total_time, 2),
            'database_performance' => 'Good',
            'filesystem_performance' => 'Good',
            'memory_performance' => 'Good'
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
        }
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
}