<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rws', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kelurahan_id');
            $table->string('name');
            $table->timestamps();

            $table->foreign('kelurahan_id')->references('id')->on('areas')->cascadeOnDelete();
            $table->unique(['kelurahan_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rws');
    }
};
