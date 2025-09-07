-- Add kids mode PIN fields to user_preferences table
-- These fields enable secure PIN-based locking/unlocking of kids mode

ALTER TABLE user_preferences 
ADD COLUMN kids_mode_pin VARCHAR(255) NULL,
ADD COLUMN kids_mode_pin_salt VARCHAR(255) NULL,
ADD COLUMN kids_mode_pin_attempts INTEGER DEFAULT 0 NOT NULL,
ADD COLUMN kids_mode_pin_locked_until TIMESTAMP WITH TIME ZONE NULL;

-- Add comments to document the purpose of each field
COMMENT ON COLUMN user_preferences.kids_mode_pin IS 'Bcrypt hash of kids mode PIN';
COMMENT ON COLUMN user_preferences.kids_mode_pin_salt IS 'Additional security salt for PIN';
COMMENT ON COLUMN user_preferences.kids_mode_pin_attempts IS 'Failed PIN attempts counter for rate limiting';
COMMENT ON COLUMN user_preferences.kids_mode_pin_locked_until IS 'Lockout timestamp after failed attempts';

-- Add constraints for security
ALTER TABLE user_preferences 
ADD CONSTRAINT chk_kids_mode_pin_attempts_range 
CHECK (kids_mode_pin_attempts >= 0 AND kids_mode_pin_attempts <= 10);

-- Create index for faster lookups during PIN validation
CREATE INDEX idx_user_preferences_kids_mode_pin_lockout 
ON user_preferences(user_id, kids_mode_pin_locked_until) 
WHERE kids_mode_pin_locked_until IS NOT NULL;