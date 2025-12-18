<?php

namespace App\Notifications;

use App\Models\Product;
use App\Models\SystemSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;

class LowStockNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Product $product,
        public int $availableStock,
        public ?SystemSetting $settings = null
    ) {
    }

    public function via(object $notifiable): array
    {
        $settings = $this->settings;
        $emailEnabled = $settings?->low_stock_email_enabled ?? config('inventory.low_stock_email_enabled');

        return $emailEnabled ? ['mail'] : [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $product = $this->product;
        $url = URL::route('filament.admin.resources.products.edit', $product);
        [$thresholdEnabled, $thresholdPercent, $thresholdQty] = $this->getThreshold();
        $minimumStock = (int) ($product->minimum_stock ?? 0);

        $mail = (new MailMessage)
            ->subject('Low Stock Alert: ' . $product->product_name)
            ->line('Product: ' . $product->product_name)
            ->line('Code: ' . $product->product_code)
            ->line('Available stock: ' . $this->availableStock)
            ->line('Minimum stock: ' . $minimumStock);

        if ($thresholdEnabled) {
            $mail->line("Threshold: {$thresholdPercent}% ({$thresholdQty})");
        }

        return $mail
            ->action('Review product', $url)
            ->line('Please restock this item.');
    }

    public function sendTelegramAlert(): void
    {
        $settings = $this->settings;
        $alertsEnabled = $settings?->low_stock_alerts_enabled ?? config('inventory.low_stock_alerts_enabled');
        $telegramEnabled = $settings?->low_stock_telegram_enabled ?? config('inventory.low_stock_telegram_enabled');

        if (! $alertsEnabled || ! $telegramEnabled) {
            return;
        }

        $token = $settings?->telegram_bot_token ?? env('TELEGRAM_BOT_TOKEN');
        $chatId = $settings?->telegram_chat_id ?? env('TELEGRAM_CHAT_ID');

        if (! $token || ! $chatId) {
            return;
        }

        $product = $this->product;
        [$thresholdEnabled, $thresholdPercent, $thresholdQty] = $this->getThreshold();
        $minimumStock = (int) ($product->minimum_stock ?? 0);

        $text = "⚠️ LOW STOCK ALERT\n"
            . "Product: {$product->product_name}\n"
            . "Code: {$product->product_code}\n"
            . "Available: {$this->availableStock}\n"
            . "Minimum: {$minimumStock}\n";

        if ($thresholdEnabled) {
            $text .= "Threshold: {$thresholdPercent}% ({$thresholdQty})\n";
        }

        $text .= "Time: " . now()->toDateTimeString();

        try {
            Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
            ]);
        } catch (\Throwable $e) {
            // Never block other channels.
        }
    }

    /**
     * @return array{0:bool,1:int,2:int} [thresholdEnabled, thresholdPercent, thresholdQty]
     */
    protected function getThreshold(): array
    {
        $settings = $this->settings;
        $thresholdPercent = (int) ($settings?->low_stock_threshold_percent ?? config('inventory.low_stock_threshold_percent'));
        $thresholdPercent = max(1, min(500, $thresholdPercent));
        $thresholdEnabled = (bool) ($settings?->low_stock_threshold_enabled ?? config('inventory.low_stock_threshold_enabled'));

        $minimumStock = (int) ($this->product->minimum_stock ?? 0);
        $thresholdQty = (int) floor($minimumStock * ($thresholdPercent / 100));

        return [$thresholdEnabled, $thresholdPercent, $thresholdQty];
    }
}
