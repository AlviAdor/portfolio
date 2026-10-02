-- Adds message delivery/read receipts (sent -> delivered -> seen) and a new
-- 'call_log' message type for in-chat call history entries (duration,
-- outcome). Safe to run against an already-deployed database.
--
-- delivered_at/seen_at are plain timestamps, not encrypted -- WHEN a message
-- reached or was seen on a device is no more sensitive than the existing
-- created_at column already is. A call_log row's actual content (call type,
-- outcome, duration) IS encrypted, the same dual-wrap scheme as a text
-- message's content -- this migration only adds 'call_log' as a value the
-- existing type column can hold.

ALTER TABLE chat_messages
    MODIFY COLUMN type ENUM('text', 'attachment', 'call_log') NOT NULL DEFAULT 'text';

ALTER TABLE chat_messages
    ADD COLUMN IF NOT EXISTS delivered_at TIMESTAMP NULL DEFAULT NULL AFTER created_at,
    ADD COLUMN IF NOT EXISTS seen_at TIMESTAMP NULL DEFAULT NULL AFTER delivered_at;
