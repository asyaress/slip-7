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

    .slip-page .deduction {
        color: #b91c1c;
    }

    .slip-page .closing-text {
        margin-top: 22px;
        text-align: justify;
    }

    .slip-page .signature-section {
        margin-top: 32px;
    }

    .slip-page .signature-place-date {
        text-align: right;
        margin-bottom: 28px;
        font-size: 11pt;
    }

    .slip-page .signature-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 32px;
        align-items: start;
    }

    .slip-page .signature-block {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .slip-page .signature-role {
        margin: 0 0 10px;
        font-size: 11pt;
        min-height: 2.6em;
        display: flex;
        align-items: flex-end;
        justify-content: center;
        line-height: 1.35;
        width: 100%;
    }

    .slip-page .signature-area {
        width: 100%;
        height: 96px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
    }

    .slip-page .signature-block:first-child .signature-area {
        align-items: flex-end;
        padding-bottom: 2px;
    }

    .slip-page .signature-line {
        width: 200px;
        border-bottom: 1px dotted #666;
    }

    .slip-page .qr-image {
        width: 88px;
        height: 88px;
        object-fit: contain;
        display: block;
    }

    @media print {
        .slip-page .qr-image {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }

    .slip-page .signature-name {
        margin: 0;
        font-weight: bold;
        font-size: 11pt;
        text-decoration: underline;
        text-underline-offset: 3px;
        line-height: 1.4;
        min-height: 2.6em;
        display: flex;
        align-items: flex-start;
        justify-content: center;
    }
</style>
