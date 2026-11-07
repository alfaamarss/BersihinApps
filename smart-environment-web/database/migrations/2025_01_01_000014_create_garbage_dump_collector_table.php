<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('garbage_dump_collector', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('garbage_dump_id');
            $table->unsignedBigInteger('collector_id');
            $table->timestamps();

            $table->foreign('garbage_dump_id')->references('id')->on('garbage_dumps')->onDelete('cascade');
            $table->foreign('collector_id')->references('id')->on('users')->onDelete('cascade');

            $table->unique(['garbage_dump_id', 'collector_id']);
            $table->unique('collector_id', 'garbage_dump_collector_collector_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('garbage_dump_collector');
    }
};
