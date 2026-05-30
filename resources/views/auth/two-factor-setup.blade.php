@extends('layouts.guest')

@section('title', 'Setup Authenticator')
@section('heading', 'Scan Barcode')
@section('subheading', 'Google Authenticator')

@section('content')
<div class="auth-form">
    <div class="auth-info-box auth-info-box--slate">
        Scan barcode di Google Authenticator, lalu masukkan kode 6 digit.
    </div>

    <div class="auth-qr-wrap">
        {!! $qrCode !!}
    </div>

    <p class="text-xs text-slate-400 text-center mb-4 break-all font-mono">{{ $manualKey }}</p>

    <form method="POST" action="{{ route('two-factor.setup.confirm') }}" class="space-y-3">
        @csrf
        <div class="auth-field">
            <label for="code" class="auth-label">Kode 6 digit</label>
            <input type="text" name="code" id="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                required autofocus autocomplete="one-time-code"
                class="auth-code-input" placeholder="000000">
        </div>
        <button type="submit" class="auth-submit">Verifikasi</button>
    </form>

    @if($fromAdmin)
        <a href="{{ route('security.two-factor') }}" class="auth-link">Batal</a>
    @endif
</div>
@endsection
