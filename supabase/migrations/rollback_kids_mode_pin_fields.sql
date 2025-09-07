-- Rollback migration for kids mode PIN fields
-- This removes the kids mode PIN fields from user_preferences table

-- Remove the constraint first
ALTER TABLE user_preferences DROP CONSTRAINT IF EXISTS chk_kids_mode_pin_attempts_range;

-- Remove the index
DROP INDEX IF EXISTS idx_user_preferences_kids_mode_pin_lockout;

-- Remove the columns one by one
ALTER TABLE user_preferences DROP COLUMN IF EXISTS kids_mode_pin;
ALTER TABLE user_preferences DROP COLUMN IF EXISTS kids_mode_pin_salt;
ALTER TABLE user_preferences DROP COLUMN IF EXISTS kids_mode_pin_attempts;
ALTER TABLE user_preferences DROP COLUMN IF EXISTS kids_mode_pin_locked_until;