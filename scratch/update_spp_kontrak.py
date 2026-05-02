import re
import os

file_path = "app/Http/Controllers/SppVerifikasiController.php"
with open(file_path, "r") as f:
    content = f.read()

# I will replace everything between `private const SPP_KONTRAK_WORKFLOW = 'SPP_KONTRAK_PPK';` and the first helper method.
# Let's find the helper methods.
match = re.search(r'    /\*\*\n     \* Helper: Ensure', content)
if not match:
    print("Could not find helper methods")
else:
    end_index = match.start()
    
    # Let's see what is before `end_index`
    # print(content[:end_index])

    new_methods = """
    private function activeRoleCodes(User $user): array
    {
        $roles = [];
        if ($user->hasRole('PPK')) $roles[] = 'PPK';
        if ($user->hasRole('Koordinator Keuangan')) $roles[] = 'Koordinator Keuangan';
        if ($user->hasRole('Kepala Subbagian Keuangan dan Tata Usaha')) $roles[] = 'Kepala Subbagian Keuangan dan Tata Usaha';
        
        return $roles;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $roleCodes = $this->activeRoleCodes($user);
        
        abort_unless(count($roleCodes) > 0, 403, 'Akses ditolak.');

        $query = Spp::with([
            'tagihan.pihak',
            'tagihan.detailKontrak.kontrakTermin.kontrak.vendor',
            'tagihan.dipaRevisionItem.coa',
            'dipaRevisionItem.coa',
            'dibuatOleh',
            'workflowInstances' => fn($q) => $q->latest()->limit(1),
            'workflowInstances.approvals.actedByUser'
        ])
        ->whereHas('tagihan', fn ($q) => $q->where('tipe_tagihan', 'KONTRAK'))
        ->whereHas('workflowInstances.definition', fn ($q) => $q->where('kode', self::SPP_KONTRAK_WORKFLOW))
        ->whereNotIn('status', ['DRAFT']);

        // Filter berdasarkan Assigned Role/User
        $query->whereHas('workflowInstances', function ($q) use ($roleCodes, $user) {
            $q->whereHas('approvals', function ($q2) use ($roleCodes, $user) {
                $q2->whereIn('role_code', $roleCodes)
                   ->where(function ($q3) use ($user) {
                       $q3->whereNull('assigned_user_id')
                          ->orWhere('assigned_user_id', $user->id);
                   });
            });
        });

        $allSpps = $query->latest()->get();

        $listMenunggu = collect();
        $listDisetujui = collect();
        $listRevisi = collect();
        $listSelesai = collect();

        foreach ($allSpps as $spp) {
            $wf = $spp->workflowInstances->first();
            if (!$wf) continue;

            $myApprovals = $wf->approvals->whereIn('role_code', $roleCodes);
            if ($myApprovals->isEmpty()) continue;

            // Jika ada multi-role, kita utamakan yang PENDING
            $approval = $myApprovals->where('status', 'PENDING')->first() ?? $myApprovals->first();

            if ($wf->status === 'REVISION') {
                $statusFinal = 'Perlu Revisi';
            } elseif ($wf->status === 'APPROVED') {
                $statusFinal = 'Selesai Diverifikasi';
            } else {
                $pendingCount = $wf->approvals->where('status', 'PENDING')->count();
                $statusFinal = $pendingCount > 1 ? 'Menunggu Verifikasi' : 'Dalam Proses';
            }

            $spp->statusFinal = $statusFinal;
            $spp->myApprovalStatus = $approval->status;
            
            // For view compatibility
            $spp->ppkApprovalStatus = $wf->approvals->where('role_code', 'PPK')->first()?->status ?? 'N/A';
            $spp->kasubbagApprovalStatus = $wf->approvals->where('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha')->first()?->status ?? 'N/A';
            $spp->koordinatorApprovalStatus = $wf->approvals->where('role_code', 'Koordinator Keuangan')->first()?->status ?? 'N/A';

            if ($approval->status === 'PENDING') {
                $listMenunggu->push($spp);
            } elseif ($approval->status === 'APPROVED') {
                $listDisetujui->push($spp);
            } elseif ($approval->status === 'REVISION') {
                $listRevisi->push($spp);
            }

            if ($statusFinal === 'Selesai Diverifikasi') {
                $listSelesai->push($spp);
            }
        }

        $viewSpps = match($request->get('status', 'Semua')) {
            'Pending' => $listMenunggu,
            'Approved' => $listDisetujui,
            'Revisi' => $listRevisi,
            default => $allSpps // wait, should be merged of all valid for user?
        };
        if ($request->get('status', 'Semua') == 'Semua') {
             // to avoid duplicates if one user has 2 roles, the grouping above already mapped 1 SPP to 1 status based on prioritized approval.
             $viewSpps = $allSpps;
        }

        return view('verifikasi_kasubag.spp_index', [
            'viewSpps' => $viewSpps,
            'countPending' => $listMenunggu->count(),
            'countApprovedMe' => $listDisetujui->count(),
            'countRevisi' => $listRevisi->count(),
            'countSelesai' => $listSelesai->unique('id')->count(),
            'roleLabel' => 'Verifikator SPP',
            'indexRoute' => 'verifikasi-spp.kontrak.index',
            'showRoute' => 'verifikasi-spp.kontrak.show'
        ]);
    }

    public function show($id)
    {
        $user = Auth::user();
        $roleCodes = $this->activeRoleCodes($user);

        abort_unless(count($roleCodes) > 0, 403, 'Akses ditolak.');

        $spp = Spp::with([
            'tagihan.pihak',
            'tagihan.detailKontrak.kontrakTermin.kontrak.vendor',
            'tagihan.dipaRevisionItem.coa',
            'tagihan.potonganTagihan.pajak',
            'tagihan.potonganTagihan.akunPotongan',
            'dipaRevisionItem.coa',
            'arsipDokumen',
            'workflowInstances' => fn($q) => $q->latest()->limit(1),
            'workflowInstances.approvals.actedByUser'
        ])->findOrFail($id);

        $wf = $spp->workflowInstances->first();
        abort_unless($wf, 404, 'Workflow tidak ditemukan.');

        $activeRoleApprovals = [];
        
        // Populate activeRoleApprovals based on user's active roles
        foreach ($roleCodes as $rc) {
            $approval = $wf->approvals->where('role_code', $rc)->first();
            if ($approval && $approval->status === 'PENDING' && $wf->status === 'IN_PROGRESS' && (int)$wf->step_saat_ini === (int)$approval->urutan_step) {
                $activeRoleApprovals[] = [
                    'role' => $rc,
                    'approval_id' => $approval->id,
                    'approveRoute' => route('verifikasi-spp.kontrak.approve', $id),
                    'revisiRoute' => route('verifikasi-spp.kontrak.revisi', $id)
                ];
            }
        }

        $latestRevisionNote = $wf->approvals->where('status', 'REVISION')->sortByDesc('acted_at')->first();

        // Status overall
        if ($wf->status === 'REVISION') {
            $statusFinal = 'Perlu Revisi';
        } elseif ($wf->status === 'APPROVED') {
            $statusFinal = 'Selesai Diverifikasi';
        } else {
            $pendingCount = $wf->approvals->where('status', 'PENDING')->count();
            $statusFinal = $pendingCount > 1 ? 'Menunggu Verifikasi' : 'Dalam Proses';
        }

        // Untuk visual timeline kasubbag
        $kasubbagApproval = $wf->approvals->where('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha')->first();
        $ppkApproval = $wf->approvals->where('role_code', 'PPK')->first();
        $koordinatorApproval = $wf->approvals->where('role_code', 'Koordinator Keuangan')->first();

        return view('verifikasi_kasubag.spp_show', compact(
            'spp', 'wf', 'activeRoleApprovals', 'latestRevisionNote', 'statusFinal',
            'kasubbagApproval', 'ppkApproval', 'koordinatorApproval'
        ));
    }

    public function approve(Request $request, $id)
    {
        $spp = Spp::with('tagihan')->findOrFail($id);
        $this->ensureKontrakSpp($spp);

        $approvalId = $request->input('approval_id');
        abort_unless($approvalId, 400, 'Approval ID diperlukan.');

        try {
            app(WorkflowService::class)->approveCurrentStep($spp, Auth::id(), 'Dokumen SPP disetujui.', $approvalId);
            $workflowFullyApproved = $this->finalizeWorkflowIfComplete($spp);
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses persetujuan: ' . $e->getMessage());
        }

        if ($workflowFullyApproved) {
            $spp->update(['status' => $this->isPerjaldinSpp($spp) ? 'DISETUJUI_SPP' : 'APPROVED']);
            $this->syncParentTagihanAfterSppFinal($spp);
        } else {
            // Kita bisa atur status intermediate jika perlu, tapi 'Dalam Proses' / 'PENDING_KASUBBAG' sudah cukup
            if ($spp->status == 'Menunggu Verifikasi') {
                $spp->update(['status' => 'Dalam Proses']);
            }
        }

        $this->syncPerjaldinKomponenStatus($spp);

        $roleSaAtItu = Auth::user()?->getRoleNames()->first() ?? 'Verifikator';

        LogStatusDokumen::create([
            'dokumen_type' => DokumenSpp::class,
            'dokumen_id' => $spp->id,
            'user_id' => Auth::id(),
            'role_saat_itu' => $roleSaAtItu,
            'status_sebelumnya' => 'Menunggu Verifikasi',
            'status_baru' => $spp->status,
            'aksi' => 'APPROVE_SPP',
            'catatan' => 'Dokumen SPP disetujui oleh ' . $roleSaAtItu,
            'ip_address' => request()->ip(),
        ]);

        return back()->with('success', 'Dokumen berhasil disetujui.');
    }

    public function revisi(Request $request, $id)
    {
        $request->validate(['catatan_revisi' => 'required|string|max:1000']);

        $spp = Spp::with('tagihan')->findOrFail($id);
        $this->ensureKontrakSpp($spp);

        $approvalId = $request->input('approval_id');
        abort_unless($approvalId, 400, 'Approval ID diperlukan.');

        try {
            app(WorkflowService::class)->rejectCurrentStep($spp, Auth::id(), $request->catatan_revisi, $approvalId);
            $spp->update(['status' => 'REVISION']);
            
            // Sync jika perjaldin
            if ($this->isPerjaldinSpp($spp)) {
                $spp->tagihan()->update(['status' => 'REVISION']);
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses revisi: ' . $e->getMessage());
        }

        $roleSaAtItu = Auth::user()?->getRoleNames()->first() ?? 'Verifikator';

        LogStatusDokumen::create([
            'dokumen_type' => DokumenSpp::class,
            'dokumen_id' => $spp->id,
            'user_id' => Auth::id(),
            'role_saat_itu' => $roleSaAtItu,
            'status_sebelumnya' => 'Menunggu Verifikasi',
            'status_baru' => 'REVISION',
            'aksi' => 'REVISI_SPP',
            'catatan' => $request->catatan_revisi,
            'ip_address' => request()->ip(),
        ]);

        return redirect()->route('verifikasi-spp.kontrak.index')->with('success', 'Dokumen dikembalikan untuk revisi.');
    }
"""

    start_index = content.find("private const SPP_KONTRAK_WORKFLOW = 'SPP_KONTRAK_PPK';")
    if start_index == -1:
        print("Could not find const SPP_KONTRAK_WORKFLOW")
    else:
        start_index += len("private const SPP_KONTRAK_WORKFLOW = 'SPP_KONTRAK_PPK';")
        
        final_content = content[:start_index] + "\n" + new_methods + "\n" + content[end_index:]
        with open(file_path, "w") as f:
            f.write(final_content)
        print("SppVerifikasiController updated successfully!")
