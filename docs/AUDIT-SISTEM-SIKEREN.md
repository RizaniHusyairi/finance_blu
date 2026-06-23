# Laporan Audit Sistem Informasi — SIKEREN

> **Sistem:** SIKEREN — Aplikasi Pengelolaan Keuangan BLU/PNBP Bandara (Laravel 11, MySQL/MariaDB, Blade, Bootstrap, Spatie Permission, Workflow Approval, dompdf, QR/TTE, integrasi WhatsApp/Email/BTN Virtual Account).
> **Lingkup asumsi:** Sistem dianggap **sudah berjalan di produksi** untuk operasional keuangan bandara (penganggaran, kontrak, tagihan, pencairan, pembukuan BLU, portal mitra, TTE/QR, pembayaran).
> **Pendekatan:** Audit **berbasis risiko**, **diverifikasi terhadap kode sumber nyata** (bukan hanya dokumen arsitektur). Setiap temuan ditandai *Terverifikasi* (dikonfirmasi langsung di kode) atau *Perlu Verifikasi Kode*.
> **Metode:** 10 dimensi audit paralel membaca controller/route/middleware/service/migration/view, lalu setiap temuan diverifikasi-ulang secara adversarial terhadap kode. Total **68 temuan terverifikasi** dari 719 pembacaan kode.
> **Tanggal audit:** 22 Juni 2026.

---

## A. Executive Summary

Sistem keuangan SIKEREN memiliki **fondasi rekayasa yang baik** pada beberapa area — RBAC Spatie diterapkan rapi, posting BKU bersifat transaksional dengan *lock* + idempotency, kolom uang konsisten `decimal(18,2)`, dan engine workflow mencatat approver/timestamp/IP — **namun BELUM layak dioperasikan untuk transaksi keuangan riil** karena terdapat rangkaian kerentanan **Kritis yang saling memperkuat pada jantung kontrol persetujuan dan pembayaran**.

Empat masalah paling berbahaya:

1. **Persetujuan KPA dapat dieksekusi oleh user terotentikasi APA PUN** (SEC-02, WF-02). Endpoint `POST /p/kpa-approval/tagihan/{id}` hanya bermiddleware `[web, auth]` tanpa cek role; `processApproval()` tidak pernah memverifikasi `hasRole('KPA')`. Operator, bahkan Mitra, yang login dapat menyetujui/menolak pencairan tagihan negara.
2. **Tidak ada Segregation of Duties (maker = checker)** (WF-01). Engine workflow tidak pernah membandingkan approver dengan pembuat dokumen (`created_by`); satu user yang merangkap role dapat membuat tagihan lalu menyetujui dokumennya sendiri sampai `READY_FOR_SPP`.
3. **Endpoint `auto-approve "untuk testing"` aktif di produksi** (ARCH-01). Satu `POST` mengubah seluruh approval `PENDING/WAITING` menjadi `APPROVED` dan menerbitkan Surat Pengantar Final ber-TTE — mem-*bypass* seluruh verifikasi berjenjang.
4. **Callback pembayaran BTN dapat diproses tanpa autentikasi** pada kondisi default (INT-01) dan **tanpa idempotency** (INT-02). Pihak anonim dapat menandai tagihan PNBP `LUNAS` tanpa uang masuk, atau memicu pencatatan penerimaan ganda.

Diperparah oleh konfigurasi produksi tidak aman (`APP_DEBUG=true`, `APP_ENV=local`, cookie sesi tanpa `Secure` — SEC-03/BE-01), **arsip dokumen keuangan sensitif disimpan di disk publik** tanpa autentikasi (INF-01), **IDOR pada dokumen pencairan SPP/SPM/NPI/SP2D** (SEC-07), **PDF ber-TTE di-render ulang dari DB** sehingga isi dokumen bisa berubah setelah ditandatangani (TTE-01), dan **tidak adanya strategi backup/restore database sama sekali** (BR-01).

**Nilai akhir tertimbang: 48,85 / 100 → Grade E.** Rekomendasi tegas: **hentikan penggunaan untuk transaksi riil** sampai 10 temuan Kritis ditutup — terutama empat di atas — dan konfigurasi produksi di-*hardening*.

### Ringkasan Angka

| Metrik | Nilai |
|---|---|
| Total temuan terverifikasi | **68** |
| Kritis | **10** |
| Mayor | **28** |
| Minor | **30** |
| Status verifikasi | 68 Terverifikasi / 0 Perlu Verifikasi Kode |
| Nilai akhir tertimbang | **48,85** |
| Grade | **E** (< 60) |

---

## B. Temuan Audit (Ikhtisar)

### B.1 Skor per Kategori (sesuai bobot rubrik)

| Kategori | Bobot | Skor (0–100) | Kontribusi | Kondisi |
|---|---|---|---|---|
| Keamanan | 25% | 38 | 9,50 | Kritis — broken access control pada gate persetujuan keuangan |
| Infrastruktur | 15% | 48 | 7,20 | Lemah — konfigurasi produksi tidak aman, arsip sensitif publik |
| Database | 15% | 55 | 8,25 | Cukup — fondasi kuat, tapi unique constraint keuangan di-drop |
| Backend | 15% | 40 | 6,00 | Lemah — SoD nihil, bypass produksi, exception bocor |
| Frontend | 10% | 70 | 7,00 | Memadai — sedikit XSS, akar masalah di routing/backend |
| Monitoring Jaringan | 10% | 58 | 5,80 | Sedang — tidak ada error tracking/alerting |
| Backup & Recovery | 5% | 60 | 3,00 | Sedang — tidak ada tooling backup, tapi data belum hilang |
| Dokumentasi & Kepatuhan | 5% | 42 | 2,10 | Lemah — internal control & SoD rusak (risiko temuan BPK) |
| **Nilai Akhir** | **100%** | — | **48,85** | **Grade E** |

### B.2 Sebaran Temuan per Dimensi

| Dimensi audit | Skor dimensi | Kritis | Mayor | Minor |
|---|---|---|---|---|
| Arsitektur Sistem | 48 | 1 | 3 | 4 |
| Keamanan & Hak Akses | 58 | 1 | 6 | 0 |
| Workflow Approval | 48 | 2 | 3 | 2 |
| Integritas Data Keuangan | 62 | 0 | 4 | 3 |
| Dokumen, Penomoran & TTE | 52 | 1 | 2 | 4 |
| Integrasi Eksternal | 28 | 2 | 4 | 2 |
| Database | 74 | 0 | 2 | 6 |
| Backend | 58 | 1 | 1 | 5 |
| Frontend | 74 | 0 | 0 | 2 |
| Infrastruktur/Monitoring/Backup/Dokumentasi | 52 | 2 | 2 | 2 |

### B.3 Daftar Temuan Kritis (ringkas)

| ID | Judul | Modul/Area |
|---|---|---|
| SEC-02 | Route `kpa.approval.process` hanya `[web,auth]` — setiap user login bisa eksekusi keputusan KPA | Keamanan / KPA Approval |
| WF-01 | Tidak ada pencegahan maker=checker — pembuat bisa menyetujui dokumennya sendiri | Workflow Approval |
| WF-02 | Approval KPA via WhatsApp di luar engine workflow, otorisasi lemah | Workflow / KPA |
| ARCH-01 | Endpoint `auto-approve "untuk testing"` aktif di produksi | Arsitektur / Tagihan Jasa |
| INT-01 | Callback BTN dapat diproses tanpa autentikasi (secret default kosong), CSRF dimatikan | Integrasi / BTN VA |
| INT-02 | Tidak ada idempotency — retry/replay → double credit | Integrasi / BTN VA |
| BE-01 | Produksi `APP_DEBUG=true` & `APP_ENV=local` + `withExceptions` kosong | Backend / Konfigurasi |
| INF-01 | Arsip dokumen keuangan sensitif disimpan di disk `public` | Infrastruktur / Storage |
| TTE-01 | PDF dokumen TTE di-render ulang dari DB, bukan artefak final yang dibekukan | Dokumen / TTE |
| BR-01 | Tidak ada strategi/tooling backup database & restore sama sekali | Backup & Recovery |

---

## C. Temuan Kritis (detail lengkap)

> Format tiap temuan: ID • Lokasi • Modul/Area • Risiko • Dampak • Kemungkinan • Tingkat Risiko • Bukti/Indikasi • Cara Perbaikan • Contoh Konfigurasi Benar • Prioritas • Status Verifikasi.

### SEC-02 — Route `kpa.approval.process` hanya bermiddleware `[web,auth]`
- **Lokasi:** `routes/web.php:186-188`; `app/Http/Controllers/KpaApprovalController.php:163-209` (`processApproval`)
- **Modul/Area:** Keamanan & Hak Akses / KPA Approval / Eksekusi Persetujuan Tagihan
- **Risiko:** `POST /p/kpa-approval/tagihan/{tagihanId}` terdaftar `->middleware(['web','auth'])` **tanpa** `role:` maupun pengecekan role di dalam `processApproval()`. Method hanya memvalidasi `action`, status `PENDING_KPA`, dan `isTagihanFullyApproved`, lalu menulis `kpa_approved_by => Auth::id()` dan mengubah status `APPROVED/REJECTED`.
- **Dampak:** Bypass kontrol otorisasi KPA — pihak non-KPA (Operator/Mitra) dapat menyetujui pencairan tagihan, memicu `maybeGenerateDraftChain` (pembuatan SPP/SPM) dan merusak audit trail (`kpa_approved_by` terisi user salah).
- **Kemungkinan:** Sedang (butuh sesi login + tahu `tagihanId` ber-status `PENDING_KPA`).
- **Tingkat Risiko:** **Kritis**
- **Bukti/Indikasi:** Method saudara `showApproval()` (baris 124) JUSTRU memanggil `hasAnyRole(['KPA','PLT/PLH','Super Admin'])`, tetapi `processApproval()` yang mengubah keputusan keuangan tidak. POST juga tidak memerlukan `signed`.
- **Cara Perbaikan:** Tambahkan otorisasi role eksplisit di route dan di awal method; idealnya gunakan Policy `can('approve', $tagihan)`.
- **Contoh Konfigurasi Benar:**
```php
Route::post('/p/kpa-approval/tagihan/{tagihanId}', [KpaApprovalController::class,'processApproval'])
    ->middleware(['web','auth','role:KPA|PLT/PLH|Super Admin']);
// di controller:
abort_unless(Auth::user()->hasAnyRole(['KPA','PLT/PLH']), 403);
abort_unless($tagihan->isKpaApprover(Auth::user()), 403);
```
- **Prioritas:** Critical
- **Status Verifikasi:** **Terverifikasi**

### WF-01 — Tidak ada pencegahan maker=checker (Segregation of Duties)
- **Lokasi:** `app/Services/PerjaldinWorkflowService.php:119-193,515-524`; `app/Services/WorkflowService.php:96-154`
- **Modul/Area:** Workflow Approval Engine (Perjaldin/Kontrak/Honorarium/Tagihan Jasa)
- **Risiko:** `actorCanAct()` hanya memverifikasi `actor == assigned_user_id` ATAU `actor->hasRole(roleName)`; **tidak pernah** membandingkan actor dengan `Tagihan.created_by`. Assignee tiap step diambil langsung dari kolom `ppk_user_id/ppspm_user_id/kasubbag_user_id` tanpa pengecualian pembuat.
- **Dampak:** Pencairan dana BLU/PNBP dapat disetujui tanpa checker independen. Pembuat tagihan fiktif bisa meloloskan tagihannya sendiri hingga `READY_FOR_SPP` dengan satu identitas — pelanggaran internal control fundamental.
- **Kemungkinan:** Sedang (butuh user yang sama menjadi pembuat sekaligus penerima penugasan approver/merangkap role).
- **Tingkat Risiko:** **Kritis**
- **Bukti/Indikasi:** `grep created_by|maker` pada seluruh `*Workflow*.php` = tidak ada satu pun untuk SoD. `Tagihan::creator()` ada tetapi tidak pernah dipakai di gate workflow.
- **Cara Perbaikan:** Guard SoD di awal `approve()`/`approveCurrentStep()`; rule konfigurabel per step `allow_self_approval=false`; saat resolve assignee jangan menugaskan `created_by`.
- **Contoh Konfigurasi Benar:**
```php
$maker = $tagihan->created_by;
if ($maker && (int)$maker === (int)$actor->id) {
    throw new \RuntimeException('Pembuat dokumen tidak boleh menjadi penyetuju (maker ≠ checker).');
}
if ($resolved && (int)$resolved === (int)$tagihan->created_by) { $resolved = null; }
```
- **Prioritas:** Critical
- **Status Verifikasi:** **Terverifikasi**

### WF-02 — Approval KPA via WhatsApp berjalan di luar engine workflow, otorisasi lemah
- **Lokasi:** `app/Http/Controllers/KpaApprovalController.php:122-210`; `routes/web.php:183-188`
- **Modul/Area:** Workflow Approval / KPA Approval via WhatsApp (jalur paralel di luar `WorkflowService`)
- **Risiko:** Persetujuan KPA disimpan di kolom `kpa_approval_status/kpa_approved_by`, terpisah dari `WorkflowApproval`. (a) Route `show` hanya `['web']` (tanpa `signed`); (b) `showApproval` melakukan `Auth::loginUsingId($user->id)` berdasarkan `user_id` dari query string — siapa pun pemegang link valid 24 jam login penuh sebagai KPA; (c) `processApproval` (POST) hanya `auth` dan **tidak** memverifikasi `hasRole('KPA')`.
- **Dampak:** Persetujuan final pencairan (memicu pembuatan SPP/SPM) dapat dilakukan oleh non-KPA atau siapa pun yang memperoleh magic link (diteruskan via WA/email). Titik kontrol tertinggi dalam alur keuangan.
- **Kemungkinan:** Sedang
- **Tingkat Risiko:** **Kritis**
- **Bukti/Indikasi:** `routes/web.php:183` `->middleware(['web'])` (tanpa `signed`); `Auth::loginUsingId($user->id)` setelah `User::findOrFail($request->query('user_id'))`; `processApproval` set `'kpa_approved_by'=>Auth::id()` tanpa cek role.
- **Cara Perbaikan:** Pasang `signed` pada route; jangan auto-login sesi penuh — gunakan sesi/token terbatas satu-aksi terikat user+dokumen; wajibkan `hasAnyRole(['KPA','PLT/PLH'])`; integrasikan keputusan KPA ke `WorkflowApproval`.
- **Contoh Konfigurasi Benar:**
```php
Route::get('/p/kpa-approval/tagihan/{tagihanId}', [KpaApprovalController::class,'showApproval'])
    ->middleware(['web','signed'])->name('kpa.approval.show');
// processApproval:
abort_unless(Auth::check() && Auth::user()->hasAnyRole(['KPA','PLT/PLH']), 403);
abort_unless($tagihan->isKpaApprover(Auth::user()), 403);
```
- **Prioritas:** Critical
- **Status Verifikasi:** **Terverifikasi**

### ARCH-01 — Endpoint `auto-approve "untuk testing"` aktif di produksi
- **Lokasi:** `app/Http/Controllers/TagihanJasaController.php:1437` (`autoApproveAll`); `routes/web.php:597`
- **Modul/Area:** Arsitektur / Workflow Engine / Tagihan Jasa (PNBP)
- **Risiko:** Satu `POST /tagihan-jasa/{id}/auto-approve` mengubah seluruh `WorkflowApproval` `PENDING/WAITING` → `APPROVED` (catatan *'Auto-approved untuk testing'*), men-set tagihan `DISETUJUI`, dan memanggil `createSuratPengantarFinalTtd()` (Surat Pengantar Final ber-TTE). Verifikasi berjenjang (KPA/PLT-PLH dst.) di-*bypass* total.
- **Dampak:** Dokumen PNBP resmi ber-TTE dapat diterbitkan tanpa verifikasi sah; integritas keuangan & audit trail rusak; potensi temuan BPK/penyalahgunaan.
- **Kemungkinan:** Sedang (terbatas role Super Admin/Admin Jasa, tetapi tanpa guard environment).
- **Tingkat Risiko:** **Kritis**
- **Bukti/Indikasi:** `grep App::environment/APP_ENV` pada controller = tidak ada. Satu-satunya proteksi `abort_unless($this->canManageTagihanJasa(),403)` (cek role, bukan environment).
- **Cara Perbaikan:** Hapus dari rute produksi atau bungkus `if (app()->environment(['local','testing']))` + `abort_if(app()->isProduction(),403)`.
- **Contoh Konfigurasi Benar:**
```php
if (app()->environment(['local','testing'])) {
    Route::post('/tagihan-jasa/{id}/auto-approve', [TagihanJasaController::class,'autoApproveAll'])
        ->name('tagihan-jasa.auto-approve');
}
public function autoApproveAll($id) {
    abort_if(app()->isProduction(), 403, 'Disabled in production');
    // ...
}
```
- **Prioritas:** Critical
- **Status Verifikasi:** **Terverifikasi**

### INT-01 — Callback BTN dapat diproses tanpa autentikasi (secret default kosong), CSRF dimatikan
- **Lokasi:** `app/Http/Controllers/BtnPaymentCallbackController.php:16-26`; `routes/web.php:107-109`
- **Modul/Area:** Integrasi Eksternal / BTN Virtual Account / Payment Callback
- **Risiko:** Verifikasi keaslian dibungkus `if (filled($secret))`. Pada kondisi default (secret kosong), blok abort dilewati dan `$service->handlePaymentCallback($request->all())` langsung dieksekusi. Route `->withoutMiddleware([ValidateCsrfToken::class])` dan publik (tanpa `auth`).
- **Dampak:** Pihak anonim dapat `POST` `nomor_va + amount` korban dan menandai tagihan PNBP `LUNAS` (`sisa_tagihan=0`). **Kerugian negara langsung:** pendapatan dianggap lunas tanpa uang masuk.
- **Kemungkinan:** Tinggi (endpoint publik, tanpa kredensial pada state default).
- **Tingkat Risiko:** **Kritis**
- **Bukti/Indikasi:** `BtnPaymentCallbackController.php:17` `if (filled($secret)) {…}` tanpa `else`; view `integrasi/index.blade.php:180` placeholder *'Wajib diisi untuk produksi'* menandakan secret bisa kosong.
- **Cara Perbaikan:** Jadikan secret WAJIB (tolak 503/401 bila kosong); verifikasi HMAC atas raw body + allowlist IP BTN.
- **Contoh Konfigurasi Benar:**
```php
$secret = IntegrationSetting::getValue('btn.callback_secret');
abort_if(blank($secret), 503, 'Callback secret belum dikonfigurasi.');
abort_unless(hash_equals((string)$secret, (string)$request->header('X-Callback-Secret')), 401);
$calc = hash_hmac('sha256', $request->getContent(), $secret);
abort_unless(hash_equals($calc, (string)$request->header('X-Signature')), 401);
```
- **Prioritas:** Critical
- **Status Verifikasi:** **Terverifikasi**

### INT-02 — Tidak ada idempotency nyata (retry/replay → double credit)
- **Lokasi:** `app/Services/BtnVirtualAccountService.php:49-55,83-118`
- **Modul/Area:** Integrasi Eksternal / BTN Virtual Account / PaymentTransaction
- **Risiko:** Kunci idempotency `(provider, external_reference)`. Bila payload tak memuat reference, kode memakai `'BTN-' . Str::uuid()` yang **berbeda tiap request** → `updateOrCreate` selalu membuat record baru dan blok pelunasan dieksekusi ulang. `jumlah_dibayar => $amount` adalah *overwrite*, bukan akumulasi.
- **Dampak:** Replay/retry menghasilkan banyak `PaymentTransaction` untuk tagihan sama; `PiutangSyncService::syncFromLunas` dipanggil berulang; struk LUNAS ganda; **pencatatan penerimaan ganda di pembukuan**.
- **Kemungkinan:** Tinggi (terutama tanpa secret — INT-01).
- **Tingkat Risiko:** **Kritis**
- **Bukti/Indikasi:** `BtnVirtualAccountService.php:55` `… ?: 'BTN-' . Str::uuid();`; baris 103 `'jumlah_dibayar' => $amount`; baris 169 memicu notifikasi ulang.
- **Cara Perbaikan:** Wajibkan `external_reference` dari provider; tolak tanpa reference; cek transaksi `paid` sebelum proses; `lockForUpdate` dalam `DB::transaction`.
- **Contoh Konfigurasi Benar:**
```php
$ref = $this->firstFilled($payload, ['reference','transaction_id']);
abort_if(blank($ref), 422, 'reference wajib ada.');
$existing = PaymentTransaction::where('provider','btn')->where('external_reference',$ref)
    ->lockForUpdate()->first();
if ($existing && $existing->status === 'paid') return ['matched'=>true,'transaction'=>$existing];
```
- **Prioritas:** Critical
- **Status Verifikasi:** **Terverifikasi**

### BE-01 — Produksi `APP_DEBUG=true` & `APP_ENV=local` + `withExceptions` kosong
- **Lokasi:** `.env:2-4`; `bootstrap/app.php:29-31`
- **Modul/Area:** Backend / Exception Handling / Konfigurasi
- **Risiko:** `APP_ENV=local`, `APP_DEBUG=true`, `withExceptions()` kosong (tanpa custom render/report). Setiap exception tak tertangani dirender halaman debug Ignition yang menampilkan source code, query SQL, path server, dan dump environment (termasuk `APP_KEY` yang terisi; token integrasi bila diisi).
- **Dampak:** Kebocoran kredensial & secret integrasi ke pihak luar → pengambilalihan data keuangan, manipulasi tagihan/piutang, akses sistem pembayaran.
- **Kemungkinan:** Tinggi (dipicu exception apa pun).
- **Tingkat Risiko:** **Kritis**
- **Bukti/Indikasi:** `.env:2` `APP_ENV=local`, `:4` `APP_DEBUG=true`; `bootstrap/app.php:29-31` `withExceptions(function (Exceptions $exceptions) { // })` kosong. (Catatan verifier: saat ini `DB_PASSWORD`, `FONNTE_TOKEN`, `WA_API_KEY` kosong di `.env`, namun `APP_KEY` terisi dan mekanisme kebocoran tetap aktif untuk setiap secret yang diisi.)
- **Cara Perbaikan:** `APP_ENV=production`, `APP_DEBUG=false`, `php artisan config:cache`; isi `withExceptions` untuk report ke log/Sentry dan render `errors/500`.
- **Contoh Konfigurasi Benar:**
```php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (\Throwable $e, $request) {
        if (! config('app.debug')) return response()->view('errors.500', [], 500);
    });
})
```
- **Prioritas:** Critical
- **Status Verifikasi:** **Terverifikasi**

### INF-01 — Arsip dokumen keuangan sensitif disimpan di disk `public`
- **Lokasi:** `app/Services/DocumentArchiveService.php:22`; `TagihanController.php:205,219,233`; `TagihanProsesController.php:332`; `TagihanJasaController.php:1624,1650`; `TagihanJasaVerifikasiController.php:197` (total 15 kemunculan di 9 controller)
- **Modul/Area:** Infrastruktur / Penyimpanan File Privat
- **Risiko:** `DocumentArchiveService::upload()` default `disk='public'`, dan controller eksplisit mengirim `'disk'=>'public'` untuk dokumen sensitif (INVOICE, FAKTUR_PAJAK, BAPP_GAMBAR_RAB, SURAT_PENGANTAR_FINAL_TTD). Disk `public` di-symlink ke `public/storage` → dapat diunduh siapa pun yang mengetahui/menebak URL **tanpa login**.
- **Dampak:** Kebocoran data keuangan & pajak (NPWP, nominal, faktur pajak, identitas mitra/vendor), dokumen TTE final → potensi pelanggaran UU PDP & temuan kepatuhan BLU, memudahkan pemalsuan.
- **Kemungkinan:** Tinggi (URL diekspos via `asset('storage/…')` di puluhan blade, `target=_blank`).
- **Tingkat Risiko:** **Kritis**
- **Bukti/Indikasi:** `DocumentArchiveService.php:22` `$disk = $attributes['disk'] ?? 'public';`; `config/filesystems.php:42` `url public => APP_URL.'/storage'`; `TagihanDokumenPendukung.php:37` `Storage::disk($disk)->url($path)`.
- **Cara Perbaikan:** Pindahkan arsip sensitif ke disk `local` (privat), layani via route terproteksi `auth`+otorisasi; ubah default service ke `local`; migrasikan file yang terlanjur publik.
- **Contoh Konfigurasi Benar:**
```php
$disk = $attributes['disk'] ?? 'local';            // default privat
Route::middleware(['auth'])->get('/arsip/{arsip}/download', function (ArsipDokumen $arsip) {
    Gate::authorize('view', $arsip);
    return Storage::disk($arsip->disk)->download($arsip->path_file, $arsip->nama_file_asli);
});
```
- **Prioritas:** Critical
- **Status Verifikasi:** **Terverifikasi**

### TTE-01 — PDF dokumen TTE di-render ulang dari DB, bukan artefak final yang dibekukan
- **Lokasi:** `app/Http/Controllers/PublicDocumentSignatureController.php:65-78`; `app/Support/DocumentTte.php:93-131`
- **Modul/Area:** Dokumen / TTE / Integritas Dokumen (SPP/SPM/NPI/SP2D)
- **Risiko:** Endpoint `public.document-tte.document` memanggil ulang `cetakPdf`/`cetakPdfSpm`/`cetakPdf` yang me-render PDF dari nilai kolom saat ini. Tidak ada snapshot PDF final yang disimpan & di-hash. `hashPayload()` hanya mencakup metadata (type, id, nomor, tanggal, amount, status, approvals) — **bukan** isi tubuh PDF (uraian, rincian, lampiran).
- **Dampak:** Perubahan editorial pada field yang tidak masuk hash tidak terdeteksi QR — penerima tetap melihat 'cocok'. Potensi pemalsuan/penyangkalan isi dokumen pencairan ber-TTE.
- **Kemungkinan:** Sedang
- **Tingkat Risiko:** **Kritis**
- **Bukti/Indikasi:** `PublicDocumentSignatureController::document()` → `app(SppController::class)->cetakPdf($id)` (render live); `DocumentTte::hash() = sha256(json_encode(payload))` tanpa checksum tubuh PDF. (Catatan: `amount` masuk hash, jadi perubahan nominal utama terdeteksi; field bebas tidak.)
- **Cara Perbaikan:** Saat fully-verified, render PDF final sekali, simpan ke arsip immutable + `sha256`, layani endpoint publik dari file beku; masukkan `pdf_checksum` ke payload hash QR.
- **Contoh Konfigurasi Benar:**
```php
$binary = Pdf::loadHTML($html)->setPaper('a4')->output();
$checksum = hash('sha256', $binary);
$spp->arsipDokumen()->create(['jenis_dokumen'=>'SPP_FINAL_TTE','path_file'=>$path,'checksum'=>$checksum,'is_active'=>true]);
// payload QR menyertakan 'pdf_checksum' => $checksum; endpoint publik => download file beku.
```
- **Prioritas:** Critical
- **Status Verifikasi:** **Terverifikasi**

### BR-01 — Tidak ada strategi/tooling backup database & restore sama sekali
- **Lokasi:** `composer.json:7-16` (tidak ada paket backup); `routes/console.php:1-31` (tidak ada schedule backup)
- **Modul/Area:** Backup & Recovery
- **Risiko:** Tidak ada `spatie/laravel-backup`, tidak ada Artisan command backup, `routes/console.php` hanya menjadwalkan reminder WA + nonaktif user. Tidak ada RPO/RTO yang terjamin.
- **Dampak:** Kehilangan data transaksi keuangan (SPP/SPM/SP2D, tagihan PNBP, bukti setor pajak) akibat korupsi DB/ransomware/human error tidak dapat dipulihkan → temuan BPK/inspektorat & kerugian negara.
- **Kemungkinan:** Sedang (probabilitas insiden × ketiadaan mitigasi total).
- **Tingkat Risiko:** **Kritis**
- **Bukti/Indikasi:** `grep 'laravel-backup|backup:run|mysqldump'` = tidak ada. 7 file `app/Console/Commands/*` tanpa command backup.
- **Cara Perbaikan:** Install `spatie/laravel-backup`, tujuan off-site terenkripsi, jadwalkan harian, uji restore berkala (DR drill), dokumentasikan RPO/RTO + retensi.
- **Contoh Konfigurasi Benar:**
```bash
composer require spatie/laravel-backup
```
```php
Schedule::command('backup:clean')->daily()->at('01:00');
Schedule::command('backup:run --only-db')->daily()->at('01:30');
// config/backup.php: destination disks=['s3_backup'], notifications mail ke tim-it, enkripsi aktif
```
- **Prioritas:** Critical
- **Status Verifikasi:** **Terverifikasi**

---

## D. Temuan Mayor (detail)

### SEC-01 — Auto-login KPA mempercayai parameter `user_id` tanpa verifikasi role
- **Lokasi:** `KpaApprovalController.php:122-136,58-62` · **Area:** KPA Approval (Magic Link)
- **Risiko:** `showApproval()` `Auth::loginUsingId($user->id)` dari `user_id` di signed URL tanpa cek role KPA; sesi penuh 24 jam (bukan sesi satu-aksi).
- **Dampak:** Pemegang tautan WA (diteruskan/bocor) memperoleh sesi KPA penuh — menyetujui tagihan bernilai besar & akses seluruh menu KPA. **Kemungkinan:** Sedang · **Prioritas:** High
- **Perbaikan/Contoh:** `abort_unless($user->hasAnyRole(['KPA','PLT/PLH']),403)` sebelum login; gunakan sesi terbatas satu-aksi; perpendek TTL (2–4 jam) + token sekali-pakai. · **Status:** Terverifikasi

### SEC-03 — Konfigurasi produksi tidak aman (cookie sesi tanpa `Secure`)
- **Lokasi:** `.env:2-6,29-33`; `config/session.php:172,202` · **Area:** Session & Cookie Security
- **Risiko:** `APP_ENV=local`, `APP_DEBUG=true`, `SESSION_SECURE_COOKIE`/`SESSION_SAME_SITE` tidak diset (cookie tidak `Secure`, `same_site=lax`).
- **Dampak:** Kebocoran info via halaman debug; pencurian cookie sesi pada jalur non-TLS (session hijacking akun bendahara/KPA). **Kemungkinan:** Tinggi · **Prioritas:** High
- **Perbaikan/Contoh:** `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=lax`, `SESSION_ENCRYPT=true`, `php artisan config:cache`. · **Status:** Terverifikasi

### SEC-04 — Magic-link TTE Berita Acara/upload tidak `signed` & tak terikat identitas
- **Lokasi:** `routes/web.php:171-175`; `PublicMagicLinkSignatureController.php:32-90,158` · **Area:** Public TTE (BAPP/BAST/BAP) / Vendor Upload
- **Risiko:** Route `public.magic-link.*` tanpa middleware `signed`, hanya mengandalkan kerahasiaan `{token}`; `sign()/uploadArsip()` memproses TTE & upload hanya berbekal token, tanpa verifikasi identitas.
- **Dampak:** Pemalsuan tanda tangan & unggahan dokumen kontrak final oleh pihak tak berwenang bila token bocor/ditebak. **Kemungkinan:** Sedang · **Prioritas:** High
- **Perbaikan/Contoh:** Token `Str::random(40+)` kriptografis + `expires_at` + sekali-pakai; tambahkan `signed`/OTP; catat IP & user-agent. · **Status:** Terverifikasi

### SEC-05 — Vendor upload dokumen kontrak tanpa pengikatan vendor & tanpa pencatatan pengunggah
- **Lokasi:** `PublicContractVendorUploadController.php:17-126`; `routes/web.php:149-154` · **Area:** Public Vendor Contract Upload
- **Risiko:** Hanya validasi `signed` + status kontrak; tidak ada pengikatan `vendor_id`; `replaceKontrakArsipAktif()` menyimpan arsip baru `uploaded_by=null`.
- **Dampak:** Penimpaan dokumen kontrak final (SPK/SPMK/Ringkasan) tanpa jejak identitas; integritas dokumen dasar pembayaran terganggu. **Kemungkinan:** Sedang · **Prioritas:** Medium
- **Perbaikan/Contoh:** Perpendek TTL signed URL; ikat & validasi `vendor_id` pada signature; batasi penimpaan dokumen final; catat IP/identitas/timestamp. · **Status:** Terverifikasi

### SEC-06 — Catch-all route `{any}` di luar grup auth
- **Lokasi:** `routes/web.php:1000` · **Area:** Routing / Fallback Handler
- **Risiko:** `Route::get('{any}', [HomeController::class,'root'])->where('any','.*')` menangkap setiap path GET tak dikenal di luar grup `['auth','account.active']`.
- **Dampak:** Potensi information disclosure/perilaku tak terduga bagi anonim bila `root()` tidak konsisten me-redirect. **Kemungkinan:** Rendah · **Prioritas:** High
- **Perbaikan/Contoh:** Pastikan `root()` hanya redirect ke login/dashboard; pertimbangkan `Route::fallback()` yang mengembalikan 404. · **Status:** Terverifikasi

### SEC-07 — Cetak PDF SPP/SPM/NPI/SP2D hanya `auth` (IDOR)
- **Lokasi:** `routes/web.php:810-815` · **Area:** Dokumen Pencairan
- **Risiko:** Grup `middleware('auth')` membuka `/spps|spms|npis|sp2ds/{id}/pdf` untuk SEMUA user terotentikasi (termasuk Mitra/AMC) tanpa filter role/kepemilikan.
- **Dampak:** IDOR — unduh dokumen pencairan keuangan lintas tagihan dengan menebak ID; membocorkan nominal, rekening, rincian pembayaran negara. **Kemungkinan:** Sedang · **Prioritas:** High
- **Perbaikan/Contoh:** Tambahkan `role:` internal keuangan pada grup atau `authorize()` keterkaitan user-tagihan tiap method. · **Status:** Terverifikasi

### WF-03 — Race condition: approve tanpa lock pada baris approval/instance
- **Lokasi:** `PerjaldinWorkflowService.php:141-192`; `WorkflowService.php:104-154` · **Area:** Workflow Engine — konkurensi
- **Risiko:** Approve membaca approval lalu update tanpa `lockForUpdate()`/guard atomik; double-klik atau dua request bersamaan dapat melewati cek `status !== 'PENDING'`.
- **Dampak:** Duplikasi approval, lompatan step tidak konsisten, instance `APPROVED` lebih cepat dari seharusnya → dokumen lolos tanpa seluruh verifikator. **Kemungkinan:** Rendah · **Prioritas:** High
- **Perbaikan/Contoh:** Ambil ulang approval `lockForUpdate()` dalam transaksi; `UPDATE … WHERE status='PENDING'` + cek affected rows; unique guard satu APPROVED per approval. · **Status:** Terverifikasi

### WF-04 — Manipulasi status dokumen langsung di controller (di luar transisi sah)
- **Lokasi:** `TagihanJasaVerifikasiController.php:94-109,…`; `KpaApprovalController.php:104-109,…`; `ContractController.php:600-728` · **Area:** Sinkronisasi status
- **Risiko:** Banyak controller menulis status langsung (`$tagihan->status='DISETUJUI'`, `$kontrak->update(['status_kontrak'=>'AKTIF'])`) alih-alih lewat engine (`syncTagihanStatus`).
- **Dampak:** Status menyimpang dari kondisi approval sebenarnya, mengaburkan kontrol & audit; perubahan status tanpa transisi tervalidasi. **Kemungkinan:** Sedang · **Prioritas:** High
- **Perbaikan/Contoh:** Jadikan engine satu-satunya penulis kolom status; controller hanya memanggil service approve/reject. · **Status:** Terverifikasi

### WF-06 — Resubmit setelah revisi menghapus jejak approval sebelumnya
- **Lokasi:** `PerjaldinWorkflowService.php:64-74`; `WorkflowService.php:23-27` · **Area:** Audit trail & revisi
- **Risiko:** Submit ulang dari `REVISION` me-reset baris approval in-place (`acted_by_user_id=NULL, acted_at=NULL, ip_address=NULL`) → histori per-step terdahulu hilang.
- **Dampak:** Jejak audit granular (approver mana, kapan, IP) tak dapat direkonstruksi dari tabel approval — melemahkan akuntabilitas audit keuangan. **Kemungkinan:** Sedang · **Prioritas:** Medium
- **Perbaikan/Contoh:** Buat putaran/instance approval BARU per submit; pertahankan baris lama; bedakan `SUPERSEDED` vs `REJECTED`. · **Status:** Terverifikasi

### DI-01 — Pembayaran parsial menimpa `total_dibayar` (bukan akumulasi)
- **Lokasi:** `PiutangSyncService.php:106-109`; `BtnVirtualAccountService.php:80-118` · **Area:** Penerimaan PNBP / Piutang / BKU
- **Risiko:** `syncFromPartial` menulis `total_dibayar = $amount` (assignment); `isFullPayment` membandingkan SATU callback vs total. Dua setoran parsial (40%+60%) tak pernah `>= total` → status tak pernah LUNAS → `syncFromLunas` (baris BKU DEBIT_MASUK) tak pernah dipanggil.
- **Dampak:** Kas riil masuk tapi tidak tercatat di BKU (saldo understated); piutang tampak kurang bayar padahal lunas; laporan pengesahan understated. **Kemungkinan:** Sedang · **Prioritas:** High
- **Perbaikan/Contoh:** Akumulasi Σ `PaymentTransaction` sukses; tentukan `isFullPayment` dari Σ vs total. · **Status:** Terverifikasi

### DI-02 — Pencocokan piutang↔setoran hanya nominal + tanggal ±3 hari (tanpa pengikat unik)
- **Lokasi:** `PiutangRekonsiliasiService.php:95-160`; dipakai `PiutangSyncService.php:184` · **Area:** Rekonsiliasi Penerimaan
- **Risiko:** `matchQuery/autoMatch` memasangkan BKU↔piutang hanya `ABS(nominal-x)<0.01` + tanggal ±3 hari, tanpa ikat mitra/VA/no referensi. Dua mitra dengan nominal sama (tarif/PJP2U seragam) bisa tertukar.
- **Dampak:** Setoran mitra A dikreditkan ke piutang mitra B; salah saji piutang per mitra, sengketa penagihan, integritas buku pembantu rusak. **Kemungkinan:** Sedang · **Prioritas:** High
- **Perbaikan/Contoh:** Cocokkan nomor referensi bank/VA ke `nomor_invoice/nomor_va` (prioritas tertinggi); batasi kandidat per mitra; jika kandidat >1 → manual. · **Status:** Terverifikasi

### DI-03 — `realisasi_anggaran` kehilangan unique; idempotency hanya di aplikasi
- **Lokasi:** `2026_06_13_090000_drop_unique_nomor_bukti_on_realisasi_anggaran.php:19-20`; `BudgetRealizationService.php:31-38,163-174` · **Area:** Realisasi Anggaran (DIPA→SP2D)
- **Risiko:** Unique `nomor_bukti` di-drop (alasan sah); penggantinya hanya `exists(dokumen_sp2d_id,status=TERCATAT)`. Bila baris sebelumnya `DIBATALKAN`, tidak ada constraint mencegah dobel per `(dokumen_sp2d_id, dipa_revision_item_id)`.
- **Dampak:** Dobel pencatatan realisasi → pagu DIPA tampak terpakai ganda / over-realisasi. **Kemungkinan:** Rendah · **Prioritas:** Medium
- **Perbaikan/Contoh:** `unique(['dokumen_sp2d_id','dipa_revision_item_id','deleted_at'])`; pertahankan `lockForUpdate`. · **Status:** Terverifikasi

### DI-07 — `syncFromLunas` menelan exception (tagihan LUNAS tanpa baris BKU)
- **Lokasi:** `PiutangSyncService.php:124-233`; `BtnVirtualAccountService.php:156-167` · **Area:** Penerimaan PNBP / BKU
- **Risiko:** `try/catch \Throwable → Log::error + return null`. Transaksi internal rollback baris BKU, TETAPI tagihan SUDAH ditandai LUNAS di transaksi BTN terpisah sebelumnya. Bila gagal (mis. rekening penerimaan tidak ada), tagihan LUNAS tanpa baris BKU.
- **Dampak:** Tagihan LUNAS tanpa pencatatan kas → penerimaan understated; inkonsistensi senyap hanya terlihat di log. **Kemungkinan:** Sedang · **Prioritas:** High
- **Perbaikan/Contoh:** Satukan penandaan LUNAS + posting BKU dalam SATU transaksi (atau outbox/queue idempoten); jangan telan exception jalur keuangan; status `LUNAS_PENDING_BKU` + retry + alert. · **Status:** Terverifikasi

### INT-03 — Nominal & VA pelunasan dipercaya penuh dari payload (tanpa rekonsiliasi balik)
- **Lokasi:** `BtnVirtualAccountService.php:56-110` · **Area:** BTN VA / Rekonsiliasi
- **Risiko:** `$amount` mentah dari payload dipakai untuk `isFullPayment` & `jumlah_dibayar`; tidak ada query balik (inquiry/confirm) ke BTN; tidak ada validasi currency/negatif.
- **Dampak:** Bila INT-01 belum diperbaiki, kirim `amount=total` memicu LUNAS; tanpa rekonsiliasi dua-arah rentan manipulasi nilai bayar. **Kemungkinan:** Sedang · **Prioritas:** High
- **Perbaikan/Contoh:** Inquiry server-to-server ke BTN sebelum LUNAS; validasi `amount>0` + toleransi pembulatan; cocokkan VA↔tagihan ketat. · **Status:** Terverifikasi

### INT-04 — Tidak ada allowlist IP / HMAC / anti-replay pada callback
- **Lokasi:** `routes/web.php:107-109`; `BtnPaymentCallbackController.php:11-26` · **Area:** BTN VA / Hardening Endpoint
- **Risiko:** Satu-satunya proteksi shared-secret header (bila diisi); tanpa allowlist IP, HMAC body, nonce/timestamp window → request sah pun bisa di-replay tanpa batas.
- **Dampak:** Secret yang bocor (log proxy/WAF) langsung dipakai replay tak terbatas. **Kemungkinan:** Sedang · **Prioritas:** High
- **Perbaikan/Contoh:** Middleware allowlist IP + throttle + HMAC body + header timestamp (window 5 mnt) + simpan nonce/reference terproses. · **Status:** Terverifikasi

### INT-05 — Password login mitra dikirim plaintext via email
- **Lokasi:** `EmailNotificationService.php:151-153,176` · **Area:** Email / Provisioning Akun Mitra
- **Risiko:** Template email tagihan menyertakan `{password_login}` = `$accountInfo['password']` dikirim plaintext (SMTP/log).
- **Dampak:** Kebocoran password portal mitra (akses data tagihan/keuangan mitra); pelanggaran praktik kredensial. **Kemungkinan:** Sedang · **Prioritas:** High
- **Perbaikan/Contoh:** Jangan kirim password; kirim tautan set-password sekali pakai (signed, kedaluwarsa singkat) atau wajibkan reset saat login pertama. · **Status:** Terverifikasi

### INT-07 — Verifikasi TLS dimatikan saat memanggil WhatsApp Gateway
- **Lokasi:** `WhatsappService.php:142-147` · **Area:** WhatsApp Gateway
- **Risiko:** `Http::withoutVerifying()` → sertifikat server tidak diverifikasi; API key (Bearer) & isi pesan dikirim tanpa jaminan keaslian endpoint.
- **Dampak:** MITM — pencegatan/perubahan pesan & pencurian API key gateway, lalu pengiriman pesan atas nama instansi (nomor VA palsu ke mitra). **Kemungkinan:** Rendah · **Prioritas:** Medium
- **Perbaikan/Contoh:** Hapus `withoutVerifying()` di produksi; HTTPS sertifikat valid atau pin CA internal. · **Status:** Terverifikasi

### MON-01 — Tidak ada monitoring aplikasi, error tracking, atau alerting
- **Lokasi:** `composer.json:7-25`; `config/logging.php:76-83` · **Area:** Monitoring Jaringan
- **Risiko:** Tidak ada Sentry/Bugsnag/Telescope; channel `slack` ada tapi `LOG_SLACK_WEBHOOK_URL` tak diset; `/up` ada tapi tanpa uptime monitor eksternal.
- **Dampak:** Kegagalan proses keuangan (job notifikasi gagal, error SPM/SP2D, error BTN/WA) tidak terdeteksi proaktif → MTTR lambat, keterlambatan pembayaran/penerimaan. **Kemungkinan:** Tinggi · **Prioritas:** High
- **Perbaikan/Contoh:** `sentry/sentry-laravel` + DSN; set `LOG_SLACK_WEBHOOK_URL` & masukkan `slack` ke `LOG_STACK`; uptime monitor `/up`; alert pertambahan `failed_jobs`. · **Status:** Terverifikasi

### INF-02 — `.env` kerja masih konfigurasi local/dev untuk sistem produksi
- **Lokasi:** `.env:2,4,6,20,49` · **Area:** Infrastruktur / Kesiapan Produksi
- **Risiko:** `APP_ENV=local`, `APP_DEBUG=true`, `APP_URL=http://localhost`, `MAIL_MAILER=log`, `LOG_LEVEL=debug`, `SESSION_SECURE_COOKIE` tak diset.
- **Dampak:** Halaman error bocorkan stack trace+kredensial; cookie via HTTP; email reset tidak terkirim. **Kemungkinan:** Sedang · **Prioritas:** High
- **Perbaikan/Contoh:** `.env` produksi: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://…`, `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`, `LOG_LEVEL=warning`, `MAIL_MAILER=smtp`; `config:cache`. · **Status:** Terverifikasi

### TTE-03 — Signed URL & QR TTE permanen (tanpa kedaluwarsa)
- **Lokasi:** `DocumentTte.php:139,162`; `TagihanDocumentTte.php:154`; `PublicDocumentSignatureController.php:38`; `routes/web.php:110-180` · **Area:** TTE / Tautan Publik
- **Risiko:** Seluruh tautan publik dokumen/QR memakai `URL::signedRoute` (permanen); hanya KPA approval pakai `temporarySignedRoute`. QR dicetak di PDF & disebar via WA/Email.
- **Dampak:** Tautan/QR bocor (PDF diteruskan, screenshot) valid selamanya selama `APP_KEY` tidak diputar; tanpa mekanisme revoke. **Kemungkinan:** Sedang · **Prioritas:** High
- **Perbaikan/Contoh:** `temporarySignedRoute` dengan TTL wajar untuk distribusi; QR verifikasi validasi hash isi (TTE-02); tambahkan kemampuan revoke (nonce per dokumen). · **Status:** Terverifikasi

### TTE-04 — Magic-link penandatanganan vendor tanpa expiry, endpoint sign publik tanpa rate limit
- **Lokasi:** `routes/web.php:171-175`; `PublicMagicLinkSignatureController.php:32-106`; `2026_05_28_034147_create_document_signatures_table.php:22` · **Area:** TTE Penandatanganan Publik
- **Risiko:** `/public/tte/sign/{token}` tanpa `signed`, hanya `Str::random(40)` tanpa kolom kedaluwarsa; POST `sign` mengubah status ke `signed` & menerima upload final hanya berbekal token.
- **Dampak:** Token tidak pernah kedaluwarsa; bila bocor, siapa pun menandatangani/mengunggah dokumen final atas nama vendor/pemeriksa; non-repudiation lemah. **Kemungkinan:** Sedang · **Prioritas:** High
- **Perbaikan/Contoh:** Tambah `expires_at` + tolak token kedaluwarsa; `signed`/OTP per penandatangan; throttle POST sign/upload; konfirmasi OTP WhatsApp sebelum `signed`. · **Status:** Terverifikasi

### DB-01 — Unique `nomor_bukti` pada `realisasi_anggaran` di-drop di produksi
- **Lokasi:** `2026_06_13_090000_drop_unique_nomor_bukti_on_realisasi_anggaran.php:17-20` · **Area:** Realisasi Anggaran / SP2D
- **Risiko:** Setelah `dropUnique`, DB tidak lagi mencegah dua realisasi dengan `nomor_bukti` (nomor SP2D) sama; proteksi hanya di `BudgetRealizationService`.
- **Dampak:** Bila guard aplikasi gagal (race/jalur impor/console), satu SP2D tercatat dua kali → menggelembungkan penyerapan anggaran/DIPA tanpa rem DB. **Kemungkinan:** Sedang · **Prioritas:** High
- **Perbaikan/Contoh:** `unique(['dokumen_sp2d_id','dipa_revision_item_id'])` (granularitas baru) + `lockForUpdate` sebagai lapis kedua. · **Status:** Terverifikasi

### DB-03 — Unique `laporan_utilitas` (mitra+layanan+periode) di-drop → duplikasi tagihan utilitas
- **Lokasi:** `2026_06_15_000000_drop_unik_laporan_utilitas_unique.php:19-26` · **Area:** Mitra Jasa / Utilitas (Listrik & Air)
- **Risiko:** Drop unique `unik_laporan_utilitas` → banyak laporan per mitra+layanan+periode tanpa rem DB.
- **Dampak:** Pemakaian listrik/air bisa dilaporkan & ditagih ganda untuk periode sama → over-billing/salah catat PNBP. **Kemungkinan:** Sedang · **Prioritas:** Medium
- **Perbaikan/Contoh:** Bila harus >1, tambahkan kolom pembeda ke composite unique (`['mitra_jasa_id','layanan_jasa_id','nomor_meter','bulan','tahun']`) alih-alih menghapus total; validasi server-side + konfirmasi UI. · **Status:** Terverifikasi

### BE-02 — Pesan exception mentah dikembalikan ke pengguna
- **Lokasi:** `HonorariumController.php:257`; `TagihanJasaController.php:516,857,893,1481` · **Area:** Exception Handling
- **Risiko:** Banyak `catch` mengembalikan `$e->getMessage()` ke UI (`'Gagal menyimpan: '.$e->getMessage()`); pesan SQL/PDO dapat memuat nama tabel/kolom/constraint.
- **Dampak:** Membocorkan struktur skema keuangan ke pengguna non-admin → reconnaissance untuk serangan lanjutan. **Kemungkinan:** Sedang · **Prioritas:** Medium
- **Perbaikan/Contoh:** `Log::error($e)`; tampilkan pesan generik; `getMessage()` hanya untuk exception domain (`\DomainException`) yang ramah-pengguna. · **Status:** Terverifikasi

### ARCH-02 — God controller: `TagihanJasaController` 2.352 baris
- **Lokasi:** `TagihanJasaController.php:1-2352` (25 method publik, 33 privat) · **Area:** Arsitektur / Domain Jasa
- **Risiko:** Controller mencakup validasi, generate nomor, generate/arsip PDF, sinkronisasi VA, link garbarata, TTE, pelunasan dalam satu kelas (9 `DB::transaction` tersebar).
- **Dampak:** Maintainability rendah, risiko regresi tinggi pada modul keuangan inti; sulit diuji unit. **Kemungkinan:** Tinggi · **Prioritas:** High
- **Perbaikan/Contoh:** Ekstraksi penomoran ke `DocumentNumberService`, PDF/pelunasan/garbarata ke service; `FormRequest` untuk validasi; target controller <300 baris. · **Status:** Terverifikasi

### ARCH-05 — God model `TagihanJasa` (denda, kualitas piutang, TTE tertanam)
- **Lokasi:** `TagihanJasa.php:140-258` · **Area:** Arsitektur / Piutang
- **Risiko:** Model memuat kebijakan tarif denda 2%/30 hari, klasifikasi kualitas piutang (Lancar/Kurang Lancar/Diragukan/Macet), HMAC TTE; sebagian duplikat dengan `PiutangAgingService`.
- **Dampak:** Aturan ter-hardcode menyulitkan perubahan kebijakan & menimbulkan inkonsistensi; logika TTE di model menyulitkan uji/rotasi kunci. **Kemungkinan:** Sedang · **Prioritas:** Medium
- **Perbaikan/Contoh:** Pindahkan kebijakan ke `PiutangPolicy/DendaService` sebagai sumber tunggal; TTE ke `DigitalSealService`; model hanya accessor tipis. · **Status:** Terverifikasi

### ARCH-06 — Mass-assignment terbuka (60/81 model `$guarded=['id']`)
- **Lokasi:** `app/Models/*.php` (incl. `TagihanJasa.php:15`) · **Area:** Lapisan Model / Keamanan Arsitektur
- **Risiko:** Mayoritas model membiarkan semua kolom mass-assignable; dipadukan controller yang memakai `$request->all()` (mis. `MasterUangHarianPerjaldinController:30,52`).
- **Dampak:** Potensi manipulasi nilai keuangan/status (`total_tagihan`, `jumlah_dibayar`, `status`) via parameter ekstra. **Kemungkinan:** Sedang · **Prioritas:** Medium
- **Perbaikan/Contoh:** `$fillable` eksplisit pada model keuangan; selalu pakai `validated()` bukan `$request->all()`; kunci kolom status/total agar hanya diubah lewat service. · **Status:** Terverifikasi

### ARCH-07 — Workflow state ganda (kolom status dokumen vs `WorkflowInstance`)
- **Lokasi:** `PerjaldinWorkflowService.php:27-84`; `SppPerjaldinWorkflowService.php:27-60`; `WorkflowService.php:96-223` · **Area:** Konsistensi State
- **Risiko:** Dokumen menyimpan status string panjang yang disinkronkan manual ke `WorkflowInstance.status`; ada logika 'recovery' (indikasi sinkronisasi pernah gagal di tengah transaksi).
- **Dampak:** Dua sumber kebenaran status berpotensi divergen → dokumen keuangan tampil tidak konsisten dengan approval sebenarnya. **Kemungkinan:** Sedang · **Prioritas:** Medium
- **Perbaikan/Contoh:** Satu sumber kebenaran (derive status dokumen dari instance via accessor) atau transisi atomik dalam satu transaksi dengan state machine terpusat. · **Status:** Terverifikasi

---

## E. Temuan Minor (tabel detail)

| ID | Lokasi | Modul/Area | Risiko (ringkas) | Dampak (ringkas) | Tingkat | Prioritas | Perbaikan (ringkas) | Status |
|---|---|---|---|---|---|---|---|---|
| ARCH-03 | 5 service workflow | Workflow Engine | Duplikasi engine di 5 service paralel | Perubahan aturan harus disalin 5 tempat; risiko inkonsistensi | Minor | Medium | Konsolidasi ke `AbstractDocumentWorkflowService` | Terverifikasi |
| ARCH-04 | `DashboardController.php:1-1692` | Pelaporan/Dashboard | 206 query agregasi inline di controller | Sulit optimasi/test; inkonsistensi angka antar dashboard | Minor | Medium | Pindah agregasi ke `ReportAggregationService` + caching | Terverifikasi |
| ARCH-08 | `AppServiceProvider.php:16-19`; controllers | Modularitas | `register()` kosong; penamaan domain campur ID/EN | Coupling konkret; bingung domain kontrak (2 jalur) | Minor | Low | Interface + bind di `register()`; konvensi penamaan tunggal | Terverifikasi |
| ARSIP-01 | `DocumentArchiveService.php:40-65` | Arsip/QR Cache | Replace/delete arsip final tanpa kunci immutabilitas | Bukti TTE bisa ditimpa/hapus; QR cache usang | Minor | Medium | Cegah replace/delete `*_FINAL_TTD`; soft delete + checksum | Terverifikasi |
| BE-03 | `Tagihan.php:13`, `BukuKasUmum.php:13`, dll | Mass Assignment | `$guarded=['id']` + `$request->all()` di jalur tulis | Injeksi field tersembunyi (ntpn/status/created_by) | Minor | Medium | `$fillable` eksplisit; array kolom dipetakan | Terverifikasi |
| BE-04 | `app/Http/Requests/` (hanya 6) | Validasi Request | Validasi inline terduplikasi di >100 controller | Aturan file/numerik tidak seragam; celah validasi | Minor | Medium | Ekstrak ke Form Request per entitas inti | Terverifikasi |
| BE-05 | `.env:37`; `WorkflowNotification.php` | Queue/Jobs | `QUEUE_CONNECTION=database` bergantung Supervisor eksternal | Notifikasi tertunda diam-diam bila worker mati | Minor | Medium | Monitor `failed_jobs`/`queue:monitor` + alert | Terverifikasi |
| BE-06 | `WhatsappService.php:66-74` | Network IO | Pemanggilan Fonnte sinkron tanpa `->timeout()` | Endpoint WA lambat menahan PHP-FPM/command | Minor | Medium | Tambah `timeout()`+`retry()`; pindah ke job `ShouldQueue` | Terverifikasi |
| BE-07 | `HonorariumController.php:26-29` | Performa | `index` tanpa `paginate()` (unbounded) | Halaman lambat/boros memori seiring waktu | Minor | Low | `->paginate(15)->withQueryString()` | Terverifikasi |
| DB-02 | `2026_06_18_000004_create_transaksi_pembukuan_table.php` | BKU Pengeluaran | `no_bukti` hanya index; `kode_transaksi` FK logis | Posting jurnal ganda; kode transaksi invalid lolos | Minor | Medium | FK nyata + `unique(['no_bukti','kode_transaksi','rekening_bank_id'])` | Terverifikasi |
| DB-04 | `2026_05_18_042830_create_laporan_utilitas_table.php:25-27` | Utilitas | Meteran/pemakaian `integer` | Overflow/truncation → total_biaya salah | Minor | Low | `unsignedBigInteger`/`decimal` untuk stan & pemakaian | Terverifikasi |
| DB-05 | `…transaksi_tagihan_v2.php:42-44`; `…tagihan_jasa_details` | Audit Trail | Tanpa `updated_by`; detail tanpa audit/soft delete | Sulit forensik 'siapa ubah nominal'; hard-delete rincian | Minor | Medium | Tambah `updated_by` (blameable) + `softDeletes` pada detail | Terverifikasi |
| DB-06 | `2026_04_03_070500_drop_soft_deletes_from_dipa_revision_items.php` | DIPA/Anggaran | Soft delete di-drop dari `dipa_revision_items` | Item pagu hard-delete; histori hilang | Minor | Low | Kembalikan `softDeletes` atau arsip/log revisi pagu | Terverifikasi |
| DB-07 | `2026_06_11_…normalize_master_pihak_morph_types.php` | Polymorphic | Morph type tanpa `enforceMorphMap` permanen | Rekening vendor tak terbaca di SPP/SPM/NPI/SP2D | Minor | Medium | `Relation::enforceMorphMap([...])` di provider + test | Terverifikasi |
| DB-08 | `2026_05_30_010000_add_unique_index_to_buku_kas_umum.php` | BKU/Posting Kas | Unique hanya sisi pengeluaran; penerimaan & `no_bukti` tidak | Posting ganda penerimaan → saldo rusak | Minor | Medium | `unique(['referensi_penerimaan_id','nomor_bukti'])` | Terverifikasi |
| DI-04 | `PostingPembukuanService.php:82-95` | BKU Pengeluaran | Baris BKU dibuat `referensi_pengeluaran_id=NULL` | Unique tak aktif saat insert (NULL ≠ duplikat di MySQL) | Minor | Low | Set referensi saat insert atau unique `(transaksi_pembukuan_id,kode_buku,arus_kas)` | Terverifikasi |
| DI-05 | `BukuKasUmum.php:67-72` | Recompute Saldo | Aritmetika saldo pakai `(float)` atas `decimal` | Galat pembulatan akumulatif sub-rupiah | Minor | Low | bcmath/integer sen; SUM decimal di DB | Terverifikasi |
| DI-06 | `BukuKasUmum.php:74`; `PiutangRekonsiliasiService.php` | Audit Trail BKU | Perubahan saldo via `saveQuietly()` tanpa jejak | Saldo berubah tanpa audit (siapa/kapan/nilai lama) | Minor | Medium | Tulis baris audit nilai lama→baru + aktor/waktu | Terverifikasi |
| DOC-01 | `README.md:1-66` | Dokumentasi | README masih template default Laravel | Onboarding terhambat; kontinuitas pengetahuan lemah | Minor | Low | Tulis README khusus SIKEREN + link docs | Terverifikasi |
| DOC-02 | `docs/deployment-guide.md:155-186` | Dokumentasi/Kepatuhan | Tanpa prosedur backup/restore/DR & retensi log | Pemulihan insiden bergantung individu; gap kepatuhan | Minor | Medium | Tambah bab Backup & Restore, rotasi log, runbook insiden | Terverifikasi |
| FE-01 | `perjaldins/pdf_lampiran.blade.php:119` | Perjaldin PDF | Field `tujuan` di-render unescaped lalu join HTML | XSS dompdf (baca file lokal) | Minor | Medium | `e()` sebelum join | Terverifikasi |
| FE-02 | `tagihan_jasa/create.blade.php:1295` | Tagihan Jasa | `json_encode` di `<script>` tanpa `JSON_HEX_TAG` | XSS breakout → salahguna CSRF approve | Minor | Low | Flag `JSON_HEX_TAG` atau `@json` directive | Terverifikasi |
| INT-06 | `BtnVirtualAccountService.php:95,124-134` | Logging | Payload callback/response disimpan mentah ke DB/log | Data sensitif (secret/VA/nominal) tersimpan tanpa masking | Minor | Medium | Redaksi field sensitif; enkripsi at-rest; batasi akses log | Terverifikasi |
| INT-08 | `ShortLinkController.php:18-36`; `routes/web.php:191-193` | Short Link | `/i/{slug}` tanpa throttle; `expires_at` jarang diisi | Enumerasi tak terbatas; tautan tak kedaluwarsa | Minor | Low | Throttle route + `expires_at` default + nonaktif saat lunas | Terverifikasi |
| MON-02 | `config/logging.php:55-66`; `.env:17-18` | Logging/Retensi | `LOG_STACK=single` tanpa rotasi/retensi | File log tumbuh tak terbatas; disk penuh | Minor | Medium | `LOG_STACK=daily` + `LOG_DAILY_DAYS` + log terpusat | Terverifikasi |
| NUM-01 | `PerjaldinKomponenService.php:253`; `TagihanJasaController.php:1889` | Penomoran | Nomor via `count()+1` tanpa lock (race) | Tabrakan nomor → error 500/nomor bolong | Minor | Medium | `DocumentNumberService::generateByKey` (lock+UNIQUE) | Terverifikasi |
| NUM-02 | `DocumentNumberingService.php:47-56` | Penomoran | Nomor SPM/NPI/SP2D via `preg_replace('SPP')`, tak ter-ledger | Inkonsistensi/tabrakan turunan bila format SPP berbeda | Minor | Medium | Catat nomor turunan ke ledger `DocumentNumber` + validasi prefix | Terverifikasi |
| TTE-02 | `PublicDocumentSignatureController.php:27-78` | TTE/Verifikasi QR | Ketidakcocokan hash hanya ditampilkan, tak memblokir | Dokumen berubah tetap bisa diunduh; verifikasi kosmetik | Minor | Low | Jadikan hash kontrol akses (abort 409 bila mismatch) | Terverifikasi |
| WF-05 | `WorkflowService.php:104-120` | Workflow Engine | Cabang `approvalId` tanpa cek peran/penugasan | Bila ada endpoint kirim `approval_id`, bypass otorisasi step | Minor | Medium | Validasi `getPendingApprovalForUser` + `step_saat_ini` | Terverifikasi |
| WF-07 | `PerjaldinWorkflowService.php:472-524` | Penugasan Verifikator | `assigned_user_id` null → 'siapa pun pemegang role' | Verifikator bukan penanggung jawab tetap bisa approve | Minor | Medium | Wajibkan `assigned_user_id` per step; hilangkan fallback role-wide | Terverifikasi |

---

## F. Risk Register

> Likelihood × Dampak → Tingkat Risiko. Semua temuan **Terverifikasi** terhadap kode.

| ID | Area | Tingkat | Kemungkinan | Prioritas | Status |
|---|---|---|---|---|---|
| SEC-02 | KPA Approval | Kritis | Sedang | Critical | Terverifikasi |
| WF-01 | Workflow/SoD | Kritis | Sedang | Critical | Terverifikasi |
| WF-02 | KPA/WhatsApp | Kritis | Sedang | Critical | Terverifikasi |
| ARCH-01 | Tagihan Jasa | Kritis | Sedang | Critical | Terverifikasi |
| INT-01 | BTN VA Callback | Kritis | Tinggi | Critical | Terverifikasi |
| INT-02 | BTN VA Idempotency | Kritis | Tinggi | Critical | Terverifikasi |
| BE-01 | Konfigurasi/Exception | Kritis | Tinggi | Critical | Terverifikasi |
| INF-01 | Storage Arsip | Kritis | Tinggi | Critical | Terverifikasi |
| TTE-01 | Integritas TTE | Kritis | Sedang | Critical | Terverifikasi |
| BR-01 | Backup & Recovery | Kritis | Sedang | Critical | Terverifikasi |
| SEC-01 | KPA Magic Link | Mayor | Sedang | High | Terverifikasi |
| SEC-03 | Session/Cookie | Mayor | Tinggi | High | Terverifikasi |
| SEC-04 | Public TTE/Upload | Mayor | Sedang | High | Terverifikasi |
| SEC-05 | Vendor Upload | Mayor | Sedang | Medium | Terverifikasi |
| SEC-06 | Routing Fallback | Mayor | Rendah | High | Terverifikasi |
| SEC-07 | IDOR Dokumen Pencairan | Mayor | Sedang | High | Terverifikasi |
| WF-03 | Race Condition | Mayor | Rendah | High | Terverifikasi |
| WF-04 | Manipulasi Status | Mayor | Sedang | High | Terverifikasi |
| WF-06 | Audit Trail Revisi | Mayor | Sedang | Medium | Terverifikasi |
| DI-01 | Pembayaran Parsial | Mayor | Sedang | High | Terverifikasi |
| DI-02 | Rekonsiliasi Piutang | Mayor | Sedang | High | Terverifikasi |
| DI-03 | Realisasi Anggaran | Mayor | Rendah | Medium | Terverifikasi |
| DI-07 | Exception Tertelan | Mayor | Sedang | High | Terverifikasi |
| INT-03 | Nominal Payload | Mayor | Sedang | High | Terverifikasi |
| INT-04 | Hardening Callback | Mayor | Sedang | High | Terverifikasi |
| INT-05 | Password Plaintext | Mayor | Sedang | High | Terverifikasi |
| INT-07 | TLS WA Gateway | Mayor | Rendah | Medium | Terverifikasi |
| MON-01 | Monitoring/Alerting | Mayor | Tinggi | High | Terverifikasi |
| INF-02 | Env Produksi | Mayor | Sedang | High | Terverifikasi |
| TTE-03 | Signed URL Permanen | Mayor | Sedang | High | Terverifikasi |
| TTE-04 | Magic-link TTE | Mayor | Sedang | High | Terverifikasi |
| DB-01 | Unique Realisasi | Mayor | Sedang | High | Terverifikasi |
| DB-03 | Unique Utilitas | Mayor | Sedang | Medium | Terverifikasi |
| BE-02 | Exception ke User | Mayor | Sedang | Medium | Terverifikasi |
| ARCH-02 | God Controller | Mayor | Tinggi | High | Terverifikasi |
| ARCH-05 | God Model | Mayor | Sedang | Medium | Terverifikasi |
| ARCH-06 | Mass Assignment | Mayor | Sedang | Medium | Terverifikasi |
| ARCH-07 | Workflow State Ganda | Mayor | Sedang | Medium | Terverifikasi |
| ARCH-03, ARCH-04, ARCH-08, ARSIP-01, BE-03..07, DB-02,04,05,06,07,08, DI-04,05,06, DOC-01,02, FE-01,02, INT-06,08, MON-02, NUM-01,02, TTE-02, WF-05,07 | Lihat §E | Minor | Rendah–Sedang | Medium/Low | Terverifikasi |

---

## G. Analisis Dampak Bisnis

Dalam konteks keuangan bandara (PNBP/BLU), temuan ini bukan sekadar risiko teknis melainkan **kehancuran internal control dan segregation of duties** yang menjadi syarat pengelolaan keuangan negara:

1. **Pencairan tanpa persetujuan sah.** SEC-02, WF-01, WF-02, dan ARCH-01 secara kolektif memungkinkan pihak non-KPA atau pembuat tagihan fiktif meloloskan tagihannya sendiri, dan dokumen resmi ber-TTE diterbitkan tanpa verifikasi. **Risiko material temuan BPK**, membuka jalan fraud anggaran, dan menggugurkan keabsahan hukum dokumen pembayaran kontraktor.
2. **Kerugian negara langsung.** INT-01 + INT-02 memungkinkan tagihan PNBP ditandai LUNAS tanpa uang masuk, atau pencatatan penerimaan ganda — selisih antara fisik bank dan buku (DI-01, DI-07) menimbulkan saldo kas understated dan laporan pengesahan BLU yang tidak akurat.
3. **Kebocoran data & pelanggaran kepatuhan.** INF-01 (arsip sensitif publik), SEC-07 (IDOR SPP/SPM/NPI/SP2D), dan BE-01/SEC-03 (debug & cookie tidak aman) membocorkan nominal, rekening, NPWP, dan rincian pembayaran negara ke pihak/role tak berhak — potensi pelanggaran UU PDP.
4. **Hilangnya kemampuan forensik.** Audit trail rusak (catatan *'Auto-approved untuk testing'*, `kpa_approved_by` salah, `uploaded_by=null`, reset approval saat revisi — WF-06) menghapus jejak saat terjadi sengketa/dugaan penyimpangan.
5. **Risiko kehilangan data permanen.** BR-01 (tanpa backup/restore) berarti korupsi DB/ransomware/human error tidak dapat dipulihkan — kehilangan seluruh jejak penerimaan negara.

**Kesimpulan bisnis:** Sistem **tidak boleh** digunakan untuk transaksi keuangan riil sampai 4 Kritis kontrol-akses/SoD (SEC-02, WF-01, WF-02, ARCH-01) dan 4 Kritis pembayaran/integritas/infra (INT-01, INT-02, INF-01, BR-01) ditutup serta konfigurasi produksi di-hardening (BE-01/SEC-03/INF-02).

---

## H. Rekomendasi Perbaikan

**H.1 Kontrol Akses & Otorisasi (paling mendesak)**
- Tegakkan `role:KPA|PLT/PLH` pada route & method `processApproval`; pasang `signed` pada `kpa.approval.show`; hentikan auto-login sesi penuh via `user_id` (SEC-02, WF-02, SEC-01).
- Terapkan **Segregation of Duties** terpusat: tolak `actor == created_by` di semua jalur approval; jangan tugaskan pembuat sebagai approver (WF-01, WF-07).
- Hapus/guard `auto-approve` ke environment non-produksi (ARCH-01).
- Tambah `role:` internal pada cetak PDF pencairan; perbaiki catch-all `{any}` (SEC-07, SEC-06).

**H.2 Integrasi Pembayaran**
- Wajibkan callback secret + HMAC body + allowlist IP + anti-replay (INT-01, INT-04).
- Idempotency berbasis `external_reference` provider + `lockForUpdate`; akumulasi pembayaran (INT-02, DI-01).
- Rekonsiliasi balik (inquiry) ke BTN; validasi nominal/VA (INT-03). Jangan kirim password plaintext (INT-05). Aktifkan verifikasi TLS WA (INT-07).

**H.3 Integritas Data & Database**
- Kembalikan/ganti unique constraint keuangan dengan composite yang tepat (DB-01, DB-03, DB-08, DI-03, DI-04); FK nyata + unique untuk `transaksi_pembukuan` (DB-02).
- Akumulasi & presisi tetap untuk saldo; audit perubahan saldo (DI-05, DI-06). Pengikat unik pada rekonsiliasi piutang (DI-02). Satukan LUNAS+posting BKU dalam satu transaksi (DI-07).
- Tambah `updated_by`/soft delete pada tabel transaksi & detail (DB-05, DB-06); `enforceMorphMap` (DB-07).

**H.4 Dokumen & TTE**
- Bekukan PDF final ber-checksum saat fully-verified; masukkan `pdf_checksum` ke hash QR; jadikan hash sebagai kontrol akses (TTE-01, TTE-02).
- TTL + revoke untuk signed URL/QR; `expires_at` + throttle untuk magic-link TTE (TTE-03, TTE-04, SEC-04, SEC-05).
- Migrasikan penomoran sekunder ke `DocumentNumberService` ber-lock; ledger nomor turunan (NUM-01, NUM-02).

**H.5 Infrastruktur, Monitoring, Backup**
- Hardening `.env` produksi (BE-01, SEC-03, INF-02); isi `withExceptions`; pesan error generik (BE-02).
- Pindahkan arsip sensitif ke disk privat + route terproteksi (INF-01).
- Pasang `spatie/laravel-backup` + DR drill + dokumentasi RPO/RTO (BR-01, DOC-02); error tracking + alerting + uptime monitor (MON-01); `LOG_STACK=daily` (MON-02).

**H.6 Arsitektur & Kualitas Kode**
- Konsolidasi engine workflow (ARCH-03); ekstraksi god controller/model ke service (ARCH-02, ARCH-04, ARCH-05); Form Request terpusat (BE-04); `$fillable` eksplisit (ARCH-06, BE-03); satu sumber kebenaran status (ARCH-07, WF-04); putaran approval baru per revisi (WF-06); lock konkurensi approve (WF-03).

---

## I. Prioritas Perbaikan

**Critical (segera — sebelum transaksi riil):**
SEC-02, WF-01, WF-02, ARCH-01, INT-01, INT-02, BE-01, INF-01, TTE-01, BR-01.

**High:**
SEC-01, SEC-03, SEC-04, SEC-06, SEC-07, WF-03, WF-04, DI-01, DI-02, DI-07, INT-03, INT-04, INT-05, MON-01, INF-02, TTE-03, TTE-04, DB-01, ARCH-02.

**Medium:**
SEC-05, WF-06, DI-03, INT-07, BE-02, DB-03, ARCH-05, ARCH-06, ARCH-07, ARCH-03, ARCH-04, ARSIP-01, BE-03, BE-04, BE-05, BE-06, DB-02, DB-05, DB-07, DB-08, DI-06, DOC-02, INT-06, MON-02, NUM-01, NUM-02, WF-05, WF-07, FE-01.

**Low:**
ARCH-08, BE-07, DB-04, DB-06, DI-04, DI-05, DOC-01, FE-02, INT-08, TTE-02.

---

## J. Roadmap Perbaikan 30 Hari (Stabilkan & Hentikan Pendarahan)

> Sasaran: tutup seluruh Kritis + High keamanan/pembayaran; sistem aman untuk transaksi riil.

**Minggu 1 — Kontrol akses & konfigurasi (quick win berdampak tinggi)**
- Hardening `.env` produksi: `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE/ENCRYPT=true`, `config:cache` (BE-01, SEC-03, INF-02).
- Guard/hapus `auto-approve` (ARCH-01); pasang `role:` + `signed` pada jalur KPA (SEC-02, WF-02, SEC-01); `role:` pada cetak PDF pencairan (SEC-07).
- Isi `withExceptions` + pesan error generik (BE-01, BE-02).

**Minggu 2 — Pembayaran BTN VA**
- Wajibkan secret + HMAC + allowlist IP + anti-replay (INT-01, INT-04); idempotency + lock + akumulasi (INT-02, DI-01); hapus password plaintext (INT-05); aktifkan TLS WA (INT-07).

**Minggu 3 — Integritas & SoD**
- SoD terpusat (WF-01, WF-07); lock konkurensi approve (WF-03); satukan LUNAS+posting BKU (DI-07); pindahkan arsip sensitif ke disk privat + route terproteksi (INF-01).

**Minggu 4 — Backup & TTE dasar**
- Pasang `spatie/laravel-backup` + jadwal harian + uji restore (BR-01); bekukan PDF final ber-checksum + hash QR sebagai kontrol akses (TTE-01, TTE-02); `expires_at`+throttle magic-link (TTE-04).
- **Gate:** uji penetrasi ulang 10 Kritis → semua tertutup sebelum go-live transaksi riil.

---

## K. Roadmap Perbaikan 90 Hari (Perkuat Kontrol & Observability)

- **Database & integritas (bln 2):** kembalikan/ganti unique constraint keuangan (DB-01, DB-03, DB-08, DI-03, DI-04); FK + unique `transaksi_pembukuan` (DB-02); pengikat unik rekonsiliasi piutang (DI-02); `updated_by`/soft delete (DB-05, DB-06); `enforceMorphMap` (DB-07); presisi & audit saldo (DI-05, DI-06).
- **Dokumen & penomoran (bln 2-3):** TTL+revoke signed URL/QR (TTE-03, SEC-04, SEC-05); migrasi penomoran ke `DocumentNumberService` + ledger turunan (NUM-01, NUM-02); proteksi immutabilitas arsip final (ARSIP-01).
- **Observability (bln 2):** error tracking (Sentry) + alerting Slack/WA + uptime monitor `/up` + monitor `failed_jobs` (MON-01); `LOG_STACK=daily` + retensi (MON-02); redaksi log sensitif (INT-06).
- **Workflow & status (bln 3):** satu sumber kebenaran status (ARCH-07, WF-04); putaran approval baru per revisi (WF-06); perketat engine generik (WF-05).
- **Dokumentasi kepatuhan (bln 3):** runbook backup/restore/DR + retensi log (DOC-02); README SIKEREN (DOC-01).

## L. Roadmap Perbaikan 1 Tahun (Refactor & Tata Kelola Berkelanjutan)

- **Refactor arsitektur:** konsolidasi 5 engine workflow → `AbstractDocumentWorkflowService` (ARCH-03); ekstraksi god controller/model ke service + query objects (ARCH-02, ARCH-04, ARCH-05); Form Request menyeluruh + `$fillable` eksplisit (BE-03, BE-04, ARCH-06); interface + binding (ARCH-08); konvensi penamaan domain tunggal.
- **Ketahanan & skala:** pindahkan IO eksternal (WA/email) ke job `ShouldQueue` + worker termonitor (BE-05, BE-06); paginasi & caching laporan (BE-07, ARCH-04); presisi uang berbasis bcmath/integer sen lintas modul (DI-05).
- **Tata kelola:** DR drill triwulanan + uji restore terdokumentasi; SAST/dependency scanning di CI; code review wajib untuk perubahan jalur keuangan; audit log terpusat (blameable) untuk seluruh entitas keuangan; reviu hak akses berkala.
- **Kepatuhan:** sertakan kontrol SoD, audit trail, dan retensi sebagai bagian sertifikasi/penilaian kesiapan (alignment dengan ketentuan pengelolaan keuangan BLU/PNBP).

---

## M. Penilaian Akhir

### M.1 Skor per Kategori (berbobot)

| No | Kategori | Bobot | Skor | Skor × Bobot |
|---|---|---|---|---|
| 1 | Keamanan | 25% | 38 | 9,50 |
| 2 | Infrastruktur | 15% | 48 | 7,20 |
| 3 | Database | 15% | 55 | 8,25 |
| 4 | Backend | 15% | 40 | 6,00 |
| 5 | Frontend | 10% | 70 | 7,00 |
| 6 | Monitoring Jaringan | 10% | 58 | 5,80 |
| 7 | Backup & Recovery | 5% | 60 | 3,00 |
| 8 | Dokumentasi & Kepatuhan | 5% | 42 | 2,10 |
| | **TOTAL** | **100%** | | **48,85** |

**Perhitungan:** (38×0,25)+(48×0,15)+(55×0,15)+(40×0,15)+(70×0,10)+(58×0,10)+(60×0,05)+(42×0,05) = **48,85**

### M.2 Grade

| Rentang | Grade |
|---|---|
| 90–100 | A |
| 80–89 | B |
| 70–79 | C |
| 60–69 | D |
| **< 60** | **E ← SIKEREN (48,85)** |

### M.3 Kesimpulan Akhir

**Grade E (48,85/100). Sistem BELUM layak produksi untuk transaksi keuangan riil.**

SIKEREN memperlihatkan kematangan rekayasa pada lapisan data (skema `decimal` konsisten, FK, posting BKU transaksional) dan RBAC dasar yang rapi, sehingga **bukan sistem yang lemah secara menyeluruh**. Namun kelemahan terkonsentrasi tepat pada titik paling kritis sebuah sistem keuangan negara: **kontrol persetujuan (otorisasi), segregation of duties, dan integritas pembayaran**. Sepuluh temuan Kritis — terutama persetujuan KPA tanpa cek role, ketiadaan maker≠checker, endpoint auto-approve produksi, dan callback pembayaran tanpa autentikasi/idempotency — secara langsung mengancam keabsahan pencairan dan penerimaan negara.

Dengan menutup 10 Kritis pada Roadmap 30 Hari dan 19 High pada 90 Hari, perkiraan skor dapat naik ke kisaran **Grade C–B**. Prioritas absolut: **hentikan operasional transaksi riil hingga empat Kritis kontrol-akses/SoD (SEC-02, WF-01, WF-02, ARCH-01) dan empat Kritis pembayaran/infra (INT-01, INT-02, INF-01, BR-01) diperbaiki dan diverifikasi ulang.**

---

*Laporan ini dihasilkan melalui audit multi-agen berbasis risiko dengan verifikasi langsung terhadap kode sumber (routes, controllers, services, models, migrations, views, config). Seluruh 68 temuan berstatus Terverifikasi. Lokasi `file:line` merujuk kondisi kode per 22 Juni 2026.*
