<?php

/**
 * Aplica a Fase 1 da integração Laravel ↔ GestRisk FastAPI.
 * Copia patches de storage/app/patches para o projeto e executa migrations.
 *
 * Execute como usuário com permissão de escrita (ex.: Carol):
 *   php storage/app/aplicar_fase1.php
 */

$root = dirname(__DIR__, 2);
$patchRoot = __DIR__.'/patches';

$files = [
    'config/services.php',
    'app/Services/CcfApiClient.php',
    'app/Services/CcfPayloadMapper.php',
    'app/Models/AnaliseHistorico.php',
    'app/Models/Gestante.php',
    'app/Models/Consulta.php',
    'app/Jobs/ExecutarAnaliseGestante.php',
    'app/Jobs/AnalisarDadosIA.php',
    'app/Observers/ConsultaObserver.php',
    'app/Providers/AppServiceProvider.php',
];

echo "=== Fase 1: Integração GestRisk ===\n";

foreach ($files as $rel) {
    $from = $patchRoot.'/'.str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $to = $root.'/'.str_replace('/', DIRECTORY_SEPARATOR, $rel);

    $dir = dirname($to);
    if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
        fwrite(STDERR, "Não foi possível criar diretório: {$dir}\n");
        exit(1);
    }

    if (! is_readable($from)) {
        fwrite(STDERR, "Patch ausente: {$from}\n");
        exit(1);
    }

    if (! @copy($from, $to)) {
        fwrite(STDERR, "Falha ao copiar para {$to}. Verifique permissões.\n");
        exit(1);
    }

    echo "OK {$rel}\n";
}

chdir($root);

$migrationPath = 'storage/app/migrations/2026_06_16_210000_create_analises_historico_table.php';
passthru(PHP_BINARY.' artisan migrate --force --no-interaction --path='.$migrationPath, $code);
if ($code !== 0) {
    exit($code);
}

passthru(PHP_BINARY.' artisan config:clear', $code);
if ($code !== 0) {
    exit($code);
}

passthru(PHP_BINARY.' artisan queue:restart', $code);

echo "\nFase 1 aplicada. Configure no .env:\n";
echo "  CCF_API_URL=http://127.0.0.1:8010\n";
echo "  QUEUE_CONNECTION=database\n";
echo "\nInicie a API: cd AnalisePython-main && uvicorn api.main:app --host 127.0.0.1 --port 8010\n";
echo "Inicie o worker: php artisan queue:work\n";

exit($code);
