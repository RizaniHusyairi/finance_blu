<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('master_jenis_layanan', function (Blueprint $table) {
            $table->id();
            $table->string('kode_jenis')->unique();
            $table->string('nama_jenis');
            $table->text('deskripsi')->nullable();
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('status_aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('master_kategori_layanan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jenis_layanan_id')
                ->constrained('master_jenis_layanan')
                ->cascadeOnDelete();
            $table->string('kode_kategori');
            $table->string('nama_kategori');
            $table->text('deskripsi')->nullable();
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('status_aktif')->default(true);
            $table->timestamps();

            $table->unique(['jenis_layanan_id', 'kode_kategori'], 'mkategori_layanan_unique_per_jenis');
        });

        Schema::create('master_item_tarif_layanan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jenis_layanan_id')
                ->constrained('master_jenis_layanan')
                ->cascadeOnDelete();
            $table->foreignId('kategori_layanan_id')
                ->constrained('master_kategori_layanan')
                ->cascadeOnDelete();
            $table->string('kode_item');
            $table->string('nama_item');
            $table->unsignedTinyInteger('kdlevel')->default(3);
            $table->string('kdmak')->nullable();
            $table->string('kdakunplus')->nullable();
            $table->string('satuan')->nullable();
            $table->string('kdmu')->nullable();
            $table->string('nmmu')->nullable();
            $table->decimal('tarif', 20, 2)->nullable();
            $table->boolean('is_billable')->default(false);
            $table->unsignedInteger('sumber_excel_row')->nullable();
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('status_aktif')->default(true);
            $table->timestamps();

            $table->unique(['kategori_layanan_id', 'kode_item'], 'mitem_tarif_unique_per_kategori');
            $table->index(['jenis_layanan_id', 'kategori_layanan_id'], 'mitem_tarif_jenis_kategori_idx');
            $table->index('is_billable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_item_tarif_layanan');
        Schema::dropIfExists('master_kategori_layanan');
        Schema::dropIfExists('master_jenis_layanan');
    }
};
