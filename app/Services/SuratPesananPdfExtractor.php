<?php

namespace App\Services;

use Smalot\PdfParser\Parser;

/**
 * Ekstraksi data dari PDF Surat Pesanan e-Purchasing/INAPROC untuk auto-isi
 * form Tagihan Kontrak Eksternal. Bekerja pada PDF digital ber-layer teks
 * (dokumen INAPROC selalu demikian); PDF hasil scan tanpa teks akan
 * mengembalikan array kosong dan form diisi manual.
 *
 * Label yang diandalkan (konsisten pada Katalog Elektronik v6):
 *   No. Surat Pesanan : EP-...
 *   Tanggal Surat Pesanan : 08 Apr 2026, 14:21:58 WIB
 *   Penyedia ... Nama Penanggung Jawab : ... NPWP Penyedia : ... Alamat Penyedia : ...
 *   Pembayaran : 1 Termin
 *   Estimasi Total Pembayaran Rp435.675.000,00
 */
class SuratPesananPdfExtractor
{
    /** Batas halaman yang dibaca — data inti selalu di halaman-halaman awal. */
    private const MAX_PAGES = 4;

    private const MONTHS = [
        'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4,
        'mei' => 5, 'may' => 5, 'jun' => 6, 'jul' => 7,
        'agu' => 8, 'aug' => 8, 'sep' => 9, 'okt' => 10, 'oct' => 10,
        'nov' => 11, 'des' => 12, 'dec' => 12,
    ];

    /**
     * @return array{
     *     nomor_surat_pesanan: ?string,
     *     tanggal_surat_pesanan: ?string,
     *     vendor_nama: ?string,
     *     vendor_penanggung_jawab: ?string,
     *     vendor_npwp: ?string,
     *     vendor_alamat: ?string,
     *     total_bruto: ?float,
     *     total_termin: ?int,
     *     termin_ke: ?int,
     *     nama_pekerjaan_saran: ?string,
     * }
     */
    public function extract(string $rawPdfBytes): array
    {
        $empty = [
            'nomor_surat_pesanan' => null,
            'tanggal_surat_pesanan' => null,
            'vendor_nama' => null,
            'vendor_penanggung_jawab' => null,
            'vendor_npwp' => null,
            'vendor_alamat' => null,
            'total_bruto' => null,
            'total_termin' => null,
            'termin_ke' => null,
            'nama_pekerjaan_saran' => null,
            // Potongan teks daftar produk — bahan ringkasan judul via LLM
            // (internal; tidak dikirim ke respons endpoint).
            'ringkasan_produk' => null,
        ];

        try {
            $document = (new Parser())->parseContent($rawPdfBytes);
            $pages = array_slice($document->getPages(), 0, self::MAX_PAGES);
            $text = '';
            foreach ($pages as $page) {
                $text .= "\n" . $page->getText();
            }
        } catch (\Throwable) {
            return $empty;
        }

        // Normalisasi whitespace: parser sering menyisipkan tab/newline di
        // tengah kalimat — jadikan satu baris panjang agar regex sederhana.
        $flat = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
        if ($flat === '') {
            return $empty;
        }

        $out = $empty;

        // Teks PDF INAPROC kerap menempel tanpa spasi ("...24CSTanggal") —
        // kapture nomor case-sensitive agar tidak ikut menelan kata berikutnya.
        // Berhenti sebelum kata "Tanggal" yang ikut menempel di belakang nomor.
        if (preg_match('/No\.?\s*Surat\s*Pesanan\s*:?\s*#?\s*((?-i)EP-(?:(?!Tanggal)[A-Z0-9])+)/iu', $flat, $m)) {
            $out['nomor_surat_pesanan'] = $m[1];
        }

        if (preg_match('/Tanggal\s*Surat\s*Pesanan\s*:?\s*(\d{1,2})\s+([A-Za-z]{3,9})\s+(\d{4})/iu', $flat, $m)) {
            $month = self::MONTHS[strtolower(substr($m[2], 0, 3))] ?? null;
            if ($month) {
                $out['tanggal_surat_pesanan'] = sprintf('%04d-%02d-%02d', (int) $m[3], $month, (int) $m[1]);
            }
        }

        // Blok Penyedia: nama = teks antara heading "Penyedia" dan label
        // "Nama Penanggung Jawab". Badge kualifikasi (UMKK/UMK/PKP) dibuang.
        if (preg_match('/\bPenyedia\s*(.{3,120}?)\s*Nama\s*Penanggung\s*Jawab/iu', $flat, $m)) {
            // Badge kualifikasi bisa menempel di ujung nama ("...INDONESIAUMKK").
            $nama = trim(preg_replace('/\s*(UMKK|UMK|PKP)\s*$/u', '', trim($m[1])) ?? '');
            $out['vendor_nama'] = $nama !== '' ? $nama : null;
        }

        // Penanggung jawab penyedia = kemunculan label SETELAH "NPWP Penyedia"
        // tidak reliabel; di dokumen, blok Penyedia memuat urutan
        // "Nama Penanggung Jawab : X Jabatan Penanggung Jawab : ... NPWP Penyedia : ...".
        // Blok Pemesan juga memuat label yang sama, jadi ambil kandidat yang
        // paling dekat sebelum "NPWP Penyedia".
        if (preg_match_all('/Nama\s*Penanggung\s*Jawab\s*:?\s*(.{2,80}?)\s*(?:Jabatan|NPWP)/iu', $flat, $mm, PREG_OFFSET_CAPTURE)
            && preg_match('/NPWP\s*Penyedia/iu', $flat, $anchor, PREG_OFFSET_CAPTURE)
        ) {
            $anchorPos = $anchor[0][1];
            $best = null;
            foreach ($mm[1] as $cand) {
                if ($cand[1] < $anchorPos) {
                    $best = $cand[0]; // kandidat terakhir sebelum anchor
                }
            }
            $out['vendor_penanggung_jawab'] = $best !== null ? trim($best) : null;
        }

        if (preg_match('/NPWP\s*Penyedia\s*:?\s*([\d.\-]{10,25})/iu', $flat, $m)) {
            $out['vendor_npwp'] = trim($m[1], '.-');
        }

        if (preg_match('/Alamat\s*Penyedia\s*:?\s*(.{5,300}?)\s*(?:Informasi\s*Pembayaran|Ringkasan\s*Pesanan|Pemesan\b|Halaman\s*\d)/iu', $flat, $m)) {
            $out['vendor_alamat'] = trim($m[1]);
        }

        if (preg_match('/Estimasi\s*Total\s*Pembayaran(?:\s*Termin\s*\d+)?\s*:?\s*Rp\s*([\d.,]+)/iu', $flat, $m)) {
            $out['total_bruto'] = self::parseRupiah($m[1]);
        }

        if (preg_match('/Pembayaran\s*:?\s*(\d{1,2})\s*Termin/iu', $flat, $m)) {
            $out['total_termin'] = (int) $m[1];
            $out['termin_ke'] = 1;
        }

        // Saran nama pekerjaan: nama produk pertama pada Ringkasan Pesanan —
        // baris setelah badge "Barang PDN/Import" pertama di bawah heading.
        // Beberapa nama produk bisa ter-garble oleh font CID pada PDF — ambil
        // kandidat pertama yang tampak wajar (multi-kata & dominan huruf/angka).
        if (preg_match_all('/Barang\s*(?:PDN|Import)?\s*(.{5,150}?)\s*\d+,\d{2}\s*(?:paket|unit|roll|buah|set|lot)/iu', $flat, $mm)) {
            foreach ($mm[1] as $kandidat) {
                $kandidat = trim($kandidat);
                $huruf = preg_match_all('/[A-Za-z0-9 ]/u', $kandidat);
                if (str_contains($kandidat, ' ') && $huruf >= mb_strlen($kandidat) * 0.85) {
                    $out['nama_pekerjaan_saran'] = 'Pengadaan ' . $kandidat;
                    break;
                }
            }
        }

        // Seluruh potongan daftar produk (untuk dirangkum LLM menjadi judul).
        // URL snapshot katalog dibuang — panjang dan tidak informatif.
        if (preg_match('/Ringkasan\s*Pesanan\s*(.{20,}?)\s*(?:Ringkasan\s*Pembayaran|Detail\s*Informasi\s*Pembayaran)/iu', $flat, $m)
            || preg_match('/Ringkasan\s*Pesanan\s*(.{20,}?)\s*Halaman\s*\d/iu', $flat, $m)
        ) {
            $produk = preg_replace('/https?:\/\/\S+(?:\s+\S+){0,8}?(?=Barang|Ringkasan|Halaman|$)/iu', ' ', $m[1]) ?? $m[1];
            $produk = trim(preg_replace('/\s+/u', ' ', $produk) ?? '');
            $out['ringkasan_produk'] = $produk !== '' ? mb_substr($produk, 0, 2000) : null;
        }

        return $out;
    }

    /** Ada minimal satu field inti yang terbaca? */
    public static function hasUsefulData(array $extracted): bool
    {
        return filled($extracted['nomor_surat_pesanan'] ?? null)
            || filled($extracted['vendor_nama'] ?? null)
            || filled($extracted['total_bruto'] ?? null);
    }

    /** "435.675.000,00" / "435,675,000.00" → 435675000.0 */
    private static function parseRupiah(string $raw): ?float
    {
        $raw = trim($raw, " .,");
        if ($raw === '') {
            return null;
        }

        // Buang bagian sen (2 digit setelah pemisah desimal terakhir).
        if (preg_match('/^(.*)[.,](\d{2})$/', $raw, $m) && strlen($m[2]) === 2) {
            $raw = $m[1];
        }

        $digits = preg_replace('/\D+/', '', $raw);

        return $digits !== '' ? (float) $digits : null;
    }
}
