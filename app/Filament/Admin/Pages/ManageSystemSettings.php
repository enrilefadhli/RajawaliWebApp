<?php

namespace App\Filament\Admin\Pages;

use App\Models\SystemSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class ManageSystemSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'System Settings';
    protected static ?string $title = 'System Settings';
    protected static ?int $navigationSort = 90;
    protected static string $view = 'filament.admin.pages.manage-system-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = SystemSetting::first();
        $this->form->fill($settings?->toArray() ?? []);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Low Stock Alerts')
                    ->schema([
                        Forms\Components\Toggle::make('low_stock_alerts_enabled')
                            ->label('Enable low stock alerts')
                            ->default(false)
                            ->columnSpanFull()
                            ->live(debounce: 500),
                        Forms\Components\Toggle::make('low_stock_threshold_enabled')
                            ->label('Enable stock threshold alerts')
                            ->default(false)
                            ->disabled(fn (Get $get) => ! $get('low_stock_alerts_enabled'))
                            ->columnSpanFull()
                            ->live(debounce: 500),
                        Forms\Components\TextInput::make('low_stock_threshold_percent')
                            ->label('Stock threshold (%)')
                            ->helperText('Alert triggers when stock is <= floor(min_stock × %). Use > 100 to alert earlier (before hitting min stock).')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(500)
                            ->suffix('%')
                            ->default(100)
                            ->live(onBlur: true)
                            ->dehydrateStateUsing(fn ($state) => (int) floor((float) $state))
                            ->disabled(fn (Get $get) => ! $get('low_stock_alerts_enabled') || ! $get('low_stock_threshold_enabled')),
                    ])
                    ->columns(1),
                Forms\Components\Section::make('Expiry Alerts')
                    ->schema([
                        Forms\Components\Toggle::make('expiry_alerts_enabled')
                            ->label('Enable expiry alerts')
                            ->default(false)
                            ->columnSpanFull()
                            ->live(debounce: 500),
                        Forms\Components\Toggle::make('expiry_threshold_enabled')
                            ->label('Enable near expiry threshold alerts')
                            ->default(true)
                            ->disabled(fn (Get $get) => ! $get('expiry_alerts_enabled'))
                            ->columnSpanFull()
                            ->live(debounce: 500),
                        Forms\Components\TextInput::make('expiry_threshold_days')
                            ->label('Near expiry threshold (days)')
                            ->helperText('Batches expiring within this many days will be classified as NEAR EXPIRY. Expiry day and already expired batches can also trigger alerts.')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(365)
                            ->suffix('days')
                            ->default(30)
                            ->live(onBlur: true)
                            ->dehydrateStateUsing(fn ($state) => (int) floor((float) $state))
                            ->disabled(fn (Get $get) => ! $get('expiry_alerts_enabled') || ! $get('expiry_threshold_enabled')),
                    ])
                    ->columns(1),
                Forms\Components\Section::make('Telegram Alerts')
                    ->schema([
                        Forms\Components\Toggle::make('low_stock_telegram_enabled')
                            ->label('Enable Telegram notifications')
                            ->default(true)
                            ->disabled(fn (Get $get) => ! $get('low_stock_alerts_enabled') && ! $get('expiry_alerts_enabled'))
                            ->columnSpanFull()
                            ->live(debounce: 500),
                        Forms\Components\TextInput::make('telegram_bot_token')
                            ->label('Telegram Bot Token')
                            ->password()
                            ->revealable()
                            ->helperText('Required if any Telegram notifications are enabled.')
                            ->live(onBlur: true)
                            ->disabled(fn (Get $get) => ! self::isAnyTelegramEnabled($get)),
                        Forms\Components\TextInput::make('telegram_chat_id')
                            ->label('Telegram Chat ID')
                            ->helperText('Chat or channel ID for the bot to send messages to.')
                            ->live(onBlur: true)
                            ->disabled(fn (Get $get) => ! self::isAnyTelegramEnabled($get)),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Email Alerts (SMTP override)')
                    ->schema([
                        Forms\Components\Toggle::make('low_stock_email_enabled')
                            ->label('Enable email notifications')
                            ->default(true)
                            ->disabled(fn (Get $get) => ! $get('low_stock_alerts_enabled') && ! $get('expiry_alerts_enabled'))
                            ->columnSpanFull()
                            ->live(debounce: 500),
                        Forms\Components\TextInput::make('mail_username')
                            ->label('SMTP Username')
                            ->helperText('Optional override for mail username. Leave blank to use .env')
                            ->live(onBlur: true)
                            ->disabled(fn (Get $get) => ! self::isAnyEmailEnabled($get)),
                        Forms\Components\TextInput::make('mail_password')
                            ->label('SMTP Password')
                            ->password()
                            ->revealable()
                            ->helperText('Optional override for mail password. Leave blank to use .env')
                            ->live(onBlur: true)
                            ->disabled(fn (Get $get) => ! self::isAnyEmailEnabled($get)),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    protected static function isAnyTelegramEnabled(Get $get): bool
    {
        return (bool) (($get('low_stock_alerts_enabled') || $get('expiry_alerts_enabled')) && $get('low_stock_telegram_enabled'));
    }

    protected static function isAnyEmailEnabled(Get $get): bool
    {
        return (bool) (($get('low_stock_alerts_enabled') || $get('expiry_alerts_enabled')) && $get('low_stock_email_enabled'));
    }

    public function save(): void
    {
        $settings = SystemSetting::firstOrNew([]);
        $settings->fill($this->data ?? []);
        $settings->save();

        Notification::make()
            ->title('Settings saved')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('save')
                ->label('Save')
                ->submit('save'),
        ];
    }
}
