-- Add child_id to subjects table to make subjects child-specific
-- This migration enables parent management of subjects per child

-- Add child_id column as nullable foreign key
ALTER TABLE subjects ADD COLUMN child_id INTEGER;

-- Add foreign key constraint
ALTER TABLE subjects ADD CONSTRAINT subjects_child_id_fkey 
  FOREIGN KEY (child_id) REFERENCES children(id) ON DELETE CASCADE;

-- Add index for performance
CREATE INDEX idx_subjects_child_id ON subjects(child_id);

-- Update RLS policies to support both user-level and child-level access
-- Drop existing policies first
DROP POLICY "Users can view own subjects" ON subjects;
DROP POLICY "Users can insert own subjects" ON subjects;
DROP POLICY "Users can update own subjects" ON subjects;
DROP POLICY "Users can delete own subjects" ON subjects;

-- Create new policies that handle both user-level (child_id IS NULL) and child-specific subjects
CREATE POLICY "Users can view own subjects" ON subjects
    FOR SELECT USING (
        auth.uid() = user_id AND 
        (child_id IS NULL OR EXISTS (
            SELECT 1 FROM children 
            WHERE children.id = subjects.child_id 
            AND children.user_id = auth.uid()
        ))
    );

CREATE POLICY "Users can insert own subjects" ON subjects
    FOR INSERT WITH CHECK (
        auth.uid() = user_id AND 
        (child_id IS NULL OR EXISTS (
            SELECT 1 FROM children 
            WHERE children.id = subjects.child_id 
            AND children.user_id = auth.uid()
        ))
    );

CREATE POLICY "Users can update own subjects" ON subjects
    FOR UPDATE USING (
        auth.uid() = user_id AND 
        (child_id IS NULL OR EXISTS (
            SELECT 1 FROM children 
            WHERE children.id = subjects.child_id 
            AND children.user_id = auth.uid()
        ))
    );

CREATE POLICY "Users can delete own subjects" ON subjects
    FOR DELETE USING (
        auth.uid() = user_id AND 
        (child_id IS NULL OR EXISTS (
            SELECT 1 FROM children 
            WHERE children.id = subjects.child_id 
            AND children.user_id = auth.uid()
        ))
    );