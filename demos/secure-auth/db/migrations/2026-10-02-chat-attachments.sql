-- Adds encrypted file attachments (images and PDFs) to chat. Safe to run
-- against an already-deployed database -- IF NOT EXISTS / nothing destructive,
-- nothing that touches existing rows (existing chat_messages rows implicitly
-- become type='text' via the new column's default, exactly what they already
-- were). A brand-new install from db/setup.sql already includes both of
-- these and doesn't need this file.
--
-- Deliberately no filename/mime_type/size columns on chat_attachments in the
-- clear -- the server validates those at upload time but never persists them
-- outside encrypted storage.

ALTER TABLE chat_messages
    ADD COLUMN IF NOT EXISTS type ENUM('text', 'attachment') NOT NULL DEFAULT 'text' AFTER sender_user_id;

CREATE TABLE IF NOT EXISTS chat_attachments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    message_id INT UNSIGNED NOT NULL UNIQUE,
    ciphertext LONGTEXT NOT NULL,
    iv VARCHAR(32) NOT NULL,
    wrapped_key_sender TEXT NOT NULL,
    wrapped_key_recipient TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_attachment_message FOREIGN KEY (message_id) REFERENCES chat_messages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
