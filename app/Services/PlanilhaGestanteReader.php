<?php

namespace App\Services;

use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Lê CSV ou Excel (primeira planilha) no mesmo formato de linhas associativas.
 */
class PlanilhaGestanteReader
{
    /** @var list<string> */
    public const EXTENSOES = ['csv', 'txt', 'xlsx', 'xls'];

    /**
     * Rótulos da planilha clínica (capa + legenda) para as chaves já usadas na importação.
     *
     * @var array<string, string>
     */
    private const ALIASES = [
        'registro' => 'id_gestante',
        'idade_meses' => 'idade_crianca_meses',
        'gemelar' => 'gestacao_gemelar',
        'raca_cor' => 'etnia',
        'img_gestacional' => 'imc_gestacional_categoria',
        'aborto' => 'abortamentos_previos',
        'cc_em_1_grau' => 'historia_familiar_cardiopatia',
        'dmg' => 'diabetes_gestacional',
        'dm_previo' => 'diabetes_pre_gestacional',
        'lupus_eritematoso_sistemico' => 'lupus',
        'has' => 'hipertensao_cronica',
        'pre_eclampsia' => 'hipertensao_pre_eclampsia',
        'infeccao_materna_bacteriana_aguda' => 'infeccao_materna_bacteriana',
    ];

    public function extensaoValida(string $nomeArquivo): bool
    {
        $ext = strtolower(pathinfo($nomeArquivo, PATHINFO_EXTENSION));

        return in_array($ext, self::EXTENSOES, true);
    }

    /**
     * @return array{header: list<string>, rows: list<array<string, string>>, skipped: int}
     */
    public function ler(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        if ($path === false || ! is_readable($path)) {
            throw new \RuntimeException('Não foi possível abrir o arquivo.');
        }

        $ext = strtolower($file->getClientOriginalExtension());

        if (in_array($ext, ['xlsx', 'xls'], true)) {
            return $this->lerExcel($path);
        }

        return $this->lerCsv($path);
    }

    /**
     * @return array{header: list<string>, rows: list<array<string, string>>, skipped: int}
     */
    private function lerCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Não foi possível abrir o CSV.');
        }

        $matriz = [];
        while (($row = fgetcsv($handle)) !== false) {
            $matriz[] = $row;
        }
        fclose($handle);

        if ($matriz === []) {
            throw new \RuntimeException('Arquivo sem cabeçalho.');
        }

        $indice = $this->indiceCabecalho($matriz);
        $header = $this->cabecalhoCanonico($matriz[$indice]);
        if ($header === [] || implode('', $header) === '') {
            throw new \RuntimeException('Arquivo sem cabeçalho.');
        }

        $rows = [];
        $skipped = 0;

        foreach (array_slice($matriz, $indice + 1) as $row) {
            if ($this->linhaVazia($row)) {
                continue;
            }

            if (count($row) !== count($header)) {
                $skipped++;

                continue;
            }

            $combinada = array_combine($header, array_map(fn ($c) => $this->cellToString($c), $row));
            if ($combinada === false) {
                $skipped++;

                continue;
            }

            $rows[] = $combinada;
        }

        return ['header' => $header, 'rows' => $rows, 'skipped' => $skipped];
    }

    /**
     * @return array{header: list<string>, rows: list<array<string, string>>, skipped: int}
     */
    private function lerExcel(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $matriz = $sheet->toArray(null, true, true, false);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        if ($matriz === []) {
            throw new \RuntimeException('Planilha vazia.');
        }

        $indice = $this->indiceCabecalho($matriz);
        $header = $this->cabecalhoCanonico($matriz[$indice]);
        if ($header === [] || implode('', $header) === '') {
            throw new \RuntimeException('Arquivo sem cabeçalho.');
        }

        $cols = count($header);
        $rows = [];

        foreach (array_slice($matriz, $indice + 1) as $row) {
            $valores = array_map(fn ($c) => $this->cellToString($c), array_slice($row, 0, $cols));
            if ($this->linhaVazia($valores)) {
                continue;
            }

            if (count($valores) < $cols) {
                $valores = array_pad($valores, $cols, '');
            }

            $combinada = array_combine($header, $valores);
            if ($combinada !== false) {
                $rows[] = $combinada;
            }
        }

        return ['header' => $header, 'rows' => $rows, 'skipped' => 0];
    }

    /**
     * @param  list<list<mixed>>  $matriz
     */
    private function indiceCabecalho(array $matriz): int
    {
        foreach ($matriz as $indice => $row) {
            if (in_array('id_gestante', $this->cabecalhoCanonico($row), true)) {
                return $indice;
            }
        }

        return 0;
    }

    /**
     * @param  list<mixed>  $header
     * @return list<string>
     */
    private function cabecalhoCanonico(array $header): array
    {
        $colunas = [];
        foreach ($header as $celula) {
            $slug = $this->slugCabecalho($this->cellToString($celula));
            $colunas[] = $slug === '' ? '' : (self::ALIASES[$slug] ?? $slug);
        }

        while ($colunas !== [] && end($colunas) === '') {
            array_pop($colunas);
        }

        return $colunas;
    }

    private function slugCabecalho(string $texto): string
    {
        $texto = preg_replace('/^\xEF\xBB\xBF/', '', $texto) ?? $texto;
        $texto = str_replace(["\r\n", "\r"], "\n", $texto);
        $texto = preg_replace('/\n\s*0\s*=.*$/s', '', $texto) ?? $texto;
        $texto = str_replace(['º', 'ª', '°'], '', $texto);
        $texto = strtolower(Str::ascii($texto));
        $texto = preg_replace('/[^a-z0-9]+/', '_', $texto) ?? $texto;

        return trim($texto, '_');
    }

    /**
     * @param  list<mixed>  $row
     */
    private function linhaVazia(array $row): bool
    {
        foreach ($row as $cell) {
            if ($this->cellToString($cell) !== '') {
                return false;
            }
        }

        return true;
    }

    private function cellToString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_float($value) && $value == floor($value)) {
            return (string) (int) $value;
        }

        return trim((string) $value);
    }
}
