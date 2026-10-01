<?php
declare(strict_types=1);

class DashboardController
{
    public function index(): void
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }

        $user = User::findById((int)$_SESSION['user_id']);
        if (!$user) {
            session_destroy();
            header('Location: login.php');
            exit;
        }

        $isAdmin = $user['role'] === 'admin';

        // Role-based access: only admins can see the cross-account audit trail.
        $recentLogins = $isAdmin
            ? LoginAudit::recentForAll(10)
            : LoginAudit::recentForUser((int)$user['id'], 10);

        view('dashboard', ['user' => $user, 'isAdmin' => $isAdmin, 'recentLogins' => $recentLogins]);
    }
}
