<?php

return [
    'low_stock_alerts_enabled' => env('LOW_STOCK_ALERTS_ENABLED', false),
    'low_stock_email_enabled' => env('LOW_STOCK_EMAIL_ENABLED', true),
    'low_stock_telegram_enabled' => env('LOW_STOCK_TELEGRAM_ENABLED', true),
    'low_stock_threshold_percent' => env('LOW_STOCK_THRESHOLD_PERCENT', 100),
    'low_stock_threshold_enabled' => env('LOW_STOCK_THRESHOLD_ENABLED', false),

    'expiry_alerts_enabled' => env('EXPIRY_ALERTS_ENABLED', false),
    'expiry_threshold_enabled' => env('EXPIRY_THRESHOLD_ENABLED', true),
    'expiry_threshold_days' => env('EXPIRY_THRESHOLD_DAYS', 30),
    'expiry_email_enabled' => env('EXPIRY_EMAIL_ENABLED', true),
    'expiry_telegram_enabled' => env('EXPIRY_TELEGRAM_ENABLED', true),
];
