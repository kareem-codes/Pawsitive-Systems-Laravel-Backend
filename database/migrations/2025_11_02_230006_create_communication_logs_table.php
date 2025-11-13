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
        Schema::create('communication_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Owner/customer
            $table->foreignId('staff_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('type', ['call', 'email', 'whatsapp', 'sms', 'visit', 'other'])->default('call');
            $table->enum('direction', ['inbound', 'outbound'])->default('outbound');
            $table->string('subject')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('contacted_at');
            $table->integer('duration_minutes')->nullable(); // For calls
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communication_logs');
    }
};
