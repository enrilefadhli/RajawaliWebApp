<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->boolean('low_stock_threshold_enabled')->default(false)->after('low_stock_telegram_enabled');
            $table->unsignedInteger('low_stock_threshold_percent')->default(100)->after('low_stock_threshold_enabled');
            $table->boolean('expiry_alerts_enabled')->default(false)->after('low_stock_threshold_percent');
            $table->boolean('expiry_threshold_enabled')->default(true)->after('expiry_alerts_enabled');
            $table->unsignedInteger('expiry_threshold_days')->default(30)->after('expiry_threshold_enabled');
            $table->boolean('expiry_email_enabled')->default(true)->after('expiry_threshold_days');
            $table->boolean('expiry_telegram_enabled')->default(true)->after('expiry_email_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn([
                'low_stock_threshold_enabled',
                'low_stock_threshold_percent',
                'expiry_alerts_enabled',
                'expiry_threshold_enabled',
                'expiry_threshold_days',
                'expiry_email_enabled',
                'expiry_telegram_enabled',
            ]);
        });
    }
};
