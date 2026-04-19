<?php

namespace App\Http\Controllers;

use App\Models\Gestante;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class GestanteController extends Controller
{
    public function index()
    {
        $gestantes = Gestante::withCount('consultas')
            ->orderBy('nome')
            ->orderBy('id')
            ->paginate(15);

        return view('gestantes.index', compact('gestantes'));
    }

    public function create()
    {
        return view('gestantes.create');
    }

    public function store(Request $request, WhatsAppService $whatsAppService)
    {
        $request->merge([
            'cpf' => $this->normalizeDigits($request->input('cpf')),
            'telefone' => $this->normalizeDigits($request->input('telefone')),
        ]);

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'data_nascimento' => 'required|date',
            'cpf' => ['required', 'regex:/^\d{11}$/', Rule::unique('gestantes', 'cpf')],
            'telefone' => ['required', 'regex:/^\d{10,13}$/', Rule::unique('gestantes', 'telefone')],
        ], [
            'cpf.required' => 'Informe o CPF da gestante.',
            'cpf.regex' => 'O CPF deve conter 11 dígitos.',
            'telefone.required' => 'Informe o telefone da gestante.',
            'telefone.regex' => 'O telefone deve ter entre 10 e 13 dígitos (com DDD ou 55).',
        ]);

        $gestante = Gestante::create($validated);

        try {
            $whatsAppService->sendGestanteWelcomeMessage($gestante);
        } catch (Throwable $e) {
            Log::error('Falha ao disparar WhatsApp de boas-vindas após cadastro de gestante.', [
                'gestante_id' => $gestante->id,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
        }

        return redirect()->route('gestantes.show', $gestante->id)->with('success', 'Gestante cadastrada com sucesso!');
    }

    public function show(Gestante $gestante)
    {
        $gestante->load(['consultas' => function ($query) {
            $query->orderBy('consulta_numero');
        }]);

        // Passa a gestante e suas consultas para a view
        return view('gestantes.show', compact('gestante'));
    }

    public function edit($id)
    {
        $gestante = Gestante::findOrFail($id);

        return view('gestantes.edit', compact('gestante'));

    }

    public function update(Request $request, $id)
    {
        $gestante = Gestante::findOrFail($id);

        $request->merge([
            'cpf' => $this->normalizeDigits($request->input('cpf')),
            'telefone' => $this->normalizeDigits($request->input('telefone')),
        ]);

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'data_nascimento' => 'required|date',
            'cpf' => ['required', 'regex:/^\d{11}$/', Rule::unique('gestantes', 'cpf')->ignore($gestante->id)],
            'telefone' => ['required', 'regex:/^\d{10,13}$/', Rule::unique('gestantes', 'telefone')->ignore($gestante->id)],
        ], [
            'cpf.required' => 'Informe o CPF da gestante.',
            'cpf.regex' => 'O CPF deve conter 11 dígitos.',
            'telefone.required' => 'Informe o telefone da gestante.',
            'telefone.regex' => 'O telefone deve ter entre 10 e 13 dígitos (com DDD ou 55).',
        ]);

        $gestante->update($validated);

        return redirect()->route('gestantes.show', $gestante->id)->with('success', 'Dados da gestante atualizados com sucesso!');
    }

    public function destroy($id)
    {
        $gestante = Gestante::findOrFail($id);
        $gestante->delete();

        return redirect()->route('gestantes.index')->with('success', 'Gestante removida com sucesso!');
    }

    private function normalizeDigits(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $value);

        return $digits !== '' ? $digits : null;
    }
}
