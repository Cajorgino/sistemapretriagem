<?php

namespace App\Http\Controllers;

use App\Models\Consulta;
use App\Models\Gestante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConsultaController extends Controller
{
    public function index()
    {
        return view('consultas.import');
    }

    // STORE VIA PREENCHIMENTO MANUAL
    private function storeFromForm(Request $request, $id)
    {
        // 1. Validar todos os campos do formulário
        $validatedData = $request->validate([
            'data_consulta' => 'required|date',
            'idade_gestacional' => 'required|integer',
            'altura' => 'nullable|numeric',
            'peso' => 'nullable|numeric',
            'pressao_sistolica' => 'nullable|integer',
            'diabetes_gestacional' => 'required|boolean',
            'obesidade_pre_gestacional' => 'required|boolean',
            'hipertensao' => 'required|boolean',
            'hipertensao_pre_eclampsia' => 'required|boolean',
            'historico_familiar_chd' => 'required|boolean',
            'uso_medicamentos' => 'required|boolean',
            'tabagismo' => 'required|boolean',
            'alcoolismo' => 'required|boolean',
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
            'doppler_ducto_venoso' => 'nullable|string|max:255',
            'eixo_cardiaco' => 'nullable|string|max:255',
            'quatro_camaras' => 'nullable|string|max:255',
            'chd_confirmada' => 'required|boolean',
            'tipo_chd' => 'nullable|string|max:255',

        ]);

        // 2. Adicionar o ID da gestante para o salvamento
        $validatedData['gestante_id'] = $id;

        // Adicionar o número da consulta
        $ultimoNumero = Consulta::where('gestante_id', $id)->max('consulta_numero');
        $validatedData['consulta_numero'] = ($ultimoNumero ?? 0) + 1;

        // 3. Criar a consulta usando atribuição em massa
        $consulta = Consulta::create($validatedData);

        // 4. Redirecionar para a página de detalhes da gestante
        return redirect()->route('gestantes.show', $id)->with('success', 'Consulta cadastrada com sucesso!');
    }

    // STORE VIA PREENCHIMENTO MANUAL
    public function store(Request $request, $id)
    {
        // Lógica para salvar uma nova consulta a partir do formulário
        return $this->storeFromForm($request, $id);
    }

    // STORE VIA IMPORTAÇÃO CSV
    public function import(Request $request)
    {
        if (! $request->hasFile('csv')) {
            return redirect()->back()->with('error', 'Nenhum arquivo CSV foi enviado.');
        }

        return $this->importStore($request);
    }

    // STORE VIA IMPORTAÇÃO CSV

    private function toBoolean($value)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    public function importStore(Request $request)
    {
        // Define o tempo limite para 5 minutos (300 segundos) para esta requisição,
        // permitindo que o processamento do CSV e a chamada da API de IA terminem.
        set_time_limit(300);

        // 1. Validar upload
        $request->validate([
            'csv' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('csv');

        // 2. Ler CSV
        $handle = fopen($file->getRealPath(), 'r');

        $header = fgetcsv($handle);

        // 3. Definir colunas esperadas
        $colunasEsperadas = [
            'data_consulta',
            'gestante_id',
            'consulta_numero',
            'idade_gestacional',
            'altura',
            'peso',
            'pressao_sistolica',
            'diabetes_gestacional',
            'obesidade_pre_gestacional',
            'hipertensao',
            'hipertensao_pre_eclampsia',
            'historico_familiar_chd',
            'uso_medicamentos',
            'tabagismo',
            'alcoolismo',
            'bpm_materno',
            'saturacao',
            'temperatura_corporal',
            // 'glicemia_jejum',
            // 'glicemia_pos_prandial',
            // 'hba1c',
            'frequencia_cardiaca_fetal',
            'circunferencia_cefalica_fetal_mm',
            'circunferencia_abdominal_mm',
            'comprimento_femur_mm',
            'translucencia_nucal_mm',
            'doppler_ducto_venoso',
            'eixo_cardiaco',
            'quatro_camaras',
            'chd_confirmada',
            'tipo_chd',
        ];

        // 4. Validar estrutura do CSV
        // 4. Validar estrutura do CSV (melhorado)
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
        $numeroLinha = 1; // Row 1 is the header

        // 5. Validar linha a linha
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

            // 👇 CONVERTE AQUI (ANTES DO VALIDATE)
            $camposBooleanos = [
                'diabetes_gestacional',
                'obesidade_pre_gestacional',
                'hipertensao',
                'hipertensao_pre_eclampsia',
                'historico_familiar_chd',
                'uso_medicamentos',
                'tabagismo',
                'alcoolismo',
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
                        $linha[$campo] = null; // força erro no validator
                    }
                }
            }

            $validator = Validator::make($linha, [
                'data_consulta' => 'required|date',
                'idade_gestacional' => 'required|integer',
                'altura' => 'nullable|numeric',
                'peso' => 'nullable|numeric',
                'pressao_sistolica' => 'nullable|integer',
                'diabetes_gestacional' => 'required|boolean',
                'obesidade_pre_gestacional' => 'required|boolean',
                'hipertensao' => 'required|boolean',
                'hipertensao_pre_eclampsia' => 'required|boolean',
                'historico_familiar_chd' => 'required|boolean',
                'uso_medicamentos' => 'required|boolean',
                'tabagismo' => 'required|boolean',
                'alcoolismo' => 'required|boolean',
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
                'doppler_ducto_venoso' => 'nullable|string',
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

        // 6. Inserir as consultas no banco de dados
        foreach ($dados as $item) {
            Consulta::create($item);
        }

        // O processamento da IA agora deve ser disparado pelo botão "Analisar"
        // na Dashboard para evitar timeouts durante o upload.
        return response()->json([
            'message' => 'Importação realizada com sucesso!',
        ]);
    }

    public function create($id)
    {
        // 1. Encontrar a gestante pelo ID recebido na rota.
        $gestante = Gestante::findOrFail($id);

        // 2. Passar o objeto 'gestante' para a view.
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

        // A validação foi expandida para incluir todos os campos do formulário,
        // espelhando o método 'storeFromForm'. Isso evita a perda de dados ao atualizar.
        $validatedData = $request->validate([
            'data_consulta' => 'required|date',
            'idade_gestacional' => 'required|integer',
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
            'doppler_ducto_venoso' => 'nullable|string|max:255',
            'eixo_cardiaco' => 'nullable|string|max:255',
            'quatro_camaras' => 'nullable|string|max:255',
            'diabetes_gestacional' => 'required|boolean',
            'obesidade_pre_gestacional' => 'required|boolean',
            'hipertensao' => 'required|boolean',
            'hipertensao_pre_eclampsia' => 'required|boolean',
            'historico_familiar_chd' => 'required|boolean',
            'uso_medicamentos' => 'required|boolean',
            'tabagismo' => 'required|boolean',
            'alcoolismo' => 'required|boolean',
            'chd_confirmada' => 'required|boolean',
            'tipo_chd' => 'nullable|string|max:255',
        ]);

        $consulta->update($validatedData);

        return redirect()->route('gestantes.show', $consulta->gestante_id)->with('success', 'Consulta atualizada com sucesso!');
    }
}
