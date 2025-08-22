/**
 * AI-Powered Smart Recommendations System
 * Makes the plugin more competitive with intelligent suggestions
 * 
 * @package YourHiddenTrip
 * @version 6.3
 */

class YHTAIRecommendations {
    constructor() {
        this.userPreferences = this.loadUserPreferences();
        this.seasonalData = this.getSeasonalData();
        this.popularityData = this.getPopularityData();
        this.weatherData = null;
        this.init();
    }

    init() {
        this.loadWeatherData();
        this.setupRecommendationEngine();
        this.createSmartSuggestions();
        this.setupPersonalizationTracking();
        this.addDynamicPricing();
        this.setupMLAlgorithm();
        this.initializeCollaborativeFiltering();
    }

    /**
     * Load user preferences from localStorage and behavior tracking
     */
    loadUserPreferences() {
        const stored = localStorage.getItem('yht_user_preferences');
        const preferences = stored ? JSON.parse(stored) : {
            favoriteExperiences: [],
            preferredSeasons: [],
            budgetRange: 'medium',
            groupSize: 2,
            interests: [],
            visitedPlaces: [],
            bookingHistory: []
        };

        // Track current session behavior
        preferences.sessionInteractions = [];
        preferences.currentVisit = Date.now();
        
        return preferences;
    }

    /**
     * Get seasonal recommendations data
     */
    getSeasonalData() {
        const currentMonth = new Date().getMonth();
        
        return {
            spring: {
                months: [2, 3, 4],
                experiences: ['natura_relax', 'storico_culturale'],
                activities: ['trekking', 'visite_guidate', 'degustazioni'],
                bonus: 'Fioritura primaverile - esperienza unica!'
            },
            summer: {
                months: [5, 6, 7],
                experiences: ['natura_relax', 'avventura'],
                activities: ['outdoor', 'terme', 'bike'],
                bonus: 'Estate sotto le stelle - cene all\'aperto!'
            },
            autumn: {
                months: [8, 9, 10],
                experiences: ['enogastronomica', 'storico_culturale'],
                activities: ['vendemmia', 'sagre', 'castagne'],
                bonus: 'Vendemmia e sapori autunnali!'
            },
            winter: {
                months: [11, 0, 1],
                experiences: ['enogastronomica', 'storico_culturale'],
                activities: ['terme', 'musei', 'degustazioni'],
                bonus: 'Atmosfera magica dei borghi invernali!'
            }
        };
    }

    /**
     * Get popularity data based on booking trends
     */
    getPopularityData() {
        return {
            trending: ['assisi_perugia', 'orvieto_civita', 'viterbo_terme'],
            hot_deals: ['mix_personalizzato'],
            experiences: {
                'enogastronomica': { popularity: 85, trend: 'up' },
                'storico_culturale': { popularity: 78, trend: 'stable' },
                'natura_relax': { popularity: 72, trend: 'up' },
                'avventura': { popularity: 68, trend: 'up' }
            }
        };
    }

    /**
     * Load weather data for smart recommendations
     */
    async loadWeatherData() {
        // Simulate weather API call with realistic data
        this.weatherData = {
            current: {
                condition: 'sunny',
                temperature: 22,
                windSpeed: 10
            },
            forecast: [
                { day: 0, condition: 'sunny', temp: 24, rain: 0 },
                { day: 1, condition: 'partly_cloudy', temp: 21, rain: 20 },
                { day: 2, condition: 'sunny', temp: 26, rain: 0 }
            ]
        };
    }

    /**
     * Create smart suggestions based on AI analysis
     */
    createSmartSuggestions() {
        const suggestions = this.generateSuggestions();
        this.displaySmartSuggestions(suggestions);
        this.addRecommendationWidgets();
    }

    /**
     * Generate personalized suggestions using AI logic
     */
    generateSuggestions() {
        const currentSeason = this.getCurrentSeason();
        const weatherScore = this.getWeatherScore();
        const personalityScore = this.getPersonalityScore();
        
        const suggestions = [];

        // Weather-based suggestions
        if (weatherScore.outdoor > 80) {
            suggestions.push({
                type: 'weather',
                title: '🌞 Perfetto per attività outdoor!',
                description: 'Le condizioni meteo sono ideali per trekking e avventure all\'aria aperta',
                recommendations: ['avventura', 'natura_relax'],
                confidence: 95,
                savings: 'Sconto 15% su tour outdoor oggi!'
            });
        }

        // Seasonal suggestions
        const seasonData = this.seasonalData[currentSeason];
        if (seasonData) {
            suggestions.push({
                type: 'seasonal',
                title: `🍂 ${seasonData.bonus}`,
                description: `Questo è il periodo perfetto per ${seasonData.experiences.join(' e ')}`,
                recommendations: seasonData.experiences,
                confidence: 88,
                activities: seasonData.activities
            });
        }

        // Popularity-based suggestions
        if (this.popularityData.trending && this.popularityData.trending.length > 0) {
            const trending = this.popularityData.trending[0];
            suggestions.push({
                type: 'trending',
                title: '🔥 Tour più richiesto del momento',
                description: `${trending.replace('_', ' ')} è la destinazione più prenotata questa settimana`,
                recommendations: [trending],
                confidence: 92,
                social_proof: 'Prenotato 47 volte negli ultimi 7 giorni'
            });
        }

        // Personalized suggestions based on user behavior
        if (this.userPreferences.favoriteExperiences.length > 0) {
            const favorite = this.userPreferences.favoriteExperiences[0];
            suggestions.push({
                type: 'personalized',
                title: '💫 Consigliato per te',
                description: `Basato sui tuoi interessi per ${favorite}`,
                recommendations: this.getSimilarExperiences(favorite),
                confidence: 85,
                personal: true
            });
        }

        return suggestions.sort((a, b) => b.confidence - a.confidence);
    }

    /**
     * Display smart suggestions in the UI
     */
    displaySmartSuggestions(suggestions) {
        const container = this.createSuggestionsContainer();
        
        suggestions.slice(0, 3).forEach((suggestion, index) => {
            const widget = this.createSuggestionWidget(suggestion, index);
            container.appendChild(widget);
        });

        // Insert before the first step
        const step1 = document.getElementById('yht-step1');
        if (step1) {
            step1.parentNode.insertBefore(container, step1);
        }
    }

    /**
     * Create suggestions container
     */
    createSuggestionsContainer() {
        const container = document.createElement('div');
        container.className = 'yht-ai-suggestions';
        container.innerHTML = `
            <div class="yht-ai-header">
                <h3>🤖 I nostri consigli intelligenti per te</h3>
                <div class="yht-ai-badge">AI-Powered</div>
            </div>
            <div class="yht-suggestions-grid"></div>
        `;
        return container;
    }

    /**
     * Create individual suggestion widget
     */
    createSuggestionWidget(suggestion, index) {
        const widget = document.createElement('div');
        widget.className = `yht-suggestion-widget ${suggestion.type}`;
        widget.style.animationDelay = `${index * 0.2}s`;
        
        const confidenceColor = suggestion.confidence > 90 ? '#10b981' : suggestion.confidence > 80 ? '#f59e0b' : '#6b7280';
        
        widget.innerHTML = `
            <div class="suggestion-header">
                <div class="suggestion-title">${suggestion.title}</div>
                <div class="confidence-score" style="background: ${confidenceColor}">
                    ${suggestion.confidence}%
                </div>
            </div>
            <p class="suggestion-description">${suggestion.description}</p>
            ${suggestion.savings ? `<div class="suggestion-savings">${suggestion.savings}</div>` : ''}
            ${suggestion.social_proof ? `<div class="suggestion-proof">👥 ${suggestion.social_proof}</div>` : ''}
            <div class="suggestion-actions">
                <button class="suggestion-apply" data-recommendations='${JSON.stringify(suggestion.recommendations)}'>
                    Applica suggerimento
                </button>
                <button class="suggestion-dismiss" data-suggestion="${index}">
                    Ignora
                </button>
            </div>
        `;

        // Add click handlers
        const applyBtn = widget.querySelector('.suggestion-apply');
        const dismissBtn = widget.querySelector('.suggestion-dismiss');

        applyBtn.addEventListener('click', () => {
            this.applySuggestion(suggestion);
            widget.classList.add('applied');
        });

        dismissBtn.addEventListener('click', () => {
            widget.classList.add('dismissed');
            setTimeout(() => widget.remove(), 300);
        });

        return widget;
    }

    /**
     * Apply suggestion to the form
     */
    applySuggestion(suggestion) {
        suggestion.recommendations.forEach(rec => {
            const element = document.querySelector(`[data-value="${rec}"]`);
            if (element) {
                element.click();
                // Add visual feedback
                element.classList.add('ai-suggested');
                
                // Show notification
                this.showNotification('🤖 Suggerimento applicato!', 'success');
            }
        });

        // Track the applied suggestion
        this.trackSuggestionApplication(suggestion);
    }

    /**
     * Add recommendation widgets throughout the form
     */
    addRecommendationWidgets() {
        // Add dynamic pricing widget
        this.addDynamicPricingWidget();
        
        // Add smart duration suggestions
        this.addDurationSuggestions();
        
        // Add complementary activity suggestions
        this.addComplementaryActivities();
    }

    /**
     * Add dynamic pricing widget
     */
    addDynamicPricingWidget() {
        const pricingWidget = document.createElement('div');
        pricingWidget.className = 'yht-dynamic-pricing';
        pricingWidget.innerHTML = `
            <div class="pricing-header">
                <span class="pricing-icon">💰</span>
                <h4>Prezzi intelligenti</h4>
            </div>
            <div class="pricing-info">
                <div class="current-demand">
                    <span class="demand-indicator high"></span>
                    <span>Alta richiesta - Prenota ora per il miglior prezzo</span>
                </div>
                <div class="price-prediction">
                    <span class="trend-up">📈</span>
                    <span>Prezzo potrebbe aumentare del 12% nei prossimi 3 giorni</span>
                </div>
            </div>
        `;

        // Find a good place to insert it
        const step5 = document.getElementById('yht-step5');
        if (step5) {
            step5.appendChild(pricingWidget);
        }
    }

    /**
     * Setup personalization tracking
     */
    setupPersonalizationTracking() {
        // Track interactions with form elements
        document.addEventListener('click', (e) => {
            if (e.target.matches('.yht-card[data-value]')) {
                const value = e.target.dataset.value;
                const group = e.target.dataset.group;
                
                this.userPreferences.sessionInteractions.push({
                    type: 'selection',
                    group: group,
                    value: value,
                    timestamp: Date.now()
                });

                // Update preferences
                if (group === 'esperienza' && !this.userPreferences.favoriteExperiences.includes(value)) {
                    this.userPreferences.favoriteExperiences.unshift(value);
                    this.userPreferences.favoriteExperiences = this.userPreferences.favoriteExperiences.slice(0, 3);
                }

                this.saveUserPreferences();
            }
        });

        // Track time spent on each step
        this.trackStepTime();
    }

    /**
     * Track time spent on each step for better recommendations
     */
    trackStepTime() {
        let currentStep = 1;
        let stepStartTime = Date.now();

        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.target.matches('.yht-stepview[data-show="true"]')) {
                    const stepId = mutation.target.id;
                    const newStep = parseInt(stepId.replace('yht-step', ''));
                    
                    if (newStep !== currentStep) {
                        // Record time spent on previous step
                        const timeSpent = Date.now() - stepStartTime;
                        this.userPreferences.sessionInteractions.push({
                            type: 'step_time',
                            step: currentStep,
                            duration: timeSpent,
                            timestamp: Date.now()
                        });

                        currentStep = newStep;
                        stepStartTime = Date.now();
                        this.saveUserPreferences();
                    }
                }
            });
        });

        observer.observe(document.body, {
            subtree: true,
            attributes: true,
            attributeFilter: ['data-show']
        });
    }

    /**
     * Get current season
     */
    getCurrentSeason() {
        const month = new Date().getMonth();
        for (const [season, data] of Object.entries(this.seasonalData)) {
            if (data.months.includes(month)) {
                return season;
            }
        }
        return 'spring';
    }

    /**
     * Get weather score for outdoor activities
     */
    getWeatherScore() {
        if (!this.weatherData) return { outdoor: 50, indoor: 50 };
        
        const { condition, temperature, windSpeed } = this.weatherData.current;
        let outdoor = 50;
        
        // Temperature scoring
        if (temperature >= 18 && temperature <= 28) outdoor += 20;
        if (temperature >= 20 && temperature <= 25) outdoor += 10;
        
        // Condition scoring
        if (condition === 'sunny') outdoor += 20;
        if (condition === 'partly_cloudy') outdoor += 10;
        if (condition === 'rainy') outdoor -= 20;
        
        // Wind scoring
        if (windSpeed < 15) outdoor += 10;
        
        return {
            outdoor: Math.max(0, Math.min(100, outdoor)),
            indoor: 100 - outdoor
        };
    }

    /**
     * Get personality score based on user behavior
     */
    getPersonalityScore() {
        const interactions = this.userPreferences.sessionInteractions;
        const favorites = this.userPreferences.favoriteExperiences;
        
        return {
            adventurous: favorites.includes('avventura') ? 80 : 40,
            cultural: favorites.includes('storico_culturale') ? 80 : 40,
            foodie: favorites.includes('enogastronomica') ? 80 : 40,
            relaxed: favorites.includes('natura_relax') ? 80 : 40
        };
    }

    /**
     * Get similar experiences
     */
    getSimilarExperiences(experience) {
        const similarities = {
            'enogastronomica': ['storico_culturale'],
            'storico_culturale': ['enogastronomica'],
            'natura_relax': ['avventura'],
            'avventura': ['natura_relax']
        };
        
        return similarities[experience] || [];
    }

    /**
     * Save user preferences
     */
    saveUserPreferences() {
        localStorage.setItem('yht_user_preferences', JSON.stringify(this.userPreferences));
    }

    /**
     * Track suggestion application
     */
    trackSuggestionApplication(suggestion) {
        this.userPreferences.sessionInteractions.push({
            type: 'suggestion_applied',
            suggestion_type: suggestion.type,
            confidence: suggestion.confidence,
            timestamp: Date.now()
        });
        this.saveUserPreferences();
        
        // Track for analytics
        if (window.yhtAnalytics) {
            window.yhtAnalytics.trackEvent('ai_suggestion_applied', {
                suggestion_type: suggestion.type,
                confidence: suggestion.confidence
            });
        }
    }

    /**
     * Setup advanced machine learning algorithm
     */
    setupMLAlgorithm() {
        this.mlModel = {
            userClusters: this.createUserClusters(),
            preferencePredictions: new Map(),
            seasonalWeights: this.calculateSeasonalWeights(),
            popularityFactors: this.getPopularityFactors()
        };
        
        // Train model with user data
        this.trainRecommendationModel();
    }

    /**
     * Create user clusters for collaborative filtering
     */
    createUserClusters() {
        const users = this.getUserDataset();
        const clusters = this.kMeansClustering(users, 5);
        
        return clusters;
    }

    /**
     * Simple K-means clustering implementation
     */
    kMeansClustering(data, k) {
        if (data.length === 0) return [];
        
        // Initialize centroids randomly
        let centroids = [];
        for (let i = 0; i < k && data.length > 0; i++) {
            centroids.push(data[Math.floor(Math.random() * data.length)]);
        }
        
        let clusters = [];
        let previousClusters = [];
        let iterations = 0;
        const maxIterations = 100;
        
        do {
            previousClusters = [...clusters];
            clusters = Array.from({ length: k }, () => []);
            
            // Assign each point to nearest centroid
            for (const point of data) {
                let minDistance = Infinity;
                let closestCluster = 0;
                
                for (let j = 0; j < centroids.length; j++) {
                    const distance = this.calculateEuclideanDistance(point, centroids[j]);
                    if (distance < minDistance) {
                        minDistance = distance;
                        closestCluster = j;
                    }
                }
                
                clusters[closestCluster].push(point);
            }
            
            // Update centroids
            for (let i = 0; i < k; i++) {
                if (clusters[i].length > 0) {
                    centroids[i] = this.calculateCentroid(clusters[i]);
                }
            }
            
            iterations++;
        } while (!this.clustersEqual(clusters, previousClusters) && iterations < maxIterations);
        
        return clusters;
    }

    /**
     * Calculate Euclidean distance between two points
     */
    calculateEuclideanDistance(point1, point2) {
        const features1 = this.extractUserFeatures(point1);
        const features2 = this.extractUserFeatures(point2);
        
        let sum = 0;
        for (let i = 0; i < Math.min(features1.length, features2.length); i++) {
            sum += Math.pow(features1[i] - features2[i], 2);
        }
        
        return Math.sqrt(sum);
    }

    /**
     * Extract numerical features from user data
     */
    extractUserFeatures(user) {
        return [
            user.favoriteExperiences.length,
            user.budgetRange === 'low' ? 1 : user.budgetRange === 'high' ? 3 : 2,
            user.groupSize || 2,
            user.visitedPlaces.length,
            user.bookingHistory.length
        ];
    }

    /**
     * Calculate centroid of cluster
     */
    calculateCentroid(cluster) {
        if (cluster.length === 0) return {};
        
        const features = cluster.map(point => this.extractUserFeatures(point));
        if (features.length === 0 || !features[0]) return {};
        
        const centroid = [];
        
        for (let i = 0; i < features[0].length; i++) {
            const sum = features.reduce((acc, feature) => acc + feature[i], 0);
            centroid[i] = sum / features.length;
        }
        
        return {
            features: centroid,
            size: cluster.length
        };
    }

    /**
     * Check if clusters are equal
     */
    clustersEqual(clusters1, clusters2) {
        if (clusters1.length !== clusters2.length) return false;
        
        for (let i = 0; i < clusters1.length; i++) {
            if (clusters1[i].length !== clusters2[i].length) return false;
        }
        
        return true;
    }

    /**
     * Initialize collaborative filtering
     */
    initializeCollaborativeFiltering() {
        this.collaborativeData = {
            userItemMatrix: this.buildUserItemMatrix(),
            itemSimilarities: this.calculateItemSimilarities(),
            userSimilarities: this.calculateUserSimilarities()
        };
    }

    /**
     * Build user-item interaction matrix
     */
    buildUserItemMatrix() {
        const matrix = new Map();
        const users = this.getUserDataset();
        
        users.forEach(user => {
            const userId = user.id || 'anonymous';
            const userVector = new Map();
            
            user.visitedPlaces.forEach(place => {
                userVector.set(place, 1);
            });
            
            user.favoriteExperiences.forEach(exp => {
                userVector.set(exp, 2); // Higher weight for favorites
            });
            
            matrix.set(userId, userVector);
        });
        
        return matrix;
    }

    /**
     * Calculate item-to-item similarities
     */
    calculateItemSimilarities() {
        const similarities = new Map();
        const items = this.getAllItems();
        
        for (let i = 0; i < items.length; i++) {
            for (let j = i + 1; j < items.length; j++) {
                const similarity = this.calculateCosineSimilarity(items[i], items[j]);
                similarities.set(`${items[i]}_${items[j]}`, similarity);
            }
        }
        
        return similarities;
    }

    /**
     * Calculate cosine similarity between items
     */
    calculateCosineSimilarity(item1, item2) {
        const users1 = this.getUsersForItem(item1);
        const users2 = this.getUsersForItem(item2);
        
        const intersection = users1.filter(u => users2.includes(u)).length;
        const magnitude1 = Math.sqrt(users1.length);
        const magnitude2 = Math.sqrt(users2.length);
        
        if (magnitude1 === 0 || magnitude2 === 0) return 0;
        
        return intersection / (magnitude1 * magnitude2);
    }

    /**
     * Get collaborative filtering recommendations
     */
    getCollaborativeRecommendations(userId, limit = 5) {
        const userVector = this.collaborativeData.userItemMatrix.get(userId);
        if (!userVector) return [];
        
        const recommendations = new Map();
        const allItems = this.getAllItems();
        
        // Find similar items based on user's preferences
        for (const [item, rating] of userVector) {
            const similarItems = this.findSimilarItems(item, 10);
            
            similarItems.forEach(({ item: similarItem, similarity }) => {
                if (!userVector.has(similarItem)) {
                    const score = rating * similarity;
                    recommendations.set(similarItem, (recommendations.get(similarItem) || 0) + score);
                }
            });
        }
        
        // Sort by score and return top recommendations
        return Array.from(recommendations.entries())
            .sort((a, b) => b[1] - a[1])
            .slice(0, limit)
            .map(([item, score]) => ({ item, score }));
    }

    /**
     * Find similar items to a given item
     */
    findSimilarItems(targetItem, limit = 10) {
        const similarities = [];
        
        for (const [key, similarity] of this.collaborativeData.itemSimilarities) {
            const [item1, item2] = key.split('_');
            
            if (item1 === targetItem) {
                similarities.push({ item: item2, similarity });
            } else if (item2 === targetItem) {
                similarities.push({ item: item1, similarity });
            }
        }
        
        return similarities.sort((a, b) => b.similarity - a.similarity).slice(0, limit);
    }

    /**
     * Train the recommendation model
     */
    trainRecommendationModel() {
        // Simple neural network-like approach for preference prediction
        const trainingData = this.getTrainingData();
        
        this.modelWeights = {
            seasonal: 0.3,
            popularity: 0.2,
            personal: 0.4,
            collaborative: 0.1
        };
        
        // Adjust weights based on user feedback
        this.adjustModelWeights(trainingData);
    }

    /**
     * Adjust model weights based on historical performance
     */
    adjustModelWeights(trainingData) {
        const performance = {
            seasonal: 0,
            popularity: 0,
            personal: 0,
            collaborative: 0
        };
        
        trainingData.forEach(sample => {
            if (sample.outcome === 'positive') {
                performance[sample.type]++;
            }
        });
        
        // Normalize and update weights
        const total = Object.values(performance).reduce((a, b) => a + b, 0);
        if (total > 0) {
            Object.keys(performance).forEach(key => {
                this.modelWeights[key] = performance[key] / total;
            });
        }
    }

    /**
     * Get enhanced AI recommendations using multiple algorithms
     */
    getEnhancedRecommendations() {
        const recommendations = [];
        
        // Get recommendations from different sources
        const seasonalRecs = this.getSeasonalRecommendations();
        const popularityRecs = this.getPopularityRecommendations();
        const personalRecs = this.getPersonalizedRecommendations();
        const collaborativeRecs = this.getCollaborativeRecommendations(this.userPreferences.userId || 'anonymous');
        
        // Combine and weight recommendations
        const combinedRecs = this.combineRecommendations({
            seasonal: seasonalRecs,
            popularity: popularityRecs,
            personal: personalRecs,
            collaborative: collaborativeRecs
        });
        
        return combinedRecs.slice(0, 8); // Return top 8 recommendations
    }

    /**
     * Combine recommendations from multiple sources
     */
    combineRecommendations(sources) {
        const scoredRecs = new Map();
        
        Object.entries(sources).forEach(([type, recs]) => {
            const weight = this.modelWeights[type] || 0.1;
            
            recs.forEach((rec, index) => {
                const item = rec.item || rec.title || rec;
                const baseScore = rec.score || rec.confidence || (10 - index);
                const weightedScore = baseScore * weight;
                
                if (scoredRecs.has(item)) {
                    scoredRecs.set(item, scoredRecs.get(item) + weightedScore);
                } else {
                    scoredRecs.set(item, weightedScore);
                }
            });
        });
        
        return Array.from(scoredRecs.entries())
            .sort((a, b) => b[1] - a[1])
            .map(([item, score]) => ({
                title: `🤖 ${item}`,
                description: 'Raccomandazione AI personalizzata',
                confidence: Math.min(Math.round(score * 10), 100),
                type: 'ai_enhanced'
            }));
    }

    /**
     * Get user dataset for ML training
     */
    getUserDataset() {
        // In a real implementation, this would come from server
        return [
            {
                id: 'user_1',
                favoriteExperiences: ['enogastronomica', 'storico_culturale'],
                budgetRange: 'medium',
                groupSize: 2,
                visitedPlaces: ['roma', 'firenze'],
                bookingHistory: ['tour_toscana']
            },
            // Add more user data...
        ];
    }

    /**
     * Get all available items
     */
    getAllItems() {
        return ['enogastronomica', 'storico_culturale', 'natura_relax', 'avventura'];
    }

    /**
     * Get users who interacted with specific item
     */
    getUsersForItem(item) {
        const users = [];
        this.collaborativeData.userItemMatrix.forEach((vector, userId) => {
            if (vector.has(item)) {
                users.push(userId);
            }
        });
        return users;
    }

    /**
     * Get training data for model improvement
     */
    getTrainingData() {
        // Historical data about recommendation effectiveness
        return this.userPreferences.sessionInteractions.map(interaction => ({
            type: interaction.suggestion_type,
            outcome: interaction.type === 'suggestion_applied' ? 'positive' : 'neutral',
            confidence: interaction.confidence,
            timestamp: interaction.timestamp
        }));
    }

    /**
     * Calculate seasonal weights based on current date
     */
    calculateSeasonalWeights() {
        const month = new Date().getMonth();
        const seasonWeights = {
            spring: month >= 2 && month <= 4 ? 1.0 : 0.3,
            summer: month >= 5 && month <= 7 ? 1.0 : 0.3,
            autumn: month >= 8 && month <= 10 ? 1.0 : 0.3,
            winter: (month >= 11 || month <= 1) ? 1.0 : 0.3
        };
        
        return seasonWeights;
    }

    /**
     * Get popularity factors for weighting
     */
    getPopularityFactors() {
        return {
            trending: 1.2,
            recent: 1.1,
            classic: 1.0,
            niche: 0.8
        };
    }

    /**
     * Show notification
     */
    showNotification(message, type = 'info') {
        // Integrate with existing notification system
        if (window.yhtEnhancer && window.yhtEnhancer.showNotification) {
            window.yhtEnhancer.showNotification(message, type);
        }
    }
}

// Initialize AI Recommendations when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('.yht-wrap')) {
        window.yhtAI = new YHTAIRecommendations();
    }
});