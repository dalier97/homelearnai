# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel-based task management SaaS application that integrates with Supabase for authentication and database, uses HTMX for dynamic frontend interactions, and includes Playwright for E2E testing.

## Common Development Commands

### Running the Application

```bash
# Start development server with all services
composer run dev

# Or run services individually
php artisan serve              # Laravel server
npm run dev                     # Vite dev server for assets
php artisan queue:listen        # Queue worker
php artisan pail               # Log viewer
```

### Testing

```bash
# PHP Tests
php artisan test               # Run all PHP tests
php artisan test --filter TestName  # Run specific test

# E2E Tests with Database Isolation
npm run test:e2e              # Run E2E tests with PostgreSQL isolation
npm run test:e2e:headed       # Run E2E tests with browser visible
npm run test:e2e -- <file>    # Run specific test file with isolation

# Standard Playwright Tests (not isolated)
npm run test                   # Run Playwright tests (no isolation)
npm run test:headed           # Run tests with browser UI
npm run test:ui               # Interactive test runner

# Full Test Suite
npm run test:all              # Runs lint, type-check, and tests
```

### Code Quality

```bash
npm run lint                  # Run ESLint
npm run lint:fix             # Auto-fix ESLint issues
npm run format               # Format code with Prettier
npm run type-check           # TypeScript type checking
./vendor/bin/pint            # Format PHP code
```

### Build & Deploy

```bash
npm run build                # Build frontend assets
php artisan config:cache     # Cache configuration
php artisan route:cache      # Cache routes
```

### Database Operations

```bash
php artisan migrate          # Run migrations
php artisan migrate:fresh    # Drop all tables and re-run migrations
php artisan db:seed         # Seed the database
```

## Architecture Overview

### Tech Stack

- **Backend**: Laravel 12 with PHP 8.2+
- **Database/Auth**: Supabase (PostgreSQL with Row Level Security)
- **Frontend**: HTMX for interactivity, Alpine.js for client state, Tailwind CSS
- **Testing**: PHPUnit for backend, Playwright for E2E tests
- **Dev Tools**: Vite for asset bundling, Husky for git hooks

### Key Directories

- `app/Services/SupabaseClient.php`: Supabase integration service
- `app/Http/Controllers/`: TaskController and AuthController handle main functionality
- `app/Models/`: Task model with Supabase-backed operations
- `app/Http/Middleware/SupabaseAuth.php`: Custom auth middleware for Supabase
- `resources/views/`: Blade templates with HTMX attributes
- `tests/e2e/`: Playwright E2E test specs

### Supabase Integration Pattern

The app uses a custom SupabaseClient service that:

1. Handles authentication via Supabase Auth API
2. Provides a query builder for database operations
3. Stores auth tokens in Laravel sessions
4. Uses Row Level Security policies for data access

### HTMX Implementation

- All CRUD operations use HTMX attributes (`hx-get`, `hx-post`, etc.)
- Partial views in `resources/views/tasks/partials/` for dynamic updates
- Custom event triggers for toast notifications
- CSRF tokens automatically included via JavaScript configuration

### Authentication Flow

1. User credentials sent to Supabase Auth
2. Access token stored in Laravel session
3. SupabaseAuth middleware validates token on protected routes
4. User context available via session data

## Environment Configuration

Required `.env` variables:

```
SUPABASE_URL=https://your-project.supabase.co
SUPABASE_ANON_KEY=your-anon-key
SUPABASE_SERVICE_KEY=your-service-key
```

## Testing Strategy

### Test Types

- **Unit Tests**: Test individual PHP classes and methods
- **Feature Tests**: Test API endpoints and controller actions
- **E2E Tests**: Full user journey testing with Playwright

### E2E Testing with Database Isolation

**Recommended**: Use `npm run test:e2e` for all E2E testing. This provides:

#### **Complete Database Isolation**:

- Uses **PostgreSQL test database** that gets reset between runs
- **Full Supabase authentication** compatibility
- **Zero interference** with development data
- **Automatic environment switching** and cleanup

#### **Multi-Environment Setup**:

- **🌍 Production**: Remote Supabase (`.env`)
- **🏠 Development**: Local Supabase with persistent data (`.env.local`)
- **🧪 Testing**: PostgreSQL test database, completely isolated (`.env.testing`)

#### **Prerequisites for E2E Tests**:

```bash
# 1. Start local Supabase
supabase start

# 2. Run E2E tests (handles environment automatically)
npm run test:e2e
```

#### **What the E2E Test Runner Does**:

1. Backs up your current environment
2. Switches to testing environment (PostgreSQL)
3. Connects to local Supabase for authentication
4. Drops and recreates test database
5. Runs fresh Laravel migrations
6. Starts isolated Laravel test server
7. Runs Playwright tests with full integration
8. Automatically restores original environment
9. Cleans up test server and data

#### **Environment Switching**:

```bash
./env-switch.sh              # Show current environment
./env-switch.sh local        # Switch to local development
./env-switch.sh production   # Switch to production
./env-switch.sh testing      # Switch to testing (manual)
```

## Key Development Patterns

1. **Partial Rendering**: Return partial Blade views for HTMX requests
2. **Event-Driven UI**: Use HTMX events to trigger toast notifications
3. **Service Pattern**: Business logic in service classes, not controllers
4. **Query Builder**: Custom Supabase query builder mimics Laravel's Eloquent
5. **Session-Based Auth**: Supabase tokens stored in Laravel sessions

## Debugging

For detailed debugging instructions, see [DEBUG_GUIDE.md](./DEBUG_GUIDE.md). Key points:

- Always return structured errors from external service calls
- Use standalone test scripts to verify API integrations
- Monitor Laravel logs: `tail -f storage/logs/laravel.log`
- Run server with debug mode: `APP_DEBUG=true php artisan serve`

## Development Server Auto-Reload

**Important**: PHP's built-in server (`php artisan serve`) **automatically** detects file changes and reloads on the next request. You do **NOT** need to restart the server when you change PHP code.

However, you need to manually restart if you:

- Change `.env` variables (run `php artisan config:clear` first)
- Modify configuration files in `config/`
- Install new Composer packages
- Change route definitions (clear route cache with `php artisan route:clear`)

For frontend assets (CSS/JS), Vite's dev server (`npm run dev`) provides hot module replacement (HMR) and auto-reloads.

## 🔧 Claude Code Hooks (Automatic Quality Checks)

Automatic code quality checks and testing are configured via Claude Code hooks in `.claude/hooks.json`.

### What Happens Automatically

**When you modify PHP files:**

- ✅ PHP syntax validation (`php -l`)
- 🎨 Laravel Pint code formatting (auto-fix)
- 🔒 Basic security checks (XSS, debug statements)
- 🧪 Relevant PHP unit tests

**When you modify JS/TS files:**

- ✅ TypeScript type checking (for test files)
- 🔧 Basic JavaScript syntax validation
- 🎯 HTMX-focused checks (warns about DOM manipulation)
- 🔒 Essential security checks (eval usage)
- 🧪 JavaScript tests (if needed)

**For critical changes (controllers, routes, middleware):**

- 🧪 E2E tests run in background

### Hook Management

```bash
# View hook configuration
cat .claude/hooks.json

# Test hooks manually
.claude/hooks/php-quality-check.sh
.claude/hooks/js-quality-check.sh
.claude/hooks/test-runner.sh

# Disable hooks temporarily
mv .claude/hooks.json .claude/hooks.json.disabled

# Re-enable hooks
mv .claude/hooks.json.disabled .claude/hooks.json
```

### Hook Requirements

- **PHP Hooks**: Laravel Pint (✅ installed), PHPUnit
- **JS/TS Hooks**: Node.js (✅ installed), TypeScript (✅ installed for testing)
- **Test Hooks**: Playwright (✅ installed), Laravel testing framework

### Hook Configuration Details

- **Timeouts**: PHP (30s), JS/TS (20s - minimal), Tests (60s)
- **Background execution**: Tests run in background for critical changes
- **Auto-fix**: Most issues are automatically fixed when possible
- **Error handling**: Non-critical issues don't block development

See `.claude/README.md` for detailed hook documentation and customization options.
