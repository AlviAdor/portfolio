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

1. In `config/`, create `config.local.php` -- either edit `config.local.php.example` locally
   and re-upload it renamed, or create it directly in InfinityFree's File Manager. Fill in:
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
blank white screen -- see the `set_exception_handler()` in `config/bootstrap.php`. If you land
on that, it's almost always step B or D: the database isn't reachable, or `config.local.php`
has a typo. InfinityFree's control panel also has an error log viewer under **Error Logs** if
the on-page message isn't enough.
