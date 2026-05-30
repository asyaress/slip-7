@extends('layouts.app')

@section('title', 'Edit Karyawan')
@section('page-title', 'Edit Karyawan')
@section('page-subtitle', $employee->name)

@section('header-actions')
    <a href="{{ route('employees.index') }}" class="btn-secondary text-sm">← Kembali</a>
@endsection

@section('content')
<div class="max-w-2xl">
    <form action="{{ route('employees.update', $employee) }}" method="POST" class="card p-6 space-y-5">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Nomor Urut</label>
            <input type="text" value="{{ $employee->nomor }}" readonly class="input-field bg-slate-50">
        </div>

        <div>
            <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nama Lengkap</label>
            <input type="text" name="name" id="name" value="{{ old('name', $employee->name) }}" required class="input-field">
            @error('name') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
            <input type="email" name="email" id="email" value="{{ old('email', $employee->email) }}" required class="input-field">
            @error('email') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="jabatan" class="block text-sm font-medium text-slate-700 mb-1">Jabatan</label>
            <input type="text" name="jabatan" id="jabatan" value="{{ old('jabatan', $employee->jabatan) }}" required class="input-field">
            @error('jabatan') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="alamat" class="block text-sm font-medium text-slate-700 mb-1">Alamat</label>
            <input type="text" name="alamat" id="alamat" value="{{ old('alamat', $employee->alamat) }}" required class="input-field">
            @error('alamat') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label for="tgl_masuk" class="block text-sm font-medium text-slate-700 mb-1">Tanggal Masuk</label>
                <input type="date" name="tgl_masuk" id="tgl_masuk" value="{{ old('tgl_masuk', $employee->tgl_masuk->format('Y-m-d')) }}" required class="input-field">
                @error('tgl_masuk') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Masa Kerja <span class="text-slate-400 font-normal">(otomatis)</span></label>
                <input type="text" id="display-masa-kerja" value="{{ $employee->masaKerja() }}" readonly class="input-field bg-slate-50">
            </div>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="is_active" id="is_active" value="1"
                @checked(old('is_active', $employee->is_active)) class="rounded border-slate-300 text-maroon-800 focus:ring-maroon-700">
            <label for="is_active" class="text-sm text-slate-700">Karyawan Aktif</label>
        </div>

        <div class="pt-2 flex gap-3">
            <button type="submit" class="btn-primary">Simpan Perubahan</button>
            <a href="{{ route('employees.index') }}" class="btn-secondary">Batal</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('tgl_masuk').addEventListener('change', function() {
    const start = new Date(this.value);
    const now = new Date();
    if (isNaN(start)) return;

    let years = now.getFullYear() - start.getFullYear();
    let months = now.getMonth() - start.getMonth();
    let days = now.getDate() - start.getDate();

    if (days < 0) { months--; days += new Date(now.getFullYear(), now.getMonth(), 0).getDate(); }
    if (months < 0) { years--; months += 12; }

    document.getElementById('display-masa-kerja').value =
        `${years} Tahun ${months} Bulan ${days} Hari`;
});
</script>
@endpush
