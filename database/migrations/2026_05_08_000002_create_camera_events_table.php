<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('camera_events', function (Blueprint $table) {
            $table->id();
            $table->string('sensor_key')->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type')->default('apertura')->index();
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['sensor_key', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('camera_events');
    }
};
