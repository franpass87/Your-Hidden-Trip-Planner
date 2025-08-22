/**
 * Advanced Search and Filters Module
 * 
 * @package YourHiddenTrip
 * @version 6.2
 */

class YHTSearch {
    constructor() {
        this.currentFilters = {};
        this.searchResults = [];
        this.isLoading = false;
        this.sortBy = 'relevance';
        
        this.init();
    }
    
    init() {
        this.createSearchInterface();
        this.bindEvents();
        this.loadSavedFilters();
    }
    
    createSearchInterface() {
        const container = document.querySelector('.yht-wrap');
        if (!container) return;
        
        // Create search filters HTML
        const filtersHTML = this.getSearchFiltersHTML();
        const searchContainer = document.createElement('div');
        searchContainer.className = 'yht-search-container';
        searchContainer.innerHTML = filtersHTML;
        
        // Insert after the header
        const header = container.querySelector('.yht-header');
        if (header) {
            header.parentNode.insertBefore(searchContainer, header.nextSibling);
        }
        
        // Create results container
        this.createResultsContainer();
    }
    
    getSearchFiltersHTML() {
        return `
            <div class="yht-search-filters">
                <div class="yht-filters-header">
                    <h3 class="yht-filters-title">🔍 Ricerca Avanzata</h3>
                    <button class="yht-filters-toggle" data-toggle="filters">
                        <span class="toggle-text">Mostra Filtri</span>
                        <span class="toggle-icon">▼</span>
                    </button>
                </div>
                
                <div class="yht-filters-content" id="yht-filters-content">
                    <div class="yht-filter-group">
                        <label class="yht-filter-label">Destinazione</label>
                        <select class="yht-filter-select" data-filter="destination">
                            <option value="">Tutte le destinazioni</option>
                            <option value="viterbo_tuscia">Viterbo e Alta Tuscia</option>
                            <option value="lago_bolsena">Lago di Bolsena</option>
                            <option value="orvieto_umbria">Orvieto e Umbria Sud</option>
                            <option value="todi_spoleto">Todi e Spoleto</option>
                            <option value="assisi_perugia">Assisi e Perugia</option>
                        </select>
                    </div>
                    
                    <div class="yht-filter-group">
                        <label class="yht-filter-label">Tipo Esperienza</label>
                        <select class="yht-filter-select" data-filter="experience">
                            <option value="">Tutti i tipi</option>
                            <option value="enogastronomica">Enogastronomica</option>
                            <option value="storico_culturale">Storico-Culturale</option>
                            <option value="natura_relax">Natura e Relax</option>
                            <option value="avventura">Avventura</option>
                            <option value="romantica">Romantica</option>
                            <option value="famiglia">Famiglia</option>
                        </select>
                    </div>
                    
                    <div class="yht-filter-group">
                        <label class="yht-filter-label">Durata</label>
                        <select class="yht-filter-select" data-filter="duration">
                            <option value="">Qualsiasi durata</option>
                            <option value="1_giorno">1 Giorno</option>
                            <option value="2_giorni">2 Giorni/1 Notte</option>
                            <option value="3_giorni">3 Giorni/2 Notti</option>
                            <option value="4_giorni">4 Giorni/3 Notti</option>
                            <option value="5+_notti">5+ Notti</option>
                        </select>
                    </div>
                    
                    <div class="yht-filter-group">
                        <label class="yht-filter-label">Fascia di Prezzo (€)</label>
                        <div class="yht-price-range">
                            <input type="number" class="yht-filter-input" placeholder="Min" data-filter="price_min" min="0">
                            <span class="yht-price-separator">-</span>
                            <input type="number" class="yht-filter-input" placeholder="Max" data-filter="price_max" min="0">
                        </div>
                    </div>
                    
                    <div class="yht-filter-group">
                        <label class="yht-filter-label">Valutazione Minima</label>
                        <select class="yht-filter-select" data-filter="rating">
                            <option value="">Qualsiasi valutazione</option>
                            <option value="4.5">4.5+ stelle</option>
                            <option value="4.0">4.0+ stelle</option>
                            <option value="3.5">3.5+ stelle</option>
                            <option value="3.0">3.0+ stelle</option>
                        </select>
                    </div>
                    
                    <div class="yht-filter-group">
                        <label class="yht-filter-label">Periodo</label>
                        <select class="yht-filter-select" data-filter="season">
                            <option value="">Tutto l'anno</option>
                            <option value="spring">Primavera</option>
                            <option value="summer">Estate</option>
                            <option value="autumn">Autunno</option>
                            <option value="winter">Inverno</option>
                        </select>
                    </div>
                </div>
                
                <div class="yht-filter-tags" id="yht-active-filters"></div>
                
                <div class="yht-filters-actions" style="display:none;">
                    <button class="yht-clear-filters" type="button">Pulisci Filtri</button>
                    <button class="yht-apply-filters" type="button">Applica Filtri</button>
                </div>
            </div>
        `;
    }
    
    createResultsContainer() {
        const container = document.querySelector('.yht-wrap');
        if (!container) return;
        
        const resultsHTML = `
            <div class="yht-search-results" id="yht-search-results" style="display:none;">
                <div class="yht-results-header">
                    <div class="yht-results-count" id="yht-results-count">0 risultati trovati</div>
                    <select class="yht-sort-select" id="yht-sort-select">
                        <option value="relevance">Più rilevanti</option>
                        <option value="price_asc">Prezzo: dal più basso</option>
                        <option value="price_desc">Prezzo: dal più alto</option>
                        <option value="rating">Valutazione più alta</option>
                        <option value="popularity">Più popolari</option>
                        <option value="newest">Più recenti</option>
                    </select>
                </div>
                <div class="yht-results-list" id="yht-results-list">
                    <!-- Results will be populated here -->
                </div>
            </div>
        `;
        
        const searchContainer = container.querySelector('.yht-search-container');
        if (searchContainer) {
            searchContainer.insertAdjacentHTML('afterend', resultsHTML);
        }
    }
    
    bindEvents() {
        // Toggle filters visibility
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-toggle="filters"]')) {
                this.toggleFilters();
            }
        });
        
        // Filter change events
        document.addEventListener('change', (e) => {
            if (e.target.matches('.yht-filter-select, .yht-filter-input')) {
                this.updateFilter(e.target);
            }
            
            if (e.target.matches('#yht-sort-select')) {
                this.updateSort(e.target.value);
            }
        });
        
        // Filter input events for real-time filtering
        document.addEventListener('input', (e) => {
            if (e.target.matches('.yht-filter-input')) {
                clearTimeout(this.debounceTimer);
                this.debounceTimer = setTimeout(() => {
                    this.updateFilter(e.target);
                }, 300);
            }
        });
        
        // Clear filters
        document.addEventListener('click', (e) => {
            if (e.target.matches('.yht-clear-filters')) {
                this.clearAllFilters();
            }
            
            if (e.target.matches('.yht-apply-filters')) {
                this.applyFilters();
            }
            
            if (e.target.matches('.yht-filter-tag-remove')) {
                this.removeFilter(e.target.closest('.yht-filter-tag').dataset.filter);
            }
        });
    }
    
    toggleFilters() {
        const content = document.getElementById('yht-filters-content');
        const toggle = document.querySelector('[data-toggle="filters"]');
        const actions = document.querySelector('.yht-filters-actions');
        
        if (!content || !toggle) return;
        
        const isExpanded = content.classList.contains('expanded');
        
        if (isExpanded) {
            content.classList.remove('expanded');
            toggle.querySelector('.toggle-text').textContent = 'Mostra Filtri';
            toggle.querySelector('.toggle-icon').textContent = '▼';
            actions.style.display = 'none';
        } else {
            content.classList.add('expanded');
            toggle.querySelector('.toggle-text').textContent = 'Nascondi Filtri';
            toggle.querySelector('.toggle-icon').textContent = '▲';
            actions.style.display = 'flex';
        }
    }
    
    updateFilter(element) {
        const filterKey = element.dataset.filter;
        const filterValue = element.value.trim();
        
        if (filterValue) {
            this.currentFilters[filterKey] = filterValue;
        } else {
            delete this.currentFilters[filterKey];
        }
        
        this.updateFilterTags();
        this.performSearch();
        this.saveFilters();
    }
    
    updateFilterTags() {
        const container = document.getElementById('yht-active-filters');
        if (!container) return;
        
        container.innerHTML = '';
        
        Object.entries(this.currentFilters).forEach(([key, value]) => {
            const tag = document.createElement('div');
            tag.className = 'yht-filter-tag';
            tag.dataset.filter = key;
            
            const label = this.getFilterLabel(key, value);
            
            tag.innerHTML = `
                <span>${label}</span>
                <button class="yht-filter-tag-remove" type="button">✕</button>
            `;
            
            container.appendChild(tag);
        });
        
        // Show/hide clear button
        const actionsContainer = document.querySelector('.yht-filters-actions');
        if (actionsContainer) {
            const hasFilters = Object.keys(this.currentFilters).length > 0;
            actionsContainer.style.display = hasFilters ? 'flex' : 'none';
        }
    }
    
    getFilterLabel(key, value) {
        const labels = {
            destination: {
                'viterbo_tuscia': 'Viterbo e Alta Tuscia',
                'lago_bolsena': 'Lago di Bolsena',
                'orvieto_umbria': 'Orvieto e Umbria Sud',
                'todi_spoleto': 'Todi e Spoleto',
                'assisi_perugia': 'Assisi e Perugia'
            },
            experience: {
                'enogastronomica': 'Enogastronomica',
                'storico_culturale': 'Storico-Culturale',
                'natura_relax': 'Natura e Relax',
                'avventura': 'Avventura',
                'romantica': 'Romantica',
                'famiglia': 'Famiglia'
            },
            duration: {
                '1_giorno': '1 Giorno',
                '2_giorni': '2 Giorni/1 Notte',
                '3_giorni': '3 Giorni/2 Notti',
                '4_giorni': '4 Giorni/3 Notti',
                '5+_notti': '5+ Notti'
            },
            season: {
                'spring': 'Primavera',
                'summer': 'Estate',
                'autumn': 'Autunno',
                'winter': 'Inverno'
            }
        };
        
        if (key === 'price_min') return `Min €${value}`;
        if (key === 'price_max') return `Max €${value}`;
        if (key === 'rating') return `${value}+ stelle`;
        
        return labels[key]?.[value] || value;
    }
    
    removeFilter(filterKey) {
        delete this.currentFilters[filterKey];
        
        // Update form elements
        const element = document.querySelector(`[data-filter="${filterKey}"]`);
        if (element) {
            element.value = '';
        }
        
        this.updateFilterTags();
        this.performSearch();
        this.saveFilters();
    }
    
    clearAllFilters() {
        this.currentFilters = {};
        
        // Clear all form elements
        document.querySelectorAll('.yht-filter-select, .yht-filter-input').forEach(element => {
            element.value = '';
        });
        
        this.updateFilterTags();
        this.performSearch();
        this.saveFilters();
    }
    
    applyFilters() {
        this.performSearch();
    }
    
    updateSort(sortValue) {
        this.sortBy = sortValue;
        this.sortResults();
        this.renderResults();
    }
    
    performSearch() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoading();
        
        // Simulate API call with timeout
        setTimeout(() => {
            this.searchResults = this.mockSearch();
            this.sortResults();
            this.renderResults();
            this.isLoading = false;
            this.hideLoading();
        }, 1000);
    }
    
    mockSearch() {
        // This would normally call your backend API
        const mockResults = [
            {
                id: 1,
                title: 'Tour Enogastronomico Viterbo',
                subtitle: 'Degustazioni e cantine storiche',
                destination: 'viterbo_tuscia',
                experience: 'enogastronomica',
                duration: '2_giorni',
                price: 150,
                rating: 4.8,
                reviews: 147,
                features: ['Guida esperta', 'Trasporti inclusi', 'Degustazioni'],
                image: '',
                availability: 'Disponibile',
                season: ['spring', 'summer', 'autumn']
            },
            {
                id: 2,
                title: 'Weekend Romantico Orvieto',
                subtitle: 'Cena con vista e spa relax',
                destination: 'orvieto_umbria',
                experience: 'romantica',
                duration: '2_giorni',
                price: 280,
                rating: 4.9,
                reviews: 89,
                features: ['Spa inclusa', 'Cena romantica', 'Camera premium'],
                image: '',
                availability: 'Ultimi 3 posti',
                season: ['spring', 'summer', 'autumn', 'winter']
            },
            {
                id: 3,
                title: 'Avventura Lago di Bolsena',
                subtitle: 'Trekking, kayak e natura',
                destination: 'lago_bolsena',
                experience: 'avventura',
                duration: '3_giorni',
                price: 195,
                rating: 4.6,
                reviews: 203,
                features: ['Attrezzatura inclusa', 'Guida naturalistica', 'Pernottamento'],
                image: '',
                availability: 'Disponibile',
                season: ['spring', 'summer', 'autumn']
            },
            {
                id: 4,
                title: 'Tour Famiglie Assisi',
                subtitle: 'Attività per bambini e cultura',
                destination: 'assisi_perugia',
                experience: 'famiglia',
                duration: '2_giorni',
                price: 120,
                rating: 4.7,
                reviews: 156,
                features: ['Kid-friendly', 'Laboratori creativi', 'Pranzi inclusi'],
                image: '',
                availability: 'Disponibile',
                season: ['spring', 'summer', 'autumn', 'winter']
            }
        ];
        
        // Apply filters
        return mockResults.filter(result => {
            return this.matchesFilters(result);
        });
    }
    
    matchesFilters(result) {
        for (const [key, value] of Object.entries(this.currentFilters)) {
            switch (key) {
                case 'destination':
                    if (result.destination !== value) return false;
                    break;
                case 'experience':
                    if (result.experience !== value) return false;
                    break;
                case 'duration':
                    if (result.duration !== value) return false;
                    break;
                case 'price_min':
                    if (result.price < parseInt(value)) return false;
                    break;
                case 'price_max':
                    if (result.price > parseInt(value)) return false;
                    break;
                case 'rating':
                    if (result.rating < parseFloat(value)) return false;
                    break;
                case 'season':
                    if (!result.season.includes(value)) return false;
                    break;
            }
        }
        return true;
    }
    
    sortResults() {
        this.searchResults.sort((a, b) => {
            switch (this.sortBy) {
                case 'price_asc':
                    return a.price - b.price;
                case 'price_desc':
                    return b.price - a.price;
                case 'rating':
                    return b.rating - a.rating;
                case 'popularity':
                    return b.reviews - a.reviews;
                case 'newest':
                    return b.id - a.id;
                default:
                    return 0;
            }
        });
    }
    
    renderResults() {
        const container = document.getElementById('yht-results-list');
        const countElement = document.getElementById('yht-results-count');
        const resultsContainer = document.getElementById('yht-search-results');
        
        if (!container || !countElement || !resultsContainer) return;
        
        const count = this.searchResults.length;
        countElement.textContent = `${count} risultat${count !== 1 ? 'i' : 'o'} troват${count !== 1 ? 'i' : 'o'}`;
        
        if (count === 0) {
            container.innerHTML = this.getEmptyStateHTML();
        } else {
            container.innerHTML = this.searchResults.map(result => this.getResultHTML(result)).join('');
        }
        
        resultsContainer.style.display = 'block';
        
        // Scroll to results
        resultsContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    
    getResultHTML(result) {
        const starsHTML = '★'.repeat(Math.floor(result.rating)) + 
                         (result.rating % 1 >= 0.5 ? '☆' : '') + 
                         '☆'.repeat(5 - Math.ceil(result.rating));
        
        return `
            <div class="yht-result-item" data-result-id="${result.id}">
                <div class="yht-result-content">
                    <div class="yht-result-info">
                        <div class="yht-result-header">
                            <div>
                                <h3 class="yht-result-title">${result.title}</h3>
                                <p class="yht-result-subtitle">${result.subtitle}</p>
                            </div>
                            <div class="yht-result-rating">
                                <span class="stars">${starsHTML}</span>
                                <span class="rating-number">${result.rating}</span>
                                <span class="reviews-count">(${result.reviews})</span>
                            </div>
                        </div>
                        
                        <div class="yht-result-features">
                            ${result.features.map(feature => 
                                `<span class="yht-result-feature">${feature}</span>`
                            ).join('')}
                        </div>
                    </div>
                    
                    <div class="yht-result-actions">
                        <div class="yht-result-price">
                            €${result.price}<span class="yht-result-price-unit">/pax</span>
                        </div>
                        <div class="yht-result-availability">
                            <span class="availability-icon">✓</span>
                            <span>${result.availability}</span>
                        </div>
                        <button class="yht-result-btn" onclick="yhtSearch.selectResult(${result.id})">
                            Seleziona Questo Tour
                        </button>
                    </div>
                </div>
            </div>
        `;
    }
    
    getEmptyStateHTML() {
        return `
            <div class="yht-search-empty">
                <div class="yht-search-empty-icon">🔍</div>
                <h3 class="yht-search-empty-title">Nessun risultato trovato</h3>
                <p class="yht-search-empty-text">
                    Prova a modificare i filtri di ricerca o a scegliere criteri diversi.
                </p>
                <div class="yht-search-suggestions">
                    <a href="#" class="yht-search-suggestion" onclick="yhtSearch.quickFilter('destination', 'viterbo_tuscia')">Viterbo</a>
                    <a href="#" class="yht-search-suggestion" onclick="yhtSearch.quickFilter('experience', 'enogastronomica')">Enogastronomia</a>
                    <a href="#" class="yht-search-suggestion" onclick="yhtSearch.quickFilter('experience', 'romantica')">Romantico</a>
                    <a href="#" class="yht-search-suggestion" onclick="yhtSearch.clearAllFilters()">Visualizza tutto</a>
                </div>
            </div>
        `;
    }
    
    quickFilter(key, value) {
        this.currentFilters[key] = value;
        
        // Update form element
        const element = document.querySelector(`[data-filter="${key}"]`);
        if (element) {
            element.value = value;
        }
        
        this.updateFilterTags();
        this.performSearch();
        this.saveFilters();
    }
    
    selectResult(resultId) {
        const result = this.searchResults.find(r => r.id === resultId);
        if (!result) return;
        
        // Hide search results and return to trip builder
        document.getElementById('yht-search-results').style.display = 'none';
        
        // Pre-fill trip builder with selected values
        if (result.destination) {
            const destCard = document.querySelector(`[data-value="${result.destination}"]`);
            if (destCard) {
                destCard.click();
            }
        }
        
        if (result.experience) {
            const expCard = document.querySelector(`[data-value="${result.experience}"]`);
            if (expCard) {
                expCard.click();
            }
        }
        
        // Show notification
        if (window.yhtEnhancer) {
            window.yhtEnhancer.showNotification(
                `Tour "${result.title}" selezionato! Completa la configurazione.`,
                'success',
                5000
            );
        }
    }
    
    showLoading() {
        const container = document.getElementById('yht-results-list');
        if (container) {
            container.innerHTML = `
                <div class="yht-search-loading">
                    <div class="yht-search-spinner"></div>
                    <span>Ricerca in corso...</span>
                </div>
            `;
        }
        
        document.getElementById('yht-search-results').style.display = 'block';
    }
    
    hideLoading() {
        // Loading will be hidden when results are rendered
    }
    
    saveFilters() {
        try {
            localStorage.setItem('yht_search_filters', JSON.stringify(this.currentFilters));
        } catch (e) {
            console.warn('Could not save filters to localStorage');
        }
    }
    
    loadSavedFilters() {
        try {
            const saved = localStorage.getItem('yht_search_filters');
            if (saved) {
                this.currentFilters = JSON.parse(saved);
                
                // Apply saved filters to form elements
                Object.entries(this.currentFilters).forEach(([key, value]) => {
                    const element = document.querySelector(`[data-filter="${key}"]`);
                    if (element) {
                        element.value = value;
                    }
                });
                
                this.updateFilterTags();
            }
        } catch (e) {
            console.warn('Could not load saved filters');
        }
    }
}

// Initialize search when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if on trip builder page
    if (document.getElementById('yht-builder')) {
        window.yhtSearch = new YHTSearch();
    }
});

// Export for external use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = YHTSearch;
}