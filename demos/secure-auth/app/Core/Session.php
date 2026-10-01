<?php
declare(strict_types=1);

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(): bool
{
    $submitted = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $expected = $_SESSION['csrf_token'] ?? '';
    return $submitted !== '' && $expected !== '' && hash_equals($expected, $submitted);
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) return null;
    static $cache = null;
    if ($cache !== null) return $cache;
    $stmt = db()->prepare('SELECT id, name, email, role, public_key, must_change_password FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user) return null;
    $cache = $user;
    return $user;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        json_fail('Not signed in.', 401);
    }
    return $user;
}

function require_admin(): array
{
    $user = require_login();
    if ($user['role'] !== 'admin') {
        json_fail('Admins only.', 403);
    }
    return $user;
}

function json_fail(string $message, int $code = 400): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => $message]);
    exit;
}

function json_ok(array $data = []): void
{
    header('Content-Type: application/json');
    echo json_encode(['ok' => true] + $data);
    exit;
}
