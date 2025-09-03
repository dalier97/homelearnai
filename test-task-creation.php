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

echo "Testing Task Creation\n";
echo "====================\n\n";

// Test user ID (from your successful login)
$userId = '952c360e-a9a2-4a7e-8c14-c8cc7bbc8ca6';

echo "1. Testing task creation for user: $userId\n";

$taskData = [
    'title' => 'Test Task '.time(),
    'description' => 'This is a test task',
    'priority' => 'medium',
    'status' => 'pending',
    'user_id' => $userId,
    'due_date' => date('Y-m-d H:i:s', strtotime('+1 week')),
];

echo "Task data:\n";
print_r($taskData);

try {
    $result = $supabase->from('tasks')->insert($taskData);
    echo "\nResult:\n";
    print_r($result);

    if ($result && isset($result[0]['id'])) {
        echo "\n✅ SUCCESS: Task created with ID: ".$result[0]['id']."\n";
    } else {
        echo "\n❌ FAILURE: Could not create task\n";
    }
} catch (\Exception $e) {
    echo "\n❌ ERROR: ".$e->getMessage()."\n";
    echo "\nPossible issues:\n";
    echo "1. Tasks table doesn't exist in Supabase\n";
    echo "2. RLS policies are blocking the insert\n";
    echo "3. Column mismatch between schema and data\n";
}

echo "\n\n2. Testing task retrieval\n";
try {
    $tasks = $supabase->from('tasks')
        ->select('*')
        ->eq('user_id', $userId)
        ->get();

    echo 'Found '.count($tasks)." tasks\n";
    if (count($tasks) > 0) {
        echo "First task:\n";
        print_r($tasks[0]);
    }
} catch (\Exception $e) {
    echo 'Error retrieving tasks: '.$e->getMessage()."\n";
}
