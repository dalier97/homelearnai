<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('time_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained()->onDelete('cascade');
            $table->integer('day_of_week'); // 1-7 for Monday-Sunday
            $table->time('start_time');
            $table->time('end_time');
            $table->string('label');
            $table->timestamps();

            // Indexes for performance
            $table->index(['child_id']);
            $table->index(['day_of_week']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_blocks');
    }
};
