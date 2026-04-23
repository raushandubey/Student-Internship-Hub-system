/**
 * Jest Configuration for ShreeRam AI Chatbot Tests
 * 
 * Configures Jest to run property-based and unit tests for the chatbot feature.
 */

export default {
    // Use jsdom environment for DOM testing
    testEnvironment: 'jsdom',
    
    // Test file patterns
    testMatch: [
        '**/tests/javascript/**/*.test.js'
    ],
    
    // Module file extensions
    moduleFileExtensions: ['js', 'json'],
    
    // Transform files using babel-jest for ES modules
    transform: {
        '^.+\\.js$': ['babel-jest', { configFile: './babel.config.cjs' }]
    },
    
    // Transform ES modules in node_modules
    transformIgnorePatterns: [
        'node_modules/(?!(fast-check)/)'
    ],
    
    // Coverage configuration
    collectCoverageFrom: [
        'public/js/chatbot.js',
        '!**/node_modules/**',
        '!**/vendor/**'
    ],
    
    // Coverage thresholds
    coverageThreshold: {
        global: {
            branches: 80,
            functions: 80,
            lines: 80,
            statements: 80
        }
    },
    
    // Setup files
    setupFilesAfterEnv: [],
    
    // Verbose output
    verbose: true,
    
    // Clear mocks between tests
    clearMocks: true,
    
    // Restore mocks between tests
    restoreMocks: true,
    
    // Timeout for tests (increased for property-based tests)
    testTimeout: 10000
};
