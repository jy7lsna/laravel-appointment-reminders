<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            // Stable iCalendar UID — generated once, never changes.
            $table->uuid('uid')->unique();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone', 40);
            // All datetimes are UTC.
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status', 20)->default('confirmed');
            $table->unsignedInteger('sequence')->default(0);
            $table->dateTime('reminder_24h_sent_at')->nullable();
            $table->dateTime('reminder_2h_sent_at')->nullable();
            $table->dateTime('booked_at');
            // Last time the customer moved the appointment; acts as the
            // "booked at" moment for the 24h-reminder eligibility rule.
            $table->dateTime('rescheduled_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['starts_at', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
