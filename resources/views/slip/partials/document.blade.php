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
                @if(!empty($slip['employee']['nip']))
                <tr>
                    <td class="label">NIP</td>
                    <td class="colon">:</td>
                    <td style="font-family: 'Courier New', Courier, monospace; font-size: 10pt;">{{ $slip['employee']['nip'] }}</td>
                </tr>
                @endif
                <tr>
                    <td class="label">Jabatan</td>
                    <td class="colon">:</td>
                    <td>{{ $slip['employee']['jabatan'] }}</td>
                </tr>
                <tr>
                    <td class="label">Mulai Bekerja</td>
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
            @if(($slip['bonus'] ?? 0) > 0)
            <tr>
                <td></td>
                <td>{{ $slip['bonus_label'] ?? \App\Services\SlipGajiCalculator::bonusLabel($slip['bonus_description'] ?? null) }}</td>
                <td>:</td>
                <td class="amount">{{ \App\Services\SlipGajiCalculator::formatRupiah($slip['bonus']) }}</td>
                <td class="unit-label">Masuk THP</td>
            </tr>
            @endif
            @php
                $activeTunjangan = \App\Services\SlipGajiCalculator::resolveActiveTunjanganBulanan($slip);
                $totalTunjBulanan = array_sum(array_column($activeTunjangan, 'amount'));
                $no = 1;
            @endphp
            @if(!empty($activeTunjangan))
            <tr class="section-header">
                <td colspan="5">Tunjangan</td>
            </tr>
            @foreach($activeTunjangan as $tunj)
            <tr>
                <td>{{ $no++ }}.</td>
                <td>{{ $tunj['label'] }}</td>
                <td>:</td>
                <td class="amount">{{ \App\Services\SlipGajiCalculator::formatRupiah($tunj['amount']) }}</td>
                <td class="unit-label">Per - Bulan</td>
            </tr>
            @endforeach
            <tr class="subtotal">
                <td colspan="3"></td>
                <td class="amount">{{ \App\Services\SlipGajiCalculator::formatRupiah($totalTunjBulanan) }}</td>
                <td class="unit-label">Total / Bulan</td>
            </tr>
            @endif
        </table>

        <div class="slip-section">
        <table class="gaji">
            <tr class="section-header">
                <td colspan="5">Potongan :</td>
            </tr>
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
                <td class="amount deduction">- {{ \App\Services\SlipGajiCalculator::formatRupiah($slip['potongan'][$key] ?? 0) }}</td>
                <td></td>
            </tr>
            @endforeach
            <tr class="subtotal">
                <td colspan="3"></td>
                <td class="amount deduction">- {{ \App\Services\SlipGajiCalculator::formatRupiah($slip['total_potongan']) }}</td>
                <td></td>
            </tr>
        </table>
        </div>

        <div class="slip-section">
        <div class="thp-box">
            <span><em>Take Home Pay</em> / Gaji bersih yang diterima selama satu bulan berjumlah</span>
            <span class="thp-amount">{{ \App\Services\SlipGajiCalculator::formatRupiah($slip['take_home_pay']) }}</span>
        </div>
        </div>

        @php
            $lemburWeeks = $slip['lembur']['weeks'] ?? [];
            $totalLembur = (float) ($slip['total_lembur'] ?? 0);
        @endphp
        @if($totalLembur > 0)
        <div class="slip-section">
        <table class="gaji">
            <tr class="section-header">
                <td colspan="5">Lembur :</td>
            </tr>
            @foreach($lemburWeeks as $week)
            @if(($week['nominal'] ?? 0) > 0)
            <tr>
                <td style="width:28px;">{{ $week['minggu'] }}.</td>
                <td>Minggu {{ $week['minggu'] }} ({{ $week['periode'] }})</td>
                <td style="width:12px;">:</td>
                <td class="amount">{{ \App\Services\SlipGajiCalculator::formatRupiah($week['nominal']) }}</td>
                <td class="unit-label">{{ \App\Services\LemburWeekService::statusLabel($week['status'] ?? null) }}</td>
            </tr>
            @endif
            @endforeach
            <tr class="subtotal">
                <td colspan="3"></td>
                <td class="amount">{{ \App\Services\SlipGajiCalculator::formatRupiah($totalLembur) }}</td>
                <td class="unit-label">Total Lembur</td>
            </tr>
        </table>
        </div>
        @endif

        @php
            $fasilitasList = $slip['fasilitas'] ?? [];
            $fasilitasLabels = config('slip.fasilitas', []);
        @endphp
        @if(!empty($fasilitasList))
        <div class="slip-section">
        <table class="gaji">
            <tr class="section-header">
                <td colspan="5">Adapun Fasilitas yang di peroleh :</td>
            </tr>
            @php $noFas = 1; @endphp
            @foreach($fasilitasList as $fasKey)
            @if(isset($fasilitasLabels[$fasKey]))
            <tr>
                <td style="width:28px;">{{ $noFas++ }}.</td>
                <td colspan="4">{{ $fasilitasLabels[$fasKey] }}</td>
            </tr>
            @endif
            @endforeach
        </table>
        </div>
        @endif

        @php
            $thpAmount = (float) ($slip['take_home_pay'] ?? 0);
            $lemburAmount = (float) ($slip['total_lembur'] ?? 0);
            $totalPendapatan = (float) ($slip['total_pendapatan'] ?? ($thpAmount + $lemburAmount));
        @endphp
        <div class="slip-section">
        <div class="total-pendapatan">
            <div class="total-pendapatan-info">
                <div class="total-pendapatan-title">Total Pendapatan</div>
                <div class="total-pendapatan-breakdown">
                    <em>Take Home Pay</em> {{ \App\Services\SlipGajiCalculator::formatRupiah($thpAmount) }}
                    + Lembur {{ \App\Services\SlipGajiCalculator::formatRupiah($lemburAmount) }}
                </div>
            </div>
            <div class="total-pendapatan-amount">{{ \App\Services\SlipGajiCalculator::formatRupiah($totalPendapatan) }}</div>
        </div>
        </div>

        <p class="closing-text">Demikian surat keterangan ini dibuat untuk dipergunakan sebagaimana mestinya.</p>

        <div class="slip-section signature-section">
            <p class="signature-place-date">{{ config('company.location') }}, {{ $slip['tanggal_cetak'] }}</p>

            @php
                $signatures = $slip['signatures'] ?? [];
                $allImages = $images ?? [];
                $signatureImages = $allImages['signatures'] ?? [];
                $signatureQr = function (string $key) use ($signatures, $signatureImages, $allImages, $slip): ?string {
                    return $signatureImages[$key]
                        ?? ($signatures[$key]['qr_signature_url'] ?? null)
                        ?? (($key === 'director') ? ($allImages['qr'] ?? ($slip['qr_signature_url'] ?? null)) : null);
                };
            @endphp

            <table class="signature-layout">
                <tr>
                    <td class="signature-slot">
                        @include('slip.partials.signature-cell', [
                            'signature' => $signatures['hr'] ?? config('employees.approval_signatories.hr'),
                            'qrSrc' => $signatureQr('hr'),
                        ])
                    </td>
                    <td class="signature-slot">
                        @include('slip.partials.signature-cell', [
                            'signature' => $signatures['finance'] ?? config('employees.approval_signatories.finance'),
                            'qrSrc' => $signatureQr('finance'),
                        ])
                    </td>
                    <td class="signature-slot">
                        @include('slip.partials.signature-cell', [
                            'signature' => $signatures['director'] ?? config('employees.approval_signatories.director'),
                            'qrSrc' => $signatureQr('director'),
                        ])
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>
