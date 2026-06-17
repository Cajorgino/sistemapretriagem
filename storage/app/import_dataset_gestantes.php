<?php

/**
 * Importa gestantes a partir de dataset.csv (GestRisk CCF).
 *
 * Uso: php storage/app/import_dataset_gestantes.php
 *
 * O CSV é lido de storage/app/data/dataset.csv ou ../AnalisePython-main/data/dataset.csv.
 * Cada linha vira uma gestante. CPF (004…) e telefone (109…) são fictícios e únicos.
 */

use App\Models\Gestante;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../vendor/autoload.php';
$app = require_once __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

const CPF_PREFIX = '004';
const TELEFONE_PREFIX = '109';

$candidates = [
    storage_path('app/data/dataset.csv'),
    base_path('../AnalisePython-main/data/dataset.csv'),
    'C:/Users/carol/AnalisePython-main/data/dataset.csv',
];

$path = null;
foreach ($candidates as $candidate) {
    if (is_readable($candidate)) {
        $path = $candidate;
        break;
    }
}

if ($path === null) {
    fwrite(STDERR, "dataset.csv não encontrado.\n");
    exit(1);
}

$handle = fopen($path, 'r');
$header = fgetcsv($handle);
$rows = [];
while (($line = fgetcsv($handle)) !== false) {
    if (count($line) === count($header)) {
        $rows[] = array_combine($header, $line);
    }
}
fclose($handle);

$idades = [];
$etnias = [];
$chdPositivos = 0;
foreach ($rows as $row) {
    $idade = parseFloat($row['idade_materna'] ?? null);
    if ($idade !== null) {
        $idades[] = $idade;
    }
    $etnia = trim((string) ($row['etnia'] ?? ''));
    if ($etnia === '') {
        $etnia = 'Não informada';
    }
    $etnias[$etnia] = ($etnias[$etnia] ?? 0) + 1;
    if (parseBool($row['cardiopatia_congenita'] ?? null) === true) {
        $chdPositivos++;
    }
}

$total = count($rows);
sort($idades);
echo "Análise do dataset.csv\n";
echo "  Total de registros: {$total}\n";
echo '  Idade materna: min '.min($idades).' · média '.round(array_sum($idades) / count($idades), 1).' · max '.max($idades)."\n";
echo '  Cardiopatia congênita (positivos): '.$chdPositivos.' ('.round($chdPositivos / max($total, 1) * 100, 1)."%)\n";
arsort($etnias);
echo "  Distribuição por etnia:\n";
foreach (array_slice($etnias, 0, 6, true) as $etnia => $qtd) {
    echo "    - {$etnia}: {$qtd}\n";
}
echo "\n";

Gestante::query()->where('cpf', 'like', CPF_PREFIX.'%')->delete();

$imported = 0;
DB::transaction(function () use ($rows, &$imported) {
    foreach ($rows as $index => $row) {
        $numero = $index + 1;
        $idadeMaterna = parseFloat($row['idade_materna'] ?? null);
        $etnia = trim((string) ($row['etnia'] ?? ''));
        if ($etnia === '') {
            $etnia = 'Não informada';
        }

        Gestante::create([
            'nome' => sprintf('Gestante %04d (%s)', $numero, $etnia),
            'data_nascimento' => estimateBirthDate($idadeMaterna),
            'cpf' => sprintf('%s%08d', CPF_PREFIX, $numero),
            'telefone' => sprintf('%s%08d', TELEFONE_PREFIX, $numero),
        ]);

        $imported++;
        if ($imported % 500 === 0) {
            echo "  ... {$imported} gestantes\n";
        }
    }
});

echo "Importação concluída: {$imported} gestante(s).\n";

function parseFloat(?string $value): ?float
{
    if ($value === null || trim($value) === '') {
        return null;
    }

    return (float) str_replace(',', '.', trim($value));
}

function parseBool(?string $value): ?bool
{
    if ($value === null || trim($value) === '') {
        return null;
    }

    $normalized = strtolower(trim($value));

    if (in_array($normalized, ['1', '1.0', 'true', 'sim'], true)) {
        return true;
    }

    if (in_array($normalized, ['0', '0.0', 'false', 'nao'], true)) {
        return false;
    }

    return null;
}

function estimateBirthDate(?float $idadeMaterna): string
{
    if ($idadeMaterna === null || $idadeMaterna <= 0) {
        return Carbon::now()->subYears(25)->format('Y-m-d');
    }

    $anos = (int) floor($idadeMaterna);
    $meses = (int) round(($idadeMaterna - $anos) * 12);

    return Carbon::now()->subYears($anos)->subMonths($meses)->format('Y-m-d');
}
