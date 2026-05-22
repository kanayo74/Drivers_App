<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('make');               // Toyota
            $table->string('model');              // Hiace
            $table->string('year', 4);
            $table->string('plate_number')->unique();
            $table->enum('type', ['staff_bus', 'marketing_car', 'executive_car', 'other']);
            $table->string('color')->nullable();
            $table->string('vin')->nullable();
            $table->decimal('engine_size', 4, 1); // litres e.g. 2.7
            $table->decimal('tank_capacity', 6, 2); // litres e.g. 50.00
            $table->decimal('fuel_consumption_per_hour', 5, 2)->default(2.20); // litres/hr
            $table->decimal('current_fuel_level', 5, 2)->default(0); // litres
            $table->enum('fuel_status', ['full', 'half', 'quarter', 'critical', 'empty'])->default('full');
            $table->foreignId('assigned_driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['active', 'in_service', 'inactive', 'decommissioned'])->default('active');
            $table->date('last_service_date')->nullable();
            $table->date('next_service_date')->nullable();
            $table->integer('current_mileage')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
