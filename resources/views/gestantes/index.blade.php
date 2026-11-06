@extends('layouts.app')

@section('title', 'Gestantes. Cardioprenatal')

@section('content')
@php
    use App\Models\User;

    $podeAnalises = auth()->user()?->can(User::ABILITY_ANALISES);
    $podeGerenciarGestantes = auth()->user()?->can(User::ABILITY_GESTANTES_MANAGE);
    $colspan = 5 + ($podeAnalises ? 1 : 0);
    $filtroCardiopatia = $filtroCardiopatia ?? '';
@endphp
<style>
    .gestantes-filtro {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 12px;
        margin: 0 0 18px;
    }
    .gestantes-filtro .form-label { margin: 0; }
    .gestantes-filtro .form-select { width: auto; min-width: 180px; max-width: 100%; padding: 10px 14px; }

    .gestantes-cards { display: none; }
    .gestantes-table-wrap { display: block; }

    .gestantes-card {
        display: flex;
        flex-direction: column;
        gap: 14px;
        padding: 16px;
        border: 1px solid var(--border);
        border-radius: 16px;
        background: var(--surface);
    }
    .gestantes-card + .gestantes-card { margin-top: 10px; }
    .gestantes-card-top {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 8px 12px;
        min-width: 0;
    }
    .gestantes-card-nome {
        font-size: 16px;
        font-weight: 700;
        color: var(--text);
        line-height: 1.35;
        word-break: break-word;
        min-width: 0;
        flex: 1 1 12rem;
    }
    .gestantes-card-id {
        font-size: 12px;
        font-weight: 600;
        color: var(--muted);
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }
    .gestantes-card-meta {
        display: grid;
        grid-template-columns: 1fr;
        gap: 8px;
    }
    .gestantes-card-meta dt {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: var(--muted);
        margin: 0 0 2px;
    }
    .gestantes-card-meta dd {
        margin: 0;
        font-size: 14px;
        color: var(--text);
        font-weight: 600;
        line-height: 1.35;
    }
    .gestantes-card-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        padding-top: 4px;
        border-top: 1px solid var(--border);
    }
    .gestantes-card-actions .btn-table {
        flex: 1 1 calc(33.333% - 6px);
        min-width: 44px;
        min-height: 44px;
        width: auto;
    }
    .gestantes-empty {
        padding: 28px 16px;
        text-align: center;
        color: var(--muted);
        font-size: 15px;
        border: 1px dashed var(--border);
        border-radius: 16px;
        background: rgba(253, 240, 240, 0.35);
    }

    table.data-table--gestantes {
        min-width: 0;
        table-layout: fixed;
        width: 100%;
    }
    table.data-table--gestantes th.td-cell-nome,
    table.data-table--gestantes td.td-cell-nome {
        width: 28%;
        min-width: 0;
        max-width: none;
    }
    table.data-table--gestantes th.td-risco,
    table.data-table--gestantes td.td-risco {
        width: 7.5rem;
        min-width: 0;
    }
    table.data-table--gestantes th.td-cardio,
    table.data-table--gestantes td.td-cardio {
        width: 8.5rem;
        min-width: 0;
        white-space: nowrap;
    }
    table.data-table--gestantes th.td-num--narrow,
    table.data-table--gestantes td.td-num--narrow {
        width: 5.5rem;
        min-width: 0;
    }
    table.data-table--gestantes thead th.td-actions,
    table.data-table--gestantes tbody td.td-actions {
        width: 8.5rem;
        min-width: 0;
    }
    table.data-table--gestantes .td-actions-inner {
        width: auto;
        max-width: none;
    }

    @media (max-width: 960px) {
        table.data-table--gestantes th.td-num--id,
        table.data-table--gestantes td.td-num--id {
            display: none;
        }
        table.data-table--gestantes thead th.td-actions,
        table.data-table--gestantes tbody td.td-actions {
            width: 7.5rem;
        }
    }

    @media (max-width: 768px) {
        .gestantes-filtro { align-items: stretch; }
        .gestantes-filtro .form-label { width: 100%; }
        .gestantes-filtro .form-select { width: 100%; min-width: 0; }

        .gestantes-table-wrap,
        .gestantes-table-wrap + .table-scroll-hint,
        .table-scroll-hint--gestantes {
            display: none !important;
        }
        .gestantes-cards { display: block; }

        .gestantes-card-meta {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 420px) {
        .gestantes-card-meta { grid-template-columns: 1fr; }
        .gestantes-card-actions .btn-table {
            flex: 1 1 calc(50% - 4px);
        }
    }
</style>
    <div class="page-header">
        <h1 class="page-title">Gestantes</h1>
        <p class="page-subtitle">Cadastro e acompanhamento no rastreio de cardiopatias congênitas em gestantes diabéticas.</p>
    </div>

    @if (session('success'))
        <div class="main-card" role="status" style="margin-bottom: 16px; border-left: 4px solid var(--success); background: rgba(39, 174, 96, 0.08);">
            <p style="margin: 0; font-size: 15px; color: var(--text);">{{ session('success') }}</p>
        </div>
    @endif
    @if (session('warning'))
        <div class="main-card" role="alert" style="margin-bottom: 16px; border-left: 4px solid #e67e22; background: rgba(230, 126, 34, 0.08);">
            <p style="margin: 0; font-size: 15px; color: var(--text);">{{ session('warning') }}</p>
        </div>
    @endif

    <div class="main-card">
        <div class="card-toolbar">
            <h2 class="card-title" style="margin: 0; font-size: 20px;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                </svg>
                Lista
            </h2>
            @can(\App\Models\User::ABILITY_GESTANTES_MANAGE)
            <a href="{{ route('gestantes.create') }}" class="btn-primary-custom">
                Cadastrar gestante
            </a>
            @endcan
        </div>

        <form method="GET" action="{{ route('gestantes.index') }}" class="gestantes-filtro">
            <label class="form-label" for="filtro_cardiopatia">Cardiopatia confirmada</label>
            <select class="form-select" name="cardiopatia" id="filtro_cardiopatia" onchange="this.form.submit()">
                <option value="" @selected($filtroCardiopatia === '')>Todas</option>
                <option value="sim" @selected($filtroCardiopatia === 'sim')>Sim</option>
                <option value="nao" @selected($filtroCardiopatia === 'nao')>Não</option>
            </select>
            @if ($filtroCardiopatia !== '')
                <span style="font-size: 13px; color: var(--muted);">{{ $gestantes->total() }} {{ $gestantes->total() === 1 ? 'registro' : 'registros' }}</span>
            @endif
        </form>

        @php
            $listaVaziaTexto = match ($filtroCardiopatia) {
                'sim' => 'Nenhuma gestante com cardiopatia confirmada.',
                'nao' => 'Nenhuma gestante sem cardiopatia confirmada.',
                default => 'Nenhuma gestante cadastrada.',
            };
        @endphp

        <div class="gestantes-cards" aria-label="Lista de gestantes">
            @forelse ($gestantes as $gestante)
                @php
                    $escore = null;
                    if ($podeAnalises) {
                        $ua = $gestante->ultimaAnalise;
                        $escore = ($ua && $ua->status === 'concluida') ? $ua->probabilidade_ccf : null;
                    }
                @endphp
                <article class="gestantes-card">
                    <div class="gestantes-card-top">
                        <h3 class="gestantes-card-nome">{{ $gestante->nome_exibicao }}</h3>
                        <span class="gestantes-card-id">#{{ $gestante->id }}</span>
                    </div>
                    <dl class="gestantes-card-meta">
                        @if ($podeAnalises)
                            <div>
                                <dt>Escore do modelo</dt>
                                <dd>
                                    @if ($escore !== null)
                                        {{ number_format((float) $escore * 100, 1, ',', '.') }}%
                                    @else
                                        Não informado
                                    @endif
                                </dd>
                            </div>
                        @endif
                        <div>
                            <dt>Cardiopatia confirmada</dt>
                            <dd>{{ $gestante->filho_cardiopatia_confirmada ? 'Sim' : 'Não' }}</dd>
                        </div>
                        <div>
                            <dt>Consultas</dt>
                            <dd>{{ $gestante->consultas_count }}</dd>
                        </div>
                    </dl>
                    <div class="gestantes-card-actions">
                        <a href="{{ route('gestantes.show', $gestante) }}"
                           class="btn-table btn-table--primary btn-table--icon"
                           title="Ver"
                           aria-label="Ver {{ $gestante->nome_exibicao }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </a>
                        @if ($podeGerenciarGestantes)
                        <a href="{{ route('gestantes.edit', $gestante) }}"
                           class="btn-table btn-table--secondary btn-table--icon"
                           title="Editar"
                           aria-label="Editar {{ $gestante->nome_exibicao }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 20h9"/>
                                <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>
                            </svg>
                        </a>
                        <button type="button"
                                onclick="abrirModal({{ $gestante->id }})"
                                class="btn-table btn-table--danger btn-table--icon"
                                title="Excluir"
                                aria-label="Excluir {{ $gestante->nome_exibicao }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="3 6 5 6 21 6"/>
                                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                <path d="M10 11v6"/><path d="M14 11v6"/>
                                <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                            </svg>
                        </button>
                        @endif
                    </div>
                </article>
            @empty
                <p class="gestantes-empty">{{ $listaVaziaTexto }}</p>
            @endforelse
        </div>

        <p class="table-scroll-hint table-scroll-hint--gestantes">↔ Em telas menores, a lista vira cartões; no desktop aparecem todas as colunas.</p>
        <div class="table-container table-container--flush gestantes-table-wrap">
            <table class="data-table data-table--gestantes">
                <thead>
                    <tr>
                        <th class="td-cell-nome">Nome</th>
                        <th class="td-num td-num--narrow td-num--id">Nº cadastro</th>
                        @if ($podeAnalises)
                            <th class="td-risco">Escore</th>
                        @endif
                        <th class="td-cardio">Cardiopatia</th>
                        <th class="td-num td-num--narrow">Consultas</th>
                        <th class="td-actions">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($gestantes as $gestante)
                        @php
                            $escore = null;
                            if ($podeAnalises) {
                                $ua = $gestante->ultimaAnalise;
                                $escore = ($ua && $ua->status === 'concluida') ? $ua->probabilidade_ccf : null;
                            }
                        @endphp
                        <tr>
                            <td class="td-cell-nome"><strong>{{ $gestante->nome_exibicao }}</strong></td>
                            <td class="td-num td-num--narrow td-num--id">#{{ $gestante->id }}</td>
                            @if ($podeAnalises)
                            <td class="td-risco">
                                @if ($escore !== null)
                                    <span style="font-size: 13px; font-weight: 600;">{{ number_format((float) $escore * 100, 1, ',', '.') }}%</span>
                                @else
                                    <span style="color: var(--muted); font-size: 13px;">—</span>
                                @endif
                            </td>
                            @endif
                            <td class="td-cardio">{{ $gestante->filho_cardiopatia_confirmada ? 'Sim' : 'Não' }}</td>
                            <td class="td-num td-num--narrow">{{ $gestante->consultas_count }}</td>
                            <td class="td-actions">
                                <div class="td-actions-inner td-actions-inner--icons">
                                    <div class="td-actions-group" role="group" aria-label="Cadastro">
                                        <a href="{{ route('gestantes.show', $gestante) }}"
                                           class="btn-table btn-table--primary btn-table--icon"
                                           title="Ver"
                                           aria-label="Ver {{ $gestante->nome_exibicao }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                <circle cx="12" cy="12" r="3"/>
                                            </svg>
                                        </a>
                                        @if ($podeGerenciarGestantes)
                                        <a href="{{ route('gestantes.edit', $gestante) }}"
                                           class="btn-table btn-table--secondary btn-table--icon"
                                           title="Editar"
                                           aria-label="Editar {{ $gestante->nome_exibicao }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M12 20h9"/>
                                                <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>
                                            </svg>
                                        </a>
                                        <button type="button"
                                                onclick="abrirModal({{ $gestante->id }})"
                                                class="btn-table btn-table--danger btn-table--icon"
                                                title="Excluir"
                                                aria-label="Excluir {{ $gestante->nome_exibicao }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <polyline points="3 6 5 6 21 6"/>
                                                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                                <path d="M10 11v6"/><path d="M14 11v6"/>
                                                <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                                            </svg>
                                        </button>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="data-table-empty">
                            <td colspan="{{ $colspan }}">{{ $listaVaziaTexto }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($gestantes->hasPages())
            <div class="table-pagination">
                {{ $gestantes->links() }}
            </div>
        @endif
    </div>

    @if ($podeGerenciarGestantes)
    <div id="modalExcluir"
         class="app-modal-overlay"
         style="background: rgba(28, 26, 26, 0.45); backdrop-filter: blur(4px);"
         role="dialog"
         aria-modal="true"
         aria-labelledby="modalExcluirTitulo"
         aria-hidden="true">
        <div class="main-card app-modal-panel" style="padding: 28px;" onclick="event.stopPropagation()">
            <h2 id="modalExcluirTitulo" style="font-family: 'DM Serif Display', serif; font-size: 22px; color: var(--primary); margin-bottom: 12px;">
                Confirmar exclusão
            </h2>
            <p style="color: var(--muted); margin-bottom: 24px; line-height: 1.5;">
                Tem certeza que deseja excluir esta gestante? Esta ação não pode ser desfeita.
            </p>
            <div class="app-modal-actions">
                <button type="button" onclick="fecharModal()"
                        style="padding: 10px 18px; border-radius: 12px; border: 1px solid var(--border); background: var(--surface); cursor: pointer; font-weight: 600; color: var(--text);">
                    Cancelar
                </button>
                <form id="formExcluir" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-primary-custom" style="background: linear-gradient(135deg, #8b1530, var(--accent-mid));">
                        Excluir
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('modalExcluir');
            const form = document.getElementById('formExcluir');

            window.abrirModal = function (id) {
                form.action = `/gestantes/${id}`;
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            };

            window.fecharModal = function () {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            };

            modal.addEventListener('click', function (e) {
                if (e.target === modal) {
                    fecharModal();
                }
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && modal.classList.contains('is-open')) {
                    fecharModal();
                }
            });
        })();
    </script>
    @endif
@endsection
