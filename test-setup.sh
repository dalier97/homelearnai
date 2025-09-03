#!/bin/bash

# Test Environment Setup Script
# Creates a separate test database environment that can be safely wiped

echo "Setting up test environment..."
echo "=============================="

# Option 1: Use SQLite for tests (fastest, completely isolated)
# Option 2: Use separate Supabase project for testing
# Option 3: Use different schema in same local Supabase

case ${1:-sqlite} in
    "sqlite")
        echo "🧪 Using SQLite for tests (recommended for speed and isolation)"
        
        # Create test-specific .env.testing with SQLite
        cp .env.testing .env.testing.backup.$(date +%Y%m%d_%H%M%S) 2>/dev/null || true
        
        cat > .env.testing << 'EOF'
# Testing Environment - SQLite (isolated and fast)
APP_NAME=TaskMaster
APP_ENV=testing
APP_KEY=base64:JBY6kBEkc57hWpF4SKb3lrqiYUvN1u+qzao3GDOrEro=
APP_DEBUG=true
APP_URL=http://localhost:8000

# SQLite for testing (in-memory, wiped after each test)
DB_CONNECTION=sqlite
DB_DATABASE=:memory:

# Fast configurations for testing
SESSION_DRIVER=array
CACHE_STORE=array
QUEUE_CONNECTION=sync
MAIL_MAILER=log
BROADCAST_CONNECTION=log

# For tests that need Supabase features, use local instance
SUPABASE_URL=http://localhost:54321
SUPABASE_ANON_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZS1kZW1vIiwicm9sZSI6ImFub24iLCJleHAiOjE5ODM4MTI5OTZ9.CRXP1A7WOeoJeXxjNni43kdQwgnWNReilDMblYTn_I0
SUPABASE_SERVICE_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZS1kZW1vIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImV4cCI6MTk4MzgxMjk5Nn0.EGIM96RAZx35lJzdJsyH-qQwv8Hdp7fsn3W0YpN81IU

VITE_APP_NAME="${APP_NAME}"
EOF
        
        echo "✅ SQLite testing environment configured"
        echo "📍 Tests will use in-memory SQLite database"
        echo "🔄 Database is wiped clean for each test run"
        echo "🚀 E2E tests can still use local Supabase for full integration testing"
        ;;
        
    "supabase-test-project")
        echo "🧪 Using separate Supabase project for testing"
        echo "📝 You'll need to create a separate Supabase project for testing"
        echo "🌐 Visit: https://supabase.com/dashboard and create 'taskmaster-test'"
        echo "⚡ Then update SUPABASE_URL and keys in .env.testing"
        ;;
        
    "schema-based")
        echo "🧪 Using schema-based testing in local Supabase"
        echo "🗂️  Will create 'test' schema alongside 'public' schema"
        echo "💡 This allows isolated test data while keeping dev data"
        
        # This would require modifying our Supabase client to use different schemas
        echo "⚠️  Requires code changes to support schema switching"
        ;;
        
    *)
        echo "❌ Invalid option: $1"
        echo "Usage: $0 [sqlite|supabase-test-project|schema-based]"
        echo "Recommended: sqlite (fastest and most isolated)"
        ;;
esac

echo ""
echo "Current environments:"
echo "🌍 Production: Remote Supabase (.env)"
echo "🏠 Development: Local Supabase (.env.local)"  
echo "🧪 Testing: $([ -f .env.testing ] && echo 'Configured' || echo 'Not configured') (.env.testing)"