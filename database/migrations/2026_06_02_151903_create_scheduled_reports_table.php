<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->id();

            $table->string('name');

            $table->json('camera_ids');

            $table->enum('frequency', [
                'weekly',
                'biweekly',
                'monthly'
            ]);

            $table->boolean('send_pdf')->default(true);
            $table->boolean('send_excel')->default(false);

            $table->json('recipients');

            $table->boolean('include_ai_summary')->default(false);

            $table->boolean('active')->default(true);

            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('next_run_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_reports');
    }
};