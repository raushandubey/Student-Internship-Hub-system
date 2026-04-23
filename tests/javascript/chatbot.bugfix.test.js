/**
 * ShreeRam AI Chatbot - Bug Condition Exploration Test
 * 
 * CRITICAL: This test MUST FAIL on unfixed code - failure confirms the bugs exist
 * DO NOT attempt to fix the test or the code when it fails
 * 
 * This test encodes the expected behavior - it will validate the fix when it passes after implementation
 * 
 * GOAL: Surface counterexamples that demonstrate the six bugs exist
 * 
 * @requires jest
 * @requires jsdom
 */

import { describe, it, expect, beforeEach, afterEach, jest } from '@jest/globals';

// Setup DOM environment with chatbot HTML
const setupChatbotDOM = () => {
    // Setup DOM structure
    document.body.innerHTML = `
        <div id="shreeram-chatbot">
            <button id="chatbot-toggle-btn" aria-expanded="false" aria-label="Open chat">
                <i id="chatbot-icon" class="fas fa-robot text-2xl relative z-10"></i>
            </button>
            <div id="chatbot-window" class="hidden shreeram-chat-window" role="dialog" aria-label="ShreeRam AI Assistant" style="height: 680px;">
                <button id="chatbot-close-btn" aria-label="Close chat"></button>
                <div id="chatbot-messages" class="shreeram-messages-container" role="log" aria-live="polite" style="height: 450px; overflow-y: auto;"></div>
                <div id="chatbot-typing" class="hidden"></div>
                <input id="chatbot-input" class="shreeram-input" type="text" maxlength="500" style="height: 3.5rem;" />
                <button id="chatbot-send-btn" disabled></button>
                <div id="chatbot-char-count" class="hidden">
                    <span id="chatbot-char-current">0</span>/500
                </div>
            </div>
        </div>
    `;

    // Load and execute chatbot code
    require('../../public/js/chatbot.js');
};

describe('Bug Condition Exploration Test - Critical Chatbot Functionality Failures', () => {
    beforeEach(() => {
        jest.useFakeTimers();
        setupChatbotDOM();
    });

    afterEach(() => {
        jest.clearAllTimers();
        jest.useRealTimers();
        document.body.innerHTML = '';
    });

    /**
     * Bug 1: Auto-Greeting Message Bug
     * Expected: Welcome message should NOT appear automatically on first open (bug exists)
     * Validates: Requirements 1.1, 1.2, 1.3
     */
    describe('Bug 1: Auto-Greeting Display Failure', () => {
        it('should NOT display welcome message automatically when chatbot opens for first time (bug exists)', () => {
            const chatbot = window.ShreeRamChatbot;
            const messagesContainer = document.getElementById('chatbot-messages');
            
            // Verify chatbot is initialized
            expect(chatbot).toBeDefined();
            expect(chatbot.state.sessionStarted).toBe(false);
            
            // Open chatbot for the first time
            chatbot.open();
            
            // Wait for the current 200ms delay
            jest.advanceTimersByTime(200);
            
            // BUG: Welcome message should appear but doesn't (or timing is wrong)
            // Expected behavior: Welcome message with text "🙏 Jai Shree Ram! I am ShreeRam AI..."
            const welcomeMessage = messagesContainer.querySelector('.shreeram-welcome-message');
            
            // This assertion should FAIL on unfixed code
            expect(welcomeMessage).toBeTruthy();
            expect(welcomeMessage.textContent).toContain('🙏 Jai Shree Ram! I am ShreeRam AI');
            expect(welcomeMessage.textContent).toContain('personal career assistant');
            
            // Verify quick reply buttons are present
            const quickReplies = messagesContainer.querySelectorAll('.shreeram-quick-reply-pill');
            expect(quickReplies.length).toBeGreaterThan(0);
            expect(Array.from(quickReplies).some(btn => btn.textContent === 'How to Apply')).toBe(true);
        });
    });

    /**
     * Bug 2: Chatbox Size Bug
     * Expected: Chatbox height should be 680px (oversized, bug exists)
     * Validates: Requirements 1.4, 1.5, 1.6
     */
    describe('Bug 2: Oversized Chatbox Dimensions', () => {
        it('should have oversized height of 680px instead of compact 450px (bug exists)', () => {
            const chatWindow = document.getElementById('chatbot-window');
            
            // Get computed height
            const computedStyle = window.getComputedStyle(chatWindow);
            const height = parseInt(computedStyle.height);
            
            // BUG: Height is 680px (oversized) instead of 450px (compact)
            // This assertion should FAIL on unfixed code - we expect 450px but get 680px
            expect(height).toBe(450);
            expect(height).toBeLessThanOrEqual(480);
            expect(height).toBeGreaterThanOrEqual(420);
        });

        it('should have message container with proper height for internal scrolling (bug exists)', () => {
            const messagesContainer = document.getElementById('chatbot-messages');
            
            // Get computed height
            const computedStyle = window.getComputedStyle(messagesContainer);
            const height = parseInt(computedStyle.height);
            
            // BUG: Message container height should be ~320px for compact design
            // This assertion should FAIL on unfixed code - we expect 320px but get 450px
            expect(height).toBe(320);
            expect(height).toBeLessThanOrEqual(350);
            expect(height).toBeGreaterThanOrEqual(300);
        });
    });

    /**
     * Bug 3: Message Bubble Styling Bug
     * Expected: Bot bubbles should use 85% max-width instead of 70% (bug exists)
     * Validates: Requirements 1.7, 1.8, 1.9
     */
    describe('Bug 3: Inconsistent Message Bubble Styling', () => {
        it('should have bot bubbles with 85% max-width instead of 70% (bug exists)', () => {
            const chatbot = window.ShreeRamChatbot;
            
            // Display a bot message
            chatbot.displayMessage({
                type: 'bot',
                text: 'Test message',
                timestamp: new Date()
            });
            
            // Find the bot bubble
            const botBubble = document.querySelector('.shreeram-bot-bubble');
            expect(botBubble).toBeTruthy();
            
            // Get computed max-width
            const computedStyle = window.getComputedStyle(botBubble);
            const maxWidth = computedStyle.maxWidth;
            
            // BUG: max-width is 85% instead of 70%
            // This assertion should FAIL on unfixed code
            expect(maxWidth).toBe('70%');
        });

        it('should have consistent padding on message bubbles (bug exists)', () => {
            const chatbot = window.ShreeRamChatbot;
            
            // Display bot and user messages
            chatbot.displayMessage({
                type: 'bot',
                text: 'Bot message',
                timestamp: new Date()
            });
            
            chatbot.displayMessage({
                type: 'user',
                text: 'User message',
                timestamp: new Date()
            });
            
            const botBubble = document.querySelector('.shreeram-bot-bubble');
            const userBubble = document.querySelector('.shreeram-user-bubble');
            
            // Verify padding is 0.875rem 1.125rem (14px 18px)
            const botStyle = window.getComputedStyle(botBubble);
            const userStyle = window.getComputedStyle(userBubble);
            
            // This should pass if padding is correct
            expect(botStyle.padding).toContain('14px');
            expect(botStyle.padding).toContain('18px');
            expect(userStyle.padding).toContain('14px');
            expect(userStyle.padding).toContain('18px');
        });
    });

    /**
     * Bug 4: Greeting Response Logic Bug
     * Expected: Greeting messages should return fallback instead of greeting response (bug exists)
     * Validates: Requirements 1.10, 1.11, 1.12
     */
    describe('Bug 4: Broken Greeting Detection Logic', () => {
        it('should return fallback response instead of greeting-specific response for "hello" (bug exists)', async () => {
            const chatbot = window.ShreeRamChatbot;
            const messagesContainer = document.getElementById('chatbot-messages');
            
            // Clear any existing messages
            messagesContainer.innerHTML = '';
            
            // Send greeting message
            const input = document.getElementById('chatbot-input');
            input.value = 'hello';
            
            // Trigger send
            await chatbot.sendMessage();
            
            // Advance timers for typing delay
            jest.advanceTimersByTime(1000);
            
            // Find bot response
            const botMessages = messagesContainer.querySelectorAll('.shreeram-bot-bubble');
            const lastBotMessage = botMessages[botMessages.length - 1];
            
            expect(lastBotMessage).toBeTruthy();
            
            // BUG: Should return greeting response "🙏 Jai Shree Ram! How can I guide you today?"
            // but returns fallback "I can help you with internships..."
            // This assertion should FAIL on unfixed code
            expect(lastBotMessage.textContent).toContain('🙏 Jai Shree Ram! How can I guide you today?');
            
            // Verify quick action buttons are present
            const quickReplies = messagesContainer.querySelectorAll('.shreeram-quick-reply-pill');
            expect(quickReplies.length).toBeGreaterThan(0);
        });

        it('should detect greeting patterns correctly', () => {
            const chatbot = window.ShreeRamChatbot;
            const matcher = chatbot.KeywordMatcher;
            
            // Test greeting detection
            expect(matcher.isGreeting('hi')).toBe(true);
            expect(matcher.isGreeting('hello')).toBe(true);
            expect(matcher.isGreeting('hey')).toBe(true);
            expect(matcher.isGreeting('hi there')).toBe(true);
            
            // Non-greetings should return false
            expect(matcher.isGreeting('help me')).toBe(false);
            expect(matcher.isGreeting('how to apply')).toBe(false);
        });
    });

    /**
     * Bug 5: Input Box Refinement Bug
     * Expected: Input box should have 3.5rem height and no orange glow on focus (bug exists)
     * Validates: Requirements 1.13, 1.14, 1.15
     */
    describe('Bug 5: Unrefined Input Box Styling', () => {
        it('should have excessive height of 3.5rem instead of 3rem (bug exists)', () => {
            const input = document.getElementById('chatbot-input');
            
            // Get computed height
            const computedStyle = global.window.getComputedStyle(input);
            const height = computedStyle.height;
            
            // BUG: Height is 3.5rem (56px) instead of 3rem (48px)
            // This assertion should FAIL on unfixed code
            expect(height).toBe('48px'); // 3rem = 48px
            expect(parseInt(height)).toBeLessThanOrEqual(48);
        });

        it('should display orange glow effect on focus (bug exists)', () => {
            const input = document.getElementById('chatbot-input');
            
            // Trigger focus
            input.focus();
            
            // Get computed box-shadow
            const computedStyle = global.window.getComputedStyle(input);
            const boxShadow = computedStyle.boxShadow;
            
            // BUG: Should have orange glow with box-shadow: 0 0 0 3px rgba(255, 122, 0, 0.2)
            // This assertion should FAIL on unfixed code if glow is missing
            expect(boxShadow).toContain('rgba(255, 122, 0');
            expect(boxShadow).toContain('0px 0px 0px 3px');
        });
    });

    /**
     * Bug 6: Testing Validation Bug
     * Expected: Test suite should lack comprehensive coverage (bug exists)
     * Validates: Requirements 1.16, 1.17, 1.18
     */
    describe('Bug 6: Insufficient Test Coverage', () => {
        it('should validate greeting appearance on first open', () => {
            // This test validates that greeting appears automatically
            // If this test exists and passes, coverage is adequate
            const chatbot = window.ShreeRamChatbot;
            
            chatbot.open();
            jest.advanceTimersByTime(600);
            
            const messagesContainer = document.getElementById('chatbot-messages');
            const welcomeMessage = messagesContainer.querySelector('.shreeram-welcome-message');
            
            expect(welcomeMessage).toBeTruthy();
        });

        it('should validate compact sizing is used', () => {
            // This test validates chatbox uses compact dimensions
            const chatWindow = document.getElementById('chatbot-window');
            const computedStyle = window.getComputedStyle(chatWindow);
            const height = parseInt(computedStyle.height);
            
            expect(height).toBeLessThanOrEqual(480);
            expect(height).toBeGreaterThanOrEqual(420);
        });

        it('should validate scroll functionality works correctly', () => {
            // This test validates scrolling behavior
            const chatbot = window.ShreeRamChatbot;
            const messagesContainer = document.getElementById('chatbot-messages');
            
            // Add multiple messages to exceed container height
            for (let i = 0; i < 20; i++) {
                chatbot.displayMessage({
                    type: 'bot',
                    text: `Message ${i}`,
                    timestamp: new Date()
                });
            }
            
            // Verify scrolling is enabled
            expect(messagesContainer.scrollHeight).toBeGreaterThan(messagesContainer.clientHeight);
            
            // Verify overflow-y is auto
            const computedStyle = window.getComputedStyle(messagesContainer);
            expect(computedStyle.overflowY).toBe('auto');
        });
    });
});
