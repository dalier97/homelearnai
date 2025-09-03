<?php

require_once __DIR__.'/vendor/autoload.php';

use App\Services\SupabaseClient;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Create Supabase client
$supabase = new SupabaseClient(
    $_ENV['SUPABASE_URL'],
    $_ENV['SUPABASE_ANON_KEY'],
    $_ENV['SUPABASE_SERVICE_KEY'] ?? $_ENV['SUPABASE_ANON_KEY']
);

// Test email and password
$testEmail = 'test'.time().'@example.com';
$testPassword = 'TestPassword123!';

echo "Testing Supabase Auth Flow\n";
echo "==========================\n\n";

// Test 1: Sign up
echo "1. Testing Sign Up with email: $testEmail\n";
$signupResult = $supabase->signUp($testEmail, $testPassword, ['name' => 'Test User']);
echo "Sign Up Result:\n";
print_r($signupResult);
echo "\n";

// Test 2: Sign in with a known user
echo "2. Testing Sign In with known user (you'll need to provide credentials)\n";
$knownEmail = 'leonsbox@gmail.com';
$knownPassword = 'test1234';  // Update this with correct password
$signinResult = $supabase->signIn($knownEmail, $knownPassword);
echo "Sign In Result:\n";
print_r($signinResult);
echo "\n";

// Check for access token
if (isset($signinResult['access_token'])) {
    echo "✅ SUCCESS: Access token received after sign in!\n";
    echo 'Access Token: '.substr($signinResult['access_token'], 0, 20)."...\n";
    echo 'User ID: '.$signinResult['user']['id']."\n";
} else {
    echo "❌ FAILURE: No access token in sign in response\n";
    if ($signinResult === null) {
        echo "Connection failed - check Supabase URL and keys\n";
    } else {
        echo "Response received but no token - check password or email confirmation requirement\n";
    }
}
