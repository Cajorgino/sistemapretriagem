<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CcfApiClient
{
    /**
     * Envia dados clínicos para POST /analisar/completo (GestRisk FastAPI).
     *
     * @param  array<string, mixed>  $payload  Campos no formato PredictionInput
     * @return array<string, mixed>
     *
     * @throws RequestException
     */
    public function analisarCompleto(array $payload, bool $persistirNaApi = false): array
    {
        $config = config('services.ccf_api', []);
        $baseUrl = rtrim((string) ($config['url'] ?? ''), '/');
        $timeout = (int) ($config['timeout'] ?? 120);

        if ($baseUrl === '') {
            throw new \RuntimeException('CCF_API_URL não configurada (services.ccf_api.url).');
        }

        $url = $baseUrl.'/analisar/completo';
        $query = ['persistir' => $persistirNaApi ? 'true' : 'false'];

        Log::info('CCF API: enviando análise completa.', [
            'id_gestante' => $payload['id_gestante'] ?? null,
            'url' => $url,
        ]);

        $request = Http::timeout($timeout)
            ->acceptJson()
            ->asJson();

        if (! empty($config['api_key'])) {
            $request = $request->withHeaders([
                'X-Api-Key' => $config['api_key'],
            ]);
        }

        $response = $request->post($url.'?'.http_build_query($query), $payload);
        $response->throw();

        $data = $response->json();
        if (! is_array($data)) {
            throw new \RuntimeException('Resposta inválida da API CCF (JSON esperado).');
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function health(): array
    {
        $baseUrl = rtrim((string) config('services.ccf_api.url', ''), '/');
        $response = Http::timeout(10)->get($baseUrl.'/health');

        return $response->json() ?? ['status' => 'unknown'];
    }

    /**
     * @return list<array{slug: string, titulo: string, arquivo: string|null, disponivel: bool}>
     */
    public function listarGraficosDescritivos(): array
    {
        $baseUrl = rtrim((string) config('services.ccf_api.url', ''), '/');
        if ($baseUrl === '') {
            return [];
        }

        try {
            $response = Http::timeout(15)->get($baseUrl.'/graficos');
            if (! $response->successful()) {
                return [];
            }

            $data = $response->json();
            if (! is_array($data) || ! is_array($data['graficos'] ?? null)) {
                return [];
            }

            return $data['graficos'];
        } catch (\Throwable $e) {
            Log::warning('CCF API: falha ao listar gráficos descritivos.', ['erro' => $e->getMessage()]);

            return [];
        }
    }

    public function fetchGraficoDescritivo(string $slug): Response
    {
        $baseUrl = rtrim((string) config('services.ccf_api.url', ''), '/');
        if ($baseUrl === '') {
            throw new \RuntimeException('CCF_API_URL não configurada.');
        }

        $request = Http::timeout(30);
        if ($apiKey = config('services.ccf_api.api_key')) {
            $request = $request->withHeaders(['X-Api-Key' => $apiKey]);
        }

        return $request->get($baseUrl.'/graficos/'.rawurlencode($slug));
    }
}
