<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('monthly_maintenance_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('monthly_maintenance_logs', 'month')) {
                $table->unsignedTinyInteger('month')->nullable()->after('vehicle_id');
            }
            if (! Schema::hasColumn('monthly_maintenance_logs', 'year')) {
                $table->unsignedSmallInteger('year')->nullable()->after('month');
            }
            if (! Schema::hasColumn('monthly_maintenance_logs', 'status')) {
                $table->string('status', 32)->default('pending')->after('log_date');
            }
        });
    }

    public function down()
    {
        Schema::table('monthly_maintenance_logs', function (Blueprint $table) {
            if (Schema::hasColumn('monthly_maintenance_logs', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('monthly_maintenance_logs', 'year')) {
                $table->dropColumn('year');
            }
            if (Schema::hasColumn('monthly_maintenance_logs', 'month')) {
                $table->dropColumn('month');
            }
        });
    }
};