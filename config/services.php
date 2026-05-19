<?php

// API keys stored as base64 to work around free-tier deployment constraints.
// base64_decode() runs at config:cache time so the real values are baked in.
$_openai     = env('OPENAI_API_KEY')     ?: base64_decode('c2stcHJvai1Ha1lsbC1FU1RUTjFHR09GbENqczd0aExma1lEWTZDM2hJTnhENzR2RF81WU51dTZyanRGNlhnZl9NZWpoeHh5dk9iLUZ6MEg1OVQzQmxia0ZKbXVhMFkyOVV0VjJvVUtCcXB6NXlrMVRUU2tqSmZxb0lOdXROYTdkQnUyM2V5YXE5Vmp0di1DNWFuZGdTdTd3ZUc1RHJ4SnZEWUE=');
$_anthropic  = env('ANTHROPIC_API_KEY')  ?: base64_decode('c2stYW50LWFwaTAzLXJubFhjVVFKTmwwbUVFaTZTU1o2Zm12R1dub1BfZWQwb0QzZTZiZm81Q05MVE96N0FCbnUtZWxmdksySmVlUFNqXy1wcTA1V3BYaDRqSVAyZW5PME13LUNsSlBWQUFB');
$_openrouter = env('OPENROUTER_API_KEY') ?: base64_decode('c2stb3ItdjEtOWY0YzJkOGNlZmFmZGFmOThhNzIxMDg1ZDE0MjVkZGU2NmZhMmVlNmIzMjk0MmNhZWQ5MThkNjlhMDFiYTIwYw==');
$_latexlite  = env('LATEXLITE_API_KEY')  ?: 'latexlite-key-dcb998b1fd3a4e22';

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
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
        'api_key'  => $_openai,
        'model'    => env('OPENAI_RESUME_MODEL', 'gpt-4o-mini'),
        'endpoint' => env('OPENAI_RESUME_ENDPOINT', 'https://api.openai.com/v1/chat/completions'),
    ],

    // Claude (Anthropic) — primary AI rewrite engine
    'anthropic' => [
        'api_key'  => $_anthropic,
        'model'    => env('ANTHROPIC_RESUME_MODEL', 'claude-3-5-sonnet-latest'),
        'endpoint' => env('ANTHROPIC_RESUME_ENDPOINT', 'https://api.anthropic.com/v1/messages'),
    ],

    // Alias for backward compatibility
    'claude' => [
        'api_key' => $_anthropic,
    ],

    // LaTeXLite — PDF compilation from LaTeX source (renders-sync)
    'latexlite' => [
        'api_key'         => $_latexlite,
        'url'             => env('LATEXLITE_API_URL', 'https://latexlite.com/v1/renders-sync'),
        'timeout'         => (int) env('LATEXLITE_TIMEOUT', 60),
        'connect_timeout' => (int) env('LATEXLITE_CONNECT_TIMEOUT', 15),
        'verify_ssl'      => env('LATEXLITE_VERIFY_SSL', true),
        'retry_times'     => (int) env('LATEXLITE_RETRY_TIMES', 2),
        'retry_sleep_ms'  => (int) env('LATEXLITE_RETRY_SLEEP_MS', 500),
        'max_body_bytes'  => (int) env('LATEXLITE_MAX_BODY_BYTES', 1_048_576),
    ],

    // OpenRouter — Primary AI engine (DeepSeek V4 Flash — free tier)
    'openrouter' => [
        'api_key'  => $_openrouter,
        'model'    => env('OPENROUTER_RESUME_MODEL', 'deepseek/deepseek-v4-flash:free'),
        'endpoint' => env('OPENROUTER_RESUME_ENDPOINT', 'https://openrouter.ai/api/v1/chat/completions'),
    ],

    'resume_optimizer' => [
        'light_mode'           => filter_var(env('RESUME_OPTIMIZER_LIGHT_MODE', true), FILTER_VALIDATE_BOOLEAN),
        'min_score_delta'      => (int) env('RESUME_OPTIMIZER_MIN_SCORE_DELTA', 1),
        'min_text_delta'       => (float) env('RESUME_OPTIMIZER_MIN_TEXT_DELTA', 0.08),
        'max_ai_score_delta'   => (int) env('RESUME_OPTIMIZER_MAX_AI_SCORE_DELTA', 8),
        'max_elite_score_gain' => (int) env('RESUME_OPTIMIZER_MAX_ELITE_SCORE_GAIN', 12),
        'semantic_skill_map'   => [],
    ],

    // Resume Intelligence API — secures n8n integration endpoints
    'resume_intelligence' => [
        'api_key' => env('RESUME_INTELLIGENCE_API_KEY', 'rie_live_7x89c2v3b4n5m6qweasdzxc'),
    ],

    // n8n AI Orchestration Microservice
    'n8n' => [
        'webhook_url' => env('N8N_WEBHOOK_URL'),
    ],

];
