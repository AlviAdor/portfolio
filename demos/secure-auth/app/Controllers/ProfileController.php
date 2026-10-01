<?php
declare(strict_types=1);

class ProfileController
{
    public function show(): void
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }

        $myId = (int)$_SESSION['user_id'];
        $targetId = (int)($_GET['user'] ?? 0);

        if ($targetId !== $myId && !ChatThread::sharedThreadExists($myId, $targetId)) {
            header('Location: threads.php');
            exit;
        }

        $profile = User::profileFields($targetId);
        if (!$profile) {
            header('Location: threads.php');
            exit;
        }

        $isMe = $targetId === $myId;
        $genderLabel = ['male' => 'Male', 'female' => 'Female', 'unspecified' => 'Prefer not to say'][$profile['gender']] ?? 'Prefer not to say';
        $initials = mb_strtoupper(mb_substr($profile['name'], 0, 1) . mb_substr($profile['username'], 0, 1));

        view('profile', ['profile' => $profile, 'isMe' => $isMe, 'genderLabel' => $genderLabel, 'initials' => $initials]);
    }
}
