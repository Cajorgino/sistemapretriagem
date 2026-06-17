@extends('layouts.app')

@section('title', 'Editar gestante — Cardioprenatal')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Editar gestante</h1>
        <p class="page-subtitle">{{ $gestante->nome_exibicao }}</p>
    </div>

    <div class="main-card" style="max-width: 720px;">
        <form action="{{ route('gestantes.update', $gestante->id) }}" method="POST">
            @csrf
            @method('PUT')

            @if ($errors->any())
                <div style="background: var(--accent-light); border: 1px solid var(--border); color: var(--primary); padding: 16px 18px; border-radius: 16px; margin-bottom: 24px;">
                    <ul style="margin: 0; padding-left: 1.1rem; line-height: 1.6;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div style="display: grid; gap: 20px;">
                <div>
                    <label for="nome" class="form-label">Nome completo</label>
                    <input type="text" name="nome" id="nome" required value="{{ old('nome', $gestante->nome) }}" maxlength="255"
                           autocomplete="name" class="form-input">
                </div>

                <div>
                    <span class="form-label">Número do cadastro</span>
                    <p class="form-readonly">#{{ $gestante->id }}</p>
                    <p class="form-hint">Gerado automaticamente e sequencial; não pode ser alterado.</p>
                </div>

                <div>
                    <label for="cpf" class="form-label">CPF</label>
                    <input type="text" name="cpf" id="cpf"
                           value="{{ old('cpf', $gestante->cpf ? $gestante->cpf_formatado : '') }}"
                           placeholder="000.000.000-00"
                           inputmode="numeric" maxlength="14" required class="form-input">
                </div>

                <div>
                    <label for="telefone" class="form-label">Telefone / WhatsApp</label>
                    <input type="text" name="telefone" id="telefone"
                           value="{{ old('telefone', $gestante->telefone_formatado ?? $gestante->telefone) }}"
                           placeholder="(00) 00000-0000"
                           inputmode="tel" maxlength="16" required class="form-input">
                </div>

                <div>
                    <label for="data_nascimento" class="form-label">Data de nascimento</label>
                    <input type="date" name="data_nascimento" id="data_nascimento" required
                           value="{{ old('data_nascimento', $gestante->data_nascimento_input) }}"
                           class="form-input" style="max-width: min(280px, 100%);">
                    @if ($gestante->data_nascimento_formatada)
                        <p class="form-hint">Cadastrada como {{ $gestante->data_nascimento_formatada }}.</p>
                    @endif
                </div>
            </div>

            <div class="gestante-form-actions" style="margin-top: 28px; display: flex; gap: 12px; flex-wrap: wrap;">
                <button type="submit" class="btn-primary-custom">Atualizar</button>
                <a href="{{ route('gestantes.show', $gestante) }}" class="btn-secondary-outline">Cancelar</a>
            </div>
        </form>
    </div>

    @include('partials.form-masks')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const cpf = document.getElementById('cpf');
            const tel = document.getElementById('telefone');
            if (cpf) {
                maskCPFInput(cpf);
                cpf.addEventListener('input', function () { maskCPFInput(this); });
            }
            if (tel) {
                maskTelefoneBRInput(tel);
                tel.addEventListener('input', function () { maskTelefoneBRInput(this); });
            }
        });
    </script>
@endsection
