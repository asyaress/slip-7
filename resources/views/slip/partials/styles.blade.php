{{-- Shared slip document styles --}}
<style>
    @page {
        size: A4;
        margin: 2cm;
    }

    @media print {
        html, body {
            width: 210mm;
            height: 297mm;
            background: white !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        aside,
        header,
        .no-print,
        .flash-alert,
        .page-header-bar {
            display: none !important;
        }

        .flex-1.ml-64 {
            margin-left: 0 !important;
        }

        main {
            padding: 0 !important;
            margin: 0 !important;
        }

        .print-container {
            padding: 0 !important;
            margin: 0 !important;
            max-width: none !important;
        }

        .slip-page {
            box-shadow: none !important;
            border: none !important;
            border-radius: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
            max-width: none !important;
            width: 100% !important;
        }
    }

    .print-container {
        max-width: 210mm;
        margin: 0 auto;
    }

    .slip-page {
        font-family: 'Times New Roman', Times, serif;
        font-size: 11pt;
        line-height: 1.55;
        color: #1a1a1a;
        background: white;
        padding: 2rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,.06);
    }

    .slip-kop {
        width: 100%;
        max-height: 120px;
        object-fit: contain;
        object-position: center top;
        margin-bottom: 16px;
    }

    .slip-page .doc-title {
        text-align: center;
        font-weight: bold;
        font-size: 13pt;
        letter-spacing: 0.5px;
        text-decoration: underline;
        text-underline-offset: 3px;
        margin-bottom: 4px;
        color: #42091a;
    }

    .slip-page .doc-number {
        text-align: center;
        font-size: 10pt;
        color: #444;
        margin-bottom: 22px;
    }

    .slip-page .opening-text {
        text-align: justify;
        margin-bottom: 18px;
    }

    .slip-page table.info {
        width: 100%;
        margin-bottom: 4px;
    }

    .slip-page table.info td {
        padding: 3px 8px 3px 0;
        vertical-align: top;
    }

    .slip-page table.info td.label {
        width: 110px;
        font-weight: 500;
    }

    .slip-page table.info td.colon {
        width: 12px;
    }

    .slip-page .employee-box {
        border: 1px solid #d1d5db;
        background: #fafafa;
        padding: 12px 16px;
        margin: 14px 0 18px;
        border-radius: 4px;
    }

    .slip-page .attendance-box {
        border: 1px solid #781a38;
        border-left: 4px solid #781a38;
        background: #fdf2f4;
        padding: 10px 14px;
        margin: 14px 0 18px;
        font-size: 10pt;
    }

    .slip-page .section-title {
        font-weight: bold;
        font-size: 11pt;
        color: #42091a;
        margin: 18px 0 8px;
        padding-bottom: 4px;
        border-bottom: 1px solid #e5e7eb;
    }

    .slip-page .slip-section {
        page-break-inside: avoid;
        break-inside: avoid-page;
    }

    .slip-page .slip-section table.gaji {
        page-break-inside: avoid;
        break-inside: avoid-page;
    }

    .slip-page table.gaji {
        width: 100%;
        border-collapse: collapse;
        margin-top: 4px;
    }

    .slip-page table.gaji td {
        padding: 5px 8px;
        vertical-align: middle;
        border-bottom: 1px solid #f0f0f0;
    }

    .slip-page table.gaji tr.subtotal td {
        border-top: 1.5px solid #333;
        border-bottom: none;
        font-weight: bold;
        padding-top: 8px;
    }

    .slip-page table.gaji tr.section-header td {
        font-weight: bold;
        background: #f9fafb;
        border-bottom: 1px solid #e5e7eb;
        color: #42091a;
        padding-top: 10px;
    }

    .slip-page .amount {
        text-align: right;
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
    }

    .slip-page .unit-label {
        font-size: 9pt;
        color: #666;
        width: 85px;
    }

    .slip-page .highlight {
        background-color: #fef08a;
        padding: 2px 6px;
        font-weight: bold;
    }

    .slip-page .thp-box {
        background: #fdf2f4;
        border: 1.5px solid #781a38;
        padding: 12px 16px;
        margin: 16px 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: bold;
    }

    .slip-page .thp-box .thp-amount {
        font-size: 12pt;
        color: #42091a;
        background: #fef08a;
        padding: 4px 10px;
    }

    .slip-page .total-pendapatan {
        background: #f3f4f6;
        border: 1px solid #d1d5db;
        padding: 10px 16px;
        margin: 14px 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: bold;
        font-size: 11pt;
    }

    .slip-page .total-pendapatan-info {
        flex: 1;
        padding-right: 12px;
    }

    .slip-page .total-pendapatan-title {
        line-height: 1.35;
    }

    .slip-page .total-pendapatan-breakdown {
        font-size: 9.5pt;
        font-weight: normal;
        color: #444;
        line-height: 1.45;
        margin-top: 3px;
    }

    .slip-page .total-pendapatan-amount {
        font-size: 12pt;
        white-space: nowrap;
    }

    .slip-page .deduction {
        color: #b91c1c;
    }

    .slip-page .closing-text {
        margin-top: 22px;
        text-align: justify;
    }

    .slip-page .signature-section {
        margin-top: 24px;
        clear: both;
    }

    .slip-page .signature-place-date {
        text-align: right;
        margin-bottom: 22px;
        font-size: 10.5pt;
    }

    .slip-page .signature-layout {
        width: 100%;
        table-layout: fixed;
        border-collapse: collapse;
    }

    .slip-page .signature-slot {
        width: 33.333%;
        vertical-align: top;
        padding: 0 12px;
    }

    .slip-page .signature-cell {
        width: 100%;
        max-width: 100%;
        margin: 0 auto;
        text-align: center;
    }

    .slip-page .signature-role {
        margin: 0 0 12px;
        min-height: 68px;
        font-size: 10pt;
        line-height: 1.35;
        text-align: center;
    }

    .slip-page .signature-heading {
        display: block;
        padding: 0;
        margin-bottom: 8px;
        color: #111111;
        font-size: 9.2pt;
        font-weight: 700;
        letter-spacing: 0;
    }

    .slip-page .signature-title {
        color: #111827;
        font-size: 11pt;
        font-weight: 700;
    }

    .slip-page .signature-area {
        width: 100%;
        height: 74px;
        text-align: center;
        margin-bottom: 8px;
    }

    .slip-page .qr-image {
        width: 70px;
        height: 70px;
        object-fit: contain;
        display: inline-block;
    }

    @media print {
        .slip-page .qr-image {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .slip-page .section-title {
            page-break-after: avoid;
        }

        .slip-page .slip-section,
        .slip-page .slip-section table.gaji {
            page-break-inside: avoid;
            break-inside: avoid-page;
        }
    }

    .slip-page .signature-name {
        margin: 0;
        padding-top: 9px;
        border-top: 1px solid #111111;
        color: #111111;
        font-weight: bold;
        font-size: 9.2pt;
        text-decoration: none;
        line-height: 1.4;
        text-align: center;
    }
</style>
