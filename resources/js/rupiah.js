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

export function initRupiahInputs(root = document) {
    root.querySelectorAll('.rupiah-input').forEach(input => {
        if (input.dataset.rupiahBound === '1') {
            return;
        }
        input.dataset.rupiahBound = '1';

        input.addEventListener('beforeinput', (e) => {
            const { selectionStart, selectionEnd, value } = e.target;
            const isDelete = e.inputType === 'deleteContentBackward'
                || e.inputType === 'deleteContentForward'
                || e.inputType === 'deleteByCut';

            if (isDelete && selectionStart === 0 && selectionEnd === value.length && value.length > 0) {
                e.target.dataset.rupiahClearing = '1';
            }
        });

        input.addEventListener('input', (e) => {
            if (e.target.dataset.rupiahClearing === '1') {
                delete e.target.dataset.rupiahClearing;
                e.target.value = '';
                e.target.dispatchEvent(new Event('rupiah-change', { bubbles: true }));
                return;
            }

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
