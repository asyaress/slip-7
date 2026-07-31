@extends('layouts.app')

@section('title', 'Edit Karyawan')
@section('page-title', 'Edit Karyawan')
@section('page-subtitle', $employee->name)

@section('header-actions')
    <a href="{{ route('employees.index') }}" class="btn-secondary text-sm">&larr; Kembali</a>
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
            <label class="block text-sm font-medium text-slate-700 mb-1">NIP <span class="text-slate-400 font-normal">(otomatis)</span></label>
            <input type="text" value="{{ $employee->resolvedNip() ?? '-' }}" readonly class="input-field bg-slate-50 font-mono text-sm">
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
                <label for="tgl_lahir" class="block text-sm font-medium text-slate-700 mb-1">Tanggal Lahir</label>
                <input type="date" name="tgl_lahir" id="tgl_lahir" value="{{ old('tgl_lahir', $employee->tgl_lahir?->format('Y-m-d')) }}" class="input-field">
                @error('tgl_lahir') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="jenis_kelamin" class="block text-sm font-medium text-slate-700 mb-1">Jenis Kelamin</label>
                <select name="jenis_kelamin" id="jenis_kelamin" class="input-field">
                    <option value="">- Pilih -</option>
                    <option value="LAKI-LAKI" @selected(old('jenis_kelamin', $employee->jenis_kelamin) === 'LAKI-LAKI')>Laki-laki</option>
                    <option value="PEREMPUAN" @selected(old('jenis_kelamin', $employee->jenis_kelamin) === 'PEREMPUAN')>Perempuan</option>
                </select>
                @error('jenis_kelamin') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label for="tgl_masuk" class="block text-sm font-medium text-slate-700 mb-1">Mulai Bekerja</label>
                <input type="date" name="tgl_masuk" id="tgl_masuk" value="{{ old('tgl_masuk', $employee->tgl_masuk->format('Y-m-d')) }}" required class="input-field">
                @error('tgl_masuk') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Masa Kerja <span class="text-slate-400 font-normal">(otomatis)</span></label>
                <input type="text" id="display-masa-kerja" value="{{ $employee->masaKerja() }}" readonly class="input-field bg-slate-50">
            </div>
        </div>

        <div>
            <label for="is_active" class="block text-sm font-medium text-slate-700 mb-1">Status Kerja</label>
            <select name="is_active" id="is_active" required class="input-field">
                @php $statusValue = (string) old('is_active', $employee->is_active ? '1' : '0'); @endphp
                <option value="1" @selected($statusValue === '1')>Aktif</option>
                <option value="0" @selected($statusValue === '0')>Resigned</option>
            </select>
            <p class="mt-1 text-xs text-slate-500">Karyawan resigned tidak akan muncul di Input Slip Gaji dan tidak ikut Copy Slip Bulan Sebelumnya.</p>
            @error('is_active') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
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
