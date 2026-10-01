<?php
declare(strict_types=1);

// Modern, restrictive response headers for every page in this app. Returns a
// per-request nonce that inline <script> tags must carry -- there is no
// 'unsafe-inline' anywhere in this policy, for scripts or styles.
function csp_nonce(): string
{
    static $nonce = null;
    if ($nonce === null) {
        $nonce = base64_encode(random_bytes(16));
    }
    return $nonce;
}

// Redirects to the HTTPS version of the current URL. Method-preserving (308),
// so a POST to api.php that somehow arrives over plain HTTP still redirects
// correctly instead of silently becoming a GET. Skipped under PHP's built-in
// dev server (`php -S`), which has no TLS support of its own, and skipped for
// localhost/127.0.0.1 -- local XAMPP has no trusted certificate, and forcing
// the redirect there just replaces every page with a browser security
// warning instead of the app. Any real hostname still gets redirected.
function enforce_https(): void
{
    if (PHP_SAPI === 'cli-server') return;

    $host = explode(':', $_SERVER['HTTP_HOST'] ?? '')[0];
    if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) return;

    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    if ($isHttps) return;

    $url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header("Location: {$url}", true, 308);
    exit;
}

function send_security_headers(): string
{
    enforce_https();

    $nonce = csp_nonce();

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-origin');
    header(
        "Content-Security-Policy: default-src 'self'; " .
        "script-src 'self' 'nonce-{$nonce}'; " .
        "style-src 'self'; " .
        "img-src 'self' data:; " .
        "connect-src 'self'; " .
        "font-src 'self'; " .
        "object-src 'none'; " .
        "base-uri 'self'; " .
        "form-action 'self'; " .
        "frame-ancestors 'none'; " .
        "upgrade-insecure-requests"
    );

    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }

    return $nonce;
}
