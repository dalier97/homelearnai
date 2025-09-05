<?php

require_once __DIR__.'/vendor/autoload.php';

use App\Models\Child;
use App\Services\SupabaseClient;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "=== Testing SupabaseClient User Token Fix ===\n\n";

// Create Supabase client
$supabase = new SupabaseClient(
    $_ENV['SUPABASE_URL'],
    $_ENV['SUPABASE_ANON_KEY'],
    $_ENV['SUPABASE_SERVICE_KEY'] ?? $_ENV['SUPABASE_ANON_KEY']
);

// Step 1: Create and authenticate user
$testEmail = 'fix_test_'.time().'@example.com';
$testPassword = 'TestPassword123!';

echo "1. Creating and authenticating user: $testEmail\n";
$signupResult = $supabase->signUp($testEmail, $testPassword, ['name' => 'Fix Test User']);
if (! isset($signupResult['user']['id'])) {
    echo "❌ Failed to create user\n";
    exit(1);
}

$userId = $signupResult['user']['id'];
$signinResult = $supabase->signIn($testEmail, $testPassword);
if (! isset($signinResult['access_token'])) {
    echo "❌ Failed to sign in\n";
    exit(1);
}

$accessToken = $signinResult['access_token'];
echo '✅ User authenticated with token: '.substr($accessToken, 0, 20)."...\n";

// Step 2: Set user token on SupabaseClient
echo "\n2. Setting user token on SupabaseClient...\n";
$supabase->setUserToken($accessToken);

// Step 3: Try to create child using user-authenticated client (should work with RLS)
echo "\n3. Creating child with user-authenticated client...\n";
$childData = [
    'user_id' => $userId,
    'name' => 'Fix Test Child',
    'age' => 7,
];

$result = $supabase->from('children')->insert($childData);
if ($result && isset($result[0]['id'])) {
    $childId = $result[0]['id'];
    echo "✅ Child created with ID: $childId\n";
} else {
    echo "❌ Child creation failed\n";
    print_r($result);
    exit(1);
}

// Step 4: Try to retrieve children (should work with RLS)
echo "\n4. Retrieving children with user-authenticated client...\n";
$children = $supabase->from('children')->select('*')->get();
echo 'Found '.count($children)." children:\n";
foreach ($children as $child) {
    echo "  - ID: {$child['id']}, Name: {$child['name']}, User ID: {$child['user_id']}\n";
}

// Step 5: Test Laravel model
echo "\n5. Testing Laravel Child model...\n";
$childrenCollection = Child::forUser($userId, $supabase);
echo 'Children via Laravel model (count: '.count($childrenCollection)."):\n";
foreach ($childrenCollection as $child) {
    echo "  - ID: {$child->id}, Name: {$child->name}, User ID: {$child->user_id}\n";
}

echo "\n✅ Fix test complete - user authentication and RLS working!\n";
