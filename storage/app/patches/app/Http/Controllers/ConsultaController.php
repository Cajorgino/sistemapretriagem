<?php

namespace App\Http\Controllers;

use App\Models\Consulta;
use App\Models\Gestante;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConsultaController extends Controller
{
    /** Regras do formulário clínico simplificado (GestRisk / CCF). */
    private function clinicalFormRules(): array
    {
        return [
            'data_consulta' => 'required|date',
            'idade_materna' => 'required|numeric|min:14|max:48',
            'idade_gestacional' => 'required|numeric|min:11|max:28',
            'etnia' => 'required|string|max:50',
            'imc_pre_gestacional' => 'required|numeric|min:16|max:45',
            'paridade' => 'required|integer|min:0|max:6',
            'abortamentos_previos' => 'required|integer|min:0|max:4',
            'historico_natimorto' => 'required|boolean',
            'historico_filho_anterior_chd' => 'required|boolean',
            'historico_familiar_chd' => 'required|boolean',
            'diabetes_pre_gestacional' => 'required|boolean',
            'diabetes_gestacional' => 'required|boolean',
            'lupus_eritomatoso_sistemico' => 'required|boolean',
            'fenilcetonuria' => 'required|boolean',
            'doencas_tireoidianas' => 'required|boolean',
            'hipertensao_cronica' => 'required|boolean',
            'rubeola' => 'required|boolean',
            'citomegalovirus' => 'required|boolean',
            'toxoplasmose' => 'required|boolean',
            'sifilis' => 'required|boolean',
            'uso_isotretinoina' => 'required|boolean',
            'uso_acido_valproico' => 'required|boolean',
            'uso_litio' => 'required|boolean',
            'tabagismo' => 'required|boolean',
            'alcoolismo' => 'required|boolean',
            'drogas_ilicitas' => 'required|boolean',
            'exposicao_solventes' => 'required|boolean',
            'exposicao_pesticidas' => 'required|boolean',
            'usg_precoce_confirmada' => 'required|boolean',
            'tipo_gestacao' => 'required|string|max:32',
            'corionicidade' => 'nullable|string|max:50',
            'translucencia_nucal_aumentada' => 'required|boolean',
            'doppler_ducto_venoso' => 'required|string|max:255',
            'regurgitacao_tricuspide_fetal' => 'required|boolean',
            'malformacoes_extracardiacas_associadas' => 'required|boolean',
            'crescimento_fetal_rcf_iugr' => 'required|boolean',
            'polidramnio' => 'required|boolean',
            'oligodramnio' => 'required|boolean',
        ];
    }

    /** Normaliza campos do formulário para persistência no modelo Consulta. */
    private function normalizeClinicalForm(array $validated, ?Consulta $existing = null, ?Gestante $gestante = null): array
    {
        $validated['exposicao_ocupacional'] = ($validated['exposicao_solventes'] ?? false)
            || ($validated['exposicao_pesticidas'] ?? false);

        $liquido = [];
        if (! empty($validated['polidramnio'])) {
            $liquido[] = 'Polidrâmnio';
        }
        if (! empty($validated['oligodramnio'])) {
            $liquido[] = 'Oligoidrâmnio';
        }
        $validated['polidramnio_oligoidramnio'] = $liquido !== [] ? implode(', ', $liquido) : null;

        $validated['uso_medicamentos'] = ($validated['uso_isotretinoina'] ?? false)
            || ($validated['uso_acido_valproico'] ?? false)
            || ($validated['uso_litio'] ?? false);

        $validated['obesidade_pre_gestacional'] = ($validated['imc_pre_gestacional'] ?? 0) >= 30;

        unset(
            $validated['exposicao_solventes'],
            $validated['exposicao_pesticidas'],
            $validated['polidramnio'],
            $validated['oligodramnio'],
        );

        $legacyDefaults = $existing !== null
            ? [
                'hipertensao' => (bool) $existing->hipertensao,
                'hipertensao_pre_eclampsia' => (bool) $existing->hipertensao_pre_eclampsia,
                'chd_confirmada' => (bool) $existing->chd_confirmada,
                'tipo_chd' => $existing->tipo_chd,
            ]
            : [
                'hipertensao' => false,
                'hipertensao_pre_eclampsia' => false,
                'chd_confirmada' => false,
                'tipo_chd' => null,
            ];

        $schemaDefaults = $this->legacySchemaDefaults($validated, $existing, $gestante);

        return array_merge($schemaDefaults, $legacyDefaults, $validated);
    }

    /**
     * Colunas NOT NULL do schema legado ausentes no formulário clínico simplificado.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function legacySchemaDefaults(array $validated, ?Consulta $existing, ?Gestante $gestante): array
    {
        if ($existing !== null) {
            return [
                'idade' => $existing->idade ?? $this->resolveIdade($validated, $gestante),
                'pressao_sistolica' => $existing->pressao_sistolica ?? 0,
                'bpm_materno' => $existing->bpm_materno ?? 0,
                'saturacao' => $existing->saturacao ?? 0,
                'temperatura_corporal' => $existing->temperatura_corporal ?? 0,
                'altura' => $existing->altura ?? 0,
                'peso' => $existing->peso ?? 0,
                'frequencia_cardiaca_fetal' => $existing->frequencia_cardiaca_fetal ?? 0,
                'circunferencia_cefalica_fetal_mm' => $existing->circunferencia_cefalica_fetal_mm ?? 0,
                'circunferencia_abdominal_mm' => $existing->circunferencia_abdominal_mm ?? 0,
                'comprimento_femur_mm' => $existing->comprimento_femur_mm ?? 0,
                'translucencia_nucal_mm' => $existing->translucencia_nucal_mm ?? 0,
                'eixo_cardiaco' => $existing->eixo_cardiaco ?? 'NA',
                'quatro_camaras' => $existing->quatro_camaras ?? 'NA',
            ];
        }

        return [
            'idade' => $this->resolveIdade($validated, $gestante),
            'pressao_sistolica' => 0,
            'bpm_materno' => 0,
            'saturacao' => 0,
            'temperatura_corporal' => 0,
            'altura' => 0,
            'peso' => 0,
            'frequencia_cardiaca_fetal' => 0,
            'circunferencia_cefalica_fetal_mm' => 0,
            'circunferencia_abdominal_mm' => 0,
            'comprimento_femur_mm' => 0,
            'translucencia_nucal_mm' => 0,
            'eixo_cardiaco' => 'NA',
            'quatro_camaras' => 'NA',
        ];
    }

    /** @param  array<string, mixed>  $validated */
    private function resolveIdade(array $validated, ?Gestante $gestante): int
    {
        if (isset($validated['idade_materna']) && $validated['idade_materna'] !== '') {
            return (int) round((float) $validated['idade_materna']);
        }

        if ($gestante?->data_nascimento && ! empty($validated['data_consulta'])) {
            $anos = Carbon::parse($gestante->data_nascimento)
                ->diffInYears(Carbon::parse($validated['data_consulta']));

            if ($anos >= 14 && $anos <= 48) {
                return (int) $anos;
            }
        }

        return 28;
    }

    public function index()
    {
        return view('consultas.import');
    }

    private function storeFromForm(Request $request, $id)
    {
        $gestante = Gestante::findOrFail($id);

        $validatedData = $this->normalizeClinicalForm(
            $request->validate($this->clinicalFormRules()),
            null,
            $gestante
        );

        $validatedData['gestante_id'] = $id;

        $ultimoNumero = Consulta::where('gestante_id', $id)->max('consulta_numero');
        $validatedData['consulta_numero'] = ($ultimoNumero ?? 0) + 1;

        Consulta::create($validatedData);

        return redirect()->route('gestantes.show', $id)->with('success', 'Consulta cadastrada com sucesso!');
    }

    public function store(Request $request, $id)
    {
        return $this->storeFromForm($request, $id);
    }

    public function import(Request $request)
    {
        if (! $request->hasFile('csv')) {
            return redirect()->back()->with('error', 'Nenhum arquivo CSV foi enviado.');
        }

        return $this->importStore($request);
    }

    private function toBoolean($value)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    public function importStore(Request $request)
    {
        set_time_limit(300);

        $request->validate([
            'csv' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('csv');

        $handle = fopen($file->getRealPath(), 'r');

        $header = fgetcsv($handle);

        $colunasEsperadas = [
            'data_consulta',
            'gestante_id',
            'consulta_numero',
            'idade_materna',
            'idade_gestacional',
            'etnia',
            'imc_pre_gestacional',
            'paridade',
            'abortamentos_previos',
            'historico_natimorto',
            'historico_filho_anterior_chd',
            'historico_familiar_chd',
            'diabetes_pre_gestacional',
            'diabetes_gestacional',
            'lupus_eritomatoso_sistemico',
            'fenilcetonuria',
            'doencas_tireoidianas',
            'hipertensao_cronica',
            'rubeola',
            'citomegalovirus',
            'toxoplasmose',
            'sifilis',
            'uso_isotretinoina',
            'uso_acido_valproico',
            'uso_litio',
            'uso_medicamentos',
            'tabagismo',
            'alcoolismo',
            'drogas_ilicitas',
            'exposicao_ocupacional',
            'usg_precoce_confirmada',
            'tipo_gestacao',
            'corionicidade',
            'altura',
            'peso',
            'pressao_sistolica',
            'bpm_materno',
            'saturacao',
            'temperatura_corporal',
            'frequencia_cardiaca_fetal',
            'circunferencia_cefalica_fetal_mm',
            'circunferencia_abdominal_mm',
            'comprimento_femur_mm',
            'translucencia_nucal_mm',
            'translucencia_nucal_aumentada',
            'doppler_ducto_venoso',
            'regurgitacao_tricuspide_fetal',
            'malformacoes_extracardiacas_associadas',
            'crescimento_fetal_rcf_iugr',
            'polidramnio_oligoidramnio',
            'eixo_cardiaco',
            'quatro_camaras',
            'chd_confirmada',
            'tipo_chd',
        ];

        $colunasFaltando = array_diff($colunasEsperadas, $header);
        $colunasExtras = array_diff($header, $colunasEsperadas);

        if (! empty($colunasFaltando) || ! empty($colunasExtras)) {
            return response()->json([
                'message' => 'Estrutura do CSV inválida.',
                'error' => [
                    'faltando' => array_values($colunasFaltando),
                    'extras' => array_values($colunasExtras),
                ],
            ], 422);
        }

        $dados = [];
        $erros = [];
        $numeroLinha = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $numeroLinha++;
            if (count($header) !== count($row)) {
                $erros[] = [
                    'linha_csv' => $numeroLinha ?? null,
                    'erro' => 'Quantidade de colunas inválida',
                ];

                continue;
            }

            $linha = array_combine($header, $row);

            $camposBooleanos = [
                'historico_natimorto',
                'historico_filho_anterior_chd',
                'historico_familiar_chd',
                'diabetes_pre_gestacional',
                'diabetes_gestacional',
                'lupus_eritomatoso_sistemico',
                'fenilcetonuria',
                'doencas_tireoidianas',
                'hipertensao_cronica',
                'rubeola',
                'citomegalovirus',
                'toxoplasmose',
                'sifilis',
                'uso_isotretinoina',
                'uso_acido_valproico',
                'uso_litio',
                'uso_medicamentos',
                'tabagismo',
                'alcoolismo',
                'drogas_ilicitas',
                'exposicao_ocupacional',
                'usg_precoce_confirmada',
                'translucencia_nucal_aumentada',
                'regurgitacao_tricuspide_fetal',
                'malformacoes_extracardiacas_associadas',
                'crescimento_fetal_rcf_iugr',
                'chd_confirmada',
            ];

            foreach ($camposBooleanos as $campo) {
                if (isset($linha[$campo])) {
                    $valor = strtolower(trim($linha[$campo]));

                    if (in_array($valor, ['1', 'true'])) {
                        $linha[$campo] = true;
                    } elseif (in_array($valor, ['0', 'false'])) {
                        $linha[$campo] = false;
                    } else {
                        $linha[$campo] = null;
                    }
                }
            }

            $validator = Validator::make($linha, [
                'data_consulta' => 'required|date',
                'idade_materna' => 'nullable|integer',
                'idade_gestacional' => 'required|integer',
                'etnia' => 'nullable|string|max:50',
                'imc_pre_gestacional' => 'nullable|numeric',
                'paridade' => 'nullable|integer',
                'abortamentos_previos' => 'nullable|integer',
                'historico_natimorto' => 'required|boolean',
                'historico_filho_anterior_chd' => 'required|boolean',
                'historico_familiar_chd' => 'required|boolean',
                'diabetes_pre_gestacional' => 'required|boolean',
                'diabetes_gestacional' => 'required|boolean',
                'lupus_eritomatoso_sistemico' => 'required|boolean',
                'fenilcetonuria' => 'required|boolean',
                'doencas_tireoidianas' => 'required|boolean',
                'hipertensao_cronica' => 'required|boolean',
                'rubeola' => 'required|boolean',
                'citomegalovirus' => 'required|boolean',
                'toxoplasmose' => 'required|boolean',
                'sifilis' => 'required|boolean',
                'uso_isotretinoina' => 'required|boolean',
                'uso_acido_valproico' => 'required|boolean',
                'uso_litio' => 'required|boolean',
                'uso_medicamentos' => 'required|boolean',
                'tabagismo' => 'required|boolean',
                'alcoolismo' => 'required|boolean',
                'drogas_ilicitas' => 'required|boolean',
                'exposicao_ocupacional' => 'required|boolean',
                'usg_precoce_confirmada' => 'required|boolean',
                'tipo_gestacao' => 'nullable|string|max:32',
                'corionicidade' => 'nullable|string|max:50',
                'altura' => 'nullable|numeric',
                'peso' => 'nullable|numeric',
                'pressao_sistolica' => 'nullable|integer',
                'bpm_materno' => 'nullable|integer',
                'saturacao' => 'nullable|integer',
                'temperatura_corporal' => 'nullable|numeric',
                'glicemia_jejum' => 'nullable|numeric',
                'glicemia_pos_prandial' => 'nullable|numeric',
                'hba1c' => 'nullable|numeric',
                'frequencia_cardiaca_fetal' => 'nullable|integer',
                'circunferencia_cefalica_fetal_mm' => 'nullable|numeric',
                'circunferencia_abdominal_mm' => 'nullable|numeric',
                'comprimento_femur_mm' => 'nullable|numeric',
                'translucencia_nucal_mm' => 'nullable|numeric',
                'translucencia_nucal_aumentada' => 'required|boolean',
                'doppler_ducto_venoso' => 'nullable|string',
                'regurgitacao_tricuspide_fetal' => 'required|boolean',
                'malformacoes_extracardiacas_associadas' => 'required|boolean',
                'crescimento_fetal_rcf_iugr' => 'required|boolean',
                'polidramnio_oligoidramnio' => 'nullable|string|max:32',
                'eixo_cardiaco' => 'nullable|string',
                'quatro_camaras' => 'nullable|string',
                'chd_confirmada' => 'required|boolean',
                'tipo_chd' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                $erros[] = $validator->errors();
            } else {
                $dados[] = $linha;
            }
        }

        fclose($handle);

        if (! empty($erros)) {
            return response()->json([
                'message' => 'Erro de validação nos dados.',
                'error' => $erros,
            ], 422);
        }

        foreach ($dados as $item) {
            Consulta::create($item);
        }

        return response()->json([
            'message' => 'Importação realizada com sucesso!',
        ]);
    }

    public function create($id)
    {
        $gestante = Gestante::findOrFail($id);

        return view('consultas.create', compact('gestante'));
    }

    public function edit($id)
    {
        $consulta = Consulta::with('gestante')->findOrFail($id);
        $gestante = $consulta->gestante;

        return view('consultas.edit', compact('consulta', 'gestante'));
    }

    public function update(Request $request, $id)
    {
        $consulta = Consulta::findOrFail($id);
        $gestante = $consulta->gestante;

        $validatedData = $this->normalizeClinicalForm(
            $request->validate($this->clinicalFormRules()),
            $consulta,
            $gestante
        );

        $consulta->update($validatedData);

        return redirect()->route('gestantes.show', $consulta->gestante_id)->with('success', 'Consulta atualizada com sucesso!');
    }
}
