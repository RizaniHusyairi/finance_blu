# Skenario Sosialisasi Aplikasi — Proses Tagihan
**Kontrak Pengadaan · Perjalanan Dinas (Perjaldin) · Honorarium**

Durasi yang disarankan: **±2 jam** (bisa dipadatkan ke 90 menit dengan memangkas sesi simulasi).

---

## Persiapan Malam Ini (H-1) — Checklist

- [ ] Siapkan **akun demo untuk setiap role**: Pejabat Pengadaan, PPK, PPSPM, Koordinator Keuangan, Bendahara Pengeluaran, Bendahara Penerimaan, Kasubbag Keuangan & TU, Operator BLU, Operator Perjaldin, PPABP, KPA.
- [ ] Siapkan **1 kontrak pengadaan yang sudah berstatus selesai/aktif** sebagai bahan demo tagihan kontrak (manfaatkan `CompletedKontrakPengadaanSeeder` di server demo).
- [ ] Siapkan **1 draft perjaldin** dan **1 draft honorarium** yang tinggal di-submit (supaya demo tidak habis waktu mengisi form).
- [ ] Pastikan **notifikasi WhatsApp aktif** di server demo dan nomor WA demo bisa menerima pesan — fitur magic link TTE adalah momen "wow" presentasi.
- [ ] Siapkan **proyektor + 1 HP** yang terlihat audiens (untuk menunjukkan link WA masuk secara live).
- [ ] Cetak **lembar contekan per role** (lihat bagian "Lembar Per Role" di bawah) — bagikan di awal sesi.
- [ ] Login semua akun demo di tab/browser berbeda sebelum mulai (hemat waktu pindah role saat demo).

---

## Rundown Acara

| Waktu | Sesi | Durasi |
|---|---|---|
| 09.00 | 1. Pembukaan & "Kenapa aplikasi ini ada" | 10 mnt |
| 09.10 | 2. Konsep dasar yang berlaku di semua proses | 15 mnt |
| 09.25 | 3. Demo alur 1: Tagihan Kontrak Pengadaan | 25 mnt |
| 09.50 | 4. Demo alur 2: Perjaldin | 15 mnt |
| 10.05 | 5. Demo alur 3: Honorarium | 15 mnt |
| 10.20 | 6. "Apa tugas SAYA?" — ringkasan per role | 15 mnt |
| 10.35 | 7. Simulasi langsung + Q&A | 25 mnt |
| 11.00 | Penutup & info pendampingan | — |

---

## Sesi 1 — Pembukaan (10 menit)

**Pesan kunci: "Yang berjalan sekarang dokumennya, bukan orangnya."**

Naskah pembuka yang bisa dipakai:

> "Bapak/Ibu, selama ini untuk satu tagihan kontrak, berkas fisik harus diantar dari meja ke meja — PPK, bendahara, kasubbag — dan kalau ada koreksi, berkas kembali dari awal. Mulai sekarang semua proses itu pindah ke aplikasi. Bapak/Ibu cukup buka aplikasi, dokumen yang butuh tindakan Bapak/Ibu sudah menunggu di sana, dan aplikasi akan **mengingatkan lewat WhatsApp** kalau ada yang harus diverifikasi."

Tiga janji yang disampaikan di awal (lalu dibuktikan lewat demo):
1. **Tidak ada berkas hilang** — semua dokumen dan lampiran tersimpan dan terlacak statusnya.
2. **Tidak perlu saling tunggu** — verifikasi berjalan **paralel**, lima verifikator bisa memeriksa bersamaan, tidak antre satu-satu.
3. **Tidak perlu cek aplikasi terus** — notifikasi WhatsApp datang saat giliran Anda tiba.

---

## Sesi 2 — Konsep Dasar (15 menit)

Sampaikan 5 konsep ini **sebelum** demo, supaya saat demo audiens fokus ke alur, bukan bingung istilah.

### 2.1 Role = Tugas Anda di Aplikasi
Setiap orang login dengan akun masing-masing dan hanya melihat menu sesuai perannya. Tidak perlu hafal semua menu — yang muncul di layar Anda adalah yang menjadi tugas Anda.

### 2.2 Siklus hidup dokumen (status)
Gambarkan di papan/slide sebagai garis lurus:

```
DRAFT → DIAJUKAN → VERIFIKASI (paralel) → PERSETUJUAN KASUBBAG → SIAP SPP
      → SPP → SPM → SP2D → PEMBAYARAN → SELESAI (tercatat di pembukuan)
```

Tekankan: **semua orang bisa melihat dokumen sedang di posisi mana** — tidak ada lagi "berkasnya di meja siapa ya?"

### 2.3 Tiga tombol verifikator: Setuju / Revisi / Tolak
- **Setuju** — dokumen lanjut.
- **Minta Revisi** — dokumen kembali ke pembuat **dengan catatan**, diperbaiki, lalu diajukan ulang. Ini yang paling sering dipakai.
- **Tolak** — proses dihentikan. Dipakai hanya jika tagihan memang tidak layak diproses.

### 2.4 Verifikasi paralel + finalisasi
Pola yang sama untuk ketiga jenis tagihan:
- **Tahap 1 (paralel):** PPK, PPSPM, Koordinator Keuangan, Bendahara Pengeluaran, Bendahara Penerimaan — kelimanya bisa memeriksa **kapan saja, tanpa menunggu satu sama lain**. Semua harus setuju.
- **Tahap 2 (finalisasi):** Kasubbag Keuangan & TU memberi persetujuan akhir.

### 2.5 Notifikasi WhatsApp & TTE (tanda tangan elektronik)
- Saat giliran Anda memverifikasi, **WA masuk berisi link langsung** ke dokumennya.
- Vendor/pihak eksternal **tidak perlu punya akun**: mereka menerima link WA untuk menandatangani BAPP/BAST/BAP secara elektronik dari HP.
- Setiap PDF yang dihasilkan aplikasi punya **QR code** — siapa pun bisa memindai untuk memastikan dokumen asli dan melihat riwayatnya.

---

## Sesi 3 — Demo Alur 1: Tagihan Kontrak Pengadaan (25 menit)

**Gunakan cerita, bukan fitur.** Naskah cerita:

> "Vendor CV Maju Jaya baru saja menyelesaikan pekerjaan termin 1 dari kontrak pengadaan renovasi gedung. Sekarang vendor mau menagih. Mari kita ikuti perjalanan tagihannya dari awal sampai uang keluar."

### Babak 1 — PPK membuat tagihan *(login: PPK)*
1. Buka menu Tagihan Kontrak → **Buat Tagihan**, pilih kontrak & termin yang ditagih.
2. Tunjukkan bahwa data kontrak (nilai, vendor, termin) **otomatis terisi dari data kontrak** — tidak ketik ulang.
3. Tunjukkan pilihan **mekanisme pembayaran**: untuk kontrak selalu **LS – Pihak Ketiga** (dana langsung ke rekening vendor).
4. Upload lampiran pendukung ke **arsip dokumen**.

> 💡 Sampaikan: "Mekanisme pembayaran **terkunci setelah tagihan diajukan**. Kalau salah pilih, satu-satunya jalan adalah PPK melakukan aksi revisi — ini disengaja supaya tidak ada yang mengubah cara bayar diam-diam di tengah proses."

### Babak 2 — TTE Berita Acara via WhatsApp *(momen "wow")*
1. Dari halaman tagihan, klik **Kirim TTE** untuk BAPP/BAST/BAP.
2. **Angkat HP demo ke arah audiens** — tunjukkan WA masuk ke "vendor".
3. Buka link dari HP, tanda tangani di layar HP, submit.
4. Refresh halaman aplikasi — status tanda tangan berubah. 

> Naskah: "Perhatikan — vendor tidak datang ke kantor, tidak punya akun aplikasi, cukup HP dan WhatsApp. Berita acara sudah ditandatangani secara sah dan tersimpan."

### Babak 3 — Submit & verifikasi paralel
1. PPK men-submit tagihan.
2. **Pindah tab** ke akun PPSPM → buka menu **Verifikasi Tagihan Kontrak** → tunjukkan daftar antrian → buka detail → periksa lampiran → **Setuju**.
3. Pindah ke akun Bendahara Pengeluaran → **sengaja klik "Minta Revisi"** dengan catatan, misalnya: "Nomor rekening vendor di BAST tidak sesuai".
4. Kembali ke akun PPK → tunjukkan tagihan kembali dengan catatan revisi → perbaiki → submit ulang.
5. Selesaikan persetujuan 5 verifikator (sisanya bisa dipercepat), lalu login Kasubbag untuk **persetujuan final**.

> 💡 Momen revisi ini **sangat penting didemokan** — pertanyaan paling umum user baru adalah "kalau ada yang salah bagaimana?". Tunjukkan bahwa dokumen tidak hilang, hanya kembali dengan catatan.

### Babak 4 — Dari tagihan ke uang keluar (cukup diceritakan + tunjukkan singkat)
1. Setelah disetujui penuh, status tagihan menjadi **siap SPP** → **Operator BLU** menerbitkan SPP.
2. SPP diverifikasi (PPK, Koordinator Keuangan, Kasubbag) → **KPA menyetujui lewat link WhatsApp** (tunjukkan jika sempat — momen "wow" kedua).
3. PPSPM menerbitkan & memverifikasi **SPM** → terbit **SP2D** → **Bendahara Pengeluaran** membayar dan transaksi otomatis tercatat di pembukuan (BKU).
4. Tutup dengan membuka **riwayat/log tagihan**: "Inilah jejak lengkapnya — siapa berbuat apa, jam berapa."

---

## Sesi 4 — Demo Alur 2: Perjaldin (15 menit)

Naskah cerita:

> "Tiga pegawai baru pulang perjalanan dinas ke Jakarta. Sekarang kita proses pertanggungjawaban dan pembayarannya."

Alurnya sama polanya — tekankan **bedanya saja**:

1. **Operator Perjaldin** membuat data perjaldin: pilih pegawai, tujuan, tanggal. Tunjukkan bahwa **uang harian otomatis terhitung dari tabel master tarif** — tidak perlu hitung manual, tidak bisa salah tarif.
2. Cetak **daftar nominatif** (PDF otomatis), upload nominatif yang sudah ditandatangani.
3. Submit → masuk verifikasi **paralel: PPSPM, Bendahara Penerimaan, Bendahara Pengeluaran, PPK, Koordinator Keuangan** → finalisasi **Kasubbag**. ("Pola yang sama dengan kontrak tadi — kalau Bapak/Ibu sudah paham yang pertama, yang ini otomatis paham.")
4. Beda penting: mekanisme pembayaran perjaldin bisa **LS – Pihak Ketiga** (langsung ke rekening masing-masing pegawai) **atau LS – via Bendahara** (dana ke bendahara dulu, lalu dibagikan).
5. Lanjut SPP per komponen → SPM → SP2D, sama seperti kontrak.

---

## Sesi 5 — Demo Alur 3: Honorarium (15 menit)

Naskah cerita:

> "Ada kegiatan dengan narasumber dan panitia yang harus dibayar honornya bulan ini."

1. **PPABP** membuat tagihan honorarium: daftar penerima (pegawai/personel eksternal), besaran honor, **potongan pajak** per penerima.
2. Upload dokumen pendukung (SK kegiatan, daftar hadir) → cetak **rekap & daftar nominatif** otomatis.
3. Submit verifikasi → lagi-lagi **pola yang sama**: 5 verifikator paralel → Kasubbag.
4. Pilihan mekanisme sama dengan perjaldin: langsung ke rekening penerima, atau via Bendahara Pengeluaran untuk dibagikan.

> 💡 Di titik ini audiens harus sudah bisa menebak alurnya sendiri. Itu tanda sosialisasi berhasil — tutup sesi dengan: "Tiga jenis tagihan, satu pola yang sama: **buat → lengkapi dokumen → submit → verifikasi paralel → Kasubbag → SPP → SPM → SP2D → bayar**."

---

## Sesi 6 — "Apa Tugas SAYA?" (15 menit)

Tampilkan slide ini dan bacakan per role (juga dicetak sebagai lembar contekan):

| Role | Tugas di aplikasi | Kapan bertindak |
|---|---|---|
| **Pejabat Pengadaan** | Input & kelola kontrak, addendum, termin | Saat kontrak baru/berubah |
| **PPK** | Buat tagihan kontrak, kirim TTE vendor, verifikasi (tagihan, perjaldin, honorarium, SPP, NPI, SP2D), aksi revisi | Saat ada WA notifikasi |
| **Operator Perjaldin** | Input perjaldin, nominatif, submit | Setelah perjalanan dinas selesai |
| **PPABP** | Input honorarium + dokumen pendukung, submit | Saat ada honor harus dibayar |
| **PPSPM** | Verifikasi tagihan & dokumen, terbitkan/verifikasi SPM | Saat ada WA notifikasi |
| **Koordinator Keuangan** | Verifikasi tagihan/SPP/SPM/SP2D, kelola nomor surat KU | Saat ada WA notifikasi |
| **Bendahara Penerimaan** | Verifikasi tagihan & NPI | Saat ada WA notifikasi |
| **Bendahara Pengeluaran** | Verifikasi tagihan, eksekusi pembayaran, pembukuan/BKU | Verifikasi + setelah SP2D terbit |
| **Kasubbag Keuangan & TU** | Persetujuan **final** semua tagihan & dokumen | Setelah 5 verifikator selesai |
| **Operator BLU** | Terbitkan SPP, kelola dokumen pencairan | Setelah tagihan disetujui penuh |
| **KPA / PLT** | Persetujuan SPP — cukup lewat link WhatsApp | Saat ada WA notifikasi |

---

## Sesi 7 — Simulasi & Q&A (25 menit)

- Minta **satu user betulan per role** maju memakai akun demo mereka dan menjalankan satu siklus perjaldin kecil bersama-sama (perjaldin paling cepat untuk simulasi).
- Anda memandu dari depan; biarkan mereka yang klik.
- Tutup dengan info **masa pendampingan** (siapa yang dihubungi kalau bingung, grup WA support, dsb).

### Antisipasi pertanyaan (siapkan jawaban)

1. **"Kalau saya salah input dan sudah terlanjur submit?"** → Verifikator mana pun bisa minta revisi; dokumen kembali ke Anda dengan catatan, perbaiki, submit ulang. Tidak ada yang hilang.
2. **"Kalau verifikator sedang dinas luar?"** → Bisa buka aplikasi dari mana saja (cukup browser); KPA bahkan bisa menyetujui SPP langsung dari WhatsApp.
3. **"Apakah berkas fisik masih perlu?"** → Jelaskan kebijakan kantor Anda (mis. arsip fisik tetap disimpan unit, tapi proses & persetujuan resmi lewat aplikasi).
4. **"Vendor saya gaptek, bagaimana TTE-nya?"** → Vendor hanya butuh WhatsApp; klik link, tanda tangan di layar HP, selesai. Tidak perlu install apa pun.
5. **"Bagaimana saya tahu giliran saya?"** → Notifikasi WhatsApp otomatis + daftar antrian di menu verifikasi masing-masing.
6. **"Salah pilih mekanisme pembayaran?"** → Terkunci setelah submit; minta PPK melakukan aksi revisi supaya kembali ke draft.

---

## Tips Penyampaian

1. **Cerita dulu, fitur belakangan.** Orang ingat "kisah tagihan CV Maju Jaya", bukan nama menu.
2. **Satu pola, tiga kali.** Sengaja tunjukkan bahwa ketiga alur identik polanya — ini menurunkan rasa "aplikasinya rumit".
3. **Demokan kegagalan.** Skenario revisi lebih meyakinkan daripada skenario mulus, karena ketakutan terbesar user adalah berbuat salah.
4. **Dua momen "wow" jangan dilewatkan:** TTE vendor via WA, dan approval KPA via WA.
5. **Jangan tunjukkan menu admin/master data** ke user umum — itu untuk sesi terpisah dengan admin.
6. Kalau ada error saat demo: tetap tenang, catat, lanjutkan dengan tab akun lain — karena itu siapkan semua tab login dari awal.
