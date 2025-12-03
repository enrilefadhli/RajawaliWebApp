<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_of_stocks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('batch_no')->nullable();
            $table->date('expiry_date')->nullable();
            $table->integer('quantity')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'batch_no', 'expiry_date'], 'batch_unique_per_product');
            $table->index('product_id');
            $table->index('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_of_stocks');
    }
};
