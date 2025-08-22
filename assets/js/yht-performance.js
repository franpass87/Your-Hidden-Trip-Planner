/**
 * YHT Performance Enhancement Module
 * Provides advanced caching, lazy loading, and performance optimization features
 */

class YHTPerformance {
    constructor() {
        this.cache = new Map();
        this.imageObserver = null;
        this.prefetchQueue = new Set();
        this.loadingStates = new Map();
        
        this.init();
    }

    init() {
        this.setupLazyLoading();
        this.setupIntelligentPrefetch();
        this.setupPerformanceMonitoring();
        this.initializeCache();
    }

    /**
     * Advanced caching system for API responses and computed data
     */
    initializeCache() {
        // Set up cache with TTL (Time To Live)
        this.cacheConfig = {
            recommendations: 5 * 60 * 1000, // 5 minutes
            tours: 10 * 60 * 1000, // 10 minutes
            places: 15 * 60 * 1000, // 15 minutes
            prices: 2 * 60 * 1000 // 2 minutes
        };

        // Clean expired cache entries periodically
        setInterval(() => this.cleanExpiredCache(), 60000);
    }

    /**
     * Get cached data with TTL check
     */
    getCached(key, type = 'default') {
        const cached = this.cache.get(key);
        if (!cached) return null;

        const ttl = this.cacheConfig[type] || 5 * 60 * 1000;
        if (Date.now() - cached.timestamp > ttl) {
            this.cache.delete(key);
            return null;
        }

        return cached.data;
    }

    /**
     * Set cached data with timestamp
     */
    setCache(key, data, type = 'default') {
        this.cache.set(key, {
            data: data,
            timestamp: Date.now(),
            type: type
        });
    }

    /**
     * Clean expired cache entries
     */
    cleanExpiredCache() {
        for (const [key, cached] of this.cache.entries()) {
            const ttl = this.cacheConfig[cached.type] || 5 * 60 * 1000;
            if (Date.now() - cached.timestamp > ttl) {
                this.cache.delete(key);
            }
        }
    }

    /**
     * Enhanced lazy loading for images and content
     */
    setupLazyLoading() {
        if ('IntersectionObserver' in window) {
            this.imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.loadImage(entry.target);
                        this.imageObserver.unobserve(entry.target);
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.1
            });

            // Observe all images with data-src
            document.querySelectorAll('img[data-src]').forEach(img => {
                this.imageObserver.observe(img);
            });

            // Observe lazy content sections
            document.querySelectorAll('[data-lazy-content]').forEach(section => {
                this.imageObserver.observe(section);
            });
        }
    }

    /**
     * Load image with loading state and error handling
     */
    loadImage(img) {
        const src = img.dataset.src;
        if (!src) return;

        // Add loading class
        img.classList.add('yht-loading');
        
        const newImg = new Image();
        newImg.onload = () => {
            img.src = src;
            img.classList.remove('yht-loading');
            img.classList.add('yht-loaded');
            
            // Trigger fade-in animation
            setTimeout(() => img.style.opacity = '1', 50);
        };
        
        newImg.onerror = () => {
            img.classList.remove('yht-loading');
            img.classList.add('yht-error');
            img.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjNmNGY2Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZpbGw9IiM2YjcyODAiPkltbWFnaW5lIG5vbiBkaXNwb25pYmlsZTwvdGV4dD48L3N2Zz4=';
        };
        
        newImg.src = src;
    }

    /**
     * Intelligent prefetching based on user behavior
     */
    setupIntelligentPrefetch() {
        // Prefetch on hover with delay
        document.addEventListener('mouseover', (e) => {
            const link = e.target.closest('a[href]');
            if (link && !this.prefetchQueue.has(link.href)) {
                setTimeout(() => this.prefetchResource(link.href), 300);
            }
        });

        // Prefetch likely next steps in forms
        document.addEventListener('change', (e) => {
            if (e.target.closest('.yht-form')) {
                this.prefetchFormAssets(e.target);
            }
        });
    }

    /**
     * Prefetch resource with priority
     */
    prefetchResource(url, priority = 'low') {
        if (this.prefetchQueue.has(url)) return;
        
        this.prefetchQueue.add(url);
        
        const link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = url;
        link.as = 'fetch';
        if (priority === 'high') {
            link.rel = 'preload';
        }
        
        document.head.appendChild(link);
        
        // Remove from queue after timeout
        setTimeout(() => this.prefetchQueue.delete(url), 30000);
    }

    /**
     * Prefetch form-related assets
     */
    prefetchFormAssets(changedField) {
        const form = changedField.closest('.yht-form');
        if (!form) return;

        const currentStep = parseInt(form.dataset.step || '1');
        const nextStep = currentStep + 1;
        
        // Prefetch next step assets
        this.prefetchResource(`/wp-json/yht/v1/form-step/${nextStep}`, 'high');
        
        // Prefetch related recommendations
        if (changedField.name === 'experience_type') {
            this.prefetchResource(`/wp-json/yht/v1/recommendations?type=${changedField.value}`);
        }
    }

    /**
     * Performance monitoring and optimization suggestions
     */
    setupPerformanceMonitoring() {
        if ('PerformanceObserver' in window) {
            // Monitor Largest Contentful Paint
            const lcpObserver = new PerformanceObserver((entryList) => {
                const entries = entryList.getEntries();
                const lastEntry = entries[entries.length - 1];
                
                if (lastEntry.startTime > 2500) {
                    console.warn('YHT: Slow LCP detected', lastEntry.startTime);
                    this.optimizePage();
                }
            });
            
            try {
                lcpObserver.observe({ entryTypes: ['largest-contentful-paint'] });
            } catch (e) {
                console.log('LCP observer not supported');
            }

            // Monitor long tasks
            const taskObserver = new PerformanceObserver((entryList) => {
                entryList.getEntries().forEach((entry) => {
                    if (entry.duration > 50) {
                        console.warn('YHT: Long task detected', entry.duration);
                    }
                });
            });
            
            try {
                taskObserver.observe({ entryTypes: ['longtask'] });
            } catch (e) {
                console.log('Long task observer not supported');
            }
        }
    }

    /**
     * Page optimization when performance issues detected
     */
    optimizePage() {
        // Reduce animation complexity
        document.body.classList.add('yht-reduce-motion');
        
        // Defer non-critical scripts
        document.querySelectorAll('script[data-defer]').forEach(script => {
            script.loading = 'lazy';
        });
        
        // Show performance warning to admin users
        if (document.body.classList.contains('admin-bar')) {
            this.showPerformanceWarning();
        }
    }

    /**
     * Show performance warning to admins
     */
    showPerformanceWarning() {
        if (document.querySelector('.yht-perf-warning')) return;

        const warning = document.createElement('div');
        warning.className = 'yht-perf-warning';
        warning.style.cssText = `
            position: fixed; top: 32px; right: 20px; z-index: 999999;
            background: #ff9800; color: white; padding: 12px 16px;
            border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            font-size: 14px; max-width: 300px;
        `;
        warning.innerHTML = `
            <strong>⚡ Performance Notice:</strong><br>
            Pagina in caricamento lento. Considera l'ottimizzazione delle immagini.
            <button onclick="this.parentNode.remove()" style="float:right; background:none; border:none; color:white; cursor:pointer;">×</button>
        `;
        
        document.body.appendChild(warning);
        setTimeout(() => warning.remove(), 8000);
    }

    /**
     * Advanced loading states management
     */
    setLoadingState(element, state = 'loading') {
        const states = {
            loading: { class: 'yht-loading', html: '⏳ Caricamento...' },
            success: { class: 'yht-success', html: '✅ Completato!' },
            error: { class: 'yht-error', html: '❌ Errore' }
        };

        const config = states[state];
        if (!config) return;

        // Remove previous state classes
        Object.values(states).forEach(s => element.classList.remove(s.class));
        
        // Add new state
        element.classList.add(config.class);
        
        // Update loading indicator
        let indicator = element.querySelector('.yht-state-indicator');
        if (!indicator) {
            indicator = document.createElement('span');
            indicator.className = 'yht-state-indicator';
            element.appendChild(indicator);
        }
        
        indicator.innerHTML = config.html;
        
        // Auto-remove success/error states
        if (state !== 'loading') {
            setTimeout(() => {
                element.classList.remove(config.class);
                indicator.remove();
            }, 3000);
        }
    }

    /**
     * Debounced API calls
     */
    debounce(func, wait = 300) {
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
     * Batch API requests
     */
    batchRequests(requests, batchSize = 5) {
        const batches = [];
        for (let i = 0; i < requests.length; i += batchSize) {
            batches.push(requests.slice(i, i + batchSize));
        }

        return batches.reduce((promise, batch) => {
            return promise.then(results => {
                return Promise.all(batch).then(batchResults => {
                    return results.concat(batchResults);
                });
            });
        }, Promise.resolve([]));
    }

    /**
     * Resource hints for critical resources
     */
    addResourceHints() {
        const hints = [
            { rel: 'dns-prefetch', href: '//fonts.googleapis.com' },
            { rel: 'dns-prefetch', href: '//api.yourhiddentrip.com' },
            { rel: 'preconnect', href: '//fonts.gstatic.com', crossorigin: true }
        ];

        hints.forEach(hint => {
            const link = document.createElement('link');
            Object.assign(link, hint);
            document.head.appendChild(link);
        });
    }
}

// Initialize performance enhancements
document.addEventListener('DOMContentLoaded', () => {
    window.yhtPerformance = new YHTPerformance();
});

// CSS for performance states
if (!document.getElementById('yht-performance-styles')) {
    const styles = document.createElement('style');
    styles.id = 'yht-performance-styles';
    styles.textContent = `
        .yht-loading img {
            opacity: 0.3;
            filter: blur(2px);
            transition: all 0.3s ease;
        }
        
        .yht-loaded img {
            opacity: 1;
            filter: none;
            transition: opacity 0.5s ease;
        }
        
        .yht-error img {
            opacity: 0.5;
            filter: grayscale(1);
        }
        
        .yht-loading::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid #ddd;
            border-top: 2px solid #007cba;
            border-radius: 50%;
            animation: yht-spin 1s linear infinite;
        }
        
        @keyframes yht-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .yht-reduce-motion * {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }
        
        .yht-state-indicator {
            display: inline-block;
            margin-left: 8px;
            font-size: 0.9em;
        }
        
        @media (prefers-reduced-motion: reduce) {
            .yht-loading::before {
                animation: none;
            }
        }
    `;
    document.head.appendChild(styles);
}