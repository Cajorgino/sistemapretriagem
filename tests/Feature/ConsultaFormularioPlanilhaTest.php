<?php

use App\Models\Consulta;
use App\Models\Gestante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('consulta nova grava sem os campos antigos obrigatorios do formulario', function () {
    Queue::fake();
    $medico = usuarioComPerfil(User::ROLE_MEDICO, 'CRM-CONSULTA-NOVA');
    $gestante = Gestante::query()->create([
        'nome' => 'Helena Consulta',
        'data_nascimento' => '1992-01-10',
        'cpf' => '39053344705',
        'telefone' => '21911112222',
    ]);

    $this->actingAs($medico)
        ->post(route('consultas.store', $gestante->id), [
            'data_consulta' => '2026-10-28',
            'idade_crianca_meses' => 8,
            'etnia' => 'Parda',
            'paridade' => 0,
            'imc_gestacional_categoria' => 1,
            'abortamentos_previos' => 0,
            'tipo_gestacao' => 'única',
            'polidramnio_oligoidramnio' => '',
            'prematuridade' => 0,
            'historico_familiar_chd' => 0,
            'diabetes_gestacional' => 1,
            'diabetes_pre_gestacional' => 0,
            'lupus_eritomatoso_sistemico' => 0,
            'sindrome_sjogren' => 0,
            'doencas_tireoidianas' => 0,
            'hipertensao_cronica' => 0,
            'hipertensao_pre_eclampsia' => 0,
            'eclampsia' => 0,
            'drogas_ilicitas' => 0,
            'licitas_teratogenicas' => 0,
            'infeccao_materna_bacteriana' => 0,
            'chd_confirmada' => 0,
        ])
        ->assertRedirect(route('gestantes.show', $gestante->id));

    $consulta = Consulta::query()->where('gestante_id', $gestante->id)->first();
    expect($consulta)->not->toBeNull()
        ->and($consulta->etnia)->toBe('Parda')
        ->and($consulta->diabetes_gestacional)->toBeTrue()
        ->and($consulta->hipertensao)->toBeFalse()
        ->and($consulta->obesidade_pre_gestacional)->toBeFalse()
        ->and($consulta->uso_medicamentos)->toBeFalse()
        ->and($consulta->tabagismo)->toBeFalse()
        ->and($consulta->alcoolismo)->toBeFalse();
});
