@extends('layouts.app')

@section('title', 'Gestantes — Cardioprenatal')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Gestantes</h1>
        <p class="page-subtitle">Cadastro e acompanhamento das pacientes</p>
    </div>

    <div class="main-card">
        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 24px;">
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

        <div class="table-container table-container--flush">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Nº cadastro</th>
                        <th>CPF</th>
                        <th>Telefone</th>
                        <th>Nascimento</th>
                        <th class="td-num">Consultas</th>
                        <th class="td-actions">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($gestantes as $gestante)
                        <tr>
                            <td><strong>{{ $gestante->nome_exibicao }}</strong></td>
                            <td>#{{ $gestante->id }}</td>
                            <td>{{ $gestante->cpf ? $gestante->cpf_formatado : '—' }}</td>
                            <td>{{ $gestante->telefone ? $gestante->telefone_formatado : '—' }}</td>
                            <td>{{ $gestante->data_nascimento ? \Carbon\Carbon::parse($gestante->data_nascimento)->format('d/m/Y') : '—' }}</td>
                            <td class="td-num">{{ $gestante->consultas_count }}</td>
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

    <div id="modalExcluir" class="hidden fixed inset-0 flex items-center justify-center z-50" style="background: rgba(28, 26, 26, 0.45); backdrop-filter: blur(4px);">
        <div class="main-card" style="max-width: 400px; margin: 16px; padding: 28px;">
            <h2 style="font-family: 'DM Serif Display', serif; font-size: 22px; color: var(--primary); margin-bottom: 12px;">
                Confirmar exclusão
            </h2>
            <p style="color: var(--muted); margin-bottom: 24px; line-height: 1.5;">
                Tem certeza que deseja excluir esta gestante? Esta ação não pode ser desfeita.
            </p>
            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" onclick="fecharModal()"
                        style="padding: 10px 18px; border-radius: 12px; border: 1px solid var(--border); background: var(--surface); cursor: pointer; font-weight: 600; color: var(--text);">
                    Cancelar
                </button>
                <form id="formExcluir" method="POST">
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
        function abrirModal(id) {
            const modal = document.getElementById('modalExcluir');
            const form = document.getElementById('formExcluir');
            form.action = `/gestantes/${id}`;
            modal.classList.remove('hidden');
        }

        function fecharModal() {
            document.getElementById('modalExcluir').classList.add('hidden');
        }
    </script>
@endsection
