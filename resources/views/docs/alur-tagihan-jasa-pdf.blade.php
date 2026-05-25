<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Alur Proses Tagihan Jasa ke Mitra</title>
<style>
    @page { margin: 18mm 16mm 22mm 16mm; }
    @page :first { margin: 0; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #1f2937; line-height: 1.5; }

    /* ── Cover Page ── */
    .cover {
        height: 297mm; width: 210mm;
        background: #1e3a8a;
        color: #ffffff;
        padding: 60mm 22mm 20mm 22mm;
        position: relative;
        box-sizing: border-box;
        page-break-after: always;
    }
    .cover-accent {
        position: absolute; left: 0; top: 0; width: 12mm; height: 297mm;
        background: #f59e0b;
    }
    .cover-tag {
        font-size: 11px; letter-spacing: 4px; text-transform: uppercase;
        color: #fbbf24; margin-bottom: 12mm;
        border-bottom: 1px solid #475569; padding-bottom: 6mm;
        display: inline-block; padding-right: 20mm;
    }
    .cover-title { font-size: 38px; line-height: 1.15; font-weight: bold; margin: 0 0 8mm 0; color:#fff; }
    .cover-subtitle { font-size: 14px; color: #cbd5e1; margin-bottom: 28mm; line-height: 1.5; }
    .cover-info {
        position: absolute; bottom: 20mm; left: 22mm; right: 22mm;
        border-top: 1px solid #475569; padding-top: 8mm;
        font-size: 11px; color: #cbd5e1;
    }
    .cover-info-grid { width: 100%; }
    .cover-info-grid td { padding: 3px 0; font-size: 10px; }
    .cover-info-grid .label { color: #94a3b8; width: 30mm; }
    .cover-info-grid .val { color: #f8fafc; font-weight: bold; }

    /* ── Content ── */
    h1 { font-size: 20px; margin: 0 0 4px 0; color: #0f172a; }
    h2 {
        font-size: 15px; margin: 20px 0 10px 0; color: #ffffff;
        background: #1e3a8a; padding: 6px 12px; border-radius: 4px;
        border-left: 5px solid #f59e0b;
    }
    h3 { font-size: 12px; margin: 14px 0 6px 0; color: #1e3a8a; font-weight: bold; }
    p { margin: 4px 0 8px 0; text-align: justify; }
    code {
        background: #f1f5f9; padding: 1px 6px; border-radius: 3px;
        font-family: DejaVu Sans Mono, monospace; font-size: 9.5px; color: #0f172a;
        border: 1px solid #e2e8f0;
    }

    /* ── Tables ── */
    table.t { width: 100%; border-collapse: collapse; margin: 6px 0 12px 0; font-size: 10px; }
    table.t th { background: #1e3a8a; color: #fff; padding: 7px 9px; text-align: left; font-size: 10px; }
    table.t td { border-bottom: 1px solid #e2e8f0; padding: 6px 9px; vertical-align: top; }
    table.t tr:nth-child(even) td { background: #f8fafc; }

    /* ── Badges ── */
    .badge {
        display: inline-block; padding: 2px 7px; border-radius: 10px;
        font-size: 9px; font-weight: bold; letter-spacing: 0.3px;
    }
    .b-verif  { background: #fef3c7; color: #92400e; border: 1px solid #f59e0b; }
    .b-done   { background: #d1fae5; color: #065f46; border: 1px solid #10b981; }
    .b-reject { background: #fee2e2; color: #991b1b; border: 1px solid #ef4444; }
    .b-pub    { background: #dbeafe; color: #1e40af; border: 1px solid #3b82f6; }
    .b-lunas  { background: #c7d2fe; color: #3730a3; border: 1px solid #6366f1; }

    /* ── Diagram blocks ── */
    .diagram {
        text-align: center; margin: 10px 0 14px 0;
        page-break-inside: avoid;
        background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px; border-radius: 6px;
    }
    .diagram img { max-width: 100%; max-height: 215mm; }
    .caption {
        font-size: 9.5px; color: #64748b; font-style: italic;
        margin-top: 6px; padding-top: 6px; border-top: 1px dashed #cbd5e1;
    }

    /* ── Lists ── */
    ul, ol { margin: 4px 0 10px 16px; padding: 0; }
    li { margin: 3px 0; }

    /* ── Info cards / notes ── */
    .note {
        background: #fffbeb; border-left: 4px solid #f59e0b;
        padding: 9px 12px; margin: 10px 0; font-size: 10px;
        border-radius: 0 4px 4px 0;
    }
    .note-title { font-weight: bold; color: #92400e; margin-bottom: 3px; }

    .info-card {
        background: #eff6ff; border-left: 4px solid #1e40af;
        padding: 9px 12px; margin: 10px 0; font-size: 10px;
        border-radius: 0 4px 4px 0;
    }
    .info-card-title { font-weight: bold; color: #1e3a8a; margin-bottom: 3px; }

    .actor-card {
        background: #f5f3ff; border-left: 4px solid #6d28d9;
        padding: 9px 12px; margin: 10px 0; font-size: 10px;
        border-radius: 0 4px 4px 0;
    }
    .actor-card-title { font-weight: bold; color: #4c1d95; margin-bottom: 3px; }

    /* ── Zone label / pill ── */
    .zone-pill {
        display: inline-block; padding: 3px 10px; border-radius: 12px;
        font-size: 10px; font-weight: bold; margin: 4px 0;
    }
    .zone-aj    { background: #dbeafe; color: #1e40af; }
    .zone-verif { background: #fef3c7; color: #92400e; }
    .zone-fin   { background: #d1fae5; color: #065f46; }
    .zone-bp    { background: #ede9fe; color: #4c1d95; }

    /* ── Step grid (for Bendahara section) ── */
    .step-row { width: 100%; border-collapse: collapse; margin: 8px 0; }
    .step-row td { padding: 8px 10px; vertical-align: top; border-bottom: 1px solid #e9d5ff; }
    .step-num {
        width: 36px; background: #6d28d9; color: #fff;
        text-align: center; font-weight: bold; font-size: 14px; border-radius: 50%;
    }

    .page-break { page-break-after: always; }

    /* ── Footer note ── */
    .footer-note {
        font-size: 9px; color: #64748b; margin-top: 22px;
        border-top: 2px solid #e2e8f0; padding-top: 8px;
        background: #f8fafc; padding: 8px 10px; border-radius: 4px;
    }

    /* ── TOC ── */
    .toc { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 14px 18px; }
    .toc-title { font-weight: bold; color: #1e3a8a; font-size: 13px; margin-bottom: 8px;
                 border-bottom: 2px solid #f59e0b; padding-bottom: 4px; display: inline-block; }
    .toc-item { padding: 4px 0; font-size: 10.5px; color: #334155; border-bottom: 1px dotted #cbd5e1; }
    .toc-item:last-child { border-bottom: none; }
    .toc-num { color: #1e40af; font-weight: bold; display: inline-block; width: 22px; }
</style>
</head>
<body>

{{-- ─────────── COVER PAGE ─────────── --}}
<div class="cover">
    <div class="cover-accent"></div>

    <div class="cover-tag">Dokumentasi Alur Bisnis</div>

    <div class="cover-title">
        Alur Proses<br/>
        Tagihan Jasa<br/>
        ke Mitra
    </div>

    <div class="cover-subtitle">
        Modul Tagihan Jasa (PNBP) &mdash; SIKEREN-BLU<br/>
        Pencatatan Piutang &amp; Buku Kas Umum oleh Bendahara Penerimaan
    </div>

    <div class="cover-info">
        <table class="cover-info-grid">
            <tr>
                <td class="label">Sistem</td>
                <td class="val">SIKEREN-BLU</td>
                <td class="label">Tanggal cetak</td>
                <td class="val">{{ now()->format('d F Y') }}</td>
            </tr>
            <tr>
                <td class="label">Modul</td>
                <td class="val">Tagihan Jasa (PNBP)</td>
                <td class="label">Branch</td>
                <td class="val">rombak_DB</td>
            </tr>
            <tr>
                <td class="label">Versi</td>
                <td class="val">1.1</td>
                <td class="label">Halaman</td>
                <td class="val">7 halaman</td>
            </tr>
        </table>
    </div>
</div>

{{-- ─────────── HALAMAN 2 — RINGKASAN + TOC ─────────── --}}
<h1>Ringkasan Dokumen</h1>
<p>
    Dokumen ini menggambarkan alur lengkap proses <strong>Tagihan Jasa</strong> kepada Mitra mulai dari
    pembuatan oleh Admin Jasa, verifikasi berjenjang oleh empat pejabat, publikasi ke Mitra, sampai
    pencatatan piutang dan Buku Kas Umum (BKU) saat pembayaran diterima. Versi ini menambahkan
    penegasan bahwa <strong>seluruh aktivitas setelah tagihan dipublish menjadi ranah Bendahara
    Penerimaan</strong>, baik untuk memantau piutang yang belum lunas maupun mencatat BKU saat
    pembayaran masuk.
</p>

<div class="toc">
    <div class="toc-title">Daftar Isi</div>
    <div class="toc-item"><span class="toc-num">1.</span> Daftar Status dan Aktor</div>
    <div class="toc-item"><span class="toc-num">2.</span> Pembagian Ranah Tanggung Jawab</div>
    <div class="toc-item"><span class="toc-num">3.</span> Lifecycle Status &mdash; State Diagram</div>
    <div class="toc-item"><span class="toc-num">4.</span> Alur Workflow Verifikasi &mdash; Flowchart</div>
    <div class="toc-item"><span class="toc-num">5.</span> Alur End-to-End ke Mitra &mdash; Sequence Diagram</div>
    <div class="toc-item"><span class="toc-num">6.</span> Penjelasan Tiap Tahap</div>
    <div class="toc-item"><span class="toc-num">7.</span> Aktivitas Bendahara Penerimaan</div>
</div>

<h2>1. Daftar Status dan Aktor</h2>

<h3>1.1 Status pada kolom <code>status</code> tabel <code>tagihan_jasas</code></h3>
<table class="t">
    <thead>
        <tr><th style="width: 28%;">Status</th><th>Arti</th></tr>
    </thead>
    <tbody>
        <tr><td><span class="badge b-verif">VERIFIKASI_KOORDINATOR</span></td><td>Tagihan baru dibuat, menunggu approve Koordinator Jasa.</td></tr>
        <tr><td><span class="badge b-verif">VERIFIKASI_KASI_JASA</span></td><td>Sudah lolos Koordinator, menunggu Kepala Seksi Pelayanan dan Kerjasama.</td></tr>
        <tr><td><span class="badge b-verif">VERIFIKASI_KASUBAG_TU</span></td><td>Menunggu Kepala Subbagian Keuangan dan Tata Usaha.</td></tr>
        <tr><td><span class="badge b-verif">VERIFIKASI_KABANDARA</span></td><td>Menunggu persetujuan dan tanda tangan KPA / Kabandara.</td></tr>
        <tr><td><span class="badge b-done">DISETUJUI</span></td><td>Seluruh verifikasi selesai. Workflow status APPROVED.</td></tr>
        <tr><td><span class="badge b-reject">DITOLAK</span></td><td>Salah satu verifikator menolak. Alur berhenti.</td></tr>
        <tr><td><span class="badge b-pub">PUBLISHED</span></td><td>Tagihan di-publish ke Mitra; VA dibuat, Piutang tercatat, notifikasi WA terkirim. <strong>Mulai masuk ranah Bendahara Penerimaan.</strong></td></tr>
        <tr><td><span class="badge b-lunas">LUNAS</span></td><td>Pembayaran diterima; Piutang ditandai PAID dan BKU mencatat DEBIT_MASUK.</td></tr>
    </tbody>
</table>

<div class="page-break"></div>

<h2>2. Pembagian Ranah Tanggung Jawab</h2>
<p>
    Untuk memudahkan pembacaan diagram, alur dibagi menjadi empat ranah berdasarkan aktor yang
    bertanggung jawab. Pembagian ini konsisten dipakai pada State Diagram, Flowchart, dan Sequence
    Diagram di bagian berikutnya.
</p>

<div class="info-card">
    <div class="info-card-title"><span class="zone-pill zone-aj">🏢 Ranah Admin Jasa</span></div>
    Membuat tagihan, mengisi detail layanan, mengunggah surat pengantar final bertanda tangan, dan
    melakukan publish ke Mitra (juga dapat melakukan <em>mark-as-paid</em> manual jika pembayaran
    masuk di luar VA).
</div>

<div class="info-card" style="background:#fefce8; border-left-color:#b45309;">
    <div class="info-card-title" style="color:#92400e;"><span class="zone-pill zone-verif">✔ Ranah Verifikator</span></div>
    Verifikasi berjenjang 4 step: Koordinator Jasa &rarr; Kepala Seksi Pelayanan dan Kerjasama
    &rarr; Kepala Subbagian Keuangan dan TU &rarr; KPA / Kabandara. Setiap verifikator dapat
    <em>approve</em> atau <em>reject</em>.
</div>

<div class="info-card" style="background:#ecfdf5; border-left-color:#047857;">
    <div class="info-card-title" style="color:#065f46;"><span class="zone-pill zone-fin">📄 Finalisasi Dokumen (Admin Jasa)</span></div>
    Setelah <code>DISETUJUI</code>, Admin Jasa mengunggah surat pengantar bertanda tangan basah
    (PDF) dan melakukan publish &mdash; tahap ini menjembatani verifikasi internal dan distribusi
    tagihan ke Mitra.
</div>

<div class="actor-card">
    <div class="actor-card-title"><span class="zone-pill zone-bp">💰 Ranah Bendahara Penerimaan</span></div>
    Sejak tagihan <code>PUBLISHED</code>, baris piutang otomatis muncul di menu Piutang Bendahara
    Penerimaan. Bendahara Penerimaan memantau status pembayaran, dan saat tagihan <code>LUNAS</code>
    sistem otomatis mencatat baris <code>buku_kas_umum</code> bertipe <code>DEBIT_MASUK</code>
    sebagai realisasi penerimaan kas BLU.
</div>

<div class="page-break"></div>

<h2>3. Lifecycle Status &mdash; State Diagram</h2>
<p>
    Diagram berikut menampilkan seluruh transisi nilai kolom <code>status</code>. Status berwarna
    ungu (<code>PUBLISHED</code>) dan biru (<code>LUNAS</code>) berada dalam zona Bendahara Penerimaan.
</p>
<div class="diagram">
    <img src="{{ public_path('../docs/alur-tagihan-jasa/01-lifecycle.png') }}" alt="Lifecycle status tagihan jasa">
    <div class="caption">Gambar 1. Lifecycle status Tagihan Jasa &mdash; transisi ranah dari Admin Jasa ke Bendahara Penerimaan.</div>
</div>

<div class="page-break"></div>

<h2>4. Alur Workflow Verifikasi &mdash; Flowchart</h2>
<p>
    Flowchart berikut menampilkan empat ranah secara eksplisit. Perhatikan bahwa <em>swimlane</em>
    di kanan bawah (warna ungu) adalah ranah <strong>Bendahara Penerimaan</strong>, dengan node
    <code>transaksi_penerimaan</code> dan <code>buku_kas_umum</code> sebagai output utama.
</p>
<div class="diagram">
    <img src="{{ public_path('../docs/alur-tagihan-jasa/02-workflow-verifikasi.png') }}" alt="Workflow verifikasi tagihan jasa">
    <div class="caption">Gambar 2. Workflow 4 step verifikasi &mdash; pencatatan Piutang &amp; BKU berada di ranah Bendahara Penerimaan.</div>
</div>

<div class="page-break"></div>

<h2>5. Alur End-to-End ke Mitra &mdash; Sequence Diagram</h2>
<p>
    Sequence diagram dibagi menjadi empat blok warna sesuai ranah aktor. Blok ungu di bagian
    bawah adalah <strong>tahap pembayaran dan pencatatan oleh Bendahara Penerimaan</strong>, yang
    mencakup baik jalur otomatis (callback BTN VA) maupun manual (<em>mark-as-paid</em> oleh Admin
    Jasa untuk pembayaran offline).
</p>
<div class="diagram">
    <img src="{{ public_path('../docs/alur-tagihan-jasa/03-sequence-end-to-end.png') }}" alt="Sequence end-to-end tagihan jasa">
    <div class="caption">Gambar 3. Sequence diagram end-to-end &mdash; pembayaran &amp; pencatatan ditangani Bendahara Penerimaan.</div>
</div>

<div class="page-break"></div>

<h2>6. Penjelasan Tiap Tahap</h2>

<h3>6.1 Pembuatan Tagihan (Admin Jasa)</h3>
<ul>
    <li>Endpoint: <code>POST /tagihan-jasa</code> &mdash; <code>TagihanJasaController@store</code>.</li>
    <li>Sistem membuat baris <code>TagihanJasa</code> dengan <code>status = VERIFIKASI_KOORDINATOR</code>, lalu insert detail layanan ke <code>tagihan_jasa_details</code>.</li>
    <li>Workflow di-start via <code>WorkflowService::startWorkflow('TAGIHAN_JASA')</code>: terbentuk <code>workflow_instance</code> dengan status <code>IN_PROGRESS</code> dan 4 baris <code>workflow_approval</code>.</li>
    <li>Bila tagihan berasal dari Penjualan Konsesi atau Laporan Utilitas, baris sumber diberi tanda <code>ditagihkan</code>.</li>
</ul>

<h3>6.2 Verifikasi Berjenjang</h3>
<p>Setiap step dijalankan oleh <code>TagihanJasaVerifikasiController</code>:</p>
<table class="t">
    <thead>
        <tr><th style="width: 40px;">Step</th><th>Role</th><th>Status setelah approve</th></tr>
    </thead>
    <tbody>
        <tr><td>1</td><td>Koordinator Jasa</td><td><span class="badge b-verif">VERIFIKASI_KASI_JASA</span></td></tr>
        <tr><td>2</td><td>Kepala Seksi Pelayanan dan Kerjasama</td><td><span class="badge b-verif">VERIFIKASI_KASUBAG_TU</span></td></tr>
        <tr><td>3</td><td>Kepala Subbagian Keuangan dan TU</td><td><span class="badge b-verif">VERIFIKASI_KABANDARA</span></td></tr>
        <tr><td>4</td><td>KPA / Kabandara</td><td><span class="badge b-done">DISETUJUI</span></td></tr>
    </tbody>
</table>
<p>Jika di step manapun verifikator memilih <em>reject</em>, status tagihan menjadi <code>DITOLAK</code> dan alur berhenti.</p>

<h3>6.3 Upload Surat Pengantar Final TTD (Admin Jasa)</h3>
<ul>
    <li>Setelah <code>DISETUJUI</code>, Admin Jasa mengunggah PDF surat pengantar bertanda tangan.</li>
    <li>Kolom yang diubah: <code>file_surat_pengantar_final</code>, <code>status_dokumen_pengantar = SUDAH_DITANDATANGANI</code>.</li>
    <li>Publish ke Mitra terblokir jika file ini belum ada.</li>
</ul>

<h3>6.4 Publish ke Mitra (Admin Jasa &rarr; Bendahara Penerimaan)</h3>
<ul>
    <li>Endpoint: <code>publish</code> di <code>TagihanJasaController</code>.</li>
    <li>Sistem memanggil <code>BtnVirtualAccountService::createVirtualAccount()</code> untuk membuat nomor VA (mode mock atau real).</li>
    <li>Update tagihan: <code>status = PUBLISHED</code>, <code>nomor_va</code>, <code>tanggal_publish</code>, <code>tanggal_jatuh_tempo</code>, <code>sisa_tagihan</code>.</li>
    <li><strong>Side-effect ke ranah Bendahara Penerimaan:</strong> <code>PiutangSyncService::syncFromPublished()</code> membuat baris di <code>transaksi_penerimaan</code> dengan <code>status_pembayaran = UNPAID</code>.</li>
    <li>Notifikasi WhatsApp ke Mitra berisi link signed URL ke halaman publik tagihan dan nomor VA.</li>
</ul>

<div class="page-break"></div>

<h2>7. Aktivitas Bendahara Penerimaan</h2>

<p>
    Bagian ini merangkum tanggung jawab Bendahara Penerimaan terhadap Tagihan Jasa. Semua aktivitas
    di bawah ini ter-trigger otomatis oleh sistem setelah tagihan <code>PUBLISHED</code>, namun
    Bendahara Penerimaan tetap perlu melakukan pemantauan dan rekonsiliasi.
</p>

<table class="step-row">
    <tr>
        <td class="step-num">1</td>
        <td>
            <strong>Pemantauan Piutang Tagihan</strong><br/>
            Saat tagihan dipublish, baris piutang otomatis muncul di menu Piutang
            (<code>PengecekanPembayaranPiutangController</code>). Bendahara Penerimaan memantau
            tagihan yang mendekati atau melewati jatuh tempo.
        </td>
    </tr>
    <tr>
        <td class="step-num">2</td>
        <td>
            <strong>Penerimaan Pembayaran Otomatis (BTN VA Callback)</strong><br/>
            Saat Mitra membayar via VA BTN, callback masuk ke
            <code>BtnVirtualAccountService::handlePaymentCallback()</code>. Sistem:
            <ul>
                <li>Insert <code>PaymentTransaction</code> dan, jika nominal cukup, update tagihan menjadi <code>LUNAS</code>.</li>
                <li>Panggil <code>PiutangSyncService::syncFromLunas()</code> &mdash; piutang menjadi <code>PAID</code> dan BKU baru ber-arus <code>DEBIT_MASUK</code> tercipta.</li>
                <li>Kirim WA konfirmasi lunas ke Mitra.</li>
            </ul>
        </td>
    </tr>
    <tr>
        <td class="step-num">3</td>
        <td>
            <strong>Penerimaan Pembayaran Manual (markAsPaid)</strong><br/>
            Bila Mitra membayar di luar VA (transfer manual, tunai, dll.), Admin Jasa menjalankan
            <code>markAsPaid</code>. Side-effect ke <code>transaksi_penerimaan</code> dan
            <code>buku_kas_umum</code> identik dengan jalur otomatis. Bendahara Penerimaan tetap
            memverifikasi pencatatan tersebut.
        </td>
    </tr>
    <tr>
        <td class="step-num">4</td>
        <td>
            <strong>Pencatatan Buku Kas Umum (BKU)</strong><br/>
            Setiap pembayaran lunas menghasilkan satu baris BKU dengan:
            <ul>
                <li><code>arus_kas = DEBIT_MASUK</code></li>
                <li><code>nominal</code> = jumlah dibayar</li>
                <li><code>saldo_akhir</code> = saldo terakhir + nominal</li>
                <li><code>referensi_penerimaan_id</code> = id piutang terkait</li>
            </ul>
            Service idempoten: pengecekan duplikat dilakukan sebelum insert sehingga aman jika
            dipanggil ulang.
        </td>
    </tr>
    <tr>
        <td class="step-num">5</td>
        <td>
            <strong>Rekonsiliasi &amp; Buku Pembantu Bank</strong><br/>
            BKU yang tercipta juga menjadi sumber data Buku Pembantu Bank dan rekonsiliasi mutasi
            bank. Bendahara Penerimaan mencocokkan transaksi BKU dengan mutasi rekening BLU.
        </td>
    </tr>
</table>

<div class="note">
    <div class="note-title">⚠ Catatan teknis &mdash; perlu perbaikan</div>
    Saat ini <code>PiutangSyncService::resolveCoaId()</code> mereferensi kolom <code>kode_akun</code>
    dan <code>jenis_belanja</code> yang tidak ada di tabel <code>master_coas</code> (kolom yang ada
    adalah <code>kd_akun</code> dan <code>jenis_akun</code>). Karena seluruh sync dibungkus
    <code>try/catch</code> dengan hanya menulis log, kegagalan tidak terlihat dari UI &mdash;
    tagihan tetap tercatat sebagai <code>PUBLISHED</code>/<code>LUNAS</code> namun baris Piutang
    atau BKU bisa tidak terbentuk. Perbaikan direkomendasikan sebelum dipakai produksi.
</div>

<div class="footer-note">
    <strong>Sumber dokumentasi:</strong> Di-generate otomatis dari analisis kode pada cabang
    <code>rombak_DB</code>. Sumber utama:
    <code>TagihanJasaController</code>, <code>TagihanJasaVerifikasiController</code>,
    <code>WorkflowTagihanJasaSeeder</code>, <code>PiutangSyncService</code>,
    <code>BtnVirtualAccountService</code>, <code>PengecekanPembayaranPiutangController</code>.
    Tanggal generate: {{ now()->format('d F Y H:i') }}.
</div>

</body>
</html>
