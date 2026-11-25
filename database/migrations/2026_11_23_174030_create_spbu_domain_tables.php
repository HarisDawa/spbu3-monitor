<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stations', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('address');
            $table->string('region');
            $table->decimal('latitude', 10, 6);
            $table->decimal('longitude', 10, 6);
            $table->string('phone')->nullable();
            $table->string('opening_hours')->nullable();
            $table->string('operational_status')->default('Buka');
            $table->text('facility')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('fuel_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('ron')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('price_per_liter');
            $table->boolean('is_active')->default(true);
            $table->string('color')->nullable();
            $table->timestamps();
        });

        Schema::create('fuel_stocks', function (Blueprint $table) {
            $table->id();
            $table->string('station_id');
            $table->foreignId('fuel_type_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('stock_liters')->default(0);
            $table->unsignedInteger('capacity_liters');
            $table->unsignedInteger('low_stock_threshold')->default(0);
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('station_id')->references('id')->on('stations')->cascadeOnDelete();
            $table->unique(['station_id', 'fuel_type_id']);
        });

        Schema::create('stock_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fuel_stock_id')->constrained()->cascadeOnDelete();
            $table->string('station_id');
            $table->string('fuel_name');
            $table->integer('previous_stock');
            $table->integer('new_stock');
            $table->integer('change_amount');
            $table->text('note')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('station_id')->references('id')->on('stations')->cascadeOnDelete();
            $table->index(['station_id', 'created_at']);
        });

        Schema::create('queue_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('station_id')->unique();
            $table->string('vehicle_category')->default('Semua Kendaraan');
            $table->unsignedInteger('estimated_wait_minutes')->default(0);
            $table->string('queue_level')->default('Lancar');
            $table->string('source')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('station_id')->references('id')->on('stations')->cascadeOnDelete();
        });

        Schema::create('citizen_reports', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('station_id');
            $table->string('category');
            $table->text('description');
            $table->text('photo_url')->nullable();
            $table->string('status')->default('Menunggu');
            $table->text('admin_note')->nullable();
            $table->string('handled_by')->nullable();
            $table->timestamps();

            $table->foreign('station_id')->references('id')->on('stations')->cascadeOnDelete();
            $table->index(['status', 'created_at']);
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('user_name');
            $table->string('user_role');
            $table->string('action');
            $table->string('entity_type');
            $table->string('entity_id')->nullable();
            $table->text('description');
            $table->timestamps();

            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('citizen_reports');
        Schema::dropIfExists('queue_statuses');
        Schema::dropIfExists('stock_histories');
        Schema::dropIfExists('fuel_stocks');
        Schema::dropIfExists('fuel_types');
        Schema::dropIfExists('stations');
    }
};
