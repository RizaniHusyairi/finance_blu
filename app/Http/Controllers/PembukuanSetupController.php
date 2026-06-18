<?php

namespace App\Http\Controllers;

use App\Enums\JenisRekening;
use App\Enums\KodeBuku;
use App\Enums\PeranBuku;
use App\Models\BukuKasUmum;
use App\Models\PembukuanSaldoAwal;
use App\Models\PembukuanSetup;
use App\Models\RekeningBank;
use Illuminate\Http\Request;

/** Identitas satker (kop dokumen BKU) + saldo awal per rekening — singleton kop. */
class PembukuanSetupController extends Controller
{
    public function edit()
    {
        return view('pembukuan.setup.edit', [
            'setup' => PembukuanSetup::current() ?? new PembukuanSetup(),
            'rekeningSaldo' => $this->rekeningSaldoAwal(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'nama_kl' => ['nullable', 'string', 'max:150'],
            'kode_kl' => ['nullable', 'string', 'max:10'],
            'nama_unit_org' => ['nullable', 'string', 'max:150'],
            'kode_unit_org' => ['nullable', 'string', 'max:10'],
            'nama_satker' => ['nullable', 'string', 'max:200'],
            'kode_satker' => ['nullable', 'string', 'max:12'],
            'propinsi' => ['nullable', 'string', 'max:150'],
            'nomor_dipa' => ['nullable', 'string', 'max:150'],
            'tanggal_dipa' => ['nullable', 'date'],
            'nama_kppn' => ['nullable', 'string', 'max:100'],
            'kode_kppn' => ['nullable', 'string', 'max:10'],
            'tahun_anggaran' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'nama_bendahara_penerimaan' => ['nullable', 'string', 'max:150'],
            'nama_bendahara_pengeluaran' => ['nullable', 'string', 'max:150'],
            'nama_kpa' => ['nullable', 'string', 'max:150'],
        ]);

        $setup = PembukuanSetup::current();
        $setup ? $setup->update($validated) : PembukuanSetup::create($validated);

        return redirect()->route('pembukuan.setup.edit')->with('success', 'Identitas satker (kop) pembukuan disimpan.');
    }

    /**
     * Simpan saldo awal BKU per rekening ke `pembukuan_saldo_awal` (seed saldo
     * berjalan), lalu hitung ulang saldo. Saldo awal hanya diinput sekali; bulan
     * berikutnya terbawa otomatis (carry-over) lewat recompute kronologis.
     */
    public function storeSaldoAwal(Request $request)
    {
        $validated = $request->validate([
            'saldo' => ['array'],
            'saldo.*.nama_rekening' => ['nullable', 'string', 'max:150'],
            'saldo.*.nominal' => ['nullable', 'numeric', 'min:0'],
            'saldo.*.tanggal' => ['nullable', 'date'],
        ]);

        $allowed = $this->allowedPerans();
        $affected = [];
        $renamed = 0;

        foreach ($validated['saldo'] ?? [] as $rekeningId => $data) {
            $rekening = RekeningBank::find($rekeningId);
            if (! $rekening) {
                continue;
            }

            $peran = $rekening->jenis_rekening === JenisRekening::PENERIMAAN
                ? PeranBuku::PENERIMAAN->value
                : PeranBuku::PENGELUARAN->value;

            // Batasi sesuai wewenang role: Bendahara hanya boleh kelola peran-nya sendiri.
            if (! in_array($peran, $allowed, true)) {
                continue;
            }

            // Ubah "Atas Nama" rekening bila diisi & berbeda.
            $nama = isset($data['nama_rekening']) ? trim((string) $data['nama_rekening']) : '';
            if ($nama !== '' && $nama !== $rekening->nama_rekening) {
                $rekening->nama_rekening = $nama;
                $rekening->save();
                $renamed++;
            }

            // Saldo awal (seed) — hanya bila nominal diisi.
            $nominal = $data['nominal'] ?? null;
            if ($nominal === null || $nominal === '') {
                continue;
            }

            PembukuanSaldoAwal::updateOrCreate(
                [
                    'rekening_bank_id' => $rekening->id,
                    'kode_buku' => KodeBuku::BKU->value,
                    'peran' => $peran,
                ],
                [
                    'tanggal_berlaku' => $data['tanggal'] ?: now()->startOfYear()->toDateString(),
                    'nominal' => (float) $nominal,
                ],
            );

            $affected[$rekening->id] = true;
        }

        foreach (array_keys($affected) as $rekeningId) {
            BukuKasUmum::recalculateRunningBalance((int) $rekeningId);
        }

        $pesan = [];
        if ($renamed) {
            $pesan[] = "{$renamed} nama rekening diperbarui";
        }
        if ($affected) {
            $pesan[] = count($affected) . ' saldo awal disimpan & saldo berjalan dihitung ulang';
        }

        return redirect()->route('pembukuan.setup.edit')
            ->with('success', $pesan ? (ucfirst(implode('; ', $pesan)) . '.') : 'Tidak ada perubahan disimpan.');
    }

    /**
     * Peran buku yang boleh dikelola user: Bendahara hanya peran-nya sendiri,
     * Super Admin keduanya.
     *
     * @return array<int, string>
     */
    private function allowedPerans(): array
    {
        $user = auth()->user();

        if ($user?->hasRole('Super Admin')) {
            return [PeranBuku::PENERIMAAN->value, PeranBuku::PENGELUARAN->value];
        }

        $perans = [];
        if ($user?->hasRole('Bendahara Pengeluaran')) {
            $perans[] = PeranBuku::PENGELUARAN->value;
        }
        if ($user?->hasRole('Bendahara Penerimaan')) {
            $perans[] = PeranBuku::PENERIMAAN->value;
        }

        return $perans;
    }

    /** Rekening (sesuai wewenang role) aktif + saldo awal BKU yang tersimpan. */
    private function rekeningSaldoAwal()
    {
        return RekeningBank::query()
            ->where('status_aktif', true)
            ->whereIn('jenis_rekening', $this->allowedPerans() ?: ['__none__'])
            ->orderBy('jenis_rekening')->orderBy('nama_bank')
            ->get()
            ->map(function (RekeningBank $r) {
                $peran = $r->jenis_rekening === JenisRekening::PENERIMAAN
                    ? PeranBuku::PENERIMAAN->value
                    : PeranBuku::PENGELUARAN->value;

                $sa = PembukuanSaldoAwal::query()
                    ->where('rekening_bank_id', $r->id)
                    ->where('kode_buku', KodeBuku::BKU->value)
                    ->where('peran', $peran)
                    ->orderByDesc('tanggal_berlaku')
                    ->first();

                $r->setAttribute('peran_bku', $peran);
                $r->setAttribute('saldo_awal_nominal', $sa?->nominal);
                $r->setAttribute('saldo_awal_tanggal', optional($sa?->tanggal_berlaku)->toDateString());

                return $r;
            });
    }
}
