<?php

/**
 * Copia PNGs de analise_descritiva/figuras para storage/app/gestrisk/figuras/
 * (acessível pelo PHP do servidor web sem depender de permissões externas).
 *
 * Uso: php storage/app/sync_figuras_descritivas.php
 */

$dest = __DIR__.'/gestrisk/figuras';
$origens = [
    dirname(__DIR__, 3).'/AnalisePython-main/analise_descritiva/figuras',
    'C:/Users/carol/AnalisePython-main/analise_descritiva/figuras',
];

$origem = null;
foreach ($origens as $candidato) {
    if (is_dir($candidato)) {
        $origem = $candidato;
        break;
    }
}

if ($origem === null) {
    fwrite(STDERR, "Nenhuma pasta figuras encontrada. Gere antes: py gerar_analise_descritiva.py\n");
    exit(1);
}

if (! is_dir($dest) && ! mkdir($dest, 0755, true) && ! is_dir($dest)) {
    fwrite(STDERR, "Não foi possível criar {$dest}\n");
    exit(1);
}

$pngs = glob($origem.'/*.png') ?: [];
if ($pngs === []) {
    fwrite(STDERR, "Nenhum PNG em {$origem}. Execute: py gerar_analise_descritiva.py\n");
    exit(1);
}

$copiados = 0;
foreach ($pngs as $png) {
    $nome = basename($png);
    if (copy($png, $dest.'/'.$nome)) {
        $copiados++;
    }
}

echo "OK: {$copiados} gráfico(s) copiados de {$origem} para {$dest}\n";
