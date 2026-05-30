<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Slip Gaji - {{ $slip['employee']['name'] }}</title>
    @include('slip.partials.styles')
</head>
<body style="margin:0; padding:0; background:white;">
    @include('slip.partials.document', ['slip' => $slip])
    <script>window.onload = () => window.print();</script>
</body>
</html>
