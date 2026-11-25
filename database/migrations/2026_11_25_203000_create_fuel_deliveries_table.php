<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_deliveries', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('station_id');
            $table->foreignId('fuel_type_id')->constrained()->cascadeOnDelete();
            $table->string('vehicle_name');
            $table->string('vehicle_plate');
            $table->string('driver_name');
            $table->string('origin_depot')->default('TBBM Plumpang');
            $table->decimal('origin_lat', 10, 6);
            $table->decimal('origin_lng', 10, 6);
            $table->decimal('current_lat', 10, 6);
            $table->decimal('current_lng', 10, 6);
            $table->unsignedInteger('quantity_liters');
            $table->string('status')->default('Dijadwalkan');
            $table->unsignedInteger('eta_minutes')->default(0);
            $table->timestamp('departed_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('station_id')->references('id')->on('stations')->cascadeOnDelete();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_deliveries');
    }
};
