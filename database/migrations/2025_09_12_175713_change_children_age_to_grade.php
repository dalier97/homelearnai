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
        Schema::table('children', function (Blueprint $table) {
            // Drop the old age index
            $table->dropIndex(['user_id', 'age']);

            // Change age column to grade (string)
            $table->string('grade')->after('name')->nullable();

            // Add new grade index
            $table->index(['user_id', 'grade']);
        });

        // Convert existing age data to approximate grades
        // This is a one-time conversion, users will need to update manually for accuracy
        $children = \DB::table('children')->get();
        foreach ($children as $child) {
            $grade = $this->ageToGrade($child->age);
            \DB::table('children')->where('id', $child->id)->update(['grade' => $grade]);
        }

        // Remove the age column after data conversion
        Schema::table('children', function (Blueprint $table) {
            $table->dropColumn('age');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('children', function (Blueprint $table) {
            // Add age column back
            $table->integer('age')->after('name')->nullable();

            // Drop grade index
            $table->dropIndex(['user_id', 'grade']);

            // Add age index back
            $table->index(['user_id', 'age']);
        });

        // Convert grades back to approximate ages
        $children = \DB::table('children')->get();
        foreach ($children as $child) {
            $age = $this->gradeToAge($child->grade);
            \DB::table('children')->where('id', $child->id)->update(['age' => $age]);
        }

        // Remove grade column
        Schema::table('children', function (Blueprint $table) {
            $table->dropColumn('grade');
        });
    }

    /**
     * Convert age to approximate grade
     */
    private function ageToGrade(int $age): string
    {
        return match (true) {
            $age <= 4 => 'PreK',
            $age === 5 => 'K',
            $age === 6 => '1st',
            $age === 7 => '2nd',
            $age === 8 => '3rd',
            $age === 9 => '4th',
            $age === 10 => '5th',
            $age === 11 => '6th',
            $age === 12 => '7th',
            $age === 13 => '8th',
            $age === 14 => '9th',
            $age === 15 => '10th',
            $age === 16 => '11th',
            $age >= 17 => '12th',
            default => 'K'
        };
    }

    /**
     * Convert grade back to approximate age
     */
    private function gradeToAge(string $grade): int
    {
        return match ($grade) {
            'PreK' => 4,
            'K' => 5,
            '1st' => 6,
            '2nd' => 7,
            '3rd' => 8,
            '4th' => 9,
            '5th' => 10,
            '6th' => 11,
            '7th' => 12,
            '8th' => 13,
            '9th' => 14,
            '10th' => 15,
            '11th' => 16,
            '12th' => 17,
            default => 5
        };
    }
};
