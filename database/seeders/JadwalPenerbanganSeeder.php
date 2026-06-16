<?php

namespace Database\Seeders;

use App\Models\JadwalPenerbangan;
use App\Models\MitraJasa;
use App\Models\PemakaianGarbarata;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class JadwalPenerbanganSeeder extends Seeder
{
    public function run(): void
    {
        $airlines = [
            ['kode' => 'CIT', 'nama' => 'CITILINK'],
            ['kode' => 'SAJ', 'nama' => 'SUPER AIR JET'],
            ['kode' => 'GIA', 'nama' => 'GARUDA INDONESIA'],
            ['kode' => 'BTK', 'nama' => 'BATIK AIR'],
        ];

        $mitraIds = [];
        foreach ($airlines as $a) {
            $mitra = MitraJasa::firstOrCreate(
                ['kode_mitra' => $a['kode']],
                [
                    'nama_mitra' => $a['nama'],
                    'jenis_mitra' => 'Maskapai',
                    'status_aktif' => true,
                ]
            );
            $mitraIds[$a['kode']] = $mitra->id;
        }

        $rows = [
            ['kode' => 'CIT', 'arr' => 'QG460', 'dep' => 'QG461', 'origin' => 'SUB', 'dest' => 'SUB', 'type' => 'A320', 'reg' => 'PK-GQP', 'sched_arr' => '02:30', 'sched_dep' => '03:08'],
            ['kode' => 'SAJ', 'arr' => 'IU652', 'dep' => 'IU653', 'origin' => 'SUB', 'dest' => 'SUB', 'type' => 'A320', 'reg' => 'PK-SJV', 'sched_arr' => '07:30', 'sched_dep' => '08:10'],
            ['kode' => 'GIA', 'arr' => 'GA580', 'dep' => 'GA581', 'origin' => 'CGK', 'dest' => 'CGK', 'type' => 'B738', 'reg' => 'PK-GMW', 'sched_arr' => '03:20', 'sched_dep' => '04:29'],
            ['kode' => 'BTK', 'arr' => 'ID6526', 'dep' => 'ID6527', 'origin' => 'CGK', 'dest' => 'CGK', 'type' => 'A320', 'reg' => 'PK-LZH', 'sched_arr' => '10:15', 'sched_dep' => '11:00'],
            ['kode' => 'CIT', 'arr' => 'QG422', 'dep' => 'QG423', 'origin' => 'CGK', 'dest' => 'CGK', 'type' => 'A320', 'reg' => 'PK-GQT', 'sched_arr' => '06:30', 'sched_dep' => '07:07'],
            ['kode' => 'BTK', 'arr' => 'ID6676', 'dep' => 'ID6677', 'origin' => 'CGK', 'dest' => 'CGK', 'type' => 'B738', 'reg' => 'PK-LBS', 'sched_arr' => '08:55', 'sched_dep' => '09:44'],
        ];

        $keepKeys = collect($rows)->map(fn ($r) => $mitraIds[$r['kode']] . '|' . $r['arr'] . '|' . $r['dep'])->all();

        JadwalPenerbangan::all()->each(function ($j) use ($keepKeys) {
            $key = $j->mitra_jasa_id . '|' . $j->flight_arr . '|' . $j->flight_dep;
            if (! in_array($key, $keepKeys, true)) {
                $j->delete();
            }
        });

        foreach ($rows as $r) {
            $route = $r['origin'] . '-AAP-' . $r['dest'];
            JadwalPenerbangan::updateOrCreate(
                [
                    'mitra_jasa_id' => $mitraIds[$r['kode']],
                    'flight_arr' => $r['arr'],
                    'flight_dep' => $r['dep'],
                ],
                [
                    'origin' => $r['origin'],
                    'destination' => $r['dest'],
                    'route' => $route,
                    'aircraft_type' => $r['type'],
                    'registrasi_pesawat' => $r['reg'],
                    'sched_arrival' => $r['sched_arr'],
                    'sched_departure' => $r['sched_dep'],
                    'aktif' => true,
                ]
            );
        }

        // Sample pemakaian_garbarata 14 Juni 2026 (DRAFT) — sinkron dengan jadwal di atas.
        $tanggal = Carbon::createFromDate(2026, 6, 14);
        $layananGarbarataId = \DB::table('layanan_jasas')
            ->where('kode_layanan', 'APT-01-07-JASA-PEMAKAIAN-GARBARATA-AVIOBRIDGE')
            ->value('id');
        $tarif = 280000;

        PemakaianGarbarata::whereDate('tanggal', $tanggal->toDateString())
            ->whereNull('pengajuan_penagihan_garbarata_id')
            ->where('status', PemakaianGarbarata::STATUS_DRAFT)
            ->get()
            ->each(function ($p) use ($keepKeys) {
                $key = $p->mitra_jasa_id . '|' . $p->flight_arr . '|' . $p->flight_dep;
                if (! in_array($key, $keepKeys, true)) {
                    $p->forceDelete();
                }
            });

        foreach ($rows as $r) {
            $dockTime = Carbon::parse($tanggal->format('Y-m-d') . ' ' . $r['sched_arr']);
            $undockTime = Carbon::parse($tanggal->format('Y-m-d') . ' ' . $r['sched_dep']);
            if ($undockTime->lt($dockTime)) {
                $undockTime->addDay();
            }
            $durasi = PemakaianGarbarata::computeDuration($dockTime->toDateTimeString(), $undockTime->toDateTimeString());
            $rentang = PemakaianGarbarata::computeRentang($durasi);

            PemakaianGarbarata::updateOrCreate(
                [
                    'mitra_jasa_id' => $mitraIds[$r['kode']],
                    'tanggal' => $tanggal->toDateString(),
                    'flight_arr' => $r['arr'],
                    'flight_dep' => $r['dep'],
                ],
                [
                    'layanan_jasa_id' => $layananGarbarataId,
                    'nomor_penerbangan' => $r['arr'] . '/' . $r['dep'],
                    'registrasi_pesawat' => $r['reg'],
                    'route' => $r['origin'] . '-AAP-' . $r['dest'],
                    'type_pesawat' => $r['type'],
                    'docking_at' => $dockTime,
                    'undocking_at' => $undockTime,
                    'durasi_menit' => $durasi,
                    'jumlah_rentang' => $rentang,
                    'tarif_garbarata' => $tarif,
                    'status' => PemakaianGarbarata::STATUS_DRAFT,
                ]
            );
        }
    }
}
