# Secure Auth Demo

This is the backend for the Security section of my portfolio. I was tired of security pages
that just *describe* good practices in prose, so I built something real instead: a PHP +
MySQL app with actual rate-limited login, a CAPTCHA that can't be bypassed by hiding it, and —
the part I'm most proud of — a contact form and chat system where the server genuinely cannot
read your messages. Not "we promise not to look." Cannot. There's no code path for it.

It's a standalone app. It doesn't share any code with the rest of the portfolio — the two talk
to each other over plain HTTP, which is why I can develop, deploy, and eventually move this to
its own repository without touching the static site at all.

## What's actually in here

**Accounts.** Register, log in, log out. First account to register becomes admin; everyone
else is a member. Passwords are hashed with Argon2id where the server supports it, bcrypt
otherwise (`app/Core/Database.php` picks whichever is actually available and upgrades old
hashes transparently on next login — see `hash_password()` / `maybe_rehash()`). Every account
gets a CSRF token, rate-limited login attempts (five strikes, fifteen-minute lockout), and a
session that regenerates its ID on every privilege change.

**A CAPTCHA that's genuinely hard to skip.** The login form hides the password field until
you've typed an email and hit Continue — that's just UI polish, a calmer first screen. The
part that matters is server-side: `captcha.php` draws a small math problem into a PNG with
GD and writes only the answer into the session, never into the page. `login.php` checks that
session value *before it even looks at your email or password*, and it's single-use — solved
or not, it's gone after one attempt. Skip the UI, script a raw POST straight at `login.php`,
and you'll get "Incorrect CAPTCHA" every time, because there's no session answer for a request
that never loaded the image. I've tried it.

**An encrypted inbox.** This is the part the rest of the site points to. When you register as
the first (admin) account, your browser generates an RSA-OAEP keypair with the native Web
Crypto API — no library, nothing loaded from a CDN. The public half goes to the database. The
private half gets encrypted with a key derived from your password (PBKDF2, 150k rounds) and
stays in `localStorage`, on your machine, full stop. When someone fills out the contact form
on the main site, their browser fetches your public key, generates a one-time AES-256 key,
encrypts their message with it, wraps that key with your RSA public key, and sends only the
result. What lands in the `contact_messages` table is ciphertext — I can pull up phpMyAdmin
and stare at the raw rows and see nothing but base64 noise. It only turns back into words
inside your browser, after you type your password.

**Invite-to-chat.** From the inbox you can invite whoever messaged you into a private,
end-to-end encrypted chat. That generates them a temporary password, emails it (see "Email"
below), and forces them to set a real password on first login — which, same as your own
registration, generates *their* keypair client-side too. From there every chat message is
encrypted twice (once for them, once for you, so you can scroll back through your own side of
the conversation) with a fresh key per message. The server stores and relays ciphertext and
nothing else. New messages show up via polling every 2.5 seconds — no WebSocket server to run,
fast enough that it doesn't feel like polling.

The one real tradeoff of doing it this way: your private key lives in one browser's
`localStorage`. Register and administer from the same browser, same as you'd treat a hardware
security key. If you ever need multi-device access to the same key, that's a deliberate
feature I haven't built — it'd mean either syncing an encrypted key blob somewhere or adding a
second keypair per device, and I'd rather ship the single-device version correctly than a
multi-device version with a hole in it.

## Getting it running

You need Apache and MySQL — XAMPP gives you both.

1. Start Apache and MySQL from the XAMPP control panel.
2. Create the database and import the table structure — easiest through phpMyAdmin:
   - Open `http://localhost/phpmyadmin`, click **New** in the sidebar, name it
     `secure_auth_demo`, set collation to `utf8mb4_unicode_ci`, **Create**.
   - Click into it, open its **Import** tab, choose `db/setup.sql`, **Go**.

   (Or from the command line: `mysql -u root -e "CREATE DATABASE secure_auth_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"` then `mysql -u root secure_auth_demo < db/setup.sql`.)

   `db/setup.sql` is just table definitions — no `CREATE USER`, no `GRANT`. The
   app connects as `root` with no password by default, same as the other PHP
   projects already on this machine. There's a fully optional
   `db/optional-app-user.sql` if you want a dedicated low-privilege database
   user later — worth doing before a real deployment, not before your first
   test run.
3. Go to `http://localhost/portfolio/demos/secure-auth/register.php` and create your account.
   **Do this in whatever browser you intend to administer the inbox from** — see the
   `localStorage` note above.
4. That's the admin account. There's no separate "admin panel" URL to remember:

   - **Log in** at `login.php`.
   - You land on `dashboard.php`, which shows a **Secure Inbox** link only for the admin
     account, plus a **Secure Chats** link everyone gets.
   - The inbox itself is `admin/inbox.php` — type your password in once more there (or it's
     already unlocked for the session if you just logged in) and every message decrypts in
     front of you.

No separate admin password, no hidden route — whoever registered first simply sees more
options once they're signed in.

Nothing needs a `.env` file for the default XAMPP setup (root, no password, default socket).
Pointing at a different MySQL instance -- or any other setting below -- goes through `cfg()`
in `app/Core/Config.php`, which checks real environment variables first, then falls back to
`config/config.local.php`, a plain gitignored PHP file that returns an array. Env vars are
great when the host supports them; `config.local.php` is what actually works on hosts that
don't (most free PHP hosting has no concept of an environment variable at all -- see
DEPLOY.md). Copy `config/config.local.php.example` to `config/config.local.php` and fill in
whichever keys you need; anything left out just falls back to the local-dev default.

## Email

Invite emails and "you've got a new message" notifications go through
`app/Core/Mailer.php`, which tries real SMTP first and falls back to PHP's `mail()`. The
fallback works out of the box on this machine because macOS ships a local mail transfer agent,
but that's a local-dev convenience, not something to rely on anywhere real — most hosting
either disables `mail()` outright or it lands in spam.

To wire up real delivery, the quickest path if you already have a Gmail account:

1. Turn on 2-Step Verification on the Google account.
2. Google Account → Security → 2-Step Verification → App passwords → generate one for "Mail".
   You get a 16-character password — that's what goes below, not your normal Gmail password.
3. Set these in `config/config.local.php` (or as real environment variables, if your host
   supports them -- see the note above):
   ```php
   'SAD_SMTP_HOST' => 'smtp.gmail.com',
   'SAD_SMTP_PORT' => '587',
   'SAD_SMTP_SECURE' => 'tls',
   'SAD_SMTP_USER' => 'you@gmail.com',
   'SAD_SMTP_PASS' => 'your 16-char app password',
   'SAD_SMTP_FROM' => 'you@gmail.com',
   ```

Any other SMTP provider (SendGrid, Mailgun, Resend, Postmark, SES — all of them, really) works
the same way, and is a better choice than Gmail once this is doing more than personal volume,
since Gmail's SMTP has sending limits built for a person, not an app. `app/Core/SmtpMailer.php`
is a single dependency-free file — no Composer, no PHPMailer — because I wanted this folder to
stay copy-paste-portable into its own repo without a `vendor/` directory trailing behind it.
It speaks STARTTLS and implicit TLS with AUTH LOGIN, which covers all of the above.

Once SMTP is configured, the new-message notification will name whoever messaged you — it
can't show you what they said, because the server genuinely can't decrypt it either. That's
not a limitation I'm working around; it's the point.

## HTTPS

Both this app and the main site force HTTPS for any real hostname — a `.htaccess` redirect at
the portfolio root, and a second one inside `app/Core/SecurityHeaders.php` for any future
deployment that isn't sitting behind Apache. Both deliberately leave `localhost` / `127.0.0.1`
alone: local XAMPP has no certificate anyone's browser actually trusts, so forcing the redirect
there just swaps every page for a security warning instead of the site — not what I want while
I'm working on it day to day. Deploy this anywhere with a real domain name and the redirect
applies with no extra setup; PHP's built-in dev server (`php -S`) is excluded too, since it has
no TLS support of its own.

XAMPP's bundled certificate is also worth knowing about if you ever do want to test HTTPS
locally: **it expired in 2010.** I generated a fresh self-signed one (RSA 2048, SHA-256, valid
to 2029, with the SAN entries modern browsers actually require) and left it at
`~/xampp-local-ssl/` on this machine, since I can't write to the root-owned `etc/ssl.crt/` /
`etc/ssl.key/` paths myself:

```bash
sudo cp ~/xampp-local-ssl/server.crt /Applications/XAMPP/xamppfiles/etc/ssl.crt/server.crt
sudo cp ~/xampp-local-ssl/server.key /Applications/XAMPP/xamppfiles/etc/ssl.key/server.key
sudo /Applications/XAMPP/xamppfiles/xampp restart
```

Still self-signed, so the browser will show a "not trusted" warning on top of no longer being
an *expired* one — that's expected for local dev. For a real deployment, a free
[Let's Encrypt](https://letsencrypt.org/) certificate (or whatever your host issues) drops in
instead; the redirect logic doesn't change either way.

## Security headers

Every response — PHP pages here, and the static HTML pages via the portfolio's root
`.htaccess` — sends a `Content-Security-Policy` with no `unsafe-inline` anywhere. The plain
HTML pages manage that by having zero inline scripts to begin with (I moved the last one,
theme detection, into `assets/js/theme-init.js`). This app's pages generate a random nonce per
request instead, since they render server-side — `csp_nonce()` in `app/Core/SecurityHeaders.php`.
Alongside it: `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, a locked-down
`Permissions-Policy`, and HSTS once you're actually on HTTPS.

## Deploying, and why not Vercel for this part

The static portfolio deploys to Vercel in about thirty seconds — it's plain HTML/CSS/JS, zero
config. This folder won't follow it there: Vercel's serverless functions don't give you a
persistent MySQL connection or a filesystem to drop sessions into, and rewriting this around
Vercel's model would mean trading PDO + sessions for a different database (Postgres via
Neon/Supabase, say) and a different session strategy (signed JWTs instead of server-side
sessions) — a genuinely different app, not a deploy target swap.

What I actually went with: free PHP + MySQL shared hosting (InfinityFree) — zero cost, no
credit card, and this app runs there completely unmodified, which is exactly the point of
keeping it boring and portable. The full walkthrough, start to finish, is in **DEPLOY.md**.
Point the main site at wherever it ends up by changing the one
`<meta name="secure-messaging-base">` tag in the portfolio's `index.html`, and set
`SAD_ALLOWED_ORIGIN` here (in `config/config.local.php`) to the portfolio's exact origin so the
two public, cookie-free endpoints the contact form uses (`admin_public_key`, `contact_submit`)
are allowed to talk cross-origin. Everything else — the database, SMTP config, the CAPTCHA — is
already self-contained in this one folder.

## Moving this to its own repository

This was always meant to be split out eventually — no shared code with the portfolio, its own
README, nothing it reaches outside its own folder. Two ways to do it:

**Keep the history** (if you've committed this folder to the portfolio repo at least once):
```bash
git subtree split --prefix=demos/secure-auth -b secure-auth-standalone
mkdir ../secure-auth-demo && cd ../secure-auth-demo
git init
git pull ../portfolio secure-auth-standalone
```

**Don't care about history** — just copy the folder, `git init` fresh, push it as a new repo
(`gh repo create` or GitHub's UI).

Either way, two things need updating on the portfolio side afterward: the
`secure-messaging-base` meta tag (point it at wherever this is now deployed), and
`SAD_ALLOWED_ORIGIN` here, so CORS allows the portfolio's origin once it's no longer the same
one this app is served from.

## Layout

This is a small hand-rolled MVC -- no framework, nothing to `composer install`. The URLs
didn't change when I split it this way: `login.php` is still `login.php`, not some router's
idea of `/auth/login`. Each top-level file is a thin entry point that does one thing --
require the bootstrap, call a controller method -- so invite emails, the CAPTCHA `<img src>`,
and every link on the main site keep working exactly as before.

```
register.php, login.php, logout.php, set-password.php   Entry points -- call AuthController
dashboard.php                                            Entry point -- calls DashboardController
threads.php, thread.php                                   Entry points -- call ChatController
                                                            (named around "thread", not "chat" --
                                                            some free hosts' WAF blanket-blocks any
                                                            URL containing "chat"; see DEPLOY.md)
profile.php                                               Entry point -- calls ProfileController
captcha.php                                               Entry point -- calls CaptchaController
api.php, me-public-key.php                                Entry points -- call ApiController
bridge.php                                                 Same-origin postMessage relay for the
                                                            contact form, when deployed cross-origin
                                                            from the portfolio -- see DEPLOY.md
admin/inbox.php                                           Entry point -- calls AdminController

app/Controllers/   Request handling: reads input, calls a Model, picks a View
app/Models/        All the SQL -- User, LoginAudit, ContactMessage, ChatThread, ChatMessage
app/Views/          HTML templates, one per page (auth/, chat/, admin/ subfolders)
app/Core/           Cross-cutting stuff every page needs: Database (db(), password hashing),
                    Session (CSRF, current_user(), the json_ok()/json_fail() helpers),
                    SecurityHeaders (CSP + HSTS + the HTTPS redirect), Captcha, Mailer,
                    SmtpMailer, Config (reads env vars or config.local.php), View (the
                    render() helper)

config/bootstrap.php           Every request starts here -- headers, session, autoloader
config/config.local.php.example   Copy to config.local.php and fill in real secrets (gitignored)
db/setup.sql                   Table definitions -- import via phpMyAdmin
db/optional-app-user.sql       Optional: a dedicated low-privilege DB user
assets/crypto-client.js        All browser-side crypto -- untouched by the MVC split
DEPLOY.md                      Free hosting, start to finish -- see "Deploying" below
```

Autoloading is one `spl_autoload_register()` call in `config/bootstrap.php`: a class named
`User` loads from `app/Models/User.php`, `AuthController` from `app/Controllers/AuthController.php`.
No Composer, no `vendor/`, one class per file, named after the class -- the same rule PSR-4
autoloading uses, just without pulling in a dependency to do it.
