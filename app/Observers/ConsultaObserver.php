<?php

namespace App\Observers;

use App\Jobs\ExecutarAnaliseGestante;
use App\Models\AnaliseHistorico;
use App\Models\Consulta;
use App\Models\Gestante;
use App\Services\GestanteAtendimentoService;
use Illuminate\Support\Facades\Log;
use Throwable;

class ConsultaObserver
{
    /**
     * @var list<string>
     */
    private const CAMPOS_CLINICOS = [
        'idade_materna', 'idade_gestacional', 'etnia', 'imc_pre_gestacional',
        'paridade', 'abortamentos_previos', 'historico_natimorto', 'historico_filho_anterior_chd',
        'diabetes_pre_gestacional', 'diabetes_gestacional', 'hipertensao', 'hipertensao_pre_eclampsia',
        'obesidade_pre_gestacional', 'historico_familiar_chd', 'uso_medicamentos',
        'uso_isotretinoina', 'uso_acido_valproico', 'uso_litio', 'tabagismo', 'alcoolismo',
        'drogas_ilicitas', 'exposicao_ocupacional', 'lupus_eritomatoso_sistemico', 'fenilcetonuria',
        'doencas_tireoidianas', 'hipertensao_cronica', 'rubeola', 'citomegalovirus', 'toxoplasmose',
        'sifilis', 'tipo_gestacao', 'corionicidade',
        'translucencia_nucal_aumentada', 'doppler_ducto_venoso', 'regurgitacao_tricuspide_fetal',
        'malformacoes_extracardiacas_associadas', 'crescimento_fetal_rcf_iugr', 'polidramnio_oligoidramnio',
    ];

    public function created(Consulta $consulta): void
    {
        if (! $consulta->gestante_id) {
            return;
        }

        $evento = Consulta::where('gestante_id', $consulta->gestante_id)->count() === 1
            ? AnaliseHistorico::EVENTO_CADASTRO
            : AnaliseHistorico::EVENTO_CONSULTA;

        $this->dispatchAnalise($consulta->gestante_id, $consulta->id, $evento);
        $this->sincronizarFluxo($consulta->gestante_id);
    }

    public function updated(Consulta $consulta): void
    {
        if (! $consulta->gestante_id) {
            return;
        }

        if (! $consulta->wasChanged(self::CAMPOS_CLINICOS)) {
            return;
        }

        $this->dispatchAnalise(
            $consulta->gestante_id,
            $consulta->id,
            AnaliseHistorico::EVENTO_ALTERACAO,
        );
        $this->sincronizarFluxo($consulta->gestante_id);
    }

    private function dispatchAnalise(int $gestanteId, ?int $consultaId, string $evento): void
    {
        if (ExecutarAnaliseGestante::deveConcluirNaHora()) {
            try {
                ExecutarAnaliseGestante::dispatchSync($gestanteId, $consultaId, $evento);
            } catch (Throwable $e) {
                Log::warning('ConsultaObserver: análise não concluída no cadastro.', [
                    'gestante_id' => $gestanteId,
                    'consulta_id' => $consultaId,
                    'mensagem' => $e->getMessage(),
                ]);
            }

            return;
        }

        ExecutarAnaliseGestante::dispatch($gestanteId, $consultaId, $evento);
    }

    private function sincronizarFluxo(int $gestanteId): void
    {
        $gestante = Gestante::query()->find($gestanteId);
        if ($gestante) {
            app(GestanteAtendimentoService::class)->sincronizarFluxo($gestante);
        }
    }
}
