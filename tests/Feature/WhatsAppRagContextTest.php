<?php

use App\Models\AnaliseHistorico;
use App\Models\Consulta;
use App\Models\Gestante;
use App\Models\GestanteWhatsapp;
use App\Models\InterpretacaoClinica;
use App\Services\WhatsAppRagContextService;
use App\Support\WhatsAppFluxoEstado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
});

function gestanteRag(array $attrs = []): Gestante
{
    return Gestante::query()->create(array_merge([
        'nome' => 'Caroline Silva',
        'telefone' => '551199887766',
        'whatsapp_fluxo_estado' => WhatsAppFluxoEstado::MENU_INICIAL,
        'atendimento_personalizado_aceito_em' => now(),
    ], $attrs));
}

function consultaRag(Gestante $gestante, array $attrs = []): Consulta
{
    return Consulta::query()->create(array_merge([
        'gestante_id' => $gestante->id,
        'consulta_numero' => 1,
        'data_consulta' => '2026-08-20',
        'idade_gestacional' => 22,
        'pressao_sistolica' => 118,
        'bpm_materno' => 82,
        'saturacao' => 98,
        'glicemia_jejum' => 95,
        'temperatura_corporal' => 36.5,
        'altura' => 160,
        'peso' => 68,
        'diabetes_gestacional' => false,
        'hipertensao' => false,
        'hipertensao_pre_eclampsia' => false,
        'obesidade_pre_gestacional' => false,
        'historico_familiar_chd' => false,
        'uso_medicamentos' => false,
        'tabagismo' => false,
        'alcoolismo' => false,
        'frequencia_cardiaca_fetal' => 140,
        'circunferencia_cefalica_fetal_mm' => 180,
        'circunferencia_abdominal_mm' => 160,
        'comprimento_femur_mm' => 40,
        'translucencia_nucal_mm' => 1.5,
        'doppler_ducto_venoso' => 'normal',
        'eixo_cardiaco' => 'normal',
        'quatro_camaras' => 'normal',
        'chd_confirmada' => false,
    ], $attrs));
}

test('rag em conversa livre devolve patient_snapshot e historico_conversa', function () {
    $g = gestanteRag();
    consultaRag($g);

    $analise = AnaliseHistorico::query()->create([
        'gestante_id' => $g->id,
        'evento_tipo' => AnaliseHistorico::EVENTO_CONSULTA,
        'probabilidade_ccf' => 0.42,
        'classificacao_risco' => 'moderado',
        'prioridade_ecocardiograma' => 'moderada',
        'recomenda_ecocardiograma' => true,
        'full_response' => [],
        'status' => 'concluida',
    ]);

    InterpretacaoClinica::query()->create([
        'gestante_id' => $g->id,
        'analise_historico_id' => $analise->id,
        'status' => InterpretacaoClinica::STATUS_CONCLUIDA,
        'prompt_version' => InterpretacaoClinica::PROMPT_VERSION,
        'model_id' => 'mock',
        'mock' => true,
        'resumo_gestante' => 'Seu acompanhamento está em andamento e a equipe pode orientar o próximo passo.',
        'resumo_medico' => 'Risco moderado.',
    ]);

    GestanteWhatsapp::query()->create([
        'gestante_id' => $g->id,
        'mensagem' => 'Oi, tudo bem?',
        'tipo' => 'entrada',
        'categoria' => GestanteWhatsapp::CATEGORIA_CONVERSA,
        'canal' => 'whatsapp',
    ]);

    $payload = app(WhatsAppRagContextService::class)->buildForTelefone(
        '551199887766',
        'Oi, preciso de uma informação do meu pré-natal.',
    );

    expect($payload['found'])->toBeTrue()
        ->and($payload['intencao'])->toBe('conversa_livre')
        ->and($payload['patient_snapshot'])->toBeArray()
        ->and($payload['patient_snapshot']['primeiro_nome'])->toBe('Caroline')
        ->and((string) $payload['patient_snapshot']['idade_gestacional'])->toBe('22')
        ->and($payload['patient_snapshot']['pressao_sistolica'])->toBe(118)
        ->and($payload['patient_snapshot']['glicemia_jejum'])->toBe(95)
        ->and($payload['patient_snapshot']['analise_resumo'])->toContain('risco moderado')
        ->and($payload['patient_snapshot']['interpretacao_resumo'])->toContain('acompanhamento')
        ->and($payload['historico_conversa'])->not->toBeEmpty()
        ->and($payload['historico_conversa'][0]['role'])->toBe('user')
        ->and($payload['contexto_texto'])->toContain('PatientSnapshot')
        ->and($payload['contexto_texto'])->toContain('Oi, tudo bem?');
});

test('rag em sintoma usa intencao sintoma e inclui snapshot clinico', function () {
    $g = gestanteRag(['telefone' => '5511987654321']);
    consultaRag($g, ['pressao_sistolica' => 125]);

    $payload = app(WhatsAppRagContextService::class)->buildForTelefone(
        '5511987654321',
        'estou com dor na barriga',
    );

    expect($payload['found'])->toBeTrue()
        ->and($payload['intencao'])->toBe('sintoma')
        ->and($payload['pular_ia'])->toBeFalse()
        ->and($payload['patient_snapshot']['pressao_sistolica'])->toBe(125)
        ->and($payload['contexto_texto'])->toContain('PatientSnapshot')
        ->and($payload['contexto_texto'])->toContain('125');
});

test('rag expoe flags clinicos e medidas da consulta no patient_snapshot', function () {
    $g = gestanteRag(['telefone' => '5511912345678', 'nome' => 'Adriana Lima']);
    consultaRag($g, [
        'hipertensao' => true,
        'hipertensao_cronica' => true,
        'diabetes_gestacional' => false,
        'lupus_eritomatoso_sistemico' => true,
        'tabagismo' => false,
        'hba1c' => 5.6,
        'glicemia_pos_prandial' => 120,
    ]);

    $payload = app(WhatsAppRagContextService::class)->buildForTelefone(
        '5511912345678',
        'consulte se sou hipertensa',
    );

    expect($payload['found'])->toBeTrue()
        ->and($payload['patient_snapshot']['hipertensao'])->toBeTrue()
        ->and($payload['patient_snapshot']['hipertensao_cronica'])->toBeTrue()
        ->and($payload['patient_snapshot']['diabetes_gestacional'])->toBeFalse()
        ->and($payload['patient_snapshot']['lupus_eritomatoso_sistemico'])->toBeTrue()
        ->and($payload['patient_snapshot']['tabagismo'])->toBeFalse()
        ->and((float) $payload['patient_snapshot']['hba1c'])->toBe(5.6)
        ->and($payload['contexto_texto'])->toContain('Hipertensão: sim')
        ->and($payload['contexto_texto'])->toContain('Diabetes gestacional: não')
        ->and($payload['contexto_texto'])->toContain('Lúpus: sim')
        ->and($payload['contexto_texto'])->toContain('HbA1c:');
});

test('mensagem da equipe na ficha aparece no historico_conversa como staff', function () {
    $g = gestanteRag(['telefone' => '5511911122233']);
    consultaRag($g);

    GestanteWhatsapp::query()->create([
        'gestante_id' => $g->id,
        'mensagem' => 'Gestante enviou relato de dor',
        'tipo' => 'entrada',
        'categoria' => GestanteWhatsapp::CATEGORIA_CONVERSA,
        'canal' => 'whatsapp',
    ]);

    GestanteWhatsapp::query()->create([
        'gestante_id' => $g->id,
        'mensagem' => 'Olá, aqui é a equipe. Já registramos seu caso.',
        'tipo' => 'saida',
        'categoria' => GestanteWhatsapp::CATEGORIA_CONVERSA,
        'canal' => 'whatsapp',
    ]);

    $payload = app(WhatsAppRagContextService::class)->buildForTelefone(
        '5511911122233',
        'obrigada',
    );

    $roles = collect($payload['historico_conversa'])->pluck('role')->all();
    $texts = collect($payload['historico_conversa'])->pluck('text')->all();

    expect($payload['found'])->toBeTrue()
        ->and($roles)->toContain('user')
        ->and($roles)->toContain('staff')
        ->and($texts)->toContain('Olá, aqui é a equipe. Já registramos seu caso.')
        ->and($payload['contexto_texto'])->toContain('Equipe:')
        ->and($payload['contexto_texto'])->toContain('Já registramos seu caso');
});

test('endpoint rag http devolve patient_snapshot e historico_conversa', function () {
    config(['services.waha.webhook_secret' => '']);

    $g = gestanteRag(['telefone' => '551190001122']);
    consultaRag($g);

    GestanteWhatsapp::query()->create([
        'gestante_id' => $g->id,
        'mensagem' => 'Bom dia',
        'tipo' => 'entrada',
        'categoria' => GestanteWhatsapp::CATEGORIA_CONVERSA,
        'canal' => 'whatsapp',
    ]);

    $response = $this->postJson('/api/v1/n8n/whatsapp/rag/551190001122', [
        'mensagem' => 'como está minha pressão?',
        'top_k' => 6,
    ]);

    $response->assertOk()
        ->assertJsonPath('found', true)
        ->assertJsonPath('patient_snapshot.primeiro_nome', 'Caroline')
        ->assertJsonPath('patient_snapshot.pressao_sistolica', 118)
        ->assertJsonStructure([
            'contexto_texto',
            'patient_snapshot',
            'historico_conversa' => [
                ['role', 'text', 'at'],
            ],
            'intencao',
        ]);
});
