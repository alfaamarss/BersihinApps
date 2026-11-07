<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('service_account_id')->nullable();
            $table->unsignedBigInteger('assignee_id')->nullable();
            $table->string('type');
            $table->text('description');
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('resolution_photo')->nullable();
            $table->text('resolution_description')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->enum('status', ['open', 'in_progress', 'pending_confirmation', 'resolved', 'rejected'])->default('open');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('service_account_id')->references('id')->on('service_accounts')->nullOnDelete();
            $table->foreign('assignee_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
