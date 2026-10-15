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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->string('room_type');
            $table->text('description')->nullable();
            $table->unsignedInteger('capacity')->default(2);
            $table->decimal('price_per_night', 12, 2)->index();
            $table->unsignedInteger('total_rooms')->default(1);
            $table->unsignedInteger('available_rooms')->default(1);
            $table->json('facilities')->nullable();
            $table->string('image')->nullable();
            $table->enum('status', ['available', 'unavailable', 'maintenance'])->default('available')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
