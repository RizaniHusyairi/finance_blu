<?php

namespace App\Http\Controllers;

use App\Models\ArsipDokumen;
use App\Models\KontrakAddendum;
use App\Models\KontrakPengadaan;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ContractAddendumController extends Controller
{
    public function index(KontrakPengadaan $contract): View
    {
        $contract->load([
            'vendor',
            'ppkUser.pegawai',
            'termin',
            'jaminanKontrak',
            'addendums.logs.user',
            'addendums.arsipDokumen',
        ]);

        $addendums = $contract->addendums
            ->sortByDesc(fn (KontrakAddendum $item) => $item->tanggal_addendum?->timestamp ?? 0)
            ->values();

        $summary = [
            'total' => $addendums->count(),
            'draft' => $addendums->where('status_workflow', KontrakAddendum::STATUS_DRAFT)->count(),
            'submitted' => $addendums->where('status_workflow', KontrakAddendum::STATUS_SUBMITTED)->count(),
            'approved' => $addendums->where('status_workflow', KontrakAddendum::STATUS_APPROVED)->count(),
            'rejected' => $addendums->where('status_workflow', KontrakAddendum::STATUS_REJECTED)->count(),
        ];

        return view('contracts.addendums_index', [
            'contract' => $contract,
            'addendums' => $addendums,
            'summary' => $summary,
            'canReview' => $this->canReview(),
            'canManageDraft' => $this->canManageDraft(),
            'terminSummary' => $this->buildTerminSummary($contract),
        ]);
    }

    public function create(KontrakPengadaan $contract): View
    {
        return view('contracts.addendums_create', [
            'contract' => $contract->load(['vendor', 'ppkUser.pegawai']),
            'addendum' => new KontrakAddendum([
                'tanggal_addendum' => now()->toDateString(),
                'jenis_addendum' => KontrakAddendum::TYPE_TAMBAH_KURANG_NILAI,
                'nilai_kontrak_lama' => $contract->nilai_total_kontrak,
                'tanggal_selesai_lama' => $contract->tanggal_selesai,
                'jangka_waktu_lama' => $contract->jangka_waktu,
                'nilai_kontrak_baru' => $contract->nilai_total_kontrak,
                'tanggal_selesai_baru' => $contract->tanggal_selesai,
                'jangka_waktu_baru' => $contract->jangka_waktu,
            ]),
            'jenisOptions' => KontrakAddendum::jenisOptions(),
            'formAction' => route('addendums.store', $contract),
            'formMethod' => 'POST',
            'pageTitle' => 'Buat Addendum Kontrak',
            'pageSubtitle' => 'Susun perubahan kontrak sebagai draft sebelum diajukan untuk persetujuan.',
        ]);
    }

    public function store(Request $request, KontrakPengadaan $contract): RedirectResponse
    {
        $validated = $this->validateAddendumRequest($request);
        $shouldSubmit = $request->input('action') === 'submit';

        $addendum = DB::transaction(function () use ($contract, $validated, $request, $shouldSubmit) {
            $addendum = $contract->addendums()->create($this->buildAddendumPayload($contract, $validated));

            $this->syncDocuments($addendum, $request);
            $this->logStatus(
                $addendum,
                null,
                $addendum->status_workflow,
                'CREATE_DRAFT_ADDENDUM',
                'Draft addendum disimpan dari kontrak ' . $contract->nomor_spk . '.'
            );

            if ($shouldSubmit) {
                $this->submitAddendum($addendum);
            }

            return $addendum;
        });

        return redirect()
            ->route('addendums.show', [$contract, $addendum])
            ->with('success', $shouldSubmit
                ? 'Addendum berhasil disimpan dan diajukan untuk persetujuan.'
                : 'Draft addendum berhasil disimpan.');
    }

    public function show(KontrakPengadaan $contract, KontrakAddendum $addendum): View
    {
        $this->ensureAddendumBelongsToContract($contract, $addendum);

        $contract->load(['vendor', 'ppkUser.pegawai', 'termin.detailKontrak.tagihan', 'jaminanKontrak']);
        $addendum->load(['arsipDokumen', 'logs.user']);

        $previousAddendums = $contract->addendums()
            ->whereKeyNot($addendum->id)
            ->orderByDesc('tanggal_addendum')
            ->get();

        $timeline = $addendum->logs
            ->sortByDesc('created_at')
            ->values();

        return view('contracts.addendums_show', [
            'contract' => $contract,
            'addendum' => $addendum,
            'timeline' => $timeline,
            'previousAddendums' => $previousAddendums,
            'canReview' => $this->canReview(),
            'canManageDraft' => $this->canManageDraft(),
            'terminSummary' => $this->buildTerminSummary($contract),
            'jaminanSummary' => $this->buildJaminanSummary($contract, $addendum),
            'impactSummary' => $this->buildImpactSummary($contract, $addendum),
        ]);
    }

    public function edit(KontrakPengadaan $contract, KontrakAddendum $addendum): View
    {
        $this->ensureAddendumBelongsToContract($contract, $addendum);
        $this->ensureEditable($addendum);

        return view('contracts.addendums_edit', [
            'contract' => $contract->load(['vendor', 'ppkUser.pegawai']),
            'addendum' => $addendum->load('arsipDokumen'),
            'jenisOptions' => KontrakAddendum::jenisOptions(),
            'formAction' => route('addendums.update', [$contract, $addendum]),
            'formMethod' => 'PUT',
            'pageTitle' => 'Ubah Draft Addendum',
            'pageSubtitle' => 'Perbarui draft addendum sebelum diajukan atau diajukan ulang.',
        ]);
    }

    public function update(Request $request, KontrakPengadaan $contract, KontrakAddendum $addendum): RedirectResponse
    {
        $this->ensureAddendumBelongsToContract($contract, $addendum);
        $this->ensureEditable($addendum);

        $validated = $this->validateAddendumRequest($request, $addendum);
        $shouldSubmit = $request->input('action') === 'submit';

        DB::transaction(function () use ($contract, $addendum, $validated, $request, $shouldSubmit) {
            $oldStatus = $addendum->status_workflow;

            $addendum->update($this->buildAddendumPayload($contract, $validated, $addendum));
            $this->syncDocuments($addendum, $request);

            $this->logStatus(
                $addendum,
                $oldStatus,
                $addendum->status_workflow,
                'UPDATE_DRAFT_ADDENDUM',
                'Draft addendum diperbarui.'
            );

            if ($shouldSubmit) {
                $this->submitAddendum($addendum);
            }
        });

        return redirect()
            ->route('addendums.show', [$contract, $addendum])
            ->with('success', $shouldSubmit
                ? 'Draft addendum berhasil diperbarui dan diajukan kembali.'
                : 'Draft addendum berhasil diperbarui.');
    }

    public function submit(KontrakPengadaan $contract, KontrakAddendum $addendum): RedirectResponse
    {
        $this->ensureAddendumBelongsToContract($contract, $addendum);
        $this->ensureManageDraftPermission();

        DB::transaction(function () use ($addendum) {
            $this->submitAddendum($addendum);
        });

        return back()->with('success', 'Addendum berhasil diajukan untuk persetujuan.');
    }

    public function approve(Request $request, KontrakPengadaan $contract, KontrakAddendum $addendum): RedirectResponse
    {
        $this->ensureAddendumBelongsToContract($contract, $addendum);
        $this->ensureReviewPermission();

        if ($addendum->status_workflow !== KontrakAddendum::STATUS_SUBMITTED) {
            return back()->with('error', 'Addendum tidak sedang menunggu persetujuan.');
        }

        $request->validate([
            'approval_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $warningMessage = null;

        DB::transaction(function () use ($contract, $addendum, $request, &$warningMessage) {
            $oldStatus = $addendum->status_workflow;

            $addendum->update([
                'status_addendum' => KontrakAddendum::STATUS_APPROVED,
                'status_proses' => KontrakAddendum::STATUS_APPROVED,
            ]);

            $updates = [];

            if ($addendum->has_value_change) {
                $updates['nilai_total_kontrak'] = $addendum->nilai_kontrak_baru;
            }

            if ($addendum->has_date_change) {
                $updates['tanggal_selesai'] = $addendum->tanggal_selesai_baru;
            }

            if ($addendum->has_duration_change) {
                $updates['jangka_waktu'] = $addendum->jangka_waktu_baru;
            }

            if (!empty($updates)) {
                $contract->update($updates);
            }

            $this->logStatus(
                $addendum,
                $oldStatus,
                KontrakAddendum::STATUS_APPROVED,
                'APPROVE_ADDENDUM',
                $request->input('approval_note') ?: 'Addendum disetujui dan kontrak utama diperbarui.'
            );

            $contract->refresh()->load(['termin', 'jaminanKontrak']);
            $terminSummary = $this->buildTerminSummary($contract);
            $jaminanSummary = $this->buildJaminanSummary($contract, $addendum);

            $warnings = [];
            if ($terminSummary['requires_review']) {
                $warnings[] = 'Nilai kontrak berubah dan total termin perlu ditinjau ulang.';
            }
            if ($jaminanSummary['requires_review']) {
                $warnings[] = 'Jaminan kontrak yang terkait nilai atau masa berlaku perlu ditinjau ulang.';
            }

            if (!empty($warnings)) {
                $warningMessage = implode(' ', $warnings);
            }
        });

        $redirect = redirect()
            ->route('addendums.show', [$contract, $addendum])
            ->with('success', 'Addendum berhasil disetujui dan data kontrak utama sudah diperbarui.');

        if ($warningMessage) {
            $redirect->with('warning', $warningMessage);
        }

        return $redirect;
    }

    public function reject(Request $request, KontrakPengadaan $contract, KontrakAddendum $addendum): RedirectResponse
    {
        $this->ensureAddendumBelongsToContract($contract, $addendum);
        $this->ensureReviewPermission();

        if ($addendum->status_workflow !== KontrakAddendum::STATUS_SUBMITTED) {
            return back()->with('error', 'Addendum tidak sedang menunggu persetujuan.');
        }

        $request->validate([
            'rejection_note' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        DB::transaction(function () use ($addendum, $request) {
            $oldStatus = $addendum->status_workflow;

            $addendum->update([
                'status_addendum' => KontrakAddendum::STATUS_DRAFT,
                'status_proses' => KontrakAddendum::STATUS_REJECTED,
            ]);

            $this->logStatus(
                $addendum,
                $oldStatus,
                KontrakAddendum::STATUS_REJECTED,
                'REJECT_ADDENDUM',
                $request->input('rejection_note')
            );
        });

        return back()->with('success', 'Addendum dikembalikan untuk revisi.');
    }

    public function destroy(KontrakPengadaan $contract, KontrakAddendum $addendum): RedirectResponse
    {
        $this->ensureAddendumBelongsToContract($contract, $addendum);
        $this->ensureManageDraftPermission();

        if ($addendum->status_workflow !== KontrakAddendum::STATUS_DRAFT) {
            return back()->with('error', 'Hanya addendum draft yang dapat dihapus.');
        }

        DB::transaction(function () use ($addendum) {
            $addendum->load('arsipDokumen');

            foreach ($addendum->arsipDokumen as $arsip) {
                Storage::disk($arsip->disk ?? 'public')->delete($arsip->path_file);
                $arsip->delete();
            }

            $addendum->delete();
        });

        return redirect()
            ->route('addendums.index', $contract)
            ->with('success', 'Draft addendum berhasil dihapus.');
    }

    private function validateAddendumRequest(Request $request, ?KontrakAddendum $addendum = null): array
    {
        $validator = validator($request->all(), [
            'nomor_addendum' => [
                'required',
                'string',
                'max:100',
                Rule::unique('kontrak_addendum', 'nomor_addendum')->ignore($addendum?->id),
            ],
            'tanggal_addendum' => ['required', 'date'],
            'jenis_addendum' => ['required', Rule::in(KontrakAddendum::jenisOptions())],
            'keterangan_alasan' => ['required', 'string'],
            'catatan_perubahan_spesifikasi' => ['nullable', 'string'],
            'nilai_kontrak_baru' => ['nullable', 'numeric', 'min:0'],
            'tanggal_selesai_baru' => ['nullable', 'date'],
            'jangka_waktu_baru' => ['nullable', 'integer', 'min:1'],
            'file_addendum' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
            'nota_dinas_file' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
            'dokumen_pendukung_teknis_file' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
            'lampiran_spesifikasi_file' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ]);

        $validator->after(function ($validator) use ($request) {
            $jenis = $request->input('jenis_addendum');

            if ($jenis === KontrakAddendum::TYPE_TAMBAH_KURANG_NILAI && $request->filled('nilai_kontrak_baru') === false) {
                $validator->errors()->add('nilai_kontrak_baru', 'Nilai kontrak baru wajib diisi untuk addendum perubahan nilai.');
            }

            if ($jenis === KontrakAddendum::TYPE_PERPANJANGAN_WAKTU) {
                if (!$request->filled('tanggal_selesai_baru')) {
                    $validator->errors()->add('tanggal_selesai_baru', 'Tanggal selesai baru wajib diisi untuk addendum perpanjangan waktu.');
                }
                if (!$request->filled('jangka_waktu_baru')) {
                    $validator->errors()->add('jangka_waktu_baru', 'Jangka waktu baru wajib diisi untuk addendum perpanjangan waktu.');
                }
            }

            if ($jenis === KontrakAddendum::TYPE_GANTI_SPESIFIKASI && blank($request->input('catatan_perubahan_spesifikasi'))) {
                $validator->errors()->add('catatan_perubahan_spesifikasi', 'Catatan perubahan spesifikasi wajib diisi.');
            }

            if ($jenis === KontrakAddendum::TYPE_KOMBINASI) {
                if (!$request->filled('nilai_kontrak_baru')) {
                    $validator->errors()->add('nilai_kontrak_baru', 'Nilai kontrak baru wajib diisi untuk addendum kombinasi.');
                }
                if (!$request->filled('tanggal_selesai_baru')) {
                    $validator->errors()->add('tanggal_selesai_baru', 'Tanggal selesai baru wajib diisi untuk addendum kombinasi.');
                }
                if (!$request->filled('jangka_waktu_baru')) {
                    $validator->errors()->add('jangka_waktu_baru', 'Jangka waktu baru wajib diisi untuk addendum kombinasi.');
                }
                if (blank($request->input('catatan_perubahan_spesifikasi'))) {
                    $validator->errors()->add('catatan_perubahan_spesifikasi', 'Catatan perubahan spesifikasi wajib diisi untuk addendum kombinasi.');
                }
            }
        });

        return $validator->validate();
    }

    private function buildAddendumPayload(KontrakPengadaan $contract, array $validated, ?KontrakAddendum $existing = null): array
    {
        $nilaiLama = (float) $contract->nilai_total_kontrak;
        $tanggalLama = $contract->tanggal_selesai
            ? Carbon::parse($contract->tanggal_selesai)->toDateString()
            : null;
        $jangkaWaktuLama = (int) $contract->jangka_waktu;

        $nilaiBaru = array_key_exists('nilai_kontrak_baru', $validated) && $validated['nilai_kontrak_baru'] !== null
            ? (float) $validated['nilai_kontrak_baru']
            : $nilaiLama;

        $tanggalBaru = $validated['tanggal_selesai_baru'] ?? $tanggalLama;
        $jangkaWaktuBaru = isset($validated['jangka_waktu_baru'])
            ? (int) $validated['jangka_waktu_baru']
            : $jangkaWaktuLama;

        return [
            'nomor_addendum' => $validated['nomor_addendum'],
            'tanggal_addendum' => $validated['tanggal_addendum'],
            'jenis_addendum' => $validated['jenis_addendum'],
            'keterangan_alasan' => $validated['keterangan_alasan'],
            'catatan_perubahan_spesifikasi' => $validated['catatan_perubahan_spesifikasi'] ?? null,
            'nilai_kontrak_lama' => $nilaiLama,
            'tanggal_selesai_lama' => $tanggalLama,
            'jangka_waktu_lama' => $jangkaWaktuLama,
            'nilai_kontrak_baru' => $nilaiBaru,
            'tanggal_selesai_baru' => $tanggalBaru,
            'jangka_waktu_baru' => $jangkaWaktuBaru,
            'status_addendum' => $existing?->status_addendum ?? KontrakAddendum::STATUS_DRAFT,
            'status_proses' => $existing?->status_workflow === KontrakAddendum::STATUS_REJECTED
                ? KontrakAddendum::STATUS_REJECTED
                : optional($existing)->status_proses,
        ];
    }

    private function submitAddendum(KontrakAddendum $addendum): void
    {
        $this->ensureManageDraftPermission();

        if (!$addendum->can_be_submitted) {
            throw ValidationException::withMessages([
                'status_addendum' => 'Addendum tidak dapat diajukan dari status saat ini.',
            ]);
        }

        $this->ensureAddendumCompleteness($addendum);

        $oldStatus = $addendum->status_workflow;

        $addendum->update([
            'status_addendum' => KontrakAddendum::STATUS_DRAFT,
            'status_proses' => KontrakAddendum::STATUS_SUBMITTED,
        ]);

        $this->logStatus(
            $addendum,
            $oldStatus,
            KontrakAddendum::STATUS_SUBMITTED,
            'SUBMIT_ADDENDUM',
            'Addendum diajukan untuk persetujuan.'
        );
    }

    private function ensureAddendumCompleteness(KontrakAddendum $addendum): void
    {
        $messages = [];

        if ($addendum->jenis_addendum === KontrakAddendum::TYPE_TAMBAH_KURANG_NILAI && $addendum->nilai_kontrak_baru === null) {
            $messages['nilai_kontrak_baru'] = 'Nilai kontrak baru belum lengkap.';
        }

        if ($addendum->jenis_addendum === KontrakAddendum::TYPE_PERPANJANGAN_WAKTU) {
            if (!$addendum->tanggal_selesai_baru) {
                $messages['tanggal_selesai_baru'] = 'Tanggal selesai baru belum lengkap.';
            }
            if (!$addendum->jangka_waktu_baru) {
                $messages['jangka_waktu_baru'] = 'Jangka waktu baru belum lengkap.';
            }
        }

        if ($addendum->jenis_addendum === KontrakAddendum::TYPE_GANTI_SPESIFIKASI && blank($addendum->catatan_perubahan_spesifikasi)) {
            $messages['catatan_perubahan_spesifikasi'] = 'Catatan perubahan spesifikasi belum diisi.';
        }

        if ($addendum->jenis_addendum === KontrakAddendum::TYPE_KOMBINASI) {
            if ($addendum->nilai_kontrak_baru === null) {
                $messages['nilai_kontrak_baru'] = 'Nilai kontrak baru belum lengkap.';
            }
            if (!$addendum->tanggal_selesai_baru) {
                $messages['tanggal_selesai_baru'] = 'Tanggal selesai baru belum lengkap.';
            }
            if (!$addendum->jangka_waktu_baru) {
                $messages['jangka_waktu_baru'] = 'Jangka waktu baru belum lengkap.';
            }
            if (blank($addendum->catatan_perubahan_spesifikasi)) {
                $messages['catatan_perubahan_spesifikasi'] = 'Catatan perubahan spesifikasi belum diisi.';
            }
        }

        if (!empty($messages)) {
            throw ValidationException::withMessages($messages);
        }
    }

    private function buildTerminSummary(KontrakPengadaan $contract): array
    {
        $termin = $contract->relationLoaded('termin') ? $contract->termin : $contract->termin()->get();
        $totalTermin = (float) $termin->sum('nilai_bruto_termin');
        $targetKontrak = (float) $contract->nilai_total_kontrak;

        return [
            'jumlah' => $termin->count(),
            'total' => $totalTermin,
            'sudah_ditagih' => (float) $termin->where('status_termin', 'SUDAH_DITAGIH')->sum('nilai_bruto_termin'),
            'belum_ditagih' => (float) $termin->where('status_termin', '!=', 'SUDAH_DITAGIH')->sum('nilai_bruto_termin'),
            'selisih' => round($targetKontrak - $totalTermin, 2),
            'requires_review' => round($targetKontrak - $totalTermin, 2) !== 0.0,
        ];
    }

    private function buildJaminanSummary(KontrakPengadaan $contract, KontrakAddendum $addendum): array
    {
        $jaminan = $contract->relationLoaded('jaminanKontrak')
            ? $contract->jaminanKontrak
            : $contract->jaminanKontrak()->get();

        return [
            'jumlah' => $jaminan->count(),
            'requires_review' => $jaminan->isNotEmpty() && ($addendum->has_value_change || $addendum->has_date_change || $addendum->has_duration_change),
        ];
    }

    private function buildImpactSummary(KontrakPengadaan $contract, KontrakAddendum $addendum): array
    {
        $terminSummary = $this->buildTerminSummary($contract);
        $jaminanSummary = $this->buildJaminanSummary($contract, $addendum);

        return [
            'nilai_berubah' => $addendum->has_value_change,
            'waktu_berubah' => $addendum->has_date_change || $addendum->has_duration_change,
            'spesifikasi_berubah' => $addendum->has_specification_change,
            'termin_perlu_ditinjau' => $addendum->has_value_change && $terminSummary['requires_review'],
            'jaminan_perlu_ditinjau' => $jaminanSummary['requires_review'],
        ];
    }

    private function syncDocuments(KontrakAddendum $addendum, Request $request): void
    {
        $mapping = [
            'file_addendum' => 'FILE_ADDENDUM',
            'nota_dinas_file' => 'NOTA_DINAS',
            'dokumen_pendukung_teknis_file' => 'DOKUMEN_PENDUKUNG_TEKNIS',
            'lampiran_spesifikasi_file' => 'LAMPIRAN_SPESIFIKASI',
        ];

        foreach ($mapping as $input => $jenisDokumen) {
            if (!$request->hasFile($input)) {
                continue;
            }

            $this->replaceDocument(
                $addendum,
                $jenisDokumen,
                $request->file($input)
            );
        }
    }

    private function replaceDocument(KontrakAddendum $addendum, string $jenisDokumen, UploadedFile $file): ArsipDokumen
    {
        $addendum->arsipDokumen()
            ->where('jenis_dokumen', $jenisDokumen)
            ->where('is_active', true)
            ->get()
            ->each(function (ArsipDokumen $arsip) {
                Storage::disk($arsip->disk ?? 'public')->delete($arsip->path_file);
                $arsip->update(['is_active' => false]);
            });

        $path = $file->store('kontrak/addendum', 'public');

        return $addendum->arsipDokumen()->create([
            'jenis_dokumen' => $jenisDokumen,
            'nama_file_asli' => $file->getClientOriginalName(),
            'path_file' => $path,
            'disk' => 'public',
            'mime_type' => $file->getClientMimeType(),
            'ukuran_file' => $file->getSize(),
            'uploaded_by' => Auth::id(),
            'uploaded_at' => now(),
            'is_active' => true,
        ]);
    }

    private function logStatus(KontrakAddendum $addendum, ?string $statusSebelumnya, string $statusBaru, string $aksi, ?string $catatan = null): void
    {
        $addendum->logs()->create([
            'user_id' => Auth::id(),
            'role_saat_itu' => Auth::user()?->getRoleNames()->first() ?? 'Sistem',
            'status_sebelumnya' => $statusSebelumnya,
            'status_baru' => $statusBaru,
            'aksi' => $aksi,
            'catatan' => $catatan,
            'ip_address' => request()->ip(),
        ]);
    }

    private function ensureAddendumBelongsToContract(KontrakPengadaan $contract, KontrakAddendum $addendum): void
    {
        abort_unless((int) $addendum->kontrak_pengadaan_id === (int) $contract->id, 404);
    }

    private function ensureEditable(KontrakAddendum $addendum): void
    {
        $this->ensureManageDraftPermission();

        abort_unless(
            in_array($addendum->status_workflow, [KontrakAddendum::STATUS_DRAFT, KontrakAddendum::STATUS_REJECTED], true),
            403,
            'Addendum ini tidak dapat diubah lagi.'
        );
    }

    private function ensureManageDraftPermission(): void
    {
        abort_unless($this->canManageDraft(), 403, 'Anda tidak memiliki izin untuk mengelola draft addendum.');
    }

    private function ensureReviewPermission(): void
    {
        abort_unless($this->canReview(), 403, 'Anda tidak memiliki izin untuk menyetujui atau menolak addendum.');
    }

    private function canManageDraft(): bool
    {
        return Auth::user()?->hasAnyRole(['Super Admin', 'Pejabat Pengadaan', 'PPK']) === true;
    }

    private function canReview(): bool
    {
        return Auth::user()?->hasAnyRole(['Super Admin', 'PPK']) === true;
    }
}
