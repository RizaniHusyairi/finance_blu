<?php

namespace App\Console\Commands;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;

class ExportAlurTagihanJasaMitraPdf extends Command
{
    protected $signature = 'docs:export-alur-tagihan-jasa-mitra
                            {--jenis=all : all, konsesi, pjp2u, listrik, atau air}
                            {--out-dir=docs/alur-tagihan-jasa : Folder output relatif ke base_path}';

    protected $description = 'Generate PDF alur penagihan jasa konsesi, PJP2U, listrik, dan air kepada mitra.';

    public function handle(): int
    {
        $jenis = strtolower((string) $this->option('jenis'));
        $outDir = base_path((string) $this->option('out-dir'));
        $documents = $this->documents();

        if ($jenis !== 'all' && ! isset($documents[$jenis])) {
            $this->error('Jenis tidak dikenal. Pilih: all, konsesi, pjp2u, listrik, atau air.');

            return self::FAILURE;
        }

        @mkdir($outDir, 0755, true);

        $selectedDocuments = $jenis === 'all'
            ? $documents
            : [$jenis => $documents[$jenis]];

        foreach ($selectedDocuments as $key => $document) {
            $out = $outDir . DIRECTORY_SEPARATOR . $document['filename'];

            $pdf = Pdf::loadView('docs.alur-tagihan-jasa-mitra-pdf', [
                'document' => $document,
                'jenis' => $key,
                'commonWorkflow' => $this->commonWorkflow(),
            ])
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'chroot' => base_path(),
                ]);

            file_put_contents($out, $pdf->output());

            $this->info('PDF berhasil dibuat: ' . $out);
            $this->line('Ukuran: ' . number_format(filesize($out) / 1024, 1) . ' KB');
        }

        return self::SUCCESS;
    }

    private function commonWorkflow(): array
    {
        return [
            ['step' => '1', 'role' => 'Koordinator Jasa', 'after' => 'VERIFIKASI_KASI_JASA'],
            ['step' => '2', 'role' => 'Kepala Seksi Pelayanan dan Kerjasama', 'after' => 'VERIFIKASI_KASUBAG_TU'],
            ['step' => '3', 'role' => 'Kepala Subbagian Keuangan dan Tata Usaha', 'after' => 'VERIFIKASI_KABANDARA'],
            ['step' => '4', 'role' => 'KPA / PLT/PLH', 'after' => 'DISETUJUI'],
        ];
    }

    private function documents(): array
    {
        return [
            'konsesi' => [
                'filename' => 'Alur-Tagihan-Jasa-Konsesi.pdf',
                'title' => 'Alur Penagihan Jasa Konsesi kepada Mitra',
                'subtitle' => 'Dari setup hak konsesi, laporan omzet mitra, verifikasi laporan, pembuatan tagihan, sampai pembayaran VA/BKU.',
                'entryPoint' => 'Laporan penjualan/omzet mitra pada tabel mitra_jasa_penjualan.',
                'formula' => 'Nilai tagihan = omzet x persentase konsesi layanan. Pada jalur laporan saat ini persentase diambil dari layanan, default 5% bila kosong.',
                'map' => [
                    ['no' => '01', 'phase' => 'Setup Hak', 'actor' => 'Super Admin / Koordinator', 'status' => 'konsesi aktif', 'detail' => 'Layanan konsesi dan kontrak mitra disiapkan.'],
                    ['no' => '02', 'phase' => 'Lapor Omzet', 'actor' => 'Mitra Jasa', 'status' => 'draft -> diajukan', 'detail' => 'Mitra mengisi omzet dan unggah file pendukung.'],
                    ['no' => '03', 'phase' => 'Validasi Laporan', 'actor' => 'Admin / Koordinator Jasa', 'status' => 'diverifikasi', 'detail' => 'Laporan dicek setelah bulan pelaporan berakhir.'],
                    ['no' => '04', 'phase' => 'Buat Invoice', 'actor' => 'Admin Jasa / Admin Konsesi', 'status' => 'VERIFIKASI_KOORDINATOR', 'detail' => 'Tagihan dibuat dari penjualan_id dengan nomor TAG-KONSESI.'],
                    ['no' => '05', 'phase' => 'Persetujuan', 'actor' => '4 Verifikator Jasa', 'status' => 'DISETUJUI', 'detail' => 'Koordinator, Kasi, Kasubbag, lalu KPA/PLT/PLH.'],
                    ['no' => '06', 'phase' => 'Kirim & Bayar', 'actor' => 'Admin Jasa + Mitra', 'status' => 'PUBLISHED -> LUNAS', 'detail' => 'Surat final, VA BTN, WhatsApp, piutang, dan BKU.'],
                ],
                'flow' => [
                    [
                        'actor' => 'Super Admin Jasa / Koordinator Jasa',
                        'activity' => 'Aktifkan layanan konsesi untuk mitra, hubungkan kontrak/dokumen dasar, dan simpan konfigurasi konsesi.',
                        'status' => 'mitra_jasa_konsesi.status_aktif = true',
                    ],
                    [
                        'actor' => 'Mitra Jasa',
                        'activity' => 'Input laporan omzet harian/mingguan melalui portal mitra. Beberapa detail periode digabung ke parent bulanan.',
                        'status' => 'draft',
                    ],
                    [
                        'actor' => 'Mitra Jasa',
                        'activity' => 'Submit parent laporan setelah detail lengkap dan file laporan terunggah.',
                        'status' => 'diajukan',
                    ],
                    [
                        'actor' => 'Admin Jasa / Koordinator Jasa',
                        'activity' => 'Verifikasi laporan setelah bulan pelaporan berakhir. Jika salah, laporan dikembalikan dengan catatan.',
                        'status' => 'diverifikasi atau ditolak',
                    ],
                    [
                        'actor' => 'Admin Jasa / Admin Konsesi',
                        'activity' => 'Setelah minimal satu bulan sejak submitted_at dan laporan sudah diverifikasi, buka Buat Tagihan dari penjualan_id.',
                        'status' => 'prefill TagihanJasa mode KONSESI',
                    ],
                    [
                        'actor' => 'Sistem',
                        'activity' => 'Buat TagihanJasa, detail invoice, nomor TAG-KONSESI, lalu start workflow TAGIHAN_JASA.',
                        'status' => 'VERIFIKASI_KOORDINATOR',
                    ],
                    [
                        'actor' => 'Verifikator Jasa',
                        'activity' => 'Approve berjenjang: Koordinator Jasa, Kasi Pelayanan dan Kerjasama, Kasubbag TU, lalu KPA/PLT/PLH.',
                        'status' => 'DISETUJUI atau DITOLAK',
                    ],
                    [
                        'actor' => 'Admin Jasa',
                        'activity' => 'Upload surat pengantar final TTD, publish ke mitra, buat VA BTN, kirim WhatsApp, dan sinkronkan piutang.',
                        'status' => 'PUBLISHED',
                    ],
                    [
                        'actor' => 'Mitra / BTN VA Callback',
                        'activity' => 'Mitra membayar melalui VA atau admin menandai lunas manual bila pembayaran masuk offline.',
                        'status' => 'LUNAS + BKU DEBIT_MASUK',
                    ],
                ],
                'statuses' => [
                    ['name' => 'draft', 'meaning' => 'Laporan omzet masih bisa ditambah/diedit.'],
                    ['name' => 'diajukan', 'meaning' => 'Menunggu verifikasi laporan penjualan.'],
                    ['name' => 'diverifikasi', 'meaning' => 'Laporan omzet disetujui; menunggu eligibility buat tagihan.'],
                    ['name' => 'ditagihkan', 'meaning' => 'Laporan sudah terhubung ke TagihanJasa.'],
                    ['name' => 'PUBLISHED', 'meaning' => 'Invoice resmi sudah dikirim ke mitra dengan VA.'],
                    ['name' => 'LUNAS', 'meaning' => 'Piutang dibayar dan BKU masuk tercatat.'],
                ],
                'specialNotes' => [
                    'Admin Jasa biasa tidak membuat laporan konsesi; ia memverifikasi atau membuat tagihan dari laporan yang sudah diverifikasi.',
                    'Pembuatan tagihan dari penjualan_id akan mengubah status laporan menjadi ditagihkan dan menyimpan tagihan_jasa_id.',
                    'Walaupun model konsesi menyimpan beberapa jenis skema, jalur input laporan portal saat ini memakai hitungKonsesiLayanan berbasis persentase layanan.',
                ],
                'sources' => [
                    'app/Http/Controllers/MitraJasaKonsesiController.php',
                    'app/Http/Controllers/MitraPortalController.php',
                    'app/Http/Controllers/MitraJasaPenjualanController.php',
                    'app/Services/MitraJasaKonsesiService.php',
                    'app/Http/Controllers/TagihanJasaController.php',
                    'database/seeders/WorkflowTagihanJasaSeeder.php',
                ],
            ],
            'pjp2u' => [
                'filename' => 'Alur-Tagihan-Jasa-PJP2U.pdf',
                'title' => 'Alur Penagihan Jasa PJP2U kepada Mitra',
                'subtitle' => 'Dari pemberian hak PJP2U, input pax penerbangan, verifikasi laporan, pembuatan tagihan tarif per pax, sampai pembayaran.',
                'entryPoint' => 'Laporan pax PJP2U pada mitra_jasa_penjualan dengan penerbangan_details terisi.',
                'formula' => 'Nilai tagihan = total pax x tarif_dasar layanan PJP2U.',
                'map' => [
                    ['no' => '01', 'phase' => 'Setup Hak', 'actor' => 'Super Admin / Koordinator', 'status' => 'hak PJP2U aktif', 'detail' => 'Mitra diberi hak PJP2U per layanan dan kontrak.'],
                    ['no' => '02', 'phase' => 'Input Pax', 'actor' => 'Mitra Jasa', 'status' => 'diajukan', 'detail' => 'Mitra mengisi penerbangan, pax dewasa/anak/bayi, dan file laporan.'],
                    ['no' => '03', 'phase' => 'Validasi Pax', 'actor' => 'Admin / Koordinator Jasa', 'status' => 'diverifikasi', 'detail' => 'Data pax diverifikasi setelah periode pelaporan selesai.'],
                    ['no' => '04', 'phase' => 'Buat Invoice', 'actor' => 'Admin Jasa', 'status' => 'VERIFIKASI_KOORDINATOR', 'detail' => 'Tagihan dibuat dari penjualan_id dengan qty total pax.'],
                    ['no' => '05', 'phase' => 'Persetujuan', 'actor' => '4 Verifikator Jasa', 'status' => 'DISETUJUI', 'detail' => 'Workflow TAGIHAN_JASA berjalan berjenjang.'],
                    ['no' => '06', 'phase' => 'Kirim & Bayar', 'actor' => 'Admin Jasa + Mitra', 'status' => 'PUBLISHED -> LUNAS', 'detail' => 'VA BTN dikirim via WhatsApp, lalu pembayaran masuk BKU.'],
                ],
                'flow' => [
                    [
                        'actor' => 'Super Admin Jasa / Koordinator Jasa',
                        'activity' => 'Aktifkan hak PJP2U mitra per layanan dan kontrak melalui data mitra_jasa_pjp2u.',
                        'status' => 'mitra_jasa_pjp2u.status_aktif = true',
                    ],
                    [
                        'actor' => 'Mitra Jasa',
                        'activity' => 'Input periode, daftar penerbangan, pax dewasa/anak/bayi, file laporan, dan catatan.',
                        'status' => 'diajukan',
                    ],
                    [
                        'actor' => 'Sistem',
                        'activity' => 'Hitung total pax dari detail penerbangan dan nilai tagihan awal dari tarif_dasar layanan.',
                        'status' => 'nilai_tagihan tersimpan',
                    ],
                    [
                        'actor' => 'Admin Jasa / Koordinator Jasa',
                        'activity' => 'Verifikasi laporan setelah bulan laporan berakhir. Laporan dapat ditolak dengan catatan.',
                        'status' => 'diverifikasi atau ditolak',
                    ],
                    [
                        'actor' => 'Admin Jasa',
                        'activity' => 'Setelah minimal satu bulan sejak submitted_at, buat TagihanJasa dari penjualan_id.',
                        'status' => 'prefill qty = total pax',
                    ],
                    [
                        'actor' => 'Sistem',
                        'activity' => 'Buat TagihanJasa bertipe KONSESI, mode perhitungan TARIF, nomor TAG-KONSESI, dan workflow TAGIHAN_JASA.',
                        'status' => 'VERIFIKASI_KOORDINATOR',
                    ],
                    [
                        'actor' => 'Verifikator Jasa',
                        'activity' => 'Approve berjenjang sampai KPA/PLT/PLH atau reject bila data tidak sesuai.',
                        'status' => 'DISETUJUI atau DITOLAK',
                    ],
                    [
                        'actor' => 'Admin Jasa',
                        'activity' => 'Upload surat pengantar final, publish tagihan, generate VA, dan kirim notifikasi WhatsApp.',
                        'status' => 'PUBLISHED',
                    ],
                    [
                        'actor' => 'Mitra / BTN VA Callback',
                        'activity' => 'Pembayaran VA mengubah invoice menjadi lunas, sinkron piutang, dan mencatat BKU.',
                        'status' => 'LUNAS',
                    ],
                ],
                'statuses' => [
                    ['name' => 'diajukan', 'meaning' => 'Laporan pax terkirim dari portal mitra.'],
                    ['name' => 'diverifikasi', 'meaning' => 'Laporan pax disetujui dan bisa menjadi dasar tagihan setelah periode tunggu.'],
                    ['name' => 'ditolak', 'meaning' => 'Laporan perlu diperbaiki oleh mitra.'],
                    ['name' => 'ditagihkan', 'meaning' => 'Laporan sudah dipakai untuk TagihanJasa.'],
                    ['name' => 'PUBLISHED', 'meaning' => 'Invoice PJP2U sudah dikirim ke mitra.'],
                    ['name' => 'LUNAS', 'meaning' => 'Pembayaran diterima dan BKU tercatat.'],
                ],
                'specialNotes' => [
                    'PJP2U dibedakan dari konsesi omzet dengan kolom penerbangan_details yang terisi.',
                    'Walaupun dibuat dari jalur penjualan_id, perhitungan PJP2U memakai TARIF, bukan PERSENTASE.',
                    'Hak PJP2U harus aktif; bila tidak, portal mitra menolak input pax.',
                ],
                'sources' => [
                    'app/Http/Controllers/MitraJasaPjp2uController.php',
                    'app/Http/Controllers/MitraPortalController.php',
                    'app/Http/Controllers/MitraJasaPenjualanController.php',
                    'app/Models/MitraJasaPenjualan.php',
                    'app/Http/Controllers/TagihanJasaController.php',
                    'database/seeders/WorkflowTagihanJasaSeeder.php',
                ],
            ],
            'listrik' => [
                'filename' => 'Alur-Tagihan-Jasa-Listrik.pdf',
                'title' => 'Alur Penagihan Jasa Listrik kepada Mitra',
                'subtitle' => 'Dari pencatatan meter/pemakaian listrik oleh Admin Listrik, validasi Admin Jasa, pembuatan tagihan, sampai pembayaran.',
                'entryPoint' => 'LaporanUtilitas dengan jenis = listrik.',
                'formula' => 'Total biaya = pemakaian kWh x tarif per unit yang diisi Admin Jasa pada form tagihan.',
                'map' => [
                    ['no' => '01', 'phase' => 'Catat Meter', 'actor' => 'Admin Listrik', 'status' => 'draft', 'detail' => 'Stan awal/akhir atau pemakaian flat dicatat dengan bukti.'],
                    ['no' => '02', 'phase' => 'Kirim Laporan', 'actor' => 'Admin Listrik', 'status' => 'dikirim_ke_admin_jasa', 'detail' => 'Laporan periode listrik dikirim ke Admin Jasa.'],
                    ['no' => '03', 'phase' => 'Review Tarif', 'actor' => 'Admin Jasa', 'status' => 'prefill utilitas_id', 'detail' => 'Pemakaian dicek, tarif per unit diisi pada form tagihan.'],
                    ['no' => '04', 'phase' => 'Buat Invoice', 'actor' => 'Sistem', 'status' => 'VERIFIKASI_KOORDINATOR', 'detail' => 'TagihanJasa dibuat dan laporan utilitas ditautkan.'],
                    ['no' => '05', 'phase' => 'Persetujuan', 'actor' => '4 Verifikator Jasa', 'status' => 'DISETUJUI', 'detail' => 'Workflow jasa memastikan tagihan layak dipublish.'],
                    ['no' => '06', 'phase' => 'Kirim & Bayar', 'actor' => 'Admin Jasa + Mitra', 'status' => 'PUBLISHED -> LUNAS', 'detail' => 'Invoice dikirim, VA dibayar, piutang dan BKU diperbarui.'],
                ],
                'flow' => [
                    [
                        'actor' => 'Admin Listrik',
                        'activity' => 'Pilih mitra yang berlangganan layanan listrik aktif, lalu input periode laporan.',
                        'status' => 'draft',
                    ],
                    [
                        'actor' => 'Admin Listrik',
                        'activity' => 'Untuk tipe kwh, input stan awal dan stan akhir plus foto bukti awal/akhir. Untuk flat, input pemakaian manual.',
                        'status' => 'pemakaian dihitung',
                    ],
                    [
                        'actor' => 'Sistem',
                        'activity' => 'Cegah duplikasi laporan untuk mitra, layanan, bulan, dan tahun yang sama.',
                        'status' => 'validasi overlap',
                    ],
                    [
                        'actor' => 'Admin Listrik',
                        'activity' => 'Submit laporan ke Admin Jasa setelah data dan bukti lengkap.',
                        'status' => 'dikirim_ke_admin_jasa',
                    ],
                    [
                        'actor' => 'Admin Jasa',
                        'activity' => 'Review laporan listrik. Jika valid, buka Buat Tagihan dari utilitas_id; jika tidak, tolak dengan catatan.',
                        'status' => 'prefill TagihanJasa atau ditolak',
                    ],
                    [
                        'actor' => 'Admin Jasa',
                        'activity' => 'Isi tarif per unit pada form Tagihan Jasa; qty sudah terisi dari pemakaian laporan.',
                        'status' => 'total_tagihan dihitung',
                    ],
                    [
                        'actor' => 'Sistem',
                        'activity' => 'Buat TagihanJasa tipe FUNGSI, start workflow TAGIHAN_JASA, dan update laporan utilitas menjadi ditagihkan.',
                        'status' => 'VERIFIKASI_KOORDINATOR',
                    ],
                    [
                        'actor' => 'Verifikator Jasa',
                        'activity' => 'Approve berjenjang Koordinator, Kasi, Kasubbag, lalu KPA/PLT/PLH.',
                        'status' => 'DISETUJUI atau DITOLAK',
                    ],
                    [
                        'actor' => 'Admin Jasa / Mitra',
                        'activity' => 'Upload surat pengantar TTD, publish VA/WA ke mitra, mitra bayar, lalu piutang dan BKU disinkronkan.',
                        'status' => 'PUBLISHED lalu LUNAS',
                    ],
                ],
                'statuses' => [
                    ['name' => 'draft', 'meaning' => 'Laporan listrik masih di Admin Listrik.'],
                    ['name' => 'dikirim_ke_admin_jasa', 'meaning' => 'Menunggu review Admin Jasa.'],
                    ['name' => 'ditolak', 'meaning' => 'Dikembalikan ke Admin Listrik dengan catatan.'],
                    ['name' => 'ditagihkan', 'meaning' => 'Sudah dibuatkan TagihanJasa.'],
                    ['name' => 'PUBLISHED', 'meaning' => 'Invoice listrik sudah dikirim ke mitra.'],
                    ['name' => 'LUNAS', 'meaning' => 'Pembayaran diterima dan tercatat di BKU.'],
                ],
                'specialNotes' => [
                    'View utilitas saat ini mengarahkan tombol Buat Tagihan ke TagihanJasaController dengan utilitas_id, sehingga mengikuti workflow verifikasi normal.',
                    'Ada method AdminJasaUtilitasController::buatTagihan yang membuat tagihan langsung PUBLISHED, tetapi jalur UI utama memakai prefill TagihanJasa.',
                    'Untuk tipe kwh, pemakaian = stan_akhir - stan_awal; untuk flat, pemakaian berasal dari input manual.',
                ],
                'sources' => [
                    'app/Http/Controllers/UtilitasController.php',
                    'app/Http/Controllers/AdminJasaUtilitasController.php',
                    'app/Models/LaporanUtilitas.php',
                    'app/Http/Controllers/TagihanJasaController.php',
                    'resources/views/super_admin_jasa/mitra/utilitas-index.blade.php',
                    'database/seeders/WorkflowTagihanJasaSeeder.php',
                ],
            ],
            'air' => [
                'filename' => 'Alur-Tagihan-Jasa-Air.pdf',
                'title' => 'Alur Penagihan Jasa Air kepada Mitra',
                'subtitle' => 'Dari pencatatan pemakaian air oleh Admin Air, review Admin Jasa, pembuatan tagihan, sampai pembayaran mitra.',
                'entryPoint' => 'LaporanUtilitas dengan jenis = air.',
                'formula' => 'Total biaya = pemakaian m3 x tarif per unit yang diisi Admin Jasa pada form tagihan.',
                'map' => [
                    ['no' => '01', 'phase' => 'Catat Pakai', 'actor' => 'Admin Air', 'status' => 'draft', 'detail' => 'Pemakaian air per periode dicatat dari meter atau input flat.'],
                    ['no' => '02', 'phase' => 'Kirim Laporan', 'actor' => 'Admin Air', 'status' => 'dikirim_ke_admin_jasa', 'detail' => 'Laporan air dikirim ke Admin Jasa untuk ditagihkan.'],
                    ['no' => '03', 'phase' => 'Review Tarif', 'actor' => 'Admin Jasa', 'status' => 'prefill utilitas_id', 'detail' => 'Data pemakaian dicek dan tarif per unit diisi.'],
                    ['no' => '04', 'phase' => 'Buat Invoice', 'actor' => 'Sistem', 'status' => 'VERIFIKASI_KOORDINATOR', 'detail' => 'TagihanJasa dibuat dari utilitas_id dan masuk workflow.'],
                    ['no' => '05', 'phase' => 'Persetujuan', 'actor' => '4 Verifikator Jasa', 'status' => 'DISETUJUI', 'detail' => 'Koordinator sampai KPA/PLT/PLH memberi approval.'],
                    ['no' => '06', 'phase' => 'Kirim & Bayar', 'actor' => 'Admin Jasa + Mitra', 'status' => 'PUBLISHED -> LUNAS', 'detail' => 'VA/WhatsApp dikirim dan pembayaran masuk BKU.'],
                ],
                'flow' => [
                    [
                        'actor' => 'Admin Air',
                        'activity' => 'Pilih mitra yang memiliki layanan air aktif, lalu input bulan dan tahun pemakaian.',
                        'status' => 'draft',
                    ],
                    [
                        'actor' => 'Admin Air',
                        'activity' => 'Input stan awal/akhir beserta bukti bila memakai tipe kwh/meter, atau input pemakaian manual untuk tipe flat.',
                        'status' => 'pemakaian dihitung',
                    ],
                    [
                        'actor' => 'Sistem',
                        'activity' => 'Validasi tidak ada laporan air ganda untuk mitra, layanan, bulan, dan tahun yang sama.',
                        'status' => 'validasi overlap',
                    ],
                    [
                        'actor' => 'Admin Air',
                        'activity' => 'Kirim laporan pemakaian air ke Admin Jasa untuk proses penagihan.',
                        'status' => 'dikirim_ke_admin_jasa',
                    ],
                    [
                        'actor' => 'Admin Jasa',
                        'activity' => 'Review laporan air. Jika valid, buat tagihan dari utilitas_id; jika tidak, tolak dengan catatan.',
                        'status' => 'prefill TagihanJasa atau ditolak',
                    ],
                    [
                        'actor' => 'Admin Jasa',
                        'activity' => 'Isi tarif per unit pada form Tagihan Jasa. Qty sudah terisi dari pemakaian air.',
                        'status' => 'total_tagihan dihitung',
                    ],
                    [
                        'actor' => 'Sistem',
                        'activity' => 'Buat TagihanJasa tipe FUNGSI, start workflow TAGIHAN_JASA, lalu update laporan air menjadi ditagihkan.',
                        'status' => 'VERIFIKASI_KOORDINATOR',
                    ],
                    [
                        'actor' => 'Verifikator Jasa',
                        'activity' => 'Approve berjenjang sampai KPA/PLT/PLH, atau reject bila dokumen/tarif tidak valid.',
                        'status' => 'DISETUJUI atau DITOLAK',
                    ],
                    [
                        'actor' => 'Admin Jasa / Mitra',
                        'activity' => 'Upload surat pengantar TTD, publish ke mitra dengan VA, lalu pembayaran mencatat piutang dan BKU.',
                        'status' => 'PUBLISHED lalu LUNAS',
                    ],
                ],
                'statuses' => [
                    ['name' => 'draft', 'meaning' => 'Laporan air masih bisa diedit/dihapus.'],
                    ['name' => 'dikirim_ke_admin_jasa', 'meaning' => 'Menunggu review Admin Jasa.'],
                    ['name' => 'ditolak', 'meaning' => 'Dikembalikan ke Admin Air dengan catatan.'],
                    ['name' => 'ditagihkan', 'meaning' => 'Sudah dibuatkan TagihanJasa.'],
                    ['name' => 'PUBLISHED', 'meaning' => 'Invoice air sudah dikirim ke mitra.'],
                    ['name' => 'LUNAS', 'meaning' => 'Pembayaran diterima dan tercatat di BKU.'],
                ],
                'specialNotes' => [
                    'Alur air memakai controller dan tabel yang sama dengan listrik; pembeda utamanya adalah jenis = air dan layanan yang dicari dari master Layanan Jasa.',
                    'Satuan fallback di form prefill adalah m3 bila layanan tidak punya satuan.',
                    'Seperti listrik, jalur UI utama melewati TagihanJasaController agar tetap masuk verifikasi berjenjang sebelum publish.',
                ],
                'sources' => [
                    'app/Http/Controllers/UtilitasController.php',
                    'app/Http/Controllers/AdminJasaUtilitasController.php',
                    'app/Models/LaporanUtilitas.php',
                    'app/Http/Controllers/TagihanJasaController.php',
                    'resources/views/super_admin_jasa/mitra/utilitas-show.blade.php',
                    'database/seeders/WorkflowTagihanJasaSeeder.php',
                ],
            ],
        ];
    }
}
