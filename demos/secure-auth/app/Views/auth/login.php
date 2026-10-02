<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign in | Secure Auth Demo</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="card">
    <div class="brand"><span class="mark">SA</span> Secure Auth Demo</div>
    <h1>Sign in</h1>
    <p class="subtitle">Rate-limited, CAPTCHA-protected, and session-hardened -- backing the claims on the Security page.</p>

    <?php if (!empty($_GET['registered'])): ?>
        <div class="alert ok">Registered successfully. Sign in below to get started.</div>
    <?php endif; ?>
    <?php if (!empty($errors['form'])): ?>
        <div class="alert error"><?= e($errors['form']) ?></div>
    <?php endif; ?>

    <form method="post" novalidate data-login-form>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="<?= e($email) ?>" required autofocus>

        <div data-password-step <?= $showPasswordStep ? '' : 'hidden' ?>>
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required>

            <div data-captcha-step <?= $showPasswordStep ? '' : 'hidden' ?>>
                <label for="captcha_answer" class="mt-section">Quick check: what's the answer?</label>
                <img alt="CAPTCHA challenge" width="160" height="56" data-captcha-img class="captcha-img">
                <button type="button" data-captcha-refresh class="action-link">New image</button>
                <input id="captcha_answer" name="captcha_answer" type="text" inputmode="numeric" autocomplete="off" required class="mt-sm">
            </div>

            <button type="submit" class="mt-btn">Sign in</button>
        </div>
    </form>

    <p class="foot">No account yet? <a href="register.php">Create one</a></p>
</div>
<script src="assets/crypto-client.js" nonce="<?= e($nonce) ?>"></script>
<script nonce="<?= e($nonce) ?>">
    const form = document.querySelector('[data-login-form]');
    const passwordStep = document.querySelector('[data-password-step]');
    const captchaStep = document.querySelector('[data-captcha-step]');
    const captchaImg = document.querySelector('[data-captcha-img]');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');

    function loadCaptcha() {
        captchaImg.src = 'captcha.php?t=' + Date.now();
    }

    // No button to click -- the password field just appears the moment the
    // email looks like a real address, whether that's from typing, a paste,
    // or the browser's own autofill.
    let passwordStepShown = <?= $showPasswordStep ? 'true' : 'false' ?>;
    function revealPasswordStep() {
        if (passwordStepShown) return;
        passwordStepShown = true;
        passwordStep.hidden = false;
        passwordInput.focus();
    }
    function maybeRevealPasswordStep() {
        if (emailInput.validity.valid && emailInput.value.trim()) revealPasswordStep();
    }
    emailInput.addEventListener('input', maybeRevealPasswordStep);
    emailInput.addEventListener('blur', maybeRevealPasswordStep);

    // The CAPTCHA itself waits for the first keystroke in the password field
    // rather than appearing the instant the field does -- someone who gets
    // this far and then abandons the form never even triggers an image
    // render for a challenge they were never going to answer.
    let captchaShown = <?= $showPasswordStep ? 'true' : 'false' ?>;
    if (captchaShown) loadCaptcha();
    function revealCaptcha() {
        if (captchaShown) return;
        captchaShown = true;
        captchaStep.hidden = false;
        loadCaptcha();
    }
    passwordInput.addEventListener('input', revealCaptcha, { once: true });

    document.querySelector('[data-captcha-refresh]')?.addEventListener('click', loadCaptcha);

    // Best-effort: unlock the secure key for this browser/account right away, using
    // the password already in hand, so the inbox/chat pages don't need to ask again.
    form?.addEventListener('submit', async (event) => {
        if (form.dataset.tried === 'true') return;
        event.preventDefault();
        form.dataset.tried = 'true';
        const email = form.email.value.trim();
        const password = form.password.value;
        try {
            if (email && SecureCrypto.hasLocalIdentity(email)) {
                await SecureCrypto.unlockIdentity(email, password);
            }
        } catch (err) {
            // Wrong password will also fail login server-side; a missing/locked key
            // just means the inbox or chat page will offer to unlock it there instead.
        }
        form.requestSubmit();
    });
</script>
</body>
</html>
