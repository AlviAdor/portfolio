<?php
declare(strict_types=1);

class User
{
    public static function findById(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function emailExists(string $email): bool
    {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        return (bool)$stmt->fetch();
    }

    public static function usernameTaken(string $username, ?int $excludingId = null): bool
    {
        if ($excludingId !== null) {
            $stmt = db()->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
            $stmt->execute([$username, $excludingId]);
        } else {
            $stmt = db()->prepare('SELECT id FROM users WHERE username = ?');
            $stmt->execute([$username]);
        }
        return (bool)$stmt->fetch();
    }

    public static function count(): int
    {
        return (int)db()->query('SELECT COUNT(*) AS c FROM users')->fetch()['c'];
    }

    public static function create(string $name, string $username, string $email, string $gender, string $passwordHash, string $role, string $publicKey): int
    {
        db()->prepare(
            'INSERT INTO users (name, username, email, gender, password_hash, role, public_key) VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([$name, $username, $email, $gender, $passwordHash, $role, $publicKey]);
        return (int)db()->lastInsertId();
    }

    // Used by the invite flow: creates a guest account with a placeholder
    // username (the UNIQUE NOT NULL constraint has to be satisfied at INSERT
    // time) and a temporary password; the guest picks their real username on
    // their forced first login via set-password.php.
    public static function createInvitedGuest(string $name, string $email, string $tempPasswordHash): int
    {
        $pdo = db();
        $usernameBase = preg_replace('/[^a-zA-Z0-9_]/', '', explode('@', $email)[0]) ?: 'guest';
        $usernameBase = substr($usernameBase, 0, 14);
        do {
            $candidate = $usernameBase . '_' . substr(bin2hex(random_bytes(3)), 0, 5);
        } while (self::usernameTaken($candidate));

        $pdo->prepare('INSERT INTO users (name, username, email, password_hash, role, must_change_password) VALUES (?, ?, ?, ?, ?, 1)')
            ->execute([$name, $candidate, $email, $tempPasswordHash, 'member']);
        return (int)$pdo->lastInsertId();
    }

    public static function resetToTempPassword(int $userId, string $tempPasswordHash): void
    {
        db()->prepare('UPDATE users SET password_hash = ?, must_change_password = 1, failed_attempts = 0, locked_until = NULL WHERE id = ?')
            ->execute([$tempPasswordHash, $userId]);
    }

    public static function clearLoginFailures(int $userId): void
    {
        db()->prepare('UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = ?')
            ->execute([$userId]);
    }

    public static function recordFailedAttempt(int $userId, int $attempts, ?string $lockUntil): void
    {
        db()->prepare('UPDATE users SET failed_attempts = ?, locked_until = ? WHERE id = ?')
            ->execute([$attempts, $lockUntil, $userId]);
    }

    public static function completeSetup(int $userId, string $username, string $gender, string $passwordHash, string $publicKey): void
    {
        db()->prepare('UPDATE users SET username = ?, gender = ?, password_hash = ?, public_key = ?, must_change_password = 0 WHERE id = ?')
            ->execute([$username, $gender, $passwordHash, $publicKey, $userId]);
    }

    public static function savePublicKey(int $userId, string $publicKey): void
    {
        db()->prepare('UPDATE users SET public_key = ? WHERE id = ?')->execute([$publicKey, $userId]);
    }

    public static function firstAdminWithKey(): ?array
    {
        $stmt = db()->query("SELECT public_key FROM users WHERE role = 'admin' AND public_key IS NOT NULL ORDER BY id ASC LIMIT 1");
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function firstAdmin(): ?array
    {
        $row = db()->query("SELECT name, email FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1")->fetch();
        return $row ?: null;
    }

    public static function profileFields(int $id): ?array
    {
        $stmt = db()->prepare('SELECT id, name, username, gender, role, created_at FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
