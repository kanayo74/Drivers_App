<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQualifiesForPaymentToTripsTable extends Migration
{
    public function up()
    {
        Schema::table('trips', function (Blueprint $table) {
            if (! Schema::hasColumn('trips', 'qualifies_for_payment')) {
                $table->boolean('qualifies_for_payment')->default(true)->after('status');
            }
        });
    }

    public function down()
    {
        Schema::table('trips', function (Blueprint $table) {
            if (Schema::hasColumn('trips', 'qualifies_for_payment')) {
                $table->dropColumn('qualifies_for_payment');
            }
        });
    }
}