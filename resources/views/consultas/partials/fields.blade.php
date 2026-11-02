@php
    /** @var \App\Models\Consulta|null $consulta */
    $c = $consulta ?? null;
    $etnias = ['Branca', 'Parda', 'Preta', 'Amarela', 'Indigena'];
    $etniaAtual = old('etnia', $c?->etnia);
    $paridadeAtual = old('paridade', $c?->paridade);
    $imcAtual = old('imc_gestacional_categoria', $c?->imc_gestacional_categoria);
    $liquidoAtual = old('polidramnio_oligoidramnio', $c?->polidramnio_oligoidramnio);
@endphp

<div class="consulta-form">
    <p class="consulta-form-hint">
        Estes são os campos do modelo treinado na planilha. Idade materna, idade gestacional, IMC numérico, sinais vitais e biometria fetal não entram no escore.
    </p>

    <section class="form-section form-section--wide">
        <h3 class="form-section-title">1. Registro da ficha</h3>
        <p style="margin: 0 0 12px; font-size: 13px; color: var(--muted); line-height: 1.45;">
            Os meses de vida e a cardiopatia confirmada descrevem a ficha. O modelo não usa esses dois campos.
        </p>
        <div class="form-field-grid form-field-grid--3">
            <div class="form-field">
                <label class="form-label" for="data_consulta">Data <span class="form-req">*</span></label>
                <input class="form-input" type="date" name="data_consulta" id="data_consulta" required
                       value="{{ old('data_consulta', $c && $c->data_consulta ? $c->data_consulta->format('Y-m-d') : date('Y-m-d')) }}">
            </div>
            <div class="form-field">
                <label class="form-label" for="idade_crianca_meses">Meses de vida</label>
                <input class="form-input" type="number" name="idade_crianca_meses" id="idade_crianca_meses" min="0" max="240"
                       value="{{ old('idade_crianca_meses', $c?->idade_crianca_meses) }}">
            </div>
        </div>
        <div class="bool-grid" style="margin-top: 8px;">
            @include('consultas.partials.bool-pill', ['name' => 'chd_confirmada', 'label' => 'Cardiopatia congênita confirmada', 'consulta' => $c])
        </div>
    </section>

    <section class="form-section form-section--wide">
        <h3 class="form-section-title">2. Campos do modelo</h3>
        <div class="form-field-grid form-field-grid--3">
            <div class="form-field">
                <label class="form-label" for="etnia">Etnia</label>
                <select class="form-select" name="etnia" id="etnia">
                    <option value="">Não informada</option>
                    @if ($etniaAtual && ! in_array($etniaAtual, $etnias, true))
                        <option value="{{ $etniaAtual }}" selected>{{ $etniaAtual }}</option>
                    @endif
                    @foreach ($etnias as $etnia)
                        <option value="{{ $etnia }}" @selected($etniaAtual === $etnia)>{{ $etnia }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="paridade">Paridade</label>
                <select class="form-select" name="paridade" id="paridade">
                    <option value="">Não informada</option>
                    @foreach ([0 => 'Primigesta', 1 => '1 filho', 2 => '2 filhos', 3 => 'Mais de 3 filhos'] as $valor => $rotulo)
                        <option value="{{ $valor }}" @selected((string) $paridadeAtual === (string) $valor)>{{ $rotulo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="imc_gestacional_categoria">IMC gestacional</label>
                <select class="form-select" name="imc_gestacional_categoria" id="imc_gestacional_categoria">
                    <option value="">Não informado</option>
                    <option value="0" @selected((string) $imcAtual === '0')>Normal</option>
                    <option value="1" @selected((string) $imcAtual === '1')>Acima de 24,9</option>
                    <option value="2" @selected((string) $imcAtual === '2')>Abaixo de 18,5</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="abortamentos_previos">Abortamentos prévios</label>
                <input class="form-input" type="number" name="abortamentos_previos" id="abortamentos_previos" min="0" max="20"
                       value="{{ old('abortamentos_previos', $c?->abortamentos_previos) }}">
            </div>
            <div class="form-field">
                <label class="form-label" for="tipo_gestacao">Gestação</label>
                <select class="form-select" name="tipo_gestacao" id="tipo_gestacao">
                    <option value="única" @selected(old('tipo_gestacao', $c?->tipo_gestacao) !== 'gemelar')>Única</option>
                    <option value="gemelar" @selected(old('tipo_gestacao', $c?->tipo_gestacao) === 'gemelar')>Gemelar</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="polidramnio_oligoidramnio">Líquido amniótico</label>
                <select class="form-select" name="polidramnio_oligoidramnio" id="polidramnio_oligoidramnio">
                    <option value="">Sem alteração</option>
                    <option value="polidramnio" @selected($liquidoAtual === 'polidramnio')>Polidrâmnio</option>
                    <option value="oligodramnio" @selected($liquidoAtual === 'oligodramnio')>Oligodrâmnio</option>
                </select>
            </div>
        </div>
        <div class="bool-grid" style="margin-top: 8px;">
            @include('consultas.partials.bool-pill', ['name' => 'prematuridade', 'label' => 'Prematuridade', 'consulta' => $c])
            @include('consultas.partials.bool-pill', ['name' => 'historico_familiar_chd', 'label' => 'Cardiopatia em primeiro grau', 'consulta' => $c])
            @include('consultas.partials.bool-pill', ['name' => 'diabetes_gestacional', 'label' => 'Diabetes gestacional', 'consulta' => $c])
            @include('consultas.partials.bool-pill', ['name' => 'diabetes_pre_gestacional', 'label' => 'Diabetes pré-gestacional', 'consulta' => $c])
            @include('consultas.partials.bool-pill', ['name' => 'lupus_eritomatoso_sistemico', 'label' => 'Lúpus', 'consulta' => $c])
            @include('consultas.partials.bool-pill', ['name' => 'sindrome_sjogren', 'label' => 'Síndrome de Sjögren', 'consulta' => $c])
            @include('consultas.partials.bool-pill', ['name' => 'doencas_tireoidianas', 'label' => 'Doença da tireoide', 'consulta' => $c])
            @include('consultas.partials.bool-pill', ['name' => 'hipertensao_cronica', 'label' => 'Hipertensão crônica', 'consulta' => $c])
            @include('consultas.partials.bool-pill', ['name' => 'hipertensao_pre_eclampsia', 'label' => 'Pré-eclâmpsia', 'consulta' => $c])
            @include('consultas.partials.bool-pill', ['name' => 'eclampsia', 'label' => 'Eclâmpsia', 'consulta' => $c])
            @include('consultas.partials.bool-pill', ['name' => 'drogas_ilicitas', 'label' => 'Drogas ilícitas', 'consulta' => $c])
            @include('consultas.partials.bool-pill', ['name' => 'licitas_teratogenicas', 'label' => 'Lícitas teratogênicas', 'consulta' => $c])
            @include('consultas.partials.bool-pill', ['name' => 'infeccao_materna_bacteriana', 'label' => 'Infecção bacteriana', 'consulta' => $c])
        </div>
    </section>
</div>
