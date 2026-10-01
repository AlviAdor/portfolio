<?php
declare(strict_types=1);

// Standard XAMPP MySQL locally: default socket/port, root with no password --
// the same way the other PHP projects on this machine connect. Override with
// cfg() (env vars, or config/config.local.php on a host with no env support)
// once you've set up a dedicated app user -- see db/optional-app-user.sql --
// or for any real deployment, where root is not an acceptable choice.
const DB_HOST = '';
const DB_NAME = 'secure_auth_demo';
const DB_USER = 'root';
const DB_PASS = '';

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $host = cfg('SAD_DB_HOST') ?: (DB_HOST !== '' ? DB_HOST : 'localhost');
        $user = cfg('SAD_DB_USER') ?: DB_USER;
        $pass = cfg('SAD_DB_PASS') ?: DB_PASS;
        $name = cfg('SAD_DB_NAME') ?: DB_NAME;
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $name);
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false, // real server-side prepared statements
        ]);
    }
    return $pdo;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

const MAX_FAILED_ATTEMPTS = 5;
const LOCKOUT_MINUTES = 15;

// Use the strongest algorithm this PHP build actually supports. Argon2id is
// OWASP's current first choice; if this server's PHP lacks the argon2/sodium
// extension, it falls back to bcrypt -- still OWASP-approved -- and upgrades
// itself automatically the moment Argon2 becomes available, no code change.
function password_algo_and_options(): array
{
    if (defined('PASSWORD_ARGON2ID')) {
        return [PASSWORD_ARGON2ID, ['memory_cost' => 1 << 16, 'time_cost' => 4, 'threads' => 2]];
    }
    return [PASSWORD_BCRYPT, ['cost' => 12]];
}

function hash_password(string $password): string
{
    [$algo, $options] = password_algo_and_options();
    return password_hash($password, $algo, $options);
}

// Any hash this app issued under an older/weaker policy gets silently
// re-hashed with the current one right after a successful login.
function maybe_rehash(string $password, string $hash, int $userId): void
{
    [$algo, $options] = password_algo_and_options();
    if (password_needs_rehash($hash, $algo, $options)) {
        db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([hash_password($password), $userId]);
    }
}
