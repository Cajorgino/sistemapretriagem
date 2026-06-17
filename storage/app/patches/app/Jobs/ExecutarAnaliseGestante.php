<?php

namespace App\Jobs;

use App\Models\AnaliseHistorico;
use App\Models\Consulta;
use App\Models\Gestante;
use App\Services\CcfApiClient;
use App\Services\CcfPayloadMapper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExecutarAnaliseGestante implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 180;

    /**
     * @var array<int, int>
     */
    public array $backoff = [30, 120, 300];

    public function __construct(
        public int $gestanteId,
        public ?int $consultaId = null,
        public string $eventoTipo = AnaliseHistorico::EVENTO_CONSULTA,
    ) {}

    public function uniqueId(): string
    {
        return sprintf('analise-gestante-%d-%d-%s', $this->gestanteId, $this->consultaId ?? 0, $this->eventoTipo);
    }

    public function uniqueFor(): int
    {
        return 60;
    }

    public function handle(CcfApiClient $apiClient, CcfPayloadMapper $mapper): void
    {
        Log::info('ExecutarAnaliseGestante: iniciando.', [
            'gestante_id' => $this->gestanteId,
            'consulta_id' => $this->consultaId,
            'evento_tipo' => $this->eventoTipo,
        ]);

        $gestante = Gestante::find($this->gestanteId);
        if (! $gestante) {
            Log::warning('ExecutarAnaliseGestante: gestante não encontrada.', [
                'gestante_id' => $this->gestanteId,
            ]);

            return;
        }

        $consulta = $this->resolveConsulta($gestante);
        if (! $consulta) {
            Log::info('ExecutarAnaliseGestante: sem consulta — análise adiada.', [
                'gestante_id' => $this->gestanteId,
            ]);

            return;
        }

        try {
            $payload = $mapper->fromConsulta($consulta, $gestante);
            $resposta = $apiClient->analisarCompleto($payload, persistirNaApi: false);

            $this->persistirSucesso($gestante, $consulta, $resposta);

            Log::info('ExecutarAnaliseGestante: concluída.', [
                'gestante_id' => $gestante->id,
                'consulta_id' => $consulta->id,
                'analise_uuid' => $resposta['metadados']['analise_id'] ?? null,
            ]);
        } catch (Throwable $e) {
            $this->persistirErro($gestante, $consulta, $e);
            throw $e;
        }
    }

    private function resolveConsulta(Gestante $gestante): ?Consulta
    {
        if ($this->consultaId) {
            return Consulta::where('gestante_id', $gestante->id)
                ->whereKey($this->consultaId)
                ->first();
        }

        return $gestante->consultas()->latest('id')->first();
    }

    /**
     * @param  array<string, mixed>  $resposta
     */
    private function persistirSucesso(Gestante $gestante, Consulta $consulta, array $resposta): void
    {
        $predicao = is_array($resposta['predicao'] ?? null) ? $resposta['predicao'] : [];
        $analise = is_array($resposta['analise'] ?? null) ? $resposta['analise'] : [];
        $recomendacoes = is_array($resposta['recomendacoes'] ?? null) ? $resposta['recomendacoes'] : [];
        $metadados = is_array($resposta['metadados'] ?? null) ? $resposta['metadados'] : [];

        AnaliseHistorico::create([
            'gestante_id' => $gestante->id,
            'consulta_id' => $consulta->id,
            'evento_tipo' => $this->eventoTipo,
            'analise_uuid' => $metadados['analise_id'] ?? null,
            'probabilidade_ccf' => $predicao['probabilidade_ccf'] ?? $analise['probabilidade_ccf'] ?? null,
            'classificacao_risco' => $predicao['classificacao_risco'] ?? $analise['classificacao_risco'] ?? null,
            'score_prioridade' => $analise['score_prioridade'] ?? null,
            'prioridade_ecocardiograma' => $analise['prioridade_ecocardiograma'] ?? $recomendacoes['prioridade'] ?? null,
            'recomenda_ecocardiograma' => (bool) ($analise['recomenda_ecocardiograma'] ?? $recomendacoes['ecocardiograma_fetal'] ?? false),
            'intercorrencias' => $resposta['intercorrencias'] ?? [],
            'shap' => $resposta['shap'] ?? [],
            'recomendacoes' => $recomendacoes,
            'predicao' => $predicao,
            'analise' => $analise,
            'qualidade_dados' => $resposta['qualidade_dados'] ?? [],
            'full_response' => $resposta,
            'status' => 'concluida',
        ]);
    }

    private function persistirErro(Gestante $gestante, Consulta $consulta, Throwable $e): void
    {
        Log::error('ExecutarAnaliseGestante: falha na API.', [
            'gestante_id' => $gestante->id,
            'consulta_id' => $consulta->id,
            'mensagem' => $e->getMessage(),
        ]);

        AnaliseHistorico::create([
            'gestante_id' => $gestante->id,
            'consulta_id' => $consulta->id,
            'evento_tipo' => $this->eventoTipo,
            'full_response' => [],
            'status' => 'erro',
            'erro_mensagem' => $e->getMessage(),
        ]);
    }
}
