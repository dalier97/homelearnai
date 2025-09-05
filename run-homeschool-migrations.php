#!/usr/bin/env php
<?php

require_once __DIR__.'/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "Homeschool Learning App - Supabase Migration\n";
echo "=============================================\n\n";

// Read the SQL schema
$schemaFile = __DIR__.'/database/homeschool-schema.sql';
$sql = file_get_contents($schemaFile);

echo "This script will create the foundation tables for your homeschool learning app:\n";
echo "- children (for managing multiple children)\n";
echo "- subjects (like Math, English, Science)\n";
echo "- units (curriculum units within subjects)\n";
echo "- topics (specific lessons within units)\n";
echo "- time_blocks (weekly calendar scheduling)\n\n";

echo "QUICKEST OPTION - Copy & Paste:\n";
echo "---------------------------------\n";
echo "1. Copy the SQL below\n";
echo "2. Go to your Supabase SQL editor\n";
echo "3. Paste and click 'Run'\n\n";

echo "SQL TO RUN:\n";
echo "===========\n";
echo $sql;
echo "\n\n";

echo "ALTERNATIVE - Use Supabase CLI:\n";
echo "--------------------------------\n";
echo "1. Save the SQL to a migration file\n";
echo "2. Run: supabase db push\n\n";

echo "AFTER RUNNING THE MIGRATION:\n";
echo "-----------------------------\n";
echo "Your Laravel app will be able to:\n";
echo "- Add and manage children profiles\n";
echo "- Create subjects with custom colors\n";
echo "- Build curriculum units and topics\n";
echo "- Schedule weekly time blocks\n";
echo "- All with proper Row Level Security!\n\n";

// Check if we can construct Supabase project URL from env
$supabaseUrl = $_ENV['SUPABASE_URL'] ?? null;
if ($supabaseUrl) {
    // Extract project ID from URL like https://abcdefgh.supabase.co
    preg_match('/https:\/\/([^.]+)\.supabase\.co/', $supabaseUrl, $matches);
    if (isset($matches[1])) {
        $projectId = $matches[1];
        $directLink = "https://app.supabase.com/project/{$projectId}/sql/new";
        echo "📋 Direct link to your SQL editor: $directLink\n";
    }
} else {
    echo "📋 Go to: https://app.supabase.com -> Your Project -> SQL Editor\n";
}

echo "\n🚀 Next step: Run this Laravel app with 'composer run dev' after migration!\n";
