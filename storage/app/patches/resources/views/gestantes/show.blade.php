@extends('layouts.app')

@section('title', $gestante->nome_exibicao.' — Cardioprenatal')

@section('content')
@php
    use App\Services\DashboardAnaliseService;
@endphp
<div style="padding-bottom: 32px;">
    <div style="max-width: 1152px; margin: 0 auto; display: flex; flex-direction: column; gap: 28px;">

        @if (session('success'))
            <div class="main-card" role="status" style="margin-bottom: 0; border-left: 4px solid var(--success); background: rgba(39, 174, 96, 0.08);">
                <p style="margin: 0; font-size: 15px; color: var(--text);">{{ session('success') }}</p>
            </div>
        @endif
        @if (session('warning'))
            <div class="main-card" role="alert" style="margin-bottom: 0; border-left: 4px solid #e67e22; background: rgba(230, 126, 34, 0.08);">
                <p style="margin: 0; font-size: 15px; color: var(--text);">{{ session('warning') }}</p>
            </div>
        @endif

        <div class="main-card" style="margin-bottom: 0;">
            <div style="display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 20px;">
                <div>
                    <p style="font-size: 12px; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: var(--muted); margin-bottom: 8px;">Gestante</p>
                    <h2 class="page-title" style="font-size: 28px; margin: 0;">
                        {{ $gestante->nome_exibicao }}
                    </h2>
                    <div style="margin-top: 16px; display: grid; gap: 8px; font-size: 14px; color: var(--muted);">
                        <p>
                            Número do cadastro:
                            <strong style="color: var(--text);">#{{ $gestante->id }}</strong>
                        </p>
                        <p>
                            Data de nascimento:
                            <strong style="color: var(--text);">
                                {{ $gestante->data_nascimento_formatada ?? 'Não informada' }}
                            </strong>
                        </p>
                        <p>
                            CPF:
                            <strong style="color: var(--text);">{{ $gestante->cpf ? $gestante->cpf_formatado : '—' }}</strong>
                        </p>
                        <p>
                            Telefone:
                            <strong style="color: var(--text);">{{ $gestante->telefone ? $gestante->telefone_formatado : '—' }}</strong>
                            @if ($gestante->telefone)
                                <span style="font-size: 11px; display: block; margin-top: 2px; color: var(--muted);">Número normalizado: {{ $gestante->telefone }}</span>
                            @endif
                        </p>
                        <p>
                            Consultas:
                            <strong style="color: var(--text);">{{ $gestante->consultas->count() }}</strong>
                        </p>
                        @if ($gestante->ultimaAnalise && $gestante->ultimaAnalise->status === 'concluida')
                            @php $ua = $gestante->ultimaAnalise; @endphp
                            <p>
                                Último risco (GestRisk):
                                <strong style="color: var(--text);">{{ DashboardAnaliseService::labelRisco($ua->classificacao_risco) }}</strong>
                                @if ($ua->probabilidade_ccf !== null)
                                    · {{ number_format($ua->probabilidade_ccf * 100, 1) }}% CCF
                                @endif
                                @if ($ua->recomenda_ecocardiograma)
                                    · Eco {{ DashboardAnaliseService::labelPrioridadeEco($ua->prioridade_ecocardiograma) }}
                                @endif
                            </p>
                        @endif
                    </div>
                </div>

                <div class="gestante-header-actions">
                    <a href="{{ route('gestantes.edit', $gestante) }}"
                       style="display: inline-flex; align-items: center; padding: 12px 20px; border-radius: 12px; border: 1px solid var(--border); color: var(--primary); text-decoration: none; font-weight: 600;">
                        Editar dados
                    </a>
                    <a href="{{ route('consultas.create', ['id' => $gestante->id]) }}" class="btn-primary-custom">
                        + Nova consulta
                    </a>
                </div>
            </div>
        </div>

        @include('partials.analise-historico-timeline', [
            'analises' => $gestante->analisesHistorico,
            'gestante' => $gestante,
        ])

        @forelse ($gestante->consultas as $consulta)
            <div class="main-card">
                <div class="consulta-resumo-head">
                    <div>
                        <h2 class="card-title" style="font-size: 22px; margin: 0;">Consulta nº {{ $consulta->consulta_numero }}</h2>
                        <p style="color: var(--muted); font-size: 14px; margin-top: 10px; line-height: 1.5;">
                            @if ($consulta->data_consulta)
                                {{ $consulta->data_consulta->format('d/m/Y') }}
                            @else
                                —
                            @endif
                            @if ($consulta->idade_gestacional)
                                · {{ $consulta->idade_gestacional }} sem
                            @endif
                        </p>
                    </div>
                    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 10px;">
                        <a href="{{ route('consultas.edit', $consulta->id) }}" class="btn-table btn-table--primary">Editar consulta</a>
                    </div>
                </div>

                @include('consultas.partials.resumo-ccf', ['consulta' => $consulta])
            </div>
        @empty
            <div class="main-card" style="text-align: center; color: var(--muted); padding: 40px 24px;">
                Nenhuma consulta registrada.
            </div>
        @endforelse

    </div>
</div>
@endsection
