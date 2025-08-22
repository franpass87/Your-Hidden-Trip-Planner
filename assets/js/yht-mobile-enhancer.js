/**
 * Mobile-First Experience Enhancer
 * Creates touch-friendly, immersive mobile experience
 * 
 * @package YourHiddenTrip
 * @version 6.3
 */

class YHTMobileEnhancer {
    constructor() {
        this.touchStartX = 0;
        this.touchStartY = 0;
        this.isMobile = this.detectMobile();
        this.isIOS = this.detectIOS();
        this.hasTouch = 'ontouchstart' in window;
        this.init();
    }

    init() {
        if (this.isMobile || this.hasTouch) {
            this.setupTouchGestures();
            this.setupMobileOptimizations();
            this.setupPWAFeatures();
            this.setupOfflineSupport();
            this.setupMobileAnimations();
            this.setupVibrationFeedback();
        }
    }

    /**
     * Detect if device is mobile
     */
    detectMobile() {
        return window.innerWidth <= 768 || /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }

    /**
     * Detect if device is iOS
     */
    detectIOS() {
        return /iPad|iPhone|iPod/.test(navigator.userAgent);
    }

    /**
     * Setup touch gestures for navigation
     */
    setupTouchGestures() {
        const stepViews = document.querySelectorAll('.yht-stepview');
        
        stepViews.forEach((stepView, index) => {
            this.addTouchListeners(stepView, index);
        });

        // Swipe between suggestion cards
        const suggestionGrid = document.querySelector('.yht-suggestions-grid');
        if (suggestionGrid) {
            this.enableCardSwipe(suggestionGrid);
        }

        // Pull to refresh simulation
        this.setupPullToRefresh();
    }

    /**
     * Add touch listeners to step views
     */
    addTouchListeners(element, stepIndex) {
        let startX, startY, startTime;

        element.addEventListener('touchstart', (e) => {
            const touch = e.touches[0];
            startX = touch.clientX;
            startY = touch.clientY;
            startTime = Date.now();
        }, { passive: true });

        element.addEventListener('touchmove', (e) => {
            if (!startX || !startY) return;
            
            const touch = e.touches[0];
            const diffX = startX - touch.clientX;
            const diffY = startY - touch.clientY;
            
            // Prevent default if horizontal swipe is detected
            if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 30) {
                e.preventDefault();
            }
        }, { passive: false });

        element.addEventListener('touchend', (e) => {
            if (!startX || !startY) return;
            
            const touch = e.changedTouches[0];
            const diffX = startX - touch.clientX;
            const diffY = startY - touch.clientY;
            const diffTime = Date.now() - startTime;
            
            // Swipe detection (minimum 50px movement, max 500ms duration)
            if (Math.abs(diffX) > 50 && Math.abs(diffX) > Math.abs(diffY) && diffTime < 500) {
                if (diffX > 0) {
                    // Swipe left - next step
                    this.navigateToStep(stepIndex + 1);
                } else {
                    // Swipe right - previous step
                    this.navigateToStep(stepIndex - 1);
                }
                
                // Haptic feedback
                this.vibrate(50);
            }
            
            startX = startY = null;
        }, { passive: true });
    }

    /**
     * Navigate to specific step
     */
    navigateToStep(targetStep) {
        const button = document.querySelector(`[data-step="${targetStep}"]`);
        if (button && !button.disabled) {
            button.click();
            
            // Add visual feedback
            button.style.transform = 'scale(0.95)';
            setTimeout(() => {
                button.style.transform = 'scale(1)';
            }, 150);
        }
    }

    /**
     * Enable card swipe functionality
     */
    enableCardSwipe(container) {
        let isDown = false;
        let startX;
        let scrollLeft;

        container.addEventListener('touchstart', (e) => {
            isDown = true;
            startX = e.touches[0].pageX - container.offsetLeft;
            scrollLeft = container.scrollLeft;
        }, { passive: true });

        container.addEventListener('touchmove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.touches[0].pageX - container.offsetLeft;
            const walk = (x - startX) * 2;
            container.scrollLeft = scrollLeft - walk;
        }, { passive: false });

        container.addEventListener('touchend', () => {
            isDown = false;
        }, { passive: true });
    }

    /**
     * Setup mobile-specific optimizations
     */
    setupMobileOptimizations() {
        // Optimize viewport meta tag
        this.optimizeViewport();
        
        // Add mobile-specific classes
        document.body.classList.add('yht-mobile');
        if (this.isIOS) {
            document.body.classList.add('yht-ios');
        }
        
        // Optimize form inputs for mobile
        this.optimizeMobileInputs();
        
        // Add mobile navigation aids
        this.addMobileNavigationAids();
        
        // Optimize images for mobile
        this.optimizeImagesForMobile();
    }

    /**
     * Optimize viewport for mobile
     */
    optimizeViewport() {
        let viewport = document.querySelector('meta[name="viewport"]');
        if (!viewport) {
            viewport = document.createElement('meta');
            viewport.name = 'viewport';
            document.head.appendChild(viewport);
        }
        
        viewport.content = 'width=device-width, initial-scale=1.0, user-scalable=yes, maximum-scale=2.0';
        
        // Add theme color for mobile browsers
        let themeColor = document.querySelector('meta[name="theme-color"]');
        if (!themeColor) {
            themeColor = document.createElement('meta');
            themeColor.name = 'theme-color';
            themeColor.content = '#10b981';
            document.head.appendChild(themeColor);
        }
    }

    /**
     * Optimize form inputs for mobile
     */
    optimizeMobileInputs() {
        const inputs = document.querySelectorAll('.yht-input');
        
        inputs.forEach(input => {
            // Add better touch targets
            input.style.minHeight = '44px';
            input.style.fontSize = '16px'; // Prevents zoom on iOS
            
            // Add input type optimizations
            if (input.type === 'email') {
                input.setAttribute('inputmode', 'email');
                input.setAttribute('autocomplete', 'email');
            }
            
            if (input.type === 'tel') {
                input.setAttribute('inputmode', 'tel');
                input.setAttribute('autocomplete', 'tel');
            }
            
            // Add focus enhancements
            input.addEventListener('focus', () => {
                input.classList.add('mobile-focused');
                this.scrollIntoViewIfNeeded(input);
            });
            
            input.addEventListener('blur', () => {
                input.classList.remove('mobile-focused');
            });
        });
    }

    /**
     * Add mobile navigation aids
     */
    addMobileNavigationAids() {
        // Add floating action button for quick actions
        const fab = this.createFloatingActionButton();
        document.body.appendChild(fab);
        
        // Add progress indicator
        const progressIndicator = this.createMobileProgressIndicator();
        const wrap = document.querySelector('.yht-wrap');
        if (wrap) {
            wrap.appendChild(progressIndicator);
        }
        
        // Add quick step navigation
        const quickNav = this.createQuickStepNavigation();
        document.body.appendChild(quickNav);
    }

    /**
     * Create floating action button
     */
    createFloatingActionButton() {
        const fab = document.createElement('div');
        fab.className = 'yht-fab';
        fab.innerHTML = `
            <button class="fab-main" aria-label="Azioni rapide">
                <span class="fab-icon">✨</span>
            </button>
            <div class="fab-menu">
                <button class="fab-item" data-action="save" aria-label="Salva progresso">
                    <span class="fab-icon">💾</span>
                    <span class="fab-label">Salva</span>
                </button>
                <button class="fab-item" data-action="share" aria-label="Condividi">
                    <span class="fab-icon">📤</span>
                    <span class="fab-label">Condividi</span>
                </button>
                <button class="fab-item" data-action="help" aria-label="Aiuto">
                    <span class="fab-icon">❓</span>
                    <span class="fab-label">Aiuto</span>
                </button>
            </div>
        `;
        
        // Add click handlers
        const fabMain = fab.querySelector('.fab-main');
        const fabMenu = fab.querySelector('.fab-menu');
        
        fabMain.addEventListener('click', () => {
            fab.classList.toggle('fab-open');
            this.vibrate(30);
        });
        
        // Handle fab item clicks
        fab.querySelectorAll('.fab-item').forEach(item => {
            item.addEventListener('click', () => {
                const action = item.dataset.action;
                this.handleFabAction(action);
                fab.classList.remove('fab-open');
                this.vibrate(50);
            });
        });
        
        return fab;
    }

    /**
     * Handle FAB action
     */
    handleFabAction(action) {
        switch (action) {
            case 'save':
                this.saveProgress();
                break;
            case 'share':
                this.shareTrip();
                break;
            case 'help':
                this.showHelp();
                break;
        }
    }

    /**
     * Create mobile progress indicator
     */
    createMobileProgressIndicator() {
        const indicator = document.createElement('div');
        indicator.className = 'yht-mobile-progress';
        indicator.innerHTML = `
            <div class="mobile-progress-bar">
                <div class="mobile-progress-fill" style="width: 0%"></div>
            </div>
            <div class="mobile-progress-text">Step 1 di 6</div>
        `;
        
        return indicator;
    }

    /**
     * Create quick step navigation
     */
    createQuickStepNavigation() {
        const quickNav = document.createElement('div');
        quickNav.className = 'yht-quick-nav';
        quickNav.innerHTML = `
            <button class="quick-nav-btn" data-direction="prev" aria-label="Step precedente">
                <span class="quick-nav-icon">‹</span>
            </button>
            <div class="quick-nav-dots">
                ${Array.from({length: 6}, (_, i) => `
                    <button class="quick-nav-dot ${i === 0 ? 'active' : ''}" data-step="${i + 1}" aria-label="Vai allo step ${i + 1}"></button>
                `).join('')}
            </div>
            <button class="quick-nav-btn" data-direction="next" aria-label="Step successivo">
                <span class="quick-nav-icon">›</span>
            </button>
        `;
        
        // Add click handlers
        quickNav.addEventListener('click', (e) => {
            if (e.target.matches('.quick-nav-btn')) {
                const direction = e.target.dataset.direction;
                const currentStep = this.getCurrentStep();
                
                if (direction === 'next') {
                    this.navigateToStep(currentStep + 1);
                } else {
                    this.navigateToStep(currentStep - 1);
                }
                
                this.vibrate(30);
            } else if (e.target.matches('.quick-nav-dot')) {
                const step = parseInt(e.target.dataset.step);
                this.navigateToStep(step);
                this.vibrate(30);
            }
        });
        
        return quickNav;
    }

    /**
     * Setup PWA features
     */
    setupPWAFeatures() {
        // Register service worker
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/wp-content/plugins/your-hidden-trip-planner/assets/sw.js')
                .then(registration => {
                    console.log('YHT Service Worker registered:', registration);
                    
                    // Handle updates
                    registration.addEventListener('updatefound', () => {
                        const newWorker = registration.installing;
                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                this.showUpdateNotification();
                            }
                        });
                    });
                })
                .catch(error => {
                    console.error('YHT Service Worker registration failed:', error);
                });
        }
        
        // Add install prompt
        this.setupInstallPrompt();
        
        // Handle offline/online events
        this.setupOfflineHandlers();
    }

    /**
     * Setup install prompt for PWA
     */
    setupInstallPrompt() {
        let deferredPrompt;
        
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            
            // Show install banner
            this.showInstallBanner(deferredPrompt);
        });
        
        window.addEventListener('appinstalled', () => {
            console.log('YHT PWA was installed');
            this.hideInstallBanner();
            
            // Track installation
            if (window.gtag) {
                gtag('event', 'app_install', {
                    event_category: 'pwa',
                    event_label: 'your_hidden_trip'
                });
            }
        });
    }

    /**
     * Show install banner
     */
    showInstallBanner(deferredPrompt) {
        const banner = document.createElement('div');
        banner.className = 'yht-install-banner';
        banner.innerHTML = `
            <div class="install-banner-content">
                <div class="install-banner-icon">📱</div>
                <div class="install-banner-text">
                    <div class="install-banner-title">Installa l'App</div>
                    <div class="install-banner-subtitle">Aggiungi alla schermata home per un accesso rapido</div>
                </div>
                <div class="install-banner-actions">
                    <button class="install-btn" data-action="install">Installa</button>
                    <button class="dismiss-btn" data-action="dismiss">×</button>
                </div>
            </div>
        `;
        
        banner.addEventListener('click', (e) => {
            if (e.target.dataset.action === 'install') {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('User accepted the install prompt');
                    }
                    deferredPrompt = null;
                    banner.remove();
                });
            } else if (e.target.dataset.action === 'dismiss') {
                banner.remove();
                localStorage.setItem('yht_install_dismissed', Date.now());
            }
        });
        
        // Check if user has dismissed recently
        const dismissed = localStorage.getItem('yht_install_dismissed');
        if (!dismissed || Date.now() - parseInt(dismissed) > 7 * 24 * 60 * 60 * 1000) {
            document.body.appendChild(banner);
        }
    }

    /**
     * Setup offline support
     */
    setupOfflineSupport() {
        this.updateOnlineStatus();
        
        window.addEventListener('online', () => {
            this.updateOnlineStatus();
            this.showNotification('🌐 Connessione ripristinata!', 'success');
            this.syncOfflineData();
        });
        
        window.addEventListener('offline', () => {
            this.updateOnlineStatus();
            this.showNotification('📱 Modalità offline attiva', 'info');
        });
    }

    /**
     * Update online status display
     */
    updateOnlineStatus() {
        const statusIndicator = this.getOrCreateStatusIndicator();
        const isOnline = navigator.onLine;
        
        statusIndicator.className = `yht-connection-status ${isOnline ? 'online' : 'offline'}`;
        statusIndicator.innerHTML = isOnline ? '🌐 Online' : '📱 Offline';
    }

    /**
     * Get or create status indicator
     */
    getOrCreateStatusIndicator() {
        let indicator = document.querySelector('.yht-connection-status');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'yht-connection-status';
            const wrap = document.querySelector('.yht-wrap');
            if (wrap) {
                wrap.appendChild(indicator);
            }
        }
        return indicator;
    }

    /**
     * Setup mobile-optimized animations
     */
    setupMobileAnimations() {
        // Reduce animations if user prefers reduced motion
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.body.classList.add('yht-reduced-motion');
        }
        
        // Add touch feedback animations
        this.addTouchFeedback();
        
        // Setup parallax scrolling for mobile
        this.setupMobileParallax();
    }

    /**
     * Add touch feedback to interactive elements
     */
    addTouchFeedback() {
        const interactiveElements = document.querySelectorAll('.yht-card, .yht-btn, .suggestion-apply');
        
        interactiveElements.forEach(element => {
            element.addEventListener('touchstart', () => {
                element.classList.add('touch-active');
            }, { passive: true });
            
            element.addEventListener('touchend', () => {
                setTimeout(() => {
                    element.classList.remove('touch-active');
                }, 150);
            }, { passive: true });
        });
    }

    /**
     * Setup vibration feedback
     */
    setupVibrationFeedback() {
        if (!navigator.vibrate) return;
        
        // Add vibration to button clicks
        document.addEventListener('click', (e) => {
            if (e.target.matches('.yht-btn') || e.target.closest('.yht-btn')) {
                this.vibrate(30);
            }
            
            if (e.target.matches('.yht-card') || e.target.closest('.yht-card')) {
                this.vibrate(20);
            }
        });
    }

    /**
     * Vibrate device if supported
     */
    vibrate(duration) {
        if (navigator.vibrate) {
            navigator.vibrate(duration);
        }
    }

    /**
     * Scroll element into view if needed (iOS keyboard handling)
     */
    scrollIntoViewIfNeeded(element) {
        setTimeout(() => {
            element.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }, 300); // Wait for keyboard animation
    }

    /**
     * Get current step number
     */
    getCurrentStep() {
        const activeStep = document.querySelector('.yht-step[aria-selected="true"]');
        return activeStep ? parseInt(activeStep.dataset.step) : 1;
    }

    /**
     * Setup pull to refresh
     */
    setupPullToRefresh() {
        let startY = 0;
        let pullDistance = 0;
        const threshold = 100;
        const wrap = document.querySelector('.yht-wrap');
        
        if (!wrap) return;
        
        const pullIndicator = document.createElement('div');
        pullIndicator.className = 'yht-pull-indicator';
        pullIndicator.innerHTML = '↓ Trascina per aggiornare';
        wrap.insertBefore(pullIndicator, wrap.firstChild);
        
        wrap.addEventListener('touchstart', (e) => {
            if (wrap.scrollTop === 0) {
                startY = e.touches[0].clientY;
            }
        }, { passive: true });
        
        wrap.addEventListener('touchmove', (e) => {
            if (startY === 0) return;
            
            const currentY = e.touches[0].clientY;
            pullDistance = Math.max(0, currentY - startY);
            
            if (pullDistance > 0) {
                pullIndicator.style.transform = `translateY(${Math.min(pullDistance, threshold)}px)`;
                pullIndicator.style.opacity = Math.min(pullDistance / threshold, 1);
                
                if (pullDistance > threshold) {
                    pullIndicator.innerHTML = '↑ Rilascia per aggiornare';
                } else {
                    pullIndicator.innerHTML = '↓ Trascina per aggiornare';
                }
            }
        }, { passive: true });
        
        wrap.addEventListener('touchend', () => {
            if (pullDistance > threshold) {
                this.refreshContent();
            }
            
            pullIndicator.style.transform = 'translateY(0)';
            pullIndicator.style.opacity = '0';
            startY = 0;
            pullDistance = 0;
        }, { passive: true });
    }

    /**
     * Refresh content
     */
    refreshContent() {
        // Simulate refresh
        this.vibrate(50);
        this.showNotification('🔄 Contenuto aggiornato!', 'success');
        
        // Refresh AI recommendations
        if (window.yhtAI) {
            window.yhtAI.createSmartSuggestions();
        }
    }

    /**
     * Show notification (integrate with existing system)
     */
    showNotification(message, type) {
        if (window.yhtEnhancer && window.yhtEnhancer.showNotification) {
            window.yhtEnhancer.showNotification(message, type);
        }
    }

    /**
     * Save progress
     */
    saveProgress() {
        const currentStep = this.getCurrentStep();
        const formData = this.collectFormData();
        
        localStorage.setItem('yht_saved_progress', JSON.stringify({
            step: currentStep,
            data: formData,
            timestamp: Date.now()
        }));
        
        this.showNotification('💾 Progresso salvato!', 'success');
    }

    /**
     * Collect current form data
     */
    collectFormData() {
        const data = {};
        const inputs = document.querySelectorAll('.yht-input, .yht-card[aria-checked="true"]');
        
        inputs.forEach(input => {
            if (input.value) {
                data[input.name || input.dataset.group] = input.value;
            } else if (input.dataset.value) {
                data[input.dataset.group] = input.dataset.value;
            }
        });
        
        return data;
    }
}

// Initialize mobile enhancer when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.yhtMobile = new YHTMobileEnhancer();
});