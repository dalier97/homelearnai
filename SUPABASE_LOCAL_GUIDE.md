# Supabase Local Development Guide

## Official Supabase Recommendation

Supabase **strongly recommends** using their local development stack instead of SQLite or other databases. Here's why:

### Benefits of Local Supabase:

1. **Exact Production Parity**: Your local environment runs the same PostgreSQL version and configuration as production
2. **All Features Work**: Auth, Storage, Realtime, Edge Functions - everything works locally
3. **No Internet Required**: Completely offline development
4. **Free**: No cloud costs during development
5. **Migration Testing**: Test database migrations locally before pushing to production

### How It Works:

```bash
# Start local Supabase (runs PostgreSQL, Auth, Storage, etc. in Docker)
supabase start

# Your app connects to local Supabase at:
# API: http://localhost:54321
# DB: postgresql://postgres:postgres@localhost:54322/postgres
# Studio: http://localhost:54323
```

## Environment Configuration

Create `.env.local` for local development:

```env
# Local Supabase
SUPABASE_URL=http://localhost:54321
SUPABASE_ANON_KEY=<local-anon-key-from-supabase-start>
SUPABASE_SERVICE_KEY=<local-service-key-from-supabase-start>
```

Keep `.env` for production:

```env
# Production Supabase
SUPABASE_URL=https://injdgzeiycjaljuqfuve.supabase.co
SUPABASE_ANON_KEY=<production-anon-key>
SUPABASE_SERVICE_KEY=<production-service-key>
```

## Migration Workflow

### 1. Create new migrations:
```bash
supabase migration new create_new_feature
```

### 2. Edit the migration file in `supabase/migrations/`

### 3. Apply locally:
```bash
supabase db reset  # Resets and applies all migrations
```

### 4. Push to production:
```bash
supabase db push
```

## Linking to Remote Project

```bash
# Link your local project to remote Supabase
supabase link --project-ref injdgzeiycjaljuqfuve

# Pull remote schema to local
supabase db pull

# Push local changes to remote
supabase db push
```

## Commands Reference

```bash
supabase start    # Start local stack
supabase stop     # Stop local stack
supabase status   # Show service URLs and keys
supabase db reset # Reset database and apply migrations
```

## Why Not SQLite for Local?

1. **Feature Mismatch**: SQLite doesn't support Supabase Auth, RLS, or PostgreSQL-specific features
2. **Different SQL Syntax**: PostgreSQL and SQLite have syntax differences
3. **No Testing Value**: You'd need different code for local vs production
4. **Migration Issues**: Migrations that work in SQLite might fail in PostgreSQL

## Can You Use SQLite?

Technically yes, but you would need to:
- Maintain two different database configurations
- Write different queries for some operations
- Lose all Supabase features locally
- Risk deployment issues due to environment differences

**Supabase's stance**: Use local Supabase for development. It's what they built the CLI for!