/**
 * YHT Cache Manager - Shared caching utility for all YHT modules
 * Provides centralized cache management with TTL and namespace support
 */

class YHTCacheManager {
    constructor() {
        this.cache = new Map();
        this.config = {
            default: 5 * 60 * 1000, // 5 minutes
            recommendations: 5 * 60 * 1000, // 5 minutes
            tours: 10 * 60 * 1000, // 10 minutes
            places: 15 * 60 * 1000, // 15 minutes
            prices: 2 * 60 * 1000, // 2 minutes
            analytics: 1 * 60 * 1000, // 1 minute (fast-changing data)
            user_preferences: 30 * 60 * 1000, // 30 minutes
            search_results: 3 * 60 * 1000 // 3 minutes
        };
        
        // Clean expired entries every minute
        setInterval(() => this.cleanup(), 60000);
        
        // Clean all cache on page visibility change (user returns)
        if (typeof document !== 'undefined') {
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden && this.getLastCleanup() > 10 * 60 * 1000) {
                    this.cleanup(true); // Force cleanup if page was hidden >10min
                }
            });
        }
    }

    /**
     * Generate standardized cache key
     * @param {string} namespace Namespace for the cache entry
     * @param {string} identifier Unique identifier within namespace
     * @param {Object} params Additional parameters to include in key
     * @returns {string} Generated cache key
     */
    generateKey(namespace, identifier, params = {}) {
        const baseKey = `${namespace}:${identifier}`;
        
        if (Object.keys(params).length === 0) {
            return baseKey;
        }
        
        // Sort params for consistent keys
        const sortedParams = Object.keys(params)
            .sort()
            .map(key => `${key}=${params[key]}`)
            .join('&');
            
        return `${baseKey}?${sortedParams}`;
    }

    /**
     * Set cache entry with TTL
     * @param {string} key Cache key
     * @param {*} data Data to cache
     * @param {string} type Cache type for TTL lookup
     * @param {number} customTTL Custom TTL in milliseconds
     */
    set(key, data, type = 'default', customTTL = null) {
        const ttl = customTTL || this.config[type] || this.config.default;
        
        this.cache.set(key, {
            data: data,
            timestamp: Date.now(),
            ttl: ttl,
            type: type
        });
        
        // Trigger cleanup if cache size gets too large
        if (this.cache.size > 1000) {
            this.cleanup();
        }
    }

    /**
     * Get cache entry
     * @param {string} key Cache key
     * @param {boolean} allowExpired Allow returning expired entries
     * @returns {*} Cached data or null if not found/expired
     */
    get(key, allowExpired = false) {
        const entry = this.cache.get(key);
        
        if (!entry) {
            return null;
        }
        
        const isExpired = Date.now() - entry.timestamp > entry.ttl;
        
        if (isExpired && !allowExpired) {
            this.cache.delete(key);
            return null;
        }
        
        return entry.data;
    }

    /**
     * Check if key exists in cache (even if expired)
     * @param {string} key Cache key
     * @returns {boolean}
     */
    has(key) {
        return this.cache.has(key);
    }

    /**
     * Remove specific cache entry
     * @param {string} key Cache key to remove
     */
    delete(key) {
        this.cache.delete(key);
    }

    /**
     * Clear cache by namespace
     * @param {string} namespace Namespace to clear
     */
    clearNamespace(namespace) {
        for (const [key] of this.cache) {
            if (key.startsWith(namespace + ':')) {
                this.cache.delete(key);
            }
        }
    }

    /**
     * Clear cache by type
     * @param {string} type Cache type to clear
     */
    clearType(type) {
        for (const [key, entry] of this.cache) {
            if (entry.type === type) {
                this.cache.delete(key);
            }
        }
    }

    /**
     * Clear all cache
     */
    clear() {
        this.cache.clear();
        localStorage.setItem('yht_cache_cleared', Date.now());
    }

    /**
     * Clean up expired cache entries
     * @param {boolean} force Force cleanup of all entries regardless of expiry
     */
    cleanup(force = false) {
        const now = Date.now();
        const deleted = [];
        
        for (const [key, entry] of this.cache) {
            if (force || now - entry.timestamp > entry.ttl) {
                this.cache.delete(key);
                deleted.push(key);
            }
        }
        
        localStorage.setItem('yht_last_cleanup', now);
        
        if (deleted.length > 0 && window.yhtLogger) {
            window.yhtLogger.debug(`Cache cleaned up ${deleted.length} expired entries`);
        }
        
        return deleted.length;
    }

    /**
     * Get cache statistics
     * @returns {Object} Cache statistics
     */
    getStats() {
        const stats = {
            total: this.cache.size,
            expired: 0,
            by_type: {},
            memory_estimate: 0
        };
        
        const now = Date.now();
        
        for (const [key, entry] of this.cache) {
            // Count expired entries
            if (now - entry.timestamp > entry.ttl) {
                stats.expired++;
            }
            
            // Count by type
            stats.by_type[entry.type] = (stats.by_type[entry.type] || 0) + 1;
            
            // Rough memory estimate
            stats.memory_estimate += JSON.stringify(entry).length;
        }
        
        return stats;
    }

    /**
     * Get time of last cleanup
     * @returns {number} Time since last cleanup in milliseconds
     */
    getLastCleanup() {
        const lastCleanup = localStorage.getItem('yht_last_cleanup');
        return lastCleanup ? Date.now() - parseInt(lastCleanup) : 0;
    }

    /**
     * Preload data into cache
     * @param {Array} entries Array of {key, data, type} objects
     */
    preload(entries) {
        entries.forEach(entry => {
            this.set(entry.key, entry.data, entry.type || 'default');
        });
    }

    /**
     * Get or set pattern - returns cached data or executes callback and caches result
     * @param {string} key Cache key
     * @param {Function} callback Function to execute if cache miss
     * @param {string} type Cache type
     * @returns {*} Cached or newly computed data
     */
    async getOrSet(key, callback, type = 'default') {
        const cached = this.get(key);
        
        if (cached !== null) {
            return cached;
        }
        
        try {
            const data = await callback();
            this.set(key, data, type);
            return data;
        } catch (error) {
            if (window.yhtLogger) {
                window.yhtLogger.warn('Cache: Failed to fetch data for key:', key, error);
            }
            return null;
        }
    }
}

// Create global instance
if (typeof window !== 'undefined') {
    window.yhtCache = window.yhtCache || new YHTCacheManager();
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = YHTCacheManager;
}