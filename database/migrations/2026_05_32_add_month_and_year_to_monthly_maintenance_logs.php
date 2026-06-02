<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_maintenance_logs', function (Blueprint $table) {
            $table->unsignedTinyInteger('month')->after('vehicle_id');
            $table->unsignedSmallInteger('year')->after('month');

            $table->unique(['vehicle_id', 'month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::table('monthly_maintenance_logs', function (Blueprint $table) {
            $table->dropUnique(['vehicle_id', 'month', 'year']);
            $table->dropColumn(['month', 'year']);
        });
    }
};