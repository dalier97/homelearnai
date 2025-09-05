-- Milestone 3: Flexible Scheduling Engine - Database Schema Updates
-- This migration adds support for commitment types, catch-up sessions, and unit progress tracking

-- Add commitment_type field to sessions table
ALTER TABLE sessions 
ADD COLUMN IF NOT EXISTS commitment_type VARCHAR(20) DEFAULT 'preferred' 
CHECK (commitment_type IN ('fixed', 'preferred', 'flexible'));

-- Add skipped_from_date field to track when a session was skipped from
ALTER TABLE sessions 
ADD COLUMN IF NOT EXISTS skipped_from_date DATE;

-- Create catch_up_sessions table for tracking missed items that need rescheduling
CREATE TABLE IF NOT EXISTS catch_up_sessions (
    id SERIAL PRIMARY KEY,
    original_session_id INTEGER NOT NULL REFERENCES sessions(id) ON DELETE CASCADE,
    child_id INTEGER NOT NULL REFERENCES children(id) ON DELETE CASCADE,
    topic_id INTEGER NOT NULL REFERENCES topics(id) ON DELETE CASCADE,
    estimated_minutes INTEGER NOT NULL CHECK (estimated_minutes > 0 AND estimated_minutes <= 480),
    priority INTEGER DEFAULT 1 CHECK (priority >= 1 AND priority <= 5),
    missed_date DATE NOT NULL,
    reason VARCHAR(255),
    reassigned_to_session_id INTEGER REFERENCES sessions(id) ON DELETE SET NULL,
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'reassigned', 'completed', 'cancelled')),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW()),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW())
);

-- Add progress tracking fields to units table
ALTER TABLE units 
ADD COLUMN IF NOT EXISTS completed_topics_count INTEGER DEFAULT 0,
ADD COLUMN IF NOT EXISTS total_topics_count INTEGER DEFAULT 0,
ADD COLUMN IF NOT EXISTS completion_percentage NUMERIC(5,2) DEFAULT 0.00 CHECK (completion_percentage >= 0 AND completion_percentage <= 100),
ADD COLUMN IF NOT EXISTS can_complete BOOLEAN DEFAULT true;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_sessions_commitment_type ON sessions(commitment_type);
CREATE INDEX IF NOT EXISTS idx_sessions_skipped_from_date ON sessions(skipped_from_date);
CREATE INDEX IF NOT EXISTS idx_catch_up_sessions_child_id ON catch_up_sessions(child_id);
CREATE INDEX IF NOT EXISTS idx_catch_up_sessions_status ON catch_up_sessions(status);
CREATE INDEX IF NOT EXISTS idx_catch_up_sessions_priority ON catch_up_sessions(priority);
CREATE INDEX IF NOT EXISTS idx_catch_up_sessions_missed_date ON catch_up_sessions(missed_date);
CREATE INDEX IF NOT EXISTS idx_units_completion ON units(completion_percentage);

-- Enable Row Level Security for catch_up_sessions
ALTER TABLE catch_up_sessions ENABLE ROW LEVEL SECURITY;

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

-- Add trigger for updated_at column on catch_up_sessions
CREATE TRIGGER update_catch_up_sessions_updated_at BEFORE UPDATE ON catch_up_sessions
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Function to update unit progress when sessions are completed
CREATE OR REPLACE FUNCTION update_unit_progress()
RETURNS TRIGGER AS $$
BEGIN
    -- Update the unit's progress when a session is marked as done or changed from done
    IF (TG_OP = 'UPDATE' AND OLD.status != NEW.status) OR TG_OP = 'INSERT' THEN
        -- Get the unit_id from the topic
        UPDATE units SET
            completed_topics_count = (
                SELECT COUNT(DISTINCT s.topic_id)
                FROM sessions s
                JOIN topics t ON t.id = s.topic_id
                WHERE t.unit_id = (
                    SELECT unit_id FROM topics WHERE id = NEW.topic_id
                )
                AND s.child_id = NEW.child_id
                AND s.status = 'done'
            ),
            total_topics_count = (
                SELECT COUNT(*)
                FROM topics t
                WHERE t.unit_id = (
                    SELECT unit_id FROM topics WHERE id = NEW.topic_id
                )
                AND t.required = true
            )
        WHERE id = (
            SELECT unit_id FROM topics WHERE id = NEW.topic_id
        );
        
        -- Update completion percentage
        UPDATE units SET
            completion_percentage = CASE 
                WHEN total_topics_count > 0 THEN 
                    ROUND((completed_topics_count::NUMERIC / total_topics_count::NUMERIC) * 100, 2)
                ELSE 0
            END,
            can_complete = (completed_topics_count >= total_topics_count)
        WHERE id = (
            SELECT unit_id FROM topics WHERE id = NEW.topic_id
        );
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Create trigger for automatic unit progress updates
DROP TRIGGER IF EXISTS trigger_update_unit_progress ON sessions;
CREATE TRIGGER trigger_update_unit_progress
    AFTER INSERT OR UPDATE ON sessions
    FOR EACH ROW
    EXECUTE FUNCTION update_unit_progress();

-- Function to recalculate all unit progress (can be called manually if needed)
CREATE OR REPLACE FUNCTION recalculate_all_unit_progress()
RETURNS void AS $$
BEGIN
    UPDATE units SET
        total_topics_count = (
            SELECT COUNT(*)
            FROM topics t
            WHERE t.unit_id = units.id
            AND t.required = true
        ),
        completed_topics_count = 0,
        completion_percentage = 0,
        can_complete = false;
        
    -- Update completed counts per child
    UPDATE units SET
        completed_topics_count = subq.completed_count,
        completion_percentage = CASE 
            WHEN units.total_topics_count > 0 THEN 
                ROUND((subq.completed_count::NUMERIC / units.total_topics_count::NUMERIC) * 100, 2)
            ELSE 0
        END,
        can_complete = (subq.completed_count >= units.total_topics_count)
    FROM (
        SELECT 
            t.unit_id,
            COUNT(DISTINCT s.topic_id) as completed_count
        FROM sessions s
        JOIN topics t ON t.id = s.topic_id
        WHERE s.status = 'done'
        GROUP BY t.unit_id
    ) subq
    WHERE units.id = subq.unit_id;
END;
$$ LANGUAGE plpgsql;

-- Run initial calculation of unit progress
SELECT recalculate_all_unit_progress();

-- Create a view for easy catch-up session queries
CREATE OR REPLACE VIEW catch_up_sessions_with_details AS
SELECT 
    cus.*,
    s.status as original_session_status,
    t.title as topic_title,
    t.estimated_minutes as topic_estimated_minutes,
    u.name as unit_name,
    sub.name as subject_name,
    sub.color as subject_color,
    c.name as child_name
FROM catch_up_sessions cus
JOIN sessions s ON s.id = cus.original_session_id
JOIN topics t ON t.id = cus.topic_id
JOIN units u ON u.id = t.unit_id
JOIN subjects sub ON sub.id = u.subject_id
JOIN children c ON c.id = cus.child_id;

-- Add comments for documentation
COMMENT ON COLUMN sessions.commitment_type IS 'Type of commitment: fixed (cannot be moved), preferred (should not be moved unless necessary), flexible (can be moved easily)';
COMMENT ON COLUMN sessions.skipped_from_date IS 'Original date when this session was skipped, used for rescheduling logic';
COMMENT ON TABLE catch_up_sessions IS 'Tracks sessions that were missed and need to be rescheduled';
COMMENT ON COLUMN catch_up_sessions.priority IS 'Priority level for catch-up (1=highest, 5=lowest)';
COMMENT ON COLUMN units.completion_percentage IS 'Percentage of required topics completed for this unit';
COMMENT ON COLUMN units.can_complete IS 'Whether unit has met completion requirements (all required topics done)';