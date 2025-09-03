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

echo "Testing Supabase Email Confirmation Requirements\n";
echo "================================================\n\n";

// Test with a confirmed email (if you've confirmed it)
$testEmail = 'leonsbox@gmail.com';
$testPassword = 'test1234'; // Update with your actual password

echo "1. Testing login with potentially confirmed email: $testEmail\n";
$result = $supabase->signIn($testEmail, $testPassword);

if (isset($result['access_token'])) {
    echo "✅ SUCCESS: Login worked! Email is confirmed.\n";
    echo 'User ID: '.$result['user']['id']."\n";
    echo 'Email verified: '.($result['user']['email_verified'] ? 'Yes' : 'No')."\n";
} elseif (isset($result['error'])) {
    echo '❌ FAILURE: '.$result['error']."\n";
    if (isset($result['details'])) {
        echo 'Details: '.json_encode($result['details'], JSON_PRETTY_PRINT)."\n";
    }
    echo "\nPossible reasons:\n";
    echo "1. Wrong password (double-check the password you used during registration)\n";
    echo "2. Email not confirmed (check your email for confirmation link)\n";
    echo "3. Account doesn't exist or is disabled\n";
} else {
    echo "❌ No response from Supabase\n";
}

echo "\n\nTo fix this issue:\n";
echo "1. Check your email for the confirmation link from Supabase\n";
echo "2. Click the confirmation link\n";
echo "3. Try logging in again\n";
echo "4. If still failing, the password might be wrong\n";
