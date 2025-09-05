#!/bin/bash

# Simplified E2E Test Runner
# Uses consistent PostgreSQL database without backup file chaos

set -e  # Exit on any error

echo "🧪 Starting E2E Tests (Simplified)"
echo "=================================="

# Declare Laravel PID variable for cleanup
LARAVEL_PID=""

# Function to cleanup on exit
cleanup() {
    echo ""
    echo "🧹 Cleaning up..."
    
    # Kill background Laravel server if we started it
    if [ ! -z "$LARAVEL_PID" ]; then
        echo "🛑 Stopping Laravel test server (PID: $LARAVEL_PID)..."
        kill $LARAVEL_PID 2>/dev/null || true
        wait $LARAVEL_PID 2>/dev/null || true
    fi
    
    echo "✅ Cleanup complete"
}

# Set up cleanup on script exit
trap cleanup EXIT INT TERM

echo ""
echo "🔍 Verifying environment..."

# Check if Supabase is running
echo "⏳ Checking if Supabase is available..."
if ! supabase status >/dev/null 2>&1; then
    echo "❌ ERROR: Supabase is not running"
    echo "Please start Supabase: supabase start"
    exit 1
fi

# Check if we can connect to the database
echo "⏳ Testing database connectivity..."
if ! curl -f "http://127.0.0.1:54321/rest/v1/" >/dev/null 2>&1; then
    echo "❌ ERROR: Cannot connect to Supabase API"
    echo "Please ensure Supabase is running: supabase start"
    exit 1
fi

echo "✅ Supabase is available and responding"

echo ""
echo "🗄️  Setting up test database..."

# Use .env.testing directly - no environment switching needed
export APP_ENV=testing

# Create and setup separate test database
echo "📋 Setting up separate test database..."
# Drop and recreate test database
PGPASSWORD=postgres psql -h 127.0.0.1 -p 54322 -U postgres -d postgres -c "DROP DATABASE IF EXISTS test_db;" 2>/dev/null
PGPASSWORD=postgres psql -h 127.0.0.1 -p 54322 -U postgres -d postgres -c "CREATE DATABASE test_db;" 2>/dev/null

# Run migrations on test database
APP_ENV=testing php artisan migrate:fresh --force
if [ $? -eq 0 ]; then
    echo "✅ Test database created and migrations run successfully"
else
    echo "❌ ERROR: Test database setup failed"
    exit 1
fi

echo ""
echo "🚀 Starting Laravel server for testing..."

# Start Laravel server in background with correct environment variables
echo "🔧 Using explicit environment configuration..."
APP_ENV=testing DB_CONNECTION=pgsql SESSION_DRIVER=file CACHE_STORE=array php artisan serve --host=127.0.0.1 --port=8001 >/dev/null 2>&1 &
LARAVEL_PID=$!

echo "⏳ Waiting for Laravel server to start..."
sleep 5

# Verify server is running with proper content
echo "🔍 Testing server response..."
RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8001/register)
if [ "$RESPONSE" != "200" ]; then
    echo "❌ ERROR: Laravel server not serving proper content (HTTP $RESPONSE)"
    echo "📊 Checking server logs..."
    # Give server a moment then test again
    sleep 2
    curl -I http://127.0.0.1:8001/register || true
    exit 1
fi

echo "✅ Laravel server running on http://127.0.0.1:8001 (PID: $LARAVEL_PID)"

echo ""
echo "🎭 Running Playwright E2E tests..."
echo "📊 Database: PostgreSQL test database (clean environment)"
echo "🌐 Server: Laravel testing mode"
echo "🔐 Auth: Full Supabase integration"
echo ""

# Run Playwright tests with passed arguments
# Skip Playwright's built-in server since we manage our own
PLAYWRIGHT_SKIP_SERVER=1 npx playwright test "$@"

TEST_EXIT_CODE=$?

echo ""
if [ $TEST_EXIT_CODE -eq 0 ]; then
    echo "✅ E2E Tests completed successfully!"
    echo "🧪 Test database was properly isolated and reset"
    echo "🏠 Development environment remains clean"
else
    echo "❌ E2E Tests failed with exit code: $TEST_EXIT_CODE"
fi

echo ""
echo "🔍 Test environment summary:"
echo "✅ Separate test_db database used (development data untouched)"
echo "✅ Clean PostgreSQL test database for each run"
echo "✅ No impact on your development environment"
echo "✅ Simple and reliable test execution"

exit $TEST_EXIT_CODE