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
        'model' => env('OPENAI_RESUME_MODEL', 'gpt-4o-mini'),
        'endpoint' => env('OPENAI_RESUME_ENDPOINT', 'https://api.openai.com/v1/chat/completions'),
    ],

    // Claude (Anthropic) — primary AI rewrite engine
    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_RESUME_MODEL', 'claude-3-5-sonnet-latest'),
        'endpoint' => env('ANTHROPIC_RESUME_ENDPOINT', 'https://api.anthropic.com/v1/messages'),
    ],

    // Alias for backward compatibility
    'claude' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
    ],

    // LaTeXLite — PDF compilation from LaTeX source (renders-sync)
    'latexlite' => [
        'api_key' => env('LATEXLITE_API_KEY'),
        'url'     => env('LATEXLITE_API_URL', 'https://latexlite.com/v1/renders-sync'),
        'timeout' => (int) env('LATEXLITE_TIMEOUT', 60),
        'connect_timeout' => (int) env('LATEXLITE_CONNECT_TIMEOUT', 15),
        // true | false | path to a CA bundle (.pem). Use false only for local dev SSL issues.
        'verify_ssl' => env('LATEXLITE_VERIFY_SSL', true),
        'retry_times' => (int) env('LATEXLITE_RETRY_TIMES', 2),
        'retry_sleep_ms' => (int) env('LATEXLITE_RETRY_SLEEP_MS', 500),
        'max_body_bytes' => (int) env('LATEXLITE_MAX_BODY_BYTES', 1_048_576),
    ],

    // OpenRouter — Primary AI engine (DeepSeek V4 Flash — free tier)
    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        'model' => env('OPENROUTER_RESUME_MODEL', 'deepseek/deepseek-v4-flash:free'),
        'endpoint' => env('OPENROUTER_RESUME_ENDPOINT', 'https://openrouter.ai/api/v1/chat/completions'),
    ],

    'resume_optimizer' => [
        'light_mode' => filter_var(env('RESUME_OPTIMIZER_LIGHT_MODE', true), FILTER_VALIDATE_BOOLEAN),
        'min_score_delta' => (int) env('RESUME_OPTIMIZER_MIN_SCORE_DELTA', 1),
        'min_text_delta' => (float) env('RESUME_OPTIMIZER_MIN_TEXT_DELTA', 0.08),
        'max_ai_score_delta' => (int) env('RESUME_OPTIMIZER_MAX_AI_SCORE_DELTA', 8),
        'max_elite_score_gain' => (int) env('RESUME_OPTIMIZER_MAX_ELITE_SCORE_GAIN', 12),
        // Recruiter-term => evidence phrases (merged with built-in SemanticSkillMatcher map)
        'semantic_skill_map' => [],
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
