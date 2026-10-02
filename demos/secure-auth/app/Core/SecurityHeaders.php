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

// CSP's connect-src governs WebRTC ICE connections too, not just fetch()/XHR
// -- without the STUN/TURN hosts listed here explicitly, voice calling's ICE
// negotiation gets silently blocked by the browser, no matter how correct the
// WebRTC code is. The TURN host is optional and only added if configured
// (see app/Core/Calling.php) -- STUN-only still works for most networks.
function connect_src_hosts(): string
{
    $hosts = ["'self'", 'stun:stun.l.google.com:19302', 'stun:stun1.l.google.com:19302'];
    $turnHost = cfg('SAD_TURN_HOST');
    if ($turnHost) {
        $hosts[] = "turn:{$turnHost}";
        $hosts[] = "turns:{$turnHost}";
    }
    return implode(' ', $hosts);
}

function send_security_headers(): string
{
    enforce_https();

    $nonce = csp_nonce();

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // microphone=(self), camera=(self): calling needs getUserMedia() for
    // both. Still denies geolocation/payment -- nothing here asks for those.
    header('Permissions-Policy: geolocation=(), microphone=(self), camera=(self), payment=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-origin');
    header(
        "Content-Security-Policy: default-src 'self'; " .
        "script-src 'self' 'nonce-{$nonce}'; " .
        "style-src 'self'; " .
        "img-src 'self' data: blob:; " . // blob: for decrypted attachment previews (URL.createObjectURL)
        "connect-src " . connect_src_hosts() . "; " .
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
