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

    'dolar' => [
        'driver' => env('DOLAR_DRIVER', 'open_er_api'),
        'open_er_api' => [
            'base_url' => env('OPEN_ER_API_BASE_URL', 'https://open.er-api.com/v6'),
            'timeout' => (int) env('OPEN_ER_API_TIMEOUT', 5),
            'retry_times' => (int) env('OPEN_ER_API_RETRY_TIMES', 2),
            'retry_sleep' => (int) env('OPEN_ER_API_RETRY_SLEEP', 200),
        ],
    ],

    'sismos' => [
        'driver' => env('SISMOS_DRIVER', 'usgs'),
        'usgs' => [
            'base_url' => env('USGS_BASE_URL', 'https://earthquake.usgs.gov/fdsnws/event/1/query'),
            'timeout' => (int) env('USGS_TIMEOUT', 5),
            'retry_times' => (int) env('USGS_RETRY_TIMES', 2),
            'retry_sleep' => (int) env('USGS_RETRY_SLEEP', 200),
            'minlatitude' => (float) env('USGS_MIN_LATITUDE', -19.0),
            'maxlatitude' => (float) env('USGS_MAX_LATITUDE', 0.5),
            'minlongitude' => (float) env('USGS_MIN_LONGITUDE', -82.0),
            'maxlongitude' => (float) env('USGS_MAX_LONGITUDE', -68.0),
        ],
    ],

    'feriados' => [
        'driver' => env('FERIADOS_DRIVER', 'nager'),
        'nager' => [
            'base_url' => env('NAGER_DATE_BASE_URL', 'https://date.nager.at/api/v3'),
            'timeout' => (int) env('NAGER_DATE_TIMEOUT', 5),
            'retry_times' => (int) env('NAGER_DATE_RETRY_TIMES', 2),
            'retry_sleep' => (int) env('NAGER_DATE_RETRY_SLEEP', 200),
        ],
    ],

    'ruc' => [
        'drivers' => env('RUC_DRIVERS', 'peru_consult,padron_sunat'),
        'peru_consult' => [
            'base_url' => env('PERU_CONSULT_URL', 'http://peru-consult-api:8080'),
            'token' => env('PERU_CONSULT_TOKEN', null),
            'timeout' => (int) env('PERU_CONSULT_TIMEOUT', 5),
            'retry_times' => (int) env('PERU_CONSULT_RETRY_TIMES', 2),
            'retry_sleep' => (int) env('PERU_CONSULT_RETRY_SLEEP', 200),
        ],
    ],

    'dni' => [
        'enabled' => env('DNI_ENABLED', false),
        'driver' => env('DNI_DRIVER', 'peru_consult'),
        'peru_consult' => [
            'base_url' => env('PERU_CONSULT_URL', 'http://peru-consult-api:8080'),
            'token' => env('PERU_CONSULT_TOKEN', null),
            'timeout' => (int) env('PERU_CONSULT_TIMEOUT', 5),
            'retry_times' => (int) env('PERU_CONSULT_RETRY_TIMES', 2),
            'retry_sleep' => (int) env('PERU_CONSULT_RETRY_SLEEP', 200),
        ],
        'turnstile' => [
            'secret_key' => env('TURNSTILE_SECRET_KEY', null),
            'verify_url' => env('TURNSTILE_VERIFY_URL', 'https://challenges.cloudflare.com/turnstile/v0/siteverify'),
            'bypass_in_testing' => env('TURNSTILE_BYPASS_IN_TESTING', false),
        ],
    ],

];
