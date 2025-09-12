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
        Schema::create('flashcard_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('import_type'); // 'anki', 'mnemosyne', 'csv', etc.
            $table->string('filename');
            $table->string('status')->default('pending'); // pending, processing, completed, failed, rolled_back
            $table->integer('total_cards')->default(0);
            $table->integer('imported_cards')->default(0);
            $table->integer('failed_cards')->default(0);
            $table->integer('duplicate_cards')->default(0);
            $table->integer('media_files')->default(0);
            $table->json('import_options')->nullable(); // duplicate strategy, etc.
            $table->json('import_metadata')->nullable(); // deck info, note types, etc.
            $table->json('import_results')->nullable(); // detailed results, errors, etc.
            $table->json('rollback_data')->nullable(); // data needed for rollback
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['unit_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flashcard_imports');
    }
};
