<?php

namespace App\Observers;

use App\Jobs\ExecutarAnaliseGestante;
use App\Models\AnaliseHistorico;
use App\Models\Consulta;

class ConsultaObserver
{
    /**
     * Campos clínicos que disparam nova análise quando alterados.
     *
     * @var list<string>
     */
    private const CAMPOS_CLINICOS = [
        'idade_materna', 'idade', 'idade_gestacional', 'etnia', 'imc_pre_gestacional',
        'paridade', 'abortamentos_previos', 'historico_natimorto', 'historico_filho_anterior_chd',
        'diabetes_pre_gestacional', 'diabetes_gestacional', 'hipertensao', 'hipertensao_pre_eclampsia',
        'obesidade_pre_gestacional', 'historico_familiar_chd', 'uso_medicamentos',
        'uso_isotretinoina', 'uso_acido_valproico', 'uso_litio', 'tabagismo', 'alcoolismo',
        'drogas_ilicitas', 'exposicao_ocupacional', 'lupus_eritomatoso_sistemico', 'fenilcetonuria',
        'doencas_tireoidianas', 'hipertensao_cronica', 'rubeola', 'citomegalovirus', 'toxoplasmose',
        'sifilis', 'usg_precoce_confirmada', 'tipo_gestacao', 'corionicidade',
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
    }

    private function dispatchAnalise(int $gestanteId, ?int $consultaId, string $evento): void
    {
        if (config('services.ccf_api.sync_analise', false)) {
            ExecutarAnaliseGestante::dispatchSync($gestanteId, $consultaId, $evento);

            return;
        }

        ExecutarAnaliseGestante::dispatch($gestanteId, $consultaId, $evento);
    }
}
