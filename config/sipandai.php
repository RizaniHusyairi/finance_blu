<?php

return [
    'enabled' => env('SIPANDAI_ENABLED', false),
    // Base URL including /api. Credentials stay on the server.
    'url' => env('SIPANDAI_URL', 'http://127.0.0.1:3001/api'),
    'token' => env('SIPANDAI_TOKEN', ''),
    // Explicit finance mitra ID -> SIPANDAI airline code mapping.
    'airlines' => json_decode(env('SIPANDAI_AIRLINE_MAP', '{}'), true) ?: [],
];
