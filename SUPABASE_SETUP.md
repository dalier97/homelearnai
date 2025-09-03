# Supabase Configuration

## Disable Email Confirmation (For Testing)

If you want to test without email confirmation:

1. Go to your Supabase Dashboard: https://app.supabase.com
2. Select your project (injdgzeiycjaljuqfuve)
3. Go to **Authentication** → **Providers** → **Email**
4. Toggle OFF "Confirm email" option
5. Save changes

**Note**: Re-enable this for production for security!

## Email Confirmation Setup

To fix the email confirmation redirect (currently going to port 3000), you need to update the Supabase project settings:

1. Go to your Supabase Dashboard: https://app.supabase.com
2. Select your project (injdgzeiycjaljuqfuve)
3. Go to **Authentication** → **URL Configuration**
4. Update the following URLs:

### Redirect URLs (Important!)
Add these URLs to the "Redirect URLs" section:
- `http://localhost:8000/auth/confirm`
- `http://localhost:8000/dashboard`
- `http://localhost:8000` (for production, replace with your actual domain)

### Site URL
Set the Site URL to:
- `http://localhost:8000`

### Email Templates (Optional)
You can also customize the confirmation email template:
1. Go to **Authentication** → **Email Templates**
2. Update the "Confirm signup" template
3. Make sure the confirmation URL uses the correct domain/port

## Current Issues and Solutions

### Issue 1: Email confirmation links go to port 3000
**Solution**: Update the Site URL in Supabase dashboard to `http://localhost:8000`

### Issue 2: Registration shows "Invalid credentials" after user creation
**Cause**: This happens when trying to auto-login a user that requires email confirmation.
**Solution**: The app now properly handles this by:
- Showing a success message that email confirmation is required
- Redirecting to login page with instructions
- Handling the email confirmation callback at `/auth/confirm`

## Testing Email Confirmation

1. Register a new user
2. Check your email for the confirmation link
3. Click the link - it should now redirect to `http://localhost:8000/auth/confirm#access_token=...`
4. The confirmation page will process the token and redirect to login
5. Log in with your credentials

## API Configuration

The app is configured to use:
- **Supabase URL**: `https://injdgzeiycjaljuqfuve.supabase.co`
- **Anon Key**: Configured in `.env` file
- **Service Key**: Optional, for admin operations

## Row Level Security (RLS)

Make sure to run the SQL schema in `database/supabase-schema.sql` in your Supabase SQL editor to set up:
- Tables with proper structure
- Row Level Security policies
- Indexes for performance