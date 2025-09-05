/**
 * Real-time Collaboration JavaScript
 * 
 * @package YourHiddenTrip
 * @version 6.3.0
 */

(function($) {
    'use strict';

    class YHTCollaboration {
        constructor() {
            this.sessionId = null;
            this.userId = yhtCollaboration.user_id || 'anonymous_' + this.generateUUID();
            this.userName = yhtCollaboration.user_name;
            this.userAvatar = yhtCollaboration.user_avatar;
            this.participants = {};
            this.eventSource = null;
            this.isConnected = false;
            this.reconnectAttempts = 0;
            this.maxReconnectAttempts = 5;
            this.cursors = {};
            this.lastUpdate = 0;
            
            this.init();
        }

        init() {
            this.bindEvents();
            this.initUI();
            this.checkForExistingSession();
        }

        bindEvents() {
            // Collaboration controls
            $(document).on('click', '.yht-start-collaboration', this.startCollaboration.bind(this));
            $(document).on('click', '.yht-join-collaboration', this.joinCollaboration.bind(this));
            $(document).on('click', '.yht-leave-collaboration', this.leaveCollaboration.bind(this));
            $(document).on('click', '.yht-share-session', this.shareSession.bind(this));
            
            // Trip editing events
            $(document).on('click', '.yht-add-stop', this.addStop.bind(this));
            $(document).on('click', '.yht-remove-stop', this.removeStop.bind(this));
            $(document).on('input', '.yht-stop-input', this.updateStop.bind(this));
            $(document).on('submit', '.yht-comment-form', this.addComment.bind(this));
            
            // Cursor tracking
            $(document).on('mousemove', '.yht-collaboration-area', this.trackCursor.bind(this));
            
            // Auto-save
            $(document).on('input change', '.yht-trip-field', this.scheduleAutoSave.bind(this));
            
            // Presence indicators
            $(document).on('focus', '.yht-trip-field', this.showPresence.bind(this));
            $(document).on('blur', '.yht-trip-field', this.hidePresence.bind(this));
            
            // Window events
            $(window).on('beforeunload', this.cleanup.bind(this));
            $(window).on('focus', this.handleWindowFocus.bind(this));
            $(window).on('blur', this.handleWindowBlur.bind(this));
        }

        initUI() {
            this.createCollaborationBar();
            this.createParticipantsList();
            this.createChatPanel();
        }

        createCollaborationBar() {
            const $collaborationBar = $(`
                <div id="yht-collaboration-bar" class="yht-collaboration-bar" style="display: none;">
                    <div class="collaboration-status">
                        <span class="status-indicator"></span>
                        <span class="status-text">Not connected</span>
                    </div>
                    <div class="collaboration-participants">
                        <div class="participants-list"></div>
                        <button class="btn-invite" title="Invite others">
                            <span class="icon">👥</span>
                            Invite
                        </button>
                    </div>
                    <div class="collaboration-controls">
                        <button class="btn-chat" title="Open chat">
                            <span class="icon">💬</span>
                            <span class="chat-badge" style="display: none;">0</span>
                        </button>
                        <button class="btn-save" title="Save changes">
                            <span class="icon">💾</span>
                            Save
                        </button>
                        <button class="btn-leave" title="Leave session">
                            <span class="icon">🚪</span>
                            Leave
                        </button>
                    </div>
                </div>
            `);

            $('body').append($collaborationBar);
            
            // Bind collaboration bar events
            $collaborationBar.on('click', '.btn-invite', this.shareSession.bind(this));
            $collaborationBar.on('click', '.btn-chat', this.toggleChat.bind(this));
            $collaborationBar.on('click', '.btn-save', this.saveTrip.bind(this));
            $collaborationBar.on('click', '.btn-leave', this.leaveCollaboration.bind(this));
        }

        createParticipantsList() {
            // Participants list is already created in the collaboration bar
        }

        createChatPanel() {
            const $chatPanel = $(`
                <div id="yht-chat-panel" class="yht-chat-panel" style="display: none;">
                    <div class="chat-header">
                        <h3>Trip Chat</h3>
                        <button class="chat-close">&times;</button>
                    </div>
                    <div class="chat-messages"></div>
                    <form class="chat-form">
                        <input type="text" class="chat-input" placeholder="Type a message..." maxlength="500">
                        <button type="submit" class="chat-send">Send</button>
                    </form>
                </div>
            `);

            $('body').append($chatPanel);
            
            // Bind chat events
            $chatPanel.on('click', '.chat-close', this.toggleChat.bind(this));
            $chatPanel.on('submit', '.chat-form', this.sendChatMessage.bind(this));
        }

        checkForExistingSession() {
            const urlParams = new URLSearchParams(window.location.search);
            const sessionId = urlParams.get('session');
            
            if (sessionId && urlParams.get('collaborate') === '1') {
                this.joinExistingSession(sessionId);
            } else {
                this.showCollaborationPrompt();
            }
        }

        showCollaborationPrompt() {
            if (!$('.yht-collaboration-prompt').length) {
                const $prompt = $(`
                    <div class="yht-collaboration-prompt">
                        <div class="prompt-content">
                            <h3>🤝 Collaborate on this trip</h3>
                            <p>Plan this trip together with friends or fellow travelers!</p>
                            <div class="prompt-actions">
                                <button class="btn-primary yht-start-collaboration">
                                    Start Collaboration
                                </button>
                                <button class="btn-secondary prompt-dismiss">
                                    Plan Alone
                                </button>
                            </div>
                        </div>
                    </div>
                `);

                $('.yht-trip-planner, .single-trip').prepend($prompt);
                
                $prompt.on('click', '.prompt-dismiss', function() {
                    $prompt.fadeOut(() => $prompt.remove());
                });
            }
        }

        startCollaboration() {
            const tripId = this.getTripId();
            
            if (!tripId) {
                this.showError('Trip ID not found');
                return;
            }

            this.showLoading('Starting collaboration session...');

            $.ajax({
                url: yhtCollaboration.ajaxurl,
                type: 'POST',
                data: {
                    action: 'yht_join_collaboration',
                    nonce: yhtCollaboration.nonce,
                    session_id: this.generateSessionId(),
                    trip_id: tripId,
                    user_name: this.userName,
                    user_avatar: this.userAvatar
                },
                success: (response) => {
                    if (response.success) {
                        this.sessionId = response.data.session.id;
                        this.userId = response.data.user_id;
                        this.connectToSession();
                        this.updateURL();
                        $('.yht-collaboration-prompt').fadeOut();
                        this.showSuccess('Collaboration session started!');
                    } else {
                        this.showError(response.data.message);
                    }
                },
                error: () => {
                    this.showError('Failed to start collaboration session');
                },
                complete: () => {
                    this.hideLoading();
                }
            });
        }

        joinExistingSession(sessionId) {
            this.sessionId = sessionId;
            this.joinCollaboration();
        }

        joinCollaboration() {
            if (!this.sessionId) {
                this.showError('No session ID provided');
                return;
            }

            const tripId = this.getTripId();
            this.showLoading('Joining collaboration session...');

            $.ajax({
                url: yhtCollaboration.ajaxurl,
                type: 'POST',
                data: {
                    action: 'yht_join_collaboration',
                    nonce: yhtCollaboration.nonce,
                    session_id: this.sessionId,
                    trip_id: tripId,
                    user_name: this.userName,
                    user_avatar: this.userAvatar
                },
                success: (response) => {
                    if (response.success) {
                        this.userId = response.data.user_id;
                        this.connectToSession();
                        this.showSuccess('Joined collaboration session!');
                    } else {
                        this.showError(response.data.message);
                    }
                },
                error: () => {
                    this.showError('Failed to join collaboration session');
                },
                complete: () => {
                    this.hideLoading();
                }
            });
        }

        connectToSession() {
            this.showCollaborationBar();
            this.updateConnectionStatus('connecting');
            
            // Start Server-Sent Events connection
            this.startSSEConnection();
            
            // Start ping interval to keep session alive
            this.startPingInterval();
            
            this.isConnected = true;
            this.updateConnectionStatus('connected');
        }

        startSSEConnection() {
            if (this.eventSource) {
                this.eventSource.close();
            }

            const sseUrl = yhtCollaboration.sse_endpoint + this.sessionId + '?last_update=' + this.lastUpdate;
            this.eventSource = new EventSource(sseUrl);

            this.eventSource.onopen = () => {
                console.log('SSE connection opened');
                this.reconnectAttempts = 0;
                this.updateConnectionStatus('connected');
            };

            this.eventSource.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    this.handleCollaborationUpdate(data);
                } catch (e) {
                    console.error('Failed to parse SSE data:', e);
                }
            };

            this.eventSource.addEventListener('ping', (event) => {
                // Handle ping events
                console.log('Received ping');
            });

            this.eventSource.onerror = (error) => {
                console.error('SSE error:', error);
                this.updateConnectionStatus('error');
                this.handleSSEError();
            };
        }

        handleSSEError() {
            if (this.reconnectAttempts < this.maxReconnectAttempts) {
                this.reconnectAttempts++;
                const delay = Math.pow(2, this.reconnectAttempts) * 1000; // Exponential backoff
                
                console.log(`Reconnecting in ${delay}ms (attempt ${this.reconnectAttempts})`);
                this.updateConnectionStatus('reconnecting');
                
                setTimeout(() => {
                    this.startSSEConnection();
                }, delay);
            } else {
                this.updateConnectionStatus('disconnected');
                this.showError('Connection lost. Please refresh the page.');
            }
        }

        startPingInterval() {
            this.pingInterval = setInterval(() => {
                this.sendPing();
            }, yhtCollaboration.ping_interval);
        }

        sendPing() {
            if (!this.sessionId || !this.isConnected) return;

            $.ajax({
                url: yhtCollaboration.ajaxurl,
                type: 'POST',
                data: {
                    action: 'yht_get_collaboration_updates',
                    nonce: yhtCollaboration.nonce,
                    session_id: this.sessionId,
                    user_id: this.userId,
                    last_update: this.lastUpdate
                },
                success: (response) => {
                    if (response.success && response.data.updates) {
                        response.data.updates.forEach(update => {
                            this.handleCollaborationUpdate(update);
                        });
                    }
                }
            });
        }

        handleCollaborationUpdate(update) {
            this.lastUpdate = Math.max(this.lastUpdate, update.timestamp);

            switch (update.type) {
                case 'user_joined':
                    this.handleUserJoined(update);
                    break;
                case 'user_left':
                    this.handleUserLeft(update);
                    break;
                case 'add_stop':
                    this.handleStopAdded(update);
                    break;
                case 'remove_stop':
                    this.handleStopRemoved(update);
                    break;
                case 'update_stop':
                    this.handleStopUpdated(update);
                    break;
                case 'add_comment':
                    this.handleCommentAdded(update);
                    break;
                case 'cursor_move':
                    this.handleCursorMove(update);
                    break;
                default:
                    console.log('Unknown update type:', update.type);
            }
        }

        handleUserJoined(update) {
            if (update.participants) {
                this.participants = {};
                update.participants.forEach(participant => {
                    this.participants[participant.id] = participant;
                });
                this.updateParticipantsList();
            }
            
            if (update.user && update.user.id !== this.userId) {
                this.showNotification(`${update.user.name} joined the session`, 'info');
            }
        }

        handleUserLeft(update) {
            if (update.user_id && this.participants[update.user_id]) {
                const userName = this.participants[update.user_id].name;
                delete this.participants[update.user_id];
                this.updateParticipantsList();
                this.showNotification(`${userName} left the session`, 'info');
            }
        }

        handleStopAdded(update) {
            if (update.user_id !== this.userId) {
                this.addStopToUI(update.data);
                this.showNotification('New stop added by ' + this.getParticipantName(update.user_id), 'success');
            }
        }

        handleStopRemoved(update) {
            if (update.user_id !== this.userId) {
                this.removeStopFromUI(update.data.stop_id);
                this.showNotification('Stop removed by ' + this.getParticipantName(update.user_id), 'info');
            }
        }

        handleStopUpdated(update) {
            if (update.user_id !== this.userId) {
                this.updateStopInUI(update.data);
            }
        }

        handleCommentAdded(update) {
            this.addCommentToChat(update.data);
            if (update.user_id !== this.userId) {
                this.showChatBadge();
            }
        }

        handleCursorMove(update) {
            if (update.user_id !== this.userId) {
                this.updateCursor(update.user_id, update.data);
            }
        }

        // Trip editing methods
        addStop(e) {
            e.preventDefault();
            
            const stopData = this.getStopDataFromForm(e.target);
            
            if (!stopData.name) {
                this.showError('Stop name is required');
                return;
            }

            this.sendCollaborationAction('add_stop', stopData);
            this.addStopToUI(stopData);
        }

        removeStop(e) {
            e.preventDefault();
            
            const stopId = $(e.target).closest('.trip-stop').data('stop-id');
            
            if (!stopId) return;

            this.sendCollaborationAction('remove_stop', { stop_id: stopId });
            this.removeStopFromUI(stopId);
        }

        updateStop(e) {
            const $input = $(e.target);
            const $stop = $input.closest('.trip-stop');
            const stopId = $stop.data('stop-id');
            const field = $input.data('field');
            const value = $input.val();

            if (!stopId || !field) return;

            const updateData = {
                stop_id: stopId,
                updates: {}
            };
            updateData.updates[field] = value;

            this.sendCollaborationAction('update_stop', updateData);
        }

        addComment(e) {
            e.preventDefault();
            
            const $form = $(e.target);
            const text = $form.find('.comment-input').val().trim();
            
            if (!text) return;

            const commentData = {
                text: text,
                timestamp: Date.now()
            };

            this.sendCollaborationAction('add_comment', commentData);
            $form.find('.comment-input').val('');
        }

        sendChatMessage(e) {
            e.preventDefault();
            
            const $form = $(e.target);
            const text = $form.find('.chat-input').val().trim();
            
            if (!text) return;

            const messageData = {
                text: text,
                timestamp: Date.now()
            };

            this.sendCollaborationAction('add_comment', messageData);
            $form.find('.chat-input').val('');
        }

        sendCollaborationAction(actionType, actionData) {
            if (!this.sessionId || !this.isConnected) return;

            $.ajax({
                url: yhtCollaboration.ajaxurl,
                type: 'POST',
                data: {
                    action: 'yht_collaboration_action',
                    nonce: yhtCollaboration.nonce,
                    session_id: this.sessionId,
                    user_id: this.userId,
                    action_type: actionType,
                    action_data: JSON.stringify(actionData)
                },
                error: () => {
                    this.showError('Failed to sync changes');
                }
            });
        }

        // UI update methods
        updateParticipantsList() {
            const $list = $('.participants-list');
            $list.empty();

            Object.values(this.participants).forEach(participant => {
                const $participant = $(`
                    <div class="participant" data-user-id="${participant.id}">
                        <img src="${participant.avatar}" alt="${participant.name}" class="participant-avatar">
                        <span class="participant-name">${participant.name}</span>
                        <span class="participant-status ${this.isParticipantActive(participant) ? 'active' : 'away'}"></span>
                    </div>
                `);
                $list.append($participant);
            });

            // Update participant count
            $('.collaboration-status .status-text').text(
                `${Object.keys(this.participants).length} participant${Object.keys(this.participants).length !== 1 ? 's' : ''}`
            );
        }

        addStopToUI(stopData) {
            const $stopsList = $('.yht-trip-stops');
            const $newStop = this.createStopElement(stopData);
            $stopsList.append($newStop);
            $newStop.addClass('highlight-new');
            setTimeout(() => $newStop.removeClass('highlight-new'), 2000);
        }

        removeStopFromUI(stopId) {
            $(`.trip-stop[data-stop-id="${stopId}"]`).addClass('removing').fadeOut(() => {
                $(this).remove();
            });
        }

        updateStopInUI(updateData) {
            const $stop = $(`.trip-stop[data-stop-id="${updateData.stop_id}"]`);
            Object.keys(updateData.updates).forEach(field => {
                const $field = $stop.find(`[data-field="${field}"]`);
                if ($field.length && $field.val() !== updateData.updates[field]) {
                    $field.val(updateData.updates[field]);
                    $field.addClass('highlight-updated');
                    setTimeout(() => $field.removeClass('highlight-updated'), 1000);
                }
            });
        }

        addCommentToChat(commentData) {
            const $chatMessages = $('.chat-messages');
            const participant = this.participants[commentData.user_id] || { name: 'Unknown', avatar: '' };
            
            const $message = $(`
                <div class="chat-message" data-user-id="${commentData.user_id}">
                    <img src="${participant.avatar}" alt="${participant.name}" class="message-avatar">
                    <div class="message-content">
                        <div class="message-header">
                            <span class="message-author">${participant.name}</span>
                            <span class="message-time">${this.formatTime(commentData.timestamp)}</span>
                        </div>
                        <div class="message-text">${this.escapeHtml(commentData.text)}</div>
                    </div>
                </div>
            `);

            $chatMessages.append($message);
            $chatMessages.scrollTop($chatMessages[0].scrollHeight);
        }

        // Cursor tracking
        trackCursor(e) {
            if (!this.isConnected) return;

            // Throttle cursor updates
            if (this.cursorThrottle) return;
            this.cursorThrottle = true;
            setTimeout(() => this.cursorThrottle = false, 100);

            const rect = e.currentTarget.getBoundingClientRect();
            const x = ((e.clientX - rect.left) / rect.width) * 100;
            const y = ((e.clientY - rect.top) / rect.height) * 100;

            this.sendCollaborationAction('cursor_move', { x, y });
        }

        updateCursor(userId, position) {
            const participant = this.participants[userId];
            if (!participant) return;

            let $cursor = $(`.collaboration-cursor[data-user-id="${userId}"]`);
            
            if (!$cursor.length) {
                $cursor = $(`
                    <div class="collaboration-cursor" data-user-id="${userId}">
                        <div class="cursor-pointer"></div>
                        <div class="cursor-label">${participant.name}</div>
                    </div>
                `);
                $('.yht-collaboration-area').append($cursor);
            }

            $cursor.css({
                left: position.x + '%',
                top: position.y + '%'
            });

            // Remove cursor after inactivity
            clearTimeout(this.cursors[userId]);
            this.cursors[userId] = setTimeout(() => {
                $cursor.fadeOut(() => $cursor.remove());
                delete this.cursors[userId];
            }, 5000);
        }

        // Utility methods
        getTripId() {
            return $('body').data('trip-id') || $('.yht-trip-planner').data('trip-id') || 
                   $('input[name="trip_id"]').val() || window.yhtTripId;
        }

        generateSessionId() {
            return 'session_' + this.generateUUID();
        }

        generateUUID() {
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                const r = Math.random() * 16 | 0;
                const v = c == 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
        }

        getParticipantName(userId) {
            return this.participants[userId] ? this.participants[userId].name : 'Unknown';
        }

        isParticipantActive(participant) {
            const timeDiff = Date.now() - (participant.last_seen * 1000);
            return timeDiff < 60000; // Active if seen within last minute
        }

        updateConnectionStatus(status) {
            const $indicator = $('.status-indicator');
            const $text = $('.status-text');
            
            $indicator.removeClass('connected connecting error reconnecting disconnected').addClass(status);
            
            switch (status) {
                case 'connected':
                    $text.text(`${Object.keys(this.participants).length} participant${Object.keys(this.participants).length !== 1 ? 's' : ''}`);
                    break;
                case 'connecting':
                    $text.text('Connecting...');
                    break;
                case 'reconnecting':
                    $text.text('Reconnecting...');
                    break;
                case 'error':
                case 'disconnected':
                    $text.text('Disconnected');
                    break;
            }
        }

        showCollaborationBar() {
            $('#yht-collaboration-bar').slideDown();
        }

        hideCollaborationBar() {
            $('#yht-collaboration-bar').slideUp();
        }

        toggleChat() {
            const $chatPanel = $('#yht-chat-panel');
            if ($chatPanel.is(':visible')) {
                $chatPanel.slideUp();
                this.hideChatBadge();
            } else {
                $chatPanel.slideDown();
                this.hideChatBadge();
            }
        }

        showChatBadge() {
            const $badge = $('.chat-badge');
            const count = parseInt($badge.text()) + 1;
            $badge.text(count).show();
        }

        hideChatBadge() {
            $('.chat-badge').hide().text('0');
        }

        updateURL() {
            if (this.sessionId) {
                const url = new URL(window.location);
                url.searchParams.set('collaborate', '1');
                url.searchParams.set('session', this.sessionId);
                window.history.replaceState({}, '', url);
            }
        }

        shareSession() {
            if (!this.sessionId) return;

            const shareUrl = window.location.href;
            
            if (navigator.share) {
                navigator.share({
                    title: 'Join my trip planning session',
                    text: 'Let\'s plan this trip together!',
                    url: shareUrl
                });
            } else {
                this.copyToClipboard(shareUrl);
                this.showSuccess('Share link copied to clipboard!');
            }
        }

        saveTrip() {
            // Implementation would depend on your trip saving logic
            this.showSuccess('Trip saved!');
        }

        leaveCollaboration() {
            if (confirm('Are you sure you want to leave this collaboration session?')) {
                this.cleanup();
                this.hideCollaborationBar();
                
                // Remove URL parameters
                const url = new URL(window.location);
                url.searchParams.delete('collaborate');
                url.searchParams.delete('session');
                window.history.replaceState({}, '', url);
                
                this.showNotification('Left collaboration session', 'info');
            }
        }

        cleanup() {
            if (this.eventSource) {
                this.eventSource.close();
                this.eventSource = null;
            }
            
            if (this.pingInterval) {
                clearInterval(this.pingInterval);
                this.pingInterval = null;
            }
            
            this.isConnected = false;
            this.sessionId = null;
        }

        // Utility methods
        copyToClipboard(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text);
            } else {
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
            }
        }

        formatTime(timestamp) {
            return new Date(timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }

        escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        showError(message) {
            this.showNotification(message, 'error');
        }

        showSuccess(message) {
            this.showNotification(message, 'success');
        }

        showNotification(message, type = 'info') {
            const $notification = $(`
                <div class="yht-collaboration-notification ${type}">
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

        showLoading(message) {
            if (!$('.yht-loading-overlay').length) {
                const $overlay = $(`
                    <div class="yht-loading-overlay">
                        <div class="loading-content">
                            <div class="loading-spinner"></div>
                            <div class="loading-message">${message}</div>
                        </div>
                    </div>
                `);
                $('body').append($overlay);
            } else {
                $('.loading-message').text(message);
            }
        }

        hideLoading() {
            $('.yht-loading-overlay').fadeOut(() => {
                $('.yht-loading-overlay').remove();
            });
        }
    }

    // Initialize collaboration when DOM is ready
    $(document).ready(function() {
        window.yhtCollaboration = new YHTCollaboration();
    });

})(jQuery);