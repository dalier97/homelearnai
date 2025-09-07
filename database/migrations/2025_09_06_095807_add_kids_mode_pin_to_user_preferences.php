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
            $table->string('kids_mode_pin', 255)->nullable()->comment('Bcrypt hash of kids mode PIN');
            $table->string('kids_mode_pin_salt', 255)->nullable()->comment('Additional security salt for PIN');
            $table->integer('kids_mode_pin_attempts')->default(0)->comment('Failed PIN attempts counter');
            $table->timestamp('kids_mode_pin_locked_until')->nullable()->comment('Lockout timestamp after failed attempts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->dropColumn([
                'kids_mode_pin',
                'kids_mode_pin_salt',
                'kids_mode_pin_attempts',
                'kids_mode_pin_locked_until',
            ]);
        });
    }
};
