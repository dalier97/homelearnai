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
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->integer('estimated_minutes')->default(30);
            $table->json('prerequisites')->nullable(); // For storing array of prerequisite topic IDs
            $table->boolean('required')->default(true);
            $table->timestamps();

            // Index for performance
            $table->index(['unit_id']);
            $table->index(['required']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};
