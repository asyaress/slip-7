<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Slip Gaji - {{ $slip['employee']['name'] }}</title>
    @include('slip.partials.styles')
    <style>
        @page {
            size: A4 portrait;
            margin: 1.2cm 1.4cm;
        }

        body {
            margin: 0;
            padding: 0;
            background: white;
            font-family: 'DejaVu Serif', serif;
            font-size: 10pt;
            line-height: 1.45;
        }

        .slip-page {
            box-shadow: none !important;
            border: none !important;
            padding: 0 !important;
            margin: 0 !important;
            max-width: none !important;
            font-family: 'DejaVu Serif', serif;
        }

        .slip-kop {
            max-height: 88px;
            margin-bottom: 10px;
        }

        .slip-page .doc-title { font-size: 12pt; margin-bottom: 2px; }
        .slip-page .doc-number { margin-bottom: 14px; }
        .slip-page .opening-text { margin-bottom: 12px; }
        .slip-page .employee-box { margin: 10px 0 12px; padding: 8px 12px; }
        .slip-page .attendance-box { margin: 10px 0 12px; padding: 8px 10px; }
        .slip-page .section-title { margin: 12px 0 6px; }
        .slip-page table.gaji td { padding: 3px 6px; }
        .slip-page .thp-box { margin: 10px 0; padding: 8px 12px; }
        .slip-page .total-pendapatan { margin: 10px 0; padding: 8px 12px; }
        .slip-page .closing-text { margin-top: 14px; }

        /* DomPDF tidak support flex — pakai table layout */
        .slip-page .thp-box,
        .slip-page .total-pendapatan {
            display: table;
            width: 100%;
        }

        .slip-page .thp-box > span,
        .slip-page .total-pendapatan > span {
            display: table-cell;
            vertical-align: middle;
        }

        .slip-page .thp-box .thp-amount,
        .slip-page .total-pendapatan > span:last-child {
            text-align: right;
            white-space: nowrap;
        }

        /* Tanda tangan + QR tidak boleh terpisah antar halaman */
        .slip-page .signature-section {
            margin-top: 18px;
            page-break-inside: avoid;
        }

        .slip-page .signature-place-date {
            margin-bottom: 12px;
            page-break-after: avoid;
        }

        .slip-page .signature-row,
        .slip-page .signature-block {
            page-break-inside: avoid;
        }

        .slip-page .signature-area {
            height: 82px;
            margin-bottom: 6px;
        }

        .slip-page .qr-image {
            width: 76px;
            height: 76px;
        }

        .slip-page .signature-role {
            margin-bottom: 6px;
        }
    </style>
</head>
<body>
    @include('slip.partials.document', ['slip' => $slip, 'images' => $images])
</body>
</html>
