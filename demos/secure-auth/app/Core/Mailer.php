<?php
declare(strict_types=1);
require __DIR__ . '/SmtpMailer.php';

// Tries real SMTP first (see SmtpMailer.php -- set SAD_SMTP_HOST etc. to
// enable it). Falls back to PHP's mail(), which works out of the box on this
// sandbox/macOS (local delivery) but is frequently disabled or spam-filtered
// on real hosting -- see README.md "Setting up real email delivery."
function send_app_email(string $toEmail, string $toName, string $subject, string $body): bool
{
    if (smtp_send($toEmail, $toName, $subject, $body)) {
        return true;
    }
    $cfg = smtp_config();
    $fromAddr = $cfg['from'] ?? 'no-reply@secure-auth-demo.local';
    $headers = "From: {$fromAddr}\r\nContent-Type: text/plain; charset=UTF-8";
    return @mail($toEmail, $subject, $body, $headers);
}

function send_invite_email(string $toEmail, string $toName, string $loginUrl, string $tempPassword): bool
{
    $subject = 'You have been invited to a secure chat';
    $body = "Hi {$toName},\n\n"
        . "You've been invited to a private, end-to-end encrypted chat.\n\n"
        . "Sign in here: {$loginUrl}\n"
        . "Temporary password: {$tempPassword}\n\n"
        . "You'll be asked to set your own password on first sign-in. This temporary\n"
        . "password is single-use and expires once you set a new one.\n";
    return send_app_email($toEmail, $toName, $subject, $body);
}

// Lets the admin know a new encrypted message arrived, without ever touching
// its contents -- the server can't decrypt it, so the notification can only
// say who it's from, never what it says.
function send_new_message_notification(string $adminEmail, string $adminName, string $senderName, string $senderEmail, string $inboxUrl): bool
{
    $subject = "New secure message from {$senderName}";
    $body = "Hi {$adminName},\n\n"
        . "You've received a new end-to-end encrypted message from:\n"
        . "  {$senderName} <{$senderEmail}>\n\n"
        . "This email can't show you what it says -- your server never has the\n"
        . "ability to decrypt it. Sign in to read it:\n"
        . "{$inboxUrl}\n";
    return send_app_email($adminEmail, $adminName, $subject, $body);
}
