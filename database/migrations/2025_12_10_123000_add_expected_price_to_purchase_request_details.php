<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_request_details', function (Blueprint $table) {
            $table->decimal('expected_unit_price', 15, 2)->default(0)->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_request_details', function (Blueprint $table) {
            $table->dropColumn('expected_unit_price');
        });
    }
};
