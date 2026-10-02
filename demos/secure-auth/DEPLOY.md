# Deploying this for free

The static portfolio lives on GitHub Pages (`alviador.github.io/portfolio`), which is plain
file hosting -- it can't run PHP or talk to MySQL. This folder needs an actual server, so it
has to live somewhere else and the two talk to each other over HTTP, exactly the way
`secure-messaging-base` and `SAD_ALLOWED_ORIGIN` were built to support from day one.

## Why InfinityFree

I looked for "really free, no credit card, no 30-day trial that quietly becomes a bill."
000webhost -- the other name everyone remembers -- shut down in 2024. What's left that still
runs plain PHP + MySQL for free, unmodified, no rewrite required:

- **InfinityFree** -- free forever, no credit card, real PHP 8.x + MySQL, free SSL, a
  generous-enough 5GB disk. The catch: shared hosting built for hobby/demo traffic, not
  something I'd point a business at. For a portfolio demo that gets occasional visits, that's
  a fine trade. This is what I used.
- Paid-tier platforms with a real free application tier for PHP (Railway, Render, Fly.io) kept
  coming up, but by the time I checked, every one of them had dropped free PHP hosting, free
  MySQL, or free compute entirely -- "free" with a credit card on file and a spending limit
  isn't what was asked for here.

If InfinityFree's reliability ever becomes the bottleneck, nothing about this app's structure
changes to move it elsewhere -- it's still plain PDO + MySQL + sessions. Swap hosts, update two
settings (below), done.

## A. Create the hosting account

1. Go to infinityfree.com, sign up (email + password, no card).
2. **Create Account** → pick a subdomain, e.g. `secureauth.infinityfreeapp.com` (free, instant)
   -- or point an existing domain at it later if you ever buy one. The subdomain is fine for
   this.
3. Accounts sometimes need a few minutes (occasionally longer) to finish provisioning before
   FTP/MySQL are ready. If FTP login fails right away, that's why -- wait and retry.

## B. Create the database

1. In the InfinityFree control panel, **MySQL Databases** → create one. Note what it gives
   you -- InfinityFree prefixes everything with your account ID, so you'll get something like:
   - Host: `sqlXXX.infinityfree.com` (not `localhost` -- copy it exactly)
   - Database: `epiz_XXXXXXXX_secure_auth_demo`
   - Username: `epiz_XXXXXXXX` (same username for every DB on the account)
   - Password: whatever you set
2. Open **phpMyAdmin** from the control panel, select that database, **Import** tab, choose
   this folder's `db/setup.sql`, **Go**. Table definitions only -- no `CREATE USER`, no
   `GRANT` -- so it imports cleanly through phpMyAdmin's limited privileges, the same way it
   does locally.

## C. Upload the app

InfinityFree gives you an `htdocs/` folder as the webroot. Upload the **contents** of this
`demos/secure-auth/` folder into it -- not the folder itself, its contents, so `register.php`
lands at `htdocs/register.php`, not `htdocs/secure-auth/register.php`.

1. Get FTP credentials from the control panel (**FTP Accounts**).
2. Connect with an FTP client -- [FileZilla](https://filezilla-project.org/) is free and does
   the job. Host, username, password from step 1; port 21.
3. Drag everything from `demos/secure-auth/` into `htdocs/`: `app/`, `config/`, `assets/`,
   `admin/`, `db/`, every `.php` file, `README.md`. Skip `DEPLOY.md` itself -- no reason to
   publish it.
4. This will take a few minutes -- it's a few hundred small files, and FTP isn't fast. Let it
   finish.

## D. Configure secrets

No environment variables here -- InfinityFree has nowhere to set them, so this app falls back
to a plain config file for exactly this situation (see `app/Core/Config.php`).

1. In `config/`, create `config.local.php` -- the safest way is to copy
   `config.local.php.example` (it's already valid PHP, already in the right folder) and edit
   the copy, rather than retyping this from scratch. The file must contain *only* what's shown
   below the line -- starting with `<?php` and ending with the closing `];` -- with no
   surrounding ` ```php ` fence; those triple backticks are Markdown formatting for this
   document, not part of the file. If you paste this block in, delete the fence lines, or PHP
   will fail to parse the file with a syntax error on line 1 (which, from a browser, looks
   exactly like "Something went wrong" with no hint why -- a mistake worth flagging because
   it's an easy one to make copying from here):
   ```php
   <?php
   return [
       'SAD_DB_HOST' => 'sqlXXX.infinityfree.com',      // from step B
       'SAD_DB_NAME' => 'epiz_XXXXXXXX_secure_auth_demo',
       'SAD_DB_USER' => 'epiz_XXXXXXXX',
       'SAD_DB_PASS' => 'whatever you set in step B',

       'SAD_SMTP_HOST' => 'smtp.gmail.com',
       'SAD_SMTP_PORT' => '587',
       'SAD_SMTP_SECURE' => 'tls',
       'SAD_SMTP_USER' => 'you@gmail.com',
       'SAD_SMTP_PASS' => 'your 16-char Gmail app password',   // see README.md "Email"
       'SAD_SMTP_FROM' => 'you@gmail.com',

       'SAD_ALLOWED_ORIGIN' => 'https://alviador.github.io',
   ];
   ```
2. SMTP isn't optional here the way it is on local XAMPP: InfinityFree disables PHP's
   `mail()` outright, so without real SMTP configured, invite emails and new-message
   notifications silently fail (the admin inbox still shows the credentials to copy by hand as
   a fallback -- see `renderInviteResult()` in `app/Views/admin/inbox.php` -- but real email is
   much nicer). The Gmail app-password steps are in README.md's "Email" section.
3. Double-check `config.local.php` actually uploaded -- some FTP clients skip dotfile-looking
   names or need a manual refresh to show new files.

## E. Turn on HTTPS

InfinityFree issues a free SSL certificate per subdomain, but it isn't automatic the moment
you sign up.

1. Control panel → **SSL Certificates**, issue one for your subdomain (usually instant to a
   few hours).
2. Once it's active, your app is reachable at `https://secureauth.infinityfreeapp.com/...`.
   This matters beyond "nice to have": GitHub Pages serves the portfolio over HTTPS, and a
   browser will silently block an `https://` page from calling an `http://` API as mixed
   content. Without this step, the contact form and login on the live portfolio just stop
   working, with no obvious error.

## F. Create the admin account

1. Visit `https://secureauth.infinityfreeapp.com/register.php` and register -- **in the
   browser you intend to administer the inbox from**. The first account becomes admin
   automatically, and its private encryption key is generated client-side and stored in that
   browser's `localStorage` only (see README.md's "one real tradeoff" note). There's no way to
   recover it from a different browser later, so get this one right.
2. Confirm login, dashboard, and (once there's a message to test with) the inbox all work
   before moving on.

## G. Point the portfolio at it

Two things change on the portfolio side, both of which were built in from the start for this
exact moment:

1. **`index.html`**, the `<meta name="secure-messaging-base">` tag:
   ```html
   <meta name="secure-messaging-base" content="https://secureauth.infinityfreeapp.com/">
   ```
   (trailing slash matters -- `app.js` appends paths onto it directly).
2. **`config/config.local.php`** on the server, `SAD_ALLOWED_ORIGIN` -- already set in step D,
   but double-check it's the portfolio's *exact* origin: `https://alviador.github.io`, no
   trailing slash, no `/portfolio` path (CORS origins are scheme + host + port only).

Commit and push the `index.html` change, confirm GitHub Pages has redeployed, then open the
live portfolio and test the contact form end to end -- that's the one feature that depends on
cross-origin CORS actually being configured correctly, so it's the real test that steps D and G
both landed.

## H. Two free-tier quirks I actually hit

Both of these are the hosting network's own edge layer, not this app's PHP code -- found by
deploying to `alviador.freehosting.dev`, a white-label brand on the same network as
InfinityFree (its error pages are served from `errors.infinityfree.net`, which gives it away).
If you're on a different free host, these may not apply at all -- but if something that looks
exactly like this happens, this is almost certainly why.

**Any URL path containing the word "chat" gets a flat 403, before PHP ever runs.** Not a typo,
not a permissions issue -- `chat.php`, `chat-list.php`, even a nonexistent `chatlist.php`, all
blocked identically at the edge. This is a keyword-based WAF rule, presumably aimed at the
shoutbox-spam abuse that's common on free hosting, that doesn't know or care this is a
legitimate app. There's no setting on the free tier to exempt a path from it. The fix was to
stop fighting it: the chat pages are named `threads.php` and `thread.php` here (matching the
`ChatThread` model's own terminology), not `chat*.php`, specifically so the word never appears
in a URL this host sees.

**Cross-origin `fetch()` calls can fail even with CORS configured correctly.** This host serves
a JavaScript "prove you're a browser" challenge to requests it hasn't seen before -- fine for a
normal page load (the browser runs the JS, gets a cookie, moves on), but `fetch()` from another
origin doesn't execute that JS; it just sees the challenge page's raw HTML with no
`Access-Control-Allow-Origin` header, and the browser blocks it as a CORS failure --
indistinguishable from this app's CORS actually being misconfigured, even though it isn't. This
hit `admin_public_key` and `contact_submit`, the two endpoints the portfolio's contact form
depends on, every single time I tested it, including from a browser that had already visited
the API host directly.

There's no server setting that fixes this -- the edge layer intercepts the request before
`api.php` ever runs, so nothing in `ApiController` reacting to it would help. The fix is
`bridge.php`: instead of the portfolio calling `api.php` directly cross-origin, it loads
`bridge.php` in a hidden iframe and talks to it with `postMessage`, which isn't subject to CORS
at all (it's a browser messaging API, not an HTTP request the edge layer can intercept).
`bridge.php` then makes its own call to `api.php` -- same-origin, since both live on this
server -- which the bot-check has no reason to touch. See `assets/js/app.js`
(`IS_CROSS_ORIGIN_BACKEND`, `bridgeRequest()`) for the portfolio side. This is automatic: it
only activates when `secure-messaging-base` points at a different origin than the page it's
running on, so local same-origin testing is untouched.

`bridge.php` only answers messages from the exact origin in `SAD_ALLOWED_ORIGIN` -- the same
setting CORS already uses -- so if this still isn't working, that's the first thing to check:
open the browser console on the live portfolio and confirm there's no `event.origin` mismatch
logged, and that `SAD_ALLOWED_ORIGIN` in `config.local.php` is exactly `https://alviador.github.io`
with no trailing slash.

## I. Updating an already-deployed install

`git push` only updates GitHub -- it never touches the live PHP host, since there's no CI/CD
wired up here (free tier, nothing to wire it to). Every change to this folder needs two steps:
commit and push as usual, *and* re-upload the changed files to the host over FTP. Easy to
forget, since the first step feels like "done."

If a change added a new database table or column (check `db/migrations/` -- anything dated
after your last deploy needs running), open phpMyAdmin's SQL tab on the live database and run
that migration file's contents once. It's written to be safe to run on a database with existing
data (`CREATE TABLE IF NOT EXISTS`, nothing destructive).

Voice/video calling is the first thing that needed this: `db/migrations/2026-10-01-call-signals.sql`
adds the `call_signals` table (encrypted offer/answer/ICE columns -- ciphertext, iv,
wrapped_key, not plain JSON), and `app/Core/SecurityHeaders.php` changed what it sends for
`Permissions-Policy` (microphone *and* camera access -- miss the camera one and video calls
fail with a silent permissions error that looks nothing like a camera problem) and
`Content-Security-Policy` (STUN/TURN hosts in `connect-src`). Re-upload the whole `app/`
folder, `assets/call-client.js`, and `app/Views/chat/room.php`, then run that migration, before
calling will work on the live site.

Encrypted file attachments are the second: `db/migrations/2026-10-02-chat-attachments.sql` adds
a `type` column to `chat_messages` and the `chat_attachments` table (ciphertext/iv/wrapped keys
only -- deliberately no filename/mime type/size columns, see the README). It also needs
`img-src` in `app/Core/SecurityHeaders.php`'s CSP to include `blob:` (attachment thumbnails are
rendered from a decrypted `blob:` URL, not a normal image URL) -- re-upload `app/`,
`app/Views/chat/room.php`, and `assets/crypto-client.js`, then run that migration, before
attachments work on the live site.

Read receipts and in-thread call history are the third:
`db/migrations/2026-10-03-receipts-call-log.sql` adds `delivered_at`/`seen_at` to
`chat_messages` and extends its `type` enum with `call_log`. No header or CSP changes this
time -- just re-upload `app/`, `app/Views/chat/room.php`, and `assets/style.css`, then run that
migration, before sent/delivered/seen ticks or call-log entries show up on the live site. An
already-deployed thread with older messages is unaffected -- they just show no receipt state
until someone sends something new, same as any other additive column.

## Checklist

- [ ] InfinityFree account created, subdomain active
- [ ] Database created, `db/setup.sql` imported via phpMyAdmin
- [ ] Contents of `demos/secure-auth/` uploaded to `htdocs/` (not nested a level deeper)
- [ ] `config/config.local.php` created on the server with real DB + SMTP + CORS values
- [ ] SSL certificate issued and active (site loads on `https://`)
- [ ] Admin account registered, in the browser meant to administer the inbox
- [ ] `index.html`'s `secure-messaging-base` meta tag updated and pushed
- [ ] Contact form tested live, end to end, from the actual GitHub Pages URL

## If something's wrong

Every page here has a built-in safety net for exactly this situation: an uncaught error (wrong
DB credentials, unreachable host, anything) renders a plain styled error page instead of a
blank white screen -- see the `set_exception_handler()` in `config/bootstrap.php`. The message
tells you which kind it is:

- **"The database isn't reachable right now"** -- step B or D: wrong `SAD_DB_HOST`/`SAD_DB_NAME`/
  `SAD_DB_USER`/`SAD_DB_PASS` in `config.local.php`, or `db/setup.sql` was never imported.
- **"An unexpected error occurred"** (the generic one) -- usually something in `config.local.php`
  itself before the database is even reached. The single most common cause: the Markdown code
  fence (` ```php `) from step D got copied into the file along with the real content, which
  is a PHP syntax error on line 1. Open the file and confirm the very first line is `<?php`,
  nothing before it. A missing PHP extension (rare on InfinityFree, more likely on a stricter
  host) can also land here -- this app includes a fallback for a missing `mbstring` extension
  specifically (`app/Core/Compat.php`), since that's the one most free hosts occasionally ship
  without.

Checking the actual cause instead of guessing: InfinityFree's control panel has an error log
viewer under **Error Logs** -- every uncaught error is logged there with the real exception
class and message, which is the fastest way to know which of the above you're looking at.
