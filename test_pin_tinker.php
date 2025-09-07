// Test PIN setup using artisan tinker
use App\Services\SupabaseClient;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

echo "=== Testing PIN Setup ===\n";

// Create Supabase client
$supabaseClient = new SupabaseClient(
    'http://localhost:54321',
    'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZS1kZW1vIiwicm9sZSI6ImFub24iLCJleHAiOjE5ODM4MTI5OTZ9.CRXP1A7WOeoJeXxjNni43kdQwgnWNReilDMblYTn_I0',
    'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZS1kZW1vIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImV4cCI6MTk4MzgxMjk5Nn0.EGIM96RAZx35lJzdJsyH-qQwv8Hdp7fsn3W0YpN81IU'
);

// Test authentication
$email = 'pintest@example.com';
$password = 'testpassword123';

// Try to register (may already exist)
$authResult = $supabaseClient->signUp($email, $password, ['name' => 'PIN Test User']);
echo "Registration result: " . json_encode($authResult, JSON_PRETTY_PRINT) . "\n";

// Sign in
$authResult = $supabaseClient->signIn($email, $password);
echo "Login result: " . json_encode($authResult, JSON_PRETTY_PRINT) . "\n";

if (!isset($authResult['access_token'])) {
    echo "FAILED: Could not authenticate\n";
    exit;
}

$userId = $authResult['user']['id'];
$accessToken = $authResult['access_token'];
echo "SUCCESS: User authenticated: $userId\n";

// Set user token
$supabaseClient->setUserToken($accessToken);

// Check existing preferences
echo "\n=== Checking existing preferences ===\n";
$existing = $supabaseClient->from('user_preferences')
    ->select('*')
    ->eq('user_id', $userId)
    ->single();
echo "Existing: " . json_encode($existing, JSON_PRETTY_PRINT) . "\n";

// Test PIN setup
echo "\n=== Setting up PIN ===\n";
$pin = '1234';
$salt = Str::random(32);
$hashedPin = Hash::make($pin . $salt);

$updateData = [
    'kids_mode_pin' => $hashedPin,
    'kids_mode_pin_salt' => $salt,
    'kids_mode_pin_attempts' => 0,
    'kids_mode_pin_locked_until' => null,
];

try {
    if ($existing) {
        echo "Updating existing record...\n";
        $result = $supabaseClient->from('user_preferences')
            ->update(array_merge($updateData, [
                'updated_at' => now()->toISOString(),
            ]))
            ->eq('user_id', $userId);
    } else {
        echo "Creating new record...\n";
        $result = $supabaseClient->from('user_preferences')
            ->insert(array_merge($updateData, [
                'user_id' => $userId,
                'updated_at' => now()->toISOString(),
            ]));
    }
    echo "Update result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

// Verify the PIN was saved
echo "\n=== Verifying PIN was saved ===\n";
$preferences = $supabaseClient->from('user_preferences')
    ->select('*')
    ->eq('user_id', $userId)
    ->single();
echo "Final preferences: " . json_encode($preferences, JSON_PRETTY_PRINT) . "\n";

if (!empty($preferences['kids_mode_pin'])) {
    echo "SUCCESS: PIN was saved!\n";
    
    // Test PIN validation
    echo "\n=== Testing PIN validation ===\n";
    $savedHash = $preferences['kids_mode_pin'];
    $savedSalt = $preferences['kids_mode_pin_salt'];
    
    if (Hash::check($pin . $savedSalt, $savedHash)) {
        echo "SUCCESS: PIN validation works!\n";
    } else {
        echo "FAILED: PIN validation failed!\n";
    }
} else {
    echo "FAILED: PIN was NOT saved!\n";
}

echo "\n=== Test Complete ===\n";