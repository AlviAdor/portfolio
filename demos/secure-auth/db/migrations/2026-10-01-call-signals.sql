-- Adds voice/video calling, end-to-end encrypted signaling included. Safe to
-- run against an already-deployed database -- CREATE TABLE IF NOT EXISTS,
-- nothing destructive, nothing that touches existing data. Run this once via
-- phpMyAdmin's SQL tab on any database that was set up before this migration
-- existed; a brand-new install from db/setup.sql already includes this table
-- and doesn't need it.

CREATE TABLE IF NOT EXISTS call_signals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    thread_id INT UNSIGNED NOT NULL,
    sender_user_id INT UNSIGNED NOT NULL,
    type ENUM('offer', 'answer', 'ice', 'hangup') NOT NULL,
    ciphertext MEDIUMTEXT NOT NULL,
    iv VARCHAR(32) NOT NULL,
    wrapped_key TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_callsignal_thread FOREIGN KEY (thread_id) REFERENCES chat_threads(id) ON DELETE CASCADE,
    CONSTRAINT fk_callsignal_sender FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
