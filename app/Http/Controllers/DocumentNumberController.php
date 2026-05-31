<?php

namespace App\Http\Controllers;

use App\Models\DocumentNumber;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class DocumentNumberController extends Controller
{
    /** Document key milik menu Nomor Dokumen (Pengadaan) — hanya grup kontrak PL. */
    private const KONTRAK_GROUP = 'KONTRAK_PPK_BB_APTP';

    private function kontrakDocumentKeys(): array
    {
        return collect(config('document_numbers.documents', []))
            ->filter(fn ($cfg) => ($cfg['sequence_group'] ?? null) === self::KONTRAK_GROUP)
            ->keys()
            ->all();
    }

    public function index(Request $request)
    {
        $documentKeys = $this->kontrakDocumentKeys();
        $statusOptions = [
            DocumentNumber::STATUS_AVAILABLE,
            DocumentNumber::STATUS_RESERVED,
            DocumentNumber::STATUS_USED,
            DocumentNumber::STATUS_CANCELLED,
        ];

        $query = DocumentNumber::query()
            ->where('sequence_group', self::KONTRAK_GROUP)
            ->with(['reservedBy', 'usedBy'])
            ->when($request->filled('document_key'), fn ($builder) => $builder->where('document_key', $request->document_key))
            ->when($request->filled('status'), fn ($builder) => $builder->where('status', $request->status))
            ->when($request->filled('tahun'), fn ($builder) => $builder->where('tahun', (int) $request->tahun))
            ->when($request->filled('search'), function ($builder) use ($request) {
                $search = trim((string) $request->search);

                $builder->where(function ($query) use ($search) {
                    $query->where('full_number', 'like', '%' . $search . '%')
                        ->orWhere('series_prefix', 'like', '%' . $search . '%')
                        ->orWhere('suffix_code', 'like', '%' . $search . '%')
                        ->orWhere('usage_source', 'like', '%' . $search . '%')
                        ->orWhere('notes', 'like', '%' . $search . '%');
                });
            });

        $summaryBase = DocumentNumber::query()->where('sequence_group', self::KONTRAK_GROUP);
        $summary = [
            'total' => (clone $summaryBase)->count(),
            'available' => (clone $summaryBase)->where('status', DocumentNumber::STATUS_AVAILABLE)->count(),
            'reserved' => (clone $summaryBase)->where('status', DocumentNumber::STATUS_RESERVED)->count(),
            'used' => (clone $summaryBase)->where('status', DocumentNumber::STATUS_USED)->count(),
        ];

        $numbers = $query
            ->orderByDesc('tahun')
            ->orderBy('running_number')
            ->orderBy('document_key')
            ->paginate(15)
            ->withQueryString();

        return view('document_numbers.index', compact('numbers', 'summary', 'documentKeys', 'statusOptions'));
    }

    public function store(Request $request, DocumentNumberService $service)
    {
        $documentKeys = $this->kontrakDocumentKeys();

        $validated = $request->validate([
            'document_key' => ['required', Rule::in($documentKeys)],
            'tahun' => 'required|integer|min:2000|max:2100',
            'start_number' => ['required', 'regex:/^[0-9]{4}$/', 'not_in:0000'],
            'count' => 'required|integer|min:1|max:100',
            'notes' => 'nullable|string|max:1000',
            'custom_prefix' => [
                Rule::requiredIf(fn () => strtoupper((string) $request->input('document_key')) === 'LAINNYA'),
                'nullable',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9.\-\/_]+$/',
            ],
        ], [
            'start_number.required' => 'Nomor awal wajib diisi 4 digit.',
            'start_number.regex' => 'Nomor awal harus berisi tepat 4 digit angka (contoh: 0205).',
            'start_number.not_in' => 'Nomor awal harus berada di antara 0001 sampai 9999.',
            'custom_prefix.required' => 'Kode awal wajib diisi untuk jenis dokumen Lainnya.',
            'custom_prefix.regex' => 'Kode awal hanya boleh berisi huruf, angka, titik, garis, dan slash (contoh: ND.001).',
        ]);

        $overrides = $this->buildOverrides($validated);

        try {
            $exists = $service->checkNumberExists(
                $validated['document_key'],
                (int) $validated['tahun'],
                (int) $validated['start_number'],
                $overrides
            );
            if ($exists) {
                return back()->withInput()->withErrors(['start_number' => 'Nomor urut ' . str_pad($validated['start_number'], 4, '0', STR_PAD_LEFT) . ' sudah dicatat/digunakan.']);
            }

            $created = $service->createAvailableRange(
                $validated['document_key'],
                (int) $validated['tahun'],
                (int) $validated['start_number'],
                (int) $validated['count'],
                $validated['notes'] ?? null,
                $overrides
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['error' => $exception->getMessage()]);
        }

        return redirect()
            ->route('document-numbers.index', ['document_key' => $validated['document_key'], 'tahun' => $validated['tahun']])
            ->with('success', count($created) . ' nomor dokumen berhasil dicatat sebagai pemakaian eksternal dan akan dilewati sistem.');
    }

    private function buildOverrides(array $validated): array
    {
        if (strtoupper((string) ($validated['document_key'] ?? '')) === 'LAINNYA' && ! empty($validated['custom_prefix'])) {
            return ['series_prefix' => trim($validated['custom_prefix'])];
        }

        return [];
    }

    public function reserve(Request $request, DocumentNumberService $service)
    {
        $documentKeys = $this->kontrakDocumentKeys();

        $validated = $request->validate([
            'document_key' => ['required', Rule::in($documentKeys)],
            'tahun' => 'required|integer|min:2000|max:2100',
            'running_number' => ['nullable', 'regex:/^[0-9]{1,4}$/', 'not_in:0,00,000,0000'],
            'notes' => 'nullable|string|max:1000',
        ], [
            'running_number.regex' => 'Nomor urut harus berisi angka maksimal 4 digit.',
            'running_number.not_in' => 'Nomor urut harus berada di antara 0001 sampai 9999.',
        ]);

        try {
            $number = $service->reserveByKey(
                $validated['document_key'],
                (int) $validated['tahun'],
                isset($validated['running_number']) ? (int) $validated['running_number'] : null,
                $validated['notes'] ?? null
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['error' => $exception->getMessage()]);
        }

        return redirect()
            ->route('document-numbers.index', ['document_key' => $number->document_key, 'status' => DocumentNumber::STATUS_USED])
            ->with('success', 'Nomor ' . $number->full_number . ' berhasil dicatat sebagai pemakaian eksternal dan akan dilewati sistem.');
    }

    public function release(DocumentNumber $documentNumber)
    {
        if ($documentNumber->status !== DocumentNumber::STATUS_RESERVED) {
            return back()->with('error', 'Hanya nomor berstatus RESERVED yang dapat dilepas.');
        }

        $documentNumber->update([
            'status' => DocumentNumber::STATUS_AVAILABLE,
            'reserved_by' => null,
            'reserved_at' => null,
        ]);

        return back()->with('success', 'Nomor ' . $documentNumber->full_number . ' dikembalikan ke stok tersedia.');
    }

    public function markUsed(DocumentNumber $documentNumber)
    {
        if (! in_array($documentNumber->status, [DocumentNumber::STATUS_AVAILABLE, DocumentNumber::STATUS_RESERVED], true)) {
            return back()->with('error', 'Nomor ini tidak dapat ditandai sebagai digunakan.');
        }

        $documentNumber->update([
            'status' => DocumentNumber::STATUS_USED,
            'usage_source' => DocumentNumber::SOURCE_EXTERNAL,
            'used_by' => auth()->id(),
            'used_at' => now(),
        ]);

        return back()->with('success', 'Nomor ' . $documentNumber->full_number . ' ditandai sebagai digunakan.');
    }

    public function check(Request $request, DocumentNumberService $service)
    {
        $validated = $request->validate([
            'document_key' => 'required|string',
            'tahun' => 'required|integer|min:2000|max:2100',
            'start_number' => ['required', 'regex:/^[0-9]{4}$/', 'not_in:0000'],
            'count' => 'nullable|integer|min:1|max:100',
            'custom_prefix' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9.\-\/_]+$/'],
        ]);

        try {
            $startNumber = (int) ltrim($validated['start_number'], '0');
            $count = (int) ($validated['count'] ?? 1);
            $year = (int) $validated['tahun'];
            $documentKey = $validated['document_key'];
            $overrides = $this->buildOverrides($validated);

            $conflicts = [];
            for ($i = 0; $i < $count; $i++) {
                $running = $startNumber + $i;
                if ($running < 1 || $running > 9999) {
                    break;
                }
                if ($service->checkNumberExists($documentKey, $year, $running, $overrides)) {
                    $conflicts[] = str_pad((string) $running, 4, '0', STR_PAD_LEFT);
                }
            }

            return response()->json([
                'exists' => count($conflicts) > 0,
                'conflicts' => $conflicts,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function cancel(DocumentNumber $documentNumber)
    {
        if (
            $documentNumber->status === DocumentNumber::STATUS_USED
            && $documentNumber->usage_source !== DocumentNumber::SOURCE_EXTERNAL
        ) {
            return back()->with('error', 'Nomor yang sudah digunakan tidak dapat dibatalkan.');
        }

        $documentNumber->update([
            'status' => DocumentNumber::STATUS_CANCELLED,
            'reserved_by' => null,
            'reserved_at' => null,
        ]);

        return back()->with('success', 'Nomor ' . $documentNumber->full_number . ' berhasil dibatalkan.');
    }
}
