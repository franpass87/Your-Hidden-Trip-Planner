/**
 * PWA Main JavaScript - Progressive Web App functionality
 * 
 * @package YourHiddenTrip
 * @version 6.3.0
 */

(function($) {
    'use strict';

    class YHTPWAManager {
        constructor() {
            this.deferredPrompt = null;
            this.swRegistration = null;
            this.isSubscribed = false;
            
            this.init();
        }

        init() {
            this.registerServiceWorker();
            this.handleInstallPrompt();
            this.initPushNotifications();
            this.bindEvents();
            this.checkOfflineStatus();
        }

        registerServiceWorker() {
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register(yhtPWA.sw_url)
                    .then((registration) => {
                        console.log('Service Worker registered:', registration);
                        this.swRegistration = registration;
                        
                        // Check for updates
                        this.checkForUpdates(registration);
                        
                        // Initialize push messaging
                        this.initPushMessaging(registration);
                    })
                    .catch((error) => {
                        console.log('Service Worker registration failed:', error);
                    });
            }
        }

        checkForUpdates(registration) {
            registration.addEventListener('updatefound', () => {
                const newWorker = registration.installing;
                
                newWorker.addEventListener('statechange', () => {
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        this.showUpdateAvailable();
                    }
                });
            });
        }

        showUpdateAvailable() {
            const $updateBanner = $(`
                <div id="yht-update-banner" class="yht-update-banner">
                    <div class="update-content">
                        <span>🔄 New version available!</span>
                        <button id="yht-reload-app" class="btn-primary">Update Now</button>
                        <button id="yht-dismiss-update" class="btn-secondary">Later</button>
                    </div>
                </div>
            `);

            $('body').append($updateBanner);
            $updateBanner.slideDown();

            $('#yht-reload-app').on('click', () => {
                window.location.reload();
            });

            $('#yht-dismiss-update').on('click', () => {
                $updateBanner.slideUp(() => $updateBanner.remove());
            });
        }

        handleInstallPrompt() {
            window.addEventListener('beforeinstallprompt', (e) => {
                // Prevent the mini-infobar from appearing on mobile
                e.preventDefault();
                
                // Save the event so it can be triggered later
                this.deferredPrompt = e;
                
                // Show install button
                this.showInstallPrompt();
            });

            // Handle successful installation
            window.addEventListener('appinstalled', () => {
                console.log('PWA was installed');
                this.hideInstallPrompt();
                this.trackEvent('pwa_installed');
            });
        }

        showInstallPrompt() {
            const $installPrompt = $('#yht-pwa-install-prompt');
            
            // Check if user previously dismissed
            if (localStorage.getItem('yht_install_dismissed') === 'true') {
                return;
            }

            $installPrompt.fadeIn();
        }

        hideInstallPrompt() {
            $('#yht-pwa-install-prompt').fadeOut();
        }

        bindEvents() {
            // Install app button
            $(document).on('click', '#yht-pwa-install-button', (e) => {
                e.preventDefault();
                this.installApp();
            });

            // Dismiss install prompt
            $(document).on('click', '#yht-pwa-dismiss-button', (e) => {
                e.preventDefault();
                this.dismissInstallPrompt();
            });

            // Share functionality
            $(document).on('click', '.share-trip', (e) => {
                e.preventDefault();
                const tripId = $(e.target).data('trip-id');
                this.shareTrip(tripId);
            });

            // Save offline functionality
            $(document).on('click', '.save-offline', (e) => {
                e.preventDefault();
                const tripId = $(e.target).data('trip-id');
                this.saveTripOffline(tripId);
            });

            // Push notification toggle
            $(document).on('click', '.yht-push-toggle', (e) => {
                e.preventDefault();
                this.togglePushNotifications();
            });
        }

        installApp() {
            if (this.deferredPrompt) {
                // Show the install prompt
                this.deferredPrompt.prompt();
                
                // Wait for the user to respond to the prompt
                this.deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('User accepted the install prompt');
                        this.trackEvent('pwa_install_accepted');
                    } else {
                        console.log('User dismissed the install prompt');
                        this.trackEvent('pwa_install_dismissed');
                    }
                    
                    this.deferredPrompt = null;
                });
            }
            
            this.hideInstallPrompt();
        }

        dismissInstallPrompt() {
            localStorage.setItem('yht_install_dismissed', 'true');
            this.hideInstallPrompt();
            this.trackEvent('pwa_install_dismissed');
        }

        initPushNotifications() {
            if (!('PushManager' in window)) {
                console.log('Push messaging is not supported');
                return;
            }

            if (!('Notification' in window)) {
                console.log('Notifications are not supported');
                return;
            }

            // Check current permission status
            if (Notification.permission === 'granted') {
                this.checkSubscription();
            }
        }

        initPushMessaging(registration) {
            if (!registration.pushManager) {
                console.log('Push messaging is not supported');
                return;
            }

            registration.pushManager.getSubscription().then((subscription) => {
                this.isSubscribed = !(subscription === null);
                this.updatePushUI();
            });
        }

        togglePushNotifications() {
            if (this.isSubscribed) {
                this.unsubscribeUser();
            } else {
                this.subscribeUser();
            }
        }

        subscribeUser() {
            if (!this.swRegistration) {
                console.log('Service worker not registered');
                return;
            }

            const applicationServerKey = this.urlB64ToUint8Array(yhtPWA.vapid_public_key || '');
            
            this.swRegistration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: applicationServerKey
            }).then((subscription) => {
                console.log('User is subscribed:', subscription);
                this.updateSubscriptionOnServer(subscription);
                this.isSubscribed = true;
                this.updatePushUI();
            }).catch((err) => {
                console.log('Failed to subscribe the user: ', err);
            });
        }

        unsubscribeUser() {
            this.swRegistration.pushManager.getSubscription().then((subscription) => {
                if (subscription) {
                    return subscription.unsubscribe();
                }
            }).catch((error) => {
                console.log('Error unsubscribing', error);
            }).then(() => {
                this.updateSubscriptionOnServer(null);
                console.log('User is unsubscribed.');
                this.isSubscribed = false;
                this.updatePushUI();
            });
        }

        updateSubscriptionOnServer(subscription) {
            $.ajax({
                url: yhtPWA.ajaxurl,
                type: 'POST',
                data: {
                    action: 'yht_subscribe_push',
                    nonce: yhtPWA.nonce,
                    subscription: JSON.stringify(subscription)
                },
                success: (response) => {
                    if (response.success) {
                        console.log('Subscription updated on server');
                    } else {
                        console.log('Failed to update subscription on server');
                    }
                }
            });
        }

        urlB64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding)
                .replace(/-/g, '+')
                .replace(/_/g, '/');

            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);

            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        }

        updatePushUI() {
            const $pushToggle = $('.yht-push-toggle');
            if (this.isSubscribed) {
                $pushToggle.text('Disable Notifications').removeClass('btn-primary').addClass('btn-secondary');
            } else {
                $pushToggle.text('Enable Notifications').removeClass('btn-secondary').addClass('btn-primary');
            }
        }

        checkSubscription() {
            if (this.swRegistration) {
                this.swRegistration.pushManager.getSubscription().then((subscription) => {
                    this.isSubscribed = !(subscription === null);
                    this.updatePushUI();
                });
            }
        }

        shareTrip(tripId) {
            const tripData = this.getTripData(tripId);
            
            if (navigator.share) {
                // Use native Web Share API if available
                navigator.share({
                    title: tripData.title,
                    text: tripData.description,
                    url: tripData.url
                }).then(() => {
                    console.log('Successful share');
                    this.trackEvent('trip_shared', { method: 'native', trip_id: tripId });
                }).catch((error) => {
                    console.log('Error sharing:', error);
                    this.fallbackShare(tripData);
                });
            } else {
                this.fallbackShare(tripData);
            }
        }

        fallbackShare(tripData) {
            // Fallback sharing options
            const $shareModal = $(`
                <div id="yht-share-modal" class="yht-modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>Share Trip</h3>
                            <button class="modal-close">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div class="share-options">
                                <button class="share-option" data-method="copy">
                                    📋 Copy Link
                                </button>
                                <button class="share-option" data-method="whatsapp">
                                    💬 WhatsApp
                                </button>
                                <button class="share-option" data-method="facebook">
                                    📘 Facebook
                                </button>
                                <button class="share-option" data-method="twitter">
                                    🐦 Twitter
                                </button>
                                <button class="share-option" data-method="email">
                                    📧 Email
                                </button>
                            </div>
                            <div class="share-link">
                                <input type="text" value="${tripData.url}" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            `);

            $('body').append($shareModal);
            $shareModal.fadeIn();

            // Handle share option clicks
            $shareModal.on('click', '.share-option', (e) => {
                const method = $(e.target).data('method');
                this.handleShare(method, tripData);
                $shareModal.fadeOut(() => $shareModal.remove());
            });

            // Close modal
            $shareModal.on('click', '.modal-close, .yht-modal', (e) => {
                if (e.target === e.currentTarget) {
                    $shareModal.fadeOut(() => $shareModal.remove());
                }
            });
        }

        handleShare(method, tripData) {
            const encodedUrl = encodeURIComponent(tripData.url);
            const encodedTitle = encodeURIComponent(tripData.title);
            const encodedText = encodeURIComponent(tripData.description);

            switch (method) {
                case 'copy':
                    this.copyToClipboard(tripData.url);
                    break;
                case 'whatsapp':
                    window.open(`https://wa.me/?text=${encodedTitle}%20${encodedUrl}`, '_blank');
                    break;
                case 'facebook':
                    window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`, '_blank');
                    break;
                case 'twitter':
                    window.open(`https://twitter.com/intent/tweet?text=${encodedTitle}&url=${encodedUrl}`, '_blank');
                    break;
                case 'email':
                    window.location.href = `mailto:?subject=${encodedTitle}&body=${encodedText}%0A%0A${encodedUrl}`;
                    break;
            }

            this.trackEvent('trip_shared', { method: method, trip_id: tripData.id });
        }

        copyToClipboard(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    this.showNotification('Link copied to clipboard!', 'success');
                });
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                try {
                    document.execCommand('copy');
                    this.showNotification('Link copied to clipboard!', 'success');
                } catch (err) {
                    this.showNotification('Failed to copy link', 'error');
                }
                document.body.removeChild(textArea);
            }
        }

        saveTripOffline(tripId) {
            const tripData = this.getTripData(tripId);
            
            if (!tripData) {
                this.showNotification('Trip data not found', 'error');
                return;
            }

            // Store trip data in IndexedDB for offline access
            this.storeOfflineTrip(tripData).then(() => {
                this.showNotification('Trip saved for offline viewing!', 'success');
                this.trackEvent('trip_saved_offline', { trip_id: tripId });
                
                // Update UI to show offline availability
                $(`.yht-trip-card[data-trip-id="${tripId}"]`).addClass('offline-available');
                $(`.save-offline[data-trip-id="${tripId}"]`).text('✓ Saved Offline').prop('disabled', true);
            }).catch((error) => {
                console.error('Failed to save trip offline:', error);
                this.showNotification('Failed to save trip offline', 'error');
            });
        }

        storeOfflineTrip(tripData) {
            return new Promise((resolve, reject) => {
                const request = indexedDB.open('YHTOfflineTrips', 1);
                
                request.onerror = () => reject(request.error);
                
                request.onupgradeneeded = () => {
                    const db = request.result;
                    if (!db.objectStoreNames.contains('trips')) {
                        db.createObjectStore('trips', { keyPath: 'id' });
                    }
                };
                
                request.onsuccess = () => {
                    const db = request.result;
                    const transaction = db.transaction(['trips'], 'readwrite');
                    const store = transaction.objectStore('trips');
                    
                    store.put(tripData);
                    
                    transaction.oncomplete = () => resolve();
                    transaction.onerror = () => reject(transaction.error);
                };
            });
        }

        getTripData(tripId) {
            // This would typically fetch from a data store or API
            // For now, extract from DOM
            const $tripCard = $(`.yht-trip-card[data-trip-id="${tripId}"]`);
            
            if (!$tripCard.length) return null;

            return {
                id: tripId,
                title: $tripCard.find('.trip-title').text(),
                description: $tripCard.find('.trip-description').text(),
                image: $tripCard.find('img').data('src') || $tripCard.find('img').attr('src'),
                url: window.location.origin + '/trip/' + tripId,
                duration: $tripCard.find('.duration').text(),
                price: $tripCard.find('.price').text(),
                distance: $tripCard.find('.distance').text(),
                interests: Array.from($tripCard.find('.interest-tag')).map(tag => $(tag).text())
            };
        }

        checkOfflineStatus() {
            // Check if we're online/offline
            window.addEventListener('online', () => {
                this.showNotification('You are back online!', 'success');
                this.syncOfflineData();
            });

            window.addEventListener('offline', () => {
                this.showNotification('You are offline. Some features may be limited.', 'warning');
            });

            // Initial status check
            if (!navigator.onLine) {
                this.showNotification('You are offline. Using cached content.', 'info');
            }
        }

        syncOfflineData() {
            // Sync any offline actions when back online
            const offlineActions = JSON.parse(localStorage.getItem('yht_offline_actions') || '[]');
            
            if (offlineActions.length === 0) return;

            this.showNotification('Syncing offline data...', 'info');

            const syncPromises = offlineActions.map(action => this.processOfflineAction(action));
            
            Promise.all(syncPromises).then(() => {
                localStorage.removeItem('yht_offline_actions');
                this.showNotification('Offline data synced successfully!', 'success');
            }).catch(() => {
                this.showNotification('Some offline data could not be synced', 'warning');
            });
        }

        processOfflineAction(action) {
            return $.ajax({
                url: yhtPWA.ajaxurl,
                type: 'POST',
                data: {
                    action: 'yht_process_offline_action',
                    nonce: yhtPWA.nonce,
                    offline_action: JSON.stringify(action)
                }
            });
        }

        trackEvent(eventName, parameters = {}) {
            // Track PWA events for analytics
            if (typeof gtag !== 'undefined') {
                gtag('event', eventName, {
                    event_category: 'PWA',
                    ...parameters
                });
            }

            // Also send to custom analytics if available
            if (window.yhtAnalytics) {
                window.yhtAnalytics.track(eventName, parameters);
            }
        }

        showNotification(message, type = 'info') {
            const $notification = $(`
                <div class="yht-pwa-notification ${type}">
                    <span class="message">${message}</span>
                    <button class="close">&times;</button>
                </div>
            `);

            $('body').append($notification);
            
            setTimeout(() => {
                $notification.addClass('show');
            }, 100);

            setTimeout(() => {
                $notification.removeClass('show');
                setTimeout(() => $notification.remove(), 300);
            }, 5000);

            $notification.find('.close').on('click', () => {
                $notification.removeClass('show');
                setTimeout(() => $notification.remove(), 300);
            });
        }
    }

    // Initialize PWA when DOM is ready
    $(document).ready(function() {
        window.yhtPWA = new YHTPWAManager();
    });

})(jQuery);