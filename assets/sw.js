/**
 * Service Worker for Your Hidden Trip Planner
 * Enables offline functionality and performance optimization
 * 
 * @package YourHiddenTrip
 * @version 6.3
 */

const CACHE_NAME = 'yht-cache-v6.3';
const OFFLINE_URL = '/offline.html';

// Assets to cache for offline functionality
const CACHE_ASSETS = [
    '/',
    '/wp-content/plugins/your-hidden-trip-planner/assets/css/yht-frontend.css',
    '/wp-content/plugins/your-hidden-trip-planner/assets/css/yht-competitive-enhancements.css',
    '/wp-content/plugins/your-hidden-trip-planner/assets/js/yht-enhancer.js',
    '/wp-content/plugins/your-hidden-trip-planner/assets/js/yht-ai-recommendations.js',
    '/wp-content/plugins/your-hidden-trip-planner/assets/js/yht-gamification.js',
    '/wp-content/plugins/your-hidden-trip-planner/assets/manifest.json',
    OFFLINE_URL
];

// Install event - cache assets
self.addEventListener('install', (event) => {
    console.log('YHT Service Worker installing...');
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('YHT Cache opened');
                return cache.addAll(CACHE_ASSETS);
            })
            .then(() => {
                return self.skipWaiting();
            })
            .catch((error) => {
                console.error('YHT Cache failed to open:', error);
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    console.log('YHT Service Worker activating...');
    
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((cacheName) => {
                        return cacheName.startsWith('yht-cache-') && cacheName !== CACHE_NAME;
                    })
                    .map((cacheName) => {
                        console.log('YHT Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    })
            );
        }).then(() => {
            return self.clients.claim();
        })
    );
});

// Fetch event - serve cached content when offline
self.addEventListener('fetch', (event) => {
    // Handle navigation requests
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .catch(() => {
                    return caches.open(CACHE_NAME)
                        .then((cache) => {
                            return cache.match(OFFLINE_URL);
                        });
                })
        );
        return;
    }

    // Handle other requests with cache-first strategy for assets
    if (event.request.destination === 'style' || 
        event.request.destination === 'script' || 
        event.request.destination === 'image') {
        
        event.respondWith(
            caches.open(CACHE_NAME)
                .then((cache) => {
                    return cache.match(event.request)
                        .then((cachedResponse) => {
                            if (cachedResponse) {
                                // Serve from cache
                                return cachedResponse;
                            }
                            
                            // Fetch from network and cache
                            return fetch(event.request)
                                .then((response) => {
                                    // Don't cache non-successful responses
                                    if (!response || response.status !== 200 || response.type !== 'basic') {
                                        return response;
                                    }
                                    
                                    // Clone the response to cache it
                                    const responseToCache = response.clone();
                                    cache.put(event.request, responseToCache);
                                    
                                    return response;
                                })
                                .catch(() => {
                                    // Return a fallback for images if offline
                                    if (event.request.destination === 'image') {
                                        return new Response(
                                            '<svg width="200" height="150" xmlns="http://www.w3.org/2000/svg"><rect width="100%" height="100%" fill="#f3f4f6"/><text x="50%" y="50%" text-anchor="middle" fill="#6b7280">Immagine non disponibile offline</text></svg>',
                                            { headers: { 'Content-Type': 'image/svg+xml' } }
                                        );
                                    }
                                });
                        });
                })
        );
    }
});

// Background sync for form submissions when online
self.addEventListener('sync', (event) => {
    if (event.tag === 'yht-form-sync') {
        event.waitUntil(syncFormData());
    }
});

// Handle background sync for form data
async function syncFormData() {
    try {
        const data = await getStoredFormData();
        if (data && data.length > 0) {
            for (const formData of data) {
                await submitFormData(formData);
            }
            await clearStoredFormData();
            
            // Notify client that sync completed
            self.clients.matchAll().then((clients) => {
                clients.forEach((client) => {
                    client.postMessage({
                        type: 'SYNC_COMPLETE',
                        data: { message: 'Form data synchronized successfully' }
                    });
                });
            });
        }
    } catch (error) {
        console.error('YHT Background sync failed:', error);
    }
}

// Get stored form data from IndexedDB
async function getStoredFormData() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('YHTOfflineDB', 1);
        
        request.onerror = () => reject(request.error);
        
        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['formData'], 'readonly');
            const store = transaction.objectStore('formData');
            const getAllRequest = store.getAll();
            
            getAllRequest.onsuccess = () => resolve(getAllRequest.result);
            getAllRequest.onerror = () => reject(getAllRequest.error);
        };
        
        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains('formData')) {
                const store = db.createObjectStore('formData', { keyPath: 'id', autoIncrement: true });
                store.createIndex('timestamp', 'timestamp', { unique: false });
            }
        };
    });
}

// Submit form data to server
async function submitFormData(formData) {
    const response = await fetch('/wp-json/yht/v1/submit-form', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData.data)
    });
    
    if (!response.ok) {
        throw new Error('Failed to submit form data');
    }
    
    return response.json();
}

// Clear stored form data after successful sync
async function clearStoredFormData() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('YHTOfflineDB', 1);
        
        request.onerror = () => reject(request.error);
        
        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['formData'], 'readwrite');
            const store = transaction.objectStore('formData');
            const clearRequest = store.clear();
            
            clearRequest.onsuccess = () => resolve();
            clearRequest.onerror = () => reject(clearRequest.error);
        };
    });
}

// Push notification handling
self.addEventListener('push', (event) => {
    if (!event.data) return;
    
    const data = event.data.json();
    const options = {
        body: data.body || 'Nuovo aggiornamento disponibile!',
        icon: '/wp-content/plugins/your-hidden-trip-planner/assets/images/icon-192.png',
        badge: '/wp-content/plugins/your-hidden-trip-planner/assets/images/icon-96.png',
        vibrate: [100, 50, 100],
        data: {
            url: data.url || '/',
            timestamp: Date.now()
        },
        actions: [
            {
                action: 'view',
                title: 'Visualizza',
                icon: '/wp-content/plugins/your-hidden-trip-planner/assets/images/action-view.png'
            },
            {
                action: 'dismiss',
                title: 'Ignora',
                icon: '/wp-content/plugins/your-hidden-trip-planner/assets/images/action-dismiss.png'
            }
        ],
        requireInteraction: true,
        tag: 'yht-notification'
    };
    
    event.waitUntil(
        self.registration.showNotification(data.title || 'Your Hidden Trip', options)
    );
});

// Handle notification clicks
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    
    if (event.action === 'view') {
        const url = event.notification.data.url;
        event.waitUntil(
            clients.openWindow(url)
        );
    } else if (event.action === 'dismiss') {
        // Just close the notification
        return;
    } else {
        // Default action - open the main app
        event.waitUntil(
            clients.openWindow('/')
        );
    }
});

// Message handling from main thread
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    
    if (event.data && event.data.type === 'CACHE_TOUR_DATA') {
        // Cache tour data for offline access
        caches.open(CACHE_NAME).then((cache) => {
            cache.put('/wp-json/yht/v1/tours', new Response(JSON.stringify(event.data.tours)));
        });
    }
});