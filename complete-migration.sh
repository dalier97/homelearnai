#!/bin/bash

# Complete Supabase Migration Script
echo "Completing Supabase Migration..."
echo "=================================="

# Check if user is logged in
if ! supabase projects list &>/dev/null; then
    echo "❌ Not logged in to Supabase CLI"
    echo "Please run: supabase login"
    exit 1
fi

echo "✓ Logged in to Supabase CLI"

# Link to project (will prompt for DB password if needed)
echo "Linking to project..."
if supabase link --project-ref injdgzeiycjaljuqfuve; then
    echo "✓ Project linked successfully"
else
    echo "❌ Failed to link project"
    echo "Please get your database password from:"
    echo "https://supabase.com/dashboard/project/injdgzeiycjaljuqfuve/settings/database"
    exit 1
fi

# Push migrations
echo "Pushing migrations to remote database..."
if supabase db push; then
    echo "✅ Migration completed successfully!"
    echo ""
    echo "Tasks table created with:"
    echo "- All necessary columns"
    echo "- Row Level Security policies"
    echo "- Proper indexes for performance"
    echo ""
    echo "You can now create tasks in your application!"
else
    echo "❌ Migration failed"
    echo "Alternative: Use the Supabase Dashboard SQL editor"
    echo "https://app.supabase.com/project/injdgzeiycjaljuqfuve/sql/new"
    exit 1
fi