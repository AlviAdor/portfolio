<?php
declare(strict_types=1);

class ApiController
{
    // Only these two actions are ever called by a page that might live on a
    // different origin than this API (e.g. the portfolio, hosted separately
    // on GitHub Pages). Both are unauthenticated and cookie-free by design, so
    // allowing a configured cross-origin caller here can't leak a session.
    private const PUBLIC_CORS_ACTIONS = ['admin_public_key', 'contact_submit'];

    public function dispatch(): void
    {
        $action = $_GET['action'] ?? $_POST['action'] ?? '';

        if (in_array($action, self::PUBLIC_CORS_ACTIONS, true)) {
            $this->applyCors();
        }

        switch ($action) {
            case 'admin_public_key': $this->adminPublicKey(); break;
            case 'contact_submit': $this->contactSubmit(); break;
            case 'save_public_key': $this->savePublicKey(); break;
            case 'inbox_list': $this->inboxList(); break;
            case 'mark_read': $this->markRead(); break;
            case 'invite': $this->invite(); break;
            case 'peer_key': $this->peerKey(); break;
            case 'chat_fetch': $this->chatFetch(); break;
            case 'chat_send': $this->chatSend(); break;
            case 'profile': $this->profile(); break;
            case 'my_threads': $this->myThreads(); break;
            default: json_fail('Unknown action.', 404);
        }
    }

    // ---- Standalone endpoint: me-public-key.php uses this directly ----
    public function myPublicKey(): void
    {
        $user = require_login();
        json_ok(['publicKey' => $user['public_key']]);
    }

    private function applyCors(): void
    {
        $allowedOrigin = cfg('SAD_ALLOWED_ORIGIN') ?: '';
        $requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($allowedOrigin !== '' && hash_equals($allowedOrigin, $requestOrigin)) {
            header("Access-Control-Allow-Origin: {$allowedOrigin}");
            header('Vary: Origin');
            header('Access-Control-Allow-Methods: GET, POST');
            header('Access-Control-Allow-Headers: Content-Type');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    // ---- Public: fetch the admin's public key so a visitor's browser can encrypt ----
    private function adminPublicKey(): void
    {
        $row = User::firstAdminWithKey();
        if (!$row) json_fail('No admin key has been set up yet.', 503);
        json_ok(['publicKey' => $row['public_key']]);
    }

    // ---- Public: submit an already-encrypted contact message ----
    private function contactSubmit(): void
    {
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $ciphertext = (string)($_POST['ciphertext'] ?? '');
        $iv = (string)($_POST['iv'] ?? '');
        $wrappedKey = (string)($_POST['wrappedKey'] ?? '');

        if ($name === '' || mb_strlen($name) > 80) json_fail('Enter a valid name.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_fail('Enter a valid email.');
        if ($ciphertext === '' || $iv === '' || $wrappedKey === '' || strlen($ciphertext) > 200000) {
            json_fail('Message could not be encrypted. Please try again.');
        }

        ContactMessage::create($name, $email, $ciphertext, $iv, $wrappedKey);

        $admin = User::firstAdmin();
        if ($admin) {
            $inboxUrl = (!empty($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/admin/inbox.php';
            send_new_message_notification($admin['email'], $admin['name'], $name, $email, $inboxUrl);
        }
        json_ok();
    }

    // ---- Authenticated: store the public key generated for this account ----
    private function savePublicKey(): void
    {
        $user = require_login();
        if (!csrf_verify()) json_fail('Session expired, please reload.', 403);
        $publicKey = (string)($_POST['publicKey'] ?? '');
        if ($publicKey === '' || strlen($publicKey) > 2000) json_fail('Invalid key.');
        User::savePublicKey((int)$user['id'], $publicKey);
        json_ok();
    }

    // ---- Admin: list contact messages (still encrypted -- decrypted client-side) ----
    private function inboxList(): void
    {
        require_admin();
        json_ok(['messages' => ContactMessage::allForInbox()]);
    }

    private function markRead(): void
    {
        require_admin();
        if (!csrf_verify()) json_fail('Session expired, please reload.', 403);
        $id = (int)($_POST['id'] ?? 0);
        ContactMessage::markRead($id);
        json_ok();
    }

    // ---- Admin: invite a message sender into a secure chat ----
    private function invite(): void
    {
        $admin = require_admin();
        if (!csrf_verify()) json_fail('Session expired, please reload.', 403);
        $messageId = (int)($_POST['id'] ?? 0);

        $message = ContactMessage::find($messageId);
        if (!$message) json_fail('Message not found.', 404);

        if (ChatThread::existsForMessage($messageId)) json_fail('This sender has already been invited.');

        $guest = User::findByEmail($message['sender_email']);
        $tempPassword = bin2hex(random_bytes(6));

        if ($guest) {
            $guestId = (int)$guest['id'];
            User::resetToTempPassword($guestId, hash_password($tempPassword));
        } else {
            $guestId = User::createInvitedGuest($message['sender_name'], $message['sender_email'], hash_password($tempPassword));
        }

        ChatThread::create($messageId, (int)$admin['id'], $guestId);
        ContactMessage::markInvited($messageId);

        $loginUrl = (!empty($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/login.php';
        $emailed = send_invite_email($message['sender_email'], $message['sender_name'], $loginUrl, $tempPassword);

        json_ok([
            'emailed' => $emailed,
            'loginUrl' => $loginUrl,
            'email' => $message['sender_email'],
            'tempPassword' => $tempPassword,
        ]);
    }

    // ---- Chat: who is the other participant, and what is their public key ----
    private function peerKey(): void
    {
        $user = require_login();
        $threadId = (int)($_GET['thread'] ?? 0);
        $thread = ChatThread::find($threadId);
        if (!$thread) json_fail('Chat not found.', 404);
        if (!ChatThread::isParticipant($thread, (int)$user['id'])) {
            json_fail('Not part of this chat.', 403);
        }
        $peerId = $user['id'] == $thread['admin_user_id'] ? $thread['guest_user_id'] : $thread['admin_user_id'];
        $peer = User::findById((int)$peerId);
        if (!$peer || !$peer['public_key']) json_fail('The other participant has not set up their secure key yet.', 409);
        json_ok([
            'peerId' => (int)$peer['id'],
            'peerName' => $peer['name'],
            'peerUsername' => $peer['username'],
            'peerGender' => $peer['gender'],
            'peerPublicKey' => $peer['public_key'],
            'myId' => (int)$user['id'],
        ]);
    }

    // ---- Chat: poll for new messages since a given id ----
    private function chatFetch(): void
    {
        $user = require_login();
        $threadId = (int)($_GET['thread'] ?? 0);
        $since = (int)($_GET['since'] ?? 0);

        $thread = ChatThread::find($threadId);
        if (!$thread || !ChatThread::isParticipant($thread, (int)$user['id'])) {
            json_fail('Not part of this chat.', 403);
        }

        json_ok(['messages' => ChatMessage::since($threadId, $since)]);
    }

    // ---- Chat: send a message (already encrypted twice: for self and for peer) ----
    private function chatSend(): void
    {
        $user = require_login();
        if (!csrf_verify()) json_fail('Session expired, please reload.', 403);
        $threadId = (int)($_POST['thread'] ?? 0);
        $ciphertext = (string)($_POST['ciphertext'] ?? '');
        $iv = (string)($_POST['iv'] ?? '');
        $wrappedSender = (string)($_POST['wrappedKeySender'] ?? '');
        $wrappedRecipient = (string)($_POST['wrappedKeyRecipient'] ?? '');

        if ($ciphertext === '' || $iv === '' || $wrappedSender === '' || $wrappedRecipient === '') {
            json_fail('Message could not be encrypted.');
        }
        if (strlen($ciphertext) > 50000) json_fail('Message too long.');

        $thread = ChatThread::find($threadId);
        if (!$thread || !ChatThread::isParticipant($thread, (int)$user['id'])) {
            json_fail('Not part of this chat.', 403);
        }

        $id = ChatMessage::create($threadId, (int)$user['id'], $ciphertext, $iv, $wrappedSender, $wrappedRecipient);
        json_ok(['id' => $id]);
    }

    // ---- Profile detail: only visible to someone who shares a chat thread with them ----
    private function profile(): void
    {
        $user = require_login();
        $targetId = (int)($_GET['user'] ?? 0);

        $allowed = $targetId === (int)$user['id'] || ChatThread::sharedThreadExists((int)$user['id'], $targetId);
        if (!$allowed) json_fail('Not found.', 404);

        $profile = User::profileFields($targetId);
        if (!$profile) json_fail('Not found.', 404);
        json_ok(['profile' => $profile]);
    }

    // ---- My active chat threads (for a simple inbox/launcher) ----
    private function myThreads(): void
    {
        $user = require_login();
        json_ok(['threads' => ChatThread::forUserWithPeer((int)$user['id'])]);
    }
}
