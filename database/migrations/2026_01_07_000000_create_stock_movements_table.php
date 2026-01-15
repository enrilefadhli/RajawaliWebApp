<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_of_stock_id')->nullable()->constrained('batch_of_stocks')->nullOnDelete();
            $table->string('movement_type');
            $table->string('direction');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('stock_before')->nullable();
            $table->unsignedInteger('stock_after')->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
            $table->index(['movement_type', 'direction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
