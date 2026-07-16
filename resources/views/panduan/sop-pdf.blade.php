<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SOP SIKEREN - {{ $guide['label'] }}</title>
    <style>
        @page { margin: 24px 52px 74px 52px; }
        body { font-family: Arial, DejaVu Sans, sans-serif; font-size: 10.5px; color: #000; line-height: 1.35; }
        .kop { width: 100%; border-bottom: 3px solid #111; padding-bottom: 2px; margin-bottom: 12px; }
        .kop-table { width: 100%; border-collapse: collapse; }
        .kop-table td { vertical-align: top; padding: 0; }
        .kop-logo { width: 74px; text-align: left; }
        .kop-logo img { width: 64px; height: auto; }
        .kop-text { text-align: center; padding-top: 2px; }
        .kop h1, .kop h2, .kop h3 { margin: 0; text-transform: uppercase; font-weight: bold; line-height: 1.04; }
        .kop h1 { font-size: 13px; }
        .kop h2 { font-size: 13px; }
        .kop h3 { font-size: 11px; }
        .kop-contact { width: 86%; margin: 2px auto 0; border-collapse: collapse; font-size: 6.8px; line-height: 1.05; }
        .kop-contact td { padding: 0 5px; vertical-align: top; }
        .kop-contact td + td { border-left: 1px solid #111; }
        .doc-title { text-align: center; margin: 14px 0 4px; font-size: 13px; font-weight: bold; text-transform: uppercase; }
        .doc-subtitle { text-align: center; margin: 0 0 14px; font-size: 11.5px; font-weight: bold; }
        .identitas { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .identitas td { border: 1px solid #111; padding: 4px 7px; vertical-align: top; }
        .identitas .label { width: 32%; background: #f1f1f1; font-weight: bold; }
        h4.section { font-size: 11.5px; margin: 14px 0 5px; text-transform: uppercase; border-bottom: 1.5px solid rgb({{ $guide['warna'] }}); padding-bottom: 2px; }
        p { margin: 0 0 8px; text-align: justify; }
        ul.scope { margin: 0 0 8px 16px; padding: 0; }
        ul.scope li { margin-bottom: 2px; }
        table.prosedur, table.menu-tbl, table.faq-tbl { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.prosedur th, table.prosedur td,
        table.menu-tbl th, table.menu-tbl td,
        table.faq-tbl th, table.faq-tbl td { border: 1px solid #111; padding: 4px 6px; vertical-align: top; font-size: 9.5px; line-height: 1.3; }
        table.prosedur th, table.menu-tbl th, table.faq-tbl th { background: rgb({{ $guide['warna'] }}); color: #fff; font-weight: bold; text-align: center; }
        table.prosedur tr, table.menu-tbl tr, table.faq-tbl tr { page-break-inside: avoid; }
        .step-no { text-align: center; font-weight: bold; width: 26px; }
        .rincian { margin: 3px 0 0 12px; padding: 0; }
        .rincian li { margin-bottom: 1px; }
        .catatan { font-style: italic; }
        .doc-footer { position: fixed; left: 0; right: 0; bottom: -52px; height: 46px; border-top: 0.8px solid #999; padding-top: 3px; font-size: 7.5px; color: #555; }
        .doc-footer .pagenum:before { content: counter(page); }
        .footer-tbl { width: 100%; border-collapse: collapse; }
        .footer-tbl td { padding: 0; vertical-align: top; }
    </style>
</head>
<body>
@php
    $kemenhubLogo = str_replace('\\', '/', public_path('logo/Logo_Kementerian_Perhubungan_Indonesia_(Kemenhub).png'));
    $bluLogo = str_replace('\\', '/', public_path('logo/Logo-BLU-Speed.png'));
    $tanggalCetak = now()->translatedFormat('d F Y');
@endphp

    <div class="doc-footer">
        <table class="footer-tbl">
            <tr>
                <td>Dokumen dihasilkan otomatis dari Pusat Panduan SIKEREN, {{ $tanggalCetak }}.</td>
                <td style="text-align: right;">Halaman <span class="pagenum"></span></td>
            </tr>
        </table>
    </div>

    <div class="kop">
        <table class="kop-table">
            <tr>
                <td class="kop-logo">
                    @if(is_file($kemenhubLogo))
                        <img src="{{ $kemenhubLogo }}" alt="Kemenhub">
                    @endif
                </td>
                <td class="kop-text">
                    <h1>Kementerian Perhubungan</h1>
                    <h2>Direktorat Jenderal Perhubungan Udara</h2>
                    <h2>Badan Layanan Umum</h2>
                    <h3>Kantor Unit Penyelenggara Bandar Udara Kelas I</h3>
                    <h3>Aji Pangeran Tumenggung Pranoto - Samarinda</h3>
                    <table class="kop-contact">
                        <tr>
                            <td width="46%">Jl. Poros Samarinda - Bontang, Kel. Sungai Siring, Samarinda - Kalimantan Timur</td>
                            <td width="18%">TELP. (0541) 2831593</td>
                            <td width="18%">FAX : (0541) 743786</td>
                            <td width="18%">EMAIL : mail.aptpranotoairport@gmail.com</td>
                        </tr>
                    </table>
                </td>
                <td class="kop-logo" style="text-align: right;">
                    @if(is_file($bluLogo))
                        <img src="{{ $bluLogo }}" alt="BLU">
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="doc-title">Standar Operasional Prosedur (SOP)</div>
    <div class="doc-subtitle">Penggunaan Aplikasi SIKEREN &mdash; {{ $guide['label'] }}</div>

    <table class="identitas">
        <tr>
            <td class="label">Nomor SOP</td>
            <td>....... / SOP / UPBU.APT / {{ now()->year }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Pembuatan</td>
            <td>.......</td>
        </tr>
        <tr>
            <td class="label">Tanggal Revisi</td>
            <td>.......</td>
        </tr>
        <tr>
            <td class="label">Tanggal Efektif</td>
            <td>.......</td>
        </tr>
        <tr>
            <td class="label">Disahkan oleh</td>
            <td>Kuasa Pengguna Anggaran (KPA)</td>
        </tr>
        <tr>
            <td class="label">Nama SOP</td>
            <td>SOP Penggunaan Aplikasi SIKEREN untuk peran {{ $guide['label'] }}</td>
        </tr>
        <tr>
            <td class="label">Pelaksana</td>
            <td>Pemegang peran {{ $guide['label'] }} pada aplikasi SIKEREN</td>
        </tr>
    </table>

    <h4 class="section">1. Tujuan</h4>
    <p>
        SOP ini menjadi pedoman baku bagi pelaksana dalam menggunakan aplikasi SIKEREN
        (Sistem Informasi Keuangan Terintegrasi) sesuai peran {{ $guide['label'] }}, agar
        pelaksanaan tugas berlangsung tertib, terdokumentasi, dan dapat diaudit.
    </p>
    <p>{{ $guide['ringkasan'] }}</p>

    <h4 class="section">2. Ruang Lingkup</h4>
    <p>SOP ini mencakup penggunaan menu-menu aplikasi SIKEREN berikut oleh pelaksana:</p>
    <ul class="scope">
        @foreach($guide['menus'] as $grup)
            <li><strong>{{ $grup['grup'] }}</strong>: {{ collect($grup['items'])->pluck('nama')->join(', ') }}</li>
        @endforeach
    </ul>

    <h4 class="section">3. Prosedur</h4>
    <table class="prosedur">
        <tr>
            <th class="step-no">No</th>
            <th width="18%">Aktivitas</th>
            <th>Uraian</th>
            <th width="17%">Menu Aplikasi</th>
            <th width="20%">Catatan</th>
        </tr>
        @foreach($guide['alur'] as $i => $langkah)
            <tr>
                <td class="step-no">{{ $i + 1 }}</td>
                <td><strong>{{ $langkah['judul'] }}</strong></td>
                <td>
                    {{ $langkah['detail'] }}
                    @if(!empty($langkah['rincian']))
                        <ul class="rincian">
                            @foreach($langkah['rincian'] as $r)
                                <li>{{ $r }}</li>
                            @endforeach
                        </ul>
                    @endif
                </td>
                <td>{{ $langkah['menu'] }}</td>
                <td class="catatan">{{ $langkah['tips'] ?? '' }}</td>
            </tr>
        @endforeach
    </table>

    <h4 class="section">4. Fungsi Menu</h4>
    <table class="menu-tbl">
        <tr>
            <th width="22%">Grup</th>
            <th width="30%">Menu</th>
            <th>Kegunaan</th>
        </tr>
        @foreach($guide['menus'] as $grup)
            @foreach($grup['items'] as $item)
                <tr>
                    <td>{{ $grup['grup'] }}</td>
                    <td>{{ $item['nama'] }}</td>
                    <td>{{ $item['guna'] }}</td>
                </tr>
            @endforeach
        @endforeach
    </table>

    @if(!empty($guide['faq']))
        <h4 class="section">Lampiran: Tanya-Jawab</h4>
        <table class="faq-tbl">
            <tr>
                <th width="38%">Pertanyaan</th>
                <th>Jawaban</th>
            </tr>
            @foreach($guide['faq'] as $faq)
                <tr>
                    <td><strong>{{ $faq['t'] }}</strong></td>
                    <td>{{ $faq['j'] }}</td>
                </tr>
            @endforeach
        </table>
    @endif
</body>
</html>
