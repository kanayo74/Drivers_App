<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Maintenance ──────────────────────────────────────────────
        Schema::create('maintenance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained();
            $table->string('service_type'); // Oil change, Full service, etc.
            $table->date('service_date');
            $table->date('next_service_date')->nullable();
            $table->integer('mileage_at_service')->nullable();
            $table->string('provider')->nullable();        // Workshop name
            $table->decimal('cost', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['scheduled', 'completed', 'overdue'])->default('completed');
            $table->foreignId('logged_by_id')->constrained('users');
            $table->timestamps();
        });

        // ─── Training ─────────────────────────────────────────────────
        Schema::create('training_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('users');
            $table->foreignId('assigned_by_id')->constrained('users');
            $table->string('training_type');   // Defensive driving, First aid, etc.
            $table->string('provider')->nullable();
            $table->date('training_date');
            $table->integer('duration_days')->default(1);
            $table->text('notes')->nullable();
            $table->enum('status', ['upcoming', 'in_progress', 'completed', 'cancelled'])->default('upcoming');
            $table->timestamps();
        });

        // ─── Driver Ratings ───────────────────────────────────────────
        Schema::create('driver_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained();
            $table->foreignId('driver_id')->constrained('users');
            $table->foreignId('rated_by_id')->constrained('users');
            $table->unsignedTinyInteger('rating'); // 1-5
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['trip_id', 'rated_by_id']); // one rating per trip per user
        });

        // ─── Payments ─────────────────────────────────────────────────
        Schema::create('driver_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('users');
            $table->foreignId('processed_by_id')->nullable()->constrained('users');
            $table->integer('trips_count');
            $table->decimal('amount_per_trip', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->date('period_start');
            $table->date('period_end');
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ─── Awards ───────────────────────────────────────────────────
        Schema::create('driver_awards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('users');
            $table->enum('award_type', ['driver_of_month', 'driver_of_year']);
            $table->year('year');
            $table->unsignedTinyInteger('month')->nullable(); // null for yearly
            $table->decimal('average_rating', 4, 2);
            $table->integer('total_trips');
            $table->decimal('score', 8, 2); // computed score
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['award_type', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_awards');
        Schema::dropIfExists('driver_payments');
        Schema::dropIfExists('driver_ratings');
        Schema::dropIfExists('training_assignments');
        Schema::dropIfExists('maintenance_records');
    }
};
