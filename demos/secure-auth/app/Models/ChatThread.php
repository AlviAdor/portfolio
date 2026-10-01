<?php
declare(strict_types=1);

class ChatThread
{
    public static function find(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM chat_threads WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function existsForMessage(int $contactMessageId): bool
    {
        $stmt = db()->prepare('SELECT id FROM chat_threads WHERE contact_message_id = ?');
        $stmt->execute([$contactMessageId]);
        return (bool)$stmt->fetch();
    }

    public static function create(int $contactMessageId, int $adminUserId, int $guestUserId): int
    {
        db()->prepare('INSERT INTO chat_threads (contact_message_id, admin_user_id, guest_user_id) VALUES (?, ?, ?)')
            ->execute([$contactMessageId, $adminUserId, $guestUserId]);
        return (int)db()->lastInsertId();
    }

    public static function isParticipant(array $thread, int $userId): bool
    {
        return $userId == $thread['admin_user_id'] || $userId == $thread['guest_user_id'];
    }

    public static function sharedThreadExists(int $userIdA, int $userIdB): bool
    {
        $stmt = db()->prepare(
            'SELECT 1 FROM chat_threads WHERE
             (admin_user_id = ? AND guest_user_id = ?) OR (admin_user_id = ? AND guest_user_id = ?) LIMIT 1'
        );
        $stmt->execute([$userIdA, $userIdB, $userIdB, $userIdA]);
        return (bool)$stmt->fetch();
    }

    // All threads a user belongs to, with the *other* participant's details
    // already picked out via CASE WHEN -- the view never needs to know which
    // side (admin/guest) the current user was on.
    public static function forUserWithPeer(int $userId): array
    {
        $stmt = db()->prepare(
            "SELECT t.id, t.created_at,
                    CASE WHEN t.admin_user_id = ? THEN g.id ELSE a.id END AS peer_id,
                    CASE WHEN t.admin_user_id = ? THEN g.name ELSE a.name END AS peer_name,
                    CASE WHEN t.admin_user_id = ? THEN g.username ELSE a.username END AS peer_username,
                    CASE WHEN t.admin_user_id = ? THEN g.gender ELSE a.gender END AS peer_gender,
                    CASE WHEN t.admin_user_id = ? THEN g.public_key ELSE a.public_key END AS peer_has_key
             FROM chat_threads t
             JOIN users a ON a.id = t.admin_user_id
             JOIN users g ON g.id = t.guest_user_id
             WHERE t.admin_user_id = ? OR t.guest_user_id = ?
             ORDER BY t.created_at DESC"
        );
        $stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId, $userId]);
        return $stmt->fetchAll();
    }
}
