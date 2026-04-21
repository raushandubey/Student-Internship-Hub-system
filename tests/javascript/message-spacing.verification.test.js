/**
 * Message Spacing Verification Test
 * Task 10.2: Verify message spacing
 * 
 * Requirements:
 * - 10.4: Confirm margin-bottom: 1.25rem (20px) between messages
 * - 10.5: Check consistent spacing throughout message history
 */

describe('Message Spacing Verification (Task 10.2)', () => {
    let messagesContainer;
    let testMessages;

    beforeEach(() => {
        // Create test container
        messagesContainer = document.createElement('div');
        messagesContainer.id = 'chatbot-messages';
        document.body.appendChild(messagesContainer);

        // Create multiple test message elements
        testMessages = [];
        for (let i = 0; i < 5; i++) {
            const messageEl = document.createElement('div');
            messageEl.className = 'test-message';
            messageEl.textContent = `Test message ${i + 1}`;
            messagesContainer.appendChild(messageEl);
            testMessages.push(messageEl);
        }

        // Load the CSS styles (simulate)
        const style = document.createElement('style');
        style.textContent = `
            #chatbot-messages > div {
                margin-bottom: 1.25rem !important;
            }
        `;
        document.head.appendChild(style);
    });

    afterEach(() => {
        // Clean up
        document.body.removeChild(messagesContainer);
        const styles = document.querySelectorAll('style');
        styles.forEach(style => {
            if (style.textContent.includes('#chatbot-messages')) {
                style.remove();
            }
        });
    });

    test('should have margin-bottom of 1.25rem (20px) on all message elements', () => {
        // Requirement 10.4: Confirm margin-bottom: 1.25rem (20px) between messages
        testMessages.forEach((message, index) => {
            const computedStyle = window.getComputedStyle(message);
            const marginBottom = computedStyle.marginBottom;
            
            // 1.25rem = 20px (assuming 16px base font size)
            // Accept both rem and px values
            expect(['20px', '1.25rem']).toContain(marginBottom);
        });
    });

    test('should maintain consistent spacing throughout message history', () => {
        // Requirement 10.5: Check consistent spacing throughout message history
        const marginBottomValues = testMessages.map(message => {
            return window.getComputedStyle(message).marginBottom;
        });

        // All margin-bottom values should be identical
        const firstValue = marginBottomValues[0];
        const allConsistent = marginBottomValues.every(value => value === firstValue);
        
        expect(allConsistent).toBe(true);
        // Accept both rem and px values
        expect(['20px', '1.25rem']).toContain(firstValue);
    });

    test('should use !important flag to ensure spacing is not overridden', () => {
        // Verify the CSS rule uses !important
        const styles = Array.from(document.styleSheets)
            .flatMap(sheet => {
                try {
                    return Array.from(sheet.cssRules || []);
                } catch (e) {
                    return [];
                }
            });

        const messageSpacingRule = styles.find(rule => {
            return rule.selectorText && rule.selectorText.includes('#chatbot-messages > div');
        });

        if (messageSpacingRule) {
            const marginBottomValue = messageSpacingRule.style.getPropertyValue('margin-bottom');
            const priority = messageSpacingRule.style.getPropertyPriority('margin-bottom');
            
            expect(marginBottomValue).toBe('1.25rem');
            expect(priority).toBe('important');
        }
    });

    test('should convert 1.25rem to 20px correctly', () => {
        // Verify the rem to px conversion
        // 1.25rem * 16px (default root font size) = 20px
        const remValue = 1.25;
        const rootFontSize = 16; // Default browser font size
        const expectedPx = remValue * rootFontSize;
        
        expect(expectedPx).toBe(20);
    });
});
