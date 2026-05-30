@extends('layouts.guest')

@section('title', 'Recovery Code')
@section('heading', 'Recovery Code')
@section('subheading', 'Simpan di tempat aman')

@section('content')
<div class="auth-form">
    <div class="auth-info-box auth-info-box--red">
        Setiap kode hanya bisa dipakai sekali.
    </div>

    <div class="auth-recovery-grid">
        @foreach($codes as $code)
            <div class="auth-recovery-code">{{ $code }}</div>
        @endforeach
    </div>

    <form method="POST" action="{{ route('two-factor.recovery-codes.acknowledge') }}">
        @csrf
        <button type="submit" class="auth-submit">Sudah Disimpan — Lanjut</button>
    </form>
</div>
@endsection
