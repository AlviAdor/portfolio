<?php
declare(strict_types=1);
require __DIR__ . '/config/bootstrap.php';

// Why this file exists: some free hosts (this one included -- see DEPLOY.md
// section H) run a bot-check at the edge that intercepts any request it
// hasn't seen a solved challenge for, including cross-origin fetch() calls,
// and that challenge response carries no CORS headers -- so the browser
// blocks it before this app's own CORS logic in ApiController ever gets a
// chance to run. A normal page load (like this one) passes that same check
// fine, because the browser actually executes the challenge's JS.
//
// So: instead of the portfolio's contact form calling api.php directly
// cross-origin, it loads this page in a hidden iframe and talks to it with
// postMessage, which isn't subject to CORS at all. This page then makes a
// same-origin call to api.php -- same-origin, so none of the above applies
// -- and relays the result back to the parent window.
$allowedOrigin = cfg('SAD_ALLOWED_ORIGIN') ?: '';

// Every other page in this app sends X-Frame-Options: DENY and
// frame-ancestors 'none' (see SecurityHeaders.php) -- correct for login,
// dashboard, chat, all of it, but this one page's entire job is to be
// embedded in an iframe. Replace both with a policy scoped to exactly the
// one configured origin, nothing wider.
header_remove('X-Frame-Options');
if ($allowedOrigin !== '') {
    header(
        "Content-Security-Policy: default-src 'self'; " .
        "script-src 'self' 'nonce-" . csp_nonce() . "'; " .
        "style-src 'self'; img-src 'self' data:; connect-src 'self'; font-src 'self'; " .
        "object-src 'none'; base-uri 'self'; form-action 'self'; " .
        "frame-ancestors {$allowedOrigin}; upgrade-insecure-requests"
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Secure Auth Demo — bridge</title>
</head>
<body>
<script nonce="<?= e(csp_nonce()) ?>">
const ALLOWED_ORIGIN = <?= json_encode($allowedOrigin) ?>;

async function handleRequest(type, payload) {
    if (type === 'admin_public_key') {
        const res = await fetch('api.php?action=admin_public_key', { credentials: 'omit' });
        return res.json();
    }
    if (type === 'contact_submit') {
        const body = new URLSearchParams(payload || {});
        const res = await fetch('api.php?action=contact_submit', { method: 'POST', body, credentials: 'omit' });
        return res.json();
    }
    return { ok: false, error: 'Unknown bridge request type.' };
}

window.addEventListener('message', async (event) => {
    // Only ever act on messages from the one origin this bridge exists for --
    // otherwise any page on the internet could embed this iframe and use it
    // to submit spam through the contact form.
    if (!ALLOWED_ORIGIN || event.origin !== ALLOWED_ORIGIN) return;
    const { requestId, type, payload } = event.data || {};
    if (!requestId || !type) return;

    let result;
    try {
        result = await handleRequest(type, payload);
    } catch (err) {
        result = { ok: false, error: 'Bridge request failed: ' + err.message };
    }
    event.source.postMessage({ requestId, result }, event.origin);
});

// Lets the parent know this iframe is actually ready to receive messages --
// there's an unavoidable gap between the iframe element loading and this
// script finishing (bot-challenge permitting), and a message sent too early
// would just be dropped.
if (window.parent !== window && ALLOWED_ORIGIN) {
    window.parent.postMessage({ bridgeReady: true }, ALLOWED_ORIGIN);
}
</script>
</body>
</html>
