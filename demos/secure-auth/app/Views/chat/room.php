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
        <div class="chat-head-actions">
            <button type="button" class="call-btn" data-call-audio-btn title="Start a voice call" aria-label="Start a voice call">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1.1-.3 1.2.4 2.5.6 3.8.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.6 21 3 13.4 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.6.6 3.8.1.4 0 .8-.3 1.1L6.6 10.8z" fill="currentColor"/></svg>
            </button>
            <button type="button" class="call-btn" data-call-video-btn title="Start a video call" aria-label="Start a video call">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M17 10.5V7a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-3.5l4 4v-11l-4 4z" fill="currentColor"/></svg>
            </button>
            <span class="enc-indicator">End-to-end encrypted</span>
        </div>
    </div>

    <div class="chat-log" data-chat-log></div>
    <p class="attach-status" data-attach-status hidden></p>
    <form class="chat-input-row" data-send-form>
        <input type="file" data-attach-input accept="image/jpeg,image/png,image/gif,image/webp,application/pdf" hidden>
        <button type="button" class="attach-btn" data-attach-btn title="Attach a photo or PDF" aria-label="Attach a photo or PDF">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M17.5 6.5v10a4.5 4.5 0 0 1-9 0V5a3 3 0 0 1 6 0v10.5a1.5 1.5 0 0 1-3 0V7.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        </button>
        <textarea placeholder="Type a message…" data-message-input required></textarea>
        <button type="submit">Send</button>
    </form>
</main>

<div class="call-overlay" data-call-overlay hidden>
    <video class="call-remote-video" data-remote-video autoplay playsinline hidden></video>

    <div class="call-top-info" data-call-top-info>
        <span class="call-peer-name" data-call-peer-name></span>
        <span class="call-timer" data-call-timer hidden>00:00</span>
        <span class="call-encrypted-badge" data-call-encrypted-badge title="This call's audio/video never passes through any server -- it goes directly between your two browsers, encrypted the entire way.">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2a5 5 0 0 0-5 5v3H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-1V7a5 5 0 0 0-5-5zm0 2a3 3 0 0 1 3 3v3H9V7a3 3 0 0 1 3-3zm0 10a2 2 0 0 1 1 3.73V19a1 1 0 0 1-2 0v-1.27A2 2 0 0 1 12 14z" fill="currentColor"/></svg>
            End-to-end encrypted
        </span>
    </div>

    <div class="call-signal-indicator" data-call-signal-indicator hidden title="Unstable connection">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M3 18h2v3H3v-3zm4-4h2v7H7v-7zm4-4h2v11h-2V10zm4-4h2v15h-2V6zm4-4h2v19h-2V2z" fill="currentColor" opacity="0.4"/>
        </svg>
        <span>Unstable connection…</span>
    </div>

    <div class="call-center" data-call-center>
        <div class="call-avatar" data-call-avatar></div>
        <p class="call-status-text" data-call-status-text>Calling…</p>
    </div>

    <video class="call-self-video" data-self-video autoplay playsinline muted hidden></video>

    <div class="call-controls" data-call-controls hidden>
        <button type="button" class="call-ctrl-btn" data-call-mute-btn title="Mute" aria-label="Mute">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 15a3 3 0 0 0 3-3V6a3 3 0 0 0-6 0v6a3 3 0 0 0 3 3zm5-3a5 5 0 0 1-10 0H5a7 7 0 0 0 6 6.92V21h2v-2.08A7 7 0 0 0 19 12h-2z" fill="currentColor"/></svg>
        </button>
        <button type="button" class="call-ctrl-btn" data-call-camera-btn title="Turn camera off" aria-label="Turn camera off" hidden>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M17 10.5V7a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-3.5l4 4v-11l-4 4z" fill="currentColor"/></svg>
        </button>
        <button type="button" class="call-ctrl-btn call-ctrl-end" data-call-end-btn title="End call" aria-label="End call">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><g transform="rotate(135 12 12)"><path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1.1-.3 1.2.4 2.5.6 3.8.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.6 21 3 13.4 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.6.6 3.8.1.4 0 .8-.3 1.1L6.6 10.8z" fill="currentColor"/></g></svg>
        </button>
    </div>

    <div class="call-incoming-controls" data-call-incoming-controls hidden>
        <button type="button" class="call-ctrl-btn call-ctrl-decline" data-call-decline-btn title="Decline" aria-label="Decline">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><g transform="rotate(135 12 12)"><path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1.1-.3 1.2.4 2.5.6 3.8.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.6 21 3 13.4 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.6.6 3.8.1.4 0 .8-.3 1.1L6.6 10.8z" fill="currentColor"/></g></svg>
        </button>
        <button type="button" class="call-ctrl-btn call-ctrl-accept" data-call-accept-btn title="Accept" aria-label="Accept">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1.1-.3 1.2.4 2.5.6 3.8.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.6 21 3 13.4 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.6.6 3.8.1.4 0 .8-.3 1.1L6.6 10.8z" fill="currentColor"/></svg>
        </button>
    </div>
</div>

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
<script src="assets/call-client.js"></script>
<script nonce="<?= e(csp_nonce()) ?>">
const MY_EMAIL = <?= json_encode($myEmail) ?>;
const MY_ID = <?= json_encode((int)$_SESSION['user_id']) ?>;
const THREAD_ID = <?= json_encode($threadId) ?>;
const CSRF = <?= json_encode(csrf_token()) ?>;
const ICE_SERVERS = <?= json_encode($iceServers) ?>;
window.__SAD_IS_ADMIN = <?= $isAdmin ? 'true' : 'false' ?>;

let peerPublicKey = null;
let myPrivateKey = null;
let lastId = 0;
let polling = null;
let receiptPolling = null;

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

// ---- Sent / delivered / seen ----
// A single checkmark means the server has it; a double means the other
// device has polled and received it; a colored double means they've
// actually had the thread open and visible while it arrived. Only ever
// shown on my own bubbles -- there's no receipt tick on a message someone
// else sent me.
function receiptStatus(msg) {
    if (msg.seen_at) return 'seen';
    if (msg.delivered_at) return 'delivered';
    return 'sent';
}

function receiptIconSvg(status) {
    if (status === 'sent') {
        return '<svg class="receipt-icon" width="15" height="11" viewBox="0 0 16 11" fill="none" aria-hidden="true"><path d="M1 5.5L5.5 10L15 1" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }
    const seenClass = status === 'seen' ? ' is-seen' : '';
    return `<svg class="receipt-icon receipt-double${seenClass}" width="19" height="11" viewBox="0 0 19 11" fill="none" aria-hidden="true"><path d="M1 5.5L5.5 10L13 1.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M6 5.5L10.5 10L18 1" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
}

function receiptMarkup(msg) {
    if (msg.sender_user_id != MY_ID) return '';
    return `<span class="receipt" data-receipt>${receiptIconSvg(receiptStatus(msg))}</span>`;
}

function updateBubbleReceipt(id, status) {
    const el = document.querySelector(`[data-msg-id="${id}"] [data-receipt]`);
    if (el) el.innerHTML = receiptIconSvg(status);
}

function appendBubble(msg, plaintext) {
    const log = document.querySelector('[data-chat-log]');
    const div = document.createElement('div');
    div.className = 'bubble ' + (msg.sender_user_id == MY_ID ? 'mine' : 'theirs');
    div.dataset.msgId = msg.id;
    div.innerHTML = `${escapeHtml(plaintext)}<time>${fmtTime(msg.created_at)}${receiptMarkup(msg)}</time>`;
    log.appendChild(div);
    log.scrollTop = log.scrollHeight;
}

// ---- Call log entries ----
// A centered system-style row, not a left/right bubble -- it's a shared
// event both people witnessed, not something one side "said" to the other,
// even though technically one side's user_id is the DB sender.
const CALL_LOG_AUDIO_ICON = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1.1-.3 1.2.4 2.5.6 3.8.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.6 21 3 13.4 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.6.6 3.8.1.4 0 .8-.3 1.1L6.6 10.8z" fill="currentColor"/></svg>';
const CALL_LOG_VIDEO_ICON = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M17 10.5V7a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-3.5l4 4v-11l-4 4z" fill="currentColor"/></svg>';
const CALL_LOG_MISSED_OUTCOMES = ['canceled', 'declined', 'busy', 'no-answer', 'dropped'];

function callLogText(payload) {
    const dur = payload.durationSeconds > 0 ? ` · ${fmtDuration(payload.durationSeconds)}` : '';
    const kind = payload.callType === 'video' ? 'Video' : 'Audio';
    switch (payload.outcome) {
        case 'completed': return `${kind} call${dur}`;
        case 'canceled': return 'Call canceled';
        case 'declined': return 'Call declined';
        case 'busy': return 'Call not answered — busy';
        case 'no-answer': return 'No answer';
        case 'dropped': return `Call dropped${dur}`;
        default: return `${kind} call`;
    }
}

function appendCallLogBubble(payload) {
    const log = document.querySelector('[data-chat-log]');
    const row = document.createElement('div');
    row.className = 'call-log-row';
    const missed = CALL_LOG_MISSED_OUTCOMES.includes(payload.outcome);
    const icon = payload.callType === 'video' ? CALL_LOG_VIDEO_ICON : CALL_LOG_AUDIO_ICON;
    row.innerHTML = `<span class="call-log-pill${missed ? ' is-missed' : ''}">${icon}<span>${escapeHtml(callLogText(payload))}</span></span>`;
    log.appendChild(row);
    log.scrollTop = log.scrollHeight;
}

function fmtBytes(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return Math.round(bytes / 1024) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

const PDF_ICON = '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z" fill="currentColor" opacity="0.15"/><path d="M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z" stroke="currentColor" stroke-width="1.3"/><path d="M15 2v5h5" stroke="currentColor" stroke-width="1.3"/></svg>';

// Attachments show up inline in the same encrypted log as text -- the chat
// log doesn't pre-fetch or pre-decrypt the file itself (could be a few MB of
// ciphertext per message), only this small descriptor. The actual bytes are
// fetched and decrypted lazily, the moment someone taps to open it, and
// cached on the bubble element afterward so a second tap is instant.
function appendAttachmentBubble(msg, descriptor) {
    const log = document.querySelector('[data-chat-log]');
    const div = document.createElement('div');
    const isImage = descriptor.mimeType.startsWith('image/');
    div.className = 'bubble attachment-bubble ' + (msg.sender_user_id == MY_ID ? 'mine' : 'theirs');
    div.dataset.msgId = msg.id;
    div.innerHTML = `
        <button type="button" class="attachment-trigger" data-attachment-trigger>
            <span class="attachment-icon" data-attachment-icon>${isImage ? '' : PDF_ICON}</span>
            <span class="attachment-meta">
                <span class="attachment-name">${escapeHtml(descriptor.filename)}</span>
                <span class="attachment-sub" data-attachment-sub>${fmtBytes(descriptor.sizeBytes)} · Tap to ${isImage ? 'view' : 'open'}</span>
            </span>
        </button>
        <time>${fmtTime(msg.created_at)}${receiptMarkup(msg)}</time>
    `;
    log.appendChild(div);
    log.scrollTop = log.scrollHeight;

    let cachedUrl = null;
    div.querySelector('[data-attachment-trigger]').addEventListener('click', async () => {
        const subEl = div.querySelector('[data-attachment-sub]');
        if (cachedUrl) {
            window.open(cachedUrl, '_blank', 'noopener');
            return;
        }
        subEl.textContent = 'Decrypting…';
        try {
            const res = await apiGet('chat_attachment_fetch', `&message=${msg.id}`);
            if (!res.ok) throw new Error(res.error || 'Could not load attachment.');
            const a = res.attachment;
            // Same dual-wrap as a text message: whichever side of
            // sender/recipient I am determines which wrapped key is mine.
            const wrappedKey = msg.sender_user_id == MY_ID ? a.wrapped_key_sender : a.wrapped_key_recipient;
            const buf = await SecureCrypto.decryptBuffer(a.ciphertext, a.iv, wrappedKey, myPrivateKey);
            const blob = new Blob([buf], { type: descriptor.mimeType });
            cachedUrl = URL.createObjectURL(blob);
            subEl.textContent = `${fmtBytes(descriptor.sizeBytes)} · Tap to ${isImage ? 'view' : 'open'}`;
            if (isImage) {
                const iconEl = div.querySelector('[data-attachment-icon]');
                const img = document.createElement('img');
                img.src = cachedUrl;
                img.alt = descriptor.filename;
                img.className = 'attachment-thumb';
                div.insertBefore(img, div.firstChild);
                iconEl.closest('.attachment-trigger').classList.add('has-thumb');
            }
            window.open(cachedUrl, '_blank', 'noopener');
        } catch (err) {
            subEl.textContent = 'Could not decrypt this file.';
        }
    });
}

async function fetchNew() {
    const data = await apiGet('chat_fetch', `&thread=${THREAD_ID}&since=${lastId}`);
    if (!data.ok) return;
    for (const msg of data.messages) {
        const wrappedKey = msg.sender_user_id == MY_ID ? msg.wrapped_key_sender : msg.wrapped_key_recipient;
        try {
            const plain = await SecureCrypto.decryptMessage(msg.ciphertext, msg.iv, wrappedKey, myPrivateKey);
            if (msg.type === 'attachment') {
                appendAttachmentBubble(msg, JSON.parse(plain));
            } else if (msg.type === 'call_log') {
                appendCallLogBubble(JSON.parse(plain));
            } else {
                appendBubble(msg, plain);
            }
        } catch (err) {
            appendBubble(msg, '[could not decrypt]');
        }
        lastId = msg.id;
    }
    markSeenIfVisible();
}

// "Seen" is deliberate, not just "my client happened to poll it" -- only
// fires while the tab is actually visible, and re-fires on focus/visibility
// change in case new messages arrived while it was hidden or backgrounded.
let lastSeenMarked = 0;
async function markSeenIfVisible() {
    if (document.visibilityState !== 'visible') return;
    if (lastId <= lastSeenMarked) return;
    const idToMark = lastId;
    lastSeenMarked = idToMark;
    await apiPost('chat_mark_seen', { thread: THREAD_ID, uptoId: idToMark });
}

document.addEventListener('visibilitychange', markSeenIfVisible);
window.addEventListener('focus', markSeenIfVisible);

// Updates the little tick on my own already-rendered bubbles as the other
// side's device receives, then actually sees, each one. Self-limiting: a
// message stops coming back from the server once it's been seen, so this
// naturally has nothing left to do once the other person's caught up.
async function pollReceipts() {
    const data = await apiGet('chat_receipts', `&thread=${THREAD_ID}`);
    if (!data.ok) return;
    for (const r of data.receipts) {
        updateBubbleReceipt(r.id, r.seen_at ? 'seen' : (r.delivered_at ? 'delivered' : 'sent'));
    }
}

function initials(name, username) {
    return ((name || '?')[0] + (username || '?')[0]).toUpperCase();
}

const genderLabels = { male: 'Male', female: 'Female', unspecified: 'Prefer not to say' };

// ---- Voice / video calling ----
// Every signal (offer, answer, ICE candidate, hangup) is encrypted the same
// way a chat message is -- RSA-OAEP-wrapped AES-256-GCM, the peer's public
// key -- before it ever reaches apiPost(). The server stores and relays
// ciphertext; it can't read call setup details any more than it can read a
// message. The media itself never touches the server at all: once the two
// browsers connect, audio/video flows directly peer-to-peer, encrypted by
// WebRTC's own mandatory DTLS-SRTP.
const CALL_RING_TIMEOUT_MS = 30000; // how long a call rings before giving up, either side

let callState = 'idle'; // idle | calling | ringing | connecting | connected
let lastCallSignalId = 0;
let callPolling = null;
let callTimerInterval = null;
let callStartedAt = null;
let callRingTimeout = null;
let pendingOffer = null; // the decrypted remote offer, held while ringing
let isMuted = false;
let isCameraOff = false;
let isVideoCall = false;
let remoteVideoActive = false; // tracks reality, since ontrack and setCallState race each other
let cachedLeaveSignal = null; // pre-encrypted 'hangup' blob, for a synchronous beforeunload beacon
// Which side of THIS call I am -- 'caller' | 'callee' | null. Only the
// caller ever writes the call-log chat entry (see logCallResult) so a call
// that both sides witness doesn't end up logged twice.
let callRole = null;

const callOverlay = document.querySelector('[data-call-overlay]');
const callPeerNameEl = document.querySelector('[data-call-peer-name]');
const callTimerEl = document.querySelector('[data-call-timer]');
const callCenterEl = document.querySelector('[data-call-center]');
const callAvatarEl = document.querySelector('[data-call-avatar]');
const callStatusTextEl = document.querySelector('[data-call-status-text]');
const callSignalIndicator = document.querySelector('[data-call-signal-indicator]');
const remoteVideoEl = document.querySelector('[data-remote-video]');
const selfVideoEl = document.querySelector('[data-self-video]');
const callControlsEl = document.querySelector('[data-call-controls]');
const callIncomingControlsEl = document.querySelector('[data-call-incoming-controls]');
const callMuteBtn = document.querySelector('[data-call-mute-btn]');
const callCameraBtn = document.querySelector('[data-call-camera-btn]');
const callEndBtn = document.querySelector('[data-call-end-btn]');
const callAcceptBtn = document.querySelector('[data-call-accept-btn]');
const callDeclineBtn = document.querySelector('[data-call-decline-btn]');
const callAudioBtn = document.querySelector('[data-call-audio-btn]');
const callVideoBtn = document.querySelector('[data-call-video-btn]');

function fmtDuration(totalSeconds) {
    const m = Math.floor(totalSeconds / 60).toString().padStart(2, '0');
    const s = Math.floor(totalSeconds % 60).toString().padStart(2, '0');
    return `${m}:${s}`;
}

function clearRingTimeout() {
    if (callRingTimeout) { clearTimeout(callRingTimeout); callRingTimeout = null; }
}

function setCallState(state) {
    callState = state;
    callOverlay.hidden = state === 'idle';
    callAudioBtn.hidden = state !== 'idle';
    callVideoBtn.hidden = state !== 'idle';
    callControlsEl.hidden = !(state === 'calling' || state === 'connecting' || state === 'connected');
    callIncomingControlsEl.hidden = state !== 'ringing';

    if (callTimerInterval) { clearInterval(callTimerInterval); callTimerInterval = null; }

    if (state === 'idle') {
        CallAudio.stop();
        clearRingTimeout();
        isMuted = false;
        isCameraOff = false;
        isVideoCall = false;
        remoteVideoActive = false;
        pendingOffer = null;
        callRole = null;
        callSignalIndicator.hidden = true;
        callMuteBtn.classList.remove('is-muted');
        callMuteBtn.title = 'Mute';
        callCameraBtn.hidden = true;
        callCameraBtn.classList.remove('is-off');
        remoteVideoEl.hidden = true;
        remoteVideoEl.srcObject = null;
        selfVideoEl.hidden = true;
        selfVideoEl.srcObject = null;
        callCenterEl.hidden = false;
        callTimerEl.hidden = true;
        callTimerEl.textContent = '00:00';
        callStatusTextEl.hidden = false;
        return;
    }

    // Reflects reality rather than assuming "not connected yet" -- ontrack
    // (which flips remoteVideoActive) can fire before *or* after this runs,
    // since setRemoteDescription's promise resolving and the track actually
    // arriving aren't ordered relative to each other.
    callCenterEl.hidden = remoteVideoActive;
    if (state === 'calling') {
        // "Ringing…" is what a caller traditionally hears/sees while waiting
        // -- distinct from "Connecting…" once the other side has actually
        // picked up and the two browsers are still negotiating.
        callStatusTextEl.textContent = isVideoCall ? 'Video calling… Ringing…' : 'Ringing…';
        callStatusTextEl.hidden = false;
        callTimerEl.hidden = true;
        CallAudio.startRingback();
    } else if (state === 'connecting') {
        CallAudio.stop();
        callStatusTextEl.textContent = 'Connecting…';
        callStatusTextEl.hidden = false;
        callTimerEl.hidden = true;
    } else if (state === 'connected') {
        CallAudio.stop();
        callStatusTextEl.hidden = true;
        callTimerEl.hidden = false;
        callStartedAt = Date.now();
        callTimerEl.textContent = '00:00';
        callTimerInterval = setInterval(() => {
            callTimerEl.textContent = fmtDuration((Date.now() - callStartedAt) / 1000);
        }, 1000);
    }
}

// Ends the call locally, telling the user plainly why, instead of the
// overlay just silently vanishing -- a dropped connection or an unanswered
// call are different situations and read as a bug if left unexplained.
function endCallWithMessage(message, delayMs = 2200) {
    clearRingTimeout();
    CallAudio.stop();
    VoiceCall.hangup();
    callControlsEl.hidden = true;
    callIncomingControlsEl.hidden = true;
    callSignalIndicator.hidden = true;
    remoteVideoEl.hidden = true;
    selfVideoEl.hidden = true;
    callCenterEl.hidden = false;
    callStatusTextEl.hidden = false;
    callStatusTextEl.textContent = message;
    callState = 'ending'; // blocks new call attempts/signals until the message has shown
    setTimeout(() => setCallState('idle'), delayMs);
}

// A call that was actually connected and ended cleanly logs as "completed";
// one the caller gave up on before it connected logs as "canceled". Shared
// by every call site that doesn't already know a more specific outcome
// (declined, no-answer, busy, dropped).
function callOutcomeFromState() {
    return callStartedAt ? 'completed' : 'canceled';
}

// Writes a call-log chat entry -- "Audio call · 2:14", "Missed call", etc.
// Only the caller writes one, so a call both people witnessed doesn't show
// up twice in the thread. Encrypted the same dual-wrap way a text message
// is; reuses chat_send with type=call_log rather than a new endpoint.
// Captures callType/duration synchronously, before any await, so it's
// correct even when the caller fires this and resets call state in the same
// breath (setCallState('idle') clears isVideoCall/callStartedAt).
async function logCallResult(outcome) {
    if (callRole !== 'caller' || !peerPublicKey) return;
    const callType = isVideoCall ? 'video' : 'audio';
    const durationSeconds = callStartedAt ? Math.max(0, Math.round((Date.now() - callStartedAt) / 1000)) : 0;
    try {
        const payload = JSON.stringify({ callType, outcome, durationSeconds });
        const enc = await SecureCrypto.encryptForRecipient(payload, peerPublicKey);
        const wrappedForSelf = await SecureCrypto.wrapKeyFor(enc.aesKeyRaw, window.__selfPublicKey);
        const result = await apiPost('chat_send', {
            thread: THREAD_ID, type: 'call_log',
            ciphertext: enc.ciphertext, iv: enc.iv,
            wrappedKeyRecipient: enc.wrappedKey, wrappedKeySender: wrappedForSelf,
        });
        if (result.ok) fetchNew();
    } catch (err) {
        // Best-effort -- a missing call-log entry isn't worth surfacing to
        // the user over, the call itself already ended correctly.
    }
}

async function sendCallSignal(type, payloadObj) {
    if (!peerPublicKey) return;
    const enc = await SecureCrypto.encryptForRecipient(JSON.stringify(payloadObj), peerPublicKey);
    return apiPost('call_signal_send', { thread: THREAD_ID, type, ciphertext: enc.ciphertext, iv: enc.iv, wrappedKey: enc.wrappedKey });
}

// Encrypted once, up front, so a tab closed mid-call can still tell the peer
// "I left" via a synchronous sendBeacon -- encryption itself is async and
// can't run inside a beforeunload handler with any guarantee of finishing.
async function cacheLeaveSignal() {
    if (!peerPublicKey) return;
    try {
        cachedLeaveSignal = await SecureCrypto.encryptForRecipient(JSON.stringify({ reason: 'left' }), peerPublicKey);
    } catch (err) {
        cachedLeaveSignal = null;
    }
}

function callHandlers() {
    return {
        onLocalStream: (stream, hasVideo) => {
            if (hasVideo) {
                selfVideoEl.srcObject = stream;
                selfVideoEl.hidden = false;
                callCameraBtn.hidden = false;
            }
        },
        onIceCandidate: (candidate) => sendCallSignal('ice', candidate),
        onRemoteStream: (stream) => {
            remoteVideoEl.srcObject = stream;
            remoteVideoActive = stream.getVideoTracks().length > 0;
            remoteVideoEl.hidden = !remoteVideoActive;
            callCenterEl.hidden = remoteVideoActive;
        },
        onConnectionStateChange: (state) => {
            if (state === 'connected' && (callState === 'connecting' || callState === 'calling' || callState === 'ringing')) {
                setCallState('connected');
            } else if (state === 'failed') {
                // Unlike a deliberate hangup, the peer never told us -- say so.
                logCallResult('dropped');
                endCallWithMessage('Call dropped — connection lost');
            } else if (state === 'closed' && callState !== 'idle' && callState !== 'ending') {
                setCallState('idle');
            }
        },
        onIceConnectionStateChange: (iceState) => {
            // A transient wobble, not a failure -- 'failed' is handled above,
            // via connectionState, with its own clear message. This is just
            // "things look shaky right now," shown only while it's actually
            // true.
            callSignalIndicator.hidden = iceState !== 'disconnected';
        },
    };
}

async function handleCallSignal(type, sig) {
    let payload;
    try {
        const plain = await SecureCrypto.decryptMessage(sig.ciphertext, sig.iv, sig.wrapped_key, myPrivateKey);
        payload = JSON.parse(plain);
    } catch (err) {
        return; // couldn't decrypt -- ignore rather than breaking the poll loop
    }

    if (type === 'offer') {
        if (callState !== 'idle') {
            // Already on a call (or calling out) -- decline so the caller
            // doesn't sit there waiting for someone who's busy.
            await sendCallSignal('hangup', { reason: 'busy' });
            return;
        }
        pendingOffer = payload;
        callRole = 'callee';
        isVideoCall = !!payload.video;
        const peerName = document.querySelector('[data-peer-name]').textContent;
        callStatusTextEl.textContent = isVideoCall ? `Incoming video call from ${peerName}…` : `Incoming call from ${peerName}…`;
        setCallState('ringing');
        CallAudio.startRingtone();
        // If nobody acts on it, this is a missed call, not a frozen UI.
        callRingTimeout = setTimeout(() => {
            if (callState !== 'ringing') return;
            sendCallSignal('hangup', { reason: 'no-answer' });
            endCallWithMessage('Missed call');
        }, CALL_RING_TIMEOUT_MS);
    } else if (type === 'answer') {
        if (callState !== 'calling') return;
        clearRingTimeout();
        setCallState('connecting');
        await VoiceCall.acceptAnswer(payload.sdp);
    } else if (type === 'ice') {
        await VoiceCall.addIceCandidate(payload);
    } else if (type === 'hangup') {
        if (callState === 'idle' || callState === 'ending') return;
        const reason = payload && payload.reason;
        if (reason === 'busy') {
            logCallResult('busy');
            endCallWithMessage(`${document.querySelector('[data-peer-name]').textContent} is on another call`);
        } else if (reason === 'declined') {
            logCallResult('declined');
            endCallWithMessage('Call declined');
        } else if (reason === 'no-answer') {
            // Whichever side's timeout actually fired, the message should
            // reflect THIS side's role, not just echo the sender's reason --
            // the caller's timer almost always wins the race (it starts the
            // moment the offer is sent, before the callee has even received
            // it), so the callee would otherwise see "No answer" for a call
            // they never got to pick up. (The caller already logged this
            // outcome directly, from its own timeout, in placeCall -- not
            // here, since this branch runs on whichever side RECEIVED the
            // signal, usually the callee.)
            endCallWithMessage(callState === 'ringing' ? 'Missed call' : 'No answer');
        } else {
            // Normal end: the peer hung up (or left) while we were still on
            // the call with them.
            logCallResult(callOutcomeFromState());
            VoiceCall.hangup();
            setCallState('idle');
        }
    }
}

async function pollCallSignals() {
    const data = await apiGet('call_signal_poll', `&thread=${THREAD_ID}&since=${lastCallSignalId}`);
    if (!data.ok) return;
    for (const sig of data.signals) {
        lastCallSignalId = sig.id;
        if (sig.sender_user_id == MY_ID) continue; // don't react to our own signals
        await handleCallSignal(sig.type, sig);
    }
}

// Jumps lastCallSignalId straight to "caught up" before polling ever starts
// reacting to anything -- otherwise the first real poll starts from 0 and
// replays this thread's entire signaling history, including a stale
// 'offer' from some past call that was never answered (tab closed mid-ring,
// browser crash, etc.). That replay is exactly what shows a call "ringing"
// the moment the page loads, with nobody actually dialing.
async function catchUpCallSignals() {
    const data = await apiGet('call_signal_poll', `&thread=${THREAD_ID}&since=0`);
    if (data.ok && data.signals.length) {
        lastCallSignalId = Math.max(...data.signals.map((sig) => sig.id));
    }
}

async function placeCall(withVideo) {
    if (callState !== 'idle') return;
    isVideoCall = withVideo;
    callRole = 'caller';
    setCallState('calling');
    try {
        const { sdp, video } = await VoiceCall.createOffer(ICE_SERVERS, withVideo, callHandlers());
        isVideoCall = video;
        await sendCallSignal('offer', { sdp, video });
        callRingTimeout = setTimeout(() => {
            if (callState !== 'calling') return;
            sendCallSignal('hangup', { reason: 'no-answer' });
            logCallResult('no-answer');
            endCallWithMessage('No answer');
        }, CALL_RING_TIMEOUT_MS);
    } catch (err) {
        alert('Could not start the call: ' + err.message);
        VoiceCall.hangup();
        setCallState('idle');
    }
}

callAudioBtn?.addEventListener('click', () => placeCall(false));
callVideoBtn?.addEventListener('click', () => placeCall(true));

callAcceptBtn?.addEventListener('click', async () => {
    if (!pendingOffer) return;
    clearRingTimeout();
    const offer = pendingOffer;
    pendingOffer = null;
    setCallState('connecting');
    try {
        const { sdp, video } = await VoiceCall.createAnswer(ICE_SERVERS, offer.sdp, !!offer.video, callHandlers());
        isVideoCall = video;
        await sendCallSignal('answer', { sdp, video });
    } catch (err) {
        alert('Could not answer the call: ' + err.message);
        await sendCallSignal('hangup', { reason: 'error' });
        VoiceCall.hangup();
        setCallState('idle');
    }
});

callDeclineBtn?.addEventListener('click', async () => {
    clearRingTimeout();
    await sendCallSignal('hangup', { reason: 'declined' });
    VoiceCall.hangup();
    setCallState('idle');
});

callEndBtn?.addEventListener('click', async () => {
    logCallResult(callOutcomeFromState());
    await sendCallSignal('hangup', { reason: 'ended' });
    VoiceCall.hangup();
    setCallState('idle');
});

callMuteBtn?.addEventListener('click', () => {
    isMuted = !isMuted;
    VoiceCall.setMuted(isMuted);
    callMuteBtn.classList.toggle('is-muted', isMuted);
    callMuteBtn.title = isMuted ? 'Unmute' : 'Mute';
});

callCameraBtn?.addEventListener('click', () => {
    isCameraOff = !isCameraOff;
    VoiceCall.setVideoEnabled(!isCameraOff);
    callCameraBtn.classList.toggle('is-off', isCameraOff);
    callCameraBtn.title = isCameraOff ? 'Turn camera on' : 'Turn camera off';
    selfVideoEl.classList.toggle('is-off', isCameraOff);
});

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
    cacheLeaveSignal(); // ready before any call starts, not just once one is active

    document.querySelector('[data-peer-name]').textContent = peer.peerName;
    document.querySelector('[data-peer-link]').href = `profile.php?user=${peer.peerId}`;

    const gender = peer.peerGender || 'unspecified';
    const avatarEl = document.querySelector('[data-peer-avatar]');
    avatarEl.textContent = initials(peer.peerName, peer.peerUsername);
    avatarEl.className = 'avatar gender-' + gender;
    document.querySelector('[data-peer-gender-dot]').className = 'gender-dot ' + gender;

    // Call overlay avatar/name -- same data, no extra request.
    callPeerNameEl.textContent = peer.peerName;
    callAvatarEl.textContent = initials(peer.peerName, peer.peerUsername);
    callAvatarEl.className = 'call-avatar avatar gender-' + gender;

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
    receiptPolling = setInterval(pollReceipts, 2500);
    await catchUpCallSignals();
    callPolling = setInterval(pollCallSignals, 1500);
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

// ---- Attachments: encrypted the same two ways a text message already is --
// the file itself (dual-wrapped, chat_attachments) and a small descriptor
// (filename/mime/size, dual-wrapped, chat_messages) that's what actually
// shows up in the polled log. Nothing here ever has a plaintext code path:
// if peerPublicKey or the self key isn't ready, the upload simply can't
// proceed, the same way sending a text message can't either.
const ALLOWED_ATTACHMENT_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
const MAX_ATTACHMENT_BYTES = 3 * 1024 * 1024;
const attachStatusEl = document.querySelector('[data-attach-status]');
const attachInput = document.querySelector('[data-attach-input]');

document.querySelector('[data-attach-btn]')?.addEventListener('click', () => attachInput?.click());

attachInput?.addEventListener('change', async () => {
    const file = attachInput.files && attachInput.files[0];
    attachInput.value = ''; // allow re-selecting the same file later
    if (!file || !peerPublicKey) return;

    if (!ALLOWED_ATTACHMENT_TYPES.includes(file.type)) {
        attachStatusEl.hidden = false;
        attachStatusEl.textContent = 'Only JPEG/PNG/GIF/WebP images or PDFs can be attached.';
        setTimeout(() => { attachStatusEl.hidden = true; }, 3500);
        return;
    }
    if (file.size > MAX_ATTACHMENT_BYTES) {
        attachStatusEl.hidden = false;
        attachStatusEl.textContent = `That file is too large -- 3MB limit (this one is ${fmtBytes(file.size)}).`;
        setTimeout(() => { attachStatusEl.hidden = true; }, 3500);
        return;
    }

    attachStatusEl.hidden = false;
    attachStatusEl.textContent = 'Encrypting and sending…';
    try {
        const buffer = await file.arrayBuffer();
        const encFile = await SecureCrypto.encryptBufferForRecipient(buffer, peerPublicKey);
        const fileWrappedForSelf = await SecureCrypto.wrapKeyFor(encFile.aesKeyRaw, window.__selfPublicKey);

        const descriptor = JSON.stringify({ filename: file.name, mimeType: file.type, sizeBytes: file.size });
        const encDesc = await SecureCrypto.encryptForRecipient(descriptor, peerPublicKey);
        const descWrappedForSelf = await SecureCrypto.wrapKeyFor(encDesc.aesKeyRaw, window.__selfPublicKey);

        const result = await apiPost('chat_attachment_send', {
            thread: THREAD_ID,
            descCiphertext: encDesc.ciphertext, descIv: encDesc.iv,
            descWrappedKeyRecipient: encDesc.wrappedKey, descWrappedKeySender: descWrappedForSelf,
            fileCiphertext: encFile.ciphertext, fileIv: encFile.iv,
            fileWrappedKeyRecipient: encFile.wrappedKey, fileWrappedKeySender: fileWrappedForSelf,
            filename: file.name, mimeType: file.type, sizeBytes: String(file.size),
        });
        if (result.ok) {
            attachStatusEl.hidden = true;
            fetchNew();
        } else {
            attachStatusEl.textContent = result.error || 'Could not send attachment.';
            setTimeout(() => { attachStatusEl.hidden = true; }, 3500);
        }
    } catch (err) {
        attachStatusEl.textContent = 'Could not encrypt this file: ' + err.message;
        setTimeout(() => { attachStatusEl.hidden = true; }, 3500);
    }
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

window.addEventListener('beforeunload', () => {
    if (polling) clearInterval(polling);
    if (receiptPolling) clearInterval(receiptPolling);
    if (callPolling) clearInterval(callPolling);
    if (VoiceCall.isActive() && cachedLeaveSignal) {
        navigator.sendBeacon?.(
            `api.php?action=call_signal_send`,
            new URLSearchParams({
                thread: THREAD_ID, type: 'hangup', csrf_token: CSRF,
                ciphertext: cachedLeaveSignal.ciphertext, iv: cachedLeaveSignal.iv, wrappedKey: cachedLeaveSignal.wrappedKey,
            })
        );
    }
    if (VoiceCall.isActive()) VoiceCall.hangup();
});
</script>
<script src="assets/inactivity.js"></script>
</body>
</html>
