@extends('layouts.app')

@section('title', 'Keamanan 2FA')
@section('page-title', 'Keamanan Authenticator')
@section('page-subtitle', 'Kelola perangkat Google Authenticator untuk login admin')

@section('content')
<div class="grid lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2 space-y-6">
        <section class="card p-6">
            <h2 class="text-base font-semibold text-slate-900 mb-4">Perangkat Terdaftar</h2>

            @if($devices->isEmpty())
                <p class="text-sm text-slate-500">Belum ada perangkat authenticator.</p>
            @else
                <div class="space-y-3">
                    @foreach($devices as $device)
                    <div class="flex items-center justify-between gap-4 p-4 rounded-lg border border-slate-200">
                        <div>
                            <p class="font-medium text-slate-900">{{ $device->label }}</p>
                            <p class="text-xs text-slate-500 mt-0.5">
                                @if($device->isConfirmed())
                                    Aktif sejak {{ $device->confirmed_at->format('d M Y H:i') }}
                                    @if($device->last_used_at)
                                        · Terakhir dipakai {{ $device->last_used_at->diffForHumans() }}
                                    @endif
                                @else
                                    <span class="text-amber-600">Menunggu verifikasi scan barcode</span>
                                @endif
                            </p>
                        </div>
                        @if($device->isConfirmed())
                        <form method="POST" action="{{ route('security.two-factor.devices.destroy', $device) }}"
                            onsubmit="return confirm('Hapus perangkat ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-secondary text-red-600 border-red-200 hover:bg-red-50">Hapus</button>
                        </form>
                        @endif
                    </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    <div class="lg:col-span-1">
        <section class="card p-6">
            <h2 class="text-base font-semibold text-slate-900 mb-1">Tambah Perangkat</h2>
            <p class="text-xs text-slate-500 mb-4">Scan barcode baru di Google Authenticator (HP lain, tablet, dll.)</p>

            <form method="POST" action="{{ route('security.two-factor.devices.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="label" class="block text-sm font-medium text-slate-700 mb-1">Nama Perangkat</label>
                    <input type="text" name="label" id="label" required maxlength="100"
                        class="input-field" placeholder="Contoh: HP Samsung, iPad HRD">
                </div>
                <button type="submit" class="btn-primary w-full">Tambah & Scan Barcode</button>
            </form>
        </section>

        <div class="card p-6 mt-6 bg-slate-50">
            <h3 class="text-sm font-semibold text-slate-900 mb-2">Alur Login</h3>
            <ol class="text-xs text-slate-600 space-y-2 list-decimal list-inside">
                <li>Login email & password</li>
                <li>Masukkan 6 digit dari Authenticator</li>
                <li>Atau pakai recovery code jika HP tidak ada</li>
            </ol>
        </div>
    </div>
</div>
@endsection
