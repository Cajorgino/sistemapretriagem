@extends('layouts.app')

@section('title', $gestante->nome_exibicao.' — Cardioprenatal')

@section('content')
<div style="padding-bottom: 32px;">
    <div style="max-width: 1152px; margin: 0 auto; display: flex; flex-direction: column; gap: 28px;">

        <!-- Cabeçalho -->
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
                            Nascimento:
                            <strong style="color: var(--text);">
                                {{ $gestante->data_nascimento ? \Carbon\Carbon::parse($gestante->data_nascimento)->format('d/m/Y') : 'Não informada' }}
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
                    </div>
                </div>

                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
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

        <!-- Seção de Análise da IA (aparece após clicar no botão) -->
        @if (session('resultado_analise_ia') && is_array(session('resultado_analise_ia')))
            @php $resultado_ia = session('resultado_analise_ia'); @endphp
            <div class="main-card">
                <h2 class="card-title" style="font-size: 20px; margin-bottom: 16px;">Análise de IA</h2>
                @if (!empty($resultado_ia['imagens']))
                    <div class="form-page-grid form-page-grid--3">
                        @foreach ($resultado_ia['imagens'] as $titulo => $imagem_base64)
                            <div class="form-section" style="padding: 18px;">
                                <h3 class="form-section-title" style="font-size: 16px; margin-bottom: 12px;">{{ str_replace('_', ' ', $titulo) }}</h3>
                                <img src="{{ $imagem_base64 }}" alt="Gráfico de {{ $titulo }}" style="width: 100%; height: auto; border-radius: 12px;">
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        <!-- Consultas -->
        @forelse ($gestante->consultas as $consulta)
            @php
                $idade = null;
                if ($gestante->data_nascimento && $consulta->data_consulta) {
                    $idade = \Carbon\Carbon::parse($consulta->data_consulta)->diffInYears(\Carbon\Carbon::parse($gestante->data_nascimento));
                }
            @endphp
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
                        <span class="consulta-pill {{ $consulta->chd_confirmada ? 'consulta-pill--alert' : 'consulta-pill--ok' }}">
                            CHD {{ $consulta->chd_confirmada ? 'confirmada' : 'não confirmada' }}
                        </span>
                        <a href="{{ route('consultas.edit', $consulta->id) }}" class="btn-table btn-table--primary">Editar consulta</a>
                    </div>
                </div>

                <div class="form-page-grid form-page-grid--2" style="margin-top: 16px;">
                    <div class="form-section">
                        <h3 class="form-section-title">Dados da gestante (consulta)</h3>
                        <div class="form-field-grid form-field-grid--2">
                            <div class="form-field" style="margin-bottom: 0;">
                                <span class="form-label">Idade na consulta</span>
                                <p class="consulta-valor">{{ $idade !== null ? $idade.' anos' : '—' }}</p>
                            </div>
                            <div class="form-field" style="margin-bottom: 0;">
                                <span class="form-label">Idade gestacional</span>
                                <p class="consulta-valor">{{ $consulta->idade_gestacional ?? '—' }} sem</p>
                            </div>
                            <div class="form-field" style="margin-bottom: 0;">
                                <span class="form-label">Altura</span>
                                <p class="consulta-valor">{{ $consulta->altura ?? '—' }} cm</p>
                            </div>
                            <div class="form-field" style="margin-bottom: 0;">
                                <span class="form-label">Peso</span>
                                <p class="consulta-valor">{{ $consulta->peso ?? '—' }} kg</p>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="form-section-title">Sinais vitais</h3>
                        <div class="form-field-grid form-field-grid--2">
                            <div class="form-field" style="margin-bottom: 0;">
                                <span class="form-label">Pressão sistólica</span>
                                <p class="consulta-valor">{{ $consulta->pressao_sistolica ?? '—' }}</p>
                            </div>
                            <div class="form-field" style="margin-bottom: 0;">
                                <span class="form-label">BPM materno</span>
                                <p class="consulta-valor">{{ $consulta->bpm_materno ?? '—' }}</p>
                            </div>
                            <div class="form-field" style="margin-bottom: 0;">
                                <span class="form-label">Saturação</span>
                                <p class="consulta-valor">{{ $consulta->saturacao !== null && $consulta->saturacao !== '' ? $consulta->saturacao.'%' : '—' }}</p>
                            </div>
                            <div class="form-field" style="margin-bottom: 0;">
                                <span class="form-label">Temperatura</span>
                                <p class="consulta-valor">{{ $consulta->temperatura_corporal !== null && $consulta->temperatura_corporal !== '' ? $consulta->temperatura_corporal.' °C' : '—' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="form-section-title">Dados laboratoriais</h3>
                        <div class="form-field-grid form-field-grid--2">
                            <div class="form-field" style="margin-bottom: 0;">
                                <span class="form-label">Glicemia jejum</span>
                                <p class="consulta-valor">{{ $consulta->glicemia_jejum ?? '—' }}</p>
                            </div>
                            <div class="form-field" style="margin-bottom: 0;">
                                <span class="form-label">Glicemia pós-prandial</span>
                                <p class="consulta-valor">{{ $consulta->glicemia_pos_prandial ?? '—' }}</p>
                            </div>
                            <div class="form-field" style="margin-bottom: 0;">
                                <span class="form-label">HbA1c</span>
                                <p class="consulta-valor">{{ $consulta->hba1c !== null && $consulta->hba1c !== '' ? $consulta->hba1c.'%' : '—' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="form-section-title">Dados fetais</h3>
                        <div class="form-field-grid form-field-grid--2">
                            <div class="form-field" style="margin-bottom: 0;">
                                <span class="form-label">FC fetal</span>
                                <p class="consulta-valor">{{ $consulta->frequencia_cardiaca_fetal ?? '—' }}</p>
                            </div>
                            <div class="form-field" style="margin-bottom: 0;">
                                <span class="form-label">Circ. cefálica</span>
                                <p class="consulta-valor">{{ $consulta->circunferencia_cefalica_fetal_mm !== null && $consulta->circunferencia_cefalica_fetal_mm !== '' ? $consulta->circunferencia_cefalica_fetal_mm.' mm' : '—' }}</p>
                            </div>
                            <div class="form-field" style="margin-bottom: 0;">
                                <span class="form-label">Circ. abdominal</span>
                                <p class="consulta-valor">{{ $consulta->circunferencia_abdominal_mm !== null && $consulta->circunferencia_abdominal_mm !== '' ? $consulta->circunferencia_abdominal_mm.' mm' : '—' }}</p>
                            </div>
                            <div class="form-field" style="margin-bottom: 0;">
                                <span class="form-label">Compr. fêmur</span>
                                <p class="consulta-valor">{{ $consulta->comprimento_femur_mm !== null && $consulta->comprimento_femur_mm !== '' ? $consulta->comprimento_femur_mm.' mm' : '—' }}</p>
                            </div>
                            <div class="form-field" style="margin-bottom: 0;">
                                <span class="form-label">Translucência nucal</span>
                                <p class="consulta-valor">{{ $consulta->translucencia_nucal_mm !== null && $consulta->translucencia_nucal_mm !== '' ? $consulta->translucencia_nucal_mm.' mm' : '—' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="form-section form-section--wide">
                        <h3 class="form-section-title">Avaliação cardíaca</h3>
                        <div class="form-field-grid form-field-grid--2">
                            <div class="form-field" style="margin-bottom: 0;">
                                <span class="form-label">Doppler ducto venoso</span>
                                <p class="consulta-valor">{{ $consulta->doppler_ducto_venoso ?: '—' }}</p>
                            </div>
                            <div class="form-field" style="margin-bottom: 0;">
                                <span class="form-label">Eixo cardíaco</span>
                                <p class="consulta-valor">{{ $consulta->eixo_cardiaco ?: '—' }}</p>
                            </div>
                            <div class="form-field" style="margin-bottom: 0;">
                                <span class="form-label">Quatro câmaras</span>
                                <p class="consulta-valor">{{ $consulta->quatro_camaras ?: '—' }}</p>
                            </div>
                        </div>
                        @if ($consulta->chd_confirmada && $consulta->tipo_chd)
                            <div class="consulta-chd-box">
                                Tipo de CHD: {{ $consulta->tipo_chd }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="main-card" style="text-align: center; color: var(--muted); padding: 40px 24px;">
                Nenhuma consulta registrada.
            </div>
        @endforelse

    </div>
</div>
@endsection