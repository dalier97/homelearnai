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
        Schema::table('learning_sessions', function (Blueprint $table) {
            $table->enum('commitment_type', ['fixed', 'preferred', 'flexible'])->default('preferred')->after('status');
            $table->date('skipped_from_date')->nullable()->after('scheduled_date');

            // Evidence capture fields
            $table->text('evidence_notes')->nullable()->after('notes');
            $table->json('evidence_photos')->nullable()->after('evidence_notes');
            $table->string('evidence_voice_memo')->nullable()->after('evidence_photos');
            $table->json('evidence_attachments')->nullable()->after('evidence_voice_memo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learning_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'commitment_type',
                'skipped_from_date',
                'evidence_notes',
                'evidence_photos',
                'evidence_voice_memo',
                'evidence_attachments',
            ]);
        });
    }
};
