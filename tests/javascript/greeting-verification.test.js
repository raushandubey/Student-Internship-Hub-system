/**
 * Greeting Response Logic Verification Test
 * Task 3.4: Verify greeting response logic is working correctly
 */

import { describe, it, expect, beforeEach } from '@jest/globals';

// Setup minimal DOM
const setupDOM = () => {
    document.body.innerHTML = `
        <div id="shreeram-chatbot">
            <button id="chatbot-toggle-btn"></button>
            <div id="chatbot-window" class="hidden"></div>
            <div id="chatbot-messages"></div>
            <div id="chatbot-typing" class="hidden"></div>
            <input id="chatbot-input" />
            <button id="chatbot-send-btn"></button>
            <button id="chatbot-close-btn"></button>
            <i id="chatbot-icon"></i>
            <div id="chatbot-char-count" class="hidden"></div>
            <span id="chatbot-char-current"></span>
        </div>
    `;
    
    // Load chatbot
    require('../../public/js/chatbot.js');
};

describe('Task 3.4: Greeting Response Logic Verification', () => {
    beforeEach(() => {
        setupDOM();
    });

    describe('isGreeting() function verification', () => {
        it('should detect "hi" as a greeting', () => {
            const chatbot = window.ShreeRamChatbot;
            const matcher = chatbot.KeywordMatcher;
            
            expect(matcher.isGreeting('hi')).toBe(true);
        });

        it('should detect "hello" as a greeting', () => {
            const chatbot = window.ShreeRamChatbot;
            const matcher = chatbot.KeywordMatcher;
            
            expect(matcher.isGreeting('hello')).toBe(true);
        });

        it('should detect "hey" as a greeting', () => {
            const chatbot = window.ShreeRamChatbot;
            const matcher = chatbot.KeywordMatcher;
            
            expect(matcher.isGreeting('hey')).toBe(true);
        });

        it('should detect "hi there" as a greeting', () => {
            const chatbot = window.ShreeRamChatbot;
            const matcher = chatbot.KeywordMatcher;
            
            expect(matcher.isGreeting('hi there')).toBe(true);
        });

        it('should detect "hello there" as a greeting', () => {
            const chatbot = window.ShreeRamChatbot;
            const matcher = chatbot.KeywordMatcher;
            
            expect(matcher.isGreeting('hello there')).toBe(true);
        });

        it('should detect "hey there" as a greeting', () => {
            const chatbot = window.ShreeRamChatbot;
            const matcher = chatbot.KeywordMatcher;
            
            expect(matcher.isGreeting('hey there')).toBe(true);
        });

        it('should NOT detect non-greetings', () => {
            const chatbot = window.ShreeRamChatbot;
            const matcher = chatbot.KeywordMatcher;
            
            expect(matcher.isGreeting('help me')).toBe(false);
            expect(matcher.isGreeting('how to apply')).toBe(false);
            expect(matcher.isGreeting('profile')).toBe(false);
        });
    });

    describe('match() function greeting response verification', () => {
        it('should call isGreeting() BEFORE general keyword matching', () => {
            const chatbot = window.ShreeRamChatbot;
            const matcher = chatbot.KeywordMatcher;
            
            // Test that greeting is detected and returns greeting response
            const result = matcher.match('hello');
            
            expect(result.topic.id).toBe('greeting');
            expect(result.confidence).toBe(100);
        });

        it('should return correct greeting response text', () => {
            const chatbot = window.ShreeRamChatbot;
            const matcher = chatbot.KeywordMatcher;
            
            const result = matcher.match('hi');
            
            expect(result.topic.response.text).toBe('🙏 Jai Shree Ram! How can I guide you today?');
        });

        it('should include correct quick action buttons', () => {
            const chatbot = window.ShreeRamChatbot;
            const matcher = chatbot.KeywordMatcher;
            
            const result = matcher.match('hey');
            
            expect(result.topic.response.quickReplies).toEqual([
                'How to Apply',
                'Resume Tips',
                'Profile Help',
                'Track Applications'
            ]);
        });

        it('should work for all greeting patterns', () => {
            const chatbot = window.ShreeRamChatbot;
            const matcher = chatbot.KeywordMatcher;
            
            const greetings = ['hi', 'hello', 'hey', 'hi there', 'hello there', 'hey there'];
            
            greetings.forEach(greeting => {
                const result = matcher.match(greeting);
                
                expect(result.topic.id).toBe('greeting');
                expect(result.topic.response.text).toBe('🙏 Jai Shree Ram! How can I guide you today?');
                expect(result.topic.response.quickReplies).toContain('How to Apply');
                expect(result.topic.response.quickReplies).toContain('Resume Tips');
                expect(result.topic.response.quickReplies).toContain('Profile Help');
                expect(result.topic.response.quickReplies).toContain('Track Applications');
            });
        });

        it('should prioritize greeting detection over keyword matching', () => {
            const chatbot = window.ShreeRamChatbot;
            const matcher = chatbot.KeywordMatcher;
            
            // Even if "hello" might match other keywords, greeting should take priority
            const result = matcher.match('hello');
            
            expect(result.topic.id).toBe('greeting');
            expect(result.confidence).toBe(100);
        });
    });

    describe('Preservation: Non-greeting messages should use KeywordMatcher', () => {
        it('should process non-greeting messages through keyword matching', () => {
            const chatbot = window.ShreeRamChatbot;
            const matcher = chatbot.KeywordMatcher;
            
            const result = matcher.match('how to apply for internships');
            
            // Should NOT return greeting response
            expect(result.topic?.id).not.toBe('greeting');
        });
    });
});
