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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->nullable()->constrained('learning_sessions')->onDelete('set null');
            $table->foreignId('flashcard_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('child_id')->constrained()->onDelete('cascade');
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->integer('interval_days')->default(1);
            $table->decimal('ease_factor', 3, 2)->default(2.5);
            $table->integer('repetitions')->default(0);
            $table->enum('status', ['new', 'learning', 'reviewing', 'mastered'])->default('new');
            $table->date('due_date')->nullable();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['child_id', 'due_date']);
            $table->index(['child_id', 'status']);
            $table->index(['session_id']);
            $table->index(['flashcard_id']);
            $table->index(['topic_id']);
            $table->index(['due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
