<?php

$pdo = new PDO('pgsql:host=127.0.0.1;port=54322;dbname=postgres', 'postgres', 'postgres');
$stmt = $pdo->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Tables in database:\n";
foreach ($tables as $table) {
    echo "- $table\n";
}

// Check user_preferences table structure
echo "\nuser_preferences table structure:\n";
try {
    $stmt = $pdo->query("SELECT column_name, data_type, is_nullable, column_default FROM information_schema.columns WHERE table_name = 'user_preferences' ORDER BY ordinal_position");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "- {$col['column_name']}: {$col['data_type']} ".($col['is_nullable'] === 'YES' ? 'NULL' : 'NOT NULL')."\n";
    }
} catch (Exception $e) {
    echo 'Error checking user_preferences: '.$e->getMessage()."\n";
}
