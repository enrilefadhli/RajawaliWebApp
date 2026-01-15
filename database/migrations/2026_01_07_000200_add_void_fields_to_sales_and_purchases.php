<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('status')->default('COMPLETED')->after('total_amount');
            $table->timestamp('voided_at')->nullable()->after('notes');
            $table->foreignId('voided_by')->nullable()->after('voided_at')->constrained('users')->nullOnDelete();
            $table->text('void_reason')->nullable()->after('voided_by');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->string('status')->default('COMPLETED')->after('total_amount');
            $table->timestamp('voided_at')->nullable()->after('notes');
            $table->foreignId('voided_by')->nullable()->after('voided_at')->constrained('users')->nullOnDelete();
            $table->text('void_reason')->nullable()->after('voided_by');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['voided_by']);
            $table->dropColumn(['status', 'voided_at', 'voided_by', 'void_reason']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['voided_by']);
            $table->dropColumn(['status', 'voided_at', 'voided_by', 'void_reason']);
        });
    }
};
