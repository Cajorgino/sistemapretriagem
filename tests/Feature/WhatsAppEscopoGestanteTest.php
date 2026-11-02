<?php

use App\Models\Gestante;
use App\Models\GestanteWhatsapp;
use App\Services\GestanteWhatsappMessageRecorder;
use App\Services\WhatsAppMessageTemplates;
use App\Services\WhatsAppRagContextService;
use App\Support\WhatsAppFluxoEstado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
});

test('detecta declaracao de fim de gestacao', function () {
    $t = app(WhatsAppMessageTemplates::class);

    expect($t->textoFimGestacao('Não estou mais grávida'))->toBeTrue()
        ->and($t->textoFimGestacao('nao to gravida'))->toBeTrue()
        ->and($t->textoFimGestacao('Estou com dor na barriga'))->toBeFalse()
        ->and($t->matchIntencaoLivre('Não estou mais grávida'))->toBe('fim_gestacao');
});

test('recorder devolve resposta_pronta e fora_escopo quando nao esta mais gravida', function () {
    $g = Gestante::query()->create([
        'nome' => 'Eliza Santos',
        'telefone' => '5511988776655',
        'whatsapp_fluxo_estado' => WhatsAppFluxoEstado::MENU_INICIAL,
        'atendimento_personalizado_aceito_em' => now(),
    ]);

    $recorder = app(GestanteWhatsappMessageRecorder::class);
    $recorder->record('5511988776655', 'Não estou mais grávida', 'entrada');
    $fluxo = $recorder->ultimoFluxo();

    expect($fluxo['pular_ia'])->toBeTrue()
        ->and($fluxo['respondido'])->toBeTrue()
        ->and($fluxo['intencao'])->toBe('fora_escopo')
        ->and($fluxo['resposta'])->toContain('exclusivo para gestantes')
        ->and($fluxo['resposta'])->toContain('Eliza');
});

test('rag marca fora_escopo e gestacao_ativa no patient_snapshot', function () {
    $g = Gestante::query()->create([
        'nome' => 'Lara Costa',
        'telefone' => '5511977665544',
        'whatsapp_fluxo_estado' => WhatsAppFluxoEstado::MENU_INICIAL,
        'atendimento_personalizado_aceito_em' => now(),
    ]);

    // Sem consulta: gestacao_ativa tende a false, mas fora_escopo vem da mensagem.
    $payload = app(WhatsAppRagContextService::class)->buildForTelefone(
        '5511977665544',
        'Não estou mais grávida',
    );

    expect($payload['found'])->toBeTrue()
        ->and($payload['fora_escopo'])->toBeTrue()
        ->and($payload['intencao'])->toBe('fora_escopo')
        ->and($payload['escopo'])->toBe('gestante_em_acompanhamento')
        ->and($payload['contexto_texto'])->toContain('FORA DE ESCOPO')
        ->and($payload['patient_snapshot']['escopo'])->toBe('gestante_em_acompanhamento');
});

test('endpoint de mensagem retorna resposta_pronta para fim de gestacao', function () {
    config(['services.waha.webhook_secret' => '']);

    Gestante::query()->create([
        'nome' => 'Eliza Santos',
        'telefone' => '5511966554433',
        'whatsapp_fluxo_estado' => WhatsAppFluxoEstado::MENU_INICIAL,
        'atendimento_personalizado_aceito_em' => now(),
    ]);

    $response = $this->postJson('/api/v1/n8n/whatsapp/mensagens/5511966554433', [
        'mensagem' => 'não estou mais grávida',
        'tipo' => 'entrada',
    ]);

    $response->assertCreated()
        ->assertJsonPath('pular_ia', true)
        ->assertJsonPath('fluxo_respondido', true)
        ->assertJsonPath('intencao', 'fora_escopo');

    expect($response->json('resposta_pronta'))->toContain('exclusivo para gestantes');
});
