@extends('layouts.app')

@section('title', 'Review Slip Gaji')
@section('page-title', 'Review Slip Gaji')
@section('page-subtitle', 'Lihat semua slip yang telah di-generate dan kirim ke email karyawan')

@section('header-actions')
<div class="flex flex-wrap items-center justify-end gap-2">
    <form method="POST" action="{{ route('slip.copy-previous') }}" id="review-copy-previous-form" class="flex items-center">
        @csrf
        <input type="hidden" name="bulan" value="{{ $bulan }}">
        <input type="hidden" name="tahun" value="{{ $tahun }}">
        <input type="hidden" name="redirect_to" value="review">
        <button type="submit" id="review-copy-previous-btn" class="btn-primary text-sm py-2">
            Salin Slip Gaji Bulan Lalu
        </button>
    </form>
    <form method="GET" action="{{ route('review.index') }}" class="flex items-center gap-2">
        <select name="bulan" class="select-field w-auto text-sm">
            @foreach(['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $i => $nama)
                <option value="{{ $i + 1 }}" @selected($bulan == $i + 1)>{{ $nama }}</option>
            @endforeach
        </select>
        <input type="number" name="tahun" value="{{ $tahun }}" min="2020" max="2100" class="input-field w-24 text-sm">
        <button type="submit" class="btn-secondary text-sm py-2">Filter</button>
    </form>
</div>
@endsection

@section('content')
@php
    $periodeAktif = \Carbon\Carbon::create($tahun, $bulan, 1);
    $periodeSumber = $periodeAktif->copy()->subMonthNoOverflow();
@endphp

<div class="card p-4 mb-6 border-maroon-200 bg-maroon-50">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="font-semibold text-maroon-900">Salin Slip Gaji Bulan Lalu</p>
            <p id="review-copy-previous-help" class="text-sm text-maroon-800/80">
                Salin semua slip dari {{ $periodeSumber->locale('id')->translatedFormat('F Y') }} ke {{ $periodeAktif->locale('id')->translatedFormat('F Y') }}. Slip yang sudah ada pada periode tujuan akan diperbarui.
            </p>
        </div>
        <div id="review-copy-previous-loading" class="hidden items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm font-medium text-maroon-900 shadow-sm ring-1 ring-maroon-200">
            <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                <path class="opacity-90" fill="currentColor" d="M12 2a10 10 0 0 1 10 10h-3a7 7 0 0 0-7-7V2z"></path>
            </svg>
            Menyalin semua slip gaji karyawan. Mohon tunggu...
        </div>
    </div>
</div>

<div class="grid sm:grid-cols-4 gap-4 mb-6">
    <div class="card p-4">
        <p class="text-xs text-slate-500 uppercase tracking-wide">Total Slip</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">{{ $summary['total'] }}</p>
    </div>
    <div class="card p-4">
        <p class="text-xs text-slate-500 uppercase tracking-wide">Belum Kirim</p>
        <p class="text-2xl font-bold text-amber-600 mt-1">{{ $summary['belum_kirim'] }}</p>
    </div>
    <div class="card p-4">
        <p class="text-xs text-slate-500 uppercase tracking-wide">Sudah Kirim</p>
        <p class="text-2xl font-bold text-emerald-600 mt-1">{{ $summary['sudah_kirim'] }}</p>
    </div>
    <div class="card p-4">
        <p class="text-xs text-slate-500 uppercase tracking-wide">Total THP</p>
        <p class="text-lg font-bold text-maroon-900 mt-1">{{ \App\Services\SlipGajiCalculator::formatRupiah($summary['total_thp']) }}</p>
    </div>
</div>

@if($slips->isNotEmpty())
<form id="blast-form" action="{{ route('review.blast') }}" method="POST">
    @csrf
    <input type="hidden" name="bulan" value="{{ $bulan }}">
    <input type="hidden" name="tahun" value="{{ $tahun }}">

    <div class="card p-4 mb-4 flex flex-wrap items-center justify-between gap-4 bg-maroon-50 border-maroon-200">
        <div>
            <p class="font-semibold text-maroon-900">Blast Email Slip Gaji</p>
            <p class="text-sm text-maroon-700/80">Centang karyawan yang ingin dikirimi slip, lalu klik Blast Email.</p>
        </div>
        <div class="flex items-center gap-3">
            <span id="selected-count" class="text-sm text-maroon-800 font-medium">0 dipilih</span>
            <button type="submit" id="blast-btn" disabled class="btn-primary opacity-50 cursor-not-allowed">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Blast Email Terpilih
            </button>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 w-10">
                            <input type="checkbox" id="select-all" class="rounded border-slate-300 text-maroon-800 focus:ring-maroon-800" title="Pilih semua">
                        </th>
                        <th class="text-left px-4 py-3 font-medium">No</th>
                        <th class="text-left px-6 py-3 font-medium">Nama Karyawan</th>
                        <th class="text-left px-6 py-3 font-medium">Periode</th>
                        <th class="text-right px-6 py-3 font-medium">Take Home Pay</th>
                        <th class="text-center px-6 py-3 font-medium">Status Email</th>
                        <th class="text-right px-6 py-3 font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($slips as $slip)
                    <tr class="hover:bg-slate-50/80">
                        <td class="px-4 py-3.5">
                            <input type="checkbox" name="slip_ids[]" value="{{ $slip->id }}"
                                class="slip-checkbox rounded border-slate-300 text-maroon-800 focus:ring-maroon-800">
                        </td>
                        <td class="px-4 py-3.5 text-slate-500">{{ $slip->employee->nomor }}</td>
                        <td class="px-6 py-3.5">
                            <p class="font-medium text-slate-900">{{ $slip->employee->name }}</p>
                            <p class="text-xs text-slate-400">{{ $slip->employee->email }}</p>
                        </td>
                        <td class="px-6 py-3.5 text-slate-600">{{ $slip->periodeLabel() }}</td>
                        <td class="px-6 py-3.5 text-right font-medium text-slate-900">
                            {{ \App\Services\SlipGajiCalculator::formatRupiah($slip->take_home_pay) }}
                        </td>
                        <td class="px-6 py-3.5 text-center">
                            @if($slip->isEmailSent())
                                <span class="badge badge-success">Terkirim</span>
                                <p class="text-xs text-slate-400 mt-1">{{ $slip->email_sent_at->format('d/m/Y H:i') }}</p>
                            @elseif($slip->isEmailFailed())
                                <span class="badge badge-warning"@if($msg = $slip->emailFailureMessage()) title="{{ e($msg) }}"@endif>Gagal</span>
                            @else
                                <span class="badge badge-muted">Belum</span>
                            @endif
                        </td>
                        <td class="px-6 py-3.5 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('review.show', $slip) }}" class="text-maroon-800 hover:underline text-sm font-medium">Lihat</a>
                                <a href="{{ route('slip.edit', $slip) }}" class="text-sm text-slate-500 hover:text-maroon-800" title="Edit slip">✏️</a>
                                <form action="{{ route('review.send', $slip) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-sm text-slate-500 hover:text-maroon-800" title="Kirim email">📧</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</form>
@else
<div class="card overflow-hidden">
    <div class="px-6 py-16 text-center text-slate-400">
        Belum ada slip untuk periode ini.
        <a href="{{ route('slip.create') }}" class="text-maroon-800 font-medium block mt-1">Input slip gaji →</a>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.slip-checkbox');
    const blastBtn = document.getElementById('blast-btn');
    const selectedCount = document.getElementById('selected-count');
    const blastForm = document.getElementById('blast-form');
    const copyPreviousForm = document.getElementById('review-copy-previous-form');
    const copyPreviousBtn = document.getElementById('review-copy-previous-btn');
    const copyPreviousLoading = document.getElementById('review-copy-previous-loading');
    const copyPreviousHelp = document.getElementById('review-copy-previous-help');

    function updateSelection() {
        const checked = document.querySelectorAll('.slip-checkbox:checked');
        const count = checked.length;

        if (selectedCount) {
            selectedCount.textContent = `${count} dipilih`;
        }

        if (blastBtn) {
            blastBtn.disabled = count === 0;
            blastBtn.classList.toggle('opacity-50', count === 0);
            blastBtn.classList.toggle('cursor-not-allowed', count === 0);
        }

        if (selectAll) {
            selectAll.checked = count > 0 && count === checkboxes.length;
            selectAll.indeterminate = count > 0 && count < checkboxes.length;
        }
    }

    selectAll?.addEventListener('change', () => {
        checkboxes.forEach(cb => { cb.checked = selectAll.checked; });
        updateSelection();
    });

    checkboxes.forEach(cb => cb.addEventListener('change', updateSelection));

    blastForm?.addEventListener('submit', async (e) => {
        const count = document.querySelectorAll('.slip-checkbox:checked').length;
        if (count === 0) {
            e.preventDefault();
            return;
        }

        e.preventDefault();

        const confirmed = await window.appDialogs?.confirm({
            title: 'Blast Email Slip Gaji?',
            text: `Email slip gaji akan dikirim ke ${count} karyawan terpilih.`,
            confirmText: 'Ya, kirim email',
            cancelText: 'Batal',
        });

        if (!confirmed) {
            return;
        }

        blastForm.submit();
    });

    copyPreviousForm?.addEventListener('submit', async (e) => {
        e.preventDefault();

        const confirmed = await window.appDialogs?.confirm({
            title: 'Salin Slip Gaji Bulan Lalu?',
            text: 'Semua slip gaji karyawan dari bulan lalu akan disalin ke periode review yang sedang dibuka. Slip yang sudah ada akan diperbarui.',
            confirmText: 'Ya, salin sekarang',
            cancelText: 'Batal',
        });

        if (!confirmed) {
            return;
        }

        if (copyPreviousBtn) {
            copyPreviousBtn.disabled = true;
            copyPreviousBtn.textContent = 'Menyalin Slip Gaji...';
            copyPreviousBtn.classList.add('opacity-75');
        }

        if (copyPreviousLoading) {
            copyPreviousLoading.classList.remove('hidden');
            copyPreviousLoading.classList.add('inline-flex');
        }

        if (copyPreviousHelp) {
            copyPreviousHelp.textContent = 'Proses salin sedang berjalan. Jangan tutup halaman sampai selesai.';
        }

        copyPreviousForm.submit();
    });

    updateSelection();
});
</script>
@endpush
