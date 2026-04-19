@extends('layouts.app')

@section('title', 'Nova gestante — Cardioprenatal')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Nova gestante</h1>
        <p class="page-subtitle">Preencha os dados de identificação e contato. O número do cadastro é gerado automaticamente (sequencial).</p>
    </div>

    <div class="main-card" style="max-width: 720px;">
        <form method="POST" action="{{ route('gestantes.store') }}">
            @csrf

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
                    <label for="nome" style="display: block; font-size: 13px; font-weight: 600; color: var(--muted); margin-bottom: 8px;">Nome completo</label>
                    <input type="text" name="nome" id="nome" required value="{{ old('nome') }}" maxlength="255"
                           autocomplete="name"
                           style="width: 100%; padding: 12px 14px; border-radius: 12px; border: 1px solid var(--border); font-family: inherit; font-size: 15px;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div style="grid-column: 1 / -1;">
                        <label for="cpf" style="display: block; font-size: 13px; font-weight: 600; color: var(--muted); margin-bottom: 8px;">CPF</label>
                        <input type="text" name="cpf" id="cpf" value="{{ old('cpf') }}" placeholder="000.000.000-00"
                               autocomplete="off" required
                               style="width: 100%; padding: 12px 14px; border-radius: 12px; border: 1px solid var(--border); font-family: inherit; font-size: 15px;">
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <label for="telefone" style="display: block; font-size: 13px; font-weight: 600; color: var(--muted); margin-bottom: 8px;">Telefone / WhatsApp</label>
                        <input type="text" name="telefone" id="telefone" value="{{ old('telefone') }}" placeholder="(00) 00000-0000 ou 5500111222333"
                               autocomplete="tel" required
                               style="width: 100%; padding: 12px 14px; border-radius: 12px; border: 1px solid var(--border); font-family: inherit; font-size: 15px;">
                        <p style="font-size: 12px; color: var(--muted); margin-top: 8px; line-height: 1.4;">
                            Armazenamos apenas dígitos — compatível com integrações tipo <a href="https://github.com/alexoliveira46/gestrisk-ai-whatsapp" target="_blank" rel="noopener noreferrer" style="color: var(--accent);">WhatsApp (WAHA)</a>.
                        </p>
                    </div>
                </div>

                <div>
                    <label for="data_nascimento" style="display: block; font-size: 13px; font-weight: 600; color: var(--muted); margin-bottom: 8px;">Data de nascimento</label>
                    <input type="date" name="data_nascimento" id="data_nascimento" required value="{{ old('data_nascimento') }}"
                           style="width: 100%; max-width: 280px; padding: 12px 14px; border-radius: 12px; border: 1px solid var(--border); font-family: inherit; font-size: 15px;">
                </div>
            </div>

            <div style="margin-top: 28px; display: flex; gap: 12px; flex-wrap: wrap;">
                <button type="submit" class="btn-primary-custom">Cadastrar</button>
                <a href="{{ route('gestantes.index') }}" style="display: inline-flex; align-items: center; padding: 10px 20px; border-radius: 12px; border: 1px solid var(--border); color: var(--primary); text-decoration: none; font-weight: 600;">
                    Voltar
                </a>
            </div>
        </form>
    </div>
@endsection
