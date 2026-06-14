# Runbook Demo — Modul Layanan Jasa (Menyeluruh)

Skenario demo end-to-end untuk **seluruh modul Jasa**, mencakup tiga peran utama: **Super Admin Jasa**, **Admin Jasa**, dan **Mitra**.
Untuk detail alur penagihan (verifikasi berjenjang, revisi bertarget, VA manual), dokumen ini menunjuk ke **[Runbook Penagihan Jasa](demo-penagihan-jasa.md)** agar tidak duplikatif.

---

## 1. Gambaran & Relasi Peran

Modul Jasa mengelola **PNBP Jasa Kebandarudaraan** dari hulu ke hilir. Tiga peran saling terhubung:

```
SUPER ADMIN JASA  →  menyiapkan & mengawasi
  (master layanan/tarif, akun Admin Jasa, data Mitra, kontrak, konsesi/PJP2U, integrasi, laporan)
        │
        ▼
MITRA  →  melapor mandiri
  (lihat layanan aktif, input laporan omzet konsesi / jumlah penumpang PJP2U, submit)
        │
        ▼
ADMIN JASA  →  operasional penagihan
  (verifikasi laporan mitra → buat tagihan → verifikasi berjenjang → publish → pelunasan)
        │
        ▼
MITRA  →  menerima & membayar tagihan (lihat invoice, surat pengantar TTD, bayar via VA)
        │
        ▼
SUPER ADMIN JASA  →  memantau (rekap tagihan, pembayaran, piutang, performa mitra)
```

**Pesan utama demo:** *"Satu ekosistem digital — penyedia layanan (mitra) melapor sendiri, BLU memverifikasi & menagih berjenjang, dan pimpinan memantau semuanya secara real-time."*

> **Baca juga:** §9 (alur yang sering rancu), §10 (status lifecycle), §11 (pembayaran & pengingat) — bagian ini memperjelas titik-titik yang paling sering ditanyakan saat demo.

---

## 2. Akun & Kredensial (password semua: `password`)

| Peran | Nama | Email login |
|---|---|---|
| **Super Admin Jasa** | MELLYARTI RAHMAN | `super.admin.jasa@sikeren.id` |
| **Admin Jasa** | DIAH DESTIANA | `admin.jasa@sikeren.id` |
| **Mitra (Mitra Jasa)** | PT ABC | `elfandro11@gmail.com` |
| Koordinator Jasa (verifikator 1) | MUHAMAD SAPRIANSYAH | `koordinator.jasa@sikeren.id` |
| Kepala Seksi Yan & Kerjasama (verifikator 2) | ROSLAN | `kasipk@sikeren.id` |
| Kasubbag Keuangan & TU (verifikator 3) | ZALDI ARDIAN | `kasubbag@sikeren.id` |
| KPA / Kabandara (verifikator 4 + TTD) | I KADEK YULI SASTRAWAN | `kpa@sikeren.id` |
| Admin Listrik (utilitas) | FAJRUL SYAMSI | `admin.listrik@sikeren.id` |
| Admin Air (utilitas) | PALUNG PURNAMA H. | `admin.air@sikeren.id` |

> **Password Mitra**: akun `elfandro11@gmail.com` dibuat otomatis (password tidak diketahui). Sebelum demo, **reset dari Super Admin Jasa → Kelola → Mitra Jasa → buka PT ABC → Reset Akun**, lalu catat password barunya untuk login portal mitra.

---

## 3. Peta Modul / Fitur per Peran

### 3.1 SUPER ADMIN JASA — penyiapan & pengawasan
| Modul | Menu | Fungsi |
|---|---|---|
| Dashboard Jasa | Dashboard → **Dashboard Jasa** | Ringkasan tagihan, pembayaran, mitra |
| **Master Layanan Jasa** | Layanan Jasa → Kelola Jasa → **Layanan Jasa** | Katalog layanan + tarif (PNBP & konsesi), CRUD |
| **Kelola Admin Jasa** | Kelola Jasa → **Admin Jasa** | CRUD akun Admin Jasa + tetapkan layanan yang dikelola |
| **Kelola Mitra Jasa** | Kelola Jasa → **Mitra Jasa** | CRUD mitra, akun login mitra, assign layanan, **kontrak/dokumen dasar**, **konsesi (% tarif)**, **PJP2U (tarif penumpang)** |
| **Integrasi API** | Kelola Jasa → **Integrasi API** | Toggle **VA BTN** (OFF=manual), WhatsApp, Email + tombol test |
| **Nomor Tagihan** | Tagihan Jasa → **Nomor Tagihan** | Set nomor awal register tagihan |
| **Verifikasi Laporan** | Verifikasi Laporan → Konsesi / PAX PJP2U / Utilitas | Verifikasi laporan mitra & utilitas |
| **Laporan Jasa** | Pembukuan & Laporan → Laporan Jasa | Rekap Tagihan, per Layanan, Terima Setor, Pembayaran, Piutang, **Performa Mitra** (+ export PDF/Excel) |

### 3.2 ADMIN JASA — operasional penagihan (scoped ke layanan yang ditugaskan)
| Modul | Menu | Fungsi |
|---|---|---|
| Dashboard Admin Jasa | Dashboard → **Dashboard Admin Jasa** | Ringkasan tagihan layanan yang dikelola |
| **Verifikasi Laporan Mitra** | Verifikasi Laporan Mitra → Konsesi / PAX PJP2U / Laporan Utilitas | Verifikasi laporan omzet/penumpang yang diajukan mitra |
| **Buat & Kelola Tagihan** | Tagihan → **Buat Tagihan** | Terbitkan tagihan (PNBP/konsesi), edit revisi, publish, mark lunas — lihat [runbook penagihan](demo-penagihan-jasa.md) |
| Log Tagihan Bulanan / Jatuh Tempo | Tagihan → Log Tagihan Bulanan / Jatuh Tempo | Monitoring & prioritas penagihan |
| Layanan Dikelola | Kelola → **Layanan Dikelola** | Lihat katalog layanan yang ditugaskan |
| Mitra Jasa | Kelola → **Mitra Jasa** | Lihat mitra terkait |

### 3.3 MITRA (Mitra Jasa) — self-service
| Modul | Menu | Fungsi |
|---|---|---|
| Dashboard Mitra | **Dashboard Mitra** | Ringkasan tagihan & status pembayaran |
| Layanan Aktif | **Layanan Aktif** | Daftar layanan yang berlaku + unduh kontrak |
| **Laporan Konsesi** | Laporan Konsesi → Input Laporan / Riwayat | Lapor **omzet** untuk perhitungan konsesi (% omzet) |
| **Laporan PAX PJP2U** | Laporan PAX PJP2U → Input Laporan / Riwayat | Lapor **jumlah penumpang** untuk PJP2U |
| **Tagihan & Invoice** | Dashboard → tautan tagihan | Lihat tagihan, unduh **invoice PDF** & **surat pengantar TTD** |
| Profil & Keamanan | **Profil & Keamanan** | Ubah profil & password |
| Verifikasi TTE (publik) | QR pada surat pengantar | Validasi keaslian dokumen via halaman publik |

---

## 4. Checklist Pra-Demo

Ikuti dulu **[checklist §3 runbook penagihan](demo-penagihan-jasa.md)** (matikan WhatsApp, `migrate`, `storage:link`, `optimize:clear`, smoke test). Tambahan khusus demo menyeluruh:

1. **Reset password akun Mitra** (PT ABC) dari Super Admin Jasa → Mitra → Reset Akun. Catat password.
2. **Pastikan PT ABC punya minimal 1 layanan konsesi & 1 layanan PJP2U aktif** + 1 kontrak/dokumen dasar (sudah ada — PT ABC ter-assign seluruh layanan). Cek di Kelola Mitra.
3. **Siapkan 1 laporan mitra status "diajukan"** (atau buat live di Babak 2) sebagai bahan verifikasi Admin Jasa.
4. **Toggle VA BTN = OFF** (Integrasi API) agar VA diketik manual saat demo.
5. **2 jendela browser** (normal + incognito) untuk berpindah peran cepat; manfaatkan **Auto-Approve Semua Step** untuk meloncati verifikasi berjenjang bila waktu mepet.

---

## 5. Alur Demo End-to-End (±20–25 menit)

### Babak 1 — SUPER ADMIN JASA: Penyiapan ekosistem (5–6 menit)
Login `super.admin.jasa@sikeren.id`.
1. **Master Layanan Jasa** (Kelola Jasa → Layanan Jasa): tunjukkan katalog + tarif (PNBP & konsesi). Tekankan ini sumber tarif & kode akun otomatis.
2. **Kelola Admin Jasa** (Kelola Jasa → Admin Jasa): buka satu Admin Jasa → **tetapkan layanan yang dikelola** (mengontrol apa yang bisa ditagihkan admin itu).
3. **Kelola Mitra Jasa** (Kelola Jasa → Mitra Jasa): buka **PT ABC** → tunjukkan:
   - **Akun login mitra** (buat/reset),
   - **Assign layanan** ke mitra,
   - **Kontrak / dokumen dasar** (unggah & masa berlaku),
   - **Konsesi** (tarif % omzet) & **PJP2U** (tarif per penumpang).
4. **Integrasi API**: tunjukkan toggle **VA BTN** (OFF=manual), WhatsApp, Email.
5. (Opsional) **Nomor Tagihan**: register nomor otomatis.

*Narasi:* *"Semua master data & kewenangan disiapkan di sini — siapa mengelola apa, mitra boleh apa, tarifnya berapa."*

### Babak 2 — MITRA: Pelaporan mandiri (3–4 menit)
Login akun mitra (PT ABC).
1. **Dashboard Mitra** + **Layanan Aktif** (unduh kontrak).
2. **Laporan Konsesi → Input Laporan**: isi **omzet** periode → simpan → **Submit** (status: *draft → diajukan*).
3. (Opsional) **Laporan PAX PJP2U → Input Laporan**: isi **jumlah penumpang** → Submit.

*Narasi:* *"Mitra melapor sendiri secara transparan; BLU tidak lagi menunggu data manual."*

### Babak 3 — ADMIN JASA: Verifikasi laporan & penagihan (5–6 menit)
Login `admin.jasa@sikeren.id`.
1. **Verifikasi Laporan Mitra → Konsesi** (atau PAX PJP2U): buka laporan PT ABC yang **diajukan** → **Verifikasi** (status → *diverifikasi*). Tolak juga bisa didemokan.
2. **Buat Tagihan** dari laporan terverifikasi (atau buat tagihan PNBP fungsi langsung). Detail rincian & contoh nominal realistis: lihat **[§8 runbook penagihan](demo-penagihan-jasa.md)**.
3. **Verifikasi berjenjang** (Koordinator → Kasi → Kasubbag → KPA) — lihat **[Babak 2–4 runbook penagihan](demo-penagihan-jasa.md)**; demokan juga **revisi bertarget** dan **persetujuan final + surat pengantar TTD otomatis**.
4. **Publish**: isi **Nomor VA manual** + nomor WA → preview notifikasi → status **PUBLISHED**.

### Babak 4 — MITRA: Terima & bayar tagihan (2–3 menit)
Login kembali sebagai mitra.
1. **Dashboard Mitra** → buka tagihan yang baru terbit.
2. Unduh **invoice PDF** & **surat pengantar final TTD**; tunjukkan **Nomor VA**.
3. (Simulasi bayar) — lalu kembali ke Admin Jasa untuk **Tandai Lunas**.
4. (Opsional) Scan **QR** di surat pengantar → halaman **verifikasi TTE publik**.

### Babak 5 — ADMIN JASA: Pelunasan (1–2 menit)
Sebagai Admin Jasa → **Tandai Lunas** → status **LUNAS**; sistem otomatis mencatat **piutang (PAID)** + **BKU penerimaan** dan memproses notifikasi struk.

### Babak 6 — SUPER ADMIN JASA: Monitoring & laporan (2–3 menit)
Login Super Admin Jasa → **Pembukuan & Laporan → Laporan Jasa**:
- **Rekap Tagihan**, **Rekap Pembayaran**, **Rekap Piutang**, **Rekap per Layanan**, **Rekap Terima Setor**, **Performa Mitra** (+ **export PDF/Excel**).
Tutup dengan **Dashboard Jasa**.

---

## 6. Modul Utilitas (sub-flow opsional, 2–3 menit)

Alur listrik/air yang juga bermuara ke tagihan jasa:
1. **Admin Listrik / Admin Air** (`admin.listrik@sikeren.id` / `admin.air@sikeren.id`) → **Catat Meter Utilitas** → input stan meter → **Submit**.
2. **Super Admin Jasa / Admin Jasa** → **Verifikasi Laporan → Utilitas** (atau **Laporan Utilitas**) → review → **Buat Tagihan** dari pemakaian.
3. Selanjutnya mengikuti alur penagihan yang sama (verifikasi → publish → lunas).

---

## 7. Catatan Keamanan & Rencana Cadangan

- **WhatsApp**: matikan sebelum demo (lihat [§3.1 runbook penagihan](demo-penagihan-jasa.md)) — agar publish/lunas tidak mengirim WA nyata & tidak menggantung.
- **VA**: toggle **OFF** = manual (dipakai demo). Email `log` & VA BTN `mock` — aman.
- **Loncat verifikasi**: gunakan **Auto-Approve Semua Step** bila salah satu akun verifikator bermasalah.
- **Data ter-stage**: siapkan laporan mitra & tagihan di tiap state agar tidak bergantung input live.
- **PDF tak terbuka**: jalankan `php artisan storage:link`.

---

## 8. Cheat Sheet — Urutan Login

| Urutan | Peran | Aksi inti |
|---|---|---|
| 1 | Super Admin Jasa | Master layanan → akun Admin Jasa → Mitra (akun, layanan, kontrak, konsesi/PJP2U) → Integrasi |
| 2 | Mitra | Input & submit laporan konsesi / PAX |
| 3 | Admin Jasa | Verifikasi laporan → buat tagihan → publish (VA manual) |
| 4 | Koordinator → Kasi → Kasubbag → KPA | Verifikasi berjenjang + TTD (atau Auto-Approve) |
| 5 | Mitra | Lihat tagihan, unduh surat TTD |
| 6 | Admin Jasa | Tandai Lunas |
| 7 | Super Admin Jasa | Laporan & rekap |

---

## 9. Alur Kunci yang Sering Rancu (klarifikasi)

### 9.1 Ada DUA jalur verifikasi yang berbeda
Jangan tertukar — ini dua hal terpisah:

| | **A. Verifikasi Laporan Mitra** | **B. Verifikasi Tagihan (berjenjang)** |
|---|---|---|
| Objek | Laporan omzet konsesi / jumlah penumpang PAX | Dokumen tagihan PNBP |
| Langkah | **1 langkah** | **4 langkah** |
| Pelaku | Admin Jasa, Super Admin Jasa, Koordinator Jasa | Koordinator Jasa → Kepala Seksi → Kasubbag TU → KPA/Kabandara |
| Hasil | Laporan jadi **diverifikasi** (boleh ditolak) | Tagihan **DISETUJUI** + surat pengantar TTD |

**Urutannya:** laporan mitra diverifikasi dulu (jalur A) → baru dibuatkan tagihan → tagihan masuk verifikasi berjenjang (jalur B). Tagihan **PNBP Fungsi** (mis. jasa pelayanan pesawat/kargo) tidak butuh jalur A — Admin Jasa langsung membuatnya.

### 9.2 Cara konkret: dari laporan mitra → tagihan
1. Mitra submit laporan (status **diajukan**).
2. Admin Jasa membuka **Verifikasi Laporan Mitra → Konsesi / PAX PJP2U** → **Verifikasi** (status **diverifikasi**).
3. Pada laporan yang sudah diverifikasi muncul tombol **"Buat Tagihan"** → membuka form tagihan yang **terisi otomatis dari laporan** (membawa `penjualan_id`).
4. Setelah tagihan disimpan, laporan menjadi **ditagihkan** dan tertaut ke tagihannya (tidak bisa dibuat dobel).

> ⚠️ **PENTING — aturan waktu konsesi:**
> - Laporan **KONSESI** bisa **diverifikasi DAN ditagihkan setelah bulan pelaporannya berakhir** (mis. laporan Juni mulai bisa diproses 1 Juli). Kedua gerbang memakai patokan **sama** (akhir bulan pelaporan), tidak lagi bergantung kapan mitra submit. Di bulan yang sama sistem menolak: *"Verifikasi hanya dapat dilakukan setelah bulan pelaporan berakhir."*
> - Laporan **PJP2U (PAX)** harian → **bisa langsung diverifikasi & ditagihkan**.
> - **Saat demo:** untuk konsesi **end-to-end** (submit → verifikasi → tagihan), pakai laporan **bulan yang sudah lewat** (mis. bulan lalu) — kini langsung bisa sampai tagihan di hari yang sama. PJP2U seketika.

### 9.3 Tiga asal-usul tagihan
| Tipe tagihan | Asal data | Dasar nilai |
|---|---|---|
| **PNBP Fungsi** | Diinput langsung Admin Jasa | tarif × volume (mis. pendaratan, parkir, kargo, sewa) |
| **Konsesi** | Dari laporan **omzet** mitra (jalur A) | persentase (%) dari omzet |
| **PJP2U** | Dari laporan **jumlah penumpang (PAX)** mitra | tarif × jumlah penumpang |

---

## 10. Status Lifecycle (referensi)

### 10.1 Laporan Mitra (konsesi / PAX)
```
draft → diajukan → diverifikasi → ditagihkan
                 ↘ ditolak → (mitra perbaiki) → diajukan
```
> Catatan waktu: **konsesi** hanya bisa pindah dari *diajukan → diverifikasi* **setelah bulan pelaporan berakhir**; **PJP2U** bisa langsung (lihat callout di §9.2).

### 10.2 Tagihan Jasa
```
DRAFT → VERIFIKASI_KOORDINATOR → VERIFIKASI_KASI_JASA → VERIFIKASI_KASUBAG_TU
      → VERIFIKASI_KABANDARA → DISETUJUI → PUBLISHED → LUNAS
```
Cabang:
- **REVISI** (verifikator klik *Minta Revisi*, bertarget) → kembali ke pembuat (Admin Jasa) → setelah diperbaiki, **diulang dari Koordinator**.
- **DITOLAK** → workflow ditutup; perbaikan memakai jalur Revisi, bukan Tolak.
- **Hapus** (soft delete) → hanya untuk tagihan **sebelum PUBLISHED** (status PUBLISHED/LUNAS tidak bisa dihapus).

---

## 11. Pembayaran & Pengingat

- **Status pembayaran:** `belum_dibayar` → `sebagian` → `lunas` (mendukung **pembayaran bertahap**).
- **Konfirmasi lunas (dua jalur, satu pipeline):**
  - **Manual** — Admin Jasa klik **Tandai Lunas** (dipakai sekarang).
  - **Otomatis** — callback BTN saat API VA aktif (endpoint sudah siap menunggu).
- **Saat lunas**, sistem otomatis: catat **piutang (PAID)** + **BKU penerimaan**, lalu kirim **struk** via WhatsApp & email.
- **Pengingat jatuh tempo otomatis** — perintah terjadwal `SendDueDateReminderCommand` mengirim notifikasi ke mitra yang **mendekati / lewat** jatuh tempo (lihat menu **Tagihan → Jatuh Tempo** untuk pemantauan manual).
- **Nomor VA** mengikuti toggle Integrasi: **OFF** = diketik manual saat publish; **ON** = dibuat otomatis (API BTN).
