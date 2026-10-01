<?php
declare(strict_types=1);

// Every request -- page or API action -- starts here. Marks the app as
// "booted" so app/ files can refuse to run if someone requests them directly.
define('SAD_APP', true);

require __DIR__ . '/../app/Core/Config.php';
require __DIR__ . '/../app/Core/SecurityHeaders.php';
require __DIR__ . '/../app/Core/Database.php';
require __DIR__ . '/../app/Core/Session.php';
require __DIR__ . '/../app/Core/Captcha.php';
require __DIR__ . '/../app/Core/Mailer.php';
require __DIR__ . '/../app/Core/View.php';

// One class per file, named exactly after the class -- Models and
// Controllers load on first use instead of every entry point having to
// remember which ones it needs.
spl_autoload_register(function (string $class): void {
    foreach (['Models', 'Controllers'] as $kind) {
        $path = __DIR__ . "/../app/{$kind}/{$class}.php";
        if (is_file($path)) {
            require $path;
            return;
        }
    }
});

send_security_headers();

// A broken database (or any other uncaught error) should never produce a
// blank page -- that's a dead end for a visitor and gives no clue what went
// wrong. The real cause goes to the server's error log; everyone else gets
// a plain, on-brand message instead of white-screen silence.
set_exception_handler(function (Throwable $e): void {
    error_log('[secure-auth-demo] Uncaught ' . get_class($e) . ': ' . $e->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
    }
    $isDbError = $e instanceof PDOException;
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
        . '<title>Something went wrong | Secure Auth Demo</title>'
        . '<link rel="stylesheet" href="' . (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') !== false ? '../' : '') . 'assets/style.css"></head><body>'
        . '<div class="card"><div class="brand"><span class="mark">SA</span> Secure Auth Demo</div>'
        . '<h1>Something went wrong</h1>'
        . '<p class="subtitle">'
        . ($isDbError
            ? 'The database isn\'t reachable right now. If you\'re setting this up for the first time, make sure MySQL is running and you\'ve imported <code>db/setup.sql</code> -- see the README.'
            : 'An unexpected error occurred. Try again in a moment.')
        . '</p><p class="foot"><a href="' . (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') !== false ? '../' : '') . 'login.php">Back to sign in</a></p></div></body></html>';
});

// Harden the session cookie before any session starts.
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => !empty($_SERVER['HTTPS']),
]);
session_start();
