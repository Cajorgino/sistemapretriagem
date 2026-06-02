@extends('layouts.app')

@section('title', 'Dashboard - Cardioprenatal')

@section('content')

<style>
    .dash-pres {
        max-width: 1100px;
        margin: 0 auto;
    }

    .dash-pres-hero {
        background: linear-gradient(135deg, rgba(127, 12, 26, 0.06) 0%, rgba(192, 57, 43, 0.08) 100%);
        border: 1px solid var(--border);
        border-radius: 20px;
        padding: 22px 24px;
        margin-bottom: 24px;
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
    }

    .dash-pres-hero h2 {
        font-family: 'DM Serif Display', serif;
        font-size: clamp(1.35rem, 3vw, 1.75rem);
        color: var(--primary);
        margin: 0 0 6px;
        line-height: 1.2;
    }

    .dash-pres-hero p {
        margin: 0;
        font-size: 14px;
        color: var(--muted);
        max-width: 36rem;
        line-height: 1.5;
    }

    .dash-pres-date {
        font-size: 13px;
        font-weight: 600;
        color: var(--muted);
        white-space: nowrap;
        padding: 8px 14px;
        background: var(--surface);
        border-radius: 12px;
        border: 1px solid var(--border);
    }

    .dash-kpis {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 140px), 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }

    .dash-kpi {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 16px 18px;
        box-shadow: 0 4px 16px rgba(127, 12, 26, 0.06);
    }

    .dash-kpi-label {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--muted);
    }

    .dash-kpi-value {
        font-family: 'DM Serif Display', serif;
        font-size: clamp(1.5rem, 4vw, 2rem);
        color: var(--text);
        margin-top: 6px;
        line-height: 1.1;
    }

    .dash-kpi-value--accent { color: var(--accent); }

    .dash-bars {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 260px), 1fr));
        gap: 20px;
        margin-bottom: 22px;
    }

    .dash-bar-block h4 {
        font-size: 13px;
        font-weight: 600;
        color: var(--text);
        margin: 0 0 10px;
    }

    .dash-bar-track {
        height: 12px;
        border-radius: 99px;
        background: rgba(240, 213, 213, 0.85);
        overflow: hidden;
        display: flex;
    }

    .dash-bar-seg {
        height: 100%;
        min-width: 0;
        transition: width 0.4s ease;
    }

    .dash-bar-seg--ok { background: linear-gradient(90deg, #27ae60, #2ecc71); }
    .dash-bar-seg--warn { background: linear-gradient(90deg, var(--primary), var(--accent-mid)); }

    .dash-bar-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 12px 18px;
        margin-top: 10px;
        font-size: 12px;
        color: var(--muted);
    }

    .dash-bar-legend span strong { color: var(--text); font-weight: 600; }

    .dash-mini {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 22px;
    }

    .dash-mini-pill {
        font-size: 13px;
        padding: 10px 16px;
        border-radius: 12px;
        background: rgba(253, 240, 240, 0.65);
        border: 1px solid var(--border);
        color: var(--text);
    }

    .dash-mini-pill strong { color: var(--primary); }

    .dash-table-title {
        font-family: 'DM Serif Display', serif;
        font-size: 1.15rem;
        color: var(--primary);
        margin: 0 0 12px;
    }

    .dash-empty {
        text-align: center;
        padding: 28px 16px;
        color: var(--muted);
        font-size: 14px;
    }

    /* IA (mantido) */
    .cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 200px), 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .stat-card {
        background: var(--surface);
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(127,12,26,0.08);
        border: 1px solid var(--border);
    }

    .stat-label { font-size: 13px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; }
    .stat-value { font-family: 'DM Serif Display', serif; font-size: clamp(1.75rem, 6vw, 2.375rem); color: var(--text); margin-top: 10px; }
    .stat-value.danger { color: var(--accent); }

    .btn-analisar {
        background: linear-gradient(135deg, var(--primary), var(--accent-mid));
        color: white;
        padding: 12px 28px;
        border-radius: 12px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s;
        box-shadow: 0 4px 15px rgba(192,57,43,0.3);
    }

    .btn-analisar:hover { opacity: 0.9; transform: scale(1.02); }

    @media (max-width: 560px) {
        .btn-analisar { width: 100%; justify-content: center; padding: 12px 16px; }
    }

    #tabela-estatistica th.td-left,
    #tabela-estatistica td.td-left { text-align: left; }
    #tabela-estatistica th:not(.td-left),
    #tabela-estatistica td:not(.td-left) {
        text-align: center;
        font-variant-numeric: tabular-nums;
    }
    .td-left { font-weight: 600; color: var(--text); }

    .loading-heart { width: 50px; height: 50px; color: var(--accent); animation: heartbeat 0.8s infinite; }
    @keyframes heartbeat { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.2); } }

    .graficos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 260px), 1fr));
        gap: 16px;
        margin-top: 20px;
    }
    .grafico-item { background: #fdfafa; border: 1px solid var(--border); border-radius: 16px; padding: 12px; text-align: center; min-width: 0; }
    .grafico-item h4 { margin-bottom: 10px; color: var(--primary); font-family: 'DM Serif Display', serif; font-size: clamp(0.95rem, 2.5vw, 1.1rem); word-break: break-word; }
    .grafico-item img { width: 100%; max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }

    #tabela-estatistica { min-width: 520px; }

    @media (max-width: 640px) {
        .dash-pres { padding: 0 2px; }
        .dash-pres-hero {
            padding: 18px 16px;
            flex-direction: column;
            align-items: stretch;
        }
        .dash-pres-date {
            align-self: flex-start;
            white-space: normal;
        }
        .dash-bar-block h4 {
            font-size: 12px;
            line-height: 1.35;
        }
        .dash-kpi { padding: 14px 14px; }
        .dash-mini-pill {
            font-size: 12px;
            padding: 9px 12px;
            line-height: 1.4;
        }
    }
</style>

<div class="dash-pres">
    <div class="dash-pres-hero">
        <div>
            <h2>Cardioprenatal — painel do programa</h2>
            <p>Visão rápida dos cadastros e consultas para apresentação. Os números refletem os dados já registrados no sistema.</p>
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
            <div class="dash-kpi-label">Com ≥1 consulta</div>
            <div class="dash-kpi-value">{{ $gestantesComConsulta }}</div>
        </div>
        <div class="dash-kpi">
            <div class="dash-kpi-label">Cobertura</div>
            <div class="dash-kpi-value">{{ $taxaGestantesComConsulta }}%</div>
        </div>
        <div class="dash-kpi">
            <div class="dash-kpi-label">CHD / consultas</div>
            <div class="dash-kpi-value">{{ $pctChdNasConsultas }}%</div>
        </div>
    </div>

    @if ($totalConsultas > 0)
        <div class="dash-bars">
            <div class="dash-bar-block">
                <h4>Gestantes com pelo menos uma consulta registrada</h4>
                <div class="dash-bar-track" role="img" aria-label="Cobertura {{ $taxaGestantesComConsulta }} por cento">
                    <div class="dash-bar-seg dash-bar-seg--ok" style="width: {{ $taxaGestantesComConsulta }}%"></div>
                </div>
                <div class="dash-bar-legend">
                    <span><strong>{{ $gestantesComConsulta }}</strong> de <strong>{{ $totalGestantes }}</strong> gestantes</span>
                </div>
            </div>
            <div class="dash-bar-block">
                <h4>Resultado CHD nas consultas (sim × não)</h4>
                <div class="dash-bar-track" role="img" aria-label="Proporção CHD">
                    @php
                        $wChd = $totalConsultas > 0 ? round(100 * $chdConfirmadas / $totalConsultas, 2) : 0;
                        $wNao = 100 - $wChd;
                    @endphp
                    <div class="dash-bar-seg dash-bar-seg--warn" style="width: {{ $wChd }}%"></div>
                    <div class="dash-bar-seg" style="width: {{ $wNao }}%; background: rgba(39, 174, 96, 0.35);"></div>
                </div>
                <div class="dash-bar-legend">
                    <span>CHD sim: <strong>{{ $chdConfirmadas }}</strong></span>
                    <span>CHD não: <strong>{{ $chdNegativas }}</strong></span>
                </div>
            </div>
        </div>

        <div class="dash-mini">
            <div class="dash-mini-pill">
                Idade gestacional média (consultas): <strong>{{ $mediaIdadeGestacional !== null ? $mediaIdadeGestacional.' sem' : '—' }}</strong>
            </div>
            <div class="dash-mini-pill">
                Consultas com diabetes gestacional: <strong>{{ $consultasComDiabetes }}</strong>
            </div>
            <div class="dash-mini-pill">
                Consultas com hipertensão: <strong>{{ $consultasComHipertensao }}</strong>
            </div>
        </div>
    @endif

    <h3 class="dash-table-title">Últimas consultas registradas</h3>
    <div class="table-container table-container--flush" style="margin-bottom: 28px;">
        @if ($consultasRecentes->isEmpty())
            <p class="dash-empty">Ainda não há consultas. Cadastre gestantes e inclua consultas para ver o painel preenchido.</p>
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
                            <td>
                                <strong>{{ \Illuminate\Support\Str::limit($c->gestante?->nome_exibicao ?? '—', 42) }}</strong>
                            </td>
                            <td class="td-num">{{ $c->idade_gestacional ?? '—' }}</td>
                            <td>
                                @if ($c->chd_confirmada)
                                    <span class="consulta-pill consulta-pill--alert" style="margin:0;">Sim</span>
                                    @if ($c->tipo_chd)
                                        <span style="font-size: 12px; color: var(--muted); display: block; margin-top: 4px;">{{ $c->tipo_chd }}</span>
                                    @endif
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

<div class="main-card">
    <div class="card-toolbar" style="margin-bottom: 8px;">
        <h3 class="card-title" style="margin: 0;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                <polyline points="7.5 4.21 12 6.81 16.5 4.21"/><polyline points="7.5 19.79 7.5 14.6 3 12"/><polyline points="21 12 16.5 14.6 16.5 19.79"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>
            </svg>
            Análise preditiva (IA)
        </h3>
        <div class="btn-analisar-wrap">
            <form id="formAnalisar" action="{{ route('dashboard.analisar') }}" method="POST">
                @csrf
                <button type="submit" class="btn-analisar">
                    Iniciar nova análise
                </button>
            </form>
        </div>
    </div>
    <p class="page-subtitle" style="margin: 0 0 8px;">Opcional: estatísticas e gráficos a partir do conjunto de consultas (processamento em segundo plano).</p>

    <div id="area-resultados-ia" style="{{ session('analise_iniciada') ? 'display: block;' : 'display: none;' }}">
        <hr style="margin: 30px 0; border: 0; border-top: 1px solid var(--border);">

        <div id="loading-indicator" style="text-align: center; padding: 40px 0;">
            <svg class="loading-heart" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
            </svg>
            <p class="loading-text" style="color: var(--muted); margin-top: 15px;">A IA está processando os dados médicos...</p>
        </div>

        <div id="dados-container" style="display: none;">
            <h4 style="font-family: 'DM Serif Display', serif; color: var(--primary); margin-bottom: 15px;">Estatística descritiva</h4>
            <div class="table-container">
                <table id="tabela-estatistica" class="data-table">
                    <thead>
                        <tr>
                            <th class="td-left">Variável</th>
                            <th>Média</th><th>Std</th><th>Min</th><th>P50 (Mediana)</th><th>Max</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <h4 style="font-family: 'DM Serif Display', serif; color: var(--primary); margin-top: 40px; margin-bottom: 15px;">Visualizações geradas</h4>
            <div id="graficos-container" class="graficos-grid"></div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const areaResultados = document.getElementById('area-resultados-ia');
    const loadingIndicator = document.getElementById('loading-indicator');
    const dadosContainer = document.getElementById('dados-container');
    const tabelaBody = document.querySelector('#tabela-estatistica tbody');
    const graficosContainer = document.getElementById('graficos-container');

    @if(session('analise_iniciada'))
        verificarStatus();
    @endif

    document.getElementById('formAnalisar').addEventListener('submit', function() {
        areaResultados.style.display = 'block';
        loadingIndicator.style.display = 'block';
        dadosContainer.style.display = 'none';
    });

    function verificarStatus() {
        const interval = setInterval(() => {
            fetch('{{ route("dashboard.verificarAnalise") }}')
                .then(res => {
                    if (!res.ok) throw new Error('Falha na comunicação com o servidor');
                    return res.json();
                })
                .then(data => {
                    if (data.status === 'concluido') {
                        clearInterval(interval);
                        renderizarTudo(data.resultado);
                    }
                })
                .catch(err => {
                    console.error('Erro ao verificar análise:', err);
                });
        }, 3000);
    }

    function renderizarTudo(res) {
        loadingIndicator.style.display = 'none';
        dadosContainer.style.display = 'block';

        const stats = Array.isArray(res.estatistica_geral) ? res.estatistica_geral : Object.values(res.estatistica_geral);

        tabelaBody.innerHTML = stats.map(item => `
            <tr>
                <td class="td-left">${(item.index || '').replace(/_/g, ' ')}</td>
                <td>${Number(item.mean).toFixed(2)}</td>
                <td>${Number(item.std).toFixed(2)}</td>
                <td>${Number(item.min).toFixed(2)}</td>
                <td>${Number(item.p50 || item['50%']).toFixed(2)}</td>
                <td>${Number(item.max).toFixed(2)}</td>
            </tr>
        `).join('');

        const graficos = res.graficos || res.imagens || {};
        graficosContainer.innerHTML = Object.entries(graficos).map(([titulo, b64]) => `
            <div class="grafico-item">
                <h4>${titulo.replace(/_/g, ' ')}</h4>
                <img src="data:image/png;base64,${b64}" alt="" />
            </div>
        `).join('');
    }
});
</script>
@endpush

@endsection
