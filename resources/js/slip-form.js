import { initRupiahInputs, parseRupiah, formatRupiahDisplay } from './rupiah';

document.addEventListener('DOMContentLoaded', () => {
    const employeeSelect = document.getElementById('employee_id');
    const displayJabatan = document.getElementById('display-jabatan');
    const displayEmail = document.getElementById('display-email');

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

    employeeSelect.addEventListener('change', updateEmployeeInfo);
    updateEmployeeInfo();

    initRupiahInputs();

    const tunjanganIds = [
        'tunj_transport', 'tunj_kehadiran', 'tunj_kinerja', 'tunj_jabatan',
        'tunj_perawatan', 'tunj_operator', 'tunj_konsumsi',
    ];
    const potonganIds = ['pot_angsuran', 'pot_kasbon'];
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

    document.querySelectorAll('.calc-trigger').forEach(el => {
        el.addEventListener('input', calculate);
        el.addEventListener('rupiah-change', calculate);
    });

    calculate();
});
