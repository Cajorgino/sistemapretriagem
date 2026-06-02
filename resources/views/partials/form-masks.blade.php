{{-- Máscaras: CPF (000.000.000-00), CRM (UF-números, ex.: SP-123456), telefone BR --}}
<script>
(function () {
    window.maskCPFInput = function (input) {
        if (!input) return;
        const d = input.value.replace(/\D/g, '').slice(0, 11);
        if (d.length === 0) {
            input.value = '';
            return;
        }
        if (d.length <= 3) {
            input.value = d;
        } else if (d.length <= 6) {
            input.value = d.slice(0, 3) + '.' + d.slice(3);
        } else if (d.length <= 9) {
            input.value = d.slice(0, 3) + '.' + d.slice(3, 6) + '.' + d.slice(6);
        } else {
            input.value = d.slice(0, 3) + '.' + d.slice(3, 6) + '.' + d.slice(6, 9) + '-' + d.slice(9, 11);
        }
    };

    /**
     * CRM: até 2 letras (UF) + traço + dígitos, ou só números (legado).
     */
    window.maskCRMInput = function (input) {
        if (!input) return;
        const raw = input.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        if (raw.length && /^[0-9]+$/.test(raw)) {
            input.value = raw.slice(0, 20);
            return;
        }
        let idx = 0;
        let uf = '';
        while (idx < raw.length && uf.length < 2) {
            if (/[A-Z]/.test(raw[idx])) {
                uf += raw[idx];
            }
            idx++;
        }
        const nums = raw.slice(idx).replace(/\D/g, '').slice(0, 14);
        input.value = uf + (nums.length ? '-' + nums : '');
    };

    window.maskTelefoneBRInput = function (input) {
        if (!input) return;
        let v = input.value.replace(/\D/g, '').slice(0, 11);
        if (v.length >= 7) {
            v = v.length === 11
                ? v.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3')
                : v.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
        } else if (v.length >= 3) {
            v = v.replace(/(\d{2})(\d+)/, '($1) $2');
        }
        input.value = v;
    };

    window.normalizeCRMValue = function (str) {
        return String(str || '').toUpperCase().replace(/[\s\-]+/g, '').trim();
    };
})();
</script>
