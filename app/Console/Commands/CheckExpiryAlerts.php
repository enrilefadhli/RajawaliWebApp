<?php

namespace App\Console\Commands;

use App\Models\BatchOfStock;
use App\Models\SystemSetting;
use App\Models\User;
use App\Notifications\ExpiryAlertNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class CheckExpiryAlerts extends Command
{
    protected $signature = 'inventory:check-expiry-alerts';

    protected $description = 'Send near-expiry and expired stock alerts to admins (Email/Telegram) based on system settings.';

    public function handle(): int
    {
        $settings = SystemSetting::first();

        $alertsEnabled = (bool) ($settings?->expiry_alerts_enabled ?? config('inventory.expiry_alerts_enabled'));
        if (! $alertsEnabled) {
            $this->info('Expiry alerts disabled.');
            return self::SUCCESS;
        }

        $thresholdEnabled = (bool) ($settings?->expiry_threshold_enabled ?? config('inventory.expiry_threshold_enabled', true));
        $thresholdDays = (int) ($settings?->expiry_threshold_days ?? config('inventory.expiry_threshold_days'));
        $thresholdDays = max(1, min(365, $thresholdDays));

        $admins = User::whereHas('roles', fn ($q) => $q->where('name', 'ADMIN'))->get();
        if ($admins->isEmpty()) {
            $this->warn('No ADMIN users found.');
            return self::SUCCESS;
        }

        $today = now()->startOfDay();
        $nearExpiryDate = (clone $today)->addDays($thresholdDays)->toDateString();
        $todayDate = $today->toDateString();

        $batches = BatchOfStock::query()
            ->where('quantity', '>', 0)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', $thresholdEnabled ? $nearExpiryDate : $todayDate)
            ->whereHas('product', fn ($q) => $q->where('status', 'ACTIVE'))
            ->with('product')
            ->orderBy('expiry_date')
            ->get();

        $sent = 0;

        foreach ($batches as $batch) {
            $product = $batch->product;
            if (! $product) {
                continue;
            }

            $daysLeft = (int) $today->diffInDays($batch->expiry_date->startOfDay(), false);

            if ($daysLeft < 0) {
                $status = 'EXPIRED';
                $key = "expiry_alert:expired:{$batch->id}:" . $batch->expiry_date->toDateString();
                $ttl = now()->addDays(30);
            } elseif ($daysLeft === 0) {
                $status = 'EXPIRY_TODAY';
                $key = "expiry_alert:today:{$batch->id}:" . $batch->expiry_date->toDateString();
                $ttl = now()->addDays(2);
            } else {
                $status = 'NEAR_EXPIRY';
                $key = "expiry_alert:near:{$batch->id}:" . $batch->expiry_date->toDateString() . ":{$thresholdDays}";
                $ttl = $batch->expiry_date->copy()->addDays(7);
            }

            if (Cache::has($key)) {
                continue;
            }

            $notification = new ExpiryAlertNotification($product, $batch, $daysLeft, $status, $settings);

            $telegramEnabled = (bool) ($settings?->low_stock_telegram_enabled ?? config('inventory.low_stock_telegram_enabled'));
            if ($telegramEnabled) {
                $notification->sendTelegramAlert();
            }

            $emailEnabled = (bool) ($settings?->low_stock_email_enabled ?? config('inventory.low_stock_email_enabled'));
            if ($emailEnabled) {
                if ($settings?->mail_username) {
                    config(['mail.mailers.smtp.username' => $settings->mail_username]);
                }
                if ($settings?->mail_password) {
                    config(['mail.mailers.smtp.password' => $settings->mail_password]);
                }

                Notification::send($admins, $notification);
            }

            Cache::put($key, true, $ttl);
            $sent++;
        }

        $this->info("Expiry alerts processed. Sent: {$sent}");

        return self::SUCCESS;
    }
}
