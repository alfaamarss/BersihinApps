<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waste_deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_account_id');
            $table->unsignedBigInteger('schedule_id')->nullable();
            $table->date('pickup_date')->nullable();
            $table->enum('pickup_type', ['scheduled', 'request'])->default('scheduled');
            $table->enum('request_status', ['sent', 'processing', 'pending', 'completed', 'paid', 'rejected'])->nullable();
            $table->dateTime('requested_at')->nullable();
            $table->date('requested_pickup_date')->nullable();
            $table->time('requested_pickup_time')->nullable();
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->unsignedBigInteger('assigned_collector_id')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->unsignedInteger('bag_count')->default(0);
            $table->decimal('unit_price_snapshot', 12, 2)->nullable();
            $table->decimal('base_amount', 12, 2)->default(0);
            $table->decimal('extra_fee', 12, 2)->default(0);
            $table->enum('status', ['pending', 'confirmed', 'in_progress', 'collected', 'skipped', 'completed', 'cancelled'])->default('pending');
            $table->enum('confirmation_status', ['pending', 'confirmed', 'skipped', 'no_waste'])->default('pending');
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('collected_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->text('resident_note')->nullable();
            $table->text('collector_notes')->nullable();
            $table->string('photo_url')->nullable();
            $table->text('note')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('service_account_id')->references('id')->on('service_accounts')->cascadeOnDelete();
            $table->foreign('schedule_id')->references('id')->on('schedules')->cascadeOnDelete();
            $table->foreign('requested_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('assigned_collector_id')->references('id')->on('users')->nullOnDelete();

            $table->index('pickup_date');
            $table->index('confirmation_status');
            $table->index(['schedule_id', 'pickup_date']);
            $table->index(['pickup_type', 'request_status']);
            $table->index('assigned_collector_id');
            $table->index('requested_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_deliveries');
    }
};
