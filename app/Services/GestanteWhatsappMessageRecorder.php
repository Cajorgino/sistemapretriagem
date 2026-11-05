<?php

namespace App\Services;

use App\Models\Gestante;
use App\Models\GestanteWhatsapp;

class GestanteWhatsappMessageRecorder
{
    /** @var array{estado:?string, resposta:?string, respondido:bool, pular_ia:bool, intencao:?string}|null */
    private ?array $ultimoFluxo = null;

    public function __construct(
        private WhatsAppMessageTemplates $templates,
    ) {}

    /**
     * Persiste uma mensagem (entrada ou saída) vinculada à gestante, quando encontrada.
     *
     * @param  array{
     *   gestante?: \App\Models\Gestante|null,
     *   canal?: string,
     *   canal_identidade_id?: int|null,
     *   external_user_id?: string|null,
     *   external_chat_id?: string|null,
     *   processar_fluxo?: bool,
     *   metadata?: array<string, mixed>|null
     * }  $extra
     */
    public function record(string $telefoneRaw, string $mensagem, string $tipo, array $extra = []): GestanteWhatsapp
    {
        $this->ultimoFluxo = null;
        $normalized = $this->normalizeTelefone($telefoneRaw);
        $gestante = array_key_exists('gestante', $extra)
            ? $extra['gestante']
            : ($normalized !== '' ? $this->findGestanteByTelefone($normalized) : null);
        if ($gestante === null && $normalized !== '' && ! array_key_exists('gestante', $extra)) {
            $gestanteIdAnterior = GestanteWhatsapp::query()
                ->where('external_user_id', $normalized)
                ->whereNotNull('gestante_id')
                ->orderByDesc('id')
                ->value('gestante_id');
            if ($gestanteIdAnterior) {
                $gestante = Gestante::query()->find($gestanteIdAnterior);
            }
        }
        $gestanteId = $gestante?->id;

        $tempoAtendimento = null;
        $acompanhamentoId = null;
        $categoria = GestanteWhatsapp::CATEGORIA_CONVERSA;

        if ($gestanteId !== null) {
            $prev = GestanteWhatsapp::query()
                ->where('gestante_id', $gestanteId)
                ->orderByDesc('id')
                ->first();

            if ($prev !== null) {
                $tempoAtendimento = (int) abs($prev->created_at->diffInSeconds(now()));
            }

            $acompanhamentoId = GestanteWhatsapp::acompanhamentoIdRecenteParaConversa($gestanteId);
        }

        $row = GestanteWhatsapp::query()->create([
            'gestante_id' => $gestanteId,
            'acompanhamento_id' => $acompanhamentoId,
            'mensagem' => $mensagem,
            'tipo' => $tipo,
            'categoria' => $categoria,
            'tempo_atendimento' => $tempoAtendimento,
            'canal' => $extra['canal'] ?? GestanteWhatsapp::CANAL_WHATSAPP,
            'canal_identidade_id' => $extra['canal_identidade_id'] ?? null,
            'external_user_id' => $extra['external_user_id'] ?? ($normalized !== '' ? $normalized : null),
            'external_chat_id' => $extra['external_chat_id'] ?? null,
            'metadata' => $extra['metadata'] ?? null,
        ]);

        $processarFluxo = $extra['processar_fluxo'] ?? true;
        if ($processarFluxo && $tipo === 'entrada' && $gestante !== null) {
            $this->processarFluxoMenu($gestante);
        }

        return $row;
    }

    /**
     * @return array{estado:?string, resposta:?string, respondido:bool, pular_ia:bool, intencao:?string}
     */
    public function ultimoFluxo(): array
    {
        return $this->ultimoFluxo ?? [
            'estado' => null,
            'resposta' => null,
            'respondido' => false,
            'pular_ia' => false,
            'intencao' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{telefone: string, mensagem: string, tipo: string}|null
     */
    public function extractIncoming(array $payload): ?array
    {
        return $this->templates->extractIncomingFromWahaEvent($payload);
    }

    private function processarFluxoMenu(Gestante $gestante): void
    {
        if ($gestante->whatsappIaPausada()) {
            $this->ultimoFluxo = [
                'estado' => $gestante->whatsapp_fluxo_estado,
                'resposta' => null,
                'respondido' => false,
                'pular_ia' => true,
                'intencao' => 'atendimento_humano',
            ];

            return;
        }

        $ultimaEntrada = GestanteWhatsapp::query()
            ->where('gestante_id', $gestante->id)
            ->where('tipo', 'entrada')
            ->orderByDesc('id')
            ->value('mensagem');

        if ($this->templates->textoFimGestacao(is_string($ultimaEntrada) ? $ultimaEntrada : null)) {
            $ctx = $this->templates->contextFromGestante($gestante);
            $this->ultimoFluxo = [
                'estado' => $gestante->whatsapp_fluxo_estado,
                'resposta' => $this->templates->mensagemForaEscopoNaoGestante((string) ($ctx['primeiro_nome'] ?? '')),
                'respondido' => true,
                'pular_ia' => true,
                'intencao' => 'fora_escopo',
            ];

            return;
        }

        // A resposta automática é do Gemini (n8n). O menu 1/2/3 não intercepta a mensagem.
        // A pausa da equipe continua: pular_ia impede o Gemini de responder no lugar do médico.
        $this->ultimoFluxo = [
            'estado' => $gestante->whatsapp_fluxo_estado,
            'resposta' => null,
            'respondido' => false,
            'pular_ia' => false,
            'intencao' => 'conversa_livre',
        ];
    }

    /**
     * Liga mensagens gravadas sem gestante quando a conversa anterior e a seguinte
     * são da mesma pessoa, ou quando a mensagem é a continuação recente dela.
     */
    public function vincularMensagensOrfas(): int
    {
        $orfas = GestanteWhatsapp::query()
            ->whereNull('gestante_id')
            ->orderBy('id')
            ->get(['id', 'created_at']);

        foreach ($orfas as $orfa) {
            $anterior = GestanteWhatsapp::query()
                ->where('id', '<', $orfa->id)
                ->whereNotNull('gestante_id')
                ->orderByDesc('id')
                ->first(['id', 'gestante_id', 'created_at']);
            $seguinte = GestanteWhatsapp::query()
                ->where('id', '>', $orfa->id)
                ->whereNotNull('gestante_id')
                ->orderBy('id')
                ->first(['id', 'gestante_id', 'created_at']);

            $gestanteId = null;
            if ($anterior && $seguinte && (int) $anterior->gestante_id === (int) $seguinte->gestante_id
                && abs($anterior->created_at->diffInHours($seguinte->created_at)) <= 12) {
                $gestanteId = (int) $anterior->gestante_id;
            } elseif ($anterior && ! $seguinte && abs($anterior->created_at->diffInHours($orfa->created_at)) <= 36) {
                $gestanteId = (int) $anterior->gestante_id;
            }

            if ($gestanteId) {
                GestanteWhatsapp::query()->whereKey($orfa->id)->update(['gestante_id' => $gestanteId]);
            }
        }

        return GestanteWhatsapp::query()->whereNull('gestante_id')->count();
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

        $exata = Gestante::query()->whereIn('telefone', $candidates)->first();
        if ($exata) {
            return $exata;
        }

        $sufixos = [];
        foreach ($candidates as $candidate) {
            if (strlen($candidate) >= 11) {
                $sufixos[] = substr($candidate, -11);
            }
            if (strlen($candidate) >= 10) {
                $sufixos[] = substr($candidate, -10);
            }
        }

        $encontradas = collect();
        foreach (array_values(array_unique($sufixos)) as $sufixo) {
            $encontradas = $encontradas->merge(
                Gestante::query()->where('telefone', 'like', '%'.$sufixo)->limit(2)->get()
            );
        }

        $unicas = $encontradas->unique('id');

        return $unicas->count() === 1 ? $unicas->first() : null;
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
