<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_photos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('complaint_id');
            $table->string('photo_url');
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->foreign('complaint_id')->references('id')->on('complaints')->cascadeOnDelete();
            $table->index(['complaint_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_photos');
    }
};
