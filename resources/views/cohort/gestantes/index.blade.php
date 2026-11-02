@extends('layouts.app')

@section('title', 'Análise de risco. Cardioprenatal')

@section('content')
@php
    $queryBase = array_filter([
        'q' => $busca ?: null,
        'per_page' => $perPage !== 25 ? $perPage : null,
    ]);
@endphp
<div class="page-header">
    <h1 class="page-title">Análise de risco</h1>
    <p class="page-subtitle">
        Rastreio a partir da planilha. A lista mostra os campos que o modelo usa.
        @if ($dataset) Fonte: {{ $dataset }}. @endif
    </p>
</div>

@if ($error)
    <div class="main-card" style="margin-bottom: 16px;">
        <p class="dash-empty" style="color: var(--accent);">
            Serviço de análise indisponível no momento. Tente novamente em instantes.
        </p>
    </div>
@endif

<div class="main-card">
    <div class="card-toolbar">
        <h2 class="card-title" style="margin: 0; font-size: 20px;">
            Lista
            <span style="font-size: 14px; color: var(--muted); font-weight: 500;">
                : {{ number_format($total, 0, ',', '.') }} gestantes
            </span>
        </h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
            <form method="GET" action="{{ route('cohort.gestantes.index') }}" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                <input class="form-input" type="search" name="q" value="{{ $busca }}"
                       placeholder="Buscar nome, ID ou CPF"
                       style="min-width: 220px; padding: 8px 12px; font-size: 14px;"
                       autocomplete="off">
                @if ($perPage !== 25)
                    <input type="hidden" name="per_page" value="{{ $perPage }}">
                @endif
                <button type="submit" class="btn-secondary-outline">Buscar</button>
                @if ($busca !== '')
                    <a href="{{ route('cohort.gestantes.index', array_filter(['per_page' => $perPage !== 25 ? $perPage : null])) }}" class="btn-secondary-outline" style="text-decoration:none;">Limpar</a>
                @endif
            </form>
            <a href="{{ route('gestantes.index') }}" class="btn-secondary-outline" style="text-decoration:none;">
                Ver gestantes
            </a>
        </div>
    </div>

    <div class="table-container table-container--flush">
        <table class="data-table data-table--wide">
            <thead>
                <tr>
                    <th class="td-cell-nome">Nome</th>
                    <th>ID</th>
                    <th>Etnia</th>
                    <th>Paridade</th>
                    <th class="td-num">IMC gestacional</th>
                    <th class="td-num">Prematuridade</th>
                    <th class="td-num">Diabetes gestacional</th>
                    <th class="td-num">Cardiopatia confirmada</th>
                    <th class="td-actions">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($gestantes as $g)
                    <tr>
                        <td class="td-cell-nome"><strong>{{ $g['nome'] ?? 'Não informado' }}</strong></td>
                        <td>{{ $g['id_gestante'] ?? 'Não informado' }}</td>
                        <td>{{ $g['etnia'] ?: 'Não informado' }}</td>
                        <td>
                            @php
                                $rotuloParidade = match ((string) ($g['paridade'] ?? '')) {
                                    '0' => 'Primigesta',
                                    '1' => '1 filho',
                                    '2' => '2 filhos',
                                    '3' => 'Mais de 3 filhos',
                                    default => 'Não informado',
                                };
                            @endphp
                            {{ $g['paridade'] === null || $g['paridade'] === '' ? 'Não informado' : $rotuloParidade }}
                        </td>
                        <td class="td-num">
                            @php
                                $faixa = match ((int) ($g['imc_gestacional_categoria'] ?? -99)) {
                                    0 => 'Normal',
                                    1 => 'Acima de 24,9',
                                    2 => 'Abaixo de 18,5',
                                    default => 'Não informado',
                                };
                            @endphp
                            {{ $g['imc_gestacional_categoria'] === null || $g['imc_gestacional_categoria'] === '' ? 'Não informado' : $faixa }}
                        </td>
                        <td class="td-num">{{ (int) ($g['prematuridade'] ?? 0) ? 'Sim' : 'Não' }}</td>
                        <td class="td-num">{{ (int) ($g['diabetes_gestacional'] ?? 0) ? 'Sim' : 'Não' }}</td>
                        <td class="td-num">{{ (int) ($g['chd_confirmada'] ?? 0) ? 'Sim' : 'Não' }}</td>
                        <td class="td-actions">
                            <a href="{{ route('cohort.gestantes.show', $g['id_gestante']) }}" class="btn-primary-custom" style="padding: 6px 12px; font-size: 13px;">
                                Ver / analisar
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="dash-empty">
                            @if ($busca !== '')
                                Nenhuma gestante encontrada para “{{ $busca }}”.
                            @else
                                Nenhuma gestante encontrada.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($total > $perPage)
        <div style="display:flex; gap:10px; justify-content:center; padding: 18px 8px; flex-wrap: wrap;">
            @if ($page > 1)
                <a class="btn-secondary-outline" href="{{ route('cohort.gestantes.index', array_merge($queryBase, ['page' => $page - 1])) }}">← Anterior</a>
            @endif
            <span style="align-self:center; color: var(--muted); font-size: 14px;">
                Página {{ $page }} de {{ $lastPage }}
            </span>
            @if ($page < $lastPage)
                <a class="btn-secondary-outline" href="{{ route('cohort.gestantes.index', array_merge($queryBase, ['page' => $page + 1])) }}">Próxima →</a>
            @endif
        </div>
    @endif
</div>
@endsection
