<?php

namespace App\Http\Controllers;

use App\Models\MitraJasa;
use App\Models\PermohonanNonSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PermohonanNonScheduleController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($this->canView(), 403);

        $query = PermohonanNonSchedule::with(['mitra', 'creator', 'reviewer'])
            ->orderByDesc('tanggal_surat')
            ->orderByDesc('id');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($jenis = $request->input('jenis_penerbangan')) {
            $query->where('jenis_penerbangan', $jenis);
        }
        if ($mitraId = $request->input('mitra_jasa_id')) {
            $query->where('mitra_jasa_id', $mitraId);
        }
        if ($user = Auth::user()) {
            if ($user->hasRole('AMC') && ! $user->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Admin Jasa', 'Koordinator Jasa'])) {
                $query->where('created_by', $user->id);
            }
        }

        $items = $query->paginate(20)->withQueryString();
        $mitraOptions = MitraJasa::orderBy('nama_mitra')->get(['id', 'nama_mitra']);

        return view('permohonan_non_schedule.index', [
            'items' => $items,
            'filters' => $request->only(['status', 'jenis_penerbangan', 'mitra_jasa_id']),
            'mitraOptions' => $mitraOptions,
            'canReview' => $this->canReview(),
        ]);
    }

    public function create()
    {
        abort_unless($this->canCreate(), 403);

        return view('permohonan_non_schedule.form', [
            'item' => new PermohonanNonSchedule(),
            'mitraOptions' => MitraJasa::orderBy('nama_mitra')->get(['id', 'nama_mitra']),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($this->canCreate(), 403);

        $data = $this->validateData($request);
        $data['file_surat'] = $request->file('file_surat')->store('permohonan_non_schedule', 'local');
        $data['status'] = PermohonanNonSchedule::STATUS_DIAJUKAN;
        $data['created_by'] = Auth::id();

        PermohonanNonSchedule::create($data);

        return redirect()->route('permohonan-non-schedule.index')
            ->with('success', 'Permohonan non-schedule berhasil diajukan.');
    }

    public function edit(PermohonanNonSchedule $permohonan_non_schedule)
    {
        abort_unless($this->canEdit($permohonan_non_schedule), 403);

        return view('permohonan_non_schedule.form', [
            'item' => $permohonan_non_schedule,
            'mitraOptions' => MitraJasa::orderBy('nama_mitra')->get(['id', 'nama_mitra']),
        ]);
    }

    public function update(Request $request, PermohonanNonSchedule $permohonan_non_schedule)
    {
        abort_unless($this->canEdit($permohonan_non_schedule), 403);

        $data = $this->validateData($request, $permohonan_non_schedule);
        if ($request->hasFile('file_surat')) {
            if ($permohonan_non_schedule->file_surat) {
                Storage::disk('public')->delete($permohonan_non_schedule->file_surat);
            }
            $data['file_surat'] = $request->file('file_surat')->store('permohonan_non_schedule', 'local');
        }
        $permohonan_non_schedule->update($data);

        return redirect()->route('permohonan-non-schedule.index')
            ->with('success', 'Permohonan diperbarui.');
    }

    public function review(Request $request, PermohonanNonSchedule $permohonan_non_schedule)
    {
        abort_unless($this->canReview(), 403);

        $data = $request->validate([
            'status' => 'required|in:DISETUJUI,DITOLAK',
            'catatan_review' => 'nullable|string|max:1000',
        ]);

        $permohonan_non_schedule->update([
            'status' => $data['status'],
            'catatan_review' => $data['catatan_review'] ?? null,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Status permohonan diperbarui.');
    }

    public function file(PermohonanNonSchedule $permohonan_non_schedule)
    {
        // INF-01: file di disk privat `local` (fallback `public` untuk file lama).
        $disk = $permohonan_non_schedule->file_surat && Storage::disk('local')->exists($permohonan_non_schedule->file_surat) ? 'local' : 'public';
        abort_unless($permohonan_non_schedule->file_surat && Storage::disk($disk)->exists($permohonan_non_schedule->file_surat), 404);

        return Storage::disk($disk)->download($permohonan_non_schedule->file_surat);
    }

    public function destroy(PermohonanNonSchedule $permohonan_non_schedule)
    {
        abort_unless($this->canEdit($permohonan_non_schedule) && $permohonan_non_schedule->status === PermohonanNonSchedule::STATUS_DIAJUKAN, 403);

        if ($permohonan_non_schedule->file_surat) {
            Storage::disk('public')->delete($permohonan_non_schedule->file_surat);
        }
        $permohonan_non_schedule->delete();

        return redirect()->route('permohonan-non-schedule.index')->with('success', 'Permohonan dihapus.');
    }

    private function validateData(Request $request, ?PermohonanNonSchedule $existing = null): array
    {
        $rules = [
            'mitra_jasa_id' => 'required|exists:mitra_jasa,id',
            'nomor_surat' => 'required|string|max:100',
            'tanggal_surat' => 'required|date',
            'jenis_penerbangan' => 'required|in:non_schedule_kargo,non_schedule_lain',
            'tanggal_penerbangan_dari' => 'required|date',
            'tanggal_penerbangan_sampai' => 'nullable|date|after_or_equal:tanggal_penerbangan_dari',
            'rute' => 'nullable|string|max:150',
            'nomor_penerbangan' => 'nullable|string|max:50',
            'registrasi_pesawat' => 'nullable|string|max:50',
            'keterangan' => 'nullable|string|max:2000',
            'file_surat' => ($existing ? 'nullable' : 'required') . '|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];

        return $request->validate($rules);
    }

    private function canView(): bool
    {
        $user = Auth::user();
        return $user && $user->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Admin Jasa', 'Koordinator Jasa', 'AMC', 'Operator BLU']);
    }

    private function canCreate(): bool
    {
        $user = Auth::user();
        return $user && $user->hasAnyRole(['Super Admin', 'AMC']);
    }

    private function canEdit(PermohonanNonSchedule $item): bool
    {
        $user = Auth::user();
        if (! $user) return false;
        if ($user->hasRole('Super Admin')) return true;
        if ($item->status !== PermohonanNonSchedule::STATUS_DIAJUKAN) return false;
        return $user->hasRole('AMC') && $item->created_by === $user->id;
    }

    private function canReview(): bool
    {
        $user = Auth::user();
        return $user && $user->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Admin Jasa', 'Koordinator Jasa']);
    }
}
