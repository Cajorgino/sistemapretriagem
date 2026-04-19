<?php

namespace App\Services;

use App\Models\Gestante;
use App\Models\GestanteWhatsapp;

class GestanteWhatsappMessageRecorder
{
    /**
     * Persiste uma mensagem (entrada ou saída) vinculada à gestante pelo telefone, quando encontrada.
     */
    public function record(string $telefoneRaw, string $mensagem, string $tipo): GestanteWhatsapp
    {
        $normalized = $this->normalizeTelefone($telefoneRaw);
        $gestante = $normalized !== '' ? $this->findGestanteByTelefone($normalized) : null;
        $gestanteId = $gestante?->id;

        $tempoAtendimento = null;
        if ($gestanteId !== null) {
            $prev = GestanteWhatsapp::query()
                ->where('gestante_id', $gestanteId)
                ->orderByDesc('id')
                ->first();

            if ($prev !== null) {
                $tempoAtendimento = (int) abs($prev->created_at->diffInSeconds(now()));
            }
        }

        return GestanteWhatsapp::query()->create([
            'gestante_id' => $gestanteId,
            'mensagem' => $mensagem,
            'tipo' => $tipo,
            'tempo_atendimento' => $tempoAtendimento,
        ]);
    }

    private function findGestanteByTelefone(string $normalized): ?Gestante
    {
        $candidates = [$normalized];

        if (strlen($normalized) > 11) {
            $candidates[] = substr($normalized, -11);
        }

        if (str_starts_with($normalized, '55') && strlen($normalized) >= 12) {
            $candidates[] = substr($normalized, 2);
        }

        if (strlen($normalized) === 11) {
            $candidates[] = '55'.$normalized;
        }

        $candidates = array_values(array_unique(array_filter($candidates)));

        if ($candidates === []) {
            return null;
        }

        return Gestante::query()->whereIn('telefone', $candidates)->first();
    }

    private function normalizeTelefone(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $digits = preg_replace('/\D/', '', $value);

        return $digits !== '' ? $digits : '';
    }
}
