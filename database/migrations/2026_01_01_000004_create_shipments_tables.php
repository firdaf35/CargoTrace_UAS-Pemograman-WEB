<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('shipment_number', 30)->unique();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('carrier_id')->constrained('carriers')->restrictOnDelete();
            $table->foreignId('origin_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('destination_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cargo_type', 100);
            $table->decimal('weight_kg', 12, 2);
            $table->decimal('volume_cbm', 12, 2)->nullable();
            $table->dateTime('expected_departure');
            $table->dateTime('expected_arrival');
            $table->dateTime('estimated_arrival')->nullable();
            $table->dateTime('actual_arrival')->nullable();
            $table->string('status', 20)->default('CREATED');
            $table->boolean('is_delayed')->default(false);
            $table->decimal('delay_hours', 8, 2)->default(0);
            $table->string('delay_severity', 10)->nullable();
            $table->text('delay_reason')->nullable();
            $table->timestamps();
            $table->softDeletes(); // shipments keep history for audit; never hard-deleted

            $table->index('status');
            $table->index('is_delayed');
            $table->index('expected_arrival');
            $table->index(['carrier_id', 'status']);
        });

        Schema::create('shipment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('weight_kg', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_items');
        Schema::dropIfExists('shipments');
    }
};
