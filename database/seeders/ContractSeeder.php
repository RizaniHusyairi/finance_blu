<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Contract;
use App\Models\ContractTerm;
use App\Models\ContractAddendum;
use App\Models\User;
use App\Models\Budget;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ContractSeeder extends Seeder
{
    public function run()
    {
        $ppk = User::role('PPK')->first();
        $pengadaan = User::role('Pejabat Pengadaan')->first();
        $operator = User::role('Operator BLU')->first();
        $budget = Budget::first() ?? Budget::factory()->create();
        $supplier = Supplier::first() ?? Supplier::factory()->create();

        // 1. Kontrak Sekali Bayar (Aktif)
        $c1 = Contract::updateOrCreate(
            ['contract_number' => 'SPK-901/KONTRAK/2026'],
            [
            'date' => Carbon::parse('2026-01-10'),
            'nomor_spk_sp' => 'SP-901/X/2026',
            'description' => 'Pekerjaan Pemeliharaan AC Gedung Kantor',
            'ketentuan_sanksi' => 'Sanksi denda 1/1000 per hari keterlambatan.',
            'mata_uang' => 'IDR',
            'total_amount' => 50000000,
            'cara_bayar' => 'Sekali Bayar',
            'supplier_id' => $supplier->id,
            'budget_id' => $budget->id,
            'pejabat_pengadaan_id' => $pengadaan->id ?? $operator->id ?? 1,
            'ppk_id' => $ppk->id ?? 1,
            'submitted_by' => $pengadaan->id ?? $operator->id ?? 1,
            'status' => 'Aktif',
            'end_date' => Carbon::parse('2026-03-10'),
        ]);
        
        ContractTerm::firstOrCreate(['contract_id' => $c1->id, 'term_name' => 'Pembayaran Lunas 100%'], [
            'percentage' => 100,
            'amount' => 50000000,
            'status' => 'Belum Diajukan',
        ]);

        ContractAddendum::firstOrCreate(['contract_id' => $c1->id, 'addendum_number' => 'ADD-01/SPK-901/2026'], [
            'date' => Carbon::parse('2026-02-01'),
            'new_total_amount' => 55000000,
            'new_end_date' => Carbon::parse('2026-04-10'),
            'reason' => 'Perubahan lingkup pekerjaan penambahan 2 unit AC.',
            'status' => 'Disetujui',
            'submitted_by' => $pengadaan->id ?? $operator->id ?? 1,
        ]);
        
        $c1->update([
            'total_amount' => 55000000,
            'end_date' => Carbon::parse('2026-04-10'),
        ]);


        // 2. Kontrak Termin (Menunggu PPK)
        $c2 = Contract::updateOrCreate(
            ['contract_number' => 'SPK-902/KONTRAK/2026'],
            [
            'date' => Carbon::parse('2026-02-15'),
            'description' => 'Pembangunan Pagar Pembatas Bandara',
            'mata_uang' => 'IDR',
            'total_amount' => 200000000,
            'cara_bayar' => 'Termin',
            'supplier_id' => $supplier->id,
            'budget_id' => $budget->id,
            'pejabat_pengadaan_id' => $pengadaan->id ?? $operator->id ?? 1,
            'ppk_id' => $ppk->id ?? 1,
            'submitted_by' => $pengadaan->id ?? $operator->id ?? 1,
            'status' => 'Menunggu PPK',
            'end_date' => Carbon::parse('2026-08-15'),
        ]);

        ContractTerm::firstOrCreate(['contract_id' => $c2->id, 'term_name' => 'Termin 1 (30%)'], ['percentage' => 30, 'amount' => 60000000, 'status' => 'Belum Diajukan']);
        ContractTerm::firstOrCreate(['contract_id' => $c2->id, 'term_name' => 'Termin 2 (40%)'], ['percentage' => 40, 'amount' => 80000000, 'status' => 'Belum Diajukan']);
        ContractTerm::firstOrCreate(['contract_id' => $c2->id, 'term_name' => 'Termin 3 (30%)'], ['percentage' => 30, 'amount' => 60000000, 'status' => 'Belum Diajukan']);


        // 3. Kontrak Draft
        Contract::updateOrCreate(
            ['contract_number' => 'SPK-903/KONTRAK/2026'],
            [
            'date' => Carbon::parse('2026-03-01'),
            'description' => 'Pengadaan Seragam Dinas',
            'mata_uang' => 'IDR',
            'total_amount' => 35000000,
            'cara_bayar' => 'Sekali Bayar',
            'supplier_id' => $supplier->id,
            'budget_id' => $budget->id,
            'pejabat_pengadaan_id' => $pengadaan->id ?? $operator->id ?? 1,
            'ppk_id' => $ppk->id ?? 1,
            'submitted_by' => $pengadaan->id ?? $operator->id ?? 1,
            'status' => 'Draft',
            'end_date' => Carbon::parse('2026-04-01'),
        ]);


        // 4. Kontrak Ditolak PPK
        Contract::updateOrCreate(
            ['contract_number' => 'SPK-904/KONTRAK/2026'],
            [
            'date' => Carbon::parse('2026-03-10'),
            'description' => 'Pengadaan Komputer Kantor',
            'mata_uang' => 'IDR',
            'total_amount' => 120000000,
            'cara_bayar' => 'Sekali Bayar',
            'supplier_id' => $supplier->id,
            'budget_id' => $budget->id,
            'pejabat_pengadaan_id' => $pengadaan->id ?? $operator->id ?? 1,
            'ppk_id' => $ppk->id ?? 1,
            'submitted_by' => $pengadaan->id ?? $operator->id ?? 1,
            'status' => 'Ditolak PPK',
            'end_date' => Carbon::parse('2026-05-10'),
        ]);


        // 5. Kontrak Termin Aktif
        $c5 = Contract::updateOrCreate(
            ['contract_number' => 'SPK-905/KONTRAK/2026'],
            [
            'date' => Carbon::parse('2026-01-20'),
            'description' => 'Sewa Kendaraan Operasional Tahunan',
            'mata_uang' => 'IDR',
            'total_amount' => 150000000,
            'cara_bayar' => 'Termin',
            'supplier_id' => $supplier->id,
            'budget_id' => $budget->id,
            'pejabat_pengadaan_id' => $pengadaan->id ?? $operator->id ?? 1,
            'ppk_id' => $ppk->id ?? 1,
            'submitted_by' => $pengadaan->id ?? $operator->id ?? 1,
            'status' => 'Aktif',
            'end_date' => Carbon::parse('2026-12-31'),
        ]);

        ContractTerm::firstOrCreate(['contract_id' => $c5->id, 'term_name' => 'Triwulan 1'], ['percentage' => 25, 'amount' => 37500000, 'status' => 'Diajukan']);
        ContractTerm::firstOrCreate(['contract_id' => $c5->id, 'term_name' => 'Triwulan 2'], ['percentage' => 25, 'amount' => 37500000, 'status' => 'Belum Diajukan']);
        ContractTerm::firstOrCreate(['contract_id' => $c5->id, 'term_name' => 'Triwulan 3'], ['percentage' => 25, 'amount' => 37500000, 'status' => 'Belum Diajukan']);
        ContractTerm::firstOrCreate(['contract_id' => $c5->id, 'term_name' => 'Triwulan 4'], ['percentage' => 25, 'amount' => 37500000, 'status' => 'Belum Diajukan']);
    }
}
