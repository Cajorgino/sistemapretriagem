<?php

/**
 * Carrega classes da Fase 1/2 antes do autoload de app/ (substitui arquivos em app/ quando bloqueados).
 */

$patchRoot = __DIR__.'/patches/app';

$files = [
    'Models/AnaliseHistorico.php',
    'Models/Gestante.php',
    'Models/Consulta.php',
    'Services/CcfApiClient.php',
    'Services/CcfPayloadMapper.php',
    'Services/DashboardAnaliseService.php',
    'Jobs/ExecutarAnaliseGestante.php',
    'Jobs/AnalisarDadosIA.php',
    'Observers/ConsultaObserver.php',
    'Http/Controllers/DashboardController.php',
    'Http/Controllers/GestanteController.php',
    'Http/Controllers/ConsultaController.php',
    'Http/Controllers/GraficosController.php',
];

foreach ($files as $rel) {
    $path = $patchRoot.'/'.str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (! is_readable($path)) {
        throw new RuntimeException("Patch GestRisk ausente: {$path}");
    }
    require_once $path;
}
