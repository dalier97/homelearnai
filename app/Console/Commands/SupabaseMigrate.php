<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SupabaseMigrate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'supabase:migrate {--fresh : Drop and recreate all tables}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run Supabase database migrations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Running Supabase Migrations...');

        // Get Supabase connection details from config
        $supabaseUrl = config('services.supabase.url');
        $projectId = $this->extractProjectId($supabaseUrl);

        // Construct PostgreSQL connection string
        // Note: You'll need to add SUPABASE_DB_PASSWORD to your .env file
        $dbPassword = config('services.supabase.db_password', config('services.supabase.service_key'));

        if (! $dbPassword) {
            $this->error('SUPABASE_DB_PASSWORD not set in .env file');
            $this->info('To get your database password:');
            $this->info('1. Go to https://app.supabase.com');
            $this->info('2. Select your project');
            $this->info('3. Go to Settings > Database');
            $this->info('4. Copy the password and add to .env as SUPABASE_DB_PASSWORD');

            return 1;
        }

        // Configure PostgreSQL connection
        config([
            'database.connections.supabase' => [
                'driver' => 'pgsql',
                'host' => "db.{$projectId}.supabase.co",
                'port' => 5432,
                'database' => 'postgres',
                'username' => 'postgres',
                'password' => $dbPassword,
                'charset' => 'utf8',
                'prefix' => '',
                'schema' => 'public',
                'sslmode' => 'require',
            ],
        ]);

        try {
            // Test connection
            DB::connection('supabase')->getPdo();
            $this->info('✓ Connected to Supabase database');

            // Read SQL schema file
            $schemaPath = base_path('database/supabase-schema.sql');
            if (! File::exists($schemaPath)) {
                $this->error('Schema file not found at: '.$schemaPath);

                return 1;
            }

            $sql = File::get($schemaPath);

            if ($this->option('fresh')) {
                $this->warn('Dropping existing tables...');
                DB::connection('supabase')->statement('DROP TABLE IF EXISTS tasks CASCADE');
                DB::connection('supabase')->statement('DROP TABLE IF EXISTS profiles CASCADE');
            }

            // Split and execute SQL statements
            $statements = array_filter(array_map('trim', explode(';', $sql)));

            $this->withProgressBar($statements, function ($statement) {
                if (! empty($statement)) {
                    DB::connection('supabase')->statement($statement.';');
                }
            });

            $this->newLine(2);
            $this->info('✓ Migrations completed successfully!');

            // Verify tables were created
            $tables = DB::connection('supabase')->select("
                SELECT table_name 
                FROM information_schema.tables 
                WHERE table_schema = 'public' 
                AND table_name IN ('tasks', 'profiles')
            ");

            $this->info('Created tables: '.implode(', ', array_column($tables, 'table_name')));

        } catch (\Exception $e) {
            $this->error('Migration failed: '.$e->getMessage());

            if (str_contains($e->getMessage(), 'password authentication failed')) {
                $this->info('');
                $this->info('To fix this:');
                $this->info('1. Go to Supabase Dashboard > Settings > Database');
                $this->info('2. Copy your database password');
                $this->info('3. Add to .env: SUPABASE_DB_PASSWORD=your-password');
            }

            return 1;
        }

        return 0;
    }

    private function extractProjectId($url)
    {
        // Extract project ID from URL like https://injdgzeiycjaljuqfuve.supabase.co
        if (preg_match('/https:\/\/([a-z]+)\.supabase\.co/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
