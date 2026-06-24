<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Performa — pembantu respons SERVER-SIDE untuk DataTables. Menangani pencarian
 * global, pengurutan, dan pagination di sisi DB (bukan memuat semua baris ke
 * browser), lalu mengembalikan JSON sesuai kontrak DataTables.
 *
 * Pemakaian pada controller index-data:
 *   return DataTable::respond(
 *       $request,
 *       Model::query()->with([...]),
 *       orderable: [0 => null, 1 => 'kolom_db', ...],   // index kolom → kolom DB (null = tak bisa diurut)
 *       searchable: ['kolom_a', 'kolom_b'],             // kolom untuk kotak Cari
 *       rowMapper: fn ($row, int $no) => [ ...sel HTML per kolom... ],
 *   );
 */
class DataTable
{
    /**
     * @param array<int,string|null> $orderable
     * @param array<int,string> $searchable
     * @param callable(mixed,int):array $rowMapper
     */
    public static function respond(
        Request $request,
        Builder $query,
        array $orderable,
        array $searchable,
        callable $rowMapper,
    ): JsonResponse {
        $recordsTotal = (clone $query)->count();

        $search = trim((string) $request->input('search.value', ''));
        if ($search !== '' && $searchable !== []) {
            $query->where(function ($q) use ($search, $searchable) {
                foreach ($searchable as $col) {
                    $q->orWhere($col, 'like', "%{$search}%");
                }
            });
        }
        $recordsFiltered = (clone $query)->count();

        $idx = (int) $request->input('order.0.column', 0);
        $dir = $request->input('order.0.dir') === 'asc' ? 'asc' : 'desc';
        $col = $orderable[$idx] ?? null;
        $col !== null
            ? $query->orderBy($col, $dir)
            : $query->orderByDesc($query->getModel()->getQualifiedKeyName());

        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $length = ($length < 1 || $length > 100) ? 10 : $length;

        $no = $start;
        $data = $query->skip($start)->take($length)->get()
            ->map(fn ($row) => $rowMapper($row, ++$no))
            ->all();

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }
}
