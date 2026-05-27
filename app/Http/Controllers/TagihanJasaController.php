<?php

namespace App\Http\Controllers;

use App\Models\LayananJasa;
use App\Models\KontrakMitraJasa;
use App\Models\MitraJasa;
use App\Models\MitraJasaPenjualan;
use App\Models\TagihanJasa;
use App\Models\User;
use App\Services\WhatsappService;
use App\Services\BtnVirtualAccountService;
use App\Services\WorkflowService;
use App\Services\JasaAccessService;
use App\Services\TagihanJasaCalculationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;

class TagihanJasaController extends Controller
{
    public function index(Request $request)
    {
        $tagihans = $this->hideKonsesiSetupRows(
            $this->scopeForAdminJasa(TagihanJasa::with(['mitra', 'mitraLegacy', 'creator']))
        )
            ->whereIn('tipe_pnbp', ['FUNGSI', 'NON_FUNGSI'])
            ->latest()
            ->get();

        return view('tagihan_jasa.index', compact('tagihans'));
    }

    public function create(Request $request, JasaAccessService $jasaAccessService)
    {
        abort_unless($this->canCreateTagihanJasa(), 403, 'Koordinator Jasa hanya dapat melihat dan memverifikasi tagihan.');

        $tipe = $request->query('tipe', 'FUNGSI');
        $mode = $request->query('mode') === 'konsesi' ? 'konsesi' : 'pnbp';
        $prefillTagihan = null;

        if ($request->filled('penjualan_id')) {
            $mode = 'konsesi';
            $tipe = 'KONSESI';
            $penjualan = MitraJasaPenjualan::with(['mitraJasa', 'konsesi', 'layananJasa', 'kontrakMitraJasa'])
                ->where('status', 'diverifikasi')
                ->whereNull('tagihan_jasa_id')
                ->findOrFail($request->integer('penjualan_id'));
            abort_unless($penjualan->can_create_tagihan, 403, 'Tagihan untuk laporan ini belum dapat dibuat.');

            $isPjp2uPenjualan = $penjualan->is_pjp2u_report;

            $prefillTagihan = [
                'penjualan_id' => $penjualan->id,
                'mitra_jasa_id' => $penjualan->mitra_jasa_id,
                'kontrak_mitra_jasa_id' => $penjualan->kontrak_mitra_jasa_id,
                'layanan_jasa_id' => $penjualan->layanan_jasa_id,
                'qty' => (float) $penjualan->total_omzet,
                'harga_satuan' => $isPjp2uPenjualan
                    ? (float) ($penjualan->layananJasa?->tarif_dasar ?? 0)
                    : (float) ($penjualan->persentase_konsesi ?? $penjualan->layananJasa?->persentase_konsesi ?? $penjualan->layananJasa?->tarif_dasar ?? 0),
                'kurs' => 1,
                'calculation_mode' => $isPjp2uPenjualan ? 'TARIF' : 'PERSENTASE',
                'satuan' => $penjualan->layananJasa?->satuan,
                'keterangan' => ($isPjp2uPenjualan ? 'Tagihan PAX PJP2U periode ' : 'Tagihan konsesi/penjualan periode ')
                    . optional($penjualan->periode_mulai)->format('d/m/Y')
                    . ' s.d. '
                    . optional($penjualan->periode_selesai)->format('d/m/Y')
                    . ($isPjp2uPenjualan
                        ? ' | Total pax ' . number_format((float) $penjualan->total_omzet, 0, ',', '.')
                        : ' | Omzet Rp ' . number_format((float) $penjualan->total_omzet, 0, ',', '.')),
            ];
        }

        // Prefill dari Laporan Utilitas (Admin Listrik/Air)
        if ($request->filled('utilitas_id')) {
            $utilitas = \App\Models\LaporanUtilitas::with(['mitraJasa', 'layananJasa'])
                ->where('status', 'dikirim_ke_admin_jasa')
                ->findOrFail($request->integer('utilitas_id'));

            $tipe = 'FUNGSI';
            $mode = 'pnbp';

            $deskripsi = $utilitas->tipe_perhitungan === 'flat'
                ? "Pemakaian " . ucfirst($utilitas->jenis) . " (Stan: {$utilitas->stan_awal} → {$utilitas->stan_akhir} = {$utilitas->pemakaian} unit)"
                : "Pemakaian " . ucfirst($utilitas->jenis) . " ({$utilitas->pemakaian} kWh)";

            $prefillTagihan = [
                'utilitas_id' => $utilitas->id,
                'utilitas_jenis' => $utilitas->jenis,
                'mitra_jasa_id' => $utilitas->mitra_jasa_id,
                'layanan_jasa_id' => $utilitas->layanan_jasa_id,
                'qty' => (float) $utilitas->pemakaian,
                'harga_satuan' => 0, // Admin Jasa akan mengisi tarif
                'kurs' => 1,
                'satuan' => $utilitas->layananJasa?->satuan ?? ($utilitas->jenis == 'listrik' ? 'kWh' : 'm³'),
                'keterangan' => $deskripsi . " | Periode {$utilitas->bulan}/{$utilitas->tahun}",
            ];
        }

        $prefillLayananId = $prefillTagihan['layanan_jasa_id'] ?? null;

        // For Admin Jasa: only show mitra that have layanan they manage
        $user = Auth::user();
        $mitraQuery = MitraJasa::query()
            ->with(['kontrak' => fn ($query) => $query->with('layananJasa')->orderByDesc('tanggal_kontrak')->orderByDesc('id')])
            ->where('status_aktif', true);

        if ($user?->hasRole('Admin Jasa') && ! $user->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Koordinator Jasa'])) {
            $adminLayananIds = $user->layananJasaDikelolaAktif()
                ->where('layanan_jasas.is_active', true)
                ->pluck('layanan_jasas.id')
                ->all();
            $mitraQuery->whereHas('layananJasa', fn ($q) => $q->whereIn('layanan_jasas.id', $adminLayananIds));
        }

        $mitras = $mitraQuery->orderBy('nama_mitra')->get();

        $pjp2uLayananIds = LayananJasa::all()->filter(fn($l) => $l->isPjp2u())->pluck('id');

        $eligibleLeafLayanans = LayananJasa::query()
            ->where('is_active', true)
            ->where('is_leaf', true)
            ->where(function ($query) use ($prefillLayananId, $mode) {
                if ($mode === 'konsesi') {
                    $query->where('tipe_layanan', 'KONSESI')
                        ->orWhere('mendukung_konsesi', true)
                        ->orWhere('satuan', 'like', '%\%%');
                } else {
                    $query->where('tipe_layanan', 'PNBP');
                }

                $query->when($prefillLayananId, fn ($subQuery) => $subQuery->orWhere('id', $prefillLayananId));
            })
            ->when($mode !== 'konsesi', function ($query) use ($pjp2uLayananIds, $prefillLayananId) {
                $query->where(function ($q) use ($pjp2uLayananIds, $prefillLayananId) {
                    $q->whereNotIn('id', $pjp2uLayananIds)
                      ->where('mendukung_konsesi', false)
                      ->where('tipe_layanan', '!=', 'KONSESI')
                      ->where('satuan', 'not like', '%\%%');
                      
                    // Pengecualian: Jika prefillLayananId diset, izinkan layanan tersebut meskipun punya tanda %
                    if ($prefillLayananId) {
                        $q->orWhere('id', $prefillLayananId);
                    }
                });
            })
            ->orderBy('level')
            ->orderBy('id')
            ->get();

        $treeLayananIds = $eligibleLeafLayanans->pluck('id')->map(fn ($id) => (int) $id)->all();
        $allTreeCandidates = LayananJasa::query()
            ->where('is_active', true)
            ->get(['id', 'parent_id'])
            ->keyBy('id');

        foreach ($eligibleLeafLayanans as $layanan) {
            $parentId = $layanan->parent_id;
            $guard = 0;

            while ($parentId && $guard < 10) {
                $treeLayananIds[] = (int) $parentId;
                $parent = $allTreeCandidates->get($parentId);
                $parentId = $parent?->parent_id;
                $guard++;
            }
        }

        $layanans = LayananJasa::query()
            ->whereIn('id', array_values(array_unique($treeLayananIds)))
            ->orderBy('level')
            ->orderBy('id')
            ->get();

        $mitraLayananMap = [];
        foreach ($mitras as $mitra) {
            $mitraLayananMap[$mitra->id] = $jasaAccessService
                ->getAllowedLayananForTagihan(Auth::user(), $mitra)
                ->pluck('id')
                ->values()
                ->all();

            if ($prefillTagihan && (int) $prefillTagihan['mitra_jasa_id'] === (int) $mitra->id && $prefillLayananId) {
                $mitraLayananMap[$mitra->id][] = (int) $prefillLayananId;
                $mitraLayananMap[$mitra->id] = array_values(array_unique($mitraLayananMap[$mitra->id]));
            }
        }

        $mitraMetaMap = $mitras->mapWithKeys(function (MitraJasa $mitra) {
            return [
                $mitra->id => [
                    'id' => $mitra->id,
                    'kode_mitra' => $mitra->kode_mitra,
                    'nama_mitra' => $mitra->nama_mitra,
                    'jenis_mitra' => $mitra->jenis_mitra,
                    'npwp' => $mitra->npwp,
                    'email' => $mitra->email,
                    'no_telepon' => $mitra->no_telepon,
                    'alamat' => $mitra->alamat,
                    'nama_penanggung_jawab' => $mitra->nama_penanggung_jawab,
                    'jabatan_penanggung_jawab' => $mitra->jabatan_penanggung_jawab,
                    'kontrak' => $mitra->kontrak->map(fn (KontrakMitraJasa $kontrak) => [
                        'id' => $kontrak->id,
                        'nomor_kontrak' => $kontrak->nomor_kontrak,
                        'nama_kontrak' => $kontrak->nama_kontrak,
                        'jenis_dokumen' => $kontrak->jenis_dokumen,
                        'tanggal_kontrak' => optional($kontrak->tanggal_kontrak)->format('Y-m-d'),
                        'tanggal_mulai' => optional($kontrak->tanggal_mulai)->format('Y-m-d'),
                        'tanggal_selesai' => optional($kontrak->tanggal_selesai)->format('Y-m-d'),
                        'file_url' => $kontrak->file_kontrak ? asset('storage/' . $kontrak->file_kontrak) : null,
                        'status_kontrak' => $kontrak->status_kontrak,
                        'layanan_ids' => $kontrak->layananJasa->pluck('id')->map(fn ($id) => (int) $id)->values(),
                    ])->values(),
                ],
            ];
        });

        return view('tagihan_jasa.create', compact('mitras', 'layanans', 'tipe', 'mode', 'mitraLayananMap', 'mitraMetaMap', 'prefillTagihan'));
    }

    public function store(Request $request, WorkflowService $workflowService, JasaAccessService $jasaAccessService, TagihanJasaCalculationService $calculationService)
    {
        abort_unless($this->canCreateTagihanJasa(), 403, 'Koordinator Jasa hanya dapat melihat dan memverifikasi tagihan.');

        $validated = $request->validate([
            'tipe_pnbp' => ['required', 'in:FUNGSI,NON_FUNGSI,KONSESI'],
            'mitra_jasa_id' => ['required', 'exists:mitra_jasa,id'],
            'tanggal_tagihan' => ['required', 'date'],
            'final_verifier_role' => ['required', 'in:KPA,PLT/PLH'],
            'kontrak_mitra_jasa_id' => ['nullable', 'exists:kontrak_mitra_jasa,id'],
            'penjualan_id' => ['nullable', 'exists:mitra_jasa_penjualan,id'],
            'utilitas_id' => ['nullable', 'exists:laporan_utilitas,id'],
            'layanan' => ['required', 'array', 'min:1'],
            'layanan.*.id' => ['required', 'exists:layanan_jasas,id'],
            'layanan.*.mode' => ['nullable', 'in:TARIF,PERSENTASE'],
            'layanan.*.kode_akun' => ['nullable', 'string', 'max:255'],
            'layanan.*.qty' => ['required', 'numeric', 'min:0'],
            'layanan.*.harga_satuan' => ['required', 'numeric', 'min:0'],
            'layanan.*.kurs' => ['nullable', 'numeric', 'min:0'],
            'layanan.*.keterangan' => ['nullable', 'string', 'max:1000'],
            'layanan.*.calculation_payload' => ['nullable', 'json'],
        ]);

        try {
            $mitra = MitraJasa::findOrFail($validated['mitra_jasa_id']);
            if (! $mitra->status_aktif) {
                return back()->withInput()->with('error', 'Mitra nonaktif tidak dapat dibuatkan tagihan.');
            }

            $penjualan = null;
            if (! empty($validated['penjualan_id'])) {
                $penjualan = MitraJasaPenjualan::where('mitra_jasa_id', $mitra->id)
                    ->where('status', 'diverifikasi')
                    ->whereNull('tagihan_jasa_id')
                    ->findOrFail($validated['penjualan_id']);

                if (! $penjualan->can_create_tagihan) {
                    return back()
                        ->withInput()
                        ->with('error', 'Tagihan untuk laporan ini belum dapat dibuat.');
                }
            }

            $kontrak = null;
            if (! empty($validated['kontrak_mitra_jasa_id'])) {
                $kontrak = KontrakMitraJasa::with('layananJasa')
                    ->where('mitra_jasa_id', $mitra->id)
                    ->findOrFail($validated['kontrak_mitra_jasa_id']);
            }

            if ($kontrak && $kontrak->layananJasa->isNotEmpty()) {
                $kontrakLayananIds = $kontrak->layananJasa->pluck('id')->map(fn ($id) => (int) $id);
                $outsideScope = collect($validated['layanan'])
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->reject(fn ($id) => $kontrakLayananIds->contains($id));

                if ($outsideScope->isNotEmpty()) {
                    return back()
                        ->withInput()
                        ->with('error', 'Kontrak/dokumen dasar yang dipilih tidak mencakup semua layanan pada tagihan.');
                }
            }

            foreach ($validated['layanan'] as $row) {
                if ($penjualan && (int) $row['id'] === (int) $penjualan->layanan_jasa_id) {
                    continue;
                }

                if (! $jasaAccessService->canUseLayananForMitra(Auth::user(), $mitra, (int) $row['id'])) {
                    return back()
                        ->withInput()
                        ->with('error', 'Terdapat layanan yang belum aktif untuk mitra atau belum ditugaskan kepada Admin Jasa login.');
                }
            }

            $requestedLayananIds = collect($validated['layanan'])->pluck('id')->unique()->values();
            $requestedLayanans = LayananJasa::query()
                ->whereIn('id', $requestedLayananIds)
                ->get()
                ->keyBy('id');

            foreach ($validated['layanan'] as $row) {
                if (($row['mode'] ?? 'TARIF') === 'TARIF' && (float) $row['qty'] <= 0) {
                    return back()
                        ->withInput()
                        ->with('error', 'Volume layanan tarif rupiah harus lebih dari 0.');
                }

                if (($row['mode'] ?? 'TARIF') === 'PERSENTASE' && ! $this->canUsePercentageCalculation($requestedLayanans->get($row['id']))) {
                    return back()
                        ->withInput()
                        ->with('error', 'Terdapat layanan yang tidak dapat dihitung sebagai persentase omzet.');
                }
            }

            $tagihan = DB::transaction(function () use ($validated, $workflowService, $kontrak, $penjualan, $calculationService) {
                $layananIds = collect($validated['layanan'])->pluck('id')->unique()->values();
                $layananById = LayananJasa::query()
                    ->whereIn('id', $layananIds)
                    ->get()
                    ->keyBy('id');

                $totalTagihan = collect($validated['layanan'])->sum(function ($row) use ($layananById, $calculationService) {
                    return $calculationService->calculateSubtotal($row, $layananById->get($row['id']));
                });

                $tipe = $validated['tipe_pnbp'];
                $nomorTagihanVariant = $penjualan?->is_pjp2u_report ? 'PJP2U' : null;
                $kpaPenandatangan = $this->getKpaPenandatanganData();

                $tagihan = TagihanJasa::create([
                    'tipe_pnbp' => $tipe,
                    'mitra_jasa_id' => $validated['mitra_jasa_id'],
                    'kontrak_mitra_jasa_id' => $kontrak?->id,
                    'file_kontrak' => $kontrak?->file_kontrak,
                    'nomor_kontrak' => $kontrak?->nomor_kontrak,
                    'tanggal_mulai_kontrak' => optional($kontrak?->tanggal_mulai)->format('Y-m-d'),
                    'tanggal_selesai_kontrak' => optional($kontrak?->tanggal_selesai)->format('Y-m-d'),
                    'nomor_tagihan' => $this->generateNomorTagihan($tipe, $nomorTagihanVariant),
                    'tanggal_surat_pengantar' => $validated['tanggal_tagihan'],
                    'perihal_surat_pengantar' => 'Penyampaian Tagihan PNBP Jasa',
                    'pejabat_penandatangan_nama' => $kpaPenandatangan['nama'],
                    'pejabat_penandatangan_nip' => $kpaPenandatangan['nip'],
                    'pejabat_penandatangan_jabatan' => $kpaPenandatangan['jabatan'],
                    'status_dokumen_pengantar' => 'DRAFT',
                    'tanggal_tagihan' => $validated['tanggal_tagihan'],
                    'total_tagihan' => $totalTagihan,
                    'status' => 'VERIFIKASI_KOORDINATOR',
                    'final_verifier_role' => $validated['final_verifier_role'],
                    'created_by' => Auth::id(),
                ]);

                $tagihan->update([
                    'nomor_surat_pengantar' => $this->generateNomorSuratPengantar($tagihan),
                ]);

                foreach ($validated['layanan'] as $row) {
                    $hargaSatuan = (float) $row['harga_satuan'];
                    $kurs = (float) ($row['kurs'] ?? 1);
                    $layananJasa = $layananById->get($row['id']);
                    $calculationPayload = $calculationService->buildPayload($row, $layananJasa);
                    $qty = (float) $calculationPayload['billable_qty'];
                    $subtotal = $calculationService->calculateSubtotal($row, $layananJasa);

                    $tagihan->details()->create([
                        'layanan_jasa_id' => $row['id'],
                        'kode_akun' => $layananJasa?->kode_pembayaran_lengkap ?: ($layananJasa?->kode_akun ?: ($layananJasa?->kode_mak ?: ($row['kode_akun'] ?? null))),
                        'qty' => $qty,
                        'harga_satuan' => $hargaSatuan,
                        'kurs' => $kurs,
                        'subtotal' => $subtotal,
                        'keterangan' => $row['keterangan'] ?? null,
                        'calculation_payload' => $calculationPayload,
                    ]);
                }

                $workflowService->startWorkflow('TAGIHAN_JASA', $tagihan);

                if ($penjualan) {
                    $penjualan->update([
                        'tagihan_jasa_id' => $tagihan->id,
                        'status' => 'ditagihkan',
                        'updated_by' => Auth::id(),
                    ]);
                }

                // Link Laporan Utilitas jika ada
                if (! empty($validated['utilitas_id'])) {
                    $utilitas = \App\Models\LaporanUtilitas::find($validated['utilitas_id']);
                    if ($utilitas) {
                        $firstDetail = $tagihan->details->first();
                        $utilitas->update([
                            'tagihan_jasa_id' => $tagihan->id,
                            'tarif_per_unit' => $firstDetail?->harga_satuan,
                            'total_biaya' => $tagihan->total_tagihan,
                            'status' => 'ditagihkan',
                        ]);
                    }
                }

                return $tagihan;
            });

            return redirect()
                ->route('tagihan-jasa.show', $tagihan->id)
                ->with('success', 'Tagihan Jasa berhasil dibuat dan masuk alur verifikasi.');
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal membuat tagihan jasa: ' . $e->getMessage());
        }
    }

    public function edit(Request $request, $id, JasaAccessService $jasaAccessService)
    {
        abort_unless($this->canCreateTagihanJasa(), 403, 'Koordinator Jasa hanya dapat melihat dan memverifikasi tagihan.');

        $tagihan = TagihanJasa::with([
            'details.layananJasa',
            'mitra.kontrak.layananJasa',
        ])->findOrFail($id);
        $this->abortIfAdminJasaCannotAccess($tagihan);

        if (! in_array($tagihan->status, ['REVISI', 'DITOLAK'], true)) {
            return redirect()
                ->route('tagihan-jasa.show', $tagihan->id)
                ->with('error', 'Tagihan hanya dapat diedit saat berstatus REVISI atau DITOLAK.');
        }

        $tipe = $tagihan->tipe_pnbp ?: 'FUNGSI';
        $mode = $tipe === 'KONSESI' ? 'konsesi' : 'pnbp';
        $prefillTagihan = [
            'mitra_jasa_id' => $tagihan->mitra_jasa_id,
            'kontrak_mitra_jasa_id' => $tagihan->kontrak_mitra_jasa_id,
        ];
        $detailPrefills = $tagihan->details->map(function ($detail) {
            $payload = is_array($detail->calculation_payload) ? $detail->calculation_payload : [];

            return [
                'layanan_jasa_id' => $detail->layanan_jasa_id,
                'kode_akun' => $detail->kode_akun,
                'qty' => (float) $detail->qty,
                'harga_satuan' => (float) $detail->harga_satuan,
                'kurs' => (float) ($detail->kurs ?: 1),
                'keterangan' => $detail->keterangan,
                'calculation_mode' => ($payload['rule'] ?? null) === 'PERSENTASE' ? 'PERSENTASE' : 'TARIF',
                'calculation_payload' => $payload,
                'satuan' => $detail->layananJasa?->satuan,
            ];
        })->values();

        $prefillLayananIds = $detailPrefills->pluck('layanan_jasa_id')->filter()->map(fn ($id) => (int) $id)->values();
        $user = Auth::user();
        $mitraQuery = MitraJasa::query()
            ->with(['kontrak' => fn ($query) => $query->with('layananJasa')->orderByDesc('tanggal_kontrak')->orderByDesc('id')])
            ->where(function ($query) use ($tagihan) {
                $query->where('status_aktif', true)
                    ->orWhere('id', $tagihan->mitra_jasa_id);
            });

        if ($user?->hasRole('Admin Jasa') && ! $user->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Koordinator Jasa'])) {
            $adminLayananIds = $user->layananJasaDikelolaAktif()
                ->where('layanan_jasas.is_active', true)
                ->pluck('layanan_jasas.id')
                ->all();
            $mitraQuery->whereHas('layananJasa', fn ($q) => $q->whereIn('layanan_jasas.id', $adminLayananIds));
        }

        $mitras = $mitraQuery->orderBy('nama_mitra')->get();
        $pjp2uLayananIds = LayananJasa::all()->filter(fn($l) => $l->isPjp2u())->pluck('id');

        $eligibleLeafLayanans = LayananJasa::query()
            ->where(function ($query) use ($prefillLayananIds, $mode, $pjp2uLayananIds) {
                $query->where(function ($activeQuery) use ($mode, $pjp2uLayananIds) {
                    $activeQuery->where('is_active', true)
                        ->where('is_leaf', true)
                        ->when($mode === 'konsesi', function ($q) {
                            $q->where(function ($sub) {
                                $sub->where('tipe_layanan', 'KONSESI')
                                    ->orWhere('mendukung_konsesi', true)
                                    ->orWhere('satuan', 'like', '%\%%');
                            });
                        })
                        ->when($mode !== 'konsesi', function ($q) use ($pjp2uLayananIds) {
                            $q->whereNotIn('id', $pjp2uLayananIds)
                                ->where('mendukung_konsesi', false)
                                ->where('tipe_layanan', '!=', 'KONSESI')
                                ->where('satuan', 'not like', '%\%%');
                        });
                });

                if ($prefillLayananIds->isNotEmpty()) {
                    $query->orWhereIn('id', $prefillLayananIds);
                }
            })
            ->orderBy('level')
            ->orderBy('id')
            ->get();

        $treeLayananIds = $eligibleLeafLayanans->pluck('id')->map(fn ($id) => (int) $id)->all();
        $allTreeCandidates = LayananJasa::query()
            ->where('is_active', true)
            ->orWhereIn('id', $prefillLayananIds)
            ->get(['id', 'parent_id'])
            ->keyBy('id');

        foreach ($eligibleLeafLayanans as $layanan) {
            $parentId = $layanan->parent_id;
            $guard = 0;

            while ($parentId && $guard < 10) {
                $treeLayananIds[] = (int) $parentId;
                $parent = $allTreeCandidates->get($parentId);
                $parentId = $parent?->parent_id;
                $guard++;
            }
        }

        $layanans = LayananJasa::query()
            ->whereIn('id', array_values(array_unique($treeLayananIds)))
            ->orderBy('level')
            ->orderBy('id')
            ->get();

        $mitraLayananMap = [];
        foreach ($mitras as $mitra) {
            $allowedIds = $jasaAccessService
                ->getAllowedLayananForTagihan(Auth::user(), $mitra)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            if ((int) $tagihan->mitra_jasa_id === (int) $mitra->id) {
                $allowedIds = array_values(array_unique(array_merge($allowedIds, $prefillLayananIds->all())));
            }

            $mitraLayananMap[$mitra->id] = $allowedIds;
        }

        $mitraMetaMap = $mitras->mapWithKeys(function (MitraJasa $mitra) {
            return [
                $mitra->id => [
                    'id' => $mitra->id,
                    'kode_mitra' => $mitra->kode_mitra,
                    'nama_mitra' => $mitra->nama_mitra,
                    'jenis_mitra' => $mitra->jenis_mitra,
                    'npwp' => $mitra->npwp,
                    'email' => $mitra->email,
                    'no_telepon' => $mitra->no_telepon,
                    'alamat' => $mitra->alamat,
                    'nama_penanggung_jawab' => $mitra->nama_penanggung_jawab,
                    'jabatan_penanggung_jawab' => $mitra->jabatan_penanggung_jawab,
                    'kontrak' => $mitra->kontrak->map(fn (KontrakMitraJasa $kontrak) => [
                        'id' => $kontrak->id,
                        'nomor_kontrak' => $kontrak->nomor_kontrak,
                        'nama_kontrak' => $kontrak->nama_kontrak,
                        'jenis_dokumen' => $kontrak->jenis_dokumen,
                        'tanggal_kontrak' => optional($kontrak->tanggal_kontrak)->format('Y-m-d'),
                        'tanggal_mulai' => optional($kontrak->tanggal_mulai)->format('Y-m-d'),
                        'tanggal_selesai' => optional($kontrak->tanggal_selesai)->format('Y-m-d'),
                        'file_url' => $kontrak->file_kontrak ? asset('storage/' . $kontrak->file_kontrak) : null,
                        'status_kontrak' => $kontrak->status_kontrak,
                        'layanan_ids' => $kontrak->layananJasa->pluck('id')->map(fn ($id) => (int) $id)->values(),
                    ])->values(),
                ],
            ];
        });

        return view('tagihan_jasa.create', compact('mitras', 'layanans', 'tipe', 'mode', 'mitraLayananMap', 'mitraMetaMap', 'prefillTagihan', 'detailPrefills', 'tagihan'));
    }

    public function update(Request $request, $id, JasaAccessService $jasaAccessService, TagihanJasaCalculationService $calculationService)
    {
        abort_unless($this->canCreateTagihanJasa(), 403, 'Koordinator Jasa hanya dapat melihat dan memverifikasi tagihan.');

        $tagihan = TagihanJasa::with('details')->findOrFail($id);
        $this->abortIfAdminJasaCannotAccess($tagihan);

        if (! in_array($tagihan->status, ['REVISI', 'DITOLAK'], true)) {
            return redirect()
                ->route('tagihan-jasa.show', $tagihan->id)
                ->with('error', 'Tagihan hanya dapat diperbarui saat berstatus REVISI atau DITOLAK.');
        }

        $validated = $this->validateTagihanJasaInput($request);

        try {
            $mitra = MitraJasa::findOrFail($validated['mitra_jasa_id']);
            if (! $mitra->status_aktif && (int) $tagihan->mitra_jasa_id !== (int) $mitra->id) {
                return back()->withInput()->with('error', 'Mitra nonaktif tidak dapat dipilih untuk revisi tagihan.');
            }

            $kontrak = null;
            if (! empty($validated['kontrak_mitra_jasa_id'])) {
                $kontrak = KontrakMitraJasa::with('layananJasa')
                    ->where('mitra_jasa_id', $mitra->id)
                    ->findOrFail($validated['kontrak_mitra_jasa_id']);
            }

            if ($kontrak && $kontrak->layananJasa->isNotEmpty()) {
                $kontrakLayananIds = $kontrak->layananJasa->pluck('id')->map(fn ($kontrakLayananId) => (int) $kontrakLayananId);
                $outsideScope = collect($validated['layanan'])
                    ->pluck('id')
                    ->map(fn ($layananId) => (int) $layananId)
                    ->unique()
                    ->reject(fn ($layananId) => $kontrakLayananIds->contains($layananId));

                if ($outsideScope->isNotEmpty()) {
                    return back()
                        ->withInput()
                        ->with('error', 'Kontrak/dokumen dasar yang dipilih tidak mencakup semua layanan pada tagihan.');
                }
            }

            foreach ($validated['layanan'] as $row) {
                if (! $jasaAccessService->canUseLayananForMitra(Auth::user(), $mitra, (int) $row['id'])) {
                    return back()
                        ->withInput()
                        ->with('error', 'Terdapat layanan yang belum aktif untuk mitra atau belum ditugaskan kepada Admin Jasa login.');
                }
            }

            $requestedLayanans = LayananJasa::query()
                ->whereIn('id', collect($validated['layanan'])->pluck('id')->unique()->values())
                ->get()
                ->keyBy('id');

            foreach ($validated['layanan'] as $row) {
                if (($row['mode'] ?? 'TARIF') === 'TARIF' && (float) $row['qty'] <= 0) {
                    return back()
                        ->withInput()
                        ->with('error', 'Volume layanan tarif rupiah harus lebih dari 0.');
                }

                if (($row['mode'] ?? 'TARIF') === 'PERSENTASE' && ! $this->canUsePercentageCalculation($requestedLayanans->get($row['id']))) {
                    return back()
                        ->withInput()
                        ->with('error', 'Terdapat layanan yang tidak dapat dihitung sebagai persentase omzet.');
                }
            }

            DB::transaction(function () use ($tagihan, $validated, $kontrak, $requestedLayanans, $calculationService) {
                $totalTagihan = collect($validated['layanan'])->sum(function ($row) use ($requestedLayanans, $calculationService) {
                    return $calculationService->calculateSubtotal($row, $requestedLayanans->get($row['id']));
                });

                $tagihan->update([
                    'tipe_pnbp' => $validated['tipe_pnbp'],
                    'mitra_jasa_id' => $validated['mitra_jasa_id'],
                    'kontrak_mitra_jasa_id' => $kontrak?->id,
                    'file_kontrak' => $kontrak?->file_kontrak,
                    'nomor_kontrak' => $kontrak?->nomor_kontrak,
                    'tanggal_mulai_kontrak' => optional($kontrak?->tanggal_mulai)->format('Y-m-d'),
                    'tanggal_selesai_kontrak' => optional($kontrak?->tanggal_selesai)->format('Y-m-d'),
                    'tanggal_tagihan' => $validated['tanggal_tagihan'],
                    'tanggal_surat_pengantar' => $validated['tanggal_tagihan'],
                    'total_tagihan' => $totalTagihan,
                    'status' => 'REVISI',
                    'final_verifier_role' => $validated['final_verifier_role'],
                    'status_dokumen_pengantar' => 'DRAFT',
                ]);

                $tagihan->details()->delete();
                foreach ($validated['layanan'] as $row) {
                    $layananJasa = $requestedLayanans->get($row['id']);
                    $calculationPayload = $calculationService->buildPayload($row, $layananJasa);

                    $tagihan->details()->create([
                        'layanan_jasa_id' => $row['id'],
                        'kode_akun' => $layananJasa?->kode_pembayaran_lengkap ?: ($layananJasa?->kode_akun ?: ($layananJasa?->kode_mak ?: ($row['kode_akun'] ?? null))),
                        'qty' => (float) $calculationPayload['billable_qty'],
                        'harga_satuan' => (float) $row['harga_satuan'],
                        'kurs' => (float) ($row['kurs'] ?? 1),
                        'subtotal' => $calculationService->calculateSubtotal($row, $layananJasa),
                        'keterangan' => $row['keterangan'] ?? null,
                        'calculation_payload' => $calculationPayload,
                    ]);
                }

                $utilitas = \App\Models\LaporanUtilitas::where('tagihan_jasa_id', $tagihan->id)->first();
                if ($utilitas) {
                    $firstDetail = $tagihan->details()->first();
                    $utilitas->update([
                        'mitra_jasa_id' => $validated['mitra_jasa_id'],
                        'layanan_jasa_id' => $firstDetail?->layanan_jasa_id,
                        'tarif_per_unit' => $firstDetail?->harga_satuan,
                        'total_biaya' => $totalTagihan,
                    ]);
                }
            });

            return redirect()
                ->route('tagihan-jasa.show', $tagihan->id)
                ->with('success', 'Revisi tagihan berhasil disimpan. Kirim ulang tagihan untuk memulai workflow verifikasi baru.');
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal menyimpan revisi tagihan jasa: ' . $e->getMessage());
        }
    }

    public function resubmit($id, WorkflowService $workflowService)
    {
        abort_unless($this->canCreateTagihanJasa(), 403, 'Koordinator Jasa hanya dapat melihat dan memverifikasi tagihan.');

        $tagihan = TagihanJasa::with('details')->findOrFail($id);
        $this->abortIfAdminJasaCannotAccess($tagihan);

        if (! in_array($tagihan->status, ['REVISI', 'DITOLAK'], true)) {
            return back()->with('error', 'Hanya tagihan berstatus REVISI atau DITOLAK yang dapat dikirim ulang.');
        }

        if ($tagihan->details->isEmpty()) {
            return back()->with('error', 'Tagihan belum memiliki detail layanan.');
        }

        try {
            DB::transaction(function () use ($tagihan, $workflowService) {
                $tagihan->update([
                    'status' => 'VERIFIKASI_KOORDINATOR',
                    'status_dokumen_pengantar' => 'DRAFT',
                ]);

                $workflowService->startWorkflow('TAGIHAN_JASA', $tagihan->fresh());
            });

            return back()->with('success', 'Tagihan berhasil dikirim ulang ke alur verifikasi.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal mengirim ulang tagihan jasa: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $tagihan = TagihanJasa::with([
            'mitra',
            'mitraLegacy',
            'kontrakMitraJasa',
            'creator',
            'arsipDokumen.uploader',
            'details.layananJasa.parent.parent.parent.parent.parent',
            'workflowInstance.approvals.actedByUser',
        ])->findOrFail($id);

        $this->abortIfAdminJasaCannotAccess($tagihan);
        $this->ensureSuratPengantarDefaults($tagihan);
        $tagihan->refresh()->load([
            'mitra',
            'mitraLegacy',
            'kontrakMitraJasa',
            'creator',
            'arsipDokumen.uploader',
            'details.layananJasa.parent.parent.parent.parent.parent',
            'workflowInstance.approvals.actedByUser',
        ]);

        return view('tagihan_jasa.show', compact('tagihan'));
    }

    public function generateInvoicePdf(Request $request, $id)
    {
        $tagihan = TagihanJasa::with([
            'mitra',
            'mitraLegacy',
            'kontrakMitraJasa',
            'details.layananJasa.parent.parent.parent.parent.parent',
        ])->findOrFail($id);
        $this->abortIfAdminJasaCannotAccess($tagihan);
        $terbilang = function_exists('terbilang_rupiah')
            ? terbilang_rupiah((float) $tagihan->total_tagihan)
            : trim(terbilang((float) $tagihan->total_tagihan)) . ' Rupiah';

        $pdf = Pdf::loadView('tagihan_jasa.pdf', compact('tagihan', 'terbilang'))
            ->setPaper('a4', 'portrait');

        $fileName = 'invoice-' . str_replace(['/', '\\'], '-', $tagihan->nomor_tagihan) . '.pdf';

        if ($request->boolean('download')) {
            return $pdf->download($fileName);
        }

        return $pdf->stream($fileName);
    }

    public function generateSuratPengantarPdf(Request $request, $id)
    {
        $tagihan = TagihanJasa::with([
            'mitra',
            'mitraLegacy',
            'kontrakMitraJasa',
            'details.layananJasa.parent.parent.parent.parent.parent',
        ])->findOrFail($id);
        $this->abortIfAdminJasaCannotAccess($tagihan);

        $this->ensureSuratPengantarDefaults($tagihan);
        $tagihan->refresh()->load([
            'mitra',
            'mitraLegacy',
            'kontrakMitraJasa',
            'details.layananJasa.parent.parent.parent.parent.parent',
        ]);

        $pdf = Pdf::loadView('tagihan_jasa.surat_pengantar_pdf', compact('tagihan'))
            ->setPaper('a4', 'portrait');

        $fileName = 'surat-pengantar-' . str_replace(['/', '\\'], '-', $tagihan->nomor_tagihan) . '.pdf';
        $pdfContent = $pdf->output();

        $this->archiveSuratPengantarContent(
            $tagihan,
            'SURAT_PENGANTAR_DRAFT',
            $fileName,
            $pdfContent,
            'Arsip draft surat pengantar tagihan jasa.'
        );

        if ($request->boolean('download')) {
            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ]);
        }

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }

    public function updateSuratPengantarDraft(Request $request, $id)
    {
        abort_unless($this->canManageTagihanJasa(), 403);

        $validated = $request->validate([
            'nomor_surat_pengantar' => ['nullable', 'string', 'max:255'],
            'tanggal_surat_pengantar' => ['nullable', 'date'],
            'perihal_surat_pengantar' => ['nullable', 'string', 'max:255'],
        ]);

        $tagihan = TagihanJasa::findOrFail($id);
        $this->abortIfAdminJasaCannotAccess($tagihan);

        if (in_array($tagihan->status, ['PUBLISHED', 'LUNAS'], true)) {
            return back()->with('error', 'Surat pengantar tidak dapat diubah setelah tagihan dipublish atau lunas.');
        }

        $tagihan->update([
            'nomor_surat_pengantar' => $validated['nomor_surat_pengantar'] ?: $tagihan->nomor_surat_pengantar,
            'tanggal_surat_pengantar' => $validated['tanggal_surat_pengantar'] ?: $tagihan->tanggal_surat_pengantar,
            'perihal_surat_pengantar' => $validated['perihal_surat_pengantar'] ?: $tagihan->perihal_surat_pengantar,
        ]);

        return back()->with('success', 'Draft surat pengantar berhasil diperbarui.');
    }

    public function uploadSuratPengantarFinal(Request $request, $id)
    {
        abort_unless($this->canManageTagihanJasa(), 403);

        $validated = $request->validate([
            'file_surat_pengantar_final' => ['required', 'file', 'mimes:pdf', 'max:5120'],
        ]);

        $tagihan = TagihanJasa::with('workflowInstance')->findOrFail($id);
        $this->abortIfAdminJasaCannotAccess($tagihan);

        if (! $tagihan->workflowInstance || $tagihan->workflowInstance->status !== 'APPROVED') {
            return back()->with('error', 'Surat pengantar final hanya dapat diunggah setelah seluruh verifikasi disetujui.');
        }

        $path = $request->file('file_surat_pengantar_final')->store('tagihan-jasa/surat-pengantar-final', 'public');
        $this->archiveUploadedSuratPengantar(
            $tagihan,
            'SURAT_PENGANTAR_FINAL_TTD',
            $request->file('file_surat_pengantar_final'),
            $path,
            'Arsip surat pengantar final bertanda tangan yang diunggah manual.'
        );

        $tagihan->update([
            'file_surat_pengantar_final' => $path,
            'uploaded_surat_pengantar_by' => Auth::id(),
            'uploaded_surat_pengantar_at' => now(),
            'status_dokumen_pengantar' => 'SUDAH_DITANDATANGANI',
        ]);

        return back()->with('success', 'Surat pengantar final bertanda tangan berhasil diunggah.');
    }

    public function generateSuratPengantarFinal($id)
    {
        abort_unless($this->canManageTagihanJasa(), 403);

        $tagihan = TagihanJasa::with([
            'mitra',
            'mitraLegacy',
            'kontrakMitraJasa',
            'details.layananJasa',
            'workflowInstance.approvals.actedByUser',
        ])->findOrFail($id);
        $this->abortIfAdminJasaCannotAccess($tagihan);

        if (! $tagihan->workflowInstance || $tagihan->workflowInstance->status !== 'APPROVED') {
            return back()->with('error', 'Surat final hanya dapat digenerate setelah seluruh verifikasi disetujui.');
        }

        try {
            $this->ensureSuratPengantarDefaults($tagihan);

            $finalApproval = $tagihan->workflowInstance->approvals
                ->where('status', 'APPROVED')
                ->sortByDesc('urutan_step')
                ->first();
            $approver = $finalApproval?->actedByUser ?: Auth::user();
            $pegawai = $approver?->pegawai;

            $tagihan->forceFill([
                'pejabat_penandatangan_nama' => $pegawai?->nama_lengkap ?: ($approver?->name ?: $tagihan->pejabat_penandatangan_nama),
                'pejabat_penandatangan_nip' => $pegawai?->nip ?: $tagihan->pejabat_penandatangan_nip,
                'pejabat_penandatangan_jabatan' => $pegawai?->jabatan
                    ?: ($approver?->hasRole('PLT/PLH') ? 'PLT/PLH Kepala Badan Layanan Umum' : ($tagihan->pejabat_penandatangan_jabatan ?: 'Kepala Badan Layanan Umum')),
                'tanggal_surat_pengantar' => $tagihan->tanggal_surat_pengantar ?: $tagihan->tanggal_tagihan,
                'perihal_surat_pengantar' => $tagihan->perihal_surat_pengantar ?: 'Penyampaian Tagihan PNBP Jasa',
            ])->save();

            $signedTagihan = $tagihan->fresh([
                'mitra',
                'mitraLegacy',
                'kontrakMitraJasa',
                'details.layananJasa',
            ]);

            $pdf = Pdf::loadView('tagihan_jasa.surat_pengantar_pdf', [
                'tagihan' => $signedTagihan,
                'signed' => true,
            ])->setPaper('a4', 'portrait');

            $fileName = 'surat-pengantar-final-' . str_replace(['/', '\\'], '-', $signedTagihan->nomor_tagihan) . '-' . now()->format('YmdHis') . '.pdf';
            $path = 'tagihan-jasa/surat-pengantar-final/' . $fileName;

            $pdfContent = $pdf->output();
            Storage::disk('public')->put($path, $pdfContent);
            $this->archiveStoredSuratPengantar(
                $tagihan,
                'SURAT_PENGANTAR_FINAL_TTD',
                $fileName,
                $path,
                'Arsip surat pengantar final bertanda tangan elektronik.'
            );

            $tagihan->forceFill([
                'file_surat_pengantar_final' => $path,
                'uploaded_surat_pengantar_by' => $approver?->id ?: Auth::id(),
                'uploaded_surat_pengantar_at' => $finalApproval?->acted_at ?: now(),
                'status_dokumen_pengantar' => 'SUDAH_DITANDATANGANI',
            ])->save();

            return back()->with('success', 'Surat pengantar final bertanda tangan berhasil digenerate.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal generate surat pengantar final: ' . $e->getMessage());
        }
    }

    public function publish(Request $request, $id, WhatsappService $whatsappService, BtnVirtualAccountService $btnVirtualAccountService)
    {
        abort_unless($this->canManageTagihanJasa(), 403);

        $validated = $request->validate([
            'wa_tujuan' => ['required', 'string', 'max:30'],
        ]);

        $tagihan = TagihanJasa::with(['mitra', 'mitraLegacy', 'workflowInstance', 'details.layananJasa'])->findOrFail($id);
        $this->abortIfAdminJasaCannotAccess($tagihan);

        if (!$tagihan->workflowInstance || $tagihan->workflowInstance->status !== 'APPROVED') {
            return back()->with('error', 'Tagihan belum selesai diverifikasi.');
        }

        if (! $tagihan->file_surat_pengantar_final || $tagihan->status_dokumen_pengantar !== 'SUDAH_DITANDATANGANI') {
            return back()->with('error', 'Generate surat pengantar final TTD terlebih dahulu sebelum publish ke mitra.');
        }

        if (! $tagihan->mitra) {
            return back()->with('error', 'Tagihan lama belum terhubung ke data Mitra Jasa.');
        }

        $accountInfo = $this->ensureMitraAccount($tagihan->mitra);

        $dueData = $this->resolveDueDateData($tagihan);
        $vaData = $btnVirtualAccountService->createVirtualAccount($tagihan);

        $tagihan->update([
            'status' => 'PUBLISHED',
            'status_pembayaran' => 'belum_dibayar',
            'nomor_va' => $vaData['number'] ?? ($tagihan->nomor_va ?: $this->generateNomorVa($tagihan)),
            'va_provider' => $vaData['provider'] ?? 'btn',
            'va_reference' => $vaData['reference'] ?? null,
            'va_expired_at' => $vaData['expired_at'] ?? null,
            'tanggal_publish' => now()->toDateString(),
            'jumlah_hari_jatuh_tempo' => $dueData['jumlah_hari_jatuh_tempo'],
            'masa_toleransi_hari' => $dueData['masa_toleransi_hari'],
            'tanggal_jatuh_tempo' => $dueData['tanggal_jatuh_tempo'],
            'tanggal_akhir_toleransi' => $dueData['tanggal_akhir_toleransi'],
            'catatan_jatuh_tempo' => $dueData['catatan_jatuh_tempo'],
            'jumlah_dibayar' => 0,
            'sisa_tagihan' => $tagihan->total_tagihan,
        ]);

        $publishedTagihan = $tagihan->fresh(['mitra', 'mitraLegacy', 'details']);

        // Sync ke piutang (TransaksiPenerimaan) — muncul di menu Piutang Bendahara Penerimaan.
        try {
            app(\App\Services\Pembukuan\PiutangSyncService::class)->syncFromPublished($publishedTagihan);
        } catch (\Throwable $e) {
            \Log::error('Gagal sync piutang saat publish: ' . $e->getMessage());
        }

        $message = $this->buildWhatsappMessage($publishedTagihan, $accountInfo);
        $whatsappService->sendMessage($validated['wa_tujuan'], $message, $publishedTagihan);

        return back()
            ->with('success', 'Tagihan berhasil dipublish dan notifikasi WA diproses.')
            ->with('wa_message_preview', $message)
            ->with('is_new_mitra', $accountInfo['is_new'])
            ->with('mitra_email', $accountInfo['email'])
            ->with('mitra_password', $accountInfo['password']);
    }

    public function markAsPaid($id)
    {
        abort_unless($this->canManageTagihanJasa(), 403);

        $tagihan = TagihanJasa::findOrFail($id);
        $this->abortIfAdminJasaCannotAccess($tagihan);

        if ($tagihan->status !== 'PUBLISHED') {
            return back()->with('error', 'Hanya tagihan yang sudah dipublish yang dapat ditandai lunas.');
        }

        $totalTagihanBerjalan = (float) $tagihan->total_dengan_denda;

        $tagihan->update([
            'status' => 'LUNAS',
            'status_pembayaran' => 'lunas',
            'jumlah_dibayar' => $totalTagihanBerjalan,
            'sisa_tagihan' => 0,
            'tanggal_lunas' => now()->toDateString(),
        ]);

        $freshTagihan = $tagihan->fresh(['mitra', 'mitraLegacy', 'details']);

        // Sync ke piutang (PAID) + catat BKU DEBIT_MASUK.
        try {
            app(\App\Services\Pembukuan\PiutangSyncService::class)->syncFromLunas(
                $freshTagihan,
                [
                    'amount' => $totalTagihanBerjalan,
                    'paid_at' => now(),
                    'reference' => 'MANUAL/' . $freshTagihan->nomor_tagihan,
                ]
            );
        } catch (\Throwable $e) {
            \Log::error('Gagal sync piutang/BKU saat mark-lunas: ' . $e->getMessage());
        }

        // Kirim notifikasi WA "lunas" — sama dengan callback BTN VA, hanya saja
        // ini path manual (simulasi). Pakai service yang sudah membaca template
        // di IntegrationSetting Super Admin.
        try {
            app(\App\Services\BtnVirtualAccountService::class)->sendLunasNotification(
                $freshTagihan,
                [
                    'amount' => $totalTagihanBerjalan,
                    'paid_at' => now(),
                    'reference' => 'MANUAL/' . $freshTagihan->nomor_tagihan,
                ]
            );
        } catch (\Throwable $e) {
            \Log::error('Gagal kirim notifikasi WA lunas: ' . $e->getMessage());
        }

        return back()->with('success', 'Tagihan berhasil ditandai LUNAS, piutang & BKU diperbarui, notifikasi WA dikirim.');
    }

    public function autoApproveAll($id)
    {
        abort_unless($this->canManageTagihanJasa(), 403);

        $tagihan = TagihanJasa::with('workflowInstance.approvals')->findOrFail($id);
        $this->abortIfAdminJasaCannotAccess($tagihan);

        try {
            DB::transaction(function () use ($tagihan) {
                $instance = $tagihan->workflowInstance;

                if (!$instance || $instance->status !== 'IN_PROGRESS') {
                    throw new \RuntimeException('Workflow tidak sedang berjalan.');
                }

                $instance->approvals()
                    ->whereIn('status', ['PENDING', 'WAITING'])
                    ->update([
                        'status' => 'APPROVED',
                        'acted_by_user_id' => Auth::id(),
                        'acted_at' => now(),
                        'catatan' => 'Auto-approved untuk testing',
                        'ip_address' => request()->ip(),
                    ]);

                $lastStep = (int) $instance->approvals()->max('urutan_step');
                $instance->update([
                    'step_saat_ini' => $lastStep,
                    'status' => 'APPROVED',
                ]);

                $tagihan->update(['status' => 'VERIFIKASI_KABANDARA']);
            });

            return back()->with('success', 'Semua step verifikasi berhasil di-auto-approve.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal auto-approve: ' . $e->getMessage());
        }
    }

    private function archiveSuratPengantarContent(
        TagihanJasa $tagihan,
        string $jenisDokumen,
        string $fileName,
        string $content,
        ?string $keterangan = null
    ): void {
        $safeNomor = Str::slug(str_replace(['/', '\\'], '-', $tagihan->nomor_tagihan ?: ('tagihan-' . $tagihan->id)), '-');
        $path = 'arsip-dokumen/TagihanJasa/' . $safeNomor . '/' . now()->format('YmdHis') . '-' . Str::random(6) . '-' . $fileName;

        Storage::disk('public')->put($path, $content);

        $this->archiveStoredSuratPengantar($tagihan, $jenisDokumen, $fileName, $path, $keterangan, strlen($content));
    }

    private function archiveUploadedSuratPengantar(
        TagihanJasa $tagihan,
        string $jenisDokumen,
        UploadedFile $file,
        string $path,
        ?string $keterangan = null
    ): void {
        $this->deactivateSuratPengantarArchives($tagihan, $jenisDokumen);

        $tagihan->arsipDokumen()->create([
            'jenis_dokumen' => $jenisDokumen,
            'nama_file_asli' => $file->getClientOriginalName(),
            'path_file' => $path,
            'disk' => 'public',
            'mime_type' => $file->getMimeType() ?: 'application/pdf',
            'ukuran_file' => $file->getSize(),
            'checksum' => hash_file('sha256', $file->getRealPath()),
            'uploaded_by' => Auth::id(),
            'uploaded_at' => now(),
            'keterangan' => $keterangan,
            'is_active' => true,
        ]);
    }

    private function archiveStoredSuratPengantar(
        TagihanJasa $tagihan,
        string $jenisDokumen,
        string $fileName,
        string $path,
        ?string $keterangan = null,
        ?int $size = null
    ): void {
        $this->deactivateSuratPengantarArchives($tagihan, $jenisDokumen);
        $fullPath = Storage::disk('public')->path($path);

        $tagihan->arsipDokumen()->create([
            'jenis_dokumen' => $jenisDokumen,
            'nama_file_asli' => $fileName,
            'path_file' => $path,
            'disk' => 'public',
            'mime_type' => 'application/pdf',
            'ukuran_file' => $size ?? (Storage::disk('public')->exists($path) ? Storage::disk('public')->size($path) : null),
            'checksum' => is_file($fullPath) ? hash_file('sha256', $fullPath) : null,
            'uploaded_by' => Auth::id(),
            'uploaded_at' => now(),
            'keterangan' => $keterangan,
            'is_active' => true,
        ]);
    }

    private function deactivateSuratPengantarArchives(TagihanJasa $tagihan, string $jenisDokumen): void
    {
        $tagihan->arsipDokumen()
            ->where('jenis_dokumen', $jenisDokumen)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }

    private function generateNomorTagihan(string $tipe = 'FUNGSI', ?string $variant = null): string
    {
        $label = match ($variant) {
            'PJP2U' => 'TAG-PJP2U',
            default => $tipe === 'KONSESI' ? 'TAG-KONSESI' : 'TAG-JASA',
        };
        $prefix = $label . '/' . now()->format('Ymd');
        $count = TagihanJasa::where('nomor_tagihan', 'like', $prefix . '/%')
            ->whereDate('created_at', now()->toDateString())
            ->count() + 1;

        do {
            $nomor = $prefix . '/' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
            $count++;
        } while (TagihanJasa::where('nomor_tagihan', $nomor)->exists());

        return $nomor;
    }

    private function canCreateTagihanJasa(): bool
    {
        return Auth::user()?->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Admin Jasa', 'Admin Konsesi']) === true;
    }

    private function canManageTagihanJasa(): bool
    {
        return Auth::user()?->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Admin Jasa']) === true;
    }

    private function canUsePercentageCalculation(?LayananJasa $layanan): bool
    {
        if (! $layanan) {
            return false;
        }

        return $layanan->tipe_layanan === 'KONSESI'
            || (bool) $layanan->mendukung_konsesi
            || str_contains((string) $layanan->satuan, '%');
    }

    private function validateTagihanJasaInput(Request $request): array
    {
        return $request->validate([
            'tipe_pnbp' => ['required', 'in:FUNGSI,NON_FUNGSI,KONSESI'],
            'mitra_jasa_id' => ['required', 'exists:mitra_jasa,id'],
            'tanggal_tagihan' => ['required', 'date'],
            'final_verifier_role' => ['required', 'in:KPA,PLT/PLH'],
            'kontrak_mitra_jasa_id' => ['nullable', 'exists:kontrak_mitra_jasa,id'],
            'layanan' => ['required', 'array', 'min:1'],
            'layanan.*.id' => ['required', 'exists:layanan_jasas,id'],
            'layanan.*.mode' => ['nullable', 'in:TARIF,PERSENTASE'],
            'layanan.*.kode_akun' => ['nullable', 'string', 'max:255'],
            'layanan.*.qty' => ['required', 'numeric', 'min:0'],
            'layanan.*.harga_satuan' => ['required', 'numeric', 'min:0'],
            'layanan.*.kurs' => ['nullable', 'numeric', 'min:0'],
            'layanan.*.keterangan' => ['nullable', 'string', 'max:1000'],
            'layanan.*.calculation_payload' => ['nullable', 'json'],
        ]);
    }

    private function generateNomorVa(TagihanJasa $tagihan): string
    {
        return '88' . str_pad((string) $tagihan->id, 10, '0', STR_PAD_LEFT);
    }

    private function generateNomorSuratPengantar(TagihanJasa $tagihan): string
    {
        return 'SP-PNBP/' . now()->format('Ymd') . '/' . str_pad((string) $tagihan->id, 4, '0', STR_PAD_LEFT);
    }

    private function resolveDueDateData(TagihanJasa $tagihan): array
    {
        $layanans = $tagihan->details->pluck('layananJasa')->filter();
        $dueDays = (int) ($layanans->min('jumlah_hari_jatuh_tempo') ?: 30);
        $toleranceDays = (int) ($layanans->min('masa_toleransi_hari') ?? 0);
        $publishDate = now()->startOfDay();
        $dueDate = $publishDate->copy()->addDays($dueDays);
        $toleranceDate = $dueDate->copy()->addDays($toleranceDays);
        $forcedSeparate = $layanans->where('wajib_tagihan_terpisah', true);
        $notes = $layanans
            ->pluck('catatan_jatuh_tempo')
            ->filter()
            ->unique()
            ->values();

        if ($forcedSeparate->isNotEmpty()) {
            $notes->push('Terdapat layanan yang wajib dibuat dalam tagihan terpisah.');
        }

        return [
            'jumlah_hari_jatuh_tempo' => $dueDays,
            'masa_toleransi_hari' => $toleranceDays,
            'tanggal_jatuh_tempo' => $dueDate->toDateString(),
            'tanggal_akhir_toleransi' => $toleranceDate->toDateString(),
            'catatan_jatuh_tempo' => $notes->isNotEmpty()
                ? $notes->implode(' ')
                : "Jatuh tempo {$dueDays} hari sejak tanggal publish tagihan.",
        ];
    }

    private function ensureSuratPengantarDefaults(TagihanJasa $tagihan): void
    {
        $kpa = $this->getKpaPenandatanganData();

        $defaults = [
            'nomor_surat_pengantar' => $tagihan->nomor_surat_pengantar ?: $this->generateNomorSuratPengantar($tagihan),
            'tanggal_surat_pengantar' => $tagihan->tanggal_surat_pengantar ?: $tagihan->tanggal_tagihan,
            'perihal_surat_pengantar' => $tagihan->perihal_surat_pengantar ?: 'Penyampaian Tagihan PNBP Jasa',
            'pejabat_penandatangan_nama' => $kpa['nama'] ?: $tagihan->pejabat_penandatangan_nama,
            'pejabat_penandatangan_nip' => $kpa['nip'] ?: $tagihan->pejabat_penandatangan_nip,
            'pejabat_penandatangan_jabatan' => $kpa['jabatan'] ?: $tagihan->pejabat_penandatangan_jabatan ?: 'Kepala Bandara',
        ];

        $changes = [];
        foreach ($defaults as $field => $value) {
            if (! $tagihan->{$field} && $value) {
                $changes[$field] = $value;
            }
        }

        if ($changes !== []) {
            $tagihan->update($changes);
        }
    }

    private function getKpaPenandatanganData(): array
    {
        $kpaUser = User::role(['KPA', 'PLT/PLH'])->active()->with('profilable')->orderByDisplayName()->first();
        $pegawai = $kpaUser?->pegawai;

        return [
            'nama' => $pegawai?->nama_lengkap ?: $kpaUser?->name,
            'nip' => $pegawai?->nip,
            'jabatan' => $pegawai?->jabatan ?: ($kpaUser?->hasRole('PLT/PLH') ? 'PLT/PLH' : 'Kepala Bandara'),
        ];
    }

    private function scopeForAdminJasa($query)
    {
        $user = Auth::user();

        if (! $user?->hasRole('Admin Jasa') || $user->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Koordinator Jasa'])) {
            return $query;
        }

        $allowedIds = $this->allowedLayananIdsForCurrentAdmin();

        return $query->whereHas('details', fn ($detail) => $detail->whereIn('layanan_jasa_id', $allowedIds));
    }

    private function hideKonsesiSetupRows($query)
    {
        $finalTagihanIds = MitraJasaPenjualan::query()
            ->whereNotNull('tagihan_jasa_id')
            ->select('tagihan_jasa_id');

        return $query->where(function ($outer) use ($finalTagihanIds) {
            $outer->where('total_tagihan', '>', 0)
                ->orWhereIn('id', $finalTagihanIds)
                ->orWhereDoesntHave('details.layananJasa', function ($layanan) {
                    $layanan->where(function ($konsesi) {
                        $konsesi->where('tipe_layanan', 'KONSESI')
                            ->orWhere('mendukung_konsesi', true)
                            ->orWhere('satuan', 'like', '%\%%');
                    });
                });
        });
    }

    private function abortIfAdminJasaCannotAccess(TagihanJasa $tagihan): void
    {
        $user = Auth::user();

        if (! $user?->hasRole('Admin Jasa') || $user->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Koordinator Jasa'])) {
            return;
        }

        $allowedIds = $this->allowedLayananIdsForCurrentAdmin();
        $tagihan->loadMissing('details');

        if ((int) $tagihan->created_by === (int) $user->id) {
            return;
        }

        if ($tagihan->details->contains(fn ($detail) => in_array((int) $detail->layanan_jasa_id, $allowedIds, true))) {
            return;
        }

        if ($tagihan->mitra_jasa_id && $this->adminCanViewMitraTagihan((int) $tagihan->mitra_jasa_id, $allowedIds)) {
            return;
        }

        abort_unless(
            false,
            403,
            'Anda tidak memiliki akses ke tagihan jasa ini.'
        );
    }

    private function adminCanViewMitraTagihan(int $mitraJasaId, array $allowedLayananIds): bool
    {
        if ($allowedLayananIds === []) {
            return false;
        }

        return MitraJasa::query()
            ->whereKey($mitraJasaId)
            ->whereHas('layananJasa', function ($query) use ($allowedLayananIds) {
                $query->whereIn('layanan_jasas.id', $allowedLayananIds)
                    ->where('mitra_jasa_layanan.status_aktif', true);
            })
            ->exists();
    }

    private function allowedLayananIdsForCurrentAdmin(): array
    {
        return Auth::user()
            ->layananJasaDikelolaAktif()
            ->where('layanan_jasas.is_active', true)
            ->where('layanan_jasas.is_leaf', true)
            ->pluck('layanan_jasas.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function ensureMitraAccount(MitraJasa $mitra): array
    {
        $user = $mitra->user;

        if ($user) {
            return [
                'is_new' => false,
                'email' => $user->email,
                'password' => null,
            ];
        }

        $email = $mitra->email ?: 'mitra-' . $mitra->id . '@sikeren.id';

        if (User::where('email', $email)->exists()) {
            $email = 'mitra-' . $mitra->id . '-' . Str::lower(Str::random(5)) . '@sikeren.id';
        }
        $password = Str::password(10);

        Role::findOrCreate('Mitra Jasa', 'web');

        $user = User::create([
            'email' => $email,
            'password' => Hash::make($password),
            'profilable_type' => MitraJasa::class,
            'profilable_id' => $mitra->id,
        ]);
        $user->assignRole('Mitra Jasa');

        return [
            'is_new' => true,
            'email' => $email,
            'password' => $password,
        ];
    }

    private function buildWhatsappMessage(TagihanJasa $tagihan, array $accountInfo): string
    {
        $message = "*PEMBERITAHUAN TAGIHAN PNBP*\n\n";
        $message .= "Yth. " . ($tagihan->mitra->nama_mitra ?? '-') . ",\n\n";
        $message .= "Berikut adalah informasi tagihan layanan Anda:\n";
        $message .= "No Tagihan: *" . $tagihan->nomor_tagihan . "*\n";
        $message .= "Total Tagihan: *Rp " . number_format((float) $tagihan->total_tagihan, 0, ',', '.') . "*\n\n";
        $message .= "Silakan lakukan pembayaran melalui Virtual Account Bank BTN berikut:\n";
        $message .= "No VA: *" . ($tagihan->nomor_va ?? '-') . "*\n\n";
        if ($tagihan->tanggal_jatuh_tempo) {
            $message .= "Jatuh Tempo: *" . $tagihan->tanggal_jatuh_tempo->format('d/m/Y') . "*\n";
        }
        $shortLink = \App\Models\ShortLink::forTarget('tagihan_jasa', $tagihan->id, auth()->id());
        $message .= "Link Invoice: " . $shortLink->publicUrl() . "\n\n";
        $message .= "----------------------------------------\n";
        $message .= "*AKUN PORTAL MITRA*\n";
        $message .= "Email Login: " . ($accountInfo['email'] ?? '-') . "\n";

        if (!empty($accountInfo['password'])) {
            $message .= "Password: " . $accountInfo['password'] . "\n";
            $message .= "Mohon segera ubah password setelah login pertama.\n";
        } else {
            $message .= "Gunakan password akun yang sudah terdaftar sebelumnya.\n";
        }

        $message .= "Login Portal: " . route('login') . "\n";
        $message .= "----------------------------------------\n\n";
        $message .= "Terima kasih atas kerja sama Anda.\n";
        $message .= "_Sistem Informasi Keuangan (SIKEREN)_";

        return $message;
    }
}
