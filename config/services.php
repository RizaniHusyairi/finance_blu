<?php

return [

    'tesseract' => [
        'path' => env('TESSERACT_PATH', 'C:\\Program Files\\Tesseract-OCR\\tesseract.exe'),
        'tessdata' => env('TESSERACT_TESSDATA', storage_path('app/tessdata')),
    ],

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

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Google Gemini (free tier) — merangkum daftar produk Surat Pesanan
    // menjadi judul "nama pekerjaan" pada auto-isi form kontrak eksternal.
    // Kosongkan GEMINI_API_KEY untuk menonaktifkan (fallback heuristik).
    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-flash-latest'),
    ],

];
