<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($profile['name']) ?> | Secure Auth Demo</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="wide">
<header class="topbar">
    <div class="brand"><span class="mark">SA</span> Secure Auth Demo</div>
    <div class="topbar-links">
        <a class="action-link" href="threads.php">&larr; Back to chats</a>
    </div>
</header>

<main class="shell">
    <div class="profile-header">
        <div class="avatar avatar-lg gender-<?= e($profile['gender']) ?>"><?= e($initials) ?></div>
        <div>
            <h1><?= e($profile['name']) ?> <?php if ($isMe): ?><span class="badge">You</span><?php endif; ?></h1>
            <p class="subtitle">@<?= e($profile['username']) ?></p>
        </div>
    </div>

    <dl class="kv">
        <dt>Gender</dt><dd><span class="gender-dot <?= e($profile['gender']) ?>" aria-hidden="true"></span> <?= e($genderLabel) ?></dd>
        <dt>Role</dt><dd><span class="badge <?= $profile['role'] === 'admin' ? 'admin' : '' ?>"><?= e(ucfirst($profile['role'])) ?></span></dd>
        <dt>Member since</dt><dd><?= e(date('F j, Y', strtotime($profile['created_at']))) ?></dd>
    </dl>
</main>
</body>
</html>
