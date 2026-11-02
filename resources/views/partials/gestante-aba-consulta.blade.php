@php
    /** @var \App\Models\Gestante $gestante */
    /** @var \App\Models\Consulta|null $consultaAtual */
    /** @var \Illuminate\Support\Collection $historicoConsultas */
    $consultaAtual = $consultaAtual ?? $gestante->consultas->first();
    $historicoConsultas = $historicoConsultas ?? collect();
    $examesDocumentos = $gestante->documentos ?? collect();
    $podeConsultas = $podeConsultas ?? false;
    $podeAnalises = $podeAnalises ?? false;
    $podeVerAgenda = $podeVerAgenda ?? false;
    $podeAcompanhamentos = $podeAcompanhamentos ?? false;
    $podeDirecionarAtendimento = $podeDirecionarAtendimento ?? false;
    $podeGerenciarGestantes = $podeGerenciarGestantes ?? false;
    $atendimentoPersonalizadoLiberado = $atendimentoPersonalizadoLiberado ?? false;
    $encaminhadaParaFilaEco = $encaminhadaParaFilaEco ?? false;
    $temGraficosIa = $temGraficosIa ?? false;

    $rotuloParidade = function ($consulta): string {
        return match ((int) $consulta->paridade) {
            0 => 'Primigesta',
            1 => '1 filho',
            2 => '2 filhos',
            3 => 'Mais de 3 filhos',
            default => 'Não informado',
        };
    };
    $rotuloLiquido = function ($consulta): string {
        return match (strtolower((string) $consulta->polidramnio_oligoidramnio)) {
            'polidramnio' => 'Polidrâmnio',
            'oligodramnio' => 'Oligodrâmnio',
            default => 'Sem alteração',
        };
    };
    $rotuloImc = function ($consulta): string {
        if ($consulta->imc_gestacional_categoria === null) {
            return 'Não informado';
        }

        return match ((int) $consulta->imc_gestacional_categoria) {
            0 => 'Normal',
            1 => 'Acima de 24,9',
            2 => 'Abaixo de 18,5',
            default => 'Não informado',
        };
    };
    $simNao = fn ($valor): string => $valor ? 'Sim' : 'Não';
    $camposConsulta = $consultaAtual ? [
        ['Data', $consultaAtual->data_consulta ? $consultaAtual->data_consulta->format('d/m/Y') : 'Não informado'],
        ['Meses de vida', $consultaAtual->idade_crianca_meses !== null ? (string) (int) $consultaAtual->idade_crianca_meses : 'Não informado'],
        ['Cardiopatia congênita confirmada', $simNao($consultaAtual->chd_confirmada)],
        ['Etnia', $consultaAtual->etnia ?: 'Não informado'],
        ['Paridade', $consultaAtual->paridade !== null ? $rotuloParidade($consultaAtual) : 'Não informado'],
        ['IMC gestacional', $rotuloImc($consultaAtual)],
        ['Abortamentos prévios', $consultaAtual->abortamentos_previos !== null ? (string) (int) $consultaAtual->abortamentos_previos : 'Não informado'],
        ['Gestação', strtolower((string) $consultaAtual->tipo_gestacao) === 'gemelar' ? 'Gemelar' : 'Única'],
        ['Líquido amniótico', $rotuloLiquido($consultaAtual)],
        ['Prematuridade', $simNao($consultaAtual->prematuridade)],
        ['Cardiopatia em primeiro grau', $simNao($consultaAtual->historico_familiar_chd)],
        ['Diabetes gestacional', $simNao($consultaAtual->diabetes_gestacional)],
        ['Diabetes pré-gestacional', $simNao($consultaAtual->diabetes_pre_gestacional)],
        ['Lúpus', $simNao($consultaAtual->lupus_eritomatoso_sistemico)],
        ['Síndrome de Sjögren', $simNao($consultaAtual->sindrome_sjogren)],
        ['Doença da tireoide', $simNao($consultaAtual->doencas_tireoidianas)],
        ['Hipertensão crônica', $simNao($consultaAtual->hipertensao_cronica)],
        ['Pré-eclâmpsia', $simNao($consultaAtual->hipertensao_pre_eclampsia)],
        ['Eclâmpsia', $simNao($consultaAtual->eclampsia)],
        ['Drogas ilícitas', $simNao($consultaAtual->drogas_ilicitas)],
        ['Lícitas teratogênicas', $simNao($consultaAtual->licitas_teratogenicas)],
        ['Infecção bacteriana', $simNao($consultaAtual->infeccao_materna_bacteriana)],
    ] : [];

    $positivosClinicos = [];
    if ($consultaAtual) {
        $mapa = [
            'prematuridade' => 'Prematuridade',
            'historico_familiar_chd' => 'Cardiopatia em 1º grau',
            'diabetes_gestacional' => 'Diabetes gestacional',
            'diabetes_pre_gestacional' => 'Diabetes prévio',
            'lupus_eritomatoso_sistemico' => 'Lúpus',
            'sindrome_sjogren' => 'Síndrome de Sjögren',
            'doencas_tireoidianas' => 'Doença da tireoide',
            'hipotireoidismo' => 'Hipotireoidismo',
            'hipertireoidismo' => 'Hipertireoidismo',
            'hipertensao_cronica' => 'HAS',
            'hipertensao_pre_eclampsia' => 'Pré-eclâmpsia',
            'eclampsia' => 'Eclâmpsia',
            'drogas_ilicitas' => 'Drogas ilícitas',
            'licitas_teratogenicas' => 'Lícitas teratogênicas',
            'infeccao_materna_bacteriana' => 'Infecção bacteriana',
        ];
        foreach ($mapa as $campo => $label) {
            if ($consultaAtual->{$campo}) {
                $positivosClinicos[] = $label;
            }
        }
    }

    $observacoes = [];
    if ($podeAcompanhamentos) {
        foreach ($gestante->acompanhamentos ?? collect() as $acomp) {
            $nota = trim((string) $acomp->observacao);
            if ($nota !== '') {
                $observacoes[] = $acomp->titulo ? $acomp->titulo.': '.$nota : $nota;
            }
        }
    }
@endphp

<nav class="gestante-area-nav" aria-label="Seções da consulta">
    <a href="#ficha-dados">Dados clínicos</a>
    <a href="#ficha-consulta-atual">Consulta atual</a>
    <a href="#ficha-historico">Histórico</a>
    @if ($examesDocumentos->isNotEmpty())
        <a href="#ficha-exames">Exames</a>
    @endif
    @if ($podeAnalises)
        <a href="#ficha-analises">Análises</a>
    @endif
    @if ($observacoes !== [])
        <a href="#ficha-observacoes">Observações</a>
    @endif
    <a href="#ficha-acoes">Ações</a>
</nav>

<section class="gestante-area" id="ficha-dados">
    <p class="gestante-area-kicker">Dados clínicos da gestante</p>
    <div class="main-card">
        <h2 class="card-title" style="font-size: 20px; margin: 0 0 16px;">Identificação e resumo clínico</h2>
        <div class="form-field-grid form-field-grid--2">
            <div class="form-field" style="margin-bottom: 0;">
                <span class="form-label">Nome</span>
                <p class="consulta-valor">{{ $gestante->nome_exibicao }}</p>
            </div>
            <div class="form-field" style="margin-bottom: 0;">
                <span class="form-label">Cadastro</span>
                <p class="consulta-valor">#{{ $gestante->id }}</p>
            </div>
            <div class="form-field" style="margin-bottom: 0;">
                <span class="form-label">Registro da planilha</span>
                <p class="consulta-valor">{{ $consultaAtual?->registro_planilha ?: $gestante->gestante_id }}</p>
            </div>
            <div class="form-field" style="margin-bottom: 0;">
                <span class="form-label">Consultas registradas</span>
                <p class="consulta-valor">{{ $gestante->consultas->count() }}</p>
            </div>
        </div>
        @if ($positivosClinicos !== [])
            <div style="margin-top: 18px;">
                <span class="form-label">Antecedentes e comorbidades ativos</span>
                <p class="consulta-valor" style="font-weight: 500; line-height: 1.5;">{{ implode(' · ', $positivosClinicos) }}</p>
            </div>
        @endif
        @if ($podeAnalises && $gestante->ultimaAnalise && $gestante->ultimaAnalise->status === 'concluida')
            @php $ua = $gestante->ultimaAnalise; @endphp
            <p style="margin: 16px 0 0; font-size: 14px; color: var(--muted);">
                {{ \App\Services\DashboardAnaliseService::textoEscoreModelo($ua->probabilidade_ccf) }}.
                Este número descreve a ficha no modelo da planilha. Não é a chance de cardiopatia e não define a fila do ecocardiograma.
            </p>
        @endif
    </div>
</section>

<section class="gestante-area" id="ficha-consulta-atual">
    <p class="gestante-area-kicker">Consulta atual</p>
    @if ($consultaAtual)
        <div class="main-card">
            <div class="consulta-resumo-head">
                <div>
                    <h2 class="card-title" style="font-size: 22px; margin: 0;">Registro {{ $consultaAtual->registro_planilha ?: $consultaAtual->consulta_numero }}</h2>
                    <p style="color: var(--muted); font-size: 14px; margin-top: 10px; line-height: 1.5;">
                        Meses de vida: {{ $consultaAtual->idade_crianca_meses !== null ? (int) $consultaAtual->idade_crianca_meses : 'Não informado' }}
                    </p>
                </div>
                <span class="consulta-pill {{ $consultaAtual->chd_confirmada ? 'consulta-pill--alert' : 'consulta-pill--ok' }}">
                    Cardiopatia congênita {{ $consultaAtual->chd_confirmada ? 'confirmada' : 'não confirmada' }}
                </span>
            </div>

            <div class="form-section" style="margin-top: 16px;">
                <h3 class="form-section-title">Dados da consulta</h3>
                <div class="form-field-grid form-field-grid--2">
                    @foreach ($camposConsulta as $campo)
                        <div class="form-field" style="margin-bottom: 0;">
                            <span class="form-label">{{ $campo[0] }}</span>
                            <p class="consulta-valor">{{ $campo[1] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @elseif ($podeConsultas)
        <div class="main-card">
            <h2 class="card-title" style="font-size: 20px; margin: 0 0 8px;">Registrar consulta</h2>
            <p class="page-subtitle" style="margin: 0 0 20px;">Esta tela é o fluxo da consulta. Preencha e salve para iniciar o acompanhamento clínico.</p>
            @if ($errors->any())
                <div class="form-alert-error">
                    <strong>Verifique os campos abaixo:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form action="{{ route('consultas.store', ['id' => $gestante->id]) }}" method="POST">
                @csrf
                @include('consultas.partials.fields', ['consulta' => null])
                <div class="form-actions" style="margin-top: 22px;">
                    <div class="form-actions-end">
                        <button type="submit" class="btn-primary-custom">Salvar consulta</button>
                    </div>
                </div>
            </form>
        </div>
    @else
        <div class="main-card" style="text-align: center; color: var(--muted); padding: 40px 24px;">
            Nenhuma consulta registrada.
        </div>
    @endif
</section>

<section class="gestante-area" id="ficha-historico">
    <p class="gestante-area-kicker">Histórico de consultas</p>
    <div class="main-card">
        <h2 class="card-title" style="font-size: 20px; margin: 0 0 16px;">Consultas anteriores</h2>
        @if ($historicoConsultas->isEmpty())
            <p class="dash-empty" style="text-align:left; padding: 8px 0; margin: 0;">
                {{ $consultaAtual ? 'Não há consultas anteriores além da atual.' : 'Nenhuma consulta no histórico.' }}
            </p>
        @else
            <p class="table-scroll-hint">↔ Deslize horizontalmente para ver todas as colunas.</p>
            <div class="table-container table-container--flush">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="td-nowrap">Registro</th>
                            <th class="td-nowrap">Meses de vida</th>
                            <th>Cardiopatia congênita</th>
                            @if ($podeConsultas)
                                <th class="td-actions">Ação</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($historicoConsultas as $consulta)
                            <tr>
                                <td class="td-nowrap">{{ $consulta->registro_planilha ?: $consulta->consulta_numero }}</td>
                                <td class="td-nowrap">{{ $consulta->idade_crianca_meses !== null ? (int) $consulta->idade_crianca_meses : 'Não informado' }}</td>
                                <td>{{ $consulta->chd_confirmada ? 'Confirmada' : 'Não confirmada' }}</td>
                                @if ($podeConsultas)
                                    <td class="td-actions">
                                        <a href="{{ route('consultas.edit', $consulta->id) }}" class="btn-table btn-table--secondary">Editar</a>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>

@if ($examesDocumentos->isNotEmpty())
<section class="gestante-area" id="ficha-exames">
    <p class="gestante-area-kicker">Exames</p>
    <div class="main-card">
        <h2 class="card-title" style="font-size: 20px; margin: 0 0 16px;">Documentos</h2>
            <div class="table-container table-container--flush">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Exame</th>
                            <th class="td-date">Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($examesDocumentos as $doc)
                            <tr>
                                <td>{{ $doc->titulo ?: 'Exame' }}</td>
                                <td class="td-date">{{ $doc->data_documento ? $doc->data_documento->format('d/m/Y') : 'Não informado' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
    </div>
</section>
@endif

@if ($podeAnalises)
<section class="gestante-area" id="ficha-analises">
    <p class="gestante-area-kicker">Análises / risco</p>
    @include('partials.interpretacao-clinica', [
        'interpretacao' => $gestante->ultimaInterpretacao,
    ])
    @include('partials.analise-historico-timeline', [
        'analises' => $gestante->analisesHistorico,
        'gestante' => $gestante,
    ])
    @if ($temGraficosIa)
        @php $resultado_ia = session('resultado_analise_ia'); @endphp
        <div class="main-card">
            <h2 class="card-title" style="font-size: 20px; margin-bottom: 16px;">Gráficos da análise</h2>
            <div class="form-page-grid form-page-grid--3">
                @foreach ($resultado_ia['imagens'] as $titulo => $imagem_base64)
                    <div class="form-section" style="padding: 18px;">
                        <h3 class="form-section-title" style="font-size: 16px; margin-bottom: 12px;">{{ str_replace('_', ' ', $titulo) }}</h3>
                        <img src="{{ $imagem_base64 }}" alt="Gráfico de {{ $titulo }}" style="width: 100%; height: auto; border-radius: 12px;">
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</section>
@endif

@if ($observacoes !== [])
<section class="gestante-area" id="ficha-observacoes">
    <p class="gestante-area-kicker">Observações</p>
    <div class="main-card">
        <h2 class="card-title" style="font-size: 20px; margin: 0 0 16px;">Notas clínicas</h2>
        <div style="display: grid; gap: 12px;">
            @foreach ($observacoes as $nota)
                <p style="margin: 0; font-size: 14px; line-height: 1.55; white-space: pre-wrap;">{{ $nota }}</p>
            @endforeach
        </div>
    </div>
</section>
@endif

<section class="gestante-area" id="ficha-acoes">
    <p class="gestante-area-kicker">Ações da consulta</p>
    <div class="main-card">
        <h2 class="card-title" style="font-size: 20px; margin: 0 0 16px;">Ações relacionadas à consulta</h2>
        <p class="consulta-acoes-status">
            Status:
            <strong>{{ $gestante->labelFluxoAtendimento() }}</strong>
        </p>
        @if ($podeDirecionarAtendimento)
            <div class="consulta-check-list">
                @include('partials.consulta-acao-check', [
                    'done' => $atendimentoPersonalizadoLiberado,
                    'enabled' => (bool) $consultaAtual,
                    'action' => route('gestantes.atendimento-personalizado', $gestante),
                    'title' => 'Aceitar atendimento personalizado',
                    'hint' => 'Permitir acompanhamento personalizado desta gestante.',
                    'hintDone' => 'Atendimento personalizado autorizado.',
                ])
                @include('partials.consulta-acao-check', [
                    'done' => $encaminhadaParaFilaEco,
                    'enabled' => (bool) $consultaAtual,
                    'action' => route('gestantes.encaminhar-ecocardiograma', $gestante),
                    'title' => 'Encaminhar para fila do ecocardiograma',
                    'hint' => 'Adicionar a gestante à fila de acompanhamento do exame.',
                    'hintDone' => 'Gestante encaminhada para a fila do ecocardiograma.',
                ])
            </div>
            @if (! $consultaAtual)
                <p style="margin: 4px 0 0; font-size: 13px; color: var(--muted);">Registre a consulta para liberar o aceite e o encaminhamento.</p>
            @endif
        @endif

        @if (($podeConsultas && $consultaAtual) || $podeGerenciarGestantes)
            <div class="consulta-outras-acoes">
                <h3>Outras ações</h3>
                <div class="consulta-outras-acoes-list">
                    @if ($podeConsultas && $consultaAtual)
                        <a href="{{ route('consultas.edit', $consultaAtual->id) }}" class="consulta-acao-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
                            Editar consulta atual
                        </a>
                        <a href="{{ route('consultas.create', ['id' => $gestante->id]) }}" class="consulta-acao-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M12 14v4M10 16h4"/></svg>
                            Registrar próxima consulta
                        </a>
                    @endif
                    @if ($podeGerenciarGestantes)
                        <a href="{{ route('gestantes.edit', $gestante) }}" class="consulta-acao-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            Editar cadastro
                        </a>
                    @endif
                </div>
            </div>
        @endif
    </div>
</section>

@if ($podeVerAgenda)
    @include('partials.gestante-consultas-agendadas', ['gestante' => $gestante])
@endif
