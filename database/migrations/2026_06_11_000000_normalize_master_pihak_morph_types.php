<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rekening vendor yang dibuat lewat form Supplier tersimpan dengan
     * pemilik_type subclass (MasterMitraVendor/MasterPersonelEksternal),
     * sehingga tidak terbaca oleh relasi MasterPihak::rekening() di detail
     * SPP/SPM/NPI/SP2D. Normalisasi semua morph type subclass ke MasterPihak
     * (sejalan dengan override getMorphClass() di model MasterPihak).
     */
    private array $subclassTypes = [
        'App\\Models\\MasterMitraVendor',
        'App\\Models\\MasterPersonelEksternal',
    ];

    public function up(): void
    {
        if (Schema::hasTable('rekening_bank')) {
            DB::table('rekening_bank')
                ->whereIn('pemilik_type', $this->subclassTypes)
                ->update(['pemilik_type' => 'App\\Models\\MasterPihak']);
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'profilable_type')) {
            DB::table('users')
                ->whereIn('profilable_type', $this->subclassTypes)
                ->update(['profilable_type' => 'App\\Models\\MasterPihak']);
        }
    }

    public function down(): void
    {
        // Tidak bisa dikembalikan: subclass asal tiap baris tidak tercatat.
        // Data tetap valid karena semua subclass berbagi tabel master_pihak.
    }
};
