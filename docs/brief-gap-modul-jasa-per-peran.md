# Brief Gap Modul Layanan Jasa per Peran — & Solusinya

Disusun 2026-06-19. Hasil audit kode (controller/route/view) untuk peran **Super Admin Jasa**, **Admin Jasa**, **Mitra**. Sudah disaring dari hal spekulatif; setiap gap diberi tingkat keyakinan + dampak.

> Bacaan terkait: item yang **sengaja ditunda** ada di memori `modul-jasa-penyempurnaan-ditunda`; yang **sudah selesai** sesi ini ada di `kontrak-wajib-layanan-mitra` & `pjp2u-diskon-temporal`. Brief ini fokus pada gap **baru** yang belum tercakup keduanya.

---

## 0. Konteks — sudah beres / sudah ditunda (jangan diulang)

**Sudah selesai sesi ini:** kontrak wajib (pool layanan = turunan kontrak), denda 2% per 30 hari + macet (aging >180 hari), diskon PJP2U temporal (auto-balik normal) + badge status di riwayat/log, tampilan master layanan jadi pohon, `nilai_kontrak` dihapus.

**Sudah ditunda (keputusan user):** pembayaran sebagian/cicilan, akun pendapatan denda terpisah, nota kredit/koreksi tagihan publish, keringanan (waiver) denda, generate tagihan massal/recurring, SP bertingkat (dunning), rekonsiliasi VA otomatis, integrasi SIMPONI/MPN, statement/rekening koran mitra, dashboard aging + target vs realisasi, komponen PPN/PPh.

Gap di bawah **di luar** dua daftar itu.

---

## 1. Super Admin Jasa
Sudah kuat: dashboard + 6 laporan (rekap tagihan/layanan/terima-setor/pembayaran/piutang/performa mitra) + export, master layanan, CRUD mitra/admin/kontrak/konsesi/PJP2U, integrasi (VA/WA/email), monitoring pelaporan + remind, log tarif PJP2U + export, penomoran tagihan.

| Gap | Keyakinan | Dampak | Solusi ringkas |
|---|---|---|---|
| **Audit trail global** belum ada (hanya `created_by/updated_by` parsial, `LogStatusDokumen` khusus dokumen) | Tinggi | Sulit telusuri siapa ubah tarif/mitra/kontrak & kapan; lemah untuk kepatuhan | Tabel `activity_logs` + observer pada model jasa (old/new value, user, ip); view audit per entitas |
| **Bulk operations & import CSV** tidak ada (mitra/kontrak/penugasan layanan satu-satu) | Tinggi | Setup/maintenance massal lambat & rawan salah | Checkbox + aksi massal (aktif/nonaktif, perpanjang kontrak, assign layanan admin); import CSV mitra/kontrak dgn preview |
| **Notifikasi sistem untuk event penting** (kontrak segera berakhir, laporan overdue, tarif diubah) | Sedang | SA harus cek manual; SLA terlewat | Scheduled job harian + notifikasi in-app/email (kontrak H-30, laporan overdue, perubahan tarif) |
| **Recovery/arsip soft-delete** tanpa UI restore + `deleted_by` | Rendah | Salah hapus sulit dipulihkan | Tab "Arsip" + tombol restore + kolom `deleted_by` |

## 2. Admin Jasa
Sudah kuat: dashboard prioritas, buat/edit/publish tagihan (multiline, PJP2U, konsesi, garbarata/AMC, utilitas), verifikasi bukti bayar (terima/tolak/perbaikan), mark lunas + sync piutang/BKU, jatuh tempo & denda, kualitas piutang, log bulanan + export, verifikasi laporan mitra.

> Catatan akurasi: **reminder sudah ada** (command `SendDueDateReminderCommand`, `SendReportReminderCommand`, plus `MonitoringPelaporanController::remind/remindAll`). Jadi gap-nya bukan "tak ada reminder", melainkan cakupan & penjadwalannya.

| Gap | Keyakinan | Dampak | Solusi ringkas |
|---|---|---|---|
| **Bulk publish / bulk reminder tagihan** | Tinggi | Publish/ingatkan ratusan tagihan satu-satu | Multi-select + `bulk-publish` & `bulk-remind` (queue) |
| **Penjadwalan reminder jatuh tempo terverifikasi aktif** (command ada, perlu dipastikan terjadwal H-3/H-1 + overdue mingguan) | Sedang (perlu verifikasi `routes/console.php` schedule) | Mitra tak diingatkan proaktif → tunggakan naik | Pastikan command dijadwalkan + catat `reminder_sent_at`; tampilkan status terkirim |
| **Dashboard aging piutang untuk Admin Jasa** (kualitas piutang sudah dihitung, belum divisualkan untuk admin) | Sedang | Admin tak lihat piutang yang memburuk | Widget/halaman aging: nominal per kualitas (Lancar/Kurang Lancar/Diragukan/Macet) |
| **KPI penagihan** (collection rate, rata-rata umur piutang, mitra terburuk) | Sedang | Tak ada ukuran efektivitas penagihan | Halaman analitik ringkas per mitra/layanan |
| **Rekap per-mitra (satu berkas: tagihan + bukti + rekonsiliasi)** untuk pemeriksaan | Rendah | Audit per mitra repot | Export PDF per mitra |

(Item *partial payment, nota kredit, waiver, cicilan* → sudah ditunda; pondasinya sebagian ada — lihat memori.)

## 3. Mitra
Sudah kuat: dashboard + countdown jatuh tempo, lihat/unduh nota tagihan & surat pengantar, unggah bukti bayar (nominal terkunci incl. denda), riwayat bukti per tagihan, lapor penjualan konsesi & PJP2U/PAX (draft→ajukan), lihat layanan aktif, ubah password, tautan publik tagihan, notifikasi WA/email.

| Gap | Keyakinan | Dampak | Solusi ringkas |
|---|---|---|---|
| **Kuitansi / bukti lunas (PDF)** tidak digenerate saat lunas | Tinggi | Mitra tak punya bukti resmi pelunasan | Saat bukti DITERIMA → generate Kuitansi PDF + tombol unduh di detail |
| **Riwayat pembayaran terpusat** (semua bukti lintas tagihan + filter) | Tinggi | Cari bukti lama harus buka tiap tagihan | Halaman `riwayat-pembayaran` + filter periode/status + export |
| **Catatan penolakan laporan penjualan terlihat mitra** | Sedang (perlu verifikasi field `catatan` reject) | Mitra tak tahu alasan ditolak → revisi buta | Pastikan catatan verifikator wajib saat tolak & tampil di timeline + notifikasi |
| **Notifikasi in-app** (tagihan terbit, bukti ditolak, laporan diverifikasi) | Sedang | Mitra harus refresh manual | Laravel Notification (database) + bell di portal |
| **Lupa password mandiri** (hanya ganti via password lama; reset hanya oleh admin) | Sedang | Mitra terkunci → bebani admin | Alur "lupa password" via email bertoken |
| **Cari/filter tagihan** di portal | Rendah | Mitra dgn banyak tagihan kesulitan | Search nomor + filter status/periode |

(Item *rekening koran/aging mitra* → sudah masuk daftar ditunda.)

---

## 4. Rekomendasi prioritas (gap baru)

1. **Audit trail global** (SA) — fondasi kepatuhan, dipakai lintas peran.
2. **Kuitansi/bukti lunas PDF** (Mitra) — cepat, dampak langsung dirasakan.
3. **Bulk publish + bulk reminder** (Admin) — efisiensi operasional besar.
4. **Riwayat pembayaran terpusat + catatan penolakan laporan** (Mitra) — transparansi.
5. **Notifikasi in-app** (SA/Admin/Mitra) — sekali bangun, manfaat lintas peran.

Sisanya (dashboard aging/KPI admin, bulk import, lupa password, search) menyusul sesuai kebutuhan.

> Catatan: beberapa gap "keyakinan sedang" perlu verifikasi cepat sebelum dieksekusi (penjadwalan reminder di `routes/console.php`, field catatan penolakan laporan). Tidak ada perubahan kode dibuat oleh brief ini.
