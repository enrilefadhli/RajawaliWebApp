<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained();
            $table->string('product_code')->unique();
            $table->string('sku')->nullable()->unique();
            $table->string('product_name');
            $table->string('variant')->nullable();
            $table->decimal('purchase_price',15,2)->default(0);
            $table->decimal('selling_price',15,2)->default(0);
            $table->decimal('discount_percent',5,2)->nullable();
            $table->decimal('discount_amount',15,2)->nullable();
            $table->unsignedInteger('minimum_stock');
            $table->timestamps();

            $table->index('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
