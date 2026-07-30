@php
    $heading = trim((string) ($signature['heading'] ?? ''));
    $title = trim((string) ($signature['title'] ?? ''));
    $name = trim((string) ($signature['name'] ?? ''));
@endphp

<div class="signature-cell">
    <div class="signature-role">
        @if($heading !== '')
            <div>{{ $heading }}</div>
        @endif
        <div>{{ $title }}</div>
    </div>
    <div class="signature-area">
        @if(!empty($qrSrc))
            <img src="{{ $qrSrc }}" alt="QR {{ $title }}" class="qr-image">
        @endif
    </div>
    <p class="signature-name">{{ $name }}</p>
</div>
