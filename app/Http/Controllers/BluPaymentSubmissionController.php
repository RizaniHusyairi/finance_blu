<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class BluPaymentSubmissionController extends Controller
{
    public function index(): View
    {
        $submissions = $this->submissions();

        $stats = [
            [
                'label' => 'Total Pengajuan',
                'count' => $submissions->count(),
                'icon' => 'receipt_long',
                'color' => 'primary',
                'note' => 'Seluruh pengajuan BLU',
            ],
            [
                'label' => 'Draft',
                'count' => $submissions->where('status', 'Draft')->count(),
                'icon' => 'edit_note',
                'color' => 'warning',
                'note' => 'Belum dikirim verifikasi',
            ],
            [
                'label' => 'Menunggu Verifikasi',
                'count' => $submissions->where('status', 'Menunggu Verifikasi')->count(),
                'icon' => 'fact_check',
                'color' => 'info',
                'note' => 'Perlu pemeriksaan awal',
            ],
            [
                'label' => 'Disetujui',
                'count' => $submissions->where('status', 'Disetujui')->count(),
                'icon' => 'task_alt',
                'color' => 'success',
                'note' => 'Siap proses pencairan',
            ],
            [
                'label' => 'Ditolak / Revisi',
                'count' => $submissions->filter(fn (array $item) => in_array($item['status'], ['Direvisi', 'Ditolak'], true))->count(),
                'icon' => 'rule',
                'color' => 'danger',
                'note' => 'Butuh tindak lanjut operator',
            ],
            [
                'label' => 'Sudah Cair',
                'count' => $submissions->where('status', 'Sudah Cair')->count(),
                'icon' => 'account_balance',
                'color' => 'secondary',
                'note' => 'Dana telah ditransfer',
            ],
        ];

        $warningSummary = $submissions
            ->flatMap(fn (array $item) => collect($item['alerts'])->map(function (array $alert) use ($item) {
                return [
                    'submission_number' => $item['submission_number'],
                    'title' => $alert['title'],
                    'message' => $alert['message'],
                    'type' => $alert['type'],
                ];
            }))
            ->take(3)
            ->values();

        return view('blu-payment-submissions.index', [
            'submissions' => $submissions,
            'stats' => $stats,
            'warningSummary' => $warningSummary,
        ]);
    }

    public function show(string $submissionNumber): View
    {
        $submission = $this->submissions()
            ->firstWhere('submission_number', $submissionNumber);

        abort_unless($submission, 404);

        return view('blu-payment-submissions.show', [
            'submission' => $submission,
            'statusClasses' => $this->statusClasses(),
            'alertClasses' => $this->alertClasses(),
        ]);
    }

    private function submissions(): Collection
    {
        return collect($this->dummySubmissions())->map(function (array $submission) {
            $submission['tax_total'] = $submission['ppn'] + $submission['pph'];
            $submission['net_amount'] = $submission['gross_amount'] - $submission['tax_total'] - $submission['penalty'];
            $submission['status_key'] = strtolower(str_replace(' ', '-', $submission['status']));
            $submission['is_final'] = in_array($submission['status'], ['Ditolak', 'Disetujui', 'Sudah Cair'], true);
            $submission['can_submit'] = $submission['status'] === 'Draft';
            $submission['can_cancel'] = ! in_array($submission['status'], ['Ditolak', 'Sudah Cair'], true);

            return $submission;
        });
    }

    private function statusClasses(): array
    {
        return [
            'Draft' => 'warning text-dark',
            'Menunggu Verifikasi' => 'info text-dark',
            'Menunggu Persetujuan' => 'primary',
            'Direvisi' => 'warning text-dark',
            'Ditolak' => 'danger',
            'Disetujui' => 'success',
            'Proses SP2D' => 'secondary',
            'Sudah Cair' => 'success',
        ];
    }

    private function alertClasses(): array
    {
        return [
            'warning' => 'warning',
            'danger' => 'danger',
            'success' => 'success',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function dummySubmissions(): array
    {
        return [
            [
                'submission_number' => 'PGJ-BLU-2026-0012',
                'date' => '2026-03-15',
                'date_label' => '15 Maret 2026',
                'npi_number' => 'NPI-2026-0012',
                'title' => 'Pembayaran Termin 2 Pemeliharaan Gedung Terminal',
                'supplier' => 'PT. GARUDA TAWAKAL ABADI',
                'supplier_short' => 'PT. GARUDA TAWAKAL ABADI',
                'payment_type' => 'Belanja Jasa',
                'contract_type' => 'Kontrak',
                'contract_number' => '027/SPK-BLU/2026',
                'contract_term_label' => 'Termin 2/3',
                'gross_amount' => 350000000,
                'ppn' => 35000000,
                'pph' => 3500000,
                'penalty' => 0,
                'status' => 'Menunggu Persetujuan',
                'operator' => 'RANI APRILIA',
                'ppk' => 'GUNAWAN',
                'ppspm' => 'ACHMAD RIZAL',
                'bast_date' => '13 Maret 2026',
                'payment_method' => 'Termin',
                'payment_note' => 'Pembayaran termin kedua berdasarkan progres pekerjaan 68%.',
                'disbursement_status' => 'Belum Diproses',
                'sp2d_number' => '-',
                'sp2d_date' => '-',
                'transfer_date' => '-',
                'disbursement_note' => 'Menunggu persetujuan PPSPM sebelum penerbitan SPM.',
                'contract_info' => [
                    'number' => '027/SPK-BLU/2026',
                    'description' => 'Pemeliharaan gedung terminal penumpang tahap lanjutan.',
                    'total_amount' => 1050000000,
                    'previous_realization' => 350000000,
                    'remaining' => 350000000,
                    'status' => 'Active',
                ],
                'documents' => [
                    ['name' => 'kontrak.pdf', 'size' => '1,8 MB', 'status' => 'Lengkap'],
                    ['name' => 'bast-termin-2.pdf', 'size' => '512 KB', 'status' => 'Lengkap'],
                    ['name' => 'invoice.pdf', 'size' => '420 KB', 'status' => 'Lengkap'],
                    ['name' => 'billing-pajak.pdf', 'size' => '276 KB', 'status' => 'Lengkap'],
                    ['name' => 'bukti-potong.pdf', 'size' => '295 KB', 'status' => 'Lengkap'],
                ],
                'timeline' => $this->timeline([
                    ['step' => 'Draft', 'role' => 'Operator BLU', 'state' => 'Selesai', 'datetime' => '15 Mar 2026, 08:15', 'note' => 'Draft pengajuan dibuat.'],
                    ['step' => 'Verifikasi', 'role' => 'PPABP', 'state' => 'Selesai', 'datetime' => '15 Mar 2026, 09:40', 'note' => 'Dokumen dan akun belanja sesuai.'],
                    ['step' => 'Persetujuan', 'role' => 'PPK', 'state' => 'Selesai', 'datetime' => '15 Mar 2026, 11:10', 'note' => 'Disetujui untuk diteruskan ke PPSPM.'],
                    ['step' => 'SPM / SPP', 'role' => 'PPSPM', 'state' => 'Menunggu', 'datetime' => '-', 'note' => 'Menunggu persetujuan PPSPM.'],
                    ['step' => 'SP2D', 'role' => 'Bendahara Pengeluaran', 'state' => 'Belum', 'datetime' => '-', 'note' => null],
                    ['step' => 'Cair', 'role' => 'Bank Operasional', 'state' => 'Belum', 'datetime' => '-', 'note' => null],
                ]),
                'alerts' => [
                    ['type' => 'warning', 'title' => 'Pencairan belum diproses', 'message' => 'SPM belum diterbitkan karena masih menunggu persetujuan akhir PPSPM.'],
                ],
            ],
            [
                'submission_number' => 'PGJ-BLU-2026-0011',
                'date' => '2026-03-14',
                'date_label' => '14 Maret 2026',
                'npi_number' => 'NPI-2026-0011',
                'title' => 'Pembayaran Pengadaan CCTV Area Operasional',
                'supplier' => 'PT. JAYA KENCANA',
                'supplier_short' => 'PT. JAYA KENCANA',
                'payment_type' => 'Belanja Barang',
                'contract_type' => 'Kontrak',
                'contract_number' => '031/SPK-BLU/2026',
                'contract_term_label' => 'Termin 1/2',
                'gross_amount' => 225000000,
                'ppn' => 22500000,
                'pph' => 2250000,
                'penalty' => 0,
                'status' => 'Menunggu Verifikasi',
                'operator' => 'RANI APRILIA',
                'ppk' => 'GUNAWAN',
                'ppspm' => 'ACHMAD RIZAL',
                'bast_date' => '12 Maret 2026',
                'payment_method' => 'Termin',
                'payment_note' => 'Termin pertama untuk pengadaan dan pemasangan CCTV bandara.',
                'disbursement_status' => 'Belum Diproses',
                'sp2d_number' => '-',
                'sp2d_date' => '-',
                'transfer_date' => '-',
                'disbursement_note' => 'Pengajuan baru masuk tahap pemeriksaan PPABP.',
                'contract_info' => [
                    'number' => '031/SPK-BLU/2026',
                    'description' => 'Pengadaan dan pemasangan CCTV area operasional utama.',
                    'total_amount' => 485000000,
                    'previous_realization' => 0,
                    'remaining' => 260000000,
                    'status' => 'Active',
                ],
                'documents' => [
                    ['name' => 'kontrak-cctv.pdf', 'size' => '2,1 MB', 'status' => 'Lengkap'],
                    ['name' => 'bast-pemasangan.pdf', 'size' => '648 KB', 'status' => 'Lengkap'],
                    ['name' => 'invoice-cctv.pdf', 'size' => '385 KB', 'status' => 'Lengkap'],
                    ['name' => 'faktur.pdf', 'size' => '188 KB', 'status' => 'Lengkap'],
                    ['name' => 'billing-pajak.pdf', 'size' => '240 KB', 'status' => 'Lengkap'],
                ],
                'timeline' => $this->timeline([
                    ['step' => 'Draft', 'role' => 'Operator BLU', 'state' => 'Selesai', 'datetime' => '14 Mar 2026, 13:20', 'note' => 'Draft dilengkapi seluruh dokumen.'],
                    ['step' => 'Verifikasi', 'role' => 'PPABP', 'state' => 'Menunggu', 'datetime' => '-', 'note' => 'Menunggu review kelengkapan.'],
                    ['step' => 'Persetujuan', 'role' => 'PPK', 'state' => 'Belum', 'datetime' => '-', 'note' => null],
                    ['step' => 'SPM / SPP', 'role' => 'PPSPM', 'state' => 'Belum', 'datetime' => '-', 'note' => null],
                    ['step' => 'SP2D', 'role' => 'Bendahara Pengeluaran', 'state' => 'Belum', 'datetime' => '-', 'note' => null],
                    ['step' => 'Cair', 'role' => 'Bank Operasional', 'state' => 'Belum', 'datetime' => '-', 'note' => null],
                ]),
                'alerts' => [
                    ['type' => 'success', 'title' => 'Dokumen lengkap', 'message' => 'Seluruh dokumen pendukung telah diunggah dan siap diverifikasi.'],
                ],
            ],
            [
                'submission_number' => 'PGJ-BLU-2026-0010',
                'date' => '2026-03-12',
                'date_label' => '12 Maret 2026',
                'npi_number' => null,
                'title' => 'Pembayaran Honorarium Narasumber Sosialisasi Layanan BLU',
                'supplier' => 'CV. MAHAKAM RAYA HARMONI',
                'supplier_short' => 'CV. MAHAKAM RAYA HARMONI',
                'payment_type' => 'Belanja Jasa',
                'contract_type' => 'Non-Kontrak',
                'contract_number' => null,
                'contract_term_label' => null,
                'gross_amount' => 58000000,
                'ppn' => 0,
                'pph' => 2900000,
                'penalty' => 0,
                'status' => 'Draft',
                'operator' => 'FIRMAN HIDAYAT',
                'ppk' => 'GUNAWAN',
                'ppspm' => 'ACHMAD RIZAL',
                'bast_date' => '11 Maret 2026',
                'payment_method' => 'Sekali Bayar',
                'payment_note' => 'Belanja jasa non-kontrak untuk kegiatan sosialisasi internal BLU.',
                'disbursement_status' => 'Belum Diproses',
                'sp2d_number' => '-',
                'sp2d_date' => '-',
                'transfer_date' => '-',
                'disbursement_note' => 'Belum diajukan untuk proses verifikasi.',
                'contract_info' => null,
                'documents' => [
                    ['name' => 'bast-kegiatan.pdf', 'size' => '396 KB', 'status' => 'Lengkap'],
                    ['name' => 'invoice-honorarium.pdf', 'size' => '228 KB', 'status' => 'Lengkap'],
                    ['name' => 'billing-pph.pdf', 'size' => '144 KB', 'status' => 'Lengkap'],
                    ['name' => 'surat-tugas.pdf', 'size' => '332 KB', 'status' => 'Lengkap'],
                ],
                'timeline' => $this->timeline([
                    ['step' => 'Draft', 'role' => 'Operator BLU', 'state' => 'Berjalan', 'datetime' => '12 Mar 2026, 15:05', 'note' => 'Draft masih menunggu nomor NPI.'],
                    ['step' => 'Verifikasi', 'role' => 'PPABP', 'state' => 'Belum', 'datetime' => '-', 'note' => null],
                    ['step' => 'Persetujuan', 'role' => 'PPK', 'state' => 'Belum', 'datetime' => '-', 'note' => null],
                    ['step' => 'SPM / SPP', 'role' => 'PPSPM', 'state' => 'Belum', 'datetime' => '-', 'note' => null],
                    ['step' => 'SP2D', 'role' => 'Bendahara Pengeluaran', 'state' => 'Belum', 'datetime' => '-', 'note' => null],
                    ['step' => 'Cair', 'role' => 'Bank Operasional', 'state' => 'Belum', 'datetime' => '-', 'note' => null],
                ]),
                'alerts' => [
                    ['type' => 'warning', 'title' => 'Pajak belum dihitung penuh', 'message' => 'Komponen PPN belum terisi. Pastikan jenis belanja tidak menimbulkan PPN sebelum dikirim.'],
                ],
            ],
            [
                'submission_number' => 'PGJ-BLU-2026-0009',
                'date' => '2026-03-10',
                'date_label' => '10 Maret 2026',
                'npi_number' => 'NPI-2026-0009',
                'title' => 'Pembayaran Termin 3 Pekerjaan Perluasan Halaman Parkir PKP-PK',
                'supplier' => 'CV. ANUGERAH ZIKIR KELUARGA UTAMA',
                'supplier_short' => 'CV. ANUGERAH ZIKIR KELUARGA UTAMA',
                'payment_type' => 'Belanja Jasa',
                'contract_type' => 'Kontrak',
                'contract_number' => '040/SPK-BLU/2026',
                'contract_term_label' => 'Termin 3/3',
                'gross_amount' => 300000000,
                'ppn' => 30000000,
                'pph' => 3000000,
                'penalty' => 15000000,
                'status' => 'Direvisi',
                'operator' => 'RANI APRILIA',
                'ppk' => 'GUNAWAN',
                'ppspm' => 'ACHMAD RIZAL',
                'bast_date' => '08 Maret 2026',
                'payment_method' => 'Termin',
                'payment_note' => 'Termin akhir dengan koreksi denda keterlambatan penyelesaian 5 hari kalender.',
                'disbursement_status' => 'Belum Diproses',
                'sp2d_number' => '-',
                'sp2d_date' => '-',
                'transfer_date' => '-',
                'disbursement_note' => 'Perlu perbaikan nilai termin agar sesuai sisa kontrak.',
                'contract_info' => [
                    'number' => '040/SPK-BLU/2026',
                    'description' => 'Perluasan halaman parkir kendaraan PKP-PK.',
                    'total_amount' => 875000000,
                    'previous_realization' => 620000000,
                    'remaining' => 255000000,
                    'status' => 'Active',
                ],
                'documents' => [
                    ['name' => 'kontrak-pkppk.pdf', 'size' => '1,5 MB', 'status' => 'Lengkap'],
                    ['name' => 'bast-termin-3.pdf', 'size' => '604 KB', 'status' => 'Lengkap'],
                    ['name' => 'invoice-final.pdf', 'size' => '301 KB', 'status' => 'Lengkap'],
                    ['name' => 'faktur-ppn.pdf', 'size' => '189 KB', 'status' => 'Lengkap'],
                    ['name' => 'billing-pajak-final.pdf', 'size' => '167 KB', 'status' => 'Kurang'],
                ],
                'timeline' => $this->timeline([
                    ['step' => 'Draft', 'role' => 'Operator BLU', 'state' => 'Selesai', 'datetime' => '10 Mar 2026, 08:50', 'note' => 'Draft pengajuan termin akhir telah dibuat.'],
                    ['step' => 'Verifikasi', 'role' => 'PPABP', 'state' => 'Revisi', 'datetime' => '10 Mar 2026, 10:25', 'note' => 'Nilai termin melebihi sisa kontrak dan billing pajak belum final.'],
                    ['step' => 'Persetujuan', 'role' => 'PPK', 'state' => 'Belum', 'datetime' => '-', 'note' => null],
                    ['step' => 'SPM / SPP', 'role' => 'PPSPM', 'state' => 'Belum', 'datetime' => '-', 'note' => null],
                    ['step' => 'SP2D', 'role' => 'Bendahara Pengeluaran', 'state' => 'Belum', 'datetime' => '-', 'note' => null],
                    ['step' => 'Cair', 'role' => 'Bank Operasional', 'state' => 'Belum', 'datetime' => '-', 'note' => null],
                ]),
                'alerts' => [
                    ['type' => 'danger', 'title' => 'Nilai termin melebihi sisa kontrak', 'message' => 'Nilai pengajuan Rp300.000.000 melebihi sisa kontrak Rp255.000.000.'],
                    ['type' => 'warning', 'title' => 'Dokumen pajak belum lengkap', 'message' => 'Billing pajak final masih perlu pembaruan sebelum dikirim ulang.'],
                ],
            ],
            [
                'submission_number' => 'PGJ-BLU-2026-0008',
                'date' => '2026-03-08',
                'date_label' => '8 Maret 2026',
                'npi_number' => 'NPI-2026-0008',
                'title' => 'Pembayaran Pengadaan Peralatan Pendukung Ruang Server',
                'supplier' => 'CV. GUNA AKHLAK SUKSES',
                'supplier_short' => 'CV. GUNA AKHLAK SUKSES',
                'payment_type' => 'Belanja Barang',
                'contract_type' => 'Non-Kontrak',
                'contract_number' => null,
                'contract_term_label' => null,
                'gross_amount' => 148000000,
                'ppn' => 14800000,
                'pph' => 2960000,
                'penalty' => 0,
                'status' => 'Sudah Cair',
                'operator' => 'FIRMAN HIDAYAT',
                'ppk' => 'GUNAWAN',
                'ppspm' => 'ACHMAD RIZAL',
                'bast_date' => '05 Maret 2026',
                'payment_method' => 'Sekali Bayar',
                'payment_note' => 'Pembayaran pengadaan non-kontrak untuk penguatan infrastruktur TI.',
                'disbursement_status' => 'Sudah Cair',
                'sp2d_number' => 'SP2D-BLU-2026-0145',
                'sp2d_date' => '11 Maret 2026',
                'transfer_date' => '12 Maret 2026',
                'disbursement_note' => 'Dana telah ditransfer ke rekening penyedia.',
                'contract_info' => null,
                'documents' => [
                    ['name' => 'invoice-server.pdf', 'size' => '280 KB', 'status' => 'Lengkap'],
                    ['name' => 'bast-server.pdf', 'size' => '340 KB', 'status' => 'Lengkap'],
                    ['name' => 'faktur-pajak.pdf', 'size' => '176 KB', 'status' => 'Lengkap'],
                    ['name' => 'bukti-potong.pdf', 'size' => '158 KB', 'status' => 'Lengkap'],
                ],
                'timeline' => $this->timeline([
                    ['step' => 'Draft', 'role' => 'Operator BLU', 'state' => 'Selesai', 'datetime' => '08 Mar 2026, 09:00', 'note' => 'Draft disusun lengkap.'],
                    ['step' => 'Verifikasi', 'role' => 'PPABP', 'state' => 'Selesai', 'datetime' => '08 Mar 2026, 10:15', 'note' => 'Verifikasi dokumen selesai.'],
                    ['step' => 'Persetujuan', 'role' => 'PPK', 'state' => 'Selesai', 'datetime' => '09 Mar 2026, 08:20', 'note' => 'Disetujui PPK dan PPSPM.'],
                    ['step' => 'SPM / SPP', 'role' => 'PPSPM', 'state' => 'Selesai', 'datetime' => '09 Mar 2026, 13:10', 'note' => 'SPM diterbitkan.'],
                    ['step' => 'SP2D', 'role' => 'Bendahara Pengeluaran', 'state' => 'Selesai', 'datetime' => '11 Mar 2026, 10:30', 'note' => 'SP2D diterbitkan.'],
                    ['step' => 'Cair', 'role' => 'Bank Operasional', 'state' => 'Selesai', 'datetime' => '12 Mar 2026, 15:45', 'note' => 'Dana masuk ke rekening penyedia.'],
                ]),
                'alerts' => [
                    ['type' => 'success', 'title' => 'Pencairan berhasil', 'message' => 'Pengajuan telah selesai hingga tahap transfer dana.'],
                ],
            ],
        ];
    }

    /**
     * @param  array<int, array<string, string|null>>  $steps
     * @return array<int, array<string, string|null>>
     */
    private function timeline(array $steps): array
    {
        return $steps;
    }
}
