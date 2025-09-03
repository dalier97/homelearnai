<?php

require_once __DIR__.'/../vendor/autoload.php';

use Dotenv\Dotenv;
use GuzzleHttp\Client;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__.'/..');
$dotenv->load();

$supabaseUrl = $_ENV['SUPABASE_URL'];
$supabaseServiceKey = $_ENV['SUPABASE_SERVICE_KEY'] ?? $_ENV['SUPABASE_ANON_KEY'];

echo "Running Supabase Database Migrations\n";
echo "====================================\n\n";

// Read the SQL schema file
$schemaFile = __DIR__.'/supabase-schema.sql';
if (! file_exists($schemaFile)) {
    exit("Error: Schema file not found at $schemaFile\n");
}

$sql = file_get_contents($schemaFile);

// For Supabase, we need to use the service key for admin operations
// We'll use the REST API to execute SQL
$client = new Client([
    'base_uri' => $supabaseUrl,
    'headers' => [
        'apikey' => $supabaseServiceKey,
        'Authorization' => 'Bearer '.$supabaseServiceKey,
        'Content-Type' => 'application/json',
    ],
]);

try {
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $index => $statement) {
        if (empty($statement)) {
            continue;
        }

        echo 'Executing statement '.($index + 1)."...\n";

        // Use Supabase's RPC to execute raw SQL
        // Note: This requires a custom RPC function in Supabase
        // Alternative: Use supabase CLI or direct PostgreSQL connection

        // For now, let's output the SQL for manual execution
        echo 'SQL: '.substr($statement, 0, 50)."...\n";
    }

    echo "\n⚠️  IMPORTANT: Supabase doesn't allow direct SQL execution via REST API.\n";
    echo "You have three options:\n\n";

    echo "OPTION 1: Use Supabase Dashboard (Recommended)\n";
    echo "1. Go to https://app.supabase.com\n";
    echo "2. Select your project\n";
    echo "3. Go to SQL Editor\n";
    echo "4. Paste and run the contents of database/supabase-schema.sql\n\n";

    echo "OPTION 2: Use Supabase CLI\n";
    echo "1. Install Supabase CLI: npm install -g supabase\n";
    echo "2. Login: supabase login\n";
    echo "3. Run: supabase db push --db-url \"postgresql://postgres:[PASSWORD]@db.[PROJECT_ID].supabase.co:5432/postgres\"\n\n";

    echo "OPTION 3: Direct PostgreSQL Connection\n";
    echo "1. Get connection string from Supabase Dashboard > Settings > Database\n";
    echo "2. Use psql or any PostgreSQL client\n";
    echo "3. Run the SQL schema\n";

} catch (\Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
}

echo "\n";
echo "For Laravel-style migrations, consider using:\n";
echo "- Laravel's built-in migrations with a PostgreSQL connection\n";
echo "- Supabase CLI for version-controlled migrations\n";
echo "- A custom migration system that uses Supabase's PostgreSQL connection string\n";
