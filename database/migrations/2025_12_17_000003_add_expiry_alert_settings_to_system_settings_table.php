<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->boolean('expiry_alerts_enabled')->default(false)->after('low_stock_threshold_enabled');
            $table->unsignedInteger('expiry_threshold_days')->default(30)->after('expiry_alerts_enabled');
            $table->boolean('expiry_email_enabled')->default(true)->after('expiry_threshold_days');
            $table->boolean('expiry_telegram_enabled')->default(true)->after('expiry_email_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn('expiry_telegram_enabled');
            $table->dropColumn('expiry_email_enabled');
            $table->dropColumn('expiry_threshold_days');
            $table->dropColumn('expiry_alerts_enabled');
        });
    }
};

