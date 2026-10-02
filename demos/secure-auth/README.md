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

**Sent, delivered, seen.** A message you send shows a single checkmark the moment it's stored,
a double gray checkmark once the other person's device has actually polled and received it, and
a double colored checkmark once they've had the thread open and visible while it arrived --
same visual language most chat apps use. Delivery is automatic: a poll *is* delivery, so
`chat_fetch` marks it server-side with no extra round trip. "Seen" is a deliberate signal the
client only sends while the tab is actually focused and visible (`chat_mark_seen`), not
inferred from polling alone -- the difference between a message that reached a device and one a
person actually looked at. Both are plain timestamps (`delivered_at`/`seen_at` on
`chat_messages`), not encrypted -- *when* something arrived is no more sensitive than the
`created_at` column already is; *what* it says stays exactly as encrypted as ever.

**Call history, in the thread itself.** When a call ends, a small centered entry shows up in
the chat -- "Audio call · 2:14", "Missed call", "Call declined" -- the same place the
conversation already lives, instead of a separate call log you'd have to go find. Only the
caller's browser ever writes it (`logCallResult()` in `room.php`), so a call both people were on
doesn't end up logged twice; the callee's side still sees it normally, the same way they see any
other message in the thread. It's encrypted the same dual-wrap way a text message is -- call
type, outcome, and duration are never stored in the clear, same as the call setup signaling
itself already wasn't.

The one real tradeoff of doing it this way: your private key lives in one browser's
`localStorage`. Register and administer from the same browser, same as you'd treat a hardware
security key. If you ever need multi-device access to the same key, that's a deliberate
feature I haven't built — it'd mean either syncing an encrypted key blob somewhere or adding a
second keypair per device, and I'd rather ship the single-device version correctly than a
multi-device version with a hole in it.

**Voice and video calling.** Any open chat thread can place a real call, audio or video, full
screen, styled after Apple's FaceTime -- dark takeover, centered avatar or full-bleed video, a
translucent pill of controls floating at the bottom. Two layers of encryption, deliberately:

- *The media.* Once two browsers connect, audio/video flows directly between them --
  DTLS-SRTP, mandatory in the WebRTC spec, so it's encrypted the same way every WebRTC call is,
  with no code here involved in that part. This holds even through a TURN relay: the DTLS keys
  are negotiated directly between the two browsers, so a relay forwards encrypted packets it
  can't itself decrypt.
- *The signaling.* The offer, answer, and ICE candidates that let the two browsers find each
  other in the first place are *not* automatically private the way the media is -- so each one
  is encrypted the same way a chat message is (RSA-OAEP-wrapped AES-256-GCM, the peer's public
  key) before it ever reaches the server. `call_signals` stores and this server relays
  ciphertext, same as `chat_messages` -- it can't read call setup details any more than it can
  read a message. Polled the same way chat messages are (`app/Models/CallSignal.php`) -- no
  WebSocket server, same reasoning as the chat's own polling.

Deliberately plain WebRTC throughout -- no library, no SFU, no experimental APIs (insertable
streams, etc.) -- so it's as lightweight and broadly compatible as the rest of this app.
Camera/mic toggle mid-call just flips `track.enabled` rather than renegotiating the connection,
for the same reason. STUN (free, no account, built into `app/Core/Calling.php`) is enough for
most home networks; stricter ones (symmetric NAT, corporate firewalls) need a TURN relay too,
which costs real bandwidth to run -- optional, configured via `SAD_TURN_HOST`/
`SAD_TURN_USERNAME`/`SAD_TURN_CREDENTIAL` in `config.local.php` if you sign up for one. Without
it, calling still works, just across a narrower set of networks.

**The FaceTime details that make a call feel real, not like a demo.** A caller hears a real
ringback tone; a callee hears a real ringtone -- both synthesized on the fly with the Web Audio
API (`CallAudio` in `assets/call-client.js`, a couple of oscillators and a gain envelope), not
shipped as audio files, so there's nothing extra to download. The status text is explicit about
what's actually happening -- "Calling…" while it rings, "Connecting…" once the other side
answers and the two browsers are still negotiating, "Connected" with a running timer once media
is actually flowing -- because those are three different states and collapsing them into one
"Calling..." was more confusing than the extra word. An unanswered call gives up after 30
seconds (`CALL_RING_TIMEOUT_MS` in `room.php`) -- the caller sees "No answer", the callee's
incoming-call UI clears to "Missed call" -- rather than ringing forever. A badge reading
"End-to-end encrypted" stays visible the entire call, next to the peer's name, specifically so
the encryption isn't just true, it's *visible* while you're using it. A small signal-strength
indicator appears in a corner only when `RTCPeerConnection.iceConnectionState` actually reports
`disconnected` -- it's invisible the rest of the time, not a constant status widget -- and if
the connection fails outright, both sides get an explicit "Call dropped — connection lost"
instead of a UI that just quietly hangs.

## Encrypted file attachments

Chat isn't just text. The paperclip button in the message box lets you attach an image (JPEG,
PNG, GIF, WebP) or a PDF, up to 3MB, and it's encrypted exactly the same way a message is --
client-side, before it leaves the browser, with the same RSA-OAEP-wrapped-AES-256-GCM scheme,
dual-wrapped so both people in the thread can decrypt their own copy. Two things get encrypted
separately: the file bytes themselves, and a small descriptor (filename, MIME type, size) sent
as its own encrypted payload -- because the filename and type are themselves information about
you, and leaving them in the clear "just for convenience" would quietly undercut the whole
point. The `chat_attachments` table has no `filename`, `mime_type`, or `size_bytes` column at
all, on purpose -- the server validates those at upload time (type allow-list, size cap) using
the values the browser sends, then discards them; what's actually stored is `ciphertext`, `iv`,
and two wrapped keys, same shape as every other encrypted row in this app. Opening an
attachment fetches that ciphertext, decrypts it client-side, and renders it from a local
`blob:` URL -- images inline, PDFs in a new tab -- so the plaintext file only ever exists
briefly in the recipient's own browser memory.

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
app/Models/        All the SQL -- User, LoginAudit, ContactMessage, ChatThread, ChatMessage,
                   CallSignal (encrypted offers/answers/ICE candidates for voice/video calls),
                   ChatAttachment (encrypted file attachments -- no filename/type/size columns)
app/Views/          HTML templates, one per page (auth/, chat/, admin/ subfolders)
app/Core/           Cross-cutting stuff every page needs: Database (db(), password hashing),
                    Session (CSRF, current_user(), the json_ok()/json_fail() helpers),
                    SecurityHeaders (CSP + HSTS + the HTTPS redirect, incl. the
                    microphone/camera Permissions-Policy calling needs), Captcha, Mailer,
                    SmtpMailer, Calling (STUN/TURN config), Compat (polyfills for PHP builds
                    missing an extension, e.g. mbstring), Config (reads env vars or
                    config.local.php), View (the render() helper)

assets/call-client.js          WebRTC mechanics for one call -- peer connection, mic/camera,
                                remote audio/video -- plus CallAudio, the Web Audio ringback/
                                ringtone synthesizer. Encryption of the signaling itself (so an
                                offer/answer/ICE candidate is ciphertext the whole way, not just
                                HTTPS-in-transit) and the actual api.php calls both live in
                                app/Views/chat/room.php, reusing SecureCrypto the same way text
                                messages already do -- this file stays pure WebRTC mechanics.

config/bootstrap.php           Every request starts here -- headers, session, autoloader
config/config.local.php.example   Copy to config.local.php and fill in real secrets (gitignored)
db/setup.sql                   Table definitions -- import via phpMyAdmin
db/migrations/                 Schema changes made after the initial setup.sql -- run these by
                                hand (phpMyAdmin's SQL tab) against a database that predates them
db/optional-app-user.sql       Optional: a dedicated low-privilege DB user
assets/crypto-client.js        All browser-side crypto -- untouched by the MVC split
DEPLOY.md                      Free hosting, start to finish -- see "Deploying" below
```

Autoloading is one `spl_autoload_register()` call in `config/bootstrap.php`: a class named
`User` loads from `app/Models/User.php`, `AuthController` from `app/Controllers/AuthController.php`.
No Composer, no `vendor/`, one class per file, named after the class -- the same rule PSR-4
autoloading uses, just without pulling in a dependency to do it.
