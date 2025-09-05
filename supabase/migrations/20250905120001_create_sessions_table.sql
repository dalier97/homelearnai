-- Create Laravel sessions table compatible with PostgreSQL
-- This replaces any existing sessions table with the proper Laravel format

-- Drop the table if it exists (sessions is a table, not a view)
DROP TABLE IF EXISTS sessions;

CREATE TABLE sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id UUID,
    ip_address INET,
    user_agent TEXT,
    payload TEXT NOT NULL,
    last_activity INTEGER NOT NULL
);

-- Create index for better performance
CREATE INDEX idx_sessions_user_id ON sessions(user_id);
CREATE INDEX idx_sessions_last_activity ON sessions(last_activity);

-- Recreate the view that was dropped to maintain compatibility with homeschool sessions
CREATE VIEW homeschool_sessions_view AS SELECT * FROM homeschool_sessions;