-- Secure Auth Demo -- table setup.
--
-- How to run this with phpMyAdmin (the easy way):
--   1. Open phpMyAdmin (http://localhost/phpmyadmin), click "New" in the left
--      sidebar, name the database "secure_auth_demo", set collation to
--      utf8mb4_unicode_ci, click Create.
--   2. Click into the new "secure_auth_demo" database, open its "Import" tab,
--      choose this file, and go. (Or open its "SQL" tab and paste this in.)
--
-- Or from the command line, with MySQL running:
--   /Applications/XAMPP/xamppfiles/bin/mysql -u root -e "CREATE DATABASE secure_auth_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
--   /Applications/XAMPP/xamppfiles/bin/mysql -u root secure_auth_demo < db/setup.sql
--
-- This file is just CREATE TABLE statements -- no CREATE USER, no GRANT, no
-- FLUSH PRIVILEGES. Those need server-admin privileges phpMyAdmin's import
-- doesn't always have, and they're not needed for local dev: the app connects
-- as root by default, the same way the other PHP projects on this machine do.
-- If you want a dedicated low-privilege database user later, see
-- db/optional-app-user.sql -- that's worth doing before a real deployment,
-- not before your first test run.

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    username VARCHAR(30) NOT NULL UNIQUE,
    email VARCHAR(190) NOT NULL UNIQUE,
    gender ENUM('male', 'female', 'unspecified') NOT NULL DEFAULT 'unspecified',
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('member', 'admin') NOT NULL DEFAULT 'member',
    public_key TEXT NULL,
    must_change_password TINYINT(1) NOT NULL DEFAULT 0,
    failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_audit (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    email_attempted VARCHAR(190) NOT NULL,
    success TINYINT(1) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_login_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Messages submitted through the public contact form. Encrypted in the visitor's
-- browser with the admin's public key (RSA-OAEP + AES-256-GCM) -- only the admin's
-- own browser, unlocked with their password, can ever decrypt one.
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_name VARCHAR(80) NOT NULL,
    sender_email VARCHAR(190) NOT NULL,
    ciphertext MEDIUMTEXT NOT NULL,
    iv VARCHAR(32) NOT NULL,
    wrapped_key TEXT NOT NULL,
    status ENUM('new', 'read', 'invited') NOT NULL DEFAULT 'new',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_threads (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contact_message_id INT UNSIGNED NOT NULL,
    admin_user_id INT UNSIGNED NOT NULL,
    guest_user_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_thread_message FOREIGN KEY (contact_message_id) REFERENCES contact_messages(id) ON DELETE CASCADE,
    CONSTRAINT fk_thread_admin FOREIGN KEY (admin_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_thread_guest FOREIGN KEY (guest_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Each message is encrypted once per recipient (including the sender, so they can
-- re-read their own sent messages) -- two wrapped copies of one AES key, one ciphertext.
-- type='attachment' rows carry an encrypted *descriptor* (filename, mime type,
-- size) in these same ciphertext/iv/wrapped_key_* columns -- the actual file
-- bytes live in chat_attachments below, fetched and decrypted only when the
-- recipient chooses to open it, not pre-loaded for every message in the log.
CREATE TABLE IF NOT EXISTS chat_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    thread_id INT UNSIGNED NOT NULL,
    sender_user_id INT UNSIGNED NOT NULL,
    type ENUM('text', 'attachment', 'call_log') NOT NULL DEFAULT 'text',
    ciphertext MEDIUMTEXT NOT NULL,
    iv VARCHAR(32) NOT NULL,
    wrapped_key_sender TEXT NOT NULL,
    wrapped_key_recipient TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- Delivery/read receipts -- plain timestamps, not encrypted: WHEN a
    -- message reached or was seen on a device is no more sensitive than
    -- created_at already is, and knowing it lets a sender's own bubble show
    -- sent/delivered/seen without the server ever touching the message
    -- content itself.
    delivered_at TIMESTAMP NULL DEFAULT NULL,
    seen_at TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT fk_chatmsg_thread FOREIGN KEY (thread_id) REFERENCES chat_threads(id) ON DELETE CASCADE,
    CONSTRAINT fk_chatmsg_sender FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The actual file for an attachment-type chat_messages row, one-to-one via
-- message_id. Encrypted the same way the message descriptor pointing at it
-- is -- RSA-OAEP-wrapped AES-256-GCM, dual-wrapped sender+recipient -- so an
-- attachment is exactly as unreadable to this server as message text is.
-- Images and PDFs only, size-capped client *and* server side (see
-- ApiController::chatAttachmentSend) to stay well under typical shared-
-- hosting upload limits. Deliberately no filename/mime_type/size columns
-- here in the clear -- the server validates those at upload time but never
-- persists them outside encrypted storage; a DB dump should tell you
-- nothing about an attachment beyond "a file exists," not its name or type.
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

-- WebRTC signaling for voice/video calls -- offers, answers, ICE candidates,
-- and hangups, polled the same way chat_messages is. Encrypted the same way
-- chat messages are, too (RSA-OAEP-wrapped AES-256-GCM, recipient's key) --
-- the server relays ciphertext and can't read call setup details any more
-- than it can read a chat message. Once two browsers connect, audio/video
-- flows directly peer-to-peer (DTLS-SRTP, mandatory in the WebRTC spec) and
-- never touches this table or this server again.
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
