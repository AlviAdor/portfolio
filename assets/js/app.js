// Main site JavaScript (moved to assets/js/app.js)
const body = document.body;
const intro = document.querySelector("[data-intro]");
const introGreeting = document.querySelector("[data-intro-greeting]");
const preloader = document.querySelector("[data-preloader]");
const nav = document.querySelector("[data-nav]");
const navToggle = document.querySelector("[data-nav-toggle]");
const panel = document.querySelector("[data-access-panel]");
const panelToggles = Array.from(document.querySelectorAll("[data-panel-toggle]"));
const panelClose = document.querySelector("[data-panel-close]");
const themeToggles = Array.from(document.querySelectorAll("[data-theme-toggle]"));
const greeting = document.querySelector("[data-greeting]");
const form = document.querySelector("[data-contact-form]");
const overlay = document.querySelector("[data-message-overlay]");
const messageClose = document.querySelector("[data-message-close]");
const mailtoLink = document.querySelector("[data-mailto-link]");
const cookieBanner = document.querySelector("[data-cookie-banner]");
const isHomePage = location.pathname.endsWith("index.html") || location.pathname.endsWith("/") || location.pathname === "";
const introSeenKey = "portfolio_intro_seen";
const consentKey = "portfolio_cookie_consent";

// Read from <meta name="secure-messaging-base">, so moving the secure-auth demo
// to its own repo/domain is a one-line change in index.html -- nothing here.
const SECURE_MESSAGING_BASE =
    document.querySelector('meta[name="secure-messaging-base"]')?.content || "demos/secure-auth/";

// Every nav link that says "Login" points at wherever that meta tag says the
// secure-auth app actually lives, so it keeps working after the move too.
document.querySelectorAll("[data-login-link]").forEach((link) => {
    link.setAttribute("href", `${SECURE_MESSAGING_BASE}login.php`);
});

// Ensure intro is shown again by clearing any previously persisted 'seen' flag
safeStorageSet(localStorage, introSeenKey, "false");

const greetings = ["Greetings", "Hello", "Assalamu alaikum", "Hola", "Salut", "Ciao", "Merhaba", "Xin chao", "你好", "مرحبا", "Привет", "Bonjour"];
const greetingAccents = ["#0f766e", "#5b6b7a", "#7c7f84", "#ecebe6", "#6b7280", "#81aefc", "#9b8cff"];
let greetingIndex = 0;

function safeStorageGet(storage, key) {
    try {
        return storage.getItem(key);
    } catch (error) {
        return null;
    }
}

function safeStorageSet(storage, key, value) {
    try {
        storage.setItem(key, value);
    } catch (error) {
        return;
    }
}

function setGreetingAccent(index) {
    document.documentElement.style.setProperty("--greeting-accent", greetingAccents[index % greetingAccents.length]);
}

// Handle back links that should skip intro
document.querySelectorAll("[data-skip-intro]").forEach((link) => {
    link.addEventListener("click", () => {
        sessionStorage.setItem("skipIntro", "true");
    });
});

// Ensure nav is hidden to assistive tech by default unless purposely opened
if (nav && !nav.classList.contains('is-open')) {
    nav.setAttribute('aria-hidden', 'true');
    nav.hidden = true;
}

function dismissIntro() {
    if (!intro) return;
    intro.classList.add('is-hidden');
}

function setCookie(name, value, days) {
    const expires = new Date(Date.now() + days * 864e5).toUTCString();
    document.cookie = `${name}=${encodeURIComponent(value)}; expires=${expires}; path=/; SameSite=Lax`;
}

function getCookie(name) {
    return document.cookie
        .split("; ")
        .find((row) => row.startsWith(`${name}=`))
        ?.split("=")[1];
}

function hasCookieConsent() {
    const consent = safeStorageGet(localStorage, "portfolio_cookie_consent") || decodeURIComponent(getCookie("portfolio_cookie_consent") || "");
    return consent === "accepted";
}

function applySavedPreferences() {
    const localTheme = safeStorageGet(localStorage, "portfolio_theme") || "";
    const themeCookie = decodeURIComponent(getCookie("portfolio_theme") || "");
    const hour = new Date().getHours();
    const defaultTheme = hour >= 7 && hour < 18 ? "light" : "dark";
    const theme = localTheme || themeCookie || defaultTheme;
    const readable = safeStorageGet(localStorage, "portfolio_readable") || decodeURIComponent(getCookie("portfolio_readable") || "");
    const reduced = safeStorageGet(localStorage, "portfolio_reduced_motion") || decodeURIComponent(getCookie("portfolio_reduced_motion") || "");
    const contrast = safeStorageGet(localStorage, "portfolio_high_contrast") || decodeURIComponent(getCookie("portfolio_high_contrast") || "");
    const scale = safeStorageGet(localStorage, "portfolio_text_scale") || decodeURIComponent(getCookie("portfolio_text_scale") || "1");

    document.documentElement.dataset.theme = theme;
    body.classList.toggle("dark", theme === "dark");
    if (readable === "true") body.classList.add("readable");
    if (reduced === "true") body.classList.add("reduce-motion");
    if (contrast === "true") body.classList.add("high-contrast");
    body.style.setProperty("--scale", scale);

    const readableInput = document.querySelector("[data-readable-font]");
    const reducedInput = document.querySelector("[data-reduced-motion]");
    const contrastInput = document.querySelector("[data-high-contrast]");
    const textSizeInput = document.querySelector("[data-text-size]");

    if (readableInput) readableInput.checked = readable === "true";
    if (reducedInput) reducedInput.checked = reduced === "true";
    if (contrastInput) contrastInput.checked = contrast === "true";
    if (textSizeInput) textSizeInput.value = scale === "1.16" ? "2" : scale === "1.08" ? "1" : "0";
}

function savePreference(name, value) {
    if (name === "portfolio_theme" || name === "portfolio_readable" || name === "portfolio_reduced_motion" || name === "portfolio_high_contrast" || name === "portfolio_text_scale") {
        safeStorageSet(localStorage, name, String(value));
    }
    if (hasCookieConsent()) {
        setCookie(name, String(value), 180);
    }
}

// Apply saved preferences (or sensible defaults) even before cookie consent UI.
applySavedPreferences();

// Ensure the page starts at the top instead of restoring a deep scroll position.
if ('scrollRestoration' in history) {
    history.scrollRestoration = 'manual';
}
const scrollToTop = () => window.scrollTo({ top: 0, left: 0, behavior: 'auto' });
scrollToTop();
window.addEventListener('load', () => {
    requestAnimationFrame(scrollToTop);
    setTimeout(scrollToTop, 0);
});
window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        requestAnimationFrame(scrollToTop);
    }
});

// Update theme toggle icon to reflect current theme
// The icon shows what clicking it will switch you TO, not the mode you're
// currently in -- a sun while dark (click for light), a moon while light
// (click for dark). Showing a moon while already in dark mode, like this did
// before, reads backwards: the icon should say where the click takes you.
function updateThemeIcon() {
    if (!themeToggles.length) return;
    const sunIcon = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">'
        + '<circle cx="12" cy="12" r="4.5" fill="currentColor"/>'
        + '<g stroke="currentColor" stroke-width="1.8" stroke-linecap="round">'
        + '<line x1="12" y1="1.5" x2="12" y2="4"/><line x1="12" y1="20" x2="12" y2="22.5"/>'
        + '<line x1="1.5" y1="12" x2="4" y2="12"/><line x1="20" y1="12" x2="22.5" y2="12"/>'
        + '<line x1="4.2" y1="4.2" x2="5.9" y2="5.9"/><line x1="18.1" y1="18.1" x2="19.8" y2="19.8"/>'
        + '<line x1="4.2" y1="19.8" x2="5.9" y2="18.1"/><line x1="18.1" y1="5.9" x2="19.8" y2="4.2"/>'
        + '</g></svg>';
    const moonIcon = '<svg width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" fill="currentColor"/></svg>';
    const icon = body.classList.contains('dark') ? sunIcon : moonIcon;
    themeToggles.forEach((button) => {
        // Replace only the <svg> -- the mobile menu's toggle also carries a
        // "Theme" <span> label next to it, and innerHTML would have wiped
        // that out along with the icon.
        const existingSvg = button.querySelector('svg');
        if (existingSvg) {
            existingSvg.outerHTML = icon;
        } else {
            button.insertAdjacentHTML('beforeend', icon);
        }
        button.setAttribute('aria-label', body.classList.contains('dark') ? 'Switch to light mode' : 'Switch to dark mode');
    });
}
updateThemeIcon();

const storedConsent = safeStorageGet(localStorage, consentKey) || decodeURIComponent(getCookie(consentKey) || "");

function showCookieBanner() {
    if (!cookieBanner) return;
    cookieBanner.hidden = false;
    cookieBanner.classList.add("is-open");
}

function hideCookieBanner() {
    if (!cookieBanner) return;
    cookieBanner.classList.remove("is-open");
    cookieBanner.hidden = true;
}

if (storedConsent) {
    hideCookieBanner();
} else if (cookieBanner) {
    cookieBanner.hidden = true;
}

// Show intro with animated greeting (only on initial page load, not on returns from other pages)
const introSeen = safeStorageGet(localStorage, introSeenKey) === "true";
if (intro && !isHomePage) {
    intro.hidden = true;
}

if (introGreeting && isHomePage && !introSeen && !sessionStorage.getItem("skipIntro")) {
    safeStorageSet(localStorage, introSeenKey, "true");
    introGreeting.textContent = greetings[greetingIndex];
    setGreetingAccent(greetingIndex);
    setInterval(() => {
        if (!introGreeting) return;
        greetingIndex = (greetingIndex + 1) % greetings.length;
        introGreeting.textContent = greetings[greetingIndex];
        setGreetingAccent(greetingIndex);
    }, 1800);
} else if (intro) {
    intro.classList.add("is-hidden");
    intro.hidden = true;
}

if (!storedConsent) {
    const promptDelay = intro && !intro.classList.contains('is-hidden') && isHomePage && !introSeen ? 4600 : 700;
    setTimeout(() => {
        if (!safeStorageGet(localStorage, consentKey)) {
            showCookieBanner();
        }
    }, promptDelay);
}

if (greeting) {
    setGreetingAccent(greetingIndex);
}

// Auto-dismiss intro and preloader after 5 seconds -- but only on the home page,
// and only when the greeting intro is actually about to play. Every other page
// (a security deep-dive reached via "Read more", or a return visit) has nothing
// to wait for, so it dismisses almost immediately instead of sitting on a loading
// screen for no reason.
const dismissDelay = (!isHomePage || sessionStorage.getItem("skipIntro")) ? 100 : 4200;
setTimeout(() => {
    if (intro && !intro.classList.contains('is-hidden')) dismissIntro();
    // Hide preloader shortly after intro dismisses
    setTimeout(() => {
        preloader?.classList.add("is-hidden");
    }, 260);
}, dismissDelay);

// Fallback: if page loads before timeout, hide preloader
window.addEventListener("load", () => {
    setTimeout(() => {
        preloader?.classList.add("is-hidden");
    }, 450);
});

setInterval(() => {
    if (!greeting) return;
    greetingIndex = (greetingIndex + 1) % greetings.length;
    greeting.textContent = greetings[greetingIndex];
    setGreetingAccent(greetingIndex);
}, 2400);

themeToggles.forEach((button) => {
    button.addEventListener("click", () => {
        body.classList.toggle("dark");
        savePreference("portfolio_theme", body.classList.contains("dark") ? "dark" : "light");
        updateThemeIcon();
    });
});

const navBack = document.querySelector('[data-nav-back]');
let _lastFocusedBeforeNav = null;
let _navKeyHandler = null;
let _navHideTimer = null;
const navLinks = Array.from(document.querySelectorAll('[data-nav-link]'));
const trackedSections = Array.from(['home', 'projects', 'security', 'about', 'contact']
    .map((id) => document.getElementById(id))
    .filter(Boolean));

function setActiveNav(sectionId) {
    if (!navLinks.length) return;
    navLinks.forEach((link) => {
        const isActive = link.getAttribute('href') === `#${sectionId}`;
        link.classList.toggle('is-active', isActive);
        if (isActive) link.setAttribute('aria-current', 'page');
        else link.removeAttribute('aria-current');
    });
}

setActiveNav(location.hash.replace('#', '') || 'home');

if (trackedSections.length) {
    const sectionObserver = new IntersectionObserver((entries) => {
        let visibleSection = null;
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                visibleSection = entry.target.id;
            }
        });
        if (visibleSection) setActiveNav(visibleSection);
    }, {
        rootMargin: '-35% 0px -55% 0px',
        threshold: 0.01,
    });

    trackedSections.forEach((section) => sectionObserver.observe(section));

    window.addEventListener('hashchange', () => {
        const id = location.hash.replace('#', '') || 'home';
        setActiveNav(id);
    });
}

function getFocusable(container) {
    if (!container) return [];
    const selectors = 'a[href], button:not([disabled]), input:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
    return Array.from(container.querySelectorAll(selectors)).filter((el) => el.offsetWidth || el.offsetHeight || el.getClientRects().length);
}

function openNav() {
    if (!nav) return;
    if (_navHideTimer) {
        clearTimeout(_navHideTimer);
        _navHideTimer = null;
    }
    _lastFocusedBeforeNav = document.activeElement;
    nav.hidden = false;
    nav.classList.add('is-open');
    nav.setAttribute('aria-hidden', 'false');
    navToggle.classList.add('is-open');
    navToggle.setAttribute('aria-expanded', 'true');
    navToggle.setAttribute('aria-label', 'Close navigation');
    panel?.classList.remove('is-open');

    const focusables = getFocusable(nav);
    if (focusables.length) focusables[0].focus();

    _navKeyHandler = function(e) {
        if (e.key === 'Tab') {
            const f = getFocusable(nav);
            if (f.length === 0) { e.preventDefault(); return; }
            const first = f[0];
            const last = f[f.length - 1];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        }
        if (e.key === 'Escape') {
            closeNav(true);
        }
    };
    document.addEventListener('keydown', _navKeyHandler);
}

function closeNav(returnFocus = true) {
    if (!nav) return;
    nav.classList.remove('is-open');
    nav.setAttribute('aria-hidden', 'true');
    navToggle.classList.remove('is-open');
    navToggle.setAttribute('aria-expanded', 'false');
    navToggle.setAttribute('aria-label', 'Open navigation');
    if (_navKeyHandler) {
        document.removeEventListener('keydown', _navKeyHandler);
        _navKeyHandler = null;
    }
    if (_navHideTimer) clearTimeout(_navHideTimer);
    _navHideTimer = window.setTimeout(() => {
        if (!nav.classList.contains('is-open')) {
            nav.hidden = true;
        }
    }, 340);
    if (returnFocus && _lastFocusedBeforeNav && typeof _lastFocusedBeforeNav.focus === 'function') {
        _lastFocusedBeforeNav.focus();
    }
    _lastFocusedBeforeNav = null;
}

navToggle?.addEventListener('click', () => {
    if (nav?.classList.contains('is-open')) closeNav(true);
    else openNav();
});

// Close when clicking the backdrop area (nav element itself)
nav?.addEventListener('click', (e) => {
    if (e.target === nav) closeNav(true);
});

navBack?.addEventListener('click', () => closeNav(true));

// New back button inside the nav list (back-to-top behavior)
const navHomeBack = document.querySelector('.nav-homeback');
navHomeBack?.addEventListener('click', () => {
    closeNav(true);
    scrollToTop();
});

// Header back buttons (pages that show a brand link replace it with a header-back button)
const headerBacks = Array.from(document.querySelectorAll('[data-header-back]'));
headerBacks.forEach((btn) => {
    btn.addEventListener('click', () => {
        // Prefer a normal back in history; fall back to home if none
        if (history.length > 1) history.back();
        else window.location.href = '/';
    });
});

nav?.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => closeNav(true));
});

panelToggles.forEach((button) => {
    button.addEventListener("click", () => {
        panel?.classList.toggle("is-open");
        nav?.classList.remove("is-open");
        navToggle?.classList.remove("is-open");
        navToggle?.setAttribute("aria-expanded", "false");
        if (nav) nav.hidden = true;
    });
});

panelClose?.addEventListener("click", () => panel?.classList.remove("is-open"));

document.querySelector("[data-readable-font]")?.addEventListener("change", (event) => {
    body.classList.toggle("readable", event.target.checked);
    savePreference("portfolio_readable", event.target.checked);
});

document.querySelector("[data-reduced-motion]")?.addEventListener("change", (event) => {
    body.classList.toggle("reduce-motion", event.target.checked);
    savePreference("portfolio_reduced_motion", event.target.checked);
});

document.querySelector("[data-high-contrast]")?.addEventListener("change", (event) => {
    body.classList.toggle("high-contrast", event.target.checked);
    savePreference("portfolio_high_contrast", event.target.checked);
});

document.querySelector("[data-text-size]")?.addEventListener("input", (event) => {
    const scale = ["1", "1.08", "1.16"][Number(event.target.value)] || "1";
    body.style.setProperty("--scale", scale);
    savePreference("portfolio_text_scale", scale);
});

document.querySelector("[data-reset-access]")?.addEventListener("click", () => {
    body.classList.remove("readable", "reduce-motion", "high-contrast");
    body.style.setProperty("--scale", "1");
    document.querySelectorAll(".access-panel input").forEach((input) => {
        if (input.type === "checkbox") input.checked = false;
        if (input.type === "range") input.value = "0";
    });
    savePreference("portfolio_readable", false);
    savePreference("portfolio_reduced_motion", false);
    savePreference("portfolio_high_contrast", false);
    savePreference("portfolio_text_scale", "1");
});

function setError(name, message) {
    const error = document.querySelector(`[data-error-for="${name}"]`);
    if (error) error.textContent = message;
}

let cryptoClientLoad = null;
function loadCryptoClient() {
    if (typeof SecureCrypto !== "undefined") return Promise.resolve(true);
    if (cryptoClientLoad) return cryptoClientLoad;
    cryptoClientLoad = new Promise((resolve) => {
        const script = document.createElement("script");
        script.src = `${SECURE_MESSAGING_BASE}assets/crypto-client.js`;
        script.onload = () => resolve(true);
        script.onerror = () => resolve(false);
        document.head.appendChild(script);
    });
    return cryptoClientLoad;
}

// Encrypts the message in-browser with the admin's public key (RSA-OAEP wraps a
// one-time AES-256-GCM key) and submits only ciphertext. Returns true if the
// encrypted send succeeded, false if it quietly fell back (e.g. demo backend not
// set up on this deployment) so the mailto fallback still works either way.
async function sendEncryptedContactMessage(name, email, message) {
    try {
        if (!window.crypto?.subtle) return false;
        const loaded = await loadCryptoClient();
        if (!loaded || typeof SecureCrypto === "undefined") return false;

        const keyRes = await fetch(`${SECURE_MESSAGING_BASE}api.php?action=admin_public_key`);
        const keyData = await keyRes.json();
        if (!keyData.ok) return false;

        const enc = await SecureCrypto.encryptForRecipient(message, keyData.publicKey);
        const body = new URLSearchParams({
            name, email,
            ciphertext: enc.ciphertext,
            iv: enc.iv,
            wrappedKey: enc.wrappedKey,
        });
        const sendRes = await fetch(`${SECURE_MESSAGING_BASE}api.php?action=contact_submit`, {
            method: "POST",
            body,
        });
        const sendData = await sendRes.json();
        return !!sendData.ok;
    } catch (error) {
        return false;
    }
}

form?.addEventListener("submit", (event) => {
    event.preventDefault();
    const data = new FormData(form);
    const name = String(data.get("name") || "").trim();
    const email = String(data.get("email") || "").trim();
    const message = String(data.get("message") || "").trim();
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    setError("name", "");
    setError("email", "");
    setError("message", "");

    let valid = true;
    if (name.length < 2 || name.length > 60) {
        setError("name", "Please enter your name.");
        form.elements.name?.focus();
        valid = false;
    }
    if (!emailPattern.test(email) || email.length > 120) {
        setError("email", "Please enter a valid email.");
        if (valid) form.elements.email?.focus();
        valid = false;
    }
    if (message.length < 10 || message.length > 1000) {
        setError("message", "Please enter at least 10 characters.");
        if (valid) form.elements.message?.focus();
        valid = false;
    }
    if (!valid) return;

    const subject = encodeURIComponent(`Portfolio inquiry from ${name}`);
    const bodyText = encodeURIComponent(`From: ${name}\nEmail: ${email}\n\n${message}`);
    mailtoLink?.setAttribute("href", `mailto:sarker.faizal2537@gmail.com?subject=${subject}&body=${bodyText}`);

    const overlayNote = document.querySelector("[data-overlay-note]");
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) submitBtn.disabled = true;

    sendEncryptedContactMessage(name, email, message)
        .then((encrypted) => {
            if (overlayNote) {
                overlayNote.textContent = encrypted
                    ? "This message was end-to-end encrypted in your browser (RSA-OAEP + AES-256-GCM) before it ever left your device -- only the admin, signed in with their own password, can decrypt it."
                    : "Your message is ready to send. Use the button below to email it directly.";
            }
            overlay?.classList.add("is-open");
            form.reset();
        })
        .finally(() => {
            if (submitBtn) submitBtn.disabled = false;
        });
});

messageClose?.addEventListener("click", () => overlay?.classList.remove("is-open"));

document.querySelector("[data-cookie-accept]")?.addEventListener("click", () => {
    safeStorageSet(localStorage, consentKey, "accepted");
    setCookie(consentKey, "accepted", 180);
    savePreference("portfolio_theme", body.classList.contains("dark") ? "dark" : "light");
    savePreference("portfolio_readable", body.classList.contains("readable"));
    savePreference("portfolio_reduced_motion", body.classList.contains("reduce-motion"));
    savePreference("portfolio_high_contrast", body.classList.contains("high-contrast"));
    savePreference("portfolio_text_scale", getComputedStyle(body).getPropertyValue("--scale").trim() || "1");
    hideCookieBanner();
});

document.querySelector("[data-cookie-decline]")?.addEventListener("click", () => {
    safeStorageSet(localStorage, consentKey, "declined");
    setCookie(consentKey, "declined", 30);
    hideCookieBanner();
});

document.querySelector("[data-cookie-manage]")?.addEventListener("click", () => {
    showCookieBanner();
});

document.addEventListener("keydown", (event) => {
    if (event.key !== "Escape") return;
    nav?.classList.remove("is-open");
    panel?.classList.remove("is-open");
    overlay?.classList.remove("is-open");
    navToggle?.classList.remove("is-open");
    navToggle?.setAttribute("aria-expanded", "false");
});

// Smooth scroll to contact form and focus first field
document.querySelectorAll('[data-contact-scroll]').forEach((btn) => {
    btn.addEventListener('click', (e) => {
        const contact = document.querySelector('#contact');
        if (!contact) return;
        contact.scrollIntoView({ behavior: 'smooth', block: 'start' });
        // focus the first input after a short delay
        setTimeout(() => {
            const first = contact.querySelector('input, textarea, button');
            if (first) first.focus({ preventScroll: true });
        }, 550);
    });
});

// Simple scroll reveal inspired by modern sites (IntersectionObserver)
(() => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    const targets = Array.from(document.querySelectorAll(
        '.section-intro, .project-card, .method-grid article, .hero-copy, .hero-visual, .security-grid article, .project-media, .about-copy, .skill-cloud, .contact-form, .screenshot-grid figure'
    ));

    // assign --i variables for simple stagger inside containers and pick animation variant
    targets.forEach((el) => {
        if (el.children && el.children.length > 1) {
            Array.from(el.children).forEach((child, i) => child.style.setProperty('--i', String(i)));
        }
        let variant = 'reveal-slide-up';
        if (el.matches('.hero-visual, .project-media, .screenshot-grid figure')) variant = 'reveal-zoom';
        else if (el.matches('.hero-copy, .section-intro, .project-copy, .about-copy')) variant = 'reveal-slide-left';
        else if (el.matches('.project-card, .method-grid article, .security-grid article, .contact-card')) variant = 'reveal-slide-up';
        el.classList.add('reveal-target', variant);
    });

    const obs = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-revealed');
                if (entry.target.children && entry.target.children.length > 1) entry.target.classList.add('reveal-stagger');
            } else {
                entry.target.classList.remove('is-revealed', 'reveal-stagger');
            }
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });

    targets.forEach((t) => obs.observe(t));
})();
