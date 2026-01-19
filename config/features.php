<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Control which features are enabled/disabled.
    | Useful for demo mode, A/B testing, or gradual rollouts.
    |
    | Phase 10: Demo Control
    | - Turn off features during viva if needed
    | - Test system behavior with features disabled
    | - Show understanding of feature flag patterns
    |
    */

    'analytics_enabled' => env('FEATURE_ANALYTICS', true),
    'recommendations_enabled' => env('FEATURE_RECOMMENDATIONS', true),
    'timeline_predictions_enabled' => env('FEATURE_TIMELINE_PREDICTIONS', true),
    'career_intelligence_enabled' => env('FEATURE_CAREER_INTELLIGENCE', true),
    'email_notifications_enabled' => env('FEATURE_EMAIL_NOTIFICATIONS', true),

    /*
    |--------------------------------------------------------------------------
    | Demo Mode
    |--------------------------------------------------------------------------
    |
    | When enabled:
    | - Disables application submission
    | - Disables status updates
    | - Shows read-only banner
    | - Prevents data corruption during demos
    |
    | IMPORTANT: Set to false in production!
    |
    */

    'demo_mode' => env('DEMO_MODE', false),

    /*
    |--------------------------------------------------------------------------
    | Demo Mode Settings
    |--------------------------------------------------------------------------
    */

    'demo_mode_banner' => 'Demo Mode – Data is read-only',
    'demo_mode_message' => 'This is a demonstration environment. All write operations are disabled.',

];
