-- Create guest_preferences table for anonymous user locale preferences
-- This enables persistent language preferences for non-authenticated users
-- Uses guest_id for identification across browser sessions

CREATE TABLE IF NOT EXISTS guest_preferences (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    guest_id TEXT NOT NULL UNIQUE,
    locale VARCHAR(5) DEFAULT 'en' CHECK (locale IN ('en', 'ru')),
    -- Store additional guest preferences for future use
    timezone VARCHAR(50) DEFAULT 'UTC',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW()),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW())
);

-- Create indexes for better performance
CREATE INDEX idx_guest_preferences_guest_id ON guest_preferences(guest_id);
CREATE INDEX idx_guest_preferences_locale ON guest_preferences(locale);

-- Enable Row Level Security
ALTER TABLE guest_preferences ENABLE ROW LEVEL SECURITY;

-- RLS Policies for guest_preferences table
-- Allow anyone to read guest preferences (they're not sensitive)
CREATE POLICY "Anyone can view guest preferences" ON guest_preferences
    FOR SELECT USING (true);

-- Allow anyone to insert guest preferences
CREATE POLICY "Anyone can insert guest preferences" ON guest_preferences
    FOR INSERT WITH CHECK (true);

-- Allow anyone to update their own guest preferences (identified by guest_id)
CREATE POLICY "Anyone can update guest preferences by guest_id" ON guest_preferences
    FOR UPDATE USING (true);

-- Allow deletion of guest preferences (for cleanup)
CREATE POLICY "Anyone can delete guest preferences" ON guest_preferences
    FOR DELETE USING (true);

-- Add trigger for updated_at column
CREATE TRIGGER update_guest_preferences_updated_at BEFORE UPDATE ON guest_preferences
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Create helper function to get or create guest preferences
CREATE OR REPLACE FUNCTION get_or_create_guest_preferences(p_guest_id TEXT, p_locale TEXT DEFAULT 'en')
RETURNS guest_preferences AS $$
DECLARE
    preferences guest_preferences;
BEGIN
    -- Try to get existing preferences
    SELECT * INTO preferences FROM guest_preferences WHERE guest_id = p_guest_id;
    
    -- If not found, create default preferences
    IF NOT FOUND THEN
        INSERT INTO guest_preferences (guest_id, locale, timezone)
        VALUES (p_guest_id, p_locale, 'UTC')
        RETURNING * INTO preferences;
    END IF;
    
    RETURN preferences;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Create function to update guest locale preference
CREATE OR REPLACE FUNCTION update_guest_locale(p_guest_id TEXT, p_locale TEXT)
RETURNS guest_preferences AS $$
DECLARE
    preferences guest_preferences;
BEGIN
    -- Validate locale
    IF p_locale NOT IN ('en', 'ru') THEN
        RAISE EXCEPTION 'Invalid locale: %', p_locale;
    END IF;
    
    -- Insert or update guest preferences
    INSERT INTO guest_preferences (guest_id, locale, updated_at)
    VALUES (p_guest_id, p_locale, TIMEZONE('utc', NOW()))
    ON CONFLICT (guest_id) 
    DO UPDATE SET 
        locale = p_locale,
        updated_at = TIMEZONE('utc', NOW())
    RETURNING * INTO preferences;
    
    RETURN preferences;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;