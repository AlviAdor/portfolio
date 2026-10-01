<?php
declare(strict_types=1);

// A small, dependency-free SMTP client -- no Composer, no PHPMailer, nothing to
// install. Deliberately kept to one file so it's trivial to carry into a
// separate repo. Supports STARTTLS (587) and implicit TLS (465) with AUTH LOGIN,
// which covers Gmail, Outlook, SendGrid, Mailgun, Resend's SMTP endpoints, etc.
//
// Configure via environment variables (preferred where supported) or
// config/config.local.php (required on hosts with no env var support, e.g.
// most free shared hosting):
//   SAD_SMTP_HOST, SAD_SMTP_PORT, SAD_SMTP_USER, SAD_SMTP_PASS,
//   SAD_SMTP_SECURE ("tls" for STARTTLS on 587, "ssl" for implicit TLS on 465),
//   SAD_SMTP_FROM, SAD_SMTP_FROM_NAME
//
// If SAD_SMTP_HOST is not set, smtp_send() returns false and callers should
// fall back to PHP's mail() -- see Mailer.php.

function smtp_config(): ?array
{
    $host = cfg('SAD_SMTP_HOST') ?: '';
    if ($host === '') return null;

    return [
        'host' => $host,
        'port' => (int)(cfg('SAD_SMTP_PORT') ?: 587),
        'user' => cfg('SAD_SMTP_USER') ?: '',
        'pass' => cfg('SAD_SMTP_PASS') ?: '',
        'secure' => cfg('SAD_SMTP_SECURE') ?: 'tls', // 'tls' (STARTTLS) or 'ssl' (implicit)
        'from' => cfg('SAD_SMTP_FROM') ?: 'no-reply@secure-auth-demo.local',
        'fromName' => cfg('SAD_SMTP_FROM_NAME') ?: 'Secure Auth Demo',
    ];
}

class SmtpException extends RuntimeException {}

function smtp_send(string $toEmail, string $toName, string $subject, string $body): bool
{
    $cfg = smtp_config();
    if ($cfg === null) return false;

    try {
        smtp_send_via($cfg, $toEmail, $toName, $subject, $body);
        return true;
    } catch (SmtpException $e) {
        error_log('[secure-auth-demo] SMTP send failed: ' . $e->getMessage());
        return false;
    }
}

function smtp_send_via(array $cfg, string $toEmail, string $toName, string $subject, string $body): void
{
    $transport = $cfg['secure'] === 'ssl' ? 'ssl://' . $cfg['host'] : $cfg['host'];
    $stream = @stream_socket_client("{$transport}:{$cfg['port']}", $errno, $errstr, 10);
    if (!$stream) {
        throw new SmtpException("Could not connect to {$cfg['host']}:{$cfg['port']} ({$errstr})");
    }
    stream_set_timeout($stream, 10);

    $expect = function (string $context, array $okCodes) use ($stream) {
        $line = '';
        do {
            $line = fgets($stream, 515);
            if ($line === false) throw new SmtpException("No response during {$context}");
        } while (isset($line[3]) && $line[3] === '-'); // multi-line response, keep reading
        $code = (int)substr($line, 0, 3);
        if (!in_array($code, $okCodes, true)) {
            throw new SmtpException("Unexpected response during {$context}: " . trim($line));
        }
        return $line;
    };
    $send = function (string $line) use ($stream) {
        fwrite($stream, $line . "\r\n");
    };

    $expect('connect', [220]);

    $localHost = gethostname() ?: 'localhost';
    $send("EHLO {$localHost}");
    $expect('EHLO', [250]);

    if ($cfg['secure'] === 'tls') {
        $send('STARTTLS');
        $expect('STARTTLS', [220]);
        if (!stream_socket_enable_crypto($stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new SmtpException('TLS negotiation failed');
        }
        $send("EHLO {$localHost}");
        $expect('EHLO after STARTTLS', [250]);
    }

    if ($cfg['user'] !== '') {
        $send('AUTH LOGIN');
        $expect('AUTH LOGIN', [334]);
        $send(base64_encode($cfg['user']));
        $expect('AUTH username', [334]);
        $send(base64_encode($cfg['pass']));
        $expect('AUTH password', [235]);
    }

    $send("MAIL FROM:<{$cfg['from']}>");
    $expect('MAIL FROM', [250]);
    $send("RCPT TO:<{$toEmail}>");
    $expect('RCPT TO', [250, 251]);

    $send('DATA');
    $expect('DATA', [354]);

    $headers = [
        'From: ' . encode_header($cfg['fromName']) . " <{$cfg['from']}>",
        'To: ' . encode_header($toName) . " <{$toEmail}>",
        'Subject: ' . encode_header($subject),
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'Date: ' . date('r'),
    ];
    // Dot-stuff lines that start with '.' per RFC 5321, and end with the lone-dot terminator.
    $escapedBody = preg_replace('/^\./m', '..', $body);
    $send(implode("\r\n", $headers) . "\r\n\r\n" . $escapedBody . "\r\n.");
    $expect('message body', [250]);

    $send('QUIT');
    fclose($stream);
}

function encode_header(string $value): string
{
    if (preg_match('/^[\x20-\x7E]*$/', $value)) return $value;
    return '=?UTF-8?B?' . base64_encode($value) . '?=';
}
