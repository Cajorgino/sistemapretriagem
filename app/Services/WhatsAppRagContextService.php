<?php

namespace App\Services;

use App\Contracts\RagContextBuilder;
use App\Models\AnaliseHistorico;
use App\Models\Consulta;
use App\Models\Gestante;
use App\Models\GestanteCanal;
use App\Models\GestanteDocumento;
use App\Models\GestanteDocumentoChunk;
use App\Models\GestanteWhatsapp;
use App\Models\InterpretacaoClinica;
use App\Support\WhatsAppFluxoEstado;
use Illuminate\Support\Str;

/**
 * RAG WhatsApp (fase 1–2): monta chunks a partir do prontuário Laravel
 * (consultas, análises, interpretações, documentos textuais) e ranqueia
 * por sobreposição lexical com a mensagem da gestante.
 *
 * Não inclui CPF/telefone/nome completo (minimização LGPD).
 */
class WhatsAppRagContextService implements RagContextBuilder
{
    private const HISTORICO_LIMIT = 20;

    private const HISTORICO_TEXT_LIMIT = 400;

    private const INTERPRETACAO_LIMIT = 500;

    public function __construct(
        private WhatsAppMessageTemplates $templates,
    ) {}

    /**
     * @return array{
     *   found: bool,
     *   gestante_id: int|null,
     *   gestante_id_publico: string|null,
     *   chunks: list<array{source: string, title: string, text: string, score: float}>,
     *   contexto_texto: string,
     *   total_fontes: int,
     *   pular_ia: bool,
     *   atendimento_humano: bool,
     *   ia_pausada_ate: string|null,
     *   menu_opcao: string|null,
     *   fluxo_estado: string|null,
     *   intencao: string,
     *   resumo_prenatal: string,
     *   nome: string|null,
     *   patient_snapshot: array<string, mixed>,
     *   historico_conversa: list<array{role: string, text: string, at: string|null}>
     * }
     */
    public function buildForTelefone(string $telefoneRaw, ?string $mensagem = null, int $topK = 6): array
    {
        $empty = [
            'found' => false,
            'gestante_id' => null,
            'gestante_id_publico' => null,
            'chunks' => [],
            'contexto_texto' => '',
            'total_fontes' => 0,
            'pular_ia' => false,
            'atendimento_humano' => false,
            'ia_pausada_ate' => null,
            'menu_opcao' => null,
            'fluxo_estado' => null,
            'intencao' => 'conversa_livre',
            'resumo_prenatal' => '',
            'nome' => null,
            'patient_snapshot' => [],
            'historico_conversa' => [],
            'gestacao_ativa' => false,
            'fora_escopo' => false,
            'escopo' => 'gestante_em_acompanhamento',
        ];

        $normalized = preg_replace('/\D/', '', $telefoneRaw) ?: '';
        if ($normalized === '') {
            return $empty;
        }

        $gestante = $this->findGestanteByTelefone($normalized);
        if ($gestante === null) {
            return $empty;
        }

        return $this->buildForGestante($gestante, $mensagem, $topK, GestanteCanal::CANAL_WHATSAPP);
    }

    /**
     * @return array{
     *   found: bool,
     *   gestante_id: int|null,
     *   gestante_id_publico: string|null,
     *   chunks: list<array{source: string, title: string, text: string, score: float}>,
     *   contexto_texto: string,
     *   total_fontes: int,
     *   pular_ia: bool,
     *   atendimento_humano: bool,
     *   ia_pausada_ate: string|null,
     *   menu_opcao: string|null,
     *   fluxo_estado: string|null,
     *   intencao: string,
     *   resumo_prenatal: string,
     *   nome: string|null,
     *   patient_snapshot: array<string, mixed>,
     *   historico_conversa: list<array{role: string, text: string, at: string|null}>
     * }
     */
    public function buildForGestante(Gestante $gestante, ?string $mensagem = null, int $topK = 6, string $canal = GestanteCanal::CANAL_WHATSAPP): array
    {
        $fluxoEstado = $gestante->whatsapp_fluxo_estado;
        $menuOpcao = $gestante->whatsapp_menu_opcao;
        $modoSintoma = $fluxoEstado === WhatsAppFluxoEstado::CONVERSA_SINTOMA || $menuOpcao === 'sintoma';
        $intencaoLivre = $this->templates->matchIntencaoLivre($mensagem);
        $opcaoMenu = $this->templates->matchMenuOpcao($mensagem) ?: $menuOpcao;
        $usarProntuarioCompleto = in_array($intencaoLivre, ['exames_dados', 'consultas_dados', 'compromissos'], true);
        $modoSintomaAtivo = $modoSintoma || $intencaoLivre === 'sintoma';
        $foraEscopoFimGestacao = $intencaoLivre === 'fim_gestacao';

        $pularIa = $gestante->whatsappIaPausada();
        $ctx = $this->templates->contextFromGestante($gestante);
        $patientSnapshot = $this->montarPatientSnapshot($gestante, $ctx);
        $gestacaoAtiva = $this->inferirGestacaoAtiva($patientSnapshot, $ctx);
        $patientSnapshot['gestacao_ativa'] = $gestacaoAtiva;
        $patientSnapshot['escopo'] = 'gestante_em_acompanhamento';

        $todosChunks = $this->collectChunks($gestante, $canal);
        $ranked = [];
        $totalFontes = count($todosChunks);

        if (! $foraEscopoFimGestacao) {
            if ($usarProntuarioCompleto) {
                $ranked = $this->rankChunks($todosChunks, $mensagem, $topK);
            } elseif ($modoSintomaAtivo || $menuOpcao === null || $menuOpcao === 'exames' || $intencaoLivre === null) {
                // Conversa livre / sintoma: snapshot + últimas consultas/WhatsApp obrigatórios.
                $ranked = $this->garantirContextoConversa($todosChunks, $this->rankChunks($todosChunks, $mensagem, $topK), $topK);
            }
        }

        $linhasChunks = [];
        foreach ($ranked as $i => $c) {
            $n = $i + 1;
            $linhasChunks[] = "[{$n}] ({$c['source']}) {$c['title']}: {$c['text']}";
        }

        $blocoChunks = $linhasChunks === []
            ? ''
            : "Trechos do prontuário / conversa (use apenas se forem relevantes; não invente):\n".implode("\n", $linhasChunks);

        $resumoPrenatal = $this->montarResumoPrenatal($ctx, $patientSnapshot);
        $historicoConversa = $this->montarHistoricoConversaEstruturado($gestante, $canal);
        $historicoTexto = $this->formatarHistoricoConversa($historicoConversa);

        $regras = <<<'TXT'
REGRAS OBRIGATÓRIAS (não viole):
- Este canal é para gestantes em acompanhamento Cardioprenatal. Quem já está cadastrada neste chat deve ser tratada como gestante do serviço (falta de IG no prontuário NÃO encerra o atendimento).
- Tom íntimo e humano; evite frases robóticas repetidas.
- Nunca invente informações da gestante, exames, resultados, consultas, datas, horários, profissionais, locais, passeios ou histórias.
- Nunca afirme que uma informação existe se ela não estiver neste contexto.
- Não indique medicamentos nem faça diagnóstico.
- Se o dado não estiver aqui, diga que a informação não está disponível no sistema e ofereça encaminhamento à equipe.
- Se perguntarem quantas semanas / idade gestacional: use só o valor cadastrado. Se estiver marcado como não cadastrado, diga isso com clareza e oriente a equipe ou a próxima consulta — nunca calcule nem invente semanas.
- Se perguntarem meses de vida / idade da criança na ficha: use só “Meses de vida da criança”. Não confunda com idade gestacional (semanas) nem com idade materna. Se estiver não cadastrado, diga isso com clareza.
- Não complete lacunas com conhecimento médico geral. Não altere dados retornados.
- Há um PatientSnapshot clínico disponível: use-o. Não verbalize classificação de risco de ML de forma alarmista.
- Não repita em toda mensagem o aviso “este canal não substitui consulta” — no máximo uma vez se ainda não foi dito no histórico recente.
TXT;

        $blocoSnapshot = $this->formatarPatientSnapshot($patientSnapshot);

        if ($foraEscopoFimGestacao) {
            $contexto = "FORA DE ESCOPO: a pessoa declarou que não está (mais) grávida.\n"
                ."Resposta curta: acolha, diga que o Cardioprenatal é só para gestantes em acompanhamento, oriente procurar a equipe/unidade de saúde e encerre. Não continue conversa clínica.\n\n"
                .$blocoSnapshot
                ."\n\n".$historicoTexto;
        } elseif ($usarProntuarioCompleto) {
            $contexto = $regras
                ."\n\nA gestante pediu informações de consulta, exame ou compromisso. Use apenas os registros abaixo; não invente datas nem resultados."
                ."\n\n".$blocoSnapshot
                ."\n\n".$resumoPrenatal
                .($blocoChunks !== '' ? "\n\n".$blocoChunks : '')
                ."\n\n".$historicoTexto;
        } elseif ($modoSintomaAtivo) {
            $contexto = $this->templates->instrucoesConversaSintoma()
                ."\n\n".$regras
                ."\n\n".$blocoSnapshot
                ."\n\n".$resumoPrenatal
                .($blocoChunks !== '' ? "\n\n".$blocoChunks : '')
                ."\n\n".$historicoTexto;
        } elseif ($menuOpcao === 'atendente') {
            $contexto = "A gestante pediu falar com atendente. Não responda a dúvida médica; o backend registra a solicitação."
                ."\n\n".$blocoSnapshot
                ."\n\n".$historicoTexto;
        } elseif ($menuOpcao === 'medico') {
            $contexto = "A gestante pediu falar com o médico. Não avalie clinicamente; o backend registra a solicitação."
                ."\n\n".$blocoSnapshot
                ."\n\n".$historicoTexto;
        } else {
            $contexto = "Converse de forma ÍNTIMA e acolhedora com a gestante (como companheira de cuidado do pré-natal), "
                ."SOMENTE no escopo gestação/pré-natal/Cardioprenatal. Use o primeiro nome com naturalidade. "
                ."Mostre que ouviu o que ela disse. Em bate-papo leve (passeio, esporte sem sintoma), responda com carinho em 1–2 frases "
                ."e volte gentilmente ao cuidado (“como você está se sentindo na gestação?”). Não invente locais nem histórias."
                ."\n\n".$regras
                ."\n\n".$blocoSnapshot
                ."\n\n".$resumoPrenatal
                .($blocoChunks !== '' ? "\n\n".$blocoChunks : '')
                ."\n\n".$historicoTexto;
        }

        $intencao = match ($intencaoLivre ?? $opcaoMenu) {
            'atendente' => 'atendimento_humano',
            'medico', 'interpretacao' => 'falar_medico',
            'exames', 'exames_dados', 'consultas_dados', 'compromissos' => 'exames_consultas',
            'sintoma' => 'sintoma',
            'fim_gestacao' => 'fora_escopo',
            default => 'conversa_livre',
        };
        if ($modoSintomaAtivo && $intencao === 'conversa_livre') {
            $intencao = 'sintoma';
        }

        $estadosComRespostaPronta = [
            WhatsAppFluxoEstado::ATENDENTE,
            WhatsAppFluxoEstado::ATENDENTE_CATEGORIA,
            WhatsAppFluxoEstado::EXAMES_CONSULTAS,
            WhatsAppFluxoEstado::MEUS_EXAMES,
            WhatsAppFluxoEstado::MINHAS_CONSULTAS,
            WhatsAppFluxoEstado::PROXIMOS_COMPROMISSOS,
            WhatsAppFluxoEstado::FALAR_COM_MEDICO,
            WhatsAppFluxoEstado::AGUARDANDO_MENSAGEM_MEDICO,
        ];

        return [
            'found' => true,
            'gestante_id' => $gestante->id,
            'gestante_id_publico' => $gestante->gestante_id ?: ('G-'.$gestante->id),
            'chunks' => $ranked,
            'contexto_texto' => $contexto,
            'resumo_prenatal' => $resumoPrenatal,
            'nome' => $ctx['primeiro_nome'] ?: null,
            'total_fontes' => $totalFontes,
            'pular_ia' => $pularIa || (
                ! $modoSintomaAtivo && in_array($fluxoEstado, $estadosComRespostaPronta, true)
            ),
            'atendimento_humano' => $pularIa,
            'ia_pausada_ate' => $pularIa
                ? $gestante->whatsapp_ia_pausada_ate?->toIso8601String()
                : null,
            'menu_opcao' => $menuOpcao,
            'fluxo_estado' => $fluxoEstado,
            'intencao' => $intencao,
            'patient_snapshot' => $patientSnapshot,
            'historico_conversa' => $historicoConversa,
            'gestacao_ativa' => $gestacaoAtiva,
            'fora_escopo' => $foraEscopoFimGestacao,
            'escopo' => 'gestante_em_acompanhamento',
        ];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @param  array<string, mixed>  $ctx
     */
    private function inferirGestacaoAtiva(array $snapshot, array $ctx): bool
    {
        $ig = $snapshot['idade_gestacional'] ?? $ctx['idade_gestacional'] ?? null;
        if ($ig !== null && $ig !== '' && is_numeric($ig) && (float) $ig > 0) {
            return true;
        }

        return filled($snapshot['data_ultima_consulta'] ?? null)
            || filled($ctx['data_ultima_consulta'] ?? null)
            || filled($snapshot['data_agendada'] ?? null)
            || filled($ctx['data_agendada'] ?? null);
    }

    /**
     * Indexa texto livre em chunks (~500–700 chars) para retrieval.
     */
    public function indexDocumentoTexto(
        Gestante $gestante,
        string $titulo,
        string $conteudoTexto,
        string $tipo = 'outro',
        ?string $fonte = 'manual',
        ?string $dataDocumento = null,
    ): GestanteDocumento {
        $doc = GestanteDocumento::query()->create([
            'gestante_id' => $gestante->id,
            'titulo' => Str::limit(trim($titulo), 255, ''),
            'tipo' => $tipo,
            'fonte' => $fonte,
            'data_documento' => $dataDocumento,
            'conteudo_texto' => $conteudoTexto,
            'ativo' => true,
        ]);

        $parts = $this->splitIntoChunks($conteudoTexto, 650, 80);
        foreach ($parts as $i => $part) {
            GestanteDocumentoChunk::query()->create([
                'gestante_documento_id' => $doc->id,
                'gestante_id' => $gestante->id,
                'chunk_index' => $i,
                'conteudo' => $part,
            ]);
        }

        return $doc->load('chunks');
    }

    /**
     * @return list<array{source: string, title: string, text: string, score: float}>
     */
    private function collectChunks(Gestante $gestante, string $canal = GestanteCanal::CANAL_WHATSAPP): array
    {
        $out = [];

        $consultas = $gestante->consultas()
            ->orderByDesc('consulta_numero')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        foreach ($consultas as $c) {
            $bits = $this->textoChunkConsulta($c);
            if ($bits === []) {
                continue;
            }
            $out[] = [
                'source' => 'consulta',
                'title' => 'Consulta #'.($c->consulta_numero ?? $c->id),
                'text' => implode('; ', $bits),
                'score' => 0.0,
            ];
        }

        $analise = AnaliseHistorico::query()
            ->where('gestante_id', $gestante->id)
            ->where('status', 'concluida')
            ->latest('id')
            ->first();

        if ($analise) {
            $bits = array_filter([
                $analise->classificacao_risco ? 'risco '.$analise->classificacao_risco : null,
                $analise->probabilidade_ccf !== null ? 'probabilidade CCF '.$analise->probabilidade_ccf : null,
                $analise->recomenda_ecocardiograma ? 'recomenda ecocardiograma' : null,
                $analise->prioridade_ecocardiograma ? 'prioridade eco '.$analise->prioridade_ecocardiograma : null,
            ]);
            if ($bits !== []) {
                $out[] = [
                    'source' => 'analise',
                    'title' => 'Última análise de risco',
                    'text' => implode('; ', $bits),
                    'score' => 0.0,
                ];
            }
        }

        $interp = InterpretacaoClinica::query()
            ->where('gestante_id', $gestante->id)
            ->whereIn('status', [
                InterpretacaoClinica::STATUS_CONCLUIDA,
                InterpretacaoClinica::STATUS_CACHE_HIT,
            ])
            ->latest('id')
            ->first();

        if ($interp && filled($interp->resumo_gestante)) {
            $out[] = [
                'source' => 'interpretacao',
                'title' => 'Última interpretação (linguagem gestante)',
                'text' => Str::limit((string) $interp->resumo_gestante, 700, '…'),
                'score' => 0.0,
            ];
        }

        $docChunks = GestanteDocumentoChunk::query()
            ->where('gestante_id', $gestante->id)
            ->whereHas('documento', fn ($q) => $q->where('ativo', true))
            ->with('documento:id,titulo,tipo')
            ->orderByDesc('id')
            ->limit(40)
            ->get();

        foreach ($docChunks as $chunk) {
            $out[] = [
                'source' => 'documento:'.($chunk->documento->tipo ?? 'outro'),
                'title' => (string) ($chunk->documento->titulo ?? 'Documento'),
                'text' => Str::limit((string) $chunk->conteudo, 800, '…'),
                'score' => 0.0,
            ];
        }

        $msgs = $this->mensagensDoCanal($gestante, $canal, self::HISTORICO_LIMIT);

        foreach ($msgs as $m) {
            $out[] = [
                'source' => 'whatsapp_'.$m->tipo,
                'title' => 'Mensagem anterior ('.$m->remetenteLabel().')',
                'text' => Str::limit((string) $m->mensagem, self::HISTORICO_TEXT_LIMIT, '…'),
                'score' => 0.0,
            ];
        }

        return $out;
    }

    /**
     * @param  list<array{source: string, title: string, text: string, score: float}>  $chunks
     * @return list<array{source: string, title: string, text: string, score: float}>
     */
    private function rankChunks(array $chunks, ?string $mensagem, int $topK): array
    {
        $tokens = $this->tokenize($mensagem ?? '');
        if ($tokens === [] || $chunks === []) {
            return array_slice($chunks, 0, $topK);
        }

        foreach ($chunks as &$c) {
            $hay = mb_strtolower($c['title'].' '.$c['text'], 'UTF-8');
            $score = 0.0;
            foreach ($tokens as $t) {
                if (str_contains($hay, $t)) {
                    $score += 1.0;
                }
            }
            // leve boost para fontes clínicas estruturadas
            if (str_starts_with($c['source'], 'consulta') || $c['source'] === 'analise') {
                $score += 0.25;
            }
            if (str_starts_with($c['source'], 'whatsapp_')) {
                $score += 0.4;
            }
            if (str_starts_with($c['source'], 'documento')) {
                $score += 0.15;
            }
            $c['score'] = $score;
        }
        unset($c);

        usort($chunks, fn ($a, $b) => $b['score'] <=> $a['score']);

        $filtered = array_values(array_filter($chunks, fn ($c) => $c['score'] > 0));
        if ($filtered === []) {
            return array_slice($chunks, 0, $topK);
        }

        return array_slice($filtered, 0, $topK);
    }

    /**
     * @return list<string>
     */
    private function tokenize(?string $text): array
    {
        if ($text === null || trim($text) === '') {
            return [];
        }

        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text) ?? '';
        $parts = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $stop = [
            'a', 'o', 'e', 'de', 'da', 'do', 'em', 'um', 'uma', 'para', 'com', 'por', 'que',
            'meu', 'minha', 'eu', 'oi', 'ola', 'olá', 'sim', 'nao', 'não', 'por', 'favor',
        ];

        $out = [];
        foreach ($parts as $p) {
            if (mb_strlen($p, 'UTF-8') < 3) {
                continue;
            }
            if (in_array($p, $stop, true)) {
                continue;
            }
            $out[] = $p;
        }

        return array_values(array_unique($out));
    }

    /**
     * @return list<string>
     */
    private function splitIntoChunks(string $text, int $size, int $overlap): array
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
        if ($text === '') {
            return [];
        }

        $len = mb_strlen($text, 'UTF-8');
        if ($len <= $size) {
            return [$text];
        }

        $chunks = [];
        $start = 0;
        while ($start < $len) {
            $chunks[] = mb_substr($text, $start, $size, 'UTF-8');
            $start += max(1, $size - $overlap);
            if (count($chunks) > 80) {
                break;
            }
        }

        return $chunks;
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

        return Gestante::query()->whereIn('telefone', $candidates)->first();
    }

    /**
     * Garante consulta recente e histórico do WhatsApp no contexto da conversa.
     *
     * @param  list<array{source: string, title: string, text: string, score: float}>  $todos
     * @param  list<array{source: string, title: string, text: string, score: float}>  $ranked
     * @return list<array{source: string, title: string, text: string, score: float}>
     */
    private function garantirContextoConversa(array $todos, array $ranked, int $topK): array
    {
        $consultas = array_values(array_filter($todos, fn (array $c): bool => $c['source'] === 'consulta'));
        $whatsapp = array_values(array_filter($todos, fn (array $c): bool => str_starts_with($c['source'], 'whatsapp_')));
        $obrigatorios = array_merge(array_slice($consultas, 0, 2), array_slice($whatsapp, -8));

        $vistos = [];
        $out = [];
        foreach (array_merge($obrigatorios, $ranked) as $c) {
            $chave = $c['source'].'|'.$c['title'].'|'.$c['text'];
            if (isset($vistos[$chave])) {
                continue;
            }
            $vistos[$chave] = true;
            $out[] = $c;
            if (count($out) >= max($topK, count($obrigatorios))) {
                break;
            }
        }

        return $out;
    }

    /**
     * Texto indexável da consulta com todos os campos clínicos úteis ao RAG.
     *
     * @return list<string>
     */
    private function textoChunkConsulta(Consulta $c): array
    {
        $bits = [];
        if ($c->data_consulta) {
            $bits[] = 'data '.$c->data_consulta->format('Y-m-d');
        }
        if (filled($c->registro_planilha)) {
            $bits[] = 'registro planilha '.$c->registro_planilha;
        }

        foreach ($this->catalogoMedidasClinicas() as $campo => $label) {
            $val = $c->{$campo} ?? null;
            if ($val === null || $val === '') {
                continue;
            }
            $bits[] = match ($campo) {
                'idade_gestacional' => 'idade gestacional '.$val.' semanas',
                'idade_crianca_meses' => 'meses de vida '.$val,
                'imc_gestacional_categoria' => 'IMC gestacional '.$this->rotuloImcGestacional($val),
                'paridade' => 'paridade '.$this->rotuloParidade($val),
                default => mb_strtolower($label, 'UTF-8').' '.$val,
            };
        }

        foreach ($this->catalogoFlagsClinicos() as $campo => $label) {
            if (! (bool) ($c->{$campo} ?? false)) {
                continue;
            }
            $bits[] = mb_strtolower($label, 'UTF-8');
        }

        return $bits;
    }

    private function rotuloImcGestacional(mixed $faixa): string
    {
        return match ((int) $faixa) {
            0 => 'normal',
            1 => 'acima de 24,9',
            2 => 'abaixo de 18,5',
            default => (string) $faixa,
        };
    }

    private function rotuloParidade(mixed $paridade): string
    {
        return match ((int) $paridade) {
            0 => 'primigesta',
            1 => '1 filho',
            2 => '2 filhos',
            default => ((int) $paridade >= 3 ? 'mais de 3 filhos' : (string) $paridade),
        };
    }

    /**
     * Campos booleanos da última consulta → sempre sim/não no snapshot.
     *
     * @return array<string, string>
     */
    private function catalogoFlagsClinicos(): array
    {
        return [
            'diabetes_pre_gestacional' => 'Diabetes pré-gestacional',
            'diabetes_gestacional' => 'Diabetes gestacional',
            'hipertensao' => 'Hipertensão',
            'hipertensao_cronica' => 'Hipertensão crônica',
            'hipertensao_pre_eclampsia' => 'Pré-eclâmpsia',
            'eclampsia' => 'Eclâmpsia',
            'obesidade_pre_gestacional' => 'Obesidade pré-gestacional',
            'historico_familiar_chd' => 'Histórico familiar de cardiopatia congênita',
            'historico_natimorto' => 'Histórico de natimorto',
            'historico_filho_anterior_chd' => 'Filho anterior com cardiopatia congênita',
            'uso_medicamentos' => 'Uso de medicamentos',
            'uso_isotretinoina' => 'Uso de isotretinoína',
            'uso_acido_valproico' => 'Uso de ácido valpróico',
            'uso_litio' => 'Uso de lítio',
            'licitas_teratogenicas' => 'Exposição a substâncias lícitas teratogênicas',
            'tabagismo' => 'Tabagismo',
            'alcoolismo' => 'Álcool',
            'drogas_ilicitas' => 'Drogas ilícitas',
            'exposicao_ocupacional' => 'Exposição ocupacional',
            'lupus_eritomatoso_sistemico' => 'Lúpus',
            'sindrome_sjogren' => 'Síndrome de Sjögren',
            'fenilcetonuria' => 'Fenilcetonúria',
            'doencas_tireoidianas' => 'Doenças tireoidianas',
            'hipotireoidismo' => 'Hipotireoidismo',
            'hipertireoidismo' => 'Hipertireoidismo',
            'rubeola' => 'Rubéola',
            'citomegalovirus' => 'Citomegalovírus',
            'toxoplasmose' => 'Toxoplasmose',
            'sifilis' => 'Sífilis',
            'infeccao_materna_bacteriana' => 'Infecção materna bacteriana',
            'usg_precoce_confirmada' => 'USG precoce confirmada',
            'prematuridade' => 'Prematuridade',
            'translucencia_nucal_aumentada' => 'Translucência nucal aumentada',
            'regurgitacao_tricuspide_fetal' => 'Regurgitação tricúspide fetal',
            'malformacoes_extracardiacas_associadas' => 'Malformações extracardíacas',
            'crescimento_fetal_rcf_iugr' => 'Restrição de crescimento fetal (RCF/IUGR)',
            'chd_confirmada' => 'Cardiopatia congênita confirmada',
        ];
    }

    /**
     * Medidas / textos da última consulta (só entram se preenchidos).
     *
     * @return array<string, string>
     */
    private function catalogoMedidasClinicas(): array
    {
        return [
            'consulta_numero' => 'Número da consulta',
            'idade_materna' => 'Idade materna',
            'idade_gestacional' => 'Idade gestacional (semanas)',
            'idade_crianca_meses' => 'Meses de vida da criança',
            'etnia' => 'Etnia',
            'paridade' => 'Paridade',
            'abortamentos_previos' => 'Abortamentos prévios',
            'imc_pre_gestacional' => 'IMC pré-gestacional',
            'imc_gestacional_categoria' => 'Categoria IMC gestacional',
            'registro_planilha' => 'Registro da planilha',
            'pressao_sistolica' => 'PA sistólica',
            'bpm_materno' => 'FC materna',
            'saturacao' => 'Saturação',
            'temperatura_corporal' => 'Temperatura corporal',
            'altura' => 'Altura',
            'peso' => 'Peso',
            'glicemia_jejum' => 'Glicemia jejum',
            'glicemia_pos_prandial' => 'Glicemia pós-prandial',
            'hba1c' => 'HbA1c',
            'tipo_gestacao' => 'Tipo de gestação',
            'corionicidade' => 'Corionicidade',
            'frequencia_cardiaca_fetal' => 'FC fetal',
            'circunferencia_cefalica_fetal_mm' => 'Circunferência cefálica fetal (mm)',
            'circunferencia_abdominal_mm' => 'Circunferência abdominal (mm)',
            'comprimento_femur_mm' => 'Comprimento do fêmur (mm)',
            'translucencia_nucal_mm' => 'Translucência nucal (mm)',
            'doppler_ducto_venoso' => 'Doppler ducto venoso',
            'polidramnio_oligoidramnio' => 'Polidrâmnio/oligoidrâmnio',
            'eixo_cardiaco' => 'Eixo cardíaco',
            'quatro_camaras' => 'Quatro câmaras',
            'tipo_chd' => 'Tipo de cardiopatia congênita',
        ];
    }

    /**
     * @param  array<string, mixed>  $ctx
     * @return array<string, mixed>
     */
    private function montarPatientSnapshot(Gestante $gestante, array $ctx): array
    {
        $consulta = $gestante->consultas()
            ->orderByDesc('consulta_numero')
            ->orderByDesc('id')
            ->first();

        $analise = AnaliseHistorico::query()
            ->where('gestante_id', $gestante->id)
            ->where('status', 'concluida')
            ->latest('id')
            ->first();

        $interp = InterpretacaoClinica::query()
            ->where('gestante_id', $gestante->id)
            ->whereIn('status', [
                InterpretacaoClinica::STATUS_CONCLUIDA,
                InterpretacaoClinica::STATUS_CACHE_HIT,
            ])
            ->latest('id')
            ->first();

        $analiseLinha = null;
        if ($analise) {
            $bits = array_filter([
                $analise->classificacao_risco ? 'risco '.$analise->classificacao_risco : null,
                $analise->recomenda_ecocardiograma ? 'eco recomendada' : null,
                $analise->prioridade_ecocardiograma ? 'prioridade '.$analise->prioridade_ecocardiograma : null,
            ]);
            $analiseLinha = $bits !== [] ? implode('; ', $bits) : null;
        }

        $snapshot = [
            'primeiro_nome' => $ctx['primeiro_nome'] ?: null,
            'data_ultima_consulta' => $ctx['data_ultima_consulta'] ?? ($consulta?->data_consulta?->format('d/m/Y')),
            'data_agendada' => $ctx['data_agendada'] ?? null,
            'analise_resumo' => $analiseLinha,
            'interpretacao_resumo' => $interp && filled($interp->resumo_gestante)
                ? Str::limit((string) $interp->resumo_gestante, self::INTERPRETACAO_LIMIT, '…')
                : null,
        ];

        if ($consulta === null) {
            $snapshot['idade_gestacional'] = $ctx['idade_gestacional'] ?? null;
            $snapshot['idade_crianca_meses'] = $ctx['idade_crianca_meses'] ?? null;
            $snapshot['pressao_sistolica'] = $ctx['pressao_sistolica'] ?? null;
            $snapshot['bpm_materno'] = $ctx['bpm_materno'] ?? null;
            $snapshot['peso'] = $ctx['peso'] ?? null;
            $snapshot['saturacao'] = $ctx['saturacao'] ?? null;

            return $snapshot;
        }

        foreach ($this->catalogoMedidasClinicas() as $campo => $_label) {
            $val = $consulta->{$campo} ?? null;
            if ($val === null || $val === '') {
                // Preferências do contextFromGestante quando a consulta não tem o valor
                if ($campo === 'idade_gestacional' && ! empty($ctx['idade_gestacional'])) {
                    $snapshot[$campo] = $ctx['idade_gestacional'];
                } elseif ($campo === 'idade_crianca_meses' && isset($ctx['idade_crianca_meses']) && $ctx['idade_crianca_meses'] !== '') {
                    $snapshot[$campo] = $ctx['idade_crianca_meses'];
                } elseif ($campo === 'pressao_sistolica' && isset($ctx['pressao_sistolica'])) {
                    $snapshot[$campo] = $ctx['pressao_sistolica'];
                } elseif ($campo === 'bpm_materno' && isset($ctx['bpm_materno'])) {
                    $snapshot[$campo] = $ctx['bpm_materno'];
                } elseif ($campo === 'peso' && isset($ctx['peso'])) {
                    $snapshot[$campo] = $ctx['peso'];
                } elseif ($campo === 'saturacao' && isset($ctx['saturacao'])) {
                    $snapshot[$campo] = $ctx['saturacao'];
                }
                continue;
            }
            $snapshot[$campo] = $val;
        }

        foreach (array_keys($this->catalogoFlagsClinicos()) as $campo) {
            $snapshot[$campo] = (bool) ($consulta->{$campo} ?? false);
        }

        return $snapshot;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function formatarPatientSnapshot(array $snapshot): string
    {
        $linhas = ['PatientSnapshot (fonte Laravel — use quando relevante; não invente campos ausentes):'];
        $temDado = false;

        $cabecalho = [
            'primeiro_nome' => 'Primeiro nome',
            'data_ultima_consulta' => 'Data da última consulta',
            'data_agendada' => 'Compromisso agendado',
        ];
        foreach ($cabecalho as $key => $label) {
            $val = $snapshot[$key] ?? null;
            if ($val === null || $val === '') {
                continue;
            }
            $temDado = true;
            $linhas[] = '- '.$label.': '.$val;
        }

        $linhas[] = 'Medidas / dados da última consulta:';
        $temMedida = false;
        foreach ($this->catalogoMedidasClinicas() as $key => $label) {
            if (! array_key_exists($key, $snapshot) && ! in_array($key, ['idade_gestacional', 'idade_crianca_meses'], true)) {
                continue;
            }
            $val = $snapshot[$key] ?? null;
            if ($key === 'idade_gestacional') {
                $temDado = true;
                $temMedida = true;
                if ($val === null || $val === '') {
                    $linhas[] = '- '.$label.': não cadastrada no sistema';
                } else {
                    $linhas[] = '- '.$label.': '.$val.' semanas';
                }

                continue;
            }
            if ($key === 'idade_crianca_meses') {
                $temDado = true;
                $temMedida = true;
                if ($val === null || $val === '') {
                    $linhas[] = '- '.$label.': não cadastrado no sistema';
                } else {
                    $linhas[] = '- '.$label.': '.$val.' meses';
                }

                continue;
            }
            if ($val === null || $val === '') {
                continue;
            }
            $temDado = true;
            $temMedida = true;
            if ($key === 'imc_gestacional_categoria') {
                $linhas[] = '- '.$label.': '.$this->rotuloImcGestacional($val);
            } elseif ($key === 'paridade') {
                $linhas[] = '- '.$label.': '.$this->rotuloParidade($val);
            } else {
                $linhas[] = '- '.$label.': '.$val;
            }
        }
        if (! $temMedida) {
            $linhas[] = '- (sem medidas preenchidas)';
        }

        $linhas[] = 'Condições / fatores marcados no cadastro (sim/não):';
        $temFlag = false;
        foreach ($this->catalogoFlagsClinicos() as $key => $label) {
            if (! array_key_exists($key, $snapshot) || $snapshot[$key] === null) {
                continue;
            }
            $temDado = true;
            $temFlag = true;
            $linhas[] = '- '.$label.': '.($snapshot[$key] ? 'sim' : 'não');
        }
        if (! $temFlag) {
            $linhas[] = '- (sem consulta cadastrada para flags)';
        }

        if (! empty($snapshot['analise_resumo'])) {
            $temDado = true;
            $linhas[] = '- Última análise ML (não alarmar a gestante): '.$snapshot['analise_resumo'];
        }
        if (! empty($snapshot['interpretacao_resumo'])) {
            $temDado = true;
            $linhas[] = '- Resumo da última interpretação: '.$snapshot['interpretacao_resumo'];
        }

        if (! $temDado) {
            return 'PatientSnapshot: sem dados clínicos cadastrados além do histórico de mensagens.';
        }

        $linhas[] = 'Se perguntarem se tem hipertensão, diabetes, lúpus etc.: responda só com o sim/não do cadastro acima e oriente a equipe se precisar confirmar.';

        return implode("\n", $linhas);
    }

    /**
     * @return list<array{role: string, text: string, at: string|null}>
     */
    private function montarHistoricoConversaEstruturado(Gestante $gestante, string $canal = GestanteCanal::CANAL_WHATSAPP): array
    {
        $msgs = $this->mensagensDoCanal($gestante, $canal, self::HISTORICO_LIMIT);
        $out = [];

        foreach ($msgs as $m) {
            $role = match ($m->remetente()) {
                GestanteWhatsapp::REMETENTE_PACIENTE => 'user',
                GestanteWhatsapp::REMETENTE_SISTEMA => 'assistant',
                default => 'staff',
            };

            $out[] = [
                'role' => $role,
                'text' => Str::limit((string) $m->mensagem, self::HISTORICO_TEXT_LIMIT, '…'),
                'at' => $m->created_at?->toIso8601String(),
            ];
        }

        return $out;
    }

    /**
     * @param  list<array{role: string, text: string, at: string|null}>  $historico
     */
    private function formatarHistoricoConversa(array $historico): string
    {
        if ($historico === []) {
            return 'Ainda não há histórico de mensagens desta conversa.';
        }

        $linhas = ['Histórico recente desta conversa (Laravel gestante_whatsapp — não invente falas):'];
        foreach ($historico as $item) {
            $quem = match ($item['role']) {
                'user' => 'Gestante',
                'staff' => 'Equipe',
                default => 'Assistente',
            };
            $linhas[] = '- '.$quem.': '.$item['text'];
        }

        return implode("\n", $linhas);
    }

    /**
     * @return \Illuminate\Support\Collection<int, GestanteWhatsapp>
     */
    private function mensagensDoCanal(Gestante $gestante, string $canal, int $limit)
    {
        return GestanteWhatsapp::query()
            ->where('gestante_id', $gestante->id)
            ->where(function ($q) use ($canal) {
                $q->where('canal', $canal);
                if ($canal === GestanteCanal::CANAL_WHATSAPP) {
                    $q->orWhereNull('canal');
                }
            })
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * @param  array<string, mixed>  $ctx
     * @param  array<string, mixed>  $snapshot
     */
    private function montarResumoPrenatal(array $ctx, array $snapshot = []): string
    {
        $linhas = ['Dados do acompanhamento (só o que está cadastrado):'];
        $nome = trim((string) ($ctx['primeiro_nome'] ?? $snapshot['primeiro_nome'] ?? ''));
        if ($nome !== '') {
            $linhas[] = '- Primeiro nome: '.$nome;
        }
        $ig = $ctx['idade_gestacional'] ?? $snapshot['idade_gestacional'] ?? null;
        if ($ig !== null && $ig !== '' && is_numeric($ig)) {
            $linhas[] = '- Idade gestacional na última consulta: '.$ig.' semanas';
        } else {
            $linhas[] = '- Idade gestacional na última consulta: não cadastrada no sistema';
        }
        $meses = $ctx['idade_crianca_meses'] ?? $snapshot['idade_crianca_meses'] ?? null;
        if ($meses !== null && $meses !== '' && is_numeric($meses)) {
            $linhas[] = '- Meses de vida da criança na última consulta: '.$meses.' meses';
        } else {
            $linhas[] = '- Meses de vida da criança na última consulta: não cadastrado no sistema';
        }
        $dataConsulta = $ctx['data_ultima_consulta'] ?? $snapshot['data_ultima_consulta'] ?? null;
        if (! empty($dataConsulta)) {
            $linhas[] = '- Data da última consulta: '.$dataConsulta;
        }
        $dataAgendada = $ctx['data_agendada'] ?? $snapshot['data_agendada'] ?? null;
        if (! empty($dataAgendada)) {
            $linhas[] = '- Compromisso agendado: '.$dataAgendada;
        }
        $pa = $ctx['pressao_sistolica'] ?? $snapshot['pressao_sistolica'] ?? null;
        if ($pa !== null && $pa !== '') {
            $linhas[] = '- Pressão sistólica na última consulta: '.$pa;
        }
        $fc = $ctx['bpm_materno'] ?? $snapshot['bpm_materno'] ?? null;
        if ($fc !== null && $fc !== '') {
            $linhas[] = '- Frequência cardíaca materna na última consulta: '.$fc;
        }
        $peso = $ctx['peso'] ?? $snapshot['peso'] ?? null;
        if ($peso !== null && $peso !== '') {
            $linhas[] = '- Peso na última consulta: '.$peso;
        }
        $sat = $ctx['saturacao'] ?? $snapshot['saturacao'] ?? null;
        if ($sat !== null && $sat !== '') {
            $linhas[] = '- Saturação na última consulta: '.$sat;
        }
        if (count($linhas) === 1) {
            return 'Não há resumo de consulta cadastrado além dos trechos abaixo.';
        }

        if (array_key_exists('hipertensao', $snapshot) && $snapshot['hipertensao'] !== null) {
            $linhas[] = '- Hipertensão no cadastro: '.($snapshot['hipertensao'] ? 'sim' : 'não');
        }
        if (array_key_exists('diabetes_gestacional', $snapshot) && $snapshot['diabetes_gestacional'] !== null) {
            $linhas[] = '- Diabetes gestacional no cadastro: '.($snapshot['diabetes_gestacional'] ? 'sim' : 'não');
        }

        $linhas[] = 'Não mencione classificação de risco de modelo de ML de forma alarmista. Não invente campos ausentes.';
        $linhas[] = 'Se perguntarem se é hipertensa/diabética: responda só com o que está marcado no cadastro (sim/não) e oriente a equipe se precisar confirmar.';

        return implode("\n", $linhas);
    }
}
