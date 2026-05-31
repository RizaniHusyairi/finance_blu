<?php

namespace App\Http\Controllers;

use App\Models\DocumentNumber;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * Manajemen Nomor Surat KU (role Koordinator Keuangan).
 *
 * Mengelola register nomor surat berawalan KU yang dipakai bersama oleh:
 *  - KU_HONOR               (Honorarium, prefix KU.201)
 *  - KU_PERJALDIN           (Perjalanan Dinas, prefix KU.201)
 *  - KU_SURAT_PENGANTAR_JASA(Surat Pengantar Penagihan Jasa, prefix KU.102)
 *
 * Ketiganya berbagi satu sequence_group (KU_APTP) sehingga nomor urut 4 digit
 * unik lintas tipe. Sistem yang menentukan nomor otomatis; menu ini untuk
 * monitoring + mencatat nomor yang dipakai di luar sistem agar dilewati.
 */
class SuratNumberController extends Controller
{
    /** Document key yang dikelola pada menu ini. */
    private const KU_KEYS = ['KU_HONOR', 'KU_PERJALDIN', 'KU_SURAT_PENGANTAR_JASA'];

    private const SEQUENCE_GROUP = 'KU_APTP';

    private const LABELS = [
        'KU_HONOR' => 'Honorarium (KU.201)',
        'KU_PERJALDIN' => 'Perjalanan Dinas (KU.201)',
        'KU_SURAT_PENGANTAR_JASA' => 'Surat Pengantar Jasa (KU.102)',
    ];

    public function index(Request $request, DocumentNumberService $service)
    {
        $documentKeys = self::KU_KEYS;
        $statusOptions = [
            DocumentNumber::STATUS_AVAILABLE,
            DocumentNumber::STATUS_RESERVED,
            DocumentNumber::STATUS_USED,
            DocumentNumber::STATUS_CANCELLED,
        ];

        $base = DocumentNumber::query()->where('sequence_group', self::SEQUENCE_GROUP);

        $query = (clone $base)
            ->with(['reservedBy', 'usedBy'])
            ->when($request->filled('document_key'), fn ($b) => $b->where('document_key', $request->document_key))
            ->when($request->filled('status'), fn ($b) => $b->where('status', $request->status))
            ->when($request->filled('tahun'), fn ($b) => $b->where('tahun', (int) $request->tahun))
            ->when($request->filled('search'), function ($b) use ($request) {
                $search = trim((string) $request->search);
                $b->where(function ($q) use ($search) {
                    $q->where('full_number', 'like', '%' . $search . '%')
                        ->orWhere('series_prefix', 'like', '%' . $search . '%')
                        ->orWhere('notes', 'like', '%' . $search . '%');
                });
            });

        $summary = [
            'total' => (clone $base)->count(),
            'honor' => (clone $base)->where('document_key', 'KU_HONOR')->count(),
            'perjaldin' => (clone $base)->where('document_key', 'KU_PERJALDIN')->count(),
            'jasa' => (clone $base)->where('document_key', 'KU_SURAT_PENGANTAR_JASA')->count(),
        ];

        // Preview nomor berikutnya per jenis (semua berbagi pool, jadi sama).
        $nextPreview = [
            'KU_HONOR' => $service->previewByKey('KU_HONOR'),
            'KU_PERJALDIN' => $service->previewByKey('KU_PERJALDIN'),
            'KU_SURAT_PENGANTAR_JASA' => $service->previewByKey('KU_SURAT_PENGANTAR_JASA'),
        ];

        $numbers = $query
            ->orderByDesc('tahun')
            ->orderByDesc('running_number')
            ->paginate(15)
            ->withQueryString();

        $labels = self::LABELS;

        return view('surat_numbers.index', compact(
            'numbers', 'summary', 'documentKeys', 'statusOptions', 'nextPreview', 'labels'
        ));
    }

    /**
     * Catat nomor eksternal (sudah dipakai di luar sistem) agar dilewati.
     */
    public function store(Request $request, DocumentNumberService $service)
    {
        $validated = $request->validate([
            'document_key' => ['required', Rule::in(self::KU_KEYS)],
            'tahun' => 'required|integer|min:2000|max:2100',
            'start_number' => ['required', 'regex:/^[0-9]{1,4}$/', 'not_in:0,00,000,0000'],
            'count' => 'required|integer|min:1|max:100',
            'notes' => 'nullable|string|max:1000',
        ], [
            'start_number.required' => 'Nomor awal wajib diisi.',
            'start_number.regex' => 'Nomor awal harus berisi angka maksimal 4 digit (contoh: 0205).',
            'start_number.not_in' => 'Nomor awal harus di antara 0001 sampai 9999.',
        ]);

        try {
            $created = $service->createAvailableRange(
                $validated['document_key'],
                (int) $validated['tahun'],
                (int) $validated['start_number'],
                (int) $validated['count'],
                $validated['notes'] ?? null
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('surat-numbers.index', ['document_key' => $validated['document_key'], 'tahun' => $validated['tahun']])
            ->with('success', count($created) . ' nomor surat KU dicatat sebagai pemakaian eksternal dan akan dilewati sistem.');
    }

    public function cancel(DocumentNumber $suratNumber)
    {
        abort_unless($suratNumber->sequence_group === self::SEQUENCE_GROUP, 404);

        if (
            $suratNumber->status === DocumentNumber::STATUS_USED
            && $suratNumber->usage_source !== DocumentNumber::SOURCE_EXTERNAL
        ) {
            return back()->with('error', 'Nomor yang dipakai oleh sistem tidak dapat dibatalkan.');
        }

        $suratNumber->update([
            'status' => DocumentNumber::STATUS_CANCELLED,
            'reserved_by' => null,
            'reserved_at' => null,
        ]);

        return back()->with('success', 'Nomor ' . $suratNumber->full_number . ' berhasil dibatalkan.');
    }

    /**
     * Cek ketersediaan nomor (AJAX) untuk pencatatan eksternal.
     */
    public function check(Request $request, DocumentNumberService $service)
    {
        $validated = $request->validate([
            'document_key' => ['required', Rule::in(self::KU_KEYS)],
            'tahun' => 'required|integer|min:2000|max:2100',
            'start_number' => ['required', 'regex:/^[0-9]{1,4}$/'],
            'count' => 'nullable|integer|min:1|max:100',
        ]);

        try {
            $start = (int) ltrim($validated['start_number'], '0');
            $count = (int) ($validated['count'] ?? 1);
            $year = (int) $validated['tahun'];
            $conflicts = [];

            for ($i = 0; $i < $count; $i++) {
                $run = $start + $i;
                if ($run < 1 || $run > 9999) {
                    break;
                }
                if ($service->checkNumberExists($validated['document_key'], $year, $run)) {
                    $conflicts[] = str_pad((string) $run, 4, '0', STR_PAD_LEFT);
                }
            }

            return response()->json(['exists' => count($conflicts) > 0, 'conflicts' => $conflicts]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
