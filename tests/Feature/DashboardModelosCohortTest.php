<?php

use App\Models\Consulta;
use App\Models\User;
use App\Services\DatasetGestanteImporter;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

function catalogoExperimentosFake(): array
{
    return [
        'disponivel' => true,
        'experimento' => 'experimento_3_completo',
        'fonte' => 'data/metricas_modelos.csv',
        'criterio_selecao' => 'melhor_f1_cv',
        'total_modelos' => 12,
        'destaques' => [
            'melhor_f1_cv' => [
                'modelo' => 'CatBoost',
                'nome_exibicao' => 'CatBoost',
                'valor' => 0.3595,
                'balanceamento' => 'class_weight',
            ],
        ],
        'modelos' => [
            [
                'modelo' => 'Logistic Regression',
                'nome_exibicao' => 'Regressão Logística',
                'balanceamento' => 'random_oversampling',
                'status' => null,
                'treino_cv' => ['f1' => 0.3368, 'acuracia' => 0.798],
                'teste' => ['f1' => 0.3395, 'roc_auc' => 0.8411, 'sensibilidade' => 0.7432, 'especificidade' => 0.7894, 'acuracia' => 0.786],
            ],
            [
                'modelo' => 'CatBoost',
                'nome_exibicao' => 'CatBoost',
                'balanceamento' => 'class_weight',
                'status' => 'publicado',
                'treino_cv' => ['f1' => 0.3595, 'acuracia' => 0.8412],
                'teste' => ['f1' => 0.3488, 'roc_auc' => 0.8041, 'sensibilidade' => 0.6081, 'especificidade' => 0.8499, 'acuracia' => 0.832],
            ],
            [
                'modelo' => 'SVM',
                'nome_exibicao' => 'SVM',
                'balanceamento' => 'random_oversampling',
                'status' => null,
                'treino_cv' => ['f1' => 0.3379],
                'teste' => ['f1' => 0.3563, 'roc_auc' => 0.8522, 'sensibilidade' => 0.7703, 'especificidade' => 0.7959, 'acuracia' => 0.794],
            ],
        ],
        'legendas' => [
            'treino_cv' => 'Média Stratified 10-Fold Cross-Validation',
        ],
        'producao' => [
            'modelo' => 'CatBoost',
            'nome_exibicao' => 'CatBoost',
            'balanceamento' => 'class_weight',
            'carregado' => false,
            'modo_inferencia' => 'heuristica',
            'mensagem' => 'Modelo publicado sem artefato .pkl; a inferência usa regras clínicas até o arquivo ser carregado. O catálogo de experimentos permanece intacto.',
        ],
        'mensagem' => null,
    ];
}

beforeEach(function () {
    $this->withoutMiddleware(ValidateCsrfToken::class);
    config(['services.python_api.url' => 'http://ml.test', 'services.ccf_api.url' => 'http://ml.test']);
    $this->comCatalogoMl = false;

    Http::fake(function (\Illuminate\Http\Client\Request $request) {
        $path = (string) parse_url($request->url(), PHP_URL_PATH);

        if ($path === '/health') {
            return Http::response(['status' => 'ok']);
        }

        if ($path === '/model/info') {
            return Http::response([
                'name' => 'CardioPrenatal-ML',
                'version' => '1.0.0',
                'loaded' => false,
                'model_path' => 'models/model.pkl',
                'features' => ['idade', 'diabetes', 'hipertensao', 'imc'],
                'framework' => 'heuristic',
            ]);
        }

        if ($path === '/metrics') {
            return Http::response([
                'model_loaded' => false,
                'total_predictions' => 3,
                'avg_execution_time_ms' => 1.2,
                'last_prediction_at' => null,
            ]);
        }

        if ($path === '/modelos' || $path === '/modelos/metricas') {
            if ($this->comCatalogoMl) {
                return Http::response(catalogoExperimentosFake());
            }

            return Http::response(['detail' => 'Not Found'], 404);
        }

        return Http::response(['detail' => 'Not Found'], 404);
    });
});

function medicoDashboard(): User
{
    return User::query()->create([
        'name' => 'Dra. Dashboard',
        'crm' => 'CRM-DASH-'.uniqid(),
        'password' => 'password',
    ]);
}

test('dashboard lista o catalogo de experimentos com metricas reais', function () {
    $this->comCatalogoMl = true;

    $this->actingAs(medicoDashboard())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Catálogo de experimentos')
        ->assertSee('experimento_3_completo')
        ->assertSee('CatBoost')
        ->assertSee('Regressão Logística')
        ->assertSee('SVM')
        ->assertSee('Soma as variáveis clínicas com um peso para cada uma.')
        ->assertSee('Procura a fronteira que separa as fichas com e sem cardiopatia registrada.')
        ->assertSee('class_weight')
        ->assertSee(number_format(0.3595 * 100, 1, ',', '.').'%')
        ->assertSee('Modelo em produção')
        ->assertDontSee('Esta API publica o modelo em produção, não o catálogo de experimentos de treino.')
        ->assertDontSee('Endpoint /modelos indisponível nesta API.');
});

test('dashboard nao substitui o catalogo pela heuristica quando GET /modelos nao existe', function () {
    $this->actingAs(medicoDashboard())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Catálogo de experimentos indisponível nesta API.')
        ->assertSee('Modelo em produção')
        ->assertSee('Heurística')
        ->assertDontSee('experimento_3_completo')
        ->assertDontSee('Regressão Logística')
        ->assertDontSee('Esta API publica o modelo em produção, não o catálogo de experimentos de treino.');
});

test('dashboard desenha graficos do cohort a partir do cadastro local', function () {
    Queue::fake();
    app(DatasetGestanteImporter::class)->upsert([
        'id_gestante' => 'GEST-200',
        'idade_materna' => 34,
        'etnia' => 'Branca',
        'imc_pre_gestacional' => 31,
        'idade_gestacional' => 22,
        'diabetes_gestacional' => 1,
        'cardiopatia_congenita' => 1,
    ]);

    $this->actingAs(medicoDashboard())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Cadastro local')
        ->assertSee('Gestantes com desfecho de cardiopatia congênita')
        ->assertSee('chart-cohort-desfecho_gestantes')
        ->assertSee('Perfil das gestantes com cardiopatia congênita')
        ->assertSee('chart-cohort-desfecho_fatores')
        ->assertSee('Prevalência de CCF nas fichas')
        ->assertSee('chart-cohort-prevalencia')
        ->assertDontSee('Gráficos descritivos indisponíveis.');
});

test('dashboard destaca gestantes com desfecho de cardiopatia congênita', function () {
    Queue::fake();
    $importer = app(DatasetGestanteImporter::class);
    $importer->upsert([
        'id_gestante' => 'GEST-CCF-1',
        'idade_materna' => 33,
        'etnia' => 'Parda',
        'imc_pre_gestacional' => 28,
        'idade_gestacional' => 24,
        'diabetes_gestacional' => 1,
        'cardiopatia_congenita' => 1,
    ]);
    $importer->upsert([
        'id_gestante' => 'GEST-CCF-2',
        'idade_materna' => 29,
        'etnia' => 'Branca',
        'imc_pre_gestacional' => 22,
        'idade_gestacional' => 20,
        'cardiopatia_congenita' => 0,
    ]);

    $this->actingAs(medicoDashboard())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeInOrder([
            'Desfecho CCF',
            'Desfecho de cardiopatia congênita',
            'Visão geral',
            'Panorama da base',
        ])
        ->assertSee('Gestantes com CCF')
        ->assertSee('1 com desfecho')
        ->assertSee('Cada gestante entra uma vez.')
        ->assertSee('Só entram gestantes com cardiopatia confirmada.');
});

test('dashboard descreve todas as variaveis da ficha com contagem e percentual', function () {
    Queue::fake();
    $importer = app(DatasetGestanteImporter::class);
    $importer->upsert([
        'id_gestante' => 'GEST-D1',
        'idade_materna' => 30,
        'etnia' => 'Parda',
        'paridade' => 0,
        'imc_pre_gestacional' => 22,
        'idade_gestacional' => 20,
        'cardiopatia_congenita' => 1,
        'diabetes_gestacional' => 1,
    ]);
    $importer->upsert([
        'id_gestante' => 'GEST-D2',
        'idade_materna' => 28,
        'etnia' => 'Branca',
        'paridade' => 1,
        'imc_pre_gestacional' => 22,
        'idade_gestacional' => 20,
        'cardiopatia_congenita' => 0,
    ]);

    Consulta::query()->whereHas('gestante', fn ($q) => $q->where('gestante_id', 'GEST-D1'))->update([
        'idade_crianca_meses' => 8,
        'imc_gestacional_categoria' => 0,
        'prematuridade' => true,
    ]);
    Consulta::query()->whereHas('gestante', fn ($q) => $q->where('gestante_id', 'GEST-D2'))->update([
        'idade_crianca_meses' => 14,
        'imc_gestacional_categoria' => 1,
    ]);

    $this->actingAs(medicoDashboard())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Leitura descritiva')
        ->assertSee('2 fichas')
        ->assertSee('50,0%')
        ->assertSee('Parda')
        ->assertSee('Diabetes gestacional')
        ->assertSee('Prematuridade')
        ->assertSee('média é 11,0 meses')
        ->assertSee('Sem registro positivo')
        ->assertSee('Rubéola')
        ->assertSee('Fenilcetonúria');
});
