<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Secure Chats | Secure Auth Demo</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="wide">
<header class="topbar">
    <div class="brand"><span class="mark">SA</span> Secure Auth Demo</div>
    <div class="topbar-links">
        <a class="action-link" href="dashboard.php">Dashboard</a>
        <?php if ($isAdmin): ?><a class="action-link" href="admin/inbox.php">Secure Inbox</a><?php endif; ?>
        <form method="post" action="logout.php" class="inline-form"><button class="action-link" type="submit">Sign out</button></form>
    </div>
</header>

<main class="shell">
    <h1>Secure Chats</h1>
    <?php if (!empty($_GET['welcome'])): ?>
        <div class="alert ok">You're all set. Your secure key is ready, and so is your chat.</div>
    <?php endif; ?>
    <p class="subtitle">End-to-end encrypted — each message is encrypted in your browser for the other person's key before it ever reaches the server.</p>
    <div class="thread-list" data-thread-list></div>
    <p data-empty hidden class="subtitle">No secure chats yet.</p>
</main>

<script nonce="<?= e(csp_nonce()) ?>">
const genderLabels = { male: 'Male', female: 'Female', unspecified: 'Prefer not to say' };

function initials(name, username) {
    return ((name || '?')[0] + (username || '?')[0]).toUpperCase();
}

async function load() {
    const res = await fetch('api.php?action=my_threads', { credentials: 'same-origin' });
    const data = await res.json();
    const list = document.querySelector('[data-thread-list]');
    if (!data.ok || !data.threads.length) {
        document.querySelector('[data-empty]').hidden = false;
        return;
    }
    for (const t of data.threads) {
        const gender = t.peer_gender || 'unspecified';

        const row = document.createElement('a');
        row.className = 'thread-row';
        row.href = `chat.php?thread=${t.id}`;

        const peer = document.createElement('span');
        peer.className = 'peer';

        const avatar = document.createElement('span');
        avatar.className = 'avatar gender-' + gender;
        avatar.textContent = initials(t.peer_name, t.peer_username);

        const name = document.createElement('span');
        name.textContent = t.peer_name;

        const dot = document.createElement('span');
        dot.className = 'gender-dot ' + gender;
        dot.title = genderLabels[gender] || '';

        peer.append(avatar, name, dot);

        const meta = document.createElement('span');
        meta.className = 'meta';
        meta.textContent = t.peer_has_key ? 'Ready' : 'Waiting on their secure key';

        row.append(peer, meta);
        list.appendChild(row);
    }
}
load();
</script>
</body>
</html>
