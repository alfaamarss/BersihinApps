<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('area_id');
            $table->enum('day_of_week', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']);
            $table->time('pickup_time');
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_generate_delivery')->default(true);
            $table->integer('generate_days_ahead')->default(7);
            $table->timestamps();

            $table->foreign('area_id')->references('id')->on('areas')->cascadeOnDelete();
            $table->unique(['area_id', 'day_of_week'], 'schedules_area_day_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
