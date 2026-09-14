<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-opus-5'),
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-flash-latest'),
    ],

    'open_food_facts' => [
        'base' => env('OPEN_FOOD_FACTS_BASE', 'https://world.openfoodfacts.org'),
        'user_agent' => env('OPEN_FOOD_FACTS_USER_AGENT', 'HealthyMe/1.0 ('.env('APP_URL', 'http://localhost').')'),
    ],

    'rxnorm' => [
        'base' => env('RXNORM_BASE', 'https://rxnav.nlm.nih.gov/REST'),
        'user_agent' => env('RXNORM_USER_AGENT', 'HealthyMe/1.0 ('.env('APP_URL', 'http://localhost').')'),
    ],

    /*
    | Google Analytics (GA4). Empty disables the tag. Production only —
    | local and testing never load it. Cookies stay off until the person
    | accepts on the banner (Google Consent Mode).
    */
    'google_analytics' => [
        'measurement_id' => env('GOOGLE_ANALYTICS_ID'),
    ],

];
