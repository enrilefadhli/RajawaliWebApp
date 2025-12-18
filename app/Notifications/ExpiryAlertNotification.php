<?php

namespace App\Notifications;

use App\Models\BatchOfStock;
use App\Models\Product;
use App\Models\SystemSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExpiryAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Product $product,
        public readonly BatchOfStock $batch,
        public readonly int $daysLeft,
        public readonly string $status,
        public readonly ?SystemSetting $settings = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        $emailEnabled = $this->settings?->low_stock_email_enabled ?? config('inventory.low_stock_email_enabled');

        return $emailEnabled ? ['mail'] : [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $expiryText = $this->batch->expiry_date?->format('d/m/Y') ?? '-';
        $subject = "[Rajawali IMS] Expiry Alert: {$this->product->product_code} ({$this->status})";

        $mail = (new MailMessage())
            ->subject($subject)
            ->greeting('Expiry Alert')
            ->line("Product: {$this->product->product_name}")
            ->line("Product Code: {$this->product->product_code}")
            ->line("Batch No: " . ($this->batch->batch_no ?? '-'))
            ->line("Expiry Date: {$expiryText}")
            ->line("Quantity: " . (int) ($this->batch->quantity ?? 0))
            ->line("Status: {$this->status}");

        if ($this->daysLeft >= 0) {
            $mail->line("Days left: {$this->daysLeft}");
        } else {
            $mail->line("Days overdue: " . abs($this->daysLeft));
        }

        return $mail;
    }

    public function sendTelegramAlert(): void
    {
        try {
            $alertsEnabled = $this->settings?->expiry_alerts_enabled ?? config('inventory.expiry_alerts_enabled');
            $telegramEnabled = $this->settings?->low_stock_telegram_enabled ?? config('inventory.low_stock_telegram_enabled');
            if (! $alertsEnabled || ! $telegramEnabled) {
                return;
            }

            $token = $this->settings?->telegram_bot_token ?: env('TELEGRAM_BOT_TOKEN');
            $chatId = $this->settings?->telegram_chat_id ?: env('TELEGRAM_CHAT_ID');
            if (! $token || ! $chatId) {
                return;
            }

            $expiryText = $this->batch->expiry_date?->format('d/m/Y') ?? '-';
            $qty = (int) ($this->batch->quantity ?? 0);

            $headline = match ($this->status) {
                'EXPIRED' => '🚨 EXPIRED STOCK',
                'EXPIRY_TODAY' => '⚠️ EXPIRY TODAY',
                default => '⏰ NEAR EXPIRY',
            };

            $daysLine = $this->daysLeft >= 0
                ? "Days left: {$this->daysLeft}"
                : "Days overdue: " . abs($this->daysLeft);

            $message = implode("\n", [
                $headline,
                "",
                "Product: {$this->product->product_name}",
                "Code: {$this->product->product_code}",
                "Batch: " . ($this->batch->batch_no ?? '-'),
                "Expiry: {$expiryText}",
                "Qty: {$qty}",
                $daysLine,
                "",
                "Rajawali Retail IMS",
            ]);

            Http::timeout(5)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'disable_web_page_preview' => true,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Expiry Telegram alert failed: ' . $e->getMessage(), [
                'product_id' => $this->product->id ?? null,
                'batch_id' => $this->batch->id ?? null,
            ]);
        }
    }
}
