<?php

use App\Jobs\ExecutarAnaliseGestante;
use App\Models\AnaliseHistorico;
use App\Models\Consulta;
use App\Services\DatasetGestanteImporter;
use App\Services\PythonApiService;
use App\Services\PythonPredictPayloadMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('consulta envia so a planilha e nao entra na fila do ecocardiograma', function () {
    Queue::fake();
    config(['services.python_api.url' => 'http://ml.test', 'services.interpretacao.enabled' => false]);

    Http::fake([
        'http://ml.test/predict' => Http::response([
            'risk' => 'Alto',
            'probability' => 0.81,
            'classificacao_risco' => 'Alto',
            'probabilidade_risco' => 0.81,
            'class' => 1,
            'shap' => [['feature' => 'etnia', 'impact' => 0.2]],
            'execution_time_ms' => 1.2,
        ]),
    ]);

    app(DatasetGestanteImporter::class)->upsert([
        'id_gestante' => 'FICHA-1',
        'idade_materna' => 41,
        'etnia' => 'Parda',
        'imc_pre_gestacional' => 33,
        'idade_gestacional' => 28,
        'paridade' => 1,
        'abortamentos_previos' => 2,
        'diabetes_gestacional' => 1,
    ]);

    $consulta = Consulta::query()->first();
    $consulta->update([
        'hipotireoidismo' => true,
        'doencas_tireoidianas' => false,
        'imc_gestacional_categoria' => 1,
    ]);

    $job = new ExecutarAnaliseGestante((int) $consulta->gestante_id, (int) $consulta->id);
    $job->handle(app(PythonApiService::class), app(PythonPredictPayloadMapper::class));

    Http::assertSent(function ($request) {
        if (! str_ends_with($request->url(), '/predict')) {
            return false;
        }
        $dados = $request->data();

        return ! array_key_exists('idade_materna', $dados)
            && ! array_key_exists('idade', $dados)
            && ! array_key_exists('idade_gestacional', $dados)
            && ! array_key_exists('imc_pre_gestacional', $dados)
            && ! array_key_exists('imc', $dados)
            && ($dados['etnia'] ?? null) === 'Parda'
            && ($dados['paridade'] ?? null) === 1
            && ($dados['abortamentos_previos'] ?? null) === 2
            && ($dados['imc_gestacional_categoria'] ?? null) === '1'
            && ($dados['doenca_tireoide'] ?? null) === 1
            && ($dados['diabetes_gestacional'] ?? null) === 1;
    });

    $analise = AnaliseHistorico::query()->first();
    expect($analise)->not->toBeNull()
        ->and($analise->recomenda_ecocardiograma)->toBeFalse()
        ->and($analise->prioridade_ecocardiograma)->toBeNull()
        ->and($analise->score_prioridade)->toBeNull()
        ->and($analise->classificacao_risco)->toBe('alto');
});
