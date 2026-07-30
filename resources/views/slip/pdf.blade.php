<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Slip Gaji - {{ $slip['employee']['name'] }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1.15cm 1.35cm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            background: white;
            font-family: 'DejaVu Serif', serif;
            font-size: 10pt;
            line-height: 1.38;
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
            max-height: 78px;
            object-fit: contain;
            object-position: center top;
            margin-bottom: 8px;
        }

        .doc-title {
            text-align: center;
            font-weight: bold;
            font-size: 12pt;
            letter-spacing: 0.4px;
            text-decoration: underline;
            margin-bottom: 3px;
            color: #42091a;
        }

        .doc-number {
            text-align: center;
            font-size: 10pt;
            color: #444;
            margin-bottom: 10px;
        }

        .opening-text {
            text-align: justify;
            margin: 0 0 10px;
        }

        table.info {
            width: 100%;
            border-collapse: collapse;
        }

        table.info td {
            padding: 2px 8px 2px 0;
            vertical-align: top;
            line-height: 1.35;
        }

        table.info td.label {
            width: 100px;
            font-weight: 500;
            white-space: nowrap;
        }

        table.info td.colon {
            width: 12px;
        }

        .employee-box {
            border: 1px solid #d1d5db;
            background: #fafafa;
            padding: 8px 12px;
            margin: 8px 0 10px;
        }

        .attendance-box {
            border: 1px solid #781a38;
            border-left: 4px solid #781a38;
            background: #fdf2f4;
            padding: 7px 10px;
            margin: 8px 0 10px;
            font-size: 9.5pt;
            line-height: 1.35;
        }

        .section-title {
            font-weight: bold;
            font-size: 10pt;
            color: #42091a;
            margin: 10px 0 4px;
            padding-bottom: 3px;
            border-bottom: 1px solid #e5e7eb;
            page-break-after: avoid;
        }

        .slip-section {
            page-break-inside: avoid;
            break-inside: avoid-page;
        }

        .slip-section table.gaji {
            page-break-inside: avoid;
            break-inside: avoid-page;
        }

        table.gaji {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3px;
        }

        table.gaji td {
            padding: 3px 6px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f0f0;
            font-size: 9.5pt;
            line-height: 1.35;
        }

        table.gaji tr.subtotal td {
            border-top: 1.5px solid #333;
            border-bottom: none;
            font-weight: bold;
            padding-top: 5px;
        }

        table.gaji tr.section-header td {
            font-weight: bold;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            color: #42091a;
            padding-top: 6px;
        }

        .amount {
            text-align: right;
            white-space: nowrap;
            width: 105px;
        }

        .unit-label {
            font-size: 8.5pt;
            color: #666;
            width: 78px;
            white-space: nowrap;
        }

        .highlight {
            background-color: #fef08a;
            padding: 2px 6px;
            font-weight: bold;
        }

        .thp-box,
        .total-pendapatan {
            display: table;
            width: 100%;
            font-weight: bold;
            table-layout: fixed;
        }

        .thp-box {
            background: #fdf2f4;
            border: 1.5px solid #781a38;
            padding: 7px 10px;
            margin: 8px 0;
        }

        .thp-box > span {
            display: table-cell;
            vertical-align: middle;
        }

        .total-pendapatan {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 7px 10px;
            margin: 8px 0;
            font-size: 10pt;
        }

        .total-pendapatan-info {
            display: table-cell;
            vertical-align: middle;
            width: 62%;
            padding-right: 10px;
        }

        .total-pendapatan-title {
            line-height: 1.35;
        }

        .total-pendapatan-breakdown {
            font-size: 9pt;
            font-weight: normal;
            color: #444;
            line-height: 1.4;
            margin-top: 2px;
        }

        .total-pendapatan-amount {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            white-space: nowrap;
            font-size: 11pt;
        }

        .thp-box > span:first-child {
            width: 62%;
            padding-right: 10px;
            line-height: 1.35;
        }

        .thp-box .thp-amount {
            text-align: right;
            white-space: nowrap;
            font-size: 11pt;
            color: #42091a;
            background: #fef08a;
            padding: 3px 8px;
        }

        .deduction {
            color: #b91c1c;
        }

        .closing-text {
            margin: 12px 0 0;
            text-align: justify;
            line-height: 1.4;
        }

        .signature-section {
            margin-top: 16px;
            clear: both;
        }

        .signature-place-date {
            text-align: right;
            margin: 0 0 10px;
            font-size: 10pt;
        }

        .signature-layout {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }

        .signature-layout-bottom {
            margin-top: 18px;
        }

        .signature-slot {
            vertical-align: top;
            padding: 0 12px;
        }

        .signature-layout-top .signature-slot {
            width: 50%;
        }

        .signature-layout-bottom .signature-slot {
            width: 100%;
            padding: 0;
        }

        .signature-cell {
            width: 240px;
            max-width: 100%;
            margin: 0 auto;
            text-align: center;
            page-break-inside: avoid;
        }

        .signature-role {
            margin: 0 0 6px;
            min-height: 34px;
            font-size: 9.2pt;
            font-weight: bold;
            line-height: 1.35;
            text-align: center;
        }

        .signature-area {
            width: 100%;
            height: 58px;
            text-align: center;
            margin-bottom: 6px;
        }

        .qr-image {
            width: 56px;
            height: 56px;
            object-fit: contain;
        }

        .signature-name {
            margin: 0;
            font-weight: bold;
            font-size: 8.3pt;
            text-decoration: underline;
            line-height: 1.35;
            text-align: center;
        }
    </style>
</head>
<body>
    @include('slip.partials.document', ['slip' => $slip, 'images' => $images])
</body>
</html>
