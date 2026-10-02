<?php
declare(strict_types=1);

class ChatController
{
    public function list(): void
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }
        $isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';

        view('chat/list', ['isAdmin' => $isAdmin]);
    }

    public function room(): void
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }

        $threadId = (int)($_GET['thread'] ?? 0);
        $thread = ChatThread::find($threadId);
        if (!$thread || !ChatThread::isParticipant($thread, (int)$_SESSION['user_id'])) {
            header('Location: threads.php');
            exit;
        }

        $user = User::findById((int)$_SESSION['user_id']);
        $myEmail = (string)($user['email'] ?? '');

        view('chat/room', ['threadId' => $threadId, 'myEmail' => $myEmail, 'iceServers' => ice_servers()]);
    }
}
