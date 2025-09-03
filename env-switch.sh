#!/bin/bash

# Environment Switcher for Supabase Development
# Usage: ./env-switch.sh [local|production]

if [ $# -eq 0 ]; then
    echo "Environment Switcher"
    echo "==================="
    echo "Usage: $0 [local|production|testing]"
    echo ""
    echo "Available environments:"
    echo "🌍 production - Remote Supabase project"
    echo "🏠 local     - Local Supabase with persistent dev data"
    echo "🧪 testing   - PostgreSQL test database for isolated tests"
    echo ""
    echo "Current environment:"
    if [ -f .env ]; then
        CURRENT_URL=$(grep SUPABASE_URL .env | cut -d'=' -f2)
        if [[ $CURRENT_URL == *"localhost"* ]]; then
            echo "📍 LOCAL (using local Supabase)"
        else
            echo "🌍 PRODUCTION (using remote Supabase)"
        fi
    else
        echo "❌ No .env file found"
    fi
    exit 0
fi

case $1 in
    "local")
        echo "🔄 Switching to LOCAL environment..."
        
        # Check if local Supabase is running
        if ! curl -f http://localhost:54321/rest/v1/ >/dev/null 2>&1; then
            echo "❌ Local Supabase not running!"
            echo "Start it with: supabase start"
            echo "Then run this script again."
            exit 1
        fi
        
        # Get actual local keys
        echo "Getting local Supabase credentials..."
        if command -v supabase >/dev/null 2>&1; then
            LOCAL_KEYS=$(supabase status --format json 2>/dev/null)
            if [ $? -eq 0 ]; then
                # Parse JSON to get keys (requires jq)
                if command -v jq >/dev/null 2>&1; then
                    ANON_KEY=$(echo $LOCAL_KEYS | jq -r '.anon_key')
                    SERVICE_KEY=$(echo $LOCAL_KEYS | jq -r '.service_role_key')
                else
                    # Fallback to default keys
                    ANON_KEY="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZS1kZW1vIiwicm9sZSI6ImFub24iLCJleHAiOjE5ODM4MTI5OTZ9.CRXP1A7WOeoJeXxjNni43kdQwgnWNReilDMblYTn_I0"
                    SERVICE_KEY="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZS1kZW1vIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImV4cCI6MTk4MzgxMjk5Nn0.EGIM96RAZx35lJzdJsyH-qQwv8Hdp7fsn3W0YpN81IU"
                fi
            fi
        fi
        
        # Update .env for local
        cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
        sed -i '' "s|SUPABASE_URL=.*|SUPABASE_URL=http://localhost:54321|" .env
        sed -i '' "s|SUPABASE_ANON_KEY=.*|SUPABASE_ANON_KEY=$ANON_KEY|" .env
        sed -i '' "s|SUPABASE_SERVICE_KEY=.*|SUPABASE_SERVICE_KEY=$SERVICE_KEY|" .env
        
        echo "✅ Switched to LOCAL environment"
        echo "📍 Supabase API: http://localhost:54321"
        echo "📊 Supabase Studio: http://localhost:54323"
        echo "💾 Backup saved as: .env.backup.$(date +%Y%m%d_%H%M%S)"
        ;;
        
    "production")
        echo "🔄 Switching to PRODUCTION environment..."
        
        # Restore production settings
        cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
        sed -i '' "s|SUPABASE_URL=.*|SUPABASE_URL=https://injdgzeiycjaljuqfuve.supabase.co|" .env
        sed -i '' "s|SUPABASE_ANON_KEY=.*|SUPABASE_ANON_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImluamRnemVpeWNqYWxqdXFmdXZlIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTY4OTkwNTcsImV4cCI6MjA3MjQ3NTA1N30.bBOue_ietqSCeTDn3y6Phz6G4fwTGzzK_5r0tkd_TyY|" .env
        sed -i '' "s|SUPABASE_SERVICE_KEY=.*|SUPABASE_SERVICE_KEY=your-service-key|" .env
        
        echo "✅ Switched to PRODUCTION environment"
        echo "🌍 Supabase API: https://injdgzeiycjaljuqfuve.supabase.co"
        echo "💾 Backup saved as: .env.backup.$(date +%Y%m%d_%H%M%S)"
        ;;
        
    "testing")
        echo "🔄 Switching to TESTING environment..."
        
        # Copy testing configuration to .env
        if [ ! -f .env.testing ]; then
            echo "❌ .env.testing not found!"
            echo "Run: ./scripts/run-e2e-tests.sh"
            exit 1
        fi
        
        cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
        cp .env.testing .env
        
        echo "✅ Switched to TESTING environment"
        echo "🧪 Using PostgreSQL test database"
        echo "🔄 Database wiped clean for each test run"
        echo "💾 Backup saved as: .env.backup.$(date +%Y%m%d_%H%M%S)"
        ;;
        
    *)
        echo "❌ Invalid option: $1"
        echo "Use 'local', 'production', or 'testing'"
        exit 1
        ;;
esac

# Clear Laravel config cache
if [ -f artisan ]; then
    echo "🧹 Clearing Laravel config cache..."
    php artisan config:clear
fi

echo ""
echo "🎯 Environment switch complete!"
echo "Your app now connects to the selected Supabase instance."