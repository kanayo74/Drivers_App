<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained();
            $table->foreignId('driver_id')->constrained('users');
            $table->decimal('current_level_litres', 6, 2);
            $table->decimal('current_level_percent', 5, 2);
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'acknowledged', 'fuelled'])->default('pending');
            $table->foreignId('acknowledged_by_id')->nullable()->constrained('users');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();
        });

        Schema::create('fuel_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained();
            $table->foreignId('driver_id')->constrained('users');
            $table->foreignId('fuel_request_id')->nullable()->constrained();
            $table->enum('fill_type', ['full', 'half']); // driver selects
            $table->decimal('litres_added', 6, 2);
            $table->decimal('level_before', 6, 2);  // litres
            $table->decimal('level_after', 6, 2);   // litres
            $table->decimal('estimated_hours_remaining', 6, 2)->nullable(); // auto-calculated
            $table->timestamp('estimated_empty_at')->nullable();           // auto-calculated
            $table->decimal('cost', 10, 2)->nullable();
            $table->timestamps();
        });

        // Fuel depletion snapshots for dashboard charting
        Schema::create('fuel_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained();
            $table->decimal('level_litres', 6, 2);
            $table->decimal('level_percent', 5, 2);
            $table->timestamp('estimated_empty_at')->nullable();
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_snapshots');
        Schema::dropIfExists('fuel_logs');
        Schema::dropIfExists('fuel_requests');
    }
};
