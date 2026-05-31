<?php

namespace App\Http\Controllers;

use App\Models\Spp;
use Illuminate\Http\Request;

/**
 * Standing Instruction (KPA).
 *
 * Mendata seluruh SPP (Standing Instruction) yang diajukan PPK ke KPA untuk
 * dimintakan persetujuan. Sumber data: kolom kpa_approval_status pada dokumen_spp
 * yang diisi saat PPK menekan "Kirim ke KPA" (KpaApprovalController::sendWa).
 *
 * Halaman ini read-only/monitoring untuk KPA — proses setuju/tolak tetap melalui
 * halaman persetujuan (kpa.approval.*).
 */
class StandingInstructionKpaController extends Controller
{
    public function index(Request $request)
    {
        $statusFilter = $request->input('status'); // PENDING_KPA | APPROVED | REJECTED | null
        $search = trim((string) $request->input('search'));

        $query = Spp::query()
            ->whereNotNull('kpa_approval_status')
            ->with([
                'tagihan.pihak',
                'tagihan.detailKontrak.kontrakTermin.kontrak.vendor',
                'ppkVerifikator',
                'dibuatOleh',
                'kpaApprover',
            ]);

        if (in_array($statusFilter, ['PENDING_KPA', 'APPROVED', 'REJECTED'], true)) {
            $query->where('kpa_approval_status', $statusFilter);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_spp', 'like', "%{$search}%")
                    ->orWhereHas('tagihan', function ($t) use ($search) {
                        $t->where('nomor_tagihan', 'like', "%{$search}%")
                            ->orWhere('deskripsi', 'like', "%{$search}%");
                    })
                    ->orWhereHas('tagihan.pihak', fn ($p) => $p->where('nama_pihak', 'like', "%{$search}%"));
            });
        }

        $spps = $query
            ->orderByRaw("CASE WHEN kpa_approval_status = 'PENDING_KPA' THEN 0 ELSE 1 END")
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        // Ringkasan (tanpa filter status) untuk kartu KPI.
        $base = Spp::query()->whereNotNull('kpa_approval_status');
        $summary = [
            'total'    => (clone $base)->count(),
            'pending'  => (clone $base)->where('kpa_approval_status', 'PENDING_KPA')->count(),
            'approved' => (clone $base)->where('kpa_approval_status', 'APPROVED')->count(),
            'rejected' => (clone $base)->where('kpa_approval_status', 'REJECTED')->count(),
            'nominal_pending' => (clone $base)->where('kpa_approval_status', 'PENDING_KPA')->sum('nominal_spp'),
        ];

        return view('standing_instruction.kpa_index', compact('spps', 'summary', 'statusFilter', 'search'));
    }
}
