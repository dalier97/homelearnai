<?php

require_once __DIR__.'/vendor/autoload.php';

use App\Services\SupabaseClient;
use Illuminate\Support\Facades\Session;

echo "=== Debugging E2E Test Flow ===\n\n";

// Simulate the exact E2E test flow
echo "1. Starting session to simulate web request...\n";
session_start();

// Simulate user registration/login like in E2E test
$supabase = new SupabaseClient(
    'http://localhost:54321',
    'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZS1kZW1vIiwicm9sZSI6ImFub24iLCJleHAiOjE5ODM4MTI5OTZ9.CRXP1A7WOeoJeXxjNni43kdQwgnWNReilDMblYTn_I0',
    'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZS1kZW1vIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImV4cCI6MTk4MzgxMjk5Nn0.EGIM96RAZx35lJzdJsyH-qQwv8Hdp7fsn3W0YpN81IU'
);

// Create user
$testEmail = 'e2e_debug_'.time().'@example.com';
$testPassword = 'testpassword123';

echo "2. Registering user: $testEmail\n";
$signupResult = $supabase->signUp($testEmail, $testPassword, ['name' => 'E2E Debug User']);
if (! isset($signupResult['user']['id'])) {
    echo "❌ Registration failed\n";
    print_r($signupResult);
    exit(1);
}

$userId = $signupResult['user']['id'];
echo "✅ User registered with ID: $userId\n";

// Sign in
echo "\n3. Signing in user...\n";
$signinResult = $supabase->signIn($testEmail, $testPassword);
if (! isset($signinResult['access_token'])) {
    echo "❌ Sign in failed\n";
    print_r($signinResult);
    exit(1);
}

$accessToken = $signinResult['access_token'];
echo '✅ User signed in with token: '.substr($accessToken, 0, 30)."...\n";

// Simulate Laravel session storage (like AuthController does)
echo "\n4. Storing user session data (simulating Laravel)...\n";
$_SESSION['supabase_token'] = $accessToken;
$_SESSION['user_id'] = $userId;
$_SESSION['user'] = $signinResult['user'];
echo "✅ Session data stored\n";

// Now simulate what happens in SupabaseAuth middleware
echo "\n5. Simulating SupabaseAuth middleware...\n";
if (isset($_SESSION['supabase_token']) && isset($_SESSION['user_id'])) {
    echo "✅ Session tokens present\n";

    // Configure SupabaseClient with user token (like middleware does)
    $supabase->setUserToken($_SESSION['supabase_token']);
    echo "✅ SupabaseClient configured with user token\n";
} else {
    echo "❌ Session tokens missing\n";
    exit(1);
}

// Now simulate child creation (like ChildController::store does)
echo "\n6. Creating child via API (simulating ChildController::store)...\n";
$childData = [
    'name' => 'E2E Debug Child',
    'age' => 8,
    'user_id' => $_SESSION['user_id'], // This should be the UUID now
    'independence_level' => 2,
];

echo "Child data to create:\n";
print_r($childData);

try {
    $result = $supabase->from('children')->insert($childData);
    if ($result && isset($result[0]['id'])) {
        $childId = $result[0]['id'];
        echo "✅ Child created successfully with ID: $childId\n";

        echo "Child details:\n";
        print_r($result[0]);
    } else {
        echo "❌ Child creation failed\n";
        print_r($result);
    }
} catch (Exception $e) {
    echo '❌ Exception during child creation: '.$e->getMessage()."\n";
}

// Now test retrieval (simulating page refresh)
echo "\n7. Retrieving children (simulating page refresh)...\n";
try {
    $children = $supabase->from('children')->select('*')->get();
    echo 'Found '.count($children)." children:\n";
    foreach ($children as $child) {
        echo "  - ID: {$child['id']}, Name: {$child['name']}, User ID: {$child['user_id']}\n";
    }

    if (count($children) > 0) {
        echo "✅ SUCCESS: Child persistence working!\n";
    } else {
        echo "❌ FAILURE: No children found after creation\n";
    }
} catch (Exception $e) {
    echo '❌ Exception during retrieval: '.$e->getMessage()."\n";
}

echo "\n=== E2E Debug Complete ===\n";
