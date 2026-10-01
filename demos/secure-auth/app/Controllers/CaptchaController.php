<?php
declare(strict_types=1);

class CaptchaController
{
    public function image(): void
    {
        render_captcha_image();
    }
}
