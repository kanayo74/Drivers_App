<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('license_number')->unique();
            $table->date('license_expiry');
            $table->enum('license_class', ['A', 'B', 'C', 'D', 'E'])->default('B');
            $table->integer('years_experience')->default(0);
            $table->string('guarantor_name')->nullable();
            $table->string('guarantor_phone')->nullable();
            $table->decimal('per_trip_rate', 10, 2)->default(1200.00);
            $table->enum('status', ['available', 'on_trip', 'off_duty', 'training', 'suspended'])->default('available');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_profiles');
    }
};
