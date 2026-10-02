<?php
declare(strict_types=1);

class ChatAttachment
{
    public static function create(int $messageId, string $ciphertext, string $iv, string $wrappedKeySender, string $wrappedKeyRecipient): int
    {
        db()->prepare(
            'INSERT INTO chat_attachments (message_id, ciphertext, iv, wrapped_key_sender, wrapped_key_recipient)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([$messageId, $ciphertext, $iv, $wrappedKeySender, $wrappedKeyRecipient]);
        return (int)db()->lastInsertId();
    }

    public static function findByMessageId(int $messageId): ?array
    {
        $stmt = db()->prepare('SELECT * FROM chat_attachments WHERE message_id = ?');
        $stmt->execute([$messageId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
