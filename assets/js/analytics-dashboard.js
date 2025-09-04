/**
 * Advanced Analytics Dashboard JavaScript
 * Enhanced Tourism Management System
 */

(function($) {
    'use strict';
    
    class YHTAnalyticsDashboard {
        constructor() {
            this.charts = {};
            this.currentPeriod = '30';
            this.currentView = 'overview';
            this.refreshInterval = null;
            
            this.init();
        }
        
        init() {
            this.bindEvents();
            this.initializeCharts();
            this.loadInitialData();
            this.startAutoRefresh();
        }
        
        bindEvents() {
            // Period and view changes
            $('#analytics-period').on('change', () => this.handlePeriodChange());
            $('#analytics-view').on('change', () => this.handleViewChange());
            
            // Refresh button
            $('#refresh-analytics').on('click', () => this.refreshData());
            
            // Export button
            $('#export-report').on('click', () => this.exportReport());
            
            // Tab switching for entity performance
            $('.tab-button').on('click', (e) => this.switchTab(e));
            
            // Section toggles
            $('.section-toggle').on('click', (e) => this.toggleSection(e));
            
            // Real-time monitoring
            this.initializeRealTimeMonitoring();
        }
        
        initializeCharts() {
            // Revenue trend chart
            const revenueTrendCtx = document.getElementById('revenue-trend-chart');
            if (revenueTrendCtx) {
                this.charts.revenueTrend = new Chart(revenueTrendCtx, {
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Revenue Giornaliero',
                            data: [],
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: this.getChartOptions('revenue')
                });
            }
            
            // Revenue by category
            const revenueCategoryCtx = document.getElementById('revenue-category-chart');
            if (revenueCategoryCtx) {
                this.charts.revenueCategory = new Chart(revenueCategoryCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Luoghi', 'Alloggi', 'Servizi'],
                        datasets: [{
                            data: [],
                            backgroundColor: ['#667eea', '#764ba2', '#f093fb'],
                            borderWidth: 0
                        }]
                    },
                    options: this.getChartOptions('doughnut')
                });
            }
            
            // Entity performance charts
            this.initializeEntityCharts();
            
            // Multiple options analytics
            this.initializeOptionsCharts();
            
            // Client behavior charts
            this.initializeClientBehaviorCharts();
        }
        
        initializeEntityCharts() {
            const entityTypes = ['luoghi', 'alloggi', 'servizi'];
            
            entityTypes.forEach(type => {
                const ctx = document.getElementById(`${type}-performance-chart`);
                if (ctx) {
                    this.charts[`${type}Performance`] = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: [],
                            datasets: [{
                                label: 'Revenue',
                                data: [],
                                backgroundColor: '#667eea',
                                borderColor: '#5a6fd8',
                                borderWidth: 1
                            }, {
                                label: 'Prenotazioni',
                                data: [],
                                backgroundColor: '#764ba2',
                                borderColor: '#6a4190',
                                borderWidth: 1,
                                yAxisID: 'y1'
                            }]
                        },
                        options: this.getChartOptions('bar')
                    });
                }
            });
        }
        
        initializeOptionsCharts() {
            // Overbooking prevention chart
            const overbookingCtx = document.getElementById('overbooking-prevention-chart');
            if (overbookingCtx) {
                this.charts.overbookingPrevention = new Chart(overbookingCtx, {
                    type: 'radar',
                    data: {
                        labels: ['Flessibilità', 'Copertura', 'Backup', 'Ridondanza', 'Protezione'],
                        datasets: [{
                            label: 'Score Attuale',
                            data: [],
                            backgroundColor: 'rgba(102, 126, 234, 0.2)',
                            borderColor: '#667eea',
                            borderWidth: 2,
                            pointBackgroundColor: '#667eea'
                        }]
                    },
                    options: this.getChartOptions('radar')
                });
            }
            
            // Pricing optimization chart
            const pricingCtx = document.getElementById('pricing-optimization-chart');
            if (pricingCtx) {
                this.charts.pricingOptimization = new Chart(pricingCtx, {
                    type: 'scatter',
                    data: {
                        datasets: [{
                            label: 'Tour Performance',
                            data: [],
                            backgroundColor: '#667eea',
                            borderColor: '#5a6fd8'
                        }]
                    },
                    options: this.getChartOptions('scatter')
                });
            }
        }
        
        initializeClientBehaviorCharts() {
            // Conversion funnel
            const funnelCtx = document.getElementById('conversion-funnel-chart');
            if (funnelCtx) {
                this.charts.conversionFunnel = new Chart(funnelCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Visite', 'Selezioni', 'Prenotazioni', 'Conferme'],
                        datasets: [{
                            label: 'Clienti',
                            data: [],
                            backgroundColor: [
                                '#667eea',
                                '#764ba2',
                                '#f093fb',
                                '#28a745'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: this.getChartOptions('funnel')
                });
            }
            
            // Satisfaction trends
            const satisfactionCtx = document.getElementById('satisfaction-trends-chart');
            if (satisfactionCtx) {
                this.charts.satisfactionTrends = new Chart(satisfactionCtx, {
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Soddisfazione Media',
                            data: [],
                            borderColor: '#28a745',
                            backgroundColor: 'rgba(40, 167, 69, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: this.getChartOptions('satisfaction')
                });
            }
            
            // Seasonal demand
            const seasonalCtx = document.getElementById('seasonal-demand-chart');
            if (seasonalCtx) {
                this.charts.seasonalDemand = new Chart(seasonalCtx, {
                    type: 'line',
                    data: {
                        labels: ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'],
                        datasets: [{
                            label: 'Domanda 2024',
                            data: [],
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        }, {
                            label: 'Domanda 2023',
                            data: [],
                            borderColor: '#cccccc',
                            backgroundColor: 'rgba(204, 204, 204, 0.1)',
                            borderWidth: 1,
                            borderDash: [5, 5],
                            fill: false,
                            tension: 0.4
                        }]
                    },
                    options: this.getChartOptions('seasonal')
                });
            }
        }
        
        getChartOptions(type) {
            const baseOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: '#667eea',
                        borderWidth: 1,
                        cornerRadius: 6
                    }
                }
            };
            
            switch (type) {
                case 'revenue':
                    return {
                        ...baseOptions,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '€' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    };
                    
                case 'bar':
                    return {
                        ...baseOptions,
                        scales: {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '€' + value.toLocaleString();
                                    }
                                }
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                beginAtZero: true,
                                grid: {
                                    drawOnChartArea: false,
                                }
                            }
                        }
                    };
                    
                case 'radar':
                    return {
                        ...baseOptions,
                        scales: {
                            r: {
                                beginAtZero: true,
                                max: 10,
                                ticks: {
                                    stepSize: 2
                                }
                            }
                        }
                    };
                    
                case 'scatter':
                    return {
                        ...baseOptions,
                        scales: {
                            x: {
                                title: {
                                    display: true,
                                    text: 'Prezzo Medio (€)'
                                }
                            },
                            y: {
                                title: {
                                    display: true,
                                    text: 'Tasso Conversione (%)'
                                }
                            }
                        }
                    };
                    
                case 'doughnut':
                    return {
                        ...baseOptions,
                        plugins: {
                            ...baseOptions.plugins,
                            tooltip: {
                                ...baseOptions.plugins.tooltip,
                                callbacks: {
                                    label: function(context) {
                                        return context.label + ': €' + context.parsed.toLocaleString();
                                    }
                                }
                            }
                        }
                    };
                    
                default:
                    return baseOptions;
            }
        }
        
        loadInitialData() {
            this.showLoading();
            this.fetchAnalyticsData();
        }
        
        fetchAnalyticsData() {
            $.ajax({
                url: yht_analytics_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'yht_get_analytics_data',
                    period: this.currentPeriod,
                    view: this.currentView,
                    nonce: yht_analytics_ajax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateDashboard(response.data);
                    } else {
                        console.error('Error loading analytics:', response.data);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Ajax error:', error);
                },
                complete: () => {
                    this.hideLoading();
                }
            });
        }
        
        updateDashboard(data) {
            // Update KPIs
            this.updateKPIs(data.kpis);
            
            // Update charts
            this.updateCharts(data);
            
            // Update insights
            this.updateInsights(data);
            
            // Update flexibility analysis
            this.updateFlexibilityAnalysis(data.multiple_options);
            
            // Update optimization suggestions
            this.updateOptimizationSuggestions(data.optimization_suggestions);
            
            // Update real-time data
            this.updateRealTimeData(data);
        }
        
        updateKPIs(kpis) {
            $('#total-revenue').text('€' + this.formatNumber(kpis.total_revenue));
            $('#total-bookings').text(kpis.total_bookings);
            $('#conversion-rate').text(kpis.conversion_rate + '%');
            $('#avg-satisfaction').text(kpis.avg_satisfaction.toFixed(1));
            $('#flexibility-index').text(kpis.flexibility_index.toFixed(1));
            $('#protection-score').text(kpis.protection_score.toFixed(1));
            
            // Add change indicators (simulated)
            this.updateChangeIndicators();
        }
        
        updateChangeIndicators() {
            $('.kpi-change').each(function() {
                const change = (Math.random() - 0.5) * 20; // Random change for demo
                const isPositive = change >= 0;
                
                $(this).text((isPositive ? '+' : '') + change.toFixed(1) + '%')
                       .removeClass('positive negative')
                       .addClass(isPositive ? 'positive' : 'negative');
            });
        }
        
        updateCharts(data) {
            // Revenue trend
            if (this.charts.revenueTrend && data.revenue.trends) {
                const labels = data.revenue.trends.map(t => new Date(t.date).toLocaleDateString());
                const revenues = data.revenue.trends.map(t => t.revenue);
                
                this.charts.revenueTrend.data.labels = labels;
                this.charts.revenueTrend.data.datasets[0].data = revenues;
                this.charts.revenueTrend.update('none');
            }
            
            // Revenue by category
            if (this.charts.revenueCategory && data.revenue.categories) {
                this.charts.revenueCategory.data.datasets[0].data = [
                    data.revenue.categories.luoghi,
                    data.revenue.categories.alloggi,
                    data.revenue.categories.servizi
                ];
                this.charts.revenueCategory.update('none');
            }
            
            // Entity performance charts
            this.updateEntityPerformanceCharts(data.entities);
            
            // Multiple options charts
            this.updateMultipleOptionsCharts(data.multiple_options);
            
            // Client behavior charts
            this.updateClientBehaviorCharts(data.client_behavior);
        }
        
        updateEntityPerformanceCharts(entities) {
            Object.keys(entities).forEach(type => {
                const chart = this.charts[`${type}Performance`];
                if (chart && entities[type]) {
                    const items = entities[type].slice(0, 10); // Top 10
                    
                    chart.data.labels = items.map(item => item.name);
                    chart.data.datasets[0].data = items.map(item => item.revenue);
                    chart.data.datasets[1].data = items.map(item => item.bookings);
                    chart.update('none');
                    
                    // Update top performers list
                    this.updateTopPerformersList(type, items);
                }
            });
        }
        
        updateTopPerformersList(type, items) {
            const $container = $(`#top-${type}`);
            $container.empty();
            
            items.slice(0, 5).forEach((item, index) => {
                const $item = $(`
                    <div class="performer-item">
                        <span class="performer-rank">#${index + 1}</span>
                        <div class="performer-details">
                            <div class="performer-name">${item.name}</div>
                            <div class="performer-stats">
                                €${this.formatNumber(item.revenue)} • ${item.bookings} prenotazioni • ⭐${item.rating.toFixed(1)}
                            </div>
                        </div>
                    </div>
                `);
                $container.append($item);
            });
        }
        
        updateMultipleOptionsCharts(optionsData) {
            // Update options statistics
            $('#tours-with-options').text(optionsData.tours_with_options);
            $('#avg-options-per-tour').text(optionsData.avg_options_per_tour.toFixed(1));
            $('#options-coverage').text(optionsData.coverage_percentage + '%');
            $('#overbooking-prevented').text(optionsData.overbooking_prevented);
            
            // Update flexibility bars
            if (optionsData.flexibility_distribution) {
                Object.keys(optionsData.flexibility_distribution).forEach(level => {
                    const count = optionsData.flexibility_distribution[level];
                    const total = Object.values(optionsData.flexibility_distribution).reduce((a, b) => a + b, 0);
                    const percentage = total > 0 ? (count / total) * 100 : 0;
                    
                    $(`.flexibility-level.${level} .level-fill`).css('width', percentage + '%');
                    $(`.flexibility-level.${level} .level-count`).text(`${count} tour`);
                });
            }
            
            // Update overbooking prevention radar
            if (this.charts.overbookingPrevention) {
                this.charts.overbookingPrevention.data.datasets[0].data = [
                    optionsData.avg_flexibility_score || 0,
                    optionsData.coverage_percentage / 10 || 0,
                    optionsData.avg_protection_score || 0,
                    (optionsData.avg_options_per_tour || 0) * 2,
                    optionsData.avg_protection_score || 0
                ];
                this.charts.overbookingPrevention.update('none');
            }
        }
        
        updateClientBehaviorCharts(behaviorData) {
            // Update pattern values
            $('#luxury-preference').text(behaviorData.luxury_preference + '%');
            $('#multiple-selection-rate').text(behaviorData.multiple_selection_rate + '%');
            $('#avg-decision-time').text(behaviorData.avg_decision_time + ' min');
            $('#abandonment-rate').text(behaviorData.abandonment_rate + '%');
            
            // Update conversion funnel
            if (this.charts.conversionFunnel) {
                this.charts.conversionFunnel.data.datasets[0].data = [
                    100, // Baseline visits
                    behaviorData.multiple_selection_rate || 70,
                    behaviorData.conversion_rate || 25,
                    (behaviorData.conversion_rate || 25) * 0.8 // Confirmed bookings
                ];
                this.charts.conversionFunnel.update('none');
            }
        }
        
        updateInsights(data) {
            const $container = $('#revenue-insights');
            $container.empty();
            
            // Generate automatic insights
            const insights = this.generateInsights(data);
            
            insights.forEach(insight => {
                const $insight = $(`
                    <div class="insight-item">
                        <span class="insight-icon">${insight.icon}</span>
                        <span class="insight-text">${insight.text}</span>
                    </div>
                `);
                $container.append($insight);
            });
        }
        
        generateInsights(data) {
            const insights = [];
            
            // Revenue insights
            if (data.kpis.total_revenue > 30000) {
                insights.push({
                    icon: '📈',
                    text: 'Revenue sopra la media mensile! Ottima performance.'
                });
            }
            
            // Multiple options insights
            if (data.multiple_options && data.multiple_options.coverage_percentage > 80) {
                insights.push({
                    icon: '🛡️',
                    text: 'Eccellente copertura del sistema opzioni multiple.'
                });
            }
            
            // Conversion insights
            if (data.kpis.conversion_rate > 25) {
                insights.push({
                    icon: '🎯',
                    text: 'Tasso di conversione superiore alla media del settore.'
                });
            }
            
            return insights;
        }
        
        updateFlexibilityAnalysis(optionsData) {
            // This would update the flexibility analysis section
            // Implementation depends on the specific data structure
        }
        
        updateOptimizationSuggestions(suggestions) {
            const $container = $('#optimization-suggestions');
            $container.empty();
            
            if (suggestions && suggestions.length > 0) {
                suggestions.forEach(suggestion => {
                    const $suggestion = $(`
                        <div class="suggestion-item ${suggestion.priority}">
                            <div class="suggestion-header">
                                <span class="suggestion-priority ${suggestion.priority}">${suggestion.priority.toUpperCase()}</span>
                                <h5>${suggestion.title}</h5>
                            </div>
                            <p>${suggestion.description}</p>
                            <div class="suggestion-action">${suggestion.action}</div>
                        </div>
                    `);
                    $container.append($suggestion);
                });
            } else {
                $container.html('<p>Nessun suggerimento di ottimizzazione al momento.</p>');
            }
        }
        
        updateRealTimeData(data) {
            // Update active sessions (simulated)
            $('#active-sessions').text(Math.floor(Math.random() * 15) + 1);
            
            // Update recent activities
            this.updateRecentActivities();
            
            // Update availability status
            $('#luoghi-availability').text('98%');
            $('#alloggi-availability').text('95%');
            $('#servizi-availability').text('97%');
        }
        
        updateRecentActivities() {
            const $container = $('#recent-activities-list');
            const activities = [
                { icon: '👤', text: 'Nuovo cliente ha visitato il portale tour #245', time: '2 min fa' },
                { icon: '✅', text: 'Prenotazione confermata per Tour Cinque Terre', time: '8 min fa' },
                { icon: '📋', text: 'Cliente ha salvato preferenze per Tour Toscana', time: '15 min fa' },
                { icon: '📧', text: 'Email di selezione inviata per Tour Amalfi', time: '23 min fa' },
                { icon: '💰', text: 'Pagamento ricevuto per prenotazione #YHT-001234', time: '31 min fa' }
            ];
            
            $container.empty();
            activities.slice(0, 5).forEach(activity => {
                const $activity = $(`
                    <div class="activity-item">
                        <span class="activity-icon">${activity.icon}</span>
                        <div class="activity-content">
                            <div class="activity-text">${activity.text}</div>
                            <div class="activity-time">${activity.time}</div>
                        </div>
                    </div>
                `);
                $container.append($activity);
            });
        }
        
        initializeRealTimeMonitoring() {
            // Update real-time data every 30 seconds
            setInterval(() => {
                this.updateRealTimeData({});
            }, 30000);
        }
        
        handlePeriodChange() {
            this.currentPeriod = $('#analytics-period').val();
            this.refreshData();
        }
        
        handleViewChange() {
            this.currentView = $('#analytics-view').val();
            this.refreshData();
        }
        
        refreshData() {
            this.showLoading();
            this.fetchAnalyticsData();
        }
        
        switchTab(e) {
            e.preventDefault();
            
            const $button = $(e.target);
            const tabId = $button.data('tab');
            
            // Update active states
            $('.tab-button').removeClass('active');
            $button.addClass('active');
            
            $('.tab-panel').removeClass('active');
            $(`#${tabId}-tab`).addClass('active');
        }
        
        toggleSection(e) {
            e.preventDefault();
            
            const $section = $(e.target).closest('.analytics-section');
            const $content = $section.find('.section-content');
            
            $content.slideToggle();
            $(e.target).text($content.is(':visible') ? 'Comprimi' : 'Espandi');
        }
        
        exportReport() {
            const exportData = {
                period: this.currentPeriod,
                view: this.currentView,
                generated_at: new Date().toISOString(),
                kpis: this.getCurrentKPIs(),
                charts_data: this.getChartsData()
            };
            
            // Create and download JSON file
            const blob = new Blob([JSON.stringify(exportData, null, 2)], {type: 'application/json'});
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `yht-analytics-report-${new Date().toISOString().split('T')[0]}.json`;
            link.click();
            URL.revokeObjectURL(url);
        }
        
        getCurrentKPIs() {
            return {
                total_revenue: $('#total-revenue').text(),
                total_bookings: $('#total-bookings').text(),
                conversion_rate: $('#conversion-rate').text(),
                avg_satisfaction: $('#avg-satisfaction').text(),
                flexibility_index: $('#flexibility-index').text(),
                protection_score: $('#protection-score').text()
            };
        }
        
        getChartsData() {
            const data = {};
            Object.keys(this.charts).forEach(key => {
                if (this.charts[key]) {
                    data[key] = this.charts[key].data;
                }
            });
            return data;
        }
        
        startAutoRefresh() {
            // Auto-refresh every 5 minutes
            this.refreshInterval = setInterval(() => {
                this.refreshData();
            }, 300000);
        }
        
        showLoading() {
            $('#analytics-loading').show();
        }
        
        hideLoading() {
            $('#analytics-loading').hide();
        }
        
        formatNumber(num) {
            return new Intl.NumberFormat('it-IT', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(num);
        }
    }
    
    // Initialize dashboard when document is ready
    $(document).ready(function() {
        if ($('.yht-analytics-dashboard').length) {
            new YHTAnalyticsDashboard();
        }
    });
    
})(jQuery);