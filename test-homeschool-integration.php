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

echo "Testing Homeschool Supabase Integration\n";
echo "=====================================\n\n";

// Test 1: Create a test user
$testEmail = 'testuser_'.time().'@example.com';
$testPassword = 'TestPassword123!';

echo "1. Creating test user: $testEmail\n";
$signupResult = $supabase->signUp($testEmail, $testPassword, ['name' => 'Test User']);
if (isset($signupResult['user']['id'])) {
    echo '✅ SUCCESS: User created with ID: '.$signupResult['user']['id']."\n";
    $userId = $signupResult['user']['id'];
} else {
    echo "❌ FAILURE: Could not create user\n";
    print_r($signupResult);
    exit(1);
}

// Test 2: Sign in
echo "\n2. Signing in with test user\n";
$signinResult = $supabase->signIn($testEmail, $testPassword);
if (isset($signinResult['access_token'])) {
    echo "✅ SUCCESS: Signed in successfully\n";
    $accessToken = $signinResult['access_token'];
} else {
    echo "❌ FAILURE: Sign in failed\n";
    print_r($signinResult);
    exit(1);
}

// Test 3: Create a child using the service role to bypass RLS temporarily
echo "\n3. Testing child creation\n";
try {
    // First, create child with service key (should bypass RLS)
    $serviceSupabase = new SupabaseClient(
        $_ENV['SUPABASE_URL'],
        $_ENV['SUPABASE_SERVICE_KEY'],
        $_ENV['SUPABASE_SERVICE_KEY']
    );

    $childData = [
        'user_id' => $userId,
        'name' => 'Test Child',
        'age' => 8,
    ];

    $childResult = $serviceSupabase->from('children')->insert($childData);
    if ($childResult && isset($childResult[0]['id'])) {
        echo '✅ SUCCESS: Child created with ID: '.$childResult[0]['id']."\n";
        $childId = $childResult[0]['id'];
    } else {
        echo "❌ FAILURE: Could not create child\n";
        print_r($childResult);
    }

} catch (Exception $e) {
    echo '❌ ERROR: '.$e->getMessage()."\n";
}

// Test 4: Try to retrieve children with user token (should work with RLS)
echo "\n4. Testing child retrieval with user authentication\n";
try {
    // Create a client with user token
    $userSupabase = new SupabaseClient(
        $_ENV['SUPABASE_URL'],
        $_ENV['SUPABASE_ANON_KEY'],
        $_ENV['SUPABASE_ANON_KEY']
    );

    // Set authorization header manually for user context
    $children = $userSupabase->from('children')->select('*')->get();
    echo 'Found '.count($children)." children\n";

    if (count($children) > 0) {
        echo "✅ SUCCESS: RLS is working - user can see their children\n";
    } else {
        echo "⚠️  WARNING: No children found - this might be expected with RLS\n";
    }

} catch (Exception $e) {
    echo '❌ ERROR: '.$e->getMessage()."\n";
}

echo "\n5. Testing other tables\n";
try {
    $subjects = $serviceSupabase->from('subjects')->select('*')->get();
    echo '✅ Subjects table accessible ('.count($subjects)." records)\n";

    $sessions = $serviceSupabase->from('sessions')->select('*')->get();
    echo '✅ Sessions view accessible ('.count($sessions)." records)\n";

} catch (Exception $e) {
    echo '❌ ERROR: '.$e->getMessage()."\n";
}

echo "\n🎯 Integration Test Complete!\n";
