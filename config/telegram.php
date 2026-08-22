<?php

return [

    'bot_token' => env('TELEGRAM_BOT_TOKEN'),

    'bot_username' => env('TELEGRAM_BOT_USERNAME'),

    'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),

    'webhook_url' => env('TELEGRAM_WEBHOOK_URL', env('APP_URL').'/telegram/webhook'),

    'link_token_ttl_minutes' => (int) env('TELEGRAM_LINK_TOKEN_TTL_MINUTES', 10),

    'api_base_url' => env('TELEGRAM_API_BASE_URL', 'https://api.telegram.org'),

];
