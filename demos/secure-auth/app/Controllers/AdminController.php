<?php
declare(strict_types=1);

class AdminController
{
    public function inbox(): void
    {
        if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
            header('Location: ../login.php');
            exit;
        }

        $user = User::findById((int)$_SESSION['user_id']);
        $adminEmail = (string)($user['email'] ?? '');

        view('admin/inbox', ['adminEmail' => $adminEmail]);
    }
}
