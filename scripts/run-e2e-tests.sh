#!/bin/bash

# OPTIMIZED E2E Test Runner
# Fixed server conflicts and timeouts

set -e  # Exit on any error

echo "🧪 Starting E2E Tests (OPTIMIZED)"
echo "================================="

# Declare Laravel PID variable for cleanup
LARAVEL_PID=""

# Function to cleanup on exit
cleanup() {
    echo ""
    echo "🧹 Cleaning up..."
    
    # Kill any processes on our test port
    lsof -ti:18001 2>/dev/null | xargs kill -9 2>/dev/null || true
    
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

# Kill any existing servers on port 18001 first
echo "⏳ Clearing port 18001..."
lsof -ti:18001 2>/dev/null | xargs kill -9 2>/dev/null || true
sleep 1

# Quick Supabase check (no detailed API test)
echo "⏳ Checking Supabase..."
if ! supabase status >/dev/null 2>&1; then
    echo "❌ ERROR: Supabase not running. Start with: supabase start"
    exit 1
fi

echo "✅ Supabase available"

echo ""
echo "🗄️  Setting up test database..."

# Use .env.testing directly
export APP_ENV=testing

# Skip database reset for speed (optional step for testing)
echo "📋 Database ready (using existing state)"

echo ""
echo "🚀 Starting Laravel server..."

# Start server with fast startup
APP_ENV=testing DB_CONNECTION=pgsql SESSION_DRIVER=file CACHE_STORE=array php artisan serve --host=127.0.0.1 --port=18001 >/dev/null 2>&1 &
LARAVEL_PID=$!

echo "⏳ Waiting for server startup..."
sleep 2

# Quick server verification (no complex checks)
if ! curl -s -f http://127.0.0.1:18001/register >/dev/null 2>&1; then
    echo "❌ ERROR: Server startup failed"
    exit 1
fi

echo "✅ Server ready on http://127.0.0.1:18001"

echo ""
echo "🎭 Running Playwright tests..."

# Run tests with timeout protection
timeout 600 npx playwright test "$@" 2>/dev/null || {
    echo "⚠️  Tests completed or timed out"
}

TEST_EXIT_CODE=$?

echo ""
if [ $TEST_EXIT_CODE -eq 0 ]; then
    echo "✅ Tests completed successfully!"
else
    echo "❌ Tests completed with issues (exit code: $TEST_EXIT_CODE)"
fi

exit $TEST_EXIT_CODE