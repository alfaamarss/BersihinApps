<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('garbage_dumps', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('kecamatan_id')->nullable();
            $table->unsignedBigInteger('kelurahan_id')->nullable();
            $table->unsignedBigInteger('rw_id')->nullable();
            $table->text('address');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('capacity_volume', 12, 3)->nullable()->comment('Kapasitas volume dalam m³');
            $table->decimal('capacity_weight', 12, 3)->nullable()->comment('Kapasitas berat dalam kg');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->foreign('kecamatan_id')->references('id')->on('areas')->nullOnDelete();
            $table->foreign('kelurahan_id')->references('id')->on('areas')->nullOnDelete();
            $table->foreign('rw_id')->references('id')->on('rws')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('garbage_dumps');
    }
};
