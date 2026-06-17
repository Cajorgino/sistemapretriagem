<?php

namespace App\Jobs;

use App\Models\AnaliseHistorico;
use App\Models\Consulta;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * @deprecated Use ExecutarAnaliseGestante (análise por gestante via REST JSON).
 *             Mantido para reprocessamento em lote pelo dashboard legado.
 */
class AnalisarDadosIA implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        Log::info('Job AnalisarDadosIA (legado) enfileirado — redirecionando para ExecutarAnaliseGestante.');
    }

    public function handle(): void
    {
        Cache::put('analise_ia_status', 'enfileirado', now()->addHours(2));

        $gestanteIds = Consulta::query()
            ->whereNotNull('gestante_id')
            ->distinct()
            ->pluck('gestante_id');

        if ($gestanteIds->isEmpty()) {
            Cache::put('analise_ia_status', 'concluido', now()->addHours(2));
            Cache::put('resultado_analise_ia', [
                'status' => 'concluido',
                'mensagem' => 'Nenhuma consulta para analisar.',
            ], now()->addHours(2));

            return;
        }

        $total = 0;

        foreach ($gestanteIds as $gestanteId) {
            $consulta = Consulta::where('gestante_id', $gestanteId)->latest('id')->first();
            if (! $consulta) {
                continue;
            }

            ExecutarAnaliseGestante::dispatch(
                (int) $gestanteId,
                $consulta->id,
                AnaliseHistorico::EVENTO_REPROCESSAMENTO,
            );
            $total++;
        }

        Cache::put('analise_ia_status', 'processando', now()->addHours(2));
        Cache::put('resultado_analise_ia', [
            'status' => 'enfileirado',
            'total_gestantes' => $total,
            'mensagem' => "Reprocessamento enfileirado para {$total} gestante(s). Consulte analises_historico.",
        ], now()->addHours(2));

        Log::info('AnalisarDadosIA: jobs individuais enfileirados.', ['total' => $total]);
    }
}
