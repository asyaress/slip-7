@extends('layouts.app')

@section('title', 'Tambah Karyawan')
@section('page-title', 'Tambah Karyawan')
@section('page-subtitle', 'Daftarkan karyawan baru untuk slip gaji')

@section('header-actions')
    <a href="{{ route('employees.index') }}" class="btn-secondary text-sm">&larr; Kembali</a>
@endsection

@section('content')
<div class="max-w-2xl">
    <form action="{{ route('employees.store') }}" method="POST" class="card p-6 space-y-5">
        @csrf
        @include('employees._form', ['isCreate' => true])
        <div class="pt-2 flex gap-3">
            <button type="submit" class="btn-primary">Simpan Karyawan</button>
            <a href="{{ route('employees.index') }}" class="btn-secondary">Batal</a>
        </div>
    </form>
</div>
@endsection

@include('employees._form-scripts')
