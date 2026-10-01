<?php
declare(strict_types=1);

// Always consumes the pending CAPTCHA (single-use), independent of the result,
// so a given image/session state can never be replayed. Returns true only if
// a CAPTCHA was actually issued this session AND the submitted answer matches
// AND it hasn't expired -- there is no code path that treats "missing" as
// "passed," which is what makes this impossible to bypass client-side.
function verify_captcha(string $submitted): bool
{
    $expected = $_SESSION['captcha_answer'] ?? null;
    $expires = $_SESSION['captcha_expires'] ?? 0;
    unset($_SESSION['captcha_answer'], $_SESSION['captcha_expires']);

    if ($expected === null || time() > $expires) {
        return false;
    }
    return hash_equals($expected, trim($submitted));
}

// Self-hosted image CAPTCHA (no third-party keys/service, nothing to
// configure). The answer lives only in the server-side session -- never in
// the HTML, never in a client-readable cookie -- so there is no value for a
// script to read or forge. Writes a PNG directly to the response.
function render_captcha_image(): void
{
    $a = random_int(2, 9);
    $b = random_int(2, 9);
    $op = random_int(0, 1) === 0 ? '+' : '-';
    $answer = $op === '+' ? $a + $b : $a - $b;
    // Keep subtraction non-negative so the answer is always a simple one/two-digit number.
    if ($op === '-' && $b > $a) { [$a, $b] = [$b, $a]; $answer = $a - $b; }

    $_SESSION['captcha_answer'] = (string)$answer;
    $_SESSION['captcha_expires'] = time() + 300;

    $text = "{$a} {$op} {$b} =";

    $width = 160;
    $height = 56;
    $im = imagecreatetruecolor($width, $height);

    $bg = imagecolorallocate($im, 16, 16, 16);
    imagefilledrectangle($im, 0, 0, $width, $height, $bg);

    // Noise lines behind the text to resist trivial template matching.
    for ($i = 0; $i < 6; $i++) {
        $lineColor = imagecolorallocate($im, random_int(40, 70), random_int(40, 70), random_int(40, 70));
        imageline($im, random_int(0, $width), random_int(0, $height), random_int(0, $width), random_int(0, $height), $lineColor);
    }

    $fg = imagecolorallocate($im, 245, 247, 243);
    $font = 5; // built-in GD font, no external .ttf dependency needed
    $x = 14;
    foreach (str_split($text) as $ch) {
        $y = 16 + random_int(-4, 4);
        imagestring($im, $font, $x, $y, $ch, $fg);
        $x += imagefontwidth($font) + random_int(2, 6);
    }

    // Scatter dots for extra noise.
    for ($i = 0; $i < 60; $i++) {
        $dotColor = imagecolorallocate($im, random_int(50, 90), random_int(50, 90), random_int(50, 90));
        imagesetpixel($im, random_int(0, $width - 1), random_int(0, $height - 1), $dotColor);
    }

    header('Content-Type: image/png');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    imagepng($im);
    imagedestroy($im);
}
