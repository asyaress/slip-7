<div class="print-container">
    <div class="slip-page">
        {{-- Kop Surat --}}
        <img src="{{ ($images ?? [])['kop'] ?? asset('images/kop.png') }}" alt="Kop Surat {{ config('company.name') }}" class="slip-kop">

        <div class="doc-title">SURAT KETERANGAN GAJI</div>
        <div class="doc-number">{{ $slip['nomor_surat'] }}</div>

        <p class="opening-text">
            Yang bertanda tangan di bawah ini, HRD {{ config('company.name') }}, dengan ini menerangkan bahwa :
        </p>

        <div class="employee-box">
            <table class="info">
                <tr>
                    <td class="label">Nama</td>
                    <td class="colon">:</td>
                    <td><strong>{{ $slip['employee']['name'] }}</strong></td>
                </tr>
                <tr>
                    <td class="label">Jabatan</td>
                    <td class="colon">:</td>
                    <td>{{ $slip['employee']['jabatan'] }}</td>
                </tr>
                <tr>
                    <td class="label">Tgl Masuk</td>
                    <td class="colon">:</td>
                    <td>{{ $slip['tgl_masuk'] }}</td>
                </tr>
                <tr>
                    <td class="label">Masa Kerja</td>
                    <td class="colon">:</td>
                    <td>{{ $slip['masa_kerja'] }}</td>
                </tr>
            </table>
        </div>

        <p class="opening-text" style="margin-bottom: 0;">
            Adapun yang bersangkutan bekerja di perusahaan kami dengan rincian gaji bulan
            <strong>{{ $slip['nama_bulan'] }} {{ $slip['tahun'] }}</strong> sebagai berikut :
        </p>

        <div class="attendance-box">
            <strong>Rekap Kehadiran</strong><br>
            Jumlah Hari Kerja: <strong>{{ $slip['jumlah_kehadiran'] }}</strong> hari &nbsp;|&nbsp;
            Hadir: <strong>{{ $slip['hadir'] }}</strong> hari &nbsp;|&nbsp;
            Sakit/Izin: <strong>{{ $slip['sakit_izin'] }}</strong> hari &nbsp;|&nbsp;
            Tidak Hadir: <strong>{{ $slip['tidak_hadir'] }}</strong> hari
        </div>

        <div class="section-title">Rincian Gaji Sebagai Berikut :</div>

        <table class="gaji">
            <tr>
                <td style="width:28px;"></td>
                <td>Gaji Pokok</td>
                <td style="width:12px;">:</td>
                <td class="amount highlight">{{ \App\Services\SlipGajiCalculator::formatRupiah($slip['gaji_pokok']) }}</td>
                <td class="unit-label">Per - Bulan</td>
            </tr>
            <tr class="section-header">
                <td colspan="5">Tunjangan</td>
            </tr>
            @php
                $tunjLabels = [
                    'transport' => 'Tunjangan Transport',
                    'kehadiran' => 'Tunjangan Kehadiran',
                    'kinerja' => 'Tunjangan Kinerja',
                    'jabatan' => 'Tunjangan Jabatan',
                    'perawatan' => 'Tunjangan Perawatan',
                    'operator' => 'Tunjangan Operator',
                    'konsumsi' => 'Tunjangan Konsumsi',
                ];
                $no = 1;
            @endphp
            @foreach($tunjLabels as $key => $label)
            <tr>
                <td>{{ $no++ }}.</td>
                <td>{{ $label }}</td>
                <td>:</td>
                <td class="amount">{{ \App\Services\SlipGajiCalculator::formatRupiah($slip['tunjangan'][$key]) }}</td>
                <td class="unit-label">Per - Bulan</td>
            </tr>
            @endforeach
            <tr class="subtotal">
                <td colspan="3"></td>
                <td class="amount">{{ \App\Services\SlipGajiCalculator::formatRupiah($slip['total_tunjangan']) }}</td>
                <td></td>
            </tr>
        </table>

        <div class="section-title">Potongan :</div>
        <table class="gaji">
            @php $noPot = 1; @endphp
            @foreach([
                'angsuran' => 'Angsuran',
                'kasbon' => 'Kasbon',
                'lain_lain' => 'Lain-Lain (Kelalaian Kerja, Keterlambatan, dll.)',
            ] as $key => $label)
            <tr>
                <td style="width:28px;">{{ $noPot++ }}.</td>
                <td>{{ $label }}</td>
                <td style="width:12px;">:</td>
                <td class="amount deduction">− {{ \App\Services\SlipGajiCalculator::formatRupiah($slip['potongan'][$key] ?? 0) }}</td>
                <td></td>
            </tr>
            @endforeach
            <tr class="subtotal">
                <td colspan="3"></td>
                <td class="amount deduction">− {{ \App\Services\SlipGajiCalculator::formatRupiah($slip['total_potongan']) }}</td>
                <td></td>
            </tr>
        </table>

        <div class="thp-box">
            <span>Maka Take Home Pay Selama Satu Bulan Berjumlah</span>
            <span class="thp-amount">{{ \App\Services\SlipGajiCalculator::formatRupiah($slip['take_home_pay']) }}</span>
        </div>

        <div class="section-title">Adapun Fasilitas yang di peroleh :</div>
        <table class="gaji">
            <tr>
                <td style="width:28px;"></td>
                <td>BPJS Kesehatan</td>
                <td style="width:12px;">:</td>
                <td class="amount">{{ \App\Services\SlipGajiCalculator::formatRupiah($slip['bpjs_kesehatan']) }}</td>
                <td class="unit-label">Per - Bulan</td>
            </tr>
            <tr>
                <td></td>
                <td>Makan Siang/Malam</td>
                <td>:</td>
                <td class="amount">{{ \App\Services\SlipGajiCalculator::formatRupiah($slip['makan_siang_malam']) }}</td>
                <td class="unit-label">Per - Bulan</td>
            </tr>
            <tr>
                <td></td>
                <td>Pensiun</td>
                <td>:</td>
                <td class="amount">{{ \App\Services\SlipGajiCalculator::formatRupiah($slip['pensiun']) }}</td>
                <td class="unit-label">Per - Bulan</td>
            </tr>
            <tr class="subtotal">
                <td colspan="3"></td>
                <td class="amount">{{ \App\Services\SlipGajiCalculator::formatRupiah($slip['total_fasilitas']) }}</td>
                <td></td>
            </tr>
        </table>

        <div class="total-pendapatan">
            <span>Total Pendapatan Keseluruhan</span>
            <span>{{ \App\Services\SlipGajiCalculator::formatRupiah($slip['total_pendapatan']) }}</span>
        </div>

        <p class="closing-text">Demikian surat keterangan ini dibuat untuk dipergunakan sebagaimana mestinya.</p>

        <div class="signature-section">
            <p class="signature-place-date">{{ config('company.location') }}, {{ $slip['tanggal_cetak'] }}</p>

            <div class="signature-row">
                <div class="signature-block">
                    <p class="signature-role">{{ config('employees.director.title') }},</p>
                    <div class="signature-area">
                        @php $qrSrc = ($images ?? [])['qr'] ?? ($slip['qr_signature_url'] ?? null); @endphp
                        @if(!empty($qrSrc))
                            <img src="{{ $qrSrc }}" alt="QR Dokumen" class="qr-image">
                        @endif
                    </div>
                    <p class="signature-name">{{ config('employees.director.name') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
