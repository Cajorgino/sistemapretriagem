@php
    /** @var \App\Models\Consulta|null $consulta */
    $c = $consulta ?? null;
    $bool = fn (string $key, int $default = 0): int => (int) old($key, $c !== null ? (int) $c->{$key} : $default);
@endphp

<div class="form-page-grid form-page-grid--3">

    <div class="form-section">
        <h3 class="form-section-title">Dados da consulta</h3>
        <div class="form-field">
            <label class="form-label" for="data_consulta">Data da consulta</label>
            <input class="form-input" type="date" name="data_consulta" id="data_consulta" required
                   value="{{ old('data_consulta', $c && $c->data_consulta ? $c->data_consulta->format('Y-m-d') : date('Y-m-d')) }}">
        </div>
        <div class="form-field">
            <label class="form-label" for="idade_gestacional">Idade gestacional (sem.)</label>
            <input class="form-input" type="number" name="idade_gestacional" id="idade_gestacional" min="4" max="42" required
                   value="{{ old('idade_gestacional', $c?->idade_gestacional) }}">
        </div>
    </div>

    <div class="form-section">
        <h3 class="form-section-title">Dados maternos</h3>
        <div class="form-field-grid form-field-grid--2">
            <div class="form-field">
                <label class="form-label" for="altura">Altura (cm)</label>
                <input class="form-input" type="number" name="altura" id="altura" min="140" max="210"
                       value="{{ old('altura', $c?->altura) }}">
            </div>
            <div class="form-field">
                <label class="form-label" for="peso">Peso (kg)</label>
                <input class="form-input" type="number" name="peso" id="peso" step="0.1" min="30" max="300"
                       value="{{ old('peso', $c?->peso) }}">
            </div>
        </div>
        <div class="form-field">
            <label class="form-label" for="obesidade_pre_gestacional">Obesidade pré-gestacional</label>
            <select class="form-select" name="obesidade_pre_gestacional" id="obesidade_pre_gestacional" required>
                <option value="0" @selected($bool('obesidade_pre_gestacional') === 0)>Não</option>
                <option value="1" @selected($bool('obesidade_pre_gestacional') === 1)>Sim</option>
            </select>
        </div>
        <div class="form-field">
            <label class="form-label" for="diabetes_gestacional">Diabetes gestacional</label>
            <select class="form-select" name="diabetes_gestacional" id="diabetes_gestacional" required>
                <option value="0" @selected($bool('diabetes_gestacional') === 0)>Não</option>
                <option value="1" @selected($bool('diabetes_gestacional') === 1)>Sim</option>
            </select>
        </div>
    </div>

    <div class="form-section">
        <h3 class="form-section-title">Sinais vitais</h3>
        <div class="form-field-grid form-field-grid--2">
            <div class="form-field">
                <label class="form-label" for="pressao_sistolica">Pressão sistólica</label>
                <input class="form-input" type="number" name="pressao_sistolica" id="pressao_sistolica"
                       value="{{ old('pressao_sistolica', $c?->pressao_sistolica) }}">
            </div>
            <div class="form-field">
                <label class="form-label" for="bpm_materno">BPM materno</label>
                <input class="form-input" type="number" name="bpm_materno" id="bpm_materno"
                       value="{{ old('bpm_materno', $c?->bpm_materno) }}">
            </div>
            <div class="form-field">
                <label class="form-label" for="saturacao">Saturação (%)</label>
                <input class="form-input" type="number" name="saturacao" id="saturacao" min="0" max="100"
                       value="{{ old('saturacao', $c?->saturacao) }}">
            </div>
            <div class="form-field">
                <label class="form-label" for="temperatura_corporal">Temperatura (°C)</label>
                <input class="form-input" type="number" step="0.1" name="temperatura_corporal" id="temperatura_corporal"
                       value="{{ old('temperatura_corporal', $c?->temperatura_corporal) }}">
            </div>
        </div>
    </div>

    <div class="form-section form-section--wide">
        <h3 class="form-section-title">Dados laboratoriais</h3>
        <div class="form-field-grid form-field-grid--2">
            <div class="form-field">
                <label class="form-label" for="glicemia_jejum">Glicemia jejum</label>
                <input class="form-input" type="number" step="0.01" name="glicemia_jejum" id="glicemia_jejum"
                       value="{{ old('glicemia_jejum', $c?->glicemia_jejum) }}">
            </div>
            <div class="form-field">
                <label class="form-label" for="glicemia_pos_prandial">Glicemia pós-prandial</label>
                <input class="form-input" type="number" step="0.01" name="glicemia_pos_prandial" id="glicemia_pos_prandial"
                       value="{{ old('glicemia_pos_prandial', $c?->glicemia_pos_prandial) }}">
            </div>
            <div class="form-field">
                <label class="form-label" for="hba1c">HbA1c (%)</label>
                <input class="form-input" type="number" step="0.01" name="hba1c" id="hba1c" min="0" max="20"
                       value="{{ old('hba1c', $c?->hba1c) }}">
            </div>
        </div>
    </div>

    <div class="form-section form-section--span-2">
        <h3 class="form-section-title">Fatores de risco</h3>
        <div class="form-field-grid form-field-grid--2">
            <div class="form-field">
                <label class="form-label" for="hipertensao">Hipertensão</label>
                <select class="form-select" name="hipertensao" id="hipertensao" required>
                    <option value="0" @selected($bool('hipertensao') === 0)>Não</option>
                    <option value="1" @selected($bool('hipertensao') === 1)>Sim</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="hipertensao_pre_eclampsia">Pré-eclâmpsia</label>
                <select class="form-select" name="hipertensao_pre_eclampsia" id="hipertensao_pre_eclampsia" required>
                    <option value="0" @selected($bool('hipertensao_pre_eclampsia') === 0)>Não</option>
                    <option value="1" @selected($bool('hipertensao_pre_eclampsia') === 1)>Sim</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="historico_familiar_chd">Histórico familiar CHD</label>
                <select class="form-select" name="historico_familiar_chd" id="historico_familiar_chd" required>
                    <option value="0" @selected($bool('historico_familiar_chd') === 0)>Não</option>
                    <option value="1" @selected($bool('historico_familiar_chd') === 1)>Sim</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="uso_medicamentos">Uso de medicamentos</label>
                <select class="form-select" name="uso_medicamentos" id="uso_medicamentos" required>
                    <option value="0" @selected($bool('uso_medicamentos') === 0)>Não</option>
                    <option value="1" @selected($bool('uso_medicamentos') === 1)>Sim</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="tabagismo">Tabagismo</label>
                <select class="form-select" name="tabagismo" id="tabagismo" required>
                    <option value="0" @selected($bool('tabagismo') === 0)>Não</option>
                    <option value="1" @selected($bool('tabagismo') === 1)>Sim</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="alcoolismo">Alcoolismo</label>
                <select class="form-select" name="alcoolismo" id="alcoolismo" required>
                    <option value="0" @selected($bool('alcoolismo') === 0)>Não</option>
                    <option value="1" @selected($bool('alcoolismo') === 1)>Sim</option>
                </select>
            </div>
        </div>
    </div>

    <div class="form-section">
        <h3 class="form-section-title">Dados fetais</h3>
        <div class="form-field">
            <label class="form-label" for="frequencia_cardiaca_fetal">Frequência cardíaca fetal</label>
            <input class="form-input" type="number" name="frequencia_cardiaca_fetal" id="frequencia_cardiaca_fetal"
                   value="{{ old('frequencia_cardiaca_fetal', $c?->frequencia_cardiaca_fetal) }}">
        </div>
        <div class="form-field">
            <label class="form-label" for="circunferencia_cefalica_fetal_mm">Circunferência cefálica (mm)</label>
            <input class="form-input" type="number" step="0.1" name="circunferencia_cefalica_fetal_mm" id="circunferencia_cefalica_fetal_mm"
                   value="{{ old('circunferencia_cefalica_fetal_mm', $c?->circunferencia_cefalica_fetal_mm) }}">
        </div>
        <div class="form-field">
            <label class="form-label" for="circunferencia_abdominal_mm">Circunferência abdominal (mm)</label>
            <input class="form-input" type="number" step="0.1" name="circunferencia_abdominal_mm" id="circunferencia_abdominal_mm"
                   value="{{ old('circunferencia_abdominal_mm', $c?->circunferencia_abdominal_mm) }}">
        </div>
        <div class="form-field">
            <label class="form-label" for="comprimento_femur_mm">Comprimento do fêmur (mm)</label>
            <input class="form-input" type="number" step="0.1" name="comprimento_femur_mm" id="comprimento_femur_mm"
                   value="{{ old('comprimento_femur_mm', $c?->comprimento_femur_mm) }}">
        </div>
        <div class="form-field">
            <label class="form-label" for="translucencia_nucal_mm">Translucência nucal (mm)</label>
            <input class="form-input" type="number" step="0.1" name="translucencia_nucal_mm" id="translucencia_nucal_mm"
                   value="{{ old('translucencia_nucal_mm', $c?->translucencia_nucal_mm) }}">
        </div>
    </div>

    <div class="form-section form-section--wide">
        <h3 class="form-section-title">Avaliação cardíaca</h3>
        <div class="form-field-grid form-field-grid--2">
            <div class="form-field">
                <label class="form-label" for="doppler_ducto_venoso">Doppler ducto venoso</label>
                <select class="form-select" name="doppler_ducto_venoso" id="doppler_ducto_venoso">
                    <option value="">Selecione…</option>
                    @foreach (['Ausente', 'Fluxo normal', 'Fluxo aumentado', 'Fluxo reverso'] as $opt)
                        <option value="{{ $opt }}" @selected(old('doppler_ducto_venoso', $c?->doppler_ducto_venoso) === $opt)>{{ $opt }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="eixo_cardiaco">Eixo cardíaco</label>
                <input class="form-input" type="text" name="eixo_cardiaco" id="eixo_cardiaco"
                       value="{{ old('eixo_cardiaco', $c?->eixo_cardiaco) }}"
                       placeholder="Ex.: 45°">
            </div>
            <div class="form-field">
                <label class="form-label" for="quatro_camaras">Quatro câmaras</label>
                <select class="form-select" name="quatro_camaras" id="quatro_camaras">
                    <option value="">Selecione…</option>
                    @foreach (['Normal', 'Não visível'] as $opt)
                        <option value="{{ $opt }}" @selected(old('quatro_camaras', $c?->quatro_camaras) === $opt)>{{ $opt }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="chd_confirmada">CHD confirmada</label>
                <select class="form-select" name="chd_confirmada" id="chd_confirmada" required>
                    <option value="0" @selected($bool('chd_confirmada') === 0)>Não</option>
                    <option value="1" @selected($bool('chd_confirmada') === 1)>Sim</option>
                </select>
            </div>
            <div class="form-field" style="grid-column: 1 / -1;">
                <label class="form-label" for="tipo_chd">Tipo de CHD</label>
                <select class="form-select" name="tipo_chd" id="tipo_chd">
                    <option value="">Selecione…</option>
                    @php
                        $tipos = [
                            'DSV — Defeito do Septo Ventricular' => 'DSV',
                            'DSA — Defeito do Septo Atrial' => 'DSA',
                            'Tetralogia de Fallot' => 'Tetralogia de Fallot',
                            'TGA — Transposição das Grandes Artérias' => 'TGA',
                            'Hipoplasia do Coração Esquerdo' => 'Hipoplasia VE',
                        ];
                    @endphp
                    @foreach ($tipos as $val => $label)
                        <option value="{{ $val }}" @selected(old('tipo_chd', $c?->tipo_chd) === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>
