<?php
declare(strict_types=1);

class ContactMessage
{
    public static function create(string $name, string $email, string $ciphertext, string $iv, string $wrappedKey, ?string $senderIp = null, ?string $senderUserAgent = null): int
    {
        db()->prepare(
            'INSERT INTO contact_messages (sender_name, sender_email, ciphertext, iv, wrapped_key, sender_ip, sender_user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([$name, $email, $ciphertext, $iv, $wrappedKey, $senderIp, $senderUserAgent]);
        return (int)db()->lastInsertId();
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM contact_messages WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function allForInbox(): array
    {
        return db()->query(
            'SELECT id, sender_name, sender_email, ciphertext, iv, wrapped_key, status, created_at, sender_ip, sender_user_agent FROM contact_messages ORDER BY created_at DESC'
        )->fetchAll();
    }

    public static function markRead(int $id): void
    {
        db()->prepare("UPDATE contact_messages SET status = 'read' WHERE id = ? AND status = 'new'")->execute([$id]);
    }

    public static function markInvited(int $id): void
    {
        db()->prepare("UPDATE contact_messages SET status = 'invited' WHERE id = ?")->execute([$id]);
    }
}
