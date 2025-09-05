/**
 * Advanced Filters for Trip Planning PWA
 * 
 * @package YourHiddenTrip
 * @version 6.3.0
 */

(function($) {
    'use strict';

    class YHTAdvancedFilters {
        constructor() {
            this.filters = {
                location: '',
                radius: 50,
                duration: '',
                difficulty: '',
                season: '',
                interests: [],
                budget: { min: 0, max: 1000 },
                groupSize: '',
                accessibility: false,
                offline: false
            };
            
            this.init();
        }

        init() {
            this.bindEvents();
            this.initGeolocation();
            this.initOfflineMode();
            this.loadSavedFilters();
        }

        bindEvents() {
            // Filter form events
            $(document).on('change', '.yht-filter-input', this.onFilterChange.bind(this));
            $(document).on('click', '.yht-filter-preset', this.applyPreset.bind(this));
            $(document).on('click', '.yht-filter-clear', this.clearFilters.bind(this));
            $(document).on('click', '.yht-filter-save', this.saveFilterPreset.bind(this));
            
            // Advanced filter toggles
            $(document).on('click', '.yht-filter-toggle', this.toggleAdvancedFilters.bind(this));
            $(document).on('input', '.yht-range-slider', this.updateRangeValue.bind(this));
            
            // Map integration
            $(document).on('click', '.yht-use-current-location', this.useCurrentLocation.bind(this));
            $(document).on('click', '.yht-draw-area', this.enableAreaDrawing.bind(this));
            
            // Offline mode
            $(document).on('click', '.yht-offline-toggle', this.toggleOfflineMode.bind(this));
        }

        onFilterChange(e) {
            const $input = $(e.target);
            const filterName = $input.data('filter');
            const value = $input.val();
            
            if ($input.is(':checkbox')) {
                if (filterName === 'interests') {
                    if ($input.is(':checked')) {
                        this.filters.interests.push(value);
                    } else {
                        const index = this.filters.interests.indexOf(value);
                        if (index > -1) {
                            this.filters.interests.splice(index, 1);
                        }
                    }
                } else {
                    this.filters[filterName] = $input.is(':checked');
                }
            } else if ($input.hasClass('budget-range')) {
                const type = $input.data('type'); // 'min' or 'max'
                this.filters.budget[type] = parseInt(value);
            } else {
                this.filters[filterName] = value;
            }
            
            this.applyFilters();
            this.saveFiltersToStorage();
        }

        applyFilters() {
            const $resultsContainer = $('.yht-trip-results');
            
            // Show loading state
            $resultsContainer.addClass('loading');
            
            // Prepare filter data
            const filterData = {
                action: 'yht_apply_advanced_filters',
                nonce: yhtPWA.nonce,
                filters: this.filters,
                offline: navigator.onLine === false || this.filters.offline
            };

            // Apply filters via AJAX or offline cache
            if (navigator.onLine && !this.filters.offline) {
                this.applyFiltersOnline(filterData);
            } else {
                this.applyFiltersOffline();
            }
        }

        applyFiltersOnline(filterData) {
            $.ajax({
                url: yhtPWA.ajaxurl,
                type: 'POST',
                data: filterData,
                success: (response) => {
                    if (response.success) {
                        this.displayResults(response.data.trips);
                        this.updateMapMarkers(response.data.trips);
                        
                        // Cache results for offline use
                        this.cacheResults(response.data.trips);
                    } else {
                        this.showError(response.data.message || 'Filter error');
                    }
                },
                error: () => {
                    // Fallback to offline mode
                    this.applyFiltersOffline();
                },
                complete: () => {
                    $('.yht-trip-results').removeClass('loading');
                }
            });
        }

        applyFiltersOffline() {
            const cachedTrips = this.getCachedTrips();
            const filteredTrips = this.filterTripsLocally(cachedTrips);
            
            this.displayResults(filteredTrips);
            this.updateMapMarkers(filteredTrips);
            $('.yht-trip-results').removeClass('loading');
            
            // Show offline indicator
            this.showOfflineIndicator();
        }

        filterTripsLocally(trips) {
            return trips.filter(trip => {
                // Location/radius filter
                if (this.filters.location && this.filters.radius) {
                    const distance = this.calculateDistance(
                        this.filters.location.lat,
                        this.filters.location.lng,
                        trip.lat,
                        trip.lng
                    );
                    if (distance > this.filters.radius) return false;
                }

                // Duration filter
                if (this.filters.duration && trip.duration !== this.filters.duration) {
                    return false;
                }

                // Difficulty filter
                if (this.filters.difficulty && trip.difficulty !== this.filters.difficulty) {
                    return false;
                }

                // Season filter
                if (this.filters.season && !trip.seasons.includes(this.filters.season)) {
                    return false;
                }

                // Interests filter
                if (this.filters.interests.length > 0) {
                    const hasInterest = this.filters.interests.some(interest => 
                        trip.interests.includes(interest)
                    );
                    if (!hasInterest) return false;
                }

                // Budget filter
                if (trip.price < this.filters.budget.min || trip.price > this.filters.budget.max) {
                    return false;
                }

                // Group size filter
                if (this.filters.groupSize && trip.maxGroupSize < this.filters.groupSize) {
                    return false;
                }

                // Accessibility filter
                if (this.filters.accessibility && !trip.accessible) {
                    return false;
                }

                return true;
            });
        }

        calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371; // Radius of the Earth in kilometers
            const dLat = this.deg2rad(lat2 - lat1);
            const dLon = this.deg2rad(lon2 - lon1);
            const a = 
                Math.sin(dLat/2) * Math.sin(dLat/2) +
                Math.cos(this.deg2rad(lat1)) * Math.cos(this.deg2rad(lat2)) * 
                Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c;
        }

        deg2rad(deg) {
            return deg * (Math.PI/180);
        }

        displayResults(trips) {
            const $container = $('.yht-trip-results');
            $container.empty();

            if (trips.length === 0) {
                $container.html('<div class="yht-no-results">' + 
                    '<h3>No trips found</h3>' +
                    '<p>Try adjusting your filters or explore nearby areas.</p>' +
                    '</div>');
                return;
            }

            trips.forEach(trip => {
                const $tripCard = this.createTripCard(trip);
                $container.append($tripCard);
            });

            // Initialize lazy loading for images
            this.initLazyLoading();
        }

        createTripCard(trip) {
            const offlineClass = navigator.onLine ? '' : 'offline-available';
            
            return $(`
                <div class="yht-trip-card ${offlineClass}" data-trip-id="${trip.id}">
                    <div class="trip-image">
                        <img data-src="${trip.image}" alt="${trip.title}" class="lazy-load">
                        <div class="trip-badges">
                            ${trip.difficulty ? `<span class="difficulty-badge ${trip.difficulty}">${trip.difficulty}</span>` : ''}
                            ${trip.accessible ? '<span class="accessibility-badge">♿</span>' : ''}
                            ${!navigator.onLine ? '<span class="offline-badge">📱</span>' : ''}
                        </div>
                    </div>
                    <div class="trip-content">
                        <h3 class="trip-title">${trip.title}</h3>
                        <p class="trip-description">${trip.excerpt}</p>
                        <div class="trip-meta">
                            <span class="duration">⏱️ ${trip.duration}</span>
                            <span class="price">💰 €${trip.price}</span>
                            <span class="distance">📍 ${trip.distance}km</span>
                        </div>
                        <div class="trip-interests">
                            ${trip.interests.map(interest => `<span class="interest-tag">${interest}</span>`).join('')}
                        </div>
                        <div class="trip-actions">
                            <button class="btn-primary view-trip" data-trip-id="${trip.id}">
                                View Details
                            </button>
                            <button class="btn-secondary save-offline" data-trip-id="${trip.id}">
                                💾 Save Offline
                            </button>
                            <button class="btn-secondary share-trip" data-trip-id="${trip.id}">
                                🔗 Share
                            </button>
                        </div>
                    </div>
                </div>
            `);
        }

        initGeolocation() {
            if (navigator.geolocation) {
                $('.yht-use-current-location').show();
            }
        }

        useCurrentLocation() {
            if (!navigator.geolocation) {
                this.showError('Geolocation is not supported by this browser.');
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.filters.location = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    
                    // Update location input with reverse geocoding
                    this.reverseGeocode(position.coords.latitude, position.coords.longitude);
                    this.applyFilters();
                },
                (error) => {
                    this.showError('Unable to retrieve your location: ' + error.message);
                }
            );
        }

        reverseGeocode(lat, lng) {
            // Simple reverse geocoding - in production, use a proper service
            $('.yht-filter-input[data-filter="location"]').val(`${lat.toFixed(4)}, ${lng.toFixed(4)}`);
        }

        applyPreset(e) {
            const presetName = $(e.target).data('preset');
            const presets = {
                'family': {
                    difficulty: 'easy',
                    interests: ['family-friendly', 'nature'],
                    accessibility: true,
                    duration: 'half-day'
                },
                'adventure': {
                    difficulty: 'hard',
                    interests: ['adventure', 'hiking', 'outdoor'],
                    duration: 'full-day'
                },
                'romantic': {
                    interests: ['romantic', 'wine', 'restaurants'],
                    groupSize: '2',
                    budget: { min: 100, max: 500 }
                },
                'budget': {
                    budget: { min: 0, max: 50 },
                    interests: ['nature', 'walking', 'free']
                }
            };

            if (presets[presetName]) {
                this.filters = { ...this.filters, ...presets[presetName] };
                this.updateFilterUI();
                this.applyFilters();
            }
        }

        updateFilterUI() {
            // Update form inputs to reflect current filter values
            Object.keys(this.filters).forEach(key => {
                const $input = $(`.yht-filter-input[data-filter="${key}"]`);
                
                if ($input.is(':checkbox')) {
                    $input.prop('checked', this.filters[key]);
                } else if ($input.length) {
                    $input.val(this.filters[key]);
                }
            });

            // Update budget range sliders
            $('.budget-range[data-type="min"]').val(this.filters.budget.min);
            $('.budget-range[data-type="max"]').val(this.filters.budget.max);
            this.updateRangeDisplays();

            // Update interests checkboxes
            $('.yht-filter-input[data-filter="interests"]').each(function() {
                const value = $(this).val();
                $(this).prop('checked', this.filters.interests.includes(value));
            }.bind(this));
        }

        clearFilters() {
            this.filters = {
                location: '',
                radius: 50,
                duration: '',
                difficulty: '',
                season: '',
                interests: [],
                budget: { min: 0, max: 1000 },
                groupSize: '',
                accessibility: false,
                offline: false
            };
            
            this.updateFilterUI();
            this.applyFilters();
        }

        saveFilterPreset() {
            const presetName = prompt('Enter a name for this filter preset:');
            if (presetName) {
                const savedPresets = JSON.parse(localStorage.getItem('yht_filter_presets') || '{}');
                savedPresets[presetName] = this.filters;
                localStorage.setItem('yht_filter_presets', JSON.stringify(savedPresets));
                
                this.updatePresetsList();
                this.showSuccess('Filter preset saved!');
            }
        }

        loadSavedFilters() {
            const saved = localStorage.getItem('yht_current_filters');
            if (saved) {
                this.filters = { ...this.filters, ...JSON.parse(saved) };
                this.updateFilterUI();
            }
        }

        saveFiltersToStorage() {
            localStorage.setItem('yht_current_filters', JSON.stringify(this.filters));
        }

        initOfflineMode() {
            // Check online status
            window.addEventListener('online', this.onOnline.bind(this));
            window.addEventListener('offline', this.onOffline.bind(this));
            
            // Load cached trips on startup
            this.loadCachedTrips();
        }

        onOnline() {
            $('.yht-offline-indicator').hide();
            this.showSuccess('Back online! Syncing data...');
            this.syncOfflineData();
        }

        onOffline() {
            this.showOfflineIndicator();
            this.showWarning('You are offline. Using cached data.');
        }

        showOfflineIndicator() {
            if (!$('.yht-offline-indicator').length) {
                $('body').append('<div class="yht-offline-indicator">📱 Offline Mode</div>');
            }
            $('.yht-offline-indicator').show();
        }

        cacheResults(trips) {
            localStorage.setItem('yht_cached_trips', JSON.stringify(trips));
            localStorage.setItem('yht_cache_timestamp', Date.now().toString());
        }

        getCachedTrips() {
            const cached = localStorage.getItem('yht_cached_trips');
            return cached ? JSON.parse(cached) : [];
        }

        loadCachedTrips() {
            const cached = this.getCachedTrips();
            if (cached.length > 0 && !navigator.onLine) {
                this.displayResults(cached);
            }
        }

        syncOfflineData() {
            // Sync any offline actions when connection is restored
            const offlineActions = JSON.parse(localStorage.getItem('yht_offline_actions') || '[]');
            
            offlineActions.forEach(action => {
                // Process offline actions (bookmarks, reviews, etc.)
                this.processOfflineAction(action);
            });
            
            // Clear offline actions after sync
            localStorage.removeItem('yht_offline_actions');
        }

        updateRangeValue(e) {
            const $slider = $(e.target);
            const $display = $slider.siblings('.range-value');
            $display.text($slider.val());
        }

        updateRangeDisplays() {
            $('.yht-range-slider').each(function() {
                const $slider = $(this);
                const $display = $slider.siblings('.range-value');
                $display.text($slider.val());
            });
        }

        initLazyLoading() {
            const images = document.querySelectorAll('img.lazy-load');
            
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.classList.remove('lazy-load');
                            imageObserver.unobserve(img);
                        }
                    });
                });

                images.forEach(img => imageObserver.observe(img));
            } else {
                // Fallback for browsers without IntersectionObserver
                images.forEach(img => {
                    img.src = img.dataset.src;
                    img.classList.remove('lazy-load');
                });
            }
        }

        showError(message) {
            this.showNotification(message, 'error');
        }

        showSuccess(message) {
            this.showNotification(message, 'success');
        }

        showWarning(message) {
            this.showNotification(message, 'warning');
        }

        showNotification(message, type = 'info') {
            const $notification = $(`
                <div class="yht-notification ${type}">
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

    // Initialize when DOM is ready
    $(document).ready(function() {
        window.yhtFilters = new YHTAdvancedFilters();
    });

})(jQuery);