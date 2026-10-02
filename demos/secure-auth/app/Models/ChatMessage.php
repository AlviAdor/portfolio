<?php
declare(strict_types=1);

class ChatMessage
{
    public static function since(int $threadId, int $sinceId, int $limit = 100): array
    {
        $stmt = db()->prepare(
            'SELECT id, sender_user_id, type, ciphertext, iv, wrapped_key_sender, wrapped_key_recipient, created_at, delivered_at, seen_at
             FROM chat_messages WHERE thread_id = ? AND id > ? ORDER BY id ASC LIMIT ' . (int)$limit
        );
        $stmt->execute([$threadId, $sinceId]);
        return $stmt->fetchAll();
    }

    public static function create(int $threadId, int $senderUserId, string $ciphertext, string $iv, string $wrappedKeySender, string $wrappedKeyRecipient, string $type = 'text'): int
    {
        db()->prepare(
            'INSERT INTO chat_messages (thread_id, sender_user_id, type, ciphertext, iv, wrapped_key_sender, wrapped_key_recipient)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([$threadId, $senderUserId, $type, $ciphertext, $iv, $wrappedKeySender, $wrappedKeyRecipient]);
        return (int)db()->lastInsertId();
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM chat_messages WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // A poll from the recipient IS the message reaching their device -- that's
    // what "delivered" means here, same as any chat app's single-to-double
    // checkmark. Scoped by thread, not by the since() window just fetched, so
    // a message that arrived while the recipient was offline still gets
    // marked the first time they poll at all, not only on its own page.
    public static function markDelivered(int $threadId, int $recipientUserId): void
    {
        db()->prepare(
            'UPDATE chat_messages SET delivered_at = NOW()
             WHERE thread_id = ? AND sender_user_id <> ? AND delivered_at IS NULL'
        )->execute([$threadId, $recipientUserId]);
    }

    // "Seen" is a deliberate signal from the recipient's client (the thread
    // was open and visible), not inferred from polling alone -- marks
    // everything up to a given id, backfilling delivered_at too since seeing
    // a message implies it was delivered.
    public static function markSeen(int $threadId, int $recipientUserId, int $uptoId): void
    {
        db()->prepare(
            'UPDATE chat_messages
             SET seen_at = NOW(), delivered_at = COALESCE(delivered_at, NOW())
             WHERE thread_id = ? AND sender_user_id <> ? AND id <= ? AND seen_at IS NULL'
        )->execute([$threadId, $recipientUserId, $uptoId]);
    }

    // What a sender's own client polls to update the little sent/delivered/
    // seen tick on messages it already rendered. Bounded and self-limiting,
    // but NOT simply "still unresolved" -- a message that both parties
    // reach "seen" on within a second or two (both tabs open and visible,
    // the common case) would otherwise flip straight from unseen-by-sender
    // to fully-resolved between two of the sender's own polls and never get
    // reported at all, leaving its tick stuck on single/double-gray forever
    // even though the server knows better. The grace window below keeps a
    // just-resolved row matching for a little while after seen_at is set,
    // so the sender's next several polls still pick it up -- only rows that
    // settled a while ago (long since reported) actually stop matching.
    public static function pendingReceiptsForSender(int $threadId, int $senderUserId): array
    {
        $stmt = db()->prepare(
            'SELECT id, delivered_at, seen_at FROM chat_messages
             WHERE thread_id = ? AND sender_user_id = ?
               AND (delivered_at IS NULL OR seen_at IS NULL OR seen_at > (NOW() - INTERVAL 15 SECOND))
             ORDER BY id ASC LIMIT 200'
        );
        $stmt->execute([$threadId, $senderUserId]);
        return $stmt->fetchAll();
    }
}
