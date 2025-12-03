<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('supplier_phone')->nullable()->change();
            $table->string('supplier_address')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('supplier_phone')->nullable(false)->change();
            $table->string('supplier_address')->nullable(false)->change();
        });
    }
};
