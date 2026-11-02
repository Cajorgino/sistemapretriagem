<?php

use App\Models\AnaliseHistorico;
use App\Models\Consulta;
use App\Models\InterpretacaoClinica;
use App\Services\DatasetGestanteImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('laudos antigos passam a descrever a ficha sem citar idade materna ausente', function () {
    config([
        'services.python_api.url' => 'http://ml.test',
        'services.interpretacao.enabled' => true,
    ]);

    Http::fake([
        'http://ml.test/health' => Http::response(['status' => 'ok']),
        'http://ml.test/predict' => Http::response([
            'risk' => 'Baixo',
            'probability' => 0.095,
            'classificacao_risco' => 'Baixo',
            'probabilidade_risco' => 0.095,
            'class' => 0,
            'shap' => [
                ['feature' => 'etnia', 'nome' => 'etnia', 'impact' => 0.02, 'impacto_absoluto' => 0.02],
                ['feature' => 'paridade', 'nome' => 'paridade', 'impact' => 0.01, 'impacto_absoluto' => 0.01],
            ],
            'fonte' => 'planilha',
            'recomenda_ecocardiograma' => true,
            'prioridade_ecocardiograma' => 'alta',
            'score_prioridade' => 80,
            'execution_time_ms' => 1.2,
        ]),
        'http://ml.test/interpretar/gestante' => Http::response([
            'mock' => true,
            'model_id' => 'mock-fase2',
            'prompt_version' => 'system_v2',
            'resumo_medico' => 'Risco de triagem baixo (9,5%). Etnia registrada: Parda. Paridade: 1 filho.',
            'resumo_gestante' => 'Nesta consulta, o nível de atenção do pré-natal está baixo.',
            'fatores_explicados' => [
                ['feature' => 'Etnia', 'impacto' => 0.02, 'explicacao' => 'É o que mais pesa para maior atenção nesta triagem.'],
            ],
            'recomendacoes' => ['Ler este resumo junto com o exame e os dados desta consulta.'],
            'deltas' => [],
            'tokens_in' => 0,
            'tokens_out' => 0,
            'latency_ms' => 1,
        ]),
    ]);

    app(DatasetGestanteImporter::class)->upsert([
        'id_gestante' => 'FICHA-LAUDO',
        'etnia' => 'Parda',
        'paridade' => 1,
        'idade_materna' => 41,
        'idade_gestacional' => 28,
        'imc_pre_gestacional' => 33,
    ]);

    $consulta = Consulta::query()->first();
    $analise = AnaliseHistorico::query()->create([
        'gestante_id' => $consulta->gestante_id,
        'consulta_id' => $consulta->id,
        'evento_tipo' => AnaliseHistorico::EVENTO_CONSULTA,
        'probabilidade_ccf' => 0.374,
        'classificacao_risco' => 'baixo',
        'score_prioridade' => 40,
        'prioridade_ecocardiograma' => 'moderada',
        'recomenda_ecocardiograma' => true,
        'shap' => [
            ['feature' => 'idade_materna', 'nome' => 'idade_materna', 'impact' => 0.023],
        ],
        'full_response' => [],
        'status' => 'concluida',
    ]);

    InterpretacaoClinica::query()->create([
        'gestante_id' => $consulta->gestante_id,
        'consulta_id' => $consulta->id,
        'analise_historico_id' => $analise->id,
        'status' => InterpretacaoClinica::STATUS_CONCLUIDA,
        'prompt_version' => InterpretacaoClinica::PROMPT_VERSION,
        'model_id' => 'mock-fase2',
        'mock' => true,
        'resumo_medico' => 'Risco de triagem baixo (37,4%). A idade materna não está registrada nesta consulta. A idade gestacional não está registrada nesta consulta.',
        'fatores_explicados' => [
            ['feature' => 'Idade materna', 'impacto' => 0.023, 'explicacao' => 'Também pesa para maior atenção, em menor grau.'],
        ],
        'recomendacoes' => ['Considerar a priorização do ecocardiograma fetal.'],
    ]);

    $this->artisan('laudos:atualizar')->assertSuccessful();

    $analise->refresh();
    $laudo = InterpretacaoClinica::query()->first();

    expect($analise->recomenda_ecocardiograma)->toBeFalse()
        ->and($analise->prioridade_ecocardiograma)->toBeNull()
        ->and($analise->score_prioridade)->toBeNull()
        ->and(json_encode($analise->shap))->not->toContain('idade_materna')
        ->and($laudo->resumo_medico)->toBe('Risco de triagem baixo (9,5%). Etnia registrada: Parda. Paridade: 1 filho.')
        ->and($laudo->resumo_medico)->not->toContain('não está registrada')
        ->and(json_encode($laudo->fatores_explicados))->not->toContain('Idade materna')
        ->and($laudo->recomendacoes)->not->toContain('Considerar a priorização do ecocardiograma fetal.');
});
