{{-- ═══════════════════════════════════════════════════════════════════
     COMMAND CENTER — mode TV display (layar penuh, tanpa scroll halaman)
     Halaman berdiri sendiri (tanpa layout sidebar/topbar) agar seluruh
     panel muat dalam satu viewport. Dibuka dari dashboard Super Admin.
     ═══════════════════════════════════════════════════════════════════ --}}
@php($eventMeta = \App\Models\ActivityLog::eventMeta())
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Command Center | SIKEREN-BLU</title>
  <link rel="icon" href="{{ asset('logo/minilogo-sikeren.png') }}" type="image/png">
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Material+Icons+Outlined" rel="stylesheet">
  <style>
    :root { --bg:#0b1020; --panel:#111831; --panel2:#0e1428; --border:rgba(99,132,255,.18);
      --text:#dbe4ff; --muted:#7c8db5; --accent:#5b8cff; --accent2:#22d3ee; --good:#34d399;
      --warn:#fbbf24; --bad:#f87171; }
    * { box-sizing:border-box; margin:0; padding:0; }
    html, body { height:100%; overflow:hidden; }
    body { font-family:'Noto Sans', system-ui, sans-serif; color:var(--text);
      background: radial-gradient(1200px 500px at 80% -10%, rgba(91,140,255,.14), transparent 60%),
                  radial-gradient(900px 420px at -10% 30%, rgba(34,211,238,.10), transparent 55%),
                  var(--bg); }

    /* Grid radar + garis scanner */
    body::before { content:''; position:fixed; inset:0; pointer-events:none; z-index:0;
      background-image: linear-gradient(rgba(124,141,181,.06) 1px, transparent 1px),
                        linear-gradient(90deg, rgba(124,141,181,.06) 1px, transparent 1px);
      background-size:42px 42px;
      mask-image: radial-gradient(ellipse at 50% 0%, #000 30%, transparent 78%); }
    body::after { content:''; position:fixed; left:0; right:0; top:0; height:2px; z-index:5; pointer-events:none;
      background: linear-gradient(90deg, transparent, var(--accent2), var(--accent), transparent);
      background-size:200% 100%; animation: scan 6s linear infinite; }
    @keyframes scan { 0%{background-position:200% 0} 100%{background-position:-200% 0} }

    /* ── Kerangka layar penuh: header / KPI / panel utama ── */
    .screen { position:relative; z-index:1; height:100vh; height:100dvh; padding:1.1vh 1.1vw;
      display:grid; grid-template-rows:auto auto 1fr; gap:1.2vh; }

    /* Header */
    .hdr { display:flex; justify-content:space-between; align-items:center; gap:1rem; }
    .hdr-title { display:flex; align-items:center; gap:1rem; }
    .hdr h1 { font-size:clamp(1.2rem, 2.2vw, 2rem); font-weight:800; letter-spacing:.14em;
      background: linear-gradient(90deg,#8fb3ff,#22d3ee,#8fb3ff); background-size:200% auto;
      -webkit-background-clip:text; background-clip:text; -webkit-text-fill-color:transparent;
      animation: shimmer 5s linear infinite; }
    @keyframes shimmer { to { background-position:200% center; } }
    .hdr .sub { color:var(--muted); font-size:clamp(.6rem, .85vw, .8rem); letter-spacing:.05em; }
    .live { display:inline-flex; align-items:center; gap:.45rem; font-size:clamp(.55rem,.75vw,.72rem);
      font-weight:700; letter-spacing:.18em; color:var(--good); border:1px solid rgba(52,211,153,.35);
      background:rgba(52,211,153,.08); padding:.3rem .8rem; border-radius:999px; }
    .live .dot { width:8px; height:8px; border-radius:50%; background:var(--good); position:relative; }
    .live .dot::after { content:''; position:absolute; inset:-4px; border-radius:50%;
      border:2px solid var(--good); animation: ping 1.6s cubic-bezier(0,0,.2,1) infinite; }
    @keyframes ping { 0%{transform:scale(.6);opacity:.9} 80%,100%{transform:scale(1.8);opacity:0} }
    .clock { font-family:SFMono-Regular,Menlo,Consolas,monospace; font-size:clamp(1.2rem,2vw,1.9rem);
      font-weight:700; color:var(--accent2); text-shadow:0 0 18px rgba(34,211,238,.45); line-height:1; }
    .hdr-right { display:flex; align-items:center; gap:1rem; }
    .hdr-date { text-align:right; }
    .hdr-btn { display:inline-flex; align-items:center; justify-content:center; width:38px; height:38px;
      border-radius:11px; border:1px solid var(--border); background:rgba(17,24,49,.8); color:var(--muted);
      cursor:pointer; text-decoration:none; transition:all .2s ease; }
    .hdr-btn:hover { color:#fff; border-color:var(--accent); box-shadow:0 0 16px rgba(91,140,255,.3); }
    .hdr-btn i { font-size:20px; }

    /* Panel dasar */
    .panel { background:linear-gradient(180deg, var(--panel), var(--panel2));
      border:1px solid var(--border); border-radius:14px; overflow:hidden; position:relative;
      box-shadow:0 10px 30px rgba(3,7,18,.45); animation: rise .55s ease both;
      display:flex; flex-direction:column; min-height:0; }
    @keyframes rise { from{opacity:0; transform:translateY(14px)} to{opacity:1; transform:none} }
    .panel-head { display:flex; justify-content:space-between; align-items:center;
      padding:.85vh 1vw .3vh; flex:0 0 auto; }
    .panel-title { font-size:clamp(.55rem,.72vw,.72rem); font-weight:700; letter-spacing:.16em;
      text-transform:uppercase; color:var(--muted); }
    .panel-body { flex:1 1 auto; min-height:0; position:relative; }

    /* KPI */
    .kpis { display:grid; grid-template-columns:repeat(6, 1fr); gap:1vw; }
    .kpi { padding:1.2vh 1vw; transition:transform .25s ease, box-shadow .25s ease;
      display:flex; align-items:center; gap:.9vw; }
    .kpi:hover { transform:translateY(-3px); box-shadow:0 14px 34px rgba(91,140,255,.18); }
    .kpi::after { content:''; position:absolute; top:0; left:-60%; width:45%; height:100%;
      background:linear-gradient(100deg, transparent, rgba(255,255,255,.05), transparent);
      transform:skewX(-20deg); animation: sheen 6s ease-in-out infinite; }
    @keyframes sheen { 0%,60%{left:-60%} 85%,100%{left:130%} }
    .kpi-icon { width:clamp(34px,3.2vw,52px); height:clamp(34px,3.2vw,52px); border-radius:12px;
      display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .kpi-icon i { font-size:clamp(18px,1.7vw,27px); }
    .kpi-value { font-size:clamp(1.15rem,1.9vw,2rem); font-weight:800; font-variant-numeric:tabular-nums;
      color:#fff; line-height:1.05; }
    .kpi-label { font-size:clamp(.5rem,.68vw,.7rem); letter-spacing:.08em; text-transform:uppercase;
      color:var(--muted); margin-top:.25vh; }
    .glow-blue  { background:rgba(91,140,255,.14); color:#8fb3ff; box-shadow:0 0 22px rgba(91,140,255,.25); }
    .glow-cyan  { background:rgba(34,211,238,.12); color:#67e8f9; box-shadow:0 0 22px rgba(34,211,238,.22); }
    .glow-green { background:rgba(52,211,153,.12); color:#6ee7b7; box-shadow:0 0 22px rgba(52,211,153,.22); }
    .glow-red   { background:rgba(248,113,113,.12); color:#fca5a5; box-shadow:0 0 22px rgba(248,113,113,.22); }
    .glow-amber { background:rgba(251,191,36,.12); color:#fcd34d; box-shadow:0 0 22px rgba(251,191,36,.2); }
    .glow-violet{ background:rgba(167,139,250,.13); color:#c4b5fd; box-shadow:0 0 22px rgba(167,139,250,.22); }

    /* Area utama: kiri (tren + top user), kanan (donut + live feed) */
    .main { display:grid; grid-template-columns:1.9fr 1.1fr; grid-template-rows:1fr 1.15fr; gap:1.2vh 1vw; min-height:0; }
    .p-trend { grid-column:1; grid-row:1; animation-delay:.08s; }
    .p-top   { grid-column:1; grid-row:2; animation-delay:.16s; }
    .p-donut { grid-column:2; grid-row:1; animation-delay:.12s; }
    .p-feed  { grid-column:2; grid-row:2; animation-delay:.2s; }

    /* Badge */
    .badge { font-size:clamp(.5rem,.62vw,.64rem); font-weight:700; letter-spacing:.06em;
      padding:.24rem .55rem; border-radius:999px; text-transform:uppercase; white-space:nowrap; }
    .b-success  { background:rgba(52,211,153,.14);  color:#6ee7b7; border:1px solid rgba(52,211,153,.3); }
    .b-secondary{ background:rgba(124,141,181,.14); color:#aab8d8; border:1px solid rgba(124,141,181,.3); }
    .b-danger   { background:rgba(248,113,113,.14); color:#fca5a5; border:1px solid rgba(248,113,113,.3); }
    .b-primary  { background:rgba(91,140,255,.14);  color:#8fb3ff; border:1px solid rgba(91,140,255,.3); }
    .b-warning  { background:rgba(251,191,36,.14);  color:#fcd34d; border:1px solid rgba(251,191,36,.3); }
    .b-info     { background:rgba(34,211,238,.14);  color:#67e8f9; border:1px solid rgba(34,211,238,.3); }

    /* Live feed — hanya panel ini yang boleh scroll (internal), halaman tidak */
    .feed { position:absolute; inset:0; overflow-y:auto; scrollbar-width:thin;
      scrollbar-color:#2c3a63 transparent; }
    .feed::-webkit-scrollbar { width:5px; }
    .feed::-webkit-scrollbar-thumb { background:#2c3a63; border-radius:99px; }
    .feed-item { display:flex; gap:.7vw; padding:.9vh 1vw; border-bottom:1px solid rgba(124,141,181,.08); }
    .feed-item.new { animation: slidein .45s ease both; }
    @keyframes slidein { from{opacity:0; transform:translateX(18px); background:rgba(34,211,238,.12)}
      to{opacity:1; transform:none; background:transparent} }
    .feed-icon { flex:0 0 auto; width:clamp(26px,2vw,34px); height:clamp(26px,2vw,34px); border-radius:9px;
      display:flex; align-items:center; justify-content:center; }
    .feed-icon i { font-size:clamp(14px,1.1vw,18px); }
    .feed-desc { font-size:clamp(.65rem,.82vw,.84rem); line-height:1.35; word-break:break-word; }
    .feed-meta { font-size:clamp(.55rem,.68vw,.7rem); color:var(--muted); margin-top:.3vh;
      font-family:SFMono-Regular,Menlo,Consolas,monospace; }
    .feed-empty { position:absolute; inset:0; display:flex; align-items:center; justify-content:center;
      color:var(--muted); font-size:.85rem; }
    .feed-row-top { display:flex; justify-content:space-between; align-items:flex-start; gap:.5vw; }

    @media (max-width:1100px) {
      html, body { overflow:auto; } /* layar kecil (bukan TV): izinkan scroll agar tetap terbaca */
      .screen { height:auto; }
      .kpis { grid-template-columns:repeat(3,1fr); }
      .main { grid-template-columns:1fr; grid-template-rows:repeat(4, 320px); }
      .p-trend{grid-column:1;grid-row:1} .p-donut{grid-column:1;grid-row:2}
      .p-top{grid-column:1;grid-row:3} .p-feed{grid-column:1;grid-row:4}
    }
  </style>
</head>
<body>
<div class="screen">

  {{-- ════════ HEADER ════════ --}}
  <div class="hdr">
    <div class="hdr-title">
      <img src="{{ asset('logo/minilogo-sikeren.png') }}" alt="" style="height:clamp(28px,3vw,44px);">
      <div>
        <div style="display:flex; align-items:center; gap:.9rem;">
          <h1>COMMAND CENTER</h1>
          <span class="live"><span class="dot"></span> LIVE</span>
        </div>
        <div class="sub">Pusat audit &amp; pemantauan seluruh aktivitas pengguna — SIKEREN-BLU</div>
      </div>
    </div>
    <div class="hdr-right">
      <div class="hdr-date">
        <div class="clock" id="ccClock">--:--:--</div>
        <div class="sub">{{ now()->translatedFormat('l, d F Y') }}</div>
      </div>
      <button type="button" class="hdr-btn" id="btnFullscreen" title="Layar penuh (F)">
        <i class="material-icons-outlined" id="icFullscreen">fullscreen</i>
      </button>
      <a href="{{ route('dashboard') }}" class="hdr-btn" title="Kembali ke Dashboard">
        <i class="material-icons-outlined">logout</i>
      </a>
    </div>
  </div>

  {{-- ════════ KPI ════════ --}}
  <div class="kpis">
    @php($kpis = [
      ['label' => 'Total Aktivitas',     'value' => $stats['total'],           'icon' => 'insights', 'glow' => 'glow-blue'],
      ['label' => 'Aktivitas Hari Ini',  'value' => $stats['hari_ini'],        'icon' => 'today',    'glow' => 'glow-cyan',  'id' => 'kpiHariIni'],
      ['label' => 'User Aktif Hari Ini', 'value' => $stats['user_aktif'],      'icon' => 'group',    'glow' => 'glow-violet'],
      ['label' => 'Online (5 mnt)',      'value' => $stats['online'],          'icon' => 'sensors',  'glow' => 'glow-green', 'id' => 'kpiOnline'],
      ['label' => 'Mutasi Data Hari Ini','value' => $stats['mutasi_hari_ini'], 'icon' => 'sync_alt', 'glow' => 'glow-amber'],
      ['label' => 'Login Gagal (7 hari)','value' => $stats['login_gagal_7d'],  'icon' => 'gpp_maybe','glow' => 'glow-red'],
    ])
    @foreach($kpis as $i => $kpi)
      <div class="panel kpi" style="animation-delay:{{ $i * 60 }}ms">
        <div class="kpi-icon {{ $kpi['glow'] }}"><i class="material-icons-outlined">{{ $kpi['icon'] }}</i></div>
        <div>
          <div class="kpi-value countup" @isset($kpi['id']) id="{{ $kpi['id'] }}" @endisset data-target="{{ $kpi['value'] }}">0</div>
          <div class="kpi-label">{{ $kpi['label'] }}</div>
        </div>
      </div>
    @endforeach
  </div>

  {{-- ════════ PANEL UTAMA ════════ --}}
  <div class="main">
    <div class="panel p-trend">
      <div class="panel-head">
        <span class="panel-title">Tren Aktivitas — 14 Hari Terakhir</span>
        <span class="badge b-info">harian</span>
      </div>
      <div class="panel-body"><div id="ccTrendChart" style="position:absolute;inset:0 6px 0 0;"></div></div>
    </div>

    <div class="panel p-donut">
      <div class="panel-head">
        <span class="panel-title">Komposisi Jenis Aktivitas</span>
        <span class="badge b-primary">30 hari</span>
      </div>
      <div class="panel-body"><div id="ccDonutChart" style="position:absolute;inset:4px;"></div></div>
    </div>

    <div class="panel p-top">
      <div class="panel-head">
        <span class="panel-title">Top User Teraktif</span>
        <span class="badge b-primary">30 hari</span>
      </div>
      <div class="panel-body"><div id="ccTopUserChart" style="position:absolute;inset:0 6px 0 0;"></div></div>
    </div>

    <div class="panel p-feed">
      <div class="panel-head">
        <span class="panel-title">Live Activity Feed</span>
        <span class="sub" style="color:var(--muted);font-size:clamp(.55rem,.7vw,.72rem);">
          <i class="material-icons-outlined" style="font-size:13px;vertical-align:-2px;">autorenew</i> auto-refresh 8 detik
        </span>
      </div>
      <div class="panel-body">
        <div class="feed" id="ccFeed"></div>
        <div class="feed-empty" id="ccFeedEmpty">Memuat aktivitas…</div>
      </div>
    </div>
  </div>

</div>

<script src="{{ URL::asset('build/plugins/apexchart/apexcharts.min.js') }}"></script>
<script>
(function () {
  'use strict';

  /* ── Jam digital ── */
  const clockEl = document.getElementById('ccClock');
  function tick() { clockEl.textContent = new Date().toLocaleTimeString('id-ID', { hour12: false }); }
  tick(); setInterval(tick, 1000);

  /* ── Tombol & shortcut fullscreen (untuk TV) ── */
  const btnFs = document.getElementById('btnFullscreen');
  const icFs = document.getElementById('icFullscreen');
  function toggleFs() {
    if (document.fullscreenElement) document.exitFullscreen();
    else document.documentElement.requestFullscreen().catch(() => {});
  }
  btnFs.addEventListener('click', toggleFs);
  document.addEventListener('keydown', e => { if (e.key === 'f' || e.key === 'F') toggleFs(); });
  document.addEventListener('fullscreenchange', () => {
    icFs.textContent = document.fullscreenElement ? 'fullscreen_exit' : 'fullscreen';
  });

  /* ── Count-up KPI ── */
  document.querySelectorAll('.countup').forEach(function (el) {
    const target = parseInt(el.dataset.target || '0', 10);
    const dur = 1200, start = performance.now();
    function step(now) {
      const p = Math.min((now - start) / dur, 1);
      const eased = 1 - Math.pow(1 - p, 3);
      el.textContent = Math.round(target * eased).toLocaleString('id-ID');
      if (p < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  });

  const MUTED = '#7c8db5', GRID = 'rgba(124,141,181,.12)';
  const baseChart = {
    chart: { background: 'transparent', foreColor: MUTED, toolbar: { show: false },
      animations: { speed: 900, easing: 'easeoutcubic' }, parentHeightOffset: 0 },
    tooltip: { theme: 'dark' },
    grid: { borderColor: GRID, strokeDashArray: 4 },
  };

  /* ── Tren area chart ── */
  const trend = @json($trend);
  new ApexCharts(document.querySelector('#ccTrendChart'), Object.assign({}, baseChart, {
    chart: Object.assign({}, baseChart.chart, { type: 'area', height: '100%' }),
    series: [{ name: 'Aktivitas', data: trend.map(t => t.jumlah) }],
    xaxis: { categories: trend.map(t => t.label), labels: { style: { colors: MUTED, fontSize: '11px' } },
      axisBorder: { show: false }, axisTicks: { show: false } },
    yaxis: { labels: { style: { colors: MUTED } } },
    colors: ['#5b8cff'],
    stroke: { curve: 'smooth', width: 3 },
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: .45, opacityTo: .02, stops: [0, 95] } },
    dataLabels: { enabled: false },
    markers: { size: 0, hover: { size: 6 } },
  })).render();

  /* ── Donut komposisi event ── */
  const byEvent = @json($byEvent);
  const eventMeta = @json($eventMeta);
  const donutColors = { success:'#34d399', secondary:'#94a3b8', danger:'#f87171', primary:'#5b8cff', warning:'#fbbf24', info:'#22d3ee' };
  new ApexCharts(document.querySelector('#ccDonutChart'), Object.assign({}, baseChart, {
    chart: Object.assign({}, baseChart.chart, { type: 'donut', height: '100%' }),
    series: byEvent.map(e => e.jumlah),
    labels: byEvent.map(e => (eventMeta[e.event] || { label: e.event }).label),
    colors: byEvent.map(e => donutColors[(eventMeta[e.event] || { color: 'secondary' }).color] || '#94a3b8'),
    legend: { position: 'bottom', labels: { colors: MUTED } },
    dataLabels: { enabled: true, style: { fontSize: '11px' } },
    stroke: { colors: ['#111831'], width: 3 },
    plotOptions: { pie: { donut: { size: '68%', labels: { show: true,
      total: { show: true, label: 'Total', color: MUTED, formatter: w => w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString('id-ID') },
      value: { color: '#fff', fontSize: '20px', fontWeight: 800 } } } } },
    noData: { text: 'Belum ada data', style: { color: MUTED } },
  })).render();

  /* ── Bar top user ── */
  const topUsers = @json($topUsers);
  new ApexCharts(document.querySelector('#ccTopUserChart'), Object.assign({}, baseChart, {
    chart: Object.assign({}, baseChart.chart, { type: 'bar', height: '100%' }),
    series: [{ name: 'Aktivitas', data: topUsers.map(u => u.jumlah) }],
    xaxis: { categories: topUsers.map(u => u.user_name || ('User #' + u.user_id)),
      labels: { style: { colors: MUTED } } },
    yaxis: { labels: { style: { colors: MUTED, fontSize: '12px' } } },
    colors: ['#22d3ee'],
    plotOptions: { bar: { horizontal: true, borderRadius: 6, barHeight: '55%',
      colors: { backgroundBarColors: ['rgba(124,141,181,.06)'], backgroundBarRadius: 6 } } },
    fill: { type: 'gradient', gradient: { shade: 'dark', type: 'horizontal', gradientToColors: ['#5b8cff'], stops: [0, 100] } },
    dataLabels: { enabled: true, style: { fontSize: '11px', colors: ['#fff'] }, offsetX: 6 },
    noData: { text: 'Belum ada data', style: { color: MUTED } },
  })).render();

  /* ── Live feed (polling) ── */
  const feedEl = document.getElementById('ccFeed');
  const feedEmpty = document.getElementById('ccFeedEmpty');
  let lastId = 0, firstLoad = true;
  const glowByColor = { success:'glow-green', secondary:'glow-blue', danger:'glow-red',
    primary:'glow-blue', warning:'glow-amber', info:'glow-cyan' };

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, c =>
      ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' })[c]);
  }

  function renderItem(item, isNew) {
    const div = document.createElement('div');
    div.className = 'feed-item' + (isNew ? ' new' : '');
    div.innerHTML =
      '<div class="feed-icon ' + (glowByColor[item.color] || 'glow-blue') + '">' +
        '<i class="material-icons-outlined">' + esc(item.icon) + '</i></div>' +
      '<div style="flex:1;min-width:0;">' +
        '<div class="feed-row-top">' +
          '<span class="feed-desc"><strong style="color:#fff;">' + esc(item.user) + '</strong> — ' + esc(item.description || item.label) + '</span>' +
          '<span class="badge b-' + esc(item.color) + '">' + esc(item.label) + '</span>' +
        '</div>' +
        '<div class="feed-meta">' + esc(item.waktu) + ' · ' + esc(item.relatif) +
          (item.ip ? ' · IP ' + esc(item.ip) : '') + (item.modul ? ' · ' + esc(item.modul) : '') + '</div>' +
      '</div>';
    return div;
  }

  function fetchFeed() {
    fetch('{{ route('command-center.feed') }}?after_id=' + lastId, { headers: { 'Accept': 'application/json' } })
      .then(r => r.json())
      .then(data => {
        if (data.items.length > 0) {
          feedEmpty && feedEmpty.remove();
          // items datang terbaru → terlama; sisipkan dari yang terlama agar urutan benar
          data.items.slice().reverse().forEach(item => {
            feedEl.prepend(renderItem(item, !firstLoad));
          });
          lastId = Math.max(lastId, data.last_id);
          while (feedEl.children.length > 60) feedEl.removeChild(feedEl.lastChild);
        } else if (firstLoad && feedEmpty) {
          feedEmpty.textContent = 'Belum ada aktivitas terekam.';
        }
        const hariIni = document.getElementById('kpiHariIni');
        const online = document.getElementById('kpiOnline');
        if (hariIni) hariIni.textContent = Number(data.stats.hari_ini).toLocaleString('id-ID');
        if (online) online.textContent = Number(data.stats.online).toLocaleString('id-ID');
        firstLoad = false;
      })
      .catch(() => { /* koneksi gagal: coba lagi pada interval berikutnya */ });
  }
  fetchFeed();
  setInterval(fetchFeed, 8000);

  /* ── Mode TV: chart & statistik ikut segar — muat ulang halaman tiap 10 menit ── */
  setTimeout(() => window.location.reload(), 10 * 60 * 1000);
})();
</script>
</body>
</html>
