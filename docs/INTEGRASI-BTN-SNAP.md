# Integrasi BTN SNAP Virtual Account v2.04

Implementasi berdasarkan dokumen BTN revisi 21 Oktober 2025. Kode siap diuji dengan sandbox setelah BTN memberikan akses. Belum merupakan sertifikasi atau hasil UAT BTN.

## Cakupan

- Publish tagihan memanggil Create VA sungguhan pada mode `sandbox`/`production`.
- Token B2B menggunakan RSA SHA-256; request layanan menggunakan HMAC-SHA512 dan bearer token. Token disimpan terenkripsi dalam cache, dengan margin kedaluwarsa 60 detik.
- Header timestamp dikirim dalam WIB (`+07:00`), terlepas dari timezone aplikasi/server.
- Endpoint inquiry dan payment menerima signature RSA tanpa token sesuai skenario pada bagian D.2 dokumen. **Inbound default nonaktif**, sampai BTN mengonfirmasi skenario tersebut dan menyediakan public key serta identitas pengirim.
- Inquiry, update, delete, report, status, serta pemulihan create/update tersedia melalui command `btn:va`.
- Pembayaran diverifikasi terhadap VA, institusi, customerNo, trxId, mata uang, nominal dan waktu pembayaran. Nominal SNAP diproses dalam satuan sen menggunakan integer.
- Pembayaran dan sinkronisasi piutang/BKU dilakukan dalam satu transaksi database. Jika pembukuan gagal, callback mendapat kode 500 dan perubahan dibatalkan sehingga BTN dapat mencoba ulang.
- `paymentRequestId` bersama institusi menjadi identitas pembayaran. Retry pembayaran penuh maupun parsial mengembalikan respons tersimpan; payload penting yang berubah dengan identitas sama ditolak. Kunci baris tagihan menserialkan callback untuk tagihan sama.
- Callback secret lama hanya berlaku pada mode mock dan tidak dapat mengubah tagihan yang sudah memiliki metadata SNAP.

## Pemasangan

Migrasi ini hanya menambahkan dua kolom JSON nullable, tanpa mengubah data pembayaran lama:

```powershell
php artisan migrate --path=database/migrations/2026_09_07_000001_add_btn_snap_metadata.php
```

Isi menu **Integrasi API** sebagai Super Admin. Aktifkan BTN untuk VA otomatis; gunakan `mock` hanya untuk simulasi. Saat nonaktif, alur VA manual tetap digunakan.

| Pengaturan | Sumber/nilai |
|---|---|
| Base URL | Sandbox di dokumen: `https://devapi.btn.co.id`; production wajib dari BTN. Tanpa `/snap` atau path lain. |
| OAuth ID / X-CLIENT-KEY | OAuth ID dari BTN, berbeda dari API Key ID |
| API Key ID / X-PARTNER-ID | API Key ID dari BTN |
| API Key Secret | Apikey Secret dari BTN |
| Private key aplikasi | PEM RSA minimal 2048 bit; private key disimpan terenkripsi, public key pasangannya didaftarkan ke BTN |
| Kode institusi | Lima digit kode VA, dikirim sebagai `partnerServiceId` sepanjang 8 karakter dengan spasi di kiri |
| CHANNEL-ID | Lima digit dari BTN |
| Origin | Domain yang didaftarkan ke BTN |
| Giro tujuan | Opsional, maksimal 16 digit, sesuai penetapan BTN |
| Kode transaksi VA | Default `F` (Full); kode parsial harus dikonfirmasi BTN |
| Aktif (hari) | Masa berlaku VA, terpisah dari aturan jatuh tempo/denda tagihan |
| Autentikasi masuk | `disabled` atau `rsa` setelah disepakati BTN |
| X-PARTNER-ID pengirim BTN | Identitas yang BTN kirim ke aplikasi; jangan diasumsikan sama dengan API Key ID aplikasi |
| Public key BTN | PEM RSA yang diterima melalui onboarding BTN, digunakan memverifikasi panggilan masuk |

Merchant lama dan prefix mock tidak digunakan untuk membentuk identitas SNAP. Private key tidak ditampilkan kembali pada form, tidak dimasukkan ke audit request, dan tidak disimpan sebagai old input ketika validasi gagal. Pertahankan `APP_KEY` karena kredensial dan cache token bergantung pada enkripsi Laravel.

Nomor pelanggan yang diusulkan implementasi: ID tagihan dipad nol menjadi 14 digit. VA = kode institusi 5 digit + customerNo, total 19 digit. `trxId` = `BTN` + ID tagihan dipad nol menjadi 16 digit. **BTN perlu menyetujui aturan ini saat UAT.** VA lama/manual/mock tidak otomatis diklaim sebagai VA SNAP.

## Pemeriksaan dan operasi

```powershell
# Pemeriksaan lokal, tidak menghubungi BTN.
php artisan btn:va check

# Meminta token; token tidak dicetak ke terminal.
php artisan btn:va token

# Pemeriksaan VA dan status transaksi; ID berikut hanya contoh.
php artisan btn:va inquiry --tagihan=123
php artisan btn:va status --tagihan=123 --inquiry-id=INQ123 --payment-id=PAY123
php artisan btn:va report --start=2026-09-01 --end=2026-09-07

# Pemulihan create/update setelah timeout: inquiry dan cocokkan respons BTN.
php artisan btn:va recover --tagihan=123

# Update nominal berjalan termasuk denda dan perpanjang masa berlaku.
# Update/delete dibatasi pada VA aktif yang belum menerima pembayaran.
php artisan btn:va update --tagihan=123 --execute
php artisan btn:va delete --tagihan=123 --execute
```

`inquiry`, `status`, dan `report` hanya membaca bank. Laporan dapat memuat data mitra; batasi akses terminal/outputnya. Tidak ada polling atau scheduler bank yang diaktifkan otomatis.

Create menyimpan identitas permintaan sebelum menghubungi bank. Setelah timeout, percobaan publish berikutnya melakukan inquiry dengan identitas sama, bukan langsung membuat VA lagi. Respons bank harus cocok pada VA/customer/institusi/trxId, nominal, jenis transaksi dan expiry sebelum VA dianggap aktif.

Penolakan eksplisit 400/401/403/404 memungkinkan percobaan ulang setelah koreksi. Timeout, 409, 500, atau respons yang tidak cocok tetap dianggap belum pasti. `recover` untuk create/update hanya mengaktifkan snapshot setelah inquiry cocok. Delete yang timeout, inquiry tidak menemukan VA setelah create timeout, atau update yang tetap menunjukkan data lama memerlukan rekonsiliasi dengan BTN; jangan menghapus metadata untuk memaksa create ulang. Operation pending menolak pembayaran baru sampai kepastian diperoleh.

## Endpoint masuk

Daftarkan domain HTTPS aplikasi dengan path berikut ke BTN:

- `POST /snap/v1/transfer-va/inquiry`
- `POST /snap/v1/transfer-va/payment`

Format header: `Content-Type: application/json`, `X-TIMESTAMP`, `X-PARTNER-ID`, `X-EXTERNAL-ID`, `CHANNEL-ID`, `X-SIGNATURE`. Timestamp header WIB wajib berada dalam toleransi 120 detik. Server harus sinkron waktunya.

Untuk opsi `rsa`, signature merupakan Base64 RSA SHA-256 atas:

```text
HTTPMethod:EndpointUrl:lowercase(hex(SHA256(minify(raw JSON body)))):X-TIMESTAMP
```

Whitespace di luar string JSON dihapus; angka, escape, spasi dalam string, dan urutan properti dipertahankan. Body dibaca langsung dari request mentah, sehingga middleware trim Laravel tidak mengubah bahan verifikasi signature. Mode HMAC inbound dengan penerbitan token aplikasi belum diaktifkan karena kontrak autentikasi arah BTN ke aplikasi masih perlu dikonfirmasi.

Respons sukses inquiry memakai `2002400`; payment memakai `2002500`, beserta `virtualAccountData`. Untuk payment berbasis Create VA, implementasi memerlukan `trxId`, `paymentRequestId`, `paidAmount` dan `totalAmount` dalam IDR, serta `trxDateTime`. Nilai uang berupa string dua angka desimal. `referenceNo` opsional. Pembayaran sebelum expiry yang terlambat dikirim masih dapat diterima, asalkan signature request saat pengiriman valid.

## Batas operasional yang harus dituntaskan saat UAT

1. **Autentikasi inbound:** konfirmasi RSA tanpa token atau kontrak tambahan jika BTN mensyaratkan token/HMAC. Konfirmasi public key, partner ID pengirim, Origin, pendaftaran IP/domain, dan TLS.
2. **Perbedaan dokumen:** panjang VA/customerNo/reference berbeda antarbagian; contoh kode Full `F` dan contoh inquiry `1`; service Status tertulis 25 sedangkan contoh respons 26. Implementasi Status menggunakan `2002600`. Minta fixture resmi dari BTN.
3. **Pembayaran parsial:** kode parsial harus ditetapkan BTN; implementasi menganggap selain `F` sebagai parsial setelah dikonfigurasi. `totalAmount` callback diasumsikan sisa nominal snapshot bank saat pembayaran. Konfirmasi apakah BTN memakai sisa atau nilai tagihan awal. Update/delete setelah pembayaran sebagian belum diizinkan sampai semantiknya jelas.
4. **Denda:** inquiry memakai nominal terakhir yang sudah dikonfirmasi bank. Denda lokal tidak otomatis mengubah VA bank. Jalankan update sebelum pembayaran bila belum ada cicilan. Jika VA nominal lama dibayar saat total lokal sudah bertambah, pembayaran dicatat sebagai parsial dan sisa denda tetap ada; penyelesaiannya memerlukan rekonsiliasi. Pembayaran terlambat yang melintasi batas periode denda mengikuti perhitungan lokal yang ada, sehingga perlu aturan tanggal efektif yang disepakati.
5. **Pembukuan parsial:** mengikuti alur project: piutang menerima total akumulasi; BKU dibentuk ketika lunas dengan total akumulasi, bukan hanya cicilan terakhir. Posting kas per cicilan belum merupakan perilaku layanan pembukuan saat ini.
6. **Retry dan reversal:** konfirmasi kestabilan paymentRequestId/referenceNo, aturan flagAdvise, timeout, retry schedule, transaksi dobel, pembayaran lebih, pembatalan setelah pembayaran, dan reversal. Reversal belum diproses otomatis.
7. **Rekonsiliasi:** Report VA tidak menyertakan ID unik setiap pembayaran dalam contoh v2.04. Laporan hanya dibaca, tidak dikreditkan otomatis agar callback dan report tidak mencatat penerimaan dua kali. Status juga tidak otomatis mengubah pembukuan.
8. **Rekening koran:** API ini tidak menggantikan impor CMS rekening koran debit/kredit seluruh rekening. Minta spesifikasi saldo/mutasi terpisah jika diperlukan.
9. **Lingkungan:** setiap VA terikat fingerprint mode, base URL, partner ID, dan institusi. Jangan mengganti sandbox menjadi production pada tagihan yang sama. Gunakan database/cache bersama untuk seluruh worker produksi agar lock dan idempotensi berfungsi lintas worker.
10. **Notifikasi:** dikirim setelah commit; kegagalan WA/email tidak membatalkan pembayaran dan retry callback tidak mengirim ulang struk. Periksa log dan mekanisme pengiriman ulang notifikasi jika gagal.

## Pengujian lokal

```powershell
php artisan test --compact --filter=BtnSnapTest
```

Pengujian memakai SQLite in-memory, key RSA sementara, dan HTTP fake yang menolak koneksi eksternal. Mencakup tanda tangan dua arah, token cache, payload create, timeout/inquiry, respons salah, pembayaran penuh dan parsial, replay, penolakan signature/nominal/mata uang, rollback pembukuan, expiry, denda, update/delete, laporan tanpa kredit otomatis, dan kerahasiaan pengaturan. Key test tidak digunakan untuk onboarding BTN.
