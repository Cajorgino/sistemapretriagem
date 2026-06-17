@php
    /** @var \App\Models\Consulta|null $consulta */
    $c = $consulta ?? null;
    $bool = fn (string $key, int $default = 0): int => (int) old($key, $c !== null ? (int) $c->{$key} : $default);
    $liquido = mb_strtolower((string) old('polidramnio_oligoidramnio', $c?->polidramnio_oligoidramnio ?? ''));
    $polidramnioVal = (int) old('polidramnio', str_contains($liquido, 'polidram') ? 1 : 0);
    $oligodramnioVal = (int) old('oligodramnio', (str_contains($liquido, 'oligodram') || str_contains($liquido, 'oligoidram')) ? 1 : 0);
    $exposicaoVal = $bool('exposicao_ocupacional');
    $solventesVal = (int) old('exposicao_solventes', $exposicaoVal);
    $pesticidasVal = (int) old('exposicao_pesticidas', $exposicaoVal);
    $idadeMaternaVal = old('idade_materna', $c?->idade_materna);
@endphp

<style>
    .ccf-form { display: flex; flex-direction: column; gap: 28px; }
    .ccf-form .form-section { padding: 24px 26px; margin: 0; }
    .ccf-form .form-section-title { margin-bottom: 20px; padding-bottom: 14px; }
    .ccf-form .form-field { margin-bottom: 20px; }
    .ccf-form .form-field-grid { gap: 20px 24px; }
    .ccf-form .form-hint {
        font-size: 12px; color: var(--muted); margin-top: 8px; line-height: 1.45;
    }
    .ccf-form .form-hint--alert {
        color: #c0392b; font-weight: 600;
    }
    .ccf-form .form-section-desc {
        font-size: 14px; color: var(--muted); margin: -8px 0 20px; line-height: 1.5;
    }
</style>

<div class="ccf-form form-page-grid form-page-grid--2">

    <div class="form-section form-section--wide">
        <h3 class="form-section-title">Consulta e idade gestacional</h3>
        <p class="form-section-desc">Campos clínicos utilizados pelo modelo de predição de cardiopatia congênita (GestRisk).</p>
        <div class="form-field-grid form-field-grid--2">
            <div class="form-field">
                <label class="form-label" for="data_consulta">Data da consulta</label>
                <input class="form-input" type="date" name="data_consulta" id="data_consulta" required
                       value="{{ old('data_consulta', $c && $c->data_consulta ? $c->data_consulta->format('Y-m-d') : date('Y-m-d')) }}">
            </div>
            <div class="form-field">
                <label class="form-label" for="idade_gestacional">Idade gestacional (semanas)</label>
                <input class="form-input" type="number" name="idade_gestacional" id="idade_gestacional" min="11" max="28" step="0.1" required
                       value="{{ old('idade_gestacional', $c?->idade_gestacional) }}">
            </div>
            <div class="form-field">
                <label class="form-label" for="usg_precoce_confirmada">USG precoce confirmou a IG</label>
                <select class="form-select" name="usg_precoce_confirmada" id="usg_precoce_confirmada" required>
                    <option value="0" @selected($bool('usg_precoce_confirmada') === 0)>Não</option>
                    <option value="1" @selected($bool('usg_precoce_confirmada') === 1)>Sim</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="tipo_gestacao">Tipo de gestação</label>
                <select class="form-select" name="tipo_gestacao" id="tipo_gestacao" required>
                    <option value="">Selecione…</option>
                    <option value="única" @selected(old('tipo_gestacao', $c?->tipo_gestacao) === 'única')>Única</option>
                    <option value="gemelar" @selected(old('tipo_gestacao', $c?->tipo_gestacao) === 'gemelar')>Gemelar</option>
                </select>
            </div>
            <div class="form-field" id="campo-corionicidade">
                <label class="form-label" for="corionicidade">Corionicidade (gestação múltipla)</label>
                <select class="form-select" name="corionicidade" id="corionicidade">
                    <option value="">Não se aplica</option>
                    @foreach (['Monocoriônica', 'Dicoriônica'] as $opt)
                        <option value="{{ $opt }}" @selected(old('corionicidade', $c?->corionicidade) === $opt)>{{ $opt }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="form-section form-section--wide">
        <h3 class="form-section-title">Dados maternos</h3>
        <div class="form-field-grid form-field-grid--2">
            <div class="form-field">
                <label class="form-label" for="idade_materna">Idade materna (anos)</label>
                <input class="form-input" type="number" name="idade_materna" id="idade_materna" min="14" max="48" step="0.1" required
                       value="{{ $idadeMaternaVal }}">
                <p class="form-hint" id="idade-materna-hint">Atenção especial se &lt; 18 ou &gt; 35 anos.</p>
            </div>
            <div class="form-field">
                <label class="form-label" for="etnia">Etnia</label>
                <select class="form-select" name="etnia" id="etnia" required>
                    <option value="">Selecione…</option>
                    @foreach (['Branca', 'Parda', 'Preta', 'Amarela', 'Indígena'] as $opt)
                        <option value="{{ $opt }}" @selected(old('etnia', $c?->etnia) === $opt)>{{ $opt }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="imc_pre_gestacional">IMC pré-gestacional</label>
                <input class="form-input" type="number" step="0.1" name="imc_pre_gestacional" id="imc_pre_gestacional" min="16" max="45" required
                       value="{{ old('imc_pre_gestacional', $c?->imc_pre_gestacional) }}">
            </div>
            <div class="form-field">
                <label class="form-label" for="paridade">Paridade</label>
                <input class="form-input" type="number" name="paridade" id="paridade" min="0" max="6" required
                       value="{{ old('paridade', $c?->paridade ?? 0) }}">
            </div>
            <div class="form-field">
                <label class="form-label" for="abortamentos_previos">Abortamentos prévios</label>
                <input class="form-input" type="number" name="abortamentos_previos" id="abortamentos_previos" min="0" max="4" required
                       value="{{ old('abortamentos_previos', $c?->abortamentos_previos ?? 0) }}">
            </div>
            <div class="form-field">
                <label class="form-label" for="historico_natimorto">História de natimorto</label>
                <select class="form-select" name="historico_natimorto" id="historico_natimorto" required>
                    <option value="0" @selected($bool('historico_natimorto') === 0)>Não</option>
                    <option value="1" @selected($bool('historico_natimorto') === 1)>Sim</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="historico_filho_anterior_chd">Filho anterior com cardiopatia congênita</label>
                <select class="form-select" name="historico_filho_anterior_chd" id="historico_filho_anterior_chd" required>
                    <option value="0" @selected($bool('historico_filho_anterior_chd') === 0)>Não</option>
                    <option value="1" @selected($bool('historico_filho_anterior_chd') === 1)>Sim</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="historico_familiar_chd">História familiar de cardiopatia (1º grau)</label>
                <select class="form-select" name="historico_familiar_chd" id="historico_familiar_chd" required>
                    <option value="0" @selected($bool('historico_familiar_chd') === 0)>Não</option>
                    <option value="1" @selected($bool('historico_familiar_chd') === 1)>Sim</option>
                </select>
            </div>
        </div>
    </div>

    <div class="form-section">
        <h3 class="form-section-title">Comorbidades</h3>
        <div class="form-field-grid form-field-grid--2">
            <div class="form-field">
                <label class="form-label" for="diabetes_pre_gestacional">Diabetes mellitus pré-gestacional</label>
                <select class="form-select" name="diabetes_pre_gestacional" id="diabetes_pre_gestacional" required>
                    <option value="0" @selected($bool('diabetes_pre_gestacional') === 0)>Não</option>
                    <option value="1" @selected($bool('diabetes_pre_gestacional') === 1)>Sim</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="diabetes_gestacional">Diabetes mellitus gestacional</label>
                <select class="form-select" name="diabetes_gestacional" id="diabetes_gestacional" required>
                    <option value="0" @selected($bool('diabetes_gestacional') === 0)>Não</option>
                    <option value="1" @selected($bool('diabetes_gestacional') === 1)>Sim</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="lupus_eritomatoso_sistemico">Lúpus eritematoso sistêmico</label>
                <select class="form-select" name="lupus_eritomatoso_sistemico" id="lupus_eritomatoso_sistemico" required>
                    <option value="0" @selected($bool('lupus_eritomatoso_sistemico') === 0)>Não</option>
                    <option value="1" @selected($bool('lupus_eritomatoso_sistemico') === 1)>Sim</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="fenilcetonuria">Fenilcetonúria</label>
                <select class="form-select" name="fenilcetonuria" id="fenilcetonuria" required>
                    <option value="0" @selected($bool('fenilcetonuria') === 0)>Não</option>
                    <option value="1" @selected($bool('fenilcetonuria') === 1)>Sim</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="doencas_tireoidianas">Doenças tireoidianas</label>
                <select class="form-select" name="doencas_tireoidianas" id="doencas_tireoidianas" required>
                    <option value="0" @selected($bool('doencas_tireoidianas') === 0)>Não</option>
                    <option value="1" @selected($bool('doencas_tireoidianas') === 1)>Sim</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="hipertensao_cronica">Hipertensão crônica</label>
                <select class="form-select" name="hipertensao_cronica" id="hipertensao_cronica" required>
                    <option value="0" @selected($bool('hipertensao_cronica') === 0)>Não</option>
                    <option value="1" @selected($bool('hipertensao_cronica') === 1)>Sim</option>
                </select>
            </div>
        </div>
    </div>

    <div class="form-section">
        <h3 class="form-section-title">Infecções</h3>
        <div class="form-field-grid form-field-grid--2">
            <div class="form-field">
                <label class="form-label" for="rubeola">Rubéola</label>
                <select class="form-select" name="rubeola" id="rubeola" required>
                    <option value="0" @selected($bool('rubeola') === 0)>Não</option>
                    <option value="1" @selected($bool('rubeola') === 1)>Sim</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="citomegalovirus">Citomegalovírus</label>
                <select class="form-select" name="citomegalovirus" id="citomegalovirus" required>
                    <option value="0" @selected($bool('citomegalovirus') === 0)>Não</option>
                    <option value="1" @selected($bool('citomegalovirus') === 1)>Sim</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="toxoplasmose">Toxoplasmose</label>
                <select class="form-select" name="toxoplasmose" id="toxoplasmose" required>
                    <option value="0" @selected($bool('toxoplasmose') === 0)>Não</option>
                    <option value="1" @selected($bool('toxoplasmose') === 1)>Sim</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="sifilis">Sífilis</label>
                <select class="form-select" name="sifilis" id="sifilis" required>
                    <option value="0" @selected($bool('sifilis') === 0)>Não</option>
                    <option value="1" @selected($bool('sifilis') === 1)>Sim</option>
                </select>
            </div>
        </div>
    </div>

    <div class="form-section form-section--wide">
        <h3 class="form-section-title">Medicações de risco</h3>
        <div class="form-field-grid form-field-grid--2">
            <div class="form-field">
                <label class="form-label" for="uso_isotretinoina">Isotretinoína</label>
                <select class="form-select" name="uso_isotretinoina" id="uso_isotretinoina" required>
                    <option value="0" @selected($bool('uso_isotretinoina') === 0)>Não</option>
                    <option value="1" @selected($bool('uso_isotretinoina') === 1)>Sim</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="uso_acido_valproico">Ácido valproico</label>
                <select class="form-select" name="uso_acido_valproico" id="uso_acido_valproico" required>
                    <option value="0" @selected($bool('uso_acido_valproico') === 0)>Não</option>
                    <option value="1" @selected($bool('uso_acido_valproico') === 1)>Sim</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="uso_litio">Lítio</label>
                <select class="form-select" name="uso_litio" id="uso_litio" required>
                    <option value="0" @selected($bool('uso_litio') === 0)>Não</option>
                    <option value="1" @selected($bool('uso_litio') === 1)>Sim</option>
                </select>
            </div>
        </div>
    </div>

    <div class="form-section form-section--wide">
        <h3 class="form-section-title">Hábitos e exposições</h3>
        <div class="form-field-grid form-field-grid--2">
            <div class="form-field">
                <label class="form-label" for="alcoolismo">Álcool</label>
                <select class="form-select" name="alcoolismo" id="alcoolismo" required>
                    <option value="0" @selected($bool('alcoolismo') === 0)>Não</option>
                    <option value="1" @selected($bool('alcoolismo') === 1)>Sim</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="tabagismo">Tabaco</label>
                <select class="form-select" name="tabagismo" id="tabagismo" required>
                    <option value="0" @selected($bool('tabagismo') === 0)>Não</option>
                    <option value="1" @selected($bool('tabagismo') === 1)>Sim</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="drogas_ilicitas">Drogas ilícitas</label>
                <select class="form-select" name="drogas_ilicitas" id="drogas_ilicitas" required>
                    <option value="0" @selected($bool('drogas_ilicitas') === 0)>Não</option>
                    <option value="1" @selected($bool('drogas_ilicitas') === 1)>Sim</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="exposicao_solventes">Exposição ocupacional — solventes</label>
                <select class="form-select" name="exposicao_solventes" id="exposicao_solventes" required>
                    <option value="0" @selected($solventesVal === 0)>Não</option>
                    <option value="1" @selected($solventesVal === 1)>Sim</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="exposicao_pesticidas">Exposição ocupacional — pesticidas</label>
                <select class="form-select" name="exposicao_pesticidas" id="exposicao_pesticidas" required>
                    <option value="0" @selected($pesticidasVal === 0)>Não</option>
                    <option value="1" @selected($pesticidasVal === 1)>Sim</option>
                </select>
            </div>
        </div>
    </div>

    <div class="form-section form-section--wide">
        <h3 class="form-section-title">Marcadores ecográficos fetais</h3>
        <div class="form-field-grid form-field-grid--2">
            <div class="form-field">
                <label class="form-label" for="translucencia_nucal_aumentada">Translucência nucal aumentada</label>
                <select class="form-select" name="translucencia_nucal_aumentada" id="translucencia_nucal_aumentada" required>
                    <option value="0" @selected($bool('translucencia_nucal_aumentada') === 0)>Não</option>
                    <option value="1" @selected($bool('translucencia_nucal_aumentada') === 1)>Sim</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="doppler_ducto_venoso">Alterações no ducto venoso</label>
                <select class="form-select" name="doppler_ducto_venoso" id="doppler_ducto_venoso" required>
                    <option value="">Selecione…</option>
                    @foreach (['Ausente', 'Fluxo normal', 'Fluxo aumentado', 'Fluxo reverso'] as $opt)
                        <option value="{{ $opt }}" @selected(old('doppler_ducto_venoso', $c?->doppler_ducto_venoso) === $opt)>{{ $opt }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="regurgitacao_tricuspide_fetal">Regurgitação tricúspide fetal</label>
                <select class="form-select" name="regurgitacao_tricuspide_fetal" id="regurgitacao_tricuspide_fetal" required>
                    <option value="0" @selected($bool('regurgitacao_tricuspide_fetal') === 0)>Não</option>
                    <option value="1" @selected($bool('regurgitacao_tricuspide_fetal') === 1)>Sim</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="malformacoes_extracardiacas_associadas">Malformações extracardíacas associadas</label>
                <select class="form-select" name="malformacoes_extracardiacas_associadas" id="malformacoes_extracardiacas_associadas" required>
                    <option value="0" @selected($bool('malformacoes_extracardiacas_associadas') === 0)>Não</option>
                    <option value="1" @selected($bool('malformacoes_extracardiacas_associadas') === 1)>Sim</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="crescimento_fetal_rcf_iugr">Restrição de crescimento fetal (RCF/IUGR)</label>
                <select class="form-select" name="crescimento_fetal_rcf_iugr" id="crescimento_fetal_rcf_iugr" required>
                    <option value="0" @selected($bool('crescimento_fetal_rcf_iugr') === 0)>Não</option>
                    <option value="1" @selected($bool('crescimento_fetal_rcf_iugr') === 1)>Sim</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="polidramnio">Polidrâmnio</label>
                <select class="form-select" name="polidramnio" id="polidramnio" required>
                    <option value="0" @selected($polidramnioVal === 0)>Não</option>
                    <option value="1" @selected($polidramnioVal === 1)>Sim</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="oligodramnio">Oligoidrâmnio</label>
                <select class="form-select" name="oligodramnio" id="oligodramnio" required>
                    <option value="0" @selected($oligodramnioVal === 0)>Não</option>
                    <option value="1" @selected($oligodramnioVal === 1)>Sim</option>
                </select>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const idade = document.getElementById('idade_materna');
    const hint = document.getElementById('idade-materna-hint');
    const tipo = document.getElementById('tipo_gestacao');
    const corio = document.getElementById('campo-corionicidade');

    function atualizarIdadeHint() {
        if (!idade || !hint) return;
        const v = parseFloat(idade.value);
        if (!isNaN(v) && (v < 18 || v > 35)) {
            hint.classList.add('form-hint--alert');
            hint.textContent = v < 18
                ? 'Atenção: idade materna abaixo de 18 anos (grupo de risco).'
                : 'Atenção: idade materna acima de 35 anos (grupo de risco).';
        } else {
            hint.classList.remove('form-hint--alert');
            hint.textContent = 'Atenção especial se < 18 ou > 35 anos.';
        }
    }

    function atualizarCorionicidade() {
        if (!tipo || !corio) return;
        const gemelar = tipo.value === 'gemelar';
        corio.style.display = gemelar ? '' : 'none';
        if (!gemelar) {
            const sel = document.getElementById('corionicidade');
            if (sel) sel.value = '';
        }
    }

    if (idade) {
        idade.addEventListener('input', atualizarIdadeHint);
        atualizarIdadeHint();
    }
    if (tipo) {
        tipo.addEventListener('change', atualizarCorionicidade);
        atualizarCorionicidade();
    }
});
</script>
@endpush
