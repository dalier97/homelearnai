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
        Schema::create('catch_up_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('original_session_id')->constrained('learning_sessions')->onDelete('cascade');
            $table->foreignId('child_id')->constrained()->onDelete('cascade');
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->integer('estimated_minutes');
            $table->integer('priority')->default(3); // 1=highest, 5=lowest
            $table->date('missed_date');
            $table->text('reason')->nullable();
            $table->foreignId('reassigned_to_session_id')->nullable()->constrained('learning_sessions')->onDelete('set null');
            $table->enum('status', ['pending', 'reassigned', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();

            // Indexes for performance
            $table->index(['child_id', 'status']);
            $table->index(['original_session_id']);
            $table->index(['priority']);
            $table->index(['missed_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catch_up_sessions');
    }
};
