{{-- Chart.js servido localmente (storage/app/assets) — evita bloqueio de CDN --}}
<script src="{{ route('gestrisk.chartjs') }}"></script>
<script>
    window.GestRiskCharts = window.GestRiskCharts || {
        palette: {
            primary: '#7f0c1a',
            accent: '#c0392b',
            ok: '#1e7a45',
            warn: '#b86e00',
            high: '#b71c1c',
            muted: '#6b5a5a',
            grid: 'rgba(127, 12, 26, 0.10)',
            riskLow: '#1e7a45',
            riskMid: '#b86e00',
            riskHigh: '#b71c1c',
        },
        ready(callback, attempts) {
            attempts = attempts || 0;
            if (typeof Chart !== 'undefined' && this.doughnut) {
                callback();
                return;
            }
            if (attempts > 50) {
                console.warn('GestRisk: Chart.js não carregou.');
                return;
            }
            setTimeout(() => this.ready(callback, attempts + 1), 100);
        },
        defaults() {
            if (typeof Chart === 'undefined') return;
            Chart.defaults.font.family = "'DM Sans', system-ui, sans-serif";
            Chart.defaults.color = '#5c4a4a';
        },
        doughnut(canvasId, labels, values, colors) {
            this.defaults();
            const el = document.getElementById(canvasId);
            if (!el || typeof Chart === 'undefined') return null;
            return new Chart(el, {
                type: 'doughnut',
                data: {
                    labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors,
                        borderWidth: 2,
                        borderColor: '#fff',
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12, padding: 14 } },
                    },
                },
            });
        },
        bar(canvasId, labels, values, colors) {
            this.defaults();
            const el = document.getElementById(canvasId);
            if (!el || typeof Chart === 'undefined') return null;
            return new Chart(el, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors,
                        borderRadius: 6,
                        maxBarThickness: 48,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false } },
                        y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 } },
                    },
                },
            });
        },
        shapHorizontal(canvasId, items) {
            this.defaults();
            const el = document.getElementById(canvasId);
            if (!el || typeof Chart === 'undefined' || !items || !items.length) return null;

            if (el._gestRiskChart) {
                el._gestRiskChart.destroy();
                el._gestRiskChart = null;
            }

            // Altura dinâmica para análise completa (até 36 features).
            const wrap = el.parentElement;
            if (wrap) {
                const h = Math.max(280, items.length * 24);
                wrap.style.height = h + 'px';
                wrap.style.minHeight = h + 'px';
            }

            const labels = items.map(i => {
                const n = String(i.nome || i.campo || i.feature || 'Não informado');
                return n.length > 36 ? n.slice(0, 34) + '…' : n;
            });
            const values = items.map(i => {
                const raw = i.percentual_contribuicao ?? i.impacto_absoluto ?? i.impact ?? 0;
                return Math.abs(Number(raw));
            });
            const colors = items.map(i => {
                const dir = i.direcao || ((Number(i.impact ?? 0) >= 0) ? 'aumenta_risco' : 'reduz_risco');
                return dir === 'aumenta_risco' ? this.palette.riskHigh : this.palette.riskLow;
            });

            const chart = new Chart(el, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Contribuição (%)',
                        data: values,
                        backgroundColor: colors,
                        borderRadius: 4,
                        barThickness: items.length > 20 ? 14 : 18,
                    }],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: { padding: { left: 4, right: 12, top: 4, bottom: 4 } },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label(ctx) {
                                    const item = items[ctx.dataIndex] || {};
                                    const dir = (item.direcao === 'aumenta_risco' || Number(item.impact ?? 0) >= 0)
                                        ? '↑ risco' : '↓ risco';
                                    return Number(ctx.raw).toFixed(1) + '% (' + dir + ')';
                                },
                            },
                        },
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            suggestedMax: Math.max(10, Math.ceil(Math.max(...values, 0) * 1.1)),
                            grid: { color: this.palette.grid },
                            title: { display: true, text: 'Contribuição relativa (%)' },
                        },
                        y: {
                            grid: { display: false },
                            ticks: { autoSkip: false, font: { size: items.length > 24 ? 10 : 11 } },
                        },
                    },
                },
            });
            el._gestRiskChart = chart;
            return chart;
        },
        cohortDoughnut(canvasId, labels, values, colors) {
            this.defaults();
            const el = document.getElementById(canvasId);
            if (!el || typeof Chart === 'undefined') return null;
            const total = values.reduce(function (soma, valor) { return soma + Number(valor); }, 0) || 1;
            const pct = (100 * Number(values[0] || 0) / total).toLocaleString('pt-BR', {
                minimumFractionDigits: 1,
                maximumFractionDigits: 1,
            }) + '%';
            return new Chart(el, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors,
                        borderWidth: 2,
                        borderColor: '#fff',
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12, font: { size: 12 } } },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    const n = Number(ctx.raw);
                                    const parte = (100 * n / total).toLocaleString('pt-BR', { maximumFractionDigits: 1 });
                                    return ' ' + n.toLocaleString('pt-BR') + ' (' + parte + '%)';
                                },
                            },
                        },
                    },
                },
                plugins: [{
                    id: 'centroPrevalencia',
                    afterDraw: function (chart) {
                        const area = chart.chartArea;
                        const ctx = chart.ctx;
                        ctx.save();
                        ctx.font = "600 22px 'DM Serif Display', Georgia, serif";
                        ctx.fillStyle = '#7f0c1a';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.fillText(pct, (area.left + area.right) / 2, (area.top + area.bottom) / 2 - 6);
                        ctx.restore();
                    },
                }],
            });
        },
        cohortBar(canvasId, labels, values, unidade, particao) {
            this.defaults();
            const el = document.getElementById(canvasId);
            if (!el || typeof Chart === 'undefined') return null;
            const horizontal = labels.length > 4 || labels.some(function (rotulo) {
                return String(rotulo).length > 12;
            });
            const wrap = el.parentElement;
            if (wrap) {
                const altura = horizontal
                    ? Math.max(360, labels.length * 48 + 80)
                    : 380;
                wrap.style.height = altura + 'px';
                wrap.style.minHeight = altura + 'px';
            }
            const total = values.reduce(function (soma, valor) { return soma + Number(valor); }, 0) || 1;
            const maior = Math.max.apply(null, values.map(Number).concat([1]));
            const casos = canvasId.indexOf('casos_ccf') !== -1;
            const cor = casos ? 'rgba(183, 28, 28, 0.82)' : 'rgba(127, 12, 26, 0.78)';
            const eixoValor = {
                beginAtZero: true,
                suggestedMax: Math.ceil(maior * 1.18),
                grid: { color: this.palette.grid },
                ticks: { precision: 0, maxTicksLimit: 6 },
                title: { display: true, text: unidade === 'cardiopatias' ? 'Cardiopatias' : 'Registros' },
            };
            const eixoRotulo = {
                grid: { display: false },
                ticks: { autoSkip: false, font: { size: 14 } },
            };

            return new Chart(el, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: cor,
                        borderRadius: 4,
                        barThickness: horizontal ? 22 : 42,
                    }],
                },
                options: {
                    indexAxis: horizontal ? 'y' : 'x',
                    responsive: true,
                    maintainAspectRatio: false,
                    clip: false,
                    layout: { padding: horizontal ? { right: 56, top: 4 } : { top: 22 } },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    const n = Number(ctx.raw);
                                    const texto = n.toLocaleString('pt-BR') + ' ' + (unidade || 'registros');
                                    if (!particao) return ' ' + texto;
                                    const parte = (100 * n / total).toLocaleString('pt-BR', { maximumFractionDigits: 1 });
                                    return ' ' + texto + ' (' + parte + '%)';
                                },
                            },
                        },
                    },
                    scales: horizontal
                        ? { x: eixoValor, y: eixoRotulo }
                        : { x: eixoRotulo, y: eixoValor },
                },
                plugins: [{
                    id: 'rotuloValor',
                    afterDatasetsDraw: function (chart) {
                        const ctx = chart.ctx;
                        ctx.save();
                        ctx.font = "600 11px 'DM Sans', system-ui, sans-serif";
                        ctx.fillStyle = '#5c4a4a';
                        chart.getDatasetMeta(0).data.forEach(function (barra, indice) {
                            const texto = Number(chart.data.datasets[0].data[indice]).toLocaleString('pt-BR');
                            if (horizontal) {
                                ctx.textAlign = 'left';
                                ctx.textBaseline = 'middle';
                                ctx.fillText(texto, barra.x + 6, barra.y);
                            } else {
                                ctx.textAlign = 'center';
                                ctx.textBaseline = 'bottom';
                                ctx.fillText(texto, barra.x, barra.y - 4);
                            }
                        });
                        ctx.restore();
                    },
                }],
            });
        },
    };
</script>
