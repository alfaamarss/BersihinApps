<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waste_delivery_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('waste_delivery_id');
            $table->unsignedBigInteger('waste_id');
            $table->unsignedBigInteger('pocket_size_id');
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 12, 2);
            $table->timestamps();

            $table->foreign('waste_delivery_id')->references('id')->on('waste_deliveries')->cascadeOnDelete();
            $table->foreign('waste_id')->references('id')->on('wastes')->cascadeOnDelete();
            $table->foreign('pocket_size_id')->references('id')->on('pocket_sizes')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_delivery_items');
    }
};
