<?php

namespace App\Services;

use App\Models\Gestante;
use App\Models\GestanteWhatsapp;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsAppService
{
    /**
     * Envia texto via WAHA — {@see https://waha.devlike.pro/docs/how-to/send-messages/}
     * POST /api/sendText com body: session, chatId ({numero}@c.us), text.
     * Autenticação opcional: header X-Api-Key (WAHA_API_KEY), omitido se WAHA_NO_API_KEY=true.
     *
     * @return bool true se o WAHA respondeu com sucesso ao envio
     */
    public function sendMessage(string $telefone, string $mensagem): bool
    {
        $config = config('services.waha', []);

        if (empty($config['enabled']) || empty($config['base_url'])) {
            Log::debug('WhatsApp (WAHA): envio ignorado — integração desabilitada ou base URL vazia (WAHA_BASE_URL / WAHA_API_URL).', [
                'enabled' => ! empty($config['enabled']),
                'has_base_url' => ! empty($config['base_url']),
            ]);

            return false;
        }

        $digits = $this->normalizePhoneToBrazilE164Digits($telefone);
        if ($digits === null || $digits === '') {
            Log::warning('WhatsApp (WAHA): telefone inválido ou vazio após normalização.', [
                'telefone_prefix' => $this->maskPhoneForLog($telefone),
            ]);

            return false;
        }

        $chatId = $digits.'@c.us';
        $url = rtrim((string) $config['base_url'], '/').'/api/sendText';
        $session = (string) ($config['session'] ?? 'default');

        $payload = [
            'session' => $session,
            'chatId' => $chatId,
            'text' => $mensagem,
        ];

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
        $omitApiKey = ! empty($config['no_api_key']);
        if (! $omitApiKey && ! empty($config['api_token'])) {
            $headers['X-Api-Key'] = (string) $config['api_token'];
        }

        try {
            $response = Http::timeout((int) ($config['timeout'] ?? 20))
                ->withHeaders($headers)
                ->post($url, $payload);

            if ($response->successful()) {
                Log::info('WhatsApp (WAHA): mensagem enviada.', [
                    'chatId_suffix' => substr($chatId, -12),
                    'status' => $response->status(),
                ]);

                return true;
            }

            Log::warning('WhatsApp (WAHA): resposta não bem-sucedida.', [
                'status' => $response->status(),
                'body' => $this->truncate($response->body()),
                'url' => $url,
            ]);

            return false;
        } catch (Throwable $e) {
            Log::error('WhatsApp (WAHA): exceção inesperada ao enviar mensagem.', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Mensagem de boas-vindas após cadastro de gestante (GestRisk).
     */
    public function sendGestanteWelcomeMessage(Gestante $gestante): void
    {
        $nome = trim((string) ($gestante->nome ?? ''));
        if ($nome === '') {
            Log::warning('WhatsApp (WAHA): boas-vindas não enviadas — nome da gestante vazio.', [
                'gestante_id' => $gestante->id,
            ]);

            return;
        }

        $telefone = $gestante->telefone ?? '';
        if ($telefone === '') {
            Log::warning('WhatsApp (WAHA): boas-vindas não enviadas — telefone vazio.', [
                'gestante_id' => $gestante->id,
            ]);

            return;
        }

        $texto = $this->buildGestanteWelcomeText($nome);

        if (! $this->sendMessage($telefone, $texto)) {
            return;
        }

        $this->persistOutgoingMessage($gestante->id, $texto);
    }

    /**
     * Grava mensagem de saída no histórico (mesma tabela do webhook n8n/WAHA).
     */
    private function persistOutgoingMessage(int $gestanteId, string $mensagem): void
    {
        try {
            $tempoAtendimento = $this->resolveTempoAtendimentoSegundos($gestanteId);

            GestanteWhatsapp::query()->create([
                'gestante_id' => $gestanteId,
                'mensagem' => $mensagem,
                'tipo' => 'saida',
                'tempo_atendimento' => $tempoAtendimento,
            ]);
        } catch (Throwable $e) {
            Log::error('WhatsApp: falha ao salvar mensagem no histórico (gestante_whatsapp).', [
                'gestante_id' => $gestanteId,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Mesma regra do webhook: intervalo em segundos desde a última mensagem desta gestante, ou null se for a primeira.
     */
    private function resolveTempoAtendimentoSegundos(int $gestanteId): ?int
    {
        $prev = GestanteWhatsapp::query()
            ->where('gestante_id', $gestanteId)
            ->orderByDesc('id')
            ->first();

        if ($prev === null) {
            return null;
        }

        return (int) abs($prev->created_at->diffInSeconds(now()));
    }

    private function buildGestanteWelcomeText(string $nome): string
    {
        return <<<TXT
Olá {$nome}, seja muito bem-vinda ao GestRisk!

Sou seu assistente durante sua gestação. Sempre que precisar, pode me mandar uma mensagem por aqui.

Estou aqui para te ajudar!
TXT;
    }

    /**
     * Garante dígitos no formato 55 + DDD + número (celular/fixo BR).
     */
    public function normalizePhoneToBrazilE164Digits(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $value);
        if ($digits === '' || $digits === null) {
            return null;
        }

        if (str_starts_with($digits, '55') && strlen($digits) >= 12) {
            return $digits;
        }

        if (strlen($digits) === 11) {
            return '55'.$digits;
        }

        if (strlen($digits) === 10) {
            return '55'.$digits;
        }

        if (strlen($digits) === 13 && str_starts_with($digits, '55')) {
            return $digits;
        }

        return strlen($digits) >= 12 ? $digits : null;
    }

    private function maskPhoneForLog(?string $raw): string
    {
        $d = preg_replace('/\D/', '', (string) $raw);
        if (strlen($d) < 4) {
            return '***';
        }

        return '***'.substr($d, -4);
    }

    private function truncate(string $body, int $max = 500): string
    {
        if (strlen($body) <= $max) {
            return $body;
        }

        return substr($body, 0, $max).'…';
    }
}
