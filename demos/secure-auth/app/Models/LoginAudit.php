<?php
declare(strict_types=1);

class LoginAudit
{
    public static function record(?int $userId, string $emailAttempted, bool $success, string $ip): void
    {
        db()->prepare('INSERT INTO login_audit (user_id, email_attempted, success, ip_address) VALUES (?, ?, ?, ?)')
            ->execute([$userId, $emailAttempted, $success ? 1 : 0, $ip]);
    }

    public static function recentForAll(int $limit = 10): array
    {
        $stmt = db()->prepare('SELECT email_attempted, success, ip_address, created_at FROM login_audit ORDER BY created_at DESC LIMIT ?');
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function recentForUser(int $userId, int $limit = 10): array
    {
        $stmt = db()->prepare('SELECT email_attempted, success, ip_address, created_at FROM login_audit WHERE user_id = ? ORDER BY created_at DESC LIMIT ?');
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
