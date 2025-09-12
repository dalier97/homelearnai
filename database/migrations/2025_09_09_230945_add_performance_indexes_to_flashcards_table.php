<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('flashcards', function (Blueprint $table) {
            // Composite indexes for optimized queries
            $table->index(['unit_id', 'is_active', 'created_at'], 'flashcards_unit_active_created_idx');
            $table->index(['unit_id', 'card_type', 'is_active'], 'flashcards_unit_type_active_idx');
            $table->index(['unit_id', 'difficulty_level', 'is_active'], 'flashcards_unit_difficulty_active_idx');
            $table->index(['is_active', 'created_at'], 'flashcards_active_created_idx');
            $table->index(['import_source'], 'flashcards_import_source_idx');
        });

        // Full-text search support (for PostgreSQL) - without CONCURRENTLY
        if (config('database.default') === 'pgsql') {
            // Use raw SQL for PostgreSQL full-text search
            DB::statement('CREATE INDEX IF NOT EXISTS flashcards_question_fulltext_idx 
                          ON flashcards USING gin(to_tsvector(\'english\', question))');
            DB::statement('CREATE INDEX IF NOT EXISTS flashcards_answer_fulltext_idx 
                          ON flashcards USING gin(to_tsvector(\'english\', answer))');
            DB::statement('CREATE INDEX IF NOT EXISTS flashcards_hint_fulltext_idx 
                          ON flashcards USING gin(to_tsvector(\'english\', hint))');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flashcards', function (Blueprint $table) {
            // Drop composite indexes
            $table->dropIndex('flashcards_unit_active_created_idx');
            $table->dropIndex('flashcards_unit_type_active_idx');
            $table->dropIndex('flashcards_unit_difficulty_active_idx');
            $table->dropIndex('flashcards_active_created_idx');
            $table->dropIndex('flashcards_import_source_idx');
        });

        // Drop full-text indexes for PostgreSQL
        if (config('database.default') === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS flashcards_question_fulltext_idx');
            DB::statement('DROP INDEX IF EXISTS flashcards_answer_fulltext_idx');
            DB::statement('DROP INDEX IF EXISTS flashcards_hint_fulltext_idx');
        }
    }
};
