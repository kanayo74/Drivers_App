<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('monthly_maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete(); 
            $table->dateTime('log_date')->nullable();
            $table->foreignId('done_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('done_at')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('needs_repair')->default(false);
            $table->json('issues')->nullable(); // e.g. ["oil_change","tyre"]
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('monthly_maintenance_logs');
    }
};