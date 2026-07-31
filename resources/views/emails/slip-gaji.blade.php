<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; color: #333; line-height: 1.6; margin: 0; }
        .header { background: #42091a; color: white; padding: 20px; text-align: center; }
        .content { padding: 24px; }
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

        <p>
            Berikut kami sampaikan Surat Keterangan Gaji Anda untuk periode
            <strong>{{ $slip['nama_bulan'] }} {{ $slip['tahun'] }}</strong>.
            Rincian lengkap terlampir dalam file PDF pada email ini.
        </p>

        <p>
            File PDF dilindungi password. Gunakan tanggal lahir Anda dengan format
            <strong>tanggal-bulan-tahun (DDMMYYYY)</strong> tanpa spasi atau tanda pemisah.
            Contoh: lahir 16 Februari 1994, maka password PDF adalah
            <strong>16021994</strong>.
        </p>

        <p>Demikian pemberitahuan ini kami sampaikan. Apabila ada pertanyaan, silakan hubungi HRD.</p>

        <div class="footer">
            <p>Email ini dikirim otomatis dari sistem Slip Gaji {{ config('company.short_name') }}.<br>
            HRD - {{ config('company.hrd_email') }}</p>
        </div>
    </div>
</body>
</html>
