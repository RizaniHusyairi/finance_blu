<?php

namespace App\Support\Panduan;

/**
 * Sumber konten Pusat Panduan (panduan penggunaan aplikasi) per peran.
 *
 * Konten disimpan di kode (file-based, ikut version-control) — bukan database —
 * sesuai keputusan prototipe. Menambah panduan peran baru cukup menyalin satu
 * blok pada array di bawah; kunci array WAJIB sama persis dengan nama role di
 * Spatie Permission (lihat $internalRoles pada routes/web.php).
 *
 * Struktur tiap entri:
 *  - 'label'     : judul tampil (boleh beda dari nama role teknis)
 *  - 'ikon'      : nama material-icons-outlined
 *  - 'warna'     : "r,g,b" untuk aksen (dipakai rgb()/rgba() di Blade)
 *  - 'ringkasan' : 1-2 kalimat peran
 *  - 'alur'      : langkah kerja utama [ikon, judul, detail, menu, tips?, video?]; kosong = "sedang disiapkan"
 *                  tips  = callout saran/peringatan singkat (opsional)
 *                  video = URL video tutorial langkah (opsional; tombol muncul otomatis bila diisi)
 *                  rincian = daftar langkah rinci (array string, opsional) — tampil sebagai poin
 *  - 'faq'       : tanya-jawab singkat [t, j]
 *  - 'menus'     : daftar menu sidebar peran + kegunaannya, dikelompokkan per grup
 *
 * Catatan: hanya peran PPK yang ditulis lengkap sebagai contoh (prototipe).
 * Peran lain berisi ringkasan + penanda "sedang disiapkan" sampai dilengkapi.
 */
class PanduanRegistry
{
    /**
     * Seluruh panduan, terurut sesuai urutan tampil pada pemilih peran.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            'PPK' => [
                'label' => 'Pejabat Pembuat Komitmen (PPK)',
                'menus' => [
                    ['grup' => 'Utama', 'items' => [
                        ['ikon' => 'space_dashboard', 'nama' => 'Dashboard Internal', 'guna' => 'Ringkasan tugas & status pekerjaan yang menunggu Anda.'],
                    ]],
                    ['grup' => 'Persetujuan & Verifikasi', 'items' => [
                        ['ikon' => 'verified', 'nama' => 'Approve Kontrak', 'guna' => 'Menyetujui atau menolak kontrak pengadaan yang diajukan ke Anda.'],
                        ['ikon' => 'fact_check', 'nama' => 'Verifikasi Tagihan', 'guna' => 'Memverifikasi tagihan Kontrak, Perjaldin, dan Honorarium.'],
                    ]],
                    ['grup' => 'Tagihan & Pencairan', 'items' => [
                        ['ikon' => 'account_tree', 'nama' => 'Proses Tagihan', 'guna' => 'Memantau posisi tiap tagihan sepanjang alur hingga cair.'],
                    ]],
                    ['grup' => 'Master & Administrasi', 'items' => [
                        ['ikon' => 'account_balance', 'nama' => 'Master Data › DIPA', 'guna' => 'Melihat pagu anggaran (DIPA) sebagai dasar komitmen.'],
                        ['ikon' => 'list_alt', 'nama' => 'Master Data › COA', 'guna' => 'Melihat bagan akun (COA) untuk klasifikasi anggaran.'],
                    ]],
                ],
                'ikon' => 'verified_user',
                'warna' => '124,58,237',
                'ringkasan' => 'Menetapkan komitmen (kontrak) dengan penyedia, memverifikasi dokumen & tagihan, lalu meneruskannya ke tahap pembayaran.',
                'alur' => [
                    [
                        'ikon' => 'notifications',
                        'judul' => 'Pantau tugas masuk',
                        'detail' => 'Lonceng di kanan atas dan Dashboard menampilkan kontrak/tagihan yang menunggu tindakan Anda. Mulai kerja dari sini setiap hari.',
                        'menu' => 'Dashboard',
                        'tips' => 'Cek notifikasi tiap pagi agar tidak ada tagihan yang lewat batas waktu.',
                        'video' => null,
                    ],
                    [
                        'ikon' => 'description',
                        'judul' => 'Buka daftar kontrak',
                        'detail' => 'Masuk menu Kontrak untuk melihat kontrak yang perlu ditetapkan atau diverifikasi. Gunakan kolom cari/filter bila daftar panjang.',
                        'menu' => 'Kontrak',
                        'tips' => 'Pakai filter status "Menunggu Verifikasi" agar fokus ke yang perlu tindakan.',
                    ],
                    [
                        'ikon' => 'fact_check',
                        'judul' => 'Periksa dokumen & data',
                        'detail' => 'Teliti nilai, masa berlaku, data penyedia, dan lampiran. Cocokkan dengan dokumen pengadaan sebelum mengambil keputusan.',
                        'menu' => 'Kontrak › Detail',
                        'tips' => 'Bandingkan nilai kontrak dengan pagu DIPA sebelum menyetujui.',
                    ],
                    [
                        'ikon' => 'task_alt',
                        'judul' => 'Setujui atau minta revisi',
                        'detail' => 'Setujui untuk meneruskan ke tahap berikutnya, atau kembalikan dengan catatan bila ada yang perlu diperbaiki. Data tidak terhapus saat diminta revisi.',
                        'menu' => 'Kontrak › Detail',
                        'tips' => 'Tulis catatan revisi yang jelas agar pengaju tidak bolak-balik memperbaiki.',
                    ],
                    [
                        'ikon' => 'receipt_long',
                        'judul' => 'Verifikasi tagihan',
                        'detail' => 'Saat penyedia menagih, periksa kelengkapan berkas dan kesesuaian nilai sebelum diteruskan ke PPSPM untuk penerbitan SPM.',
                        'menu' => 'Tagihan',
                        'tips' => 'Pastikan progres pekerjaan/BAST sesuai sebelum tagihan diteruskan ke PPSPM.',
                    ],
                ],
                'faq' => [
                    [
                        't' => "Apa beda \"Setujui\" dan \"Minta revisi\"?",
                        'j' => '"Setujui" meneruskan dokumen ke tahap berikutnya (mis. PPSPM). "Minta revisi" mengembalikannya ke pengaju beserta catatan agar diperbaiki — dokumen dan datanya tetap tersimpan, tidak dihapus.',
                    ],
                    [
                        't' => 'Kenapa kontrak belum muncul di daftar saya?',
                        'j' => 'Kontrak baru masuk antrean Anda setelah pengaju mengirimkannya ke tahap verifikasi PPK. Pastikan juga filter status/tahun tidak menyembunyikannya.',
                    ],
                    [
                        't' => 'Bisakah saya menarik persetujuan yang sudah diberikan?',
                        'j' => 'Tergantung tahap workflow. Bila dokumen sudah diproses tahap berikutnya, koordinasikan dengan PPSPM/Koordinator Keuangan. Bila belum, buka menu detail untuk membatalkan atau merevisi.',
                    ],
                ],
            ],

            'PPSPM' => [
                'label' => 'Pejabat Penanda Tangan SPM (PPSPM)',
                'menus' => [
                    ['grup' => 'Utama', 'items' => [
                        ['ikon' => 'space_dashboard', 'nama' => 'Dashboard PPSPM', 'guna' => 'Ringkasan SPP/tagihan yang menunggu pengujian & penerbitan SPM.'],
                    ]],
                    ['grup' => 'Persetujuan & Verifikasi', 'items' => [
                        ['ikon' => 'fact_check', 'nama' => 'Verifikasi Tagihan', 'guna' => 'Menguji tagihan (Kontrak/Perjaldin/Honorarium) dan menerbitkan SPM.'],
                    ]],
                    ['grup' => 'Tagihan & Pencairan', 'items' => [
                        ['ikon' => 'account_tree', 'nama' => 'Proses Tagihan', 'guna' => 'Memantau tagihan hingga terbit SP2D dan dibayar.'],
                    ]],
                ],
                'ikon' => 'fact_check',
                'warna' => '5,150,105',
                'ringkasan' => 'Menguji tagihan yang diajukan dan menerbitkan Surat Perintah Membayar (SPM) atas dasar SPP yang sah.',
                'alur' => [
                    [
                        'ikon' => 'space_dashboard',
                        'judul' => 'Pantau tagihan menunggu',
                        'detail' => 'Dashboard PPSPM menampilkan SPP/tagihan yang menunggu pengujian Anda.',
                        'menu' => 'Dashboard PPSPM',
                        'tips' => 'Prioritaskan tagihan yang mendekati batas waktu agar tidak terlambat dibayar.',
                    ],
                    [
                        'ikon' => 'rule',
                        'judul' => 'Uji tagihan',
                        'detail' => 'Buka Verifikasi Tagihan lalu uji kelengkapan dan kebenaran (Kontrak, Perjaldin, Honorarium).',
                        'menu' => 'Verifikasi Tagihan',
                        'tips' => 'Jangan terbitkan SPM bila kelengkapan SPP belum 100% — kembalikan dulu.',
                    ],
                    [
                        'ikon' => 'assignment_return',
                        'judul' => 'Setujui atau kembalikan',
                        'detail' => 'Setujui bila uji benar, atau kembalikan ke pengaju dengan catatan bila ada kekurangan.',
                        'menu' => 'Verifikasi Tagihan',
                        'tips' => 'Bila menolak, sebutkan dokumen spesifik yang kurang agar cepat dilengkapi.',
                    ],
                    [
                        'ikon' => 'fact_check',
                        'judul' => 'Terbitkan SPM',
                        'detail' => 'Atas tagihan yang lolos uji, terbitkan Surat Perintah Membayar (SPM).',
                        'menu' => 'Verifikasi Tagihan',
                        'tips' => 'Periksa ulang nomor & nilai SPM sebelum tanda tangan — koreksi setelah terbit merepotkan.',
                    ],
                    [
                        'ikon' => 'account_tree',
                        'judul' => 'Pantau sampai SP2D',
                        'detail' => 'Ikuti perkembangan tagihan pada Proses Tagihan hingga terbit SP2D.',
                        'menu' => 'Proses Tagihan',
                        'tips' => 'Jika SP2D tertahan di KPPN, koordinasikan dengan Bendahara Pengeluaran.',
                    ],
                ],
                'faq' => [
                    [
                        't' => 'Apa syarat sebuah tagihan bisa diterbitkan SPM-nya?',
                        'j' => 'Tagihan harus lolos uji kelengkapan dan kebenaran (SPP sah, dokumen pendukung lengkap, nilai sesuai). Bila ada yang kurang, kembalikan dulu ke pengaju sebelum SPM diterbitkan.',
                    ],
                    [
                        't' => 'SP2D belum terbit padahal SPM sudah saya tanda tangani. Apa yang dicek?',
                        'j' => 'Pantau statusnya di Proses Tagihan. Bila tertahan, koordinasikan dengan Bendahara Pengeluaran/KPPN — biasanya menyangkut saldo, data rekening, atau kelengkapan berkas SPM.',
                    ],
                ],
            ],

            'Koordinator Keuangan' => [
                'label' => 'Koordinator Keuangan',
                'menus' => [
                    ['grup' => 'Utama', 'items' => [
                        ['ikon' => 'space_dashboard', 'nama' => 'Dashboard Koordinator Keuangan', 'guna' => 'Ringkasan dokumen yang perlu dikoordinasikan/diverifikasi.'],
                    ]],
                    ['grup' => 'Persetujuan & Verifikasi', 'items' => [
                        ['ikon' => 'fact_check', 'nama' => 'Verifikasi Tagihan', 'guna' => 'Memverifikasi & mengoordinasikan usulan tagihan.'],
                    ]],
                    ['grup' => 'Tagihan & Pencairan', 'items' => [
                        ['ikon' => 'account_tree', 'nama' => 'Proses Tagihan', 'guna' => 'Memantau posisi tiap tagihan sepanjang alur.'],
                    ]],
                    ['grup' => 'Master & Administrasi', 'items' => [
                        ['ikon' => 'tag', 'nama' => 'Nomor Surat', 'guna' => 'Membuat & menatausahakan nomor surat dokumen keuangan.'],
                    ]],
                ],
                'ikon' => 'hub',
                'warna' => '79,70,229',
                'ringkasan' => 'Mengoordinasikan alur dokumen keuangan, memverifikasi usulan, dan menjembatani unit dengan PPK/PPSPM.',
                'alur' => [
                    [
                        'ikon' => 'space_dashboard',
                        'judul' => 'Pantau dashboard',
                        'detail' => 'Dashboard Koordinator Keuangan menampilkan dokumen yang perlu dikoordinasikan.',
                        'menu' => 'Dashboard Koordinator Keuangan',
                        'tips' => 'Tinjau dashboard tiap pagi agar dokumen tidak menumpuk menjelang akhir bulan.',
                    ],
                    [
                        'ikon' => 'fact_check',
                        'judul' => 'Verifikasi & koordinasikan',
                        'detail' => 'Periksa usulan tagihan (Kontrak/Perjaldin/Honorarium) dan koordinasikan kelengkapannya.',
                        'menu' => 'Verifikasi Tagihan',
                        'tips' => 'Koordinasikan kekurangan berkas lebih awal agar tidak menumpuk di akhir bulan.',
                    ],
                    [
                        'ikon' => 'tag',
                        'judul' => 'Terbitkan nomor surat',
                        'detail' => 'Buat dan tatausahakan nomor surat untuk dokumen keuangan terkait.',
                        'menu' => 'Nomor Surat',
                        'tips' => 'Ambil nomor surat sesuai jenis dokumen agar penomoran tetap runtut.',
                    ],
                    [
                        'ikon' => 'account_tree',
                        'judul' => 'Pantau alur tagihan',
                        'detail' => 'Ikuti posisi setiap tagihan pada Proses Tagihan hingga selesai.',
                        'menu' => 'Proses Tagihan',
                        'tips' => 'Bila satu tahap macet, hubungi penanggung jawab tahap itu lebih awal.',
                    ],
                ],
                'faq' => [
                    [
                        't' => 'Apa peran saya dibanding PPK dan PPSPM?',
                        'j' => 'Anda mengoordinasikan kelengkapan dan kelancaran dokumen di antara unit, PPK, dan PPSPM — memastikan usulan tagihan siap sebelum masuk ke tahap verifikasi/penerbitan SPM.',
                    ],
                    [
                        't' => 'Bagaimana cara mengambil nomor surat?',
                        'j' => 'Buka menu Nomor Surat, pilih jenis dokumen, lalu sistem memberi nomor berurutan. Ambil sesuai kebutuhan agar penomoran tetap runtut.',
                    ],
                ],
            ],

            'Bendahara Pengeluaran' => [
                'label' => 'Bendahara Pengeluaran',
                'menus' => [
                    ['grup' => 'Utama', 'items' => [
                        ['ikon' => 'space_dashboard', 'nama' => 'Dashboard Internal', 'guna' => 'Ringkasan tugas pembayaran & pembukuan Anda.'],
                    ]],
                    ['grup' => 'Persetujuan & Verifikasi', 'items' => [
                        ['ikon' => 'fact_check', 'nama' => 'Verifikasi Tagihan', 'guna' => 'Memeriksa tagihan yang akan dibayar.'],
                    ]],
                    ['grup' => 'Tagihan & Pencairan', 'items' => [
                        ['ikon' => 'account_tree', 'nama' => 'Proses Tagihan', 'guna' => 'Memantau & melaksanakan pembayaran setelah SP2D.'],
                    ]],
                    ['grup' => 'Pembukuan & Laporan', 'items' => [
                        ['ikon' => 'menu_book', 'nama' => 'Buku Kas Umum', 'guna' => 'Mencatat seluruh penerimaan & pengeluaran kas.'],
                        ['ikon' => 'account_balance', 'nama' => 'Buku Pembantu Bank', 'guna' => 'Mencatat mutasi & saldo rekening bank.'],
                        ['ikon' => 'account_balance_wallet', 'nama' => 'Buku Pembantu Bendahara', 'guna' => 'Mencatat saldo uang persediaan di tangan bendahara.'],
                        ['ikon' => 'percent', 'nama' => 'Buku Pembantu Bunga Rekening', 'guna' => 'Mencatat bunga/jasa giro rekening.'],
                        ['ikon' => 'receipt_long', 'nama' => 'Buku Pembantu Pajak', 'guna' => 'Mencatat pemungutan & penyetoran pajak.'],
                        ['ikon' => 'verified', 'nama' => 'Buku Pengesahan Belanja', 'guna' => 'Menyusun pengesahan belanja (SP3B/SP2B) ke KPPN.'],
                        ['ikon' => 'settings', 'nama' => 'Setup Pembukuan', 'guna' => 'Mengatur saldo awal & parameter pembukuan.'],
                    ]],
                ],
                'ikon' => 'payments',
                'warna' => '220,38,38',
                'ringkasan' => 'Mengelola uang persediaan, membukukan pengeluaran (BKU), dan melaksanakan pembayaran sesuai SPM/SP2D.',
                'alur' => [
                    [
                        'ikon' => 'fact_check',
                        'judul' => 'Verifikasi tagihan',
                        'detail' => 'Periksa tagihan yang akan dibayar (Kontrak/Perjaldin/Honorarium).',
                        'menu' => 'Verifikasi Tagihan',
                        'tips' => 'Cek nomor rekening penerima cocok dengan dokumen agar pembayaran tidak salah alamat.',
                    ],
                    [
                        'ikon' => 'payments',
                        'judul' => 'Bayar setelah SP2D',
                        'detail' => 'Setelah SPM/SP2D terbit, laksanakan pembayaran kepada pihak yang berhak.',
                        'menu' => 'Proses Tagihan',
                        'tips' => 'Bayar hanya setelah SP2D sah; pembayaran mendahului SP2D berisiko temuan.',
                    ],
                    [
                        'ikon' => 'menu_book',
                        'judul' => 'Bukukan ke BKU',
                        'detail' => 'Catat setiap pengeluaran pada Buku Kas Umum agar saldo kas selalu cocok.',
                        'menu' => 'Pembukuan › Buku Kas Umum',
                        'tips' => 'Bukukan di hari yang sama dengan transaksi agar saldo BKU selalu cocok.',
                    ],
                    [
                        'ikon' => 'account_balance',
                        'judul' => 'Rekonsiliasi bank & pajak',
                        'detail' => 'Perbarui Buku Pembantu Bank dan catat/setor pajak pada Buku Pembantu Pajak.',
                        'menu' => 'Pembukuan › Buku Pembantu',
                        'tips' => 'Setor pajak sebelum tanggal jatuh tempo untuk menghindari denda.',
                    ],
                    [
                        'ikon' => 'verified',
                        'judul' => 'Susun pengesahan belanja',
                        'detail' => 'Susun Buku Pengesahan Belanja sebagai dasar pengesahan ke KPPN.',
                        'menu' => 'Pembukuan › Pengesahan Belanja',
                        'tips' => 'Pastikan seluruh BKU bulan berjalan sudah final sebelum menyusun pengesahan.',
                    ],
                ],
                'faq' => [
                    [
                        't' => 'Kapan saya boleh melakukan pembayaran?',
                        'j' => 'Setelah SPM/SP2D sah. Pembayaran yang mendahului SP2D berisiko menjadi temuan pemeriksaan.',
                    ],
                    [
                        't' => 'Saldo BKU tidak cocok dengan kas/bank. Apa langkahnya?',
                        'j' => 'Telusuri transaksi yang belum atau terlambat dibukukan, lalu cocokkan dengan Buku Pembantu Bank. Membukukan di hari yang sama dengan transaksi mencegah selisih.',
                    ],
                    [
                        't' => 'Apa itu Buku Pengesahan Belanja?',
                        'j' => 'Dokumen untuk mengesahkan belanja BLU ke KPPN (SP3B/SP2B). Pastikan seluruh BKU bulan berjalan sudah final sebelum menyusunnya.',
                    ],
                ],
            ],

            'Bendahara Penerimaan' => [
                'label' => 'Bendahara Penerimaan',
                'menus' => [
                    ['grup' => 'Utama', 'items' => [
                        ['ikon' => 'savings', 'nama' => 'Manajemen PNBP', 'guna' => 'Mengelola data penerimaan negara bukan pajak (PNBP).'],
                    ]],
                    ['grup' => 'Persetujuan & Verifikasi', 'items' => [
                        ['ikon' => 'fact_check', 'nama' => 'Verifikasi Tagihan', 'guna' => 'Memverifikasi tagihan dari sisi penerimaan.'],
                    ]],
                    ['grup' => 'Tagihan & Pencairan', 'items' => [
                        ['ikon' => 'account_tree', 'nama' => 'Proses Tagihan', 'guna' => 'Memantau posisi tagihan sepanjang alur.'],
                    ]],
                    ['grup' => 'Pembukuan & Laporan', 'items' => [
                        ['ikon' => 'menu_book', 'nama' => 'Buku Kas Umum', 'guna' => 'Mencatat seluruh penerimaan kas.'],
                        ['ikon' => 'account_balance', 'nama' => 'Buku Pembantu Bank', 'guna' => 'Rekonsiliasi mutasi rekening dengan setoran.'],
                        ['ikon' => 'account_balance_wallet', 'nama' => 'Buku Pembantu Bendahara', 'guna' => 'Mencatat saldo uang di tangan bendahara penerimaan.'],
                        ['ikon' => 'percent', 'nama' => 'Buku Pembantu Bunga Rekening', 'guna' => 'Mencatat bunga/jasa giro.'],
                        ['ikon' => 'category', 'nama' => 'Klasifikasi Penerimaan', 'guna' => 'Mengelompokkan penerimaan per akun pendapatan.'],
                        ['ikon' => 'trending_up', 'nama' => 'Realisasi Penerimaan', 'guna' => 'Mencatat realisasi penerimaan terhadap target.'],
                        ['ikon' => 'verified', 'nama' => 'Buku Pengesahan Pendapatan', 'guna' => 'Menyusun pengesahan pendapatan ke KPPN.'],
                        ['ikon' => 'request_quote', 'nama' => 'Pengecekan Pembayaran (Piutang)', 'guna' => 'Memantau & menindaklanjuti piutang.'],
                        ['ikon' => 'settings', 'nama' => 'Setup Pembukuan', 'guna' => 'Mengatur saldo awal & parameter pembukuan.'],
                        ['ikon' => 'assessment', 'nama' => 'Laporan', 'guna' => 'Rekap penerimaan, setoran, pembayaran, dan piutang.'],
                    ]],
                ],
                'ikon' => 'savings',
                'warna' => '14,165,233',
                'ringkasan' => 'Menatausahakan penerimaan negara (PNBP), membukukan setoran, dan merekonsiliasi rekening koran.',
                'alur' => [
                    [
                        'ikon' => 'savings',
                        'judul' => 'Kelola PNBP',
                        'detail' => 'Pantau dan kelola penerimaan negara (PNBP) yang masuk.',
                        'menu' => 'Manajemen PNBP',
                        'tips' => 'Pisahkan penerimaan per jenis layanan agar mudah direkonsiliasi nanti.',
                    ],
                    [
                        'ikon' => 'category',
                        'judul' => 'Klasifikasikan penerimaan',
                        'detail' => 'Catat dan klasifikasikan tiap penerimaan sesuai akun pendapatan.',
                        'menu' => 'Pembukuan › Klasifikasi Penerimaan',
                        'tips' => 'Gunakan akun pendapatan yang benar agar laporan realisasi akurat.',
                    ],
                    [
                        'ikon' => 'menu_book',
                        'judul' => 'Bukukan ke BKU',
                        'detail' => 'Bukukan penerimaan pada Buku Kas Umum (sisi penerimaan).',
                        'menu' => 'Pembukuan › Buku Kas Umum',
                        'tips' => 'Bukukan penerimaan begitu dana masuk, jangan ditunda ke hari berikutnya.',
                    ],
                    [
                        'ikon' => 'account_balance',
                        'judul' => 'Rekonsiliasi rekening',
                        'detail' => 'Cocokkan setoran dengan rekening koran pada Buku Pembantu Bank.',
                        'menu' => 'Pembukuan › Buku Pembantu Bank',
                        'tips' => 'Cocokkan tiap setoran dengan rekening koran di hari yang sama.',
                    ],
                    [
                        'ikon' => 'verified',
                        'judul' => 'Realisasi & pengesahan',
                        'detail' => 'Susun Realisasi Penerimaan dan Buku Pengesahan Pendapatan.',
                        'menu' => 'Pembukuan › Pengesahan Pendapatan',
                        'tips' => 'Cocokkan total realisasi dengan BKU sebelum mengajukan pengesahan.',
                    ],
                    [
                        'ikon' => 'request_quote',
                        'judul' => 'Pantau piutang',
                        'detail' => 'Tindak lanjuti penerimaan yang belum lunas lewat Pengecekan Pembayaran (Piutang).',
                        'menu' => 'Pembukuan › Pengecekan Pembayaran',
                        'tips' => 'Kirim pengingat ke mitra sebelum jatuh tempo untuk menekan piutang macet.',
                    ],
                ],
                'faq' => [
                    [
                        't' => 'Bagaimana cara merekonsiliasi penerimaan dengan rekening koran?',
                        'j' => 'Cocokkan tiap setoran yang dibukukan di Buku Kas Umum dengan mutasi pada Buku Pembantu Bank. Lakukan harian agar selisih cepat ketahuan.',
                    ],
                    [
                        't' => 'Apa beda Klasifikasi Penerimaan dan Realisasi Penerimaan?',
                        'j' => 'Klasifikasi mengelompokkan tiap penerimaan ke akun pendapatan yang benar; Realisasi merangkum capaian penerimaan terhadap target untuk pelaporan.',
                    ],
                    [
                        't' => 'Bagaimana menindaklanjuti piutang yang belum terbayar?',
                        'j' => 'Gunakan menu Pengecekan Pembayaran (Piutang) untuk memantau yang jatuh tempo, lalu kirim pengingat ke mitra sebelum jatuh tempo.',
                    ],
                ],
            ],

            'PPABP' => [
                'label' => 'Petugas Pengelola Administrasi Belanja Pegawai (PPABP)',
                'menus' => [
                    ['grup' => 'Utama', 'items' => [
                        ['ikon' => 'space_dashboard', 'nama' => 'Dashboard Internal', 'guna' => 'Ringkasan tugas honorarium Anda.'],
                    ]],
                    ['grup' => 'Tagihan & Pencairan', 'items' => [
                        ['ikon' => 'payments', 'nama' => 'Manajemen Honor', 'guna' => 'Menyusun & mengajukan honorarium serta belanja pegawai.'],
                    ]],
                ],
                'ikon' => 'groups',
                'warna' => '202,138,4',
                'ringkasan' => 'Mengelola data kepegawaian terkait pembayaran serta menyusun honorarium dan belanja pegawai.',
                'alur' => [
                    [
                        'ikon' => 'groups',
                        'judul' => 'Buka Manajemen Honor',
                        'detail' => 'Mulai dari menu Manajemen Honor untuk mengelola pembayaran honorarium.',
                        'menu' => 'Manajemen Honor',
                        'tips' => 'Pastikan data pegawai & jabatan mutakhir sebelum menyusun honor.',
                    ],
                    [
                        'ikon' => 'edit_note',
                        'judul' => 'Susun daftar honorarium',
                        'detail' => 'Tetapkan penerima, besaran, dan potongan pajak sesuai ketentuan.',
                        'menu' => 'Manajemen Honor',
                        'tips' => 'Pastikan potongan pajak (PPh 21) sesuai status kepegawaian penerima.',
                    ],
                    [
                        'ikon' => 'send',
                        'judul' => 'Ajukan tagihan honor',
                        'detail' => 'Ajukan honorarium agar masuk ke alur verifikasi tagihan.',
                        'menu' => 'Manajemen Honor',
                        'tips' => 'Lampirkan dasar pembayaran (SK/surat tugas) agar verifikasi lancar.',
                    ],
                    [
                        'ikon' => 'notifications_active',
                        'judul' => 'Pantau status',
                        'detail' => 'Cek status pengajuan; perbaiki bila ada catatan revisi dari verifikator.',
                        'menu' => 'Manajemen Honor',
                        'tips' => 'Tanggapi catatan revisi segera agar honor tidak tertunda pembayarannya.',
                    ],
                ],
                'faq' => [
                    [
                        't' => 'Data apa yang harus disiapkan sebelum menyusun honorarium?',
                        'j' => 'Pastikan data pegawai, jabatan, dan dasar pembayaran (SK/surat tugas) sudah mutakhir, serta potongan pajak (PPh 21) sesuai status kepegawaian penerima.',
                    ],
                    [
                        't' => 'Honorarium yang saya ajukan dikembalikan. Apa yang dilakukan?',
                        'j' => 'Baca catatan revisi dari verifikator, perbaiki di menu Manajemen Honor, lalu ajukan ulang. Tanggapi cepat agar pembayaran tidak tertunda.',
                    ],
                ],
            ],

            'Operator Perjaldin' => [
                'label' => 'Operator Perjalanan Dinas',
                'menus' => [
                    ['grup' => 'Utama', 'items' => [
                        ['ikon' => 'space_dashboard', 'nama' => 'Dashboard Internal', 'guna' => 'Ringkasan dokumen perjalanan dinas Anda.'],
                    ]],
                    ['grup' => 'Tagihan & Pencairan', 'items' => [
                        ['ikon' => 'flight_takeoff', 'nama' => 'Manajemen Perjaldin', 'guna' => 'Membuat & mengelola dokumen perjalanan dinas (SPD) dan usulannya.'],
                    ]],
                    ['grup' => 'Master & Administrasi', 'items' => [
                        ['ikon' => 'payments', 'nama' => 'Master Data › Uang Harian', 'guna' => 'Acuan tarif uang harian perjalanan dinas.'],
                    ]],
                ],
                'ikon' => 'flight_takeoff',
                'warna' => '13,148,136',
                'ringkasan' => 'Menginput dan mengelola dokumen perjalanan dinas serta usulan SPP perjaldin.',
                'alur' => [
                    [
                        'ikon' => 'tune',
                        'judul' => 'Cek master uang harian',
                        'detail' => 'Pastikan tarif Uang Harian sudah sesuai sebelum membuat dokumen.',
                        'menu' => 'Master Data › Uang Harian',
                        'tips' => 'Pakai tarif uang harian terbaru sesuai zona/tujuan agar tidak salah hitung.',
                    ],
                    [
                        'ikon' => 'flight_takeoff',
                        'judul' => 'Buat dokumen perjaldin',
                        'detail' => 'Buat surat tugas/SPD dan isi data pelaksana perjalanan dinas.',
                        'menu' => 'Manajemen Perjaldin',
                        'tips' => 'Pastikan surat tugas sudah terbit sebelum membuat SPD.',
                    ],
                    [
                        'ikon' => 'calculate',
                        'judul' => 'Isi rincian biaya',
                        'detail' => 'Lengkapi komponen biaya (uang harian, transport, penginapan) sesuai ketentuan.',
                        'menu' => 'Manajemen Perjaldin',
                        'tips' => 'Lampirkan bukti riil (tiket, bill hotel) untuk komponen at-cost.',
                    ],
                    [
                        'ikon' => 'send',
                        'judul' => 'Ajukan untuk verifikasi',
                        'detail' => 'Kirim dokumen perjaldin agar masuk ke alur verifikasi tagihan.',
                        'menu' => 'Manajemen Perjaldin',
                        'tips' => 'Periksa kembali tanggal & tujuan agar perhitungan uang harian tepat.',
                    ],
                    [
                        'ikon' => 'account_tree',
                        'judul' => 'Pantau status',
                        'detail' => 'Ikuti status sampai disetujui dan dibayar; perbaiki bila ada catatan revisi.',
                        'menu' => 'Manajemen Perjaldin',
                        'tips' => 'Simpan bukti pengeluaran asli sampai pembayaran selesai diverifikasi.',
                    ],
                ],
                'faq' => [
                    [
                        't' => 'Uang harian yang dihitung sistem terasa tidak sesuai. Kenapa?',
                        'j' => 'Cek master Uang Harian (tarif per zona/tujuan) dan pastikan tanggal serta tujuan pada dokumen sudah benar — perhitungan mengikuti data tersebut.',
                    ],
                    [
                        't' => 'Dokumen apa yang harus dilampirkan untuk biaya at-cost?',
                        'j' => 'Lampirkan bukti riil seperti tiket dan bill hotel. Simpan bukti asli sampai pembayaran selesai diverifikasi.',
                    ],
                ],
            ],

            'Pejabat Pengadaan' => [
                'label' => 'Pejabat Pengadaan',
                'menus' => [
                    ['grup' => 'Utama', 'items' => [
                        ['ikon' => 'space_dashboard', 'nama' => 'Dashboard Internal', 'guna' => 'Ringkasan proses pengadaan Anda.'],
                    ]],
                    ['grup' => 'Tagihan & Pencairan', 'items' => [
                        ['ikon' => 'description', 'nama' => 'Manajemen Kontrak', 'guna' => 'Membuat & mengelola kontrak pengadaan barang/jasa.'],
                    ]],
                    ['grup' => 'Master & Administrasi', 'items' => [
                        ['ikon' => 'store', 'nama' => 'Master Data › Vendor', 'guna' => 'Mengelola data penyedia/vendor untuk kontrak.'],
                        ['ikon' => 'confirmation_number', 'nama' => 'Nomor Dokumen', 'guna' => 'Penomoran dokumen pengadaan (kontrak, BAPP, BAP).'],
                    ]],
                ],
                'ikon' => 'shopping_cart',
                'warna' => '234,88,12',
                'ringkasan' => 'Melaksanakan proses pengadaan barang/jasa: menyiapkan, memilih penyedia, dan menetapkan hasil pengadaan.',
                'alur' => [
                    [
                        'ikon' => 'store',
                        'judul' => 'Kelola data vendor',
                        'detail' => 'Pastikan data Vendor/penyedia lengkap sebelum membuat kontrak.',
                        'menu' => 'Master Data › Vendor',
                        'tips' => 'Pastikan NPWP & rekening vendor benar sebelum kontrak agar tidak gagal bayar.',
                    ],
                    [
                        'ikon' => 'confirmation_number',
                        'judul' => 'Siapkan nomor dokumen',
                        'detail' => 'Ambil dan atur nomor dokumen pengadaan pada menu Nomor Dokumen.',
                        'menu' => 'Nomor Dokumen',
                        'tips' => 'Ambil nomor dokumen lebih dulu agar penomoran kontrak runtut.',
                    ],
                    [
                        'ikon' => 'description',
                        'judul' => 'Buat kontrak',
                        'detail' => 'Susun kontrak pengadaan: penyedia, nilai, lingkup, dan masa berlaku.',
                        'menu' => 'Manajemen Kontrak',
                        'tips' => 'Tetapkan masa berlaku & nilai dengan teliti — keduanya sulit diubah setelah disetujui.',
                    ],
                    [
                        'ikon' => 'upload_file',
                        'judul' => 'Lengkapi & kirim ke PPK',
                        'detail' => 'Unggah dokumen pendukung lalu kirim kontrak untuk persetujuan PPK.',
                        'menu' => 'Manajemen Kontrak',
                        'tips' => 'Pastikan seluruh lampiran terunggah agar PPK tidak mengembalikan kontrak.',
                    ],
                    [
                        'ikon' => 'account_tree',
                        'judul' => 'Pantau status kontrak',
                        'detail' => 'Pantau kontrak hingga disetujui; perbaiki bila ada permintaan revisi.',
                        'menu' => 'Manajemen Kontrak',
                        'tips' => 'Tanggapi permintaan revisi PPK segera agar proses tidak tertunda.',
                    ],
                ],
                'faq' => [
                    [
                        't' => 'Apa yang harus disiapkan sebelum membuat kontrak?',
                        'j' => 'Pastikan data Vendor (NPWP, rekening, alamat) lengkap dan benar, lalu ambil nomor dokumen lebih dulu agar penomoran kontrak runtut.',
                    ],
                    [
                        't' => 'Kontrak saya dikembalikan PPK. Apa langkahnya?',
                        'j' => 'Baca catatan PPK, lengkapi atau koreksi pada menu Manajemen Kontrak (termasuk lampiran), lalu kirim ulang untuk persetujuan.',
                    ],
                ],
            ],

            'Kepala Subbagian Keuangan dan Tata Usaha' => [
                'label' => 'Kasubbag Keuangan & Tata Usaha',
                'menus' => [
                    ['grup' => 'Utama', 'items' => [
                        ['ikon' => 'space_dashboard', 'nama' => 'Dashboard Internal', 'guna' => 'Ringkasan administrasi keuangan & tata usaha.'],
                    ]],
                    ['grup' => 'Persetujuan & Verifikasi', 'items' => [
                        ['ikon' => 'fact_check', 'nama' => 'Verifikasi Tagihan', 'guna' => 'Memeriksa & memberi disposisi atas tagihan.'],
                        ['ikon' => 'monitoring', 'nama' => 'Verifikasi Laporan › Monitoring', 'guna' => 'Mengawasi laporan jasa/keuangan (read-only).'],
                    ]],
                    ['grup' => 'Tagihan & Pencairan', 'items' => [
                        ['ikon' => 'account_tree', 'nama' => 'Proses Tagihan', 'guna' => 'Memantau alur tagihan secara menyeluruh.'],
                    ]],
                    ['grup' => 'Master & Administrasi', 'items' => [
                        ['ikon' => 'account_balance', 'nama' => 'Master Data › DIPA', 'guna' => 'Melihat pagu anggaran.'],
                        ['ikon' => 'list_alt', 'nama' => 'Master Data › COA', 'guna' => 'Melihat bagan akun.'],
                        ['ikon' => 'receipt_long', 'nama' => 'Master Data › Pajak', 'guna' => 'Melihat master tarif pajak.'],
                    ]],
                ],
                'ikon' => 'supervisor_account',
                'warna' => '100,116,139',
                'ringkasan' => 'Mengawasi administrasi keuangan & tata usaha, memverifikasi, dan memberi disposisi.',
                'alur' => [
                    [
                        'ikon' => 'fact_check',
                        'judul' => 'Tinjau tagihan masuk',
                        'detail' => 'Buka Verifikasi Tagihan untuk dokumen yang menunggu disposisi Anda.',
                        'menu' => 'Verifikasi Tagihan',
                        'tips' => 'Dahulukan dokumen yang mendekati batas waktu pembayaran.',
                    ],
                    [
                        'ikon' => 'rule',
                        'judul' => 'Periksa & beri disposisi',
                        'detail' => 'Periksa kelengkapan, lalu setujui atau kembalikan dengan catatan.',
                        'menu' => 'Verifikasi Tagihan',
                        'tips' => 'Beri disposisi dengan catatan jelas agar alur verifikasi tidak tersendat.',
                    ],
                    [
                        'ikon' => 'monitoring',
                        'judul' => 'Awasi pelaporan',
                        'detail' => 'Gunakan Monitoring Pelaporan untuk mengawasi laporan (read-only).',
                        'menu' => 'Verifikasi Laporan › Monitoring',
                        'tips' => 'Gunakan Monitoring untuk mendeteksi laporan yang belum masuk lebih awal.',
                    ],
                    [
                        'ikon' => 'account_tree',
                        'judul' => 'Pantau alur tagihan',
                        'detail' => 'Ikuti posisi tagihan pada Proses Tagihan secara menyeluruh.',
                        'menu' => 'Proses Tagihan',
                        'tips' => 'Bila ada tahap yang macet, beri arahan ke unit terkait.',
                    ],
                ],
                'faq' => [
                    [
                        't' => 'Apa fokus pemeriksaan saya saat verifikasi?',
                        'j' => 'Periksa kelengkapan administrasi dan kesesuaian dokumen, lalu beri disposisi/persetujuan atau kembalikan dengan catatan yang jelas.',
                    ],
                    [
                        't' => 'Untuk apa menu Monitoring Pelaporan?',
                        'j' => 'Untuk mengawasi laporan jasa/keuangan secara read-only — berguna mendeteksi laporan yang belum masuk lebih awal.',
                    ],
                ],
            ],

            'KPA' => [
                'label' => 'Kuasa Pengguna Anggaran (KPA)',
                'menus' => [
                    ['grup' => 'Utama', 'items' => [
                        ['ikon' => 'space_dashboard', 'nama' => 'Dashboard Internal', 'guna' => 'Ringkasan persetujuan & realisasi anggaran.'],
                    ]],
                    ['grup' => 'Persetujuan & Verifikasi', 'items' => [
                        ['ikon' => 'rule', 'nama' => 'Standing Instruction', 'guna' => 'Menetapkan instruksi tetap pembayaran.'],
                        ['ikon' => 'fact_check', 'nama' => 'Verifikasi Tagihan', 'guna' => 'Memberi persetujuan akhir atas tagihan.'],
                        ['ikon' => 'monitoring', 'nama' => 'Verifikasi Laporan › Monitoring', 'guna' => 'Mengawasi pelaporan (read-only).'],
                    ]],
                    ['grup' => 'Tagihan & Pencairan', 'items' => [
                        ['ikon' => 'account_tree', 'nama' => 'Proses Tagihan', 'guna' => 'Memantau realisasi tagihan.'],
                        ['ikon' => 'receipt_long', 'nama' => 'Riwayat Tagihan Jasa', 'guna' => 'Log tagihan bulanan & jatuh tempo jasa.'],
                    ]],
                    ['grup' => 'Master & Administrasi', 'items' => [
                        ['ikon' => 'account_balance', 'nama' => 'Master Data › DIPA', 'guna' => 'Memantau pagu & realisasi anggaran.'],
                        ['ikon' => 'list_alt', 'nama' => 'Master Data › COA', 'guna' => 'Melihat bagan akun.'],
                        ['ikon' => 'receipt_long', 'nama' => 'Master Data › Pajak', 'guna' => 'Melihat master tarif pajak.'],
                    ]],
                ],
                'ikon' => 'account_balance',
                'warna' => '217,70,239',
                'ringkasan' => 'Pemegang kewenangan tertinggi anggaran satker; memberi persetujuan akhir atas usulan dan kebijakan keuangan.',
                'alur' => [
                    [
                        'ikon' => 'fact_check',
                        'judul' => 'Atur Standing Instruction',
                        'detail' => 'Tetapkan instruksi tetap (Standing Instruction) sebagai dasar kebijakan pembayaran.',
                        'menu' => 'Standing Instruction',
                        'tips' => 'Tinjau ulang Standing Instruction secara berkala agar tetap relevan.',
                    ],
                    [
                        'ikon' => 'gavel',
                        'judul' => 'Setujui tagihan akhir',
                        'detail' => 'Tinjau dan berikan persetujuan akhir atas tagihan yang telah diverifikasi.',
                        'menu' => 'Verifikasi Tagihan',
                        'tips' => 'Pastikan tagihan sudah lolos verifikasi berjenjang sebelum persetujuan akhir.',
                    ],
                    [
                        'ikon' => 'account_tree',
                        'judul' => 'Pantau realisasi',
                        'detail' => 'Pantau realisasi dan jatuh tempo pembayaran pada Proses Tagihan.',
                        'menu' => 'Proses Tagihan',
                        'tips' => 'Pantau serapan agar realisasi tidak menumpuk di akhir tahun anggaran.',
                    ],
                    [
                        'ikon' => 'account_balance',
                        'judul' => 'Awasi anggaran',
                        'detail' => 'Pantau pagu dan realisasi melalui DIPA pada Master Data.',
                        'menu' => 'Master Data › DIPA',
                        'tips' => 'Bandingkan realisasi dengan pagu DIPA untuk mendeteksi potensi sisa/over.',
                    ],
                ],
                'faq' => [
                    [
                        't' => 'Apa itu Standing Instruction dan kapan dipakai?',
                        'j' => 'Instruksi tetap sebagai dasar kebijakan pembayaran berulang. Tinjau ulang secara berkala agar tetap relevan dengan kondisi terkini.',
                    ],
                    [
                        't' => 'Apa yang saya pastikan sebelum memberi persetujuan akhir?',
                        'j' => 'Pastikan tagihan sudah lolos verifikasi berjenjang (PPK/PPSPM/Koordinator) dan tersedia pagu pada DIPA. Pantau serapan agar realisasi tidak menumpuk di akhir tahun.',
                    ],
                ],
            ],

            'Admin Jasa' => [
                'label' => 'Admin Jasa',
                'menus' => [
                    ['grup' => 'AMC — Operasional', 'items' => [
                        ['ikon' => 'flight_takeoff', 'nama' => 'Permohonan Non-Schedule', 'guna' => 'Mencatat permohonan penerbangan non-schedule.'],
                        ['ikon' => 'airline_seat_recline_normal', 'nama' => 'Pemakaian Garbarata', 'guna' => 'Mencatat pemakaian garbarata per penerbangan.'],
                        ['ikon' => 'request_quote', 'nama' => 'Rekap Tagihan Garbarata', 'guna' => 'Merekap pemakaian garbarata untuk ditagihkan.'],
                    ]],
                    ['grup' => 'Persetujuan & Verifikasi', 'items' => [
                        ['ikon' => 'fact_check', 'nama' => 'Verifikasi (Konsesi/PJP2U/Utilitas)', 'guna' => 'Memverifikasi laporan mitra sebagai dasar tagihan.'],
                        ['ikon' => 'monitoring', 'nama' => 'Monitoring Pelaporan', 'guna' => 'Memantau kelengkapan laporan mitra.'],
                    ]],
                    ['grup' => 'Tagihan & Pencairan', 'items' => [
                        ['ikon' => 'receipt_long', 'nama' => 'Buat Tagihan', 'guna' => 'Membuat tagihan jasa untuk mitra.'],
                        ['ikon' => 'event_note', 'nama' => 'Log Tagihan Bulanan', 'guna' => 'Memantau rekap & status tagihan per bulan.'],
                        ['ikon' => 'schedule', 'nama' => 'Jatuh Tempo', 'guna' => 'Memantau tagihan yang mendekati atau lewat jatuh tempo.'],
                    ]],
                    ['grup' => 'Layanan Jasa', 'items' => [
                        ['ikon' => 'tune', 'nama' => 'Layanan Dikelola', 'guna' => 'Mengelola layanan jasa yang menjadi tanggung jawab Anda.'],
                        ['ikon' => 'storefront', 'nama' => 'Mitra Jasa', 'guna' => 'Mengelola data mitra jasa.'],
                    ]],
                ],
                'ikon' => 'receipt_long',
                'warna' => '37,99,235',
                'ringkasan' => 'Mengelola laporan jasa, membuat tagihan, mengurus surat pengantar & arsip, publish Virtual Account, dan memantau pembayaran mitra.',
                'alur' => [
                    [
                        'ikon' => 'storage',
                        'judul' => 'Siapkan sumber data',
                        'detail' => 'Tagihan berasal dari laporan manual, PAX PJP2U, konsesi, listrik, atau air.',
                        'menu' => 'Verifikasi / Layanan Jasa',
                        'rincian' => [
                            'Cek data mitra yang akan ditagihkan: NPWP, alamat, email, dan nomor WhatsApp.',
                            'Pastikan layanan dan tarif aktif sudah sesuai dengan jenis tagihan.',
                            'Untuk PJP2U/konsesi/utilitas, gunakan laporan yang sudah diverifikasi sebagai dasar tagihan.',
                        ],
                        'tips' => 'Jangan lanjut buat tagihan kalau layanan belum punya tarif atau kode akun.',
                        'video' => null,
                    ],
                    [
                        'ikon' => 'receipt_long',
                        'judul' => 'Buat tagihan',
                        'detail' => 'Pilih mitra, layanan, volume, tarif, dan dokumen dasar.',
                        'menu' => 'Tagihan › Buat Tagihan',
                        'rincian' => [
                            'Buka menu Buat Tagihan lalu pilih mitra jasa.',
                            'Pilih dokumen dasar jika tagihan terkait kontrak.',
                            'Tambahkan layanan, isi volume, tarif, satuan, dan keterangan.',
                            'Simpan draf agar sistem membuat nomor tagihan dan draft surat pengantar.',
                        ],
                        'tips' => 'Untuk Garbarata, isi rincian penerbangan agar volume per 2 jam dihitung otomatis.',
                    ],
                    [
                        'ikon' => 'description',
                        'judul' => 'Kelola draft surat',
                        'detail' => 'Sistem membuat surat pengantar dengan QR keaslian dokumen.',
                        'menu' => 'Tagihan › Detail',
                        'rincian' => [
                            'Buka detail tagihan lalu cek bagian Surat Pengantar.',
                            'Gunakan Preview Draft untuk memastikan kop, nomor surat, dan nota tagihan sudah benar.',
                            'Jika nomor/tanggal/perihal perlu diganti, edit data surat pengantar sebelum verifikasi final.',
                            'Draft yang dipreview akan tersimpan ke arsip draft.',
                        ],
                        'tips' => 'QR pada draft hanya untuk validasi dokumen, bukan QR tanda tangan final.',
                    ],
                    [
                        'ikon' => 'account_tree',
                        'judul' => 'Ikuti workflow verifikasi',
                        'detail' => 'Tagihan masuk verifikasi berjenjang; jika revisi, edit ulang lalu kirim kembali.',
                        'menu' => 'Tagihan › Log Bulanan',
                        'rincian' => [
                            'Setelah tagihan dibuat, workflow berjalan ke verifikator sesuai urutan.',
                            'Pantau status pada detail tagihan atau log tagihan bulanan.',
                            'Jika ada revisi, baca catatan verifikator lalu edit ulang data tagihan.',
                            'Kirim ulang agar tagihan kembali masuk proses verifikasi.',
                        ],
                        'tips' => 'Selesaikan catatan revisi dulu sebelum membuat draft surat ulang.',
                    ],
                    [
                        'ikon' => 'verified',
                        'judul' => 'Generate surat final TTD',
                        'detail' => 'Setelah disetujui final, surat final bertanda tangan elektronik terbentuk.',
                        'menu' => 'Tagihan › Detail',
                        'rincian' => [
                            'Tunggu sampai semua verifikator menyetujui tagihan.',
                            'Sistem membuat Surat Final TTD setelah verifikasi terakhir selesai.',
                            'Buka Lihat Surat Pengantar TTD untuk memastikan QR tanda tangan muncul.',
                            'Scan QR untuk mengecek halaman TTE dan hash dokumen.',
                        ],
                        'tips' => 'Surat final TTD tidak perlu digenerate ulang jika sudah tersedia.',
                    ],
                    [
                        'ikon' => 'send',
                        'judul' => 'Publish ke mitra',
                        'detail' => 'Sistem membuat Virtual Account dan mengirim notifikasi WhatsApp ke mitra.',
                        'menu' => 'Tagihan › Detail',
                        'rincian' => [
                            'Pastikan status workflow sudah final dan Surat Final TTD tersedia.',
                            'Klik Publish pada detail tagihan.',
                            'Sistem membuat Virtual Account dan link tagihan untuk mitra.',
                            'Cek nomor VA dan pesan notifikasi sebelum dikirimkan ke mitra.',
                        ],
                        'tips' => 'Publish dikunci jika surat final TTD belum tersedia.',
                    ],
                    [
                        'ikon' => 'payments',
                        'judul' => 'Pantau pembayaran',
                        'detail' => 'Pembayaran masuk dari VA atau ditandai lunas manual sesuai kewenangan.',
                        'menu' => 'Tagihan › Log Bulanan',
                        'rincian' => [
                            'Pantau status pembayaran dari log tagihan bulanan.',
                            'Pembayaran VA akan mengubah status setelah callback diterima.',
                            'Jika pembayaran dicatat manual, pastikan bukti setor sudah sesuai.',
                            'Cek jatuh tempo dan denda jika tagihan belum lunas.',
                        ],
                        'tips' => 'Status bayar dan status workflow adalah dua hal berbeda.',
                    ],
                    [
                        'ikon' => 'inventory_2',
                        'judul' => 'Arsip & laporan',
                        'detail' => 'Draft, final TTD, status, dan pembayaran tersimpan untuk audit.',
                        'menu' => 'Tagihan › Log Bulanan',
                        'rincian' => [
                            'Buka detail tagihan untuk melihat arsip surat pengantar.',
                            'Gunakan Log Tagihan Bulanan untuk monitoring rekap tagihan.',
                            'Export PDF/Excel jika diperlukan untuk laporan bulanan.',
                            'Pastikan arsip final TTD aktif adalah versi terbaru.',
                        ],
                        'tips' => 'Riwayat versi tetap disimpan untuk kebutuhan audit.',
                    ],
                ],
                'faq' => [
                    [
                        't' => 'Kenapa tombol Publish terkunci?',
                        'j' => 'Publish hanya aktif setelah workflow disetujui final dan Surat Final TTD sudah tersedia. Selesaikan verifikasi lalu generate surat final dulu.',
                    ],
                    [
                        't' => 'Apa beda QR draft dan QR final?',
                        'j' => 'QR pada draft hanya untuk validasi keaslian dokumen; QR pada surat final adalah tanda tangan elektronik (TTE). Scan QR final untuk melihat halaman TTE dan hash dokumen.',
                    ],
                    [
                        't' => 'Tagihan saya diminta revisi. Apa yang dilakukan?',
                        'j' => 'Status menjadi REVISI. Baca catatan verifikator, perbaiki data tagihan atau surat pengantar, lalu kirim ulang agar workflow berjalan lagi dari tahap verifikasi.',
                    ],
                    [
                        't' => 'Bagaimana denda jatuh tempo dihitung?',
                        'j' => 'Denda 2% per 30 hari (per periode, dibulatkan ke atas) dari total tagihan bila belum lunas, dan terus berjalan sampai dilunasi.',
                    ],
                ],
            ],
        ];
    }

    /**
     * Ambil panduan satu peran, atau null bila belum terdaftar.
     *
     * @return array<string, mixed>|null
     */
    public static function forRole(string $role): ?array
    {
        return static::all()[$role] ?? null;
    }
}
