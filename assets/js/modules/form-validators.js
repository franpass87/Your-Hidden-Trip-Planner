/**
 * YHT Form Validators - Client-side validation utilities
 * Provides consistent validation across all YHT forms
 */

class YHTValidators {
    /**
     * Validate email address
     * @param {string} email Email to validate
     * @returns {boolean|string} True if valid, error message if invalid
     */
    static email(email) {
        if (!email || typeof email !== 'string') {
            return 'Email è obbligatorio.';
        }
        
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email.trim()) ? true : 'Formato email non valido.';
    }

    /**
     * Validate URL
     * @param {string} url URL to validate
     * @param {boolean} required Whether URL is required
     * @returns {boolean|string} True if valid, error message if invalid
     */
    static url(url, required = false) {
        if (!url || typeof url !== 'string') {
            return required ? 'URL è obbligatorio.' : true;
        }
        
        try {
            new URL(url.trim());
            return true;
        } catch {
            return 'URL non valido.';
        }
    }

    /**
     * Validate required text field
     * @param {string} text Text to validate
     * @param {number} minLength Minimum length
     * @param {number} maxLength Maximum length
     * @returns {boolean|string} True if valid, error message if invalid
     */
    static required(text, minLength = 1, maxLength = 255) {
        if (!text || typeof text !== 'string') {
            return 'Campo obbligatorio.';
        }
        
        const trimmed = text.trim();
        
        if (trimmed.length < minLength) {
            return `Minimo ${minLength} caratteri richiesti.`;
        }
        
        if (trimmed.length > maxLength) {
            return `Massimo ${maxLength} caratteri consentiti.`;
        }
        
        return true;
    }

    /**
     * Validate numeric input
     * @param {*} value Value to validate
     * @param {number} min Minimum value
     * @param {number} max Maximum value
     * @param {boolean} required Whether field is required
     * @returns {boolean|string} True if valid, error message if invalid
     */
    static numeric(value, min = null, max = null, required = false) {
        if (value === '' || value === null || value === undefined) {
            return required ? 'Campo numerico obbligatorio.' : true;
        }
        
        const num = Number(value);
        
        if (isNaN(num)) {
            return 'Valore numerico non valido.';
        }
        
        if (min !== null && num < min) {
            return `Valore minimo: ${min}`;
        }
        
        if (max !== null && num > max) {
            return `Valore massimo: ${max}`;
        }
        
        return true;
    }

    /**
     * Validate coordinates (latitude, longitude)
     * @param {number} lat Latitude
     * @param {number} lng Longitude
     * @returns {boolean|string} True if valid, error message if invalid
     */
    static coordinates(lat, lng) {
        const latResult = this.numeric(lat, -90, 90, true);
        if (latResult !== true) {
            return `Latitudine non valida: ${latResult}`;
        }
        
        const lngResult = this.numeric(lng, -180, 180, true);
        if (lngResult !== true) {
            return `Longitudine non valida: ${lngResult}`;
        }
        
        return true;
    }

    /**
     * Validate date string
     * @param {string} dateStr Date string
     * @param {boolean} required Whether date is required
     * @returns {boolean|string} True if valid, error message if invalid
     */
    static date(dateStr, required = false) {
        if (!dateStr) {
            return required ? 'Data è obbligatoria.' : true;
        }
        
        const date = new Date(dateStr);
        
        if (isNaN(date.getTime())) {
            return 'Data non valida.';
        }
        
        return true;
    }

    /**
     * Validate API key format based on provider
     * @param {string} key API key
     * @param {string} provider Provider name
     * @returns {boolean|string} True if valid, error message if invalid
     */
    static apiKey(key, provider) {
        if (!key || typeof key !== 'string') {
            return 'API Key è obbligatoria.';
        }
        
        const trimmed = key.trim();
        
        switch (provider) {
            case 'stripe':
                if (!(/^(sk_|pk_)/.test(trimmed))) {
                    return 'Stripe API Key deve iniziare con sk_ o pk_';
                }
                break;
                
            case 'mailchimp':
                if (!/^[a-f0-9]{32}-[a-z]{2,4}[0-9]+$/.test(trimmed)) {
                    return 'Mailchimp API Key non valida (formato: xxxxxxxx-xx0)';
                }
                break;
                
            case 'google_analytics':
                if (!/^G-[A-Z0-9]{10}$/.test(trimmed)) {
                    return 'GA4 Measurement ID non valido (formato: G-XXXXXXXXXX)';
                }
                break;
                
            default:
                if (trimmed.length < 10) {
                    return 'API Key troppo corta (minimo 10 caratteri)';
                }
                break;
        }
        
        return true;
    }

    /**
     * Validate phone number
     * @param {string} phone Phone number
     * @param {boolean} required Whether phone is required
     * @returns {boolean|string} True if valid, error message if invalid
     */
    static phone(phone, required = false) {
        if (!phone || typeof phone !== 'string') {
            return required ? 'Numero di telefono è obbligatorio.' : true;
        }
        
        // Remove all non-digits for validation
        const cleaned = phone.replace(/\D/g, '');
        
        if (cleaned.length < 8 || cleaned.length > 15) {
            return 'Numero di telefono non valido (8-15 cifre).';
        }
        
        return true;
    }

    /**
     * Validate password strength
     * @param {string} password Password to validate
     * @param {number} minLength Minimum length
     * @returns {boolean|string} True if valid, error message if invalid
     */
    static password(password, minLength = 8) {
        if (!password || typeof password !== 'string') {
            return 'Password è obbligatoria.';
        }
        
        if (password.length < minLength) {
            return `Password troppo corta (minimo ${minLength} caratteri).`;
        }
        
        // Check for at least one number, one letter, and one special character
        if (!/\d/.test(password)) {
            return 'Password deve contenere almeno un numero.';
        }
        
        if (!/[a-zA-Z]/.test(password)) {
            return 'Password deve contenere almeno una lettera.';
        }
        
        if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
            return 'Password deve contenere almeno un carattere speciale.';
        }
        
        return true;
    }

    /**
     * Validate form data object
     * @param {Object} data Form data to validate
     * @param {Object} rules Validation rules
     * @returns {Object} Validation result {valid: boolean, errors: {}}
     */
    static validateForm(data, rules) {
        const errors = {};
        
        Object.keys(rules).forEach(field => {
            const value = data[field];
            const rule = rules[field];
            
            if (typeof rule === 'function') {
                const result = rule(value);
                if (result !== true) {
                    errors[field] = result;
                }
            } else if (Array.isArray(rule)) {
                // Multiple validators
                for (const validator of rule) {
                    const result = validator(value);
                    if (result !== true) {
                        errors[field] = result;
                        break; // Stop at first error
                    }
                }
            }
        });
        
        return {
            valid: Object.keys(errors).length === 0,
            errors: errors
        };
    }

    /**
     * Display validation errors on form
     * @param {Object} errors Validation errors
     * @param {HTMLElement} form Form element
     */
    static displayErrors(errors, form) {
        // Clear previous errors
        form.querySelectorAll('.yht-error').forEach(el => el.remove());
        form.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
        
        Object.keys(errors).forEach(field => {
            const input = form.querySelector(`[name="${field}"]`);
            if (input) {
                input.classList.add('error');
                
                const errorDiv = document.createElement('div');
                errorDiv.className = 'yht-error';
                errorDiv.textContent = errors[field];
                errorDiv.style.cssText = 'color: #dc3545; font-size: 0.875em; margin-top: 4px;';
                
                input.parentNode.insertBefore(errorDiv, input.nextSibling);
            }
        });
    }

    /**
     * Real-time validation setup for form
     * @param {HTMLElement} form Form element
     * @param {Object} rules Validation rules
     */
    static setupRealTimeValidation(form, rules) {
        Object.keys(rules).forEach(field => {
            const input = form.querySelector(`[name="${field}"]`);
            if (input) {
                const validateField = () => {
                    const rule = rules[field];
                    let result = true;
                    
                    if (typeof rule === 'function') {
                        result = rule(input.value);
                    } else if (Array.isArray(rule)) {
                        for (const validator of rule) {
                            result = validator(input.value);
                            if (result !== true) break;
                        }
                    }
                    
                    // Clear previous error
                    const existingError = input.parentNode.querySelector('.yht-error');
                    if (existingError) {
                        existingError.remove();
                    }
                    input.classList.remove('error');
                    
                    // Show error if validation failed
                    if (result !== true) {
                        input.classList.add('error');
                        
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'yht-error';
                        errorDiv.textContent = result;
                        errorDiv.style.cssText = 'color: #dc3545; font-size: 0.875em; margin-top: 4px;';
                        
                        input.parentNode.insertBefore(errorDiv, input.nextSibling);
                    }
                };
                
                input.addEventListener('blur', validateField);
                input.addEventListener('input', 
                    window.yhtCache ? 
                    window.yhtCache.debounce(validateField, 500) : 
                    validateField
                );
            }
        });
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.YHTValidators = YHTValidators;
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = YHTValidators;
}