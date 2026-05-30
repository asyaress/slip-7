<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; color: #333; line-height: 1.6; }
        .header { background: #42091a; color: white; padding: 20px; text-align: center; }
        .content { padding: 24px; }
        .highlight { background: #FFFF00; padding: 2px 6px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 12px 0; }
        td { padding: 4px 8px; }
        .amount { text-align: right; }
        .footer { margin-top: 24px; padding-top: 16px; border-top: 1px solid #eee; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h2 style="margin:0;">SURAT KETERANGAN GAJI</h2>
        <p style="margin:8px 0 0; opacity:0.9;">{{ config('company.name') }}</p>
    </div>

    <div class="content">
        <p>Yth. <strong>{{ $slip['employee']['name'] }}</strong>,</p>
        <p>Berikut rincian gaji Anda untuk periode <strong>{{ $slip['nama_bulan'] }} {{ $slip['tahun'] }}</strong>:</p>

        <table>
            <tr><td>Gaji Pokok</td><td class="amount">{{ \App\Services\SlipGajiCalculator::formatRupiah($slip['gaji_pokok']) }}</td></tr>
            <tr><td>Total Tunjangan</td><td class="amount">{{ \App\Services\SlipGajiCalculator::formatRupiah($slip['total_tunjangan']) }}</td></tr>
            <tr><td>Total Potongan</td><td class="amount" style="color:#c00;">− {{ \App\Services\SlipGajiCalculator::formatRupiah($slip['total_potongan']) }}</td></tr>
            <tr><td><strong>Take Home Pay</strong></td><td class="amount"><span class="highlight">{{ \App\Services\SlipGajiCalculator::formatRupiah($slip['take_home_pay']) }}</span></td></tr>
            <tr><td>Total Fasilitas</td><td class="amount">{{ \App\Services\SlipGajiCalculator::formatRupiah($slip['total_fasilitas']) }}</td></tr>
            <tr><td><strong>Total Pendapatan</strong></td><td class="amount"><strong>{{ \App\Services\SlipGajiCalculator::formatRupiah($slip['total_pendapatan']) }}</strong></td></tr>
        </table>

        <p><strong>Rekap Kehadiran:</strong> Hadir {{ $slip['hadir'] }} hari | Sakit/Izin {{ $slip['sakit_izin'] }} hari | Tidak Hadir {{ $slip['tidak_hadir'] }} hari</p>

        <p>Demikian surat keterangan gaji ini kami sampaikan. Apabila ada pertanyaan, silakan hubungi HRD.</p>

        <div class="footer">
            <p>Email ini dikirim otomatis dari sistem Slip Gaji {{ config('company.short_name') }}.<br>
            HRD — {{ config('company.hrd_email') }}</p>
        </div>
    </div>
</body>
</html>
