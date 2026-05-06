<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Tagihan;
use App\Models\DetailHonorarium;
use App\Models\MasterPersonelEksternal;
use App\Models\LogStatusDokumen;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Support\DipaBudgetOptionService;
use App\Notifications\WorkflowNotification;
use App\Services\DocumentArchiveService;
use App\Services\WorkflowService;

class HonorariumController extends Controller
{
    // Verifikasi routes (pendingPpk, verifyPpk, approvePpk, rejectPpk) dihapus karena dipisah ke PpkHonorariumVerifikasiController dan BendaharaHonorariumVerifikasiController

    public function index()
    {
        $tagihans = Tagihan::where('tipe_tagihan', 'HONORARIUM')
            ->with(['detailHonorarium', 'logs' => fn($q) => $q->latest()->limit(1)])
            ->latest()
            ->get();

        return view('honorarium.index', compact('tagihans'));
    }

    public function create()
    {
        $budgetGroups = DipaBudgetOptionService::groupedOptions();
        $nextNumber = $this->generateNextNumber();
        $tarifPajaks = \App\Models\MasterTarifPajak::where('status_aktif', true)
            ->where('kode_pajak', 'like', 'PPH%')
            ->orderBy('kode_pajak')->get();
        $ppkUsers = User::role('PPK')->get();
        $bendaharaUsers = User::role('Bendahara Pengeluaran')->get();

        return view('honorarium.create', compact('budgetGroups', 'nextNumber', 'tarifPajaks', 'ppkUsers', 'bendaharaUsers'));
    }

    public function store(Request $request)
    {
        // Strip commas from currency inputs
        $input = $request->all();
        if (isset($input['items']) && is_array($input['items'])) {
            foreach ($input['items'] as &$item) {
                foreach (['nilai_honor', 'pph'] as $field) {
                    if (isset($item[$field])) $item[$field] = str_replace(',', '', $item[$field]);
                }
            }
            $request->merge($input);
        }

        $request->validate([
            'deskripsi' => 'required|string|max:255',
            'nama_supplier' => 'required|string|max:150',
            'dipa_revision_item_id' => 'required|exists:dipa_revision_items,id',
            'items' => 'required|array|min:1',
            'items.*.nama_personel' => 'required|string|max:255',
            'items.*.nrp_nip' => 'nullable|string|max:100',
            'items.*.pangkat_korp' => 'nullable|string|max:100',
            'items.*.jabatan' => 'nullable|string|max:100',
            'items.*.nilai_honor' => 'required|numeric|min:0',
            'items.*.pph' => 'nullable|numeric|min:0',
            'items.*.rekening' => 'nullable|string|max:100',
            'items.*.jenis_bank' => 'nullable|string|max:50',
            'items.*.nama_rekening' => 'nullable|string|max:100',
            'items.*.no_hp' => 'nullable|string|max:50',
            'file_sk' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'ppk_id' => 'required|exists:users,id',
            'bendahara_pengeluaran_id' => 'required|exists:users,id',
        ]);

        try {
            DB::beginTransaction();
            $selectedItem = DipaBudgetOptionService::resolveActiveItem($request->dipa_revision_item_id);

            $totalBruto = collect($request->items)->sum(fn($i) => (float)($i['nilai_honor'] ?? 0));
            $totalPph = collect($request->items)->sum(fn($i) => (float)($i['pph'] ?? 0));
            $totalNetto = $totalBruto - $totalPph;

            $tahun = date('Y');
            $urut = Tagihan::withTrashed()->whereYear('created_at', $tahun)->where('tipe_tagihan', 'HONORARIUM')->count() + 1;
            $nomorTagihan = 'HON-' . $tahun . '-' . str_pad($urut, 4, '0', STR_PAD_LEFT);

            $status = 'DRAFT';

            $ppk = User::find($request->ppk_id);
            $bendahara = User::find($request->bendahara_pengeluaran_id);

            $tagihan = Tagihan::create([
                'nomor_tagihan' => $nomorTagihan,
                'tipe_tagihan' => 'HONORARIUM',
                'master_dipa_id' => $selectedItem->dipaRevision->master_dipa_id,
                'dipa_revision_item_id' => $selectedItem->id,
                'deskripsi' => $request->deskripsi,
                'nama_supplier' => $request->nama_supplier,
                'total_bruto' => $totalBruto,
                'total_potongan' => $totalPph,
                'total_netto' => $totalNetto,
                'status' => $status,
                'created_by' => auth()->id(),
                'ppk_user_id' => $ppk?->id,
                'ppk_nama_snapshot' => $ppk?->name,
                'ppk_nip_snapshot' => $ppk?->nip,
                'bendahara_pengeluaran_user_id' => $bendahara?->id,
                'bendahara_pengeluaran_nama_snapshot' => $bendahara?->name,
                'bendahara_pengeluaran_nip_snapshot' => $bendahara?->nip,
            ]);

            foreach ($request->items as $itemData) {
                DetailHonorarium::create([
                    'tagihan_id' => $tagihan->id,
                    'nama_personel' => $itemData['nama_personel'],
                    'nrp_nip' => $itemData['nrp_nip'] ?? null,
                    'pangkat_korp' => $itemData['pangkat_korp'] ?? null,
                    'jabatan' => $itemData['jabatan'] ?? null,
                    'nilai_honor' => $itemData['nilai_honor'] ?? 0,
                    'pph' => $itemData['pph'] ?? 0,
                    'rekening' => $itemData['rekening'] ?? '',
                    'jenis_bank' => $itemData['jenis_bank'] ?? '',
                    'nama_rekening' => $itemData['nama_rekening'] ?? '',
                    'no_hp' => $itemData['no_hp'] ?? '',
                ]);
            }

            LogStatusDokumen::create([
                'dokumen_type' => Tagihan::class,
                'dokumen_id' => $tagihan->id,
                'user_id' => Auth::id(),
                'role_saat_itu' => Auth::user()->getRoleNames()->first() ?? '-',
                'status_sebelumnya' => null,
                'status_baru' => $status,
                'aksi' => 'SIMPAN_DRAFT',
                'catatan' => 'Tagihan Honorarium dibuat sebagai Draft.',
                'ip_address' => $request->ip(),
            ]);

            if ($request->hasFile('file_sk')) {
                app(DocumentArchiveService::class)->upload(
                    $tagihan,
                    'SK Honorarium',
                    $request->file('file_sk'),
                    [
                        'directory' => 'arsip-dokumen/Tagihan',
                        'uploaded_by' => Auth::id(),
                        'keterangan' => 'File SK otomatis terunggah saat pembuatan NPI Honorarium',
                    ]
                );
            }


            DB::commit();
            return redirect()->route('honorarium.index')->with('success', 'Data honorarium berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Gagal menyimpan: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'HONORARIUM')
            ->with([
                'detailHonorarium',
                'logs' => fn($q) => $q->latest(),
                'workflowInstances.definition',
                'workflowInstances.approvals.assignedUser',
                'workflowInstances.approvals.actedByUser',
                'creator'
            ])
            ->findOrFail($id);

        $activeWorkflowInstance = $tagihan->workflowInstances->filter(fn($w) => $w->definition?->kode === 'TAGIHAN_HONORARIUM')->sortByDesc('created_at')->first();
        $approvals = collect($activeWorkflowInstance?->approvals ?? []);
        $ppkApproval = $approvals->firstWhere('role_code', 'PPK');
        $bendaharaApproval = $approvals->firstWhere('role_code', 'Bendahara Pengeluaran');

        return view('honorarium.show', compact('tagihan', 'ppkApproval', 'bendaharaApproval'));
    }

    public function edit($id)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'HONORARIUM')
            ->with('detailHonorarium')
            ->findOrFail($id);

        if (!in_array($tagihan->status, ['DRAFT', 'DITOLAK_PPK'])) {
            return redirect()->route('honorarium.index')
                ->with('error', 'Data dengan status ' . $tagihan->status . ' tidak bisa diedit.');
        }

        $budgetGroups = DipaBudgetOptionService::groupedOptions();
        $tarifPajaks = \App\Models\MasterTarifPajak::where('status_aktif', true)
            ->where('kode_pajak', 'like', 'PPH%')
            ->orderBy('kode_pajak')->get();

        $ppkUsers = User::role('PPK')->get();
        $bendaharaUsers = User::role('Bendahara Pengeluaran')->get();

        return view('honorarium.edit', compact('tagihan', 'budgetGroups', 'tarifPajaks', 'ppkUsers', 'bendaharaUsers'));
    }

    public function update(Request $request, $id)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'HONORARIUM')->findOrFail($id);

        if (!in_array($tagihan->status, ['DRAFT', 'DITOLAK_PPK'])) {
            return redirect()->route('honorarium.index')
                ->with('error', 'Data dengan status ' . $tagihan->status . ' tidak bisa diedit.');
        }

        $input = $request->all();
        if (isset($input['items']) && is_array($input['items'])) {
            foreach ($input['items'] as &$item) {
                foreach (['nilai_honor', 'pph'] as $field) {
                    if (isset($item[$field])) $item[$field] = str_replace(',', '', $item[$field]);
                }
            }
            $request->merge($input);
        }

        $request->validate([
            'deskripsi' => 'required|string|max:255',
            'nama_supplier' => 'required|string|max:150',
            'dipa_revision_item_id' => 'required|exists:dipa_revision_items,id',
            'items' => 'required|array|min:1',
            'items.*.nama_personel' => 'required|string|max:255',
            'items.*.nrp_nip' => 'nullable|string|max:100',
            'items.*.pangkat_korp' => 'nullable|string|max:100',
            'items.*.jabatan' => 'nullable|string|max:100',
            'items.*.nilai_honor' => 'required|numeric|min:0',
            'items.*.pph' => 'nullable|numeric|min:0',
            'items.*.rekening' => 'nullable|string|max:100',
            'items.*.jenis_bank' => 'nullable|string|max:50',
            'items.*.nama_rekening' => 'nullable|string|max:100',
            'items.*.no_hp' => 'nullable|string|max:50',
            'file_sk' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'ppk_id' => 'required|exists:users,id',
            'bendahara_pengeluaran_id' => 'required|exists:users,id',
        ]);

        try {
            DB::beginTransaction();
            $selectedItem = DipaBudgetOptionService::resolveActiveItem($request->dipa_revision_item_id);

            $totalBruto = collect($request->items)->sum(fn($i) => (float)($i['nilai_honor'] ?? 0));
            $totalPph = collect($request->items)->sum(fn($i) => (float)($i['pph'] ?? 0));
            $totalNetto = $totalBruto - $totalPph;

            $statusLama = $tagihan->status;
            $statusBaru = 'DRAFT';

            $ppk = User::find($request->ppk_id);
            $bendahara = User::find($request->bendahara_pengeluaran_id);

            $tagihan->update([
                'master_dipa_id' => $selectedItem->dipaRevision->master_dipa_id,
                'dipa_revision_item_id' => $selectedItem->id,
                'deskripsi' => $request->deskripsi,
                'nama_supplier' => $request->nama_supplier,
                'total_bruto' => $totalBruto,
                'total_potongan' => $totalPph,
                'total_netto' => $totalNetto,
                'status' => $statusBaru,
                'ppk_user_id' => $ppk?->id,
                'ppk_nama_snapshot' => $ppk?->name,
                'ppk_nip_snapshot' => $ppk?->nip,
                'bendahara_pengeluaran_user_id' => $bendahara?->id,
                'bendahara_pengeluaran_nama_snapshot' => $bendahara?->name,
                'bendahara_pengeluaran_nip_snapshot' => $bendahara?->nip,
            ]);

            // Delete old, re-insert
            DetailHonorarium::where('tagihan_id', $tagihan->id)->delete();

            foreach ($request->items as $itemData) {
                DetailHonorarium::create([
                    'tagihan_id' => $tagihan->id,
                    'nama_personel' => $itemData['nama_personel'],
                    'nrp_nip' => $itemData['nrp_nip'] ?? null,
                    'pangkat_korp' => $itemData['pangkat_korp'] ?? null,
                    'jabatan' => $itemData['jabatan'] ?? null,
                    'nilai_honor' => $itemData['nilai_honor'] ?? 0,
                    'pph' => $itemData['pph'] ?? 0,
                    'rekening' => $itemData['rekening'] ?? '',
                    'jenis_bank' => $itemData['jenis_bank'] ?? '',
                    'nama_rekening' => $itemData['nama_rekening'] ?? '',
                    'no_hp' => $itemData['no_hp'] ?? '',
                ]);
            }

            LogStatusDokumen::create([
                'dokumen_type' => Tagihan::class,
                'dokumen_id' => $tagihan->id,
                'user_id' => Auth::id(),
                'role_saat_itu' => Auth::user()->getRoleNames()->first() ?? '-',
                'status_sebelumnya' => $statusLama,
                'status_baru' => $statusBaru,
                'aksi' => 'UPDATE_DRAFT',
                'catatan' => 'Data honorarium diperbarui oleh PPABP.',
                'ip_address' => $request->ip(),
            ]);

            if ($request->hasFile('file_sk')) {
                $docService = app(DocumentArchiveService::class);
                $existingSk = $tagihan->arsipDokumen()->where('jenis_dokumen', 'SK Honorarium')->first();
                if ($existingSk) {
                    $docService->replace(
                        $tagihan,
                        'SK Honorarium',
                        $request->file('file_sk'),
                        [
                            'directory' => 'arsip-dokumen/Tagihan',
                            'uploaded_by' => Auth::id(),
                            'keterangan' => 'Pembaruan File SK Honorarium',
                        ]
                    );
                } else {
                    $docService->upload(
                        $tagihan,
                        'SK Honorarium',
                        $request->file('file_sk'),
                        [
                            'directory' => 'arsip-dokumen/Tagihan',
                            'uploaded_by' => Auth::id(),
                            'keterangan' => 'File SK diunggah pada saat update Honorarium',
                        ]
                    );
                }
            }


            DB::commit();
            return redirect()->route('honorarium.index')->with('success', 'Data honorarium berhasil diupdate.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Gagal memperbarui: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'HONORARIUM')->findOrFail($id);

        if (!in_array($tagihan->status, ['DRAFT', 'DITOLAK_PPK'])) {
            return redirect()->route('honorarium.index')
                ->with('error', 'Data dengan status ' . $tagihan->status . ' tidak bisa dihapus.');
        }

        DetailHonorarium::where('tagihan_id', $tagihan->id)->delete();
        $tagihan->logs()->delete();
        $tagihan->delete();

        return redirect()->route('honorarium.index')->with('success', 'Data honorarium berhasil dihapus.');
    }

    public function uploadDokumen(Request $request, $id)
    {
        $request->validate([
            'jenis_dokumen' => 'required|string|in:Daftar Nominatif Bertandatangan,Dokumen Honorarium Bertandatangan,Lainnya',
            'file_dokumen' => 'required|file|mimes:pdf|max:10240',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $tagihan = Tagihan::where('tipe_tagihan', 'HONORARIUM')->findOrFail($id);

        if ($tagihan->status !== 'DRAFT') {
            return redirect()->back()->withErrors(['error' => 'Dokumen hanya bisa diunggah pada saat status DRAFT.']);
        }

        try {
            $docService = app(DocumentArchiveService::class);
            $docService->upload(
                $tagihan,
                $request->jenis_dokumen,
                $request->file('file_dokumen'),
                [
                    'directory' => 'arsip-dokumen/Tagihan/' . $tagihan->nomor_tagihan,
                    'uploaded_by' => Auth::id(),
                    'keterangan' => $request->keterangan ?? 'Diunggah oleh PPABP',
                ]
            );

            return redirect()->back()->with('success', "Dokumen {$request->jenis_dokumen} berhasil diunggah.");
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Gagal mengunggah dokumen: ' . $e->getMessage()]);
        }
    }

    public function deleteDokumen($id, $arsip_id)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'HONORARIUM')->findOrFail($id);

        if ($tagihan->status !== 'DRAFT') {
            return redirect()->back()->withErrors(['error' => 'Dokumen hanya bisa dihapus pada saat status DRAFT.']);
        }

        try {
            $arsip = $tagihan->arsipDokumen()->findOrFail($arsip_id);
            $docService = app(DocumentArchiveService::class);
            $docService->delete($arsip);

            return redirect()->back()->with('success', 'Dokumen berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Gagal menghapus dokumen: ' . $e->getMessage()]);
        }
    }

    public function submitVerifikasi(Request $request, $id)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'HONORARIUM')->findOrFail($id);

        if ($tagihan->status !== 'DRAFT') {
            return redirect()->back()->withErrors(['error' => 'Honorarium tidak dalam status DRAFT.']);
        }

        // Cek dokumen wajib
        $requiredDocs = ['Daftar Nominatif Bertandatangan', 'Dokumen Honorarium Bertandatangan'];
        $uploadedDocs = $tagihan->arsipDokumen()->pluck('jenis_dokumen')->toArray();

        foreach ($requiredDocs as $doc) {
            if (!in_array($doc, $uploadedDocs)) {
                return redirect()->back()->withErrors(['error' => "Dokumen wajib \"{$doc}\" belum diunggah."]);
            }
        }

        DB::beginTransaction();
        try {
            // Update tagihan status
            $tagihan->update(['status' => 'PENDING_VERIFIKASI']);

            // Start Parallel Workflow
            $workflowService = app(WorkflowService::class);
            $workflowService->startWorkflow('TAGIHAN_HONORARIUM', $tagihan);

            LogStatusDokumen::create([
                'dokumen_type' => Tagihan::class,
                'dokumen_id' => $tagihan->id,
                'user_id' => Auth::id(),
                'role_saat_itu' => Auth::user()->getRoleNames()->first() ?? '-',
                'status_sebelumnya' => 'DRAFT',
                'status_baru' => 'PENDING_VERIFIKASI',
                'aksi' => 'SUBMIT_WORKFLOW',
                'catatan' => 'PPABP mengajukan dokumen untuk verifikasi paralel PPK dan Bendahara.',
                'ip_address' => $request->ip(),
            ]);

            DB::commit();
            return redirect()->route('honorarium.show', $tagihan->id)->with('success', 'Dokumen honorarium berhasil diajukan untuk verifikasi.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Gagal submit workflow: ' . $e->getMessage()]);
        }
    }

    public function exportPdf($id)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'HONORARIUM')
            ->with(['detailHonorarium', 'dipaRevisionItem.coa'])
            ->findOrFail($id);

        $data = [
            'tagihan' => $tagihan,
            'details' => $tagihan->detailHonorarium,
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('honorarium.pdf', $data);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->stream('Honorarium_' . \Illuminate\Support\Str::slug($tagihan->nomor_tagihan, '_') . '.pdf');
    }

    public function exportNominatifPdf($id)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'HONORARIUM')
            ->findOrFail($id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('honorarium.pdf-nominatif', [
            'tagihan' => $tagihan,
        ]);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('Nominatif_Honorarium_' . \Illuminate\Support\Str::slug($tagihan->nomor_tagihan, '_') . '.pdf');
    }

    private function generateNextNumber(): string
    {
        $year = now()->format('Y');
        $count = Tagihan::whereYear('created_at', $year)
            ->where('tipe_tagihan', 'HONORARIUM')
            ->count();
        return 'HON-' . $year . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }

    private function notifyRoles(array $roles, string $judul, string $pesan, ?string $linkUrl = null): void
    {
        Notification::send(User::role($roles)->get(), new WorkflowNotification([
            'title' => $judul,
            'message' => $pesan,
            'url' => $linkUrl,
            'icon' => 'notifications',
            'color' => 'primary',
        ]));
    }
}
