<?php

namespace App\Services;

use App\Models\MitraJasaPenjualan;
use App\Models\SipandaiReconciliation;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SipandaiReconciliationService
{
    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['sipandai' => $message]);
    }

    public function airline(MitraJasaPenjualan $report): ?string
    {
        $mapping = config('sipandai.airlines', []);
        if (! is_array($mapping)) {
            $mapping = [];
        }
        $saved = Schema::hasTable('sipandai_airline_mappings')
            ? DB::table('sipandai_airline_mappings')->where('mitra_jasa_id', $report->mitra_jasa_id)->value('airline_code') : null;
        $code = strtoupper(trim((string) ($saved ?? $mapping[$report->mitra_jasa_id] ?? '')));

        return preg_match('/^[A-Z0-9]{2,3}$/', $code) ? $code : null;
    }

    public function saveMapping(MitraJasaPenjualan $report, string $code, int $userId): void
    {
        if (! Schema::hasTable('sipandai_airline_mappings')) {
            $this->fail('Jalankan migrasi integrasi SIPANDAI terlebih dahulu.');
        }
        $code = strtoupper(trim($code));
        if (! preg_match('/^[A-Z0-9]{2,3}$/', $code)) {
            $this->fail('Kode maskapai harus terdiri dari 2–3 huruf atau angka.');
        }
        if (DB::table('sipandai_airline_mappings')->where('airline_code', $code)->where('mitra_jasa_id', '!=', $report->mitra_jasa_id)->exists()) {
            $this->fail('Kode maskapai sudah dipetakan ke mitra lain. Periksa master mitra.');
        }
        try {
            DB::table('sipandai_airline_mappings')->updateOrInsert(
                ['mitra_jasa_id' => $report->mitra_jasa_id],
                ['airline_code' => $code, 'updated_by' => $userId, 'updated_at' => now(), 'created_at' => now()],
            );
        } catch (UniqueConstraintViolationException $e) {
            $this->fail('Pemetaan maskapai berubah bersamaan. Muat ulang dan periksa kode mitra.');
        }
    }

    public function latest(MitraJasaPenjualan $report): ?SipandaiReconciliation
    {
        if (! Schema::hasTable('sipandai_reconciliations')) {
            return null;
        }

        return SipandaiReconciliation::where('penjualan_id', $report->id)->latest('id')->first();
    }

    public function fingerprint(MitraJasaPenjualan $report): string
    {
        return hash('sha256', json_encode([
            $report->mitra_jasa_id, $report->layanan_jasa_id, $report->kontrak_mitra_jasa_id,
            $report->periode_mulai?->format('Y-m-d'), $report->periode_selesai?->format('Y-m-d'),
            $report->penerbangan_details, (string) $report->total_omzet, (string) $report->nilai_tagihan,
        ], JSON_THROW_ON_ERROR));
    }

    private function mutable(MitraJasaPenjualan $report): void
    {
        if ($report->status !== 'diajukan' || $report->tagihan_jasa_id) {
            $this->fail('Rekonsiliasi hanya dapat diubah pada laporan diajukan yang belum ditagihkan.');
        }
    }

    public function fetch(MitraJasaPenjualan $report, int $userId): SipandaiReconciliation
    {
        $this->mutable($report);
        if (! Schema::hasTable('sipandai_reconciliations')) {
            $this->fail('Jalankan migrasi integrasi SIPANDAI terlebih dahulu.');
        }
        $airline = $this->airline($report);
        if (! config('sipandai.enabled') || ! $airline || strlen((string) config('sipandai.token')) < 32) {
            $this->fail('Koneksi SIPANDAI atau pemetaan kode maskapai belum dikonfigurasi.');
        }
        $from = $report->periode_mulai?->format('Y-m-d');
        $to = $report->periode_selesai?->format('Y-m-d');
        if (! $from || ! $to || $to < $from || $report->periode_mulai->diffInDays($report->periode_selesai) > 30) {
            $this->fail('Periode laporan harus valid dan maksimal 31 hari.');
        }
        $url = rtrim((string) config('sipandai.url'), '/');
        $parts = parse_url($url);
        if (! $parts || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment']) ||
            ! (($parts['scheme'] ?? '') === 'https' || (($parts['scheme'] ?? '') === 'http' && in_array($parts['host'] ?? '', ['localhost', '127.0.0.1', '[::1]'], true)))) {
            $this->fail('URL SIPANDAI harus HTTPS; HTTP hanya diizinkan untuk localhost.');
        }
        try {
            $response = Http::acceptJson()->withToken(config('sipandai.token'))
                ->connectTimeout(5)->timeout(20)->withoutRedirecting()
                ->get($url.'/integrations/finance/manifests', compact('from', 'to', 'airline'));
        } catch (ConnectionException $e) {
            $this->fail('SIPANDAI tidak dapat dihubungi. Data sebelumnya tetap tersimpan.');
        }
        if (! $response->successful()) {
            $this->fail('Pengambilan data SIPANDAI gagal (HTTP '.$response->status().'). Periksa koneksi dan token integrasi.');
        }
        $data = $response->json();
        if (! is_array($data)) {
            $this->fail('Respons SIPANDAI tidak valid.');
        }
        $validator = Validator::make($data, [
            'schemaVersion' => ['required', 'integer', 'in:1'],
            'timezone' => ['required', 'in:Asia/Makassar'],
            'basis' => ['required', 'in:valid_scans_not_confirmed_boarding'],
            'from' => ['required', 'in:'.$from], 'to' => ['required', 'in:'.$to],
            'airlineCode' => ['required', 'in:'.$airline],
            'generatedAt' => ['required', 'date'], 'flights' => ['present', 'array'],
            'flights.*.id' => ['required', 'string', 'distinct'],
            'flights.*.flightNumber' => ['required', 'string', 'max:30'],
            'flights.*.airlineCode' => ['required', 'in:'.$airline],
            'flights.*.std' => ['required', 'date'],
            'flights.*.status' => ['required', 'in:SCHEDULED,OPEN,ACTIVE,CLOSED,CANCELLED'],
            'flights.*.passengers' => ['required', 'integer', 'min:0'],
        ]);
        if ($validator->fails()) {
            $this->fail('Format atau cakupan respons SIPANDAI tidak sesuai laporan.');
        }
        foreach ($data['flights'] as $flight) {
            $day = Carbon::parse($flight['std'])->setTimezone('Asia/Makassar')->format('Y-m-d');
            if ($day < $from || $day > $to) {
                $this->fail('SIPANDAI mengembalikan penerbangan di luar periode laporan.');
            }
        }
        $hash = $this->fingerprint($report);
        $comparison = $this->compare($report, $data);

        return DB::transaction(function () use ($report, $userId, $hash, $airline, $data, $comparison) {
            $locked = MitraJasaPenjualan::lockForUpdate()->findOrFail($report->id);
            $this->mutable($locked);
            if ($this->fingerprint($locked) !== $hash) {
                $this->fail('Laporan berubah saat pengambilan data. Muat ulang dan coba lagi.');
            }

            return SipandaiReconciliation::create([
                'penjualan_id' => $report->id, 'report_hash' => $hash, 'airline_code' => $airline,
                'snapshot' => $data, 'comparison' => $comparison, 'fetched_by' => $userId, 'fetched_at' => now(),
            ]);
        });
    }

    /** Legacy reports lack flight dates, so compare totals per flight number over the report period. */
    public function compare(MitraJasaPenjualan $report, array $snapshot): array
    {
        $normalize = fn ($number) => strtoupper(preg_replace('/[\s-]+/', '', (string) $number));
        $rows = [];
        foreach ($report->penerbangan_details ?? [] as $flight) {
            $number = $normalize($flight['nomor_penerbangan'] ?? '');
            if ($number === '') {
                $this->fail('Nomor penerbangan pada laporan tidak lengkap.');
            }
            $rows[$number] ??= ['number' => $number, 'reported' => null, 'scanned' => null, 'statuses' => []];
            $total = 0;
            foreach (['pax_dewasa', 'pax_anak', 'pax_bayi', 'pax_transit'] as $key) {
                $value = $flight[$key] ?? null;
                if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 0) {
                    $this->fail('Kategori penumpang pada laporan tidak lengkap.');
                }
                $total += (int) $value;
            }
            $rows[$number]['reported'] = ($rows[$number]['reported'] ?? 0) + $total;
        }
        if (! $rows) {
            $this->fail('Laporan belum memiliki rincian penerbangan untuk direkonsiliasi.');
        }
        foreach ($snapshot['flights'] as $flight) {
            $number = $normalize($flight['flightNumber']);
            $rows[$number] ??= ['number' => $number, 'reported' => null, 'scanned' => null, 'statuses' => []];
            $rows[$number]['scanned'] = ($rows[$number]['scanned'] ?? 0) + $flight['passengers'];
            $rows[$number]['statuses'][] = $flight['status'];
        }
        ksort($rows);
        foreach ($rows as &$row) {
            $row['difference'] = $row['scanned'] !== null && $row['reported'] !== null ? $row['scanned'] - $row['reported'] : null;
            $row['statuses'] = array_values(array_unique($row['statuses']));
        }
        unset($row);

        return array_values($rows);
    }

    public function accept(MitraJasaPenjualan $report, int $snapshotId, int $userId, string $note): void
    {
        DB::transaction(function () use ($report, $snapshotId, $userId, $note) {
            $locked = MitraJasaPenjualan::lockForUpdate()->findOrFail($report->id);
            $this->mutable($locked);
            $latest = $this->latest($locked);
            if (! $latest || $latest->id !== $snapshotId || $latest->report_hash !== $this->fingerprint($locked) ||
                $latest->airline_code !== $this->airline($locked)) {
                $this->fail('Data rekonsiliasi sudah berubah. Ambil ulang data SIPANDAI.');
            }
            if ($latest->accepted_at) {
                $this->fail('Rekonsiliasi ini sudah disimpan. Ambil data kembali untuk membuat revisi.');
            }
            if (mb_strlen(trim($note)) < 10) {
                $this->fail('Isi catatan pemeriksaan minimal 10 karakter, termasuk alasan selisih bila ada.');
            }
            $latest->update(['accepted_by' => $userId, 'accepted_at' => now(), 'note' => trim($note)]);
        });
    }

    public function assertVerifiedBasis(MitraJasaPenjualan $report): void
    {
        if (! $report->is_pjp2u_report) {
            return;
        }
        $latest = $this->latest($report);
        if (! config('sipandai.enabled') && ! $latest) {
            return;
        }
        if (! $latest || ! $latest->accepted_at || $latest->report_hash !== $this->fingerprint($report) ||
            $latest->airline_code !== $this->airline($report)) {
            $this->fail('Ambil data SIPANDAI dan simpan rekonsiliasi penumpang sebelum verifikasi laporan PJP2U.');
        }
    }
}
