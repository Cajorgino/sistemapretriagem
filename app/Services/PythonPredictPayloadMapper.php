<?php

namespace App\Services;

use App\Models\Consulta;
use App\Models\Gestante;

/**
 * Converte a consulta no payload da planilha treinado.
 * Não envia idade materna, idade gestacional nem IMC numérico.
 */
class PythonPredictPayloadMapper
{
    /**
     * @return array<string, int|float|string|null>
     */
    public function fromConsulta(Consulta $consulta, ?Gestante $gestante = null): array
    {
        $gestante = $gestante ?? $consulta->gestante;
        $gemelar = strtolower((string) ($consulta->tipo_gestacao ?? '')) === 'gemelar' ? 1 : 0;
        $liquido = strtolower((string) ($consulta->polidramnio_oligoidramnio ?? ''));
        $tireoide = (bool) $consulta->doencas_tireoidianas
            || (bool) $consulta->hipotireoidismo
            || (bool) $consulta->hipertireoidismo;

        return [
            'id_gestante' => $gestante?->gestante_id,
            'etnia' => $this->nullableString($consulta->etnia),
            'paridade' => $consulta->paridade === null ? null : (int) $consulta->paridade,
            'imc_gestacional_categoria' => $consulta->imc_gestacional_categoria === null
                ? null
                : (string) (int) $consulta->imc_gestacional_categoria,
            'prematuridade' => (int) ((bool) $consulta->prematuridade),
            'gestacao_gemelar' => $gemelar,
            'abortamentos_previos' => (int) ($consulta->abortamentos_previos ?? 0),
            'historia_familiar_cardiopatia' => (int) ((bool) $consulta->historico_familiar_chd),
            'diabetes_gestacional' => (int) ((bool) $consulta->diabetes_gestacional),
            'diabetes_pre_gestacional' => (int) ((bool) $consulta->diabetes_pre_gestacional),
            'lupus' => (int) ((bool) $consulta->lupus_eritomatoso_sistemico),
            'sindrome_sjogren' => (int) ((bool) $consulta->sindrome_sjogren),
            'doenca_tireoide' => $tireoide ? 1 : 0,
            'hipertensao_cronica' => (int) ((bool) $consulta->hipertensao_cronica),
            'hipertensao_pre_eclampsia' => (int) ((bool) $consulta->hipertensao_pre_eclampsia),
            'eclampsia' => (int) ((bool) $consulta->eclampsia),
            'drogas_ilicitas' => (int) ((bool) $consulta->drogas_ilicitas),
            'licitas_teratogenicas' => (int) ((bool) $consulta->licitas_teratogenicas),
            'polidramnio' => $liquido === 'polidramnio' ? 1 : 0,
            'oligodramnio' => $liquido === 'oligodramnio' ? 1 : 0,
            'infeccao_materna_bacteriana' => (int) ((bool) $consulta->infeccao_materna_bacteriana),
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $v = trim((string) $value);

        return $v === '' || strtolower($v) === 'null' ? null : $v;
    }
}
