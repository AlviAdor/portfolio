<?php
declare(strict_types=1);

class CallSignal
{
    public static function create(int $threadId, int $senderUserId, string $type, string $ciphertext, string $iv, string $wrappedKey): int
    {
        db()->prepare(
            'INSERT INTO call_signals (thread_id, sender_user_id, type, ciphertext, iv, wrapped_key) VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([$threadId, $senderUserId, $type, $ciphertext, $iv, $wrappedKey]);
        return (int)db()->lastInsertId();
    }

    public static function since(int $threadId, int $sinceId, int $limit = 100): array
    {
        $stmt = db()->prepare(
            'SELECT id, sender_user_id, type, ciphertext, iv, wrapped_key, created_at
             FROM call_signals WHERE thread_id = ? AND id > ? ORDER BY id ASC LIMIT ' . (int)$limit
        );
        $stmt->execute([$threadId, $sinceId]);
        return $stmt->fetchAll();
    }
}
