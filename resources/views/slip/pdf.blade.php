<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Slip Gaji - {{ $slip['employee']['name'] }}</title>
    @include('slip.partials.styles')
    <style>
        body { margin: 0; padding: 0; background: white; font-family: 'DejaVu Serif', serif; }
        .slip-page { box-shadow: none !important; border: none !important; padding: 0 !important; margin: 0 !important; max-width: none !important; }
    </style>
</head>
<body>
    @include('slip.partials.document', ['slip' => $slip, 'images' => $images])
</body>
</html>
