import { initRupiahInputs, parseRupiah, formatRupiahDisplay } from './rupiah';

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('slip-form-root');
    const employeeSelect = document.getElementById('employee_id');
    const displayJabatan = document.getElementById('display-jabatan');
    const displayEmail = document.getElementById('display-email');
    const bulanSelect = document.getElementById('bulan');
    const tahunInput = document.getElementById('tahun');
    const existingNotice = document.getElementById('existing-slip-notice');
    const slipForm = document.getElementById('slip-form');
    const autoSaveStatus = document.getElementById('autosave-status');
    const autoSaveStatusDot = document.getElementById('autosave-status-dot');
    const autoSaveStatusText = document.getElementById('autosave-status-text');
    const copyPreviousForm = document.getElementById('copy-previous-form');
    const copyPreviousButton = document.getElementById('btn-copy-previous');
    const copyPreviousBulan = document.getElementById('copy_previous_bulan');
    const copyPreviousTahun = document.getElementById('copy_previous_tahun');
    const copyPreviousHelp = document.getElementById('copy-previous-help');
    const existingUrl = root?.dataset.existingUrl;
    const lemburWeeksUrl = root?.dataset.lemburWeeksUrl;
    const monthlyTunjanganUrl = root?.dataset.monthlyTunjanganUrl;
    const autoSaveUrl = root?.dataset.autosaveUrl;
    const preserveForm = root?.dataset.preserveForm === '1';
    const autoSaveDelay = 1000;
    let autoSaveTimer = null;
    let autoSaveController = null;
    let autoSaveSequence = 0;

    const rupiahFields = [
        'gaji_pokok', 'bonus',
        'pot_angsuran', 'pot_kasbon', 'pot_lain_lain',
    ];

    const tunjanganHarianIds = Array.from(document.querySelectorAll('.tunj-row:not([data-tunj-monthly-only="1"]) .tunj-harian-input'))
        .map(el => el.id);

    const tunjanganBulananIds = Array.from(document.querySelectorAll('.tunj-bulanan-input'))
        .map(el => el.id);

    const tunjanganMonthlyOnlyIds = Array.from(document.querySelectorAll('.tunj-monthly-only-input'))
        .map(el => el.id);

    const tunjanganModeIds = Array.from(document.querySelectorAll('input[id^="tunj_mode_"]'))
        .map(el => el.id);

    const numberFields = [
        'jumlah_kehadiran', 'hadir', 'sakit_izin', 'tidak_hadir',
    ];

    const textFields = ['bonus_description'];

    const potonganIds = ['pot_angsuran', 'pot_kasbon', 'pot_lain_lain'];

    function formatNominalInput(value) {
        const num = parseFloat(value) || 0;
        return num > 0 ? Math.round(num).toLocaleString('id-ID') : '';
    }

    function getSelectedPeriod() {
        return {
            bulan: parseInt(bulanSelect?.value, 10) || 0,
            tahun: parseInt(tahunInput?.value, 10) || 0,
        };
    }

    function formatPeriodLabel(month, year) {
        if (!month || !year) {
            return '';
        }

        return new Intl.DateTimeFormat('id-ID', {
            month: 'long',
            year: 'numeric',
        }).format(new Date(year, month - 1, 1));
    }

    function getPreviousPeriod(month, year) {
        if (!month || !year) {
            return null;
        }

        const date = new Date(year, month - 1, 1);
        date.setMonth(date.getMonth() - 1);

        return {
            bulan: date.getMonth() + 1,
            tahun: date.getFullYear(),
        };
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

    function setAutoSaveStatus(state, message) {
        if (!autoSaveStatus || !autoSaveStatusText || !autoSaveStatusDot) {
            return;
        }

        const dotClasses = {
            idle: 'h-2 w-2 rounded-full bg-slate-300',
            pending: 'h-2 w-2 rounded-full bg-amber-400',
            saving: 'h-2 w-2 rounded-full bg-blue-500 animate-pulse',
            saved: 'h-2 w-2 rounded-full bg-emerald-500',
            error: 'h-2 w-2 rounded-full bg-red-500',
        };

        const textClasses = {
            idle: 'inline-flex items-center gap-2 text-sm text-slate-500',
            pending: 'inline-flex items-center gap-2 text-sm text-amber-700',
            saving: 'inline-flex items-center gap-2 text-sm text-blue-700',
            saved: 'inline-flex items-center gap-2 text-sm text-emerald-700',
            error: 'inline-flex items-center gap-2 text-sm text-red-700',
        };

        autoSaveStatus.className = textClasses[state] ?? textClasses.idle;
        autoSaveStatusDot.className = dotClasses[state] ?? dotClasses.idle;
        autoSaveStatusText.textContent = message;
    }

    function syncEditingSlipId(slipId) {
        if (!slipForm || !slipId) {
            return;
        }

        let input = slipForm.querySelector('input[name="_editing_slip_id"]');
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = '_editing_slip_id';
            slipForm.appendChild(input);
        }

        input.value = slipId;
    }

    function clearEditingSlipId() {
        slipForm?.querySelector('input[name="_editing_slip_id"]')?.remove();
    }

    function hasAutoSaveMinimumData() {
        return !!(
            employeeSelect?.value
            && bulanSelect?.value
            && tahunInput?.value
            && parseRupiah(document.getElementById('gaji_pokok')?.value) > 0
        );
    }

    function queueAutoSave(delay = autoSaveDelay) {
        if (!autoSaveUrl || !slipForm) {
            return;
        }

        window.clearTimeout(autoSaveTimer);

        if (!hasAutoSaveMinimumData()) {
            setAutoSaveStatus('idle', 'Auto-save aktif setelah karyawan dan gaji pokok diisi');
            return;
        }

        setAutoSaveStatus('pending', 'Perubahan belum tersimpan');
        autoSaveTimer = window.setTimeout(saveNow, delay);
    }

    async function saveNow() {
        if (!autoSaveUrl || !slipForm || !hasAutoSaveMinimumData()) {
            return;
        }

        const sequence = ++autoSaveSequence;

        if (autoSaveController) {
            autoSaveController.abort();
        }

        autoSaveController = new AbortController();
        calculate();
        setAutoSaveStatus('saving', 'Menyimpan otomatis...');

        try {
            const response = await fetch(autoSaveUrl, {
                method: 'POST',
                body: new FormData(slipForm),
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: autoSaveController.signal,
            });

            if (sequence !== autoSaveSequence) {
                return;
            }

            if (response.status === 422) {
                setAutoSaveStatus('error', 'Lengkapi data wajib agar bisa tersimpan');
                return;
            }

            if (!response.ok) {
                throw new Error(`Auto-save failed with status ${response.status}`);
            }

            const result = await response.json();
            syncEditingSlipId(result.slip_id);
            setEditMode(true);
            setAutoSaveStatus('saved', `${result.message ?? 'Tersimpan otomatis.'} ${result.updated_at ?? ''}`.trim());
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            setAutoSaveStatus('error', 'Auto-save gagal, cek koneksi lalu ubah lagi');
        }
    }

    function getJumlahKehadiran() {
        return Math.max(1, parseInt(document.getElementById('jumlah_kehadiran')?.value, 10) || 26);
    }

    function setTunjanganMode(row, mode) {
        if (!row) {
            return;
        }

        const normalized = mode === 'bulanan' ? 'bulanan' : 'harian';
        const key = row.dataset.tunjKey;
        const modeInput = document.getElementById(`tunj_mode_${key}`);

        row.dataset.bulananOverridden = normalized === 'bulanan' ? '1' : '0';

        if (modeInput) {
            modeInput.value = normalized;
        }
    }

    function inferTunjanganMode(row) {
        const jumlahKehadiran = getJumlahKehadiran();
        const key = row.dataset.tunjKey;
        const harian = parseRupiah(document.getElementById(`tunj_harian_${key}`)?.value);
        const bulanan = parseRupiah(document.getElementById(`tunj_bulanan_${key}`)?.value);
        const autoBulanan = harian * jumlahKehadiran;

        return (
            (bulanan > 0 && Math.abs(bulanan - autoBulanan) > 1)
            || (bulanan === 0 && harian > 0 && autoBulanan > 0)
        ) ? 'bulanan' : 'harian';
    }

    function syncBulananOverrideFlags() {
        document.querySelectorAll('.tunj-row').forEach(row => {
            const key = row.dataset.tunjKey;
            if (row.dataset.tunjMonthlyOnly === '1') {
                setTunjanganMode(row, 'bulanan');
                return;
            }

            const modeInput = document.getElementById(`tunj_mode_${key}`);
            setTunjanganMode(row, modeInput?.value || inferTunjanganMode(row));
        });
    }

    function fillForm(data) {
        rupiahFields.forEach(field => setFieldValue(field, data[field] ?? 0));
        tunjanganHarianIds.forEach(field => setFieldValue(field, data[field] ?? 0));
        tunjanganBulananIds.forEach(field => setFieldValue(field, data[field] ?? 0));
        numberFields.forEach(field => setFieldValue(field, data[field] ?? 0));
        textFields.forEach(field => setFieldValue(field, data[field] ?? ''));
        tunjanganModeIds.forEach(field => setFieldValue(field, data[field] ?? 'harian'));
        setFasilitasCheckboxes(data.fasilitas ?? []);
        syncBulananOverrideFlags();
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
                    <input type="hidden" name="lembur[${index}][date_start]" value="${week.date_start}">
                    <input type="hidden" name="lembur[${index}][date_end]" value="${week.date_end}">
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
            const jumlahKehadiran = document.getElementById('jumlah_kehadiran')?.value || 26;
            const params = new URLSearchParams({ bulan, tahun, jumlah_kehadiran: jumlahKehadiran });
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

            document.querySelectorAll('.tunj-row').forEach(row => {
                if (row.dataset.tunjMonthlyOnly === '1') {
                    setTunjanganMode(row, 'bulanan');
                    return;
                }

                const key = row.dataset.tunjKey;
                const hasMonthlyRate = parseRupiah(document.getElementById(`tunj_bulanan_${key}`)?.value) > 0;
                setTunjanganMode(row, hasMonthlyRate ? 'bulanan' : 'harian');
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
                syncEditingSlipId(result.slip_id);
                if (result.lembur_weeks) {
                    renderLemburRows(result.lembur_weeks);
                }
                setEditMode(true);
                setAutoSaveStatus('saved', 'Slip periode ini sudah tersimpan');
                return true;
            }

            clearEditingSlipId();
            setEditMode(false);
            return false;
        } catch {
            return false;
        }
    }

    async function onPeriodChange() {
        syncCopyPreviousForm();
        const found = await checkExistingSlip();
        if (!found) {
            await Promise.all([fetchMonthlyTunjangan(), fetchLemburWeeks()]);
        }
    }

    function syncCopyPreviousForm() {
        const { bulan, tahun } = getSelectedPeriod();
        const previous = getPreviousPeriod(bulan, tahun);

        if (copyPreviousBulan) {
            copyPreviousBulan.value = bulan || '';
        }

        if (copyPreviousTahun) {
            copyPreviousTahun.value = tahun || '';
        }

        if (copyPreviousButton) {
            copyPreviousButton.disabled = !bulan || !tahun;
        }

        if (copyPreviousHelp) {
            if (!previous) {
                copyPreviousHelp.textContent = 'Pilih bulan dan tahun tujuan terlebih dahulu.';
            } else {
                copyPreviousHelp.textContent = `Salin semua slip dari ${formatPeriodLabel(previous.bulan, previous.tahun)} ke ${formatPeriodLabel(bulan, tahun)}. Jika slip tujuan sudah ada, datanya akan diperbarui.`;
            }
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
    syncCopyPreviousForm();

    if (preserveForm) {
        setEditMode(
            !!document.querySelector('[name="_editing_slip_id"]')
        );
        syncBulananOverrideFlags();
        calculate();
        if (!employeeSelect?.value && bulanSelect?.value && tahunInput?.value) {
            Promise.all([fetchMonthlyTunjangan(), fetchLemburWeeks()]);
        }
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
        }
    }

    setAutoSaveStatus(
        hasAutoSaveMinimumData() ? 'saved' : 'idle',
        hasAutoSaveMinimumData()
            ? 'Auto-save aktif'
            : 'Auto-save aktif setelah karyawan dan gaji pokok diisi'
    );

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
        const bonus = parseRupiah(document.getElementById('bonus')?.value);
        const jumlahKehadiran = getJumlahKehadiran();
        const hadir = parseInt(document.getElementById('hadir')?.value, 10) || 0;

        let totalTunjBulanan = 0;
        let totalTunjHarian = 0;
        let tunjanganFlatBulanan = 0;

        tunjanganMonthlyOnlyIds.forEach(bulananId => {
            const amount = parseRupiah(document.getElementById(bulananId)?.value);
            tunjanganFlatBulanan += amount;
            totalTunjBulanan += amount;
        });

        tunjanganHarianIds.forEach(harianId => {
            const key = harianId.replace('tunj_harian_', '');
            const bulananId = `tunj_bulanan_${key}`;
            const row = document.querySelector(`.tunj-row[data-tunj-key="${key}"]`);
            const harianEl = document.getElementById(harianId);
            const bulananEl = document.getElementById(bulananId);
            const mode = document.getElementById(`tunj_mode_${key}`)?.value;
            const isOverridden = mode === 'bulanan' || row?.dataset.bulananOverridden === '1';

            if (isOverridden && bulananEl && harianEl) {
                const bulanan = parseRupiah(bulananEl.value);
                const harian = bulanan / jumlahKehadiran;
                harianEl.value = formatNominalInput(harian);
                tunjanganFlatBulanan += bulanan;
                totalTunjBulanan += bulanan;
            } else {
                const harian = parseRupiah(harianEl?.value);

                if (bulananEl) {
                    bulananEl.value = formatNominalInput(harian * jumlahKehadiran);
                }

                totalTunjHarian += harian;
                totalTunjBulanan += parseRupiah(bulananEl?.value);
            }
        });

        const tunjanganEarned = (totalTunjHarian * hadir) + tunjanganFlatBulanan;
        const totalPotongan = sumFields(potonganIds);
        const totalLembur = calculateLembur();
        const thp = gajiPokok + bonus + tunjanganEarned - totalPotongan;
        const totalPendapatan = thp + totalLembur;

        const summaryTunjHarian = document.getElementById('summary-tunj-harian');
        const summaryTunjEarned = document.getElementById('summary-tunj-earned');
        const summaryTunjBulananTotal = document.getElementById('summary-tunj-bulanan-total');
        const summaryTunjFlatRow = document.getElementById('summary-tunj-flat-row');
        const summaryTunjFlat = document.getElementById('summary-tunj-flat');

        if (summaryTunjHarian) {
            summaryTunjHarian.textContent = formatRupiahDisplay(totalTunjHarian);
        }
        if (summaryTunjEarned) {
            summaryTunjEarned.textContent = formatRupiahDisplay(totalTunjHarian * hadir);
        }
        if (summaryTunjFlatRow && summaryTunjFlat) {
            summaryTunjFlatRow.classList.toggle('hidden', tunjanganFlatBulanan <= 0);
            summaryTunjFlat.textContent = formatRupiahDisplay(tunjanganFlatBulanan);
        }
        if (summaryTunjBulananTotal) {
            summaryTunjBulananTotal.textContent = formatRupiahDisplay(totalTunjBulanan);
        }

        document.getElementById('summary-bonus').textContent = formatRupiahDisplay(bonus);
        document.getElementById('summary-potongan').textContent = formatRupiahDisplay(totalPotongan);
        document.getElementById('summary-thp').textContent = formatRupiahDisplay(thp);
        const summaryPendapatan = document.getElementById('summary-pendapatan');
        if (summaryPendapatan) {
            summaryPendapatan.textContent = formatRupiahDisplay(totalPendapatan);
        }
        updateLemburSummary(totalLembur);
    }

    function bindLemburInputs() {
        document.querySelectorAll('.lembur-input, .lembur-status').forEach(el => {
            if (el.dataset.slipFormBound === '1') {
                return;
            }

            el.dataset.slipFormBound = '1';
            el.addEventListener('input', calculate);
            el.addEventListener('rupiah-change', calculate);
            el.addEventListener('change', calculate);
            el.addEventListener('input', () => queueAutoSave());
            el.addEventListener('rupiah-change', () => queueAutoSave());
            el.addEventListener('change', () => queueAutoSave());
        });
    }

    window.slipFormCalculate = calculate;

    function bindTunjanganOverrideListeners() {
        document.querySelectorAll('.tunj-bulanan-input').forEach(el => {
            const markOverridden = () => {
                const row = el.closest('.tunj-row');
                setTunjanganMode(row, 'bulanan');
            };

            ['beforeinput', 'input', 'paste'].forEach(eventName => {
                el.addEventListener(eventName, markOverridden, true);
            });
        });

        document.querySelectorAll('.tunj-harian-input').forEach(el => {
            const markAuto = () => {
                const row = el.closest('.tunj-row');
                setTunjanganMode(row, 'harian');
            };

            ['beforeinput', 'input', 'paste'].forEach(eventName => {
                el.addEventListener(eventName, markAuto, true);
            });
        });
    }

    bindTunjanganOverrideListeners();

    slipForm?.addEventListener('submit', event => {
        const submitter = event.submitter;

        if (submitter instanceof HTMLButtonElement && submitter.id === 'btn-preview-slip') {
            return;
        }

        event.preventDefault();
        saveNow();
    });

    copyPreviousForm?.addEventListener('submit', async event => {
        const { bulan, tahun } = getSelectedPeriod();
        const previous = getPreviousPeriod(bulan, tahun);

        if (!bulan || !tahun || !previous) {
            event.preventDefault();
            return;
        }

        event.preventDefault();

        const confirmed = await window.appDialogs?.confirm({
            title: 'Salin Slip Bulan Sebelumnya?',
            text: `Semua slip dari ${formatPeriodLabel(previous.bulan, previous.tahun)} akan disalin ke ${formatPeriodLabel(bulan, tahun)}. Slip yang sudah ada pada periode tujuan akan diperbarui.`,
            confirmText: 'Ya, salin sekarang',
            cancelText: 'Batal',
        });

        if (!confirmed) {
            return;
        }

        if (copyPreviousButton) {
            copyPreviousButton.disabled = true;
            copyPreviousButton.textContent = 'Memproses Copy...';
        }

        copyPreviousForm.submit();
    });

    document.querySelectorAll('.calc-trigger').forEach(el => {
        el.addEventListener('input', calculate);
        el.addEventListener('change', calculate);
        el.addEventListener('rupiah-change', calculate);
        el.addEventListener('input', () => queueAutoSave());
        el.addEventListener('change', () => queueAutoSave());
        el.addEventListener('rupiah-change', () => queueAutoSave());
    });

    numberFields.forEach(id => {
        const el = document.getElementById(id);
        if (!el) {
            return;
        }

        el.addEventListener('input', calculate);
        el.addEventListener('change', calculate);
        el.addEventListener('input', () => queueAutoSave());
        el.addEventListener('change', () => queueAutoSave());
    });

    bindLemburInputs();
    syncBulananOverrideFlags();
    calculate();
});
