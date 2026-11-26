<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive migration: existing shipments, users and locations are kept.
 * Existing shipments default to transport_mode = 'sea'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('transport_mode', 10)->default('sea')->after('shipment_number');
            $table->string('vessel_name', 100)->nullable();
            $table->string('container_number', 20)->nullable();
            $table->string('flight_number', 20)->nullable();
            $table->string('airline', 100)->nullable();
            $table->string('vehicle_number', 30)->nullable();
            $table->string('driver_name', 100)->nullable();
            $table->index('transport_mode');
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->string('country_code', 2)->nullable()->after('country');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable()->unique()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
        Schema::table('locations', fn (Blueprint $table) => $table->dropColumn('country_code'));
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex(['transport_mode']);
            $table->dropColumn(['transport_mode', 'vessel_name', 'container_number', 'flight_number', 'airline', 'vehicle_number', 'driver_name']);
        });
    }
};
