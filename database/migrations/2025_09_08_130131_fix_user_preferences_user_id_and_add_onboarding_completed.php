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
        Schema::table('user_preferences', function (Blueprint $table) {
            // Drop the unique constraint first
            $table->dropUnique(['user_id']);

            // Change user_id from string to foreignId
            $table->dropColumn('user_id');
            $table->foreignId('user_id')->after('id')->constrained()->onDelete('cascade');
            $table->unique('user_id');

            // Add onboarding_completed column
            $table->boolean('onboarding_completed')->default(false)->after('date_format');
            $table->boolean('onboarding_skipped')->default(false)->after('onboarding_completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            // Remove the new columns
            $table->dropColumn(['onboarding_completed', 'onboarding_skipped']);

            // Revert user_id back to string
            $table->dropUnique(['user_id']);
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->string('user_id')->after('id');
            $table->unique('user_id');
        });
    }
};
