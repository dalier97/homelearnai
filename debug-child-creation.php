<?php

require_once __DIR__.'/vendor/autoload.php';

use App\Models\Child;
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

echo "=== Child Creation Debug Test ===\n\n";

// Step 1: Create and authenticate user
$testEmail = 'debug_user_'.time().'@example.com';
$testPassword = 'TestPassword123!';

echo "1. Creating test user: $testEmail\n";
$signupResult = $supabase->signUp($testEmail, $testPassword, ['name' => 'Debug User']);
if (! isset($signupResult['user']['id'])) {
    echo "❌ Failed to create user\n";
    print_r($signupResult);
    exit(1);
}

$userId = $signupResult['user']['id'];
echo "✅ User created with ID: $userId\n";

// Step 2: Sign in to get access token
echo "\n2. Signing in...\n";
$signinResult = $supabase->signIn($testEmail, $testPassword);
if (! isset($signinResult['access_token'])) {
    echo "❌ Failed to sign in\n";
    print_r($signinResult);
    exit(1);
}

$accessToken = $signinResult['access_token'];
echo "✅ Signed in successfully\n";

// Step 3: Create child directly using Supabase service role
echo "\n3. Creating child via service role (should work)...\n";
$serviceSupabase = new SupabaseClient(
    $_ENV['SUPABASE_URL'],
    $_ENV['SUPABASE_SERVICE_KEY'],
    $_ENV['SUPABASE_SERVICE_KEY']
);

$childData = [
    'user_id' => $userId,  // Keep as UUID string
    'name' => 'Debug Child',
    'age' => 8,
];

echo "Child data to insert:\n";
print_r($childData);

$serviceResult = $serviceSupabase->from('children')->insert($childData);
echo "Service role creation result:\n";
print_r($serviceResult);

if ($serviceResult && isset($serviceResult[0]['id'])) {
    $childId = $serviceResult[0]['id'];
    echo "✅ Child created with ID: $childId\n";
} else {
    echo "❌ Service role creation failed\n";
    exit(1);
}

// Step 4: Try to retrieve children with service role
echo "\n4. Retrieving all children via service role...\n";
$allChildren = $serviceSupabase->from('children')->select('*')->get();
echo "All children in database:\n";
print_r($allChildren);

// Step 5: Create a user-authenticated Supabase client and try to retrieve
echo "\n5. Creating user-authenticated client with access token...\n";
$userSupabase = new SupabaseClient(
    $_ENV['SUPABASE_URL'],
    $accessToken,  // Use user's access token
    $accessToken
);

echo "Trying to retrieve children with user access token...\n";
try {
    $userChildren = $userSupabase->from('children')->select('*')->get();
    echo "Children visible to authenticated user:\n";
    print_r($userChildren);

    if (count($userChildren) > 0) {
        echo "✅ RLS is working - user can see their children\n";
    } else {
        echo "❌ RLS issue - user cannot see their children\n";
        echo "This suggests RLS policies are blocking access\n";
    }
} catch (Exception $e) {
    echo '❌ Error retrieving children: '.$e->getMessage()."\n";
}

// Step 6: Test Laravel model approach
echo "\n6. Testing Laravel Child model...\n";
try {
    $children = Child::forUser($userId, $serviceSupabase);
    echo 'Children via Laravel model (count: '.count($children)."):\n";
    foreach ($children as $child) {
        echo "  - ID: {$child->id}, Name: {$child->name}, User ID: {$child->user_id}\n";
    }
} catch (Exception $e) {
    echo '❌ Laravel model error: '.$e->getMessage()."\n";
}

echo "\n=== Debug Complete ===\n";
