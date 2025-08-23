/**
 * YHT Debug Logger - Centralized logging utility
 * Provides controlled logging based on debug settings
 */

class YHTLogger {
    constructor() {
        // Enable debug in development or when explicitly enabled
        this.debugEnabled = (
            window.location.hostname === 'localhost' ||
            window.location.hostname.includes('dev') ||
            window.location.hostname.includes('staging') ||
            localStorage.getItem('yht_debug') === 'true' ||
            window.yhtDebug === true
        );
        
        this.logLevels = {
            ERROR: 0,
            WARN: 1,
            INFO: 2,
            DEBUG: 3
        };
        
        this.currentLevel = this.debugEnabled ? this.logLevels.DEBUG : this.logLevels.ERROR;
    }

    /**
     * Log error message (always shown)
     * @param {string} message Error message
     * @param {...any} args Additional arguments
     */
    error(message, ...args) {
        if (this.currentLevel >= this.logLevels.ERROR) {
            console.error(`[YHT ERROR] ${message}`, ...args);
        }
    }

    /**
     * Log warning message (shown in debug mode)
     * @param {string} message Warning message
     * @param {...any} args Additional arguments
     */
    warn(message, ...args) {
        if (this.currentLevel >= this.logLevels.WARN) {
            console.warn(`[YHT WARN] ${message}`, ...args);
        }
    }

    /**
     * Log info message (shown in debug mode)
     * @param {string} message Info message
     * @param {...any} args Additional arguments
     */
    info(message, ...args) {
        if (this.currentLevel >= this.logLevels.INFO) {
            console.info(`[YHT INFO] ${message}`, ...args);
        }
    }

    /**
     * Log debug message (shown only in debug mode)
     * @param {string} message Debug message
     * @param {...any} args Additional arguments
     */
    debug(message, ...args) {
        if (this.currentLevel >= this.logLevels.DEBUG) {
            console.log(`[YHT DEBUG] ${message}`, ...args);
        }
    }

    /**
     * Group related log messages
     * @param {string} label Group label
     * @param {Function} callback Function containing grouped logs
     */
    group(label, callback) {
        if (this.debugEnabled) {
            console.group(`[YHT] ${label}`);
            callback();
            console.groupEnd();
        } else {
            callback();
        }
    }

    /**
     * Log performance timing
     * @param {string} label Timer label
     * @param {Function} callback Function to time
     * @returns {*} Result of callback
     */
    async time(label, callback) {
        const start = performance.now();
        
        if (this.debugEnabled) {
            console.time(`[YHT TIME] ${label}`);
        }
        
        try {
            const result = await callback();
            
            if (this.debugEnabled) {
                console.timeEnd(`[YHT TIME] ${label}`);
                const duration = performance.now() - start;
                this.debug(`${label} took ${duration.toFixed(2)}ms`);
            }
            
            return result;
        } catch (error) {
            if (this.debugEnabled) {
                console.timeEnd(`[YHT TIME] ${label}`);
            }
            this.error(`${label} failed:`, error);
            throw error;
        }
    }

    /**
     * Log API request/response
     * @param {string} method HTTP method
     * @param {string} url Request URL
     * @param {*} data Request/response data
     * @param {string} type 'request' or 'response'
     */
    api(method, url, data, type = 'request') {
        if (this.debugEnabled) {
            const emoji = type === 'request' ? '→' : '←';
            this.debug(`${emoji} ${method} ${url}`, data);
        }
    }

    /**
     * Enable or disable debug logging
     * @param {boolean} enabled Whether to enable debug logging
     */
    setDebug(enabled) {
        this.debugEnabled = enabled;
        this.currentLevel = enabled ? this.logLevels.DEBUG : this.logLevels.ERROR;
        localStorage.setItem('yht_debug', enabled.toString());
        
        if (enabled) {
            this.info('Debug logging enabled');
        }
    }

    /**
     * Check if debug is currently enabled
     * @returns {boolean}
     */
    isDebugEnabled() {
        return this.debugEnabled;
    }
}

// Create global logger instance
if (typeof window !== 'undefined') {
    window.yhtLogger = window.yhtLogger || new YHTLogger();
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = YHTLogger;
}