@extends('layouts.guest')

@section('title', 'Verifikasi 2FA')
@section('heading', 'Verifikasi')
@section('subheading', 'Google Authenticator')

@section('content')
<div class="auth-form">
    <form method="POST" action="{{ route('two-factor.challenge') }}" class="space-y-3">
        @csrf
        <div class="auth-field">
            <label for="code" class="auth-label">Kode 6 digit / Recovery code</label>
            <input type="text" name="code" id="code" required autofocus autocomplete="one-time-code"
                class="auth-code-input text-base tracking-widest uppercase" placeholder="000000">
        </div>
        <button type="submit" class="auth-submit">Verifikasi</button>
    </form>

    <a href="{{ route('login') }}" class="auth-link">Kembali ke login</a>
</div>
@endsection
