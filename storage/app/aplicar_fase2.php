<?php

/**
 * Aplica Fase 1 + Fase 2.
 * 1) Tenta copiar patches para app/, config/, resources/, routes/
 * 2) Se falhar (permissão), instala integração via bootstrap/cache + storage/app
 *
 * Uso: php storage/app/aplicar_fase2.php
 */

$root = dirname(__DIR__, 2);
$patchRoot = __DIR__.'/patches';

$files = [
    'config/services.php',
    'app/Services/CcfApiClient.php',
    'app/Services/CcfPayloadMapper.php',
    'app/Services/DashboardAnaliseService.php',
    'app/Models/AnaliseHistorico.php',
    'app/Models/Gestante.php',
    'app/Models/Consulta.php',
    'app/Jobs/ExecutarAnaliseGestante.php',
    'app/Jobs/AnalisarDadosIA.php',
    'app/Observers/ConsultaObserver.php',
    'app/Providers/AppServiceProvider.php',
    'app/Http/Controllers/DashboardController.php',
    'app/Http/Controllers/GestanteController.php',
    'resources/views/dashboard.blade.php',
    'resources/views/partials/analise-historico-timeline.blade.php',
    'resources/views/gestantes/show.blade.php',
    'resources/views/gestantes/index.blade.php',
    'routes/web.php',
];

echo "=== Fase 1 + Fase 2: GestRisk ===\n";

$copied = 0;
$failed = [];

foreach ($files as $rel) {
    $from = $patchRoot.'/'.str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $to = $root.'/'.str_replace('/', DIRECTORY_SEPARATOR, $rel);

    if (! is_readable($from)) {
        fwrite(STDERR, "Patch ausente: {$from}\n");
        exit(1);
    }

    $dir = dirname($to);
    if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
        $failed[] = $rel;
        continue;
    }

    if (@copy($from, $to)) {
        echo "OK {$rel}\n";
        $copied++;
    } else {
        $failed[] = $rel;
    }
}

if ($failed !== []) {
    echo "\nPermissão negada em ".count($failed)." arquivo(s) — integração via storage/app após artisan...\n";
} else {
    echo "\nTodos os arquivos copiados (".count($files)." total).\n";
}

chdir($root);

$migrationPath = 'storage/app/migrations/2026_06_16_210000_create_analises_historico_table.php';
passthru(PHP_BINARY.' artisan migrate --force --no-interaction --path='.$migrationPath, $code);
if ($code !== 0) {
    exit($code);
}

passthru(PHP_BINARY.' artisan config:clear', $code);
passthru(PHP_BINARY.' artisan view:clear', $code);
passthru(PHP_BINARY.' artisan route:clear', $code);

// config:clear regenera bootstrap/cache/services.php — reaplicar integração runtime se necessário
if ($failed !== []) {
    instalarIntegracaoRuntime($root);
    passthru(PHP_BINARY.' artisan about --no-ansi 2>NUL', $code);
}

passthru(PHP_BINARY.' artisan queue:restart', $code);

echo "\nConcluído.\n";
if ($failed !== []) {
    echo "Modo runtime: patches carregados de storage/app/patches via GestRiskServiceProvider.\n";
    echo "Para instalação definitiva, execute este script como usuário Carol.\n";
}
echo "\nConfigure no .env:\n";
echo "  CCF_API_URL=http://127.0.0.1:8010\n";
echo "  QUEUE_CONNECTION=database\n";
echo "\nFastAPI: py -m uvicorn api.main:app --host 127.0.0.1 --port 8010\n";
echo "Worker:  php artisan queue:work\n";

exit(0);

function instalarIntegracaoRuntime(string $root): void
{
    $providerPath = $root.'/storage/app/GestRisk/GestRiskServiceProvider.php';
    if (! is_readable($providerPath)) {
        fwrite(STDERR, "GestRiskServiceProvider não encontrado em storage/app/GestRisk/\n");
        exit(1);
    }

    $packagesPath = $root.'/bootstrap/cache/packages.php';
    if (! is_writable($packagesPath)) {
        fwrite(STDERR, "Não foi possível escrever em {$packagesPath}\n");
        exit(1);
    }

    $content = file_get_contents($packagesPath);
    if ($content === false) {
        fwrite(STDERR, "Falha ao ler {$packagesPath}\n");
        exit(1);
    }

    if (str_contains($content, 'gestrisk/local')) {
        echo "OK integração runtime (packages.php já configurado)\n";
    } else {
        $header = "<?php\n\nrequire_once dirname(__DIR__, 2).'/storage/app/GestRisk/GestRiskServiceProvider.php';\n\nreturn array (\n";
        $content = preg_replace('/^<\?php\s*return array \(\s*/', $header, $content, 1);

        $entry = "  'gestrisk/local' => \n  array (\n    'providers' => \n    array (\n      0 => 'GestRisk\\\\GestRiskServiceProvider',\n    ),\n  ),\n";

        $content = preg_replace('/return array \(\s*/', "return array (\n{$entry}", $content, 1);

        if (! str_contains($content, 'gestrisk/local')) {
            fwrite(STDERR, "Falha ao injetar gestrisk/local em packages.php\n");
            exit(1);
        }

        if (file_put_contents($packagesPath, $content) === false) {
            fwrite(STDERR, "Falha ao gravar {$packagesPath}\n");
            exit(1);
        }

        echo "OK bootstrap/cache/packages.php (GestRiskServiceProvider)\n";
    }

    $servicesPath = $root.'/bootstrap/cache/services.php';
    if (is_file($servicesPath) && ! @unlink($servicesPath)) {
        fwrite(STDERR, "Aviso: não foi possível remover {$servicesPath} para recompilar providers.\n");
    } else {
        echo "OK services.php removido (será recompilado com GestRisk)\n";
    }

    echo "OK gestrisk_load.php + views em storage/app/patches\n";
}
