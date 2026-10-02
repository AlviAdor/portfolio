-- Adds sender IP + user-agent to contact_messages, for spam/abuse triage
-- in the admin inbox only -- never the message content, which stays
-- exactly as encrypted as ever. Same plain-column treatment
-- login_audit.ip_address already gets for login attempts. Safe to run
-- against an already-deployed database.

ALTER TABLE contact_messages
    ADD COLUMN IF NOT EXISTS sender_ip VARCHAR(45) NULL AFTER created_at,
    ADD COLUMN IF NOT EXISTS sender_user_agent VARCHAR(255) NULL AFTER sender_ip;
