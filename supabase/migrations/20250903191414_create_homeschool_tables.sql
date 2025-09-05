-- Homeschool Learning App Database Schema
-- This script creates tables for managing children, subjects, units, topics, and time blocks

-- Enable UUID extension if not already enabled
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Create children table
CREATE TABLE IF NOT EXISTS children (
    id SERIAL PRIMARY KEY,
    user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    age INTEGER NOT NULL CHECK (age >= 3 AND age <= 25),
    independence_level INTEGER DEFAULT 1 CHECK (independence_level >= 1 AND independence_level <= 4),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW()),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW())
);

-- Create subjects table  
CREATE TABLE IF NOT EXISTS subjects (
    id SERIAL PRIMARY KEY,
    user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    color VARCHAR(7) DEFAULT '#3b82f6' CHECK (color ~ '^#[0-9A-Fa-f]{6}$'),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW()),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW())
);

-- Create units table
CREATE TABLE IF NOT EXISTS units (
    id SERIAL PRIMARY KEY,
    subject_id INTEGER NOT NULL REFERENCES subjects(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    target_completion_date DATE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW()),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW())
);

-- Create topics table
CREATE TABLE IF NOT EXISTS topics (
    id SERIAL PRIMARY KEY,
    unit_id INTEGER NOT NULL REFERENCES units(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    estimated_minutes INTEGER DEFAULT 30 CHECK (estimated_minutes > 0 AND estimated_minutes <= 480),
    prerequisites INTEGER[] DEFAULT '{}',
    required BOOLEAN DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW()),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW())
);

-- Create time_blocks table for weekly calendar scheduling
CREATE TABLE IF NOT EXISTS time_blocks (
    id SERIAL PRIMARY KEY,
    child_id INTEGER NOT NULL REFERENCES children(id) ON DELETE CASCADE,
    day_of_week INTEGER NOT NULL CHECK (day_of_week >= 1 AND day_of_week <= 7), -- 1=Monday, 7=Sunday
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    label VARCHAR(255) NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW()),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW()),
    CONSTRAINT valid_time_range CHECK (end_time > start_time)
);

-- Create sessions table for topic planning and scheduling (renamed to homeschool_sessions to avoid conflict with auth.sessions)
CREATE TABLE IF NOT EXISTS homeschool_sessions (
    id SERIAL PRIMARY KEY,
    topic_id INTEGER NOT NULL REFERENCES topics(id) ON DELETE CASCADE,
    child_id INTEGER NOT NULL REFERENCES children(id) ON DELETE CASCADE,
    estimated_minutes INTEGER NOT NULL CHECK (estimated_minutes > 0 AND estimated_minutes <= 480),
    status VARCHAR(20) DEFAULT 'backlog' CHECK (status IN ('backlog', 'planned', 'scheduled', 'done')),
    scheduled_day_of_week INTEGER CHECK (scheduled_day_of_week >= 1 AND scheduled_day_of_week <= 7),
    scheduled_start_time TIME,
    scheduled_end_time TIME,
    scheduled_date DATE,
    notes TEXT,
    completed_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW()),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW()),
    CONSTRAINT valid_scheduled_time_range CHECK (
        (scheduled_start_time IS NULL AND scheduled_end_time IS NULL) OR 
        (scheduled_start_time IS NOT NULL AND scheduled_end_time IS NOT NULL AND scheduled_end_time > scheduled_start_time)
    ),
    CONSTRAINT scheduled_fields_consistency CHECK (
        (status = 'scheduled' AND scheduled_day_of_week IS NOT NULL AND scheduled_start_time IS NOT NULL AND scheduled_end_time IS NOT NULL) OR
        (status != 'scheduled')
    )
);

-- Create a view named 'sessions' to maintain API compatibility
-- Note: sessions table already exists from Laravel migrations, so we comment this out
-- CREATE VIEW sessions AS SELECT * FROM homeschool_sessions;

-- Create indexes for better performance
CREATE INDEX idx_children_user_id ON children(user_id);
CREATE INDEX idx_subjects_user_id ON subjects(user_id);
CREATE INDEX idx_units_subject_id ON units(subject_id);
CREATE INDEX idx_topics_unit_id ON topics(unit_id);
CREATE INDEX idx_time_blocks_child_id ON time_blocks(child_id);
CREATE INDEX idx_time_blocks_day_of_week ON time_blocks(day_of_week);
CREATE INDEX idx_homeschool_sessions_topic_id ON homeschool_sessions(topic_id);
CREATE INDEX idx_homeschool_sessions_child_id ON homeschool_sessions(child_id);
CREATE INDEX idx_homeschool_sessions_status ON homeschool_sessions(status);
CREATE INDEX idx_homeschool_sessions_scheduled_day ON homeschool_sessions(scheduled_day_of_week);

-- Enable Row Level Security
ALTER TABLE children ENABLE ROW LEVEL SECURITY;
ALTER TABLE subjects ENABLE ROW LEVEL SECURITY;
ALTER TABLE units ENABLE ROW LEVEL SECURITY;
ALTER TABLE topics ENABLE ROW LEVEL SECURITY;
ALTER TABLE time_blocks ENABLE ROW LEVEL SECURITY;
ALTER TABLE homeschool_sessions ENABLE ROW LEVEL SECURITY;

-- RLS Policies for children table
CREATE POLICY "Users can view own children" ON children
    FOR SELECT USING (auth.uid() = user_id);

CREATE POLICY "Users can insert own children" ON children
    FOR INSERT WITH CHECK (auth.uid() = user_id);

CREATE POLICY "Users can update own children" ON children
    FOR UPDATE USING (auth.uid() = user_id);

CREATE POLICY "Users can delete own children" ON children
    FOR DELETE USING (auth.uid() = user_id);

-- RLS Policies for subjects table
CREATE POLICY "Users can view own subjects" ON subjects
    FOR SELECT USING (auth.uid() = user_id);

CREATE POLICY "Users can insert own subjects" ON subjects
    FOR INSERT WITH CHECK (auth.uid() = user_id);

CREATE POLICY "Users can update own subjects" ON subjects
    FOR UPDATE USING (auth.uid() = user_id);

CREATE POLICY "Users can delete own subjects" ON subjects
    FOR DELETE USING (auth.uid() = user_id);

-- RLS Policies for units table (access via subjects ownership)
CREATE POLICY "Users can view own units" ON units
    FOR SELECT USING (EXISTS (
        SELECT 1 FROM subjects 
        WHERE subjects.id = units.subject_id 
        AND subjects.user_id = auth.uid()
    ));

CREATE POLICY "Users can insert own units" ON units
    FOR INSERT WITH CHECK (EXISTS (
        SELECT 1 FROM subjects 
        WHERE subjects.id = units.subject_id 
        AND subjects.user_id = auth.uid()
    ));

CREATE POLICY "Users can update own units" ON units
    FOR UPDATE USING (EXISTS (
        SELECT 1 FROM subjects 
        WHERE subjects.id = units.subject_id 
        AND subjects.user_id = auth.uid()
    ));

CREATE POLICY "Users can delete own units" ON units
    FOR DELETE USING (EXISTS (
        SELECT 1 FROM subjects 
        WHERE subjects.id = units.subject_id 
        AND subjects.user_id = auth.uid()
    ));

-- RLS Policies for topics table (access via units/subjects ownership)
CREATE POLICY "Users can view own topics" ON topics
    FOR SELECT USING (EXISTS (
        SELECT 1 FROM units u
        JOIN subjects s ON s.id = u.subject_id
        WHERE u.id = topics.unit_id 
        AND s.user_id = auth.uid()
    ));

CREATE POLICY "Users can insert own topics" ON topics
    FOR INSERT WITH CHECK (EXISTS (
        SELECT 1 FROM units u
        JOIN subjects s ON s.id = u.subject_id
        WHERE u.id = topics.unit_id 
        AND s.user_id = auth.uid()
    ));

CREATE POLICY "Users can update own topics" ON topics
    FOR UPDATE USING (EXISTS (
        SELECT 1 FROM units u
        JOIN subjects s ON s.id = u.subject_id
        WHERE u.id = topics.unit_id 
        AND s.user_id = auth.uid()
    ));

CREATE POLICY "Users can delete own topics" ON topics
    FOR DELETE USING (EXISTS (
        SELECT 1 FROM units u
        JOIN subjects s ON s.id = u.subject_id
        WHERE u.id = topics.unit_id 
        AND s.user_id = auth.uid()
    ));

-- RLS Policies for time_blocks table (access via children ownership)
CREATE POLICY "Users can view own time_blocks" ON time_blocks
    FOR SELECT USING (EXISTS (
        SELECT 1 FROM children 
        WHERE children.id = time_blocks.child_id 
        AND children.user_id = auth.uid()
    ));

CREATE POLICY "Users can insert own time_blocks" ON time_blocks
    FOR INSERT WITH CHECK (EXISTS (
        SELECT 1 FROM children 
        WHERE children.id = time_blocks.child_id 
        AND children.user_id = auth.uid()
    ));

CREATE POLICY "Users can update own time_blocks" ON time_blocks
    FOR UPDATE USING (EXISTS (
        SELECT 1 FROM children 
        WHERE children.id = time_blocks.child_id 
        AND children.user_id = auth.uid()
    ));

CREATE POLICY "Users can delete own time_blocks" ON time_blocks
    FOR DELETE USING (EXISTS (
        SELECT 1 FROM children 
        WHERE children.id = time_blocks.child_id 
        AND children.user_id = auth.uid()
    ));

-- RLS Policies for homeschool_sessions table (access via children ownership)
CREATE POLICY "Users can view own sessions" ON homeschool_sessions
    FOR SELECT USING (EXISTS (
        SELECT 1 FROM children 
        WHERE children.id = homeschool_sessions.child_id 
        AND children.user_id = auth.uid()
    ));

CREATE POLICY "Users can insert own sessions" ON homeschool_sessions
    FOR INSERT WITH CHECK (EXISTS (
        SELECT 1 FROM children 
        WHERE children.id = homeschool_sessions.child_id 
        AND children.user_id = auth.uid()
    ));

CREATE POLICY "Users can update own sessions" ON homeschool_sessions
    FOR UPDATE USING (EXISTS (
        SELECT 1 FROM children 
        WHERE children.id = homeschool_sessions.child_id 
        AND children.user_id = auth.uid()
    ));

CREATE POLICY "Users can delete own sessions" ON homeschool_sessions
    FOR DELETE USING (EXISTS (
        SELECT 1 FROM children 
        WHERE children.id = homeschool_sessions.child_id 
        AND children.user_id = auth.uid()
    ));

-- Create function for updating updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = TIMEZONE('utc', NOW());
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Add triggers for updated_at columns
CREATE TRIGGER update_children_updated_at BEFORE UPDATE ON children
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_subjects_updated_at BEFORE UPDATE ON subjects
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_units_updated_at BEFORE UPDATE ON units
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_topics_updated_at BEFORE UPDATE ON topics
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_time_blocks_updated_at BEFORE UPDATE ON time_blocks
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_homeschool_sessions_updated_at BEFORE UPDATE ON homeschool_sessions
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
