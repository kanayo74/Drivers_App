<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->string('trip_code')->unique(); // TR-0001
            $table->foreignId('booked_by_id')->constrained('users');     // who booked
            $table->foreignId('passenger_id')->constrained('users');     // who is travelling
            $table->foreignId('driver_id')->constrained('users');
            $table->foreignId('vehicle_id')->constrained('vehicles');
            $table->text('reason');                                       // mandatory reason
            $table->string('destination')->nullable();
            $table->string('pickup_location')->nullable();
            $table->dateTime('scheduled_at');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->enum('status', [
                'pending',     // awaiting admin approval
                'approved',    // admin approved, driver notified
                'rejected',    // admin rejected
                'in_progress', // trip started
                'completed',   // trip done
                'cancelled'    // cancelled
            ])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('approved_by_id')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->boolean('payment_processed')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        // Staff bus stop-by-stop route logging
        Schema::create('trip_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->string('location_name');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->integer('sequence')->default(1); // stop order
            $table->enum('direction', ['morning', 'evening']); // pickup vs dropoff
            $table->timestamp('arrived_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_locations');
        Schema::dropIfExists('trips');
    }
};
