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
        Schema::create('flights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('airline_id')->constrained('airlines')->cascadeOnDelete();
            $table->foreignId('departure_airport_id')->constrained('airports')->cascadeOnDelete();
            $table->foreignId('arrival_airport_id')->constrained('airports')->cascadeOnDelete();
            $table->string('flight_number')->index();
            $table->dateTime('departure_time')->index();
            $table->dateTime('arrival_time')->index();
            $table->unsignedInteger('duration'); // duration in minutes
            $table->decimal('price', 12, 2)->index();
            $table->unsignedInteger('total_seats')->default(100);
            $table->unsignedInteger('available_seats')->default(100);
            $table->enum('status', ['scheduled', 'delayed', 'departed', 'arrived', 'cancelled'])->default('scheduled')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flights');
    }
};
