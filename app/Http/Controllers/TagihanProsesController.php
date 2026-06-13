<?php

namespace App\Http\Controllers;

use App\Models\DetailDipa;
use App\Models\DokumenNpi;
use App\Models\DokumenSp2d;
use App\Models\DokumenSpm;
use App\Models\DokumenSpp;
use App\Models\MasterTarifPajak;
use App\Models\PotonganTagihan;
use App\Models\Tagihan;
use App\Models\WorkflowApproval;
use App\Services\DokumenChainService;
use App\Services\PerjaldinKomponenService;
use App\Services\WorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Halaman Proses Tagihan terpadu: seluruh rantai SPP/SPM/NPI/SP2D
 * (pembuatan draft, pengajuan, verifikasi, bukti transfer, pajak, pembukuan)
 * dikerjakan semua role dari satu halaman per tagihan.
 */
class TagihanProsesController extends Controller
{
    public function __construct(
        private DokumenChainService $chain,
        private WorkflowService $workflow,
    ) {
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Tagihan::query()
            ->where(function ($q) {
                $q->whereIn('status', array_merge(DokumenChainService::TAGIHAN_READY_STATUSES, ['SELESAI']))
                    ->orWhereHas('spps');
            })
            ->with([
                'pihak',
                'detailKontrak.kontrakTermin.kontrak.vendor',
                'spps' => fn ($q) => $q->latest('id'),
                'spps.spm.npi.sp2d',
            ]);

        if ($request->filled('tipe')) {
            $query->where('tipe_tagihan', strtoupper($request->input('tipe')));
        }

        if ($request->filled('search')) {
            $kw = $request->input('search');
            $query->where(function ($q) use ($kw) {
                $q->where('nomor_tagihan', 'like', "%{$kw}%")
                    ->orWhere('deskripsi', 'like', "%{$kw}%")
                    ->orWhereHas('pihak', fn ($p) => $p->where('nama_pihak', 'like', "%{$kw}%"));
            });
        }

        // Ringkasan agregat (mengikuti filter pencarian/tipe) untuk kartu statistik.
        $summary = [
            'total' => (clone $query)->count(),
            'selesai' => (clone $query)->where('status', 'SELESAI')->count(),
            'nominal' => (float) (clone $query)->sum('total_netto'),
        ];
        $summary['proses'] = $summary['total'] - $summary['selesai'];

        $tagihans = $query->latest('updated_at')->paginate(15)->withQueryString();

        // Tandai tagihan yang menunggu tindakan user saat ini (approval PENDING
        // pada salah satu dokumen rantai, atau seksi yang menjadi tugas role-nya).
        $tagihans->getCollection()->transform(function (Tagihan $tagihan) use ($user) {
            $tagihan->setAttribute('proses_state', $this->ringkasState($tagihan, $user));

            return $tagihan;
        });

        // Jumlah tagihan pada halaman ini yang menunggu tindakan user (badge tab).
        $perluAksiCount = $tagihans->getCollection()
            ->filter(fn (Tagihan $tagihan) => (bool) data_get($tagihan, 'proses_state.perluSaya'))
            ->count();

        if ($request->input('tab') === 'perlu-saya') {
            $tagihans->setCollection($tagihans->getCollection()
                ->filter(fn (Tagihan $tagihan) => (bool) data_get($tagihan, 'proses_state.perluSaya'))
                ->values());
        }

        return view('proses_tagihan.index', [
            'tagihans' => $tagihans,
            'tipeFilter' => $request->input('tipe'),
            'search' => $request->input('search'),
            'tab' => $request->input('tab', 'semua'),
            'summary' => $summary,
            'perluAksiCount' => $perluAksiCount,
        ]);
    }

    public function show($id)
    {
        $tagihan = Tagihan::with([
            'pihak',
            'arsipDokumen',
            'detailKontrak.kontrakTermin.kontrak.vendor',
            'detailKontrak.arsipDokumen',
            'potonganTagihan.pajak',
            'potonganTagihan.arsipDokumen',
            'detailPerjaldin.pegawai',
            'detailPerjaldin.provinsi',
            'detailHonorarium',
            'komponenPerjaldin.dipaRevisionItem.coa',
            'potonganTagihan',
            'dipaRevisionItem.coa',
            'workflowInstance.approvals.actedByUser',
            'kpaApprover',
            'logs.user',
        ])->findOrFail($id);

        $state = $this->buildPipelineState($tagihan, Auth::user());

        $coaOptions = DetailDipa::with(['coa', 'dipaRevision.masterDipa'])
            ->where('status_aktif', true)
            ->whereHas('coa')
            ->whereHas('dipaRevision', fn ($q) => $q->where('is_active', true))
            ->get()
            ->sortBy(fn ($item) => $item->coa?->kode_mak_lengkap)
            ->values();

        // PPh 21 (honor per golongan) tidak relevan untuk potongan kontrak.
        $pajakOptions = $tagihan->tipe_tagihan === 'KONTRAK'
            ? MasterTarifPajak::where('status_aktif', true)
                ->where('kode_pajak', 'not like', 'PPH21%')
                ->orderBy('jenis_pajak')
                ->get()
            : collect();

        // Daftar dokumen yang diunggah/di-generate saat pembuatan tagihan.
        $dokumenPendukung = \App\Support\TagihanDokumenPendukung::collect($tagihan);

        return view('proses_tagihan.show', compact('tagihan', 'state', 'coaOptions', 'pajakOptions', 'dokumenPendukung'));
    }

    // ─────────────────────────────────────────────────────────────────
    // Seksi COA (PPK)
    // ─────────────────────────────────────────────────────────────────

    public function simpanCoa(Request $request, $id, PerjaldinKomponenService $komponenService)
    {
        $this->ensureRole(['PPK', 'Super Admin']);

        $tagihan = Tagihan::findOrFail($id);

        if (! $this->chain->isTagihanFullyApproved($tagihan)) {
            return back()->with('error', 'COA baru dapat dipilih setelah tagihan disetujui seluruh verifikator.');
        }

        if ($this->chain->chainSpp($tagihan)) {
            return back()->with('error', 'COA tidak dapat diubah karena draft dokumen pencairan sudah dibuat.');
        }

        try {
            if ($tagihan->tipe_tagihan === 'PERJALDIN') {
                $request->validate(['coa' => 'required|array', 'coa.*' => 'required|integer|exists:dipa_revision_items,id']);

                foreach ($request->input('coa', []) as $komponenId => $itemId) {
                    $komponen = $tagihan->komponenPerjaldin()->findOrFail($komponenId);
                    $komponenService->updateKomponenCoa($komponen, (int) $itemId);
                }
            } else {
                $request->validate(['dipa_revision_item_id' => 'required|integer|exists:dipa_revision_items,id']);

                $item = DetailDipa::where('id', $request->dipa_revision_item_id)
                    ->where('status_aktif', true)
                    ->firstOrFail();

                if ((float) $tagihan->total_netto > (float) $item->sisa_pagu) {
                    return back()->with('error', sprintf(
                        'Nominal tagihan (Rp %s) melebihi sisa pagu COA terpilih (Rp %s).',
                        number_format((float) $tagihan->total_netto, 0, ',', '.'),
                        number_format((float) $item->sisa_pagu, 0, ',', '.')
                    ));
                }

                $tagihan->update([
                    'dipa_revision_item_id' => $item->id,
                    // DIPA induk ikut diisi di sini karena pembuatan tagihan/kontrak
                    // tidak lagi memilih COA di awal.
                    'master_dipa_id' => $item->dipaRevision?->master_dipa_id ?? $tagihan->master_dipa_id,
                ]);
            }

            $this->chain->clearChainCorrection($tagihan->fresh(), 'coa');
            $this->chain->maybeGenerateDraftChain($tagihan->fresh(), Auth::user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'COA berhasil disimpan.');
    }

    // ─────────────────────────────────────────────────────────────────
    // Seksi Pajak & Faktur Pajak — khusus KONTRAK (Operator BLU)
    // ─────────────────────────────────────────────────────────────────

    /**
     * Operator BLU memilih tipe pajak (potongan PAJAK) dan mengunggah faktur
     * pajak. Wajib selesai sebelum draft SPP/SPM/NPI dibuat (prasyarat rantai).
     */
    public function simpanPajak(Request $request, $id)
    {
        $this->ensureRole(['Operator BLU', 'Super Admin']);

        $tagihan = Tagihan::with(['potonganTagihan', 'detailKontrak.arsipDokumen'])->findOrFail($id);
        abort_unless($tagihan->tipe_tagihan === 'KONTRAK', 404);

        if (! $this->chain->isTagihanFullyApproved($tagihan)) {
            return back()->with('error', 'Pajak baru dapat diatur setelah tagihan disetujui seluruh verifikator.');
        }

        // Rantai yang masih draft/revisi boleh disesuaikan (nominal ikut diperbarui).
        // Setelah ada dokumen yang diajukan/diverifikasi, pajak terkunci.
        $sppChain = $this->chain->chainSpp($tagihan);
        if ($sppChain && ! $this->chain->isChainStillDraft($tagihan)) {
            return back()->with('error', 'Pajak tidak dapat diubah karena dokumen pencairan sudah diajukan/diverifikasi. Batalkan rantai dokumen terlebih dahulu bila pajak perlu diubah.');
        }

        $sudahDisetor = $tagihan->potonganTagihan
            ->where('jenis_potongan', 'PAJAK')
            ->first(fn ($p) => filled($p->kode_billing) || filled($p->ntpn));
        if ($sudahDisetor) {
            return back()->with('error', 'Pajak tidak dapat diubah karena sudah memasuki tahap billing/penyetoran.');
        }

        if (! $tagihan->detailKontrak) {
            return back()->with('error', 'Detail kontrak tidak ditemukan pada tagihan ini.');
        }

        $fakturLama = $tagihan->detailKontrak->file_faktur_pajak;

        $request->validate([
            'pajak' => 'required|array|min:1',
            'pajak.*' => 'required|integer|distinct|exists:master_tarif_pajak,id',
            'dpp' => 'array',
            'dpp.*' => 'nullable|numeric|min:0',
            'nominal' => 'array',
            'nominal.*' => 'nullable|numeric|min:0',
            'faktur_pajak' => ($fakturLama ? 'nullable' : 'required') . '|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'pajak.required' => 'Pilih minimal satu tipe pajak untuk tagihan kontrak ini.',
            'faktur_pajak.required' => 'Faktur pajak wajib diunggah untuk tagihan kontrak.',
        ]);

        try {
            DB::transaction(function () use ($request, $tagihan, $sppChain) {
                // Ganti seluruh baris pajak lama (aman: belum ada billing/NTPN).
                $tagihan->potonganTagihan()->where('jenis_potongan', 'PAJAK')->get()->each->delete();

                $totalBruto = (float) $tagihan->total_bruto;

                $selectedTarifs = MasterTarifPajak::where('status_aktif', true)
                    ->whereIn('id', $request->input('pajak', []))
                    ->get()
                    ->keyBy('id');

                // Sesuai kalkulator pajak: DPP default = bruto × 100/(100+PPN),
                // memakai tarif PPN yang dipilih (default 11% bila PPN tidak dipilih).
                $ppnRate = (float) ($selectedTarifs
                    ->first(fn ($t) => str_starts_with(strtoupper((string) $t->kode_pajak), 'PPN'))
                    ?->persentase ?? 11);
                $dppDefault = round($totalBruto * 100 / (100 + $ppnRate), 2);

                foreach ($request->input('pajak', []) as $tarifId) {
                    $tarif = $selectedTarifs->get((int) $tarifId);
                    if (! $tarif) {
                        throw new \RuntimeException('Tarif pajak yang dipilih tidak valid atau sudah tidak aktif.');
                    }

                    $dpp = (float) ($request->input("dpp.{$tarifId}") ?: $dppDefault);
                    // Pembulatan ke atas ke ratusan terdekat (ROUNDUP(x, -2)) sesuai kalkulator pajak.
                    $nominal = (float) ($request->input("nominal.{$tarifId}")
                        ?: ceil(($dpp * $tarif->persentase / 100) / 100) * 100);

                    if ($nominal <= 0) {
                        throw new \RuntimeException("Nominal potongan {$tarif->jenis_pajak} harus lebih dari nol.");
                    }

                    PotonganTagihan::create([
                        'tagihan_id' => $tagihan->id,
                        'pajak_id' => $tarif->id,
                        'jenis_potongan' => 'PAJAK',
                        'deskripsi' => "{$tarif->jenis_pajak} ({$tarif->kode_pajak})",
                        'dpp' => $dpp,
                        'persentase_tarif_snapshot' => $tarif->persentase,
                        'nama_pajak_snapshot' => $tarif->jenis_pajak,
                        'nominal_potongan' => $nominal,
                    ]);
                }

                $totalPotongan = (float) $tagihan->potonganTagihan()->sum('nominal_potongan');
                if ($totalPotongan >= $totalBruto) {
                    throw new \RuntimeException('Total potongan melebihi atau menyamai nilai bruto tagihan. Periksa kembali DPP/nominal pajak.');
                }

                $totalNetto = $totalBruto - $totalPotongan;

                $tagihan->update([
                    'total_potongan' => $totalPotongan,
                    'total_netto' => $totalNetto,
                ]);

                // Sinkronkan nominal draft SPP/SPM yang sudah terlanjur dibuat.
                if ($sppChain) {
                    $sppChain->update(['nominal_spp' => $totalNetto]);
                    $sppChain->spm?->update(['nominal_spm' => $totalNetto]);
                }

                if ($request->hasFile('faktur_pajak')) {
                    $detail = $tagihan->detailKontrak;
                    $detail->arsipDokumen()
                        ->where('jenis_dokumen', 'FAKTUR_PAJAK')
                        ->where('is_active', true)
                        ->update(['is_active' => false]);

                    $file = $request->file('faktur_pajak');
                    $detail->arsipDokumen()->create([
                        'jenis_dokumen' => 'FAKTUR_PAJAK',
                        'nama_file_asli' => $file->getClientOriginalName(),
                        'path_file' => $file->store('tagihan/faktur_pajak', 'public'),
                        'disk' => 'public',
                        'mime_type' => $file->getMimeType(),
                        'ukuran_file' => $file->getSize(),
                        'uploaded_by' => Auth::id(),
                        'uploaded_at' => now(),
                        'keterangan' => 'Faktur pajak tagihan kontrak (diunggah Operator BLU sebelum draft dokumen pencairan).',
                        'is_active' => true,
                    ]);
                }

                \App\Models\LogStatusDokumen::create([
                    'dokumen_type' => Tagihan::class,
                    'dokumen_id' => $tagihan->id,
                    'user_id' => Auth::id(),
                    'role_saat_itu' => Auth::user()?->getRoleNames()->first() ?? 'Operator BLU',
                    'status_sebelumnya' => $tagihan->status,
                    'status_baru' => $tagihan->status,
                    'aksi' => 'SET_PAJAK_KONTRAK',
                    'catatan' => 'Tipe pajak dipilih' . ($request->hasFile('faktur_pajak') ? ' dan faktur pajak diunggah' : '') . ' oleh Operator BLU.',
                    'ip_address' => $request->ip(),
                ]);
            });

            // Peringatan lunak ambang PMK 59/2022: PPN & PPh 22 hanya dipungut
            // bila pembayaran > Rp 2 juta (tidak memblokir — keputusan tetap di operator).
            if ((float) $tagihan->total_bruto <= 2_000_000) {
                $adaPpnAtauPph22 = MasterTarifPajak::whereIn('id', $request->input('pajak', []))
                    ->where(function ($q) {
                        $q->where('kode_pajak', 'like', 'PPN%')
                            ->orWhere('kode_pajak', 'like', 'PPH22%');
                    })
                    ->exists();

                if ($adaPpnAtauPph22) {
                    session()->flash('warning', 'Perhatian: nilai tagihan ≤ Rp 2 juta. Sesuai PMK 59/2022, PPN dan PPh 22 hanya dipungut bila pembayaran di atas Rp 2 juta — pastikan pemungutan ini memang diperlukan.');
                }
            }

            $this->chain->clearChainCorrection($tagihan->fresh(), 'pajak');
            $this->chain->maybeGenerateDraftChain($tagihan->fresh(), Auth::user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pajak dan faktur pajak berhasil disimpan.');
    }

    // ─────────────────────────────────────────────────────────────────
    // Pengajuan dokumen (paralel)
    // ─────────────────────────────────────────────────────────────────

    public function ajukanSpp($id)
    {
        $this->ensureRole(['Operator BLU', 'Super Admin']);

        return $this->lakukanPengajuan($id, fn ($tagihan) => $this->chain->submitSpp($tagihan, Auth::user()), 'SPP');
    }

    public function ajukanSpm($id)
    {
        $this->ensureRole(['Operator BLU', 'Super Admin']);

        return $this->lakukanPengajuan($id, fn ($tagihan) => $this->chain->submitSpm($tagihan, Auth::user()), 'SPM');
    }

    public function ajukanNpi($id)
    {
        $this->ensureRole(['Bendahara Pengeluaran', 'Super Admin']);

        return $this->lakukanPengajuan($id, fn ($tagihan) => $this->chain->submitNpi($tagihan, Auth::user()), 'NPI');
    }

    private function lakukanPengajuan($id, \Closure $callback, string $label)
    {
        $tagihan = Tagihan::findOrFail($id);

        try {
            $callback($tagihan);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "{$label} berhasil diajukan untuk verifikasi.");
    }

    // ─────────────────────────────────────────────────────────────────
    // Aksi verifikasi inline per dokumen
    // ─────────────────────────────────────────────────────────────────

    public function aksiDokumen(Request $request, $id, string $jenis)
    {
        // Verifikator hanya dapat menyetujui atau meminta revisi — opsi tolak
        // permanen dihilangkan agar rantai dokumen selalu bisa diperbaiki.
        // Karena dokumen di-generate dari data tagihan, revisi diarahkan ke
        // akar masalahnya: 'tagihan' (kembali ke pembuat tagihan, verifikasi
        // ulang penuh), 'pajak' (Operator BLU), 'coa' (PPK), atau 'bukti'
        // (khusus SP2D: Bendahara Pengeluaran mengganti bukti transfer).
        $isRevisi = $request->input('aksi') === 'revisi';
        $target = $request->input('target');

        $request->validate([
            'aksi' => 'required|in:approve,revisi',
            'approval_id' => 'required|integer',
            'target' => $isRevisi ? 'required|in:tagihan,pajak,coa,bukti' : 'nullable',
            'catatan' => $isRevisi && $target !== 'tagihan' ? 'required|string|max:1000' : 'nullable|string|max:1000',
            'revisi_doc' => $isRevisi && $target === 'tagihan' ? 'required|array|min:1' : 'nullable|array',
            'revisi_doc.*' => 'in:' . implode(',', array_keys(DokumenChainService::RETURNABLE_PARTS)),
            'revisi_catatan' => 'nullable|array',
            'revisi_catatan.*' => 'nullable|string|max:1000',
        ], [
            'target.required' => 'Pilih akar masalah revisi terlebih dahulu.',
            'catatan.required' => 'Catatan revisi wajib diisi.',
            'revisi_doc.required' => 'Pilih minimal satu bagian yang perlu direvisi.',
        ]);

        $tagihan = Tagihan::findOrFail($id);
        $document = $this->resolveDokumen($tagihan, $jenis, $request->input('dokumen_id'));

        if (! $document) {
            return back()->with('error', 'Dokumen tidak ditemukan pada rantai tagihan ini.');
        }

        if ($isRevisi && $target === 'pajak' && $tagihan->tipe_tagihan !== 'KONTRAK') {
            return back()->with('error', 'Perbaikan pajak hanya berlaku untuk tagihan kontrak.');
        }

        if ($isRevisi && $target === 'bukti' && strtolower($jenis) !== 'sp2d') {
            return back()->with('error', 'Perbaikan bukti transfer hanya berlaku pada SP2D.');
        }

        // Pastikan approval yang ditarget memang milik user (atau role-nya).
        $approval = WorkflowApproval::where('id', $request->approval_id)
            ->where('status', 'PENDING')
            ->first();

        if (! $approval || ! $this->approvalMilikUser($approval, Auth::user())) {
            return back()->with('error', 'Anda tidak memiliki approval yang pending pada dokumen ini.');
        }

        $aksi = $request->input('aksi');
        $catatan = $request->input('catatan');

        try {
            if ($aksi === 'approve') {
                $instance = $this->workflow->approveCurrentStep($document, Auth::id(), $catatan ?: 'Disetujui.', $approval->id);

                if ($instance->status === 'APPROVED') {
                    $this->tandaiDokumenDisetujui($document, strtolower($jenis));
                }

                $pesan = strtoupper($jenis) . ' berhasil disetujui.';
            } elseif ($target === 'tagihan') {
                // Catatan wajib per bagian yang dicentang verifikator.
                $items = [];
                foreach ($request->input('revisi_doc', []) as $key) {
                    $catatanBagian = trim((string) $request->input("revisi_catatan.{$key}", ''));
                    if ($catatanBagian === '') {
                        return back()->with('error', 'Catatan revisi wajib diisi untuk setiap bagian yang dipilih.');
                    }
                    $items[$key] = $catatanBagian;
                }

                $this->workflow->requestRevision(
                    $document,
                    Auth::id(),
                    $items[strtolower($jenis)] ?? 'Tagihan dikembalikan ke pembuatnya untuk revisi.',
                    $approval->id
                );
                $this->chain->returnChainToCreator($tagihan, Auth::user(), $items, $request->input('catatan'));

                $pesan = 'Rantai dokumen dibatalkan dan tagihan dikembalikan ke pembuatnya untuk revisi.';
            } elseif ($target === 'bukti') {
                // SP2D kembali ke Bendahara Pengeluaran untuk mengganti bukti
                // transfer — satu-satunya substansi SP2D yang diperbaiki manual.
                $this->workflow->requestRevision($document, Auth::id(), $catatan, $approval->id);
                $document->update(['status' => DokumenSp2d::STATUS_REVISI]);
                $this->chain->notifyDocumentRevisionRequested($tagihan, $jenis, $catatan, Auth::user());

                $pesan = 'SP2D dikembalikan ke Bendahara Pengeluaran untuk perbaikan bukti transfer.';
            } else {
                // pajak | coa — rantai di-rewind ke penanggung jawab bagian
                // tersebut tanpa mengulang verifikasi tagihan.
                $this->workflow->requestRevision($document, Auth::id(), $catatan, $approval->id);
                $this->chain->rewindChainForCorrection($tagihan, Auth::user(), $target, $catatan);

                $pesan = $target === 'pajak'
                    ? 'Rantai dokumen dibatalkan; dikembalikan ke Operator BLU untuk perbaikan pajak & faktur pajak.'
                    : 'Rantai dokumen dibatalkan; dikembalikan ke PPK untuk perbaikan pembebanan COA.';
            }
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $pesan);
    }

    // ─────────────────────────────────────────────────────────────────
    // Bukti transfer → pengajuan SP2D, dan pembatalan rantai
    // ─────────────────────────────────────────────────────────────────

    public function uploadBuktiTransfer(Request $request, $id)
    {
        $this->ensureRole(['Bendahara Pengeluaran', 'Super Admin']);

        $request->validate([
            'bukti_transfer' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'tanggal_sp2d' => 'nullable|date',
        ]);

        $tagihan = Tagihan::findOrFail($id);

        try {
            $this->chain->submitSp2d($tagihan, Auth::user(), $request->file('bukti_transfer'), $request->input('tanggal_sp2d'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Bukti transfer diunggah. SP2D diajukan ke PPK untuk penerbitan.');
    }

    public function batalkanRantai(Request $request, $id)
    {
        $this->ensureRole(['Operator BLU', 'Super Admin']);

        $request->validate(['alasan' => 'nullable|string|max:1000']);

        $tagihan = Tagihan::findOrFail($id);

        try {
            $this->chain->cancelChain($tagihan, Auth::user(), $request->input('alasan'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Rantai dokumen dibatalkan. Draft baru akan dibuat ulang setelah prasyarat terpenuhi.');
    }

    // ─────────────────────────────────────────────────────────────────
    // Pipeline state
    // ─────────────────────────────────────────────────────────────────

    private function buildPipelineState(Tagihan $tagihan, $user): array
    {
        $spp = $this->chain->chainSpp($tagihan);
        $spm = $spp?->spm;
        $npi = $spm?->npi;
        $sp2d = $npi?->sp2d;

        $sppApproved = $this->chain->isDocumentApproved($spp);
        $spmApproved = $this->chain->isDocumentApproved($spm);
        $npiApproved = $this->chain->isDocumentApproved($npi);

        $potonganPajak = $tagihan->potonganTagihan->where('jenis_potongan', 'PAJAK')->where('nominal_potongan', '>', 0)->values();
        $pajakSettled = $potonganPajak->isNotEmpty() && $potonganPajak->every(fn ($p) => trim((string) $p->ntpn) !== '');

        $bkuPosted = \App\Models\BukuKasUmum::where('referensi_pengeluaran_id', $tagihan->id)->exists();

        // Rantai per-komponen alur lama (perjaldin) — render read/act-only.
        $legacySpps = $this->chain->hasLegacyKomponenChain($tagihan)
            ? $tagihan->spps()->whereNotNull('tagihan_perjaldin_komponen_id')
                ->with(['tagihanPerjaldinKomponen', 'spm.npi.sp2d'])
                ->get()
            : collect();

        return [
            'tagihanApproved' => $this->chain->isTagihanFullyApproved($tagihan),
            'coaDone' => $this->chain->isCoaComplete($tagihan),
            'kpaDone' => $this->chain->isKpaApproved($tagihan),
            'pajakKontrak' => $tagihan->tipe_tagihan === 'KONTRAK',
            'pajakTipeDone' => $this->chain->isPajakTipeDipilih($tagihan),
            'fakturPajak' => $tagihan->tipe_tagihan === 'KONTRAK' ? $tagihan->detailKontrak?->file_faktur_pajak : null,
            'pajakKontrakDone' => $this->chain->isPajakKontrakComplete($tagihan),
            'chainStillDraft' => $this->chain->isChainStillDraft($tagihan),
            'missingPrereqs' => $this->chain->missingDraftPrerequisites($tagihan),
            'spp' => $spp,
            'spm' => $spm,
            'npi' => $npi,
            'sp2d' => $sp2d,
            'sppApproved' => $sppApproved,
            'spmApproved' => $spmApproved,
            'npiApproved' => $npiApproved,
            'dokumenSiapBayar' => $sppApproved && $spmApproved && $npiApproved,
            'sppInstance' => $this->chain->latestInstance($spp),
            'spmInstance' => $this->chain->latestInstance($spm),
            'npiInstance' => $this->chain->latestInstance($npi),
            'sp2dInstance' => $this->chain->latestInstance($sp2d),
            'myApprovals' => [
                'spp' => $this->pendingApprovalsUntukUser($spp, $user),
                'spm' => $this->pendingApprovalsUntukUser($spm, $user),
                'npi' => $this->pendingApprovalsUntukUser($npi, $user),
                'sp2d' => $this->pendingApprovalsUntukUser($sp2d, $user),
            ],
            'buktiTransfer' => $sp2d?->bukti_transfer,
            'sp2dTerbit' => $sp2d?->status === DokumenSp2d::STATUS_EXECUTED,
            'potonganPajak' => $potonganPajak,
            'pajakSettled' => $pajakSettled,
            'bkuPosted' => $bkuPosted,
            'legacySpps' => $legacySpps,
        ];
    }

    /** Versi ringan untuk halaman index. */
    private function ringkasState(Tagihan $tagihan, $user): array
    {
        $spp = $this->chain->chainSpp($tagihan);
        $sp2d = $spp?->spm?->npi?->sp2d;

        $tahap = 'Menunggu COA & KPA';
        if ($tagihan->status === 'SELESAI') {
            $tahap = 'Selesai';
        } elseif ($sp2d && $sp2d->status === DokumenSp2d::STATUS_EXECUTED) {
            $tahap = 'SP2D Terbit';
        } elseif ($sp2d && $sp2d->status === DokumenSp2d::STATUS_MENUNGGU_VERIFIKASI) {
            $tahap = 'Menunggu Penerbitan SP2D';
        } elseif ($spp) {
            $tahap = 'Proses SPP/SPM/NPI';
        } elseif ($this->chain->hasLegacyKomponenChain($tagihan)) {
            $tahap = 'Proses Dokumen (Alur Lama)';
        }

        $perluSaya = collect(['spp' => $spp, 'spm' => $spp?->spm, 'npi' => $spp?->spm?->npi, 'sp2d' => $sp2d])
            ->contains(fn ($doc) => $this->pendingApprovalsUntukUser($doc, $user)->isNotEmpty());

        if (! $perluSaya && $user?->hasAnyRole(['Operator BLU', 'Super Admin'])) {
            $perluSaya = $this->chain->isTagihanFullyApproved($tagihan)
                && (in_array($spp?->status, ['DRAFT', 'Revisi', 'REVISI'], true)
                    || in_array($spp?->spm?->status, [DokumenSpm::STATUS_DRAFT, DokumenSpm::STATUS_REVISI, 'Revisi'], true)
                    // Verifikator meminta perbaikan pajak — tugas Operator BLU.
                    || $tagihan->chain_correction_target === 'PAJAK'
                    // Kontrak: tipe pajak & faktur pajak wajib diisi Operator BLU sebelum draft dibuat
                    // (atau selagi rantai masih draft untuk tagihan lama).
                    || ((! $spp || $this->chain->isChainStillDraft($tagihan))
                        && ! $this->chain->isPajakKontrakComplete($tagihan)));
        }

        if (! $perluSaya && $user?->hasAnyRole(['PPK', 'Super Admin'])) {
            $perluSaya = $this->chain->isTagihanFullyApproved($tagihan)
                && (! $this->chain->isCoaComplete($tagihan)
                    || ! $this->chain->isKpaApproved($tagihan));
        }

        if (! $perluSaya && $user?->hasAnyRole(['Bendahara Pengeluaran', 'Super Admin'])) {
            // Pengajuan NPI baru menjadi tugas BP setelah SPP & SPM disetujui.
            $sppSpmApproved = $spp && $spp->spm
                && $this->chain->isDocumentApproved($spp)
                && $this->chain->isDocumentApproved($spp->spm);

            $perluSaya = ($sppSpmApproved
                    && in_array($spp?->spm?->npi?->status, [DokumenNpi::STATUS_DRAFT, DokumenNpi::STATUS_REVISI], true))
                || ($sppSpmApproved && $spp?->spm?->npi
                    && $this->chain->isDocumentApproved($spp->spm->npi)
                    && (! $sp2d?->bukti_transfer
                        // PPK meminta perbaikan bukti transfer pada SP2D.
                        || $sp2d?->status === DokumenSp2d::STATUS_REVISI));
        }

        return ['tahap' => $tahap, 'perluSaya' => $perluSaya];
    }

    // ─────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────

    private function resolveDokumen(Tagihan $tagihan, string $jenis, $dokumenId = null)
    {
        $jenis = strtolower($jenis);

        // Dukungan rantai per-komponen lama: target dokumen spesifik via dokumen_id.
        if ($dokumenId) {
            $model = match ($jenis) {
                'spp' => DokumenSpp::find($dokumenId),
                'spm' => DokumenSpm::find($dokumenId),
                'npi' => DokumenNpi::find($dokumenId),
                'sp2d' => DokumenSp2d::find($dokumenId),
                default => null,
            };

            if ($model && $this->dokumenMilikTagihan($model, $jenis, $tagihan)) {
                return $model;
            }

            return null;
        }

        $spp = $this->chain->chainSpp($tagihan);

        return match ($jenis) {
            'spp' => $spp,
            'spm' => $spp?->spm,
            'npi' => $spp?->spm?->npi,
            'sp2d' => $spp?->spm?->npi?->sp2d,
            default => null,
        };
    }

    private function dokumenMilikTagihan($model, string $jenis, Tagihan $tagihan): bool
    {
        $spp = match ($jenis) {
            'spp' => $model,
            'spm' => $model->spp,
            'npi' => $model->spm?->spp,
            'sp2d' => $model->npi?->spm?->spp,
            default => null,
        };

        return $spp && (int) $spp->tagihan_id === (int) $tagihan->id;
    }

    private function tandaiDokumenDisetujui($document, string $jenis): void
    {
        if ($jenis === 'sp2d') {
            $hasil = $this->chain->finalizeSp2d($document, Auth::user());

            session()->flash('success', $hasil['deferBkuUntilTax']
                ? 'SP2D terbit dan tagihan selesai. Lanjutkan penyetoran pajak agar tagihan masuk BKU.'
                : 'SP2D terbit. Tagihan selesai dan tercatat di pembukuan.');

            return;
        }

        $status = match ($jenis) {
            'spm' => DokumenSpm::STATUS_DISETUJUI_FINAL,
            'npi' => DokumenNpi::STATUS_DISETUJUI_FINAL,
            default => 'DISETUJUI_FINAL',
        };

        $document->update(['status' => $status]);

        if ($jenis === 'spp') {
            $tagihan = $document->tagihan;
            if ($tagihan && $tagihan->tipe_tagihan === 'PERJALDIN') {
                $tagihan->komponenPerjaldin->each->syncStatusFromDocuments();
            }
        }
    }

    private function pendingApprovalsUntukUser($document, $user)
    {
        if (! $document || ! $user) {
            return collect();
        }

        $instance = $this->chain->latestInstance($document);
        if (! $instance || $instance->status !== 'IN_PROGRESS') {
            return collect();
        }

        $roleCodes = $this->roleCodeVariants($user->getRoleNames()->toArray());

        return $instance->approvals()
            ->where('urutan_step', $instance->step_saat_ini)
            ->where('status', 'PENDING')
            ->where(function ($q) use ($user, $roleCodes) {
                $q->where('assigned_user_id', $user->id)
                    ->orWhere(function ($q2) use ($roleCodes) {
                        $q2->whereNull('assigned_user_id')->whereIn('role_code', $roleCodes);
                    });
            })
            ->get();
    }

    private function approvalMilikUser(WorkflowApproval $approval, $user): bool
    {
        if ((int) $approval->assigned_user_id === (int) $user->id) {
            return true;
        }

        return $approval->assigned_user_id === null
            && in_array($approval->role_code, $this->roleCodeVariants($user->getRoleNames()->toArray()), true);
    }

    /** Mapping nama role Spatie → semua varian role_code yang dipakai definisi workflow. */
    private function roleCodeVariants(array $roleNames): array
    {
        $map = [
            'PPK' => ['PPK'],
            'PPSPM' => ['PPSPM'],
            'Koordinator Keuangan' => ['Koordinator Keuangan', 'KOORDINATOR_KEUANGAN'],
            'Kepala Subbagian Keuangan dan Tata Usaha' => ['Kepala Subbagian Keuangan dan Tata Usaha', 'KASUBBAG'],
            'Bendahara Penerimaan' => ['Bendahara Penerimaan', 'BENDAHARA_PENERIMAAN'],
            'Bendahara Pengeluaran' => ['Bendahara Pengeluaran', 'BENDAHARA_PENGELUARAN'],
        ];

        $codes = [];
        foreach ($roleNames as $name) {
            foreach ($map[$name] ?? [$name] as $code) {
                $codes[] = $code;
            }
        }

        return array_values(array_unique($codes));
    }

    private function ensureRole(array $roles): void
    {
        abort_unless(Auth::user() && Auth::user()->hasAnyRole($roles), 403, 'Anda tidak memiliki akses untuk aksi ini.');
    }
}
