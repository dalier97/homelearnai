# Testing Guide

## Environment Setup

You now have three separate environments:

### 🌍 Production Environment
```bash
./env-switch.sh production
```
- Uses remote Supabase project
- Real user data
- Never run destructive tests here

### 🏠 Development Environment  
```bash
./env-switch.sh local
```
- Uses local Supabase with PostgreSQL
- Persistent data for manual testing
- Requires `supabase start` to be running

### 🧪 Testing Environment
```bash
./env-switch.sh testing
```
- Uses SQLite in-memory database
- Wiped clean on each test run
- Fast and isolated
- For E2E tests that need Supabase features, can still access local Supabase

## Running Tests

### Unit Tests (PHPUnit)
```bash
# Switch to testing environment
./env-switch.sh testing

# Run PHP unit tests
vendor/bin/phpunit
```

### E2E Tests (Playwright)
```bash
# Start local Supabase for integration testing
supabase start

# Start Laravel server in testing mode
./env-switch.sh testing
php artisan serve --port=8000 &

# Run E2E tests
npm run test:e2e
```

### Database Reset for Tests
```bash
# For SQLite tests (automatic - uses :memory:)
./env-switch.sh testing
vendor/bin/phpunit

# For local Supabase integration tests
supabase db reset  # Resets local database only
```

## Test Database Strategy

### Unit Tests → SQLite
- Fast execution
- Complete isolation
- No external dependencies
- Perfect for business logic testing

### Integration/E2E Tests → Local Supabase
- Full feature testing (Auth, RLS, etc.)
- Real PostgreSQL behavior
- Can be reset with `supabase db reset`
- Separate from development data

### Manual Testing → Local Development
- Persistent data between sessions
- Can populate with test data
- Never gets wiped by automated tests

## Creating Test Data

### For Development (persistent)
```bash
./env-switch.sh local
# Create users and tasks manually
# Data persists between sessions
```

### For Automated Tests (temporary)
```bash
# Use factories and seeders in tests
# SQLite database is wiped automatically
# Local Supabase can be reset with supabase db reset
```

## Best Practices

1. **Always use testing environment for automated tests**
2. **Never run tests against production**
3. **Use factories for test data creation**
4. **Reset database state between test runs**
5. **Test both success and error scenarios**

## Commands Reference

```bash
# Environment switching
./env-switch.sh                    # Show current environment
./env-switch.sh local              # Switch to local development
./env-switch.sh production         # Switch to production  
./env-switch.sh testing            # Switch to testing

# Testing setup
./test-setup.sh sqlite            # Configure SQLite testing
supabase start                    # Start local Supabase
supabase db reset                 # Reset local database

# Running tests
vendor/bin/phpunit                # PHP unit tests
npm run test:e2e                  # E2E tests (requires setup)
```