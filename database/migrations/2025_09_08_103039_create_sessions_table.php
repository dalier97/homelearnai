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
        Schema::create('learning_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->foreignId('child_id')->constrained()->onDelete('cascade');
            $table->integer('estimated_minutes');
            $table->enum('status', ['backlog', 'planned', 'scheduled', 'done'])->default('backlog');
            $table->integer('scheduled_day_of_week')->nullable(); // 1-7 for Monday-Sunday
            $table->time('scheduled_start_time')->nullable();
            $table->time('scheduled_end_time')->nullable();
            $table->date('scheduled_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['topic_id']);
            $table->index(['child_id']);
            $table->index(['status']);
            $table->index(['scheduled_day_of_week']);
            $table->index(['scheduled_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_sessions');
    }
};
