<?php
declare(strict_types=1);
require __DIR__ . '/config/bootstrap.php';

// Why this file exists: some free hosts (this one included -- see DEPLOY.md
// section H) run a bot-check at the edge that intercepts any request it
// hasn't seen a solved challenge for, including cross-origin fetch() calls,
// and that challenge response carries no CORS headers -- so the browser
// blocks it before this app's own CORS logic in ApiController ever gets a
// chance to run. A normal top-level page load (like this one) passes that
// same check fine, because the browser actually executes the challenge's JS
// and the resulting proof-of-passing cookie gets attached normally.
//
// So: instead of the portfolio's contact form calling api.php directly
// cross-origin, it opens this page in a small popup window and talks to it
// with postMessage, which isn't subject to CORS at all. This page then
// makes a same-origin call to api.php -- same-origin, so none of the above
// applies -- and relays the result back via window.opener.
//
// This used to be a hidden iframe instead of a popup. That failed silently,
// every time: this host's anti-bot cookie has no explicit SameSite
// attribute, which Chrome defaults to Lax, and a Lax cookie is never sent on
// a cross-site iframe's request -- only on a genuine top-level navigation.
// So the embedded version could never actually present proof of a solved
// challenge, no matter how many times it solved one. A popup doesn't have
// that problem; it IS a top-level navigation. No frame-ancestors override is
// needed here anymore either, for the same reason -- this page is opened,
// not embedded, so the site's default `frame-ancestors 'none'` already
// applies correctly and needs no exception.
$allowedOrigin = cfg('SAD_ALLOWED_ORIGIN') ?: '';
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
    // otherwise any page on the internet could open this window and use it
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

// Lets the opener know this window is actually ready to receive messages --
// there's an unavoidable gap between the window opening and this script
// finishing (bot-challenge permitting), and a message sent too early would
// just be dropped. A popup, not an iframe: this host's anti-bot cookie is
// SameSite=Lax by default, which browsers withhold from a cross-site
// iframe's request but send normally on a real top-level navigation like
// this one -- see assets/js/app.js's getBridgePopup() for the full story.
if (window.opener && ALLOWED_ORIGIN) {
    window.opener.postMessage({ bridgeReady: true }, ALLOWED_ORIGIN);
}
</script>
</body>
</html>
