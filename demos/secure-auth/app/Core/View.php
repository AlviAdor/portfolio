<?php
declare(strict_types=1);

// Renders app/Views/{$name}.php with $data extracted into local variables --
// the only templating this app needs. Kept as a plain include rather than a
// template engine so there's nothing extra to learn reading these views: it's
// just PHP and HTML, same as every page was before the MVC split.
function view(string $name, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require __DIR__ . '/../Views/' . $name . '.php';
}
