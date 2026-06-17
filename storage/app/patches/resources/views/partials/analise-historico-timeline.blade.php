@php
    use App\Services\DashboardAnaliseService;
@endphp

@if ($analises->isEmpty())
    <div class="main-card" style="text-align: center; color: var(--muted); padding: 32px 20px;">
        <p style="margin: 0 0 8px;">Nenhuma análise registrada para esta gestante.</p>
        @if (isset($gestante) && $gestante->consultas->isNotEmpty())
            <p style="margin: 0 0 8px; font-size: 13px;">
                Há consulta cadastrada — a análise pode estar <strong>na fila</strong>.
                Atualize a página em alguns segundos ou execute
                <code style="font-size: 12px;">php artisan queue:work</code> em outro terminal.
            </p>
        @else
            <p style="margin: 0; font-size: 13px;">Cadastre uma consulta clínica para disparar a análise GestRisk automaticamente.</p>
        @endif
    </div>
@else
    <div class="main-card">
        <h2 class="card-title" style="font-size: 20px; margin-bottom: 6px;">Histórico de análises GestRisk</h2>
        <p class="page-subtitle" style="margin: 0 0 20px;">Cada evento clínico gera um novo registro — nunca sobrescreve análises anteriores.</p>

        <div class="analise-timeline">
            @foreach ($analises as $idx => $analise)
                @php
                    $numero = $analises->count() - $idx;
                    $risco = $analise->classificacao_risco;
                    $riscoClass = match ($risco) {
                        'alto' => 'consulta-pill--alert',
                        'moderado' => 'consulta-pill--warn',
                        default => 'consulta-pill--ok',
                    };
                    $shapTop = collect($analise->shap['top_contribuicoes'] ?? [])->take(8);
                    $resumo = $analise->analise['resumo_clinico']['simplificado'] ?? null;
                @endphp
                <article class="analise-timeline-item">
                    <div class="analise-timeline-marker" aria-hidden="true"></div>
                    <div class="analise-timeline-body">
                        <div class="analise-timeline-head">
                            <div>
                                <span class="analise-timeline-num">Análise #{{ $numero }}</span>
                                <span class="analise-timeline-evento">{{ DashboardAnaliseService::labelEvento($analise->evento_tipo) }}</span>
                                <p class="analise-timeline-data">{{ $analise->created_at?->format('d/m/Y H:i') ?? '—' }}</p>
                            </div>
                            <div class="analise-timeline-badges">
                                @if ($analise->status === 'erro')
                                    <span class="consulta-pill consulta-pill--alert">Erro</span>
                                @else
                                    <span class="consulta-pill {{ $riscoClass }}">{{ DashboardAnaliseService::labelRisco($risco) }}</span>
                                    @if ($analise->probabilidade_ccf !== null)
                                        <span class="analise-timeline-prob">{{ number_format($analise->probabilidade_ccf * 100, 1) }}% CCF</span>
                                    @endif
                                @endif
                            </div>
                        </div>

                        @if ($analise->status === 'concluida')
                            <div class="analise-timeline-grid">
                                @if ($analise->recomenda_ecocardiograma)
                                    <div class="analise-timeline-meta">
                                        <span class="form-label">Ecocardiograma</span>
                                        <p class="consulta-valor">
                                            Recomendado —
                                            {{ DashboardAnaliseService::labelPrioridadeEco($analise->prioridade_ecocardiograma) }}
                                            @if ($analise->score_prioridade !== null)
                                                (score {{ number_format($analise->score_prioridade, 0) }})
                                            @endif
                                        </p>
                                    </div>
                                @endif
                                @if ($resumo)
                                    <div class="analise-timeline-meta analise-timeline-meta--wide">
                                        <span class="form-label">Resumo clínico</span>
                                        <p class="consulta-valor" style="font-weight: 400; line-height: 1.5;">{{ $resumo }}</p>
                                    </div>
                                @endif
                            </div>

                            @if ($shapTop->isNotEmpty())
                                <div class="analise-shap-chart-wrap">
                                    <p class="form-label" style="margin-bottom: 10px;">Contribuição SHAP (top {{ min(8, $shapTop->count()) }})</p>
                                    <div class="analise-shap-canvas">
                                        <canvas id="shap-chart-{{ $analise->id }}" aria-label="Gráfico SHAP"></canvas>
                                    </div>
                                </div>
                                <details class="analise-timeline-shap">
                                    <summary>Ver lista de fatores</summary>
                                    <ul>
                                        @foreach ($shapTop as $item)
                                            <li>
                                                <strong>{{ $item['nome'] ?? $item['campo'] ?? '—' }}</strong>
                                                — {{ isset($item['percentual_contribuicao']) ? number_format($item['percentual_contribuicao'], 1).'%' : '' }}
                                                <span style="color: var(--muted);">({{ ($item['direcao'] ?? '') === 'aumenta_risco' ? '↑ risco' : '↓ risco' }})</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </details>
                            @endif
                        @elseif ($analise->erro_mensagem)
                            <p style="margin: 8px 0 0; font-size: 13px; color: var(--accent);">{{ $analise->erro_mensagem }}</p>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </div>
@endif

<style>
    .analise-timeline { position: relative; padding-left: 20px; border-left: 2px solid var(--border); }
    .analise-timeline-item { position: relative; padding-bottom: 24px; }
    .analise-timeline-item:last-child { padding-bottom: 0; }
    .analise-timeline-marker {
        position: absolute; left: -27px; top: 6px;
        width: 12px; height: 12px; border-radius: 50%;
        background: var(--primary); border: 2px solid var(--surface);
        box-shadow: 0 0 0 2px var(--border);
    }
    .analise-timeline-head { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 12px; margin-bottom: 10px; }
    .analise-timeline-num { font-weight: 700; color: var(--primary); font-size: 15px; margin-right: 8px; }
    .analise-timeline-evento { font-size: 13px; color: var(--muted); }
    .analise-timeline-data { margin: 4px 0 0; font-size: 13px; color: var(--muted); }
    .analise-timeline-badges { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
    .analise-timeline-prob { font-size: 13px; font-weight: 600; color: var(--text); }
    .analise-timeline-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; }
    .analise-timeline-meta--wide { grid-column: 1 / -1; }
    .analise-timeline-shap { margin-top: 12px; font-size: 13px; }
    .analise-timeline-shap summary { cursor: pointer; font-weight: 600; color: var(--primary); }
    .analise-timeline-shap ul { margin: 8px 0 0; padding-left: 18px; color: var(--text); }
    .analise-shap-chart-wrap { margin-top: 14px; }
    .analise-shap-canvas { position: relative; height: 220px; max-width: 100%; }
    .consulta-pill--warn { background: rgba(230, 126, 34, 0.15); color: #c0392b; border: 1px solid rgba(230, 126, 34, 0.35); }
</style>

@php
    $shapChartPayload = $analises
        ->filter(fn ($a) => $a->status === 'concluida' && ! empty($a->shap['top_contribuicoes']))
        ->mapWithKeys(fn ($a) => [
            $a->id => collect($a->shap['top_contribuicoes'] ?? [])->take(8)->values()->all(),
        ])
        ->all();
@endphp

@if (! empty($shapChartPayload))
    @push('scripts')
        @include('partials.chart-lib')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const payloads = @json($shapChartPayload);
                GestRiskCharts.ready(function () {
                    Object.entries(payloads).forEach(function (entry) {
                        GestRiskCharts.shapHorizontal('shap-chart-' + entry[0], entry[1]);
                    });
                });
            });
        </script>
    @endpush
@endif
