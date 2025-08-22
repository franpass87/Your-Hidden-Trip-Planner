/**
 * YHT User Experience Enhancement Module
 * Provides dark mode, enhanced accessibility, keyboard navigation, and micro-interactions
 */

class YHTUserExperience {
    constructor() {
        this.theme = localStorage.getItem('yht-theme') || 'light';
        this.keyboardNavigation = false;
        this.reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        this.currentFocus = null;
        this.toasts = [];
        
        this.init();
    }

    init() {
        this.setupThemeToggle();
        this.setupKeyboardNavigation();
        this.setupAccessibilityFeatures();
        this.setupMicroInteractions();
        this.setupToastSystem();
        this.setupContextualHelp();
        this.initializeTheme();
    }

    /**
     * Dark mode and theme management
     */
    setupThemeToggle() {
        // Create theme toggle button if not exists
        if (!document.querySelector('.yht-theme-toggle')) {
            const toggle = document.createElement('button');
            toggle.className = 'yht-theme-toggle';
            toggle.setAttribute('aria-label', 'Cambia tema');
            toggle.innerHTML = this.theme === 'dark' ? '☀️' : '🌙';
            
            // Add to header or appropriate location
            const header = document.querySelector('.yht-header') || document.querySelector('header');
            if (header) {
                header.appendChild(toggle);
            }
            
            toggle.addEventListener('click', () => this.toggleTheme());
        }

        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!localStorage.getItem('yht-theme')) {
                this.setTheme(e.matches ? 'dark' : 'light');
            }
        });
    }

    /**
     * Toggle between light and dark themes
     */
    toggleTheme() {
        const newTheme = this.theme === 'light' ? 'dark' : 'light';
        this.setTheme(newTheme);
        
        // Show feedback
        this.showToast(`Tema ${newTheme === 'dark' ? 'scuro' : 'chiaro'} attivato`, 'info');
    }

    /**
     * Set theme and persist preference
     */
    setTheme(theme) {
        this.theme = theme;
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('yht-theme', theme);
        
        // Update toggle button
        const toggle = document.querySelector('.yht-theme-toggle');
        if (toggle) {
            toggle.innerHTML = theme === 'dark' ? '☀️' : '🌙';
            toggle.setAttribute('aria-label', `Passa al tema ${theme === 'dark' ? 'chiaro' : 'scuro'}`);
        }
        
        // Trigger theme change event
        document.dispatchEvent(new CustomEvent('yht-theme-changed', { detail: { theme } }));
    }

    /**
     * Initialize theme on page load
     */
    initializeTheme() {
        // Use stored preference or system preference
        if (!localStorage.getItem('yht-theme')) {
            const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            this.theme = systemDark ? 'dark' : 'light';
        }
        
        this.setTheme(this.theme);
    }

    /**
     * Enhanced keyboard navigation
     */
    setupKeyboardNavigation() {
        // Detect keyboard usage
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                this.keyboardNavigation = true;
                document.body.classList.add('yht-keyboard-nav');
            }
        });

        document.addEventListener('mousedown', () => {
            this.keyboardNavigation = false;
            document.body.classList.remove('yht-keyboard-nav');
        });

        // Skip links for accessibility
        this.addSkipLinks();

        // Enhanced form navigation
        this.setupFormNavigation();

        // Escape key handling
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.handleEscapeKey();
            }
        });
    }

    /**
     * Add skip links for screen readers
     */
    addSkipLinks() {
        const skipLinks = document.createElement('div');
        skipLinks.className = 'yht-skip-links';
        skipLinks.innerHTML = `
            <a href="#main-content" class="yht-skip-link">Vai al contenuto principale</a>
            <a href="#yht-form" class="yht-skip-link">Vai al modulo</a>
        `;
        
        document.body.insertBefore(skipLinks, document.body.firstChild);
    }

    /**
     * Enhanced form navigation with arrow keys
     */
    setupFormNavigation() {
        document.addEventListener('keydown', (e) => {
            if (!e.target.closest('.yht-form')) return;

            const form = e.target.closest('.yht-form');
            const inputs = form.querySelectorAll('input, select, textarea, button');
            const currentIndex = Array.from(inputs).indexOf(e.target);

            switch (e.key) {
                case 'ArrowDown':
                    if (e.ctrlKey && currentIndex < inputs.length - 1) {
                        e.preventDefault();
                        inputs[currentIndex + 1].focus();
                    }
                    break;
                case 'ArrowUp':
                    if (e.ctrlKey && currentIndex > 0) {
                        e.preventDefault();
                        inputs[currentIndex - 1].focus();
                    }
                    break;
                case 'Enter':
                    if (e.target.type === 'button' || e.target.tagName === 'BUTTON') {
                        e.target.click();
                    }
                    break;
            }
        });
    }

    /**
     * Handle escape key press
     */
    handleEscapeKey() {
        // Close modals
        const openModal = document.querySelector('.yht-modal.active');
        if (openModal) {
            this.closeModal(openModal);
            return;
        }

        // Close dropdowns
        const openDropdown = document.querySelector('.yht-dropdown.open');
        if (openDropdown) {
            openDropdown.classList.remove('open');
            return;
        }

        // Clear search
        const searchInput = document.querySelector('.yht-search-input:focus');
        if (searchInput && searchInput.value) {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
            return;
        }
    }

    /**
     * Setup accessibility features
     */
    setupAccessibilityFeatures() {
        // Add ARIA labels to interactive elements
        document.querySelectorAll('.yht-btn').forEach(btn => {
            if (!btn.getAttribute('aria-label') && !btn.textContent.trim()) {
                btn.setAttribute('aria-label', 'Pulsante');
            }
        });

        // Add role attributes where missing
        document.querySelectorAll('.yht-tabs').forEach(tabs => {
            tabs.setAttribute('role', 'tablist');
            tabs.querySelectorAll('.yht-tab').forEach((tab, index) => {
                tab.setAttribute('role', 'tab');
                tab.setAttribute('tabindex', index === 0 ? '0' : '-1');
                tab.setAttribute('aria-selected', index === 0 ? 'true' : 'false');
            });
        });

        // Announce form errors to screen readers
        this.setupErrorAnnouncement();

        // Focus management
        this.setupFocusManagement();
    }

    /**
     * Setup error announcement for screen readers
     */
    setupErrorAnnouncement() {
        const announcer = document.createElement('div');
        announcer.id = 'yht-aria-announcer';
        announcer.setAttribute('aria-live', 'polite');
        announcer.setAttribute('aria-atomic', 'true');
        announcer.style.cssText = 'position: absolute; left: -10000px; width: 1px; height: 1px; overflow: hidden;';
        document.body.appendChild(announcer);

        // Listen for form errors
        document.addEventListener('yht-form-error', (e) => {
            announcer.textContent = `Errore: ${e.detail.message}`;
        });
    }

    /**
     * Focus management for better accessibility
     */
    setupFocusManagement() {
        // Remember focus before modal opens
        document.addEventListener('yht-modal-open', (e) => {
            this.currentFocus = document.activeElement;
            
            // Focus first interactive element in modal
            setTimeout(() => {
                const firstFocusable = e.detail.modal.querySelector('input, select, textarea, button, [tabindex]:not([tabindex="-1"])');
                if (firstFocusable) {
                    firstFocusable.focus();
                }
            }, 100);
        });

        // Restore focus when modal closes
        document.addEventListener('yht-modal-close', () => {
            if (this.currentFocus) {
                this.currentFocus.focus();
                this.currentFocus = null;
            }
        });
    }

    /**
     * Micro-interactions and animations
     */
    setupMicroInteractions() {
        if (this.reducedMotion) return;

        // Button hover effects
        document.addEventListener('mouseover', (e) => {
            if (e.target.classList.contains('yht-btn')) {
                this.animateButton(e.target, 'hover');
            }
        });

        document.addEventListener('mouseout', (e) => {
            if (e.target.classList.contains('yht-btn')) {
                this.animateButton(e.target, 'normal');
            }
        });

        // Click ripple effect
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('yht-btn')) {
                this.createRipple(e);
            }
        });

        // Form field focus effects
        document.querySelectorAll('.yht-input').forEach(input => {
            input.addEventListener('focus', () => this.animateInput(input, 'focus'));
            input.addEventListener('blur', () => this.animateInput(input, 'blur'));
        });
    }

    /**
     * Animate button interactions
     */
    animateButton(button, state) {
        switch (state) {
            case 'hover':
                button.style.transform = 'translateY(-2px)';
                button.style.boxShadow = '0 4px 12px rgba(0,123,186,0.3)';
                break;
            case 'normal':
                button.style.transform = '';
                button.style.boxShadow = '';
                break;
        }
    }

    /**
     * Create ripple effect on click
     */
    createRipple(event) {
        const button = event.target;
        const rect = button.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = event.clientX - rect.left - size / 2;
        const y = event.clientY - rect.top - size / 2;

        const ripple = document.createElement('span');
        ripple.className = 'yht-ripple';
        ripple.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            left: ${x}px;
            top: ${y}px;
            background: rgba(255,255,255,0.3);
            border-radius: 50%;
            transform: scale(0);
            animation: yht-ripple 0.6s ease-out;
            pointer-events: none;
        `;

        button.style.position = 'relative';
        button.style.overflow = 'hidden';
        button.appendChild(ripple);

        setTimeout(() => ripple.remove(), 600);
    }

    /**
     * Animate input field interactions
     */
    animateInput(input, state) {
        const container = input.parentElement;
        switch (state) {
            case 'focus':
                container.classList.add('yht-input-focused');
                input.style.borderColor = '#007cba';
                break;
            case 'blur':
                container.classList.remove('yht-input-focused');
                input.style.borderColor = '';
                break;
        }
    }

    /**
     * Toast notification system
     */
    setupToastSystem() {
        // Create toast container
        if (!document.getElementById('yht-toast-container')) {
            const container = document.createElement('div');
            container.id = 'yht-toast-container';
            container.setAttribute('role', 'status');
            container.setAttribute('aria-live', 'polite');
            document.body.appendChild(container);
        }
    }

    /**
     * Show toast notification
     */
    showToast(message, type = 'info', duration = 4000) {
        const container = document.getElementById('yht-toast-container');
        
        const toast = document.createElement('div');
        toast.className = `yht-toast yht-toast-${type}`;
        
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };
        
        toast.innerHTML = `
            <span class="yht-toast-icon">${icons[type] || 'ℹ️'}</span>
            <span class="yht-toast-message">${message}</span>
            <button class="yht-toast-close" aria-label="Chiudi notifica">×</button>
        `;

        // Add close functionality
        toast.querySelector('.yht-toast-close').addEventListener('click', () => {
            this.removeToast(toast);
        });

        container.appendChild(toast);
        this.toasts.push(toast);

        // Animate in
        setTimeout(() => toast.classList.add('yht-toast-show'), 100);

        // Auto remove
        if (duration > 0) {
            setTimeout(() => this.removeToast(toast), duration);
        }

        return toast;
    }

    /**
     * Remove toast notification
     */
    removeToast(toast) {
        toast.classList.add('yht-toast-hide');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
            const index = this.toasts.indexOf(toast);
            if (index > -1) {
                this.toasts.splice(index, 1);
            }
        }, 300);
    }

    /**
     * Contextual help system
     */
    setupContextualHelp() {
        // Add help buttons to complex form sections
        document.querySelectorAll('[data-help]').forEach(element => {
            const helpButton = document.createElement('button');
            helpButton.className = 'yht-help-btn';
            helpButton.innerHTML = '?';
            helpButton.setAttribute('aria-label', 'Mostra aiuto');
            helpButton.setAttribute('type', 'button');
            
            helpButton.addEventListener('click', () => {
                this.showHelp(element.dataset.help, helpButton);
            });
            
            element.appendChild(helpButton);
        });
    }

    /**
     * Show contextual help
     */
    showHelp(helpText, trigger) {
        // Remove existing help tooltips
        document.querySelectorAll('.yht-help-tooltip').forEach(tooltip => tooltip.remove());

        const tooltip = document.createElement('div');
        tooltip.className = 'yht-help-tooltip';
        tooltip.innerHTML = `
            <div class="yht-help-content">
                ${helpText}
                <button class="yht-help-close">Chiudi</button>
            </div>
        `;

        // Position tooltip
        const rect = trigger.getBoundingClientRect();
        tooltip.style.cssText = `
            position: absolute;
            top: ${rect.bottom + window.scrollY + 5}px;
            left: ${rect.left + window.scrollX}px;
            z-index: 1000;
        `;

        document.body.appendChild(tooltip);

        // Close functionality
        tooltip.querySelector('.yht-help-close').addEventListener('click', () => {
            tooltip.remove();
        });

        // Close on click outside
        setTimeout(() => {
            document.addEventListener('click', function closeTooltip(e) {
                if (!tooltip.contains(e.target) && e.target !== trigger) {
                    tooltip.remove();
                    document.removeEventListener('click', closeTooltip);
                }
            });
        }, 100);
    }

    /**
     * Close modal with proper cleanup
     */
    closeModal(modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        
        // Dispatch close event
        document.dispatchEvent(new CustomEvent('yht-modal-close', { detail: { modal } }));
    }

    /**
     * Get current user preferences
     */
    getUserPreferences() {
        return {
            theme: this.theme,
            reducedMotion: this.reducedMotion,
            keyboardNavigation: this.keyboardNavigation
        };
    }

    /**
     * Update accessibility based on user capabilities
     */
    updateAccessibility(preferences = {}) {
        if (preferences.reducedMotion !== undefined) {
            this.reducedMotion = preferences.reducedMotion;
            document.body.classList.toggle('yht-reduced-motion', this.reducedMotion);
        }

        if (preferences.highContrast) {
            document.body.classList.add('yht-high-contrast');
        }

        if (preferences.largeText) {
            document.body.classList.add('yht-large-text');
        }
    }
}

// Initialize UX enhancements
document.addEventListener('DOMContentLoaded', () => {
    window.yhtUX = new YHTUserExperience();
});

// CSS Styles for UX enhancements
if (!document.getElementById('yht-ux-styles')) {
    const styles = document.createElement('style');
    styles.id = 'yht-ux-styles';
    styles.textContent = `
        /* Theme Variables */
        :root {
            --yht-primary: #007cba;
            --yht-secondary: #6c757d;
            --yht-success: #28a745;
            --yht-danger: #dc3545;
            --yht-warning: #ffc107;
            --yht-info: #17a2b8;
            --yht-light: #f8f9fa;
            --yht-dark: #343a40;
            --yht-bg: #ffffff;
            --yht-text: #333333;
            --yht-border: #e0e0e0;
        }
        
        [data-theme="dark"] {
            --yht-bg: #1a1a1a;
            --yht-text: #e0e0e0;
            --yht-border: #404040;
            --yht-light: #2d2d2d;
            --yht-dark: #f8f9fa;
        }
        
        /* Theme toggle */
        .yht-theme-toggle {
            background: none;
            border: 1px solid var(--yht-border);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .yht-theme-toggle:hover {
            background: var(--yht-light);
            transform: rotate(180deg);
        }
        
        /* Skip links */
        .yht-skip-links {
            position: absolute;
            top: -100px;
            left: 0;
            z-index: 999999;
        }
        
        .yht-skip-link {
            display: inline-block;
            padding: 8px 16px;
            background: var(--yht-primary);
            color: white;
            text-decoration: none;
            border-radius: 0 0 4px 0;
        }
        
        .yht-skip-link:focus {
            top: 0;
            position: relative;
        }
        
        /* Keyboard navigation */
        .yht-keyboard-nav *:focus {
            outline: 2px solid var(--yht-primary) !important;
            outline-offset: 2px !important;
        }
        
        /* Toast notifications */
        #yht-toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 999999;
            max-width: 400px;
        }
        
        .yht-toast {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            margin-bottom: 10px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-left: 4px solid var(--yht-info);
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
        }
        
        .yht-toast-show {
            opacity: 1;
            transform: translateX(0);
        }
        
        .yht-toast-hide {
            opacity: 0;
            transform: translateX(100%);
        }
        
        .yht-toast-success { border-left-color: var(--yht-success); }
        .yht-toast-error { border-left-color: var(--yht-danger); }
        .yht-toast-warning { border-left-color: var(--yht-warning); }
        
        .yht-toast-icon {
            margin-right: 8px;
            font-size: 16px;
        }
        
        .yht-toast-message {
            flex: 1;
        }
        
        .yht-toast-close {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            padding: 0;
            margin-left: 8px;
            opacity: 0.6;
        }
        
        .yht-toast-close:hover {
            opacity: 1;
        }
        
        /* Ripple effect */
        @keyframes yht-ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        
        /* Input animations */
        .yht-input-focused {
            transform: translateY(-2px);
            transition: transform 0.2s ease;
        }
        
        /* Help system */
        .yht-help-btn {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--yht-info);
            color: white;
            border: none;
            font-size: 12px;
            cursor: pointer;
            margin-left: 8px;
        }
        
        .yht-help-tooltip {
            background: var(--yht-dark);
            color: var(--yht-light);
            padding: 12px;
            border-radius: 8px;
            max-width: 300px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        /* Accessibility features */
        .yht-reduced-motion * {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }
        
        .yht-high-contrast {
            filter: contrast(150%);
        }
        
        .yht-large-text {
            font-size: 120%;
        }
        
        /* Dark theme specific */
        [data-theme="dark"] body {
            background: var(--yht-bg);
            color: var(--yht-text);
        }
        
        [data-theme="dark"] .yht-toast {
            background: var(--yht-light);
            color: var(--yht-text);
        }
        
        [data-theme="dark"] .yht-help-tooltip {
            background: var(--yht-light);
            color: var(--yht-text);
        }
        
        @media (prefers-reduced-motion: reduce) {
            .yht-theme-toggle:hover {
                transform: none;
            }
        }
    `;
    document.head.appendChild(styles);
}