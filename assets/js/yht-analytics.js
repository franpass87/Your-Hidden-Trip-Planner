/**
 * YHT Analytics and Insights Module
 * Advanced user behavior tracking, A/B testing, and analytics dashboard
 */

class YHTAnalytics {
    constructor() {
        this.sessionId = this.generateSessionId();
        this.userId = this.getUserId();
        this.events = [];
        this.experiments = {};
        this.heatmapData = [];
        this.userJourney = [];
        this.performanceMetrics = {};
        
        this.init();
    }

    init() {
        this.setupEventTracking();
        this.setupHeatmapping();
        this.setupUserJourney();
        this.setupPerformanceTracking();
        this.setupABTesting();
        this.initializeExperiments();
        this.startSession();
    }

    /**
     * Generate unique session ID
     */
    generateSessionId() {
        return 'yht_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    /**
     * Get or create user ID
     */
    getUserId() {
        let userId = localStorage.getItem('yht_user_id');
        if (!userId) {
            userId = 'user_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem('yht_user_id', userId);
        }
        return userId;
    }

    /**
     * Start analytics session
     */
    startSession() {
        this.trackEvent('session_start', {
            timestamp: Date.now(),
            user_agent: navigator.userAgent,
            screen_resolution: `${screen.width}x${screen.height}`,
            viewport_size: `${window.innerWidth}x${window.innerHeight}`,
            referrer: document.referrer,
            page_url: window.location.href,
            session_id: this.sessionId,
            user_id: this.userId
        });

        // Track session duration
        window.addEventListener('beforeunload', () => {
            this.trackEvent('session_end', {
                duration: Date.now() - this.sessionStartTime,
                events_count: this.events.length
            });
            this.flushEvents();
        });

        this.sessionStartTime = Date.now();
    }

    /**
     * Setup comprehensive event tracking
     */
    setupEventTracking() {
        // Form interactions
        document.addEventListener('input', (e) => {
            if (e.target.closest('.yht-form')) {
                this.trackFormInteraction(e);
            }
        });

        document.addEventListener('change', (e) => {
            if (e.target.closest('.yht-form')) {
                this.trackFormInteraction(e);
            }
        });

        // Button clicks
        document.addEventListener('click', (e) => {
            if (e.target.matches('.yht-btn, button, .yht-clickable')) {
                this.trackButtonClick(e);
            }
        });

        // Navigation tracking
        document.addEventListener('click', (e) => {
            if (e.target.matches('a[href]')) {
                this.trackNavigation(e);
            }
        });

        // Scroll tracking
        this.setupScrollTracking();

        // Error tracking
        this.setupErrorTracking();

        // Custom event tracking
        document.addEventListener('yht-custom-event', (e) => {
            this.trackEvent(e.detail.name, e.detail.data);
        });
    }

    /**
     * Track form interactions with detailed context
     */
    trackFormInteraction(event) {
        const element = event.target;
        const form = element.closest('form') || element.closest('.yht-form');
        const formId = form?.id || form?.className || 'unknown_form';
        
        this.trackEvent('form_interaction', {
            form_id: formId,
            field_name: element.name || element.id,
            field_type: element.type || element.tagName.toLowerCase(),
            action: event.type,
            value_length: element.value ? element.value.length : 0,
            step: form?.dataset?.step || null,
            timestamp: Date.now()
        });

        // Track form completion progress
        this.updateFormProgress(form);
    }

    /**
     * Track button clicks with context
     */
    trackButtonClick(event) {
        const button = event.target;
        
        this.trackEvent('button_click', {
            button_text: button.textContent.trim(),
            button_class: button.className,
            button_id: button.id,
            parent_context: button.closest('[data-context]')?.dataset?.context || null,
            position: this.getElementPosition(button),
            timestamp: Date.now()
        });
    }

    /**
     * Track navigation events
     */
    trackNavigation(event) {
        const link = event.target;
        
        this.trackEvent('navigation_click', {
            href: link.href,
            text: link.textContent.trim(),
            external: !link.href.includes(window.location.hostname),
            position: this.getElementPosition(link),
            timestamp: Date.now()
        });
    }

    /**
     * Get element position in viewport
     */
    getElementPosition(element) {
        const rect = element.getBoundingClientRect();
        return {
            x: rect.left + rect.width / 2,
            y: rect.top + rect.height / 2,
            viewport_percentage_x: ((rect.left + rect.width / 2) / window.innerWidth) * 100,
            viewport_percentage_y: ((rect.top + rect.height / 2) / window.innerHeight) * 100
        };
    }

    /**
     * Setup scroll tracking and engagement metrics
     */
    setupScrollTracking() {
        let maxScroll = 0;
        let scrollMilestones = [25, 50, 75, 90, 100];
        let trackedMilestones = new Set();

        const trackScroll = this.debounce(() => {
            const scrollPercent = Math.round((window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100);
            
            if (scrollPercent > maxScroll) {
                maxScroll = scrollPercent;
            }

            // Track milestone achievements
            scrollMilestones.forEach(milestone => {
                if (scrollPercent >= milestone && !trackedMilestones.has(milestone)) {
                    trackedMilestones.add(milestone);
                    this.trackEvent('scroll_milestone', {
                        milestone: milestone,
                        time_to_milestone: Date.now() - this.sessionStartTime
                    });
                }
            });
        }, 250);

        window.addEventListener('scroll', trackScroll, { passive: true });

        // Track final scroll percentage on page unload
        window.addEventListener('beforeunload', () => {
            this.trackEvent('scroll_depth', {
                max_scroll_percent: maxScroll,
                total_time: Date.now() - this.sessionStartTime
            });
        });
    }

    /**
     * Setup error tracking
     */
    setupErrorTracking() {
        // JavaScript errors
        window.addEventListener('error', (e) => {
            this.trackEvent('javascript_error', {
                message: e.message,
                filename: e.filename,
                lineno: e.lineno,
                colno: e.colno,
                stack: e.error?.stack,
                timestamp: Date.now()
            });
        });

        // Promise rejections
        window.addEventListener('unhandledrejection', (e) => {
            this.trackEvent('promise_rejection', {
                reason: e.reason?.toString(),
                timestamp: Date.now()
            });
        });

        // Form validation errors
        document.addEventListener('yht-validation-error', (e) => {
            this.trackEvent('form_validation_error', {
                field: e.detail.field,
                error: e.detail.error,
                form_id: e.detail.formId
            });
        });
    }

    /**
     * Update form completion progress
     */
    updateFormProgress(form) {
        if (!form) return;

        const fields = form.querySelectorAll('input, select, textarea');
        const filledFields = Array.from(fields).filter(field => {
            return field.value.trim() !== '' || field.checked;
        });

        const progress = Math.round((filledFields.length / fields.length) * 100);
        
        this.trackEvent('form_progress', {
            form_id: form.id || form.className,
            progress_percent: progress,
            filled_fields: filledFields.length,
            total_fields: fields.length,
            step: form.dataset?.step || null
        });
    }

    /**
     * Setup heatmap data collection
     */
    setupHeatmapping() {
        let clickHeatmap = [];
        let moveHeatmap = [];
        let scrollHeatmap = [];

        // Click heatmap
        document.addEventListener('click', (e) => {
            clickHeatmap.push({
                x: e.clientX,
                y: e.clientY,
                timestamp: Date.now(),
                element: e.target.tagName + (e.target.className ? '.' + e.target.className.replace(/\s+/g, '.') : '')
            });
        });

        // Mouse movement heatmap (sampled)
        let mouseMoveThrottle = this.throttle((e) => {
            moveHeatmap.push({
                x: e.clientX,
                y: e.clientY,
                timestamp: Date.now()
            });
        }, 1000); // Sample every second

        document.addEventListener('mousemove', mouseMoveThrottle);

        // Scroll heatmap
        let scrollThrottle = this.throttle(() => {
            scrollHeatmap.push({
                scrollY: window.scrollY,
                timestamp: Date.now()
            });
        }, 500);

        window.addEventListener('scroll', scrollThrottle, { passive: true });

        // Store heatmap data
        this.heatmapData = { clickHeatmap, moveHeatmap, scrollHeatmap };

        // Send heatmap data periodically
        setInterval(() => {
            if (clickHeatmap.length > 0 || moveHeatmap.length > 0) {
                this.sendHeatmapData();
                clickHeatmap = [];
                moveHeatmap = [];
                scrollHeatmap = [];
            }
        }, 30000); // Send every 30 seconds
    }

    /**
     * Setup user journey tracking
     */
    setupUserJourney() {
        this.userJourney = JSON.parse(localStorage.getItem('yht_user_journey') || '[]');

        // Add current page to journey
        this.addJourneyStep({
            page: window.location.pathname,
            timestamp: Date.now(),
            referrer: document.referrer,
            session_id: this.sessionId
        });

        // Track journey milestones
        this.trackJourneyMilestones();
    }

    /**
     * Add step to user journey
     */
    addJourneyStep(step) {
        this.userJourney.push(step);
        
        // Keep only last 50 steps
        if (this.userJourney.length > 50) {
            this.userJourney = this.userJourney.slice(-50);
        }

        localStorage.setItem('yht_user_journey', JSON.stringify(this.userJourney));
    }

    /**
     * Track journey milestones (first visit, return visitor, etc.)
     */
    trackJourneyMilestones() {
        const visitCount = localStorage.getItem('yht_visit_count') || '0';
        const newVisitCount = parseInt(visitCount) + 1;
        localStorage.setItem('yht_visit_count', newVisitCount.toString());

        this.trackEvent('page_visit', {
            visit_count: newVisitCount,
            is_returning_visitor: newVisitCount > 1,
            journey_length: this.userJourney.length
        });

        // Track conversion funnel
        this.trackConversionFunnel();
    }

    /**
     * Track conversion funnel progress
     */
    trackConversionFunnel() {
        const funnelSteps = [
            { name: 'landing', pattern: /^\/$|^\/home/ },
            { name: 'trip_builder', pattern: /trip-builder|planner/ },
            { name: 'form_start', selector: '.yht-form' },
            { name: 'form_progress', progress: 50 },
            { name: 'form_completion', completed: true },
            { name: 'booking', pattern: /booking|checkout/ }
        ];

        const currentPath = window.location.pathname;
        
        funnelSteps.forEach(step => {
            if (step.pattern && step.pattern.test(currentPath)) {
                this.trackEvent('funnel_step', {
                    step: step.name,
                    timestamp: Date.now()
                });
            }
        });
    }

    /**
     * Setup performance tracking
     */
    setupPerformanceTracking() {
        // Page load metrics
        window.addEventListener('load', () => {
            const navigation = performance.getEntriesByType('navigation')[0];
            
            this.performanceMetrics = {
                dns_lookup: navigation.domainLookupEnd - navigation.domainLookupStart,
                connection_time: navigation.connectEnd - navigation.connectStart,
                request_time: navigation.responseStart - navigation.requestStart,
                response_time: navigation.responseEnd - navigation.responseStart,
                dom_processing: navigation.domContentLoadedEventEnd - navigation.domContentLoadedEventStart,
                load_complete: navigation.loadEventEnd - navigation.loadEventStart,
                total_load_time: navigation.loadEventEnd - navigation.navigationStart
            };

            this.trackEvent('page_performance', this.performanceMetrics);
        });

        // Core Web Vitals
        this.trackCoreWebVitals();
    }

    /**
     * Track Core Web Vitals metrics
     */
    trackCoreWebVitals() {
        // Largest Contentful Paint (LCP)
        if ('PerformanceObserver' in window) {
            try {
                const lcpObserver = new PerformanceObserver((entryList) => {
                    const entries = entryList.getEntries();
                    const lastEntry = entries[entries.length - 1];
                    
                    this.trackEvent('core_web_vitals', {
                        metric: 'LCP',
                        value: lastEntry.startTime,
                        rating: lastEntry.startTime < 2500 ? 'good' : lastEntry.startTime < 4000 ? 'needs_improvement' : 'poor'
                    });
                });
                
                lcpObserver.observe({ entryTypes: ['largest-contentful-paint'] });
            } catch (e) {
                console.log('LCP observer not supported');
            }

            // Cumulative Layout Shift (CLS)
            try {
                const clsObserver = new PerformanceObserver((entryList) => {
                    let clsScore = 0;
                    
                    entryList.getEntries().forEach((entry) => {
                        if (!entry.hadRecentInput) {
                            clsScore += entry.value;
                        }
                    });
                    
                    this.trackEvent('core_web_vitals', {
                        metric: 'CLS',
                        value: clsScore,
                        rating: clsScore < 0.1 ? 'good' : clsScore < 0.25 ? 'needs_improvement' : 'poor'
                    });
                });
                
                clsObserver.observe({ entryTypes: ['layout-shift'] });
            } catch (e) {
                console.log('CLS observer not supported');
            }
        }

        // First Input Delay (FID)
        if ('addEventListener' in document) {
            let firstInputDelay;
            
            const measureFID = (event) => {
                firstInputDelay = performance.now() - event.timeStamp;
                
                this.trackEvent('core_web_vitals', {
                    metric: 'FID',
                    value: firstInputDelay,
                    rating: firstInputDelay < 100 ? 'good' : firstInputDelay < 300 ? 'needs_improvement' : 'poor'
                });
                
                // Remove listeners after first input
                ['mousedown', 'keydown', 'touchstart'].forEach(type => {
                    document.removeEventListener(type, measureFID, true);
                });
            };
            
            ['mousedown', 'keydown', 'touchstart'].forEach(type => {
                document.addEventListener(type, measureFID, { once: true, passive: true, capture: true });
            });
        }
    }

    /**
     * A/B Testing setup
     */
    setupABTesting() {
        this.experiments = JSON.parse(localStorage.getItem('yht_experiments') || '{}');
    }

    /**
     * Initialize active experiments
     */
    initializeExperiments() {
        const activeExperiments = [
            {
                name: 'button_color_test',
                variants: ['primary', 'success', 'warning'],
                traffic_split: [40, 40, 20] // percentages
            },
            {
                name: 'form_layout_test',
                variants: ['single_column', 'two_column'],
                traffic_split: [50, 50]
            },
            {
                name: 'recommendation_algorithm',
                variants: ['collaborative', 'content_based', 'hybrid'],
                traffic_split: [33, 33, 34]
            }
        ];

        activeExperiments.forEach(experiment => {
            this.assignExperimentVariant(experiment);
        });
    }

    /**
     * Assign user to experiment variant
     */
    assignExperimentVariant(experiment) {
        if (this.experiments[experiment.name]) {
            return this.experiments[experiment.name];
        }

        const random = Math.random() * 100;
        let cumulativePercentage = 0;
        let assignedVariant = experiment.variants[0];

        for (let i = 0; i < experiment.variants.length; i++) {
            cumulativePercentage += experiment.traffic_split[i];
            if (random <= cumulativePercentage) {
                assignedVariant = experiment.variants[i];
                break;
            }
        }

        this.experiments[experiment.name] = assignedVariant;
        localStorage.setItem('yht_experiments', JSON.stringify(this.experiments));

        this.trackEvent('experiment_assignment', {
            experiment: experiment.name,
            variant: assignedVariant
        });

        return assignedVariant;
    }

    /**
     * Get experiment variant for a user
     */
    getExperimentVariant(experimentName) {
        return this.experiments[experimentName] || null;
    }

    /**
     * Track experiment conversion
     */
    trackConversion(experimentName, conversionType = 'primary') {
        const variant = this.getExperimentVariant(experimentName);
        if (!variant) return;

        this.trackEvent('experiment_conversion', {
            experiment: experimentName,
            variant: variant,
            conversion_type: conversionType,
            timestamp: Date.now()
        });
    }

    /**
     * Track custom event
     */
    trackEvent(eventName, eventData = {}) {
        const event = {
            name: eventName,
            data: eventData,
            timestamp: Date.now(),
            session_id: this.sessionId,
            user_id: this.userId,
            page_url: window.location.href,
            user_agent: navigator.userAgent
        };

        this.events.push(event);

        // Send events when buffer is full or after delay
        if (this.events.length >= 10) {
            this.flushEvents();
        }

        // Auto-flush after 30 seconds
        if (!this.flushTimeout) {
            this.flushTimeout = setTimeout(() => {
                this.flushEvents();
            }, 30000);
        }
    }

    /**
     * Send events to analytics endpoint
     */
    flushEvents() {
        if (this.events.length === 0) return;

        const payload = {
            events: this.events,
            session_id: this.sessionId,
            user_id: this.userId,
            timestamp: Date.now()
        };

        // Send to WordPress REST API endpoint
        fetch('/wp-json/yht/v1/analytics', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload)
        }).then(response => {
            if (response.ok) {
                this.events = [];
                if (this.flushTimeout) {
                    clearTimeout(this.flushTimeout);
                    this.flushTimeout = null;
                }
            }
        }).catch(error => {
            console.error('Analytics flush failed:', error);
        });
    }

    /**
     * Send heatmap data
     */
    sendHeatmapData() {
        if (!this.heatmapData || 
            (this.heatmapData.clickHeatmap.length === 0 && 
             this.heatmapData.moveHeatmap.length === 0)) {
            return;
        }

        const payload = {
            heatmap_data: this.heatmapData,
            page_url: window.location.href,
            viewport: {
                width: window.innerWidth,
                height: window.innerHeight
            },
            session_id: this.sessionId,
            user_id: this.userId,
            timestamp: Date.now()
        };

        fetch('/wp-json/yht/v1/heatmap', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload)
        }).catch(error => {
            console.error('Heatmap data send failed:', error);
        });
    }

    /**
     * Generate analytics report (for admin dashboard)
     */
    generateReport(timeframe = '7d') {
        return fetch(`/wp-json/yht/v1/analytics/report?timeframe=${timeframe}`, {
            method: 'GET',
            headers: {
                'X-WP-Nonce': yhtData?.nonce || ''
            }
        }).then(response => response.json());
    }

    /**
     * Utility: Debounce function
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Utility: Throttle function
     */
    throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    /**
     * Get current analytics summary
     */
    getAnalyticsSummary() {
        return {
            session_id: this.sessionId,
            user_id: this.userId,
            events_count: this.events.length,
            session_duration: Date.now() - this.sessionStartTime,
            experiments: this.experiments,
            performance_metrics: this.performanceMetrics
        };
    }
}

// Initialize analytics
document.addEventListener('DOMContentLoaded', () => {
    // Only initialize if analytics is enabled
    if (!window.yhtAnalyticsDisabled) {
        window.yhtAnalytics = new YHTAnalytics();
        
        // Global function for custom event tracking
        window.trackYHTEvent = (name, data) => {
            window.yhtAnalytics.trackEvent(name, data);
        };
    }
});