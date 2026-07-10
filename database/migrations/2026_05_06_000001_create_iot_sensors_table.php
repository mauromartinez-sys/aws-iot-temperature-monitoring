<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iot_sensors', function (Blueprint $table) {
            $table->id();
            $table->string('sensor_key')->unique(); // Ej: camara_1, camara_2, camara_3. No debería cambiarse.
            $table->string('display_name'); // Nombre visible para dashboard/reportes.
            $table->string('location')->nullable();
            $table->string('camera_type')->nullable();
            $table->text('description')->nullable();
            $table->decimal('alarm_min', 8, 2)->nullable();
            $table->decimal('alarm_max', 8, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iot_sensors');
    }
};
