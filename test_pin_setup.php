<?php

require_once 'bootstrap/app.php';

// Set up testing environment
$app = app();
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Set testing configuration
config(['app.env' => 'testing']);
config(['database.default' => 'pgsql']);
config(['database.connections.pgsql.host' => '127.0.0.1']);
config(['database.connections.pgsql.port' => 54322]);
config(['database.connections.pgsql.database' => 'postgres']);
config(['database.connections.pgsql.username' => 'postgres']);
config(['database.connections.pgsql.password' => 'postgres']);

use App\Services\SupabaseClient;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

echo "=== PIN Setup Test ===\n";

// Create Supabase client
$supabaseClient = new SupabaseClient(
    'http://localhost:54321',
    'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZS1kZW1vIiwicm9sZSI6ImFub24iLCJleHAiOjE5ODM4MTI5OTZ9.CRXP1A7WOeoJeXxjNni43kdQwgnWNReilDMblYTn_I0',
    'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZS1kZW1vIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImV4cCI6MTk4MzgxMjk5Nn0.EGIM96RAZx35lJzdJsyH-qQwv8Hdp7fsn3W0YpN81IU'
);

// Test user - create or get existing user
echo "1. Setting up test user...\n";

try {
    // First register a test user
    $email = 'pintest@example.com';
    $password = 'testpassword123';

    $authResult = $supabaseClient->signUp($email, $password, ['name' => 'PIN Test User']);
    if (isset($authResult['error']) && ! strpos($authResult['error'], 'already been registered')) {
        echo 'Registration failed: '.$authResult['error']."\n";
        exit(1);
    }

    // Try to sign in
    $authResult = $supabaseClient->signIn($email, $password);
    if (! isset($authResult['access_token'])) {
        echo "Login failed\n";
        var_dump($authResult);
        exit(1);
    }

    $userId = $authResult['user']['id'];
    $accessToken = $authResult['access_token'];

    echo "✓ User authenticated: $userId\n";

    // Set user token for Supabase client
    $supabaseClient->setUserToken($accessToken);

    // 2. Check existing preferences
    echo "2. Checking existing preferences...\n";

    $existing = $supabaseClient->from('user_preferences')
        ->select('kids_mode_pin, kids_mode_pin_salt, kids_mode_pin_attempts, kids_mode_pin_locked_until')
        ->eq('user_id', $userId)
        ->single();

    echo 'Existing preferences: '.json_encode($existing)."\n";

    // 3. Test PIN setup
    echo "3. Testing PIN setup...\n";

    $pin = '1234';
    $salt = Str::random(32);
    $hashedPin = Hash::make($pin.$salt);

    $updateData = [
        'kids_mode_pin' => $hashedPin,
        'kids_mode_pin_salt' => $salt,
        'kids_mode_pin_attempts' => 0,
        'kids_mode_pin_locked_until' => null,
    ];

    if ($existing) {
        echo "Updating existing preferences...\n";
        $result = $supabaseClient->from('user_preferences')
            ->update(array_merge($updateData, [
                'updated_at' => now()->toISOString(),
            ]))
            ->eq('user_id', $userId);
    } else {
        echo "Creating new preferences...\n";
        $result = $supabaseClient->from('user_preferences')
            ->insert(array_merge($updateData, [
                'user_id' => $userId,
                'updated_at' => now()->toISOString(),
            ]));
    }

    echo 'Update result: '.json_encode($result)."\n";

    // 4. Verify PIN was saved
    echo "4. Verifying PIN was saved...\n";

    $preferences = $supabaseClient->from('user_preferences')
        ->select('kids_mode_pin, kids_mode_pin_salt, kids_mode_pin_attempts, kids_mode_pin_locked_until')
        ->eq('user_id', $userId)
        ->single();

    echo 'Saved preferences: '.json_encode($preferences)."\n";

    if (! empty($preferences['kids_mode_pin'])) {
        echo "✓ PIN was saved successfully!\n";

        // Test PIN validation
        echo "5. Testing PIN validation...\n";

        $savedHash = $preferences['kids_mode_pin'];
        $savedSalt = $preferences['kids_mode_pin_salt'];

        if (Hash::check($pin.$savedSalt, $savedHash)) {
            echo "✓ PIN validation works correctly!\n";
        } else {
            echo "✗ PIN validation failed!\n";
        }
    } else {
        echo "✗ PIN was NOT saved!\n";
    }

} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
    echo 'Stack trace: '.$e->getTraceAsString()."\n";
}

echo "\n=== Test Complete ===\n";
