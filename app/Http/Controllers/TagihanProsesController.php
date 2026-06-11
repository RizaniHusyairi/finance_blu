<?php

namespace App\Http\Controllers;

use App\Models\DetailDipa;
use App\Models\DokumenNpi;
use App\Models\DokumenSp2d;
use App\Models\DokumenSpm;
use App\Models\DokumenSpp;
use App\Models\Tagihan;
use App\Models\WorkflowApproval;
use App\Services\DokumenChainService;
use App\Services\PerjaldinKomponenService;
use App\Services\WorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        $tagihans = $query->latest('updated_at')->paginate(15)->withQueryString();

        // Tandai tagihan yang menunggu tindakan user saat ini (approval PENDING
        // pada salah satu dokumen rantai, atau seksi yang menjadi tugas role-nya).
        $tagihans->getCollection()->transform(function (Tagihan $tagihan) use ($user) {
            $tagihan->setAttribute('proses_state', $this->ringkasState($tagihan, $user));

            return $tagihan;
        });

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
        ]);
    }

    public function show($id)
    {
        $tagihan = Tagihan::with([
            'pihak',
            'detailKontrak.kontrakTermin.kontrak.vendor',
            'detailPerjaldin',
            'detailHonorarium',
            'komponenPerjaldin.dipaRevisionItem.coa',
            'potonganTagihan',
            'dipaRevisionItem.coa',
            'workflowInstance.approvals.actedByUser',
            'kpaApprover',
            'logs.user',
        ])->findOrFail($id);

        $state = $this->buildPipelineState($tagihan, Auth::user());

        $coaOptions = DetailDipa::with('coa')
            ->where('status_aktif', true)
            ->whereHas('coa')
            ->whereHas('dipaRevision', fn ($q) => $q->where('is_active', true))
            ->get()
            ->sortBy(fn ($item) => $item->coa?->kode_mak_lengkap)
            ->values();

        return view('proses_tagihan.show', compact('tagihan', 'state', 'coaOptions'));
    }

    // ─────────────────────────────────────────────────────────────────
    // Seksi COA (Operator BLU)
    // ─────────────────────────────────────────────────────────────────

    public function simpanCoa(Request $request, $id, PerjaldinKomponenService $komponenService)
    {
        $this->ensureRole(['Operator BLU', 'Super Admin']);

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

                $tagihan->update(['dipa_revision_item_id' => $item->id]);
            }

            $this->chain->maybeGenerateDraftChain($tagihan->fresh(), Auth::user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'COA berhasil disimpan.');
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
        $request->validate([
            'aksi' => 'required|in:approve,revisi,reject',
            'approval_id' => 'required|integer',
            'catatan' => $request->input('aksi') === 'approve' ? 'nullable|string|max:1000' : 'required|string|max:1000',
        ]);

        $tagihan = Tagihan::findOrFail($id);
        $document = $this->resolveDokumen($tagihan, $jenis, $request->input('dokumen_id'));

        if (! $document) {
            return back()->with('error', 'Dokumen tidak ditemukan pada rantai tagihan ini.');
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
            } elseif ($aksi === 'revisi') {
                $this->workflow->requestRevision($document, Auth::id(), $catatan, $approval->id);
                $document->update(['status' => $this->statusRevisiFor(strtolower($jenis))]);
            } else {
                $this->workflow->rejectCurrentStep($document, Auth::id(), $catatan, $approval->id);
                $document->update(['status' => 'DITOLAK']);
            }
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $pesan = match ($aksi) {
            'approve' => strtoupper($jenis) . ' berhasil disetujui.',
            'revisi' => 'Permintaan revisi ' . strtoupper($jenis) . ' terkirim.',
            default => strtoupper($jenis) . ' ditolak.',
        };

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
                && (! $this->chain->isCoaComplete($tagihan)
                    || in_array($spp?->status, ['DRAFT', 'Revisi', 'REVISI'], true)
                    || in_array($spp?->spm?->status, [DokumenSpm::STATUS_DRAFT, DokumenSpm::STATUS_REVISI, 'Revisi'], true));
        }

        if (! $perluSaya && $user?->hasAnyRole(['PPK', 'Super Admin'])) {
            $perluSaya = $this->chain->isTagihanFullyApproved($tagihan)
                && ! $this->chain->isKpaApproved($tagihan);
        }

        if (! $perluSaya && $user?->hasAnyRole(['Bendahara Pengeluaran', 'Super Admin'])) {
            $perluSaya = in_array($spp?->spm?->npi?->status, [DokumenNpi::STATUS_DRAFT, DokumenNpi::STATUS_REVISI], true)
                || ($spp && $spp?->spm && $spp?->spm?->npi
                    && $this->chain->isDocumentApproved($spp)
                    && $this->chain->isDocumentApproved($spp->spm)
                    && $this->chain->isDocumentApproved($spp->spm->npi)
                    && ! $sp2d?->bukti_transfer);
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

    private function statusRevisiFor(string $jenis): string
    {
        return match ($jenis) {
            'spp', 'spm' => 'Revisi',
            default => 'REVISI',
        };
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
