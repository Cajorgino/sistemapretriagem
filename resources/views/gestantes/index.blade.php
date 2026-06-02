@extends('layouts.app')

@section('title', 'Gestantes — Cardioprenatal')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Gestantes</h1>
        <p class="page-subtitle">Cadastro e acompanhamento das pacientes</p>
    </div>

    <div class="main-card">
        <div class="card-toolbar">
            <h2 class="card-title" style="margin: 0; font-size: 20px;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                </svg>
                Lista
            </h2>
            <a href="{{ route('gestantes.create') }}" class="btn-primary-custom">
                Cadastrar gestante
            </a>
        </div>

        <p class="table-scroll-hint">↔ Deslize horizontalmente para ver CPF, telefone e botões.</p>
        <div class="table-container table-container--flush">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="td-cell-nome">Nome</th>
                        <th class="td-num td-num--narrow">Nº cadastro</th>
                        <th class="td-nowrap">CPF</th>
                        <th class="td-nowrap">Telefone</th>
                        <th class="td-nowrap">Nascimento</th>
                        <th class="td-num td-num--narrow">Consultas</th>
                        <th class="td-actions">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($gestantes as $gestante)
                        <tr>
                            <td class="td-cell-nome"><strong>{{ $gestante->nome_exibicao }}</strong></td>
                            <td class="td-num td-num--narrow">#{{ $gestante->id }}</td>
                            <td class="td-nowrap">{{ $gestante->cpf ? $gestante->cpf_formatado : '—' }}</td>
                            <td class="td-nowrap">{{ $gestante->telefone ? $gestante->telefone_formatado : '—' }}</td>
                            <td class="td-nowrap">{{ $gestante->data_nascimento ? \Carbon\Carbon::parse($gestante->data_nascimento)->format('d/m/Y') : '—' }}</td>
                            <td class="td-num td-num--narrow">{{ $gestante->consultas_count }}</td>
                            <td class="td-actions">
                                <div class="td-actions-inner">
                                    <a href="{{ route('gestantes.show', $gestante) }}" class="btn-table btn-table--primary">Ver</a>
                                    <a href="{{ route('gestantes.edit', $gestante) }}" class="btn-table btn-table--secondary">Editar</a>
                                    <button type="button" onclick="abrirModal({{ $gestante->id }})" class="btn-table btn-table--danger">Excluir</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="data-table-empty">
                            <td colspan="7">Nenhuma gestante cadastrada.</td>
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
@endsection
