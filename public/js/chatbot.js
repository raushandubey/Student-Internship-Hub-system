/**
 * ShreeRam AI Guide Chatbot
 * 
 * A lightweight, rule-based chatbot assistant for the Student Internship Hub platform.
 * Provides contextual guidance, answers FAQs, and offers navigation assistance.
 * 
 * @version 1.0.0
 * @author Development Team
 */

(function() {
    'use strict';

    /**
     * Main Chatbot Module
     */
    const ShreeRamChatbot = {
        // Configuration
        config: {
            maxMessages: 50,
            typingDelay: 800,
            responseDelay: 200,
            confidenceThreshold: 70,
            animationDuration: 300,
            maxCharacters: 500
        },

        // State management
        state: {
            isOpen: false,
            messages: [],
            isTyping: false,
            sessionStarted: false
        },

        // DOM elements (cached for performance)
        elements: {},

        /**
         * Initialize the chatbot
         */
        init() {
            const initStart = performance.now();
            
            console.log('[DEBUG] ========================================');
            console.log('[DEBUG] ShreeRam Chatbot Initialization START');
            console.log('[DEBUG] ========================================');
            
            try {
                // Cache DOM elements
                this.cacheElements();
                console.log('[DEBUG] DOM elements cached');
                
                // Set up event listeners
                this.setupEventListeners();
                console.log('[DEBUG] Event listeners set up');
                
                // Log initialization time
                const initTime = performance.now() - initStart;
                console.log(`[ShreeRam Chatbot] Initialized in ${initTime.toFixed(2)}ms`);
                console.log('[DEBUG] Initial sessionStarted value:', this.state.sessionStarted);
                console.log('[DEBUG] ========================================');
            } catch (error) {
                console.error('[ShreeRam Chatbot] Initialization failed:', error);
                // Hide chatbot if initialization fails
                if (this.elements.container) {
                    this.elements.container.style.display = 'none';
                }
            }
        },

        /**
         * Cache DOM elements for performance
         */
        cacheElements() {
            this.elements = {
                container: document.getElementById('shreeram-chatbot'),
                toggleBtn: document.getElementById('chatbot-toggle-btn'),
                closeBtn: document.getElementById('chatbot-close-btn'),
                window: document.getElementById('chatbot-window'),
                messages: document.getElementById('chatbot-messages'),
                typing: document.getElementById('chatbot-typing'),
                input: document.getElementById('chatbot-input'),
                sendBtn: document.getElementById('chatbot-send-btn'),
                icon: document.getElementById('chatbot-icon'),
                charCount: document.getElementById('chatbot-char-count'),
                charCurrent: document.getElementById('chatbot-char-current')
            };
            
            // Verify critical elements exist
            console.log('[DEBUG] Elements cached:', {
                messages: !!this.elements.messages,
                window: !!this.elements.window,
                toggleBtn: !!this.elements.toggleBtn
            });
            
            if (!this.elements.messages) {
                console.error('[ERROR] chatbot-messages element not found!');
            }
        },

        /**
         * Set up event listeners
         */
        setupEventListeners() {
            // Toggle button click
            this.elements.toggleBtn.addEventListener('click', () => this.toggle());
            
            // Close button click
            this.elements.closeBtn.addEventListener('click', () => this.toggle());
            
            // Keyboard navigation for toggle button
            this.elements.toggleBtn.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.toggle();
                }
            });
            
            // Send button click
            this.elements.sendBtn.addEventListener('click', () => this.sendMessage());
            
            // Input field enter key
            this.elements.input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.sendMessage();
                }
            });
            
            // Input field validation
            this.elements.input.addEventListener('input', () => this.validateInput());
            
            // Escape key to close
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.state.isOpen) {
                    this.toggle();
                }
            });
            
            // Clear history on page navigation
            window.addEventListener('beforeunload', () => this.clearHistory());
        },

        /**
         * Toggle chat window open/close
         */
        toggle() {
            console.log('[DEBUG] toggle() called, current isOpen:', this.state.isOpen);
            this.state.isOpen = !this.state.isOpen;
            console.log('[DEBUG] toggle() new isOpen:', this.state.isOpen);
            
            if (this.state.isOpen) {
                this.open();
            } else {
                this.close();
            }
        },

        /**
         * Open chat window
         */
        open() {
            console.log('[Chat opened]');
            
            this.elements.window.classList.remove('hidden');
            this.elements.toggleBtn.setAttribute('aria-expanded', 'true');
            this.elements.toggleBtn.setAttribute('aria-label', 'Close chat');
            
            // Update icon safely - icon is the Om symbol span
            if (this.elements.icon) {
                this.elements.icon.textContent = '✕';
                this.elements.icon.style.fontSize = '1.5rem';
            }
            
            // Focus input field
            setTimeout(() => {
                if (this.elements.input) this.elements.input.focus();
            }, this.config.animationDuration);
            
            // Show welcome message on first open only
            if (!this.state.sessionStarted) {
                console.log('[Greeting triggered]');
                this.state.sessionStarted = true;
                setTimeout(() => {
                    this.showWelcomeMessage();
                }, 100);
                this.logAnalytics('chatbot_opened', {});
            }
        },

        /**
         * Close chat window
         */
        close() {
            this.elements.window.classList.add('hidden');
            this.elements.toggleBtn.setAttribute('aria-expanded', 'false');
            this.elements.toggleBtn.setAttribute('aria-label', 'Open chat');
            
            // Restore icon safely
            if (this.elements.icon) {
                this.elements.icon.textContent = '🕉️';
                this.elements.icon.style.fontSize = '2.25rem';
            }
        },

        /**
         * Validate input field
         */
        validateInput() {
            const value = this.elements.input.value.trim();
            const length = value.length;
            
            // Enable/disable send button
            this.elements.sendBtn.disabled = length === 0;
            
            // Show character count if approaching limit
            if (length > 400) {
                this.elements.charCount.classList.remove('hidden');
                this.elements.charCurrent.textContent = length;
                
                if (length >= this.config.maxCharacters) {
                    this.elements.charCount.classList.add('text-red-500');
                } else {
                    this.elements.charCount.classList.remove('text-red-500');
                }
            } else {
                this.elements.charCount.classList.add('hidden');
            }
        },

        /**
         * Send user message
         */
        async sendMessage() {
            const text = this.elements.input.value.trim();
            
            // Validate input
            if (!text || text.length === 0) {
                return;
            }
            
            if (text.length > this.config.maxCharacters) {
                this.displayMessage({
                    type: 'bot',
                    text: `Your message is too long. Please keep it under ${this.config.maxCharacters} characters.`,
                    timestamp: new Date()
                });
                return;
            }
            
            // Display user message
            this.displayMessage({
                type: 'user',
                text: text,
                timestamp: new Date()
            });
            
            // Clear input
            this.elements.input.value = '';
            this.validateInput();
            
            // PRODUCTION FIX: Add comprehensive error handling
            try {
                await this.MessageHandler.process(text);
            } catch (error) {
                console.error('[ShreeRam Chatbot] Message processing error:', error);
                
                // Hide typing indicator if shown
                this.hideTyping();
                
                // Show user-friendly error message
                this.displayMessage({
                    type: 'bot',
                    text: "I apologize, but I'm having trouble processing your message right now. Please try:\n\n• Refreshing the page\n• Asking a simpler question\n• Using the quick reply buttons below",
                    timestamp: new Date(),
                    quickReplies: ['How to Apply', 'Resume Tips', 'Track Applications', 'Profile Help']
                });
                
                // Log error for debugging
                this.logAnalytics('chatbot_error', {
                    error: error.message,
                    stack: error.stack,
                    userMessage: text
                });
            }
        },

        /**
         * Display a message in the chat window
         */
        displayMessage(message) {
            console.log('[DEBUG] displayMessage() called with:', { type: message.type, isWelcome: message.isWelcome, text: message.text.substring(0, 50) + '...' });
            
            // Add to message history
            this.SessionManager.addMessage(message);
            
            // Create message container
            const messageEl = document.createElement('div');
            messageEl.className = `flex ${message.type === 'user' ? 'justify-end' : 'justify-start'} items-end space-x-2`;
            
            // Add bot avatar for bot messages
            if (message.type === 'bot') {
                const avatarEl = document.createElement('div');
                avatarEl.className = 'shreeram-msg-avatar';
                avatarEl.innerHTML = '<span class="text-sm">🕉️</span>';
                messageEl.appendChild(avatarEl);
            }
            
            // Create message content wrapper
            const contentWrapper = document.createElement('div');
            contentWrapper.className = 'flex flex-col max-w-[75%]';
            
            // Create message bubble
            const bubbleEl = document.createElement('div');
            
            // Check if this is a welcome message
            if (message.isWelcome) {
                console.log('[DEBUG] Rendering WELCOME message with special styling');
                bubbleEl.className = 'shreeram-welcome-message';
                bubbleEl.innerHTML = `
                    <div class="shreeram-welcome-title">
                        <i class="fas fa-hand-sparkles"></i>
                        <span>Welcome!</span>
                    </div>
                    <div class="shreeram-welcome-text">${message.text}</div>
                `;
            }
            // Check if message should use card layout
            else if (message.useCard && message.cardTitle) {
                bubbleEl.className = `px-4 py-3 ${
                    message.type === 'user' 
                        ? 'shreeram-user-bubble text-white' 
                        : 'shreeram-bot-bubble'
                }`;
                
                const cardEl = document.createElement('div');
                cardEl.className = 'shreeram-card';
                
                // Card header
                const headerEl = document.createElement('div');
                headerEl.className = 'shreeram-card-header';
                headerEl.innerHTML = `
                    <div class="shreeram-card-title">
                        <i class="fas ${message.cardIcon || 'fa-info-circle'}"></i>
                        <span>${message.cardTitle}</span>
                    </div>
                `;
                cardEl.appendChild(headerEl);
                
                // Card body
                const bodyEl = document.createElement('div');
                bodyEl.className = 'shreeram-card-body';
                bodyEl.textContent = message.text;
                cardEl.appendChild(bodyEl);
                
                bubbleEl.appendChild(cardEl);
            }
            // Check if message should use info box
            else if (message.useInfoBox) {
                bubbleEl.className = `px-4 py-3 ${
                    message.type === 'user' 
                        ? 'shreeram-user-bubble text-white' 
                        : 'shreeram-bot-bubble'
                }`;
                
                const infoBoxClass = message.infoType === 'success' ? 'shreeram-success-box' : 
                                    message.infoType === 'warning' ? 'shreeram-warning-box' : 
                                    'shreeram-info-box';
                const iconClass = message.infoType === 'success' ? 'fa-check-circle' : 
                                 message.infoType === 'warning' ? 'fa-exclamation-triangle' : 
                                 'fa-info-circle';
                
                const infoBoxEl = document.createElement('div');
                infoBoxEl.className = infoBoxClass;
                infoBoxEl.innerHTML = `<i class="fas ${iconClass}"></i>${message.text}`;
                bubbleEl.appendChild(infoBoxEl);
            }
            // Check if message should use list layout
            else if (message.useList && message.listItems) {
                bubbleEl.className = `px-4 py-3 ${
                    message.type === 'user' 
                        ? 'shreeram-user-bubble text-white' 
                        : 'shreeram-bot-bubble'
                }`;
                
                const textEl = document.createElement('p');
                textEl.className = 'text-sm leading-relaxed mb-3';
                textEl.textContent = 'Resume Tips:';
                bubbleEl.appendChild(textEl);
                
                const listEl = document.createElement('ul');
                listEl.className = 'shreeram-list';
                
                message.listItems.forEach(item => {
                    const listItemEl = document.createElement('li');
                    listItemEl.className = 'shreeram-list-item';
                    listItemEl.innerHTML = `
                        <i class="fas ${item.icon}"></i>
                        <span class="shreeram-list-item-content">${item.text}</span>
                    `;
                    listEl.appendChild(listItemEl);
                });
                
                bubbleEl.appendChild(listEl);
            }
            // Check if message should use grid layout
            else if (message.useGrid && message.gridItems) {
                bubbleEl.className = `px-4 py-3 ${
                    message.type === 'user' 
                        ? 'shreeram-user-bubble text-white' 
                        : 'shreeram-bot-bubble'
                }`;
                
                const textEl = document.createElement('p');
                textEl.className = 'text-sm leading-relaxed mb-3';
                textEl.textContent = message.text;
                bubbleEl.appendChild(textEl);
                
                const gridEl = document.createElement('div');
                gridEl.className = 'shreeram-grid';
                
                message.gridItems.forEach(item => {
                    const gridItemEl = document.createElement('div');
                    gridItemEl.className = 'shreeram-grid-item';
                    gridItemEl.innerHTML = `
                        <i class="fas ${item.icon}"></i>
                        <div class="shreeram-grid-item-label">${item.label}</div>
                    `;
                    gridEl.appendChild(gridItemEl);
                });
                
                bubbleEl.appendChild(gridEl);
            }
            // Default text message
            else {
                bubbleEl.className = `px-4 py-3 ${
                    message.type === 'user' 
                        ? 'shreeram-user-bubble text-white' 
                        : 'shreeram-bot-bubble'
                }`;
                
                const textEl = document.createElement('p');
                textEl.className = 'text-sm leading-relaxed whitespace-pre-wrap';
                textEl.textContent = message.text;
                bubbleEl.appendChild(textEl);
            }
            
            contentWrapper.appendChild(bubbleEl);
            
            // Add timestamp
            const timestampEl = document.createElement('div');
            timestampEl.className = `message-timestamp ${message.type === 'user' ? 'text-right' : 'text-left'} px-2`;
            const time = new Date(message.timestamp);
            timestampEl.textContent = time.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            contentWrapper.appendChild(timestampEl);
            
            // Add links if present
            if (message.links && message.links.length > 0) {
                const linksContainer = document.createElement('div');
                linksContainer.className = 'mt-3 space-y-2';
                
                message.links.forEach(link => {
                    const linkEl = document.createElement('a');
                    linkEl.href = link.url;
                    linkEl.className = 'shreeram-nav-link text-sm';
                    linkEl.innerHTML = `
                        <i class="fas ${link.icon}"></i>
                        <span>${link.text}</span>
                        <i class="fas fa-arrow-right ml-auto text-xs"></i>
                    `;
                    linkEl.addEventListener('click', () => {
                        this.logAnalytics('navigation_link_clicked', { url: link.url, text: link.text });
                    });
                    linksContainer.appendChild(linkEl);
                });
                
                contentWrapper.appendChild(linksContainer);
            }
            
            // Add quick replies if present (using enhanced style)
            if (message.quickReplies && message.quickReplies.length > 0) {
                const quickRepliesContainer = document.createElement('div');
                quickRepliesContainer.className = 'shreeram-quick-replies';
                
                message.quickReplies.forEach(reply => {
                    const replyBtn = document.createElement('button');
                    replyBtn.type = 'button';
                    replyBtn.className = 'shreeram-quick-reply-pill';
                    replyBtn.textContent = reply;
                    replyBtn.addEventListener('click', () => {
                        this.handleQuickReply(reply, quickRepliesContainer);
                    });
                    quickRepliesContainer.appendChild(replyBtn);
                });
                
                contentWrapper.appendChild(quickRepliesContainer);
            }
            
            messageEl.appendChild(contentWrapper);
            
            // Add user avatar for user messages
            if (message.type === 'user') {
                const userAvatarEl = document.createElement('div');
                userAvatarEl.className = 'w-8 h-8 bg-gradient-to-br from-orange-500 to-amber-500 rounded-full flex items-center justify-center flex-shrink-0 text-white text-sm font-semibold shadow-lg';
                userAvatarEl.textContent = (window.userName || 'U').charAt(0).toUpperCase();
                messageEl.appendChild(userAvatarEl);
            }
            
            this.elements.messages.appendChild(messageEl);
            
            console.log('[DEBUG] Message element appended to DOM');
            console.log('[DEBUG] Messages container children count:', this.elements.messages.children.length);
            
            // Scroll to bottom
            this.scrollToBottom();
        },

        /**
         * Handle quick reply button click
         */
        handleQuickReply(text, container) {
            // Remove quick reply buttons
            container.remove();
            
            // Log analytics
            this.logAnalytics('quick_reply_clicked', { text });
            
            // Process as user message
            this.elements.input.value = text;
            this.sendMessage();
        },

        /**
         * Scroll to bottom of message list
         */
        scrollToBottom() {
            setTimeout(() => {
                this.elements.messages.scrollTo({
                    top: this.elements.messages.scrollHeight,
                    behavior: 'smooth'
                });
            }, 100);
        },

        /**
         * Show typing indicator
         */
        showTyping() {
            this.state.isTyping = true;
            this.elements.typing.classList.remove('hidden');
            this.scrollToBottom();
        },

        /**
         * Hide typing indicator
         */
        hideTyping() {
            this.state.isTyping = false;
            this.elements.typing.classList.add('hidden');
        },

        /**
         * Show welcome message
         */
        async showWelcomeMessage() {
            this.showTyping();
            await this.delay(400);
            this.hideTyping();

            // PRODUCTION FIX: Safe profile access with fallbacks
            const p = window.chatbotUserProfile || {};
            const name = p.name ? p.name.split(' ')[0] : null;
            const completion = typeof p.profileCompletion === 'number' ? p.profileCompletion : null;

            let text = '🙏 Jai Shree Ram! I am ShreeRam AI, your personal career assistant. How can I help you today?';
            
            if (name && completion !== null) {
                text = `🙏 Jai Shree Ram, ${name}! I am ShreeRam AI, your personal career assistant.\n\nYour profile is ${completion}% complete. Ask me about resume tips, skills to learn, or job application strategy — I'll give you advice based on your profile!`;
            }

            this.displayMessage({
                type: 'bot',
                text,
                timestamp: new Date(),
                quickReplies: ['Resume Tips', 'Skills to Learn', 'Job Strategy', 'Track Applications'],
                isWelcome: true
            });
        },

        /**
         * Clear message history
         */
        clearHistory() {
            this.SessionManager.clearHistory();
        },

        /**
         * Delay utility
         */
        delay(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        },

        /**
         * Log analytics event
         */
        logAnalytics(eventType, data) {
            this.Analytics.logEvent(eventType, data);
        },

        /**
         * Message Handler Module
         */
        MessageHandler: {
            async process(message) {
                const startTime = performance.now();
                
                // Show typing indicator
                ShreeRamChatbot.showTyping();
                
                // Simulate processing delay
                await ShreeRamChatbot.delay(ShreeRamChatbot.config.typingDelay);
                
                // Try personalized response first using profile data
                const personalized = ShreeRamChatbot.PersonalizedResponder.respond(message);
                
                // Hide typing indicator
                ShreeRamChatbot.hideTyping();
                
                if (personalized) {
                    ShreeRamChatbot.displayMessage({
                        type: 'bot',
                        text: personalized.text,
                        links: personalized.links || [],
                        quickReplies: personalized.quickReplies || [],
                        timestamp: new Date()
                    });
                    ShreeRamChatbot.logAnalytics('personalized_response', { topic: personalized.topic });
                    return;
                }

                // Fall back to keyword matching
                const match = ShreeRamChatbot.KeywordMatcher.match(message);
                const response = this.generateResponse(match);
                
                ShreeRamChatbot.displayMessage({
                    type: 'bot',
                    text: response.text,
                    links: response.links,
                    quickReplies: response.quickReplies,
                    timestamp: new Date()
                });
                
                const processingTime = performance.now() - startTime;
                if (match.confidence >= ShreeRamChatbot.config.confidenceThreshold) {
                    ShreeRamChatbot.logAnalytics('message_sent', { 
                        topic: match.topic.id,
                        confidence: match.confidence,
                        processingTime
                    });
                } else {
                    ShreeRamChatbot.logAnalytics('unmatched_query', { 
                        query: message,
                        processingTime
                    });
                }
            },

            generateResponse(match) {
                if (match.confidence >= ShreeRamChatbot.config.confidenceThreshold) {
                    return {
                        text: match.topic.response.text,
                        links: match.topic.response.links || [],
                        quickReplies: match.topic.response.quickReplies || []
                    };
                } else {
                    return this.getFallbackResponse();
                }
            },

            getFallbackResponse() {
                return {
                    text: "I can help you with internships, resume building, or tracking applications. Choose an option below 👇",
                    quickReplies: ['How to Apply', 'Resume Tips', 'Profile Help', 'Track Applications']
                };
            }
        },

        /**
         * Keyword Matcher Module
         */
        KeywordMatcher: {
            stopWords: new Set([
                'the', 'a', 'an', 'is', 'are', 'was', 'were',
                'how', 'what', 'when', 'where', 'why', 'who',
                'can', 'could', 'should', 'would', 'will',
                'i', 'me', 'my', 'you', 'your', 'to', 'do'
            ]),

            /**
             * Check if input is a greeting
             * @param {string} input - User input text
             * @returns {boolean} - True if input matches greeting pattern
             */
            isGreeting(input) {
                const greetingPatterns = [
                    /^hii$/i,
                    /^hello$/i,
                    /^hey$/i,
                    /^hii\s+there$/i,
                    /^hello\s+there$/i,
                    /^hey\s+there$/i,
                    /^hii!$/i,
                    /^hello!$/i,
                    /^hey!$/i
                ];
                
                const trimmed = input.trim();
                return greetingPatterns.some(pattern => pattern.test(trimmed));
            },

            match(input) {
                // Check for greetings first
                if (this.isGreeting(input)) {
                    return {
                        topic: {
                            id: 'greeting',
                            response: {
                                text: "🙏 Jai Shree Ram! How can I guide you today?",
                                quickReplies: ['How to Apply', 'Resume Tips', 'Profile Help', 'Track Applications']
                            }
                        },
                        confidence: 100
                    };
                }
                
                // Continue with existing keyword matching
                const tokens = this.tokenize(input);
                const matches = this.findMatches(tokens);
                const best = this.selectBestMatch(matches);
                return best;
            },

            tokenize(input) {
                return input
                    .toLowerCase()
                    .replace(/[^\w\s]/g, '')
                    .split(/\s+/)
                    .filter(word => word.length > 0 && !this.stopWords.has(word));
            },

            findMatches(tokens) {
                const matches = [];
                
                for (const topic of ShreeRamChatbot.knowledgeBase) {
                    const score = this.calculateScore(tokens, topic.keywords);
                    if (score > 0) {
                        matches.push({ topic, score });
                    }
                }
                
                return matches;
            },

            calculateScore(tokens, keywords) {
                if (tokens.length === 0) return 0;
                
                let matches = 0;
                for (const token of tokens) {
                    for (const keyword of keywords) {
                        if (this.isMatch(token, keyword)) {
                            matches++;
                            break;
                        }
                    }
                }
                
                return (matches / tokens.length) * 100;
            },

            isMatch(token, keyword) {
                return token.includes(keyword) || keyword.includes(token);
            },

            selectBestMatch(matches) {
                if (matches.length === 0) {
                    return { topic: null, confidence: 0 };
                }
                
                // Sort by score descending
                matches.sort((a, b) => b.score - a.score);
                
                return {
                    topic: matches[0].topic,
                    confidence: matches[0].score
                };
            }
        },

        /**
         * Session Manager Module
         */
        SessionManager: {
            addMessage(message) {
                ShreeRamChatbot.state.messages.push(message);
                
                // Enforce message limit (FIFO)
                if (ShreeRamChatbot.state.messages.length > ShreeRamChatbot.config.maxMessages) {
                    ShreeRamChatbot.state.messages.shift();
                }
            },

            getHistory() {
                return ShreeRamChatbot.state.messages;
            },

            clearHistory() {
                ShreeRamChatbot.state.messages = [];
                ShreeRamChatbot.state.sessionStarted = false;
            }
        },

        /**
         * Analytics Module
         */
        Analytics: {
            logEvent(eventType, data) {
                const event = {
                    eventType,
                    timestamp: new Date().toISOString(),
                    userId: window.userId || 'anonymous',
                    data
                };
                
                // Log to console (production can integrate with analytics service)
                console.log('[ShreeRam Chatbot Analytics]', event);
            }
        },

        /**
         * Knowledge Base
         */
        knowledgeBase: [
            {
                id: 'apply_internship',
                category: 'navigation',
                keywords: ['apply', 'application', 'applying', 'submit'],
                response: {
                    text: "To apply for internships, visit your Recommendations page where you'll find personalized matches. Click 'Apply Now' on any internship that interests you.",
                    links: [
                        {
                            text: 'View Recommendations',
                            url: '/recommendations',
                            icon: 'fa-star'
                        }
                    ],
                    quickReplies: ['Track Applications', 'Profile Help'],
                    useCard: true,
                    cardTitle: '📋 How to Apply',
                    cardIcon: 'fa-clipboard-check'
                }
            },
            {
                id: 'profile_help',
                category: 'faq',
                keywords: ['profile', 'create', 'complete', 'edit', 'update'],
                response: {
                    text: 'Complete your profile to get better internship matches. Add your skills, education, and experience. A complete profile increases your chances of getting selected!',
                    links: [
                        {
                            text: 'Edit Profile',
                            url: '/profile/edit',
                            icon: 'fa-user-edit'
                        },
                        {
                            text: 'View Profile',
                            url: '/profile',
                            icon: 'fa-user'
                        }
                    ],
                    quickReplies: ['Resume Tips', 'Skills Help'],
                    useInfoBox: true,
                    infoType: 'info'
                }
            },
            {
                id: 'track_applications',
                category: 'navigation',
                keywords: ['track', 'status', 'application', 'tracker', 'progress'],
                response: {
                    text: 'Track all your applications in one place! See the status of each application, timeline predictions, and next steps.',
                    links: [
                        {
                            text: 'Application Tracker',
                            url: '/my-applications',
                            icon: 'fa-clipboard-list'
                        }
                    ],
                    quickReplies: ['How to Apply', 'Profile Help'],
                    useCard: true,
                    cardTitle: '📊 Application Tracker',
                    cardIcon: 'fa-chart-line'
                }
            },
            {
                id: 'resume_tips',
                category: 'career_advice',
                keywords: ['resume', 'cv', 'tips', 'writing', 'improve'],
                response: {
                    text: 'Resume Tips:\n• Keep it concise (1-2 pages)\n• Highlight relevant skills and projects\n• Use action verbs (developed, implemented, led)\n• Quantify achievements (increased by 20%)\n• Proofread for errors',
                    quickReplies: ['Skills Help', 'Profile Help', 'Career Guidance'],
                    useList: true,
                    listItems: [
                        { icon: 'fa-file-alt', text: 'Keep it concise (1-2 pages)' },
                        { icon: 'fa-star', text: 'Highlight relevant skills and projects' },
                        { icon: 'fa-bolt', text: 'Use action verbs (developed, implemented, led)' },
                        { icon: 'fa-chart-line', text: 'Quantify achievements (increased by 20%)' },
                        { icon: 'fa-check-circle', text: 'Proofread for errors' }
                    ]
                }
            },
            {
                id: 'skills_improvement',
                category: 'career_advice',
                keywords: ['skills', 'improve', 'learn', 'development', 'training'],
                response: {
                    text: 'Check your Career Dashboard to see your skill strengths and gaps. Focus on building skills that match your target internships. Consider online courses, projects, and certifications.',
                    links: [
                        {
                            text: 'View Dashboard',
                            url: '/dashboard',
                            icon: 'fa-chart-line'
                        }
                    ],
                    quickReplies: ['Resume Tips', 'Career Guidance'],
                    useInfoBox: true,
                    infoType: 'success'
                }
            },
            {
                id: 'career_guidance',
                category: 'career_advice',
                keywords: ['career', 'guidance', 'advice', 'path', 'direction'],
                response: {
                    text: 'Your Career Readiness Score shows how prepared you are for internships. Focus on:\n• Completing your profile\n• Building relevant skills\n• Gaining experience through projects\n• Applying to matched internships',
                    links: [
                        {
                            text: 'View Dashboard',
                            url: '/dashboard',
                            icon: 'fa-chart-line'
                        }
                    ],
                    quickReplies: ['Skills Help', 'Resume Tips'],
                    useCard: true,
                    cardTitle: '🎯 Career Guidance',
                    cardIcon: 'fa-compass'
                }
            },
            {
                id: 'recommendations',
                category: 'faq',
                keywords: ['recommendation', 'matches', 'matching', 'suggested', 'personalized'],
                response: {
                    text: 'Our recommendation system matches you with internships based on your skills, education, and preferences. The match confidence badge shows how well you fit each opportunity.',
                    links: [
                        {
                            text: 'View Recommendations',
                            url: '/recommendations',
                            icon: 'fa-star'
                        }
                    ],
                    quickReplies: ['How to Apply', 'Profile Help'],
                    useInfoBox: true,
                    infoType: 'info'
                }
            },
            {
                id: 'help_topics',
                category: 'meta',
                keywords: ['help', 'support', 'assist', 'guide'],
                response: {
                    text: 'I can help you with:\n• Applying for internships\n• Completing your profile\n• Tracking applications\n• Resume writing tips\n• Skills improvement\n• Career guidance',
                    quickReplies: ['How to Apply', 'Profile Help', 'Track Applications', 'Resume Tips'],
                    useGrid: true,
                    gridItems: [
                        { icon: 'fa-paper-plane', label: 'Apply' },
                        { icon: 'fa-user', label: 'Profile' },
                        { icon: 'fa-clipboard-list', label: 'Track' },
                        { icon: 'fa-file-alt', label: 'Resume' },
                        { icon: 'fa-graduation-cap', label: 'Skills' },
                        { icon: 'fa-compass', label: 'Career' }
                    ]
                }
            }
        ]
    };

    /**
     * Personalized Responder - uses window.chatbotUserProfile for context-aware replies
     */
    ShreeRamChatbot.PersonalizedResponder = {
        profile() {
            return window.chatbotUserProfile || null;
        },

        matches(msg, keywords) {
            const lower = msg.toLowerCase();
            return keywords.some(k => lower.includes(k));
        },

        respond(message) {
            const p = this.profile();
            if (!p) return null;

            const name = p.name ? p.name.split(' ')[0] : 'there';
            const skills = Array.isArray(p.skills) ? p.skills : [];
            const missing = Array.isArray(p.missingSkills) ? p.missingSkills.slice(0, 3) : [];
            const completion = p.profileCompletion || 0;
            const applied = p.appliedJobsCount || 0;
            const hasResume = p.hasResume;

            // --- Resume improvement ---
            if (this.matches(message, ['resume', 'cv', 'improve resume', 'resume tips'])) {
                const issues = [];
                if (completion < 80) issues.push(`your profile is only ${completion}% complete — fill in missing sections`);
                if (!hasResume) issues.push('you haven\'t uploaded a resume yet');
                if (skills.length < 3) issues.push('add more skills to your profile');
                if (missing.length) issues.push(`highlight in-demand skills like: ${missing.join(', ')}`);

                const text = issues.length
                    ? `Hi ${name}! Here's your personalized resume advice:\n\n` +
                      issues.map((i, idx) => `${idx + 1}. ${i.charAt(0).toUpperCase() + i.slice(1)}`).join('\n') +
                      `\n\nYour current skills (${skills.slice(0,4).join(', ') || 'none added'}) are a good start — make sure they're prominent on your resume.`
                    : `Great news ${name}! Your profile looks strong at ${completion}%. Make sure your resume highlights: ${skills.slice(0,4).join(', ')}. Quantify achievements with numbers wherever possible.`;

                return {
                    topic: 'resume',
                    text,
                    links: [{ text: 'Edit Profile', url: '/profile/edit', icon: 'fa-user-edit' }],
                    quickReplies: ['Skills to Learn', 'Job Strategy', 'Track Applications']
                };
            }

            // --- Skills improvement ---
            if (this.matches(message, ['skill', 'skills', 'learn', 'improve skill', 'what to learn'])) {
                const currentSkills = skills.slice(0, 4).join(', ') || 'none added yet';
                const toLearn = missing.length ? missing.join(', ') : 'Python, SQL, Git';
                const text = `${name}, based on your profile:\n\n` +
                    `✅ Your current skills: ${currentSkills}\n\n` +
                    `📚 Skills you should add next: ${toLearn}\n\n` +
                    `These are in high demand for internships. Focus on one at a time — start with the one closest to your career interests (${p.careerInterests || 'not set yet'}).`;

                return {
                    topic: 'skills',
                    text,
                    links: [{ text: 'Update Skills', url: '/profile/edit', icon: 'fa-graduation-cap' }],
                    quickReplies: ['Resume Tips', 'Job Strategy', 'Profile Help']
                };
            }

            // --- Job application strategy ---
            if (this.matches(message, ['apply', 'job strategy', 'application strategy', 'how to apply', 'get job', 'internship strategy'])) {
                let advice = '';
                if (completion < 60) {
                    advice = `${name}, your profile is only ${completion}% complete. Recruiters skip incomplete profiles — complete it first before applying.`;
                } else if (applied === 0) {
                    advice = `${name}, you haven't applied to any internships yet! With ${completion}% profile completion and skills in ${skills.slice(0,2).join(', ') || 'your area'}, you're ready to start. Apply to at least 5 matching internships this week.`;
                } else if (applied < 5) {
                    advice = `${name}, you've applied to ${applied} internship${applied > 1 ? 's' : ''} so far. Aim for 10-15 applications to increase your chances. Your profile at ${completion}% is solid — keep going!`;
                } else {
                    advice = `${name}, great effort with ${applied} applications! Focus on quality now — tailor each application to highlight your top skills: ${skills.slice(0,3).join(', ') || 'your skills'}. Follow up after 1 week.`;
                }

                return {
                    topic: 'job_strategy',
                    text: advice,
                    links: [
                        { text: 'View Recommendations', url: '/recommendations', icon: 'fa-star' },
                        { text: 'My Applications', url: '/my-applications', icon: 'fa-clipboard-list' }
                    ],
                    quickReplies: ['Resume Tips', 'Skills to Learn', 'Track Applications']
                };
            }

            // --- Profile completion ---
            if (this.matches(message, ['profile', 'complete profile', 'profile completion', 'profile help'])) {
                const missing_fields = [];
                if (!p.hasResume) missing_fields.push('upload your resume');
                if (!p.careerInterests) missing_fields.push('add career interests');
                if (skills.length < 3) missing_fields.push('add at least 3 skills');
                if (!p.academicBackground) missing_fields.push('fill in academic background');

                const text = missing_fields.length
                    ? `${name}, your profile is ${completion}% complete. To reach 100%:\n\n` +
                      missing_fields.map((f, i) => `${i + 1}. ${f.charAt(0).toUpperCase() + f.slice(1)}`).join('\n') +
                      '\n\nA complete profile gets 3x more recruiter views!'
                    : `${name}, your profile is ${completion}% complete — looking great! Make sure your skills (${skills.slice(0,3).join(', ')}) are up to date.`;

                return {
                    topic: 'profile',
                    text,
                    links: [{ text: 'Edit Profile', url: '/profile/edit', icon: 'fa-user-edit' }],
                    quickReplies: ['Resume Tips', 'Skills to Learn', 'Job Strategy']
                };
            }

            // --- Track applications ---
            if (this.matches(message, ['track', 'application status', 'my applications', 'applied'])) {
                const text = applied === 0
                    ? `${name}, you haven't applied to any internships yet. Visit Recommendations to find matches for your skills: ${skills.slice(0,2).join(', ') || 'add skills to your profile first'}.`
                    : `${name}, you've submitted ${applied} application${applied > 1 ? 's' : ''}. Track their status in My Applications. Keep applying — consistency is key!`;

                return {
                    topic: 'track',
                    text,
                    links: [{ text: 'My Applications', url: '/my-applications', icon: 'fa-clipboard-list' }],
                    quickReplies: ['Job Strategy', 'Resume Tips']
                };
            }

            return null; // No personalized match — fall through to keyword matcher
        }
    };

    // Initialize chatbot when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => ShreeRamChatbot.init());
    } else {
        ShreeRamChatbot.init();
    }

    // Expose to window for testing
    window.ShreeRamChatbot = ShreeRamChatbot;
})();
