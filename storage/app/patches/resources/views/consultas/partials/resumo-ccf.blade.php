@php
    $simNao = fn ($v) => $v ? 'Sim' : 'Não';
    $liquido = mb_strtolower((string) ($consulta->polidramnio_oligoidramnio ?? ''));
@endphp

<div class="form-page-grid form-page-grid--2 ccf-form" style="margin-top: 20px; gap: 24px;">
    <div class="form-section form-section--wide">
        <h3 class="form-section-title">Consulta e idade gestacional</h3>
        <div class="form-field-grid form-field-grid--2">
            <div class="form-field"><span class="form-label">USG precoce confirmou IG</span><p class="consulta-valor">{{ $simNao($consulta->usg_precoce_confirmada) }}</p></div>
            <div class="form-field"><span class="form-label">Tipo de gestação</span><p class="consulta-valor">{{ $consulta->tipo_gestacao ?: '—' }}</p></div>
            <div class="form-field"><span class="form-label">Corionicidade</span><p class="consulta-valor">{{ $consulta->corionicidade ?: '—' }}</p></div>
        </div>
    </div>

    <div class="form-section form-section--wide">
        <h3 class="form-section-title">Dados maternos</h3>
        <div class="form-field-grid form-field-grid--2">
            <div class="form-field"><span class="form-label">Idade materna</span><p class="consulta-valor">{{ $consulta->idade_materna ?? '—' }} anos</p></div>
            <div class="form-field"><span class="form-label">Etnia</span><p class="consulta-valor">{{ $consulta->etnia ?: '—' }}</p></div>
            <div class="form-field"><span class="form-label">IMC pré-gestacional</span><p class="consulta-valor">{{ $consulta->imc_pre_gestacional ?? '—' }}</p></div>
            <div class="form-field"><span class="form-label">Paridade</span><p class="consulta-valor">{{ $consulta->paridade ?? '—' }}</p></div>
            <div class="form-field"><span class="form-label">Abortamentos prévios</span><p class="consulta-valor">{{ $consulta->abortamentos_previos ?? '—' }}</p></div>
            <div class="form-field"><span class="form-label">Natimorto</span><p class="consulta-valor">{{ $simNao($consulta->historico_natimorto) }}</p></div>
            <div class="form-field"><span class="form-label">Filho anterior com CHD</span><p class="consulta-valor">{{ $simNao($consulta->historico_filho_anterior_chd) }}</p></div>
            <div class="form-field"><span class="form-label">História familiar CHD (1º grau)</span><p class="consulta-valor">{{ $simNao($consulta->historico_familiar_chd) }}</p></div>
        </div>
    </div>

    <div class="form-section">
        <h3 class="form-section-title">Comorbidades</h3>
        <div class="form-field-grid form-field-grid--2">
            <div class="form-field"><span class="form-label">DM pré-gestacional</span><p class="consulta-valor">{{ $simNao($consulta->diabetes_pre_gestacional) }}</p></div>
            <div class="form-field"><span class="form-label">DM gestacional</span><p class="consulta-valor">{{ $simNao($consulta->diabetes_gestacional) }}</p></div>
            <div class="form-field"><span class="form-label">Lúpus</span><p class="consulta-valor">{{ $simNao($consulta->lupus_eritomatoso_sistemico) }}</p></div>
            <div class="form-field"><span class="form-label">Fenilcetonúria</span><p class="consulta-valor">{{ $simNao($consulta->fenilcetonuria) }}</p></div>
            <div class="form-field"><span class="form-label">Doenças tireoidianas</span><p class="consulta-valor">{{ $simNao($consulta->doencas_tireoidianas) }}</p></div>
            <div class="form-field"><span class="form-label">Hipertensão crônica</span><p class="consulta-valor">{{ $simNao($consulta->hipertensao_cronica) }}</p></div>
        </div>
    </div>

    <div class="form-section">
        <h3 class="form-section-title">Infecções</h3>
        <div class="form-field-grid form-field-grid--2">
            <div class="form-field"><span class="form-label">Rubéola</span><p class="consulta-valor">{{ $simNao($consulta->rubeola) }}</p></div>
            <div class="form-field"><span class="form-label">CMV</span><p class="consulta-valor">{{ $simNao($consulta->citomegalovirus) }}</p></div>
            <div class="form-field"><span class="form-label">Toxoplasmose</span><p class="consulta-valor">{{ $simNao($consulta->toxoplasmose) }}</p></div>
            <div class="form-field"><span class="form-label">Sífilis</span><p class="consulta-valor">{{ $simNao($consulta->sifilis) }}</p></div>
        </div>
    </div>

    <div class="form-section form-section--wide">
        <h3 class="form-section-title">Medicações, hábitos e exposições</h3>
        <div class="form-field-grid form-field-grid--2">
            <div class="form-field"><span class="form-label">Isotretinoína</span><p class="consulta-valor">{{ $simNao($consulta->uso_isotretinoina) }}</p></div>
            <div class="form-field"><span class="form-label">Ácido valproico</span><p class="consulta-valor">{{ $simNao($consulta->uso_acido_valproico) }}</p></div>
            <div class="form-field"><span class="form-label">Lítio</span><p class="consulta-valor">{{ $simNao($consulta->uso_litio) }}</p></div>
            <div class="form-field"><span class="form-label">Álcool</span><p class="consulta-valor">{{ $simNao($consulta->alcoolismo) }}</p></div>
            <div class="form-field"><span class="form-label">Tabaco</span><p class="consulta-valor">{{ $simNao($consulta->tabagismo) }}</p></div>
            <div class="form-field"><span class="form-label">Drogas ilícitas</span><p class="consulta-valor">{{ $simNao($consulta->drogas_ilicitas) }}</p></div>
            <div class="form-field"><span class="form-label">Exposição ocupacional</span><p class="consulta-valor">{{ $simNao($consulta->exposicao_ocupacional) }}</p></div>
        </div>
    </div>

    <div class="form-section form-section--wide">
        <h3 class="form-section-title">Marcadores ecográficos fetais</h3>
        <div class="form-field-grid form-field-grid--2">
            <div class="form-field"><span class="form-label">TN aumentada</span><p class="consulta-valor">{{ $simNao($consulta->translucencia_nucal_aumentada) }}</p></div>
            <div class="form-field"><span class="form-label">Ducto venoso</span><p class="consulta-valor">{{ $consulta->doppler_ducto_venoso ?: '—' }}</p></div>
            <div class="form-field"><span class="form-label">Regurgitação tricúspide</span><p class="consulta-valor">{{ $simNao($consulta->regurgitacao_tricuspide_fetal) }}</p></div>
            <div class="form-field"><span class="form-label">Malformações extracardíacas</span><p class="consulta-valor">{{ $simNao($consulta->malformacoes_extracardiacas_associadas) }}</p></div>
            <div class="form-field"><span class="form-label">RCF / IUGR</span><p class="consulta-valor">{{ $simNao($consulta->crescimento_fetal_rcf_iugr) }}</p></div>
            <div class="form-field"><span class="form-label">Polidrâmnio</span><p class="consulta-valor">{{ str_contains($liquido, 'polidram') ? 'Sim' : 'Não' }}</p></div>
            <div class="form-field"><span class="form-label">Oligoidrâmnio</span><p class="consulta-valor">{{ (str_contains($liquido, 'oligodram') || str_contains($liquido, 'oligoidram')) ? 'Sim' : 'Não' }}</p></div>
        </div>
    </div>
</div>
