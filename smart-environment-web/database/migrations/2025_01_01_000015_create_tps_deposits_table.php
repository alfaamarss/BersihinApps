<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tps_deposits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('collector_id')->comment('Kolektor yang setor');
            $table->unsignedBigInteger('garbage_dump_id')->comment('TPS tujuan');
            $table->dateTime('deposited_at')->comment('Waktu setor');
            $table->decimal('latitude', 10, 8)->nullable()->comment('Koordinat saat setor');
            $table->decimal('longitude', 11, 8)->nullable()->comment('Koordinat saat setor');
            $table->text('notes')->nullable()->comment('Catatan opsional');
            $table->timestamps();

            $table->foreign('collector_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('garbage_dump_id')->references('id')->on('garbage_dumps')->cascadeOnDelete();

            $table->index(['collector_id', 'deposited_at']);
            $table->index(['garbage_dump_id', 'deposited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tps_deposits');
    }
};
