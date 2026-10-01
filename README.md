# Alvi Ador — Portfolio

My personal portfolio: a cybersecurity-focused developer site built as plain HTML, CSS, and
JavaScript — no framework, no build step. It covers who I am, the projects I've shipped, and a
breakdown of how I think about security across authentication, validation, data integrity, and
messaging.

**Live structure:**

```
index.html                     Home page: hero, projects, security overview, about, contact
security-authentication.html   Deep dive: authentication
security-validation.html       Deep dive: input validation
security-data-integrity.html   Deep dive: data integrity
security-messaging.html        Deep dive: secure messaging
assets/css/styles.css          One stylesheet, light + dark themes via CSS custom properties
assets/js/app.js               Theme toggle, nav, contact form, scroll reveal, cookie prefs
assets/js/theme-init.js        Picks light/dark before first paint, avoids a flash of the wrong theme
assets/img/                    Project screenshots, visuals
demos/secure-auth/             A real, working PHP + MySQL app — see below
```

Nothing here needs `npm install` or a bundler. Open `index.html` in a browser, or point any
static server (or XAMPP's Apache) at this folder.

## Why `demos/secure-auth/` exists

The Security section of this site makes specific claims — rate limiting, parameterized
queries, session hardening, encrypted messaging. I got tired of security pages that just
*assert* this stuff with no evidence, so I built a real app to back it up instead of writing
another paragraph about best practices. It's a PHP + MySQL registration/login system with a
zero-knowledge encrypted contact inbox and an end-to-end encrypted chat, and the portfolio's
own contact form talks to it directly.

Full details, setup, and how to log in as the admin are in
[`demos/secure-auth/README.md`](demos/secure-auth/README.md) — I'm planning to split that
folder into its own repository eventually (it's fully self-contained already, no shared code
with this one), so I kept its documentation independent on purpose.

## Running this locally

1. Clone or copy this folder into your web server's document root (or just open `index.html`
   directly — the static pages don't need a server at all).
2. If you want the contact form to actually encrypt and deliver messages, set up
   `demos/secure-auth/` per its own README — it needs PHP and MySQL (XAMPP covers both). If
   you skip this, the contact form still works, it just falls back to a plain `mailto:` link.

## Deploying

The static site (everything except `demos/secure-auth/`) lives on **GitHub Pages**, at
`alviador.github.io/portfolio` — plain HTML/CSS/JS, so it's a straight push, no build step.
([Vercel](https://vercel.com) works the same way, zero config, if I ever want a custom domain
without DNS pointed at GitHub.)

`demos/secure-auth/` needs an actual PHP + MySQL runtime, which GitHub Pages can't provide —
that piece lives on its own, on free PHP + MySQL shared hosting (InfinityFree). Full
walkthrough in [`demos/secure-auth/DEPLOY.md`](demos/secure-auth/DEPLOY.md). Once it's up,
this site points at it through exactly one setting: the `<meta name="secure-messaging-base">`
tag near the top of `index.html`. Everything else (CORS, the contact form, the dynamic script
loading) was already built to work across two different domains, not just one.

## Contact

`sarker.faizal2537@gmail.com` — or just use the contact form, it's actually encrypted now.
