/*
 * Client-side, zero-knowledge crypto -- a synced copy of
 * demos/secure-auth/assets/crypto-client.js, served from the portfolio's
 * own origin instead of the backend's.
 *
 * Why a copy instead of just loading the original cross-origin: the contact
 * form used to fetch this file directly from the backend origin via a
 * <script src> tag, which -- same as any other cross-origin request to that
 * host -- gets intercepted by its anti-bot edge layer and served a JS
 * challenge page instead of the real script. A <script src> load doesn't
 * execute a challenge page's JS the way a real navigation does, so that
 * request just silently "succeeded" with garbage content, SecureCrypto
 * never got defined, and the contact form fell back to the mailto link on
 * every single submission. This file has zero server dependency -- it's
 * pure Web Crypto API calls -- so there's no reason it needs to come from
 * the backend's origin at all. Loading it same-origin sidesteps the
 * cross-origin request (and the anti-bot layer) entirely.
 *
 * Keep this in sync with the backend's copy by hand when one changes --
 * there's no build step here to do it automatically.
 *
 * The server never sees a private key or a plaintext message. Every account's
 * RSA-OAEP keypair is generated in the browser (Web Crypto API). The public key
 * is uploaded to the server; the private key is encrypted at rest (AES-256-GCM,
 * key derived from the account password via PBKDF2-SHA256) and kept only in this
 * browser's localStorage. Unlocking it for the current tab session caches the raw
 * key bytes in sessionStorage so you aren't re-prompted on every page -- closing
 * the tab clears it, as does Sign out.
 */
const SecureCrypto = (() => {
    const RSA_PARAMS = { name: 'RSA-OAEP', modulusLength: 2048, publicExponent: new Uint8Array([1, 0, 1]), hash: 'SHA-256' };
    const PBKDF2_ITERATIONS = 150000;

    function b64encode(buf) {
        const bytes = new Uint8Array(buf);
        let bin = '';
        for (let i = 0; i < bytes.byteLength; i++) bin += String.fromCharCode(bytes[i]);
        return btoa(bin);
    }

    function b64decode(b64) {
        const bin = atob(b64);
        const bytes = new Uint8Array(bin.length);
        for (let i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);
        return bytes.buffer;
    }

    function randomBytes(len) {
        const arr = new Uint8Array(len);
        crypto.getRandomValues(arr);
        return arr;
    }

    async function deriveAesKeyFromPassword(password, saltBytes) {
        const baseKey = await crypto.subtle.importKey('raw', new TextEncoder().encode(password), 'PBKDF2', false, ['deriveKey']);
        return crypto.subtle.deriveKey(
            { name: 'PBKDF2', salt: saltBytes, iterations: PBKDF2_ITERATIONS, hash: 'SHA-256' },
            baseKey,
            { name: 'AES-GCM', length: 256 },
            false,
            ['encrypt', 'decrypt']
        );
    }

    function storageKey(email) {
        return 'sad_identity_' + email.trim().toLowerCase();
    }

    function sessionKey(email) {
        return 'sad_unlocked_' + email.trim().toLowerCase();
    }

    // Generates a fresh keypair, stores the encrypted private key in localStorage,
    // and returns the base64 SPKI public key to upload to the server.
    async function setupIdentity(email, password) {
        const keypair = await crypto.subtle.generateKey(RSA_PARAMS, true, ['encrypt', 'decrypt']);
        const publicKeyRaw = await crypto.subtle.exportKey('spki', keypair.publicKey);
        const privateKeyRaw = await crypto.subtle.exportKey('pkcs8', keypair.privateKey);

        const salt = randomBytes(16);
        const iv = randomBytes(12);
        const aesKey = await deriveAesKeyFromPassword(password, salt);
        const encryptedPrivateKey = await crypto.subtle.encrypt({ name: 'AES-GCM', iv }, aesKey, privateKeyRaw);

        localStorage.setItem(storageKey(email), JSON.stringify({
            salt: b64encode(salt),
            iv: b64encode(iv),
            encryptedPrivateKey: b64encode(encryptedPrivateKey),
        }));

        sessionStorage.setItem(sessionKey(email), b64encode(privateKeyRaw));

        return b64encode(publicKeyRaw);
    }

    function hasLocalIdentity(email) {
        return localStorage.getItem(storageKey(email)) !== null;
    }

    // Decrypts the stored private key with the account password and caches the
    // raw bytes in sessionStorage for the rest of this tab session.
    async function unlockIdentity(email, password) {
        const raw = localStorage.getItem(storageKey(email));
        if (!raw) throw new Error('No secure key found for this account in this browser.');
        const blob = JSON.parse(raw);
        const salt = new Uint8Array(b64decode(blob.salt));
        const iv = new Uint8Array(b64decode(blob.iv));
        const aesKey = await deriveAesKeyFromPassword(password, salt);
        let privateKeyRaw;
        try {
            privateKeyRaw = await crypto.subtle.decrypt({ name: 'AES-GCM', iv }, aesKey, b64decode(blob.encryptedPrivateKey));
        } catch (err) {
            throw new Error('Wrong password for this secure key.');
        }
        sessionStorage.setItem(sessionKey(email), b64encode(privateKeyRaw));
        return true;
    }

    function isUnlocked(email) {
        return sessionStorage.getItem(sessionKey(email)) !== null;
    }

    function lock(email) {
        sessionStorage.removeItem(sessionKey(email));
    }

    async function getPrivateKey(email) {
        const b64 = sessionStorage.getItem(sessionKey(email));
        if (!b64) throw new Error('Secure key is locked. Please sign in again.');
        return crypto.subtle.importKey('pkcs8', b64decode(b64), RSA_PARAMS, false, ['decrypt']);
    }

    async function importPublicKey(publicKeyB64) {
        return crypto.subtle.importKey('spki', b64decode(publicKeyB64), RSA_PARAMS, false, ['encrypt']);
    }

    // Encrypts plaintext with a fresh AES-256-GCM key, then wraps that key with the
    // recipient's RSA-OAEP public key. Returns everything the server needs to store.
    async function encryptForRecipient(plaintext, recipientPublicKeyB64) {
        const recipientKey = await importPublicKey(recipientPublicKeyB64);
        const aesKeyRaw = randomBytes(32);
        const aesKey = await crypto.subtle.importKey('raw', aesKeyRaw, 'AES-GCM', true, ['encrypt']);
        const iv = randomBytes(12);
        const ciphertext = await crypto.subtle.encrypt({ name: 'AES-GCM', iv }, aesKey, new TextEncoder().encode(plaintext));
        const wrappedKey = await crypto.subtle.encrypt({ name: 'RSA-OAEP' }, recipientKey, aesKeyRaw);
        return {
            ciphertext: b64encode(ciphertext),
            iv: b64encode(iv),
            wrappedKey: b64encode(wrappedKey),
            aesKeyRaw, // kept only for double-wrapping (chat); never sent anywhere raw
        };
    }

    async function wrapKeyFor(aesKeyRaw, publicKeyB64) {
        const key = await importPublicKey(publicKeyB64);
        const wrapped = await crypto.subtle.encrypt({ name: 'RSA-OAEP' }, key, aesKeyRaw);
        return b64encode(wrapped);
    }

    async function decryptMessage(ciphertextB64, ivB64, wrappedKeyB64, privateKey) {
        const aesKeyRaw = await crypto.subtle.decrypt({ name: 'RSA-OAEP' }, privateKey, b64decode(wrappedKeyB64));
        const aesKey = await crypto.subtle.importKey('raw', aesKeyRaw, 'AES-GCM', false, ['decrypt']);
        const iv = new Uint8Array(b64decode(ivB64));
        const plaintextBuf = await crypto.subtle.decrypt({ name: 'AES-GCM', iv }, aesKey, b64decode(ciphertextB64));
        return new TextDecoder().decode(plaintextBuf);
    }

    // Same scheme as encryptForRecipient/decryptMessage, for a file's raw
    // bytes instead of a text string -- skips the TextEncoder/TextDecoder
    // step so a binary file never gets needlessly re-encoded as text first.
    // Used for chat attachments.
    async function encryptBufferForRecipient(buffer, recipientPublicKeyB64) {
        const recipientKey = await importPublicKey(recipientPublicKeyB64);
        const aesKeyRaw = randomBytes(32);
        const aesKey = await crypto.subtle.importKey('raw', aesKeyRaw, 'AES-GCM', true, ['encrypt']);
        const iv = randomBytes(12);
        const ciphertext = await crypto.subtle.encrypt({ name: 'AES-GCM', iv }, aesKey, buffer);
        const wrappedKey = await crypto.subtle.encrypt({ name: 'RSA-OAEP' }, recipientKey, aesKeyRaw);
        return {
            ciphertext: b64encode(ciphertext),
            iv: b64encode(iv),
            wrappedKey: b64encode(wrappedKey),
            aesKeyRaw,
        };
    }

    async function decryptBuffer(ciphertextB64, ivB64, wrappedKeyB64, privateKey) {
        const aesKeyRaw = await crypto.subtle.decrypt({ name: 'RSA-OAEP' }, privateKey, b64decode(wrappedKeyB64));
        const aesKey = await crypto.subtle.importKey('raw', aesKeyRaw, 'AES-GCM', false, ['decrypt']);
        const iv = new Uint8Array(b64decode(ivB64));
        return crypto.subtle.decrypt({ name: 'AES-GCM', iv }, aesKey, b64decode(ciphertextB64)); // returns an ArrayBuffer
    }

    return {
        setupIdentity, hasLocalIdentity, unlockIdentity, isUnlocked, lock,
        getPrivateKey, importPublicKey, encryptForRecipient, wrapKeyFor, decryptMessage,
        encryptBufferForRecipient, decryptBuffer,
    };
})();
