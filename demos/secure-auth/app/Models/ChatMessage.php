<?php
declare(strict_types=1);

class ChatMessage
{
    public static function since(int $threadId, int $sinceId, int $limit = 100): array
    {
        $stmt = db()->prepare(
            'SELECT id, sender_user_id, ciphertext, iv, wrapped_key_sender, wrapped_key_recipient, created_at
             FROM chat_messages WHERE thread_id = ? AND id > ? ORDER BY id ASC LIMIT ' . (int)$limit
        );
        $stmt->execute([$threadId, $sinceId]);
        return $stmt->fetchAll();
    }

    public static function create(int $threadId, int $senderUserId, string $ciphertext, string $iv, string $wrappedKeySender, string $wrappedKeyRecipient): int
    {
        db()->prepare(
            'INSERT INTO chat_messages (thread_id, sender_user_id, ciphertext, iv, wrapped_key_sender, wrapped_key_recipient)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([$threadId, $senderUserId, $ciphertext, $iv, $wrappedKeySender, $wrappedKeyRecipient]);
        return (int)db()->lastInsertId();
    }
}
