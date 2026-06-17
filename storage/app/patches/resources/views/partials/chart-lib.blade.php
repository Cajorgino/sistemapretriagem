{{-- Chart.js servido localmente (storage/app/assets) — evita bloqueio de CDN --}}
<script src="{{ route('gestrisk.chartjs') }}"></script>
<script>
    window.GestRiskCharts = window.GestRiskCharts || {
        palette: {
            primary: '#c0392b',
            accent: '#e74c3c',
            ok: '#27ae60',
            warn: '#e67e22',
            muted: '#8b7355',
            grid: 'rgba(192, 57, 43, 0.12)',
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
            if (!el || typeof Chart === 'undefined' || !items.length) return null;

            const labels = items.map(i => {
                const n = i.nome || i.campo || '—';
                return n.length > 42 ? n.slice(0, 40) + '…' : n;
            });
            const values = items.map(i => Math.abs(Number(i.percentual_contribuicao ?? i.impacto_absoluto ?? 0)));
            const colors = items.map(i =>
                (i.direcao === 'aumenta_risco') ? this.palette.primary : this.palette.ok
            );

            return new Chart(el, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Contribuição (%)',
                        data: values,
                        backgroundColor: colors,
                        borderRadius: 4,
                    }],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label(ctx) {
                                    const item = items[ctx.dataIndex];
                                    const dir = item.direcao === 'aumenta_risco' ? '↑ risco' : '↓ risco';
                                    return Number(ctx.raw).toFixed(1) + '% (' + dir + ')';
                                },
                            },
                        },
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: { color: this.palette.grid },
                            title: { display: true, text: 'Contribuição relativa (%)' },
                        },
                        y: { grid: { display: false } },
                    },
                },
            });
        },
    };
</script>
