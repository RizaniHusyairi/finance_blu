<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Kompresi PDF saat upload
    |--------------------------------------------------------------------------
    |
    | Mengompres dokumen PDF (mis. file kontrak mitra) memakai Ghostscript saat
    | diunggah. Bila Ghostscript tidak terpasang atau gagal, file ASLI tetap
    | disimpan apa adanya — upload tidak pernah gagal karena kompresi.
    |
    | Lihat App\Support\PdfCompressor.
    */
    'compression' => [
        // Aktif/nonaktifkan kompresi. Saat false, file selalu disimpan apa adanya.
        'enabled' => env('PDF_COMPRESSION_ENABLED', true),

        // Path biner Ghostscript. Kosong = auto-detect (PATH + lokasi instalasi
        // umum Windows/Linux). Isi manual bila biner berada di lokasi non-standar,
        // contoh: "C:\Program Files\gs\gs10.04.0\bin\gswin64c.exe".
        'ghostscript_binary' => env('GHOSTSCRIPT_BINARY'),

        // Preset kualitas Ghostscript (-dPDFSETTINGS):
        //   /screen   72dpi  — terkecil, kualitas paling rendah
        //   /ebook   150dpi  — seimbang (default): teks & tanda tangan tetap tajam
        //   /printer 300dpi  — kualitas cetak
        //   /prepress 300dpi — kualitas tinggi, ukuran terbesar
        'preset' => env('PDF_COMPRESSION_PRESET', '/ebook'),

        // Lewati file yang lebih kecil dari ini (byte) — tidak sepadan dikompres.
        'min_bytes' => (int) env('PDF_COMPRESSION_MIN_BYTES', 51200), // 50 KB

        // Batas waktu (detik) proses Ghostscript agar request tidak menggantung.
        'timeout' => (int) env('PDF_COMPRESSION_TIMEOUT', 60),
    ],
];
