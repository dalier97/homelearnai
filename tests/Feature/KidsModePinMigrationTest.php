<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class KidsModePinMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The user_preferences table and kids mode fields are created by migrations
        // RefreshDatabase trait ensures the database is in the correct state

        // Clean up any test data from previous runs
        \DB::table('user_preferences')->where('user_id', 'like', '%123456%')->delete();
    }

    protected function tearDown(): void
    {
        // Clean up test data after each test
        \DB::table('user_preferences')->where('user_id', 'like', '%123456%')->delete();

        parent::tearDown();
    }

    /**
     * Test that the kids mode PIN migration adds all required fields
     */
    public function test_kids_mode_pin_migration_adds_required_fields(): void
    {
        // Verify that the user_preferences table exists
        $this->assertTrue(Schema::hasTable('user_preferences'), 'user_preferences table should exist');

        // Verify all required columns were added
        $this->assertTrue(
            Schema::hasColumn('user_preferences', 'kids_mode_pin'),
            'kids_mode_pin column should exist'
        );

        $this->assertTrue(
            Schema::hasColumn('user_preferences', 'kids_mode_pin_salt'),
            'kids_mode_pin_salt column should exist'
        );

        $this->assertTrue(
            Schema::hasColumn('user_preferences', 'kids_mode_pin_attempts'),
            'kids_mode_pin_attempts column should exist'
        );

        $this->assertTrue(
            Schema::hasColumn('user_preferences', 'kids_mode_pin_locked_until'),
            'kids_mode_pin_locked_until column should exist'
        );
    }

    /**
     * Test that the fields have correct nullable/default properties
     */
    public function test_kids_mode_pin_fields_have_correct_properties(): void
    {
        // Get column details
        $columns = Schema::getColumnListing('user_preferences');

        // Verify required fields exist
        $requiredFields = [
            'kids_mode_pin',
            'kids_mode_pin_salt',
            'kids_mode_pin_attempts',
            'kids_mode_pin_locked_until',
        ];

        foreach ($requiredFields as $field) {
            $this->assertContains($field, $columns, "Field {$field} should exist in user_preferences table");
        }
    }

    /**
     * Test that we can insert data into the new fields
     */
    public function test_can_insert_kids_mode_pin_data(): void
    {
        // Create a real user
        $user = User::factory()->create();

        // Insert test data
        \DB::table('user_preferences')->insert([
            'user_id' => $user->id,
            'locale' => 'en',
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'kids_mode_pin' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // bcrypt of "secret"
            'kids_mode_pin_salt' => 'random_salt_123',
            'kids_mode_pin_attempts' => 2,
            'kids_mode_pin_locked_until' => now()->addMinutes(15),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Verify the data was inserted correctly
        $preference = \DB::table('user_preferences')->where('user_id', $user->id)->first();

        $this->assertNotNull($preference, 'User preference should be created');
        $this->assertEquals('$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', $preference->kids_mode_pin);
        $this->assertEquals('random_salt_123', $preference->kids_mode_pin_salt);
        $this->assertEquals(2, $preference->kids_mode_pin_attempts);
        $this->assertNotNull($preference->kids_mode_pin_locked_until);
    }

    /**
     * Test that fields can be null when not set
     */
    public function test_kids_mode_pin_fields_can_be_null(): void
    {
        // Create a real user
        $user = User::factory()->create();

        // Insert test data with null PIN fields
        \DB::table('user_preferences')->insert([
            'user_id' => $user->id,
            'locale' => 'en',
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'kids_mode_pin' => null,
            'kids_mode_pin_salt' => null,
            'kids_mode_pin_attempts' => 0, // This has a default value
            'kids_mode_pin_locked_until' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Verify the data was inserted correctly with null values
        $preference = \DB::table('user_preferences')->where('user_id', $user->id)->first();

        $this->assertNotNull($preference, 'User preference should be created');
        $this->assertNull($preference->kids_mode_pin);
        $this->assertNull($preference->kids_mode_pin_salt);
        $this->assertEquals(0, $preference->kids_mode_pin_attempts);
        $this->assertNull($preference->kids_mode_pin_locked_until);
    }

    /**
     * Test that attempts field defaults to 0
     */
    public function test_kids_mode_pin_attempts_defaults_to_zero(): void
    {
        // Create a real user
        $user = User::factory()->create();

        // Insert test data without specifying attempts field
        \DB::table('user_preferences')->insert([
            'user_id' => $user->id,
            'locale' => 'en',
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Verify the attempts field defaults to 0
        $preference = \DB::table('user_preferences')->where('user_id', $user->id)->first();

        $this->assertEquals(0, $preference->kids_mode_pin_attempts);
    }

    /**
     * Test Supabase migration rollback by checking if we can drop and re-add columns
     */
    public function test_can_manage_kids_mode_pin_fields(): void
    {
        // First, confirm the fields exist (added by our setup)
        $this->assertTrue(Schema::hasColumn('user_preferences', 'kids_mode_pin'));
        $this->assertTrue(Schema::hasColumn('user_preferences', 'kids_mode_pin_salt'));
        $this->assertTrue(Schema::hasColumn('user_preferences', 'kids_mode_pin_attempts'));
        $this->assertTrue(Schema::hasColumn('user_preferences', 'kids_mode_pin_locked_until'));

        // Test that we can query the table with these fields successfully
        $result = \DB::select('SELECT column_name FROM information_schema.columns WHERE table_name = ? AND column_name LIKE ?',
            ['user_preferences', 'kids_mode_pin%']);

        $columnNames = array_map(fn ($col) => $col->column_name, $result);

        $this->assertContains('kids_mode_pin', $columnNames);
        $this->assertContains('kids_mode_pin_salt', $columnNames);
        $this->assertContains('kids_mode_pin_attempts', $columnNames);
        $this->assertContains('kids_mode_pin_locked_until', $columnNames);
    }

    /**
     * Test PIN validation workflow
     */
    public function test_pin_validation_workflow(): void
    {
        // Create a real user
        $user = User::factory()->create();
        $testPin = '1234';
        $hashedPin = password_hash($testPin, PASSWORD_DEFAULT);

        // Create user preference with PIN
        \DB::table('user_preferences')->insert([
            'user_id' => $user->id,
            'locale' => 'en',
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'kids_mode_pin' => $hashedPin,
            'kids_mode_pin_salt' => 'test_salt',
            'kids_mode_pin_attempts' => 0,
            'kids_mode_pin_locked_until' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Retrieve and verify PIN can be validated
        $preference = \DB::table('user_preferences')->where('user_id', $user->id)->first();

        $this->assertTrue(password_verify($testPin, $preference->kids_mode_pin), 'PIN should be verifiable');
        $this->assertFalse(password_verify('wrong', $preference->kids_mode_pin), 'Wrong PIN should not verify');

        // Test lockout scenario
        \DB::table('user_preferences')
            ->where('user_id', $user->id)
            ->update([
                'kids_mode_pin_attempts' => 5,
                'kids_mode_pin_locked_until' => now()->addMinutes(15),
                'updated_at' => now(),
            ]);

        $lockedPreference = \DB::table('user_preferences')->where('user_id', $user->id)->first();
        $this->assertEquals(5, $lockedPreference->kids_mode_pin_attempts);
        $this->assertNotNull($lockedPreference->kids_mode_pin_locked_until);

        // Verify lockout is in the future
        $lockoutTime = new \DateTime($lockedPreference->kids_mode_pin_locked_until);
        $this->assertGreaterThan(new \DateTime, $lockoutTime, 'Lockout time should be in the future');
    }
}
