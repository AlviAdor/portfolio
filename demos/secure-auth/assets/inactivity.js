// Auto-signs a member out after a stretch of no activity -- a signed-in
// session on a shared or unattended device shouldn't just sit open
// indefinitely. Skipped entirely for the admin account (window.__SAD_IS_ADMIN
// set by the page before this script loads): the one person expected to
// leave the inbox open for long stretches while actually working in it.
(function () {
    if (window.__SAD_IS_ADMIN) return;

    const TIMEOUT_MS = 10 * 60 * 1000; // 10 minutes of inactivity
    const WARNING_MS = 30 * 1000; // warn 30s before actually signing out

    let timer = null;
    let warningTimer = null;
    let banner = null;

    function hideWarning() {
        if (banner) { banner.remove(); banner = null; }
    }

    function showWarning() {
        if (banner) return;
        banner = document.createElement('div');
        banner.setAttribute('role', 'status');
        banner.textContent = "You'll be signed out soon due to inactivity.";
        Object.assign(banner.style, {
            position: 'fixed', bottom: '20px', left: '50%', transform: 'translateX(-50%)',
            background: '#161616', color: '#f5f7f3', padding: '10px 20px', borderRadius: '999px',
            fontSize: '0.82rem', fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
            zIndex: 2147483647, boxShadow: '0 12px 30px rgba(0,0,0,.35)', pointerEvents: 'none',
        });
        document.body.appendChild(banner);
    }

    function signOut() {
        window.location.href = 'logout.php?reason=inactive';
    }

    function resetTimer() {
        hideWarning();
        if (timer) clearTimeout(timer);
        if (warningTimer) clearTimeout(warningTimer);
        warningTimer = setTimeout(showWarning, TIMEOUT_MS - WARNING_MS);
        timer = setTimeout(signOut, TIMEOUT_MS);
    }

    ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'].forEach((evt) => {
        document.addEventListener(evt, resetTimer, { passive: true });
    });

    resetTimer();
})();
