# Tes lokal BTN melalui Postman

Helper `tools/btn-local.php` menyediakan database SQLite terpisah, tagihan uji Rp100.000, key RSA khusus pengujian, dan generator request bertanda tangan. Helper tidak membaca `.env` utama; koneksi HTTP keluar melalui Laravel diblokir. Konfigurasi dan tagihan pada aplikasi utama tidak perlu diubah.

## 1. Buka PowerShell di folder project

```powershell
Set-Location C:\laragon\www\finance_blu
php -v
```

Gunakan PHP Laragon minimal 8.2 dengan ekstensi OpenSSL dan SQLite. Jika `php` tidak dikenali, buka terminal Laragon yang telah menyediakan PHP pada PATH.

## 2. Siapkan database dan tagihan baru

```powershell
php tools/btn-local.php init
php tools/btn-local.php new
```

`init` menyiapkan database di `storage/app/btn-local/database.sqlite`. `new` membuat tagihan uji baru dan menjadikannya target pengujian. Data percobaan sebelumnya tetap ada.

Hasil awal yang diharapkan:

```json
{
  "status": "PUBLISHED",
  "jumlah_dibayar": "0.00",
  "sisa_tagihan": "100000.00",
  "jumlah_transaksi_tagihan": 0,
  "jumlah_bku_tagihan": 0
}
```

Nomor tagihan dan VA ditentukan otomatis, sehingga tidak perlu mengetik nomor contoh secara manual.

## 3. Jalankan server pengujian

Pada terminal pertama:

```powershell
php -S 127.0.0.1:8091 tools/btn-local.php
```

Biarkan terminal tersebut terbuka. Server ini hanya melayani dua endpoint SNAP lokal. Membuka alamat root `/` di browser akan menghasilkan 404 dan itu normal.

Buka terminal PowerShell kedua dan masuk ke folder project yang sama. Jalankan perintah-perintah berikutnya pada terminal kedua. Gunakan Postman desktop agar dapat mengakses komputer lokal secara langsung.

## 4. Tes inquiry tagihan

```powershell
php tools/btn-local.php request inquiry | Set-Clipboard
```

Pada Postman, pilih **Import**, tempel isi clipboard berupa perintah cURL, lalu impor sebagai request. Klik **Send**. Header, body, URL, dan signature sudah terisi otomatis.

Hasil yang diharapkan: HTTP **200**, `responseCode` **2002400**, dan `virtualAccountData.totalAmount.value` **100000.00**.

## 5. Tes pembayaran

```powershell
php tools/btn-local.php request payment | Set-Clipboard
```

Impor kembali isi clipboard sebagai request baru di Postman, lalu klik **Send**. Hasil yang diharapkan: HTTP **200**, `responseCode` **2002500**, dan `paymentFlagStatus` **00**.

Periksa hasil database dengan:

```powershell
php tools/btn-local.php state
```

Hasil yang diharapkan:

```json
{
  "status": "LUNAS",
  "jumlah_dibayar": "100000.00",
  "sisa_tagihan": "0.00",
  "jumlah_transaksi_tagihan": 1,
  "jumlah_bku_tagihan": 1
}
```

Hasil ini diperiksa melalui helper, bukan halaman tagihan aplikasi utama, karena databasenya berbeda.

## 6. Tes callback duplikat

Pada request payment yang sama, klik **Send** lagi, lalu jalankan `state`.

Respons tetap **2002500**. Jumlah transaksi dan BKU untuk tagihan tetap **1**, nominal tetap Rp100.000. Jika signature sudah kedaluwarsa, jalankan generator `request payment` lagi dan impor hasilnya; identitas pembayaran tetap sama sehingga ini tetap merupakan pengujian duplikat.

## 7. Tes signature salah

```powershell
php tools/btn-local.php request invalid | Set-Clipboard
```

Impor ke Postman dan klik **Send**. Hasil yang diharapkan: HTTP **401**, `responseCode` **4012500**, `responseMessage` **Unauthorized Signature**. Jalankan `state` untuk memastikan tidak ada tambahan pembayaran.

## 8. Ulangi atau hentikan pengujian

Untuk kembali ke tagihan yang belum dibayar:

```powershell
php tools/btn-local.php new
```

Kemudian buat request inquiry/payment yang baru. Jangan memakai request lama karena masih menunjuk tagihan sebelumnya.

Untuk menghentikan server, tekan **Ctrl+C** pada terminal pertama.

## Catatan penggunaan

- Kirim request dalam **dua menit** setelah dibuat. Jika lewat, buat request baru melalui helper dan impor ulang; jangan hanya mengubah timestamp karena signature juga harus berubah.
- Jangan mengedit body atau header request valid untuk langkah dasar ini. Perubahan body memerlukan signature baru.
- Mode `sandbox` di database helper hanya memenuhi konfigurasi handler SNAP. Semua pengujian di atas menuju `127.0.0.1`, tanpa akses sandbox BTN dan tanpa transaksi bank sungguhan.
- Data uji, key, dan log berada di `storage/app/btn-local/`, terpisah dari data utama dan dikecualikan dari Git. Key tersebut bukan key onboarding BTN.
- Jika muncul `Could not send request`/connection refused, pastikan server masih berjalan di terminal pertama dan URL memakai `127.0.0.1:8091`.
- Jika port 8091 sedang dipakai, hentikan server pengujian sebelumnya. Jangan mengganti port tanpa menyesuaikan helper karena generator menggunakan port 8091.
- Jika `state` menunjukkan tagihan sudah lunas sebelum mulai, jalankan `new` dan buat ulang request.

Skenario lebih luas, termasuk pembayaran parsial, timeout, dan kegagalan pembukuan, tersedia melalui tes otomatis:

```powershell
php artisan test --filter=BtnSnapTest
```

Panduan impor cURL resmi: https://learning.postman.com/docs/getting-started/importing-and-exporting/importing-data/
