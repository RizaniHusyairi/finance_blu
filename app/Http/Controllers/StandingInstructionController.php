<?php

namespace App\Http\Controllers;

use App\Models\DokumenSpp;
use App\Models\User;
use App\Services\DocumentArchiveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class StandingInstructionController extends Controller
{
    public function form(Request $request, DokumenSpp $spp)
    {
        $user = Auth::user();
        if (!$this->canManageStandingInstruction($user)) {
            return back()->with('error', 'Anda tidak memiliki akses untuk membuat Standing Instruction.');
        }

        $spp->load('standingInstruction', 'tagihan');
        $kpaUser = User::role(['KPA', 'PLT/PLH'])->active()->first();
        $returnUrl = $this->resolveReturnUrl($request, $spp);

        // Warning jika nominal beda
        $warningNominal = false;
        if ($spp->standingInstruction && $spp->standingInstruction->status === 'FINAL') {
            if (abs($spp->standingInstruction->nominal_transfer - $spp->nominal_spp) > 0.01) {
                $warningNominal = true;
            }
        }

        return view('spps.standing_instruction_form', compact('spp', 'kpaUser', 'warningNominal', 'returnUrl'));
    }

    public function storeOrUpdate(Request $request, DokumenSpp $spp)
    {
        $user = Auth::user();

        // Hanya role PPK atau Super Admin yang boleh
        if (!$this->canManageStandingInstruction($user)) {
            return back()->with('error', 'Anda tidak memiliki akses untuk membuat Standing Instruction.');
        }

        // Ambil KPA otomatis
        $kpaUser = User::role(['KPA', 'PLT/PLH'])->active()->first();

        if (!$kpaUser) {
            return back()->with('error', 'Gagal membuat Standing Instruction: User dengan role KPA atau PLT/PLH tidak ditemukan.');
        }

        $request->validate([
            'nomor_surat' => 'nullable|string|max:255',
            'tanggal_surat' => 'nullable|date',
            'rekening_sumber_nomor' => 'required|string|max:255',
            'rekening_sumber_nama' => 'required|string|max:255',
            'rekening_sumber_bank' => 'required|string|max:255',
            'rekening_tujuan_nomor' => 'required|string|max:255',
            'rekening_tujuan_nama' => 'required|string|max:255',
            'rekening_tujuan_bank' => 'required|string|max:255',
            'uraian_penggunaan' => 'nullable|string',
            'nominal_transfer' => 'nullable|numeric',
        ]);

        $nominalTransfer = $request->nominal_transfer ?: $spp->nominal_spp;

        $si = $spp->standingInstruction()->updateOrCreate(
            ['dokumen_spp_id' => $spp->id],
            [
                'nomor_surat' => $request->nomor_surat,
                'tanggal_surat' => $request->tanggal_surat,
                'ppk_user_id' => $user->id, // Atau bisa diambil dari auth()->id() atau dari tagihan PPK
                'kpa_user_id' => $kpaUser->id,
                'nama_ppk_snapshot' => $user->name,
                'jabatan_ppk_snapshot' => $user->pegawai?->jabatan ?? 'PPK',
                'nama_kpa_snapshot' => $kpaUser->name,
                'jabatan_kpa_snapshot' => $kpaUser->pegawai?->jabatan ?? ($kpaUser->hasRole('PLT/PLH') ? 'PLT/PLH' : 'KPA'),
                'rekening_sumber_nomor' => $request->rekening_sumber_nomor,
                'rekening_sumber_nama' => $request->rekening_sumber_nama,
                'rekening_sumber_bank' => $request->rekening_sumber_bank,
                'rekening_tujuan_nomor' => $request->rekening_tujuan_nomor,
                'rekening_tujuan_nama' => $request->rekening_tujuan_nama,
                'rekening_tujuan_bank' => $request->rekening_tujuan_bank,
                'nominal_transfer' => $nominalTransfer,
                'uraian_penggunaan' => $request->uraian_penggunaan,
                'status' => 'DRAFT',
                'dibuat_oleh_id' => $user->id,
                'difinalkan_oleh_id' => null,
                'finalized_at' => null,
            ]
        );

        $spp->arsipDokumen()
            ->where('jenis_dokumen', DokumenSpp::STANDING_INSTRUCTION_SIGNED_ARCHIVE_TYPE)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        // Tambahkan log
        $spp->logs()->create([
            'user_id' => $user->id,
            'role_saat_itu' => $user->getRoleNames()->first() ?? 'PPK',
            'status_sebelumnya' => $spp->status,
            'status_baru' => $spp->status,
            'aksi' => 'STANDING_INSTRUCTION_UPDATED',
            'catatan' => 'Standing Instruction diperbarui.',
            'ip_address' => $request->ip(),
        ]);

        $returnUrl = $this->resolveReturnUrl($request, $spp);
        $action = $request->input('action', 'save');

        if ($action === 'preview') {
            return redirect()
                ->route('standing-instructions.form', ['spp' => $spp->id, 'return_to' => $returnUrl])
                ->with('success', 'Draft tersimpan. Menampilkan preview PDF.');
        } else {
            return redirect($returnUrl)
                ->with('success', 'Standing Instruction berhasil disimpan/diperbarui.');
        }
    }

    public function finalize(Request $request, DokumenSpp $spp)
    {
        $user = Auth::user();

        if (!$this->canManageStandingInstruction($user)) {
            return back()->with('error', 'Anda tidak memiliki akses untuk mengunggah SI bertanda tangan.');
        }

        $request->validate([
            'file_si_bertanda_tangan' => 'required|file|mimes:pdf|max:10240',
        ], [
            'file_si_bertanda_tangan.required' => 'File SI bertanda tangan wajib diunggah.',
            'file_si_bertanda_tangan.mimes' => 'File SI bertanda tangan harus berformat PDF.',
            'file_si_bertanda_tangan.max' => 'Ukuran file SI bertanda tangan maksimal 10 MB.',
        ]);

        $si = $spp->standingInstruction;

        if (!$si) {
            return back()->with('error', 'Standing Instruction belum dibuat.');
        }

        // Validasi semua field wajib lengkap
        if (!$si->nomor_surat || !$si->tanggal_surat || !$si->rekening_sumber_nomor || !$si->rekening_sumber_nama || !$si->rekening_sumber_bank || !$si->rekening_tujuan_nomor || !$si->rekening_tujuan_nama || !$si->rekening_tujuan_bank || !$si->nominal_transfer) {
            return back()->with('error', 'Semua field wajib diisi sebelum upload SI bertanda tangan (Nomor Surat, Tanggal Surat, Rekening Sumber, Rekening Tujuan, Nominal).');
        }

        if (!$si->kpa_user_id) {
            return back()->with('error', 'User KPA atau PLT/PLH tidak ditemukan dalam Standing Instruction.');
        }

        DB::transaction(function () use ($request, $spp, $si, $user) {
            app(DocumentArchiveService::class)->replace(
                $spp,
                DokumenSpp::STANDING_INSTRUCTION_SIGNED_ARCHIVE_TYPE,
                $request->file('file_si_bertanda_tangan'),
                [
                    'directory' => 'standing-instructions/final-ttd',
                    'uploaded_by' => $user->id,
                    'keterangan' => 'Standing Instruction bertanda tangan.',
                ]
            );

            $si->update([
                'status' => 'FINAL',
                'difinalkan_oleh_id' => $user->id,
                'finalized_at' => now(),
            ]);

            $spp->logs()->create([
                'user_id' => $user->id,
                'role_saat_itu' => $user->getRoleNames()->first() ?? 'PPK',
                'status_sebelumnya' => $spp->status,
                'status_baru' => $spp->status,
                'aksi' => 'STANDING_INSTRUCTION_SIGNED_FILE_UPLOADED',
                'catatan' => 'File Standing Instruction bertanda tangan diunggah.',
                'ip_address' => $request->ip(),
            ]);
        });

        return redirect()->back()->with('success', 'File SI bertanda tangan berhasil diunggah. PPK sekarang dapat memverifikasi SPP.');
    }

    public function print(DokumenSpp $spp)
    {
        $si = $spp->standingInstruction;

        if (!$si) {
            return back()->with('error', 'Standing Instruction belum dibuat.');
        }

        $pdf = Pdf::loadView('pdf.standing_instruction', compact('si', 'spp'));

        $nomorSurat = $si->nomor_surat ?: 'DRAFT';
        $safeNomorSurat = preg_replace('/[\/\\\\]+/', '-', $nomorSurat);
        $safeNomorSurat = preg_replace('/[^A-Za-z0-9._-]+/', '_', $safeNomorSurat) ?: 'DRAFT';

        return $pdf->stream('Standing_Instruction_' . $safeNomorSurat . '.pdf');
    }

    public function signedFile(DokumenSpp $spp)
    {
        $user = Auth::user();
        if (!$this->canViewStandingInstruction($user)) {
            abort(403);
        }

        $arsip = $spp->arsipDokumen()
            ->where('jenis_dokumen', DokumenSpp::STANDING_INSTRUCTION_SIGNED_ARCHIVE_TYPE)
            ->where('is_active', true)
            ->latest('uploaded_at')
            ->firstOrFail();

        $disk = Storage::disk($arsip->disk ?: 'public');
        abort_unless($disk->exists($arsip->path_file), 404);

        return $disk->response(
            $arsip->path_file,
            $arsip->nama_file_asli ?: basename($arsip->path_file)
        );
    }

    private function resolveReturnUrl(Request $request, DokumenSpp $spp): string
    {
        foreach ([$request->query('return_to'), $request->input('return_to'), url()->previous()] as $candidate) {
            $safeUrl = $this->safeInternalReturnUrl($candidate, $request);

            if ($safeUrl) {
                return $safeUrl;
            }
        }

        return $this->fallbackReturnUrl($spp);
    }

    private function safeInternalReturnUrl(?string $url, Request $request): ?string
    {
        if (!is_string($url) || trim($url) === '') {
            return null;
        }

        $parts = parse_url($url);
        if ($parts === false) {
            return null;
        }

        if (isset($parts['host']) && $parts['host'] !== $request->getHost()) {
            return null;
        }

        $path = $parts['path'] ?? '';
        if (str_contains($path, '/standing-instructions/')) {
            return null;
        }

        return $url;
    }

    private function fallbackReturnUrl(DokumenSpp $spp): string
    {
        $spp->loadMissing('tagihan');

        return match ($spp->tagihan?->tipe_tagihan) {
            'HONORARIUM' => route('verifikasi-spp.honor.detail', $spp->id),
            'PERJALDIN' => route('verifikasi-spp.perjaldin.show', $spp->id),
            'KONTRAK' => route('verifikasi-spp.kontrak.show', $spp->id),
            default => route('dashboard'),
        };
    }

    private function canManageStandingInstruction(?User $user): bool
    {
        return $user && ($user->hasRole('PPK') || $user->hasRole('Super Admin'));
    }

    private function canViewStandingInstruction(?User $user): bool
    {
        return $user && (
            $user->hasRole('PPK')
            || $user->hasRole('Super Admin')
            || $user->hasRole('Koordinator Keuangan')
            || $user->hasRole('Kepala Subbagian Keuangan dan Tata Usaha')
        );
    }
}
