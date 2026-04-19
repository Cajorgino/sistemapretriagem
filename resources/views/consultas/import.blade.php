@extends('layouts.app')

@section('title', 'Importar consultas — Cardioprenatal')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Importar consultas</h1>
        <p class="page-subtitle">Envie um arquivo CSV com o layout esperado pelo sistema</p>
    </div>

    <div class="main-card" style="max-width: 560px;">
        <h2 class="card-title" style="font-size: 20px; margin-bottom: 8px;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
            </svg>
            Arquivo CSV
        </h2>
        <p style="color: var(--muted); font-size: 14px; line-height: 1.55; margin-bottom: 24px;">
            Após a importação bem-sucedida, você será redirecionado ao painel. Em caso de erro na estrutura do arquivo, uma mensagem será exibida abaixo.
        </p>

        <form id="importForm">
            @csrf

            <div class="form-field">
                <label class="form-label" for="csv">Selecionar arquivo</label>
                <input class="form-input" type="file" name="csv" id="csv" accept=".csv,text/csv" required
                       style="padding: 10px; cursor: pointer;">
            </div>

            <div id="feedback" class="form-alert-error hidden" style="margin-top: 20px;" role="alert"></div>

            <div class="form-actions" style="border-top: none; padding-top: 8px; margin-top: 8px;">
                <a href="{{ route('dashboard') }}" class="btn-secondary-outline">Voltar</a>
                <button type="submit" class="btn-primary-custom" id="importSubmit">Importar</button>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('importForm').addEventListener('submit', async function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const feedbackDiv = document.getElementById('feedback');
            const submitButton = document.getElementById('importSubmit');

            feedbackDiv.classList.add('hidden');
            submitButton.disabled = true;
            submitButton.textContent = 'Importando…';

            try {
                const response = await fetch("{{ route('consultas.import.store') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                if (response.ok) {
                    window.location.href = "{{ route('dashboard') }}";
                    return;
                }

                let message = 'Não foi possível importar.';
                try {
                    const data = await response.json();
                    if (data.message) {
                        message = data.message;
                    }
                    if (data.error) {
                        message += ' ' + (typeof data.error === 'string' ? data.error : JSON.stringify(data.error));
                    }
                } catch (_) {}

                feedbackDiv.textContent = message;
                feedbackDiv.classList.remove('hidden');
            } catch (error) {
                feedbackDiv.textContent = 'Erro de conexão ou ao processar o arquivo.';
                feedbackDiv.classList.remove('hidden');
            }

            submitButton.disabled = false;
            submitButton.textContent = 'Importar';
        });
    </script>
@endsection
