<?php
declare(strict_types=1);

// Some free PHP hosts ship a minimal build with the mbstring extension
// disabled. Without this, any mb_*() call is a fatal "undefined function"
// error -- not a PDOException, so it looks nothing like a database problem,
// which makes it a confusing one to diagnose from a live site alone. These
// fallbacks are UTF-8 aware (via PCRE's /u modifier, which doesn't depend on
// mbstring) for the two functions this app actually needs; only
// mb_strtoupper falls back to ASCII-only casing, which is fine for this
// app's one use (avatar initials).
if (!function_exists('mb_strlen')) {
    function mb_strlen(string $string, string $encoding = 'UTF-8'): int
    {
        preg_match_all('/./us', $string, $matches);
        return count($matches[0]);
    }
}

if (!function_exists('mb_substr')) {
    function mb_substr(string $string, int $start, ?int $length = null, string $encoding = 'UTF-8'): string
    {
        preg_match_all('/./us', $string, $matches);
        $chars = $matches[0];
        return implode('', $length === null ? array_slice($chars, $start) : array_slice($chars, $start, $length));
    }
}

if (!function_exists('mb_strtoupper')) {
    function mb_strtoupper(string $string, string $encoding = 'UTF-8'): string
    {
        return strtoupper($string);
    }
}
