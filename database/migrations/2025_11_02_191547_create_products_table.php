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
            $table->string('name');
            $table->string('sku')->unique();
            $table->text('description')->nullable();
            $table->enum('category', ['food', 'medicine', 'accessories', 'toys', 'grooming', 'other'])->default('other');
            $table->decimal('price', 10, 2);
            $table->decimal('cost', 10, 2)->nullable();
            $table->integer('quantity_in_stock')->default(0);
            $table->integer('reorder_threshold')->default(10);
            $table->string('barcode')->unique()->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('tax_percentage', 5, 2)->nullable();
            $table->decimal('tax_fixed', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('image')->nullable();
            $table->timestamps();
            $table->softDeletes();
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
