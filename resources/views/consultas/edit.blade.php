@extends('layouts.app')

@section('title', 'Editar consulta — Cardioprenatal')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Editar consulta</h1>
        <p class="page-subtitle">
            {{ $gestante->nome_exibicao }}
            <span style="color: var(--muted);">· #{{ $gestante->id }}</span>
            · Consulta nº {{ $consulta->consulta_numero }}
            @if ($consulta->data_consulta)
                · {{ $consulta->data_consulta->format('d/m/Y') }}
            @endif
        </p>
    </div>

    <div class="main-card" style="max-width: 1152px; margin: 0 auto;">
        @if ($errors->any())
            <div class="form-alert-error">
                <strong>Verifique os campos abaixo:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('consultas.update', $consulta->id) }}" method="POST">
            @csrf
            @method('PUT')

            @include('consultas.partials.fields', ['consulta' => $consulta])

            <div class="form-actions">
                <a href="{{ route('gestantes.show', $gestante) }}" class="btn-secondary-outline">Voltar à gestante</a>
                <div class="form-actions-end">
                    <button type="submit" class="btn-primary-custom">Atualizar consulta</button>
                </div>
            </div>
        </form>
    </div>
@endsection
