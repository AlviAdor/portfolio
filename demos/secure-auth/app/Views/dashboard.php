<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard | Secure Auth Demo</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="card card-wide">
    <div class="brand"><span class="mark">SA</span> Secure Auth Demo</div>
    <h1>Welcome, <?= e($user['name']) ?></h1>
    <p class="subtitle">
        <span class="badge <?= $isAdmin ? 'admin' : '' ?>"><?= e(ucfirst($user['role'])) ?></span>
        &nbsp;Signed in with a hardened, regenerated session.
    </p>

    <dl class="kv">
        <dt>Email</dt><dd><?= e($user['email']) ?></dd>
        <dt>Account created</dt><dd><?= e($user['created_at']) ?></dd>
        <dt>Session ID</dt><dd><?= e(substr(session_id(), 0, 12)) ?>&hellip; (httponly, regenerated on login)</dd>
    </dl>

    <div class="actions-row">
        <?php if ($isAdmin): ?>
            <a class="action-link" href="admin/inbox.php">Secure Inbox &rarr;</a>
        <?php endif; ?>
        <a class="action-link" href="threads.php">Secure Chats &rarr;</a>
    </div>

    <p class="subtitle mt-section">
        <?= $isAdmin ? 'Admin view: the last 10 login attempts across all accounts.' : 'Your last 10 login attempts.' ?>
    </p>
    <div class="table-scroll">
        <table>
            <thead><tr><th>Email</th><th>Result</th><th>IP</th><th>When</th></tr></thead>
            <tbody>
            <?php foreach ($recentLogins as $row): ?>
                <tr>
                    <td><?= e($row['email_attempted']) ?></td>
                    <td class="<?= $row['success'] ? 'success' : 'fail' ?>"><?= $row['success'] ? 'Success' : 'Failed' ?></td>
                    <td><?= e($row['ip_address']) ?></td>
                    <td><?= e($row['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$recentLogins): ?>
                <tr><td colspan="4">No login history yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <form method="post" action="logout.php" class="mt-sm">
        <button type="submit">Sign out</button>
    </form>
</div>
<script nonce="<?= e(csp_nonce()) ?>">window.__SAD_IS_ADMIN = <?= $isAdmin ? 'true' : 'false' ?>;</script>
<script src="assets/inactivity.js"></script>
</body>
</html>
