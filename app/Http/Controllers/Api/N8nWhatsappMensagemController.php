<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GestanteWhatsappMessageRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API pensada para workflows n8n + WAHA: grava mensagens recebidas/enviadas por número.
 */
class N8nWhatsappMensagemController extends Controller
{
    public function __construct(
        private GestanteWhatsappMessageRecorder $recorder
    ) {}

    /**
     * Telefone vem na URL (somente dígitos, 10–15 caracteres, ex.: 5521999999999).
     */
    public function store(Request $request, string $telefone): JsonResponse
    {
        $validated = $request->validate([
            'mensagem' => ['required', 'string'],
            'tipo' => ['required', 'in:entrada,saida'],
        ]);

        $row = $this->recorder->record($telefone, $validated['mensagem'], $validated['tipo']);

        return response()->json([
            'id' => $row->id,
            'gestante_id' => $row->gestante_id,
            'tempo_atendimento' => $row->tempo_atendimento,
            'tempo_atendimento_formatado' => $row->tempo_atendimento_formatado,
        ], 201);
    }
}
