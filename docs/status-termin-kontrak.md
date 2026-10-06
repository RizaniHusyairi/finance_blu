# Status Termin Kontrak dan SPK

Berlaku untuk SPK yang dibuat di sistem dan kontrak eksternal.

| Status | Arti | Perubahan berikutnya |
| --- | --- | --- |
| `LOCKED` | Termin belum boleh ditagih. | Termin sebelumnya `LUNAS` membuka termin ini. |
| `READY_TO_BILL` | Satu tagihan boleh dibuat. | Draft tagihan dibuat. |
| `DRAFT` | Draft sudah terikat ke termin. | Tagihan diajukan. |
| `DALAM_PROSES` | Tagihan diajukan dan menunggu rangkaian pencairan; belum dibayar. | SP2D dieksekusi. Revisi tetap memakai tagihan yang sama. |
| `LUNAS` | SP2D berstatus `EXECUTED`; pembayaran termin selesai. | Termin berikutnya dibuka. |

`total_diajukan` menjumlahkan termin `DALAM_PROSES` dan `LUNAS`.
`total_terserap` / serapan dana hanya menjumlahkan termin `LUNAS`.
Selisih kedua angka adalah nilai tagihan dalam proses.
Jika setoran pajak belum memiliki NTPN, pencatatan BKU dapat menyusul setelah
SP2D dieksekusi; status `LUNAS` tetap berarti pencairan vendor sudah dieksekusi.

Termin yang direvisi tetap terikat pada tagihan semula. Membatalkan tagihan dan
membuka kembali termin membutuhkan alur pembatalan khusus yang menangani relasi
tagihan lama; mengubah status termin saja tidak cukup.

Migrasi `2026_09_27_000001_clarify_kontrak_termin_status.php` memetakan status
lama `SUDAH_DITAGIH` menjadi `DALAM_PROSES`, kecuali tagihan dengan SP2D
`EXECUTED` yang dipetakan menjadi `LUNAS`.
