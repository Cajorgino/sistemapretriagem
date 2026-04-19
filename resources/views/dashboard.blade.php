@extends('layouts.app')

@section('title', 'Dashboard - Cardioprenatal')

@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap');

    /* Grid de Stats */
    .cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: var(--surface);
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(127,12,26,0.08);
        border: 1px solid var(--border);
        transition: transform 0.3s ease;
    }
    .stat-card:hover { transform: translateY(-5px); }
    .stat-label { font-size: 13px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; }
    .stat-value { font-family: 'DM Serif Display', serif; font-size: 38px; color: var(--text); margin-top: 10px; }
    .stat-value.danger { color: var(--accent); }

    /* Botão Estilizado */
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

    /* Tabela de estatísticas: primeira coluna à esquerda, demais centralizadas */
    #tabela-estatistica th.td-left,
    #tabela-estatistica td.td-left {
        text-align: left;
    }
    #tabela-estatistica th:not(.td-left),
    #tabela-estatistica td:not(.td-left) {
        text-align: center;
        font-variant-numeric: tabular-nums;
    }
    .td-left { font-weight: 600; color: var(--text); }

    /* Loading Heart */
    .loading-heart { width: 50px; height: 50px; color: var(--accent); animation: heartbeat 0.8s infinite; }
    @keyframes heartbeat { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.2); } }

    /* Grid de Gráficos */
    .graficos-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-top: 20px; }
    .grafico-item { background: #fdfafa; border: 1px solid var(--border); border-radius: 16px; padding: 15px; text-align: center; }
    .grafico-item h4 { margin-bottom: 10px; color: var(--primary); font-family: 'DM Serif Display', serif; }
    .grafico-item img { width: 100%; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
</style>

<div class="page-header">
    <h2 class="page-title">Visão Geral</h2>
    <p class="page-subtitle">Monitoramento materno-fetal e apoio ao rastreio de cardiopatias congênitas (CHD)</p>
</div>

<div class="cards-grid">
    <div class="stat-card">
        <span class="stat-label">Gestantes</span>
        <div class="stat-value">{{ $totalGestantes }}</div>
    </div>
    <div class="stat-card">
        <span class="stat-label">Consultas</span>
        <div class="stat-value">{{ $totalConsultas }}</div>
    </div>
    <div class="stat-card">
        <span class="stat-label">Casos de CHD</span>
        <div class="stat-value danger">{{ $chdConfirmadas }}</div>
    </div>
</div>

<div class="main-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 class="card-title">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                <polyline points="7.5 4.21 12 6.81 16.5 4.21"/><polyline points="7.5 19.79 7.5 14.6 3 12"/><polyline points="21 12 16.5 14.6 16.5 19.79"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>
            </svg>
            Análise Preditiva de IA
        </h3>
        <form id="formAnalisar" action="{{ route('dashboard.analisar') }}" method="POST">
            @csrf
            <button type="submit" class="btn-analisar">
                Iniciar Nova Análise
            </button>
        </form>
    </div>

    <div id="area-resultados-ia" style="{{ session('analise_iniciada') ? 'display: block;' : 'display: none;' }}">
        <hr style="margin: 30px 0; border: 0; border-top: 1px solid var(--border);">
        
        <div id="loading-indicator" style="text-align: center; padding: 40px 0;">
            <svg class="loading-heart" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
            </svg>
            <p class="loading-text" style="color: var(--muted); margin-top: 15px;">A IA está processando os dados médicos...</p>
        </div>

        <div id="dados-container" style="display: none;">
            <h4 style="font-family: 'DM Serif Display', serif; color: var(--primary); margin-bottom: 15px;">Estatística Descritiva</h4>
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

            <h4 style="font-family: 'DM Serif Display', serif; color: var(--primary); margin-top: 40px; margin-bottom: 15px;">Visualizações Geradas</h4>
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

    // Feedback imediato ao clicar no botão
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

        // 1. Renderizar Tabela
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

        // 2. Renderizar Gráficos
        const graficos = res.graficos || res.imagens || {};
        graficosContainer.innerHTML = Object.entries(graficos).map(([titulo, b64]) => `
            <div class="grafico-item">
                <h4>${titulo.replace(/_/g, ' ')}</h4>
                <img src="data:image/png;base64,${b64}" />
            </div>
        `).join('');
    }
});
</script>
@endpush

@endsection