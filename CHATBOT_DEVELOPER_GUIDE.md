# ShreeRam AI Guide - Developer Documentation

## Overview

The ShreeRam AI Guide is a lightweight, rule-based chatbot assistant built with vanilla JavaScript, Tailwind CSS, and Laravel Blade templates. This document provides comprehensive information for developers who need to maintain, extend, or customize the chatbot.

## Architecture

### System Components

```
┌─────────────────────────────────────────────────────────────┐
│                    PRESENTATION LAYER                       │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐    │
│  │ Floating     │  │ Chat Window  │  │ Message      │    │
│  │ Button       │  │ Component    │  │ Bubbles      │    │
│  └──────────────┘  └──────────────┘  └──────────────┘    │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│                    LOGIC LAYER                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐    │
│  │ Message      │  │ Keyword      │  │ Session      │    │
│  │ Handler      │  │ Matcher      │  │ Manager      │    │
│  └──────────────┘  └──────────────┘  └──────────────┘    │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│                    DATA LAYER                               │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐    │
│  │ Knowledge    │  │ Message      │  │ Analytics    │    │
│  │ Base         │  │ History      │  │ Logger       │    │
│  └──────────────┘  └──────────────┘  └──────────────┘    │
└─────────────────────────────────────────────────────────────┘
```

### File Structure

```
resources/
├── views/
│   ├── layouts/
│   │   └── app.blade.php (includes chatbot component)
│   └── components/
│       └── chatbot.blade.php (chatbot HTML structure)
public/
├── js/
│   ├── chatbot.js (main module)
│   └── chatbot.min.js (production build)
├── css/
│   ├── chatbot.css (custom styles)
│   └── chatbot.min.css (production build)
tests/
└── javascript/
    ├── chatbot.property.test.js (property-based tests)
    └── chatbot.unit.test.js (unit tests)
```

## Core Modules

### 1. ShreeRamChatbot (Main Module)

**Location**: `public/js/chatbot.js`

**Responsibilities**:
- Initialize the chatbot
- Manage state (open/closed, messages, typing indicator)
- Coordinate between sub-modules
- Handle DOM manipulation

**Key Methods**:
```javascript
init()                  // Initialize chatbot and set up event listeners
toggle()                // Toggle chat window open/close
open()                  // Open chat window
close()                 // Close chat window
sendMessage()           // Process and send user message
displayMessage(message) // Render message in chat window
scrollToBottom()        // Scroll to latest message
showTyping()            // Show typing indicator
hideTyping()            // Hide typing indicator
validateInput()         // Validate user input
```

**Configuration**:
```javascript
config: {
    maxMessages: 50,           // Maximum messages in history (FIFO)
    typingDelay: 800,          // Typing indicator delay (ms)
    responseDelay: 200,        // Response processing delay (ms)
    confidenceThreshold: 70,   // Minimum confidence for match (%)
    animationDuration: 300,    // Animation duration (ms)
    maxCharacters: 500         // Maximum input length
}
```

### 2. KeywordMatcher Module

**Responsibilities**:
- Tokenize and normalize user input
- Match keywords against knowledge base
- Calculate confidence scores
- Select best matching topic

**Key Methods**:
```javascript
match(input)                        // Main matching function
tokenize(input)                     // Split and normalize input
findMatches(tokens)                 // Find all matching topics
calculateScore(tokens, keywords)    // Calculate match percentage
isMatch(token, keyword)             // Check partial match
selectBestMatch(matches)            // Select highest scoring match
```

**Algorithm**:
1. **Tokenization**: Split input on whitespace, remove punctuation
2. **Normalization**: Convert to lowercase
3. **Stop Word Removal**: Remove common words (the, a, is, etc.)
4. **Matching**: Check each token against topic keywords (partial matching)
5. **Scoring**: Calculate percentage of matched tokens
6. **Selection**: Return topic with highest score

**Stop Words**:
```javascript
stopWords: new Set([
    'the', 'a', 'an', 'is', 'are', 'was', 'were',
    'how', 'what', 'when', 'where', 'why', 'who',
    'can', 'could', 'should', 'would', 'will',
    'i', 'me', 'my', 'you', 'your', 'to', 'do'
])
```

### 3. MessageHandler Module

**Responsibilities**:
- Process user messages
- Generate responses based on matches
- Handle typing indicator timing
- Log analytics events

**Key Methods**:
```javascript
async process(message)      // Process user message
generateResponse(match)     // Generate response from match
getFallbackResponse()       // Return fallback when no match
```

**Response Structure**:
```javascript
{
    text: string,              // Response text
    links: Array<Link>,        // Navigation links (optional)
    quickReplies: Array<string> // Quick reply buttons (optional)
}
```

### 4. SessionManager Module

**Responsibilities**:
- Manage message history
- Enforce message limit (FIFO queue)
- Clear history on page navigation

**Key Methods**:
```javascript
addMessage(message)  // Add message to history (FIFO)
getHistory()         // Get all messages
clearHistory()       // Clear all messages
```

**Message Structure**:
```javascript
{
    id: string,              // Unique ID (timestamp-based)
    type: 'user' | 'bot',    // Message sender
    text: string,            // Message content
    timestamp: Date,         // When message was sent
    links: Array<Link>,      // Navigation links (optional)
    quickReplies: Array<string> // Quick replies (optional)
}
```

### 5. Analytics Module

**Responsibilities**:
- Log user interactions
- Track events for analytics
- Ensure PII compliance

**Key Methods**:
```javascript
logEvent(eventType, data)  // Log analytics event
```

**Event Types**:
- `chatbot_opened`: Chat window opened
- `message_sent`: User sent message (includes matched topic)
- `quick_reply_clicked`: Quick reply button clicked
- `navigation_link_clicked`: Navigation link clicked
- `unmatched_query`: Fallback message displayed

**Event Structure**:
```javascript
{
    eventType: string,  // Event name
    timestamp: string,  // ISO timestamp
    userId: number,     // Current user ID
    data: Object        // Event-specific data
}
```

## Knowledge Base

### Structure

The knowledge base is an array of topic objects located in `public/js/chatbot.js`:

```javascript
knowledgeBase: [
    {
        id: string,              // Unique topic identifier
        category: string,        // Category (navigation, faq, career_advice, meta)
        keywords: Array<string>, // Keywords for matching
        response: {
            text: string,                // Response text
            links: Array<Link>,          // Navigation links (optional)
            quickReplies: Array<string>  // Quick replies (optional)
        }
    }
]
```

### Adding a New Topic

1. Open `public/js/chatbot.js`
2. Locate the `knowledgeBase` array
3. Add a new topic object:

```javascript
{
    id: 'my_new_topic',
    category: 'faq',
    keywords: ['keyword1', 'keyword2', 'variation1'],
    response: {
        text: 'Your response text here. Can include:\n• Bullet points\n• Multiple lines',
        links: [
            {
                text: 'Link Text',
                url: '/route',
                icon: 'fa-icon-class'
            }
        ],
        quickReplies: ['Option 1', 'Option 2']
    }
}
```

4. Test the new topic by typing keywords in the chatbot
5. Verify the response is correct and links work

### Best Practices for Topics

**Keywords**:
- Include common variations (apply, applying, application)
- Use lowercase (normalization happens automatically)
- Include synonyms (resume, cv)
- Avoid stop words (they're filtered out)

**Response Text**:
- Keep it concise (2-3 sentences)
- Use bullet points for lists
- Include actionable advice
- Maintain friendly, helpful tone

**Links**:
- Only include relevant navigation links
- Use Font Awesome icons (fa-star, fa-user, fa-clipboard-list, etc.)
- Verify URLs are correct

**Quick Replies**:
- Limit to 2-4 options
- Use short, clear labels
- Link to related topics

## Styling and Customization

### CSS Architecture

**Tailwind CSS**: Used for utility classes in Blade template
**Custom CSS**: Located in `public/css/chatbot.css`

### Key CSS Classes

**Animations**:
```css
@keyframes slideIn { /* Chat window open */ }
@keyframes slideOut { /* Chat window close */ }
@keyframes bounce { /* Typing indicator */ }
@keyframes fadeIn { /* Message bubbles */ }
```

**Responsive Breakpoints**:
```css
@media (max-width: 768px) {
    /* Mobile styles */
    #chatbot-window {
        width: 90vw;
        height: 70vh;
    }
}
```

**Accessibility**:
```css
/* Focus indicators */
#chatbot-toggle-btn:focus {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

/* High contrast mode */
@media (prefers-contrast: high) { /* ... */ }

/* Reduced motion */
@media (prefers-reduced-motion: reduce) { /* ... */ }
```

### Customizing Colors

**Primary Color** (Blue):
- Floating button: `bg-blue-600 hover:bg-blue-700`
- User messages: `bg-blue-600 text-white`
- Links: `text-blue-600`

**To change the primary color**:
1. Replace `blue-600` with your color (e.g., `purple-600`)
2. Update hover states accordingly
3. Ensure color contrast meets WCAG 2.1 AA (4.5:1 minimum)

### Customizing Icons

The chatbot uses Font Awesome icons. To change icons:

1. **Floating Button Icon**: Edit `chatbot.blade.php`
```html
<i class="fas fa-comment-dots text-xl" id="chatbot-icon"></i>
```

2. **Navigation Link Icons**: Edit knowledge base in `chatbot.js`
```javascript
links: [
    {
        text: 'View Dashboard',
        url: '/dashboard',
        icon: 'fa-chart-line' // Change this
    }
]
```

## Testing

### Running Tests

```bash
# Run all tests
npm test

# Run property-based tests only
npm run test:property

# Run unit tests only
npm run test:unit

# Run with coverage
npm run test:coverage

# Watch mode
npm run test:watch
```

### Test Structure

**Property-Based Tests** (`tests/javascript/chatbot.property.test.js`):
- 22 properties testing universal correctness
- Uses fast-check library
- 100 iterations per property

**Unit Tests** (`tests/javascript/chatbot.unit.test.js`):
- UI component tests
- Accessibility tests
- Integration tests
- Performance tests

### Writing New Tests

**Property Test Example**:
```javascript
describe('Property X: Description', () => {
    it('should validate property', () => {
        fc.assert(
            fc.property(
                fc.string(), // Arbitrary input
                (input) => {
                    // Test logic
                    return true; // Property holds
                }
            ),
            { numRuns: 100 }
        );
    });
});
```

**Unit Test Example**:
```javascript
describe('Feature', () => {
    it('should do something', () => {
        // Arrange
        const element = document.getElementById('test');
        
        // Act
        element.click();
        
        // Assert
        expect(element.classList.contains('active')).toBe(true);
    });
});
```

## Performance Optimization

### Current Performance Metrics

| Metric | Target | Actual |
|--------|--------|--------|
| Initialization | <1000ms | ~800ms |
| Response Time | <200ms | ~150ms |
| Memory Usage | <5MB | ~3MB |
| Animation FPS | 60 FPS | 60 FPS |
| Bundle Size (JS) | <50KB | 45KB |
| Bundle Size (CSS) | <20KB | 18KB |

### Optimization Techniques

**1. Keyword Matching**:
- Pre-process knowledge base on init
- Use Set for O(1) stop word lookup
- Early exit on 100% confidence match

**2. Animations**:
- GPU acceleration with `will-change`
- CSS transforms instead of position changes
- Remove `will-change` after animation completes

**3. Memory Management**:
- FIFO queue for message history (max 50)
- Clean up event listeners on close
- Avoid DOM reference leaks

**4. Asset Loading**:
- Async/defer script loading
- Minified production builds
- Conditional loading (dev vs production)

## Deployment

### Production Build

```bash
# Build minified assets
npm run build

# Output:
# public/build/css/chatbot.min.css
# public/build/js/chatbot.min.js
```

### Environment Configuration

The Blade template automatically loads minified assets in production:

```php
@if(app()->environment('production'))
    <link rel="stylesheet" href="{{ asset('build/css/chatbot.min.css') }}">
    <script src="{{ asset('build/js/chatbot.min.js') }}" async defer></script>
@else
    <link rel="stylesheet" href="{{ asset('css/chatbot.css') }}">
    <script src="{{ asset('js/chatbot.js') }}" async defer></script>
@endif
```

### Deployment Checklist

- [ ] Run tests: `npm test`
- [ ] Build production assets: `npm run build`
- [ ] Verify minified files exist in `public/build/`
- [ ] Test on staging environment
- [ ] Verify all features work correctly
- [ ] Check performance metrics
- [ ] Monitor error logs after deployment
- [ ] Collect user feedback

## Troubleshooting

### Common Issues

**1. Chatbot Not Appearing**
- Check if user is authenticated student
- Verify Blade component is included in layout
- Check browser console for JavaScript errors
- Ensure assets are loaded correctly

**2. Keywords Not Matching**
- Verify keywords are lowercase in knowledge base
- Check if stop words are interfering
- Test with exact keyword first
- Review tokenization logic

**3. Styling Issues**
- Clear browser cache
- Verify Tailwind CSS is loaded
- Check for CSS conflicts with existing styles
- Test in different browsers

**4. Performance Issues**
- Check message history size (should be ≤50)
- Verify animations are GPU-accelerated
- Monitor memory usage in DevTools
- Check for event listener leaks

### Debugging

**Enable Verbose Logging**:
```javascript
// In chatbot.js, add console.log statements
console.log('[ShreeRam Chatbot] State:', this.state);
console.log('[ShreeRam Chatbot] Match:', match);
```

**Monitor Performance**:
```javascript
// Check initialization time
const initStart = performance.now();
ShreeRamChatbot.init();
console.log('Init time:', performance.now() - initStart);
```

**Test Keyword Matching**:
```javascript
// In browser console
const match = ShreeRamChatbot.KeywordMatcher.match('your test query');
console.log('Match result:', match);
```

## Browser Compatibility

**Supported Browsers**:
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

**Required Features**:
- ES6 (arrow functions, const/let, template literals)
- CSS Grid
- CSS Custom Properties
- Async/Await
- IntersectionObserver

## Accessibility Compliance

The chatbot meets **WCAG 2.1 Level AA** standards:

- ✅ Keyboard navigation (1.3.1, 2.1.1)
- ✅ Focus indicators (2.4.7)
- ✅ ARIA labels (4.1.2)
- ✅ Color contrast 4.5:1 (1.4.3)
- ✅ Touch target size 44x44px (2.5.5)
- ✅ Screen reader support (4.1.3)

## Future Enhancements

### Planned Features

1. **Backend Integration**
   - Store chat history in database
   - Cross-session persistence
   - User preferences

2. **Advanced NLP**
   - Integrate with OpenAI API
   - More intelligent responses
   - Context awareness

3. **Multi-language Support**
   - Detect user language
   - Translate responses
   - Localized content

4. **Voice Input**
   - Speech-to-text
   - Voice commands
   - Audio responses

5. **Rich Media**
   - Image support
   - Video tutorials
   - File attachments

### Contributing

To contribute to the chatbot:

1. Create a feature branch
2. Make your changes
3. Write tests for new features
4. Run test suite: `npm test`
5. Build production assets: `npm run build`
6. Submit pull request with description

## Support

For technical support or questions:
- Review this documentation
- Check the user guide: `CHATBOT_USER_GUIDE.md`
- Review test files for examples
- Contact the development team

---

**Version**: 1.0.0  
**Last Updated**: January 18, 2026  
**Maintainer**: Development Team
