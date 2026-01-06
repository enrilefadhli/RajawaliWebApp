<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('system_settings', 'low_stock_threshold_enabled')) {
            Schema::table('system_settings', function (Blueprint $table) {
                $table->boolean('low_stock_threshold_enabled')->default(false)->after('low_stock_telegram_enabled');
            });
        }

        if (! Schema::hasColumn('system_settings', 'low_stock_threshold_percent')) {
            Schema::table('system_settings', function (Blueprint $table) {
                $table->unsignedInteger('low_stock_threshold_percent')->default(100)->after('low_stock_threshold_enabled');
            });
        }

        if (! Schema::hasColumn('system_settings', 'expiry_alerts_enabled')) {
            Schema::table('system_settings', function (Blueprint $table) {
                $table->boolean('expiry_alerts_enabled')->default(false)->after('low_stock_threshold_percent');
            });
        }

        if (! Schema::hasColumn('system_settings', 'expiry_threshold_enabled')) {
            Schema::table('system_settings', function (Blueprint $table) {
                $table->boolean('expiry_threshold_enabled')->default(true)->after('expiry_alerts_enabled');
            });
        }

        if (! Schema::hasColumn('system_settings', 'expiry_threshold_days')) {
            Schema::table('system_settings', function (Blueprint $table) {
                $table->unsignedInteger('expiry_threshold_days')->default(30)->after('expiry_threshold_enabled');
            });
        }

        if (! Schema::hasColumn('system_settings', 'expiry_email_enabled')) {
            Schema::table('system_settings', function (Blueprint $table) {
                $table->boolean('expiry_email_enabled')->default(true)->after('expiry_threshold_days');
            });
        }

        if (! Schema::hasColumn('system_settings', 'expiry_telegram_enabled')) {
            Schema::table('system_settings', function (Blueprint $table) {
                $table->boolean('expiry_telegram_enabled')->default(true)->after('expiry_email_enabled');
            });
        }
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
