#!/bin/bash

# Safe PHP Test Runner - Prevents accidental dev database wipes
# This script ensures tests ALWAYS use the test database

set -e

echo "🛡️  Safe Test Runner - Protecting your development database"
echo "==========================================================="

# Force testing environment to prevent accidental data loss
export APP_ENV=testing
export DB_CONNECTION=pgsql
export DB_DATABASE=learning_app_test
export SESSION_DRIVER=file
export CACHE_STORE=array

# Verify we're using the test database
if [ "$DB_DATABASE" != "learning_app_test" ]; then
    echo "❌ ERROR: Not using test database! Aborting to protect your data."
    exit 1
fi

echo "✅ Using test database: $DB_DATABASE"
echo ""

# Run the tests with all arguments passed through
php artisan test "$@"