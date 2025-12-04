<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('status')->default('DISABLED')->after('minimum_stock');
            $table->dropColumn('is_active');
        });

        DB::table('products')->update(['status' => 'DISABLED']);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('minimum_stock');
            $table->dropColumn('status');
        });
    }
};
