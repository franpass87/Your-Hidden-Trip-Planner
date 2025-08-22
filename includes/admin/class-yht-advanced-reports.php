<?php
/**
 * Advanced Reports and Export System
 * 
 * @package YourHiddenTrip
 * @version 6.3
 */

if (!defined('ABSPATH')) exit;

/**
 * Advanced Reports Manager Class
 */
class YHT_Advanced_Reports {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_page'), 16);
        add_action('wp_ajax_yht_generate_report', array($this, 'ajax_generate_report'));
        add_action('wp_ajax_yht_export_report', array($this, 'ajax_export_report'));
        add_action('wp_ajax_yht_schedule_report', array($this, 'ajax_schedule_report'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('yht_daily_reports', array($this, 'send_scheduled_reports'));
        
        // Schedule daily reports check
        if (!wp_next_scheduled('yht_daily_reports')) {
            wp_schedule_event(time(), 'daily', 'yht_daily_reports');
        }
    }

    /**
     * Add menu page
     */
    public function add_menu_page() {
        add_submenu_page(
            'yht-dashboard',
            __('📊 Report Avanzati', 'your-hidden-trip'),
            __('📊 Report Avanzati', 'your-hidden-trip'),
            'manage_options',
            'yht-advanced-reports',
            array($this, 'render_page')
        );
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'your-hidden-trip_page_yht-advanced-reports') return;

        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
        wp_enqueue_script('date-fns', 'https://cdn.jsdelivr.net/npm/date-fns@2.29.3/index.min.js', array(), '2.29.3', true);
        
        wp_localize_script('jquery', 'yhtAdvancedReports', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('yht_advanced_reports_nonce'),
            'strings' => array(
                'generating' => __('Generazione report...', 'your-hidden-trip'),
                'exporting' => __('Esportazione in corso...', 'your-hidden-trip'),
                'scheduled' => __('Report programmato con successo!', 'your-hidden-trip'),
                'error' => __('Errore durante l\'operazione.', 'your-hidden-trip'),
                'no_data' => __('Nessun dato disponibile per il periodo selezionato.', 'your-hidden-trip')
            )
        ));
    }

    /**
     * Render the advanced reports page
     */
    public function render_page() {
        ?>
        <div class="wrap yht-advanced-reports-page">
            <div class="yht-header">
                <h1>📊 <?php _e('Report e Analisi Avanzate', 'your-hidden-trip'); ?></h1>
                <p class="description">
                    <?php _e('Genera report dettagliati, visualizza analisi avanzate ed esporta i dati in vari formati.', 'your-hidden-trip'); ?>
                </p>
            </div>

            <div class="yht-reports-container">
                <div class="yht-report-controls">
                    <div class="control-group">
                        <h3><?php _e('Filtri Report', 'your-hidden-trip'); ?></h3>
                        
                        <div class="filter-row">
                            <div class="filter-item">
                                <label for="report_type"><?php _e('Tipo Report', 'your-hidden-trip'); ?></label>
                                <select id="report_type" class="regular-text">
                                    <option value="bookings"><?php _e('📋 Analisi Prenotazioni', 'your-hidden-trip'); ?></option>
                                    <option value="revenue"><?php _e('💰 Analisi Ricavi', 'your-hidden-trip'); ?></option>
                                    <option value="customers"><?php _e('👥 Analisi Clienti', 'your-hidden-trip'); ?></option>
                                    <option value="locations"><?php _e('🗺️ Analisi Destinazioni', 'your-hidden-trip'); ?></option>
                                    <option value="performance"><?php _e('⚡ Performance Sistema', 'your-hidden-trip'); ?></option>
                                    <option value="custom"><?php _e('🔧 Report Personalizzato', 'your-hidden-trip'); ?></option>
                                </select>
                            </div>
                            
                            <div class="filter-item">
                                <label for="date_from"><?php _e('Da', 'your-hidden-trip'); ?></label>
                                <input type="date" id="date_from" class="regular-text" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                            </div>
                            
                            <div class="filter-item">
                                <label for="date_to"><?php _e('A', 'your-hidden-trip'); ?></label>
                                <input type="date" id="date_to" class="regular-text" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        
                        <div class="filter-row">
                            <div class="filter-item">
                                <label for="groupby"><?php _e('Raggruppa per', 'your-hidden-trip'); ?></label>
                                <select id="groupby" class="regular-text">
                                    <option value="day"><?php _e('Giorno', 'your-hidden-trip'); ?></option>
                                    <option value="week"><?php _e('Settimana', 'your-hidden-trip'); ?></option>
                                    <option value="month" selected><?php _e('Mese', 'your-hidden-trip'); ?></option>
                                    <option value="quarter"><?php _e('Trimestre', 'your-hidden-trip'); ?></option>
                                    <option value="year"><?php _e('Anno', 'your-hidden-trip'); ?></option>
                                </select>
                            </div>
                            
                            <div class="filter-item">
                                <label for="status_filter"><?php _e('Stato', 'your-hidden-trip'); ?></label>
                                <select id="status_filter" class="regular-text">
                                    <option value="all"><?php _e('Tutti', 'your-hidden-trip'); ?></option>
                                    <option value="confirmed"><?php _e('Confermato', 'your-hidden-trip'); ?></option>
                                    <option value="pending"><?php _e('In Attesa', 'your-hidden-trip'); ?></option>
                                    <option value="cancelled"><?php _e('Cancellato', 'your-hidden-trip'); ?></option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="action-buttons">
                            <button id="generate_report" class="button button-primary">
                                <span class="dashicons dashicons-chart-line"></span>
                                <?php _e('Genera Report', 'your-hidden-trip'); ?>
                            </button>
                            <button id="export_report" class="button button-secondary" disabled>
                                <span class="dashicons dashicons-download"></span>
                                <?php _e('Esporta', 'your-hidden-trip'); ?>
                            </button>
                            <button id="schedule_report" class="button">
                                <span class="dashicons dashicons-clock"></span>
                                <?php _e('Programma', 'your-hidden-trip'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="yht-report-content">
                    <div id="report_loading" class="report-loading" style="display: none;">
                        <div class="loading-spinner"></div>
                        <p><?php _e('Generazione report in corso...', 'your-hidden-trip'); ?></p>
                    </div>
                    
                    <div id="report_results" class="report-results" style="display: none;">
                        <div class="report-header">
                            <h3 id="report_title"></h3>
                            <div class="report-meta">
                                <span id="report_period"></span>
                                <span id="report_generated"></span>
                            </div>
                        </div>
                        
                        <div class="report-summary">
                            <div class="summary-cards">
                                <div class="summary-card">
                                    <div class="card-icon">📊</div>
                                    <div class="card-content">
                                        <h4><?php _e('Totale Record', 'your-hidden-trip'); ?></h4>
                                        <span id="total_records">0</span>
                                    </div>
                                </div>
                                <div class="summary-card">
                                    <div class="card-icon">💰</div>
                                    <div class="card-content">
                                        <h4><?php _e('Valore Totale', 'your-hidden-trip'); ?></h4>
                                        <span id="total_value">€0</span>
                                    </div>
                                </div>
                                <div class="summary-card">
                                    <div class="card-icon">📈</div>
                                    <div class="card-content">
                                        <h4><?php _e('Crescita', 'your-hidden-trip'); ?></h4>
                                        <span id="growth_rate">0%</span>
                                    </div>
                                </div>
                                <div class="summary-card">
                                    <div class="card-icon">⭐</div>
                                    <div class="card-content">
                                        <h4><?php _e('Media', 'your-hidden-trip'); ?></h4>
                                        <span id="average_value">€0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="report-charts">
                            <div class="chart-container">
                                <canvas id="main_chart"></canvas>
                            </div>
                            <div class="chart-container">
                                <canvas id="secondary_chart"></canvas>
                            </div>
                        </div>
                        
                        <div class="report-data">
                            <div class="data-table-wrapper">
                                <table id="data_table" class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr id="table_headers"></tr>
                                    </thead>
                                    <tbody id="table_body"></tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="report-insights">
                            <h4><?php _e('💡 Insights e Raccomandazioni', 'your-hidden-trip'); ?></h4>
                            <div id="insights_content"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Schedule Report Modal -->
            <div id="schedule_modal" class="yht-modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3><?php _e('Programma Report Automatico', 'your-hidden-trip'); ?></h3>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="schedule_frequency"><?php _e('Frequenza', 'your-hidden-trip'); ?></label>
                            <select id="schedule_frequency" class="regular-text">
                                <option value="daily"><?php _e('Giornaliera', 'your-hidden-trip'); ?></option>
                                <option value="weekly" selected><?php _e('Settimanale', 'your-hidden-trip'); ?></option>
                                <option value="monthly"><?php _e('Mensile', 'your-hidden-trip'); ?></option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="schedule_email"><?php _e('Invia a', 'your-hidden-trip'); ?></label>
                            <input type="email" id="schedule_email" class="regular-text" value="<?php echo get_option('admin_email'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="schedule_format"><?php _e('Formato', 'your-hidden-trip'); ?></label>
                            <select id="schedule_format" class="regular-text">
                                <option value="pdf"><?php _e('PDF', 'your-hidden-trip'); ?></option>
                                <option value="excel"><?php _e('Excel', 'your-hidden-trip'); ?></option>
                                <option value="csv"><?php _e('CSV', 'your-hidden-trip'); ?></option>
                            </select>
                        </div>
                        
                        <div class="modal-actions">
                            <button id="save_schedule" class="button button-primary">
                                <?php _e('Salva Programmazione', 'your-hidden-trip'); ?>
                            </button>
                            <button class="button modal-close">
                                <?php _e('Annulla', 'your-hidden-trip'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Export Options Modal -->
            <div id="export_modal" class="yht-modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3><?php _e('Opzioni Esportazione', 'your-hidden-trip'); ?></h3>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="export-formats">
                            <h4><?php _e('Scegli Formato', 'your-hidden-trip'); ?></h4>
                            <div class="format-options">
                                <label class="format-option">
                                    <input type="radio" name="export_format" value="pdf" checked>
                                    <div class="format-card">
                                        <div class="format-icon">📄</div>
                                        <div class="format-name">PDF</div>
                                        <div class="format-desc"><?php _e('Report formattato con grafici', 'your-hidden-trip'); ?></div>
                                    </div>
                                </label>
                                <label class="format-option">
                                    <input type="radio" name="export_format" value="excel">
                                    <div class="format-card">
                                        <div class="format-icon">📊</div>
                                        <div class="format-name">Excel</div>
                                        <div class="format-desc"><?php _e('Foglio di calcolo con dati', 'your-hidden-trip'); ?></div>
                                    </div>
                                </label>
                                <label class="format-option">
                                    <input type="radio" name="export_format" value="csv">
                                    <div class="format-card">
                                        <div class="format-icon">📋</div>
                                        <div class="format-name">CSV</div>
                                        <div class="format-desc"><?php _e('Dati grezzi separati da virgola', 'your-hidden-trip'); ?></div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <div class="export-options">
                            <h4><?php _e('Opzioni', 'your-hidden-trip'); ?></h4>
                            <label>
                                <input type="checkbox" id="include_charts" checked>
                                <?php _e('Includi grafici', 'your-hidden-trip'); ?>
                            </label>
                            <label>
                                <input type="checkbox" id="include_insights" checked>
                                <?php _e('Includi insights', 'your-hidden-trip'); ?>
                            </label>
                            <label>
                                <input type="checkbox" id="include_summary">
                                <?php _e('Includi riassunto esecutivo', 'your-hidden-trip'); ?>
                            </label>
                        </div>
                        
                        <div class="modal-actions">
                            <button id="start_export" class="button button-primary">
                                <?php _e('Esporta Report', 'your-hidden-trip'); ?>
                            </button>
                            <button class="button modal-close">
                                <?php _e('Annulla', 'your-hidden-trip'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .yht-advanced-reports-page {
                max-width: 1400px;
            }

            .yht-header {
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 1px solid #e1e1e1;
            }

            .yht-reports-container {
                display: grid;
                grid-template-columns: 350px 1fr;
                gap: 30px;
            }

            .yht-report-controls {
                background: white;
                border: 1px solid #e1e1e1;
                border-radius: 8px;
                padding: 25px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                height: fit-content;
            }

            .yht-report-controls h3 {
                margin: 0 0 20px 0;
                font-size: 18px;
                color: #333;
            }

            .filter-row {
                display: flex;
                gap: 15px;
                margin-bottom: 20px;
                flex-wrap: wrap;
            }

            .filter-item {
                flex: 1;
                min-width: 120px;
            }

            .filter-item label {
                display: block;
                font-weight: 600;
                margin-bottom: 5px;
                color: #555;
            }

            .filter-item input,
            .filter-item select {
                width: 100%;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }

            .action-buttons {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
                margin-top: 25px;
            }

            .action-buttons .button {
                display: flex;
                align-items: center;
                gap: 5px;
            }

            .yht-report-content {
                background: white;
                border: 1px solid #e1e1e1;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }

            .report-loading {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 60px;
                color: #666;
            }

            .loading-spinner {
                width: 40px;
                height: 40px;
                border: 4px solid #f3f3f3;
                border-top: 4px solid #0073aa;
                border-radius: 50%;
                animation: spin 1s linear infinite;
                margin-bottom: 20px;
            }

            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }

            .report-header {
                padding: 25px 25px 0 25px;
                border-bottom: 1px solid #e1e1e1;
            }

            .report-header h3 {
                margin: 0 0 10px 0;
                font-size: 24px;
                color: #333;
            }

            .report-meta {
                display: flex;
                gap: 20px;
                font-size: 14px;
                color: #666;
                margin-bottom: 20px;
            }

            .report-summary {
                padding: 25px;
            }

            .summary-cards {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
            }

            .summary-card {
                display: flex;
                align-items: center;
                gap: 15px;
                padding: 20px;
                background: #f8f9fa;
                border-radius: 8px;
                border-left: 4px solid #0073aa;
            }

            .card-icon {
                font-size: 24px;
            }

            .card-content h4 {
                margin: 0 0 5px 0;
                font-size: 14px;
                color: #666;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .card-content span {
                font-size: 24px;
                font-weight: bold;
                color: #333;
            }

            .report-charts {
                padding: 25px;
                display: grid;
                grid-template-columns: 2fr 1fr;
                gap: 25px;
            }

            .chart-container {
                position: relative;
                background: #f8f9fa;
                border-radius: 8px;
                padding: 20px;
            }

            .report-data {
                padding: 25px;
            }

            .data-table-wrapper {
                overflow-x: auto;
            }

            .report-insights {
                padding: 25px;
                background: #f8f9fa;
                border-top: 1px solid #e1e1e1;
            }

            .report-insights h4 {
                margin: 0 0 15px 0;
                color: #333;
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
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .modal-header h3 {
                margin: 0;
                color: #333;
            }

            .modal-close {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: #666;
            }

            .modal-body {
                padding: 25px;
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
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }

            .export-formats h4 {
                margin: 0 0 15px 0;
                color: #333;
            }

            .format-options {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 15px;
                margin-bottom: 25px;
            }

            .format-option {
                cursor: pointer;
            }

            .format-option input {
                display: none;
            }

            .format-card {
                text-align: center;
                padding: 20px;
                border: 2px solid #e1e1e1;
                border-radius: 8px;
                transition: all 0.3s;
            }

            .format-option input:checked + .format-card {
                border-color: #0073aa;
                background: #f0f8ff;
            }

            .format-icon {
                font-size: 32px;
                margin-bottom: 10px;
            }

            .format-name {
                font-weight: bold;
                margin-bottom: 5px;
                color: #333;
            }

            .format-desc {
                font-size: 12px;
                color: #666;
            }

            .export-options label {
                display: block;
                margin-bottom: 10px;
                cursor: pointer;
            }

            .export-options input {
                margin-right: 8px;
                width: auto;
            }

            .modal-actions {
                display: flex;
                gap: 10px;
                justify-content: flex-end;
                margin-top: 25px;
            }

            @media (max-width: 1200px) {
                .yht-reports-container {
                    grid-template-columns: 1fr;
                    gap: 20px;
                }
                
                .report-charts {
                    grid-template-columns: 1fr;
                }
                
                .format-options {
                    grid-template-columns: 1fr;
                }
            }
        </style>

        <script>
            jQuery(document).ready(function($) {
                let currentReportData = null;
                let mainChart = null;
                let secondaryChart = null;

                // Generate report
                $('#generate_report').click(function() {
                    var params = {
                        action: 'yht_generate_report',
                        nonce: yhtAdvancedReports.nonce,
                        report_type: $('#report_type').val(),
                        date_from: $('#date_from').val(),
                        date_to: $('#date_to').val(),
                        groupby: $('#groupby').val(),
                        status_filter: $('#status_filter').val()
                    };

                    $('#report_loading').show();
                    $('#report_results').hide();

                    $.post(yhtAdvancedReports.ajaxurl, params, function(response) {
                        $('#report_loading').hide();
                        
                        if (response.success && response.data) {
                            currentReportData = response.data;
                            displayReport(response.data);
                            $('#export_report').prop('disabled', false);
                        } else {
                            alert(response.data?.message || yhtAdvancedReports.strings.error);
                        }
                    });
                });

                // Display report function
                function displayReport(data) {
                    // Update header
                    $('#report_title').text(data.title);
                    $('#report_period').text(data.period);
                    $('#report_generated').text('Generato: ' + new Date().toLocaleString('it-IT'));

                    // Update summary cards
                    $('#total_records').text(data.summary.total_records);
                    $('#total_value').text('€' + data.summary.total_value);
                    $('#growth_rate').text(data.summary.growth_rate + '%');
                    $('#average_value').text('€' + data.summary.average_value);

                    // Create charts
                    createCharts(data.chart_data);

                    // Populate data table
                    populateDataTable(data.table_data);

                    // Show insights
                    $('#insights_content').html(data.insights);

                    $('#report_results').show();
                }

                // Create charts function
                function createCharts(chartData) {
                    // Destroy existing charts
                    if (mainChart) mainChart.destroy();
                    if (secondaryChart) secondaryChart.destroy();

                    // Main chart (line chart)
                    const mainCtx = document.getElementById('main_chart').getContext('2d');
                    mainChart = new Chart(mainCtx, {
                        type: 'line',
                        data: chartData.main,
                        options: {
                            responsive: true,
                            plugins: {
                                title: {
                                    display: true,
                                    text: chartData.main.title
                                }
                            }
                        }
                    });

                    // Secondary chart (doughnut chart)
                    const secondaryCtx = document.getElementById('secondary_chart').getContext('2d');
                    secondaryChart = new Chart(secondaryCtx, {
                        type: 'doughnut',
                        data: chartData.secondary,
                        options: {
                            responsive: true,
                            plugins: {
                                title: {
                                    display: true,
                                    text: chartData.secondary.title
                                }
                            }
                        }
                    });
                }

                // Populate data table function
                function populateDataTable(tableData) {
                    // Headers
                    let headersHtml = '';
                    tableData.headers.forEach(header => {
                        headersHtml += '<th>' + header + '</th>';
                    });
                    $('#table_headers').html(headersHtml);

                    // Rows
                    let rowsHtml = '';
                    tableData.rows.forEach(row => {
                        rowsHtml += '<tr>';
                        row.forEach(cell => {
                            rowsHtml += '<td>' + cell + '</td>';
                        });
                        rowsHtml += '</tr>';
                    });
                    $('#table_body').html(rowsHtml);
                }

                // Export report
                $('#export_report').click(function() {
                    if (!currentReportData) return;
                    $('#export_modal').show();
                });

                $('#start_export').click(function() {
                    var format = $('input[name="export_format"]:checked').val();
                    var params = {
                        action: 'yht_export_report',
                        nonce: yhtAdvancedReports.nonce,
                        report_data: JSON.stringify(currentReportData),
                        format: format,
                        include_charts: $('#include_charts').is(':checked'),
                        include_insights: $('#include_insights').is(':checked'),
                        include_summary: $('#include_summary').is(':checked')
                    };

                    $(this).text(yhtAdvancedReports.strings.exporting).prop('disabled', true);

                    $.post(yhtAdvancedReports.ajaxurl, params, function(response) {
                        $('#start_export').text('Esporta Report').prop('disabled', false);
                        
                        if (response.success && response.data.download_url) {
                            // Create download link
                            var link = document.createElement('a');
                            link.href = response.data.download_url;
                            link.download = response.data.filename;
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                            
                            $('#export_modal').hide();
                        } else {
                            alert(response.data?.message || yhtAdvancedReports.strings.error);
                        }
                    });
                });

                // Schedule report
                $('#schedule_report').click(function() {
                    $('#schedule_modal').show();
                });

                $('#save_schedule').click(function() {
                    var params = {
                        action: 'yht_schedule_report',
                        nonce: yhtAdvancedReports.nonce,
                        report_type: $('#report_type').val(),
                        frequency: $('#schedule_frequency').val(),
                        email: $('#schedule_email').val(),
                        format: $('#schedule_format').val()
                    };

                    $.post(yhtAdvancedReports.ajaxurl, params, function(response) {
                        if (response.success) {
                            alert(yhtAdvancedReports.strings.scheduled);
                            $('#schedule_modal').hide();
                        } else {
                            alert(response.data?.message || yhtAdvancedReports.strings.error);
                        }
                    });
                });

                // Modal controls
                $('.modal-close').click(function() {
                    $(this).closest('.yht-modal').hide();
                });

                $('.yht-modal').click(function(e) {
                    if (e.target === this) {
                        $(this).hide();
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * AJAX: Generate report
     */
    public function ajax_generate_report() {
        check_ajax_referer('yht_advanced_reports_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permessi insufficienti.', 'your-hidden-trip'));
        }

        $report_type = sanitize_key($_POST['report_type']);
        $date_from = sanitize_text_field($_POST['date_from']);
        $date_to = sanitize_text_field($_POST['date_to']);
        $groupby = sanitize_key($_POST['groupby']);
        $status_filter = sanitize_key($_POST['status_filter']);

        $report_data = $this->generate_report_data($report_type, $date_from, $date_to, $groupby, $status_filter);

        wp_send_json_success($report_data);
    }

    /**
     * Generate report data
     */
    private function generate_report_data($report_type, $date_from, $date_to, $groupby, $status_filter) {
        global $wpdb;

        // This is a simplified example - in a real implementation, you'd query actual booking data
        $data = array(
            'title' => $this->get_report_title($report_type),
            'period' => date('d/m/Y', strtotime($date_from)) . ' - ' . date('d/m/Y', strtotime($date_to)),
            'summary' => array(
                'total_records' => rand(50, 500),
                'total_value' => number_format(rand(5000, 50000), 2),
                'growth_rate' => rand(-20, 50),
                'average_value' => number_format(rand(100, 500), 2)
            ),
            'chart_data' => $this->generate_chart_data($report_type),
            'table_data' => $this->generate_table_data($report_type),
            'insights' => $this->generate_insights($report_type)
        );

        return $data;
    }

    /**
     * Get report title
     */
    private function get_report_title($report_type) {
        $titles = array(
            'bookings' => __('Analisi Prenotazioni', 'your-hidden-trip'),
            'revenue' => __('Analisi Ricavi', 'your-hidden-trip'),
            'customers' => __('Analisi Clienti', 'your-hidden-trip'),
            'locations' => __('Analisi Destinazioni', 'your-hidden-trip'),
            'performance' => __('Performance Sistema', 'your-hidden-trip'),
            'custom' => __('Report Personalizzato', 'your-hidden-trip')
        );

        return isset($titles[$report_type]) ? $titles[$report_type] : __('Report Avanzato', 'your-hidden-trip');
    }

    /**
     * Generate chart data
     */
    private function generate_chart_data($report_type) {
        // Generate sample chart data
        $labels = array('Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu');
        $data1 = array(rand(10, 100), rand(10, 100), rand(10, 100), rand(10, 100), rand(10, 100), rand(10, 100));
        $data2 = array(rand(10, 100), rand(10, 100), rand(10, 100), rand(10, 100), rand(10, 100), rand(10, 100));

        return array(
            'main' => array(
                'title' => 'Trend nel Tempo',
                'labels' => $labels,
                'datasets' => array(
                    array(
                        'label' => 'Serie 1',
                        'data' => $data1,
                        'borderColor' => '#0073aa',
                        'backgroundColor' => 'rgba(0, 115, 170, 0.1)'
                    ),
                    array(
                        'label' => 'Serie 2',
                        'data' => $data2,
                        'borderColor' => '#dc3545',
                        'backgroundColor' => 'rgba(220, 53, 69, 0.1)'
                    )
                )
            ),
            'secondary' => array(
                'title' => 'Distribuzione',
                'labels' => array('Categoria A', 'Categoria B', 'Categoria C', 'Altro'),
                'datasets' => array(
                    array(
                        'data' => array(rand(20, 50), rand(20, 50), rand(20, 50), rand(10, 30)),
                        'backgroundColor' => array('#0073aa', '#28a745', '#ffc107', '#dc3545')
                    )
                )
            )
        );
    }

    /**
     * Generate table data
     */
    private function generate_table_data($report_type) {
        return array(
            'headers' => array('Data', 'Descrizione', 'Valore', 'Stato'),
            'rows' => array(
                array('22/01/2025', 'Prenotazione #001', '€150.00', 'Confermato'),
                array('21/01/2025', 'Prenotazione #002', '€250.00', 'Confermato'),
                array('20/01/2025', 'Prenotazione #003', '€180.00', 'In attesa'),
                array('19/01/2025', 'Prenotazione #004', '€320.00', 'Confermato'),
                array('18/01/2025', 'Prenotazione #005', '€200.00', 'Cancellato')
            )
        );
    }

    /**
     * Generate insights
     */
    private function generate_insights($report_type) {
        $insights = array(
            '<div class="insight-item">📈 <strong>Crescita positiva:</strong> I ricavi sono aumentati del 25% rispetto al periodo precedente.</div>',
            '<div class="insight-item">🎯 <strong>Picco di attività:</strong> Il maggior numero di prenotazioni si concentra nel weekend.</div>',
            '<div class="insight-item">💡 <strong>Raccomandazione:</strong> Considera di aumentare l\'offerta per i giorni di maggiore richiesta.</div>',
            '<div class="insight-item">⚠️ <strong>Attenzione:</strong> Il tasso di cancellazione è leggermente aumentato nell\'ultimo mese.</div>'
        );

        return '<div class="insights-list">' . implode('', $insights) . '</div>';
    }

    /**
     * AJAX: Export report
     */
    public function ajax_export_report() {
        check_ajax_referer('yht_advanced_reports_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permessi insufficienti.', 'your-hidden-trip'));
        }

        $report_data = json_decode(stripslashes($_POST['report_data']), true);
        $format = sanitize_key($_POST['format']);
        $include_charts = isset($_POST['include_charts']);
        $include_insights = isset($_POST['include_insights']);
        $include_summary = isset($_POST['include_summary']);

        $export_result = $this->export_report($report_data, $format, $include_charts, $include_insights, $include_summary);

        if ($export_result) {
            wp_send_json_success($export_result);
        } else {
            wp_send_json_error(array('message' => __('Errore durante l\'esportazione.', 'your-hidden-trip')));
        }
    }

    /**
     * Export report
     */
    private function export_report($data, $format, $include_charts, $include_insights, $include_summary) {
        $upload_dir = wp_upload_dir();
        $reports_dir = $upload_dir['basedir'] . '/yht-reports/';
        
        if (!file_exists($reports_dir)) {
            wp_mkdir_p($reports_dir);
        }

        $filename = 'yht-report-' . date('Y-m-d-H-i-s') . '.' . $format;
        $filepath = $reports_dir . $filename;

        switch ($format) {
            case 'csv':
                $this->export_csv($data, $filepath);
                break;
            case 'excel':
                $this->export_excel($data, $filepath);
                break;
            case 'pdf':
                $this->export_pdf($data, $filepath, $include_charts, $include_insights, $include_summary);
                break;
        }

        if (file_exists($filepath)) {
            return array(
                'download_url' => $upload_dir['baseurl'] . '/yht-reports/' . $filename,
                'filename' => $filename
            );
        }

        return false;
    }

    /**
     * Export CSV
     */
    private function export_csv($data, $filepath) {
        $file = fopen($filepath, 'w');
        
        // Write headers
        fputcsv($file, $data['table_data']['headers']);
        
        // Write rows
        foreach ($data['table_data']['rows'] as $row) {
            fputcsv($file, $row);
        }
        
        fclose($file);
    }

    /**
     * Export Excel (simplified CSV with .xlsx extension)
     */
    private function export_excel($data, $filepath) {
        // For a real implementation, you'd use a library like PhpSpreadsheet
        $this->export_csv($data, $filepath);
    }

    /**
     * Export PDF (simplified HTML to PDF)
     */
    private function export_pdf($data, $filepath, $include_charts, $include_insights, $include_summary) {
        $html = '<h1>' . $data['title'] . '</h1>';
        $html .= '<p>Periodo: ' . $data['period'] . '</p>';
        
        if ($include_summary) {
            $html .= '<h2>Riassunto</h2>';
            $html .= '<p>Totale Record: ' . $data['summary']['total_records'] . '</p>';
            $html .= '<p>Valore Totale: €' . $data['summary']['total_value'] . '</p>';
        }
        
        $html .= '<h2>Dati</h2>';
        $html .= '<table border="1" cellpadding="5">';
        $html .= '<tr>';
        foreach ($data['table_data']['headers'] as $header) {
            $html .= '<th>' . $header . '</th>';
        }
        $html .= '</tr>';
        
        foreach ($data['table_data']['rows'] as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . $cell . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</table>';
        
        if ($include_insights) {
            $html .= '<h2>Insights</h2>';
            $html .= $data['insights'];
        }
        
        // For a real implementation, you'd use a library like TCPDF or DOMPDF
        file_put_contents($filepath, $html);
    }

    /**
     * AJAX: Schedule report
     */
    public function ajax_schedule_report() {
        check_ajax_referer('yht_advanced_reports_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permessi insufficienti.', 'your-hidden-trip'));
        }

        $report_type = sanitize_key($_POST['report_type']);
        $frequency = sanitize_key($_POST['frequency']);
        $email = sanitize_email($_POST['email']);
        $format = sanitize_key($_POST['format']);

        $scheduled_reports = get_option('yht_scheduled_reports', array());
        $scheduled_reports[] = array(
            'report_type' => $report_type,
            'frequency' => $frequency,
            'email' => $email,
            'format' => $format,
            'created' => current_time('mysql')
        );

        update_option('yht_scheduled_reports', $scheduled_reports);

        wp_send_json_success(array(
            'message' => __('Report programmato con successo!', 'your-hidden-trip')
        ));
    }

    /**
     * Send scheduled reports
     */
    public function send_scheduled_reports() {
        $scheduled_reports = get_option('yht_scheduled_reports', array());
        
        foreach ($scheduled_reports as $report) {
            // Check if it's time to send based on frequency
            // This is a simplified check - in a real implementation you'd have more sophisticated scheduling
            
            $report_data = $this->generate_report_data(
                $report['report_type'],
                date('Y-m-d', strtotime('-30 days')),
                date('Y-m-d'),
                'day',
                'all'
            );

            $export_result = $this->export_report($report_data, $report['format'], true, true, true);
            
            if ($export_result) {
                $subject = 'Report automatico: ' . $report_data['title'];
                $message = 'Il tuo report automatico è pronto. Puoi scaricarlo dal link allegato.';
                
                // In a real implementation, you'd attach the file to the email
                wp_mail($report['email'], $subject, $message);
            }
        }
    }
}