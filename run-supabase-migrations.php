#!/usr/bin/env php
<?php

require_once __DIR__.'/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "Supabase Migration Instructions\n";
echo "================================\n\n";

// Read the SQL schema
$schemaFile = __DIR__.'/database/supabase-schema.sql';
$sql = file_get_contents($schemaFile);

echo "Unfortunately, Supabase doesn't allow direct SQL execution via REST API for security reasons.\n";
echo "However, here are your options:\n\n";

echo "QUICKEST OPTION - Copy & Paste:\n";
echo "---------------------------------\n";
echo "1. Copy the SQL below\n";
echo "2. Go to: https://app.supabase.com/project/injdgzeiycjaljuqfuve/sql/new\n";
echo "3. Paste and click 'Run'\n\n";

echo "SQL TO RUN:\n";
echo "===========\n";
echo $sql;
echo "\n\n";

echo "ALTERNATIVE - Use Supabase CLI:\n";
echo "--------------------------------\n";
echo "1. Install: npm install -g supabase\n";
echo "2. Login: supabase login\n";
echo "3. Link: supabase link --project-ref injdgzeiycjaljuqfuve\n";
echo "4. Push: supabase db push\n\n";

echo "FOR FUTURE - Better Migration Strategy:\n";
echo "---------------------------------------\n";
echo "1. Use Supabase CLI for version-controlled migrations\n";
echo "2. Create migrations with: supabase migration new create_tasks_table\n";
echo "3. This allows programmatic deployment via CI/CD\n";

// Create a clickable link for convenience
$directLink = 'https://app.supabase.com/project/injdgzeiycjaljuqfuve/sql/new';
echo "\n📋 Direct link to SQL editor: $directLink\n";
