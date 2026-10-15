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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->enum('discount_type', ['percentage', 'fixed']);
            $table->decimal('discount_value', 12, 2);
            $table->decimal('minimum_transaction', 12, 2)->default(0);
            $table->decimal('maximum_discount', 12, 2)->nullable();
            $table->unsignedInteger('quota')->default(100);
            $table->unsignedInteger('used_quota')->default(0);
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
