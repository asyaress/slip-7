<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Slip Gaji - {{ $slip['employee']['name'] }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0.85cm 1.1cm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            background: white;
            font-family: 'DejaVu Serif', serif;
            font-size: 9pt;
            line-height: 1.28;
            color: #1a1a1a;
        }

        .print-container,
        .slip-page {
            margin: 0;
            padding: 0;
            max-width: none;
            width: 100%;
        }

        .slip-kop {
            width: 100%;
            max-height: 64px;
            object-fit: contain;
            object-position: center top;
            margin-bottom: 4px;
        }

        .doc-title {
            text-align: center;
            font-weight: bold;
            font-size: 11pt;
            letter-spacing: 0.3px;
            text-decoration: underline;
            margin-bottom: 2px;
            color: #42091a;
        }

        .doc-number {
            text-align: center;
            font-size: 9pt;
            color: #444;
            margin-bottom: 8px;
        }

        .opening-text {
            text-align: justify;
            margin: 0 0 8px;
        }

        table.info {
            width: 100%;
            border-collapse: collapse;
        }

        table.info td {
            padding: 1px 6px 1px 0;
            vertical-align: top;
        }

        table.info td.label {
            width: 95px;
            font-weight: 500;
            white-space: nowrap;
        }

        table.info td.colon {
            width: 10px;
        }

        .employee-box {
            border: 1px solid #d1d5db;
            background: #fafafa;
            padding: 6px 10px;
            margin: 6px 0 8px;
        }

        .attendance-box {
            border: 1px solid #781a38;
            border-left: 3px solid #781a38;
            background: #fdf2f4;
            padding: 5px 8px;
            margin: 6px 0 8px;
            font-size: 9pt;
        }

        .section-title {
            font-weight: bold;
            font-size: 9.5pt;
            color: #42091a;
            margin: 6px 0 2px;
            padding-bottom: 2px;
            border-bottom: 1px solid #e5e7eb;
        }

        table.gaji {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
        }

        table.gaji td {
            padding: 2px 4px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f0f0;
            font-size: 9pt;
        }

        table.gaji tr.subtotal td {
            border-top: 1px solid #333;
            border-bottom: none;
            font-weight: bold;
            padding-top: 3px;
        }

        table.gaji tr.section-header td {
            font-weight: bold;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            color: #42091a;
            padding-top: 4px;
        }

        .amount {
            text-align: right;
            white-space: nowrap;
        }

        .unit-label {
            font-size: 8pt;
            color: #666;
            width: 72px;
            white-space: nowrap;
        }

        .highlight {
            background-color: #fef08a;
            padding: 1px 4px;
            font-weight: bold;
        }

        .thp-box,
        .total-pendapatan {
            display: table;
            width: 100%;
            font-weight: bold;
        }

        .thp-box {
            background: #fdf2f4;
            border: 1px solid #781a38;
            padding: 5px 8px;
            margin: 6px 0;
        }

        .thp-box > span,
        .total-pendapatan > span {
            display: table-cell;
            vertical-align: middle;
        }

        .thp-box .thp-amount {
            text-align: right;
            white-space: nowrap;
            font-size: 10pt;
            color: #42091a;
            background: #fef08a;
            padding: 2px 6px;
        }

        .total-pendapatan {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 5px 8px;
            margin: 6px 0;
            font-size: 9.5pt;
        }

        .total-pendapatan > span:last-child {
            text-align: right;
            white-space: nowrap;
        }

        .deduction {
            color: #b91c1c;
        }

        .closing-text {
            margin: 8px 0 0;
            text-align: justify;
        }

        .signature-section {
            margin-top: 8px;
        }

        .signature-place-date {
            text-align: right;
            margin: 0 0 6px;
            font-size: 9.5pt;
        }

        .signature-row {
            width: 100%;
            text-align: right;
        }

        .signature-block {
            display: inline-block;
            width: 220px;
            text-align: center;
            page-break-inside: avoid;
        }

        .signature-role {
            margin: 0 0 4px;
            font-size: 9.5pt;
            line-height: 1.25;
            text-align: center;
        }

        .signature-area {
            width: 100%;
            height: 64px;
            text-align: center;
            margin-bottom: 4px;
        }

        .qr-image {
            width: 60px;
            height: 60px;
            object-fit: contain;
        }

        .signature-name {
            margin: 0;
            font-weight: bold;
            font-size: 9.5pt;
            text-decoration: underline;
            line-height: 1.25;
            text-align: center;
        }
    </style>
</head>
<body>
    @include('slip.partials.document', ['slip' => $slip, 'images' => $images])
</body>
</html>
