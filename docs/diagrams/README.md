# Use Case Diagram — SIKEREN-BLU

File `.drawio` di folder ini berisi diagram use case untuk seluruh role di SIKEREN-BLU.

## File

- **`use-case-sikeren.drawio`** — diagram multi-page (Draw.io / diagrams.net format).
  Berisi 9 halaman:
  1. **Overview** — peta cluster modul + actor (ringkasan).
  2. **Administrasi** — Super Admin.
  3. **Anggaran & Eksekutif** — KPA, PLT/PLH, Kasubbag TU, Kasi PK.
  4. **Komitmen & Pengadaan** — PPK, Pejabat Pengadaan.
  5. **Pencairan** — Operator BLU, PPSPM, Koordinator Keuangan, PPABP, Operator Perjaldin.
  6. **Bendahara** — Bendahara Pengeluaran, Bendahara Penerimaan.
  7. **Modul Jasa (PNBP)** — Super Admin Jasa, Koordinator Jasa, Admin Jasa, Admin Konsesi.
  8. **Utilitas** — Admin Listrik, Admin Air.
  9. **Mitra Eksternal** — Mitra, Mitra Jasa.

- **`build-usecase-drawio.php`** — generator. Daftar use case per role dan
  konfigurasi halaman ada di array `$useCases` dan `$pages` di file ini.

- **`flow-tagihan-kontrak.drawio`** — diagram alur proses Tagihan Kontrak (BAST/Termin)
  dalam format Draw.io (3 halaman, swimlane horizontal). Ada saja kalau prefer
  format vector di Draw.io.

- **`flow-tagihan-kontrak.md`** — **dokumentasi lengkap berbasis Mermaid** (rekomendasi).
  Berisi 9 blok diagram (phase map, 5 fase detail, state machine, sequence diagram,
  plus tabel referensi & glosarium) yang langsung tampil di GitHub/VS Code dan
  bisa di-paste ke Draw.io via *Arrange → Insert → Advanced → Mermaid*.

- **`flow-tagihan-perjaldin.md`** — **dokumentasi alur Tagihan Perjaldin** (Mermaid).
  Berisi 9 blok diagram (phase map, 6 fase detail termasuk gate Upload Nominatif TTD,
  state machine, sequence diagram), tabel referensi workflow Perjaldin/SPP/SPM/NPI/SP2D,
  dan tabel perbandingan dengan Tagihan Kontrak.

- **`build-flow-tagihan-kontrak.php`** — generator versi `.drawio` (legacy).

## Cara membuka

Pilih salah satu:

- Buka **<https://app.diagrams.net>** → File → Open from Device → pilih file `.drawio`.
- Pasang ekstensi **Draw.io Integration** di VS Code, lalu klik dua kali file `.drawio`.
- Pasang aplikasi desktop **drawio-desktop**.

Tiap halaman bisa diakses lewat tab di bawah canvas.

## Cara update

Setiap kali ada perubahan akses role di `routes/web.php` atau penambahan modul:

1. Edit array `$useCases` (mapping role → daftar use case) di
   `build-usecase-drawio.php`, atau struktur lane / aktivitas di
   `build-flow-tagihan-kontrak.php`.
2. (Opsional) Sesuaikan `$pages` jika ada halaman baru.
3. Regenerate:

   ```bash
   php docs/diagrams/build-usecase-drawio.php
   php docs/diagrams/build-flow-tagihan-kontrak.php
   ```

   File `.drawio` akan ditimpa dengan versi baru.

## Konvensi

- Actor digambar pakai stick figure UML di kolom kiri.
- Use case berbentuk ellipse di dalam boundary "SIKEREN-BLU".
- Garis tanpa panah menyatakan asosiasi `actor → use case` (interaksi).
- Halaman per-modul hanya menampilkan actor & use case yang relevan untuk modul tersebut.
- Halaman Overview menggunakan kelompok cluster (rounded box) per modul,
  tidak menampilkan use case detail demi keterbacaan.
