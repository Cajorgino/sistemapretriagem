@extends('layouts.app')

@section('title', 'Dashboard - Cardioprenatal')

@section('content')

@php
    use App\Services\DashboardAnaliseService;
    $dr = $distribuicaoRisco;
    $im = $indicadoresModelo;
@endphp

<style>
    .dash-pres { max-width: 1100px; margin: 0 auto; }
    .dash-pres-hero {
        background: linear-gradient(135deg, rgba(127, 12, 26, 0.06) 0%, rgba(192, 57, 43, 0.08) 100%);
        border: 1px solid var(--border); border-radius: 20px; padding: 22px 24px;
        margin-bottom: 24px; display: flex; flex-wrap: wrap; align-items: flex-start;
        justify-content: space-between; gap: 16px;
    }
    .dash-pres-hero h2 {
        font-family: 'DM Serif Display', serif; font-size: clamp(1.35rem, 3vw, 1.75rem);
        color: var(--primary); margin: 0 0 6px; line-height: 1.2;
    }
    .dash-pres-hero p { margin: 0; font-size: 14px; color: var(--muted); max-width: 36rem; line-height: 1.5; }
    .dash-pres-date {
        font-size: 13px; font-weight: 600; color: var(--muted); white-space: nowrap;
        padding: 8px 14px; background: var(--surface); border-radius: 12px; border: 1px solid var(--border);
    }
    .dash-kpis {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 140px), 1fr));
        gap: 12px; margin-bottom: 20px;
    }
    .dash-kpi {
        background: var(--surface); border: 1px solid var(--border); border-radius: 16px;
        padding: 16px 18px; box-shadow: 0 4px 16px rgba(127, 12, 26, 0.06);
    }
    .dash-kpi-label {
        font-size: 11px; font-weight: 700; letter-spacing: 0.06em;
        text-transform: uppercase; color: var(--muted);
    }
    .dash-kpi-value {
        font-family: 'DM Serif Display', serif; font-size: clamp(1.5rem, 4vw, 2rem);
        color: var(--text); margin-top: 6px; line-height: 1.1;
    }
    .dash-kpi-value--accent { color: var(--accent); }
    .dash-kpi-value--ok { color: #27ae60; }
    .dash-kpi-value--warn { color: #e67e22; }
    .dash-bars {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 260px), 1fr));
        gap: 20px; margin-bottom: 22px;
    }
    .dash-bar-block h4 { font-size: 13px; font-weight: 600; color: var(--text); margin: 0 0 10px; }
    .dash-bar-track {
        height: 12px; border-radius: 99px; background: rgba(240, 213, 213, 0.85);
        overflow: hidden; display: flex;
    }
    .dash-bar-seg { height: 100%; min-width: 0; transition: width 0.4s ease; }
    .dash-bar-seg--ok { background: linear-gradient(90deg, #27ae60, #2ecc71); }
    .dash-bar-seg--warn { background: linear-gradient(90deg, #e67e22, #f39c12); }
    .dash-bar-seg--risk { background: linear-gradient(90deg, var(--primary), var(--accent-mid)); }
    .dash-bar-legend { display: flex; flex-wrap: wrap; gap: 12px 18px; margin-top: 10px; font-size: 12px; color: var(--muted); }
    .dash-bar-legend span strong { color: var(--text); font-weight: 600; }
    .dash-mini { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 22px; }
    .dash-mini-pill {
        font-size: 13px; padding: 10px 16px; border-radius: 12px;
        background: rgba(253, 240, 240, 0.65); border: 1px solid var(--border); color: var(--text);
    }
    .dash-mini-pill strong { color: var(--primary); }
    .dash-table-title {
        font-family: 'DM Serif Display', serif; font-size: 1.15rem;
        color: var(--primary); margin: 0 0 12px;
    }
    .dash-section { margin-bottom: 28px; }
    .dash-empty { text-align: center; padding: 28px 16px; color: var(--muted); font-size: 14px; }
    .dash-risco-stack { display: flex; height: 14px; border-radius: 99px; overflow: hidden; background: rgba(240,213,213,0.5); }
    .dash-risco-seg--baixo { background: #27ae60; }
    .dash-risco-seg--moderado { background: #e67e22; }
    .dash-risco-seg--alto { background: var(--accent); }
    .dash-eco-priority { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; }
    .dash-eco-priority--urgente { color: var(--accent); }
    .dash-eco-priority--alta { color: #c0392b; }
    .dash-eco-priority--moderada { color: #e67e22; }
    .dash-eco-priority--baixa { color: var(--muted); }
    .dash-note {
        font-size: 13px; color: var(--muted); margin: 0 0 16px; line-height: 1.5;
        padding: 12px 16px; background: rgba(253,240,240,0.5); border-radius: 12px; border: 1px solid var(--border);
    }
    .dash-charts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 280px), 1fr));
        gap: 20px;
        margin-bottom: 28px;
    }
    .dash-chart-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 18px;
        padding: 20px 22px;
        min-height: 280px;
        display: flex;
        flex-direction: column;
    }
    .dash-chart-card h4 {
        font-size: 14px; font-weight: 600; color: var(--text);
        margin: 0 0 14px; line-height: 1.35;
    }
    .dash-chart-wrap { position: relative; flex: 1; min-height: 200px; }
    @media (max-width: 640px) {
        .dash-pres { padding: 0 2px; }
        .dash-pres-hero { padding: 18px 16px; flex-direction: column; }
        .dash-pres-date { align-self: flex-start; white-space: normal; }
    }
</style>

<div class="dash-pres">
    <div class="dash-pres-hero">
        <div>
            <h2>Cardioprenatal — painel do programa</h2>
            <p>Indicadores populacionais e inteligência clínica pré-processada. O dashboard não executa modelos em tempo real.</p>
        </div>
        <span class="dash-pres-date" title="Data de referência">Atualizado em {{ $hoje }}</span>
    </div>

    <div class="dash-kpis">
        <div class="dash-kpi">
            <div class="dash-kpi-label">Gestantes</div>
            <div class="dash-kpi-value">{{ $totalGestantes }}</div>
        </div>
        <div class="dash-kpi">
            <div class="dash-kpi-label">Consultas</div>
            <div class="dash-kpi-value">{{ $totalConsultas }}</div>
        </div>
        <div class="dash-kpi">
            <div class="dash-kpi-label">CHD confirmada</div>
            <div class="dash-kpi-value dash-kpi-value--accent">{{ $chdConfirmadas }}</div>
        </div>
        <div class="dash-kpi">
            <div class="dash-kpi-label">Análises (total)</div>
            <div class="dash-kpi-value">{{ $im['total_analises'] }}</div>
        </div>
        <div class="dash-kpi">
            <div class="dash-kpi-label">Gestantes analisadas</div>
            <div class="dash-kpi-value">{{ $dr['total_gestantes_analisadas'] }}</div>
        </div>
        <div class="dash-kpi">
            <div class="dash-kpi-label">Eco recomendada</div>
            <div class="dash-kpi-value dash-kpi-value--warn">{{ $im['gestantes_com_eco_recomendada'] }}</div>
        </div>
    </div>

    @php $gp = $graficosPopulacionais; @endphp
    <div class="dash-section">
        <h3 class="dash-table-title">Gráficos — cohort analisado (MySQL)</h3>
        <p class="dash-note">Dados da última análise concluída por gestante em <code>analises_historico</code>. Atualizados conforme novas consultas são processadas na fila.</p>
        @if (($dr['total_gestantes_analisadas'] ?? 0) === 0)
            <div class="main-card"><p class="dash-empty">Sem dados para gráficos. Cadastre consultas com a API e o worker ativos.</p></div>
        @else
            <div class="dash-charts-grid">
                <div class="dash-chart-card">
                    <h4>Classificação de risco</h4>
                    <div class="dash-chart-wrap"><canvas id="chart-risco"></canvas></div>
                </div>
                <div class="dash-chart-card">
                    <h4>Prioridade ecocardiograma</h4>
                    <div class="dash-chart-wrap"><canvas id="chart-prioridade-eco"></canvas></div>
                </div>
                <div class="dash-chart-card">
                    <h4>Distribuição probabilidade CCF</h4>
                    <div class="dash-chart-wrap"><canvas id="chart-probabilidade"></canvas></div>
                </div>
            </div>
        @endif
    </div>

    <div class="dash-section main-card">
        <h3 class="dash-table-title">Distribuição de risco (última análise por gestante)</h3>
        @if ($dr['total_gestantes_analisadas'] === 0)
            <p class="dash-empty">Sem análises concluídas. Verifique se a API GestRisk e o worker de fila estão ativos após cadastro de consultas.</p>
        @else
            <div class="dash-risco-stack" role="img" aria-label="Distribuição de risco">
                @if ($dr['pct_baixo'] > 0)
                    <div class="dash-risco-seg--baixo" style="width: {{ $dr['pct_baixo'] }}%"></div>
                @endif
                @if ($dr['pct_moderado'] > 0)
                    <div class="dash-risco-seg--moderado" style="width: {{ $dr['pct_moderado'] }}%"></div>
                @endif
                @if ($dr['pct_alto'] > 0)
                    <div class="dash-risco-seg--alto" style="width: {{ $dr['pct_alto'] }}%"></div>
                @endif
            </div>
            <div class="dash-bar-legend" style="margin-top: 12px;">
                <span>Baixo: <strong>{{ $dr['baixo'] }}</strong> ({{ $dr['pct_baixo'] }}%)</span>
                <span>Moderado: <strong>{{ $dr['moderado'] }}</strong> ({{ $dr['pct_moderado'] }}%)</span>
                <span>Alto: <strong>{{ $dr['alto'] }}</strong> ({{ $dr['pct_alto'] }}%)</span>
                @if ($im['media_probabilidade_ccf'] !== null)
                    <span>Média CCF: <strong>{{ $im['media_probabilidade_ccf'] }}%</strong></span>
                @endif
            </div>
        @endif
    </div>

    <div class="dash-section">
        <h3 class="dash-table-title">Fila de ecocardiograma fetal</h3>
        <p class="dash-note">Priorização com base na última análise armazenada — ordenada por score de prioridade.</p>
        <div class="table-container table-container--flush main-card" style="padding: 0; overflow: hidden;">
            @if ($filaEcocardiograma->isEmpty())
                <p class="dash-empty">Nenhuma gestante com ecocardiograma recomendado na última análise.</p>
            @else
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Gestante</th>
                            <th class="td-num">Score</th>
                            <th>Prioridade</th>
                            <th class="td-num">Prob. CCF</th>
                            <th>Risco</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($filaEcocardiograma as $pos => $item)
                            <tr>
                                <td class="td-num">{{ $pos + 1 }}</td>
                                <td>
                                    @if ($item->gestante)
                                        <a href="{{ route('gestantes.show', $item->gestante) }}" style="font-weight: 600; color: var(--primary);">
                                            {{ \Illuminate\Support\Str::limit($item->gestante->nome_exibicao, 36) }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="td-num">{{ $item->score_prioridade !== null ? number_format($item->score_prioridade, 0) : '—' }}</td>
                                <td>
                                    <span class="dash-eco-priority dash-eco-priority--{{ $item->prioridade_ecocardiograma ?? 'baixa' }}">
                                        {{ DashboardAnaliseService::labelPrioridadeEco($item->prioridade_ecocardiograma) }}
                                    </span>
                                </td>
                                <td class="td-num">
                                    {{ $item->probabilidade_ccf !== null ? number_format($item->probabilidade_ccf * 100, 1).'%' : '—' }}
                                </td>
                                <td>{{ DashboardAnaliseService::labelRisco($item->classificacao_risco) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <div class="dash-section">
        <h3 class="dash-table-title">Análises recentes</h3>
        <div class="table-container table-container--flush main-card">
            @if ($analisesRecentes->isEmpty())
                <p class="dash-empty">Nenhuma análise registrada ainda.</p>
            @else
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Gestante</th>
                            <th>Evento</th>
                            <th>Risco</th>
                            <th class="td-num">CCF</th>
                            <th>Eco</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($analisesRecentes as $a)
                            <tr>
                                <td>{{ $a->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td>
                                    @if ($a->gestante)
                                        <a href="{{ route('gestantes.show', $a->gestante) }}">{{ \Illuminate\Support\Str::limit($a->gestante->nome_exibicao, 32) }}</a>
                                    @else — @endif
                                </td>
                                <td>{{ DashboardAnaliseService::labelEvento($a->evento_tipo) }}</td>
                                <td>{{ DashboardAnaliseService::labelRisco($a->classificacao_risco) }}</td>
                                <td class="td-num">{{ $a->probabilidade_ccf !== null ? number_format($a->probabilidade_ccf * 100, 1).'%' : '—' }}</td>
                                <td>{{ $a->recomenda_ecocardiograma ? 'Sim' : 'Não' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    @if ($totalConsultas > 0)
        <div class="dash-bars">
            <div class="dash-bar-block main-card">
                <h4>Cobertura de consultas</h4>
                <div class="dash-bar-track">
                    <div class="dash-bar-seg dash-bar-seg--ok" style="width: {{ $taxaGestantesComConsulta }}%"></div>
                </div>
                <div class="dash-bar-legend">
                    <span><strong>{{ $gestantesComConsulta }}</strong> de <strong>{{ $totalGestantes }}</strong> gestantes ({{ $taxaGestantesComConsulta }}%)</span>
                </div>
            </div>
            <div class="dash-bar-block main-card">
                <h4>CHD confirmada nas consultas</h4>
                @php $wChd = round(100 * $chdConfirmadas / max($totalConsultas, 1), 2); @endphp
                <div class="dash-bar-track">
                    <div class="dash-bar-seg dash-bar-seg--risk" style="width: {{ $wChd }}%"></div>
                </div>
                <div class="dash-bar-legend">
                    <span>Sim: <strong>{{ $chdConfirmadas }}</strong> ({{ $pctChdNasConsultas }}%)</span>
                    <span>Não: <strong>{{ $chdNegativas }}</strong></span>
                </div>
            </div>
        </div>
        <div class="dash-mini">
            <div class="dash-mini-pill">IG média: <strong>{{ $mediaIdadeGestacional !== null ? $mediaIdadeGestacional.' sem' : '—' }}</strong></div>
            <div class="dash-mini-pill">Diabetes gestacional: <strong>{{ $consultasComDiabetes }}</strong></div>
            <div class="dash-mini-pill">Hipertensão: <strong>{{ $consultasComHipertensao }}</strong></div>
            @if ($im['total_erros'] > 0)
                <div class="dash-mini-pill">Análises com erro: <strong>{{ $im['total_erros'] }}</strong></div>
            @endif
        </div>
    @endif

    <h3 class="dash-table-title">Últimas consultas registradas</h3>
    <div class="table-container table-container--flush main-card" style="margin-bottom: 8px;">
        @if ($consultasRecentes->isEmpty())
            <p class="dash-empty">Ainda não há consultas cadastradas.</p>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Gestante</th>
                        <th class="td-num">IG (sem.)</th>
                        <th>CHD</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($consultasRecentes as $c)
                        <tr>
                            <td>{{ $c->data_consulta?->format('d/m/Y') ?? '—' }}</td>
                            <td><strong>{{ \Illuminate\Support\Str::limit($c->gestante?->nome_exibicao ?? '—', 42) }}</strong></td>
                            <td class="td-num">{{ $c->idade_gestacional ?? '—' }}</td>
                            <td>
                                @if ($c->chd_confirmada)
                                    <span class="consulta-pill consulta-pill--alert" style="margin:0;">Sim</span>
                                @else
                                    <span class="consulta-pill consulta-pill--ok" style="margin:0;">Não</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

@if (($dr['total_gestantes_analisadas'] ?? 0) > 0)
    @push('scripts')
        @include('partials.chart-lib')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const gp = @json($gp);
                const p = window.GestRiskCharts.palette;
                GestRiskCharts.ready(function () {
                    GestRiskCharts.doughnut(
                        'chart-risco',
                        gp.risco.labels,
                        gp.risco.valores,
                        [p.ok, p.warn, p.primary]
                    );
                    GestRiskCharts.bar(
                        'chart-prioridade-eco',
                        gp.prioridade_eco.labels,
                        gp.prioridade_eco.valores,
                        [p.primary, '#c0392b', p.warn, p.muted, '#bdc3c7']
                    );
                    GestRiskCharts.bar(
                        'chart-probabilidade',
                        gp.probabilidade.labels,
                        gp.probabilidade.valores,
                        gp.probabilidade.valores.map(function () { return 'rgba(192, 57, 43, 0.72)'; })
                    );
                });
            });
        </script>
    @endpush
@endif

@endsection
