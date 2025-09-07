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
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->string('user_id'); // Supabase UUID stored as string
            $table->string('locale', 5)->default('en');
            $table->string('timezone', 50)->default('UTC');
            $table->string('date_format', 20)->default('Y-m-d');
            $table->timestamps();

            // Add unique constraint on user_id
            $table->unique('user_id');

            // Add index for better performance
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
