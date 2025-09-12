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
        Schema::create('flashcards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->onDelete('cascade');
            $table->enum('card_type', [
                'basic',
                'multiple_choice',
                'true_false',
                'cloze',
                'typed_answer',
                'image_occlusion',
            ])->default('basic');
            $table->text('question');
            $table->text('answer');
            $table->text('hint')->nullable();

            // Multiple choice specific fields
            $table->json('choices')->nullable();
            $table->json('correct_choices')->nullable();

            // Cloze deletion fields
            $table->text('cloze_text')->nullable();
            $table->json('cloze_answers')->nullable();

            // Image fields
            $table->string('question_image_url')->nullable();
            $table->string('answer_image_url')->nullable();
            $table->json('occlusion_data')->nullable();

            // Metadata
            $table->enum('difficulty_level', ['easy', 'medium', 'hard'])->default('medium');
            $table->json('tags')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('import_source', 50)->nullable();
            $table->timestamps();
            $table->softDeletes(); // Adds deleted_at column for soft deletes

            // Indexes for performance
            $table->index(['unit_id']);
            $table->index(['card_type']);
            $table->index(['difficulty_level']);
            $table->index(['is_active']);
            $table->index(['deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flashcards');
    }
};
