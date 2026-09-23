-- Migration: 002_oauth_columns.sql
-- Add OAuth provider columns to users table

-- Make password_hash nullable (for OAuth-only users)
ALTER TABLE users MODIFY COLUMN password_hash VARCHAR(255) NULL;

-- Add OAuth provider ID columns
ALTER TABLE users
    ADD COLUMN google_id VARCHAR(255) NULL AFTER password_hash,
    ADD COLUMN facebook_id VARCHAR(255) NULL AFTER google_id,
    ADD COLUMN discord_id VARCHAR(255) NULL AFTER facebook_id;

-- Add unique indexes for OAuth lookups
CREATE UNIQUE INDEX idx_google_id ON users(google_id);
CREATE UNIQUE INDEX idx_facebook_id ON users(facebook_id);
CREATE UNIQUE INDEX idx_discord_id ON users(discord_id);
