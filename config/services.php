<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
    ],

    // Claude (Anthropic) — primary AI rewrite engine
    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
    ],

    // Alias for backward compatibility
    'claude' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
    ],

    // LaTeXLite — PDF compilation from LaTeX source
    'latexlite' => [
        'api_key' => env('LATEXLITE_API_KEY'),
        'url'     => 'https://api.latexlite.com/compile',
    ],

    // OpenRouter — third-tier AI fallback (DeepSeek V4 Flash — free tier)
    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
    ],

    // Resume Intelligence API — secures n8n integration endpoints
    'resume_intelligence' => [
        'api_key' => env('RESUME_INTELLIGENCE_API_KEY'),
    ],

    // n8n AI Orchestration Microservice
    'n8n' => [
        'webhook_url' => env('N8N_WEBHOOK_URL'),
    ],

];
