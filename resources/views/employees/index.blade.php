@extends('layouts.app')

@section('title', 'Data Karyawan')
@section('page-title', 'Data Karyawan')
@section('page-subtitle', 'Kelola data karyawan - edit manual jabatan, email, tanggal masuk, status kerja, dan informasi lainnya')

@section('content')
<div class="card overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500">
            {{ $employees->where('is_active', true)->count() }} aktif,
            {{ $employees->where('is_active', false)->count() }} resigned
        </p>
        <a href="{{ route('employees.create') }}" class="btn-primary text-sm">
            + Tambah karyawan
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-6 py-3 font-medium">No</th>
                    <th class="text-left px-6 py-3 font-medium">NIP</th>
                    <th class="text-left px-6 py-3 font-medium">Nama</th>
                    <th class="text-left px-6 py-3 font-medium">Jabatan</th>
                    <th class="text-left px-6 py-3 font-medium">Email</th>
                    <th class="text-left px-6 py-3 font-medium">Mulai Bekerja</th>
                    <th class="text-left px-6 py-3 font-medium">Masa Kerja</th>
                    <th class="text-left px-6 py-3 font-medium">Status</th>
                    <th class="text-right px-6 py-3 font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($employees as $employee)
                <tr class="hover:bg-slate-50/80">
                    <td class="px-6 py-3.5 text-slate-500">{{ $employee->nomor }}</td>
                    <td class="px-6 py-3.5 text-slate-600 whitespace-nowrap font-mono text-xs">{{ $employee->resolvedNip() ?? '-' }}</td>
                    <td class="px-6 py-3.5 font-medium text-slate-900">{{ $employee->name }}</td>
                    <td class="px-6 py-3.5 text-slate-600">{{ $employee->jabatan }}</td>
                    <td class="px-6 py-3.5 text-slate-600">{{ $employee->email }}</td>
                    <td class="px-6 py-3.5 text-slate-600 whitespace-nowrap">{{ $employee->tgl_masuk->format('d-m-Y') }}</td>
                    <td class="px-6 py-3.5 text-slate-600 whitespace-nowrap">{{ $employee->masaKerja() }}</td>
                    <td class="px-6 py-3.5">
                        @if($employee->isResigned())
                            <span class="badge badge-muted">{{ $employee->statusLabel() }}</span>
                        @else
                            <span class="badge badge-success">{{ $employee->statusLabel() }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-3.5 text-right">
                        <a href="{{ route('employees.edit', $employee) }}"
                           class="text-maroon-800 hover:text-maroon-900 font-medium text-sm">
                            Edit
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-6 py-10 text-center text-slate-500">
                        Belum ada data karyawan.
                        <a href="{{ route('employees.create') }}" class="text-maroon-800 font-medium hover:underline">Tambah karyawan pertama</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
