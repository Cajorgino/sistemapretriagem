<?php

/**
 * Corrige acentuacao nos arquivos do projeto (copia de storage/app/patches).
 * Execute como usuario com permissao de escrita na pasta do projeto.
 *
 * Uso: php storage/app/aplicar_correcao_acentuacao.php
 */

$root = dirname(__DIR__, 2);
$patchRoot = __DIR__.'/patches';

$files = [
    'app/Http/Controllers/AuthController.php',
    'app/Http/Controllers/UserController.php',
    'routes/web.php',
    'resources/views/auth/login.blade.php',
    'resources/views/auth/register.blade.php',
];

foreach ($files as $rel) {
    $from = $patchRoot.'/'.str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $to = $root.'/'.str_replace('/', DIRECTORY_SEPARATOR, $rel);

    if (! is_readable($from)) {
        fwrite(STDERR, "Patch ausente: {$from}\n");
        exit(1);
    }

    if (! @copy($from, $to)) {
        fwrite(STDERR, "Falha ao copiar para {$to}. Verifique permissoes.\n");
        exit(1);
    }

    echo "OK {$rel}\n";
}

chdir($root);
passthru(PHP_BINARY.' artisan view:clear', $code);
if ($code !== 0) {
    exit($code);
}

passthru(PHP_BINARY.' storage/app/fix_acentuacao_db.php', $code);
exit($code);
