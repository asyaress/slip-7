@extends('layouts.guest')

@section('title', 'Login')
@section('heading', 'Masuk')
@section('subheading', 'Slip Gaji HRD')

@section('content')
<form method="POST" action="{{ route('login') }}" class="auth-form">
    @csrf

    <div class="auth-field">
        <label for="email" class="auth-label">Email</label>
        <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
            class="auth-input auth-input--plain" placeholder="admin@gmail.com" autocomplete="username">
        @error('email')
            <p class="auth-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="auth-field">
        <label for="password" class="auth-label">Password</label>
        <input type="password" name="password" id="password" required
            class="auth-input auth-input--plain" placeholder="••••••••" autocomplete="current-password">
        @error('password')
            <p class="auth-error">{{ $message }}</p>
        @enderror
    </div>

    <label class="auth-checkbox">
        <input type="checkbox" name="remember" value="1">
        <span>Ingat saya</span>
    </label>

    <button type="submit" class="auth-submit">Masuk</button>
</form>
@endsection
