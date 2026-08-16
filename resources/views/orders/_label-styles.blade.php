<style>
    @page { size: 80mm auto; margin: 2mm; }

    * { box-sizing: border-box; }

    body {
        width: 76mm;
        margin: 0 auto;
        font-family: Arial, sans-serif;
        font-size: 12px;
        color: #000;
    }

    .label { padding: 2mm 0; page-break-after: always; }
    .label:last-child { page-break-after: auto; }

    .divider { border-top: 1px dashed #000; margin: 4px 0; }

    .row-line { display: flex; justify-content: space-between; gap: 6px; }

    .fw-bold { font-weight: bold; }

    .text-center { text-align: center; }

    .cod-box {
        border: 1px solid #000;
        text-align: center;
        margin-top: 6px;
        padding: 4px;
    }

    .no-print { display: inline-block; }

    @media print {
        .no-print { display: none !important; }
    }
</style>
