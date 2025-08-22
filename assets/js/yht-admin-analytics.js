/**
 * YHT Admin Analytics Dashboard
 * Provides visualizations and insights for the analytics data
 */

class YHTAnalyticsDashboard {
    constructor() {
        this.charts = {};
        this.chartColors = {
            primary: '#007cba',
            secondary: '#6c757d', 
            success: '#28a745',
            warning: '#ffc107',
            danger: '#dc3545',
            info: '#17a2b8'
        };
        
        this.init();
    }

    init() {
        this.setupDashboard();
        this.loadAnalyticsData();
        this.setupRealTimeUpdates();
    }

    /**
     * Setup dashboard layout and controls
     */
    setupDashboard() {
        const container = document.getElementById('yht-analytics-dashboard');
        if (!container) return;

        container.innerHTML = `
            <div class="yht-dashboard-header">
                <h2>📊 Analytics Dashboard</h2>
                <div class="yht-dashboard-controls">
                    <select id="yht-timeframe">
                        <option value="24h">Last 24 Hours</option>
                        <option value="7d" selected>Last 7 Days</option>
                        <option value="30d">Last 30 Days</option>
                    </select>
                    <button id="yht-refresh-data" class="button">🔄 Refresh</button>
                    <button id="yht-export-data" class="button">📥 Export</button>
                </div>
            </div>
            
            <div class="yht-dashboard-grid">
                <!-- Overview Cards -->
                <div class="yht-card-grid">
                    <div class="yht-metric-card" id="total-users">
                        <div class="metric-value">-</div>
                        <div class="metric-label">Total Users</div>
                    </div>
                    <div class="yht-metric-card" id="total-sessions">
                        <div class="metric-value">-</div>
                        <div class="metric-label">Sessions</div>
                    </div>
                    <div class="yht-metric-card" id="avg-duration">
                        <div class="metric-value">-</div>
                        <div class="metric-label">Avg Session</div>
                    </div>
                    <div class="yht-metric-card" id="conversion-rate">
                        <div class="metric-value">-</div>
                        <div class="metric-label">Conversion</div>
                    </div>
                </div>

                <!-- Charts Row 1 -->
                <div class="yht-chart-row">
                    <div class="yht-chart-container">
                        <h3>📈 Events Timeline</h3>
                        <canvas id="events-timeline-chart"></canvas>
                    </div>
                    <div class="yht-chart-container">
                        <h3>🏆 Top Events</h3>
                        <canvas id="top-events-chart"></canvas>
                    </div>
                </div>

                <!-- Charts Row 2 -->
                <div class="yht-chart-row">
                    <div class="yht-chart-container">
                        <h3>⚡ Performance Metrics</h3>
                        <canvas id="performance-chart"></canvas>
                    </div>
                    <div class="yht-chart-container">
                        <h3>🎯 Conversion Funnel</h3>
                        <canvas id="funnel-chart"></canvas>
                    </div>
                </div>

                <!-- Real-time Activity -->
                <div class="yht-realtime-section">
                    <h3>🔴 Real-time Activity</h3>
                    <div id="realtime-activity"></div>
                </div>

                <!-- Security Dashboard -->
                <div class="yht-security-section">
                    <h3>🔒 Security Overview</h3>
                    <div id="security-overview"></div>
                </div>
            </div>
        `;

        this.setupEventListeners();
        this.addDashboardStyles();
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Timeframe selector
        document.getElementById('yht-timeframe')?.addEventListener('change', (e) => {
            this.loadAnalyticsData(e.target.value);
        });

        // Refresh button
        document.getElementById('yht-refresh-data')?.addEventListener('click', () => {
            this.refreshData();
        });

        // Export button
        document.getElementById('yht-export-data')?.addEventListener('click', () => {
            this.exportData();
        });
    }

    /**
     * Load analytics data from API
     */
    async loadAnalyticsData(timeframe = '7d') {
        try {
            this.showLoading(true);
            
            const [analyticsData, dashboardData, securityData] = await Promise.all([
                this.fetchAnalyticsReport(timeframe),
                this.fetchDashboardData(),
                this.fetchSecurityStats()
            ]);

            this.updateOverviewCards(analyticsData.summary);
            this.createEventsTimelineChart(analyticsData.events.timeline);
            this.createTopEventsChart(analyticsData.events.top_events);
            this.createPerformanceChart(analyticsData.performance);
            this.createFunnelChart(analyticsData.funnel);
            this.updateRealTimeActivity(dashboardData.real_time);
            this.updateSecurityOverview(securityData);

        } catch (error) {
            console.error('Failed to load analytics data:', error);
            this.showError('Failed to load analytics data');
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * Fetch analytics report
     */
    async fetchAnalyticsReport(timeframe = '7d') {
        const response = await fetch(`/wp-json/yht/v1/analytics/report?timeframe=${timeframe}`, {
            headers: {
                'X-WP-Nonce': yhtData?.nonce || ''
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to fetch analytics report');
        }
        
        return response.json();
    }

    /**
     * Fetch dashboard data
     */
    async fetchDashboardData() {
        const response = await fetch('/wp-json/yht/v1/analytics/dashboard', {
            headers: {
                'X-WP-Nonce': yhtData?.nonce || ''
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to fetch dashboard data');
        }
        
        return response.json();
    }

    /**
     * Fetch security statistics
     */
    async fetchSecurityStats() {
        // Mock security data for now - would come from YHT_Security class
        return {
            blocked_requests: 12,
            top_attackers: [
                { ip_address: '192.168.1.1', attempts: 5 },
                { ip_address: '10.0.0.1', attempts: 3 }
            ],
            attack_types: [
                { event_type: 'rate_limit_exceeded', count: 8 },
                { event_type: 'suspicious_request', count: 4 }
            ]
        };
    }

    /**
     * Update overview metric cards
     */
    updateOverviewCards(summary) {
        document.querySelector('#total-users .metric-value').textContent = 
            this.formatNumber(summary.unique_users);
        
        document.querySelector('#total-sessions .metric-value').textContent = 
            this.formatNumber(summary.total_sessions);
        
        document.querySelector('#avg-duration .metric-value').textContent = 
            this.formatDuration(summary.avg_session_duration);
        
        // Calculate conversion rate (mock calculation)
        const conversionRate = summary.unique_users > 0 ? 
            ((summary.total_sessions * 0.15) / summary.unique_users * 100) : 0;
        
        document.querySelector('#conversion-rate .metric-value').textContent = 
            conversionRate.toFixed(1) + '%';
    }

    /**
     * Create events timeline chart
     */
    createEventsTimelineChart(timelineData) {
        const ctx = document.getElementById('events-timeline-chart');
        if (!ctx) return;

        if (this.charts.timeline) {
            this.charts.timeline.destroy();
        }

        const labels = timelineData.map(item => new Date(item.date).toLocaleDateString());
        const data = timelineData.map(item => item.count);

        this.charts.timeline = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Events',
                    data: data,
                    borderColor: this.chartColors.primary,
                    backgroundColor: this.chartColors.primary + '20',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    /**
     * Create top events chart
     */
    createTopEventsChart(topEvents) {
        const ctx = document.getElementById('top-events-chart');
        if (!ctx) return;

        if (this.charts.topEvents) {
            this.charts.topEvents.destroy();
        }

        const labels = topEvents.map(item => this.formatEventName(item.event_name));
        const data = topEvents.map(item => item.count);

        this.charts.topEvents = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: [
                        this.chartColors.primary,
                        this.chartColors.success,
                        this.chartColors.warning,
                        this.chartColors.info,
                        this.chartColors.danger
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    /**
     * Create performance metrics chart
     */
    createPerformanceChart(performanceData) {
        const ctx = document.getElementById('performance-chart');
        if (!ctx) return;

        if (this.charts.performance) {
            this.charts.performance.destroy();
        }

        // Extract Core Web Vitals data
        const vitalsData = performanceData.core_vitals || [];
        
        const labels = vitalsData.map(item => item.metric?.replace(/"/g, ''));
        const data = vitalsData.map(item => parseFloat(item.avg_value) || 0);

        this.charts.performance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Performance Metrics',
                    data: data,
                    backgroundColor: data.map((value, index) => {
                        // Color based on performance thresholds
                        if (labels[index] === 'LCP') return value < 2500 ? this.chartColors.success : this.chartColors.warning;
                        if (labels[index] === 'FID') return value < 100 ? this.chartColors.success : this.chartColors.warning;
                        if (labels[index] === 'CLS') return value < 0.1 ? this.chartColors.success : this.chartColors.warning;
                        return this.chartColors.primary;
                    })
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    /**
     * Create conversion funnel chart
     */
    createFunnelChart(funnelData) {
        const ctx = document.getElementById('funnel-chart');
        if (!ctx) return;

        if (this.charts.funnel) {
            this.charts.funnel.destroy();
        }

        const labels = funnelData.map(item => this.formatEventName(item.step?.replace(/"/g, '') || 'Unknown'));
        const data = funnelData.map(item => parseInt(item.users) || 0);

        this.charts.funnel = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Users',
                    data: data,
                    backgroundColor: this.chartColors.info
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    /**
     * Update real-time activity section
     */
    updateRealTimeActivity(realTimeData) {
        const container = document.getElementById('realtime-activity');
        if (!container) return;

        const activeUsers = realTimeData.active_users || 0;
        const recentEvents = realTimeData.recent_events || [];

        container.innerHTML = `
            <div class="realtime-metrics">
                <div class="realtime-metric">
                    <span class="metric-value">${activeUsers}</span>
                    <span class="metric-label">Active Users</span>
                </div>
            </div>
            <div class="recent-events">
                <h4>Recent Events</h4>
                <ul>
                    ${recentEvents.map(event => `
                        <li>
                            <span class="event-name">${this.formatEventName(event.event_name)}</span>
                            <span class="event-count">${event.count}</span>
                        </li>
                    `).join('')}
                </ul>
            </div>
        `;
    }

    /**
     * Update security overview
     */
    updateSecurityOverview(securityData) {
        const container = document.getElementById('security-overview');
        if (!container) return;

        container.innerHTML = `
            <div class="security-metrics">
                <div class="security-metric">
                    <span class="metric-value ${securityData.blocked_requests > 10 ? 'warning' : 'success'}">
                        ${securityData.blocked_requests}
                    </span>
                    <span class="metric-label">Blocked Requests</span>
                </div>
            </div>
            
            ${securityData.top_attackers.length > 0 ? `
                <div class="security-threats">
                    <h4>🚨 Top Threat Sources</h4>
                    <ul>
                        ${securityData.top_attackers.slice(0, 5).map(attacker => `
                            <li>
                                <span class="ip-address">${attacker.ip_address}</span>
                                <span class="attempt-count">${attacker.attempts} attempts</span>
                            </li>
                        `).join('')}
                    </ul>
                </div>
            ` : '<p>✅ No security threats detected</p>'}
        `;
    }

    /**
     * Setup real-time updates
     */
    setupRealTimeUpdates() {
        // Update real-time data every 30 seconds
        setInterval(() => {
            this.updateRealTimeData();
        }, 30000);
    }

    /**
     * Update only real-time data
     */
    async updateRealTimeData() {
        try {
            const dashboardData = await this.fetchDashboardData();
            this.updateRealTimeActivity(dashboardData.real_time);
        } catch (error) {
            console.error('Failed to update real-time data:', error);
        }
    }

    /**
     * Refresh all data
     */
    refreshData() {
        const timeframe = document.getElementById('yht-timeframe')?.value || '7d';
        this.loadAnalyticsData(timeframe);
    }

    /**
     * Export analytics data
     */
    async exportData() {
        try {
            const timeframe = document.getElementById('yht-timeframe')?.value || '7d';
            const response = await fetch(`/wp-json/yht/v1/analytics/export?timeframe=${timeframe}&format=csv`, {
                headers: {
                    'X-WP-Nonce': yhtData?.nonce || ''
                }
            });
            
            if (!response.ok) {
                throw new Error('Export failed');
            }
            
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `yht-analytics-${timeframe}-${new Date().toISOString().slice(0, 10)}.csv`;
            a.click();
            window.URL.revokeObjectURL(url);
        } catch (error) {
            console.error('Failed to export data:', error);
            alert('Failed to export data');
        }
    }

    /**
     * Show/hide loading state
     */
    showLoading(show) {
        const dashboard = document.getElementById('yht-analytics-dashboard');
        if (!dashboard) return;

        if (show) {
            dashboard.classList.add('loading');
        } else {
            dashboard.classList.remove('loading');
        }
    }

    /**
     * Show error message
     */
    showError(message) {
        console.error(message);
        // Could integrate with notification system
    }

    /**
     * Format event name for display
     */
    formatEventName(eventName) {
        return eventName
            .replace(/_/g, ' ')
            .replace(/\b\w/g, l => l.toUpperCase());
    }

    /**
     * Format number with thousands separator
     */
    formatNumber(num) {
        return new Intl.NumberFormat().format(num);
    }

    /**
     * Format duration in seconds to human readable
     */
    formatDuration(seconds) {
        if (seconds < 60) return Math.round(seconds) + 's';
        if (seconds < 3600) return Math.round(seconds / 60) + 'm';
        return Math.round(seconds / 3600) + 'h';
    }

    /**
     * Add dashboard-specific styles
     */
    addDashboardStyles() {
        if (document.getElementById('yht-dashboard-styles')) return;

        const styles = document.createElement('style');
        styles.id = 'yht-dashboard-styles';
        styles.textContent = `
            .yht-dashboard-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
                padding-bottom: 15px;
                border-bottom: 1px solid #ddd;
            }
            
            .yht-dashboard-controls {
                display: flex;
                gap: 10px;
                align-items: center;
            }
            
            .yht-card-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
            }
            
            .yht-metric-card {
                background: white;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 20px;
                text-align: center;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            
            .metric-value {
                font-size: 2.5em;
                font-weight: bold;
                color: #007cba;
                margin-bottom: 8px;
            }
            
            .metric-value.warning {
                color: #ffc107;
            }
            
            .metric-value.success {
                color: #28a745;
            }
            
            .metric-label {
                font-size: 0.9em;
                color: #666;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .yht-chart-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
                margin-bottom: 30px;
            }
            
            .yht-chart-container {
                background: white;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 20px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            
            .yht-chart-container h3 {
                margin-top: 0;
                margin-bottom: 15px;
                font-size: 1.1em;
            }
            
            .yht-chart-container canvas {
                height: 300px !important;
            }
            
            .yht-realtime-section,
            .yht-security-section {
                background: white;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 20px;
                margin-bottom: 20px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            
            .realtime-metrics {
                display: flex;
                gap: 30px;
                margin-bottom: 20px;
            }
            
            .realtime-metric {
                text-align: center;
            }
            
            .recent-events ul,
            .security-threats ul {
                list-style: none;
                padding: 0;
                margin: 0;
            }
            
            .recent-events li,
            .security-threats li {
                display: flex;
                justify-content: space-between;
                padding: 8px 0;
                border-bottom: 1px solid #eee;
            }
            
            .event-name,
            .ip-address {
                font-weight: 500;
            }
            
            .event-count,
            .attempt-count {
                color: #666;
                font-size: 0.9em;
            }
            
            #yht-analytics-dashboard.loading {
                opacity: 0.6;
                pointer-events: none;
            }
            
            #yht-analytics-dashboard.loading::after {
                content: 'Loading...';
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: rgba(0,0,0,0.8);
                color: white;
                padding: 20px;
                border-radius: 8px;
                z-index: 9999;
            }
            
            @media (max-width: 768px) {
                .yht-chart-row {
                    grid-template-columns: 1fr;
                }
                
                .yht-dashboard-header {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 15px;
                }
            }
        `;
        
        document.head.appendChild(styles);
    }
}

// Initialize dashboard when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('yht-analytics-dashboard')) {
        window.yhtAnalyticsDashboard = new YHTAnalyticsDashboard();
    }
});