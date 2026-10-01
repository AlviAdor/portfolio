<?php
declare(strict_types=1);

class AuthController
{
    public function register(): void
    {
        if (!empty($_SESSION['user_id'])) {
            header('Location: dashboard.php');
            exit;
        }

        $errors = [];
        $values = ['name' => '', 'username' => '', 'email' => '', 'gender' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_verify()) {
                $errors['form'] = 'Your session expired. Please resubmit the form.';
            } else {
                $values['name'] = trim((string)($_POST['name'] ?? ''));
                $values['username'] = trim((string)($_POST['username'] ?? ''));
                $values['email'] = trim((string)($_POST['email'] ?? ''));
                $values['gender'] = (string)($_POST['gender'] ?? '');
                $password = (string)($_POST['password'] ?? '');
                $confirm = (string)($_POST['confirm_password'] ?? '');

                if ($values['name'] === '' || mb_strlen($values['name']) < 2 || mb_strlen($values['name']) > 80) {
                    $errors['name'] = 'Enter a name between 2 and 80 characters.';
                }

                if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $values['username'])) {
                    $errors['username'] = '3-20 characters: letters, numbers, and underscores only.';
                }

                if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($values['email']) > 190) {
                    $errors['email'] = 'Enter a valid email address.';
                }

                if (!in_array($values['gender'], ['male', 'female', 'unspecified'], true)) {
                    $errors['gender'] = 'Please choose one.';
                }

                // Server-side password policy -- never trust client-side checks alone.
                if (
                    mb_strlen($password) < 10
                    || !preg_match('/[A-Z]/', $password)
                    || !preg_match('/[a-z]/', $password)
                    || !preg_match('/[0-9]/', $password)
                ) {
                    $errors['password'] = 'Password must be 10+ characters with upper, lower, and a number.';
                } elseif ($password !== $confirm) {
                    $errors['confirm_password'] = 'Passwords do not match.';
                }

                if (!$errors) {
                    if (User::emailExists($values['email'])) {
                        $errors['email'] = 'An account with this email already exists. Did you mean to sign in instead?';
                    }
                    if (User::usernameTaken($values['username'])) {
                        $errors['username'] = 'That username is already taken.';
                    }

                    if (!$errors) {
                        $publicKey = (string)($_POST['public_key'] ?? '');
                        if ($publicKey === '' || strlen($publicKey) > 2000) {
                            $errors['form'] = 'Your secure key could not be generated. Make sure your browser supports the Web Crypto API and try again.';
                        } else {
                            $hash = hash_password($password);
                            $role = User::count() === 0 ? 'admin' : 'member';
                            User::create($values['name'], $values['username'], $values['email'], $values['gender'], $hash, $role, $publicKey);

                            header('Location: login.php?registered=1');
                            exit;
                        }
                    }
                }
            }
        }

        view('auth/register', ['errors' => $errors, 'values' => $values, 'nonce' => csp_nonce()]);
    }

    public function login(): void
    {
        if (!empty($_SESSION['user_id'])) {
            header('Location: dashboard.php');
            exit;
        }

        $errors = [];
        $email = '';
        $showPasswordStep = false; // re-expand the password+CAPTCHA section after a failed attempt

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $showPasswordStep = true;

            if (!csrf_verify()) {
                $errors['form'] = 'Your session expired. Please resubmit the form.';
            } elseif (!verify_captcha((string)($_POST['captcha_answer'] ?? ''))) {
                // Checked before anything else, and before we even look the account up --
                // a request that never fetched captcha.php (e.g. a scripted POST that
                // skips the UI entirely) has no session answer to match and always lands
                // here, no matter what the client sends or hides.
                $email = trim((string)($_POST['email'] ?? ''));
                $errors['form'] = 'Incorrect CAPTCHA. Please try again.';
            } else {
                $email = trim((string)($_POST['email'] ?? ''));
                $password = (string)($_POST['password'] ?? '');
                $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

                $user = User::findByEmail($email);

                $locked = false;
                if ($user && $user['locked_until'] && strtotime($user['locked_until']) > time()) {
                    $locked = true;
                    $errors['form'] = 'Too many failed attempts. Account locked until ' . date('H:i:s', strtotime($user['locked_until'])) . '.';
                }

                $success = false;
                if (!$locked && $user && password_verify($password, $user['password_hash'])) {
                    $success = true;
                }

                LoginAudit::record($user['id'] ?? null, $email, $success, $ip);

                if ($success) {
                    User::clearLoginFailures((int)$user['id']);
                    maybe_rehash($password, $user['password_hash'], (int)$user['id']);

                    // Prevent session fixation: always issue a fresh session id on privilege change.
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_role'] = $user['role'];
                    header('Location: ' . ((int)$user['must_change_password'] === 1 ? 'set-password.php' : 'dashboard.php'));
                    exit;
                } elseif (!$locked) {
                    if ($user) {
                        $attempts = (int)$user['failed_attempts'] + 1;
                        $lockUntil = null;
                        if ($attempts >= MAX_FAILED_ATTEMPTS) {
                            $lockUntil = date('Y-m-d H:i:s', time() + LOCKOUT_MINUTES * 60);
                        }
                        User::recordFailedAttempt((int)$user['id'], $attempts, $lockUntil);
                    }
                    // Same message whether the email exists or not -- no account enumeration.
                    $errors['form'] = 'Invalid email or password.';
                }
            }
        }

        view('auth/login', ['errors' => $errors, 'email' => $email, 'showPasswordStep' => $showPasswordStep, 'nonce' => csp_nonce()]);
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();

        header('Location: login.php');
        exit;
    }

    public function setPassword(): void
    {
        $user = $this->requireLoginPage();

        $errors = [];
        $username = $user['username'];
        $gender = $user['gender'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_verify()) {
                $errors['form'] = 'Your session expired. Please resubmit the form.';
            } else {
                $username = trim((string)($_POST['username'] ?? ''));
                $gender = (string)($_POST['gender'] ?? '');
                $password = (string)($_POST['password'] ?? '');
                $confirm = (string)($_POST['confirm_password'] ?? '');
                $publicKey = (string)($_POST['public_key'] ?? '');

                if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
                    $errors['username'] = '3-20 characters: letters, numbers, and underscores only.';
                } elseif (User::usernameTaken($username, (int)$user['id'])) {
                    $errors['username'] = 'That username is already taken.';
                }

                if (!in_array($gender, ['male', 'female', 'unspecified'], true)) {
                    $errors['gender'] = 'Please choose one.';
                }

                if (
                    mb_strlen($password) < 10
                    || !preg_match('/[A-Z]/', $password)
                    || !preg_match('/[a-z]/', $password)
                    || !preg_match('/[0-9]/', $password)
                ) {
                    $errors['password'] = 'Password must be 10+ characters with upper, lower, and a number.';
                } elseif ($password !== $confirm) {
                    $errors['confirm_password'] = 'Passwords do not match.';
                } elseif ($publicKey === '' || strlen($publicKey) > 2000) {
                    $errors['form'] = 'Your secure key could not be generated. Make sure your browser supports the Web Crypto API and try again.';
                }

                if (!$errors) {
                    $hash = hash_password($password);
                    User::completeSetup((int)$user['id'], $username, $gender, $hash, $publicKey);

                    header('Location: threads.php?welcome=1');
                    exit;
                }
            }
        }

        view('auth/set_password', [
            'user' => $user, 'errors' => $errors, 'username' => $username, 'gender' => $gender, 'nonce' => csp_nonce(),
        ]);
    }

    private function requireLoginPage(): array
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }
        $user = User::findById((int)$_SESSION['user_id']);
        if (!$user) {
            header('Location: login.php');
            exit;
        }
        return $user;
    }
}
