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
        Schema::create('pets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Changed from owner_id
            $table->string('name');
            $table->string('species'); // dog, cat, bird, etc.
            $table->string('breed')->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female', 'unknown'])->default('unknown');
            $table->string('color')->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->string('microchip_id')->unique()->nullable();
            $table->text('allergies')->nullable();
            $table->text('notes')->nullable();
            $table->string('photo')->nullable();
            $table->json('tags')->nullable(); // ['senior', 'diabetic', 'aggressive']
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pets');
    }
};
