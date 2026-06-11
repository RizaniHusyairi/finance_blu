const fs = require('fs');
const path = require('path');
const { pathToFileURL } = require('url');
const { execFileSync } = require('child_process');
const puppeteer = require('puppeteer');

const ROOT = path.resolve(__dirname, '..');
const BASE_URL = 'http://127.0.0.1:8000';
const OUTPUT_DIR = path.join(__dirname, 'output');
const SCREENSHOT_DIR = path.join(OUTPUT_DIR, 'screenshots');
const HTML_PATH = path.join(OUTPUT_DIR, 'brosur_sikeren_blu.html');
const PDF_PATH = path.join(ROOT, 'brosur_sikeren_blu_finance_aptp.pdf');
const COVER_PREVIEW_PATH = path.join(OUTPUT_DIR, 'cover_preview.png');
const LOGO_PATH = path.join(ROOT, 'public', 'logo', 'minilogo-sikeren.png');

const loginUser = {
  email: 'super.admin@sikeren.id',
  password: 'password',
};

const mitraLoginUser = {
  email: 'mitra.demo.brosur@sikeren.id',
  password: 'password',
};

const features = [
  {
    no: '01',
    title: 'Dashboard Keuangan BLU',
    eyebrow: 'Executive Monitoring',
    path: '/dashboard',
    accent: '#0f766e',
    summary: 'Ringkasan pagu, realisasi, sisa anggaran, kontrak aktif, tagihan, dan grafik serapan dalam satu tampilan pimpinan.',
    bullets: ['KPI keuangan real-time', 'Grafik serapan per COA', 'Status pekerjaan dan tagihan'],
  },
  {
    no: '02',
    title: 'Dashboard Jasa',
    eyebrow: 'Revenue & Service Control',
    path: '/super-admin-jasa/dashboard',
    accent: '#2563eb',
    summary: 'Monitoring layanan jasa BLU, tagihan, piutang, performa pembayaran, dan kalender jatuh tempo untuk area komersial.',
    bullets: ['Pantau tagihan jasa', 'Analitik piutang dan pembayaran', 'Kalender operasional'],
  },
  {
    no: '03',
    title: 'Kelola Mitra & Layanan',
    eyebrow: 'Partner Administration',
    path: '/jasa/mitra',
    accent: '#7c3aed',
    summary: 'Pengelolaan mitra jasa, kontrak layanan, akun portal mitra, serta layanan aktif yang menjadi dasar penagihan.',
    bullets: ['Data mitra terpusat', 'Akun portal mitra', 'Layanan dan kontrak jasa'],
  },
  {
    no: '04',
    title: 'Dashboard Mitra Jasa',
    eyebrow: 'Partner Portal',
    path: '/mitra',
    auth: 'mitra',
    accent: '#0369a1',
    summary: 'Portal mitra untuk memantau kontrak jasa, layanan aktif, status tagihan, nilai tertagih, dan riwayat pembayaran dari sisi mitra.',
    bullets: ['Ringkasan kontrak dan tagihan', 'Layanan aktif mitra', 'Akses detail invoice'],
  },
  {
    no: '05',
    title: 'Buat Tagihan Jasa',
    eyebrow: 'Invoice Creation',
    path: '/tagihan-jasa/create',
    accent: '#f97316',
    summary: 'Form pembuatan tagihan jasa untuk memilih mitra, kontrak, periode tagihan, layanan, nilai bruto, pajak, dan dokumen pengantar.',
    bullets: ['Pilih mitra dan kontrak', 'Input rincian layanan', 'Hitung nilai tagihan'],
  },
  {
    no: '06',
    title: 'Log Tagihan Bulanan',
    eyebrow: 'Billing Operation',
    path: '/admin-jasa/tagihan/log-bulanan',
    accent: '#ea580c',
    summary: 'Log penerbitan tagihan bulanan untuk konsesi, PJP2U, utilitas, dan layanan jasa lain dengan status yang mudah dipantau.',
    bullets: ['Riwayat tagihan bulanan', 'Monitoring jatuh tempo', 'Nomor dan status tagihan'],
  },
  {
    no: '07',
    title: 'Manajemen Kontrak',
    eyebrow: 'Procurement Contract',
    path: '/contracts',
    accent: '#1d4ed8',
    summary: 'Pengelolaan kontrak, termin pembayaran, dokumen SPK/SPMK, addendum, verifikasi, dan arsip final secara digital.',
    bullets: ['Kontrak dan termin', 'Approval dan revisi', 'Arsip dokumen final'],
  },
  {
    no: '07',
    title: 'Form Input Kontrak',
    eyebrow: 'Contract Entry Form',
    path: '/contracts/create',
    accent: '#4338ca',
    summary: 'Tampilan form pembuatan kontrak untuk mengisi data pekerjaan, vendor, nilai kontrak, tanggal, termin, dan dokumen pendukung.',
    bullets: ['Input informasi kontrak', 'Vendor dan nilai pekerjaan', 'Termin serta dokumen awal'],
  },
  {
    no: '08',
    title: 'Perjalanan Dinas',
    eyebrow: 'Perjaldin Workflow',
    path: '/perjaldins',
    accent: '#0891b2',
    summary: 'Alur perjalanan dinas dari input peserta, komponen biaya, dokumen nominatif, sampai verifikasi berjenjang.',
    bullets: ['Komponen biaya terstruktur', 'Nominatif dan lampiran', 'Workflow verifikasi'],
  },
  {
    no: '09',
    title: 'Form Input Perjaldin',
    eyebrow: 'Travel Assignment Form',
    path: '/perjaldins/create',
    accent: '#0e7490',
    summary: 'Tampilan form perjalanan dinas untuk merekam kegiatan, peserta, tujuan, tanggal, komponen biaya, dan dasar penugasan.',
    bullets: ['Data kegiatan dan tujuan', 'Peserta perjalanan dinas', 'Komponen biaya perjalanan'],
  },
  {
    no: '10',
    title: 'Honorarium',
    eyebrow: 'Honor Payment',
    path: '/honorarium',
    accent: '#be123c',
    summary: 'Pengelolaan data honorarium, daftar penerima, dokumen pendukung, pajak, dan pengajuan menuju proses pembayaran.',
    bullets: ['Daftar penerima honor', 'Dokumen dan nominatif', 'Pajak dan pengajuan'],
  },
  {
    no: '11',
    title: 'Form Input Honorarium',
    eyebrow: 'Honorarium Entry Form',
    path: '/honorarium/create',
    accent: '#be185d',
    summary: 'Tampilan form honorarium untuk membuat pengajuan, memilih kegiatan, mengisi penerima, nominal, pajak, dan dokumen pendukung.',
    bullets: ['Input kegiatan honorarium', 'Penerima dan nominal', 'Dokumen dan pajak'],
  },
  {
    no: '12',
    title: 'Pembuatan SPP',
    eyebrow: 'Payment Request',
    path: '/spps/perjaldin',
    accent: '#4f46e5',
    summary: 'Pembuatan Surat Permintaan Pembayaran untuk kontrak, perjalanan dinas, dan honorarium dengan status dokumen yang transparan.',
    bullets: ['SPP lintas jenis tagihan', 'Cetak PDF', 'Upload dokumen bertanda tangan'],
  },
  {
    no: '13',
    title: 'SPM, NPI & SP2D',
    eyebrow: 'Treasury Pipeline',
    path: '/npis/kontrak',
    accent: '#047857',
    summary: 'Rantai dokumen pembayaran dari SPM, Nota Pencairan Internal, hingga pencatatan SP2D dan bukti transfer.',
    bullets: ['SPM final ke NPI', 'Pencatatan SP2D', 'Audit status pembayaran'],
  },
  {
    no: '14',
    title: 'Pembukuan & Rekonsiliasi',
    eyebrow: 'Accounting Books',
    path: '/pembukuan/bku',
    accent: '#334155',
    summary: 'Buku Kas Umum, buku pembantu, bank, pajak, bunga rekening, pengesahan belanja, serta pengecekan piutang.',
    bullets: ['BKU dan buku pembantu', 'Rekonsiliasi bank', 'Export PDF/Excel'],
  },
  {
    no: '15',
    title: 'Laporan & Analitik',
    eyebrow: 'Management Reporting',
    path: '/super-admin-jasa/laporan/rekap-tagihan',
    accent: '#ca8a04',
    summary: 'Rekap tagihan, layanan, pembayaran, piutang, terima-setor, dan performa mitra untuk kebutuhan monitoring manajemen.',
    bullets: ['Rekap multi-dimensi', 'Filter periode', 'Export laporan'],
  },
];

features.forEach((feature, index) => {
  feature.no = String(index + 1).padStart(2, '0');
});

function fileUrl(filePath) {
  return pathToFileURL(filePath).href;
}

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function ensureBrochureMitraAccount() {
  const phpCode = String.raw`
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

\Illuminate\Support\Facades\DB::transaction(function () {
    \Spatie\Permission\Models\Role::findOrCreate('Mitra Jasa', 'web');

    $admin = \App\Models\User::where('email', 'super.admin@sikeren.id')->first();
    $createdBy = $admin?->id;

    $mitra = \App\Models\MitraJasa::withTrashed()
        ->where('email', 'mitra.demo.brosur@sikeren.id')
        ->first();

    if (! $mitra) {
        $mitra = new \App\Models\MitraJasa();
    }

    if (method_exists($mitra, 'trashed') && $mitra->trashed()) {
        $mitra->restore();
    }

    $mitra->fill([
        'kode_mitra' => 'BRO-MITRA-001',
        'nama_mitra' => 'PT Demo Mitra Jasa Bandara',
        'jenis_mitra' => 'KONSESI',
        'npwp' => '01.234.567.8-999.000',
        'email' => 'mitra.demo.brosur@sikeren.id',
        'no_telepon' => '081234567890',
        'alamat' => 'Area Komersial Bandar Udara APT Pranoto',
        'nama_penanggung_jawab' => 'Dewi Anggraini',
        'jabatan_penanggung_jawab' => 'Finance Manager',
        'status_aktif' => true,
        'created_by' => $createdBy,
        'updated_by' => $createdBy,
    ]);
    $mitra->save();

    $user = \App\Models\User::where('email', 'mitra.demo.brosur@sikeren.id')->first();
    if (! $user) {
        $user = new \App\Models\User(['email' => 'mitra.demo.brosur@sikeren.id']);
    }

    $user->forceFill([
        'email' => 'mitra.demo.brosur@sikeren.id',
        'password' => \Illuminate\Support\Facades\Hash::make('password'),
        'email_verified_at' => now(),
        'is_active' => true,
        'active_from' => null,
        'active_until' => null,
        'disabled_at' => null,
        'profilable_type' => \App\Models\MitraJasa::class,
        'profilable_id' => $mitra->id,
    ])->save();
    $user->syncRoles(['Mitra Jasa']);

    $demoLayanan = \App\Models\LayananJasa::withTrashed()
        ->where('kode_layanan', 'BR-DMO-001')
        ->first();
    if (! $demoLayanan) {
        $demoLayanan = new \App\Models\LayananJasa();
    }

    $demoLayanan->forceFill([
        'kode_layanan' => 'BR-DMO-001',
        'nama_layanan' => 'Layanan Komersial Demo Brosur',
        'tarif_dasar' => 68500000,
        'satuan' => 'Paket',
        'is_active' => true,
        'is_leaf' => true,
        'level' => 1,
        'tipe_layanan' => 'PNBP',
        'mendukung_konsesi' => false,
        'jumlah_hari_jatuh_tempo' => 30,
        'masa_toleransi_hari' => 0,
        'wajib_tagihan_terpisah' => false,
        'deleted_at' => null,
    ])->save();

    $layananIds = \App\Models\LayananJasa::query()
        ->where('is_active', true)
        ->where('is_leaf', true)
        ->where('tipe_layanan', 'PNBP')
        ->orderByRaw("CASE WHEN kode_layanan = 'BR-DMO-001' THEN 0 ELSE 1 END")
        ->orderBy('level')
        ->orderBy('id')
        ->limit(3)
        ->pluck('id')
        ->all();

    if (! in_array($demoLayanan->id, $layananIds, true)) {
        array_unshift($layananIds, $demoLayanan->id);
        $layananIds = array_slice(array_values(array_unique($layananIds)), 0, 3);
    }

    if ($layananIds !== []) {
        $syncPayload = [];
        foreach ($layananIds as $layananId) {
            $syncPayload[$layananId] = [
                'status_aktif' => true,
                'tanggal_mulai' => now()->subMonth()->toDateString(),
                'tanggal_selesai' => now()->addYear()->toDateString(),
                'keterangan' => 'Layanan demo untuk brosur',
                'created_by' => $createdBy,
            ];
        }
        $mitra->layananJasa()->syncWithoutDetaching($syncPayload);
    }

    $kontrak = \App\Models\KontrakMitraJasa::updateOrCreate(
        [
            'mitra_jasa_id' => $mitra->id,
            'nomor_kontrak' => 'KMJ/BROSUR/001/2026',
        ],
        [
            'nama_kontrak' => 'Kerja Sama Layanan Komersial Bandara',
            'jenis_dokumen' => 'KONTRAK',
            'tanggal_kontrak' => now()->subMonths(2)->toDateString(),
            'tanggal_mulai' => now()->subMonth()->toDateString(),
            'tanggal_selesai' => now()->addYear()->toDateString(),
            'nilai_kontrak' => 875000000,
            'status_kontrak' => 'AKTIF',
            'keterangan' => 'Kontrak demo untuk dashboard mitra jasa pada brosur.',
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
        ]
    );

    if ($layananIds !== [] && \Illuminate\Support\Facades\Schema::hasTable('kontrak_mitra_jasa_layanan')) {
        foreach ($layananIds as $layananId) {
            \Illuminate\Support\Facades\DB::table('kontrak_mitra_jasa_layanan')->updateOrInsert(
                [
                    'kontrak_mitra_jasa_id' => $kontrak->id,
                    'layanan_jasa_id' => $layananId,
                ],
                [
                    'created_by' => $createdBy,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    $tagihan = \App\Models\TagihanJasa::updateOrCreate(
        ['nomor_tagihan' => 'INV-BROSUR-MITRA-001'],
        [
            'mitra_id' => null,
            'mitra_jasa_id' => $mitra->id,
            'kontrak_mitra_jasa_id' => $kontrak->id,
            'nomor_kontrak' => $kontrak->nomor_kontrak,
            'tanggal_mulai_kontrak' => $kontrak->tanggal_mulai,
            'tanggal_selesai_kontrak' => $kontrak->tanggal_selesai,
            'tanggal_tagihan' => now()->toDateString(),
            'tanggal_publish' => now()->toDateString(),
            'tanggal_jatuh_tempo' => now()->addDays(23)->toDateString(),
            'total_tagihan' => 68500000,
            'jumlah_dibayar' => 0,
            'sisa_tagihan' => 68500000,
            'status' => 'PUBLISHED',
            'status_pembayaran' => 'belum_lunas',
            'nomor_va' => '88081234567890',
            'created_by' => $createdBy ?? $user->id,
        ]
    );

    if ($layananIds !== []) {
        \App\Models\TagihanJasaDetail::updateOrCreate(
            [
                'tagihan_jasa_id' => $tagihan->id,
                'layanan_jasa_id' => $layananIds[0],
            ],
            [
                'qty' => 1,
                'harga_satuan' => 68500000,
                'subtotal' => 68500000,
            ]
        );
    }
});
`;

  execFileSync('php', ['-r', phpCode], {
    cwd: ROOT,
    stdio: 'inherit',
  });
}

async function clearSession(page) {
  const client = await page.createCDPSession();
  await client.send('Network.clearBrowserCookies');
  await client.send('Network.clearBrowserCache');
}

async function login(page, credentials) {
  await page.goto(`${BASE_URL}/login`, { waitUntil: 'domcontentloaded', timeout: 30000 });
  await page.evaluate(() => {
    document.querySelector('.lg-wrap')?.classList.add('is-open');
    document.querySelector('.lg-wrap')?.classList.remove('is-taking-off');
  }).catch(() => {});
  await sleep(500);
  await page.waitForSelector('input[name="email"]', { visible: true, timeout: 20000 });
  await page.$eval('input[name="email"]', (input) => { input.value = ''; });
  await page.$eval('input[name="password"]', (input) => { input.value = ''; });
  await page.type('input[name="email"]', credentials.email);
  await page.type('input[name="password"]', credentials.password);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }).catch(() => {}),
    page.click('button[type="submit"]'),
  ]);
  await sleep(1000);

  if (new URL(page.url()).pathname.includes('/login')) {
    const text = await page.$eval('body', (body) => body.innerText.slice(0, 800)).catch(() => '');
    throw new Error(`Login failed for ${credentials.email}: ${text}`);
  }
}

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

async function settlePage(page) {
  await page.evaluate(() => {
    window.scrollTo(0, 0);
    localStorage.setItem('maxton-theme', 'semi-dark');
    document.documentElement.setAttribute('data-bs-theme', 'semi-dark');
  }).catch(() => {});
  await page.addStyleTag({
    content: `
      .pace, .pace-activity, .pace-progress { display: none !important; }
      .main-content { min-height: 920px !important; }
      .dropdown-menu.show { display: none !important; }
      .modal-backdrop { display: none !important; }
      .sky-alert-stack, .sky-alert-overlay, .sky-alert, .toast-container, .toast { display: none !important; }
      button[data-bs-target="#staticBackdrop"], #staticBackdrop, .offcanvas-backdrop { display: none !important; }
      body { background: #eef5ff !important; }
    `,
  }).catch(() => {});
  await page.evaluateHandle('document.fonts ? document.fonts.ready : Promise.resolve()').catch(() => {});
  await sleep(900);
}

async function captureFeature(page, feature) {
  const url = `${BASE_URL}${feature.path}`;
  const target = path.join(SCREENSHOT_DIR, `${feature.no}_${feature.title.toLowerCase().replace(/[^a-z0-9]+/g, '_')}.jpg`);
  const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 45000 });
  const status = response ? response.status() : 0;

  if (status < 200 || status >= 400) {
    throw new Error(`Route ${feature.path} returned HTTP ${status}`);
  }

  await settlePage(page);
  await page.screenshot({
    path: target,
    type: 'jpeg',
    quality: 86,
    clip: { x: 0, y: 0, width: 1440, height: 1000 },
  });

  feature.imagePath = target;
  feature.imageUrl = fileUrl(target);
  feature.status = status;
}

async function createCapturePage(browser) {
  const page = await browser.newPage();
  page.setDefaultTimeout(30000);
  await page.setViewport({ width: 1440, height: 1000, deviceScaleFactor: 1 });
  await page.evaluateOnNewDocument(() => {
    localStorage.setItem('maxton-theme', 'semi-dark');
    document.documentElement.setAttribute('data-bs-theme', 'semi-dark');
  });
  await page.setRequestInterception(true);
  page.on('request', (request) => {
    if (request.url().startsWith('https://assets.mixkit.co/')) {
      request.abort();
      return;
    }
    request.continue();
  });

  return page;
}

function statCard(value, label) {
  return `
    <div class="stat">
      <div class="stat-value">${escapeHtml(value)}</div>
      <div class="stat-label">${escapeHtml(label)}</div>
    </div>
  `;
}

function featureCard(feature) {
  return `
    <article class="overview-card" style="--accent:${feature.accent}">
      <div class="overview-no">${feature.no}</div>
      <h3>${escapeHtml(feature.title)}</h3>
      <p>${escapeHtml(feature.summary)}</p>
    </article>
  `;
}

function featurePage(feature) {
  return `
    <section class="page feature-page" style="--accent:${feature.accent}">
      <div class="feature-copy">
        <div class="kicker">${escapeHtml(feature.eyebrow)}</div>
        <div class="feature-no">${feature.no}</div>
        <h2>${escapeHtml(feature.title)}</h2>
        <p class="summary">${escapeHtml(feature.summary)}</p>
        <ul>
          ${feature.bullets.map((item) => `<li>${escapeHtml(item)}</li>`).join('')}
        </ul>
        <div class="route-chip">${escapeHtml(feature.path)}</div>
      </div>
      <div class="screen-wrap">
        <div class="browser-bar">
          <span></span><span></span><span></span>
          <strong>${escapeHtml(BASE_URL + feature.path)}</strong>
        </div>
        <img src="${feature.imageUrl}" alt="${escapeHtml(feature.title)}">
      </div>
    </section>
  `;
}

function buildHtml() {
  const logoUrl = fileUrl(LOGO_PATH);

  return `<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Brosur SIKEREN-BLU Finance APTP</title>
  <style>
    @page { size: A4 landscape; margin: 0; }
    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; color: #0f172a; background: #ffffff; }
    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .page {
      width: 297mm;
      height: 210mm;
      position: relative;
      overflow: hidden;
      page-break-after: always;
      background: #f8fafc;
    }
    .page:last-child { page-break-after: auto; }
    .cover {
      display: grid;
      grid-template-columns: 1.03fr .97fr;
      gap: 28px;
      padding: 23mm 20mm 18mm;
      color: #fff;
      background:
        linear-gradient(130deg, rgba(15, 23, 42, .96), rgba(14, 116, 144, .9) 56%, rgba(21, 94, 117, .96)),
        linear-gradient(45deg, #0f172a, #0e7490);
    }
    .cover::before {
      content: "";
      position: absolute;
      inset: 0;
      background-image:
        linear-gradient(rgba(255,255,255,.075) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.075) 1px, transparent 1px);
      background-size: 17mm 17mm;
      opacity: .55;
    }
    .cover-main, .cover-side, .overview-inner, .closing-inner { position: relative; z-index: 1; }
    .brand-lockup { display: flex; align-items: center; gap: 15px; margin-bottom: 24px; }
    .brand-lockup img { width: 58px; height: 58px; object-fit: contain; border-radius: 16px; background: #fff; padding: 7px; }
    .brand-title { font-weight: 900; letter-spacing: .12em; font-size: 13px; text-transform: uppercase; color: #bae6fd; }
    .brand-sub { margin-top: 4px; color: rgba(255,255,255,.78); font-size: 12px; }
    h1 { margin: 0; font-size: 55px; line-height: .96; letter-spacing: 0; font-weight: 900; }
    .cover-lead { margin: 22px 0 0; max-width: 580px; color: rgba(255,255,255,.84); font-size: 18px; line-height: 1.52; }
    .cover-meta { display: flex; gap: 10px; margin-top: 26px; flex-wrap: wrap; }
    .pill { padding: 9px 12px; border: 1px solid rgba(255,255,255,.22); border-radius: 999px; background: rgba(255,255,255,.1); color: #ecfeff; font-weight: 800; font-size: 12px; }
    .cover-side { align-self: end; display: grid; gap: 12px; }
    .hero-shot { border: 1px solid rgba(255,255,255,.2); border-radius: 18px; overflow: hidden; box-shadow: 0 22px 70px rgba(2, 8, 23, .44); background: #fff; transform: rotate(-1.2deg); }
    .hero-shot img { width: 100%; display: block; }
    .cover-note { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
    .stat { min-height: 88px; padding: 15px; border-radius: 16px; background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.16); backdrop-filter: blur(8px); }
    .stat-value { font-size: 21px; font-weight: 900; color: #fef3c7; white-space: nowrap; }
    .stat-label { margin-top: 6px; font-size: 11px; line-height: 1.32; color: rgba(255,255,255,.78); }

    .overview {
      padding: 17mm 17mm 14mm;
      background:
        linear-gradient(90deg, #f8fafc, #ecfeff 46%, #f8fafc),
        #f8fafc;
    }
    .section-label { color: #0891b2; font-size: 12px; font-weight: 900; letter-spacing: .14em; text-transform: uppercase; }
    .overview h2, .closing h2 { margin: 8px 0 10px; font-size: 35px; line-height: 1.08; letter-spacing: 0; }
    .overview-copy { width: 74%; color: #475569; font-size: 15px; line-height: 1.55; margin-bottom: 17px; }
    .overview-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 11px; }
    .overview-card { position: relative; min-height: 106px; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; box-shadow: 0 10px 26px rgba(15, 23, 42, .06); overflow: hidden; }
    .overview-card::before { content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: var(--accent); }
    .overview-no { color: var(--accent); font-weight: 900; font-size: 11px; letter-spacing: .1em; }
    .overview-card h3 { margin: 8px 0 6px; font-size: 16px; line-height: 1.18; }
    .overview-card p { margin: 0; font-size: 11px; line-height: 1.42; color: #64748b; }

    .feature-page {
      display: grid;
      grid-template-columns: 34% 66%;
      gap: 0;
      background: #f8fafc;
    }
    .feature-page::before {
      content: "";
      position: absolute;
      inset: 0 auto 0 0;
      width: 36%;
      background:
        linear-gradient(145deg, rgba(255,255,255,.94), rgba(241,245,249,.98)),
        #fff;
      border-right: 1px solid #e2e8f0;
    }
    .feature-copy { position: relative; z-index: 1; padding: 18mm 12mm 14mm 17mm; display: flex; flex-direction: column; min-height: 100%; }
    .kicker { color: var(--accent); font-size: 12px; font-weight: 900; letter-spacing: .14em; text-transform: uppercase; }
    .feature-no { margin-top: 16px; color: rgba(15,23,42,.08); font-size: 74px; line-height: .9; font-weight: 900; }
    .feature-copy h2 { margin: 8px 0 12px; color: #0f172a; font-size: 34px; line-height: 1.04; letter-spacing: 0; }
    .summary { margin: 0; color: #475569; font-size: 15px; line-height: 1.58; }
    .feature-copy ul { margin: 18px 0 0; padding: 0; list-style: none; display: grid; gap: 9px; }
    .feature-copy li { position: relative; padding-left: 22px; color: #1e293b; font-weight: 700; font-size: 13px; line-height: 1.35; }
    .feature-copy li::before { content: ""; position: absolute; left: 0; top: 5px; width: 10px; height: 10px; border-radius: 999px; background: var(--accent); box-shadow: 0 0 0 4px color-mix(in srgb, var(--accent), transparent 82%); }
    .route-chip { margin-top: auto; align-self: flex-start; border-radius: 999px; padding: 9px 12px; background: #0f172a; color: #e0f2fe; font-size: 11px; font-weight: 800; }
    .screen-wrap { position: relative; z-index: 1; margin: 13mm 13mm 13mm 0; border-radius: 14px; overflow: hidden; background: #0f172a; box-shadow: 0 22px 58px rgba(15,23,42,.18); border: 1px solid #cbd5e1; align-self: center; }
    .browser-bar { height: 30px; display: flex; align-items: center; gap: 7px; padding: 0 12px; background: #111827; color: #94a3b8; font-size: 10px; }
    .browser-bar span { display: block; width: 9px; height: 9px; border-radius: 50%; background: #ef4444; }
    .browser-bar span:nth-child(2) { background: #f59e0b; }
    .browser-bar span:nth-child(3) { background: #22c55e; }
    .browser-bar strong { margin-left: 7px; font-weight: 700; color: #cbd5e1; }
    .screen-wrap img { width: 100%; height: auto; display: block; object-fit: contain; object-position: top center; background: #fff; }

    .closing {
      padding: 20mm;
      color: #f8fafc;
      background:
        linear-gradient(135deg, rgba(15,23,42,.98), rgba(12,74,110,.94)),
        #0f172a;
    }
    .closing-copy { max-width: 760px; margin: 0; color: rgba(255,255,255,.76); font-size: 15px; line-height: 1.52; }
    .closing-grid { margin-top: 22px; display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .workflow-card { background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.16); border-radius: 8px; padding: 18px; }
    .closing h3 { margin: 0 0 7px; font-size: 18px; color: #fef3c7; }
    .workflow-card p { margin: 0 0 14px; color: rgba(255,255,255,.68); font-size: 12px; line-height: 1.42; }
    .workflow-steps { display: grid; gap: 8px; }
    .workflow-step { display: grid; grid-template-columns: 34px 1fr; align-items: center; gap: 10px; border-radius: 8px; background: rgba(255,255,255,.1); padding: 9px 10px; border: 1px solid rgba(255,255,255,.12); color: rgba(255,255,255,.9); font-weight: 800; font-size: 12px; line-height: 1.25; }
    .workflow-step strong { display: grid; place-items: center; width: 26px; height: 26px; border-radius: 999px; background: rgba(103,232,249,.18); color: #67e8f9; font-size: 11px; }
    .workflow-step.final { background: rgba(34,197,94,.14); border-color: rgba(74,222,128,.36); }
    .workflow-step.final strong { background: rgba(34,197,94,.22); color: #bbf7d0; }
    .closing-footer { position: absolute; left: 20mm; right: 20mm; bottom: 15mm; display: flex; justify-content: space-between; align-items: end; color: rgba(255,255,255,.68); font-size: 12px; }
    .closing-footer img { width: 58px; height: 58px; object-fit: contain; border-radius: 14px; background: #fff; padding: 7px; }
  </style>
</head>
<body>
  <section class="page cover">
    <div class="cover-main">
      <div class="brand-lockup">
        <img src="${logoUrl}" alt="SIKEREN-BLU">
        <div>
          <div class="brand-title">SIKEREN-BLU</div>
          <div class="brand-sub">Finance APT Pranoto</div>
        </div>
      </div>
      <h1>Sistem Informasi Keuangan &amp; Penagihan Terpadu</h1>
      <p class="cover-lead">Brosur fitur sistem informasi pengelolaan keuangan berbasis web: dari dashboard, kontrak, tagihan, dokumen pembayaran, sampai pembukuan dan laporan manajemen.</p>
      <div class="cover-meta">
        <span class="pill">A4 Landscape PDF</span>
        <span class="pill">Live Web Screenshots</span>
        <span class="pill">2026</span>
      </div>
    </div>
    <div class="cover-side">
      <div class="hero-shot"><img src="${features[0].imageUrl}" alt="Dashboard"></div>
      <div class="cover-note">
        ${statCard(String(features.length), 'Area fitur utama ditampilkan')}
        ${statCard('End-to-end', 'Alur pembayaran dan penagihan')}
        ${statCard('Web', 'Screenshot halaman aplikasi asli')}
      </div>
    </div>
  </section>

  <section class="page overview">
    <div class="overview-inner">
      <div class="section-label">Feature Map</div>
      <h2>Modul yang ditampilkan dalam brosur</h2>
      <p class="overview-copy">Setiap halaman berikut memakai tangkapan layar dari aplikasi lokal yang sedang berjalan, sehingga materi brosur memperlihatkan UI aktual project dan bukan mockup terpisah.</p>
      <div class="overview-grid">
        ${features.map(featureCard).join('')}
      </div>
    </div>
  </section>

  ${features.map(featurePage).join('')}

  <section class="page closing">
    <div class="closing-inner">
      <div class="section-label">Operational Value</div>
      <h2>Alur kerja ringkas dari pengajuan sampai persetujuan.</h2>
      <p class="closing-copy">Brosur ini menampilkan dua jalur operasional utama: proses pembayaran internal untuk kontrak, perjalanan dinas, dan honorarium, serta proses penagihan jasa kepada mitra dengan verifikasi berjenjang.</p>
      <div class="closing-grid">
        <div class="workflow-card">
          <h3>Pembayaran Internal</h3>
          <p>Untuk Kontrak, Perjaldin, dan Honorarium.</p>
          <div class="workflow-steps">
            <div class="workflow-step"><strong>01</strong><span>Kontrak, Perjaldin, atau Honorarium diajukan</span></div>
            <div class="workflow-step"><strong>02</strong><span>Dibuat SPP sebagai permintaan pembayaran</span></div>
            <div class="workflow-step"><strong>03</strong><span>Diproses menjadi SPM</span></div>
            <div class="workflow-step"><strong>04</strong><span>Dilanjutkan ke NPI</span></div>
            <div class="workflow-step final"><strong>05</strong><span>Terbit SP2D dan siap dicatat pembayarannya</span></div>
          </div>
        </div>
        <div class="workflow-card">
          <h3>Penagihan Jasa Mitra</h3>
          <p>Untuk tagihan jasa yang dikirim dan disetujui berjenjang.</p>
          <div class="workflow-steps">
            <div class="workflow-step"><strong>01</strong><span>Tagihan jasa ke mitra dibuat</span></div>
            <div class="workflow-step"><strong>02</strong><span>Tagihan diajukan</span></div>
            <div class="workflow-step"><strong>03</strong><span>Diverifikasi oleh Koordinator Jasa</span></div>
            <div class="workflow-step"><strong>04</strong><span>Disetujui oleh Kasi Jasa</span></div>
            <div class="workflow-step"><strong>05</strong><span>Disetujui oleh Kasubbag</span></div>
            <div class="workflow-step final"><strong>06</strong><span>Persetujuan final oleh KPA</span></div>
          </div>
        </div>
      </div>
    </div>
    <div class="closing-footer">
      <div>SIKEREN-BLU Finance APTP • Brosur Project</div>
      <img src="${logoUrl}" alt="SIKEREN-BLU">
    </div>
  </section>
</body>
</html>`;
}

async function main() {
  fs.rmSync(OUTPUT_DIR, { recursive: true, force: true });
  fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });

  console.log('Ensure brochure Mitra Jasa demo account...');
  ensureBrochureMitraAccount();

  const browser = await puppeteer.launch({
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
  });

  try {
    let page = null;
    let currentAuth = null;
    for (const feature of features) {
      const requiredAuth = feature.auth || 'admin';
      if (requiredAuth !== currentAuth) {
        if (page) {
          await page.close().catch(() => {});
        }

        page = await createCapturePage(browser);
        await clearSession(page);
        const credentials = requiredAuth === 'mitra' ? mitraLoginUser : loginUser;
        console.log(`Login as ${requiredAuth}...`);
        await login(page, credentials);
        currentAuth = requiredAuth;
      }

      console.log(`Capture ${feature.no} ${feature.title} (${feature.path})`);
      await captureFeature(page, feature);
    }

    if (page) {
      await page.close().catch(() => {});
    }

    const html = buildHtml();
    fs.writeFileSync(HTML_PATH, html, 'utf8');

    const pdfPage = await browser.newPage();
    await pdfPage.goto(fileUrl(HTML_PATH), { waitUntil: 'load', timeout: 30000 });
    await pdfPage.pdf({
      path: PDF_PATH,
      format: 'A4',
      landscape: true,
      printBackground: true,
      margin: { top: '0', right: '0', bottom: '0', left: '0' },
      preferCSSPageSize: true,
    });

    await pdfPage.screenshot({
      path: COVER_PREVIEW_PATH,
      type: 'png',
      clip: { x: 0, y: 0, width: 1123, height: 794 },
    });

    console.log(`HTML: ${HTML_PATH}`);
    console.log(`PDF: ${PDF_PATH}`);
    console.log(`Preview: ${COVER_PREVIEW_PATH}`);
  } finally {
    await browser.close();
  }
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
