<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waste_pricings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('waste_id');
            $table->unsignedBigInteger('pocket_size_id');
            $table->decimal('price', 12, 2);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->foreign('waste_id')->references('id')->on('wastes')->cascadeOnDelete();
            $table->foreign('pocket_size_id')->references('id')->on('pocket_sizes')->cascadeOnDelete();
            $table->unique(['waste_id', 'pocket_size_id', 'effective_from'], 'uq_waste_price_period');
            $table->unique(['waste_id', 'pocket_size_id'], 'waste_pricings_waste_size_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_pricings');
    }
};
