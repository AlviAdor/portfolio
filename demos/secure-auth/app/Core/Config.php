<?php
declare(strict_types=1);

// Reads config two ways: real environment variables (SetEnv, Docker, Render,
// etc.) when they're available, and a plain gitignored PHP file otherwise --
// most free PHP hosts (InfinityFree included) don't let you set environment
// variables at all, so this is the difference between the app actually being
// configurable there or not. Env vars always win when both are present.
function cfg(string $key, ?string $default = null): ?string
{
    static $local = null;
    if ($local === null) {
        $path = __DIR__ . '/../../config/config.local.php';
        $local = is_file($path) ? (require $path) : [];
    }

    $fromEnv = getenv($key);
    if ($fromEnv !== false && $fromEnv !== '') {
        return $fromEnv;
    }
    return $local[$key] ?? $default;
}
