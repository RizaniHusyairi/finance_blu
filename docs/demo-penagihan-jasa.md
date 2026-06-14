# Runbook Demo — Modul Penagihan Jasa (PNBP)

Dokumen pegangan untuk mendemokan modul **Penagihan Jasa** secara profesional dan bebas error.
Disusun untuk demo hari Senin.

---

## 1. Tujuan & Pesan Utama Demo

Tunjukkan bahwa sistem mengelola **siklus penuh tagihan jasa PNBP** secara digital, berjenjang, dan terkontrol:

> **Mitra ditagih → diverifikasi berjenjang (4 lapis) → ditandatangani Kabandara → dikirim ke mitra → dibayar → tercatat otomatis ke piutang & buku kas.**

Tiga nilai jual yang ditekankan:
1. **Verifikasi berjenjang** dengan jejak audit (timeline) — tidak ada tagihan keluar tanpa persetujuan.
2. **Revisi bertarget** — verifikator menunjuk persis bagian yang salah, bukan sekadar catatan bebas. *(fitur baru)*
3. **Otomatisasi hilir** — surat pengantar TTD, notifikasi WA/email, dan pencatatan piutang/BKU terjadi otomatis (Nomor VA diisi manual selama menunggu API BTN, siap dialihkan ke otomatis nanti).

---

## 2. Pemeran & Akun (password semua: `password`)

| Peran dalam demo | Nama | Email login | Tugas saat demo |
|---|---|---|---|
| **Admin Jasa** (pembuat tagihan) | DIAH DESTIANA | `admin.jasa@sikeren.id` | Buat tagihan, perbaiki revisi, publish, tandai lunas |
| **Koordinator Jasa** (verifikator 1) | MUHAMAD SAPRIANSYAH | `koordinator.jasa@sikeren.id` | Verifikasi / minta revisi |
| **Kepala Seksi Yan & Kerjasama** (verifikator 2) | ROSLAN | `kasipk@sikeren.id` | Verifikasi |
| **Kasubbag Keuangan & TU** (verifikator 3) | ZALDI ARDIAN | `kasubbag@sikeren.id` | Verifikasi |
| **KPA / Kabandara** (verifikator 4 + TTD) | I KADEK YULI SASTRAWAN | `kpa@sikeren.id` | Persetujuan final & tanda tangan |
| **Super Admin Jasa** (laporan) | MELLYARTI RAHMAN | `super.admin.jasa@sikeren.id` | Tampilkan rekap & dashboard |

**Alur verifikasi resmi:** Koordinator Jasa → Kepala Seksi → Kasubbag TU → KPA (Kabandara).

> **Tip multi-login:** siapkan **2 jendela browser** (mis. Chrome normal untuk Admin Jasa + Chrome Incognito untuk verifikator) agar tidak perlu logout-login bolak-balik. Untuk 2 verifikator tengah, gunakan **"Mode Cepat → Auto-Approve Semua Step"** (lihat §6 Rencana Cadangan).

---

## 3. Checklist Pra-Demo (WAJIB — penutup celah error)

Jalankan **pagi hari sebelum demo**, urut dari atas.

### 3.1 Matikan pengiriman WhatsApp nyata ⚠️ (paling penting)
Saat ini `whatsapp.enabled = true` dengan gateway aktif → **Publish / Tandai Lunas akan mengirim WA sungguhan ke nomor mitra** dan bisa menggantung ~20 detik bila gateway lambat.

- Login **Super Admin** (`super.admin@sikeren.id`) → menu **Integrasi / Pengaturan WhatsApp** → set **Nonaktif**.
- Efek: Publish & Lunas tetap **menampilkan preview pesan WA/email** (bagus untuk demo) tetapi **tidak benar-benar mengirim** dan **tidak menggantung**.
- Alternatif cepat (tanpa UI):
  ```bash
  php artisan tinker --execute="\App\Models\IntegrationSetting::setValue('whatsapp.enabled', false);"
  ```
  Kembalikan setelah demo dengan nilai `true` bila perlu.

> Email sudah aman (`MAIL_MAILER=log`). Virtual Account dikontrol saklar **"Aktif"** pada menu **Integrasi** (Super Admin Jasa → Bank BTN VA): **OFF** = nomor VA diisi manual saat publish (default sekarang, dipakai untuk demo); **ON** = otomatis via API BTN (kini masih mock). **Biarkan OFF saat demo** — tidak ada API bank yang ditembak, jadi aman.

### 3.2 Siapkan data tagihan yang realistis & rapi
Data uji sekarang bernominal tidak wajar (Rp 1 / Rp 96 / Rp 748). Untuk tampilan profesional, **siapkan tagihan baru bernominal realistis** memakai **Contoh Tagihan di §8** (komposisi layanan, qty, tarif, dan total sudah dihitung dan cocok dengan katalog) di tiap state yang dibutuhkan:
- **1 tagihan status `VERIFIKASI_KOORDINATOR`** → bahan untuk Babak 2 (verifikasi) & Babak 3 (revisi). Gunakan **Contoh A**.
- **1 tagihan status `DISETUJUI` + surat pengantar final** → bahan untuk Babak 4 (publish). Gunakan **Contoh C atau D**.
- Sisakan slot untuk **1 pembuatan tagihan LIVE** sebagai pembuka (pakai **Contoh E** yang ringkas, 2 baris).
- **Siapkan beberapa Nomor VA untuk diketik saat publish** — nomor VA asli dari BTN, atau angka dummy (mis. 16 digit) bila demo. Tiap tagihan butuh **VA berbeda** (sistem menolak VA ganda).

> Sudah dicek: mitra **PT ABC** dan Admin Jasa **DIAH** punya akses ke **seluruh 131 layanan**, jadi semua layanan pada §8 dijamin muncul di dropdown.

### 3.3 Sehatkan aplikasi
```bash
php artisan migrate                 # pastikan semua migrasi jalan (termasuk revisi_target)
php artisan storage:link            # pastikan file PDF/surat bisa diakses
php artisan optimize:clear          # bersihkan cache config/route/view
php artisan queue:work --stop-when-empty   # (opsional) proses notifikasi tertunda
```

### 3.4 Smoke test menyeluruh (H-1)
Lakukan **sekali** jalur penuh pada tagihan buangan: Buat → Verifikasi 4 lapis (boleh Auto-Approve) → Publish (**isi Nomor VA**, kirim ke **nomor WA milik sendiri** atau setelah WA dimatikan) → Tandai Lunas. Pastikan:
- Tidak ada halaman error / pesan merah.
- PDF surat pengantar & invoice terbuka.
- Status berubah benar di tiap langkah.
- Setelah lunas, piutang/BKU ikut tercatat (cek menu Bendahara Penerimaan bila ingin).

Hapus/abaikan tagihan buangan itu sebelum demo.

### 3.5 Persiapan layar
- Zoom browser 100–110%, tutup tab lain, matikan notifikasi OS.
- Buka tab-tab kunci lebih dulu: dashboard Admin Jasa, daftar Verifikasi Tagihan Jasa, satu detail tagihan.
- Siapkan **nomor WA tujuan** yang aman (nomor sendiri) bila tetap ingin mendemokan pengiriman nyata.

---

## 4. Skenario Demo (alur cerita)

Estimasi total **15–20 menit**.

### Babak 0 — Pembuka (1 menit)
Jelaskan konteks 1 kalimat: *"Modul ini menggantikan penagihan jasa manual menjadi satu alur digital dari penerbitan tagihan sampai pelunasan."* Tampilkan **Dashboard Admin Jasa** (ringkasan tagihan, jatuh tempo, nominal).

### Babak 1 — Admin Jasa menerbitkan tagihan (3–4 menit)
Login **Admin Jasa** → menu **Tagihan Jasa → Buat Tagihan**. Pakai **Contoh E (§8)** agar cepat & minim salah ketik.
1. Pilih **tipe** (PNBP Fungsi / Konsesi) dan **Mitra** (PT ABC).
2. Pilih **dokumen dasar/kontrak** dan **periode**.
3. Tambah **rincian layanan** sesuai contoh: pilih layanan, isi qty & harga satuan → sistem menghitung **kode akun** & **jumlah** otomatis.
4. Simpan → tagihan masuk status **VERIFIKASI_KOORDINATOR** dan otomatis menyusun **draft Surat Pengantar**.

*Narasi:* tekankan nomor tagihan otomatis, kode akun otomatis, dan draft surat pengantar yang langsung terbentuk.

### Babak 2 — Verifikasi berjenjang (3–4 menit)
Login **Koordinator Jasa** → menu **Verifikasi Tagihan Jasa** → buka tagihan.
- Tunjukkan panel **Tindakan Verifikasi** + tombol **Lihat Dokumen Dasar** dan **Preview Draft Surat Pengantar**.
- Klik **Setujui Dokumen** → status naik ke verifikator berikutnya.
- Tunjukkan **timeline "Riwayat Proses"** yang mencatat siapa & kapan.

Lanjutkan ke **Kasi → Kasubbag → KPA** (login bergantian, atau pakai Auto-Approve untuk yang tengah).

### Babak 3 — Skenario REVISI bertarget (3–4 menit) ⭐ *fitur baru*
*Ini sorotan utama. Gunakan tagihan kedua yang masih di Koordinator.*
1. Sebagai **Koordinator Jasa**, klik **Minta Revisi**.
2. Pada modal, **centang bagian yang salah** (mis. *Rincian / Nominal Tagihan*) + tulis catatan.
3. Login **Admin Jasa** → buka tagihan → tampil **banner kuning**: *"Tagihan ini diminta untuk direvisi"* lengkap dengan **badge bagian** + catatan verifikator.
4. Klik **Edit**, perbaiki nominal, **simpan**, lalu **Kirim Ulang**.
5. Tunjukkan tagihan kembali masuk antrean verifikasi dari awal.

*Narasi:* *"Verifikator tidak hanya menulis catatan — ia menunjuk persis bagian yang harus diperbaiki, sehingga Admin Jasa tidak menebak."*

### Babak 4 — Persetujuan final, TTD, & Publish (3 menit)
1. Sebagai **KPA/Kabandara**, **Setujui** langkah terakhir → status **DISETUJUI**, **Surat Pengantar Final TTD** dibuat otomatis.
2. Login **Admin Jasa** → buka tagihan → **Publish**:
   - **Isi Nomor Virtual Account (BTN)** — ketik nomor VA asli dari BTN, 8–30 digit, **wajib & unik per tagihan**. *(Selama API BTN belum tersedia, VA diisi manual di sini — bukan di-generate otomatis.)*
   - Isi **nomor WA tujuan** (nomor aman) dan email.
   - Sistem menampilkan **preview pesan WA & email** (sudah memuat Nomor VA tadi), status menjadi **PUBLISHED**, dan Nomor VA tampil di kotak tagihan.
3. (Opsional) Tunjukkan **Portal Mitra** / link publik tagihan + tombol unduh surat pengantar TTD.

> Narasi: *"Nomor VA diketik dari BTN. Saat API BTN aktif nanti, nomor ini terisi otomatis tanpa mengubah alur — pembayaran tetap dicocokkan lewat Nomor VA yang sama."*

### Babak 5 — Pembayaran & pencatatan otomatis (2 menit)
1. Sebagai **Admin Jasa**, klik **Tandai Lunas** (simulasi pembayaran VA).
2. Status → **LUNAS**; sistem otomatis mencatat **piutang (PAID)** + **BKU penerimaan** dan memproses notifikasi lunas.
3. (Opsional) Tunjukkan jejaknya di menu **Bendahara Penerimaan / Piutang**.

### Babak 6 — Laporan & monitoring (1–2 menit)
Login **Super Admin Jasa** → **Laporan**: rekap tagihan, rekap pembayaran, piutang, performa mitra. Tutup dengan dashboard ringkasan.

---

## 5. Fitur yang Wajib Ditonjolkan

- Penomoran tagihan & kode akun otomatis.
- Verifikasi 4 lapis dengan **timeline audit**.
- **Revisi bertarget** (bagian + catatan) — pembeda utama.
- Surat Pengantar **TTD otomatis** saat persetujuan final.
- **Nomor Virtual Account** diisi manual saat publish (wajib & unik), siap transisi ke API BTN tanpa ubah alur + preview notifikasi WA/email.
- Pelunasan otomatis tersambung ke **piutang & buku kas**.
- Laporan rekap untuk pimpinan.

---

## 6. Rencana Cadangan (anti-macet)

- **Mode Cepat → "Auto-Approve Semua Step"** (tombol pada detail tagihan milik Admin Jasa): meloncati 4 verifikasi sekaligus + membuat surat pengantar TTD. Pakai bila waktu mepet atau salah satu akun verifikator bermasalah.
- **Data ter-stage:** karena tiap babak punya tagihan yang sudah disiapkan di state-nya (lihat §3.2), bila pembuatan LIVE tersendat, langsung lanjut ke tagihan cadangan.
- **Bila Publish/Lunas lambat:** pastikan WhatsApp sudah dimatikan (§3.1) — pengiriman jadi instan (mock) tanpa menggantung.
- **Bila PDF tidak terbuka:** jalankan `php artisan storage:link` dan ulangi.

---

## 7. Ringkasan Status Keamanan Demo (hasil pemeriksaan)

| Komponen | Status | Aksi |
|---|---|---|
| Email (`MAIL_MAILER`) | `log` — aman, tidak terkirim | — |
| Virtual Account | Toggle **"Aktif"** OFF → diisi manual saat publish (wajib & unik); ON → otomatis via API BTN (kini mock) | Biarkan **OFF** untuk demo; siapkan Nomor VA (§3.2) |
| **WhatsApp** | **`enabled=true`, gateway aktif — bisa kirim nyata & menggantung** | **Matikan sebelum demo (§3.1)** |
| Nominal data uji | Tidak realistis (Rp 1/96/748) | Stage data baru realistis (§3.2) |
| Migrasi `revisi_target` | Sudah dijalankan | — |

> **Satu hal yang tidak boleh dilupakan:** matikan WhatsApp di pengaturan Integrasi sebelum demo. Itu satu-satunya celah yang bisa menyebabkan pesan nyata terkirim atau halaman menggantung di depan audiens.

---

## 8. Contoh Tagihan Realistis (siap diketik)

Semua layanan, satuan, tarif, dan kode akun di bawah **diambil langsung dari katalog layanan jasa** sistem. Qty dibuat wajar dan jumlah sudah dihitung. Saat input, presenter cukup memilih layanan + mengisi **qty** (harga satuan & kode akun terisi otomatis dari tarif katalog).

> Catatan: beberapa layanan muncul dalam **dua versi tarif** (mis. PJP2U Rp 72.000 dan Rp 79.200 — versi tarif lama/baru). Pilih yang nominalnya sesuai contoh agar total cocok.

### Contoh A — Tagihan Jasa Pelayanan Pesawat (Maskapai) · *tipe PNBP Fungsi*
Mitra: **PT ABC** · Periode: **1–31 Mei 2026** · No. dokumen dasar: kontrak/PKS maskapai

| No | Layanan | Satuan | Qty | Tarif (Rp) | Jumlah (Rp) |
|---|---|---|---:|---:|---:|
| 1 | Pelayanan Jasa Pendaratan — *a. bobot pesawat s.d 40.000kg* | tiap 1.000kg | 2.520 | 6.000 | 15.120.000 |
| 2 | Jasa Penempatan Pesawat Udara (parkir) | per jam, per ton | 1.800 | 748 | 1.346.400 |
| 3 | Jasa Pemakaian Check-in Counter | per penumpang | 3.150 | 2.400 | 7.560.000 |
| 4 | Jasa Pemakaian Garbarata (Aviobridge) | per 2 jam | 45 | 280.000 | 12.600.000 |
| | | | | **TOTAL** | **36.626.400** |

*Dipakai untuk Babak 2 (verifikasi) & Babak 3 (revisi). Skenario revisi: ubah qty PJP2U/garbarata yang "salah".*

### Contoh B — Tagihan PJP2U (Pelayanan Jasa Penumpang) · *tagihan terpisah*
Mitra: **PT ABC** · Periode bulanan

| No | Layanan | Satuan | Qty | Tarif (Rp) | Jumlah (Rp) |
|---|---|---|---:|---:|---:|
| 1 | Pelayanan Jasa Penumpang Pesawat Udara (PJP2U) | per penumpang | 3.150 | 72.000 | 226.800.000 |
| | | | | **TOTAL** | **226.800.000** |

*Menonjolkan nilai PNBP penumpang yang besar; bagus untuk menunjukkan skala.*

### Contoh C — Tagihan Konsesi BBM (Fuel Throughput) · *tipe Konsesi*
Mitra: penyedia avtur (mis. **PT Pertamina Patra Niaga**) · Periode bulanan

| No | Layanan | Satuan | Qty | Tarif (Rp) | Jumlah (Rp) |
|---|---|---|---:|---:|---:|
| 1 | Konsesi Pengisian BBM Pesawat — *Fuel Throughput* | per liter | 1.250.000 | 17 | 21.250.000 |
| | | | | **TOTAL** | **21.250.000** |

### Contoh D — Tagihan Sewa Ruang & Reklame (tenant komersial) · *tipe Konsesi/Sewa*
Mitra: penyewa komersial (mis. **PT Angkasa Niaga Retail**) · Periode bulanan

| No | Layanan | Satuan | Qty | Tarif (Rp) | Jumlah (Rp) |
|---|---|---|---:|---:|---:|
| 1 | Penempatan Mesin ATM di Bandar Udara | per unit per bulan | 4 | 1.500.000 | 6.000.000 |
| 2 | Reklame — *b. Papan reklame (billboard)* | per m2 per bulan | 24 | 80.000 | 1.920.000 |
| 3 | Penggunaan Tanah pada UPBU | per m2 per bulan | 150 | 15.000 | 2.250.000 |
| | | | | **TOTAL** | **10.170.000** |

### Contoh E — Tagihan Jasa Kargo (ringkas, untuk create LIVE) · *tipe PNBP Fungsi*
Mitra: operator kargo (mis. **PT Kargo Nusantara**) · Periode bulanan

| No | Layanan | Satuan | Qty | Tarif (Rp) | Jumlah (Rp) |
|---|---|---|---:|---:|---:|
| 1 | Jasa Layanan Kargo dan Pos Pesawat Udara | per kg | 42.000 | 96 | 4.032.000 |
| 2 | Jasa Penyimpanan Barang Antar Bandara Dalam Negeri | per kg per hari | 8.500 | 96 | 816.000 |
| | | | | **TOTAL** | **4.848.000** |

*Hanya 2 baris → cepat diketik saat demo live, kecil risiko salah.*
