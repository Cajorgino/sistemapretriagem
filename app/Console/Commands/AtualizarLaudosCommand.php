<?php

namespace App\Console\Commands;

use App\Models\AnaliseHistorico;
use App\Models\Consulta;
use App\Models\Gestante;
use App\Models\InterpretacaoClinica;
use App\Services\ClinicalContextBuilder;
use App\Services\PythonApiService;
use App\Services\PythonPredictPayloadMapper;
use Illuminate\Console\Command;
use RuntimeException;
use Throwable;

class AtualizarLaudosCommand extends Command
{
    protected $signature = 'laudos:atualizar
                            {--limit=0 : Limitar fichas (0 = todas)}';

    protected $description = 'Reescreve análises e laudos já gravados com a regra da planilha';

    public function handle(
        PythonApiService $api,
        PythonPredictPayloadMapper $mapper,
        ClinicalContextBuilder $builder,
    ): int {
        try {
            $api->health();
        } catch (Throwable $e) {
            $this->error('API Python indisponível: '.$e->getMessage());

            return self::FAILURE;
        }

        $limit = (int) $this->option('limit');
        $ids = InterpretacaoClinica::query()->orderBy('id');
        if ($limit > 0) {
            $ids->limit($limit);
        }
        $ids = $ids->pluck('id');
        $total = $ids->count();

        if ($total === 0) {
            $this->info('Nenhum laudo para atualizar.');

            return self::SUCCESS;
        }

        $this->info("Reescrevendo {$total} laudo(s) já gravado(s)…");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $ok = 0;
        $fail = 0;

        foreach ($ids->chunk(40) as $lote) {
            $laudos = InterpretacaoClinica::query()
                ->with(['analiseHistorico', 'consulta', 'gestante'])
                ->whereIn('id', $lote)
                ->orderBy('id')
                ->get();

            foreach ($laudos as $laudo) {
                try {
                    $this->atualizar($laudo, $api, $mapper, $builder);
                    $ok++;
                } catch (Throwable $e) {
                    $fail++;
                    if ($fail <= 5) {
                        $this->newLine();
                        $this->error("Falha laudo #{$laudo->id}: ".$e->getMessage());
                    }
                }
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Concluído. ok={$ok} falhas={$fail}");

        return $fail > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function atualizar(
        InterpretacaoClinica $laudo,
        PythonApiService $api,
        PythonPredictPayloadMapper $mapper,
        ClinicalContextBuilder $builder,
    ): void {
        $analise = $laudo->analiseHistorico;
        $consulta = $laudo->consulta;
        $gestante = $laudo->gestante;

        if (! $analise instanceof AnaliseHistorico || ! $consulta instanceof Consulta || ! $gestante instanceof Gestante) {
            throw new RuntimeException('Laudo sem análise, consulta ou gestante.');
        }
        if ($analise->status !== 'concluida') {
            throw new RuntimeException('Análise não está concluída.');
        }

        $predicao = $api->predict($mapper->fromConsulta($consulta, $gestante));
        $riscoRaw = (string) ($predicao['classificacao_risco'] ?? 'Baixo');
        $riscoSlug = match (mb_strtolower($riscoRaw)) {
            'alto' => 'alto',
            'moderado' => 'moderado',
            default => 'baixo',
        };
        $prob = (float) ($predicao['probabilidade_risco'] ?? 0);
        $predicao['recomenda_ecocardiograma'] = false;
        $predicao['prioridade_ecocardiograma'] = null;
        $predicao['score_prioridade'] = null;
        $fonte = is_string($predicao['fonte'] ?? null) ? $predicao['fonte'] : null;

        $analise->update([
            'probabilidade_ccf' => $prob,
            'classificacao_risco' => $riscoSlug,
            'score_prioridade' => null,
            'prioridade_ecocardiograma' => null,
            'recomenda_ecocardiograma' => false,
            'shap' => is_array($predicao['shap'] ?? null) ? $predicao['shap'] : [],
            'predicao' => $predicao,
            'analise' => [
                'fonte' => $fonte,
                'modelo' => $predicao['modelo'] ?? null,
                'experimento' => $predicao['experimento'] ?? null,
                'modo_inferencia' => $predicao['modo_inferencia'] ?? $fonte,
                'fallback_motivo' => $predicao['fallback_motivo'] ?? null,
                'define_fila_ecocardiograma' => false,
            ],
            'full_response' => $predicao,
            'status' => 'concluida',
            'erro_mensagem' => null,
        ]);
        $analise->refresh();

        $built = $builder->build($gestante, $consulta, $analise);
        $resposta = $api->interpretarGestante($built['contexto']);

        $laudo->update([
            'status' => ! empty($resposta['cache_hit'])
                ? InterpretacaoClinica::STATUS_CACHE_HIT
                : InterpretacaoClinica::STATUS_CONCLUIDA,
            'model_id' => $resposta['model_id'] ?? 'mock-fase2',
            'prompt_version' => $resposta['prompt_version'] ?? InterpretacaoClinica::PROMPT_VERSION,
            'schema_version' => ClinicalContextBuilder::SCHEMA_VERSION,
            'contexto_hash' => $built['hash'],
            'contexto_enviado' => $built['contexto'],
            'resumo_medico' => $resposta['resumo_medico'] ?? null,
            'resumo_gestante' => $resposta['resumo_gestante'] ?? null,
            'fatores_explicados' => $resposta['fatores_explicados'] ?? [],
            'recomendacoes' => $resposta['recomendacoes'] ?? [],
            'deltas' => $resposta['deltas'] ?? [],
            'resposta_bruta' => $resposta,
            'tokens_in' => $resposta['tokens_in'] ?? 0,
            'tokens_out' => $resposta['tokens_out'] ?? 0,
            'latency_ms' => isset($resposta['latency_ms']) ? (int) round((float) $resposta['latency_ms']) : null,
            'mock' => (bool) ($resposta['mock'] ?? true),
            'erro_mensagem' => null,
        ]);
    }
}
