/**
 * YHT Client Portal JavaScript - Interactive Tour Selection System
 */

(function($) {
    'use strict';
    
    class YHTClientPortal {
        constructor() {
            this.selections = {};
            this.totalEstimate = 0;
            this.tourToken = $('#tour-token').val();
            this.tourId = $('#tour-id').val();
            this.progressStep = 1;
            
            this.init();
        }
        
        init() {
            this.bindEvents();
            this.initializeInterface();
            this.updateProgressBar();
        }
        
        bindEvents() {
            // Option selection
            $(document).on('click', '.select-option-btn', (e) => {
                e.preventDefault();
                this.selectOption(e.target);
            });
            
            // Save preferences
            $('#save-preferences').on('click', () => {
                this.savePreferences();
            });
            
            // Request booking
            $('#request-booking').on('click', () => {
                this.requestBooking();
            });
            
            // Chat functionality
            $('#open-chat').on('click', () => {
                this.openChat();
            });
            
            // Option card hover effects
            $(document).on('mouseenter', '.option-card', function() {
                $(this).addClass('pulse');
            });
            
            $(document).on('mouseleave', '.option-card', function() {
                $(this).removeClass('pulse');
            });
        }
        
        initializeInterface() {
            // Add progress indicator
            this.addProgressBar();
            
            // Animate cards on load
            $('.yht-day-card').each(function(index) {
                setTimeout(() => {
                    $(this).addClass('fade-in');
                }, index * 200);
            });
            
            // Initialize tooltips
            this.initializeTooltips();
            
            // Load any existing selections
            this.loadExistingSelections();
        }
        
        addProgressBar() {
            const progressHTML = `
                <div class="progress-indicator">
                    <div class="progress-bar" style="width: 20%"></div>
                </div>
                <p class="text-center">
                    <small>Passo ${this.progressStep} di 5: Seleziona le tue preferenze</small>
                </p>
            `;
            
            $('.yht-days-selector h3').after(progressHTML);
        }
        
        initializeTooltips() {
            // Add helpful tooltips to option cards
            $('.option-card').each(function() {
                const category = $(this).closest('.entity-category').find('h5').text();
                $(this).attr('title', `Clicca per selezionare questa opzione per ${category}`);
            });
        }
        
        selectOption(button) {
            const $button = $(button);
            const $card = $button.closest('.option-card');
            const category = $button.data('category');
            const day = $button.data('day');
            const entityId = $button.data('entity');
            
            // Remove previous selection for this category/day
            $(`.option-card[data-category="${category}"][data-day="${day}"]`).removeClass('selected');
            $card.addClass('selected');
            
            // Store selection
            if (!this.selections[day]) {
                this.selections[day] = {};
            }
            
            this.selections[day][category] = {
                entityId: entityId,
                name: $card.find('h6').text(),
                price: this.extractPrice($card.find('.option-price').text()),
                category: category
            };
            
            // Update UI
            this.updateSelectionsSummary();
            this.updateTotalEstimate();
            this.checkCompleteness();
            this.updateProgressBar();
            
            // Add success animation
            this.showSelectionFeedback($card);
        }
        
        extractPrice(priceText) {
            const match = priceText.match(/€([\d.,]+)/);
            return match ? parseFloat(match[1].replace(',', '.')) : 0;
        }
        
        showSelectionFeedback($card) {
            const $feedback = $('<div class="selection-feedback">✅ Selezionato!</div>');
            $feedback.css({
                position: 'absolute',
                top: '10px',
                right: '10px',
                background: '#28a745',
                color: 'white',
                padding: '5px 10px',
                borderRadius: '5px',
                fontSize: '12px',
                zIndex: 10
            });
            
            $card.css('position', 'relative').append($feedback);
            
            setTimeout(() => {
                $feedback.fadeOut(() => $feedback.remove());
            }, 2000);
        }
        
        updateSelectionsSummary() {
            const $list = $('#selections-list');
            $list.empty();
            
            Object.keys(this.selections).forEach(day => {
                const daySelections = this.selections[day];
                
                Object.keys(daySelections).forEach(category => {
                    const selection = daySelections[category];
                    const categoryIcons = {
                        luoghi: '📍',
                        alloggi: '🏨',
                        servizi: '🍽️'
                    };
                    
                    const $item = $(`
                        <div class="selection-item">
                            <span>
                                ${categoryIcons[category]} Giorno ${day}: ${selection.name}
                            </span>
                            <strong>€${selection.price.toFixed(2)}</strong>
                        </div>
                    `);
                    
                    $list.append($item);
                });
            });
            
            if ($list.children().length === 0) {
                $list.html('<p class="text-muted">Nessuna selezione effettuata</p>');
            }
        }
        
        updateTotalEstimate() {
            this.totalEstimate = 0;
            
            Object.keys(this.selections).forEach(day => {
                const daySelections = this.selections[day];
                Object.keys(daySelections).forEach(category => {
                    this.totalEstimate += daySelections[category].price;
                });
            });
            
            $('#total-estimate').text(this.totalEstimate.toFixed(2));
            
            // Animate total change
            $('#total-estimate').parent().addClass('pulse');
            setTimeout(() => {
                $('#total-estimate').parent().removeClass('pulse');
            }, 1000);
        }
        
        checkCompleteness() {
            const totalDays = $('.yht-day-card').length;
            const selectedDays = Object.keys(this.selections).length;
            
            let totalSelections = 0;
            Object.keys(this.selections).forEach(day => {
                totalSelections += Object.keys(this.selections[day]).length;
            });
            
            const isComplete = totalSelections >= totalDays; // At least one selection per day
            
            $('#save-preferences').prop('disabled', !isComplete);
            
            if (isComplete) {
                this.progressStep = 2;
                this.showCompletionMessage();
            }
        }
        
        showCompletionMessage() {
            if (!$('.completion-message').length) {
                const $message = $(`
                    <div class="completion-message success-message fade-in">
                        🎉 Perfetto! Hai selezionato le tue preferenze. 
                        Ora puoi salvarle e procedere con la prenotazione.
                    </div>
                `);
                
                $('.yht-portal-actions').prepend($message);
                
                // Auto-scroll to actions
                $('html, body').animate({
                    scrollTop: $('.yht-portal-actions').offset().top - 50
                }, 1000);
            }
        }
        
        updateProgressBar() {
            const progress = Math.min(100, (this.progressStep / 5) * 100);
            $('.progress-bar').animate({ width: progress + '%' }, 500);
            
            const stepTexts = [
                'Seleziona le tue preferenze',
                'Conferma le selezioni',
                'Inserisci i dettagli',
                'Revisione finale',
                'Prenotazione completata'
            ];
            
            $('.progress-indicator').next('p').find('small').text(
                `Passo ${this.progressStep} di 5: ${stepTexts[this.progressStep - 1]}`
            );
        }
        
        savePreferences() {
            this.showLoadingState('#save-preferences', 'Salvataggio...');
            
            const data = {
                tour_id: this.tourId,
                tour_token: this.tourToken,
                selections: this.selections,
                total_estimate: this.totalEstimate,
                timestamp: new Date().toISOString()
            };
            
            $.ajax({
                url: yht_portal_ajax.rest_url + 'save_client_preferences',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(data),
                headers: {
                    'X-WP-Nonce': yht_portal_ajax.nonce
                }
            })
            .done((response) => {
                if (response.ok) {
                    this.progressStep = 3;
                    this.updateProgressBar();
                    this.showSuccessMessage('✅ Preferenze salvate con successo!');
                    $('#request-booking').show().addClass('fade-in');
                    this.enableBookingFlow();
                } else {
                    this.showErrorMessage('❌ Errore nel salvataggio: ' + response.message);
                }
            })
            .fail(() => {
                this.showErrorMessage('❌ Errore di connessione. Riprova più tardi.');
            })
            .always(() => {
                this.hideLoadingState('#save-preferences', '💾 Salva le Mie Preferenze');
            });
        }
        
        enableBookingFlow() {
            // Add booking form
            const bookingFormHTML = `
                <div class="booking-form-container fade-in" style="margin-top: 30px;">
                    <h4>📝 Completa la Prenotazione</h4>
                    <form id="booking-form" class="booking-form">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Nome Completo *</label>
                                <input type="text" name="full_name" required>
                            </div>
                            <div class="form-group">
                                <label>Email *</label>
                                <input type="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label>Telefono *</label>
                                <input type="tel" name="phone" required>
                            </div>
                            <div class="form-group">
                                <label>Numero Partecipanti *</label>
                                <input type="number" name="participants" min="1" required>
                            </div>
                            <div class="form-group full-width">
                                <label>Data Preferita di Partenza</label>
                                <input type="date" name="preferred_date">
                            </div>
                            <div class="form-group full-width">
                                <label>Note Aggiuntive</label>
                                <textarea name="notes" rows="3" placeholder="Richieste speciali, allergie, preferenze..."></textarea>
                            </div>
                        </div>
                        
                        <div class="booking-summary">
                            <h5>Riepilogo Prenotazione</h5>
                            <div class="summary-details">
                                <div class="summary-item">
                                    <span>Totale Stimato:</span>
                                    <strong>€${this.totalEstimate.toFixed(2)}</strong>
                                </div>
                                <div class="summary-item">
                                    <span>Acconto Richiesto (30%):</span>
                                    <strong>€${(this.totalEstimate * 0.3).toFixed(2)}</strong>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-success">
                                🚀 Conferma Prenotazione
                            </button>
                        </div>
                    </form>
                </div>
            `;
            
            $('.yht-portal-actions').append(bookingFormHTML);
            
            // Bind form submission
            $('#booking-form').on('submit', (e) => {
                e.preventDefault();
                this.submitBooking();
            });
        }
        
        submitBooking() {
            const formData = new FormData($('#booking-form')[0]);
            const bookingData = {};
            
            for (let [key, value] of formData.entries()) {
                bookingData[key] = value;
            }
            
            bookingData.tour_id = this.tourId;
            bookingData.tour_token = this.tourToken;
            bookingData.selections = this.selections;
            bookingData.total_estimate = this.totalEstimate;
            
            this.showLoadingState('#booking-form button[type="submit"]', 'Elaborazione...');
            
            $.ajax({
                url: yht_portal_ajax.rest_url + 'submit_booking_request',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(bookingData),
                headers: {
                    'X-WP-Nonce': yht_portal_ajax.nonce
                }
            })
            .done((response) => {
                if (response.ok) {
                    this.progressStep = 5;
                    this.updateProgressBar();
                    this.showBookingSuccess(response.booking_id);
                } else {
                    this.showErrorMessage('❌ Errore nella prenotazione: ' + response.message);
                }
            })
            .fail(() => {
                this.showErrorMessage('❌ Errore di connessione. Riprova più tardi.');
            })
            .always(() => {
                this.hideLoadingState('#booking-form button[type="submit"]', '🚀 Conferma Prenotazione');
            });
        }
        
        showBookingSuccess(bookingId) {
            const successHTML = `
                <div class="booking-success fade-in">
                    <div class="success-container">
                        <h2>🎉 Prenotazione Inviata con Successo!</h2>
                        <p>Il tuo ID prenotazione è: <strong>#${bookingId}</strong></p>
                        <p>Riceverai una conferma via email entro 24 ore con tutti i dettagli del tuo tour personalizzato.</p>
                        
                        <div class="next-steps">
                            <h4>Prossimi Passi:</h4>
                            <ol>
                                <li>✅ Riceverai un'email di conferma</li>
                                <li>📞 Il nostro team ti contatterà per finalizzare i dettagli</li>
                                <li>💳 Procederai con il pagamento dell'acconto</li>
                                <li>🎯 Il tuo tour personalizzato sarà confermato!</li>
                            </ol>
                        </div>
                        
                        <div class="booking-actions">
                            <a href="/" class="btn btn-primary">🏠 Torna alla Home</a>
                            <button onclick="window.print()" class="btn btn-secondary">🖨️ Stampa Ricevuta</button>
                        </div>
                    </div>
                </div>
            `;
            
            $('.yht-portal-content').html(successHTML);
            
            // Confetti effect
            this.showConfetti();
        }
        
        showConfetti() {
            // Simple confetti effect
            for (let i = 0; i < 50; i++) {
                setTimeout(() => {
                    const confetti = $('<div>🎉</div>');
                    confetti.css({
                        position: 'fixed',
                        top: '-10px',
                        left: Math.random() * window.innerWidth + 'px',
                        fontSize: '20px',
                        zIndex: 9999,
                        pointerEvents: 'none'
                    });
                    
                    $('body').append(confetti);
                    
                    confetti.animate({
                        top: window.innerHeight + 'px',
                        left: '+=' + (Math.random() * 200 - 100) + 'px'
                    }, 3000, () => confetti.remove());
                }, i * 100);
            }
        }
        
        requestBooking() {
            this.progressStep = 3;
            this.updateProgressBar();
            
            // Scroll to booking form
            $('html, body').animate({
                scrollTop: $('.booking-form-container').offset().top - 50
            }, 1000);
        }
        
        openChat() {
            // Simple chat popup
            const chatHTML = `
                <div class="chat-popup">
                    <div class="chat-header">
                        <h4>💬 Chat Live</h4>
                        <button class="close-chat">×</button>
                    </div>
                    <div class="chat-body">
                        <div class="chat-message admin">
                            <strong>Assistente:</strong> Ciao! Come posso aiutarti con il tuo tour?
                        </div>
                        <div class="chat-input">
                            <input type="text" placeholder="Scrivi il tuo messaggio...">
                            <button>Invia</button>
                        </div>
                    </div>
                </div>
            `;
            
            if (!$('.chat-popup').length) {
                $('body').append(chatHTML);
                
                $('.close-chat').on('click', () => {
                    $('.chat-popup').remove();
                });
            }
        }
        
        loadExistingSelections() {
            // Load any previously saved selections
            const saved = localStorage.getItem('yht_selections_' + this.tourToken);
            if (saved) {
                try {
                    this.selections = JSON.parse(saved);
                    this.restoreSelections();
                } catch (e) {
                    console.warn('Could not restore selections:', e);
                }
            }
        }
        
        restoreSelections() {
            Object.keys(this.selections).forEach(day => {
                const daySelections = this.selections[day];
                Object.keys(daySelections).forEach(category => {
                    const selection = daySelections[category];
                    const $card = $(`.option-card[data-entity-id="${selection.entityId}"]`);
                    $card.addClass('selected');
                });
            });
            
            this.updateSelectionsSummary();
            this.updateTotalEstimate();
            this.checkCompleteness();
        }
        
        showLoadingState(selector, text) {
            $(selector).prop('disabled', true).html(`
                <span class="spinner">🔄</span> ${text}
            `);
        }
        
        hideLoadingState(selector, originalText) {
            $(selector).prop('disabled', false).html(originalText);
        }
        
        showSuccessMessage(message) {
            this.showMessage(message, 'success');
        }
        
        showErrorMessage(message) {
            this.showMessage(message, 'error');
        }
        
        showMessage(message, type) {
            const $message = $(`<div class="${type}-message fade-in">${message}</div>`);
            $('.yht-portal-actions').prepend($message);
            
            setTimeout(() => {
                $message.fadeOut(() => $message.remove());
            }, 5000);
        }
    }
    
    // Initialize when document is ready
    $(document).ready(function() {
        if ($('#yht-client-portal').length) {
            new YHTClientPortal();
        }
    });
    
})(jQuery);