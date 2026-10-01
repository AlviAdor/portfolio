<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Secure Inbox | Secure Auth Demo</title>
<link rel="stylesheet" href="../assets/style.css">
</head>
<body class="wide">
<header class="topbar">
    <div class="brand"><span class="mark">SA</span> Secure Auth Demo</div>
    <div class="topbar-links">
        <a class="action-link" href="../dashboard.php">Dashboard</a>
        <a class="action-link" href="../chat-list.php">Secure Chats</a>
        <form method="post" action="../logout.php" class="inline-form"><button class="action-link" type="submit">Sign out</button></form>
    </div>
</header>

<main class="shell">
    <h1>Secure Inbox</h1>
    <p class="subtitle">Every message below was encrypted in the sender's browser with your public key. It only decrypts here, in your browser, after you unlock your key with your password.</p>

    <div class="unlock-gate" data-unlock-gate hidden>
        <p class="subtitle">Unlock your secure key to read messages.</p>
        <input type="password" placeholder="Your account password" data-unlock-password class="text-input">
        <button type="button" data-unlock-btn class="mt-btn">Unlock</button>
        <p class="field-error" data-unlock-error></p>
    </div>

    <div class="message-list" data-message-list hidden></div>
    <p data-loading>Loading…</p>
</main>

<script src="../assets/crypto-client.js"></script>
<script nonce="<?= e(csp_nonce()) ?>">
const ADMIN_EMAIL = <?= json_encode($adminEmail) ?>;
const CSRF = <?= json_encode(csrf_token()) ?>;

const listEl = document.querySelector('[data-message-list]');
const gateEl = document.querySelector('[data-unlock-gate]');
const loadingEl = document.querySelector('[data-loading]');

function fmtDate(s) {
    return new Date(s.replace(' ', 'T') + 'Z').toLocaleString();
}

async function apiGet(action) {
    const res = await fetch(`../api.php?action=${action}`, { credentials: 'same-origin' });
    return res.json();
}

async function apiPost(action, body) {
    const form = new URLSearchParams(body);
    form.set('csrf_token', CSRF);
    const res = await fetch(`../api.php?action=${action}`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'X-CSRF-Token': CSRF },
        body: form,
    });
    return res.json();
}

function renderInviteResult(container, result) {
    const div = document.createElement('div');
    div.className = 'invite-result';
    div.innerHTML = result.emailed
        ? `Invite email sent to <strong>${result.email}</strong>.`
        : `Could not send an email from this server (no mail transport configured here) — share these credentials with <strong>${result.email}</strong> yourself:`;
    if (!result.emailed) {
        div.innerHTML += `<br>Login: <code>${result.loginUrl}</code><br>Temporary password: <code>${result.tempPassword}</code>`;
    }
    container.appendChild(div);
}

async function renderMessages() {
    const data = await apiGet('inbox_list');
    if (!data.ok) { loadingEl.textContent = data.error; return; }
    loadingEl.hidden = true;
    listEl.hidden = false;
    listEl.innerHTML = '';

    if (!data.messages.length) {
        listEl.innerHTML = '<p class="subtitle">No messages yet.</p>';
        return;
    }

    const privateKey = await SecureCrypto.getPrivateKey(ADMIN_EMAIL).catch(() => null);

    for (const msg of data.messages) {
        const card = document.createElement('article');
        card.className = 'message-card';

        const statusClass = msg.status === 'new' ? 'new' : (msg.status === 'invited' ? 'invited' : '');
        card.innerHTML = `
            <div class="message-card-head">
                <strong>${escapeHtml(msg.sender_name)}</strong>
                <span class="status-pill ${statusClass}">${msg.status}</span>
            </div>
            <div class="from">${escapeHtml(msg.sender_email)} &middot; <time>${fmtDate(msg.created_at)}</time></div>
            <div class="message-body locked" data-body>Decrypting…</div>
            <div class="message-card-actions" data-actions></div>
        `;
        listEl.appendChild(card);

        const bodyEl = card.querySelector('[data-body]');
        if (privateKey) {
            try {
                const plain = await SecureCrypto.decryptMessage(msg.ciphertext, msg.iv, msg.wrapped_key, privateKey);
                bodyEl.textContent = plain;
                bodyEl.classList.remove('locked');
                if (msg.status === 'new') apiPost('mark_read', { id: msg.id });
            } catch (err) {
                bodyEl.textContent = 'Could not decrypt this message: ' + err.message;
            }
        } else {
            bodyEl.textContent = 'Locked — unlock your key above to read this.';
        }

        const actions = card.querySelector('[data-actions]');
        if (msg.status !== 'invited') {
            const inviteBtn = document.createElement('button');
            inviteBtn.type = 'button';
            inviteBtn.textContent = 'Invite to secure chat';
            inviteBtn.addEventListener('click', async () => {
                inviteBtn.disabled = true;
                inviteBtn.textContent = 'Inviting…';
                const result = await apiPost('invite', { id: msg.id });
                if (result.ok) {
                    renderInviteResult(card, result);
                    inviteBtn.remove();
                    card.querySelector('.status-pill').textContent = 'invited';
                    card.querySelector('.status-pill').className = 'status-pill invited';
                } else {
                    inviteBtn.disabled = false;
                    inviteBtn.textContent = 'Invite to secure chat';
                    alert(result.error);
                }
            });
            actions.appendChild(inviteBtn);
        }
    }
}

function escapeHtml(s) {
    const div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
}

(async () => {
    if (SecureCrypto.isUnlocked(ADMIN_EMAIL)) {
        renderMessages();
    } else if (SecureCrypto.hasLocalIdentity(ADMIN_EMAIL)) {
        loadingEl.hidden = true;
        gateEl.hidden = false;
    } else {
        loadingEl.textContent = 'No secure key found for this account in this browser. Register and administer from the same browser, or re-generate a key.';
    }
})();

document.querySelector('[data-unlock-btn]')?.addEventListener('click', async () => {
    const pw = document.querySelector('[data-unlock-password]').value;
    const errEl = document.querySelector('[data-unlock-error]');
    try {
        await SecureCrypto.unlockIdentity(ADMIN_EMAIL, pw);
        gateEl.hidden = true;
        loadingEl.hidden = false;
        loadingEl.textContent = 'Loading…';
        renderMessages();
    } catch (err) {
        errEl.textContent = err.message;
    }
});
</script>
</body>
</html>
