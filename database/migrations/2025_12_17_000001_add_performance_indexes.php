<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->index('sale_date');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->index('status');
            $table->index('requested_at');
            $table->index('handled_at');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('purchase_request_approvals', function (Blueprint $table) {
            $table->index('status');
            $table->index('approved_at');
        });

        Schema::table('stock_opnames', function (Blueprint $table) {
            $table->index('opname_date');
        });

        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->index('adjustment_date');
        });

        Schema::table('batch_of_stocks', function (Blueprint $table) {
            $table->index('batch_no');
            $table->index(['product_id', 'expiry_date', 'id'], 'batch_of_stocks_product_expiry_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['sale_date']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['requested_at']);
            $table->dropIndex(['handled_at']);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('purchase_request_approvals', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['approved_at']);
        });

        Schema::table('stock_opnames', function (Blueprint $table) {
            $table->dropIndex(['opname_date']);
        });

        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->dropIndex(['adjustment_date']);
        });

        Schema::table('batch_of_stocks', function (Blueprint $table) {
            $table->dropIndex(['batch_no']);
            $table->dropIndex('batch_of_stocks_product_expiry_id_index');
        });
    }
};

