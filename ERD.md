
1. Migration Master Data

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

	// Tabel Master COA Pajak/Pendapatan
        Schema::create('master_coas', function (Blueprint $table) {
            $table->id();
            $table->string('kd_program', 10)->nullable();
            $table->string('kd_giat', 10)->nullable();
            $table->string('kd_output', 10)->nullable();
            $table->string('kd_suboutput', 10)->nullable();
            $table->string('kd_komponen', 10)->nullable();
            $table->string('kd_subkomponen', 10)->nullable();
            $table->string('kd_akun', 20)->nullable();
            $table->string('kd_item', 20)->nullable(); 
	    // TAMBAHAN 1: Kolom penggabung (opsional tapi sangat membantu)
            // Contoh isi: '022.01.WA.4154.EBA.962.051.521111'
            $table->string('kode_mak_lengkap', 100)->unique()->nullable()->comment('Gabungan seluruh kode urut');
            // Nama akun, misal: 'Belanja Gaji Pokok'
            $table->string('nama_akun', 150); 
            // Klasifikasi, misal: 'Aset', 'Pendapatan', 'Beban'
            $table->string('jenis_akun', 50)->nullable(); 
            $table->timestamps();
        });

        // Tabel Master DIPA (Detail dari Pagu_BLU.csv)
        Schema::create('master_dipas', function (Blueprint $table) {
            $table->id();
            // Nomor resmi surat DIPA
            $table->string('nomor_dipa', 100)->unique(); 
            // Tahun anggaran
            $table->year('tahun_anggaran'); 
            // Menyimpan nilai uang (pagu) yang besar, 15 digit angka dengan 2 desimal
            $table->decimal('total_pagu', 15, 2)->default(0); 
            // Untuk melacak ini DIPA awal (0) atau revisi ke berapa (1, 2, dst)
            $table->integer('revisi_ke')->default(0); 
            $table->date('tanggal_disahkan')->nullable();
            $table->timestamps();
        });

        Schema::create('detail_dipas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dipa_id')->constrained('master_dipas')->cascadeOnDelete();
            
            // coa_id di sini sekarang merujuk ke 1 baris utuh (Program s.d Item)
            $table->foreignId('coa_id')->constrained('master_coas')->restrictOnDelete(); 
            
            // Pagu awal yang ditetapkan di DIPA untuk rincian item tersebut
            $table->decimal('nilai_pagu', 15, 2)->default(0);
            $table->timestamps();
        });

        

        // 4. Tabel Riwayat Revisi DIPA
        Schema::create('riwayat_revisi_dipa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_dipa_id')->constrained('master_dipas')->cascadeOnDelete();
            $table->integer('nomor_revisi')->default(0);
            $table->date('tanggal_revisi');
            $table->decimal('pagu_sebelumnya', 15, 2);
            $table->decimal('pagu_baru', 15, 2);
            $table->string('file_dokumen_dipa')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });

        // 5. Tabel Master Mitra & Vendor (Dari Supplier.csv)
        Schema::create('master_mitra_vendor', function (Blueprint $table) {
            $table->id();
            $table->enum('kategori', ['VENDOR_PENGELUARAN', 'MITRA_PENERIMAAN', 'KEDUANYA'])->default('VENDOR_PENGELUARAN');
            $table->string('tipe_supplier', 50)->nullable(); // 01 Satker, 02 Penyedia, dll
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('npwp', 30)->nullable();
            $table->string('nama_perusahaan', 150);
            $table->string('nama_direktur', 100)->nullable(); // Nullable karena bisa Satker/Pegawai
            $table->text('alamat')->nullable();
            $table->string('no_telepon', 30)->nullable();
            $table->timestamps();
        });


        Schema::create('master_pegawai', function (Blueprint $table) {
            $table->id();
            // Optional referensi ke tabel users jika pegawai tersebut berhak login
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->string('nip', 50)->unique()->nullable();
            $table->string('nama_lengkap', 150);
            $table->string('jabatan', 100)->nullable();
            $table->string('npwp', 50)->nullable();
            $table->boolean('status_aktif')->default(true);
            $table->timestamps();
        });

        // 7. Tabel Rekening Bank (Polymorphic)
        Schema::create('rekening_bank', function (Blueprint $table) {
            $table->id();
            $table->morphs('pemilik'); 
            $table->string('nama_bank', 50);
            $table->string('nomor_rekening', 50);
            $table->string('nama_rekening', 150);
            $table->timestamps();
        });

        

        // 9. Tabel Master Tarif Pajak Terintegrasi
        Schema::create('master_tarif_pajak', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_pajak', 50); 
            $table->decimal('persentase', 5, 2); 
            $table->boolean('status_aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Drop urutan terbalik
        Schema::dropIfExists('master_tarif_pajak');
        Schema::dropIfExists('master_coas');
        Schema::dropIfExists('rekening_bank');
        Schema::dropIfExists('master_mitra_vendor');
        Schema::dropIfExists('riwayat_revisi_dipa');
        Schema::dropIfExists('detail_dipas');
        Schema::dropIfExists('master_dipas');
        Schema::dropIfExists('master_pegawai');
       
    }
};


2. Migration Modul Manajemen Kontrak

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kontrak_pengadaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('master_mitra_vendor')->restrictOnDelete();
            $table->foreignId('master_dipa_id')->constrained('master_dipas')->restrictOnDelete();
            $table->string('nomor_spk', 100)->unique();
            $table->date('tanggal_spk');
            $table->text('nama_pekerjaan');
            $table->decimal('nilai_total_kontrak', 15, 2);
            $table->enum('metode_pembayaran', ['LUMPSUM', 'TERMIN']);
            $table->boolean('ada_uang_muka')->default(false);
            $table->decimal('nilai_uang_muka', 15, 2)->default(0);
            $table->decimal('sisa_uang_muka_belum_lunas', 15, 2)->default(0);
            $table->integer('jangka_waktu');
            $table->enum('satuan_waktu', ['HARI', 'MINGGU', 'BULAN']);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->enum('status_kontrak', ['DRAFT', 'PENDING_PPK', 'REVISI', 'AKTIF', 'SELESAI', 'DIBATALKAN'])->default('DRAFT');
            $table->foreignId('detail_dipa_id')->nullable()
                  ->constrained('detail_dipas')->nullOnDelete();
            
            // 2. Jangka Waktu Pemeliharaan (Biasanya dalam Hari)
            $table->integer('masa_pemeliharaan_hari')->default(0)
                  ->comment('Diambil dari Resume Kontrak poin 11');

            // 3. Ketentuan Sanksi / Denda
            $table->string('ketentuan_sanksi', 255)->nullable()
                  ->comment('Diambil dari Resume Kontrak poin 12, misal: 1/1000 per hari');
            
            // Opsi Tambahan: Mata Uang (Default IDR sesuai sheet InputKontrak)
            $table->string('mata_uang', 10)->default('IDR');
            $table->foreignId('disetujui_ppk_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('waktu_persetujuan_ppk')->nullable();
            // --- KOLOM FILE DOKUMEN KONTRAK AWAL ---
            $table->string('file_spk')->nullable()->comment('Arsip Surat Perintah Kerja');
            $table->string('file_spmk')->nullable()->comment('Arsip Surat Perintah Mulai Kerja');
            $table->string('file_ringkasan_kontrak')->nullable()->comment('Arsip Ringkasan Kontrak');
            $table->timestamps();
        });

        Schema::create('kontrak_termin', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kontrak_pengadaan_id')->constrained('kontrak_pengadaan')->cascadeOnDelete();
            $table->enum('jenis_termin', ['UANG_MUKA', 'PROGRESS', 'PELUNASAN', 'RETENSI']);
            $table->integer('termin_ke');
            $table->string('keterangan_termin');
            $table->decimal('persentase', 5, 2);
            $table->decimal('nilai_bruto_termin', 15, 2);
            $table->decimal('potongan_angsuran_uang_muka', 15, 2)->default(0);
            $table->decimal('nilai_retensi', 15, 2)->default(0);
            $table->enum('status_termin', ['LOCKED', 'READY_TO_BILL', 'SUDAH_DITAGIH'])->default('LOCKED');
            $table->timestamps();
        });

        Schema::create('jaminan_kontrak', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kontrak_pengadaan_id')->constrained('kontrak_pengadaan')->cascadeOnDelete();
            $table->enum('jenis_jaminan', ['UANG_MUKA', 'PELAKSANAAN', 'PEMELIHARAAN']);
            $table->string('penjamin', 150);
            $table->string('nomor_jaminan', 100);
            $table->date('tanggal_jaminan');
            $table->integer('masa_berlaku_hari');
            $table->date('tanggal_mulai_jaminan');
            $table->date('tanggal_selesai_jaminan');
            $table->decimal('nilai_jaminan', 15, 2);
            $table->string('file_jaminan')->nullable();
            $table->timestamps();
        });

        Schema::create('kontrak_addendum', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kontrak_pengadaan_id')->constrained('kontrak_pengadaan')->cascadeOnDelete();
            $table->string('nomor_addendum', 100)->unique();
            $table->date('tanggal_addendum');
            $table->enum('jenis_addendum', ['TAMBAH_KURANG_NILAI', 'PERPANJANGAN_WAKTU', 'GANTI_SPESIFIKASI', 'KOMBINASI']);
            $table->text('keterangan_alasan');
            $table->decimal('nilai_kontrak_lama', 15, 2);
            $table->date('tanggal_selesai_lama');
            $table->integer('jangka_waktu_lama');
            $table->decimal('nilai_kontrak_baru', 15, 2)->nullable();
            $table->date('tanggal_selesai_baru')->nullable();
            $table->integer('jangka_waktu_baru')->nullable();
            $table->string('file_addendum')->nullable();
            $table->enum('status_addendum', ['DRAFT', 'APPROVED_PPK'])->default('DRAFT');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kontrak_addendum');
        Schema::dropIfExists('jaminan_kontrak');
        Schema::dropIfExists('kontrak_termin');
        Schema::dropIfExists('kontrak_pengadaan');
    }
};


3. Migration Modul Transaksi & Tagihan Utama

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Penerimaan
        Schema::create('transaksi_penerimaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mitra_id')->constrained('master_mitra_vendor')->restrictOnDelete();
            $table->foreignId('master_coa_id')->constrained('master_coas')->restrictOnDelete();
            $table->string('nomor_invoice', 100)->unique();
            $table->date('tanggal_jatuh_tempo');
            $table->decimal('nominal_tagihan', 15, 2);
            $table->decimal('nominal_denda_keterlambatan', 15, 2)->default(0);
            $table->decimal('total_dibayar', 15, 2)->default(0);
            $table->enum('status_pembayaran', ['UNPAID', 'PARTIAL', 'PAID'])->default('UNPAID');
            $table->timestamps();
        });

        // Induk Tagihan (Pengeluaran)
        Schema::create('tagihan', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_tagihan', 100)->unique();
            $table->enum('tipe_tagihan', ['PERJALDIN', 'KONTRAK', 'HONORARIUM']);
            $table->foreignId('master_dipa_id')->constrained('master_dipas')->restrictOnDelete();
            $table->text('deskripsi');
            $table->decimal('total_bruto', 15, 2);
            $table->decimal('total_potongan', 15, 2)->default(0);
            $table->decimal('total_netto', 15, 2);
            
            // Status bisa diubah menjadi lebih detail: 
            // DRAFT, PENDING_PPK, PENDING_BENDAHARA, READY_FOR_SPP
            $table->string('status', 50)->default('DRAFT'); 
            
            // Siapa yang membuat (Bisa Pejabat Pengadaan, Operator Perjaldin, atau PPABP)
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            
            // --- TAMBAHAN UNTUK WORKFLOW VERIFIKASI ---
            $table->foreignId('diverifikasi_ppk_id')->nullable()->constrained('users');
            $table->dateTime('waktu_verifikasi_ppk')->nullable();
            
            $table->foreignId('diverifikasi_bendahara_id')->nullable()->constrained('users');
            $table->dateTime('waktu_verifikasi_bendahara')->nullable();
            // ------------------------------------------

            $table->timestamps();
        });

        // Potongan
        Schema::create('potongan_tagihan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_id')->constrained('tagihan')->cascadeOnDelete();
            $table->foreignId('pajak_id')->nullable()->constrained('master_tarif_pajak')->restrictOnDelete();
            $table->foreignId('akun_potongan_id')->nullable()->constrained('master_coas')->restrictOnDelete();
            $table->string('jenis_potongan', 50); 
            $table->string('deskripsi');
            $table->decimal('dpp', 15, 2)->default(0);
            $table->decimal('nominal_potongan', 15, 2);
            $table->string('kode_billing', 50)->nullable();
            $table->string('ntpn', 50)->nullable();
            // --- KOLOM FILE DOKUMEN PAJAK ---
            $table->string('file_faktur_pajak')->nullable()->comment('Arsip Faktur Pajak dari Vendor');
            $table->string('file_bukti_setor_pajak')->nullable()->comment('Arsip BPN / SSP / Bukti Potong');
            $table->timestamps();
        });


        Schema::create('log_status_dokumen', function (Blueprint $table) {
            $table->id();
            
            // 1. Relasi Polymorphic (Sihir Laravel!)
            // Ini akan otomatis membuat 2 kolom: 'dokumen_type' (string) dan 'dokumen_id' (bigInt)
            // Jadi bisa dipakai untuk model Tagihan, DokumenSPP, DokumenSPM, dll.
            $table->morphs('dokumen'); 
            
            // 2. Aktor (Siapa yang melakukan aksi)
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            
            // Snapshot role pengguna saat itu (misal: 'PPK', 'Bendahara Pengeluaran', 'Pembuat Tagihan')
            // Penting jika suatu saat user ganti jabatan, riwayat sejarah tidak kacau.
            $table->string('role_saat_itu', 100); 

            // 3. Mesin Status (Perubahan State)
            $table->string('status_sebelumnya', 50)->nullable(); // Misal: 'PENDING_PPK'
            $table->string('status_baru', 50); // Misal: 'REVISI_BENDAHARA'
            
            // 4. Aksi yang dilakukan
            // Contoh: 'DIAJUKAN', 'DISETUJUI', 'DITOLAK', 'DIBATALKAN'
            $table->string('aksi', 50); 
            
            // 5. Catatan / Alasan (Sangat krusial untuk fitur Revisi/Tolak)
            $table->text('catatan')->nullable(); 
            
            // Jejak digital tambahan (opsional tapi bagus untuk keamanan instansi)
            $table->string('ip_address', 45)->nullable(); 

            // created_at akan bertindak sebagai Waktu Eksekusi
            $table->timestamps(); 
        });

        // Anak Tagihan: Perjaldin
        Schema::create('detail_perjaldin', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_id')->constrained('tagihan')->cascadeOnDelete();
            $table->foreignId('pegawai_id')->constrained('master_pegawai')->restrictOnDelete();
            $table->string('no_spt', 100);
            $table->string('tujuan', 100);
            $table->date('tgl_berangkat');
            $table->integer('lama_hari');
            $table->decimal('biaya_tiket', 15, 2)->default(0);
            $table->decimal('biaya_transport', 15, 2)->default(0);
            $table->decimal('biaya_penginapan', 15, 2)->default(0);
            $table->decimal('uang_harian', 15, 2)->default(0);
            $table->decimal('uang_representasi', 15, 2)->default(0);
            
            
            $table->timestamps();
        });

        // Anak Tagihan: Kontrak
        Schema::create('detail_kontrak', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_id')->constrained('tagihan')->cascadeOnDelete();
            $table->foreignId('kontrak_termin_id')->constrained('kontrak_termin')->restrictOnDelete();
            
            // Nomor & Tanggal Dokumen
            $table->string('nomor_bapp', 100)->nullable()->comment('Berita Acara Pemeriksaan Pekerjaan');
            $table->date('tanggal_bapp')->nullable();
            
            $table->string('nomor_bast', 100)->nullable()->comment('Berita Acara Serah Terima');
            $table->date('tanggal_bast')->nullable();
            
            $table->string('nomor_bap', 100)->nullable()->comment('Berita Acara Pembayaran');
            $table->date('tanggal_bap')->nullable();

            // --- KOLOM FILE DOKUMEN PENAGIHAN ---
            $table->string('file_bapp')->nullable()->comment('Arsip file BAPP');
            $table->string('file_bast')->nullable()->comment('Arsip file BAST');
            $table->string('file_bap')->nullable()->comment('Arsip file BAP');
            $table->string('file_invoice')->nullable()->comment('Arsip Surat Permohonan Pembayaran');
            $table->string('file_kwitansi')->nullable()->comment('Arsip Kwitansi bermaterai');
            
            // Jaga-jaga jika ada dokumen tambahan (misal: foto dokumentasi proyek)
            $table->string('file_lampiran_lainnya')->nullable()->comment('File pendukung lainnya dalam bentuk ZIP/PDF');

            $table->timestamps();
        });

        // Anak Tagihan: Honorarium
        Schema::create('detail_honorarium', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_id')->constrained('tagihan')->cascadeOnDelete();
            $table->string('nama_personel', 255);
            $table->string('nrp_nip', 100)->nullable();
            $table->string('pangkat_korp', 100)->nullable();
            $table->string('jabatan', 100)->nullable();
            $table->decimal('nilai_honor', 15, 2);
            $table->decimal('pph', 15, 2);
            $table->string('rekening', 100);
            $table->string('jenis_bank', 50);
            $table->string('nama_rekening', 100);
            $table->timestamps();
        });

        Schema::create('realisasi_anggaran', function (Blueprint $table) {
            $table->id();
            
            // Langsung tembak ke rincian DIPA-nya
            $table->foreignId('detail_dipa_id')->constrained('detail_dipas')->restrictOnDelete();
            
            $table->date('tanggal_pencairan');
            $table->string('nomor_bukti', 100)->unique(); // Misal: nomor kuitansi/SP2D
            $table->decimal('nominal_cair', 15, 2)->default(0);
            $table->text('keterangan')->nullable(); // Misal: "Pembayaran tiket pesawat dinas..."
            
            // Bisa ditambah kolom untuk user pembuat transaksi
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_honorarium');
        Schema::dropIfExists('detail_kontrak');
        Schema::dropIfExists('detail_perjaldin');
        Schema::dropIfExists('log_status_dokumen');
        Schema::dropIfExists('potongan_tagihan');
        Schema::dropIfExists('tagihan');
        Schema::dropIfExists('transaksi_penerimaan');
        Schema::dropIfExists('realisasi_anggaran');
    }
};


4. Migration Modul Birokrasi Pencairan

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Dokumen SPP
        Schema::create('dokumen_spp', function (Blueprint $table) {
            $table->id();
            
            // Relasi Utama
            $table->foreignId('tagihan_id')->constrained('tagihan')->restrictOnDelete();
            $table->foreignId('detail_dipa_id')->nullable()->constrained('detail_dipas')->restrictOnDelete();
            
            $table->string('kategori_pembayaran', 50); 
            $table->decimal('nominal_spp', 15, 2);
            $table->string('nomor_spp', 100)->unique();
            $table->date('tanggal_spp');
            
            // --- KOLOM FILE ARSIP ---
            $table->string('file_dokumen_spp')->nullable()->comment('Arsip file PDF SPP bertanda tangan');
            
            $table->string('status', 50)->default('DRAFT'); 
            $table->foreignId('dibuat_oleh_id')->constrained('users')->restrictOnDelete();
            
            // Gerbang Verifikasi SPP
            $table->foreignId('disetujui_kasubag_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('waktu_approval_kasubag')->nullable();
            
            $table->foreignId('disetujui_ppk_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('waktu_approval_ppk')->nullable();

            $table->timestamps();
        });

        // 2. Dokumen SPM
        Schema::create('dokumen_spm', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spp_id')->constrained('dokumen_spp')->restrictOnDelete();
            $table->string('nomor_spm', 100)->unique();
            $table->date('tanggal_spm');
            
            // --- KOLOM FILE ARSIP ---
            $table->string('file_dokumen_spm')->nullable()->comment('Arsip file PDF SPM bertanda tangan');
            
            $table->foreignId('ppspm_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('disetujui_kasubag_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('waktu_approval_kasubag')->nullable();
            $table->timestamps();
        });

        // 3. Dokumen NPI
        Schema::create('dokumen_npi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spm_id')->constrained('dokumen_spm')->restrictOnDelete();
            $table->string('nomor_npi', 100)->unique();
            $table->date('tanggal_npi');
            
            // --- KOLOM FILE ARSIP ---
            $table->string('file_dokumen_npi')->nullable()->comment('Arsip file PDF NPI bertanda tangan');
            
            $table->foreignId('bendahara_penerimaan_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('disetujui_kasubag_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('waktu_approval_kasubag')->nullable();
            $table->foreignId('disetujui_ppk_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('waktu_approval_ppk')->nullable();
            $table->foreignId('disetujui_bend_pengeluaran_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('waktu_approval_bend_pengeluaran')->nullable();
            $table->timestamps();
        });

        // 4. Dokumen SP2D
        Schema::create('dokumen_sp2d', function (Blueprint $table) {
            $table->id();
            $table->foreignId('npi_id')->constrained('dokumen_npi')->restrictOnDelete();
            $table->string('nomor_sp2d', 100)->unique();
            $table->date('tanggal_sp2d');
            
            // --- KOLOM FILE ARSIP ---
            $table->string('file_dokumen_sp2d')->nullable()->comment('Arsip file PDF SP2D bertanda tangan');
            $table->string('bukti_transfer_bank')->nullable()->comment('Bisa berupa foto struk atau PDF dari bank');
            
            $table->foreignId('bendahara_pengeluaran_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('disetujui_kasubag_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('waktu_approval_kasubag')->nullable();
            $table->foreignId('disetujui_ppk_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('waktu_approval_ppk')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen_sp2d');
        Schema::dropIfExists('dokumen_npi');
        Schema::dropIfExists('dokumen_spm');
        Schema::dropIfExists('dokumen_spp');
    }
};


5. Migration Modul Pembukuan & Pelaporan (BKU & SP3B)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Buku Kas Umum (BKU)
        Schema::create('buku_kas_umum', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_transaksi');
            $table->string('nomor_bukti', 100);
            $table->text('uraian');
            $table->enum('arus_kas', ['DEBIT_MASUK', 'KREDIT_KELUAR']);
            $table->decimal('nominal', 15, 2);
            $table->decimal('saldo_akhir', 15, 2); 
            $table->foreignId('sumber_rekening_id')->constrained('rekening_bank')->restrictOnDelete();
            $table->foreignId('referensi_pengeluaran_id')->nullable()->constrained('tagihan')->nullOnDelete();
            $table->foreignId('referensi_penerimaan_id')->nullable()->constrained('transaksi_penerimaan')->nullOnDelete();
            $table->timestamps();
        });

        // Laporan Pengesahan BLU (SP3B / SAKTI)
        Schema::create('laporan_pengesahan_blu', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_laporan', 100)->unique();
            $table->integer('periode_bulan'); 
            $table->year('tahun');
            $table->decimal('total_penerimaan', 15, 2)->default(0);
            $table->decimal('total_pengeluaran', 15, 2)->default(0);
            $table->decimal('saldo_akhir_blu', 15, 2);
            $table->string('file_dokumen_sakti')->nullable();
            $table->enum('status_pengesahan', ['DRAFT', 'VERIFIKASI_KPPN', 'DISAHKAN'])->default('DRAFT');
            $table->string('status_sp3b', 50)->nullable(); // SP3B status untuk tracking
            $table->foreignId('disetujui_kpa_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Notifikasi Sistem
        Schema::create('notifikasi_sistem', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('judul', 150);
            $table->text('pesan');
            $table->boolean('is_read')->default(false);
            $table->string('link_url')->nullable(); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi_sistem');
        Schema::dropIfExists('laporan_pengesahan_blu');
        Schema::dropIfExists('buku_kas_umum');
    }
};