<?php

namespace App\Services;

use App\Models\AnaliseHistorico;
use App\Models\Consulta;
use App\Models\Gestante;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardAnaliseService
{
    /**
     * IDs da última análise concluída por gestante (leve — só IDs).
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    public function idsUltimasAnalisesPorGestante(): Collection
    {
        return AnaliseHistorico::query()
            ->select(DB::raw('MAX(id) as id'))
            ->where('status', 'concluida')
            ->groupBy('gestante_id')
            ->pluck('id');
    }

    /**
     * Última análise concluída por gestante (com relações).
     * Evite em agregados do dashboard — preferir SQL.
     */
    public function obterUltimasAnalisesPorGestante(): Collection
    {
        $ids = $this->idsUltimasAnalisesPorGestante();

        if ($ids->isEmpty()) {
            return collect();
        }

        return AnaliseHistorico::query()
            ->with(['gestante', 'consulta'])
            ->whereIn('id', $ids)
            ->get();
    }

    /**
     * @return array{
     *     total_gestantes_analisadas: int,
     *     baixo: int, moderado: int, alto: int,
     *     pct_baixo: float, pct_moderado: float, pct_alto: float
     * }
     */
    public function distribuicaoRisco(): array
    {
        $ids = $this->idsUltimasAnalisesPorGestante();
        if ($ids->isEmpty()) {
            return [
                'total_gestantes_analisadas' => 0,
                'baixo' => 0,
                'moderado' => 0,
                'alto' => 0,
                'pct_baixo' => 0.0,
                'pct_moderado' => 0.0,
                'pct_alto' => 0.0,
            ];
        }

        $rows = AnaliseHistorico::query()
            ->whereIn('id', $ids)
            ->select('classificacao_risco', DB::raw('COUNT(*) as total'))
            ->groupBy('classificacao_risco')
            ->pluck('total', 'classificacao_risco');

        $baixo = (int) ($rows['baixo'] ?? 0);
        $moderado = (int) ($rows['moderado'] ?? 0);
        $alto = (int) ($rows['alto'] ?? 0);
        $total = $baixo + $moderado + $alto;

        return [
            'total_gestantes_analisadas' => $total,
            'baixo' => $baixo,
            'moderado' => $moderado,
            'alto' => $alto,
            'pct_baixo' => $total > 0 ? round(100 * $baixo / $total, 1) : 0.0,
            'pct_moderado' => $total > 0 ? round(100 * $moderado / $total, 1) : 0.0,
            'pct_alto' => $total > 0 ? round(100 * $alto / $total, 1) : 0.0,
        ];
    }

    public function filaEcocardiograma(int $limite = 20): Collection
    {
        $ids = $this->idsUltimasAnalisesPorGestante();
        if ($ids->isEmpty()) {
            return collect();
        }

        return AnaliseHistorico::query()
            ->with(['gestante'])
            ->whereIn('id', $ids)
            ->where('recomenda_ecocardiograma', true)
            ->orderByDesc('score_prioridade')
            ->limit($limite)
            ->get([
                'id',
                'gestante_id',
                'consulta_id',
                'classificacao_risco',
                'probabilidade_ccf',
                'recomenda_ecocardiograma',
                'prioridade_ecocardiograma',
                'score_prioridade',
            ]);
    }

    /**
     * @return array{
     *     total_analises: int,
     *     total_erros: int,
     *     media_probabilidade_ccf: float|null,
     *     gestantes_com_eco_recomendada: int
     * }
     */
    public function indicadoresModelo(): array
    {
        $ids = $this->idsUltimasAnalisesPorGestante();
        $media = null;
        $eco = 0;

        if ($ids->isNotEmpty()) {
            $media = AnaliseHistorico::query()
                ->whereIn('id', $ids)
                ->avg('probabilidade_ccf');
            $eco = AnaliseHistorico::query()
                ->whereIn('id', $ids)
                ->where('recomenda_ecocardiograma', true)
                ->count();
        }

        return [
            'total_analises' => AnaliseHistorico::where('status', 'concluida')->count(),
            'total_erros' => AnaliseHistorico::where('status', 'erro')->count(),
            'media_probabilidade_ccf' => $media !== null ? round((float) $media * 100, 2) : null,
            'gestantes_com_eco_recomendada' => $eco,
        ];
    }

    public function analisesRecentes(int $limite = 10): Collection
    {
        return AnaliseHistorico::query()
            ->with(['gestante'])
            ->where('status', 'concluida')
            ->orderByDesc('created_at')
            ->limit($limite)
            ->get([
                'id',
                'gestante_id',
                'evento_tipo',
                'classificacao_risco',
                'probabilidade_ccf',
                'recomenda_ecocardiograma',
                'prioridade_ecocardiograma',
                'score_prioridade',
                'created_at',
            ]);
    }

    /**
     * @return array{urgente: int, alta: int, moderada: int, baixa: int, sem_eco: int}
     */
    public function distribuicaoPrioridadeEco(): array
    {
        $ids = $this->idsUltimasAnalisesPorGestante();
        if ($ids->isEmpty()) {
            return [
                'urgente' => 0,
                'alta' => 0,
                'moderada' => 0,
                'baixa' => 0,
                'sem_eco' => 0,
            ];
        }

        $porPrioridade = AnaliseHistorico::query()
            ->whereIn('id', $ids)
            ->where('recomenda_ecocardiograma', true)
            ->select('prioridade_ecocardiograma', DB::raw('COUNT(*) as total'))
            ->groupBy('prioridade_ecocardiograma')
            ->pluck('total', 'prioridade_ecocardiograma');

        $comEco = (int) $porPrioridade->sum();
        $total = $ids->count();

        return [
            'urgente' => (int) ($porPrioridade['urgente'] ?? 0),
            'alta' => (int) ($porPrioridade['alta'] ?? 0),
            'moderada' => (int) ($porPrioridade['moderada'] ?? 0),
            'baixa' => (int) ($porPrioridade['baixa'] ?? 0),
            'sem_eco' => max(0, $total - $comEco),
        ];
    }

    /**
     * Histograma de probabilidade CCF (última análise por gestante).
     *
     * @return array{labels: list<string>, valores: list<int>}
     */
    public function histogramaProbabilidadeCcf(int $faixas = 10): array
    {
        $labels = [];
        $valores = array_fill(0, $faixas, 0);

        for ($i = 0; $i < $faixas; $i++) {
            $min = $i * 10;
            $max = ($i + 1) * 10;
            $labels[] = $i === $faixas - 1
                ? sprintf('%d-100%%', $min)
                : sprintf('%d-%d%%', $min, $max);
        }

        $ids = $this->idsUltimasAnalisesPorGestante();
        if ($ids->isEmpty()) {
            return ['labels' => $labels, 'valores' => $valores];
        }

        // Só probabilidade — sem shap/JSON.
        $probs = AnaliseHistorico::query()
            ->whereIn('id', $ids)
            ->whereNotNull('probabilidade_ccf')
            ->pluck('probabilidade_ccf');

        foreach ($probs as $prob) {
            $pct = (float) $prob * 100;
            $idx = min($faixas - 1, (int) floor($pct / 10));
            $valores[$idx]++;
        }

        return ['labels' => $labels, 'valores' => $valores];
    }

    /**
     * Dados agregados para Chart.js no dashboard.
     *
     * @return array<string, mixed>
     */
    public function dadosGraficosPopulacionais(): array
    {
        $risco = $this->distribuicaoRisco();
        $eco = $this->distribuicaoPrioridadeEco();
        $hist = $this->histogramaProbabilidadeCcf();

        return [
            'risco' => [
                'labels' => ['Baixo', 'Moderado', 'Alto'],
                'valores' => [$risco['baixo'], $risco['moderado'], $risco['alto']],
            ],
            'prioridade_eco' => [
                'labels' => ['Urgente', 'Alta', 'Moderada', 'Baixa', 'Sem eco'],
                'valores' => [$eco['urgente'], $eco['alta'], $eco['moderada'], $eco['baixa'], $eco['sem_eco']],
            ],
            'probabilidade' => $hist,
        ];
    }

    /**
     * Totais do cohort no cadastro local (quando a API ML não publica /analise/descritiva).
     *
     * @return array{fonte: string, total_gestantes: int, total_consultas: int, prevalencia_ccf: float}
     */
    public function resumoCohortLocal(): array
    {
        $totalGestantes = (int) Gestante::query()->count();
        $totalConsultas = (int) Consulta::query()->count();
        $chd = (int) Consulta::query()->where('chd_confirmada', true)->count();

        return [
            'fonte' => 'cadastro local',
            'total_gestantes' => $totalGestantes,
            'total_consultas' => $totalConsultas,
            'prevalencia_ccf' => $totalConsultas > 0 ? round($chd / $totalConsultas, 4) : 0.0,
        ];
    }

    /**
     * Gráficos Chart.js do cohort a partir de consultas/análises locais.
     *
     * @return list<array{slug: string, titulo: string, tipo: string, labels: list<string>, valores: list<int|float>}>
     */
    public function dadosGraficosCohort(): array
    {
        $totalConsultas = (int) Consulta::query()->count();
        if ($totalConsultas === 0) {
            return [];
        }

        $chdSim = (int) Consulta::query()->where('chd_confirmada', true)->count();
        $charts = [
            [
                'slug' => 'prevalencia',
                'titulo' => 'Prevalência de CCF',
                'tipo' => 'doughnut',
                'labels' => ['Cardiopatia congênita confirmada', 'Sem cardiopatia congênita confirmada'],
                'valores' => [$chdSim, max(0, $totalConsultas - $chdSim)],
            ],
        ];

        $etniaExpr = "COALESCE(NULLIF(TRIM(etnia), ''), 'Não informada')";
        $etnias = Consulta::query()
            ->selectRaw("{$etniaExpr} as rotulo, COUNT(*) as total")
            ->groupByRaw($etniaExpr)
            ->orderByDesc('total')
            ->pluck('total', 'rotulo');
        if ($etnias->isNotEmpty()) {
            $charts[] = $this->graficoBarras('etnia', 'Etnia', $etnias->all());
        }

        $paridadeExpr = "CASE
            WHEN paridade IS NULL THEN 'Não informada'
            WHEN paridade <= 0 THEN 'Primigesta'
            WHEN paridade = 1 THEN '1 filho'
            WHEN paridade = 2 THEN '2 filhos'
            ELSE 'Mais de 3 filhos'
        END";
        $paridades = Consulta::query()
            ->selectRaw("{$paridadeExpr} as rotulo, COUNT(*) as total")
            ->groupByRaw($paridadeExpr)
            ->pluck('total', 'rotulo');
        $paridade = [];
        foreach (['Primigesta', '1 filho', '2 filhos', 'Mais de 3 filhos', 'Não informada'] as $rotulo) {
            $quantidade = (int) ($paridades[$rotulo] ?? 0);
            if ($quantidade > 0) {
                $paridade[$rotulo] = $quantidade;
            }
        }
        if (array_sum($paridade) > 0) {
            $charts[] = $this->graficoBarras('paridade', 'Paridade', $paridade);
        }

        $faixasIdade = [
            '1 a 6 meses' => (int) Consulta::query()->whereBetween('idade_crianca_meses', [1, 6])->count(),
            '7 a 12 meses' => (int) Consulta::query()->whereBetween('idade_crianca_meses', [7, 12])->count(),
            '13 a 18 meses' => (int) Consulta::query()->whereBetween('idade_crianca_meses', [13, 18])->count(),
            '19 a 24 meses' => (int) Consulta::query()->whereBetween('idade_crianca_meses', [19, 24])->count(),
        ];
        if (array_sum($faixasIdade) > 0) {
            $charts[] = $this->graficoBarras('idade_crianca', 'Meses de vida', $faixasIdade);
        }

        $rotulosImc = [
            0 => 'Normal',
            1 => 'Acima de 24,9',
            2 => 'Abaixo de 18,5',
        ];
        $imcFaixas = Consulta::query()
            ->whereNotNull('imc_gestacional_categoria')
            ->selectRaw('imc_gestacional_categoria as faixa, COUNT(*) as total')
            ->groupBy('imc_gestacional_categoria')
            ->pluck('total', 'faixa');
        $imc = [];
        foreach ($rotulosImc as $faixa => $rotulo) {
            $imc[$rotulo] = (int) ($imcFaixas[$faixa] ?? 0);
        }
        if (array_sum($imc) > 0) {
            $charts[] = $this->graficoBarras('imc', 'IMC gestacional', $imc);
        }

        $frequenciaClinica = $this->serieFatores([
            'CC em 1º grau' => static fn ($q) => $q->where('historico_familiar_chd', true),
            'Diabetes gestacional' => static fn ($q) => $q->where('diabetes_gestacional', true),
            'Diabetes prévio' => static fn ($q) => $q->where('diabetes_pre_gestacional', true),
            'Lúpus' => static fn ($q) => $q->where('lupus_eritomatoso_sistemico', true),
            'Sjögren' => static fn ($q) => $q->where('sindrome_sjogren', true),
            'Tireoide' => static fn ($q) => $q->where('doencas_tireoidianas', true),
            'HAS' => static fn ($q) => $q->where('hipertensao_cronica', true),
            'Pré-eclâmpsia' => static fn ($q) => $q->where('hipertensao_pre_eclampsia', true),
            'Eclâmpsia' => static fn ($q) => $q->where('eclampsia', true),
            'Drogas ilícitas' => static fn ($q) => $q->where('drogas_ilicitas', true),
            'Lícitas teratogênicas' => static fn ($q) => $q->where('licitas_teratogenicas', true),
            'Infecção bacteriana' => static fn ($q) => $q->where('infeccao_materna_bacteriana', true),
        ]);
        $frequenciaObstetrica = $this->serieFatores([
            'Prematuridade' => static fn ($q) => $q->where('prematuridade', true),
            'Gemelar' => static fn ($q) => $q->where('tipo_gestacao', 'gemelar'),
            'Aborto' => static fn ($q) => $q->where('abortamentos_previos', '>', 0),
            'Polidrâmnio' => static fn ($q) => $q->where('polidramnio_oligoidramnio', 'polidramnio'),
            'Oligodrâmnio' => static fn ($q) => $q->where('polidramnio_oligoidramnio', 'oligodramnio'),
        ]);
        $casos = $this->serieFatores(array_merge(
            [
                'Prematuridade' => static fn ($q) => $q->where('prematuridade', true),
                'Gemelar' => static fn ($q) => $q->where('tipo_gestacao', 'gemelar'),
                'Aborto' => static fn ($q) => $q->where('abortamentos_previos', '>', 0),
                'Polidrâmnio' => static fn ($q) => $q->where('polidramnio_oligoidramnio', 'polidramnio'),
                'Oligodrâmnio' => static fn ($q) => $q->where('polidramnio_oligoidramnio', 'oligodramnio'),
            ],
            [
                'CC em 1º grau' => static fn ($q) => $q->where('historico_familiar_chd', true),
                'Diabetes gestacional' => static fn ($q) => $q->where('diabetes_gestacional', true),
                'Diabetes prévio' => static fn ($q) => $q->where('diabetes_pre_gestacional', true),
                'Lúpus' => static fn ($q) => $q->where('lupus_eritomatoso_sistemico', true),
                'Sjögren' => static fn ($q) => $q->where('sindrome_sjogren', true),
                'Tireoide' => static fn ($q) => $q->where('doencas_tireoidianas', true),
                'HAS' => static fn ($q) => $q->where('hipertensao_cronica', true),
                'Pré-eclâmpsia' => static fn ($q) => $q->where('hipertensao_pre_eclampsia', true),
                'Eclâmpsia' => static fn ($q) => $q->where('eclampsia', true),
                'Drogas ilícitas' => static fn ($q) => $q->where('drogas_ilicitas', true),
                'Lícitas teratogênicas' => static fn ($q) => $q->where('licitas_teratogenicas', true),
                'Infecção bacteriana' => static fn ($q) => $q->where('infeccao_materna_bacteriana', true),
            ],
        ), true);

        if ($frequenciaClinica !== []) {
            $charts[] = $this->graficoBarras('fatores_risco', 'Frequência das comorbidades', $frequenciaClinica);
        }
        if ($frequenciaObstetrica !== []) {
            $charts[] = $this->graficoBarras('obstetricos', 'Marcadores obstétricos', $frequenciaObstetrica);
        }
        if ($casos !== []) {
            $charts[] = $this->graficoBarras('casos_ccf', 'Cardiopatias segundo o fator', $casos);
        }

        return $charts;
    }

    /**
     * @param  array<string, int>  $serie
     * @return array{slug: string, titulo: string, tipo: string, labels: list<string>, valores: list<int>}
     */
    private function graficoBarras(string $slug, string $titulo, array $serie): array
    {
        return [
            'slug' => $slug,
            'titulo' => $titulo,
            'tipo' => 'bar',
            'labels' => array_keys($serie),
            'valores' => array_map('intval', array_values($serie)),
        ];
    }

    /**
     * @param  array<string, callable(\Illuminate\Database\Eloquent\Builder): void>  $fatores
     * @return array<string, int>
     */
    private function serieFatores(array $fatores, bool $somenteCardiopatia = false): array
    {
        $serie = [];
        foreach ($fatores as $rotulo => $filtrar) {
            $consulta = Consulta::query();
            $filtrar($consulta);
            if ($somenteCardiopatia) {
                $consulta->where('chd_confirmada', true);
            }
            $total = (int) $consulta->count();
            if ($total > 0) {
                $serie[$rotulo] = $total;
            }
        }
        arsort($serie);

        return $serie;
    }

    /**
     * Slugs e rótulos dos gráficos descritivos servidos pela API GestRisk.
     *
     * @return list<array{slug: string, titulo: string}>
     */
    public static function catalogoGraficosDescritivos(): array
    {
        return [
            ['slug' => 'prevalencia', 'titulo' => 'Prevalência de CCF'],
            ['slug' => 'distribuicao', 'titulo' => 'Idade, IMC e IG'],
            ['slug' => 'fatores_risco', 'titulo' => 'Cardiopatia congênita por fatores de risco'],
            ['slug' => 'risco', 'titulo' => 'Distribuição de risco (cohort)'],
            ['slug' => 'probabilidade', 'titulo' => 'Distribuição de probabilidade'],
            ['slug' => 'prioridade_eco', 'titulo' => 'Priorização ecocardiograma'],
            ['slug' => 'intercorrencias_cat', 'titulo' => 'Intercorrências por categoria'],
            ['slug' => 'top_intercorrencias', 'titulo' => 'Top intercorrências'],
            ['slug' => 'importancia', 'titulo' => 'Importância das features'],
            ['slug' => 'experimentos', 'titulo' => 'Comparativo de experimentos'],
            ['slug' => 'etnia', 'titulo' => 'Distribuição por etnia'],
        ];
    }

    /**
     * Título amigável do gráfico descritivo (fallback = slug humanizado).
     */
    public static function tituloGraficoDescritivo(string $slug): string
    {
        foreach (self::catalogoGraficosDescritivos() as $item) {
            if (($item['slug'] ?? '') === $slug) {
                return (string) $item['titulo'];
            }
        }

        return ucfirst(str_replace('_', ' ', $slug));
    }

    public static function labelEvento(string $tipo): string
    {
        return match ($tipo) {
            AnaliseHistorico::EVENTO_CADASTRO => 'Cadastro',
            AnaliseHistorico::EVENTO_CONSULTA => 'Nova consulta',
            AnaliseHistorico::EVENTO_ALTERACAO => 'Alteração clínica',
            AnaliseHistorico::EVENTO_EXAME => 'Exame',
            AnaliseHistorico::EVENTO_INTERCORRENCIA => 'Intercorrência',
            AnaliseHistorico::EVENTO_REPROCESSAMENTO => 'Reprocessamento',
            default => ucfirst(str_replace('_', ' ', $tipo)),
        };
    }

    public static function labelRisco(?string $risco): string
    {
        return match ($risco) {
            'baixo' => 'Baixo',
            'moderado' => 'Moderado',
            'alto' => 'Alto',
            default => 'Não informado',
        };
    }

    public static function textoEscoreModelo(mixed $probabilidade): string
    {
        if ($probabilidade === null || $probabilidade === '') {
            return 'Sem escore do modelo';
        }

        return 'Escore do modelo '.number_format((float) $probabilidade * 100, 1, ',', '.').'%';
    }

    public static function labelFeatureModelo(string $codigo): string
    {
        $mapa = [
            'prematuridade' => 'Prematuridade',
            'gestacao_gemelar' => 'Gestação gemelar',
            'abortamentos_previos' => 'Abortamentos prévios',
            'historia_familiar_cardiopatia' => 'História familiar de cardiopatia',
            'diabetes_gestacional' => 'Diabetes gestacional',
            'diabetes_pre_gestacional' => 'Diabetes pré-gestacional',
            'lupus' => 'Lúpus',
            'sindrome_sjogren' => 'Síndrome de Sjögren',
            'doenca_tireoide' => 'Doença da tireoide',
            'hipertensao_cronica' => 'Hipertensão crônica',
            'hipertensao_pre_eclampsia' => 'Pré-eclâmpsia',
            'eclampsia' => 'Eclâmpsia',
            'drogas_ilicitas' => 'Drogas ilícitas',
            'licitas_teratogenicas' => 'Lícitas teratogênicas',
            'polidramnio' => 'Polidrâmnio',
            'oligodramnio' => 'Oligodrâmnio',
            'infeccao_materna_bacteriana' => 'Infecção bacteriana',
            'etnia' => 'Etnia',
            'imc_gestacional_categoria' => 'IMC gestacional',
            'paridade' => 'Paridade',
        ];

        $chave = mb_strtolower(trim($codigo));

        return $mapa[$chave] ?? $codigo;
    }

    /**
     * Classe CSS da pill de risco clínico (tokens --risk-*).
     */
    public static function pillClassRisco(?string $risco): string
    {
        return match ($risco) {
            'baixo' => 'consulta-pill--risk-low',
            'moderado' => 'consulta-pill--risk-mid',
            'alto' => 'consulta-pill--risk-high',
            default => 'consulta-pill--info',
        };
    }

    public static function labelPrioridadeEco(?string $prioridade): string
    {
        return match ($prioridade) {
            'urgente' => 'Urgente',
            'alta' => 'Alta',
            'moderada' => 'Moderada',
            'baixa' => 'Baixa',
            default => 'Não informado',
        };
    }

    /**
     * Normaliza SHAP da API ({feature, impact}) ou GestRisk ({top_contribuicoes: [...]})
     * para o formato do gráfico Chart.js. Default: análise completa (36 features).
     *
     * @return list<array{nome: string, percentual_contribuicao: float, direcao: string, impact: float}>
     */
    public static function normalizeShapItems(mixed $shap, int $limit = 36): array
    {
        if (! is_array($shap) || $shap === []) {
            return [];
        }

        if (isset($shap['top_contribuicoes']) && is_array($shap['top_contribuicoes'])) {
            $raw = $shap['top_contribuicoes'];
        } elseif (array_is_list($shap)) {
            $raw = $shap;
        } else {
            return [];
        }

        $rows = [];
        foreach ($raw as $item) {
            if (! is_array($item)) {
                continue;
            }

            $nome = self::labelFeatureModelo((string) ($item['nome'] ?? $item['campo'] ?? $item['feature'] ?? 'Não informado'));
            $impact = (float) ($item['impact'] ?? $item['impacto'] ?? $item['impacto_absoluto'] ?? 0);

            if (isset($item['percentual_contribuicao']) && is_numeric($item['percentual_contribuicao'])) {
                $pct = (float) $item['percentual_contribuicao'];
                $direcao = (string) ($item['direcao'] ?? ($impact >= 0 ? 'aumenta_risco' : 'reduz_risco'));
            } else {
                $pct = abs($impact);
                $direcao = (string) ($item['direcao'] ?? ($impact >= 0 ? 'aumenta_risco' : 'reduz_risco'));
            }

            $rows[] = [
                'nome' => $nome,
                'percentual_contribuicao' => $pct,
                'direcao' => $direcao,
                'impact' => $impact,
            ];
        }

        if ($rows === []) {
            return [];
        }

        // Se vieram impactos brutos (soma típica << 100), converte para % relativa.
        $soma = array_sum(array_column($rows, 'percentual_contribuicao'));
        if ($soma > 0 && $soma <= 1.5) {
            foreach ($rows as &$row) {
                $row['percentual_contribuicao'] = round(($row['percentual_contribuicao'] / $soma) * 100, 1);
            }
            unset($row);
        } elseif ($soma > 1.5 && $soma < 40) {
            foreach ($rows as &$row) {
                $row['percentual_contribuicao'] = round(($row['percentual_contribuicao'] / $soma) * 100, 1);
            }
            unset($row);
        }

        usort($rows, static fn ($a, $b) => $b['percentual_contribuicao'] <=> $a['percentual_contribuicao']);

        return array_slice($rows, 0, max(1, $limit));
    }

    /**
     * Campos que a consulta não registrou e que não podem aparecer como fator da análise.
     *
     * @return array<string, true>
     */
    public static function fatoresNaoRegistrados(?Consulta $consulta): array
    {
        if ($consulta === null) {
            return [];
        }

        $chaves = [];
        $marcar = static function (array $nomes) use (&$chaves): void {
            foreach ($nomes as $nome) {
                $chaves[mb_strtolower($nome)] = true;
            }
        };

        if ($consulta->idade_materna === null) {
            $marcar(['idade_materna', 'idade', 'Idade materna']);
        }
        if ($consulta->idade_gestacional === null) {
            $marcar(['idade_gestacional', 'Idade gestacional']);
        }
        if ($consulta->imc_pre_gestacional === null) {
            $marcar(['imc_pre_gestacional', 'imc', 'IMC pré-gestacional']);
        }
        if ($consulta->corionicidade === null || trim((string) $consulta->corionicidade) === '') {
            $marcar(['corionicidade', 'Corionicidade']);
        }

        return $chaves;
    }

    /**
     * @param  list<array<string, mixed>>  $itens
     * @param  array<string, true>  $ausentes
     * @return list<array<string, mixed>>
     */
    public static function semFatoresAusentes(array $itens, array $ausentes): array
    {
        if ($ausentes === []) {
            return $itens;
        }

        $visiveis = [];
        foreach ($itens as $item) {
            if (! is_array($item)) {
                continue;
            }
            $nome = (string) ($item['nome'] ?? $item['feature'] ?? $item['campo'] ?? '');
            if ($nome !== '' && isset($ausentes[mb_strtolower($nome)])) {
                continue;
            }
            $visiveis[] = $item;
        }

        if ($visiveis !== [] && isset($visiveis[0]['explicacao'])) {
            $impacto = (float) ($visiveis[0]['impacto'] ?? $visiveis[0]['impact'] ?? 0);
            $visiveis[0]['explicacao'] = $impacto >= 0
                ? 'É o que mais pesa para maior atenção nesta triagem.'
                : 'É o que mais pesa para menor atenção nesta triagem.';
        }

        return $visiveis;
    }

    /**
     * Leitura descritiva de todas as variáveis da ficha importada.
     *
     * @return array{
     *     disponivel: bool,
     *     n: int,
     *     gestantes: int,
     *     chd: int,
     *     abertura: string,
     *     grupos: array<string, array{texto: string, tabelas: list<array{titulo: string, nota: string, linhas: list<array{rotulo: string, n: int, pct: float, chd: int, pct_grupo: float}>}>}>
     * }
     */
    public function leituraDescritiva(): array
    {
        $n = (int) Consulta::query()->count();
        $vazio = [
            'disponivel' => false,
            'n' => 0,
            'gestantes' => 0,
            'chd' => 0,
            'abertura' => '',
            'grupos' => [],
        ];
        if ($n === 0) {
            return $vazio;
        }

        $gestantes = (int) (Consulta::query()->selectRaw('COUNT(DISTINCT gestante_id) as total')->first()->total ?? 0);
        $chd = (int) Consulta::query()->where('chd_confirmada', true)->count();
        $totais = $this->agregarMarcadores();

        $etnia = $this->tabelaPorExpressao(
            "COALESCE(NULLIF(TRIM(etnia), ''), 'Não informada')",
            $n,
        );
        $paridade = $this->tabelaPorExpressao(
            "CASE
                WHEN paridade IS NULL THEN 'Não informada'
                WHEN paridade <= 0 THEN 'Primigesta'
                WHEN paridade = 1 THEN '1 filho'
                WHEN paridade = 2 THEN '2 filhos'
                ELSE 'Mais de 3 filhos'
            END",
            $n,
            ['Primigesta', '1 filho', '2 filhos', 'Mais de 3 filhos', 'Não informada'],
        );
        $imc = $this->tabelaPorExpressao(
            "CASE
                WHEN imc_gestacional_categoria IS NULL THEN 'Não informado'
                WHEN imc_gestacional_categoria = 0 THEN 'Normal'
                WHEN imc_gestacional_categoria = 1 THEN 'Acima de 24,9'
                WHEN imc_gestacional_categoria = 2 THEN 'Abaixo de 18,5'
                ELSE 'Não informado'
            END",
            $n,
            ['Normal', 'Acima de 24,9', 'Abaixo de 18,5', 'Não informado'],
        );
        $idadeFaixas = $this->tabelaPorExpressao(
            "CASE
                WHEN idade_crianca_meses IS NULL THEN 'Não informada'
                WHEN idade_crianca_meses <= 6 THEN 'Até 6 meses'
                WHEN idade_crianca_meses <= 12 THEN '7 a 12 meses'
                WHEN idade_crianca_meses <= 18 THEN '13 a 18 meses'
                WHEN idade_crianca_meses <= 24 THEN '19 a 24 meses'
                ELSE '25 meses ou mais'
            END",
            $n,
            ['Até 6 meses', '7 a 12 meses', '13 a 18 meses', '19 a 24 meses', '25 meses ou mais', 'Não informada'],
        );

        $idades = Consulta::query()
            ->whereNotNull('idade_crianca_meses')
            ->orderBy('idade_crianca_meses')
            ->pluck('idade_crianca_meses')
            ->map(static fn ($valor) => (float) $valor)
            ->all();
        $idade = $this->resumoNumerico($idades);

        $clinicas = $this->linhasMarcadores($totais, $n, [
            'historico_familiar_chd' => 'Cardiopatia em 1º grau',
            'diabetes_gestacional' => 'Diabetes gestacional',
            'diabetes_pre_gestacional' => 'Diabetes prévio',
            'lupus_eritomatoso_sistemico' => 'Lúpus',
            'sindrome_sjogren' => 'Sjögren',
            'doencas_tireoidianas' => 'Doença da tireoide',
            'hipotireoidismo' => 'Hipotireoidismo',
            'hipertireoidismo' => 'Hipertireoidismo',
            'hipertensao_cronica' => 'HAS',
            'hipertensao_pre_eclampsia' => 'Pré-eclâmpsia',
            'eclampsia' => 'Eclâmpsia',
            'drogas_ilicitas' => 'Drogas ilícitas',
            'licitas_teratogenicas' => 'Lícitas teratogênicas',
            'infeccao_materna_bacteriana' => 'Infecção bacteriana',
            'rubeola' => 'Rubéola',
            'fenilcetonuria' => 'Fenilcetonúria',
        ]);
        $obstetricas = $this->linhasMarcadores($totais, $n, [
            'prematuridade' => 'Prematuridade',
            'gemelar' => 'Gestação gemelar',
            'aborto' => 'Abortamento prévio',
            'poli' => 'Polidrâmnio',
            'oligo' => 'Oligodrâmnio',
        ]);

        $abertura = 'A base reúne '.$this->fichas($n);
        if ($gestantes !== $n) {
            $abertura .= ', de '.$gestantes.' '.($gestantes === 1 ? 'gestante' : 'gestantes');
        }
        $abertura .= '. A cardiopatia congênita está confirmada em '.$this->fichas($chd)
            .' ('.$this->fmtPct($this->pct($chd, $n)).'%). '
            .'Os percentuais usam o total de fichas como denominador. '
            .'Nas comorbidades e nos marcadores obstétricos, a mesma ficha pode entrar em mais de um item. '
            .'A coluna de cardiopatia mostra a prevalência dentro daquela categoria.';

        return [
            'disponivel' => true,
            'n' => $n,
            'gestantes' => $gestantes,
            'chd' => $chd,
            'abertura' => $abertura,
            'grupos' => [
                'sociodemograficas' => [
                    'texto' => $this->textoParticao('A etnia', $etnia, $n)
                        .' '.$this->textoParticao('A paridade', $paridade, $n),
                    'tabelas' => [
                        $this->tabela('Etnia', 'Cada ficha entra em uma categoria.', $etnia),
                        $this->tabela('Paridade', 'Categorias do instrumento: primigesta, 1 filho, 2 filhos e mais de 3.', $paridade),
                    ],
                ],
                'antropometricas' => [
                    'texto' => $this->textoIdade($idade, $n).' '.$this->textoParticao('O IMC gestacional', $imc, $n, false),
                    'tabelas' => [
                        $this->tabela('Meses de vida', 'Faixas registradas na ficha.', $idadeFaixas),
                        $this->tabela('IMC gestacional', 'Faixas registradas na planilha.', $imc),
                    ],
                ],
                'clinicas' => [
                    'texto' => $this->textoMarcadores('Entre as comorbidades', $clinicas),
                    'tabelas' => [
                        $this->tabela('Comorbidades', 'Presença do fator na ficha. Zero significa que a variável foi prevista e não ocorreu.', $clinicas),
                    ],
                ],
                'obstetricas' => [
                    'texto' => $this->textoMarcadores('Entre os marcadores obstétricos', $obstetricas),
                    'tabelas' => [
                        $this->tabela('Marcadores obstétricos', 'Presença do marcador na ficha.', $obstetricas),
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, int>
     */
    private function agregarMarcadores(): array
    {
        $colunas = [
            'historico_familiar_chd',
            'diabetes_gestacional',
            'diabetes_pre_gestacional',
            'lupus_eritomatoso_sistemico',
            'sindrome_sjogren',
            'doencas_tireoidianas',
            'hipotireoidismo',
            'hipertireoidismo',
            'hipertensao_cronica',
            'hipertensao_pre_eclampsia',
            'eclampsia',
            'drogas_ilicitas',
            'licitas_teratogenicas',
            'infeccao_materna_bacteriana',
            'rubeola',
            'fenilcetonuria',
            'prematuridade',
        ];
        $partes = [];
        foreach ($colunas as $coluna) {
            $partes[] = "SUM(CASE WHEN {$coluna} = 1 THEN 1 ELSE 0 END) as n_{$coluna}";
            $partes[] = "SUM(CASE WHEN {$coluna} = 1 AND chd_confirmada = 1 THEN 1 ELSE 0 END) as chd_{$coluna}";
        }
        $partes[] = "SUM(CASE WHEN LOWER(COALESCE(tipo_gestacao, '')) = 'gemelar' THEN 1 ELSE 0 END) as n_gemelar";
        $partes[] = "SUM(CASE WHEN LOWER(COALESCE(tipo_gestacao, '')) = 'gemelar' AND chd_confirmada = 1 THEN 1 ELSE 0 END) as chd_gemelar";
        $partes[] = 'SUM(CASE WHEN abortamentos_previos > 0 THEN 1 ELSE 0 END) as n_aborto';
        $partes[] = 'SUM(CASE WHEN abortamentos_previos > 0 AND chd_confirmada = 1 THEN 1 ELSE 0 END) as chd_aborto';
        $partes[] = "SUM(CASE WHEN polidramnio_oligoidramnio = 'polidramnio' THEN 1 ELSE 0 END) as n_poli";
        $partes[] = "SUM(CASE WHEN polidramnio_oligoidramnio = 'polidramnio' AND chd_confirmada = 1 THEN 1 ELSE 0 END) as chd_poli";
        $partes[] = "SUM(CASE WHEN polidramnio_oligoidramnio = 'oligodramnio' THEN 1 ELSE 0 END) as n_oligo";
        $partes[] = "SUM(CASE WHEN polidramnio_oligoidramnio = 'oligodramnio' AND chd_confirmada = 1 THEN 1 ELSE 0 END) as chd_oligo";

        $row = Consulta::query()->selectRaw(implode(', ', $partes))->first();
        $totais = [];
        if ($row !== null) {
            foreach ($row->getAttributes() as $chave => $valor) {
                $totais[(string) $chave] = (int) $valor;
            }
        }

        return $totais;
    }

    /**
     * @param  array<string, int>  $totais
     * @param  array<string, string>  $rotulos
     * @return list<array{rotulo: string, n: int, pct: float, chd: int, pct_grupo: float}>
     */
    private function linhasMarcadores(array $totais, int $base, array $rotulos): array
    {
        $linhas = [];
        foreach ($rotulos as $chave => $rotulo) {
            $quantidade = (int) ($totais['n_'.$chave] ?? 0);
            $cardiopatias = (int) ($totais['chd_'.$chave] ?? 0);
            $linhas[] = [
                'rotulo' => $rotulo,
                'n' => $quantidade,
                'pct' => $this->pct($quantidade, $base),
                'chd' => $cardiopatias,
                'pct_grupo' => $this->pct($cardiopatias, $quantidade),
            ];
        }
        usort($linhas, static function (array $a, array $b): int {
            return [$b['n'], $a['rotulo']] <=> [$a['n'], $b['rotulo']];
        });

        return $linhas;
    }

    /**
     * @param  list<string>|null  $ordem
     * @return list<array{rotulo: string, n: int, pct: float, chd: int, pct_grupo: float}>
     */
    private function tabelaPorExpressao(string $expressao, int $base, ?array $ordem = null): array
    {
        $rows = Consulta::query()
            ->selectRaw("{$expressao} as rotulo, COUNT(*) as total, SUM(CASE WHEN chd_confirmada = 1 THEN 1 ELSE 0 END) as chd")
            ->groupByRaw($expressao)
            ->get();

        $porRotulo = [];
        foreach ($rows as $row) {
            $rotulo = trim((string) $row->rotulo);
            if ($rotulo === '') {
                $rotulo = 'Não informado';
            }
            $quantidade = (int) $row->total;
            $cardiopatias = (int) $row->chd;
            $porRotulo[$rotulo] = [
                'rotulo' => $rotulo,
                'n' => $quantidade,
                'pct' => $this->pct($quantidade, $base),
                'chd' => $cardiopatias,
                'pct_grupo' => $this->pct($cardiopatias, $quantidade),
            ];
        }

        if ($ordem !== null) {
            $linhas = [];
            foreach ($ordem as $rotulo) {
                if (isset($porRotulo[$rotulo])) {
                    $linhas[] = $porRotulo[$rotulo];
                    unset($porRotulo[$rotulo]);
                }
            }
            foreach ($porRotulo as $restante) {
                $linhas[] = $restante;
            }

            return $linhas;
        }

        $linhas = array_values($porRotulo);
        usort($linhas, static fn (array $a, array $b): int => $b['n'] <=> $a['n']);

        return $linhas;
    }

    /**
     * @param  list<float>  $valores
     * @return array{n: int, media: float, dp: float, mediana: float, minimo: float, maximo: float, q1: float, q3: float}|null
     */
    private function resumoNumerico(array $valores): ?array
    {
        if ($valores === []) {
            return null;
        }
        sort($valores);
        $quantidade = count($valores);
        $media = array_sum($valores) / $quantidade;
        $somaQuadrados = 0.0;
        foreach ($valores as $valor) {
            $somaQuadrados += ($valor - $media) ** 2;
        }
        $dp = $quantidade > 1 ? sqrt($somaQuadrados / ($quantidade - 1)) : 0.0;

        return [
            'n' => $quantidade,
            'media' => round($media, 1),
            'dp' => round($dp, 1),
            'mediana' => round($this->quartil($valores, 0.5), 1),
            'minimo' => round($valores[0], 1),
            'maximo' => round($valores[$quantidade - 1], 1),
            'q1' => round($this->quartil($valores, 0.25), 1),
            'q3' => round($this->quartil($valores, 0.75), 1),
        ];
    }

    /**
     * @param  list<float>  $ordenados
     */
    private function quartil(array $ordenados, float $p): float
    {
        $quantidade = count($ordenados);
        if ($quantidade === 1) {
            return $ordenados[0];
        }
        $posicao = ($quantidade - 1) * $p;
        $baixo = (int) floor($posicao);
        $alto = (int) ceil($posicao);
        if ($baixo === $alto) {
            return $ordenados[$baixo];
        }
        $peso = $posicao - $baixo;

        return ($ordenados[$baixo] * (1 - $peso)) + ($ordenados[$alto] * $peso);
    }

    /**
     * @param  list<array{rotulo: string, n: int, pct: float, chd: int, pct_grupo: float}>  $linhas
     */
    private function textoParticao(string $assunto, array $linhas, int $base, bool $feminino = true): string
    {
        if ($linhas === []) {
            return $assunto.' não tem categorias nesta base.';
        }
        $informadas = array_values(array_filter(
            $linhas,
            static fn (array $linha): bool => ! in_array($linha['rotulo'], ['Não informada', 'Não informado'], true),
        ));
        $referencia = $informadas !== [] ? $informadas : $linhas;
        $topo = $referencia[0];
        foreach ($referencia as $linha) {
            if ($linha['n'] > $topo['n']) {
                $topo = $linha;
            }
        }
        $preenchidas = array_sum(array_column($informadas, 'n'));
        $frase = $assunto.' está '.($feminino ? 'preenchida' : 'preenchido').' em '.$this->fichas($preenchidas)
            .' ('.$this->fmtPct($this->pct($preenchidas, $base)).'%). '
            .'A categoria mais frequente é '.$topo['rotulo'].', com '.$this->fichas($topo['n'])
            .' ('.$this->fmtPct($topo['pct']).'%).';
        if ($topo['n'] > 0 && $topo['chd'] > 0) {
            $frase .= ' Nesse grupo, a cardiopatia congênita aparece em '.$this->fichas($topo['chd'])
                .' ('.$this->fmtPct($topo['pct_grupo']).'% da categoria).';
        }

        return $frase;
    }

    /**
     * @param  array{n: int, media: float, dp: float, mediana: float, minimo: float, maximo: float, q1: float, q3: float}|null  $idade
     */
    private function textoIdade(?array $idade, int $base): string
    {
        if ($idade === null) {
            return 'Os meses de vida não estão preenchidos nesta base.';
        }
        $ausentes = max(0, $base - $idade['n']);
        $frase = 'Os meses de vida estão preenchidos em '.$this->fichas($idade['n'])
            .' ('.$this->fmtPct($this->pct($idade['n'], $base)).'%)';
        if ($ausentes > 0) {
            $frase .= ', e falta em '.$this->fichas($ausentes);
        }
        $frase .= '. A média é '.$this->fmtNumero($idade['media']).' meses'
            .' (desvio-padrão '.$this->fmtNumero($idade['dp'])
            .'; mediana '.$this->fmtNumero($idade['mediana'])
            .'; intervalo interquartil de '.$this->fmtNumero($idade['q1']).' a '.$this->fmtNumero($idade['q3'])
            .'; mínimo '.$this->fmtNumero($idade['minimo'])
            .' e máximo '.$this->fmtNumero($idade['maximo']).').';

        return $frase;
    }

    /**
     * @param  list<array{rotulo: string, n: int, pct: float, chd: int, pct_grupo: float}>  $linhas
     */
    private function textoMarcadores(string $assunto, array $linhas): string
    {
        $presentes = array_values(array_filter($linhas, static fn (array $linha): bool => $linha['n'] > 0));
        $ausentes = array_values(array_filter($linhas, static fn (array $linha): bool => $linha['n'] === 0));
        if ($presentes === []) {
            return $assunto.', nenhuma variável teve registro positivo nesta base.';
        }
        $destaques = array_slice($presentes, 0, 3);
        $trechos = [];
        foreach ($destaques as $linha) {
            $trecho = $linha['rotulo'].' em '.$this->fichas($linha['n']).' ('.$this->fmtPct($linha['pct']).'%)';
            if ($linha['chd'] > 0) {
                $trecho .= ', com cardiopatia em '.$this->fichas($linha['chd'])
                    .' ('.$this->fmtPct($linha['pct_grupo']).'% desse grupo)';
            }
            $trechos[] = $trecho;
        }
        $frase = $assunto.', as mais frequentes são '.implode('; ', $trechos).'.';
        if ($ausentes !== []) {
            $frase .= ' Sem registro positivo: '.implode(', ', array_column($ausentes, 'rotulo')).'.';
        }

        return $frase;
    }

    /**
     * @param  list<array{rotulo: string, n: int, pct: float, chd: int, pct_grupo: float}>  $linhas
     * @return array{titulo: string, nota: string, linhas: list<array{rotulo: string, n: int, pct: float, chd: int, pct_grupo: float}>}
     */
    private function tabela(string $titulo, string $nota, array $linhas): array
    {
        return [
            'titulo' => $titulo,
            'nota' => $nota,
            'linhas' => $linhas,
        ];
    }

    private function pct(int $parte, int $total): float
    {
        return $total > 0 ? round(100 * $parte / $total, 1) : 0.0;
    }

    private function fmtPct(float $valor): string
    {
        return number_format($valor, 1, ',', '.');
    }

    private function fmtNumero(float $valor): string
    {
        return number_format($valor, 1, ',', '.');
    }

    private function fichas(int $n): string
    {
        return $n === 1 ? '1 ficha' : number_format($n, 0, ',', '.').' fichas';
    }
}
