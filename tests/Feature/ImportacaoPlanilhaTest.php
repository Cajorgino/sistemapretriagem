<?php

use App\Jobs\ExecutarAnaliseGestante;
use App\Models\Consulta;
use App\Models\Gestante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(RefreshDatabase::class);

function medicoImportacao(): User
{
    return User::query()->create([
        'name' => 'Médica Teste',
        'crm' => 'CRM-IMPORT',
        'password' => 'password',
    ]);
}

function csvDataset(array $linhas): UploadedFile
{
    $header = 'id_gestante,idade_materna,imc_pre_gestacional,paridade,idade_gestacional,cardiopatia_congenita,diabetes_gestacional';
    $corpo = $header."\n".implode("\n", $linhas);

    return UploadedFile::fake()->createWithContent('dataset.csv', $corpo);
}

function xlsxDataset(array $linhas): UploadedFile
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray(array_merge([
        ['id_gestante', 'idade_materna', 'imc_pre_gestacional', 'paridade', 'idade_gestacional', 'cardiopatia_congenita'],
    ], $linhas), null, 'A1');

    $path = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    return new UploadedFile($path, 'dataset.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

test('importa csv e cria gestante', function () {
    Queue::fake();

    $this->actingAs(medicoImportacao())
        ->postJson(route('consultas.import.store'), [
            'csv' => csvDataset(['GEST-100,30,28,1,22,0,0']),
        ])
        ->assertOk()
        ->assertJsonPath('criadas', 1)
        ->assertJsonPath('atualizadas', 0);

    expect(Gestante::query()->where('gestante_id', 'GEST-100')->count())->toBe(1)
        ->and(Consulta::count())->toBe(1);

    Queue::assertPushed(ExecutarAnaliseGestante::class);
});

test('reimportar as mesmas gestantes atualiza e nao duplica', function () {
    Queue::fake();
    $this->actingAs(medicoImportacao());

    $this->postJson(route('consultas.import.store'), [
        'csv' => csvDataset(['GEST-100,30,28,1,22,0,0']),
    ])->assertOk();

    $consulta = Consulta::query()->first();
    $consulta->update(['pressao_sistolica' => 130]);

    $this->postJson(route('consultas.import.store'), [
        'csv' => csvDataset(['GEST-100,31,29,1,24,1,1']),
    ])
        ->assertOk()
        ->assertJsonPath('criadas', 0)
        ->assertJsonPath('atualizadas', 1);

    expect(Gestante::count())->toBe(1)
        ->and(Consulta::count())->toBe(1);

    $consulta->refresh();
    expect($consulta->pressao_sistolica)->toBe(130)
        ->and($consulta->idade_materna)->toBe(31)
        ->and($consulta->chd_confirmada)->toBeTrue()
        ->and($consulta->diabetes_gestacional)->toBeTrue();
});

test('importa xlsx no mesmo fluxo', function () {
    Queue::fake();

    $this->actingAs(medicoImportacao())
        ->postJson(route('consultas.import.store'), [
            'csv' => xlsxDataset([['GEST-200', 28, 24.5, 0, 18, 0]]),
        ])
        ->assertOk()
        ->assertJsonPath('criadas', 1);

    expect(Gestante::query()->where('gestante_id', 'GEST-200')->exists())->toBeTrue();
});

test('importa planilha clinica com capa e coluna registro', function () {
    Queue::fake();

    $legenda = "\n\n0 = Não\n1 = Sim";
    $headers = [
        'REGISTRO',
        "IDADE \n(meses)",
        'Prematuridade'.$legenda,
        'Gemelar'.$legenda,
        "Paridade\n\n0 = Primigesta\n1 = 1 filho",
        "Raça/Cor\n0 = Outra\n1 = Branca\n2 = Parda\n3 = Negra",
        "IMG\nGestacional\n\n0 = Normal\n1 = > 24,9",
        'Aborto'.$legenda,
        "CC em 1º Grau\n\n0 = Não/Sem\n1 = Sim/Com",
        "DMG\n\n0 = Não/Sem\n1 = Sim/Com",
        "DM Prévio\n\n0 = Não/Sem\n1 = Sim/Com",
        "Lúpus\nEritematoso\nSistêmico".$legenda,
        "Síndrome\nSjögren\n\n0 = Não/Sem\n1 = Sim/Com",
        'Fenilcetonúria'.$legenda,
        'Rubéola'.$legenda,
        'Hipotireoidismo'.$legenda,
        'Hipertireoidismo'.$legenda,
        'HAS'.$legenda,
        "Pré\nEclâmpsia".$legenda,
        'Eclâmpsia'.$legenda,
        'Drogas Ilícitas'.$legenda,
        "Lícitas \nTeratogênicas".$legenda,
        'Polidrâmnio'.$legenda,
        'Oligodrâmnio'.$legenda,
        "Infecção Materna Bacteriana\nAguda\n0 = Não\n1 = Sim",
        "Cardiopatia \nCongênita".$legenda,
    ];
    $linha = [1265552, 5, 0, 1, 0, 2, 1, 0, 1, 1, 0, 0, 0, 0, 0, 1, 0, 1, 0, 0, 0, 0, 1, 0, 0, 1];

    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray(array_merge([
        ['Tempo de Coleta: 10 anos (2015 a 2025)'],
        $headers,
        $linha,
    ]), null, 'A1');

    $path = tempnam(sys_get_temp_dir(), 'carol').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);
    $arquivo = new UploadedFile($path, 'planilha-carol.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

    $this->actingAs(medicoImportacao())
        ->postJson(route('consultas.import.store'), ['csv' => $arquivo])
        ->assertOk()
        ->assertJsonPath('criadas', 1);

    $consulta = Consulta::query()->first();
    expect($consulta)->not->toBeNull()
        ->and($consulta->registro_planilha)->toBe('1265552')
        ->and($consulta->idade_crianca_meses)->toBe(5)
        ->and($consulta->idade_materna)->toBeNull()
        ->and($consulta->idade_gestacional)->toBeNull()
        ->and($consulta->etnia)->toBe('Parda')
        ->and($consulta->imc_gestacional_categoria)->toBe(1)
        ->and($consulta->tipo_gestacao)->toBe('gemelar')
        ->and($consulta->historico_familiar_chd)->toBeTrue()
        ->and($consulta->diabetes_gestacional)->toBeTrue()
        ->and($consulta->hipotireoidismo)->toBeTrue()
        ->and($consulta->hipertensao_cronica)->toBeTrue()
        ->and($consulta->polidramnio_oligoidramnio)->toBe('polidramnio')
        ->and($consulta->chd_confirmada)->toBeTrue()
        ->and($consulta->pressao_sistolica)->toBeNull()
        ->and(Gestante::query()->where('gestante_id', '1265552')->exists())->toBeTrue();
});

test('rejeita planilha sem id_gestante', function () {
    $csv = UploadedFile::fake()->createWithContent(
        'legado.csv',
        "gestante_id,data_consulta\n1,2026-01-01\n"
    );

    $this->actingAs(medicoImportacao())
        ->postJson(route('consultas.import.store'), ['csv' => $csv])
        ->assertStatus(422)
        ->assertJsonFragment(['message' => 'A planilha precisa da coluna id_gestante.']);
});
