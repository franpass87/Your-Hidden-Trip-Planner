/**
 * Gamification & Social Proof System
 * Adds competitive engagement features
 * 
 * @package YourHiddenTrip
 * @version 6.3
 */

class YHTGamification {
    constructor() {
        this.userStats = this.loadUserStats();
        this.achievements = this.getAchievements();
        this.socialProof = new YHTSocialProof();
        this.init();
    }

    init() {
        this.setupProgressTracking();
        this.setupAchievementSystem();
        this.setupSocialProofElements();
        this.setupRealtimeFeatures();
        this.setupPersonalizationRewards();
    }

    /**
     * Load user statistics from localStorage
     */
    loadUserStats() {
        const stored = localStorage.getItem('yht_user_stats');
        return stored ? JSON.parse(stored) : {
            completedSteps: 0,
            totalInteractions: 0,
            bookingsCompleted: 0,
            timeSpent: 0,
            favoriteExperiences: [],
            achievementsBadges: [],
            streakDays: 0,
            lastVisit: null,
            totalVisits: 0,
            experiencePoints: 0,
            level: 1,
            unlockedFeatures: ['basic_builder']
        };
    }

    /**
     * Get available achievements
     */
    getAchievements() {
        return [
            {
                id: 'first_steps',
                name: '🎯 Primi Passi',
                description: 'Hai completato il tuo primo step nel trip builder',
                requirement: (stats) => stats.completedSteps >= 1,
                reward: 10,
                unlocks: ['progress_tracker']
            },
            {
                id: 'explorer',
                name: '🗺️ Esploratore',
                description: 'Hai completato tutti i 6 step del builder',
                requirement: (stats) => stats.completedSteps >= 6,
                reward: 50,
                unlocks: ['advanced_filters', 'price_predictor']
            },
            {
                id: 'wine_lover',
                name: '🍷 Amante del Vino',
                description: 'Hai scelto l\'esperienza enogastronomica 3 volte',
                requirement: (stats) => this.countExperienceChoice(stats, 'enogastronomica') >= 3,
                reward: 30,
                unlocks: ['wine_pairing_suggestions']
            },
            {
                id: 'culture_enthusiast',
                name: '🏛️ Appassionato di Cultura',
                description: 'Hai scelto l\'esperienza storico-culturale 3 volte',
                requirement: (stats) => this.countExperienceChoice(stats, 'storico_culturale') >= 3,
                reward: 30,
                unlocks: ['historical_timeline']
            },
            {
                id: 'nature_seeker',
                name: '🌿 Amante della Natura',
                description: 'Hai scelto natura e relax 3 volte',
                requirement: (stats) => this.countExperienceChoice(stats, 'natura_relax') >= 3,
                reward: 30,
                unlocks: ['weather_integration']
            },
            {
                id: 'adventurer',
                name: '⛰️ Avventuriero',
                description: 'Hai scelto avventura outdoor 3 volte',
                requirement: (stats) => this.countExperienceChoice(stats, 'avventura') >= 3,
                reward: 30,
                unlocks: ['adventure_challenges']
            },
            {
                id: 'regular_visitor',
                name: '🔄 Visitatore Abituale',
                description: 'Hai visitato il sito per 7 giorni consecutivi',
                requirement: (stats) => stats.streakDays >= 7,
                reward: 100,
                unlocks: ['loyalty_discounts', 'vip_support']
            },
            {
                id: 'booking_master',
                name: '📋 Maestro delle Prenotazioni',
                description: 'Hai completato 5 prenotazioni',
                requirement: (stats) => stats.bookingsCompleted >= 5,
                reward: 200,
                unlocks: ['premium_features', 'concierge_service']
            },
            {
                id: 'social_ambassador',
                name: '📤 Ambasciatore Social',
                description: 'Hai condiviso 10 viaggi sui social',
                requirement: (stats) => stats.socialShares >= 10,
                reward: 75,
                unlocks: ['referral_program']
            }
        ];
    }

    /**
     * Setup progress tracking for gamification
     */
    setupProgressTracking() {
        // Track step completion
        const stepObserver = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.target.matches('.yht-stepview[data-show="true"]')) {
                    this.trackStepCompletion(mutation.target.id);
                }
            });
        });

        stepObserver.observe(document.body, {
            subtree: true,
            attributes: true,
            attributeFilter: ['data-show']
        });

        // Track interactions
        document.addEventListener('click', (e) => {
            if (e.target.matches('.yht-card[data-value]') || e.target.closest('.yht-card[data-value]')) {
                this.trackInteraction('card_selection');
            }
            
            if (e.target.matches('.yht-btn') || e.target.closest('.yht-btn')) {
                this.trackInteraction('button_click');
            }
        });

        // Track time spent
        this.startTimeTracking();
        
        // Track visit
        this.trackVisit();
    }

    /**
     * Track step completion
     */
    trackStepCompletion(stepId) {
        const stepNumber = parseInt(stepId.replace('yht-step', ''));
        if (stepNumber > this.userStats.completedSteps) {
            this.userStats.completedSteps = stepNumber;
            this.userStats.experiencePoints += 10;
            this.checkAchievements();
            this.updateProgressDisplay();
            
            // Show milestone for significant steps
            if (stepNumber === 3) {
                this.showMilestone('🎯 Metà completata!', 'Stai facendo progressi fantastici');
            } else if (stepNumber === 6) {
                this.showMilestone('🎉 Completato!', 'Hai completato tutto il trip builder');
            }
        }
    }

    /**
     * Track general interactions
     */
    trackInteraction(type) {
        this.userStats.totalInteractions++;
        this.userStats.experiencePoints += 1;
        
        // Check for interaction milestones
        if (this.userStats.totalInteractions % 50 === 0) {
            this.showMilestone('🔥 Interazione Master!', `${this.userStats.totalInteractions} interazioni completate`);
        }
        
        this.saveUserStats();
    }

    /**
     * Track visit for streak calculation
     */
    trackVisit() {
        const now = new Date();
        const today = now.toDateString();
        const lastVisit = this.userStats.lastVisit ? new Date(this.userStats.lastVisit).toDateString() : null;
        
        if (lastVisit !== today) {
            this.userStats.totalVisits++;
            
            if (lastVisit) {
                const yesterday = new Date(now - 24 * 60 * 60 * 1000).toDateString();
                if (lastVisit === yesterday) {
                    this.userStats.streakDays++;
                } else {
                    this.userStats.streakDays = 1;
                }
            } else {
                this.userStats.streakDays = 1;
            }
            
            this.userStats.lastVisit = now.toISOString();
            this.checkAchievements();
        }
        
        this.saveUserStats();
    }

    /**
     * Start time tracking
     */
    startTimeTracking() {
        const startTime = Date.now();
        
        // Update time spent every 30 seconds
        setInterval(() => {
            this.userStats.timeSpent += 30;
            this.saveUserStats();
        }, 30000);
        
        // Save time on page unload
        window.addEventListener('beforeunload', () => {
            this.userStats.timeSpent += Math.floor((Date.now() - startTime) / 1000);
            this.saveUserStats();
        });
    }

    /**
     * Setup achievement system
     */
    setupAchievementSystem() {
        this.checkAchievements();
        this.displayAchievementProgress();
        this.createLevelSystem();
    }

    /**
     * Check if any achievements are unlocked
     */
    checkAchievements() {
        this.achievements.forEach(achievement => {
            if (!this.userStats.achievementsBadges.includes(achievement.id) && 
                achievement.requirement(this.userStats)) {
                this.unlockAchievement(achievement);
            }
        });
    }

    /**
     * Unlock an achievement
     */
    unlockAchievement(achievement) {
        this.userStats.achievementsBadges.push(achievement.id);
        this.userStats.experiencePoints += achievement.reward;
        
        // Unlock new features
        if (achievement.unlocks) {
            achievement.unlocks.forEach(feature => {
                if (!this.userStats.unlockedFeatures.includes(feature)) {
                    this.userStats.unlockedFeatures.push(feature);
                }
            });
        }
        
        this.showAchievementNotification(achievement);
        this.updateLevel();
        this.saveUserStats();
    }

    /**
     * Show achievement notification
     */
    showAchievementNotification(achievement) {
        const notification = document.createElement('div');
        notification.className = 'yht-achievement-badge';
        notification.innerHTML = `
            <div class="achievement-content">
                <div class="achievement-title">${achievement.name}</div>
                <div class="achievement-description">${achievement.description}</div>
                <div class="achievement-reward">+${achievement.reward} XP</div>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.style.animation = 'fadeOut 0.5s ease forwards';
            setTimeout(() => notification.remove(), 500);
        }, 5000);
        
        // Add to achievement log
        this.addToAchievementLog(achievement);
    }

    /**
     * Show progress milestone
     */
    showMilestone(title, description) {
        const milestone = document.createElement('div');
        milestone.className = 'yht-progress-milestone';
        milestone.innerHTML = `
            <span class="milestone-title">${title}</span>
            <span class="milestone-description">${description}</span>
        `;
        
        // Insert after current step
        const currentStep = document.querySelector('.yht-stepview[data-show="true"]');
        if (currentStep) {
            const actions = currentStep.querySelector('.yht-actions');
            if (actions) {
                actions.parentNode.insertBefore(milestone, actions);
            }
        }
        
        // Auto remove after 8 seconds
        setTimeout(() => {
            milestone.style.animation = 'fadeOut 0.5s ease forwards';
            setTimeout(() => milestone.remove(), 500);
        }, 8000);
    }

    /**
     * Update user level based on experience points
     */
    updateLevel() {
        const newLevel = Math.floor(this.userStats.experiencePoints / 100) + 1;
        if (newLevel > this.userStats.level) {
            const oldLevel = this.userStats.level;
            this.userStats.level = newLevel;
            this.showLevelUpNotification(oldLevel, newLevel);
        }
    }

    /**
     * Show level up notification
     */
    showLevelUpNotification(oldLevel, newLevel) {
        const notification = document.createElement('div');
        notification.className = 'yht-level-up';
        notification.innerHTML = `
            <div class="level-up-content">
                <div class="level-up-title">🎉 Livello Aumentato!</div>
                <div class="level-up-levels">Livello ${oldLevel} → ${newLevel}</div>
                <div class="level-up-features">Nuove funzionalità sbloccate!</div>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'fadeOut 0.5s ease forwards';
            setTimeout(() => notification.remove(), 500);
        }, 6000);
    }

    /**
     * Create level system display
     */
    createLevelSystem() {
        const levelWidget = document.createElement('div');
        levelWidget.className = 'yht-level-widget';
        levelWidget.innerHTML = `
            <div class="level-info">
                <div class="level-number">Lv. ${this.userStats.level}</div>
                <div class="level-progress">
                    <div class="level-bar">
                        <div class="level-fill" style="width: ${(this.userStats.experiencePoints % 100)}%"></div>
                    </div>
                    <div class="level-text">${this.userStats.experiencePoints} XP</div>
                </div>
            </div>
        `;
        
        // Add to header
        const header = document.querySelector('.yht-header');
        if (header) {
            header.appendChild(levelWidget);
        }
    }

    /**
     * Update progress display
     */
    updateProgressDisplay() {
        const progressWidget = this.createProgressWidget();
        
        // Find a good place to insert it
        const step1 = document.getElementById('yht-step1');
        if (step1 && !document.querySelector('.yht-progress-immersive')) {
            step1.parentNode.insertBefore(progressWidget, step1.nextSibling);
        }
    }

    /**
     * Create progress widget
     */
    createProgressWidget() {
        const widget = document.createElement('div');
        widget.className = 'yht-progress-immersive';
        
        const completionPercentage = Math.round((this.userStats.completedSteps / 6) * 100);
        const timeSpentMinutes = Math.round(this.userStats.timeSpent / 60);
        
        widget.innerHTML = `
            <div class="progress-header">
                <h4>📊 Il Tuo Progresso</h4>
                <div class="progress-percentage">${completionPercentage}% completato</div>
            </div>
            <div class="progress-stats">
                <div class="progress-stat">
                    <span class="progress-stat-number">${this.userStats.completedSteps}</span>
                    <span class="progress-stat-label">Step completati</span>
                </div>
                <div class="progress-stat">
                    <span class="progress-stat-number">${timeSpentMinutes}</span>
                    <span class="progress-stat-label">Minuti spesi</span>
                </div>
                <div class="progress-stat">
                    <span class="progress-stat-number">${this.userStats.experiencePoints}</span>
                    <span class="progress-stat-label">Punti esperienza</span>
                </div>
                <div class="progress-stat">
                    <span class="progress-stat-number">${this.userStats.achievementsBadges.length}</span>
                    <span class="progress-stat-label">Achievement</span>
                </div>
            </div>
        `;
        
        return widget;
    }

    /**
     * Setup social proof elements
     */
    setupSocialProofElements() {
        this.socialProof.init();
        this.addRecentBookingsWidget();
        this.addPopularityIndicators();
    }

    /**
     * Add recent bookings widget
     */
    addRecentBookingsWidget() {
        const widget = document.createElement('div');
        widget.className = 'yht-social-proof';
        widget.innerHTML = `
            <div class="social-proof-header">
                <span>👥</span>
                <span>Prenotazioni Recenti</span>
            </div>
            <div class="recent-bookings" id="recent-bookings-list">
                <div class="booking-notification">Marco da Roma ha prenotato Assisi e Perugia - 2 min fa</div>
                <div class="booking-notification">Laura da Milano ha prenotato Tour Enogastronomico - 8 min fa</div>
                <div class="booking-notification">Giuseppe da Napoli ha prenotato Mix Personalizzato - 15 min fa</div>
            </div>
        `;
        
        // Insert after the header
        const header = document.querySelector('.yht-header');
        if (header) {
            header.parentNode.insertBefore(widget, header.nextSibling);
        }
        
        // Start live booking simulation
        this.startLiveBookingUpdates();
    }

    /**
     * Start live booking updates simulation
     */
    startLiveBookingUpdates() {
        const bookingsList = document.getElementById('recent-bookings-list');
        if (!bookingsList) return;
        
        const mockBookings = [
            { name: 'Marco', city: 'Roma', tour: 'Assisi e Perugia' },
            { name: 'Laura', city: 'Milano', tour: 'Tour Enogastronomico' },
            { name: 'Giuseppe', city: 'Napoli', tour: 'Mix Personalizzato' },
            { name: 'Francesca', city: 'Firenze', tour: 'Orvieto e Civita' },
            { name: 'Antonio', city: 'Torino', tour: 'Viterbo e Terme' },
            { name: 'Giulia', city: 'Bologna', tour: 'Avventura Outdoor' },
            { name: 'Roberto', city: 'Venezia', tour: 'Natura e Relax' },
            { name: 'Elena', city: 'Bari', tour: 'Storico Culturale' }
        ];
        
        // Add a new booking every 45-90 seconds
        setInterval(() => {
            const randomBooking = mockBookings[Math.floor(Math.random() * mockBookings.length)];
            const timeAgo = Math.floor(Math.random() * 5) + 1;
            
            const notification = document.createElement('div');
            notification.className = 'booking-notification live';
            notification.innerHTML = `${randomBooking.name} da ${randomBooking.city} ha prenotato ${randomBooking.tour} - ${timeAgo} min fa`;
            
            // Add to top and remove oldest
            bookingsList.insertBefore(notification, bookingsList.firstChild);
            
            // Keep only last 3 bookings
            while (bookingsList.children.length > 3) {
                bookingsList.removeChild(bookingsList.lastChild);
            }
            
        }, Math.random() * 45000 + 45000); // 45-90 seconds
    }

    /**
     * Add popularity indicators to cards
     */
    addPopularityIndicators() {
        const popularityData = {
            'enogastronomica': { popularity: 85, label: '🔥 Molto popolare' },
            'storico_culturale': { popularity: 78, label: '📈 In crescita' },
            'natura_relax': { popularity: 72, label: '⭐ Consigliato' },
            'avventura': { popularity: 68, label: '🚀 Di tendenza' }
        };
        
        Object.keys(popularityData).forEach(experience => {
            const card = document.querySelector(`[data-value="${experience}"]`);
            if (card) {
                const indicator = document.createElement('div');
                indicator.className = 'popularity-indicator';
                indicator.innerHTML = popularityData[experience].label;
                
                card.appendChild(indicator);
            }
        });
    }

    /**
     * Setup real-time features
     */
    setupRealtimeFeatures() {
        this.addLiveAvailabilityCheck();
        this.addDynamicPricingUpdates();
    }

    /**
     * Count experience choices
     */
    countExperienceChoice(stats, experience) {
        // This would be implemented based on actual user choice tracking
        return stats.favoriteExperiences.filter(exp => exp === experience).length;
    }

    /**
     * Save user stats
     */
    saveUserStats() {
        localStorage.setItem('yht_user_stats', JSON.stringify(this.userStats));
    }

    /**
     * Add to achievement log
     */
    addToAchievementLog(achievement) {
        const logs = JSON.parse(localStorage.getItem('yht_achievement_log') || '[]');
        logs.unshift({
            achievement: achievement,
            timestamp: Date.now(),
            level: this.userStats.level
        });
        
        // Keep only last 50 achievements
        localStorage.setItem('yht_achievement_log', JSON.stringify(logs.slice(0, 50)));
    }
}

/**
 * Social Proof System
 */
class YHTSocialProof {
    constructor() {
        this.viewCount = this.getViewCount();
        this.popularTours = this.getPopularTours();
    }

    init() {
        this.trackPageView();
        this.showViewCount();
        this.addUrgencyElements();
    }

    getViewCount() {
        // Simulate realistic view counts
        return Math.floor(Math.random() * 500) + 1200;
    }

    getPopularTours() {
        return [
            { name: 'Assisi e Perugia', bookings: 147 },
            { name: 'Orvieto e Civita', bookings: 132 },
            { name: 'Tour Enogastronomico', bookings: 98 }
        ];
    }

    trackPageView() {
        this.viewCount++;
        // In a real implementation, this would send to analytics
    }

    showViewCount() {
        const counter = document.createElement('div');
        counter.className = 'view-counter';
        counter.innerHTML = `👁️ ${this.viewCount.toLocaleString()} persone hanno visto questa pagina`;
        
        const wrap = document.querySelector('.yht-wrap');
        if (wrap) {
            wrap.appendChild(counter);
        }
    }

    addUrgencyElements() {
        // Add limited time offers
        const urgencyWidget = document.createElement('div');
        urgencyWidget.className = 'urgency-widget';
        urgencyWidget.innerHTML = `
            <div class="urgency-header">⏰ Offerta Limitata</div>
            <div class="urgency-message">Solo 3 posti rimasti per questo weekend!</div>
            <div class="urgency-timer" id="urgency-timer">23:45:12</div>
        `;
        
        // Start countdown
        this.startUrgencyTimer();
        
        const step6 = document.getElementById('yht-step6');
        if (step6) {
            step6.appendChild(urgencyWidget);
        }
    }

    startUrgencyTimer() {
        // Start with a random time between 12-48 hours
        let timeLeft = Math.random() * 36 * 3600 + 12 * 3600; // 12-48 hours in seconds
        
        setInterval(() => {
            timeLeft--;
            if (timeLeft <= 0) {
                timeLeft = 24 * 3600; // Reset to 24 hours
            }
            
            const hours = Math.floor(timeLeft / 3600);
            const minutes = Math.floor((timeLeft % 3600) / 60);
            const seconds = Math.floor(timeLeft % 60);
            
            const timer = document.getElementById('urgency-timer');
            if (timer) {
                timer.textContent = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }
        }, 1000);
    }
}

// Initialize gamification when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('.yht-wrap')) {
        window.yhtGamification = new YHTGamification();
    }
});