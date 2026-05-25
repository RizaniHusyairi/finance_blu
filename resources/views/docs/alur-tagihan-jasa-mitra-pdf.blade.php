<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>{{ $document['title'] }}</title>
<style>
    @page { margin: 20mm 16mm; }
    body {
        color: #1f2937;
        font-family: DejaVu Sans, sans-serif;
        font-size: 10.5px;
        line-height: 1.45;
    }
    h1 {
        color: #0f172a;
        font-size: 20px;
        line-height: 1.2;
        margin: 0 0 5px 0;
    }
    h2 {
        border-bottom: 1px solid #cbd5e1;
        color: #1e3a8a;
        font-size: 13.5px;
        margin: 18px 0 8px 0;
        padding-bottom: 4px;
    }
    h3 {
        color: #334155;
        font-size: 11.5px;
        margin: 12px 0 6px 0;
    }
    p { margin: 4px 0 8px 0; text-align: justify; }
    ul, ol { margin: 4px 0 10px 18px; padding: 0; }
    li { margin: 2px 0; }
    table { border-collapse: collapse; margin: 6px 0 12px 0; width: 100%; }
    th, td {
        border: 1px solid #cbd5e1;
        padding: 5px 7px;
        text-align: left;
        vertical-align: top;
    }
    th { background: #eff6ff; color: #1e3a8a; font-weight: bold; }
    code {
        background: #f1f5f9;
        border-radius: 3px;
        color: #7c2d12;
        font-family: DejaVu Sans Mono, monospace;
        font-size: 9.5px;
        padding: 1px 4px;
    }
    .meta { color: #64748b; font-size: 9.5px; margin-bottom: 14px; }
    .lead {
        background: #f8fafc;
        border-left: 4px solid #2563eb;
        margin: 8px 0 12px 0;
        padding: 8px 10px;
    }
    .summary {
        background: #ecfdf5;
        border: 1px solid #bbf7d0;
        margin: 8px 0 12px 0;
        padding: 8px 10px;
    }
    .map-wrap {
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        margin: 8px 0 12px 0;
        padding: 8px;
        page-break-inside: avoid;
    }
    .map-table {
        border-collapse: separate;
        border-spacing: 6px;
        margin: 0;
        width: 100%;
    }
    .map-table td {
        border: 0;
        padding: 0;
        vertical-align: top;
        width: 33.333%;
    }
    .map-card {
        background: #ffffff;
        border: 1px solid #bfdbfe;
        border-top: 4px solid #2563eb;
        min-height: 86px;
        padding: 7px 8px;
    }
    .map-head {
        border-bottom: 1px solid #e2e8f0;
        margin-bottom: 5px;
        padding-bottom: 4px;
    }
    .map-no {
        background: #1d4ed8;
        color: #ffffff;
        display: inline-block;
        font-size: 9px;
        font-weight: bold;
        padding: 2px 5px;
    }
    .map-phase {
        color: #0f172a;
        display: inline-block;
        font-size: 11px;
        font-weight: bold;
        margin-left: 5px;
    }
    .map-actor {
        color: #475569;
        font-size: 9.5px;
        margin-bottom: 3px;
    }
    .map-status {
        background: #ecfdf5;
        border: 1px solid #bbf7d0;
        color: #047857;
        display: inline-block;
        font-size: 9px;
        font-weight: bold;
        margin-bottom: 4px;
        padding: 1px 4px;
    }
    .map-detail {
        color: #334155;
        font-size: 9.5px;
        line-height: 1.35;
    }
    .map-bridge {
        background: #e0f2fe;
        border: 1px solid #7dd3fc;
        color: #075985;
        font-size: 9px;
        font-weight: bold;
        margin: 2px 6px 8px 6px;
        padding: 4px 8px;
        text-align: center;
    }
    .badge {
        background: #fef3c7;
        border-radius: 3px;
        color: #92400e;
        display: inline-block;
        font-size: 9px;
        font-weight: bold;
        padding: 1px 5px;
    }
    .note {
        background: #fffbeb;
        border-left: 3px solid #f59e0b;
        font-size: 10px;
        margin: 8px 0 12px 0;
        padding: 7px 10px;
    }
    .page-break { page-break-after: always; }
    .small { color: #64748b; font-size: 9.5px; }
    .footer-note {
        border-top: 1px solid #e2e8f0;
        color: #94a3b8;
        font-size: 9px;
        margin-top: 20px;
        padding-top: 6px;
    }
</style>
</head>
<body>

<h1>{{ $document['title'] }}</h1>
<div class="meta">
    SIKEREN-BLU - Modul Tagihan Jasa ke Mitra - Tanggal cetak: {{ now()->format('d F Y') }}
</div>

<div class="lead">
    <strong>Ringkasan analisis:</strong>
    {{ $document['subtitle'] }}
</div>

<table>
    <tbody>
        <tr>
            <th style="width: 28%;">Sumber awal</th>
            <td>{{ $document['entryPoint'] }}</td>
        </tr>
        <tr>
            <th>Rumus utama</th>
            <td>{{ $document['formula'] }}</td>
        </tr>
        <tr>
            <th>Muara proses</th>
            <td>Semua jenis ini bermuara ke <code>TagihanJasa</code>, workflow <code>TAGIHAN_JASA</code>, publish VA/WhatsApp, sinkron piutang, lalu BKU saat lunas.</td>
        </tr>
    </tbody>
</table>

<h2>1. Peta Alur Ringkas</h2>
<div class="map-wrap">
    @foreach(array_chunk($document['map'], 3) as $rowIndex => $row)
        <table class="map-table">
            <tr>
                @foreach($row as $item)
                    <td>
                        <div class="map-card">
                            <div class="map-head">
                                <span class="map-no">{{ $item['no'] }}</span>
                                <span class="map-phase">{{ $item['phase'] }}</span>
                            </div>
                            <div class="map-actor">{{ $item['actor'] }}</div>
                            <div class="map-status">{{ $item['status'] }}</div>
                            <div class="map-detail">{{ $item['detail'] }}</div>
                        </div>
                    </td>
                @endforeach
            </tr>
        </table>
        @if($rowIndex === 0)
            <div class="map-bridge">Laporan tervalidasi menjadi dasar invoice resmi TagihanJasa</div>
        @endif
    @endforeach
</div>

<h2>2. Tahapan Detail</h2>
<table>
    <thead>
        <tr>
            <th style="width: 5%;">No</th>
            <th style="width: 22%;">Aktor</th>
            <th>Aktivitas</th>
            <th style="width: 24%;">Status / output</th>
        </tr>
    </thead>
    <tbody>
        @foreach($document['flow'] as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item['actor'] }}</td>
                <td>{{ $item['activity'] }}</td>
                <td><span class="badge">{{ $item['status'] }}</span></td>
            </tr>
        @endforeach
    </tbody>
</table>

<h2>3. Workflow TagihanJasa yang Dipakai Setelah Invoice Dibuat</h2>
<p>
    Setelah baris <code>TagihanJasa</code> dibuat, proses verifikasi resmi memakai workflow
    <code>TAGIHAN_JASA</code>. Status tagihan menggambarkan role yang sedang menunggu approval.
</p>
<table>
    <thead>
        <tr>
            <th style="width: 10%;">Step</th>
            <th>Role</th>
            <th style="width: 34%;">Status setelah approve</th>
        </tr>
    </thead>
    <tbody>
        @foreach($commonWorkflow as $step)
            <tr>
                <td>{{ $step['step'] }}</td>
                <td>{{ $step['role'] }}</td>
                <td><code>{{ $step['after'] }}</code></td>
            </tr>
        @endforeach
    </tbody>
</table>
<p>
    Reject di step manapun mengubah status tagihan menjadi <code>DITOLAK</code>. Jika semua step approve,
    Admin Jasa wajib upload surat pengantar final bertanda tangan sebelum publish ke mitra.
</p>

<div class="page-break"></div>

<h2>4. Status Penting</h2>
<table>
    <thead>
        <tr>
            <th style="width: 28%;">Status</th>
            <th>Makna</th>
        </tr>
    </thead>
    <tbody>
        @foreach($document['statuses'] as $status)
            <tr>
                <td><code>{{ $status['name'] }}</code></td>
                <td>{{ $status['meaning'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<h2>5. Catatan Teknis</h2>
<ul>
    @foreach($document['specialNotes'] as $note)
        <li>{{ $note }}</li>
    @endforeach
</ul>

<div class="summary">
    <strong>Alur pembayaran:</strong>
    ketika tagihan dipublish, sistem membuat VA BTN, mengirim WhatsApp ke mitra, dan memanggil
    <code>PiutangSyncService::syncFromPublished()</code>. Saat callback BTN atau mark-as-paid manual
    menyatakan lunas, sistem memanggil <code>syncFromLunas()</code> untuk menandai piutang PAID dan
    mencatat BKU <code>DEBIT_MASUK</code>.
</div>

<h2>6. Sumber Kode yang Dianalisis</h2>
<ul>
    @foreach($document['sources'] as $source)
        <li><code>{{ $source }}</code></li>
    @endforeach
</ul>

<div class="footer-note">
    Dokumen ini dibuat dari analisis kode lokal dan digenerate oleh
    <code>docs:export-alur-tagihan-jasa-mitra</code>.
</div>

</body>
</html>
