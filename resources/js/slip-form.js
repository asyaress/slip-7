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
    const lemburWeeksUrl = root?.dataset.lemburWeeksUrl;
    const monthlyTunjanganUrl = root?.dataset.monthlyTunjanganUrl;
    const preserveForm = root?.dataset.preserveForm === '1';

    const rupiahFields = [
        'gaji_pokok',
        'pot_angsuran', 'pot_kasbon', 'pot_lain_lain',
    ];

    const tunjanganBulananIds = Array.from(document.querySelectorAll('.tunj-bulanan-input'))
        .map(el => el.id);

    const numberFields = [
        'jumlah_kehadiran', 'hadir', 'sakit_izin', 'tidak_hadir',
    ];

    const potonganIds = ['pot_angsuran', 'pot_kasbon', 'pot_lain_lain'];

    function formatNominalInput(value) {
        const num = parseFloat(value) || 0;
        return num > 0 ? Math.round(num).toLocaleString('id-ID') : '';
    }

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
            el.value = formatNominalInput(value);
        } else if (el.type === 'checkbox') {
            el.checked = !!value;
        } else {
            el.value = value ?? '';
        }
    }

    function setFasilitasCheckboxes(selected) {
        const values = Array.isArray(selected) ? selected : [];
        document.querySelectorAll('input[name="fasilitas[]"]').forEach(el => {
            el.checked = values.includes(el.value);
        });
    }

    function fillForm(data) {
        rupiahFields.forEach(field => setFieldValue(field, data[field] ?? 0));
        tunjanganBulananIds.forEach(field => setFieldValue(field, data[field] ?? 0));
        numberFields.forEach(field => setFieldValue(field, data[field] ?? 0));
        setFasilitasCheckboxes(data.fasilitas ?? []);
        calculate();
    }

    function renderLemburRows(weeks) {
        const container = document.getElementById('lembur-rows');
        if (!container || !Array.isArray(weeks)) {
            return;
        }

        container.innerHTML = weeks.map((week, index) => {
            const status = week.status === 'sudah_dibayar' ? 'sudah_dibayar' : 'belum_dibayar';

            return `
            <div class="lembur-row grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                <div class="sm:col-span-1 text-sm font-medium text-slate-500">${week.minggu}</div>
                <div class="sm:col-span-4 text-sm text-slate-700">
                    ${week.periode}
                    <input type="hidden" name="lembur[${index}][minggu]" value="${week.minggu}">
                    <input type="hidden" name="lembur[${index}][periode]" value="${week.periode}">
                </div>
                <div class="sm:col-span-4 rupiah-field">
                    <span class="rupiah-prefix">Rp</span>
                    <input type="text" inputmode="numeric"
                        name="lembur[${index}][nominal]"
                        value="${formatNominalInput(week.nominal ?? 0)}"
                        placeholder="0"
                        class="rupiah-input lembur-input">
                </div>
                <div class="sm:col-span-3">
                    <select name="lembur[${index}][status]" class="select-field text-sm lembur-status">
                        <option value="belum_dibayar"${status === 'belum_dibayar' ? ' selected' : ''}>Belum Dibayar</option>
                        <option value="sudah_dibayar"${status === 'sudah_dibayar' ? ' selected' : ''}>Sudah Dibayar</option>
                    </select>
                </div>
            </div>
        `;
        }).join('');

        initRupiahInputs(container);
        bindLemburInputs();
        calculate();
    }

    async function fetchMonthlyTunjangan() {
        const bulan = bulanSelect?.value;
        const tahun = tahunInput?.value;

        if (!monthlyTunjanganUrl || !bulan || !tahun) {
            return;
        }

        try {
            const params = new URLSearchParams({ bulan, tahun });
            const response = await fetch(`${monthlyTunjanganUrl}?${params.toString()}`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                return;
            }

            const result = await response.json();
            const fields = result.fields ?? {};

            Object.entries(fields).forEach(([field, value]) => {
                setFieldValue(field, value);
            });

            calculate();
        } catch {
            // ignore fetch errors
        }
    }

    async function fetchLemburWeeks() {
        const bulan = bulanSelect?.value;
        const tahun = tahunInput?.value;

        if (!lemburWeeksUrl || !bulan || !tahun) {
            return;
        }

        try {
            const params = new URLSearchParams({ bulan, tahun });
            const response = await fetch(`${lemburWeeksUrl}?${params.toString()}`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                return;
            }

            const result = await response.json();
            renderLemburRows(result.weeks ?? []);
        } catch {
            // ignore fetch errors
        }
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
            return false;
        }

        try {
            const params = new URLSearchParams({ employee_id: employeeId, bulan, tahun });
            const response = await fetch(`${existingUrl}?${params.toString()}`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                return false;
            }

            const result = await response.json();

            if (result.exists && result.data) {
                fillForm(result.data);
                if (result.lembur_weeks) {
                    renderLemburRows(result.lembur_weeks);
                }
                setEditMode(true);
                return true;
            }

            setEditMode(false);
            return false;
        } catch {
            return false;
        }
    }

    async function onPeriodChange() {
        const found = await checkExistingSlip();
        if (!found) {
            await Promise.all([fetchMonthlyTunjangan(), fetchLemburWeeks()]);
        }
    }

    employeeSelect.addEventListener('change', () => {
        updateEmployeeInfo();
        onPeriodChange();
    });
    bulanSelect?.addEventListener('change', onPeriodChange);
    tahunInput?.addEventListener('change', onPeriodChange);
    tahunInput?.addEventListener('input', onPeriodChange);

    updateEmployeeInfo();

    if (preserveForm) {
        setEditMode(
            !!document.querySelector('[name="_editing_slip_id"]')
            || saveButton?.textContent.includes('Perbarui')
        );
        calculate();
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
            onPeriodChange();
        } else if (bulanSelect?.value && tahunInput?.value) {
            fetchMonthlyTunjangan();
        } else if (saveButton?.textContent.includes('Perbarui')) {
            setEditMode(true);
        }
    }

    initRupiahInputs();

    function sumFields(ids) {
        return ids.reduce((sum, id) => sum + parseRupiah(document.getElementById(id)?.value), 0);
    }

    function calculateLembur() {
        return Array.from(document.querySelectorAll('.lembur-input'))
            .reduce((sum, el) => sum + parseRupiah(el.value), 0);
    }

    function updateLemburSummary(totalLembur) {
        const formatted = formatRupiahDisplay(totalLembur);
        const summaryLembur = document.getElementById('summary-lembur');
        const summaryLemburSidebar = document.getElementById('summary-lembur-sidebar');

        if (summaryLembur) {
            summaryLembur.textContent = formatted;
        }
        if (summaryLemburSidebar) {
            summaryLemburSidebar.textContent = formatted;
        }
    }

    function calculate() {
        const gajiPokok = parseRupiah(document.getElementById('gaji_pokok')?.value);
        const jumlahKehadiran = Math.max(1, parseInt(document.getElementById('jumlah_kehadiran')?.value, 10) || 26);
        const hadir = parseInt(document.getElementById('hadir')?.value, 10) || 0;

        let totalTunjBulanan = 0;
        let totalTunjHarian = 0;

        tunjanganBulananIds.forEach(id => {
            const bulanan = parseRupiah(document.getElementById(id)?.value);
            const harian = bulanan / jumlahKehadiran;
            totalTunjBulanan += bulanan;
            totalTunjHarian += harian;

            const display = document.getElementById(id.replace('tunj_bulanan_', 'tunj_harian_'));
            if (display) {
                display.textContent = formatRupiahDisplay(harian);
            }
        });

        const tunjanganEarned = totalTunjHarian * hadir;
        const totalPotongan = sumFields(potonganIds);
        const totalLembur = calculateLembur();
        const thp = gajiPokok + tunjanganEarned + totalLembur - totalPotongan;

        const summaryTunjHarian = document.getElementById('summary-tunj-harian');
        const summaryTunjEarned = document.getElementById('summary-tunj-earned');

        if (summaryTunjHarian) {
            summaryTunjHarian.textContent = formatRupiahDisplay(totalTunjHarian);
        }
        if (summaryTunjEarned) {
            summaryTunjEarned.textContent = formatRupiahDisplay(tunjanganEarned);
        }

        document.getElementById('summary-potongan').textContent = formatRupiahDisplay(totalPotongan);
        document.getElementById('summary-thp').textContent = formatRupiahDisplay(thp);
        updateLemburSummary(totalLembur);
    }

    function bindLemburInputs() {
        document.querySelectorAll('.lembur-input').forEach(el => {
            el.addEventListener('input', calculate);
            el.addEventListener('rupiah-change', calculate);
        });
    }

    window.slipFormCalculate = calculate;

    document.querySelectorAll('.calc-trigger').forEach(el => {
        el.addEventListener('input', calculate);
        el.addEventListener('change', calculate);
        el.addEventListener('rupiah-change', calculate);
    });

    bindLemburInputs();
    calculate();
});
