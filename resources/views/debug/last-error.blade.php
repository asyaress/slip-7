<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Debug — Last Error</title>
    <style>
        body { font-family: ui-monospace, monospace; background: #0f172a; color: #e2e8f0; margin: 0; padding: 1.5rem; }
        h1 { font-size: 1.1rem; margin: 0 0 1rem; color: #f87171; }
        pre { background: #1e293b; border: 1px solid #334155; border-radius: 8px; padding: 1rem; overflow: auto; white-space: pre-wrap; word-break: break-word; font-size: 12px; line-height: 1.5; }
        .meta { color: #94a3b8; margin-bottom: 1rem; font-size: 13px; }
        a { color: #38bdf8; }
    </style>
</head>
<body>
    <h1>Laravel Log — 3 entri terakhir</h1>
    <p class="meta">
        APP_DEBUG={{ $appDebug ? 'true' : 'false' }} |
        <a href="{{ url('/debug/status') }}">JSON status</a> |
        <a href="{{ url('/debug/test-slip') }}">Test slip build</a>
    </p>
    @forelse($entries as $entry)
        <pre>{{ trim($entry) }}</pre>
    @empty
        <pre>Log kosong.</pre>
    @endforelse
</body>
</html>
