@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard Analisis Gaji')
@section('page-subtitle', 'Ringkasan finansial & statistik slip — ' . $periode_label)

@section('header-actions')
<form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2">
    <select name="bulan" class="select-field w-auto text-sm">
        @foreach(['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $i => $nama)
            <option value="{{ $i + 1 }}" @selected($bulan == $i + 1)>{{ $nama }}</option>
        @endforeach
    </select>
    <input type="number" name="tahun" value="{{ $tahun }}" min="2020" max="2100" class="input-field w-24 text-sm">
    <button type="submit" class="btn-secondary text-sm py-2">Terapkan</button>
</form>
@endsection

@php
    $fmt = fn ($v) => \App\Services\SlipGajiCalculator::formatRupiah($v);
@endphp

@section('content')
{{-- KPI Cards --}}
<div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <div class="card p-5 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-24 h-24 bg-maroon-50 rounded-full -translate-y-1/2 translate-x-1/2"></div>
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total Take Home Pay</p>
        <p class="text-2xl font-bold text-maroon-900 mt-2">{{ $fmt($financials['total_thp']) }}</p>
        <p class="text-xs text-slate-400 mt-1">Rata-rata {{ $fmt($financials['rata_thp']) }} / karyawan</p>
    </div>
    <div class="card p-5 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-24 h-24 bg-amber-50 rounded-full -translate-y-1/2 translate-x-1/2"></div>
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total Lembur</p>
        <p class="text-2xl font-bold text-amber-700 mt-2">{{ $fmt($financials['total_lembur']) }}</p>
        <p class="text-xs text-slate-400 mt-1">Termasuk dalam THP</p>
    </div>
    <div class="card p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total Tunjangan</p>
        <p class="text-2xl font-bold text-slate-900 mt-2">{{ $fmt($financials['total_tunjangan']) }}</p>
        <p class="text-xs text-slate-400 mt-1">Per hari (agregat)</p>
    </div>
    <div class="card p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total Potongan</p>
        <p class="text-2xl font-bold text-red-600 mt-2">{{ $fmt($financials['total_potongan']) }}</p>
        <p class="text-xs text-slate-400 mt-1">Gaji pokok {{ $fmt($financials['total_gaji_pokok']) }}</p>
    </div>
</div>

{{-- Progress & Status --}}
<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="card p-5">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm text-slate-500">Slip Terinput</p>
            <span class="text-sm font-bold text-maroon-900">{{ $stats['slip_periode'] }}/{{ $stats['total_karyawan'] }}</span>
        </div>
        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
            <div class="h-full bg-maroon-800 rounded-full transition-all" style="width: {{ $stats['persen_input'] }}%"></div>
        </div>
        <p class="text-xs text-slate-400 mt-2">{{ $stats['persen_input'] }}% selesai · {{ $stats['belum_input'] }} belum input</p>
    </div>
    <div class="card p-5">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm text-slate-500">Email Terkirim</p>
            <span class="text-sm font-bold text-emerald-600">{{ $stats['terkirim'] }}/{{ $stats['slip_periode'] }}</span>
        </div>
        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
            <div class="h-full bg-emerald-500 rounded-full transition-all" style="width: {{ $stats['persen_kirim'] }}%"></div>
        </div>
        <p class="text-xs text-slate-400 mt-2">{{ $stats['persen_kirim'] }}% terkirim · {{ $stats['belum_kirim'] }} belum · {{ $stats['gagal_kirim'] }} gagal</p>
    </div>
    <div class="card p-5">
        <p class="text-sm text-slate-500">Total Kehadiran</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($attendance['hadir']) }} <span class="text-sm font-normal text-slate-400">hari hadir</span></p>
        <p class="text-xs text-slate-400 mt-1">Sakit/Izin {{ number_format($attendance['sakit_izin']) }} · Tidak hadir {{ number_format($attendance['tidak_hadir']) }}</p>
    </div>
    <div class="card-dark p-5">
        <p class="text-xs font-medium uppercase tracking-wide text-white/70">Periode Aktif</p>
        <p class="text-xl font-bold mt-1 text-white">{{ $periode_label }}</p>
        <p class="text-xs text-white/50 mt-1">{{ $bulan }}/{{ $tahun }}</p>
        <a href="{{ route('review.index', ['bulan' => $bulan, 'tahun' => $tahun]) }}" class="inline-block mt-3 text-xs text-white/80 hover:text-white underline">Review slip periode ini →</a>
    </div>
</div>

@if($stats['slip_periode'] === 0)
<div class="card p-12 text-center mb-6">
    <p class="text-slate-400 mb-2">Belum ada data slip untuk periode ini.</p>
    <a href="{{ route('slip.create') }}" class="btn-primary inline-flex">+ Input Slip Gaji</a>
</div>
@else

{{-- Charts Row 1 --}}
<div id="dashboard-charts" data-charts='@json($charts)' class="grid lg:grid-cols-3 gap-6 mb-6">
    <div class="card p-6 lg:col-span-1">
        <h3 class="font-semibold text-slate-900 mb-1">Komposisi Pendapatan</h3>
        <p class="text-xs text-slate-400 mb-4">Gaji pokok, tunjangan & fasilitas</p>
        <div class="h-56 relative">
            <canvas id="chart-composition"></canvas>
        </div>
        <div class="mt-4 pt-4 border-t border-slate-100 flex justify-between text-sm">
            <span class="text-slate-500">Total Potongan</span>
            <span class="font-semibold text-red-600">− {{ $fmt($charts['composition']['potongan']) }}</span>
        </div>
    </div>

    <div class="card p-6 lg:col-span-2">
        <h3 class="font-semibold text-slate-900 mb-1">Take Home Pay per Karyawan</h3>
        <p class="text-xs text-slate-400 mb-4">{{ $stats['slip_periode'] }} karyawan · periode {{ $periode_label }}</p>
        <div class="h-72 relative">
            <canvas id="chart-thp"></canvas>
        </div>
    </div>
</div>

{{-- Charts Row 2 --}}
<div class="grid lg:grid-cols-3 gap-6 mb-6">
    <div class="card p-6">
        <h3 class="font-semibold text-slate-900 mb-1">Status Pengiriman Email</h3>
        <p class="text-xs text-slate-400 mb-4">Distribusi blast email slip</p>
        <div class="h-52 relative">
            <canvas id="chart-email"></canvas>
        </div>
        @if($stats['slip_periode'] > 0 && $stats['terkirim'] + $stats['belum_kirim'] + $stats['gagal_kirim'] === 0)
            <p class="text-center text-xs text-slate-400 mt-2">Belum ada data email</p>
        @endif
    </div>

    <div class="card p-6">
        <h3 class="font-semibold text-slate-900 mb-1">Rincian Tunjangan</h3>
        <p class="text-xs text-slate-400 mb-4">Total per jenis tunjangan</p>
        <div class="h-52 relative">
            <canvas id="chart-tunjangan"></canvas>
        </div>
    </div>

    <div class="card p-6">
        <h3 class="font-semibold text-slate-900 mb-1">Rincian Potongan</h3>
        <p class="text-xs text-slate-400 mb-4">Angsuran, kasbon & lain-lain</p>
        <div class="h-52 relative">
            <canvas id="chart-potongan"></canvas>
        </div>
    </div>
</div>

{{-- Trend --}}
<div class="card p-6 mb-6">
    <h3 class="font-semibold text-slate-900 mb-1">Tren 6 Bulan Terakhir</h3>
    <p class="text-xs text-slate-400 mb-4">Perbandingan total THP vs total pendapatan per bulan</p>
    <div class="h-64 relative">
        <canvas id="chart-trend"></canvas>
    </div>
</div>

{{-- Detail Table --}}
<div class="card overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-slate-100">
        <h3 class="font-semibold text-slate-900">Ringkasan Finansial Periode</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-6 py-3 font-medium">Komponen</th>
                    <th class="text-right px-6 py-3 font-medium">Total</th>
                    <th class="text-right px-6 py-3 font-medium">Rata-rata / Slip</th>
                    <th class="text-right px-6 py-3 font-medium">% dari Pendapatan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @php
                    $rows = [
                        ['Gaji Pokok', $financials['total_gaji_pokok']],
                        ['Total Tunjangan / Hari', $financials['total_tunjangan']],
                        ['Total Lembur', $financials['total_lembur']],
                        ['Take Home Pay', $financials['total_thp']],
                        ['Total Potongan', $financials['total_potongan']],
                    ];
                    $count = max(1, $stats['slip_periode']);
                    $thpBase = max(1, $financials['total_thp']);
                @endphp
                @foreach($rows as [$label, $total])
                <tr class="hover:bg-slate-50/80">
                    <td class="px-6 py-3.5 font-medium text-slate-900">{{ $label }}</td>
                    <td class="px-6 py-3.5 text-right {{ str_contains($label, 'Potongan') ? 'text-red-600' : '' }}">{{ $fmt($total) }}</td>
                    <td class="px-6 py-3.5 text-right text-slate-600">{{ $fmt($total / $count) }}</td>
                    <td class="px-6 py-3.5 text-right text-slate-500">{{ $label !== 'Total Potongan' ? round(($total / $thpBase) * 100, 1).'%' : '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Bottom: Recent + Quick Actions --}}
<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 card">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="font-semibold text-slate-900">Slip Terbaru</h2>
            <a href="{{ route('review.index') }}" class="text-sm text-maroon-800 hover:text-maroon-900 font-medium">Lihat Semua →</a>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse($recent_slips as $slip)
                <div class="px-6 py-4 flex items-center justify-between hover:bg-slate-50">
                    <div>
                        <p class="font-medium text-slate-900">{{ $slip->employee->name }}</p>
                        <p class="text-sm text-slate-500">{{ $slip->periodeLabel() }} · THP {{ $fmt($slip->take_home_pay) }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        @if($slip->isEmailSent())
                            <span class="badge badge-success">Terkirim</span>
                        @else
                            <span class="badge badge-warning">Belum kirim</span>
                        @endif
                        <a href="{{ route('review.show', $slip) }}" class="text-sm text-maroon-800 hover:underline">Lihat</a>
                    </div>
                </div>
            @empty
                <div class="px-6 py-12 text-center text-slate-400 text-sm">
                    Belum ada slip gaji. <a href="{{ route('slip.create') }}" class="text-maroon-800 font-medium">Buat slip pertama →</a>
                </div>
            @endforelse
        </div>
    </div>

    <div class="space-y-4">
        <div class="card p-6">
            <h3 class="font-semibold text-slate-900 mb-4">Aksi Cepat</h3>
            <div class="space-y-2">
                <a href="{{ route('slip.create') }}" class="btn-primary w-full">+ Input Slip Gaji</a>
                <a href="{{ route('review.index', ['bulan' => $bulan, 'tahun' => $tahun]) }}" class="btn-secondary w-full">Review & Blast Email</a>
                <a href="{{ route('employees.index') }}" class="btn-secondary w-full">Kelola Karyawan</a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('dashboard-charts');
    if (!root?.dataset.charts || typeof Chart === 'undefined') return;

    const data = JSON.parse(root.dataset.charts);
    const C = { maroon: '#8c1b3d', maroonLight: '#c92854', emerald: '#059669', amber: '#d97706', blue: '#2563eb', slate: '#64748b' };
    const fmtShort = v => v >= 1e6 ? (v/1e6).toFixed(1)+'jt' : v >= 1e3 ? (v/1e3).toFixed(0)+'rb' : v;
    const fmtFull = v => 'Rp ' + Math.round(v).toLocaleString('id-ID');
    const baseOpts = {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { labels: { color: '#475569', font: { size: 11 }, boxWidth: 12 } } }
    };

    if (data.composition.values.some(v => v > 0)) {
        new Chart(document.getElementById('chart-composition'), {
            type: 'doughnut',
            data: {
                labels: data.composition.labels,
                datasets: [{ data: data.composition.values, backgroundColor: [C.maroon, C.maroonLight, C.blue], borderWidth: 2, borderColor: '#fff' }]
            },
            options: { ...baseOpts, cutout: '62%', plugins: { ...baseOpts.plugins, tooltip: { callbacks: { label: c => ' '+c.label+': '+fmtFull(c.raw) } } } }
        });
    }

    if (data.thp_by_employee.values.length) {
        new Chart(document.getElementById('chart-thp'), {
            type: 'bar',
            data: {
                labels: data.thp_by_employee.labels,
                datasets: [{ label: 'THP', data: data.thp_by_employee.values, backgroundColor: C.maroon, borderRadius: 6, maxBarThickness: 32 }]
            },
            options: {
                ...baseOpts, indexAxis: 'y',
                scales: {
                    x: { grid: { color: '#f1f5f9' }, ticks: { color: '#64748b', callback: v => fmtShort(v) } },
                    y: { grid: { display: false }, ticks: { color: '#334155', font: { size: 11 } } }
                },
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => ' '+fmtFull(c.raw) } } }
            }
        });
    }

    if (data.email_status.values.some(v => v > 0)) {
        new Chart(document.getElementById('chart-email'), {
            type: 'doughnut',
            data: {
                labels: data.email_status.labels,
                datasets: [{ data: data.email_status.values, backgroundColor: [C.emerald, C.amber, C.maroonLight], borderWidth: 2, borderColor: '#fff' }]
            },
            options: { ...baseOpts, cutout: '65%', plugins: { ...baseOpts.plugins, tooltip: { callbacks: { label: c => ' '+c.label+': '+c.raw+' slip' } } } }
        });
    }

    if (data.tunjangan_breakdown.values.some(v => v > 0)) {
        new Chart(document.getElementById('chart-tunjangan'), {
            type: 'bar',
            data: {
                labels: data.tunjangan_breakdown.labels,
                datasets: [{ label: 'Tunjangan', data: data.tunjangan_breakdown.values, backgroundColor: [C.maroon, C.maroonLight, C.blue, C.emerald, C.amber, C.slate, '#7c3aed'], borderRadius: 6 }]
            },
            options: {
                ...baseOpts,
                scales: { x: { grid: { display: false } }, y: { grid: { color: '#f1f5f9' }, ticks: { callback: v => fmtShort(v) } } },
                plugins: { ...baseOpts.plugins, tooltip: { callbacks: { label: c => ' '+fmtFull(c.raw) } } }
            }
        });
    }

    if (data.potongan_breakdown.values.some(v => v > 0)) {
        new Chart(document.getElementById('chart-potongan'), {
            type: 'bar',
            data: {
                labels: data.potongan_breakdown.labels,
                datasets: [{ label: 'Potongan', data: data.potongan_breakdown.values, backgroundColor: [C.maroonLight, C.amber, C.slate], borderRadius: 6 }]
            },
            options: {
                ...baseOpts,
                scales: { x: { grid: { display: false } }, y: { grid: { color: '#f1f5f9' }, ticks: { callback: v => fmtShort(v) } } },
                plugins: { ...baseOpts.plugins, tooltip: { callbacks: { label: c => ' '+fmtFull(c.raw) } } }
            }
        });
    }

    new Chart(document.getElementById('chart-trend'), {
        type: 'line',
        data: {
            labels: data.thp_trend.labels,
            datasets: [
                { label: 'Total THP', data: data.thp_trend.thpValues, borderColor: C.maroon, backgroundColor: 'rgba(140,27,61,0.08)', fill: true, tension: 0.35, pointRadius: 4 },
                { label: 'Total Lembur', data: data.thp_trend.pendapatanValues, borderColor: C.emerald, backgroundColor: 'rgba(5,150,105,0.06)', fill: true, tension: 0.35, pointRadius: 4 }
            ]
        },
        options: {
            ...baseOpts,
            scales: { x: { grid: { display: false } }, y: { grid: { color: '#f1f5f9' }, ticks: { callback: v => fmtShort(v) } } },
            plugins: { ...baseOpts.plugins, tooltip: { callbacks: { label: c => ' '+c.dataset.label+': '+fmtFull(c.raw) } } }
        }
    });
});
</script>
@endpush
