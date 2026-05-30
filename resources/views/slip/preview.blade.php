@extends('layouts.app')

@section('title', 'Preview Slip - ' . $slip['employee']['name'])
@section('page-title', 'Preview Slip Gaji')
@section('page-subtitle', $slip['employee']['name'] . ' — ' . $slip['nama_bulan'] . ' ' . $slip['tahun'])

@push('styles')
    @include('slip.partials.styles')
@endpush

@section('header-actions')
<div class="no-print flex gap-2">
    @isset($savedSlip)
        <a href="{{ route('slip.edit', $savedSlip) }}" class="btn-secondary text-sm">Edit Slip</a>
        <a href="{{ route('review.print', $savedSlip) }}" target="_blank" class="btn-primary text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Cetak / Save PDF
        </a>
        <form action="{{ route('review.send', $savedSlip) }}" method="POST" class="inline">
            @csrf
            <button type="submit" class="btn-secondary text-sm">Kirim Email</button>
        </form>
        <a href="{{ route('review.index', ['bulan' => $savedSlip->bulan, 'tahun' => $savedSlip->tahun]) }}" class="btn-secondary text-sm">Review Semua</a>
    @else
        <button type="button" onclick="window.print()" class="btn-primary text-sm">Cetak / Save PDF</button>
        <a href="{{ $returnUrl ?? route('slip.create') }}" class="btn-secondary text-sm">← Kembali ke Form</a>
    @endisset
</div>
@endsection

@section('content')
@include('slip.partials.document', ['slip' => $slip])
@endsection

@push('scripts')
<script>
    window.addEventListener('beforeprint', () => {
        document.querySelectorAll('.flash-alert').forEach(el => el.style.display = 'none');
    });
</script>
@endpush
