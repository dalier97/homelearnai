-- Review System Database Schema Migration
-- This migration extends the existing homeschool schema with review system functionality

-- Create reviews table for spaced repetition system
CREATE TABLE IF NOT EXISTS reviews (
    id SERIAL PRIMARY KEY,
    session_id INTEGER NOT NULL REFERENCES homeschool_sessions(id) ON DELETE CASCADE,
    child_id INTEGER NOT NULL REFERENCES children(id) ON DELETE CASCADE,
    topic_id INTEGER NOT NULL REFERENCES topics(id) ON DELETE CASCADE,
    interval_days INTEGER DEFAULT 1 CHECK (interval_days >= 1 AND interval_days <= 240),
    ease_factor DECIMAL(3,2) DEFAULT 2.5 CHECK (ease_factor >= 1.3 AND ease_factor <= 2.5),
    repetitions INTEGER DEFAULT 0 CHECK (repetitions >= 0),
    status VARCHAR(20) DEFAULT 'new' CHECK (status IN ('new', 'learning', 'reviewing', 'mastered')),
    due_date DATE NOT NULL,
    last_reviewed_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW()),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW()),
    UNIQUE(session_id) -- Each session can only have one review
);

-- Add evidence capture fields to homeschool_sessions table
ALTER TABLE homeschool_sessions ADD COLUMN IF NOT EXISTS evidence_notes TEXT;
ALTER TABLE homeschool_sessions ADD COLUMN IF NOT EXISTS evidence_photos TEXT[]; -- Array of file paths/URLs
ALTER TABLE homeschool_sessions ADD COLUMN IF NOT EXISTS evidence_voice_memo TEXT; -- File path/URL for voice recording
ALTER TABLE homeschool_sessions ADD COLUMN IF NOT EXISTS evidence_attachments TEXT[]; -- Array of additional file paths

-- Add commitment_type and skipped_from_date if they don't exist (from previous milestones)
ALTER TABLE homeschool_sessions ADD COLUMN IF NOT EXISTS commitment_type VARCHAR(20) DEFAULT 'preferred' 
    CHECK (commitment_type IN ('fixed', 'preferred', 'flexible'));
ALTER TABLE homeschool_sessions ADD COLUMN IF NOT EXISTS skipped_from_date DATE;

-- Create catch_up_sessions table if it doesn't exist (from previous milestones)
CREATE TABLE IF NOT EXISTS catch_up_sessions (
    id SERIAL PRIMARY KEY,
    original_session_id INTEGER NOT NULL REFERENCES homeschool_sessions(id) ON DELETE CASCADE,
    child_id INTEGER NOT NULL REFERENCES children(id) ON DELETE CASCADE,
    topic_id INTEGER NOT NULL REFERENCES topics(id) ON DELETE CASCADE,
    estimated_minutes INTEGER NOT NULL CHECK (estimated_minutes > 0 AND estimated_minutes <= 480),
    priority INTEGER DEFAULT 3 CHECK (priority >= 1 AND priority <= 5),
    missed_date DATE NOT NULL,
    reason TEXT,
    reassigned_to_session_id INTEGER REFERENCES homeschool_sessions(id) ON DELETE SET NULL,
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'reassigned', 'cancelled')),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW()),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW())
);

-- Create review_slots table for daily review time blocks
CREATE TABLE IF NOT EXISTS review_slots (
    id SERIAL PRIMARY KEY,
    child_id INTEGER NOT NULL REFERENCES children(id) ON DELETE CASCADE,
    day_of_week INTEGER NOT NULL CHECK (day_of_week >= 1 AND day_of_week <= 7), -- 1=Monday, 7=Sunday
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    max_reviews INTEGER DEFAULT 5 CHECK (max_reviews >= 1 AND max_reviews <= 20),
    slot_type VARCHAR(20) DEFAULT 'micro' CHECK (slot_type IN ('micro', 'standard')), -- micro=5min, standard=15min
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW()),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW()),
    CONSTRAINT valid_review_time_range CHECK (end_time > start_time)
);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_reviews_child_id ON reviews(child_id);
CREATE INDEX IF NOT EXISTS idx_reviews_due_date ON reviews(due_date);
CREATE INDEX IF NOT EXISTS idx_reviews_status ON reviews(status);
CREATE INDEX IF NOT EXISTS idx_reviews_session_id ON reviews(session_id);

CREATE INDEX IF NOT EXISTS idx_catch_up_sessions_child_id ON catch_up_sessions(child_id);
CREATE INDEX IF NOT EXISTS idx_catch_up_sessions_status ON catch_up_sessions(status);
CREATE INDEX IF NOT EXISTS idx_catch_up_sessions_priority ON catch_up_sessions(priority);
CREATE INDEX IF NOT EXISTS idx_catch_up_sessions_missed_date ON catch_up_sessions(missed_date);

CREATE INDEX IF NOT EXISTS idx_review_slots_child_id ON review_slots(child_id);
CREATE INDEX IF NOT EXISTS idx_review_slots_day_of_week ON review_slots(day_of_week);
CREATE INDEX IF NOT EXISTS idx_review_slots_active ON review_slots(is_active);

-- Enable Row Level Security for new tables
ALTER TABLE reviews ENABLE ROW LEVEL SECURITY;
ALTER TABLE catch_up_sessions ENABLE ROW LEVEL SECURITY;
ALTER TABLE review_slots ENABLE ROW LEVEL SECURITY;

-- RLS Policies for reviews table (access via children ownership)
CREATE POLICY "Users can view own reviews" ON reviews
    FOR SELECT USING (EXISTS (
        SELECT 1 FROM children 
        WHERE children.id = reviews.child_id 
        AND children.user_id = auth.uid()
    ));

CREATE POLICY "Users can insert own reviews" ON reviews
    FOR INSERT WITH CHECK (EXISTS (
        SELECT 1 FROM children 
        WHERE children.id = reviews.child_id 
        AND children.user_id = auth.uid()
    ));

CREATE POLICY "Users can update own reviews" ON reviews
    FOR UPDATE USING (EXISTS (
        SELECT 1 FROM children 
        WHERE children.id = reviews.child_id 
        AND children.user_id = auth.uid()
    ));

CREATE POLICY "Users can delete own reviews" ON reviews
    FOR DELETE USING (EXISTS (
        SELECT 1 FROM children 
        WHERE children.id = reviews.child_id 
        AND children.user_id = auth.uid()
    ));

-- RLS Policies for catch_up_sessions table (access via children ownership)
CREATE POLICY "Users can view own catch_up_sessions" ON catch_up_sessions
    FOR SELECT USING (EXISTS (
        SELECT 1 FROM children 
        WHERE children.id = catch_up_sessions.child_id 
        AND children.user_id = auth.uid()
    ));

CREATE POLICY "Users can insert own catch_up_sessions" ON catch_up_sessions
    FOR INSERT WITH CHECK (EXISTS (
        SELECT 1 FROM children 
        WHERE children.id = catch_up_sessions.child_id 
        AND children.user_id = auth.uid()
    ));

CREATE POLICY "Users can update own catch_up_sessions" ON catch_up_sessions
    FOR UPDATE USING (EXISTS (
        SELECT 1 FROM children 
        WHERE children.id = catch_up_sessions.child_id 
        AND children.user_id = auth.uid()
    ));

CREATE POLICY "Users can delete own catch_up_sessions" ON catch_up_sessions
    FOR DELETE USING (EXISTS (
        SELECT 1 FROM children 
        WHERE children.id = catch_up_sessions.child_id 
        AND children.user_id = auth.uid()
    ));

-- RLS Policies for review_slots table (access via children ownership)
CREATE POLICY "Users can view own review_slots" ON review_slots
    FOR SELECT USING (EXISTS (
        SELECT 1 FROM children 
        WHERE children.id = review_slots.child_id 
        AND children.user_id = auth.uid()
    ));

CREATE POLICY "Users can insert own review_slots" ON review_slots
    FOR INSERT WITH CHECK (EXISTS (
        SELECT 1 FROM children 
        WHERE children.id = review_slots.child_id 
        AND children.user_id = auth.uid()
    ));

CREATE POLICY "Users can update own review_slots" ON review_slots
    FOR UPDATE USING (EXISTS (
        SELECT 1 FROM children 
        WHERE children.id = review_slots.child_id 
        AND children.user_id = auth.uid()
    ));

CREATE POLICY "Users can delete own review_slots" ON review_slots
    FOR DELETE USING (EXISTS (
        SELECT 1 FROM children 
        WHERE children.id = review_slots.child_id 
        AND children.user_id = auth.uid()
    ));

-- Add triggers for updated_at columns
CREATE TRIGGER update_reviews_updated_at BEFORE UPDATE ON reviews
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_catch_up_sessions_updated_at BEFORE UPDATE ON catch_up_sessions
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_review_slots_updated_at BEFORE UPDATE ON review_slots
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Function to automatically create review slots for new children
CREATE OR REPLACE FUNCTION create_default_review_slots()
RETURNS TRIGGER AS $$
BEGIN
    -- Create morning micro-review slots (5min at 8:00 AM)
    INSERT INTO review_slots (child_id, day_of_week, start_time, end_time, max_reviews, slot_type, is_active)
    SELECT 
        NEW.id,
        day_num,
        '08:00:00'::time,
        '08:05:00'::time,
        3,
        'micro',
        true
    FROM (VALUES (1), (2), (3), (4), (5), (6), (7)) AS days(day_num);
    
    -- Create evening micro-review slots (5min at 7:30 PM)
    INSERT INTO review_slots (child_id, day_of_week, start_time, end_time, max_reviews, slot_type, is_active)
    SELECT 
        NEW.id,
        day_num,
        '19:30:00'::time,
        '19:35:00'::time,
        3,
        'micro',
        true
    FROM (VALUES (1), (2), (3), (4), (5), (6), (7)) AS days(day_num);
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Trigger to create review slots when a new child is added
DROP TRIGGER IF EXISTS create_review_slots_for_new_child ON children;
CREATE TRIGGER create_review_slots_for_new_child
    AFTER INSERT ON children
    FOR EACH ROW EXECUTE FUNCTION create_default_review_slots();

-- Function to automatically create review from completed session
CREATE OR REPLACE FUNCTION create_review_from_session()
RETURNS TRIGGER AS $$
BEGIN
    -- Only create review if session is marked as done and doesn't already have one
    IF NEW.status = 'done' AND NEW.completed_at IS NOT NULL AND OLD.status != 'done' THEN
        -- Check if review already exists
        IF NOT EXISTS (SELECT 1 FROM reviews WHERE session_id = NEW.id) THEN
            INSERT INTO reviews (session_id, child_id, topic_id, interval_days, ease_factor, repetitions, status, due_date)
            VALUES (
                NEW.id,
                NEW.child_id,
                NEW.topic_id,
                1, -- Start with 1 day interval
                2.5, -- Default ease factor
                0, -- No repetitions yet
                'new', -- New review
                (NEW.completed_at::date + interval '1 day')::date -- Due tomorrow
            );
        END IF;
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Trigger to automatically create reviews when sessions are completed
DROP TRIGGER IF EXISTS create_review_on_session_completion ON homeschool_sessions;
CREATE TRIGGER create_review_on_session_completion
    AFTER UPDATE ON homeschool_sessions
    FOR EACH ROW EXECUTE FUNCTION create_review_from_session();