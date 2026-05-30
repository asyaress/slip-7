import { initRupiahInputs, parseRupiah, formatRupiahDisplay } from './rupiah';

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('slip-form-root');
    const employeeSelect = document.getElementById('employee_id');
    const displayJabatan = document.getElementById('display-jabatan');
    const displayEmail = document.getElementById('display-email');
    const bulanSelect = document.getElementById('bulan');
    const tahunInput = document.getElementById('tahun');
    const existingNotice = document.getElementById('existing-slip-notice');
    const saveButton = document.getElementById('btn-save-slip');
    const existingUrl = root?.dataset.existingUrl;
    const preserveForm = root?.dataset.preserveForm === '1';

    const rupiahFields = [
        'gaji_pokok',
        'tunj_transport', 'tunj_kehadiran', 'tunj_kinerja', 'tunj_jabatan',
        'tunj_perawatan', 'tunj_operator', 'tunj_konsumsi',
        'pot_angsuran', 'pot_kasbon', 'pot_lain_lain',
        'bpjs_kesehatan', 'makan_siang_malam', 'pensiun',
    ];

    const numberFields = [
        'jumlah_kehadiran', 'hadir', 'sakit_izin', 'tidak_hadir',
    ];

    function updateEmployeeInfo() {
        const opt = employeeSelect.selectedOptions[0];
        if (opt && opt.value) {
            displayJabatan.value = opt.dataset.jabatan || '';
            displayEmail.value = opt.dataset.email || '';
        } else {
            displayJabatan.value = '';
            displayEmail.value = '';
        }
    }

    function setFieldValue(id, value) {
        const el = document.getElementById(id);
        if (!el) {
            return;
        }

        if (el.classList.contains('rupiah-input')) {
            const num = parseFloat(value) || 0;
            el.value = num > 0 ? Math.round(num).toLocaleString('id-ID') : '';
        } else {
            el.value = value ?? '';
        }
    }

    function fillForm(data) {
        rupiahFields.forEach(field => setFieldValue(field, data[field] ?? 0));
        numberFields.forEach(field => setFieldValue(field, data[field] ?? 0));
        calculate();
    }

    function setEditMode(isEdit) {
        if (existingNotice) {
            existingNotice.classList.toggle('hidden', !isEdit);
        }
        if (saveButton) {
            saveButton.textContent = isEdit ? 'Perbarui Slip Gaji' : 'Simpan & Generate Slip';
        }
    }

    async function checkExistingSlip() {
        const employeeId = employeeSelect?.value;
        const bulan = bulanSelect?.value;
        const tahun = tahunInput?.value;

        if (!existingUrl || !employeeId || !bulan || !tahun) {
            setEditMode(false);
            return;
        }

        try {
            const params = new URLSearchParams({ employee_id: employeeId, bulan, tahun });
            const response = await fetch(`${existingUrl}?${params.toString()}`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                return;
            }

            const result = await response.json();

            if (result.exists && result.data) {
                fillForm(result.data);
                setEditMode(true);
            } else {
                setEditMode(false);
            }
        } catch {
            // ignore fetch errors
        }
    }

    employeeSelect.addEventListener('change', () => {
        updateEmployeeInfo();
        checkExistingSlip();
    });
    bulanSelect?.addEventListener('change', checkExistingSlip);
    tahunInput?.addEventListener('change', checkExistingSlip);
    tahunInput?.addEventListener('input', checkExistingSlip);

    updateEmployeeInfo();

    if (preserveForm) {
        setEditMode(
            !!document.querySelector('[name="_editing_slip_id"]')
            || saveButton?.textContent.includes('Perbarui')
        );
    } else {
        const initialFormRaw = root?.dataset.initialForm;
        if (initialFormRaw) {
            try {
                fillForm(JSON.parse(initialFormRaw));
                setEditMode(true);
            } catch {
                // ignore invalid JSON
            }
        } else if (employeeSelect?.value && bulanSelect?.value && tahunInput?.value) {
            checkExistingSlip();
        } else if (saveButton?.textContent.includes('Perbarui')) {
            setEditMode(true);
        }
    }

    initRupiahInputs();

    const tunjanganIds = [
        'tunj_transport', 'tunj_kehadiran', 'tunj_kinerja', 'tunj_jabatan',
        'tunj_perawatan', 'tunj_operator', 'tunj_konsumsi',
    ];
    const potonganIds = ['pot_angsuran', 'pot_kasbon', 'pot_lain_lain'];
    const fasilitasIds = ['bpjs_kesehatan', 'makan_siang_malam', 'pensiun'];

    function sumFields(ids) {
        return ids.reduce((sum, id) => sum + parseRupiah(document.getElementById(id)?.value), 0);
    }

    function calculate() {
        const gajiPokok = parseRupiah(document.getElementById('gaji_pokok')?.value);
        const totalTunj = sumFields(tunjanganIds);
        const totalPotongan = sumFields(potonganIds);
        const thp = gajiPokok + totalTunj - totalPotongan;
        const totalFasilitas = sumFields(fasilitasIds);
        const totalPendapatan = thp + totalFasilitas;

        document.getElementById('summary-tunj').textContent = formatRupiahDisplay(totalTunj);
        document.getElementById('summary-potongan').textContent = formatRupiahDisplay(totalPotongan);
        document.getElementById('summary-thp').textContent = formatRupiahDisplay(thp);
        document.getElementById('summary-fasilitas').textContent = formatRupiahDisplay(totalFasilitas);
        document.getElementById('summary-total').textContent = formatRupiahDisplay(totalPendapatan);
    }

    window.slipFormCalculate = calculate;

    document.querySelectorAll('.calc-trigger').forEach(el => {
        el.addEventListener('input', calculate);
        el.addEventListener('rupiah-change', calculate);
    });

    calculate();
});
