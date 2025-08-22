/**
 * YHT Client-Side Security Module
 * Provides client-side security measures and request protection
 */

class YHTSecurity {
    constructor() {
        this.requestQueue = [];
        this.rateLimitTracker = new Map();
        this.nonce = yhtSecurity?.nonce || '';
        this.suspiciousActivity = false;
        
        this.init();
    }

    init() {
        this.setupRequestInterception();
        this.setupFormProtection();
        this.setupXSSProtection();
        this.monitorSuspiciousActivity();
        this.setupNonceRefresh();
    }

    /**
     * Setup request interception for security checks
     */
    setupRequestInterception() {
        // Intercept fetch requests
        const originalFetch = window.fetch;
        window.fetch = async (url, options = {}) => {
            if (this.isYHTRequest(url)) {
                options = this.secureRequest(options);
                
                if (!this.checkRateLimit(url)) {
                    throw new Error(yhtSecurity.rate_limit_exceeded);
                }
            }
            
            return originalFetch(url, options);
        };

        // Intercept XMLHttpRequest
        const originalXHROpen = XMLHttpRequest.prototype.open;
        const originalXHRSend = XMLHttpRequest.prototype.send;
        
        XMLHttpRequest.prototype.open = function(method, url, ...args) {
            this._yhtUrl = url;
            this._yhtMethod = method;
            return originalXHROpen.apply(this, [method, url, ...args]);
        };
        
        XMLHttpRequest.prototype.send = function(data) {
            if (this._yhtUrl && window.yhtSecurity.isYHTRequest(this._yhtUrl)) {
                this.setRequestHeader('X-YHT-Nonce', window.yhtSecurity.nonce);
                this.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                
                if (!window.yhtSecurity.checkRateLimit(this._yhtUrl)) {
                    throw new Error(yhtSecurity.rate_limit_exceeded);
                }
            }
            
            return originalXHRSend.apply(this, [data]);
        };
    }

    /**
     * Check if URL is a YHT request
     */
    isYHTRequest(url) {
        return typeof url === 'string' && 
               (url.includes('/wp-json/yht/') || url.includes('yht-'));
    }

    /**
     * Secure request options
     */
    secureRequest(options) {
        // Ensure headers exist
        options.headers = options.headers || {};
        
        // Add security headers
        options.headers['X-YHT-Nonce'] = this.nonce;
        options.headers['X-Requested-With'] = 'XMLHttpRequest';
        
        // Add CSRF protection
        if (options.method === 'POST' || options.method === 'PUT' || options.method === 'DELETE') {
            options.headers['Content-Type'] = options.headers['Content-Type'] || 'application/json';
        }
        
        // Sanitize request body
        if (options.body && typeof options.body === 'string') {
            try {
                const data = JSON.parse(options.body);
                const sanitizedData = this.sanitizeData(data);
                options.body = JSON.stringify(sanitizedData);
            } catch (e) {
                // Not JSON, apply basic sanitization
                options.body = this.sanitizeString(options.body);
            }
        }
        
        return options;
    }

    /**
     * Client-side rate limiting
     */
    checkRateLimit(url) {
        const endpoint = this.normalizeEndpoint(url);
        const now = Date.now();
        const window = 60000; // 1 minute window
        const maxRequests = 30; // 30 requests per minute
        
        if (!this.rateLimitTracker.has(endpoint)) {
            this.rateLimitTracker.set(endpoint, []);
        }
        
        const requests = this.rateLimitTracker.get(endpoint);
        
        // Remove old requests outside window
        const validRequests = requests.filter(timestamp => now - timestamp < window);
        
        if (validRequests.length >= maxRequests) {
            this.showSecurityWarning('Rate limit exceeded. Please slow down.');
            return false;
        }
        
        // Add current request
        validRequests.push(now);
        this.rateLimitTracker.set(endpoint, validRequests);
        
        return true;
    }

    /**
     * Normalize endpoint for rate limiting
     */
    normalizeEndpoint(url) {
        try {
            const urlObj = new URL(url, window.location.origin);
            let path = urlObj.pathname;
            
            // Remove dynamic parts
            path = path.replace(/\/\d+$/, '/{id}');
            path = path.replace(/\/[a-f0-9-]{36}$/, '/{uuid}');
            
            return path;
        } catch (e) {
            return url;
        }
    }

    /**
     * Setup form protection
     */
    setupFormProtection() {
        document.addEventListener('submit', (e) => {
            if (e.target.matches('.yht-form, form[data-yht]')) {
                if (!this.validateForm(e.target)) {
                    e.preventDefault();
                    return false;
                }
                
                // Add security tokens to form
                this.addSecurityTokens(e.target);
            }
        });

        // Monitor form fields for suspicious input
        document.addEventListener('input', (e) => {
            if (e.target.closest('.yht-form')) {
                this.validateInput(e.target);
            }
        });
    }

    /**
     * Validate form before submission
     */
    validateForm(form) {
        const inputs = form.querySelectorAll('input, textarea, select');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!this.validateInput(input)) {
                isValid = false;
            }
        });
        
        return isValid;
    }

    /**
     * Validate individual input
     */
    validateInput(input) {
        const value = input.value;
        const type = input.type || input.tagName.toLowerCase();
        
        // Check for XSS attempts
        if (this.containsXSS(value)) {
            this.handleSecurityThreat('xss_attempt', { field: input.name, value: value.substring(0, 100) });
            this.showSecurityWarning('Suspicious input detected and blocked.');
            input.value = this.sanitizeString(value);
            return false;
        }
        
        // Check for SQL injection attempts
        if (this.containsSQLInjection(value)) {
            this.handleSecurityThreat('sql_injection_attempt', { field: input.name, value: value.substring(0, 100) });
            this.showSecurityWarning('Suspicious input detected and blocked.');
            input.value = this.sanitizeString(value);
            return false;
        }
        
        // Type-specific validation
        switch (type) {
            case 'email':
                return this.validateEmail(value);
            case 'url':
                return this.validateURL(value);
            case 'tel':
                return this.validatePhone(value);
            default:
                return true;
        }
    }

    /**
     * Check for XSS patterns
     */
    containsXSS(input) {
        const xssPatterns = [
            /<script[\s\S]*?>[\s\S]*?<\/script>/gi,
            /javascript:/gi,
            /vbscript:/gi,
            /onload\s*=/gi,
            /onerror\s*=/gi,
            /<iframe[\s\S]*?>/gi,
            /eval\s*\(/gi,
            /expression\s*\(/gi
        ];
        
        return xssPatterns.some(pattern => pattern.test(input));
    }

    /**
     * Check for SQL injection patterns
     */
    containsSQLInjection(input) {
        const sqlPatterns = [
            /union\s+select/gi,
            /or\s+1\s*=\s*1/gi,
            /'\s*;\s*drop/gi,
            /'\s*;\s*delete/gi,
            /'\s*;\s*update/gi,
            /'\s*;\s*insert/gi,
            /--\s*$/gi,
            /\/\*.*\*\//gi
        ];
        
        return sqlPatterns.some(pattern => pattern.test(input));
    }

    /**
     * Validate email
     */
    validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Validate URL
     */
    validateURL(url) {
        try {
            new URL(url);
            
            // Check for suspicious schemes
            const suspiciousSchemes = ['javascript:', 'vbscript:', 'data:', 'file:'];
            return !suspiciousSchemes.some(scheme => url.toLowerCase().startsWith(scheme));
        } catch (e) {
            return false;
        }
    }

    /**
     * Validate phone number
     */
    validatePhone(phone) {
        const phoneRegex = /^\+?[\d\s\-\(\)]+$/;
        return phoneRegex.test(phone) && phone.replace(/\D/g, '').length >= 10;
    }

    /**
     * Add security tokens to form
     */
    addSecurityTokens(form) {
        // Add or update nonce field
        let nonceField = form.querySelector('input[name="_yht_nonce"]');
        if (!nonceField) {
            nonceField = document.createElement('input');
            nonceField.type = 'hidden';
            nonceField.name = '_yht_nonce';
            form.appendChild(nonceField);
        }
        nonceField.value = this.nonce;
        
        // Add timestamp for replay attack protection
        let timestampField = form.querySelector('input[name="_yht_timestamp"]');
        if (!timestampField) {
            timestampField = document.createElement('input');
            timestampField.type = 'hidden';
            timestampField.name = '_yht_timestamp';
            form.appendChild(timestampField);
        }
        timestampField.value = Date.now().toString();
    }

    /**
     * Setup XSS protection
     */
    setupXSSProtection() {
        // Monitor dynamic content insertion
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            this.scanElementForXSS(node);
                        }
                    });
                }
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        // Sanitize existing content
        this.scanElementForXSS(document.body);
    }

    /**
     * Scan element for XSS content
     */
    scanElementForXSS(element) {
        // Check for script tags
        const scripts = element.querySelectorAll('script');
        scripts.forEach(script => {
            if (!script.src && this.containsXSS(script.textContent)) {
                this.handleSecurityThreat('xss_script_detected', { content: script.textContent.substring(0, 100) });
                script.remove();
            }
        });

        // Check for suspicious attributes
        const allElements = element.querySelectorAll('*');
        allElements.forEach(el => {
            Array.from(el.attributes).forEach(attr => {
                if (attr.name.startsWith('on') && this.containsXSS(attr.value)) {
                    this.handleSecurityThreat('xss_attribute_detected', { attribute: attr.name, value: attr.value });
                    el.removeAttribute(attr.name);
                }
            });
        });
    }

    /**
     * Monitor for suspicious activity
     */
    monitorSuspiciousActivity() {
        let suspiciousEvents = 0;
        const threshold = 5;
        const timeWindow = 60000; // 1 minute
        
        const resetCounter = () => {
            setTimeout(() => {
                suspiciousEvents = 0;
            }, timeWindow);
        };

        // Monitor developer tools usage
        let devtools = false;
        setInterval(() => {
            const start = performance.now();
            debugger;
            const end = performance.now();
            
            if (end - start > 100) { // DevTools likely open
                if (!devtools) {
                    devtools = true;
                    suspiciousEvents++;
                    this.handleSecurityThreat('devtools_detected');
                }
            } else {
                devtools = false;
            }
        }, 1000);

        // Monitor rapid clicks/requests
        let clickCount = 0;
        document.addEventListener('click', () => {
            clickCount++;
            if (clickCount > 20) { // More than 20 clicks per second
                suspiciousEvents++;
                this.handleSecurityThreat('rapid_clicking_detected', { clickCount });
                clickCount = 0;
            }
        });

        setInterval(() => {
            clickCount = 0;
        }, 1000);

        // Monitor console access
        const originalConsoleLog = console.log;
        console.log = function(...args) {
            suspiciousEvents++;
            return originalConsoleLog.apply(console, args);
        };

        // Check if threshold exceeded
        setInterval(() => {
            if (suspiciousEvents >= threshold) {
                this.suspiciousActivity = true;
                this.handleSecurityThreat('suspicious_activity_threshold_exceeded', { events: suspiciousEvents });
            }
        }, 5000);

        resetCounter();
    }

    /**
     * Setup automatic nonce refresh
     */
    setupNonceRefresh() {
        // Refresh nonce every 10 minutes
        setInterval(() => {
            this.refreshNonce();
        }, 10 * 60 * 1000);

        // Refresh on window focus (user returned to tab)
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.refreshNonce();
            }
        });
    }

    /**
     * Refresh security nonce
     */
    async refreshNonce() {
        try {
            const response = await fetch(yhtSecurity.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=yht_security_nonce&_ajax_nonce=' + this.nonce
            });
            
            const data = await response.json();
            if (data.success && data.data.nonce) {
                this.nonce = data.data.nonce;
            }
        } catch (error) {
            console.warn('Failed to refresh security nonce:', error);
        }
    }

    /**
     * Sanitize data object
     */
    sanitizeData(data) {
        if (typeof data === 'string') {
            return this.sanitizeString(data);
        }
        
        if (Array.isArray(data)) {
            return data.map(item => this.sanitizeData(item));
        }
        
        if (typeof data === 'object' && data !== null) {
            const sanitized = {};
            Object.keys(data).forEach(key => {
                const sanitizedKey = this.sanitizeString(key);
                sanitized[sanitizedKey] = this.sanitizeData(data[key]);
            });
            return sanitized;
        }
        
        return data;
    }

    /**
     * Sanitize string
     */
    sanitizeString(str) {
        if (typeof str !== 'string') return str;
        
        return str
            .replace(/<script[\s\S]*?>[\s\S]*?<\/script>/gi, '')
            .replace(/<iframe[\s\S]*?>/gi, '')
            .replace(/javascript:/gi, '')
            .replace(/vbscript:/gi, '')
            .replace(/onload\s*=/gi, '')
            .replace(/onerror\s*=/gi, '')
            .replace(/eval\s*\(/gi, '')
            .trim();
    }

    /**
     * Handle security threat
     */
    handleSecurityThreat(type, data = {}) {
        // Log to analytics if available
        if (window.yhtAnalytics) {
            window.yhtAnalytics.trackEvent('security_threat', {
                threat_type: type,
                ...data,
                timestamp: Date.now(),
                user_agent: navigator.userAgent
            });
        }

        // Log to console for debugging
        console.warn('YHT Security Threat Detected:', type, data);

        // Show warning to user if necessary
        if (['xss_attempt', 'sql_injection_attempt'].includes(type)) {
            this.showSecurityWarning('Potentially malicious input blocked for your security.');
        }
    }

    /**
     * Show security warning to user
     */
    showSecurityWarning(message) {
        // Use existing notification system if available
        if (window.yhtUX && window.yhtUX.showToast) {
            window.yhtUX.showToast(message, 'warning');
            return;
        }

        // Fallback notification
        const notification = document.createElement('div');
        notification.className = 'yht-security-warning';
        notification.style.cssText = `
            position: fixed; top: 20px; right: 20px; z-index: 999999;
            background: #ff9800; color: white; padding: 12px 16px;
            border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            max-width: 300px; font-size: 14px;
        `;
        notification.innerHTML = `
            <strong>⚠️ Security Notice:</strong><br>${message}
            <button onclick="this.parentNode.remove()" style="float:right; background:none; border:none; color:white; cursor:pointer; margin-left:10px;">×</button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    }

    /**
     * Get security status
     */
    getSecurityStatus() {
        return {
            nonceValid: !!this.nonce,
            suspiciousActivity: this.suspiciousActivity,
            rateLimitStatus: this.getRateLimitStatus(),
            lastNonceRefresh: this.lastNonceRefresh || 0
        };
    }

    /**
     * Get rate limit status
     */
    getRateLimitStatus() {
        const status = {};
        this.rateLimitTracker.forEach((requests, endpoint) => {
            const now = Date.now();
            const validRequests = requests.filter(timestamp => now - timestamp < 60000);
            status[endpoint] = {
                requests: validRequests.length,
                remaining: Math.max(0, 30 - validRequests.length)
            };
        });
        return status;
    }
}

// Initialize security module
document.addEventListener('DOMContentLoaded', () => {
    if (typeof yhtSecurity !== 'undefined') {
        window.yhtSecurity = new YHTSecurity();
    }
});