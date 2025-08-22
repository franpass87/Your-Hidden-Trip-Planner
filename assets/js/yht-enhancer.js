/**
 * Your Hidden Trip - Enhanced Frontend JavaScript
 * Modern interactions and functionality
 */

class YHTEnhancer {
    constructor() {
        this.audioEnabled = false; // Initialize audio support
        this.init();
    }

    init() {
        this.setupThemeToggle();
        this.setupCardAnimations();
        this.setupFormValidation();
        this.setupLoadingStates();
        this.setupTooltips();
        this.setupSmoothScrolling();
        this.setupKeyboardNavigation();
        this.setupProgressiveEnhancement();
        this.setupNotifications();
    }

    // Dark/Light Theme Toggle
    setupThemeToggle() {
        const toggle = this.createThemeToggle();
        const wrap = document.querySelector('.yht-wrap');
        
        if (wrap && !document.querySelector('.yht-theme-toggle')) {
            wrap.appendChild(toggle);
        }

        // Check for saved theme preference or default to auto
        const savedTheme = localStorage.getItem('yht-theme');
        if (savedTheme) {
            document.body.classList.toggle('yht-dark-mode', savedTheme === 'dark');
            this.updateThemeIcon(toggle, savedTheme === 'dark');
        } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.body.classList.add('yht-dark-mode');
            this.updateThemeIcon(toggle, true);
        }
    }

    createThemeToggle() {
        const toggle = document.createElement('button');
        toggle.className = 'yht-theme-toggle';
        toggle.setAttribute('aria-label', 'Cambia tema');
        toggle.innerHTML = '🌙';
        
        toggle.addEventListener('click', () => {
            const isDark = document.body.classList.toggle('yht-dark-mode');
            localStorage.setItem('yht-theme', isDark ? 'dark' : 'light');
            this.updateThemeIcon(toggle, isDark);
            
            // Add a subtle animation
            toggle.style.transform = 'scale(0.9)';
            setTimeout(() => {
                toggle.style.transform = 'scale(1)';
            }, 150);
        });

        return toggle;
    }

    updateThemeIcon(toggle, isDark) {
        toggle.innerHTML = isDark ? '☀️' : '🌙';
    }

    // Enhanced Card Animations
    setupCardAnimations() {
        const cards = document.querySelectorAll('.yht-card');
        
        cards.forEach((card, index) => {
            // Stagger entrance animations
            card.style.animationDelay = `${index * 0.1}s`;
            
            // Add hover sound feedback (optional)
            card.addEventListener('mouseenter', () => {
                this.playHoverSound();
            });

            // Add ripple effect on click
            card.addEventListener('click', (e) => {
                this.createRipple(e, card);
            });
        });
    }

    createRipple(event, element) {
        const ripple = document.createElement('span');
        const rect = element.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = event.clientX - rect.left - size / 2;
        const y = event.clientY - rect.top - size / 2;
        
        ripple.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            left: ${x}px;
            top: ${y}px;
            background: rgba(16, 185, 129, 0.3);
            border-radius: 50%;
            transform: scale(0);
            animation: ripple 0.6s ease-out;
            pointer-events: none;
            z-index: 1;
        `;
        
        element.style.position = 'relative';
        element.appendChild(ripple);
        
        setTimeout(() => {
            ripple.remove();
        }, 600);
    }

    playHoverSound() {
        // Optional: Add subtle audio feedback
        if (this.audioEnabled) {
            try {
                // Create a simple beep sound programmatically
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                oscillator.frequency.value = 800;
                oscillator.type = 'sine';
                gainNode.gain.value = 0.1;
                
                oscillator.start();
                oscillator.stop(audioContext.currentTime + 0.1);
            } catch (error) {
                console.debug('Audio feedback not supported:', error.message);
            }
        }
    }

    // Enhanced Form Validation
    setupFormValidation() {
        const inputs = document.querySelectorAll('.yht-input');
        
        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => this.clearValidationError(input));
        });
    }

    validateField(field) {
        const value = field.value.trim();
        const fieldType = field.type || field.tagName.toLowerCase();
        
        let isValid = true;
        let errorMessage = '';

        // Basic validation rules
        if (field.hasAttribute('required') && !value) {
            isValid = false;
            errorMessage = 'Questo campo è obbligatorio';
        } else if (fieldType === 'email' && value && !this.isValidEmail(value)) {
            isValid = false;
            errorMessage = 'Inserisci un indirizzo email valido';
        } else if (fieldType === 'tel' && value && !this.isValidPhone(value)) {
            isValid = false;
            errorMessage = 'Inserisci un numero di telefono valido';
        }

        this.showValidationFeedback(field, isValid, errorMessage);
        return isValid;
    }

    isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    isValidPhone(phone) {
        const re = /^[\+]?[1-9][\d]{0,15}$/;
        return re.test(phone.replace(/\s/g, ''));
    }

    showValidationFeedback(field, isValid, errorMessage) {
        // Remove existing feedback
        const existingFeedback = field.parentNode.querySelector('.yht-field-feedback');
        if (existingFeedback) {
            existingFeedback.remove();
        }

        // Update field styling
        field.style.borderColor = isValid ? 'var(--success)' : 'var(--danger)';

        // Add error message if invalid
        if (!isValid && errorMessage) {
            const feedback = document.createElement('div');
            feedback.className = 'yht-field-feedback yht-error';
            feedback.textContent = errorMessage;
            feedback.setAttribute('data-show', 'true');
            field.parentNode.appendChild(feedback);
        }
    }

    clearValidationError(field) {
        field.style.borderColor = 'var(--line)';
        const feedback = field.parentNode.querySelector('.yht-field-feedback');
        if (feedback) {
            feedback.remove();
        }
    }

    // Loading States
    setupLoadingStates() {
        const buttons = document.querySelectorAll('.yht-btn');
        
        buttons.forEach(button => {
            button.addEventListener('click', (e) => {
                if (button.type === 'submit' || button.classList.contains('yht-submit')) {
                    this.showButtonLoading(button);
                }
            });
        });
    }

    showButtonLoading(button, duration = 2000) {
        const originalText = button.innerHTML;
        button.innerHTML = '<span class="yht-loading"><span class="yht-spinner"></span>Caricamento...</span>';
        button.disabled = true;

        setTimeout(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        }, duration);
    }

    // Tooltips
    setupTooltips() {
        const tooltipElements = document.querySelectorAll('[data-tooltip]');
        
        tooltipElements.forEach(element => {
            element.addEventListener('mouseenter', (e) => this.showTooltip(e));
            element.addEventListener('mouseleave', () => this.hideTooltip());
        });
    }

    showTooltip(event) {
        const text = event.target.getAttribute('data-tooltip');
        const tooltip = document.createElement('div');
        tooltip.className = 'yht-tooltip';
        tooltip.textContent = text;
        
        document.body.appendChild(tooltip);
        
        const rect = event.target.getBoundingClientRect();
        tooltip.style.left = rect.left + rect.width / 2 - tooltip.offsetWidth / 2 + 'px';
        tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
    }

    hideTooltip() {
        const tooltip = document.querySelector('.yht-tooltip');
        if (tooltip) {
            tooltip.remove();
        }
    }

    // Smooth Scrolling
    setupSmoothScrolling() {
        const links = document.querySelectorAll('a[href^="#"]');
        
        links.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const target = document.querySelector(link.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }

    // Keyboard Navigation
    setupKeyboardNavigation() {
        document.addEventListener('keydown', (e) => {
            const activeElement = document.activeElement;
            
            // Navigate cards with arrow keys
            if (activeElement && activeElement.classList.contains('yht-card')) {
                const cards = Array.from(document.querySelectorAll('.yht-card'));
                const currentIndex = cards.indexOf(activeElement);
                let newIndex = currentIndex;
                
                switch (e.key) {
                    case 'ArrowRight':
                    case 'ArrowDown':
                        newIndex = (currentIndex + 1) % cards.length;
                        break;
                    case 'ArrowLeft':
                    case 'ArrowUp':
                        newIndex = (currentIndex - 1 + cards.length) % cards.length;
                        break;
                    case 'Enter':
                    case ' ':
                        e.preventDefault();
                        activeElement.click();
                        return;
                }
                
                if (newIndex !== currentIndex) {
                    e.preventDefault();
                    cards[newIndex].focus();
                }
            }
        });
    }

    // Progressive Enhancement
    setupProgressiveEnhancement() {
        // Add modern browser features detection
        if ('IntersectionObserver' in window) {
            this.setupScrollAnimations();
        }
        
        if ('serviceWorker' in navigator) {
            this.registerServiceWorker();
        }
        
        // Add connection-aware loading
        if ('connection' in navigator) {
            this.optimizeForConnection();
        }
    }

    setupScrollAnimations() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animationDelay = '0s';
                    entry.target.classList.add('yht-animate-in');
                }
            });
        }, { threshold: 0.1 });

        const animateElements = document.querySelectorAll('.yht-card, .yht-summary, .testimonial');
        animateElements.forEach(el => observer.observe(el));
    }

    registerServiceWorker() {
        navigator.serviceWorker.register('/yht-sw.js')
            .then(registration => {
                console.log('YHT Service Worker registered');
            })
            .catch(error => {
                console.log('YHT Service Worker registration failed');
            });
    }

    optimizeForConnection() {
        const connection = navigator.connection;
        if (connection && connection.effectiveType === 'slow-2g') {
            // Reduce animations for slow connections
            document.body.classList.add('yht-reduced-motion');
        }
    }

    // Enhanced Notifications
    setupNotifications() {
        this.createNotificationContainer();
    }

    createNotificationContainer() {
        if (!document.querySelector('.yht-notifications')) {
            const container = document.createElement('div');
            container.className = 'yht-notifications';
            document.body.appendChild(container);
        }
    }

    showNotification(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `yht-notification yht-notification--${type}`;
        
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };
        
        notification.innerHTML = `
            <span class="yht-notification__icon">${icons[type] || icons.info}</span>
            <span class="yht-notification__message">${message}</span>
            <button class="yht-notification__close" aria-label="Chiudi">×</button>
        `;
        
        const container = document.querySelector('.yht-notifications');
        if (!container) {
            console.warn('YHT notification container not found');
            return;
        }
        container.appendChild(notification);
        
        // Auto-remove after duration
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, duration);
        
        // Manual close
        notification.querySelector('.yht-notification__close').addEventListener('click', () => {
            notification.remove();
        });
        
        return notification;
    }

    // Utility methods for external use
    updateProgress(step, totalSteps) {
        const progressBar = document.querySelector('.yht-progressbar > i');
        if (progressBar) {
            const percentage = (step / totalSteps) * 100;
            progressBar.style.width = `${percentage}%`;
        }
    }

    highlightStep(stepNumber) {
        const steps = document.querySelectorAll('.yht-step');
        steps.forEach((step, index) => {
            if (index < stepNumber - 1) {
                step.setAttribute('data-done', 'true');
                step.setAttribute('data-active', 'false');
            } else if (index === stepNumber - 1) {
                step.setAttribute('data-active', 'true');
                step.setAttribute('data-done', 'false');
            } else {
                step.setAttribute('data-active', 'false');
                step.setAttribute('data-done', 'false');
            }
        });
    }

    // Wishlist functionality
    initWishlist() {
        const wishlistButtons = document.querySelectorAll('.yht-wishlist-btn');
        
        wishlistButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                const itemId = button.dataset.itemId;
                const isWishlisted = this.toggleWishlist(itemId);
                
                button.innerHTML = isWishlisted ? '❤️' : '🤍';
                button.setAttribute('aria-label', 
                    isWishlisted ? 'Rimuovi dai preferiti' : 'Aggiungi ai preferiti'
                );
                
                this.showNotification(
                    isWishlisted ? 'Aggiunto ai preferiti!' : 'Rimosso dai preferiti',
                    'success',
                    3000
                );
            });
        });
    }

    toggleWishlist(itemId) {
        let wishlist = JSON.parse(localStorage.getItem('yht-wishlist') || '[]');
        const index = wishlist.indexOf(itemId);
        
        if (index > -1) {
            wishlist.splice(index, 1);
        } else {
            wishlist.push(itemId);
        }
        
        localStorage.setItem('yht-wishlist', JSON.stringify(wishlist));
        return index === -1;
    }

    // Share functionality
    initShareButtons() {
        const shareButtons = document.querySelectorAll('.yht-share-btn');
        
        shareButtons.forEach(button => {
            button.addEventListener('click', async () => {
                const shareData = {
                    title: 'Il Mio Viaggio Nascosto',
                    text: 'Scopri questo incredibile itinerario!',
                    url: window.location.href
                };
                
                if (navigator.share) {
                    try {
                        await navigator.share(shareData);
                        this.showNotification('Condiviso con successo!', 'success');
                    } catch (error) {
                        this.fallbackShare(shareData);
                    }
                } else {
                    this.fallbackShare(shareData);
                }
            });
        });
    }

    fallbackShare(shareData) {
        // Copy to clipboard as fallback
        navigator.clipboard.writeText(shareData.url).then(() => {
            this.showNotification('Link copiato negli appunti!', 'success');
        }).catch(() => {
            this.showNotification('Errore nella condivisione', 'error');
        });
    }
}

// Enhanced CSS for new features (to be added to the CSS file)
const additionalCSS = `
.yht-animate-in {
    animation: slideInUp 0.6s ease forwards;
}

.yht-reduced-motion * {
    animation-duration: 0.01ms !important;
    transition-duration: 0.01ms !important;
}

.yht-tooltip {
    position: absolute;
    background: var(--dark-card);
    color: var(--dark-text);
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 0.85rem;
    z-index: 1000;
    pointer-events: none;
    opacity: 0;
    animation: fadeIn 0.2s ease forwards;
}

.yht-notifications {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1000;
    max-width: 400px;
}

.yht-notification {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    margin-bottom: 12px;
    border-radius: var(--radius);
    box-shadow: var(--shadow-lg);
    animation: slideInRight 0.3s ease;
}

.yht-notification--success {
    background: var(--success);
    color: white;
}

.yht-notification--error {
    background: var(--danger);
    color: white;
}

.yht-notification--warning {
    background: var(--warning);
    color: white;
}

.yht-notification--info {
    background: var(--info);
    color: white;
}

.yht-notification__close {
    background: none;
    border: none;
    color: currentColor;
    font-size: 1.2rem;
    cursor: pointer;
    margin-left: auto;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.yht-wishlist-btn, .yht-share-btn {
    position: absolute;
    top: 12px;
    right: 12px;
    background: rgba(255, 255, 255, 0.9);
    border: none;
    border-radius: 50%;
    width: 36px;
    height: 36px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    transition: var(--transition);
    backdrop-filter: blur(10px);
}

.yht-wishlist-btn:hover, .yht-share-btn:hover {
    transform: scale(1.1);
    background: rgba(255, 255, 255, 1);
}

.yht-share-btn {
    right: 56px;
}
`;

// Add additional CSS to page
const style = document.createElement('style');
style.textContent = additionalCSS;
document.head.appendChild(style);

// Initialize enhancer when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.yhtEnhancer = new YHTEnhancer();
});

// Export for external use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = YHTEnhancer;
}