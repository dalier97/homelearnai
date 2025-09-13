#!/bin/bash

# Script to systematically fix CSRF token issues in tests

# Find all test files that likely need CSRF token fixes
echo "Finding test files with POST/PATCH/DELETE requests..."

# Look for test methods that make POST/PATCH/DELETE requests without _token
grep -r -E "(->post|->patch|->delete)" tests/Feature --include="*.php" | grep -v "_token" | cut -d: -f1 | sort -u > files_to_fix.txt

echo "Files that likely need CSRF fixes:"
cat files_to_fix.txt | head -10

echo "Found $(cat files_to_fix.txt | wc -l) files potentially needing fixes"

# We'll need to manually fix these as the patterns vary
# For now, let's identify the most common failing patterns
echo "Getting a sample of failing tests to understand patterns..."

# Clean up
rm -f files_to_fix.txt