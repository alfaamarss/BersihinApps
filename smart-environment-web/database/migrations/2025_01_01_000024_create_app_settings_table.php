<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('app_name');
            $table->string('app_logo_url')->nullable();
            $table->string('app_banner_url')->nullable();
            $table->string('province_id')->nullable();
            $table->string('configured_city_id')->nullable();
            $table->string('configured_city_name')->nullable();
            $table->decimal('off_schedule_surcharge', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
