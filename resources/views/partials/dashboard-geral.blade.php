@php
    use App\Services\DashboardAnaliseService;

    $rotulosGrupo = [
        'socio' => 'Sociodemográficas',
        'antropo' => 'Antropométricas',
        'clinicas' => 'Clínicas',
        'obstetricas' => 'Obstétricas',
    ];
    $secoesNav = [
        ['href' => '#indicadores', 'label' => 'Visão geral'],
        ['href' => '#cobertura', 'label' => 'Cobertura'],
        ['href' => '#graficos', 'label' => 'Variáveis'],
    ];
    foreach ($gruposVariaveis as $chaveNav => $grupo) {
        $temGrupo = ($grupo['graficos'] ?? []) !== []
            || ($grupo['figuras'] ?? []) !== []
            || is_array($leituraDescritiva['grupos'][$chaveNav] ?? null);
        if (! $temGrupo) {
            continue;
        }
        $secoesNav[] = [
            'href' => '#'.$grupo['id'],
            'label' => $rotulosGrupo[$grupo['id']] ?? $grupo['titulo'],
        ];
    }
    if (($gdOutros ?? []) !== []) {
        $secoesNav[] = ['href' => '#variaveis-extras', 'label' => 'Outras variáveis'];
    }
    $secoesNav[] = ['href' => '#analises', 'label' => 'Modelos'];
    $comoLer = [
        'prevalencia' => 'CCF é a cardiopatia congênita confirmada na ficha. O anel compara quem teve o desfecho com o restante da planilha.',
        'etnia' => 'Cada barra é um grupo de etnia. A mais longa é o grupo com mais fichas. A soma das barras é o total da base.',
        'paridade' => 'Quantos filhos a gestante já tinha. Primigesta é quem ainda não tinha filho. A soma das barras é o total da base.',
        'idade_crianca' => 'Meses de vida da criança quando a ficha foi registrada. Não é a idade da mãe nem a idade da gravidez.',
        'imc' => 'Faixa de IMC na gestação, do jeito que veio na planilha: normal, acima de 24,9 ou abaixo de 18,5.',
        'fatores_risco' => 'Quantas fichas marcaram cada condição. A mesma gestante pode aparecer em mais de uma barra, então as barras não se somam.',
        'obstetricos' => 'Quantas fichas têm cada marcador da gestação. A mesma ficha pode entrar em mais de um marcador.',
        'casos_ccf' => 'Entre as cardiopatias confirmadas, quantas também tinham aquele fator. Um caso pode contar em mais de uma barra.',
    ];
@endphp

<div class="dash-perfil-fixo">
    <header class="dash-hero">
        <div class="dash-hero-top">
            <div>
                <p class="dash-brand">@include('partials.brand-logo', ['variant' => 'dash'])</p>
                <h1>Perfil da população</h1>
                <p class="page-subtitle" style="margin: 6px 0 0;">Quem está na planilha e quantas fichas têm cardiopatia congênita confirmada.</p>
            </div>
            <div class="dash-hero-actions">
                <span class="dash-date">{{ $hoje }}</span>
                <a class="dash-btn" href="{{ route('gestantes.index') }}">Gestantes</a>
            </div>
        </div>
    </header>

        <nav class="dash-subnav" aria-label="Seções da análise da população">
            @foreach ($secoesNav as $secao)
                <a href="{{ $secao['href'] }}">{{ $secao['label'] }}</a>
            @endforeach
        </nav>
</div>

    <section class="dash-area" id="indicadores">
        <div class="dash-area-head">
            <p class="dash-area-kicker">Visão geral</p>
            <h2>Panorama da base</h2>
            <p>Quatro números para abrir a apresentação: o tamanho da base, as consultas, os casos e os meses de vida.</p>
        </div>

        <div class="dash-snapshot" aria-label="Indicadores principais">
            <div class="dash-snap">
                <div class="dash-snap-label">Gestantes</div>
                <div class="dash-snap-value">{{ number_format($totalGestantes, 0, ',', '.') }}</div>
                <div class="dash-snap-hint">Cadastro local</div>
            </div>
            <div class="dash-snap">
                <div class="dash-snap-label">Consultas</div>
                <div class="dash-snap-value">{{ number_format($totalConsultas, 0, ',', '.') }}</div>
                <div class="dash-snap-hint">{{ $gestantesComConsulta }} com consulta ({{ $taxaGestantesComConsulta }}%)</div>
            </div>
            <div class="dash-snap">
                <div class="dash-snap-label">Cardiopatia congênita nas consultas</div>
                <div class="dash-snap-value dash-snap-value--accent">{{ $totalConsultas > 0 ? number_format($pctChdNasConsultas, 1, ',', '.').'%' : 'Não informado' }}</div>
                <div class="dash-snap-hint">{{ number_format($chdConfirmadas, 0, ',', '.') }} confirmadas</div>
            </div>
            <div class="dash-snap">
                <div class="dash-snap-label">Meses de vida</div>
                <div class="dash-snap-value">{{ isset($mediaIdadeCriancaMeses) && $mediaIdadeCriancaMeses !== null ? number_format((float) $mediaIdadeCriancaMeses, 1, ',', '.') : 'Não informado' }}</div>
                <div class="dash-snap-hint">Média na ficha</div>
            </div>
        </div>
    </section>

    <section class="dash-zone" id="cobertura">
        <div class="dash-zone-head">
            <div>
                <h2>Cobertura</h2>
                <p>A barra de cima é a cobertura das fichas. A de baixo é a parte com cardiopatia confirmada. Ela é curta porque o desfecho é raro.</p>
            </div>
        </div>

        @if ($totalConsultas > 0)
            <div class="dash-bars">
                <div class="dash-panel dash-bar-block">
                    <h4>Cobertura de consultas</h4>
                    <div class="dash-bar-track">
                        <div class="dash-bar-seg dash-bar-seg--ok" style="width: {{ $taxaGestantesComConsulta }}%"></div>
                    </div>
                    <div class="dash-bar-meta">
                        <strong>{{ $gestantesComConsulta }}</strong> de <strong>{{ $totalGestantes }}</strong>
                        gestantes ({{ $taxaGestantesComConsulta }}%)
                    </div>
                </div>
                <div class="dash-panel dash-bar-block">
                    <h4>Cardiopatia congênita nas consultas</h4>
                    @php
                        $wChd = round(100 * $chdConfirmadas / max($totalConsultas, 1), 2);
                        $wChdBar = $chdConfirmadas > 0 ? max($wChd, 2) : 0;
                    @endphp
                    <div class="dash-bar-track">
                        <div class="dash-bar-seg dash-bar-seg--risk" style="width: {{ $wChdBar }}%"></div>
                    </div>
                    <div class="dash-bar-meta">
                        Sim: <strong>{{ $chdConfirmadas }}</strong> ({{ $pctChdNasConsultas }}%)
                        · Não: <strong>{{ $chdNegativas }}</strong>
                    </div>
                </div>
            </div>
            <div class="dash-mini">
                <span>Meses de vida: <strong>{{ isset($mediaIdadeCriancaMeses) && $mediaIdadeCriancaMeses !== null ? number_format((float) $mediaIdadeCriancaMeses, 1, ',', '.') : 'Não informado' }}</strong></span>
                <span>Diabetes gestacional: <strong>{{ $consultasComDiabetes }}</strong></span>
                <span>Hipertensão: <strong>{{ $consultasComHipertensao }}</strong></span>
            </div>
        @else
            <div class="dash-panel">
                <p class="dash-empty">Ainda não há consultas cadastradas.</p>
            </div>
        @endif
    </section>

    <section class="dash-area" id="graficos">
        <div class="dash-area-head">
            <p class="dash-area-kicker">Análise por variáveis</p>
            <h2>Características da população</h2>
            <p>
                Cada gráfico responde a uma pergunta. O quadro abaixo do título explica como ler a figura.
                @if (($ad['fonte'] ?? '') === 'cadastro local')
                    Cadastro local.
                @endif
            </p>
        </div>

        @if (! empty($leituraDescritiva['disponivel']))
            <article class="dash-leitura">
                <p class="dash-area-kicker">Leitura descritiva</p>
                <p>{{ $leituraDescritiva['abertura'] }}</p>
            </article>
        @endif

        @if (! $descOk && empty($leituraDescritiva['disponivel']))
            <div class="dash-panel">
                <p class="dash-empty">
                    Cadastre consultas para ver a distribuição das variáveis.
                </p>
            </div>
        @else
            @foreach ($gruposVariaveis as $chaveGrupo => $grupo)
                @php
                    $graficosGrupo = $grupo['graficos'] ?? [];
                    $figurasGrupo = $grupo['figuras'] ?? [];
                @endphp
                @php
                    $leituraGrupo = $leituraDescritiva['grupos'][$chaveGrupo] ?? null;
                @endphp
                @if ($graficosGrupo !== [] || $figurasGrupo !== [] || is_array($leituraGrupo))
                    <section class="dash-zone" id="{{ $grupo['id'] }}">
                        <div class="dash-zone-head">
                            <div>
                                <h2>{{ $grupo['titulo'] }}</h2>
                                @if (! empty($grupo['texto']))
                                    <p>{{ $grupo['texto'] }}</p>
                                @endif
                            </div>
                        </div>
                        @if (is_array($leituraGrupo) && ($leituraGrupo['texto'] ?? '') !== '')
                            <p class="dash-leitura-texto">{{ $leituraGrupo['texto'] }}</p>
                        @endif
                        @if ($graficosGrupo !== [])
                            <div class="dash-charts">
                                @foreach ($graficosGrupo as $grafico)
                                    @php
                                        $slugGrafico = (string) ($grafico['slug'] ?? '');
                                        $nRotulos = count($grafico['labels'] ?? []);
                                        $largo = $nRotulos > 5 || in_array($slugGrafico, ['prevalencia', 'obstetricos'], true);
                                        $somaGrafico = array_sum($grafico['valores'] ?? []);
                                        $primeiroValor = (int) ($grafico['valores'][0] ?? 0);
                                    @endphp
                                    <div class="dash-chart {{ $largo ? 'dash-chart--wide' : '' }}">
                                        <h3>{{ $grafico['titulo'] }}</h3>
                                        @if (! empty($comoLer[$slugGrafico]))
                                            <p class="dash-chart-note"><strong>Como ler</strong>{{ $comoLer[$slugGrafico] }}</p>
                                        @endif
                                        @if ($slugGrafico === 'prevalencia')
                                            <div class="dash-prevalencia">
                                                <div class="dash-chart-wrap"><canvas id="chart-cohort-{{ $slugGrafico }}"></canvas></div>
                                                <p class="dash-prevalencia-stat">
                                                    {{ number_format($primeiroValor, 0, ',', '.') }}
                                                    <span>de {{ number_format($somaGrafico, 0, ',', '.') }} fichas
                                                        ({{ $somaGrafico > 0 ? number_format(100 * $primeiroValor / $somaGrafico, 1, ',', '.') : '0,0' }}%).</span>
                                                </p>
                                            </div>
                                        @else
                                            <div class="dash-chart-wrap"><canvas id="chart-cohort-{{ $slugGrafico }}"></canvas></div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        @if ($figurasGrupo !== [])
                            <div class="dash-desc-grid" style="{{ $graficosGrupo !== [] ? 'margin-top: 14px;' : '' }}">
                                @foreach ($figurasGrupo as $figura)
                                    <article class="dash-desc-card">
                                        <h4>{{ $figura['titulo'] ?? DashboardAnaliseService::tituloGraficoDescritivo((string) ($figura['slug'] ?? '')) }}</h4>
                                        <img
                                            src="{{ route('dashboard.graficos.descritivo', $figura['slug']) }}"
                                            alt="{{ $figura['titulo'] ?? $figura['slug'] }}"
                                            loading="lazy"
                                        >
                                    </article>
                                @endforeach
                            </div>
                        @endif
                        @if (is_array($leituraGrupo) && ($leituraGrupo['tabelas'] ?? []) !== [])
                            <div class="dash-desc-tables">
                                @foreach ($leituraGrupo['tabelas'] as $tabela)
                                    <article class="dash-panel">
                                        <div class="dash-panel-head">
                                            <h3>{{ $tabela['titulo'] }}</h3>
                                        </div>
                                        <div class="dash-panel-body dash-panel-body--flush">
                                            @if (! empty($tabela['nota']))
                                                <p class="dash-chart-note" style="padding: 12px 14px 0;">{{ $tabela['nota'] }}</p>
                                            @endif
                                            <div class="dash-model-table-wrap">
                                                <table class="dash-model-table dash-freq-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Categoria</th>
                                                            <th class="num">Fichas</th>
                                                            <th class="num">% da base</th>
                                                            <th class="num">Cardiopatias</th>
                                                            <th class="num">% no grupo</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($tabela['linhas'] as $linha)
                                                            <tr>
                                                                <td>{{ $linha['rotulo'] }}</td>
                                                                <td class="num">{{ number_format($linha['n'], 0, ',', '.') }}</td>
                                                                <td class="num">{{ number_format($linha['pct'], 1, ',', '.') }}%</td>
                                                                <td class="num">{{ number_format($linha['chd'], 0, ',', '.') }}</td>
                                                                <td class="num">{{ $linha['n'] > 0 ? number_format($linha['pct_grupo'], 1, ',', '.').'%' : '—' }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </section>
                @endif
            @endforeach

            @if (($gdOutros ?? []) !== [])
                <section class="dash-zone" id="variaveis-extras">
                    <div class="dash-zone-head">
                        <div>
                            <h2>Distribuição das variáveis</h2>
                            <p>Demais figuras descritivas já geradas para a base.</p>
                        </div>
                    </div>
                    <div class="dash-desc-grid">
                        @foreach ($gdOutros as $figura)
                            <article class="dash-desc-card">
                                <h4>{{ $figura['titulo'] ?? DashboardAnaliseService::tituloGraficoDescritivo((string) ($figura['slug'] ?? '')) }}</h4>
                                <img
                                    src="{{ route('dashboard.graficos.descritivo', $figura['slug']) }}"
                                    alt="{{ $figura['titulo'] ?? $figura['slug'] }}"
                                    loading="lazy"
                                >
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif
        @endif
    </section>

    <section class="dash-area" id="analises">
        <div class="dash-area-head">
            <p class="dash-area-kicker">Consulta complementar</p>
            <h2>Catálogo de experimentos</h2>
            <p>Fica recolhido de propósito. A apresentação começa pela população. Abra só se for falar dos modelos.</p>
        </div>

        <details class="dash-details">
            <summary>Ver catálogo de modelos</summary>
            <div class="dash-details-body" style="padding: 14px;">
    <section class="dash-zone" id="modelos" style="margin: 0;">
        <div class="dash-zone-head">
            <div>
                <h2>Catálogo</h2>
                <p>
                    @if (! empty($mm['experimento']))
                        {{ $mm['experimento'] }}
                    @else
                        Métricas dos experimentos disponíveis.
                    @endif
                </p>
            </div>
        </div>

        @if ($mmProducao !== [])
            <div class="dash-panel" style="margin-bottom: 16px;">
                <div class="dash-panel-head">
                    <h3>Modelo em produção</h3>
                    <span class="dash-count">
                        {{ ($mmProducao['carregado'] ?? false) ? 'publicado' : 'artefato ausente' }}
                    </span>
                </div>
                <div class="dash-panel-body">
                    <p>
                        {{ $mmProducao['nome_exibicao'] ?? $mmProducao['modelo'] ?? 'Não informado' }}
                        @if (! empty($mmProducao['balanceamento']))
                            · {{ $mmProducao['balanceamento'] }}
                        @endif
                    </p>
                    @if (($mmProducao['modo_inferencia'] ?? '') === 'heuristica')
                        <p class="dash-footnote" style="margin-top: 8px;">
                            {{ $mmProducao['mensagem'] ?? 'A predição atual usa regras clínicas determinísticas (heurística), não um modelo do catálogo.' }}
                        </p>
                    @endif
                </div>
            </div>
        @endif

        @if (! $mmOk)
            <div class="dash-panel" style="margin-bottom: 16px;">
                <p class="dash-empty">
                    {{ $mm['mensagem'] ?? 'Catálogo de modelos indisponível no momento.' }}
                </p>
            </div>
        @else
            @if ($mmDestaques !== [])
                <div class="dash-model-hi">
                    @foreach ($destaqueLabels as $key => $label)
                        @php $d = $mmDestaques[$key] ?? null; @endphp
                        @if (is_array($d))
                            <article>
                                <div class="dash-model-hi-label">{{ $label }}</div>
                                <div class="dash-model-hi-name">{{ $d['nome_exibicao'] ?? $d['modelo'] ?? 'Não informado' }}</div>
                                <div class="dash-model-hi-meta">{{ $d['balanceamento'] ?? 'Não informado' }}</div>
                                <div class="dash-model-hi-value">{{ $fmtPct($d['valor'] ?? null) }}</div>
                            </article>
                        @endif
                    @endforeach
                </div>
            @endif

            <div class="dash-panel" style="margin-bottom: 16px;">
                <div class="dash-panel-head">
                    <h3>Métricas</h3>
                    <span class="dash-count">{{ (int) ($mm['total_modelos'] ?? count($mm['modelos'])) }} modelos</span>
                </div>
                <div class="dash-panel-body dash-panel-body--flush">
                    <div class="dash-model-table-wrap">
                        <table class="dash-model-table">
                            <thead>
                                <tr>
                                    <th>Modelo</th>
                                    <th>Balanceamento</th>
                                    @if ($mmUsaCv ?? false)
                                        <th class="num">AUC</th>
                                        <th class="num">Precisão-recall</th>
                                        <th class="num">Sens.</th>
                                        <th class="num">Espec.</th>
                                        <th class="num">Valor preditivo</th>
                                    @else
                                        <th class="num">F1 CV</th>
                                        <th class="num">F1 teste</th>
                                        <th class="num">ROC-AUC</th>
                                        <th class="num">Sens.</th>
                                        <th class="num">Espec.</th>
                                        <th class="num">Acurácia</th>
                                    @endif
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($mm['modelos'] as $m)
                                    @php
                                        $isBest = $mmMelhorF1 && ($m['modelo'] ?? null) === $mmMelhorF1;
                                        $treino = is_array($m['treino_cv'] ?? null) ? $m['treino_cv'] : [];
                                        $teste = is_array($m['teste'] ?? null) ? $m['teste'] : [];
                                        $emUso = ($m['status'] ?? '') === 'em uso';
                                    @endphp
                                    <tr @class(['is-best' => $isBest || $emUso])>
                                        <td>
                                            {{ $m['nome_exibicao'] ?? $m['modelo'] ?? 'Não informado' }}
                                            @if ($isBest)<span class="dash-model-badge">melhor F1 CV</span>@endif
                                            @if ($emUso)<span class="dash-model-badge">em uso</span>@endif
                                        </td>
                                        <td>{{ $m['balanceamento'] ?? 'Não informado' }}</td>
                                        @if ($mmUsaCv ?? false)
                                            <td class="num">{{ $fmtPct($treino['roc_auc'] ?? null) }}</td>
                                            <td class="num">{{ $fmtPct($treino['pr_auc'] ?? null) }}</td>
                                            <td class="num">{{ $fmtPct($treino['sensibilidade'] ?? null) }}</td>
                                            <td class="num">{{ $fmtPct($treino['especificidade'] ?? null) }}</td>
                                            <td class="num">{{ $fmtPct($treino['precisao'] ?? null) }}</td>
                                        @else
                                            <td class="num">{{ $fmtPct($treino['f1'] ?? null) }}</td>
                                            <td class="num">{{ $fmtPct($teste['f1'] ?? null) }}</td>
                                            <td class="num">{{ $fmtPct($teste['roc_auc'] ?? null) }}</td>
                                            <td class="num">{{ $fmtPct($teste['sensibilidade'] ?? null) }}</td>
                                            <td class="num">{{ $fmtPct($teste['especificidade'] ?? null) }}</td>
                                            <td class="num">{{ $fmtPct($teste['acuracia'] ?? null) }}</td>
                                        @endif
                                        <td>{{ $m['status'] ?? 'Não informado' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if (! empty($mm['legendas']))
                        <details class="dash-details" style="border: 0; border-radius: 0; border-top: 1px solid var(--border);">
                            <summary>Notas das métricas</summary>
                            <div class="dash-details-body" style="padding: 0 14px 14px;">
                                @foreach ($mm['legendas'] as $legenda)
                                    @if (is_string($legenda) && $legenda !== '')
                                        <p class="dash-footnote">{{ $legenda }}</p>
                                    @endif
                                @endforeach
                            </div>
                        </details>
                    @endif
                </div>
            </div>
        @endif
    </section>
            </div>
        </details>
    </section>
