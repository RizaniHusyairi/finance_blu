# Implementation Plan — AI Insight pada Penagihan Jasa (Role Admin Jasa)

Dokumen perencanaan untuk menambahkan **AI Insight** pada modul Penagihan Jasa.
Status: **Draft untuk review** · Disusun: 2026-06-14

---

## 1. Ringkasan & Keputusan

| Aspek | Keputusan |
|---|---|
| **Perlu AI Insight?** | **Ya**, tetapi hanya untuk **Admin Jasa**. Untuk Mitra **tidak** (lihat §3). |
| **Pendekatan** | **Bertahap**: rule-based dulu (Fase 0) → LLM Claude bertoggle (Fase 1). |
| **Provider LLM** | **Claude API** — `claude-haiku-4-5` (default, hemat) atau `claude-opus-4-8` (kualitas tinggi). |
| **Kontrol** | Feature toggle via `IntegrationSetting` (`ai_insight.enabled`), caching harian, async load. |
| **Prinsip** | LLM hanya menarasikan data **yang sudah diagregasi**; angka resmi tetap dari rule-based, bukan dari AI. |

> **Catatan penting hasil telusur kode:** belum ada integrasi AI/LLM apa pun di aplikasi.
> Kelas `.insight-card` di `resources/views/admin_jasa/dashboard.blade.php` hanyalah **nama CSS** untuk kartu statis — bukan fitur AI. Jadi ini kapabilitas baru dari nol.

---

## 2. Latar Belakang & Modal Data

Modul penagihan jasa sudah memiliki data terstruktur yang kaya — fondasi yang baik untuk insight berkualitas.

**Sumber data utama (sudah ada):**
- `App\Services\AdminJasaDashboardService` — menyediakan `getSummaryCards`, `getVerificationSummary`, `getMitraSummary`, `getOverdueTagihan`, `getChartTagihanBulanan`, `getPersentaseLunas`, dll. (lihat `app/Http/Controllers/AdminJasaDashboardController.php`).
- Model `TagihanJasa` — nominal, jatuh tempo, sisa tagihan, status pembayaran, hari terlambat, denda.
- `IntegrationSetting` — pola toggle + penyimpanan **terenkripsi** (`is_encrypted` + `Crypt`), sudah dipakai untuk `btn.enabled`. Cocok untuk menyimpan API key & flag AI.
- `WhatsappService` & `EmailNotificationService` — bisa disambung untuk fitur "draft pesan penagihan" (nilai tambah opsional).

**Titik integrasi UI:** panel insight baru disisipkan di `admin_jasa/dashboard.blade.php`, memakai ulang style `.insight-card` yang sudah ada (tanpa CSS baru berarti).

---

## 3. Lingkup

### Termasuk
- AI Insight untuk **dashboard Admin Jasa**: ringkasan naratif + rekomendasi tindakan prioritas berbasis data penagihan.

### Tidak Termasuk (dan alasannya)
- **Mitra (portal eksternal): TIDAK.** Alasan:
  - Nilai keputusan rendah (mitra hanya melihat tagihannya).
  - **Risiko privasi & halusinasi** — AI salah menarasikan kewajiban finansial pihak eksternal berisiko hukum/reputasi.
  - Biaya per-mitra menumpuk tanpa imbal hasil sepadan.
  - *Bila nanti dibutuhkan*, cukup ringkasan **deterministik non-AI** (Fase 2 opsional), bukan LLM.
- Role lain (Koordinator/KPA/Super Admin Jasa) — di luar lingkup permintaan ini.

---

## 4. Arsitektur Teknis

```
AdminJasaDashboardController::index()
        │
        ├─ AdminJasaDashboardService          (data agregat — SUDAH ADA)
        │       └─ getInsightContext()        (BARU: rangkum metrik jadi payload ringkas)
        │
        └─ JasaInsightService                 (BARU — orkestrator)
                ├─ buildRuleBasedInsights()   (Fase 0 — selalu jalan, jadi fallback)
                └─ AiInsightService           (Fase 1 — opsional, di balik toggle)
                        └─ Claude API (cached harian per-scope)
```

**Prinsip desain kunci:**
1. **Rule-based selalu jalan.** AI hanya *lapisan di atas*. Jika AI mati/error/timeout → tampilkan insight rule-based. Tidak pernah ada layar kosong.
2. **AI tidak menghitung.** Semua angka (overdue, nominal, %) dihitung rule-based. LLM hanya merangkai narasi + prioritas dari angka tersebut → mencegah halusinasi numerik.
3. **Async & cached.** Panel insight di-load via AJAX terpisah agar tidak memperlambat dashboard; hasil AI di-cache (mis. harian per kombinasi admin+filter) untuk menekan biaya.
4. **Bertoggle.** `ai_insight.enabled = false` → sistem berperilaku persis Fase 0.

---

## 5. Rencana per Fase

### Fase 0 — Smart Insight (rule-based, tanpa LLM)
**Tujuan:** insight & rekomendasi langsung berguna, nol biaya, sekaligus fondasi/fallback.

Logika contoh (ambang batas dari data yang sudah ada):
- Overdue: "N tagihan lewat jatuh tempo senilai Rp X — total terlambat rata-rata D hari."
- Due-soon: "M tagihan jatuh tempo ≤7 hari — prioritaskan follow-up."
- Mitra berisiko: mitra dengan ≥2 tagihan terlambat → tandai "perlu perhatian".
- Tren: bandingkan nominal bulan ini vs bulan lalu → naik/turun X%.
- Collection rate: `persentaseLunas` di bawah ambang → peringatan.

Output: array `['level' => 'info|warning|danger', 'title', 'message', 'action_url']`.

### Fase 1 — AI Insight (LLM Claude, bertoggle)
**Tujuan:** narasi natural Bahasa Indonesia + rekomendasi tindakan yang lebi  h kontekstual.

Alur:
1. `getInsightContext()` menyusun payload ringkas (metrik + top mitra/layanan + tren — **bukan** raw row, untuk hemat token & privasi).
2. `AiInsightService` mengirim payload ke Claude dengan prompt sistem yang membatasi: "Gunakan hanya angka yang diberikan; jangan mengarang nominal; keluarkan 3–5 insight + rekomendasi tindakan."
3. Respons di-*parse* jadi struktur yang sama dengan Fase 0 → UI seragam.
4. Hasil di-cache (key = admin id + hash filter + tanggal). Tombol "Perbarui" untuk regen manual.
5. Toggle & API key dibaca dari `IntegrationSetting`.

*Nilai tambah opsional:* tombol "Draft pesan penagihan" → AI menyusun draf WA/email sopan untuk mitra overdue, disambung ke `WhatsappService`/`EmailNotificationService` (tetap perlu klik kirim manual).

### Fase 2 — Ringkasan Mitra (opsional, rule-based saja)
Hanya jika diminta kemudian: ringkasan deterministik "X tagihan belum dibayar, Y jatuh tempo minggu ini" di portal mitra. **Tanpa LLM.**

---

## 6. Daftar File (Buat / Ubah)

| File | Aksi | Keterangan |
|---|---|---|
| `app/Services/JasaInsightService.php` | **Buat** | Orkestrator: rule-based + (opsional) panggil AI. |
| `app/Services/AiInsightService.php` | **Buat** (Fase 1) | Klien Claude API + prompt + parsing + cache. |
| `app/Services/AdminJasaDashboardService.php` | **Ubah** | Tambah `getInsightContext($admin, $filters)`. |
| `app/Http/Controllers/AdminJasaDashboardController.php` | **Ubah** | Endpoint async `insight()` + kirim insight rule-based awal ke view. |
| `routes/web.php` | **Ubah** | Route `admin-jasa.dashboard.insight` (role: Admin Jasa). |
| `resources/views/admin_jasa/dashboard.blade.php` | **Ubah** | Panel `.insight-card` baru + JS fetch async. |
| `resources/views/admin_jasa/_partials/ai-insight.blade.php` | **Buat** | Partial render daftar insight (dipakai async response). |
| `config/services.php` | **Ubah** (Fase 1) | Blok config `claude` (key, model, max_tokens). |
| `.env` / `.env.example` | **Ubah** (Fase 1) | `ANTHROPIC_API_KEY`, `AI_INSIGHT_MODEL`. |
| `app/Models/IntegrationSetting` (seed/UI) | **Ubah** (Fase 1) | Flag `ai_insight.enabled` + key terenkripsi (opsional via UI integrasi). |

---

## 7. Konfigurasi & Keamanan

- **API key** disimpan via `IntegrationSetting::setValue('ai_insight.api_key', $key, 'ai', encrypted: true)` (memakai enkripsi `Crypt` yang sudah ada) **atau** `.env` (`ANTHROPIC_API_KEY`). Tidak pernah di-commit, tidak pernah dikirim ke frontend.
- **Toggle**: `IntegrationSetting::getValue('ai_insight.enabled', false)` — default mati.
- **Data minimal**: hanya metrik agregat & nama mitra yang dikirim ke LLM; hindari NPWP, kontak, nomor VA, dan data pribadi tak perlu.
- **Scope**: insight menghormati `getAllowedItemIds($admin)` — Admin Jasa hanya melihat insight untuk layanan/mitra yang menjadi tanggung jawabnya.
- **Timeout & fallback**: panggilan AI dibungkus try/catch + timeout; gagal → rule-based.

---

## 8. Biaya & Kontrol

- **Fase 0 = Rp 0** (tanpa LLM).
- **Fase 1**: biaya per panggilan kecil (payload ringkas), ditekan lewat:
  - Cache harian per-scope (1 generate/hari/admin/filter, bukan tiap buka halaman).
  - Model default hemat (`claude-haiku-4-5`); naikkan ke Opus hanya bila kualitas dibutuhkan.
  - Toggle global untuk mematikan total bila perlu.

---

## 9. Risiko & Mitigasi

| Risiko | Mitigasi |
|---|---|
| Halusinasi angka oleh LLM | LLM tidak menghitung; angka dari rule-based; prompt melarang mengarang nominal. |
| AI down / lambat | Fallback rule-based + async load + timeout. |
| Biaya membengkak | Cache harian + payload ringkas + toggle + model hemat. |
| Kebocoran data sensitif | Kirim hanya agregat; key terenkripsi; tanpa data pribadi. |
| Ketergantungan vendor | Abstraksi `AiInsightService`; Fase 0 tetap berfungsi penuh tanpa vendor. |

---

## 10. Estimasi & Urutan Kerja

1. **Fase 0** (~1–2 hari): `JasaInsightService` rule-based + `getInsightContext` + panel UI + route async.
2. **Fase 1** (~2–3 hari): `AiInsightService` + config Claude + toggle + cache + parsing + fallback.
3. **Fase 2** (opsional, ~0.5 hari): ringkasan mitra rule-based — hanya jika diminta.

---

## 11. Kriteria Selesai (Acceptance)

- [ ] Dashboard Admin Jasa menampilkan panel insight (rule-based) tanpa memperlambat load halaman.
- [ ] Insight menghormati scope layanan/mitra milik Admin Jasa yang login.
- [ ] Saat `ai_insight.enabled = true` & key valid: muncul narasi AI; saat mati/error: otomatis fallback rule-based, tanpa layar kosong/error.
- [ ] Tidak ada API key atau data pribadi yang terekspos ke frontend.
- [ ] Hasil AI ter-cache (tidak regen tiap refresh) + ada tombol perbarui manual.

---

## 12. Keputusan yang Masih Terbuka (untuk review)

1. **Sumber API key**: `.env` saja, atau lewat UI Integrasi Super Admin (pakai `IntegrationSetting` terenkripsi seperti BTN)?
2. **Model default**: `claude-haiku-4-5` (hemat) vs `claude-opus-4-8` (kualitas)?
3. **Fitur "Draft pesan penagihan" WA/email**: masuk Fase 1 atau ditunda?
4. **Frekuensi cache**: harian sudah cukup, atau perlu refresh per beberapa jam?
