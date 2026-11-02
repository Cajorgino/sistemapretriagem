<?php

namespace App\Services;

use App\Models\Acompanhamento;
use App\Models\Alerta;
use App\Models\AnaliseHistorico;
use App\Models\Gestante;

class AcompanhamentoSyncService
{
    /**
     * O escore da planilha não abre acompanhamento.
     *
     * @return array{acompanhamentos: int, alertas: int}
     */
    public function syncFromUltimasAnalises(int $limit = 300): array
    {
        return ['acompanhamentos' => 0, 'alertas' => 0];
    }

    /**
     * @return array{acompanhamentos: int, alertas: int}
     */
    public function syncGestante(Gestante $gestante): array
    {
        return ['acompanhamentos' => 0, 'alertas' => 0];
    }

    /**
     * @return array{acompanhamentos: int, alertas: int}
     */
    public function syncFromAnalise(AnaliseHistorico $analise): array
    {
        return ['acompanhamentos' => 0, 'alertas' => 0];
    }

    public function atualizarStatus(
        Acompanhamento $acompanhamento,
        string $status,
        ?string $observacao = null,
        ?string $agendadoPara = null,
        bool $atualizarAgendamento = false,
    ): Acompanhamento {
        $acompanhamento->status = $status;
        if ($observacao !== null && $observacao !== '') {
            $acompanhamento->observacao = $observacao;
        }

        if ($atualizarAgendamento) {
            $acompanhamento->agendado_para = $agendadoPara ?: null;
        }

        if (in_array($status, [Acompanhamento::STATUS_CONCLUIDO, Acompanhamento::STATUS_CANCELADO], true)) {
            $acompanhamento->concluido_em = now();
            $alertas = Alerta::query()
                ->where('acompanhamento_id', $acompanhamento->id)
                ->abertos()
                ->get();

            foreach ($alertas as $alerta) {
                $alerta->resolvido_em = now();
                if ($alerta->chave) {
                    $alerta->chave = $alerta->chave.':closed:'.$alerta->id;
                }
                $alerta->save();
            }
        } elseif ($status === Acompanhamento::STATUS_EM_ANDAMENTO) {
            $acompanhamento->concluido_em = null;
        }

        $acompanhamento->save();

        $gestante = $acompanhamento->gestante ?? Gestante::query()->find($acompanhamento->gestante_id);
        if ($gestante) {
            app(GestanteAtendimentoService::class)->sincronizarFluxo($gestante);
        }

        return $acompanhamento;
    }
}
