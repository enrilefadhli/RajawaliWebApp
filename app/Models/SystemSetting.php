<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = [
        'low_stock_alerts_enabled',
        'low_stock_email_enabled',
        'low_stock_telegram_enabled',
        'low_stock_threshold_percent',
        'low_stock_threshold_enabled',
        'expiry_alerts_enabled',
        'expiry_threshold_enabled',
        'expiry_threshold_days',
        'expiry_email_enabled',
        'expiry_telegram_enabled',
        'telegram_bot_token',
        'telegram_chat_id',
        'mail_username',
        'mail_password',
    ];

    protected $casts = [
        'low_stock_alerts_enabled' => 'boolean',
        'low_stock_email_enabled' => 'boolean',
        'low_stock_telegram_enabled' => 'boolean',
        'low_stock_threshold_percent' => 'integer',
        'low_stock_threshold_enabled' => 'boolean',
        'expiry_alerts_enabled' => 'boolean',
        'expiry_threshold_enabled' => 'boolean',
        'expiry_threshold_days' => 'integer',
        'expiry_email_enabled' => 'boolean',
        'expiry_telegram_enabled' => 'boolean',
        'telegram_bot_token' => 'encrypted',
        'telegram_chat_id' => 'encrypted',
        'mail_username' => 'encrypted',
        'mail_password' => 'encrypted',
    ];
}
