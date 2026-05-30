@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Ringkasan slip gaji bulan ini')

@section('content')
<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    <div class="card p-5">
        <p class="text-sm text-slate-500">Total Karyawan Aktif</p>
        <p class="text-3xl font-bold text-maroon-900 mt-1">{{ $stats['total_karyawan'] }}</p>
    </div>
    <div class="card p-5">
        <p class="text-sm text-slate-500">Slip Bulan Ini</p>
        <p class="text-3xl font-bold text-slate-900 mt-1">{{ $stats['slip_bulan_ini'] }}</p>
    </div>
    <div class="card p-5">
        <p class="text-sm text-slate-500">Belum Kirim Email</p>
        <p class="text-3xl font-bold text-amber-600 mt-1">{{ $stats['belum_kirim'] }}</p>
    </div>
    <div class="card p-5">
        <p class="text-sm text-slate-500">Sudah Kirim Email</p>
        <p class="text-3xl font-bold text-emerald-600 mt-1">{{ $stats['sudah_kirim'] }}</p>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 card">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="font-semibold text-slate-900">Slip Terbaru</h2>
            <a href="{{ route('review.index') }}" class="text-sm text-maroon-800 hover:text-maroon-900 font-medium">Lihat Semua →</a>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse($recentSlips as $slip)
                <div class="px-6 py-4 flex items-center justify-between hover:bg-slate-50">
                    <div>
                        <p class="font-medium text-slate-900">{{ $slip->employee->name }}</p>
                        <p class="text-sm text-slate-500">{{ $slip->periodeLabel() }} · THP {{ \App\Services\SlipGajiCalculator::formatRupiah($slip->take_home_pay) }}</p>
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
                <a href="{{ route('employees.index') }}" class="btn-secondary w-full">Kelola Karyawan</a>
                <a href="{{ route('review.index') }}" class="btn-secondary w-full">Review & Blast Email</a>
            </div>
        </div>
    </div>
</div>
@endsection
