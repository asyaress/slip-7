@push('scripts')
<script>
document.getElementById('tgl_masuk')?.addEventListener('change', function () {
    const display = document.getElementById('display-masa-kerja');
    if (!display) return;

    const start = new Date(this.value);
    const now = new Date();
    if (isNaN(start)) {
        display.value = '-';
        return;
    }

    let years = now.getFullYear() - start.getFullYear();
    let months = now.getMonth() - start.getMonth();
    let days = now.getDate() - start.getDate();

    if (days < 0) { months--; days += new Date(now.getFullYear(), now.getMonth(), 0).getDate(); }
    if (months < 0) { years--; months += 12; }

    display.value = `${years} Tahun ${months} Bulan ${days} Hari`;
});
</script>
@endpush
