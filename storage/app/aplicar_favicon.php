<?php

/**
 * Aplica favicon do Cardioprenatal (aba do navegador).
 * Execute como usuário com permissão de escrita (ex.: Carol):
 *   php storage/app/aplicar_favicon.php
 */

$root = dirname(__DIR__, 2);
$patchRoot = __DIR__.'/patches';

$oldFaviconTag = '<link rel="icon" href="{{ asset(\'favicon.svg\') }}" type="image/svg+xml" sizes="any">';
$newFaviconTag = "@include('partials.favicon')";

$optionalPublicFiles = [
    'public/favicon.svg',
    'public/favicon-32.png',
    'public/apple-touch-icon.png',
    'public/favicon.ico',
];

$requiredFiles = [
    'resources/views/partials/favicon.blade.php',
];

echo "=== Favicon Cardioprenatal ===\n";

passthru(PHP_BINARY.' '.__DIR__.'/generate_favicons.php', $code);
if ($code !== 0) {
    exit($code);
}

foreach ($requiredFiles as $rel) {
    $from = $patchRoot.'/'.str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $to = $root.'/'.str_replace('/', DIRECTORY_SEPARATOR, $rel);

    if (! is_readable($from)) {
        fwrite(STDERR, "Arquivo ausente: {$from}\n");
        exit(1);
    }

    $dir = dirname($to);
    if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
        fwrite(STDERR, "Não foi possível criar diretório: {$dir}\n");
        exit(1);
    }

    if (! @copy($from, $to)) {
        fwrite(STDERR, "Falha ao copiar para {$to}. Verifique permissões.\n");
        exit(1);
    }

    echo "OK {$rel}\n";
}

foreach ($optionalPublicFiles as $rel) {
    $from = $patchRoot.'/'.str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $to = $root.'/'.str_replace('/', DIRECTORY_SEPARATOR, $rel);

    if (! is_readable($from)) {
        continue;
    }

    if (@copy($from, $to)) {
        echo "OK {$rel}\n";
    } else {
        echo "AVISO: não foi possível copiar {$rel} (o ícone inline SVG ainda funciona)\n";
    }
}

foreach (['resources/views/layouts/app.blade.php', 'resources/views/layouts/auth.blade.php'] as $rel) {
    $path = $root.'/'.str_replace('/', DIRECTORY_SEPARATOR, $rel);

    if (! is_readable($path)) {
        fwrite(STDERR, "Layout ausente: {$path}\n");
        exit(1);
    }

    $content = file_get_contents($path);
    if (str_contains($content, $newFaviconTag)) {
        echo "Já aplicado em {$rel}\n";
        continue;
    }

    if (! str_contains($content, $oldFaviconTag)) {
        fwrite(STDERR, "Tag de favicon não encontrada em {$rel}\n");
        exit(1);
    }

    $updated = str_replace($oldFaviconTag, $newFaviconTag, $content);
    if (! @file_put_contents($path, $updated)) {
        fwrite(STDERR, "Falha ao atualizar {$path}. Verifique permissões.\n");
        exit(1);
    }

    echo "OK {$rel}\n";
}

echo "\nFavicon aplicado. Atualize a página com Ctrl+F5 se o ícone antigo ainda aparecer.\n";
