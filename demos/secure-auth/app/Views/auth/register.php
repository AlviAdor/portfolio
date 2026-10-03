<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create account | Secure Auth Demo</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="card">
    <div class="brand"><span class="mark">SA</span> Secure Auth Demo</div>
    <h1>Create your account</h1>
    <p class="subtitle">Passwords are hashed server-side, inputs are validated server-side, and every query is parameterized.</p>

    <?php if (!empty($errors['form'])): ?>
        <div class="alert error"><?= e($errors['form']) ?></div>
    <?php endif; ?>

    <form method="post" novalidate data-register-form>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="public_key" id="public_key">

        <label for="name">Name</label>
        <input id="name" name="name" type="text" value="<?= e($values['name']) ?>" required minlength="2" maxlength="80" autocomplete="name">
        <?php if (!empty($errors['name'])): ?><div class="field-error"><?= e($errors['name']) ?></div><?php endif; ?>

        <label for="username">Username</label>
        <input id="username" name="username" type="text" value="<?= e($values['username']) ?>" required minlength="3" maxlength="20" pattern="[a-zA-Z0-9_]{3,20}" autocomplete="username">
        <div class="hint">3-20 characters: letters, numbers, and underscores. This is how you'll show up in chat.</div>
        <?php if (!empty($errors['username'])): ?><div class="field-error"><?= e($errors['username']) ?></div><?php endif; ?>

        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="<?= e($values['email']) ?>" required maxlength="190" autocomplete="email">
        <?php if (!empty($errors['email'])): ?><div class="field-error"><?= e($errors['email']) ?></div><?php endif; ?>

        <label class="mt-section">Gender</label>
        <div class="gender-options">
            <label class="gender-option">
                <input type="radio" name="gender" value="male" <?= $values['gender'] === 'male' ? 'checked' : '' ?> required>
                <span class="gender-dot male" aria-hidden="true"></span> Male
            </label>
            <label class="gender-option">
                <input type="radio" name="gender" value="female" <?= $values['gender'] === 'female' ? 'checked' : '' ?>>
                <span class="gender-dot female" aria-hidden="true"></span> Female
            </label>
            <label class="gender-option">
                <input type="radio" name="gender" value="unspecified" <?= $values['gender'] === 'unspecified' ? 'checked' : '' ?>>
                <span class="gender-dot unspecified" aria-hidden="true"></span> Prefer not to say
            </label>
        </div>
        <div class="hint">Shown as a small color marker next to your name in chat, nowhere else.</div>
        <?php if (!empty($errors['gender'])): ?><div class="field-error"><?= e($errors['gender']) ?></div><?php endif; ?>

        <label for="password" class="mt-section">Password</label>
        <input id="password" name="password" type="password" required minlength="10" autocomplete="new-password">
        <div class="hint">10+ characters, with an uppercase letter, a lowercase letter, and a number. This also protects your private encryption key, so don't lose it.</div>
        <?php if (!empty($errors['password'])): ?><div class="field-error"><?= e($errors['password']) ?></div><?php endif; ?>

        <label for="confirm_password">Confirm password</label>
        <input id="confirm_password" name="confirm_password" type="password" required minlength="10" autocomplete="new-password">
        <?php if (!empty($errors['confirm_password'])): ?><div class="field-error"><?= e($errors['confirm_password']) ?></div><?php endif; ?>

        <button type="submit" data-submit-btn>Create account</button>
        <p class="hint" data-key-status></p>
    </form>

    <p class="foot">Already have an account? <a href="login.php">Sign in</a></p>
</div>
<script src="assets/crypto-client.js"></script>
<script nonce="<?= e($nonce) ?>">
    const form = document.querySelector('[data-register-form]');
    const statusEl = document.querySelector('[data-key-status]');
    const btn = document.querySelector('[data-submit-btn]');
    form?.addEventListener('submit', async (event) => {
        if (form.dataset.ready === 'true') return; // second submit: let it through with the key attached
        event.preventDefault();
        const email = form.email.value.trim();
        const password = form.password.value;
        if (!email || !password) { form.requestSubmit(); return; }
        btn.disabled = true;
        statusEl.textContent = 'Generating your private encryption key in this browser…';
        try {
            const publicKey = await SecureCrypto.setupIdentity(email, password);
            document.getElementById('public_key').value = publicKey;
            form.dataset.ready = 'true';
            statusEl.textContent = '';
            form.requestSubmit();
        } catch (err) {
            statusEl.textContent = 'Could not generate a secure key in this browser: ' + err.message;
            btn.disabled = false;
        }
    });
</script>
</body>
</html>
