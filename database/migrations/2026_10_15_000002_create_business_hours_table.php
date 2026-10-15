<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_hours', function (Blueprint $table) {
            $table->id();
            // 0 = Sunday … 6 = Saturday (Carbon::dayOfWeek).
            $table->unsignedTinyInteger('weekday')->unique();
            // Local wall-clock times ("09:00") in BUSINESS_TIMEZONE.
            $table->string('opens_at', 5)->nullable();
            $table->string('closes_at', 5)->nullable();
            $table->boolean('is_closed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_hours');
    }
};
