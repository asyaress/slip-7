export function parseRupiah(value) {
    if (value === null || value === undefined || value === '') return 0;
    const cleaned = String(value).replace(/[^\d]/g, '');
    return cleaned ? parseInt(cleaned, 10) : 0;
}

export function formatRupiahNumber(num) {
    return Math.round(num).toLocaleString('id-ID');
}

export function formatRupiahDisplay(num) {
    return 'Rp ' + formatRupiahNumber(num);
}

export function initRupiahInputs() {
    document.querySelectorAll('.rupiah-input').forEach(input => {
        input.addEventListener('input', (e) => {
            const raw = parseRupiah(e.target.value);
            const pos = e.target.selectionStart;
            const oldLen = e.target.value.length;

            e.target.value = raw > 0 ? formatRupiahNumber(raw) : '';

            const newLen = e.target.value.length;
            const newPos = Math.max(0, pos + (newLen - oldLen));
            e.target.setSelectionRange(newPos, newPos);

            e.target.dispatchEvent(new Event('rupiah-change', { bubbles: true }));
        });

        input.addEventListener('focus', (e) => {
            if (e.target.value === '0') e.target.value = '';
        });

        input.addEventListener('blur', (e) => {
            const raw = parseRupiah(e.target.value);
            e.target.value = raw > 0 ? formatRupiahNumber(raw) : '';
        });
    });

    const form = document.getElementById('slip-form');
    if (form) {
        form.addEventListener('submit', () => {
            document.querySelectorAll('.rupiah-input').forEach(input => {
                input.value = parseRupiah(input.value);
            });
        });
    }
}
