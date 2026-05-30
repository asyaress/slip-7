@extends('layouts.app')

@section('title', 'Input Slip Gaji')
@section('page-title', 'Input Slip Gaji')
@section('page-subtitle', 'Pilih karyawan dan isi rincian gaji untuk generate slip')

@php
    $formatOldRupiah = function ($key, $default = '') {
        $val = old($key, $default);
        if ($val === '' || $val === null) {
            return '';
        }
        return number_format((float) $val, 0, ',', '.');
    };
@endphp

@section('content')
<div class="grid lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2">
        <form id="slip-form" action="{{ route('slip.store') }}" method="POST" class="space-y-6">
            @csrf

            {{-- Pilih Karyawan --}}
            <section class="card p-6">
                <h2 class="text-base font-semibold text-slate-900 mb-4">1. Pilih Karyawan</h2>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label for="employee_id" class="block text-sm font-medium text-slate-700 mb-1">Nama Karyawan</label>
                        <select name="employee_id" id="employee_id" required class="select-field">
                            <option value="">-- Pilih Karyawan --</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" data-jabatan="{{ $emp->jabatan }}" data-email="{{ $emp->email }}"
                                    @selected(old('employee_id') == $emp->id)>
                                    {{ $emp->nomor }}. {{ $emp->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Jabatan</label>
                        <input type="text" id="display-jabatan" readonly class="input-field bg-slate-50">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                        <input type="text" id="display-email" readonly class="input-field bg-slate-50">
                    </div>
                </div>
            </section>

            {{-- Periode --}}
            <section class="card p-6">
                <h2 class="text-base font-semibold text-slate-900 mb-4">2. Periode Gaji</h2>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label for="bulan" class="block text-sm font-medium text-slate-700 mb-1">Bulan</label>
                        <select name="bulan" id="bulan" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            @foreach(['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $i => $nama)
                                <option value="{{ $i + 1 }}" @selected(old('bulan', now()->month) == $i + 1)>{{ $nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="tahun" class="block text-sm font-medium text-slate-700 mb-1">Tahun</label>
                        <input type="number" name="tahun" id="tahun" value="{{ old('tahun', now()->year) }}" min="2020" max="2100" required
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                </div>
            </section>

            {{-- Kehadiran --}}
            <section class="card p-6">
                <h2 class="text-base font-semibold text-slate-900 mb-4">3. Data Kehadiran</h2>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label for="jumlah_kehadiran" class="block text-sm font-medium text-slate-700 mb-1">Jumlah Kehadiran (Hari Kerja)</label>
                        <input type="number" name="jumlah_kehadiran" id="jumlah_kehadiran" value="{{ old('jumlah_kehadiran', 26) }}" min="0" required
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label for="hadir" class="block text-sm font-medium text-slate-700 mb-1">Hadir</label>
                        <input type="number" name="hadir" id="hadir" value="{{ old('hadir', 26) }}" min="0" required
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label for="sakit_izin" class="block text-sm font-medium text-slate-700 mb-1">Sakit / Izin</label>
                        <input type="number" name="sakit_izin" id="sakit_izin" value="{{ old('sakit_izin', 0) }}" min="0"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label for="tidak_hadir" class="block text-sm font-medium text-slate-700 mb-1">Tidak Hadir (Tanpa Keterangan)</label>
                        <input type="number" name="tidak_hadir" id="tidak_hadir" value="{{ old('tidak_hadir', 0) }}" min="0"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                </div>
            </section>

            {{-- Rincian Gaji --}}
            <section class="card p-6">
                <h2 class="text-base font-semibold text-slate-900 mb-4">4. Rincian Gaji</h2>
                <div class="space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-center">
                        <label class="text-sm font-medium text-slate-700">Gaji Pokok <span class="text-slate-400 font-normal">(Per Bulan)</span></label>
                        <div class="sm:col-span-2 rupiah-field">
                            <span class="rupiah-prefix">Rp</span>
                            <input type="text" inputmode="numeric" name="gaji_pokok" id="gaji_pokok"
                                value="{{ $formatOldRupiah('gaji_pokok') }}" required placeholder="0"
                                class="rupiah-input calc-trigger">
                        </div>
                    </div>

                    @php
                        $tunjanganFields = [
                            'tunj_transport' => 'Tunjangan Transport',
                            'tunj_kehadiran' => 'Tunjangan Kehadiran',
                            'tunj_kinerja' => 'Tunjangan Kinerja',
                            'tunj_jabatan' => 'Tunjangan Jabatan',
                            'tunj_perawatan' => 'Tunjangan Perawatan',
                            'tunj_operator' => 'Tunjangan Operator',
                            'tunj_konsumsi' => 'Tunjangan Konsumsi',
                        ];
                    @endphp

                    @foreach($tunjanganFields as $field => $label)
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-center">
                        <label class="text-sm text-slate-700">{{ $label }} <span class="text-slate-400">(Per Bulan)</span></label>
                        <div class="sm:col-span-2 rupiah-field">
                            <span class="rupiah-prefix">Rp</span>
                            <input type="text" inputmode="numeric" name="{{ $field }}" id="{{ $field }}"
                                value="{{ $formatOldRupiah($field, 0) }}" placeholder="0"
                                class="rupiah-input calc-trigger">
                        </div>
                    </div>
                    @endforeach
                </div>
            </section>

            {{-- Potongan --}}
            <section class="card p-6 border-red-100">
                <h2 class="text-base font-semibold text-slate-900 mb-4">5. Potongan</h2>
                <div class="space-y-3">
                    @php
                        $potonganFields = [
                            'pot_angsuran' => 'Angsuran',
                            'pot_kasbon' => 'Kasbon',
                        ];
                    @endphp
                    @foreach($potonganFields as $field => $label)
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-center">
                        <label class="text-sm text-slate-700">{{ $label }}</label>
                        <div class="sm:col-span-2 rupiah-field rupiah-field--danger">
                            <span class="rupiah-prefix">Rp</span>
                            <input type="text" inputmode="numeric" name="{{ $field }}" id="{{ $field }}"
                                value="{{ $formatOldRupiah($field, 0) }}" placeholder="0"
                                class="rupiah-input calc-trigger">
                        </div>
                    </div>
                    @endforeach
                </div>
            </section>

            {{-- Fasilitas --}}
            <section class="card p-6">
                <h2 class="text-base font-semibold text-slate-900 mb-4">6. Fasilitas</h2>
                <div class="space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-center">
                        <label class="text-sm text-slate-700">BPJS Kesehatan <span class="text-slate-400">(Per Bulan)</span></label>
                        <div class="sm:col-span-2 rupiah-field">
                            <span class="rupiah-prefix">Rp</span>
                            <input type="text" inputmode="numeric" name="bpjs_kesehatan" id="bpjs_kesehatan"
                                value="{{ $formatOldRupiah('bpjs_kesehatan', 186222) }}" placeholder="0"
                                class="rupiah-input calc-trigger">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-center">
                        <label class="text-sm text-slate-700">Makan Siang/Malam <span class="text-slate-400">(Per Bulan)</span></label>
                        <div class="sm:col-span-2 rupiah-field">
                            <span class="rupiah-prefix">Rp</span>
                            <input type="text" inputmode="numeric" name="makan_siang_malam" id="makan_siang_malam"
                                value="{{ $formatOldRupiah('makan_siang_malam', 0) }}" placeholder="0"
                                class="rupiah-input calc-trigger">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-center">
                        <label class="text-sm text-slate-700">Pensiun <span class="text-slate-400">(Per Bulan)</span></label>
                        <div class="sm:col-span-2 rupiah-field">
                            <span class="rupiah-prefix">Rp</span>
                            <input type="text" inputmode="numeric" name="pensiun" id="pensiun"
                                value="{{ $formatOldRupiah('pensiun', 0) }}" placeholder="0"
                                class="rupiah-input calc-trigger">
                        </div>
                    </div>
                </div>
            </section>

            <div class="flex flex-wrap gap-3">
                <button type="submit" formaction="{{ route('slip.store') }}" class="btn-primary">
                    Simpan & Generate Slip
                </button>
                <button type="submit" formaction="{{ route('slip.preview') }}" class="btn-secondary">
                    Preview Dulu
                </button>
            </div>
        </form>
    </div>

    {{-- Sidebar Kalkulasi --}}
    <div class="lg:col-span-1">
        <div class="card p-6 sticky top-6">
            <h2 class="text-base font-semibold text-slate-900 mb-4">Ringkasan Otomatis</h2>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-500">Total Tunjangan</dt>
                    <dd id="summary-tunj" class="font-medium">Rp 0</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Total Potongan</dt>
                    <dd id="summary-potongan" class="font-medium text-red-600">Rp 0</dd>
                </div>
                <div class="border-t border-slate-200 pt-3 flex justify-between">
                    <dt class="text-slate-900 font-semibold">Take Home Pay</dt>
                    <dd id="summary-thp" class="font-bold text-maroon-900 text-lg">Rp 0</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Total Fasilitas</dt>
                    <dd id="summary-fasilitas" class="font-medium">Rp 0</dd>
                </div>
                <div class="border-t border-slate-200 pt-3 flex justify-between">
                    <dt class="text-slate-900 font-semibold">Total Pendapatan</dt>
                    <dd id="summary-total" class="font-bold text-green-600">Rp 0</dd>
                </div>
            </dl>
            <p class="mt-4 text-xs text-slate-400">
                THP = Gaji Pokok + Total Tunjangan − Potongan
            </p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@vite('resources/js/slip-form.js')
@endpush
