<?php

/**
 * Gera layouts em storage/app/patches a partir dos arquivos do projeto,
 * incluindo o favicon Cardioprenatal no <head>.
 */

$root = dirname(__DIR__, 2);
$patchRoot = __DIR__.'/patches/resources/views/layouts';
$oldTag = '<link rel="icon" href="{{ asset(\'favicon.svg\') }}" type="image/svg+xml" sizes="any">';
$newTag = "@include('partials.favicon')";

if (! is_dir($patchRoot) && ! mkdir($patchRoot, 0755, true) && ! is_dir($patchRoot)) {
    fwrite(STDERR, "Não foi possível criar {$patchRoot}\n");
    exit(1);
}

foreach (['app.blade.php', 'auth.blade.php'] as $file) {
    $source = $root.'/resources/views/layouts/'.$file;
    $target = $patchRoot.'/'.$file;

    if (! is_readable($source)) {
        fwrite(STDERR, "Layout ausente: {$source}\n");
        exit(1);
    }

    $content = file_get_contents($source);
    if (! str_contains($content, $newTag)) {
        if (! str_contains($content, $oldTag)) {
            fwrite(STDERR, "Tag de favicon não encontrada em {$file}\n");
            exit(1);
        }

        $content = str_replace($oldTag, $newTag, $content);
    }

    file_put_contents($target, $content);
    echo "OK layouts/{$file}\n";
}
