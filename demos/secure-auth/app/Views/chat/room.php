<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Secure Chat | Secure Auth Demo</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="wide">
<header class="topbar">
    <div class="brand"><span class="mark">SA</span> Secure Auth Demo</div>
    <div class="topbar-links">
        <a class="action-link" href="threads.php">&larr; All chats</a>
    </div>
</header>

<div class="unlock-gate" data-unlock-gate hidden>
    <p class="subtitle">Unlock your secure key to open this chat.</p>
    <input type="password" placeholder="Your account password" data-unlock-password class="text-input">
    <button type="button" data-unlock-btn class="mt-btn">Unlock</button>
    <p class="field-error" data-unlock-error></p>
</div>

<main class="chat-shell" data-chat-shell hidden>
    <div class="chat-head">
        <a class="peer-name-link" href="#" data-peer-link>
            <span class="avatar" data-peer-avatar></span>
            <span class="peer-name peer-name-text" data-peer-name>Loading…</span>
            <span class="gender-dot" data-peer-gender-dot aria-hidden="true"></span>
        </a>
        <span class="enc-indicator">End-to-end encrypted</span>
    </div>
    <div class="chat-log" data-chat-log></div>
    <form class="chat-input-row" data-send-form>
        <textarea placeholder="Type a message…" data-message-input required></textarea>
        <button type="submit">Send</button>
    </form>
</main>

<div class="hover-card" data-hover-card>
    <div class="hover-card-head">
        <span class="avatar avatar-lg" data-hover-avatar></span>
        <div>
            <strong data-hover-name></strong>
            <span data-hover-username></span>
        </div>
    </div>
    <dl>
        <dt>Gender</dt><dd data-hover-gender></dd>
    </dl>
</div>

<script src="assets/crypto-client.js"></script>
<script nonce="<?= e(csp_nonce()) ?>">
const MY_EMAIL = <?= json_encode($myEmail) ?>;
const MY_ID = <?= json_encode((int)$_SESSION['user_id']) ?>;
const THREAD_ID = <?= json_encode($threadId) ?>;
const CSRF = <?= json_encode(csrf_token()) ?>;

let peerPublicKey = null;
let myPrivateKey = null;
let lastId = 0;
let polling = null;

function fmtTime(s) {
    return new Date(s.replace(' ', 'T') + 'Z').toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function escapeHtml(s) {
    const div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
}

async function apiGet(action, params = '') {
    const res = await fetch(`api.php?action=${action}${params}`, { credentials: 'same-origin' });
    return res.json();
}

async function apiPost(action, body) {
    const form = new URLSearchParams(body);
    form.set('csrf_token', CSRF);
    const res = await fetch(`api.php?action=${action}`, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'X-CSRF-Token': CSRF }, body: form,
    });
    return res.json();
}

function appendBubble(msg, plaintext) {
    const log = document.querySelector('[data-chat-log]');
    const div = document.createElement('div');
    div.className = 'bubble ' + (msg.sender_user_id == MY_ID ? 'mine' : 'theirs');
    div.innerHTML = `${escapeHtml(plaintext)}<time>${fmtTime(msg.created_at)}</time>`;
    log.appendChild(div);
    log.scrollTop = log.scrollHeight;
}

async function fetchNew() {
    const data = await apiGet('chat_fetch', `&thread=${THREAD_ID}&since=${lastId}`);
    if (!data.ok) return;
    for (const msg of data.messages) {
        const wrappedKey = msg.sender_user_id == MY_ID ? msg.wrapped_key_sender : msg.wrapped_key_recipient;
        try {
            const plain = await SecureCrypto.decryptMessage(msg.ciphertext, msg.iv, wrappedKey, myPrivateKey);
            appendBubble(msg, plain);
        } catch (err) {
            appendBubble(msg, '[could not decrypt]');
        }
        lastId = msg.id;
    }
}

function initials(name, username) {
    return ((name || '?')[0] + (username || '?')[0]).toUpperCase();
}

const genderLabels = { male: 'Male', female: 'Female', unspecified: 'Prefer not to say' };

async function startChat() {
    document.querySelector('[data-unlock-gate]').hidden = true;
    document.querySelector('[data-chat-shell]').hidden = false;

    myPrivateKey = await SecureCrypto.getPrivateKey(MY_EMAIL);
    const peer = await apiGet('peer_key', `&thread=${THREAD_ID}`);
    if (!peer.ok) {
        document.querySelector('[data-peer-name]').textContent = peer.error;
        return;
    }
    peerPublicKey = peer.peerPublicKey;
    window.__peer = peer;

    document.querySelector('[data-peer-name]').textContent = peer.peerName;
    document.querySelector('[data-peer-link]').href = `profile.php?user=${peer.peerId}`;

    const gender = peer.peerGender || 'unspecified';
    const avatarEl = document.querySelector('[data-peer-avatar]');
    avatarEl.textContent = initials(peer.peerName, peer.peerUsername);
    avatarEl.className = 'avatar gender-' + gender;
    document.querySelector('[data-peer-gender-dot]').className = 'gender-dot ' + gender;

    // Hover card: built entirely from data already fetched above -- no extra request.
    const card = document.querySelector('[data-hover-card]');
    document.querySelector('[data-hover-avatar]').textContent = initials(peer.peerName, peer.peerUsername);
    document.querySelector('[data-hover-avatar]').className = 'avatar avatar-lg gender-' + gender;
    document.querySelector('[data-hover-name]').textContent = peer.peerName;
    document.querySelector('[data-hover-username]').textContent = '@' + peer.peerUsername;
    document.querySelector('[data-hover-gender]').textContent = genderLabels[gender] || 'Prefer not to say';

    const peerLink = document.querySelector('[data-peer-link]');
    peerLink.addEventListener('mouseenter', () => {
        const rect = peerLink.getBoundingClientRect();
        card.style.left = rect.left + 'px';
        card.style.top = (rect.bottom + 8) + 'px';
        card.classList.add('is-open');
    });
    peerLink.addEventListener('mouseleave', () => card.classList.remove('is-open'));

    await fetchNew();
    polling = setInterval(fetchNew, 2500);
}

document.querySelector('[data-send-form]')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const input = document.querySelector('[data-message-input]');
    const text = input.value.trim();
    if (!text || !peerPublicKey) return;
    input.value = '';

    const enc = await SecureCrypto.encryptForRecipient(text, peerPublicKey);
    const wrappedForSelf = await SecureCrypto.wrapKeyFor(enc.aesKeyRaw, window.__selfPublicKey);

    const result = await apiPost('chat_send', {
        thread: THREAD_ID,
        ciphertext: enc.ciphertext,
        iv: enc.iv,
        wrappedKeyRecipient: enc.wrappedKey,
        wrappedKeySender: wrappedForSelf,
    });
    if (result.ok) fetchNew();
});

(async () => {
    window.__selfPublicKey = null;
    const res = await fetch('me-public-key.php', { credentials: 'same-origin' });
    const data = await res.json();
    if (data.ok) window.__selfPublicKey = data.publicKey;

    if (SecureCrypto.isUnlocked(MY_EMAIL)) {
        startChat();
    } else if (SecureCrypto.hasLocalIdentity(MY_EMAIL)) {
        document.querySelector('[data-unlock-gate]').hidden = false;
    } else {
        document.querySelector('[data-unlock-gate]').hidden = false;
        document.querySelector('[data-unlock-gate] .subtitle').textContent = 'No secure key found for this account in this browser.';
    }
})();

document.querySelector('[data-unlock-btn]')?.addEventListener('click', async () => {
    const pw = document.querySelector('[data-unlock-password]').value;
    const errEl = document.querySelector('[data-unlock-error]');
    try {
        await SecureCrypto.unlockIdentity(MY_EMAIL, pw);
        startChat();
    } catch (err) {
        errEl.textContent = err.message;
    }
});

window.addEventListener('beforeunload', () => { if (polling) clearInterval(polling); });
</script>
</body>
</html>
