@extends('layouts.app')

@section('title', isset($editingSlip) ? 'Edit Slip Gaji' : 'Input Slip Gaji')
@section('page-title', isset($editingSlip) ? 'Edit Slip Gaji' : 'Input Slip Gaji')
@section('page-subtitle', isset($editingSlip)
    ? 'Perbarui slip ' . $editingSlip->employee->name . ' — ' . $editingSlip->periodeLabel()
    : 'Pilih karyawan dan isi rincian gaji untuk generate slip')

@php
    $formData = $formData ?? [];
    $preserveForm = $preserveForm ?? false;

    $formValue = function ($key, $default = '') use ($formData) {
        $val = old($key);
        if ($val !== null) {
            return $val;
        }
        if (array_key_exists($key, $formData)) {
            return $formData[$key];
        }

        return $default;
    };

    $formatFormRupiah = function ($key, $default = '') use ($formValue) {
        $val = $formValue($key, $default);
        if ($val === '' || $val === null) {
            return '';
        }

        return number_format((float) $val, 0, ',', '.');
    };
@endphp

@section('content')
<div id="slip-form-root"
     data-existing-url="{{ route('slip.existing') }}"
     data-lembur-weeks-url="{{ route('slip.lembur-weeks') }}"
     data-monthly-tunjangan-url="{{ route('slip.monthly-tunjangan') }}"
     data-autosave-url="{{ route('slip.autosave') }}"
     @if($preserveForm) data-preserve-form="1" @elseif(!empty($formData)) data-initial-form='@json($formData)' @endif
     class="grid lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2">
        <div id="existing-slip-notice" class="hidden card p-4 mb-6 bg-amber-50 border-amber-200">
            <p class="text-sm text-amber-900">
                <strong>Slip periode ini sudah ada.</strong> Data form diisi otomatis — simpan akan <strong>memperbarui</strong> slip yang sudah ada, bukan membuat baru.
            </p>
        </div>

        @isset($editingSlip)
        <div class="card p-4 mb-6 bg-blue-50 border-blue-200">
            <p class="text-sm text-blue-900">
                Anda sedang <strong>mengedit slip</strong> periode {{ $editingSlip->periodeLabel() }} untuk <strong>{{ $editingSlip->employee->name }}</strong>.
            </p>
        </div>
        @endisset

        <form id="slip-form" action="{{ route('slip.store') }}" method="POST" class="space-y-6">
            @csrf
            @isset($editingSlip)
                <input type="hidden" name="_editing_slip_id" value="{{ $editingSlip->id }}">
            @elseif(old('_editing_slip_id'))
                <input type="hidden" name="_editing_slip_id" value="{{ old('_editing_slip_id') }}">
            @endisset

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
                                    @selected($formValue('employee_id') == $emp->id)>
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
                                <option value="{{ $i + 1 }}" @selected($formValue('bulan', request('bulan', now()->month)) == $i + 1)>{{ $nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="tahun" class="block text-sm font-medium text-slate-700 mb-1">Tahun</label>
                        <input type="number" name="tahun" id="tahun" value="{{ $formValue('tahun', request('tahun', now()->year)) }}" min="2020" max="2100" required
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-slate-100 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-700">Copy data slip bulan sebelumnya</p>
                        <p id="copy-previous-help" class="text-xs text-slate-500">Salin semua slip dari periode sebelumnya ke bulan yang dipilih.</p>
                    </div>
                    <button type="submit" form="copy-previous-form" id="btn-copy-previous" class="btn-secondary w-full sm:w-auto">
                        Copy Slip Bulan Sebelumnya
                    </button>
                </div>
            </section>

            {{-- Kehadiran --}}
            <section class="card p-6">
                <h2 class="text-base font-semibold text-slate-900 mb-4">3. Data Kehadiran</h2>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label for="jumlah_kehadiran" class="block text-sm font-medium text-slate-700 mb-1">Jumlah Kehadiran (Hari Kerja)</label>
                        <input type="number" name="jumlah_kehadiran" id="jumlah_kehadiran" value="{{ $formValue('jumlah_kehadiran', 26) }}" min="0" required
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label for="hadir" class="block text-sm font-medium text-slate-700 mb-1">Hadir</label>
                        <input type="number" name="hadir" id="hadir" value="{{ $formValue('hadir', 26) }}" min="0" required
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label for="sakit_izin" class="block text-sm font-medium text-slate-700 mb-1">Sakit / Izin</label>
                        <input type="number" name="sakit_izin" id="sakit_izin" value="{{ $formValue('sakit_izin', 0) }}" min="0"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label for="tidak_hadir" class="block text-sm font-medium text-slate-700 mb-1">Tidak Hadir (Tanpa Keterangan)</label>
                        <input type="number" name="tidak_hadir" id="tidak_hadir" value="{{ $formValue('tidak_hadir', 0) }}" min="0"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                </div>
            </section>

            {{-- Gaji Pokok --}}
            <section class="card p-6">
                <h2 class="text-base font-semibold text-slate-900 mb-4">4. Gaji Pokok</h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-center">
                    <label class="text-sm font-medium text-slate-700">Gaji Pokok <span class="text-slate-400 font-normal">(Per Bulan)</span></label>
                    <div class="sm:col-span-2 rupiah-field">
                        <span class="rupiah-prefix">Rp</span>
                        <input type="text" inputmode="numeric" name="gaji_pokok" id="gaji_pokok"
                            value="{{ $formatFormRupiah('gaji_pokok') }}" required placeholder="0"
                            class="rupiah-input calc-trigger">
                    </div>
                </div>
            </section>

            {{-- Tunjangan --}}
            <section class="card p-6">
                <h2 class="text-base font-semibold text-slate-900 mb-1">5. Tunjangan</h2>
                <p class="text-xs text-slate-500 mb-4">Isi nominal <strong>per hari</strong> untuk tunjangan harian. Total bulanan dihitung otomatis dan bisa diubah manual. Tunjangan tempat tinggal diisi <strong>langsung per bulan</strong>.</p>

                <div class="hidden sm:grid sm:grid-cols-12 gap-3 mb-2 text-xs font-medium text-slate-400 uppercase tracking-wide">
                    <div class="sm:col-span-5">Jenis Tunjangan</div>
                    <div class="sm:col-span-3">Per Hari</div>
                    <div class="sm:col-span-4">Total Bulanan</div>
                </div>

                @php $tunjanganBulananOnly = config('slip.tunjangan_bulanan_only', []); @endphp
                <div class="space-y-3" id="tunjangan-rows">
                    @foreach(config('slip.tunjangan') as $key => $label)
                    @if(in_array($key, $tunjanganBulananOnly, true))
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center tunj-row tunj-monthly-only-row" data-tunj-key="{{ $key }}" data-tunj-monthly-only="1">
                        <div class="sm:col-span-5 text-sm text-slate-700">{{ $label }}</div>
                        <div class="sm:col-span-7 rupiah-field">
                            <span class="rupiah-prefix">Rp</span>
                            <input type="text" inputmode="numeric" name="tunj_bulanan_{{ $key }}" id="tunj_bulanan_{{ $key }}"
                                value="{{ $formatFormRupiah('tunj_bulanan_'.$key, 0) }}" placeholder="0"
                                class="rupiah-input calc-trigger tunj-bulanan-input tunj-monthly-only-input">
                            <input type="hidden" name="tunj_harian_{{ $key }}" id="tunj_harian_{{ $key }}" value="0">
                            <input type="hidden" name="tunj_mode_{{ $key }}" id="tunj_mode_{{ $key }}" value="bulanan">
                        </div>
                    </div>
                    @else
                    @php $tunjMode = $formValue('tunj_mode_'.$key, 'harian') === 'bulanan' ? 'bulanan' : 'harian'; @endphp
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center tunj-row" data-tunj-key="{{ $key }}" data-bulanan-overridden="{{ $tunjMode === 'bulanan' ? '1' : '0' }}">
                        <div class="sm:col-span-5 text-sm text-slate-700">{{ $label }}</div>
                        <div class="sm:col-span-3 rupiah-field">
                            <span class="rupiah-prefix">Rp</span>
                            <input type="text" inputmode="numeric" name="tunj_harian_{{ $key }}" id="tunj_harian_{{ $key }}"
                                value="{{ $formatFormRupiah('tunj_harian_'.$key, 0) }}" placeholder="0"
                                class="rupiah-input calc-trigger tunj-harian-input">
                        </div>
                        <div class="sm:col-span-4 rupiah-field">
                            <span class="rupiah-prefix">Rp</span>
                            <input type="text" inputmode="numeric" name="tunj_bulanan_{{ $key }}" id="tunj_bulanan_{{ $key }}"
                                value="{{ $formatFormRupiah('tunj_bulanan_'.$key, 0) }}" placeholder="0"
                                class="rupiah-input calc-trigger tunj-bulanan-input">
                        </div>
                        <input type="hidden" name="tunj_mode_{{ $key }}" id="tunj_mode_{{ $key }}" value="{{ $tunjMode }}">
                    </div>
                    @endif
                    @endforeach
                </div>

                <div class="mt-4 pt-4 border-t border-slate-100 flex justify-between items-center">
                    <span class="text-sm font-medium text-slate-700">Total Tunjangan Bulanan</span>
                    <span id="summary-tunj-bulanan-total" class="text-base font-bold text-slate-900">Rp 0</span>
                </div>
            </section>

            {{-- Potongan --}}
            <section class="card p-6 border-red-100">
                <h2 class="text-base font-semibold text-slate-900 mb-4">6. Potongan</h2>
                <div class="space-y-3">
                    @php
                        $potonganFields = [
                            'pot_angsuran' => 'Angsuran',
                            'pot_kasbon' => 'Kasbon',
                            'pot_lain_lain' => 'Lain-Lain (Kelalaian Kerja, Keterlambatan, dll.)',
                        ];
                    @endphp
                    @foreach($potonganFields as $field => $label)
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-center">
                        <label class="text-sm text-slate-700">{{ $label }}</label>
                        <div class="sm:col-span-2 rupiah-field rupiah-field--danger">
                            <span class="rupiah-prefix">Rp</span>
                            <input type="text" inputmode="numeric" name="{{ $field }}" id="{{ $field }}"
                                value="{{ $formatFormRupiah($field, 0) }}" placeholder="0"
                                class="rupiah-input calc-trigger">
                        </div>
                    </div>
                    @endforeach
                </div>
            </section>

            {{-- Fasilitas --}}
            <section class="card p-6">
                <h2 class="text-base font-semibold text-slate-900 mb-1">7. Fasilitas</h2>
                <p class="text-xs text-slate-500 mb-4">Centang fasilitas yang diperoleh karyawan. Hanya nama fasilitas yang tampil di slip.</p>
                <div class="space-y-3">
                    @php
                        $selectedFasilitas = (array) $formValue('fasilitas', []);
                        if (empty($selectedFasilitas) && !$preserveForm && empty($formData)) {
                            $selectedFasilitas = ['bpjs'];
                        }
                    @endphp
                    @foreach(config('slip.fasilitas') as $key => $label)
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="fasilitas[]" value="{{ $key }}"
                            @checked(in_array($key, $selectedFasilitas, true))
                            class="rounded border-slate-300 text-maroon-900 focus:ring-maroon-900 calc-trigger">
                        <span class="text-sm text-slate-700">{{ $label }}</span>
                    </label>
                    @endforeach
                </div>
            </section>

            @include('slip.partials.lembur-form', ['lemburWeeks' => $lemburWeeks ?? []])

            <div class="flex flex-wrap gap-3 items-center justify-between">
                <div id="autosave-status" class="inline-flex items-center gap-2 text-sm text-slate-500" aria-live="polite">
                    <span id="autosave-status-dot" class="h-2 w-2 rounded-full bg-slate-300"></span>
                    <span id="autosave-status-text">Auto-save aktif</span>
                </div>
                <button type="submit" formaction="{{ route('slip.preview') }}" id="btn-preview-slip" class="btn-secondary">
                    Preview Dulu
                </button>
            </div>
        </form>

        <form id="copy-previous-form" action="{{ route('slip.copy-previous') }}" method="POST" class="hidden">
            @csrf
            <input type="hidden" name="bulan" id="copy_previous_bulan" value="{{ $formValue('bulan', request('bulan', now()->month)) }}">
            <input type="hidden" name="tahun" id="copy_previous_tahun" value="{{ $formValue('tahun', request('tahun', now()->year)) }}">
        </form>
    </div>

    {{-- Sidebar Kalkulasi --}}
    <div class="lg:col-span-1">
        <div class="card p-6 sticky top-6">
            <h2 class="text-base font-semibold text-slate-900 mb-4">Ringkasan Otomatis</h2>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-500">Total Tunjangan / Hari</dt>
                    <dd id="summary-tunj-harian" class="font-medium">Rp 0</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Tunjangan × Hadir</dt>
                    <dd id="summary-tunj-earned" class="font-medium">Rp 0</dd>
                </div>
                <div class="flex justify-between hidden" id="summary-tunj-flat-row">
                    <dt class="text-slate-500">Tunjangan Bulanan</dt>
                    <dd id="summary-tunj-flat" class="font-medium">Rp 0</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Total Potongan</dt>
                    <dd id="summary-potongan" class="font-medium text-red-600">Rp 0</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Total Lembur</dt>
                    <dd id="summary-lembur-sidebar" class="font-medium text-amber-700">Rp 0</dd>
                </div>
                <div class="border-t border-slate-200 pt-3 flex justify-between">
                    <dt class="text-slate-900 font-semibold">Take Home Pay</dt>
                    <dd id="summary-thp" class="font-bold text-maroon-900 text-lg">Rp 0</dd>
                </div>
                <div class="flex justify-between pt-2">
                    <dt class="text-slate-900 font-semibold">Total Pendapatan</dt>
                    <dd id="summary-pendapatan" class="font-bold text-slate-900 text-lg">Rp 0</dd>
                </div>
            </dl>
            <p class="mt-4 text-xs text-slate-400">
                THP = Gaji Pokok + (Tunjangan/Hari × Hadir) + Tunj. Tempat Tinggal − Potongan<br>
                Total Pendapatan = THP + Lembur
            </p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@vite('resources/js/slip-form.js')
@endpush
